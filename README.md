# Infinite Loyalty — Painel Comercial

Painel de prospecção com **Vue.js** (Vercel) + **Laravel API** (Railway) + **PostgreSQL**.

## O que tem

- Meta mensal com botão **Definir meta**
- Meta de contatos na semana (editável no mesmo modal)
- Cards: contatos, empresas, assinaturas, receita recorrente
- CRUD de empresas (status, plano, retorno, observação)
- Busca e filtro por status
- Backup / restaurar (JSON)

## Estrutura

```
backend/   → Laravel API (Railway + PostgreSQL)
frontend/  → Vue 3 + Vite (Vercel)
```

## Backend (local)

Requisitos: PHP 8.2+, Composer, PostgreSQL.

```bash
cd backend
composer install
copy .env.example .env   # Windows
php artisan key:generate
# configure DB_* ou DB_URL no .env
php artisan migrate
php artisan serve
```

API em `http://localhost:8000/api`.

### Endpoints

| Método | Rota | Descrição |
|--------|------|-----------|
| GET | `/api/dashboard` | Meta + cards |
| GET/PUT | `/api/settings` | Ler/salvar metas |
| GET/POST | `/api/companies` | Listar/criar |
| PUT/DELETE | `/api/companies/{id}` | Editar/remover |
| GET | `/api/backup` | Baixar backup JSON |
| POST | `/api/backup/restore` | Restaurar backup |

## Frontend (local)

```bash
cd frontend
npm install
copy .env.example .env
# VITE_API_URL=http://localhost:8000/api
npm run dev
```

## Deploy Railway (backend)

1. Crie um projeto no Railway e adicione **PostgreSQL**.
2. Deploy do diretório `backend` (Root Directory = `backend`).
3. Variáveis:

```
APP_NAME=Infinite Loyalty
APP_ENV=production
APP_DEBUG=false
APP_KEY=base64:...   # gere com: php artisan key:generate --show
APP_URL=https://SEU-BACKEND.up.railway.app
DB_CONNECTION=pgsql
DB_URL=${{Postgres.DATABASE_URL}}
FRONTEND_URL=https://SEU-FRONT.vercel.app
CORS_ALLOWED_ORIGINS=https://SEU-FRONT.vercel.app,http://localhost:5173
```

4. Start command (já no `nixpacks.toml`):

```bash
php artisan migrate --force && php artisan serve --host=0.0.0.0 --port=$PORT
```

5. Gere um domínio público no serviço da API.

## Deploy Vercel (frontend)

1. Importe o repositório e defina **Root Directory** = `frontend`.
2. Framework preset: Vite.
3. Variável de ambiente:

```
VITE_API_URL=https://SEU-BACKEND.up.railway.app/api
```

4. Deploy. O `vercel.json` já trata SPA rewrite.

## Planos e receita

- `nenhum` → R$ 0
- `fidelidade` → R$ 97/mês (padrão)
- `completo` → R$ 197/mês (padrão)

A **meta mensal** e a **receita recorrente** usam empresas com status **Assinou**.
