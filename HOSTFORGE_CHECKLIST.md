# HostForge Deployment Checklist

## 🔴 CRITICAL: Must Fix Immediately

### 1. Database Name Mismatch ⚠️
**Current setting:** `DB_DATABASE=hf_db_htrkhzcm`
**Actual database name:** `lms-db`

**Action Required:**
- Go to HostForge Environment Variables
- Change `DB_DATABASE` from `hf_db_htrkhzcm` to `lms-db`
- Save and redeploy

This is likely causing the "Unknown database" error.

## ✅ Current Configuration (Looks Good)

### Database
- ✅ `DB_CONNECTION=mariadb` (correct for MariaDB)
- ✅ `DB_HOST=mariadb-htrkhzcm.internal` (internal HostForge host)
- ✅ `DB_PORT=3306`
- ⚠️ `DB_DATABASE=hf_db_htrkhzcm` (WRONG - should be `lms-db`)
- ✅ `DB_USERNAME=hf_oltois9u8g8g`
- ✅ `DB_PASSWORD=***` (set)

### Application
- ✅ `APP_ENV=production`
- ✅ `APP_DEBUG=false`
- ✅ `APP_URL=https://lms-lms-2.hostforgeplatforms.com`
- ✅ `APP_KEY=base64:5zVdQlTGbey23ciopMvaZC/TFhPiHgOxAw4sIKqK3DE=`
- ✅ `APP_NAME=LMS`

### Session & Cache
- ✅ `SESSION_DRIVER=database`
- ✅ `CACHE_STORE=database`
- ✅ `QUEUE_CONNECTION=sync`
- ✅ `SESSION_SECURE_COOKIE=true`
- ✅ `SESSION_SAME_SITE=lax`

### Mail
- ✅ `MAIL_MAILER=smtp`
- ✅ `MAIL_HOST=smtp.gmail.com`
- ✅ `MAIL_PORT=587`
- ✅ `MAIL_USERNAME=lmsprojectzxc@gmail.com`
- ✅ `MAIL_PASSWORD=***` (16-char app password)
- ✅ `MAIL_ENCRYPTION=tls`
- ✅ `MAIL_FROM_ADDRESS=lmsprojectzxc@gmail.com`
- ✅ `MAIL_FROM_NAME=LMS`

### Sanctum
- ✅ `SANCTUM_STATEFUL_DOMAINS=lms-lms-2.hostforgeplatforms.com`

## 📋 Verify These HostForge Settings

### 1. Release Command
Make sure the release command is set to:
```
php artisan migrate --force
```

### 2. Build Command
Make sure the build command is set to:
```
npm install && npm run build
```

### 3. Start Command
Make sure the start command is (or Procfile is used):
```
php artisan serve --host=0.0.0.0 --port="${PORT:-8080}"
```

### 4. Health Check Path
Set health check path to:
```
/up
```

## 🔍 After Fixing Database Name

1. Update `DB_DATABASE=lms-db` in HostForge environment variables
2. Redeploy the application
3. Check deployment logs for:
   - Database connection success
   - Migration completion
   - Web server startup
4. Run diagnostic via browser to verify database status:
   ```
   https://lms-lms-2.hostforgeplatforms.com/diagnostic?secret=<YOUR_APP_KEY>
   ```
   Replace `<YOUR_APP_KEY>` with your actual APP_KEY from environment variables
5. Test the application at `https://lms-lms-2.hostforgeplatforms.com`

## 🐛 If Still Failing After Database Fix

Check for these common issues:

### Database User Permissions
Ensure the database user `hf_oltois9u8g8g` has:
- CREATE TABLE
- ALTER TABLE
- DROP TABLE
- SELECT, INSERT, UPDATE, DELETE

### Storage Permissions
The application needs write access to:
- `storage/logs`
- `storage/framework`
- `storage/app`

### Composer Dependencies
Ensure `composer install` runs during build to install PHP dependencies.

## 📞 Need Help?

If deployment still fails after fixing the database name:
1. Copy the error message from HostForge logs
2. Check which step fails (build, release, or runtime)
3. Verify database user permissions in HostForge panel
4. Ensure all environment variables are correct

## 🔐 Security Notes

- ✅ `APP_DEBUG=false` (correct for production)
- ✅ Database credentials are in environment variables (not committed)
- ✅ APP_KEY is set and secure
- ✅ SMTP credentials are using app password (not Gmail password)
