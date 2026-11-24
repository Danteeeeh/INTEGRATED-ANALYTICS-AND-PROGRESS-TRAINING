#!/bin/bash

# Startup script for HostForge
# This runs when the container starts

set -e

echo "Starting application..."

# Ensure .env file exists
if [ ! -f .env ]; then
    echo "Creating .env file from .env.example..."
    cp .env.example .env
fi

# Ensure storage directories exist
mkdir -p storage/framework/cache
mkdir -p storage/framework/sessions
mkdir -p storage/framework/views
mkdir -p storage/logs

# The HostForge persistent volume is mounted outside the release directory.
if [ -n "${LMS_STORAGE_PATH:-}" ]; then
    mkdir -p "$LMS_STORAGE_PATH/public" "$LMS_STORAGE_PATH/backups"
fi

echo "Storage directories created"

# Set permissions
chmod -R 775 storage bootstrap/cache

echo "Permissions set"

# Log the PORT
echo "PORT is set to: ${PORT:-8080}"

# Start the application
echo "Starting PHP artisan serve on port ${PORT:-8080}..."
php artisan serve --host=0.0.0.0 --port=${PORT:-8080}
