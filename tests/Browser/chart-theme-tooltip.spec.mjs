/**
 * Shared Chart.js theme checks for the admin, faculty, student and AA Staff dashboards.
 *
 * A chart must show a real tooltip after a hover, and the tooltip, legend,
 * axis ticks and doughnut slice borders must all move to the current theme's
 * palette when the theme toggle flips dark -> light -> dark. The assertions
 * read Chart.js state and colours rather than comparing screenshots.
 *
 * Every role runs at desktop and 390px, including the AA Staff enrollment
 * doughnut. Desktop opens tooltips with the mouse; the touch-enabled 390px
 * project taps them. Both widths verify the dark -> light -> dark repaint.
 */

import { expect, test } from '@playwright/test';
import { authStateFile, dashboards } from './support/coverage.mjs';

const chartCases = [
    {
        role: 'admin',
        chartId: 'adminEnrollmentChart',
    },
    {
        role: 'instructor',
        chartId: 'completionChart',
        alpha: true,
    },
    {
        role: 'student',
        chartId: 'progressChart',
        alpha: true,
    },
    {
        role: 'aa_staff',
        chartId: 'aaEnrollmentChart',
    },
];

async function waitForChart(page, chartId) {
    await expect(page.locator(`#${chartId} canvas`), `#${chartId} should mount a canvas`).toBeVisible({
        timeout: 20_000,
    });

    await page.waitForFunction(
        (id) => {
            const canvas = document.querySelector(`#${id} canvas`);
            const chart = canvas && window.Chart?.getChart?.(canvas);

            return Boolean(chart) && chart.animating !== true;
        },
        chartId,
        { timeout: 20_000 },
    );
}

async function expectChartTheme(page, chartId, light, expectAlpha = false) {
    let series = [];

    await expect
        .poll(
            async () => {
                const state = await page.evaluate(({ id, light, expectAlpha }) => {
                    const theme = window.LMSCharts.theme();
                    const mismatches = [];
                    const series = [];

                    if (theme.light !== light) {
                        mismatches.push(`theme light ${theme.light} !== ${light}`);
                    }

                    const canvas = document.querySelector(`#${id} canvas`);
                    const chart = canvas && window.Chart.getChart(canvas);

                    if (!chart) return {
                        mismatches: [...mismatches, `#${id} has no Chart.js instance`],
                        series,
                    };

                    const legend = chart.options.plugins?.legend;
                    const legendColor = legend && legend.display !== false
                        ? legend.labels?.color
                        : null;

                    if (legendColor !== null && legendColor !== theme.text) {
                        mismatches.push(`#${id} legend ${legendColor} !== ${theme.text}`);
                    }

                    Object.entries(chart.options.scales || {})
                        .filter(([, scale]) => scale && scale.ticks && scale.ticks.display !== false)
                        .forEach(([axis, scale]) => {
                            if (scale.ticks.color !== theme.muted) {
                                mismatches.push(`#${id} ${axis} tick ${scale.ticks.color} !== ${theme.muted}`);
                            }
                        });

                    if (chart.config.type === 'doughnut' || chart.config.type === 'pie') {
                        chart.data.datasets.forEach((dataset) => {
                            if (dataset.borderColor !== theme.surface) {
                                mismatches.push(`#${id} doughnut border ${dataset.borderColor} !== ${theme.surface}`);
                            }
                        });
                    }

                    const palette = window.LMSCharts.palette();
                    const paletteEntries = Object.entries(palette);

                    function resolveSeriesColour(value) {
                        if (typeof value !== 'string') return null;

                        const normalised = value.trim().toLowerCase();
                        const solid = paletteEntries.find(([, colour]) => colour.toLowerCase() === normalised);

                        if (solid) return { key: solid[0], alpha: 1 };

                        const match = normalised.match(
                            /^rgba?\(\s*(\d+)\s*,\s*(\d+)\s*,\s*(\d+)(?:\s*,\s*([\d.]+))?\s*\)$/,
                        );

                        if (!match) return null;

                        const hex = '#' + match.slice(1, 4)
                            .map((channel) => Number(channel).toString(16).padStart(2, '0'))
                            .join('');
                        const entry = paletteEntries.find(([, colour]) => colour.toLowerCase() === hex);

                        return entry
                            ? { key: entry[0], alpha: match[4] === undefined ? 1 : Number(match[4]) }
                            : null;
                    }

                    chart.data.datasets.forEach((dataset, datasetIndex) => {
                        const properties = chart.config.type === 'doughnut' || chart.config.type === 'pie'
                            ? ['backgroundColor']
                            : ['backgroundColor', 'borderColor'];

                        properties.forEach((property) => {
                            if (dataset[property] === undefined) return;

                            const values = Array.isArray(dataset[property])
                                ? dataset[property]
                                : [dataset[property]];

                            values.forEach((value, index) => {
                                const resolved = resolveSeriesColour(value);

                                if (!resolved) {
                                    mismatches.push(
                                        `#${id} dataset ${datasetIndex} ${property}[${index}] ${value} is not in the ${light ? 'light' : 'dark'} palette`,
                                    );

                                    return;
                                }

                                series.push({ ...resolved, value: String(value).toLowerCase(), datasetIndex, property, index });
                            });
                        });
                    });

                    if (expectAlpha && !series.some((entry) => entry.alpha < 1)) {
                        mismatches.push(`#${id} has no alpha series to check`);
                    }

                    return { mismatches, series };
                }, { id: chartId, light, expectAlpha });

                series = state.series;

                return state.mismatches;
            },
            { timeout: 10_000 },
        )
        .toEqual([]);

    return series;
}

/**
 * Point at a real data element, then use the input method of the project:
 * hover on desktop, tap at 390px. Doughnut arcs need a point on the ring,
 * while bars expose their tooltip position directly.
 */
async function activateChartTooltip(page, chartId, touch) {
    const canvas = page.locator(`#${chartId} canvas`);
    await canvas.scrollIntoViewIfNeeded();

    const point = await page.evaluate((id) => {
        const element = document.querySelector(`#${id} canvas`);
        const chart = window.Chart.getChart(element);
        const data = chart.getDatasetMeta(0).data.find((entry) => entry);
        const rect = element.getBoundingClientRect();

        let x;
        let y;

        if (chart.config.type === 'doughnut' || chart.config.type === 'pie') {
            const angle = (data.startAngle + data.endAngle) / 2;
            const radius = (data.innerRadius + data.outerRadius) / 2;
            x = data.x + Math.cos(angle) * radius;
            y = data.y + Math.sin(angle) * radius;
        } else {
            const position = data.tooltipPosition();
            x = position.x;
            y = position.y;
        }

        return { x: rect.left + x, y: rect.top + y };
    }, chartId);

    if (touch) {
        await page.touchscreen.tap(point.x, point.y);
    } else {
        await page.mouse.move(point.x, point.y, { steps: 5 });
    }
}

async function expectTooltipVisibleWithTheme(page, chartId) {
    await expect
        .poll(
            () =>
                page.evaluate((id) => {
                    const canvas = document.querySelector(`#${id} canvas`);
                    const chart = canvas && window.Chart.getChart(canvas);
                    const tooltip = chart && chart.tooltip;
                    const options = tooltip && tooltip.options;
                    const theme = window.LMSCharts.theme();
                    const mismatches = [];

                    if (!tooltip || !options) return { visible: false, mismatches: ['tooltip is missing'] };

                    if (options.backgroundColor !== theme.tooltip) {
                        mismatches.push(`tooltip background ${options.backgroundColor} !== ${theme.tooltip}`);
                    }
                    if (options.titleColor !== theme.tooltipText) {
                        mismatches.push(`tooltip title ${options.titleColor} !== ${theme.tooltipText}`);
                    }
                    if (options.bodyColor !== theme.tooltipText) {
                        mismatches.push(`tooltip body ${options.bodyColor} !== ${theme.tooltipText}`);
                    }
                    if (options.borderColor !== theme.tooltipBorder) {
                        mismatches.push(`tooltip border ${options.borderColor} !== ${theme.tooltipBorder}`);
                    }

                    return { visible: tooltip.opacity > 0, mismatches };
                }, chartId),
            { timeout: 10_000 },
        )
        .toMatchObject({ visible: true, mismatches: [] });
}

for (const chartCase of chartCases) {
    const dashboard = dashboards.find((entry) => entry.role === chartCase.role);

    if (!dashboard) {
        throw new Error(`coverage.json is missing the ${chartCase.role} dashboard`);
    }

    test.describe(`${dashboard.label} chart theme repaint`, () => {
        test.use({ storageState: authStateFile(chartCase.role) });

        test('repaints tooltip, legend and ticks across themes', async ({ page }, testInfo) => {
            const project = testInfo.project.name;
            const touch = project === 'mobile';

            test.skip(project !== 'desktop' && !touch, `${dashboard.label} chart checks run at desktop and 390px mobile`);

            await page.addInitScript(() => localStorage.setItem('bcp-theme', 'dark'));

            const response = await page.goto(dashboard.path, { waitUntil: 'domcontentloaded' });
            expect(response?.status(), `GET ${dashboard.path}`).toBe(200);
            await expect(
                page.getByText(dashboard.marker, { exact: false }).first(),
                `${dashboard.path} did not render its dashboard content`,
            ).toBeVisible();

            await waitForChart(page, chartCase.chartId);
            const darkSeries = await expectChartTheme(page, chartCase.chartId, false, chartCase.alpha);

            await activateChartTooltip(page, chartCase.chartId, touch);
            await expectTooltipVisibleWithTheme(page, chartCase.chartId);

            await page.locator('#themeToggle').click();
            const lightSeries = await expectChartTheme(page, chartCase.chartId, true, chartCase.alpha);
            expect(
                lightSeries.map(({ key, alpha }) => ({ key, alpha })),
                'the same series keys and alpha values should survive the light repaint',
            ).toEqual(darkSeries.map(({ key, alpha }) => ({ key, alpha })));
            expect(
                lightSeries.every((entry, index) => entry.value !== darkSeries[index].value),
                'every series colour should switch to the light palette value',
            ).toBe(true);

            await activateChartTooltip(page, chartCase.chartId, touch);
            await expectTooltipVisibleWithTheme(page, chartCase.chartId);

            await page.locator('#themeToggle').click();
            await expectChartTheme(page, chartCase.chartId, false);
        });
    });
}
