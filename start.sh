#!/bin/sh
set -e

echo "=== DEBUG INFO ==="
echo "Current directory: $(pwd)"
echo "Directory contents:"
ls -la /app/public/
echo "PHP-FPM version:"
php-fpm -v
echo "Nginx version:"
nginx -v
echo "=== END DEBUG ==="

echo "Waiting for database to be ready..."
sleep 5

echo "Running database migrations..."
cd /app && php bin/console doctrine:migrations:migrate --no-interaction --allow-no-migration

# Проверка конфигураций
echo "Checking PHP-FPM config..."
php-fpm -t

echo "Checking Nginx config..."
nginx -t

# Создаем healthcheck файл, если его нет
echo "Creating healthcheck file..."
echo "<?php echo 'OK';" > /app/public/healthcheck.php
echo "<?php phpinfo();" > /app/public/info.php

# Запускаем PHP-FPM в фоне
echo "Starting PHP-FPM..."
php-fpm -D

# Даем время PHP-FPM запуститься
sleep 3

# Проверяем, что PHP-FPM слушает порт 9000
if ! netstat -tln | grep -q ':9000'; then
    echo "ERROR: PHP-FPM not listening on port 9000"
    exit 1
fi

# Запускаем Nginx на переднем плане
echo "Starting Nginx..."
nginx -g 'daemon off;'
