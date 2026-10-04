# HostForge Deployment - Ready for Production ✅

## 📋 Deployment Checklist

### ✅ Configuration Files Updated
- **.env.example** - Updated with correct HostForge settings
  - APP_URL: https://lms-lms-2-gecim.hostforgeplatforms.com
  - DB_CONNECTION: mariadb (correct for HostForge)
  - SANCTUM_STATEFUL_DOMAINS: Updated with correct domain

- **bootstrap/app.php** - Added trusted host for production domain
  - Added `lms-lms-2-gecim.hostforgeplatforms.com` to trusted hosts

- **release.sh** - Enhanced to run seeders
  - Added `--seed` flag to migrate command for initial data

- **start.sh** - Ready for HostForge
  - Creates necessary storage directories
  - Sets proper permissions
  - Starts on correct PORT

- **Procfile** - Configured
  - `web: bash start.sh`

### 🔧 HostForge Settings to Configure

#### 1. Environment Variables
Set these in HostForge Environment Variables:

```dotenv
APP_ENV=production
APP_DEBUG=false
APP_URL=https://lms-lms-2-gecim.hostforgeplatforms.com
APP_KEY=<generate-with-php-artisan-key-generate-show>

DB_CONNECTION=mariadb
DB_HOST=<your-hostforge-database-host>
DB_PORT=3306
DB_DATABASE=<your-hostforge-database-name>
DB_USERNAME=<your-hostforge-database-user>
DB_PASSWORD=<your-hostforge-database-password>

SESSION_DRIVER=database
CACHE_STORE=database
QUEUE_CONNECTION=sync
SESSION_SECURE_COOKIE=true
SESSION_SAME_SITE=lax
SESSION_DOMAIN=

MAIL_MAILER=smtp
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=<your-gmail>
MAIL_PASSWORD=<your-16-char-app-password>
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=<your-gmail>
MAIL_FROM_NAME="LMS"

SANCTUM_STATEFUL_DOMAINS=lms-lms-2-gecim.hostforgeplatforms.com

LMS_LOGIN_MAX_ATTEMPTS=5
LMS_LOGIN_LOCKOUT_MINUTES=15
LMS_SESSION_INACTIVITY_MINUTES=30
LMS_LOGIN_OTP_ENABLED=true
LMS_LOGIN_OTP_EXPIRES_MINUTES=10
LMS_LOGIN_OTP_MAX_ATTEMPTS=5
LMS_LOGIN_OTP_RESEND_COOLDOWN_SECONDS=60
LMS_LOGIN_OTP_SESSION_MINUTES=15
```

#### 2. Build Command
```
npm install && npm run build
```

#### 3. Release Command
```
bash release.sh
```

This will:
- Generate APP_KEY if not set
- Discover packages
- Create storage link
- Run migrations with seeders
- Cache configs, routes, views
- Clear cache and sessions
- Set permissions

#### 4. Start Command
Leave empty (Procfile will be used) OR set to:
```
bash start.sh
```

#### 5. Health Check Path
```
/up
```

#### 6. Working Directory
```
lms/
```
(If deploying from the INTEGRATED-ANALYTICS-AND-PROGRESS-TRAINING repo)

### 🚀 Deployment Steps

1. **Push to GitHub**
   ```bash
   git add .
   git commit -m "Ready for HostForge deployment"
   git push origin master
   ```

2. **Configure HostForge**
   - Connect GitHub repository: `Danteeeeh/INTEGRATED-ANALYTICS-AND-PROGRESS-TRAINING`
   - Set branch to: `master`
   - Set working directory to: `lms/`
   - Configure environment variables (see above)
   - Set build, release, and health check commands
   - Enable auto-deploy

3. **Monitor Deployment**
   - Watch build logs for npm install/build success
   - Watch release logs for migration success
   - Verify health check passes at `/up`

4. **Test Application**
   - Visit: https://lms-lms-2-gecim.hostforgeplatforms.com
   - Test login with seeded credentials
   - Verify all features work

### 🔍 Post-Deployment Verification

1. **Check Database Connection**
   - Visit: https://lms-lms-2-gecim.hostforgeplatforms.com/up
   - Should return 204 No Content

2. **Check Login**
   - Try logging in with seeded admin credentials
   - Check if OTP is sent via email

3. **Check File Uploads**
   - Test uploading files
   - Verify storage link is working

4. **Check Email**
   - Test OTP email delivery
   - Verify SMTP configuration

### ⚠️ Important Notes

1. **Database Name**
   - According to HOSTFORGE_CHECKLIST.md, the database name should be `lms-db`
   - Do NOT use `hf_db_htrkhzcm` (this was incorrect)
   - Set `DB_DATABASE=lms-db` in HostForge environment variables

2. **APP_KEY**
   - Generate locally: `php artisan key:generate --show`
   - Copy the output and set as APP_KEY in HostForge
   - Do NOT commit APP_KEY to repository

3. **Database User Permissions**
   - Ensure database user has CREATE, ALTER, DROP permissions
   - Needed for migrations to run successfully

4. **Session & Cache**
   - Both use database driver (recommended for production)
   - Ensures sessions persist across deployments

5. **SMTP Configuration**
   - Use Gmail app password (not regular password)
   - Enable 2FA on Gmail account
   - Generate 16-character app password

### 🐛 Troubleshooting

#### 419 Session Expired Error
If you get "419 Your session expired" on login:

**Cause:** Domain mismatch or session configuration issue

**Fix:**
1. Set these environment variables in HostForge:
   ```dotenv
   APP_URL=https://lms-lms-2-gecim.hostforgeplatforms.com
   SESSION_DOMAIN=
   SANCTUM_STATEFUL_DOMAINS=lms-lms-2-gecim.hostforgeplatforms.com
   ```
2. Ensure `SESSION_DOMAIN` is EMPTY (not set to any value)
3. Ensure `SESSION_SECURE_COOKIE=true`
4. Ensure `SESSION_SAME_SITE=lax`
5. Clear session cache: Add to release command
   ```bash
   php artisan session:clear
   ```
6. Clear browser cookies for the site
7. Redeploy the application

**Why SESSION_DOMAIN should be empty:**
- Setting SESSION_DOMAIN restricts cookies to specific domains
- Empty value allows cookies to work on the exact domain
- Only set SESSION_DOMAIN if using subdomains (e.g., `.bcpsms2.com`)

#### If health check fails
- Check if web server is starting on correct PORT
- Verify start.sh is executable
- Check deployment logs for errors

#### If migrations fail
- Verify database credentials are correct
- Check database user permissions
- Ensure database exists in HostForge

#### If login fails
- Verify sessions table exists
- Check if seeders ran successfully
- Clear session cache: `php artisan session:clear`

#### If frontend assets don't load
- Verify npm build ran successfully
- Check if vite build completed
- Clear browser cache

### 📞 Support

If deployment fails:
1. Check HostForge deployment logs
2. Verify all environment variables are set
3. Ensure database is accessible from app
4. Review error messages in logs

---

**Deployment Status: ✅ READY**

All configuration files have been updated and are ready for HostForge deployment.
