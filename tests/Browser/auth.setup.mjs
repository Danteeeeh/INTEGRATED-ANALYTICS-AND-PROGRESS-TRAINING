/**
 * Signs in once per role and saves the browser state the width projects reuse.
 *
 * Signing in here rather than in each test matters: `POST /login` is behind
 * `throttle:5,1`, so four real sign-ins are fine but twelve (one per dashboard
 * per width) would be rate limited.
 */

import { expect, test as setup } from '@playwright/test';
import { authStateFile, dashboards } from './support/coverage.mjs';
import { SEED_PASSWORD } from './support/env.mjs';

for (const dashboard of dashboards) {
    setup(`sign in as ${dashboard.label}`, async ({ playwright, baseURL }) => {
        const context = await playwright.request.newContext({ baseURL });

        try {
            const loginPage = await context.get('/login');
            expect(loginPage.status(), 'GET /login').toBe(200);

            const token = /name="_token"\s+value="([^"]+)"/.exec(await loginPage.text())?.[1];
            expect(token, 'the login form should carry a CSRF token').toBeTruthy();

            const login = await context.post('/login', {
                form: { _token: token, email: dashboard.email, password: SEED_PASSWORD },
                maxRedirects: 0,
            });

            // 419 here means the session did not survive the round trip; 429
            // means the login throttle kicked in. Name them so the next person
            // does not have to guess.
            expect(
                [302, 303],
                `POST /login for ${dashboard.email} answered ${login.status()}` +
                    (login.status() === 419 ? ' (CSRF/session lost — is SESSION_DRIVER request-scoped?)' : '') +
                    (login.status() === 429 ? ' (too many sign-in attempts)' : ''),
            ).toContain(login.status());

            const landed = await context.get(dashboard.path);
            expect(landed.status(), `GET ${dashboard.path}`).toBe(200);

            await context.storageState({ path: authStateFile(dashboard.role) });
        } finally {
            await context.dispose();
        }
    });
}
