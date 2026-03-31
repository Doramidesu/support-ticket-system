#!/bin/bash

# Exit on any error
set -e

echo "Starting Laravel application..."

# Wait for MySQL to be ready
echo "Waiting for MySQL..."
until php artisan db:show 2>/dev/null || mysql -h"${DB_HOST}" -u"${DB_USERNAME}" -p"${DB_PASSWORD}" -e "SELECT 1" 2>/dev/null; do
    echo "MySQL is unavailable - sleeping"
    sleep 2
done

echo "MySQL is up - executing commands"

# Generate application key if not set
if [ -z "$APP_KEY" ] || [ "$APP_KEY" = "" ]; then
    echo "Generating application key..."
    php artisan key:generate --force
fi

# Set proper permissions
echo "Setting permissions..."
chown -R www-data:www-data /var/www/storage /var/www/bootstrap/cache
chmod -R 775 /var/www/storage /var/www/bootstrap/cache

# Run migrations
echo "Running migrations..."
php artisan migrate --force

# Clear and cache config
echo "Optimizing application..."
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Start Laravel development server
echo "Starting Laravel server..."
exec php artisan serve --host=0.0.0.0 --port=8000
