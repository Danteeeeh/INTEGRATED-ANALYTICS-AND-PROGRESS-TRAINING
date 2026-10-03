#!/bin/bash

# Release script for HostForge deployment
# This runs after the build is complete

# Discover packages (moved from composer.json to avoid build errors)
php artisan package:discover --ansi

# Create storage link for file uploads
php artisan storage:link

# Run migrations
php artisan migrate --force

# Clear and cache configs
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Clear expired sessions and cache
php artisan cache:clear
php artisan session:clear

# Set proper permissions
chmod -R 775 storage bootstrap/cache
