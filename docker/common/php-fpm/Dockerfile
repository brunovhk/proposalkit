# syntax=docker/dockerfile:1.7

# ==========================================
# Stage 1: Build PHP dependencies and frontend assets.
# ==========================================
FROM php:8.4-fpm-alpine AS builder

WORKDIR /var/www/html

ENV APP_ENV=production \
    APP_DEBUG=false \
    LOG_CHANNEL=stderr \
    COMPOSER_ALLOW_SUPERUSER=1

RUN apk add --no-cache \
    nodejs \
    npm \
    git \
    unzip \
    icu-libs \
    libzip \
    libpng \
    libjpeg-turbo \
    freetype \
    && apk add --no-cache --virtual .build-deps \
    $PHPIZE_DEPS \
    icu-dev \
    libzip-dev \
    libpng-dev \
    libjpeg-turbo-dev \
    freetype-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j"$(nproc)" pdo_mysql gd intl zip pcntl opcache \
    && pecl install redis \
    && docker-php-ext-enable redis \
    && apk del .build-deps

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

COPY composer.json composer.lock ./
RUN composer install \
    --no-dev \
    --no-interaction \
    --no-progress \
    --prefer-dist \
    --optimize-autoloader \
    --no-scripts

COPY package.json package-lock.json ./
RUN npm ci

COPY . .

RUN composer dump-autoload --optimize --no-scripts \
    && php artisan package:discover --ansi \
    && php artisan wayfinder:generate --with-form \
    && npm run build

# ==========================================
# Stage 2: Local PHP-FPM runtime (Development)
# ==========================================
FROM php:8.4-fpm-alpine AS development

WORKDIR /var/www/html

ARG XDEBUG_ENABLED=true

RUN apk add --no-cache \
    git \
    unzip \
    icu-libs \
    libzip \
    libpng \
    libjpeg-turbo \
    freetype \
    && apk add --no-cache --virtual .build-deps \
    $PHPIZE_DEPS \
    linux-headers \
    icu-dev \
    libzip-dev \
    libpng-dev \
    libjpeg-turbo-dev \
    freetype-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j"$(nproc)" pdo_mysql gd intl zip pcntl opcache \
    && pecl install redis \
    && docker-php-ext-enable redis \
    && if [ "$XDEBUG_ENABLED" = "true" ]; then pecl install xdebug && docker-php-ext-enable xdebug; fi \
    && apk del .build-deps

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

EXPOSE 9000
CMD ["php-fpm"]

# ==========================================
# Stage 3: Production PHP-FPM runtime (App)
# ==========================================
FROM php:8.4-fpm-alpine AS production

WORKDIR /var/www/html

ENV APP_ENV=production \
    APP_DEBUG=false \
    LOG_CHANNEL=stderr

RUN apk add --no-cache \
    icu-libs \
    libzip \
    libpng \
    libjpeg-turbo \
    freetype \
    && apk add --no-cache --virtual .build-deps \
    $PHPIZE_DEPS \
    icu-dev \
    libzip-dev \
    libpng-dev \
    libjpeg-turbo-dev \
    freetype-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j"$(nproc)" pdo_mysql gd intl zip pcntl opcache \
    && pecl install redis \
    && docker-php-ext-enable redis \
    && apk del .build-deps \
    && mv "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini" \
    && { \
    echo "opcache.enable=1"; \
    echo "opcache.enable_cli=1"; \
    echo "opcache.memory_consumption=256"; \
    echo "opcache.interned_strings_buffer=16"; \
    echo "opcache.max_accelerated_files=20000"; \
    echo "opcache.validate_timestamps=0"; \
    echo "opcache.jit=1255"; \
    echo "opcache.jit_buffer_size=128M"; \
    } > /usr/local/etc/php/conf.d/opcache-production.ini

COPY --chown=www-data:www-data . .
COPY --from=builder --chown=www-data:www-data /var/www/html/vendor ./vendor
COPY --from=builder --chown=www-data:www-data /var/www/html/public/build ./public/build

RUN mkdir -p storage/framework/cache storage/framework/sessions storage/framework/views storage/logs bootstrap/cache \
    && chown -R www-data:www-data storage bootstrap/cache public/build

USER www-data

EXPOSE 9000
CMD ["php-fpm"]

# ==========================================
# Stage 4: Production NGINX runtime (Web)
# ==========================================
FROM nginx:1.27-alpine AS web-production

COPY docker/production/nginx/nginx.conf /etc/nginx/nginx.conf
# Copy ONLY the public folder (containing compiled assets) from the builder stage
COPY --from=builder /var/www/html/public /var/www/html/public

EXPOSE 80
CMD ["nginx", "-g", "daemon off;"]
