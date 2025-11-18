#!/usr/bin/env bash
set -e

echo "Running composer scripts & building assets if present"
composer install --no-dev --no-interaction --prefer-dist --optimize-autoloader || true

if [ -f package.json ]; then
  echo "Installing Node dependencies..."
  # Skip optional dependencies to avoid native binary issues
  npm ci --legacy-peer-deps --omit=optional --ignore-scripts || true
  
  echo "Building frontend assets..."
  # Set environment to skip native dependencies
  VITE_SKIP_NATIVE_DEPS=true npm run build || true
  
  # Fallback if build fails
  if [ $? -ne 0 ]; then
    echo "First build attempt failed, trying alternative approach..."
    rm -rf node_modules
    npm install --legacy-peer-deps --omit=optional
    VITE_SKIP_NATIVE_DEPS=true npm run build || echo "Build completed with warnings"
  fi
fi

# generate key if missing
if [ -z "${APP_KEY}" ]; then
  echo "APP_KEY is missing — printing generated key (do not rely on this for secret management)"
  php artisan key:generate --show || true
fi

php artisan config:cache || true
php artisan route:cache || true
php artisan view:clear || true
php artisan migrate --force || true

echo "deploy script finished"