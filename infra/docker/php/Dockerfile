FROM composer:2.8.11 AS composer

FROM php:8.4.12-fpm-bookworm AS base

RUN apt-get -o Acquire::Max-FutureTime=86400 update \
    && apt-get install -y --no-install-recommends git libicu-dev libpq-dev libzip-dev unzip \
    && docker-php-ext-install bcmath intl opcache pcntl pdo_pgsql \
    && pecl install redis \
    && docker-php-ext-enable redis \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

FROM base AS development

COPY apps/api/composer.json apps/api/composer.lock ./
RUN composer install --no-interaction --prefer-dist --no-scripts

COPY apps/api ./
RUN composer dump-autoload --optimize

CMD ["php-fpm"]
