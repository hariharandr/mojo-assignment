#!/usr/bin/env bash
set -e

echo "Running composer scripts & building assets if present"
composer install --no-dev --no-interaction --prefer-dist --optimize-autoloader || true

if [ -f package.json ]; then
  npm ci --legacy-peer-deps || true
  npm run build || true
fi

# generate key if missing (prints key so you can set env if you need)
if [ -z "${APP_KEY}" ]; then
  echo "APP_KEY is missing — printing generated key (do not rely on this for secret management)"
  php artisan key:generate --show || true
fi

php artisan config:cache || true
php artisan route:cache || true
php artisan view:clear || true
php artisan migrate --force || true

echo "deploy script finished"
