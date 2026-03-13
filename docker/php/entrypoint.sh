#!/bin/sh
set -e

# ---------------------------------------------------------------------------
# SIDAInfo Update Server — container entrypoint
# Runs before php-fpm starts: waits for DB, migrates, configures storage.
# ---------------------------------------------------------------------------

echo "[entrypoint] Waiting for database at ${DB_HOST:-db}:${DB_PORT:-3306}..."
until php -r "
try {
    new PDO(
        'mysql:host=${DB_HOST:-db};port=${DB_PORT:-3306};dbname=${DB_DATABASE:-sidainfo_updater}',
        '${DB_USERNAME:-updater}',
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

# Ensure writable directories are owned by www-data (important for fresh volumes)
chown -R www-data:www-data \
    /var/www/html/storage \
    /var/www/html/bootstrap/cache 2>/dev/null || true

echo "[entrypoint] Running migrations..."
php artisan migrate --force

echo "[entrypoint] Creating storage symlink..."
php artisan storage:link --force 2>/dev/null || true

# In production: cache config, routes, and views for faster responses
if [ "${APP_ENV}" = "production" ]; then
    echo "[entrypoint] Caching config, routes, and views..."
    php artisan config:cache
    php artisan route:cache
    php artisan view:cache
fi

echo "[entrypoint] Starting php-fpm..."
exec "$@"
