#!/usr/bin/env bash

set -e

cd /var/www/html

echo "========================================"
echo " ClareShop container starting"
echo " APP_ENV=${APP_ENV:-production}"
echo " PORT=${PORT:-10000}"
echo "========================================"

# ------------------------------------------------------------
# Required Laravel directories
# ------------------------------------------------------------
mkdir -p \
    storage/app/public \
    storage/framework/cache/data \
    storage/framework/sessions \
    storage/framework/views \
    storage/logs \
    bootstrap/cache \
    /run/nginx

chown -R www-data:www-data storage bootstrap/cache
chmod -R ug+rwX storage bootstrap/cache

# ------------------------------------------------------------
# Generate nginx config using the platform-provided PORT
# ------------------------------------------------------------
export PORT="${PORT:-10000}"

envsubst '${PORT}' \
    < /etc/nginx/templates/default.conf.template \
    > /etc/nginx/conf.d/default.conf

# ------------------------------------------------------------
# Clear caches left from build/local machine
# ------------------------------------------------------------
php artisan optimize:clear

# ------------------------------------------------------------
# Public storage symlink
# ------------------------------------------------------------
if [ ! -L public/storage ]; then
    php artisan storage:link || true
fi

# ------------------------------------------------------------
# Database migration. Railway may start the app while MySQL is still
# becoming ready, so retry briefly instead of failing the whole deployment.
# ------------------------------------------------------------
if [ "${RUN_MIGRATIONS:-true}" = "true" ]; then
    echo "Running database migrations..."
    migration_attempt=1
    migration_attempts=12

    until php artisan migrate --force; do
        if [ "$migration_attempt" -ge "$migration_attempts" ]; then
            echo "Database migration failed after ${migration_attempts} attempts."
            exit 1
        fi

        echo "Database is not ready yet (attempt ${migration_attempt}/${migration_attempts}); retrying in 5 seconds..."
        migration_attempt=$((migration_attempt + 1))
        sleep 5
    done
fi

# ------------------------------------------------------------
# Production Laravel caches
# ------------------------------------------------------------
php artisan config:cache
php artisan view:cache

echo "Starting Nginx, PHP-FPM, queue worker and scheduler..."

exec /usr/bin/supervisord \
    -c /etc/supervisor/supervisord.conf
