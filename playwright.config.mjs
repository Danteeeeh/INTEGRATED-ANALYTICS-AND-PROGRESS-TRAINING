/**
 * Responsive dashboard check.
 *
 * Loads every role dashboard (tests/Browser/coverage.json) at mobile, tablet
 * and desktop widths and fails when the document is wider than the viewport —
 * i.e. when the page scrolls sideways.
 *
 * Run with `npm run test:responsive`.
 */

import { defineConfig } from '@playwright/test';
import { BASE_URL, HOST, PORT, phpBinary, projectRoot, serverEnvironment } from './tests/Browser/support/env.mjs';

/** The three widths every dashboard is checked at. */
const viewports = [
    { name: 'mobile', viewport: { width: 390, height: 844 } },
    { name: 'tablet', viewport: { width: 768, height: 1024 } },
    { name: 'desktop', viewport: { width: 1440, height: 900 } },
];

export default defineConfig({
    testDir: './tests/Browser',
    outputDir: './test-results',
    // One shared PHP server and one shared database: keep it sequential so a
    // built-in-server worker queue never turns into a flaky timeout.
    fullyParallel: false,
    workers: 1,
    forbidOnly: !!process.env.CI,
    timeout: 90_000,
    // Some panels are revealed by the dashboard's own scripts after
    // DOMContentLoaded, and a slow first render is not a layout bug.
    expect: { timeout: 15_000 },
    globalSetup: './tests/Browser/global-setup.mjs',
    reporter: [
        ['list'],
        ['html', { outputFolder: 'playwright-report', open: 'never' }],
    ],
    webServer: {
        command: `"${phpBinary()}" -S ${HOST}:${PORT} -t public tests/Browser/router.php`,
        cwd: projectRoot,
        env: serverEnvironment(),
        url: `${BASE_URL}/login`,
        // Never adopt a server we did not start: it would be serving a
        // different database and the result would be meaningless.
        reuseExistingServer: false,
        timeout: 120_000,
        // PHP's built-in server logs every request, which buries the check's
        // own results. Switch these to 'pipe' when you need that log.
        stdout: 'ignore',
        stderr: 'ignore',
    },
    use: {
        baseURL: BASE_URL,
        deviceScaleFactor: 1,
        // The app already honours prefers-reduced-motion; honouring it here
        // keeps mid-animation layouts out of the measurements.
        reducedMotion: 'reduce',
        trace: 'retain-on-failure',
        screenshot: 'only-on-failure',
    },
    projects: [
        { name: 'auth', testMatch: /auth\.setup\.mjs/ },
        ...viewports.map(({ name, viewport }) => ({
            name,
            use: { viewport },
            dependencies: ['auth'],
        })),
    ],
});
