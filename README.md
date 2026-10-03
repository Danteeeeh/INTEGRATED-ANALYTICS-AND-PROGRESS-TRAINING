# Online Learning & Learning Management System — Phase 1

Foundation phase: Laravel setup, MySQL schema for identity/access, authentication
(web + Sanctum API), roles, permissions, Blade layouts, Tailwind, and role-specific
dashboards. No Super Admin — exactly three roles: **admin**, **instructor**, **student**.

## Setup (WSL / Ubuntu)

```bash
composer install
cp .env.example .env
php artisan key:generate

# Create the MySQL database first (e.g. `CREATE DATABASE lms;`), then:
php artisan migrate --seed

npm install
npm run build   # or `npm run dev` while working on styles/JS

php artisan serve
```

Seeded accounts (password for all: `Password123!`):

| Role       | Email                  |
|------------|-------------------------|
| Admin      | admin@lms.local         |
| Instructor | instructor@lms.local    |
| Student    | student@lms.local       |

## Run tests

```bash
php artisan test
```

For production deployment, provision a managed MySQL/MariaDB database and
configure the hosting environment variables before running migrations. See
[DEPLOYMENT.md](DEPLOYMENT.md).

## Security: login lockout, inactivity logout, Gmail SMTP

### Account lockout (brute-force protection)
After `LMS_LOGIN_MAX_ATTEMPTS` (default **5**) failed login attempts, the account
is locked for `LMS_LOGIN_LOCKOUT_MINUTES` (default **15**) minutes. Lockout is
tracked per-account in the `users` table (web login and `/api/v1/auth/login`),
on top of the existing per-IP Laravel `throttle` middleware — defense in depth.

| Setting | Env var | Default |
|---|---|---|
| Failed attempts before lock | `LMS_LOGIN_MAX_ATTEMPTS` | 5 |
| Lockout duration | `LMS_LOGIN_LOCKOUT_MINUTES` | 15 |

### Inactivity auto-logout
After `LMS_SESSION_INACTIVITY_MINUTES` (default **30**) minutes without any
request, the session is destroyed server-side and the user is redirected to the
login page. A client-side idle watchdog shows a 60-second countdown warning and
offers "Stay signed in" / "Sign out now" before posting to `/logout`. The server
middleware (`TrackUserActivity`) is authoritative.

| Setting | Env var | Default |
|---|---|---|
| Inactivity timeout | `LMS_SESSION_INACTIVITY_MINUTES` | 30 |

### Real Gmail SMTP for system email
System emails (notifications, etc.) are sent through Gmail SMTP. Configure the
following in `.env`:

```dotenv
MAIL_MAILER=smtp
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=your.school@gmail.com
MAIL_PASSWORD=your-16-char-app-password
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS="your.school@gmail.com"
MAIL_FROM_NAME="${APP_NAME}"
```

Gmail requires a 16-character **App Password** — not your regular Gmail password.
Generate one at Google Account → Security → 2-Step Verification → App passwords
(your Google account must have 2-Step Verification enabled). Then run
`php artisan config:clear` so the new mail settings take effect.

## What Phase 1 includes

- `users`, `roles`, `permissions`, `role_permissions`, `user_preferences`, `audit_logs` tables
- `User`, `Role`, `Permission`, `UserPreference`, `AuditLog` Eloquent models
- Session-based web login (`/login`) redirecting to a role-specific dashboard, and
  a Sanctum-backed `/api/v1/auth/login` for the REST API
- `role:` and `permission:` route middleware for server-side authorization
  (never rely on hidden UI alone)
- `UserPolicy` + `StoreUserRequest` / `UpdateUserRequest` for admin user management
- `AuditService` for recording administrative actions (used by later phases)
- Blade layouts (`app`, `admin`, `instructor`, `student`) with shared
  navbar/sidebar/card/alert components, styled with Tailwind
- Feature tests covering login, role-gated dashboard access, inactive-user
  blocking, and API token issuance

## Not yet built (later phases, per the spec)

Academic periods, courses, classes, enrollment (Phase 2); modules/lessons/materials
(Phase 3); assignments/rubrics (Phase 4); quizzes/question bank (Phase 5); discussions/
announcements/messaging/calendar (Phase 6); virtual classes/attendance (Phase 7);
gradebook/progress/competencies/learning plans (Phase 8); analytics/reports (Phase 9);
full REST API surface + Postman collection (Phase 10); security hardening/deployment
(Phase 11).

**Out of scope:** Achievements (Badges and Certificates) has been removed from this
build's requirements — no `badges`/`certificates` tables, routes, or sidebar links
are planned.

## Note on this build

This project was generated in a sandbox without PHP, Composer, MySQL, or network
access, so the files here were hand-authored rather than run. Before relying on
this as a working app, run `composer install`, `php artisan migrate --seed`, and
`php artisan test` locally to confirm everything boots — file an issue for anything
that doesn't line up with your installed Laravel/PHP version.
