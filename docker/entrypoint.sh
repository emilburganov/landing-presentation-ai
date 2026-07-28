#!/usr/bin/env sh
set -eu

cd /var/www/html

# Public port (Railway injects PORT). Nginx listens here.
export PORT="${PORT:-8080}"
export SERVER_PORT="$PORT"
# Laravel binds privately; nginx proxies to it.
export APP_PORT="${APP_PORT:-8081}"
export MAILPIT_SMTP_PORT="${MAILPIT_SMTP_PORT:-1025}"
export MAILPIT_UI_PORT="${MAILPIT_UI_PORT:-8025}"
export MAILPIT_ENABLED="${MAILPIT_ENABLED:-true}"

# Sensible PaaS defaults when vars are omitted
export DB_CONNECTION="${DB_CONNECTION:-sqlite}"
export CACHE_STORE="${CACHE_STORE:-file}"
export SESSION_DRIVER="${SESSION_DRIVER:-file}"
export QUEUE_CONNECTION="${QUEUE_CONNECTION:-sync}"
export LOG_CHANNEL="${LOG_CHANNEL:-stderr}"

mkdir -p storage/framework/{cache,sessions,views} storage/logs bootstrap/cache \
    /tmp/nginx_client_body /tmp/nginx_proxy /tmp/nginx_fastcgi /tmp/nginx_uwsgi /tmp/nginx_scgi
chown -R www-data:www-data storage bootstrap/cache 2>/dev/null || true
chmod -R ug+rwx storage bootstrap/cache 2>/dev/null || true

if [ -z "${APP_KEY:-}" ]; then
    echo "WARNING: APP_KEY is empty — generating temporary key"
    export APP_KEY="$(php artisan key:generate --show --no-interaction)"
fi

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

if [ "$MAILPIT_ENABLED" = "true" ]; then
    echo "Starting Mailpit (SMTP 127.0.0.1:${MAILPIT_SMTP_PORT}, UI webroot /mailpit on :${MAILPIT_UI_PORT})"
    mailpit \
        --smtp "127.0.0.1:${MAILPIT_SMTP_PORT}" \
        --listen "127.0.0.1:${MAILPIT_UI_PORT}" \
        --webroot mailpit \
        --db-file /tmp/mailpit.db \
        >/tmp/mailpit.log 2>&1 &

    case "${MAIL_HOST:-}" in
        ""|mailpit|127.0.0.1|localhost)
            export MAIL_MAILER=smtp
            export MAIL_HOST=127.0.0.1
            export MAIL_PORT="${MAILPIT_SMTP_PORT}"
            export MAIL_USERNAME="${MAIL_USERNAME:-null}"
            export MAIL_PASSWORD="${MAIL_PASSWORD:-null}"
            ;;
    esac

    MAILPIT_UPSTREAM=$(cat <<EOF
    upstream mailpit {
        server 127.0.0.1:${MAILPIT_UI_PORT};
    }
EOF
)
    MAILPIT_LOCATION=$(cat <<'EOF'
        location = /mailpit {
            return 301 /mailpit/;
        }

        location /mailpit/ {
            proxy_pass http://mailpit/mailpit/;
            proxy_http_version 1.1;
            proxy_set_header Host $host;
            proxy_set_header X-Real-IP $remote_addr;
            proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
            proxy_set_header X-Forwarded-Proto $scheme;
            proxy_set_header X-Forwarded-Host $host;
            proxy_set_header Upgrade $http_upgrade;
            proxy_set_header Connection "upgrade";
            proxy_read_timeout 60s;
        }
EOF
)
else
    MAILPIT_UPSTREAM=""
    MAILPIT_LOCATION=""
fi

echo "Clearing Laravel caches..."
php artisan optimize:clear || true
php artisan package:discover --ansi || true

if [ "${RUN_MIGRATIONS:-true}" = "true" ]; then
    php artisan migrate --force --no-interaction
fi

if [ "${CACHE_CONFIG:-true}" = "true" ]; then
    php artisan config:cache || true
    php artisan route:cache || true
    php artisan view:cache || true
fi

echo "Starting Laravel on 127.0.0.1:${APP_PORT}"
php artisan serve --host=127.0.0.1 --port="${APP_PORT}" >/tmp/laravel-serve.log 2>&1 &

# Wait until Laravel accepts connections
i=0
while [ "$i" -lt 30 ]; do
    if php -r "exit(@fsockopen('127.0.0.1', (int)getenv('APP_PORT'), \$e, \$s, 0.2) ? 0 : 1);"; then
        break
    fi
    i=$((i + 1))
    sleep 0.2
done

# Render nginx config
# shellcheck disable=SC2016
awk \
    -v listen="$PORT" \
    -v app="$APP_PORT" \
    -v mp_up="$MAILPIT_UPSTREAM" \
    -v mp_loc="$MAILPIT_LOCATION" \
    '
    {
      gsub(/__LISTEN_PORT__/, listen)
      gsub(/__APP_PORT__/, app)
      if ($0 ~ /__MAILPIT_UPSTREAM__/) { print mp_up; next }
      if ($0 ~ /__MAILPIT_LOCATION__/) { print mp_loc; next }
      print
    }
    ' /var/www/html/docker/nginx/prod.conf.template > /tmp/nginx-prod.conf

echo "Starting nginx on 0.0.0.0:${PORT} (Laravel :${APP_PORT}, Mailpit UI /mailpit)"
exec nginx -c /tmp/nginx-prod.conf -g 'daemon off;'
