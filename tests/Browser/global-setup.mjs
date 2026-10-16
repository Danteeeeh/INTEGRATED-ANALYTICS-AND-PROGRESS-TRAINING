/**
 * Prepares everything the check needs before the web server starts:
 * a freshly seeded throwaway database and a built front end.
 *
 * Playwright runs `globalSetup` before `webServer`, so by the time the server
 * boots the data is already there. `RESPONSIVE_KEEP_DB=1` skips the rebuild for
 * quick local iteration.
 */

import { spawnSync } from 'node:child_process';
import { existsSync, writeFileSync } from 'node:fs';
import path from 'node:path';
import { DB_PATH, phpBinary, projectRoot, serverEnvironment } from './support/env.mjs';

function run(args) {
    const result = spawnSync(phpBinary(), args, {
        cwd: projectRoot,
        env: serverEnvironment(),
        encoding: 'utf8',
    });

    if (result.error) throw result.error;

    return result;
}

export default async function globalSetup() {
    const manifest = path.join(projectRoot, 'public', 'build', 'manifest.json');
    if (!existsSync(manifest)) {
        throw new Error(
            `Vite build output is missing (${manifest}).\n` +
                'Every page 500s without it — run `npm run build` and re-run this check.',
        );
    }

    if (process.env.RESPONSIVE_KEEP_DB) {
        console.log(`[responsive] reusing existing database at ${DB_PATH}`);
        return;
    }

    // SQLite needs the file to exist before Laravel will connect to it.
    if (!existsSync(DB_PATH)) writeFileSync(DB_PATH, '');

    const migrate = run(['artisan', 'migrate:fresh', '--seed', '--force', '--no-interaction']);

    if (migrate.status !== 0) {
        const output = `${migrate.stdout || ''}${migrate.stderr || ''}`.trim().split('\n').slice(-15).join('\n');
        throw new Error(`Could not prepare the check database (exit ${migrate.status}):\n${output}`);
    }

    console.log(`[responsive] seeded demo data into ${DB_PATH}`);
}
