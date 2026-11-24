/**
 * Browser-level contrast checks for the role dashboards.
 *
 * The PHP contrast suite validates the design tokens at the source level. This
 * spec checks what the browser actually computes: DOM text colours composited
 * over their real backgrounds, plus Chart.js legend/tick colours drawn on the
 * chart container's rendered surface. It runs dark -> light -> dark.
 */

import { expect, test } from '@playwright/test';
import { authStateFile, dashboards } from './support/coverage.mjs';

const MIN_TEXT_CONTRAST = 4.5;

const selectorsByRole = {
    admin: [
        '.page-title',
        '.dash-section h3',
        '.dash-section-kicker',
        '.dash-panel-head h4',
        '.view-all',
    ],
    instructor: [
        '.page-title',
        '.dash-panel-head h4',
        '.dash-section-kicker',
        '.user-stat-label',
        '.user-stat-foot',
        '.view-all',
    ],
    student: [
        '.page-title',
        '.dash-panel-head h4',
        '.dash-section-kicker',
        '.user-stat-label',
        '.user-stat-foot',
        '.view-all',
    ],
    aa_staff: [
        '.page-title',
        '.dash-section h3',
        '.dash-section-kicker',
        '.dash-panel-head h4',
        '.view-all',
        '.dash-link-list a',
    ],
};

async function setTheme(page, light) {
    const isLight = await page.evaluate(() => document.body.classList.contains('light-mode'));

    if (isLight !== light) {
        await page.locator('#themeToggle').click();
    }

    await expect
        .poll(() => page.evaluate(() => document.body.classList.contains('light-mode')))
        .toBe(light);

    // The chart layer repaints from a body-class MutationObserver.
    await page.waitForTimeout(100);
}

async function waitForMountedChart(page) {
    await page.waitForFunction(
        () => {
            const canvases = [...document.querySelectorAll('.chart-container canvas')];

            return canvases.some((canvas) => Boolean(window.Chart?.getChart?.(canvas)));
        },
        { timeout: 20_000 },
    );
}

/**
 * Evaluate a page's visible dashboard text and chart text against the
 * backgrounds actually painted behind them. Colours are composited through
 * translucent ancestors instead of assuming the page surface is opaque.
 */
async function collectContrastSamples(page, selectors) {
    return page.evaluate(
        ({ selectors, minimum }) => {
            function parseColor(value) {
                const canvas = document.createElement('canvas');
                const context = canvas.getContext('2d');
                context.fillStyle = '#000000';
                context.fillStyle = value;
                const normalized = context.fillStyle;

                if (normalized.startsWith('#')) {
                    const hex = normalized.slice(1);

                    if (hex.length === 3) {
                        return {
                            r: parseInt(hex[0] + hex[0], 16),
                            g: parseInt(hex[1] + hex[1], 16),
                            b: parseInt(hex[2] + hex[2], 16),
                            a: 1,
                        };
                    }

                    return {
                        r: parseInt(hex.slice(0, 2), 16),
                        g: parseInt(hex.slice(2, 4), 16),
                        b: parseInt(hex.slice(4, 6), 16),
                        a: 1,
                    };
                }

                const match = normalized.match(/rgba?\(([^)]+)\)/);

                if (!match) {
                    throw new Error(`Could not parse computed colour: ${value} -> ${normalized}`);
                }

                const parts = match[1].split(',').map((part) => Number.parseFloat(part.trim()));

                return {
                    r: parts[0],
                    g: parts[1],
                    b: parts[2],
                    a: parts.length > 3 ? parts[3] : 1,
                };
            }

            function composite(foreground, background) {
                const alpha = foreground.a + background.a * (1 - foreground.a);

                if (alpha === 0) return { r: 0, g: 0, b: 0, a: 0 };

                return {
                    r: (foreground.r * foreground.a + background.r * background.a * (1 - foreground.a)) / alpha,
                    g: (foreground.g * foreground.a + background.g * background.a * (1 - foreground.a)) / alpha,
                    b: (foreground.b * foreground.a + background.b * background.a * (1 - foreground.a)) / alpha,
                    a: alpha,
                };
            }

            function backgroundFor(element) {
                const chain = [];

                for (let node = element; node; node = node.parentElement) {
                    chain.unshift(node);
                }

                let background = { r: 255, g: 255, b: 255, a: 1 };

                for (const node of chain) {
                    background = composite(
                        parseColor(getComputedStyle(node).backgroundColor),
                        background,
                    );
                }

                return background;
            }

            function relativeLuminance(color) {
                const channels = [color.r, color.g, color.b].map((channel) => {
                    const value = channel / 255;

                    return value <= 0.03928 ? value / 12.92 : ((value + 0.055) / 1.055) ** 2.4;
                });

                return 0.2126 * channels[0] + 0.7152 * channels[1] + 0.0722 * channels[2];
            }

            function contrast(foreground, background) {
                const fg = relativeLuminance(foreground);
                const bg = relativeLuminance(background);

                return (Math.max(fg, bg) + 0.05) / (Math.min(fg, bg) + 0.05);
            }

            function isVisible(element) {
                const style = getComputedStyle(element);
                const rect = element.getBoundingClientRect();

                return (
                    style.display !== 'none' &&
                    style.visibility !== 'hidden' &&
                    Number(style.opacity) > 0 &&
                    rect.width > 0 &&
                    rect.height > 0 &&
                    element.textContent.trim().length > 0
                );
            }

            function addSample(samples, label, color, background, kind) {
                const foreground = composite(parseColor(color), background);
                const ratio = contrast(foreground, background);

                samples.push({
                    kind,
                    label,
                    color,
                    background: `rgb(${Math.round(background.r)}, ${Math.round(background.g)}, ${Math.round(background.b)})`,
                    ratio: Number(ratio.toFixed(2)),
                });

                if (ratio < minimum) {
                    samples[samples.length - 1].failure = true;
                }
            }

            const samples = [];

            for (const selector of selectors) {
                const element = [...document.querySelectorAll(selector)].find(isVisible);

                if (!element) continue;

                const style = getComputedStyle(element);
                addSample(
                    samples,
                    selector,
                    style.color,
                    backgroundFor(element),
                    'dashboard text',
                );
            }

            const seenCharts = new Set();

            for (const canvas of document.querySelectorAll('.chart-container canvas')) {
                const chart = window.Chart?.getChart?.(canvas);

                if (!chart || seenCharts.has(chart)) continue;

                seenCharts.add(chart);

                const background = backgroundFor(canvas);
                const legend = chart.options.plugins?.legend;

                if (legend && legend.display !== false && legend.labels?.color) {
                    addSample(
                        samples,
                        `${chart.canvas.id || chart.config.type} legend`,
                        legend.labels.color,
                        background,
                        'chart text',
                    );
                }

                for (const [axis, scale] of Object.entries(chart.options.scales || {})) {
                    if (scale?.ticks?.display !== false && scale?.ticks?.color) {
                        addSample(
                            samples,
                            `${chart.canvas.id || chart.config.type} ${axis} ticks`,
                            scale.ticks.color,
                            background,
                            'chart text',
                        );
                    }
                }
            }

            return {
                samples,
                failures: samples.filter((sample) => sample.failure),
                dashboardSamples: samples.filter((sample) => sample.kind === 'dashboard text').length,
                chartSamples: samples.filter((sample) => sample.kind === 'chart text').length,
            };
        },
        { selectors, minimum: MIN_TEXT_CONTRAST },
    );
}

for (const dashboard of dashboards) {
    const selectors = selectorsByRole[dashboard.role];

    if (!selectors) {
        throw new Error(`No contrast selectors configured for ${dashboard.role}`);
    }

    test.describe(`${dashboard.label} browser contrast`, () => {
        test.use({ storageState: authStateFile(dashboard.role) });

        test('renders dashboard and chart text above 4.5:1 in both themes', async ({ page }, testInfo) => {
            test.skip(testInfo.project.name !== 'desktop', 'browser contrast is checked at desktop width');

            await page.addInitScript(() => localStorage.setItem('bcp-theme', 'dark'));

            const response = await page.goto(dashboard.path, { waitUntil: 'domcontentloaded' });
            expect(response?.status(), `GET ${dashboard.path}`).toBe(200);
            await expect(
                page.getByText(dashboard.marker, { exact: false }).first(),
                `${dashboard.path} did not render its dashboard content`,
            ).toBeVisible();

            await page.evaluate(() => document.fonts?.ready);
            await waitForMountedChart(page);

            for (const theme of ['dark', 'light', 'dark']) {
                await setTheme(page, theme === 'light');

                const result = await collectContrastSamples(page, selectors);

                expect(result.dashboardSamples, `${theme}: expected dashboard text samples`).toBeGreaterThanOrEqual(3);
                expect(result.chartSamples, `${theme}: expected chart text samples`).toBeGreaterThanOrEqual(1);
                expect(result.failures, `${theme}: contrast failures`).toEqual([]);
            }
        });
    });
}
