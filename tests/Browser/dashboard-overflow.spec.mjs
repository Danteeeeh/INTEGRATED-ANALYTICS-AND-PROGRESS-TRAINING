/**
 * Every role dashboard, at every width: the page must not scroll sideways.
 *
 * The widths come from the Playwright projects (mobile / tablet / desktop).
 */

import { expect, test } from '@playwright/test';
import { authStateFile, dashboards, tolerancePx } from './support/coverage.mjs';
import { describeOverflow, measureHorizontalOverflow } from './support/overflow.mjs';

/**
 * Charts and web fonts settle after `load`; measuring too early reads
 * half-laid-out boxes.
 */
async function waitForLayoutToSettle(page) {
    await page.evaluate(() => document.fonts?.ready);

    await page
        .waitForFunction(
            () => Array.from(document.querySelectorAll('canvas')).every((c) => c.width > 0 && c.height > 0),
            null,
            { timeout: 5_000 },
        )
        .catch(() => {
            /* A page with no charts, or a chart that failed to load, still has
               a layout worth measuring. */
        });

    // Walk the document so anything that only lays out once it has been in
    // view (sticky bars, lazy panels) has had its chance.
    await page.evaluate(async () => {
        const step = window.innerHeight;
        for (let y = 0; y < document.body.scrollHeight; y += step) {
            window.scrollTo(0, y);
            await new Promise((resolve) => requestAnimationFrame(resolve));
        }
        window.scrollTo(0, 0);
    });
}

for (const dashboard of dashboards) {
    test.describe(`${dashboard.label} dashboard — ${dashboard.path}`, () => {
        test.use({ storageState: authStateFile(dashboard.role) });

        test('renders without horizontal overflow', async ({ page }, testInfo) => {
            const response = await page.goto(dashboard.path, { waitUntil: 'domcontentloaded' });

            expect(response?.status(), `GET ${dashboard.path}`).toBe(200);
            expect(new URL(page.url()).pathname, 'the dashboard should not bounce to the sign-in page').toBe(
                dashboard.path,
            );

            // Without this the check would happily pass on an error or empty
            // page, which is the one way it could lie. It waits, because parts
            // of these dashboards are revealed by their own scripts after
            // `DOMContentLoaded`.
            await expect(
                page.getByText(dashboard.marker, { exact: false }).first(),
                `${dashboard.path} did not render its dashboard content`,
            ).toBeVisible();

            await waitForLayoutToSettle(page);

            const result = await page.evaluate(measureHorizontalOverflow, tolerancePx);
            const report = describeOverflow(dashboard, result, page.viewportSize());

            if (result.overflowPx > tolerancePx) {
                await testInfo.attach('overflow.json', {
                    body: JSON.stringify(result, null, 2),
                    contentType: 'application/json',
                });
            }

            expect(result.overflowPx, report).toBeLessThanOrEqual(tolerancePx);
        });
    });
}
