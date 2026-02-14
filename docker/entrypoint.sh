#!/bin/bash

# Clear config cache
php /var/www/artisan config:clear

# Run migrations
php /var/www/artisan migrate --force

# Start PHP-FPM in background
php-fpm -D

# Start Nginx in foreground
nginx -g "daemon off;"
