#!/bin/sh
set -e

cd /var/www/html

echo "[entrypoint] waiting for PostgreSQL at ${DB_HOST:-db}:${DB_PORT:-5432}..."

ATTEMPTS=0
until php -r '
    $host = getenv("DB_HOST") ?: "db";
    $port = (int) (getenv("DB_PORT") ?: 5432);
    $fp = @fsockopen($host, $port, $errno, $errstr, 2);
    exit($fp ? 0 : 1);
'; do
    ATTEMPTS=$((ATTEMPTS + 1))
    if [ "$ATTEMPTS" -ge 60 ]; then
        echo "[entrypoint] PostgreSQL did not answer after 60 attempts, giving up." >&2
        exit 1
    fi
    sleep 2
done

echo "[entrypoint] PostgreSQL is reachable."

# The database container is ephemeral on first boot; never cache stale config.
php artisan optimize:clear >/dev/null 2>&1 || true
php artisan package:discover --ansi >/dev/null 2>&1 || true

echo "[entrypoint] running migrations..."
php artisan migrate --force

echo "[entrypoint] ensuring seed data exists..."
php artisan db:seed --class='Database\Seeders\DockerSeeder' --force

echo "[entrypoint] ready."

# Hand over to the container CMD (apache2-foreground).
exec "$@"
