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

# Исправляем DebugBundle если нужно
if grep -q "DebugBundle" /app/config/bundles.php 2>/dev/null; then
    echo "DebugBundle found in bundles.php, installing..."
    cd /app && composer require symfony/debug-bundle --no-interaction || true
fi

# ===== ДОБАВЛЕНО: Создание validation.xml для Symfony Form =====
echo "Creating Symfony Form validation.xml..."
mkdir -p /app/vendor/symfony/form/Resources/config/
cat > /app/vendor/symfony/form/Resources/config/validation.xml << 'EOF'
<?xml version="1.0" encoding="UTF-8" ?>
<constraint-mapping xmlns="http://symfony.com/schema/dic/constraint-mapping"
    xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
    xsi:schemaLocation="http://symfony.com/schema/dic/constraint-mapping https://symfony.com/schema/dic/constraint-mapping/constraint-mapping-1.0.xsd">

    <class name="DummyConstraintClass">
        <!-- Этот пустой класс удовлетворяет требованиям схемы -->
    </class>
</constraint-mapping>
EOF

echo "Running database migrations..."
cd /app && php bin/console doctrine:migrations:migrate --no-interaction --allow-no-migration || echo "Migrations failed but continuing"

# Запускаем PHP-FPM в фоне
echo "Starting PHP-FPM with config /usr/local/etc/php-fpm.conf..."
php-fpm -y /usr/local/etc/php-fpm.conf -D

# Проверяем, что PHP-FPM запустился
sleep 3
if [ -f /run/php-fpm.pid ] || ps aux | grep -v grep | grep -q php-fpm; then
    echo "PHP-FPM started successfully"
else
    echo "WARNING: PHP-FPM might not have started, but continuing..."
fi

# Запускаем Nginx на переднем плане
echo "Starting Nginx..."
nginx -g 'daemon off;'
