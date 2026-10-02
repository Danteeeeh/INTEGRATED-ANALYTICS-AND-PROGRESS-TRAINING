# Production deployment

The app container and its database are separate services. Do not use
`DB_HOST=127.0.0.1` or `DB_HOST=localhost` for a managed database: those
addresses point back to the app container, where MySQL is not running.

## HostForge

1. Provision a MySQL or MariaDB database in HostForge and make it reachable
   from the application (prefer the provider's private network).
2. Add the following environment variables to the application. Use the exact
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
3. Confirm the database accepts connections from the app and the database user
   can create and alter tables. Keep the HostForge release command set to
   `php artisan migrate --force`.
4. Redeploy. The release command should complete migrations once the database
   connection is correct. The app health endpoint is `/up`.

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
