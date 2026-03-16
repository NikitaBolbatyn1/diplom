# Используем официальный PHP 8.4 образ
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
    libzip-dev \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

# Установка PHP расширений
RUN docker-php-ext-install pdo_mysql mbstring exif pcntl bcmath gd zip

# Установка Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Копирование файлов проекта
COPY . /app
WORKDIR /app

# Установка зависимостей Composer
RUN composer install --no-dev --optimize-autoloader --no-interaction

# Создание структуры директорий
RUN mkdir -p \
    /var/log/nginx \
    /var/lib/nginx \
    /etc/nginx \
    /run/php-fpm \
    /tmp/php-sessions \
    /app/public/uploads/archive \
    /app/var/archive \
    /app/var/cache/prod \
    /app/var/log \
    /app/var/sessions \
    /app/var/cache/dev

# Копирование конфигов Nginx
COPY nginx.conf /etc/nginx/nginx.conf

# Копирование конфигов PHP-FPM
COPY php-fpm.conf /usr/local/etc/php-fpm.conf

# СОЗДАЕМ директорию и копируем www.conf (ВАЖНО!)
RUN mkdir -p /usr/local/etc/php-fpm.d
COPY www.conf /usr/local/etc/php-fpm.d/www.conf

# Исправляем путь в php-fpm.conf
RUN sed -i 's|/etc/php-fpm.d|/usr/local/etc/php-fpm.d|g' /usr/local/etc/php-fpm.conf

# Создаем симлинк для обратной совместимости
RUN mkdir -p /etc/php-fpm.d && \
    ln -sf /usr/local/etc/php-fpm.d/www.conf /etc/php-fpm.d/www.conf

# Создание fastcgi_params
RUN echo 'fastcgi_param  QUERY_STRING       $query_string;\
fastcgi_param  REQUEST_METHOD     $request_method;\
fastcgi_param  CONTENT_TYPE       $content_type;\
fastcgi_param  CONTENT_LENGTH     $content_length;\
fastcgi_param  SCRIPT_NAME        $fastcgi_script_name;\
fastcgi_param  REQUEST_URI        $request_uri;\
fastcgi_param  DOCUMENT_URI       $document_uri;\
fastcgi_param  DOCUMENT_ROOT      $document_root;\
fastcgi_param  SERVER_PROTOCOL    $server_protocol;\
fastcgi_param  REQUEST_SCHEME     $scheme;\
fastcgi_param  HTTPS              $https if_not_empty;\
fastcgi_param  GATEWAY_INTERFACE  CGI/1.1;\
fastcgi_param  SERVER_SOFTWARE    nginx/$nginx_version;\
fastcgi_param  REMOTE_ADDR        $remote_addr;\
fastcgi_param  REMOTE_PORT        $remote_port;\
fastcgi_param  SERVER_ADDR        $server_addr;\
fastcgi_param  SERVER_PORT        $server_port;\
fastcgi_param  SERVER_NAME        $server_name;\
fastcgi_param  REDIRECT_STATUS    200;' > /etc/nginx/fastcgi_params

# Создание mime.types
RUN echo 'types {\
    text/html                             html htm shtml;\
    text/css                              css;\
    text/xml                              xml;\
    image/gif                             gif;\
    image/jpeg                            jpeg jpg;\
    application/javascript                js;\
    application/json                       json;\
    application/zip                        zip;\
    application/pdf                        pdf;\
    image/png                              png;\
    image/svg+xml                          svg svgz;\
    image/webp                             webp;\
    font/woff                              woff;\
    font/woff2                             woff2;\
}' > /etc/nginx/mime.types

# Установка прав доступа
RUN chown -R www-data:www-data /run/php-fpm /var/log/nginx /app/var /tmp/php-sessions \
    && chmod -R 755 /run/php-fpm \
    && chmod -R 775 /app/var

# Создание healthcheck файлов
RUN mkdir -p /app/public \
    && echo "<?php phpinfo();" > /app/public/info.php \
    && echo "<?php echo 'OK';" > /app/public/healthcheck.php

# Копирование и подготовка start.sh
COPY start.sh /start.sh
RUN chmod +x /start.sh

# Проверяем, что конфиги PHP-FPM на месте
RUN echo "=== VERIFYING PHP-FPM CONFIGS ===" && \
    ls -la /usr/local/etc/php-fpm.conf && \
    ls -la /usr/local/etc/php-fpm.d/ && \
    echo "=== PHP-FPM.CONF CONTENT ===" && \
    cat /usr/local/etc/php-fpm.conf | grep -A 2 "include"

# Проверка конфигурации PHP-FPM
RUN php-fpm -t

# Проверка конфигурации Nginx
RUN nginx -t

# Открываем порт
EXPOSE 8080

CMD ["/start.sh"]
