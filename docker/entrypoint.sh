#!/bin/sh
set -e

cd /var/www/html

# Generate key if not set
if [ -z "$APP_KEY" ]; then
  php artisan key:generate --force
fi

# Set APP_URL dynamically in production
if [ -n "$HOSTNAME" ] && [ "$APP_ENV" != "local" ]; then
  export APP_URL="https://${HOSTNAME}"
elif [ -z "$APP_URL" ] || [ "$APP_URL" = "http://wellcore-api.test" ]; then
  # Fallback: try to detect from HTTP_HOST or use default
  export APP_URL="https://wellcorefitness-wellcore-api.v9xcpt.easypanel.host"
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
