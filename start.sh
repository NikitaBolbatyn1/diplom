#!/bin/sh
set -e

echo "=== DEBUG INFO ==="
echo "Current directory: $(pwd)"
echo "PHP version:"
php -v
echo "PHP-FPM config test:"
php-fpm -t
echo "Nginx config test:"
nginx -t
echo "=== END DEBUG ==="

echo "Waiting for database to be ready..."
sleep 5

echo "Running database migrations..."
php bin/console doctrine:migrations:migrate --no-interaction --allow-no-migration || echo "Migrations failed but continuing"

# Создаем дополнительные директории если нужно
mkdir -p /app/var/cache/prod /app/var/log /app/var/sessions
chmod -R 777 /app/var

# Запускаем PHP-FPM в фоне
echo "Starting PHP-FPM..."
php-fpm -D

# Проверяем, что PHP-FPM запустился
sleep 3
if ! pgrep -f "php-fpm" > /dev/null; then
    echo "ERROR: PHP-FPM failed to start"
    php-fpm -t
    exit 1
fi
echo "PHP-FPM started successfully"

# Проверяем, что PHP-FPM слушает порт
if command -v netstat > /dev/null; then
    netstat -tlnp | grep 9000 || echo "Warning: Port 9000 not listening"
fi

# Запускаем Nginx на переднем плане
echo "Starting Nginx on port 8080..."
nginx -g 'daemon off;'
