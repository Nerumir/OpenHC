#!/bin/sh
set -e

# ── Restore storage structure on fresh clones / volumes ───────────────────────
mkdir -p \
    storage/framework/sessions \
    storage/framework/cache \
    storage/framework/views \
    storage/logs \
    storage/app/public \
    bootstrap/cache
ln -sfn ../storage/app/public /var/www/html/public/storage
chown -R www-data:www-data storage bootstrap/cache
chmod -R 775 storage bootstrap/cache

# ── Build APP_URL from protocol + domain + port ────────────────────────────────
PROTOCOL=${APP_PROTOCOL:-http}
DOMAIN=${APP_DOMAIN:-localhost}
PORT=${APP_PORT:-8080}

if [ "$PORT" = "80" ] || [ "$PORT" = "443" ]; then
    APP_URL="${PROTOCOL}://${DOMAIN}"
else
    APP_URL="${PROTOCOL}://${DOMAIN}:${PORT}"
fi

# ── Generate the Laravel .env (skip if already present — dev bind-mount) ──────
[ -f /var/www/html/.env ] && echo "[entrypoint] .env already present, skipping generation." && true || cat > /var/www/html/.env << EOF
APP_NAME=OpenHC
APP_ENV=production
APP_KEY=
APP_DEBUG=false
APP_URL=${APP_URL}

APP_LOCALE=en
APP_FALLBACK_LOCALE=en

LOG_CHANNEL=stderr
LOG_LEVEL=warning

DB_CONNECTION=mysql
DB_HOST=${DB_HOST:-db}
DB_PORT=${DB_PORT:-3306}
DB_DATABASE=${DB_DATABASE:-openhc}
DB_USERNAME=${DB_USERNAME:-openhc}
DB_PASSWORD=${DB_PASSWORD}

CACHE_STORE=file
SESSION_DRIVER=file
SESSION_LIFETIME=120
QUEUE_CONNECTION=sync

FILESYSTEM_DISK=local
EOF

# ── Generate app key ───────────────────────────────────────────────────────────
php artisan key:generate --force

# ── Wait for the database ──────────────────────────────────────────────────────
DB_HOST_VAL=${DB_HOST:-db}
DB_PORT_VAL=${DB_PORT:-3306}

echo "[entrypoint] Waiting for database at ${DB_HOST_VAL}:${DB_PORT_VAL}..."
until php -r "
    try {
        new PDO(
            'mysql:host=${DB_HOST_VAL};port=${DB_PORT_VAL};dbname=${DB_DATABASE}',
            '${DB_USERNAME}',
            '${DB_PASSWORD}'
        );
        exit(0);
    } catch (Exception \$e) {
        exit(1);
    }
" 2>/dev/null; do
    sleep 2
done
echo "[entrypoint] Database ready."

# ── Cache config / routes / views ─────────────────────────────────────────────
php artisan config:cache
php artisan route:cache
php artisan view:cache

exec "$@"
