FROM php:8.4-fpm-alpine

RUN apk add --no-cache \
        icu-libs \
        libpq \
        libzip \
        oniguruma \
        unzip \
    && apk add --no-cache --virtual .build-deps \
        $PHPIZE_DEPS \
        icu-dev \
        libzip-dev \
        linux-headers \
        oniguruma-dev \
        postgresql-dev \
    && docker-php-ext-install -j"$(nproc)" \
        bcmath \
        intl \
        mbstring \
        opcache \
        pcntl \
        pdo_pgsql \
        zip \
    && pecl install redis \
    && docker-php-ext-enable redis \
    && apk del .build-deps

COPY --from=composer:2 /usr/bin/composer /usr/local/bin/composer

WORKDIR /var/www/html

COPY backend/composer.json backend/composer.lock ./
RUN composer install --no-interaction --prefer-dist --no-scripts --optimize-autoloader

COPY backend/ ./
RUN composer dump-autoload --optimize \
    && chown -R www-data:www-data storage bootstrap/cache

COPY docker/php/entrypoint.sh /usr/local/bin/eclesiapp-entrypoint
RUN chmod +x /usr/local/bin/eclesiapp-entrypoint

EXPOSE 9000

ENTRYPOINT ["eclesiapp-entrypoint"]
CMD ["php-fpm"]
