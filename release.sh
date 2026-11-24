#!/bin/bash

# Release script for HostForge deployment
# This runs after the build is complete

# A failed migration, storage preflight or cache build must stop the release
# instead of serving a half-configured application.
set -e

# Create .env file from environment variables if it doesn't exist
if [ ! -f .env ]; then
    cp .env.example .env
fi

# Generate application key if not set
if [ -z "$APP_KEY" ]; then
    php artisan key:generate --ansi
fi

# Discover packages (moved from composer.json to avoid build errors)
php artisan package:discover --ansi

# A HostForge persistent volume is mounted outside the release directory. Make
# the upload/public/backup directories before Laravel needs them.
if [ -n "${LMS_STORAGE_PATH:-}" ]; then
    mkdir -p "$LMS_STORAGE_PATH/public" "$LMS_STORAGE_PATH/backups"
fi

# Point public/storage at the configured public upload root. --force replaces
# a link left behind by a previous release that used a different path.
php artisan storage:link --force

# Run migrations with seeders
php artisan migrate --force --seed

# Clear and cache configs
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Prove the upload and backup roots are writable before the new release serves
# traffic. In production this fails the release when storage is still inside the
# replaceable release directory or the public link is wrong.
php artisan lms:storage-health --require-persistent

# Clear the application cache.
# (`php artisan session:clear` used to be here; no such command exists, so it
# printed an error on every release.)
php artisan cache:clear

# Set proper permissions
chmod -R 775 storage bootstrap/cache

# Clear old build artifacts
rm -rf public/hot
