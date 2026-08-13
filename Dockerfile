FROM php:8.3-cli-bookworm

RUN apt-get update && apt-get install -y --no-install-recommends \
    git \
    unzip \
    curl \
    libpq-dev \
    libzip-dev \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    libicu-dev \
    && docker-php-ext-install -j$(nproc) \
        pdo \
        pdo_pgsql \
        zip \
        mbstring \
        xml \
        bcmath \
        intl \
        opcache \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

ENV COMPOSER_ALLOW_SUPERUSER=1
ENV COMPOSER_MEMORY_LIMIT=-1
ENV APP_ENV=production
ENV APP_DEBUG=false

# Instala o skeleton oficial do Laravel (com vendor já resolvido pelo create-project)
RUN composer create-project laravel/laravel:^11.0 /tmp/laravel \
      --prefer-dist \
      --no-interaction \
      --no-dev \
      --no-scripts \
    && cp -a /tmp/laravel/. /var/www/html/ \
    && rm -rf /tmp/laravel

# Sobrescreve com o código do painel
COPY backend/app ./app
COPY backend/bootstrap/app.php ./bootstrap/app.php
COPY backend/bootstrap/providers.php ./bootstrap/providers.php
COPY backend/routes ./routes
COPY backend/database/migrations ./database/migrations
COPY backend/database/seeders ./database/seeders
COPY backend/config/cors.php ./config/cors.php
COPY backend/.env.example ./.env.example

RUN mkdir -p \
      storage/framework/cache \
      storage/framework/sessions \
      storage/framework/views \
      storage/logs \
      bootstrap/cache \
    && chmod -R 775 storage bootstrap/cache \
    && php -r "file_exists('.env') || copy('.env.example', '.env');" \
    && php artisan key:generate --force --no-interaction \
    && php artisan package:discover --ansi

EXPOSE 8000

CMD php artisan migrate --force && php artisan serve --host=0.0.0.0 --port=${PORT:-8000}
