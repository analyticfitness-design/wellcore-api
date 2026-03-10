#!/bin/sh
set -e

cd /var/www/html

# Generate key if not set
if [ -z "$APP_KEY" ]; then
  php artisan key:generate --force
fi

# IMPORTANT: Clear caches BEFORE setting APP_URL to avoid stale config
php artisan config:clear
php artisan route:clear
php artisan view:clear
php artisan cache:clear

# Set APP_URL dynamically in production
if [ "$APP_ENV" != "local" ]; then
  export APP_URL="https://wellcorefitness-wellcore-api.v9xcpt.easypanel.host"
fi

# Re-cache config for production
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Run migrations
php artisan migrate --force --no-interaction

# Fix permissions
chown -R www-data:www-data /var/www/html/storage bootstrap/cache

# Start supervisor
exec /usr/bin/supervisord -n -c /etc/supervisor/conf.d/supervisord.conf
