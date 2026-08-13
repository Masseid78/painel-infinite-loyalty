# Infinite Loyalty — Painel Comercial

Vue.js (Vercel) + Laravel API (Railway) + PostgreSQL.

## Estrutura

```
backend/   → Laravel (Railway) — Root Directory = backend
frontend/  → Vue 3 (Vercel)
```

## Railway (backend) — do jeito que funciona

Igual aos seus outros projetos (ex.: corretora_viviane):

1. Conecte o repo `painel-infinite-loyalty` (sem `-` no começo)
2. No serviço → **Settings** → **Root Directory** = `backend`
3. Adicione PostgreSQL no projeto
4. Variáveis:

```
APP_NAME=Infinite Loyalty
APP_ENV=production
APP_DEBUG=false
APP_KEY=base64:...          # gere no deploy ou local: php artisan key:generate --show
APP_URL=https://SEU-BACKEND.up.railway.app
DB_CONNECTION=pgsql
DB_URL=${{Postgres.DATABASE_URL}}
CORS_ALLOWED_ORIGINS=https://SEU-FRONT.vercel.app,http://localhost:5173
```

5. Deploy. O `nixpacks.toml` + `start.sh` cuidam do resto.

## Vercel (frontend)

1. Root Directory = `frontend`
2. Variável:

```
VITE_API_URL=https://SEU-BACKEND.up.railway.app/api
```

## Local

```bash
# backend
cd backend
composer install
copy .env.example .env
php artisan key:generate
php artisan migrate
php artisan serve

# frontend
cd frontend
npm install
npm run dev
```
