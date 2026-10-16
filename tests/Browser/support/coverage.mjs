/**
 * Reads tests/Browser/coverage.json — the single list of role dashboards the
 * responsive check covers. `ResponsiveDashboardCheckTest` reads the same file
 * from PHP so a renamed dashboard route fails fast instead of quietly shrinking
 * the browser check.
 */

import { mkdirSync, readFileSync } from 'node:fs';
import path from 'node:path';
import { browserDir } from './env.mjs';

export const coverage = JSON.parse(readFileSync(path.join(browserDir, 'coverage.json'), 'utf8'));

export const dashboards = coverage.roles;

export const tolerancePx = coverage.tolerancePx ?? 1;

export const authDir = path.join(browserDir, '.auth');

/** Signed-in browser state for one role, written once by the `auth` project. */
export function authStateFile(role) {
    mkdirSync(authDir, { recursive: true });

    return path.join(authDir, `${role}.json`);
}
