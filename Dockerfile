# -----------------------------------------------------------------------------
# Frontend build
# -----------------------------------------------------------------------------
FROM node:22-alpine AS frontend

WORKDIR /app

COPY package.json package-lock.json ./
RUN npm ci

COPY vite.config.js ./
COPY resources ./resources
COPY public ./public

RUN npm run build

# -----------------------------------------------------------------------------
# PHP dependencies (must be PHP 8.4 — Symfony 8.1 in lock requires >=8.4.1)
# -----------------------------------------------------------------------------
FROM php:8.4-cli-bookworm AS vendor

WORKDIR /app

RUN apt-get update && apt-get install -y --no-install-recommends \
        git \
        unzip \
        libzip-dev \
    && docker-php-ext-install zip \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

COPY composer.json composer.lock ./

RUN COMPOSER_ALLOW_SUPERUSER=1 composer install \
        --no-dev \
        --no-scripts \
        --no-interaction \
        --prefer-dist \
        --optimize-autoloader \
        --no-progress

# -----------------------------------------------------------------------------
# Runtime image
# -----------------------------------------------------------------------------
FROM php:8.4-cli-bookworm AS runtime

WORKDIR /var/www/html

RUN apt-get update && apt-get install -y --no-install-recommends \
        git \
        curl \
        unzip \
        libzip-dev \
        libpng-dev \
        libonig-dev \
        libxml2-dev \
        libpq-dev \
        libsqlite3-dev \
        $PHPIZE_DEPS \
    && docker-php-ext-install \
        pdo_mysql \
        pdo_pgsql \
        pdo_sqlite \
        mbstring \
        xml \
        zip \
        bcmath \
        pcntl \
    && pecl install redis \
    && docker-php-ext-enable redis \
    && apt-get purge -y $PHPIZE_DEPS \
    && apt-get autoremove -y \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

COPY --from=vendor /app/vendor ./vendor
COPY . .
COPY --from=frontend /app/public/build ./public/build

RUN COMPOSER_ALLOW_SUPERUSER=1 composer dump-autoload --optimize --no-dev \
    && php artisan package:discover --ansi || true \
    && chown -R www-data:www-data storage bootstrap/cache \
    && chmod -R ug+rwx storage bootstrap/cache \
    && chmod +x docker/entrypoint.sh

ENV APP_ENV=production \
    APP_DEBUG=false \
    LOG_CHANNEL=stderr \
    PORT=8080

EXPOSE 8080

ENTRYPOINT ["docker/entrypoint.sh"]
CMD ["php", "artisan", "serve", "--host=0.0.0.0", "--port=8080"]
