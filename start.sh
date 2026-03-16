#!/bin/sh
set -e

# Устанавливаем права на выполнение для самого себя
chmod +x "$0"

echo "=== DEBUG INFO ==="
echo "Current directory: $(pwd)"
echo "Script path: $0"
echo "Script permissions: $(ls -la $0)"
echo "Directory contents:"
ls -la /app/
echo "Public directory contents:"
ls -la /app/public/ || echo "Public dir not found"
echo "PHP-FPM version:"
php-fpm -v || echo "PHP-FPM not found"
echo "Nginx version:"
nginx -v || echo "Nginx not found"
echo "=== END DEBUG ==="

echo "Waiting for database to be ready..."
sleep 5

echo "Running database migrations..."
if [ -f /app/bin/console ]; then
    cd /app && php bin/console doctrine:migrations:migrate --no-interaction --allow-no-migration || echo "Migrations failed but continuing"
else
    echo "Symfony console not found, skipping migrations"
fi

# Проверка конфигураций
echo "Checking PHP-FPM config..."
php-fpm -t

echo "Checking Nginx config..."
nginx -t

# Создаем healthcheck файлы
echo "Creating healthcheck files..."
mkdir -p /app/public
echo "<?php echo 'OK';" > /app/public/healthcheck.php
echo "<?php phpinfo();" > /app/public/info.php
echo "Healthcheck files created:"
ls -la /app/public/healthcheck.php /app/public/info.php

# Запускаем PHP-FPM в фоне
echo "Starting PHP-FPM..."
php-fpm -D

# Даем время PHP-FPM запуститься
sleep 3

# Проверяем, что PHP-FPM слушает порт 9000
if command -v netstat >/dev/null 2>&1; then
    if ! netstat -tln | grep -q ':9000'; then
        echo "WARNING: PHP-FPM might not be listening on port 9000"
    else
        echo "PHP-FPM is listening on port 9000"
    fi
fi

# Запускаем Nginx на переднем плане
echo "Starting Nginx..."
exec nginx -g 'daemon off;'
