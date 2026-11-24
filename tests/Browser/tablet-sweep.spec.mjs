/**
 * Permanent tablet sweep for the four role dashboards.
 *
 * Loads each dashboard once, then resizes through the sidebar/tablet range.
 * At every width it checks the layout problems that are easy to miss between
 * the phone breakpoint and a full desktop:
 *   - page-level horizontal overflow
 *   - chart canvases clipped by their container/card
 *   - hero copy or action buttons overlapping
 *   - analytics controls escaping their panel header
 */

import { expect, test } from '@playwright/test';
import { authStateFile, dashboards, tolerancePx } from './support/coverage.mjs';

// 32px steps through the tablet range, plus both sides of the 900px sidebar
// breakpoint so the fixed-sidebar/content transition is covered.
const TABLET_WIDTHS = [768, 800, 832, 864, 896, 900, 901, 928, 960, 992, 1024];

async function settleLayout(page, width) {
    await page.waitForFunction(
        (expected) => document.documentElement.clientWidth === expected,
        width,
    );

    await page.evaluate(
        () =>
            new Promise((resolve) => {
                requestAnimationFrame(() =>
                    requestAnimationFrame(() => setTimeout(resolve, 60)),
                );
            }),
    );
}

async function waitForCharts(page) {
    await page.waitForFunction(
        () => {
            const visible = (element) => {
                const style = getComputedStyle(element);
                const rect = element.getBoundingClientRect();

                return (
                    style.display !== 'none' &&
                    style.visibility !== 'hidden' &&
                    Number(style.opacity) > 0 &&
                    rect.width > 0 &&
                    rect.height > 0
                );
            };

            const stillLoading = [...document.querySelectorAll('.chart-placeholder')]
                .filter(visible)
                .some((placeholder) => /loading/i.test(placeholder.textContent));

            return (
                !stillLoading &&
                [...document.querySelectorAll('.chart-container canvas')].some((canvas) =>
                    Boolean(window.Chart?.getChart?.(canvas)),
                )
            );
        },
        { timeout: 20_000 },
    );

    await page.waitForTimeout(250);
}

async function collectTabletProblems(page, width) {
    return page.evaluate(
        ({ width: viewportWidth, tolerance }) => {
            const problems = [];
            const box = (element) => {
                const rect = element.getBoundingClientRect();

                return {
                    left: rect.left,
                    right: rect.right,
                    top: rect.top,
                    bottom: rect.bottom,
                    width: rect.width,
                    height: rect.height,
                };
            };
            const visible = (element) => {
                if (!element) return false;

                const style = getComputedStyle(element);
                const rect = element.getBoundingClientRect();

                return (
                    style.display !== 'none' &&
                    style.visibility !== 'hidden' &&
                    Number(style.opacity) > 0 &&
                    rect.width > 0 &&
                    rect.height > 0
                );
            };
            const within = (inner, outer) =>
                inner.left >= outer.left - tolerance &&
                inner.right <= outer.right + tolerance &&
                inner.top >= outer.top - tolerance &&
                inner.bottom <= outer.bottom + tolerance;
            const overlaps = (a, b) =>
                a.left < b.right - tolerance &&
                a.right > b.left + tolerance &&
                a.top < b.bottom - tolerance &&
                a.bottom > b.top + tolerance;
            const name = (element) =>
                element.id || element.className || element.tagName.toLowerCase();
            const add = (check, message, details = {}) => {
                problems.push({ width: viewportWidth, check, message, ...details });
            };

            const viewport = document.documentElement.clientWidth;
            const documentWidth = document.documentElement.scrollWidth;
            const bodyWidth = document.body.scrollWidth;

            if (documentWidth > viewport + tolerance) {
                add('horizontal-overflow', `document is ${documentWidth - viewport}px wider than the viewport`);
            }

            if (bodyWidth > viewport + tolerance) {
                add('horizontal-overflow', `body is ${bodyWidth - viewport}px wider than the viewport`);
            }

            const content = document.querySelector('.main > .content') ?? document.querySelector('.content');
            if (content) {
                const contentBox = box(content);

                if (contentBox.left < -tolerance || contentBox.right > viewport + tolerance) {
                    add('content-escape', 'content column extends past the viewport', { content: contentBox });
                }
            }

            // Hero copy and its actions must remain inside the hero and must
            // not collide after the hero switches to a stacked layout.
            const hero = [...document.querySelectorAll('.user-hero')].find(visible);
            if (hero) {
                const heroBox = box(hero);
                const copy = hero.querySelector('.user-hero-copy');
                const actions = hero.querySelector('.user-hero-actions');

                if (heroBox.left < -tolerance || heroBox.right > viewport + tolerance) {
                    add('hero-escape', 'hero extends past the viewport', { hero: heroBox });
                }

                if (copy && visible(copy)) {
                    const copyBox = box(copy);
                    if (!within(copyBox, heroBox)) {
                        add('hero-overlap', 'hero copy escapes the hero', { copy: copyBox });
                    }

                    if (actions && visible(actions)) {
                        const actionsBox = box(actions);
                        if (overlaps(copyBox, actionsBox)) {
                            add('hero-overlap', 'hero copy overlaps the action row', {
                                copy: copyBox,
                                actions: actionsBox,
                            });
                        }
                    }
                }

                if (actions && visible(actions)) {
                    const actionsBox = box(actions);
                    const actionItems = [...actions.children].filter(visible);

                    if (!within(actionsBox, heroBox)) {
                        add('hero-overlap', 'hero actions escape the hero', { actions: actionsBox });
                    }

                    actionItems.forEach((item) => {
                        const itemBox = box(item);
                        if (!within(itemBox, heroBox)) {
                            add('hero-overlap', `${name(item)} escapes the hero`, { item: itemBox, hero: heroBox });
                        }
                    });

                    for (let first = 0; first < actionItems.length; first += 1) {
                        for (let second = first + 1; second < actionItems.length; second += 1) {
                            const firstBox = box(actionItems[first]);
                            const secondBox = box(actionItems[second]);

                            if (overlaps(firstBox, secondBox)) {
                                add('hero-overlap', `${name(actionItems[first])} overlaps ${name(actionItems[second])}`, {
                                    first: firstBox,
                                    second: secondBox,
                                });
                            }
                        }
                    }
                }
            }

            // Analytics filters should stay inside the panel head at every
            // tablet width. This covers the sidebar range where the content
            // column is narrower than a normal desktop.
            const filterHeaders = [...document.querySelectorAll('.dash-panel-head')]
                .filter(visible)
                .filter((header) => header.querySelector('.analytics-controls'));

            filterHeaders.forEach((header) => {
                const controls = header.querySelector('.analytics-controls');
                const headerBox = box(header);
                const controlsBox = box(controls);
                const controlItems = [...controls.querySelectorAll('select, button, a')].filter(visible);

                if (!within(controlsBox, headerBox)) {
                    add('filter-escape', 'analytics controls escape their panel header', {
                        controls: controlsBox,
                        header: headerBox,
                    });
                }

                if (header.scrollWidth > header.clientWidth + tolerance) {
                    add('filter-escape', `analytics header overflows by ${header.scrollWidth - header.clientWidth}px`);
                }

                controlItems.forEach((control) => {
                    const controlBox = box(control);
                    if (!within(controlBox, headerBox)) {
                        add('filter-escape', `${name(control)} escapes the analytics header`, {
                            control: controlBox,
                            header: headerBox,
                        });
                    }
                });

                for (let first = 0; first < controlItems.length; first += 1) {
                    for (let second = first + 1; second < controlItems.length; second += 1) {
                        const firstBox = box(controlItems[first]);
                        const secondBox = box(controlItems[second]);

                        if (overlaps(firstBox, secondBox)) {
                            add('filter-escape', `${name(controlItems[first])} overlaps ${name(controlItems[second])}`, {
                                first: firstBox,
                                second: secondBox,
                            });
                        }
                    }
                }
            });

            // A Chart.js canvas must fit its fixed-height container and the
            // card/panel that clips it. Checking both catches a canvas that is
            // technically inside .chart-container but cropped by its card.
            let chartCount = 0;
            const chartCards = [...document.querySelectorAll('.chart-container')].filter(visible);

            chartCards.forEach((container) => {
                const canvas = container.querySelector('canvas');
                if (!canvas || !visible(canvas)) return;

                chartCount += 1;

                const containerBox = box(container);
                const canvasBox = box(canvas);

                if (!within(canvasBox, containerBox)) {
                    add('chart-clipped', `${name(canvas)} escapes its chart container`, {
                        chart: name(container),
                        canvas: canvasBox,
                        container: containerBox,
                    });
                }

                if (container.scrollWidth > container.clientWidth + tolerance) {
                    add('chart-clipped', `${name(container)} overflows horizontally by ${container.scrollWidth - container.clientWidth}px`);
                }

                if (container.scrollHeight > container.clientHeight + tolerance) {
                    add('chart-clipped', `${name(container)} overflows vertically by ${container.scrollHeight - container.clientHeight}px`);
                }

                const card = container.closest('.analytics-card, .chart-card, .dash-panel');
                if (card) {
                    const cardBox = box(card);

                    if (!within(containerBox, cardBox)) {
                        add('chart-clipped', `${name(container)} escapes its chart card`, {
                            chart: name(container),
                            container: containerBox,
                            card: cardBox,
                        });
                    }

                    if (!within(canvasBox, cardBox)) {
                        add('chart-clipped', `${name(canvas)} is clipped by its chart card`, {
                            chart: name(container),
                            canvas: canvasBox,
                            card: cardBox,
                        });
                    }
                }
            });

            return { problems, chartCount };
        },
        { width, tolerance: tolerancePx },
    );
}

for (const dashboard of dashboards) {
    test.describe(`${dashboard.label} tablet sweep`, () => {
        test.use({ storageState: authStateFile(dashboard.role) });

        test('stays contained from 768px through 1024px', async ({ page }, testInfo) => {
            test.skip(testInfo.project.name !== 'desktop', 'the sweep sets explicit tablet widths');

            await page.setViewportSize({ width: TABLET_WIDTHS[0], height: 1000 });

            const response = await page.goto(dashboard.path, { waitUntil: 'domcontentloaded' });
            expect(response?.status(), `GET ${dashboard.path}`).toBe(200);
            await expect(
                page.getByText(dashboard.marker, { exact: false }).first(),
                `${dashboard.path} did not render its dashboard content`,
            ).toBeVisible();

            await waitForCharts(page);

            const failures = [];
            let chartCount = 0;

            for (const width of TABLET_WIDTHS) {
                await page.setViewportSize({ width, height: 1000 });
                await settleLayout(page, width);

                const result = await collectTabletProblems(page, width);
                failures.push(...result.problems);
                chartCount += result.chartCount;
            }

            expect(chartCount, 'the tablet sweep should inspect rendered chart canvases').toBeGreaterThan(0);
            expect(failures, `tablet layout failures on ${dashboard.path}`).toEqual([]);
        });
    });
}
