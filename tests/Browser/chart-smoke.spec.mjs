/**
 * Browser smoke test for the admin and student dashboard charts.
 *
 * Each chart must mount a real Chart.js canvas, finish its first draw, and put
 * pixels on the canvas. The page must do all of that without writing a console
 * error or throwing an uncaught exception.
 */

import { expect, test } from '@playwright/test';
import { authStateFile, dashboards } from './support/coverage.mjs';

const chartIdsByRole = {
    admin: [
        'adminEnrollmentChart',
        'adminCoursesChart',
        'adminUsersChart',
        'adminAttendanceChart',
    ],
    student: [
        'progressChart',
        'analyticsChart',
    ],
};

/**
 * Wait until the container's canvas has a backing size and an attached
 * Chart.js instance that is not still animating.
 */
async function waitForChartToFinish(page, chartId) {
    await page.waitForFunction(
        (id) => {
            const canvas = document.querySelector(`#${id} canvas`);

            if (!canvas || canvas.width <= 0 || canvas.height <= 0) return false;

            const chart = window.Chart?.getChart?.(canvas);

            return Boolean(chart) && chart.animating !== true;
        },
        chartId,
        { timeout: 20_000 },
    );

    return page.evaluate((id) => {
        const canvas = document.querySelector(`#${id} canvas`);
        const context = canvas.getContext('2d');
        const pixels = context.getImageData(0, 0, canvas.width, canvas.height).data;
        let painted = false;

        // A mounted Chart.js instance is not proof that anything was drawn.
        // Missing chart data or a zero-sized draw area can leave the canvas
        // blank, so require at least one non-transparent pixel.
        for (let index = 3; index < pixels.length; index += 4) {
            if (pixels[index] !== 0) {
                painted = true;
                break;
            }
        }

        return {
            width: canvas.width,
            height: canvas.height,
            painted,
        };
    }, chartId);
}

for (const role of ['admin', 'student']) {
    const dashboard = dashboards.find((entry) => entry.role === role);

    if (!dashboard) {
        throw new Error(`coverage.json is missing the ${role} dashboard`);
    }

    test.describe(`${dashboard.label} dashboard charts`, () => {
        test.use({ storageState: authStateFile(role) });

        test('render without console errors', async ({ page }) => {
            const consoleErrors = [];
            const pageErrors = [];

            page.on('console', (message) => {
                if (message.type() === 'error') {
                    consoleErrors.push(message.text());
                }
            });
            page.on('pageerror', (error) => pageErrors.push(error.message));

            const response = await page.goto(dashboard.path, { waitUntil: 'domcontentloaded' });

            expect(response?.status(), `GET ${dashboard.path}`).toBe(200);
            expect(new URL(page.url()).pathname, 'the dashboard should not bounce to sign-in').toBe(
                dashboard.path,
            );
            await expect(
                page.getByText(dashboard.marker, { exact: false }).first(),
                `${dashboard.path} did not render its dashboard content`,
            ).toBeVisible();

            for (const chartId of chartIdsByRole[role]) {
                const canvas = page.locator(`#${chartId} canvas`);

                await expect(canvas, `#${chartId} should mount a canvas`).toBeVisible({
                    timeout: 20_000,
                });

                const rendered = await waitForChartToFinish(page, chartId);

                expect(rendered.width, `#${chartId} canvas width`).toBeGreaterThan(0);
                expect(rendered.height, `#${chartId} canvas height`).toBeGreaterThan(0);
                expect(rendered.painted, `#${chartId} canvas should not be blank`).toBe(true);
            }

            expect(consoleErrors, 'dashboard wrote console errors').toEqual([]);
            expect(pageErrors, 'dashboard threw uncaught exceptions').toEqual([]);
        });
    });
}
