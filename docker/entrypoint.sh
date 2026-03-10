#!/bin/sh
set -e

cd /var/www/html

# Generate key if not set
if [ -z "$APP_KEY" ]; then
  php artisan key:generate --force
fi

# Clear & cache config for production
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Run migrations
php artisan migrate --force --no-interaction

# Fix permissions
chown -R www-data:www-data /var/www/html/storage bootstrap/cache

# Start supervisor
exec /usr/bin/supervisord -n -c /etc/supervisor/conf.d/supervisord.conf
