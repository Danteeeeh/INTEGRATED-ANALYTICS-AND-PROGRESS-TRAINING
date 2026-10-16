/**
 * Proves the overflow probe can fail on the very pages it is used on.
 *
 * A check that only ever reports "no overflow" is worthless: a probe that
 * cannot see anything and a clean layout look identical. So this loads a real
 * dashboard through the same sign-in state the check uses, injects an element
 * that is unambiguously too wide, and requires the probe to notice — then
 * requires it to go quiet again once the offender is gone.
 */

import { expect, test } from '@playwright/test';
import { authStateFile, dashboards, tolerancePx } from './support/coverage.mjs';
import { measureHorizontalOverflow } from './support/overflow.mjs';

// The widest, most deeply nested of the four dashboards.
const dashboard = dashboards.find((entry) => entry.role === 'instructor');

test.describe('overflow probe', () => {
    test.use({ storageState: authStateFile(dashboard.role) });

    test('detects an injected overflow on a real dashboard', async ({ page }) => {
        await page.goto(dashboard.path, { waitUntil: 'domcontentloaded' });

        const before = await page.evaluate(measureHorizontalOverflow, tolerancePx);
        expect(before.overflowPx, `${dashboard.path} should not overflow to begin with`).toBeLessThanOrEqual(
            tolerancePx,
        );

        await page.evaluate(() => {
            const wide = document.createElement('div');
            wide.id = 'responsive-probe-selfcheck';
            // `flex:none` matters: these layouts are flex/grid containers, and a
            // plain `width` on a flex item is only a suggestion it may shrink
            // away from.
            wide.style.cssText = 'display:block;flex:none;width:4000px;min-width:4000px;height:8px';
            document.body.appendChild(wide);
        });

        const injected = await page.evaluate(measureHorizontalOverflow, tolerancePx);

        expect(
            injected.overflowPx,
            'the probe failed to see a 4000px-wide element — it cannot be trusted to catch real overflow',
        ).toBeGreaterThan(tolerancePx);
        expect(injected.offenders.map((offender) => offender.selector)).toContain(
            'div#responsive-probe-selfcheck',
        );

        await page.evaluate(() => document.getElementById('responsive-probe-selfcheck')?.remove());

        const after = await page.evaluate(measureHorizontalOverflow, tolerancePx);
        expect(after.overflowPx, 'the probe should clear once the offender is gone').toBeLessThanOrEqual(
            tolerancePx,
        );
    });
});
