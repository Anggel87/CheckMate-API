#!/usr/bin/env bash
set -e

if [ ! -f .env ]; then
    cp .env.example .env
fi

if [ ! -d vendor ] || [ -z "$(ls -A vendor 2>/dev/null)" ]; then
    composer install --no-interaction --prefer-dist
fi

if [ ! -d node_modules ] || [ -z "$(ls -A node_modules 2>/dev/null)" ]; then
    npm install
fi

php artisan config:clear --no-interaction
php artisan route:clear --no-interaction
php artisan view:clear --no-interaction
php artisan event:clear --no-interaction

if ! grep -q '^APP_KEY=base64:' .env; then
    php artisan key:generate --force --no-interaction
fi

if [ "$DB_HOST" = "mysql" ]; then
    until mysqladmin ping -h"$DB_HOST" -P"${DB_PORT:-3306}" -u"$DB_USERNAME" -p"$DB_PASSWORD" --silent; do
        sleep 2
    done
fi

php artisan migrate --force --no-interaction

exec "$@"
