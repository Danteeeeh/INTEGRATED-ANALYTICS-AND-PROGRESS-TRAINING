#!/bin/bash

# Release script for HostForge deployment
# This runs after the build is complete

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

# Create storage link for file uploads
php artisan storage:link

# Run migrations with seeders
php artisan migrate --force --seed

# Clear and cache configs
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Clear expired sessions and cache
php artisan cache:clear
php artisan session:clear

# Set proper permissions
chmod -R 775 storage bootstrap/cache

# Clear old build artifacts
rm -rf public/hot
