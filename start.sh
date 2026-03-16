#!/bin/sh
set -e

echo "=== DEBUG INFO ==="
echo "Current directory: $(pwd)"
echo "PHP version:"
php -v
echo "Composer version:"
composer --version || echo "Composer not found"
echo "Nginx version:"
nginx -v
echo "=== END DEBUG ==="

echo "Waiting for database to be ready..."
sleep 5

echo "Running database migrations..."
php bin/console doctrine:migrations:migrate --no-interaction --allow-no-migration || echo "Migrations failed but continuing"

# Проверка конфигов
echo "Checking PHP-FPM config..."
php-fpm -t

echo "Checking Nginx config..."
nginx -t

# Запускаем PHP-FPM в фоне
echo "Starting PHP-FPM..."
php-fpm -D

# Даем время PHP-FPM запуститься
sleep 3

# Проверяем, что PHP-FPM запустился
if pgrep -f "php-fpm" > /dev/null; then
    echo "PHP-FPM started successfully"
else
    echo "ERROR: PHP-FPM failed to start"
    exit 1
fi

# Запускаем Nginx на переднем плане
echo "Starting Nginx..."
nginx -g 'daemon off;'
