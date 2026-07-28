#!/usr/bin/env sh
set -eu

cd /var/www/html

# Railway / PaaS inject PORT
if [ -n "${PORT:-}" ]; then
    export SERVER_PORT="$PORT"
else
    export SERVER_PORT=8080
fi

# Sensible PaaS defaults when vars are omitted
export DB_CONNECTION="${DB_CONNECTION:-sqlite}"
export CACHE_STORE="${CACHE_STORE:-file}"
export SESSION_DRIVER="${SESSION_DRIVER:-file}"
export QUEUE_CONNECTION="${QUEUE_CONNECTION:-sync}"
export LOG_CHANNEL="${LOG_CHANNEL:-stderr}"

mkdir -p storage/framework/{cache,sessions,views} storage/logs bootstrap/cache
chown -R www-data:www-data storage bootstrap/cache 2>/dev/null || true
chmod -R ug+rwx storage bootstrap/cache 2>/dev/null || true

# Generate key if missing (local/docker demo only)
if [ -z "${APP_KEY:-}" ]; then
    echo "WARNING: APP_KEY is empty — generating temporary key"
    export APP_KEY="$(php artisan key:generate --show --no-interaction)"
fi

# Ensure sqlite file exists (Laravel default is sqlite when DB_CONNECTION unset)
if [ "$DB_CONNECTION" = "sqlite" ]; then
    DB_PATH="${DB_DATABASE:-/var/www/html/database/database.sqlite}"
    case "$DB_PATH" in
        /*) ;;
        *) DB_PATH="/var/www/html/$DB_PATH" ;;
    esac
    mkdir -p "$(dirname "$DB_PATH")"
    touch "$DB_PATH"
    export DB_DATABASE="$DB_PATH"
fi

php artisan config:clear || true
php artisan route:clear || true
php artisan view:clear || true

php artisan package:discover --ansi || true

if [ "${RUN_MIGRATIONS:-true}" = "true" ]; then
    php artisan migrate --force --no-interaction
fi

if [ "${CACHE_CONFIG:-true}" = "true" ]; then
    php artisan config:cache || true
    php artisan route:cache || true
    php artisan view:cache || true
fi

# Always bind to Railway's PORT
exec php artisan serve --host=0.0.0.0 --port="$SERVER_PORT"
