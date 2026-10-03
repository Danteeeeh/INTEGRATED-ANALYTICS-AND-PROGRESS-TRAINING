#!/bin/bash

# Startup script for HostForge
# This runs when the container starts

# Ensure storage directories exist
mkdir -p storage/framework/cache
mkdir -p storage/framework/sessions
mkdir -p storage/framework/views
mkdir -p storage/logs

# Set permissions
chmod -R 775 storage bootstrap/cache

# Start the application
php artisan serve --host=0.0.0.0 --port=${PORT:-8080}
