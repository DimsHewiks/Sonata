FROM composer:2 AS builder
WORKDIR /app
COPY composer.json composer.lock ./
RUN composer install --no-dev --no-interaction --prefer-dist && echo "✅ Builder: vendor created" && ls -la vendor

FROM php:8.4-fpm

RUN apt-get update && apt-get install -y \
    libonig-dev \
    libcurl4-openssl-dev \
    && docker-php-ext-install pdo_mysql mbstring curl \
    && rm -rf /var/lib/apt/lists/*

COPY . /var/www/html

COPY --from=builder /app/vendor /var/www/html/vendor

RUN echo "✅ Final image: checking vendor..." && ls -la /var/www/html/vendor

USER www-data