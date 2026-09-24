# ============================================================
# Stage 1: Build frontend assets
# ============================================================
FROM node:22-alpine AS frontend

WORKDIR /app

COPY package.json package-lock.json ./
RUN npm ci

COPY . .

RUN npm run build


# ============================================================
# Stage 2: Laravel production image
# PHP 8.4 + PHP-FPM + Nginx + Supervisor
# ============================================================
FROM php:8.4-fpm-bookworm AS production

ENV APP_ENV=production \
    APP_DEBUG=false \
    LOG_CHANNEL=stderr \
    PORT=10000 \
    COMPOSER_ALLOW_SUPERUSER=1

WORKDIR /var/www/html

# ------------------------------------------------------------
# Linux packages
# ------------------------------------------------------------
RUN apt-get update && apt-get install -y --no-install-recommends \
    nginx \
    supervisor \
    git \
    curl \
    unzip \
    gettext-base \
    libzip-dev \
    libicu-dev \
    libonig-dev \
    libpng-dev \
    libjpeg62-turbo-dev \
    libfreetype6-dev \
    libpq-dev \
    && rm -rf /var/lib/apt/lists/*

# ------------------------------------------------------------
# PHP extensions
# Supports both MySQL and PostgreSQL
# ------------------------------------------------------------
RUN docker-php-ext-configure gd \
    --with-freetype \
    --with-jpeg \
    && docker-php-ext-install -j"$(nproc)" \
    pdo_mysql \
    pdo_pgsql \
    mbstring \
    bcmath \
    intl \
    zip \
    gd \
    pcntl \
    exif \
    opcache

# ------------------------------------------------------------
# Composer
# ------------------------------------------------------------
COPY --from=composer:2 /usr/bin/composer /usr/local/bin/composer

# ------------------------------------------------------------
# Application source
# ------------------------------------------------------------
COPY . .

# Compiled Vite assets
COPY --from=frontend /app/public/build ./public/build

# ------------------------------------------------------------
# Production Composer dependencies
# ------------------------------------------------------------
RUN composer install \
    --no-dev \
    --no-interaction \
    --prefer-dist \
    --optimize-autoloader

# ------------------------------------------------------------
# Docker configuration
# ------------------------------------------------------------
COPY docker/nginx.conf.template /etc/nginx/templates/default.conf.template
COPY docker/supervisord.conf /etc/supervisor/conf.d/laravel.conf
COPY docker/php.ini /usr/local/etc/php/conf.d/99-production.ini
COPY docker/start.sh /usr/local/bin/start-container

# Remove Debian's default nginx site
RUN rm -f /etc/nginx/sites-enabled/default \
    && rm -f /etc/nginx/sites-available/default

# Laravel directories + permissions
RUN mkdir -p \
    storage/app/public \
    storage/framework/cache/data \
    storage/framework/sessions \
    storage/framework/views \
    storage/logs \
    bootstrap/cache \
    /run/nginx \
    && chown -R www-data:www-data storage bootstrap/cache \
    && chmod -R ug+rwX storage bootstrap/cache \
    && chmod +x /usr/local/bin/start-container

EXPOSE 10000

STOPSIGNAL SIGTERM

CMD ["/usr/local/bin/start-container"]
