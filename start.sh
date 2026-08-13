#!/bin/bash
set -e

echo "Starting Infinite Loyalty API..."

# Limpa cache gerado no BUILD (sem as envs da Railway)
php artisan config:clear
php artisan route:clear
php artisan view:clear
php artisan cache:clear || true

if [ -z "$APP_KEY" ]; then
  echo "APP_KEY missing, generating..."
  php artisan key:generate --force
fi

echo "Running migrations..."
php artisan migrate --force

echo "Caching config with runtime env..."
php artisan config:cache
php artisan route:cache
php artisan view:cache

echo "Listening on 0.0.0.0:${PORT:-8080}"
php artisan serve --host=0.0.0.0 --port="${PORT:-8080}"
