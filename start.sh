#!/bin/sh
set -e

echo "Waiting for database to be ready..."
# Небольшая задержка, чтобы база точно проснулась
sleep 5

echo "Running database migrations..."
cd /app && php bin/console doctrine:migrations:migrate --no-interaction --allow-no-migration

echo "Starting PHP-FPM..."
php-fpm -F
