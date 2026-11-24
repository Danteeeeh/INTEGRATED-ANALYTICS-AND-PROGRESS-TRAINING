# HostForge Deployment — Current Setup

This file describes the repository as it is now. It replaces the older version
that assumed `vendor/` and `public/build/` were committed and that a production
deploy seeds demo logins.

- **Stack:** Laravel 12, PHP **8.2+**, Node **18+** (Vite 5; Node 20 LTS recommended), Composer 2
- **Web process:** `bash start.sh` (`php artisan serve`, HTTP only — HostForge terminates TLS)
- **Release process:** `bash release.sh`
- **Health check:** `/up` → `204 No Content`

`vendor/` and `public/build/` are **gitignored**, so a fresh checkout is not
runnable on its own. The build step below is mandatory, not optional.

---

## 1. Environment variables

### Application and database

```dotenv
APP_NAME="LMS"
APP_ENV=production
APP_DEBUG=false
APP_KEY=<generate once with: php artisan key:generate --show>
APP_URL=https://<your-domain>

DB_CONNECTION=mariadb
DB_HOST=<hostforge-database-host>
DB_PORT=3306
DB_DATABASE=<database-name>
DB_USERNAME=<database-user>
DB_PASSWORD=<database-password>

# Absolute path to the HostForge persistent volume (not the release directory).
LMS_STORAGE_PATH=/data/lms
```

The database user needs `CREATE`, `ALTER`, `DROP`, `SELECT`, `INSERT`, `UPDATE`,
`DELETE` so `release.sh` can migrate.

`LMS_STORAGE_PATH` is covered in section 6. Do not leave it unset in
production: uploads and backups would sit in the replaceable container.

### Session, cache, queue

```dotenv
SESSION_DRIVER=database
SESSION_LIFETIME=120
SESSION_DOMAIN=
SESSION_SECURE_COOKIE=true
SESSION_SAME_SITE=lax

CACHE_STORE=database
QUEUE_CONNECTION=sync
```

- Leave `SESSION_DOMAIN` **empty**. Setting it to a value restricts the cookie
  and causes 419 "session expired" on login.
- `QUEUE_CONNECTION=sync` is correct today: nothing in the app implements
  `ShouldQueue`, so no worker is needed.

### Sanctum

```dotenv
SANCTUM_STATEFUL_DOMAINS=<your-domain>,<any-other-domain-you-serve>
```

Use the exact host(s) users type in the address bar, without the scheme.

### Mail (login one-time codes)

```dotenv
MAIL_MAILER=smtp
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=<school-gmail>
MAIL_PASSWORD=<16-character-app-password>
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=<school-gmail>
MAIL_FROM_NAME="${APP_NAME}"
```

Gmail requires 2FA plus an app password; a normal account password is rejected.

### Built-in virtual classroom (LiveKit)

```dotenv
LIVEKIT_URL=wss://<your-project>.livekit.cloud
LIVEKIT_API_KEY=<api-key>
LIVEKIT_API_SECRET=<api-secret>
```

Rules enforced by the app (`LiveKitTokenService::diagnose()`):

| Value | Requirement | If wrong |
|---|---|---|
| `LIVEKIT_URL` | must start with `ws://` or `wss://` (Cloud: `wss://…livekit.cloud`) | Join stays disabled with a banner naming the variable |
| `LIVEKIT_API_KEY` | at least 8 characters | same |
| `LIVEKIT_API_SECRET` | at least 16 characters | same |

Notes:

- **HTTPS is required** for camera and microphone (`getUserMedia` only runs on a
  secure origin). The app's `Permissions-Policy` already allows
  `camera=(self), microphone=(self)`.
- A self-hosted LiveKit server works too — point `LIVEKIT_URL` at it
  (`wss://livekit.your-domain` behind TLS, or `ws://host:7880` for a LAN pilot).
- `Meeting → End Meeting` deletes the LiveKit room server-side, which requires
  the `roomCreate` grant; the server token also carries `roomAdmin`, which
  `ListParticipants` requires. Both are per the LiveKit RoomService spec — do not
  "simplify" either grant away.
- Classes are auto-closed by the scheduler (section 7). Without the cron entry,
  a session whose end time passed can stay `ongoing` until someone opens the
  virtual class list.

### Academic Affairs (AA) database — optional

The LMS reads official data from AA; it never writes to it.

```dotenv
AA_DB_HOST=<aa-database-host>
AA_DB_PORT=3306
AA_DB_DATABASE=aa_system
AA_DB_USERNAME=<read-only-user>
AA_DB_PASSWORD=<password>
```

Optional keys if the AA server needs them: `AA_DB_URL`, `AA_DB_SOCKET`,
`AA_DB_CHARSET` (default `utf8mb4`), `AA_DB_COLLATION`.

- Use a **read-only** database account. The sync only `SELECT`s from AA.
- New AA accounts never receive a shared or public password. Each new
  student/faculty gets its own random 32-character password, and a one-time
  password-set link is emailed to the account automatically.
- The activation link is emailed through `MAIL_*` and expires after 60
  minutes; **Forgot your password?** remains available as a fallback. Test
  delivery before the first sync; without working SMTP, users cannot receive
  their setup link.
- Syncing an existing account never rotates its password; password changes are
  user-driven through the activation/reset link.
- Sync is manual: Super Admin → **Sync from AA** → pick entities → Sync.
  Final grades are never imported; AA stays authoritative for grades.
- If AA is unreachable or unset, the sync page reports the failure and the rest
  of the application keeps working. The table/column mapping lives in
  `config/aa_sync.php` and must match the real AA schema.

### Demo seeding flags — read before the first deploy

```dotenv
# LMS_SEED_DEMO_USERS=true      # only for demo/staging sites
LMS_DEV_SEED_PASSWORD=<a-password-you-choose>
```

- `LMS_SEED_DEMO_USERS` is **unset by default**, which means "seed demo logins
  only outside production". On HostForge (`APP_ENV=production`) the four demo
  accounts (`admin@`, `instructor@`, `student@`, `aa-staff@lms.local`) are
  therefore **not created**.
- `LMS_DEV_SEED_PASSWORD` is the initial password of every account the seeder
  creates — including the owner account
  (`johncedrickdayandante6@gmail.com`). **Set it before the first deploy**,
  otherwise the account starts with the repository default `Password123!`.
  Change the password after the first login.
- The seeder only sets a password when it creates an account; redeploying does
  not reset an existing password.

---

## 2. Build command

```bash
composer install --no-dev --optimize-autoloader && npm ci && npm run build
```

- `composer install` must run **first**: `release.sh` calls `php artisan`, which
  needs `vendor/`.
- `npm ci` respects `package-lock.json`; `npm run build` writes `public/build`
  (the Vite manifest the layouts load). Without it every page fails with
  "Vite manifest not found at …/public/build/manifest.json".
- Do **not** use `npm install` on the server; it can rewrite the lockfile.

---

## 3. Release command

```bash
bash release.sh
```

What it does today:

1. copies `.env.example` to `.env` if no `.env` exists (HostForge normally
   injects the variables directly)
2. `php artisan key:generate` when `APP_KEY` is empty
3. `php artisan package:discover`
4. `php artisan storage:link --force` (points at `LMS_STORAGE_PATH/public`)
5. `php artisan migrate --force --seed`
6. `php artisan config:cache`, `route:cache`, `view:cache`
7. `php artisan lms:storage-health` (fails the release if the roots are not writable)
8. `php artisan cache:clear`
9. `chmod -R 775 storage bootstrap/cache`
10. removes `public/hot`

`--seed` is safe to re-run: seeding is create-only (never a password, status or
verification reset) and the
demo accounts stay out of production unless explicitly enabled.

---

## 4. Start command and health check

- Start command / Procfile: `web: bash start.sh`
  (falls back to port `8080` when HostForge does not inject `PORT`)
- Health check path: **`/up`** → `204`
  It is answered in `public/index.php` **before** Laravel boots, so it stays
  green during maintenance and does not touch the database. `DEPLOYMENT.md`
  and `HOSTFORGE_CHECKLIST.md` use the same path.

`php artisan serve` is a single-process server. That is fine for a
class-sized pilot; if the platform offers PHP-FPM/Apache for the same app,
prefer it for heavier concurrent use.

---

## 5. Upload limits — all three ceilings must agree

The app validates uploads in **kilobytes** (`config/lms.php`):

| Upload | App ceiling |
|---|---|
| Profile photo | 2 MB |
| Assignment submission | 10 MB |
| Question import | 10 MB |
| Learning material | 100 MB |
| Module attachment | **200 MB** |
| Lesson material | **200 MB** |
| Generic | 10 MB |

The largest accepted file is therefore **200 MB**, but two lower ceilings can
reject the request before Laravel ever sees it:

```ini
; php.ini
upload_max_filesize = 200M
post_max_size = 210M        ; must be larger than upload_max_filesize
memory_limit = 256M
max_execution_time = 120
```

```nginx
# nginx server block, only if nginx sits in front
client_max_body_size 210M;  # then: nginx -t && systemctl reload nginx
```

- A request above `post_max_size` or `client_max_body_size` is refused with a
  bare **413** and no Laravel log line.
- Verify what the server actually loaded:

```bash
php artisan lms:check-uploads
```

It prints the app ceilings, the live `php.ini` values, the smallest effective
limit, and warns when PHP is below the app's 200 MB.

---

## 6. Persistent storage (uploads and backups)

HostForge runs the app in a replaceable container. `storage/app` is **not**
durable across a redeploy unless it is mounted from a persistent volume. Without
one, the database keeps `media_files` rows while the actual PDFs, images, videos
and assignment submissions disappear.

### 6.1 Attach the volume in HostForge

1. In the HostForge application dashboard, create/attach a persistent volume.
2. Mount it outside the release directory, for example at `/data/lms`.
3. Put the same absolute path in the application environment:

   ```dotenv
   LMS_STORAGE_PATH=/data/lms
   ```

4. Keep the release command as `bash release.sh`. It creates
   `$LMS_STORAGE_PATH/public` and `$LMS_STORAGE_PATH/backups`, recreates the
   `public/storage` link with `--force`, and runs the storage preflight.

If the HostForge plan or panel has no persistent volume, **do not publish**. A
container-local path is not durable; move to a host that offers one, or add an
S3-compatible disk for uploads and an external backup target before going live.

### 6.2 Move existing files once

Before the first release with the new path, copy the current uploads and backups
into the volume. Run this from the current release directory, with
`LMS_STORAGE_PATH` exported:

```bash
mkdir -p "$LMS_STORAGE_PATH"
cp -a storage/app/. "$LMS_STORAGE_PATH"/
```

Do this once. After the switch, `storage/app` is no longer the live location;
the upload, public and backup disks all read from `LMS_STORAGE_PATH`.

### 6.3 Prove it survives a redeploy

```bash
php artisan lms:storage-health
# expect uploads, public uploads and backups writable; public/storage matching

php artisan lms:storage-health --write-marker
# redeploy the app, then:
php artisan lms:storage-health --verify-marker
```

A missing marker after the redeploy means the mount did not persist (or the
environment changed). Do not publish until `--verify-marker` passes.

### 6.4 Rehearse the restore before publishing

The rehearsal reads the live database, writes a JSON snapshot, replays it into a
fresh temporary SQLite database and compares every table's row count. It does
not modify production data.

```bash
php artisan lms:backup-restore-test
# or rehearse an existing .json backup:
php artisan lms:backup-restore-test --file=lms_backup_2026-11-22_120000.json
```

Also create a backup from **Admin → Backup & Restore**, download it, and keep a
copy outside HostForge. A backup on the same volume as the database is not an
off-site copy.

## 7. Scheduler (virtual classes and expired attempts)

```cron
* * * * * cd /path/to/app && php artisan schedule:run >> /dev/null 2>&1
```

`routes/console.php` schedules `virtual-classes:auto-close` every minute. It
flips classes whose end time passed to `completed`, deletes the LiveKit room and
closes open attendance rows. Page views also self-heal the status, but the room
deletion only happens here (or when the instructor presses End Meeting).

It also schedules `assessments:auto-submit-expired` every minute. That closes
quiz and exam attempts whose own time limit ran out while the student was away,
using the answers already stored on the server and the same grading rules as the
attempt pages. It waits the same two-minute grace window as the browser so the
page's own final submission can land first. It also closes attempts whose
assessment deadline (`availability_until` for quizzes, `ends_at` for exams, or a
per-student extension) has passed, even when the assessment has no time limit.
Timed assessments with `auto_submit_on_timeout` disabled are left open until
their assessment deadline; if they have neither a timer expiry nor a deadline,
there is nothing automatic to trigger and they need manual handling.

If HostForge exposes a scheduler feature, point it at `php artisan schedule:run`
instead of adding cron by hand.

---

## 8. First deploy — old demo accounts

### Fresh database

`release.sh` migrates and seeds. Because `APP_ENV=production`:

- the four demo logins are **not** created;
- the owner account `johncedrickdayandante6@gmail.com` is created once with
  `LMS_DEV_SEED_PASSWORD` (or the default `Password123!` if unset).

Log in with the owner account and change the password immediately.

### Existing database (seeded by an older deploy)

The demo accounts are still there with a password that is published in this
repository, and they skip the login one-time code. Remove that access **once**,
after the first successful deploy:

```bash
php artisan lms:demo-accounts              # 1. preview: lists the four accounts, writes nothing
php artisan lms:demo-accounts deactivate   # 2. recommended: block logins, keep the rows
# or
php artisan lms:demo-accounts delete       # soft-delete, recoverable in the database
```

If an account has to keep working (a demo site, a panel walkthrough), rotate its
credential instead: each account gets its own random 24-character password,
printed once and never written to the log. Open sessions and API tokens of those
accounts are signed out.

```bash
php artisan lms:demo-accounts rotate-password
```

#### If artisan cannot run — SQL fallback

Deactivate or soft-delete only. A password **cannot** be rotated in SQL: the
column stores a bcrypt hash that only the application can produce, so run the
command for that. This SQL also bypasses the audit log, so prefer the command.

```sql
-- 1. identify — exactly these four addresses, no LIKE matching
SELECT id, email, status, deleted_at FROM users
WHERE email IN ('admin@lms.local','instructor@lms.local','student@lms.local','aa-staff@lms.local');

-- 2a. deactivate (keep the rows)
UPDATE users SET status = 'inactive', updated_at = NOW()
WHERE email IN ('admin@lms.local','instructor@lms.local','student@lms.local','aa-staff@lms.local')
  AND deleted_at IS NULL;

-- 2b. or soft-delete (recoverable: set deleted_at back to NULL)
UPDATE users SET deleted_at = NOW(), updated_at = NOW()
WHERE email IN ('admin@lms.local','instructor@lms.local','student@lms.local','aa-staff@lms.local')
  AND deleted_at IS NULL;

-- optional: sign out their open database sessions
DELETE FROM sessions
WHERE user_id IN (SELECT id FROM users
  WHERE email IN ('admin@lms.local','instructor@lms.local','student@lms.local','aa-staff@lms.local'));
```

Safety behaviour of the command:

- only those four exact email addresses are matched (no role/LIKE matching);
- the write actions ask for confirmation (default **no**); `--force` skips the
  prompt for scripted runs;
- it refuses to remove the last active administrator;
- it revokes the accounts' open sessions and API tokens;
- every change is written to `audit_logs`.

Also remove `LMS_SEED_DEMO_USERS=true` from the environment if an older setup
added it, otherwise the next `migrate --seed` re-creates the accounts.

---

## 9. Post-deployment verification

Run these on the server, in the release directory:

```bash
php artisan lms:check-uploads        # PHP limits vs the app's 200 MB ceiling
php artisan lms:storage-health       # upload/public/backup roots and public link
php artisan lms:demo-accounts        # demo accounts (should be gone/disabled)
php artisan about                    # environment, cache and driver summary
```

Then over HTTPS:

1. `https://<domain>/up` → **204** (no body)
2. Log in with the owner account; the one-time code email must arrive
3. Open **Admin → Sync from AA** — connection status and last sync are shown
4. Create a virtual class → **Start Class** → **Join Virtual Classroom**; the
   status must read `Connected as …`. If a `LIVEKIT_*` banner appears, one of
   the three values is missing or malformed (section 1)
5. Upload a small file, then a file near 200 MB, to confirm the three ceilings
   agree (section 5)

---

## 10. Troubleshooting

### `419` session expired on login

1. `APP_URL` exactly matches the address bar (scheme included)
2. `SESSION_DOMAIN` is empty
3. `SESSION_SECURE_COOKIE=true` on HTTPS
4. `SANCTUM_STATEFUL_DOMAINS` lists the same host
5. `php artisan config:cache`, clear cookies, reload

### `413 Request Entity Too Large`

The request never reached Laravel. Raise `post_max_size` (PHP) and, if present,
`client_max_body_size` (nginx); confirm with `php artisan lms:check-uploads`.
Raising the values in `config/lms.php` alone will not help.

### Pages 500 with "Vite manifest not found"

`npm ci && npm run build` did not run, or `public/build` was wiped. Re-run the
build step; `public/hot` must not exist in production (release.sh removes it).

### Join button disabled / `LIVEKIT_*` banner

One of the three LiveKit values is missing, the URL is not `ws(s)://`, or the
key/secret is shorter than the required length. Fix the env, then
`php artisan config:cache`.

### Login works but emails never arrive

SMTP credentials are wrong (Gmail needs an app password) or `MAIL_*` is
incomplete. Check `storage/logs/laravel.log`; `QUEUE_CONNECTION=sync` means the
mail is sent during the request, so a failure is logged immediately.

### Uploads or backups vanish after a redeploy

`LMS_STORAGE_PATH` is unset, still points inside the release directory, or the
HostForge volume is not actually mounted. Run `php artisan lms:storage-health`;
a path inside the release directory is a warning, and `--verify-marker` after a
redeploy is the proof. Restore the missing bytes from the last external backup
before publishing again.

### Migrations fail

Check the database name (`lms-db` in the current HostForge project — not
`hf_db_htrkhzcm`), the user's `CREATE/ALTER/DROP` grants, and that the database
exists.

### Sync from AA fails

`AA_DB_*` points at the wrong host/database, the read-only user lacks `SELECT`,
or `config/aa_sync.php` does not match the AA table/column names. The failure is
recorded in the sync history with the driver message. The LMS keeps working
without AA.
