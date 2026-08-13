FROM php:8.3-cli-bookworm

RUN apt-get update && apt-get install -y --no-install-recommends \
    git unzip libpq-dev \
    && docker-php-ext-install pdo pdo_pgsql \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /app
COPY backend/ ./

RUN composer install --no-dev --optimize-autoloader --no-interaction --prefer-dist \
    && mkdir -p storage/framework/{cache,sessions,views} storage/logs bootstrap/cache \
    && chmod -R 775 storage bootstrap/cache

ENV APP_ENV=production
ENV APP_DEBUG=false

EXPOSE 8000

CMD php artisan migrate --force && php artisan serve --host=0.0.0.0 --port=${PORT:-8000}
