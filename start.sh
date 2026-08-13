#!/bin/bash
set -e

echo "Starting Infinite Loyalty API..."

mkdir -p \
  storage/framework/cache \
  storage/framework/sessions \
  storage/framework/views \
  storage/logs \
  bootstrap/cache

chmod -R 775 storage bootstrap/cache || true

# Limpa cache gerado no BUILD (sem as envs da Railway)
php artisan config:clear || true
php artisan route:clear || true
php artisan view:clear || true
php artisan cache:clear || true

if [ -z "$APP_KEY" ]; then
  echo "APP_KEY missing, generating..."
  php artisan key:generate --force
fi

echo "Running migrations..."
php artisan migrate --force

echo "Listening on 0.0.0.0:${PORT:-8080}"
php artisan serve --host=0.0.0.0 --port="${PORT:-8080}"
