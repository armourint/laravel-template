#!/usr/bin/env sh
set -e
cd /var/www/html
if [ ! -f .env ]; then
  cp .env.example .env
fi
php artisan key:generate --force || true
php artisan storage:link || true
php artisan migrate --force || true
