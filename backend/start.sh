#!/bin/bash

# Script de inicialização para Railway
echo "🚀 Iniciando deploy do Laravel no Railway..."

# Gerar chave da aplicação se não existir
if [ -z "$APP_KEY" ]; then
    echo "🔑 Gerando APP_KEY..."
    php artisan key:generate --force
fi

# Limpar caches
echo "🧹 Limpando caches..."
php artisan config:clear
php artisan cache:clear
php artisan route:clear
php artisan view:clear

# Executar migrations
echo "🗄️ Executando migrations..."
php artisan migrate --force

# Otimizar para produção
echo "⚡ Otimizando para produção..."
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Iniciar servidor
echo "🌐 Iniciando servidor..."
php artisan serve --host=0.0.0.0 --port=$PORT
