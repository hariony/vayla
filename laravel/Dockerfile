# ============================================================
# Stage 1 — Dépendances Composer
# ============================================================
FROM composer:2 AS composer-deps

WORKDIR /app

COPY composer.json composer.lock ./

RUN composer install \
    --no-dev \
    --no-scripts \
    --no-autoloader \
    --prefer-dist \
    --ignore-platform-reqs

COPY . .

RUN rm -f bootstrap/cache/*.php \
    && composer dump-autoload --optimize --no-dev --no-scripts \
    && php artisan package:discover --ansi

# ============================================================
# Stage 2 — Assets frontend (Vite / Node)
# ============================================================
FROM node:20-alpine AS frontend-deps

WORKDIR /app

COPY package.json package-lock.json vite.config.js ./
COPY resources/ ./resources/

RUN npm ci && npm run build

# ============================================================
# Stage 3 — Image finale (PHP 8.5-FPM + Nginx + Supervisor)
# ============================================================
FROM php:8.5-fpm-alpine

# --- Dépendances système + extensions PHP pour Laravel + PostgreSQL ---
RUN apk add --no-cache \
        nginx \
        supervisor \
        curl \
        zip \
        unzip \
        libpng-dev \
        libjpeg-turbo-dev \
        libwebp-dev \
        freetype-dev \
        icu-dev \
        oniguruma-dev \
        libpq-dev \
    && docker-php-ext-configure gd \
        --with-freetype \
        --with-jpeg \
        --with-webp \
    && docker-php-ext-install -j$(nproc) \
        pdo_pgsql \
        gd \
        bcmath \
        intl
# pdo, mbstring et opcache sont déjà compilés en dur dans l'image
# php:8.5-fpm-alpine (docker-php-ext-install échouerait sur ces 3-là).

# --- Configuration Nginx ---
COPY docker/nginx/nginx.conf      /etc/nginx/nginx.conf
COPY docker/nginx/default.conf    /etc/nginx/http.d/default.conf

# --- Configuration PHP-FPM ---
COPY docker/php/php.ini           /usr/local/etc/php/conf.d/app.ini
COPY docker/php/fpm-pool.conf     /usr/local/etc/php-fpm.d/www.conf

# --- Configuration Supervisor (gère Nginx + PHP-FPM) ---
COPY docker/supervisord.conf      /etc/supervisord.conf

# --- Code applicatif ---
WORKDIR /var/www/html

COPY --from=composer-deps  --chown=www-data:www-data /app .
COPY --from=frontend-deps  --chown=www-data:www-data /app/public/build ./public/build

# --- Permissions Laravel ---
RUN mkdir -p storage/logs storage/framework/{cache,sessions,views} bootstrap/cache \
    && chown -R www-data:www-data storage bootstrap/cache \
    && chmod -R 775 storage bootstrap/cache

# --- Permissions Nginx tmp ---
RUN mkdir -p /var/lib/nginx/tmp/fastcgi \
    && chown -R www-data:www-data /var/lib/nginx/tmp \
    && chmod -R 755 /var/lib/nginx/tmp

COPY docker/entrypoint.sh /entrypoint.sh
RUN chmod +x /entrypoint.sh

EXPOSE 8080
ENTRYPOINT ["/entrypoint.sh"]
