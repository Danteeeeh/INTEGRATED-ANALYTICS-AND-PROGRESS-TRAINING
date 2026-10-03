# Production deployment

The app container and its database are separate services. Do not use
`DB_HOST=127.0.0.1` or `DB_HOST=localhost` for a managed database: those
addresses point back to the app container, where MySQL is not running.

## HostForge

1. Configure the web process to bind to the platform-provided port. This
   repository includes a `Procfile` with the command below; if HostForge has a
   separate **Start Command** setting that overrides the `Procfile`, set it to
   this exact command instead:

   ```sh
   php artisan serve --host=0.0.0.0 --port="${PORT:-8080}"
   ```

   `0.0.0.0` makes the server reachable outside the container, and `PORT`
   ensures it listens on the port assigned by HostForge. Configure the health
   check path as `/up`. Do not set the web port to the database port.
2. Provision a MySQL or MariaDB database in HostForge and make it reachable
   from the application (prefer the provider's private network).
3. Add the following environment variables to the application. Use the exact
   hostname, database name, username, password, and port from the HostForge
   database connection details; do not commit credentials to this repository.

   ```dotenv
   APP_ENV=production
   APP_DEBUG=false
   APP_URL=https://<the-app-domain-assigned-by-hostforge>
   APP_KEY=<generated-app-key>

   DB_CONNECTION=mysql
   DB_HOST=<hostforge-database-host>
   DB_PORT=3306
   DB_DATABASE=<hostforge-database-name>
   DB_USERNAME=<hostforge-database-user>
   DB_PASSWORD=<hostforge-database-password>

   IMPORTANT: For MariaDB, use DB_CONNECTION=mariadb instead of mysql.

   SESSION_DRIVER=database
   CACHE_STORE=database
   QUEUE_CONNECTION=sync

   MAIL_MAILER=smtp
   MAIL_HOST=<smtp-host>
   MAIL_PORT=587
   MAIL_USERNAME=<smtp-username>
   MAIL_PASSWORD=<smtp-password>
   MAIL_ENCRYPTION=tls
   MAIL_FROM_ADDRESS=<verified-sender-address>
   MAIL_FROM_NAME="LMS"
   ```

   Generate `APP_KEY` locally with `php artisan key:generate --show` and save
   the emitted value as a secret in HostForge. Use your SMTP provider's
   connection details as well: login OTP is enabled, and `MAIL_MAILER=log`
   (the local-development default) does not deliver email. Never commit
   database, app-key, or mail credentials.
4. Confirm the database accepts connections from the app and the database user
   can create and alter tables. Keep the HostForge release command set to
   `php artisan migrate --force`.
5. Redeploy. The `/up` health check should pass when the web process is
   listening on the assigned port. The release command should complete
   migrations once the database connection is correct.

If the deployment still times out at `/up`, check the runtime logs for the
web-process startup command and confirm it uses the HostForge-assigned `PORT`.
The health check runs before the release migration, so a database error in a
later release step is a separate issue.

## Diagnosing a failed release

An error like `SQLSTATE[HY000] [2002] Connection refused` with `Host:
127.0.0.1` means the app is trying to connect to MySQL inside its own
container. Provision/link the database and replace `DB_HOST` and the other
`DB_*` variables with the values shown in HostForge. Do not skip or remove the
migration release command to hide this error; the app needs its schema before
it can serve requests.

The `127.0.0.1` value in `.env.example` is for local development only. A
container's local filesystem is also not a suitable production database: it
may be discarded when the app is redeployed or restarted.

## Common Deployment Issues

### Database Name Mismatch
Error: `SQLSTATE[HY000] [1049] Unknown database`
- Cause: `DB_DATABASE` environment variable doesn't match the actual database name in HostForge
- Fix: Go to HostForge database panel and copy the exact database name, then update `DB_DATABASE` in environment variables

### Database Connection Failed
Error: `SQLSTATE[HY000] [2002] Connection refused`
- Cause: Using `127.0.0.1` or `localhost` for `DB_HOST` instead of the HostForge internal host
- Fix: Use the internal hostname provided by HostForge (e.g., `mariadb-xxxxx.internal`)

### Migration Failed
Error: `Access denied for user` or `CREATE TABLE permission denied`
- Cause: Database user doesn't have sufficient permissions
- Fix: Ensure the database user has CREATE, ALTER, and DROP permissions

### Build Command Missing
Error: Frontend assets not loading (404 on CSS/JS)
- Cause: `npm run build` not executed during deployment
- Fix: Add build command to HostForge: `npm install && npm run build`

### Health Check Failing
Error: `/up` returns 404 or 500
- Cause: Health check route not configured or web server not starting
- Fix: Ensure `/up` route exists in routes/web.php and Procfile is correct

### Login Fails with "These credentials do not match our records"
Error: Login fails even with correct credentials
- Cause: Database migrations didn't run or seeders didn't create user accounts
- Fix: Run diagnostics and fix database:
  ```bash
  php artisan db:diagnose
  php artisan migrate --force --seed
  ```
