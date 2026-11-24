# HostForge Deployment Checklist

Tick these in order. Full explanations live in `HOSTFORGE_DEPLOYMENT_READY.md`.

---

## 0. Before you deploy

- [ ] PHP **8.2+**, Node **18+** (Vite 5; Node 20 LTS recommended), Composer 2 available in the build environment
- [ ] `APP_KEY` generated once and stored as a HostForge secret:
      `php artisan key:generate --show`
      If an old key was ever committed anywhere, rotate it and treat the old one as exposed.
- [ ] `APP_DEBUG=false`
- [ ] Database name matches the real one in this project:
      `DB_DATABASE=lms-db` — **not** `hf_db_htrkhzcm`
- [ ] Database user has `CREATE`, `ALTER`, `DROP`, `SELECT`, `INSERT`, `UPDATE`, `DELETE`

---

## 1. Environment variables

### Core

- [ ] `APP_NAME=LMS`
- [ ] `APP_ENV=production`
- [ ] `APP_URL=https://<your-domain>` (exact scheme + host)
- [ ] `DB_CONNECTION=mariadb`, `DB_HOST`, `DB_PORT=3306`, `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD`
- [ ] `SESSION_DRIVER=database`, `CACHE_STORE=database`, `QUEUE_CONNECTION=sync`
- [ ] `SESSION_SECURE_COOKIE=true`, `SESSION_SAME_SITE=lax`, `SESSION_DOMAIN=` **(empty)**
- [ ] `SANCTUM_STATEFUL_DOMAINS=<your-domain>`
- [ ] `MAIL_*` complete, Gmail app password (not the account password)

### Virtual classroom (LiveKit)

- [ ] `LIVEKIT_URL=wss://<project>.livekit.cloud` — must start with `ws://` or `wss://`
- [ ] `LIVEKIT_API_KEY` — at least **8** characters
- [ ] `LIVEKIT_API_SECRET` — at least **16** characters
- [ ] Site is served over **HTTPS** (camera/mic will not work otherwise)

Missing or malformed values do not crash the app: the Join button is disabled and
a banner names the exact variable. Fix the env, then `php artisan config:cache`.

### Academic Affairs (optional, read-only)

- [ ] `AA_DB_HOST`, `AA_DB_PORT`, `AA_DB_DATABASE`, `AA_DB_USERNAME`, `AA_DB_PASSWORD`
- [ ] Account is **read-only** (the sync only reads)
- [ ] `config/aa_sync.php` matches the real AA table/column names
- [ ] No shared-password override is configured; every new account gets its
      own random password
- [ ] The automatic activation email arrives for a new AA account; if not,
      **Forgot your password?** is reachable and a reset email arrives

Without AA the LMS still runs; **Sync from AA** just reports it unavailable.

### Demo accounts (decide once)

- [ ] `LMS_SEED_DEMO_USERS` is **unset** (if an old setup set it to `true`, remove it)
- [ ] `LMS_DEV_SEED_PASSWORD=<your-own-password>` set **before the first deploy** —
      it becomes the owner account's initial password
- [ ] Remember: on `APP_ENV=production` the four `@lms.local` demo logins are not created

---

## 2. Commands

### Build command

```bash
composer install --no-dev --optimize-autoloader && npm ci && npm run build
```

- [ ] Copied exactly as above (`composer` first, then `npm`)
- [ ] `vendor/` and `public/build/` are gitignored — they only exist because this step ran

### Release command

```bash
bash release.sh
```

- [ ] Runs: `key:generate` (if needed) → `package:discover` → `storage:link` →
      `migrate --force --seed` → `config:cache` → `route:cache` → `view:cache` →
      `cache:clear` → permissions → removes `public/hot`

### Start command

```bash
bash start.sh
```

- [ ] Or leave empty so the `Procfile` (`web: bash start.sh`) is used
- [ ] `PORT` is injected by the platform; the script falls back to `8080`

### Health check

- [ ] Path set to `/up`
- [ ] Returns **204** with no body (answered before Laravel boots)

### Scheduler

- [ ] Cron (or HostForge scheduler) runs every minute:
      `* * * * * cd /path/to/app && php artisan schedule:run >> /dev/null 2>&1`
      Needed for `virtual-classes:auto-close` (closes classes and deletes rooms)
      and `assessments:auto-submit-expired` (closes expired quiz/exam attempts,
      including attempts past the assessment deadline).

---

## 3. Persistent storage — uploads and backups

- [ ] Persistent volume attached in HostForge and mounted **outside** the release directory (e.g. `/data/lms`)
- [ ] `LMS_STORAGE_PATH` set to that exact absolute path
- [ ] Existing `storage/app` files copied once into the volume:
      `cp -a storage/app/. "$LMS_STORAGE_PATH"/`
- [ ] Release command is `bash release.sh` (it creates `public`, `backups`, recreates `public/storage --force`, and runs the preflight)
- [ ] `php artisan lms:storage-health` → uploads, public uploads and backups writable; no "inside the release directory" warning
- [ ] `php artisan lms:storage-health --write-marker` run, then a redeploy, then `--verify-marker` passes
- [ ] `php artisan lms:backup-restore-test` passes
- [ ] One backup downloaded from **Admin → Backup & Restore** and stored outside HostForge
- [ ] **Do not publish** if the marker is missing or storage is inside the release directory

If HostForge has no persistent volume on this plan, stop here. A container-local
path is not durable; use a plan with a volume, or move uploads and backups to an
external storage target before publishing.

## 4. Upload ceilings — set all three

The app accepts up to **200 MB** (module/lesson material) and validates in KB.

```ini
; php.ini
upload_max_filesize = 200M
post_max_size = 210M        ; larger than upload_max_filesize
memory_limit = 256M
max_execution_time = 120
```

```nginx
# only if nginx is in front
client_max_body_size 210M;
```

- [ ] `upload_max_filesize` ≥ **200M**
- [ ] `post_max_size` > `upload_max_filesize` (**210M** recommended)
- [ ] `client_max_body_size` ≥ `post_max_size` if nginx/Apache enforces it
- [ ] Verified on the server: `php artisan lms:check-uploads` → no warnings

A 413 with no Laravel log line means one of the two lower ceilings rejected the
request before the app ran.

---

## 5. First deploy with an existing database — old demo accounts

Older deploys seeded `admin@lms.local`, `instructor@lms.local`,
`student@lms.local`, `aa-staff@lms.local`. Their password is published in this
repository and they skip the login one-time code.

- [ ] Run the preview first (writes nothing):
      `php artisan lms:demo-accounts`
- [ ] Confirm the table lists only those four addresses
- [ ] Remove their access (pick one):
      `php artisan lms:demo-accounts deactivate`  ← recommended, reversible
      `php artisan lms:demo-accounts delete`      ← soft delete, recoverable
- [ ] Or, if an account must keep working, rotate instead:
      `php artisan lms:demo-accounts rotate-password`  ← unique random password per account, shown once
- [ ] Answer `yes` at the prompt; `--force` exists for scripted runs only
- [ ] Confirm the command did **not** touch the owner account or real users

On a **fresh** database skip this section: the demo accounts are not created at
all under `APP_ENV=production`, and only the owner account is seeded.

---

## 6. Post-deploy verification

### On the server

- [ ] `php artisan lms:check-uploads` — no "below the app limit" warnings
- [ ] `php artisan lms:storage-health` — writable roots and matching `public/storage`
- [ ] `php artisan lms:demo-accounts` — demo accounts gone or `inactive`
- [ ] `php artisan about` — environment `production`, correct drivers

### Over HTTPS

- [ ] `https://<domain>/up` → **204**
- [ ] Owner login works and the one-time code email arrives
- [ ] **Admin → Sync from AA** shows a connection status (or a clear failure)
- [ ] Create a virtual class → **Start Class** → **Join Virtual Classroom** →
      status shows `Connected as …`, participants list updates
- [ ] A second browser joins the same room as a student (2 participants, chat works)
- [ ] Upload a small file and one near 200 MB
- [ ] A page with images/CSS loads with no 404s (Vite build present)

---

## 7. Fast troubleshooting

| Symptom | Cause | Fix |
|---|---|---|
| `419` on login | domain/session mismatch | `APP_URL` exact, `SESSION_DOMAIN` empty, `SESSION_SECURE_COOKIE=true`, `SANCTUM_STATEFUL_DOMAINS` matches, `config:cache` |
| `413` on upload | PHP/nginx ceiling | raise `post_max_size` + `client_max_body_size`; run `lms:check-uploads` |
| 500 "Vite manifest not found" | build skipped | `npm ci && npm run build`; ensure no `public/hot` |
| Join disabled / `LIVEKIT_*` banner | bad LiveKit env | URL `ws(s)://`, key ≥ 8, secret ≥ 16, then `config:cache` |
| Uploads/backups vanish after redeploy | storage is container-local | set `LMS_STORAGE_PATH` to the persistent volume, copy existing files, run the marker test |
| Class stays `ongoing` after end | scheduler not running | add the cron entry above |
| `Sync from AA` fails | wrong `AA_DB_*` or mapping | check sync history message; verify `config/aa_sync.php` |
| Migrations fail | DB name/grants | `DB_DATABASE=lms-db`, user has `CREATE/ALTER/DROP` |
| Emails not sent | SMTP | Gmail app password, complete `MAIL_*`, check `storage/logs/laravel.log` |

---

## 8. Security notes

- [ ] `APP_DEBUG=false`
- [ ] `APP_KEY` only in the platform secret store (never committed)
- [ ] Database and SMTP credentials only in environment variables
- [ ] `SESSION_SECURE_COOKIE=true`
- [ ] Demo logins removed/disabled; owner password changed after the first login
- [ ] AA database user is read-only
