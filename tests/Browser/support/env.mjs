/**
 * Environment wiring for the responsive dashboard check.
 *
 * The check serves the real application from a throwaway SQLite database so it
 * can never touch the developer's own data, and it drives the server through
 * environment variables it sets itself rather than whatever happens to be in
 * `.env` (which is not always a runnable web/mail/session configuration).
 */

import { existsSync } from 'node:fs';
import path from 'node:path';
import { fileURLToPath } from 'node:url';

const supportDir = path.dirname(fileURLToPath(import.meta.url));

/** Project root — tests/Browser/support/ upwards three levels. */
export const projectRoot = path.resolve(supportDir, '..', '..', '..');

export const browserDir = path.join(projectRoot, 'tests', 'Browser');

export const HOST = '127.0.0.1';

export const PORT = Number(process.env.RESPONSIVE_PORT || 8123);

export const BASE_URL = `http://${HOST}:${PORT}`;

/** Dedicated database; gitignored (`*.sqlite`) and rebuilt on every run. */
export const DB_PATH = path.join(projectRoot, 'database', 'responsive-check.sqlite');

/**
 * Password the seeders give every demo account (UserSeeder / config
 * `lms.dev_seed_password`).
 */
export const SEED_PASSWORD = process.env.LMS_DEV_SEED_PASSWORD || 'Password123!';

/**
 * Locate the PHP CLI. `PHP_BIN` wins so the check can be pointed at any
 * interpreter; the rest are the usual Windows install locations, and plain
 * `php` is the last resort (it must then be on PATH).
 */
export function phpBinary() {
    const candidates = [
        process.env.PHP_BIN,
        'C:/php/php84/php.exe',
        'C:/php/php.exe',
        'C:/xampp/php/php.exe',
        'C:/laragon/bin/php/php.exe',
        '/usr/bin/php',
    ].filter(Boolean);

    for (const candidate of candidates) {
        if (candidate === 'php' || existsSync(candidate)) {
            return candidate;
        }
    }

    return 'php';
}

/**
 * Environment handed to every PHP process the check starts (the migration run
 * and the web server), merged over the current environment so the child still
 * sees PATH and friends.
 */
export function serverEnvironment(extra = {}) {
    return {
        ...process.env,
        APP_URL: BASE_URL,
        DB_CONNECTION: 'sqlite',
        DB_DATABASE: DB_PATH,
        // A request-scoped session driver cannot survive the login POST — the
        // CSRF token would be regenerated on the next request.
        SESSION_DRIVER: 'file',
        SESSION_SECURE_COOKIE: 'false',
        // Rendering the dashboards must not depend on a queue worker or an SMTP
        // server being reachable.
        QUEUE_CONNECTION: 'sync',
        MAIL_MAILER: 'log',
        CACHE_STORE: 'array',
        // Demo accounts are OTP-exempt already; this keeps the check honest if
        // that exemption list ever changes.
        LMS_LOGIN_OTP_ENABLED: 'false',
        ...extra,
    };
}
