# Используем официальный PHP образ с FPM
FROM php:8.4-fpm

# Установка системных зависимостей
RUN apt-get update && apt-get install -y \
    git \
    curl \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    zip \
    unzip \
    nginx \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

# Установка PHP расширений
RUN docker-php-ext-install pdo_mysql mbstring exif pcntl bcmath gd

# Установка Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Копирование файлов проекта
COPY . /app
WORKDIR /app

# Установка прав на директории
RUN chown -R www-data:www-data /app \
    && chmod -R 755 /app/var

# Установка зависимостей PHP
RUN composer install --no-dev --optimize-autoloader --no-interaction

# Копирование конфигов
COPY nginx.conf /etc/nginx/nginx.conf
COPY php-fpm.conf /usr/local/etc/php-fpm.conf
COPY www.conf /usr/local/etc/php-fpm.d/www.conf

# Создание необходимых директорий
RUN mkdir -p /var/log/nginx /var/lib/nginx /run/php-fpm

# Права доступа
RUN chown -R www-data:www-data /var/log/nginx /var/lib/nginx /run/php-fpm

# Создание healthcheck файлов
RUN echo "<?php echo 'OK';" > /app/public/healthcheck.php \
    && echo "<?php phpinfo();" > /app/public/info.php

# Копирование и установка прав на start.sh
COPY start.sh /start.sh
RUN chmod +x /start.sh

# Открываем порт
EXPOSE 8080

CMD ["/start.sh"]
