import { test } from '@playwright/test';
import { authStateFile } from './support/coverage.mjs';

test.describe('debug', () => {
    test.use({ storageState: authStateFile('instructor') });

    test('how does the dashboard clip', async ({ page }) => {
        await page.goto('/instructor/dashboard', { waitUntil: 'domcontentloaded' });
        await page.waitForTimeout(1500);

        const info = await page.evaluate(() => {
            const doc = document.documentElement;
            const body = document.body;
            const snapshot = () => ({
                docClient: doc.clientWidth,
                docScroll: doc.scrollWidth,
                bodyClient: body.clientWidth,
                bodyScroll: body.scrollWidth,
            });
            const before = snapshot();

            const wide = document.createElement('div');
            wide.id = 'dbg-wide';
            wide.style.cssText = 'display:block;flex:none;width:4000px;min-width:4000px;height:8px';
            body.appendChild(wide);
            const inBody = snapshot();

            wide.remove();
            const wide2 = document.createElement('div');
            wide2.id = 'dbg-wide-2';
            wide2.style.cssText = 'display:block;flex:none;width:4000px;min-width:4000px;height:8px';
            doc.appendChild(wide2);
            const inHtml = snapshot();
            wide2.remove();

            return {
                htmlOverflowX: getComputedStyle(doc).overflowX,
                htmlOverflowY: getComputedStyle(doc).overflowY,
                bodyOverflowX: getComputedStyle(body).overflowX,
                bodyDisplay: getComputedStyle(body).display,
                mainOverflowX: getComputedStyle(document.querySelector('.main')).overflowX,
                contentOverflowX: getComputedStyle(document.querySelector('.content')).overflowX,
                contentWidth: document.querySelector('.content').getBoundingClientRect().width,
                innerWidth: window.innerWidth,
                scrollingElement: document.scrollingElement?.tagName,
                before,
                inBody,
                inHtml,
            };
        });

        console.log('DEBUG ' + JSON.stringify(info, null, 2));
    });
});
