/**
 * Dark-mode smoke checks for the Admin, Faculty and Student dashboards.
 *
 * Every dashboard must load in dark mode, paint its main surfaces dark, keep
 * the dark design tokens active, and do so without writing a console error or
 * throwing an uncaught exception. The smoke test runs at the standard Playwright
 * widths plus an explicit 900px case for the sidebar breakpoint.
 */

import { expect, test } from '@playwright/test';
import { authStateFile, dashboards } from './support/coverage.mjs';

const ROLES = ['admin', 'instructor', 'student'];

/** Surfaces that make the page read as dark rather than light. */
const SURFACE_SELECTORS = ['.main', '.content', '.dash-panel', '.topbar'];

const MAX_DARK_LUMINANCE = 0.2;

async function readDarkSurfaces(page) {
    return page.evaluate(
        ({ surfaceSelectors, maxLuminance }) => {
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
                if (!match) throw new Error(`Cannot parse colour ${value}`);

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

            function luminance(color) {
                const channels = [color.r, color.g, color.b].map((channel) => {
                    const value = channel / 255;
                    return value <= 0.03928 ? value / 12.92 : ((value + 0.055) / 1.055) ** 2.4;
                });

                return 0.2126 * channels[0] + 0.7152 * channels[1] + 0.0722 * channels[2];
            }

            function visible(element) {
                const style = getComputedStyle(element);
                const rect = element.getBoundingClientRect();

                return (
                    style.display !== 'none' &&
                    style.visibility !== 'hidden' &&
                    Number(style.opacity) > 0 &&
                    rect.width > 0 &&
                    rect.height > 0
                );
            }

            const bodyStyle = getComputedStyle(document.body);
            const bodyBackground = parseColor(bodyStyle.backgroundColor);
            const surfaces = [];

            for (const selector of surfaceSelectors) {
                const element = [...document.querySelectorAll(selector)].find(visible);

                if (!element) {
                    surfaces.push({ selector, missing: true, dark: false });
                    continue;
                }

                const background = getComputedStyle(element).backgroundColor;
                const painted = composite(parseColor(background), bodyBackground);
                const ratio = luminance(painted);

                surfaces.push({
                    selector,
                    missing: false,
                    luminance: Number(ratio.toFixed(4)),
                    dark: ratio <= maxLuminance,
                });
            }

            return {
                bodyDark: luminance(bodyBackground) <= maxLuminance,
                variables: {
                    background: bodyStyle.getPropertyValue('--dash-bg').trim(),
                    surface: bodyStyle.getPropertyValue('--dash-surface').trim(),
                    text: bodyStyle.getPropertyValue('--dash-text').trim(),
                },
                surfaces,
            };
        },
        { surfaceSelectors: SURFACE_SELECTORS, maxLuminance: MAX_DARK_LUMINANCE },
    );
}

async function expectDarkDashboard(page, dashboard) {
    const consoleErrors = [];
    const pageErrors = [];

    page.on('console', (message) => {
        if (message.type() === 'error') consoleErrors.push(message.text());
    });
    page.on('pageerror', (error) => pageErrors.push(error.message));

    await page.addInitScript(() => localStorage.setItem('bcp-theme', 'dark'));

    const response = await page.goto(dashboard.path, { waitUntil: 'domcontentloaded' });
    expect(response?.status(), `GET ${dashboard.path}`).toBe(200);
    expect(new URL(page.url()).pathname, 'the dashboard should not bounce to sign-in').toBe(
        dashboard.path,
    );
    await expect(
        page.getByText(dashboard.marker, { exact: false }).first(),
        `${dashboard.path} did not render its dashboard content`,
    ).toBeVisible();
    await expect
        .poll(() => page.evaluate(() => document.body.classList.contains('light-mode')))
        .toBe(false);

    await page.evaluate(() => document.fonts?.ready);
    await page.waitForTimeout(250);

    const dark = await readDarkSurfaces(page);

    expect(dark.bodyDark, 'body background should be dark').toBe(true);
    expect(
        Object.entries(dark.variables).filter(([, value]) => value === ''),
        'missing dark design tokens',
    ).toEqual([]);
    expect(
        dark.surfaces.filter((surface) => !surface.dark),
        'missing or non-dark dashboard surfaces',
    ).toEqual([]);

    expect(consoleErrors, `${dashboard.path} console errors`).toEqual([]);
    expect(pageErrors, `${dashboard.path} uncaught page errors`).toEqual([]);
}

for (const role of ROLES) {
    const dashboard = dashboards.find((entry) => entry.role === role);

    if (!dashboard) {
        throw new Error(`coverage.json is missing the ${role} dashboard`);
    }

    test.describe(`${dashboard.label} dark-mode smoke`, () => {
        test.use({ storageState: authStateFile(role) });

        test('loads dark surfaces without console errors', async ({ page }) => {
            await expectDarkDashboard(page, dashboard);
        });

        test('stays dark at the 900px sidebar breakpoint', async ({ page }, testInfo) => {
            test.skip(testInfo.project.name !== 'desktop', 'the 900px case is checked once');

            await page.setViewportSize({ width: 900, height: 1000 });
            await expectDarkDashboard(page, dashboard);

            expect(
                await page.evaluate(() => document.documentElement.clientWidth),
                'the sidebar breakpoint should be measured at exactly 900px',
            ).toBe(900);
        });
    });
}
