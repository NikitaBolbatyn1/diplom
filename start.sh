#!/bin/sh
set -e

echo "=== DEBUG INFO ==="
echo "Current directory: $(pwd)"
echo "Script path: $0"
echo "Script permissions: $(ls -la $0)"
echo "Directory contents:"
ls -la /app/
echo "Public directory contents:"
ls -la /app/public/ || echo "Public dir not found"
echo "PHP-FPM configs:"
ls -la /etc/php-fpm.conf /etc/php-fpm.d/ || echo "Config files not found in /etc/"
echo "PHP-FPM version:"
php-fpm -v
echo "Nginx version:"
nginx -v
echo "=== END DEBUG ==="

echo "Waiting for database to be ready..."
sleep 5

echo "Running database migrations..."
if [ -f /app/bin/console ]; then
    cd /app && php bin/console doctrine:migrations:migrate --no-interaction --allow-no-migration || echo "Migrations failed but continuing"
else
    echo "Symfony console not found, skipping migrations"
fi

# Проверка конфигураций с явным указанием пути
echo "Checking PHP-FPM config..."
if [ -f /etc/php-fpm.conf ]; then
    php-fpm -t -y /etc/php-fpm.conf
else
    echo "ERROR: /etc/php-fpm.conf not found!"
    exit 1
fi

echo "Checking Nginx config..."
nginx -t

# Создаем healthcheck файлы
echo "Creating healthcheck files..."
mkdir -p /app/public
echo "<?php echo 'OK';" > /app/public/healthcheck.php
echo "<?php phpinfo();" > /app/public/info.php

# Запускаем PHP-FPM в фоне с явным указанием конфига
echo "Starting PHP-FPM with config /etc/php-fpm.conf..."
php-fpm -y /etc/php-fpm.conf -D

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
