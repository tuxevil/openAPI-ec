FROM composer:2 AS composer

FROM php:8.4-fpm-alpine

WORKDIR /var/www/html

RUN apk add --no-cache \
    bash \
    curl \
    icu-dev \
    libzip-dev \
    oniguruma-dev \
    sqlite-dev \
    zip \
    && docker-php-ext-configure intl \
    && docker-php-ext-install \
        bcmath \
        intl \
        mbstring \
        pdo_sqlite \
    && rm -rf /var/cache/apk/*

COPY --from=composer /usr/bin/composer /usr/bin/composer

COPY . .
RUN composer install --no-interaction --prefer-dist --optimize-autoloader

RUN mkdir -p storage/framework/cache storage/framework/sessions storage/framework/views bootstrap/cache \
    && chown -R www-data:www-data storage bootstrap/cache

CMD ["php-fpm"]
