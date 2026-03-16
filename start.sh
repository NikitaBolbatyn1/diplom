#!/bin/sh
set -e

echo "=== DEBUG INFO ==="
echo "Current directory: $(pwd)"
echo "PHP version:"
php -v
echo "PHP-FPM config test:"
php-fpm -t -y /usr/local/etc/php-fpm.conf
echo "Nginx config test:"
nginx -t
echo "=== END DEBUG ==="

echo "Waiting for database to be ready..."
sleep 5

echo "Running database migrations..."
cd /app && php bin/console doctrine:migrations:migrate --no-interaction --allow-no-migration || echo "Migrations failed but continuing"

# Запускаем PHP-FPM в фоне с правильным конфигом
echo "Starting PHP-FPM with config /usr/local/etc/php-fpm.conf..."
php-fpm -y /usr/local/etc/php-fpm.conf -D

# Проверяем, что PHP-FPM запустился
sleep 3
if ! pgrep -f "php-fpm" > /dev/null; then
    echo "ERROR: PHP-FPM failed to start"
    php-fpm -t -y /usr/local/etc/php-fpm.conf
    exit 1
fi
echo "PHP-FPM started successfully"

# Запускаем Nginx на переднем плане
echo "Starting Nginx..."
nginx -g 'daemon off;'
