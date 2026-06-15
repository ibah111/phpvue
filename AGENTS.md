# Laravel + Vue + Bun Fullstack Project

## Stack

* Backend: PHP + Laravel
* Frontend: Vue 3 + Vite + TypeScript
* Frontend package manager/runtime: Bun
* Database: PostgreSQL
* Infrastructure: Docker Compose
* API format: JSON over HTTP
* API contracts: OpenAPI / manual contracts in `contracts/`

---

## Project Structure

```txt
project/
├─ backend/                    # Laravel API
├─ frontend/                   # Vue 3 + Vite + TypeScript + Bun
├─ contracts/                  # API contracts, OpenAPI specs
├─ docker/
│  ├─ php/
│  │  └─ Dockerfile
│  ├─ nginx/
│  │  └─ default.conf
│  └─ frontend/
│     └─ Dockerfile
├─ docs/
├─ docker-compose.yml
├─ Makefile
├─ AGENTS.md
├─ .gitignore
└─ README.md
```

---

## Architecture

The project is a monorepository with two independent applications:

```txt
backend/   Laravel API application
frontend/  Vue.js frontend application
```

Backend and frontend are not launched from each other.

They communicate through HTTP API:

```txt
Vue frontend → JSON HTTP requests → Laravel backend
```

Default local URLs:

```txt
Frontend:    http://localhost:5173
Backend API: http://localhost:8000/api
Database:    localhost:5432
```

---

## Requirements

Required tools for local development:

```txt
Docker
Docker Compose
PHP 8.3+
Composer
Bun
```

If everything is launched through Docker, only Docker and Docker Compose are required on the host machine.

---

## Environment Files

Real `.env` files must not be committed.

Required examples:

```txt
backend/.env.example
frontend/.env.example
.env.example
```

Create local env files:

```bash
cp backend/.env.example backend/.env
cp frontend/.env.example frontend/.env
```

---

## Backend Environment

Example `backend/.env`:

```env
APP_NAME=MyProject
APP_ENV=local
APP_KEY=
APP_DEBUG=true
APP_URL=http://localhost:8000

DB_CONNECTION=pgsql
DB_HOST=postgres
DB_PORT=5432
DB_DATABASE=my_project
DB_USERNAME=my_project
DB_PASSWORD=my_project

FRONTEND_URL=http://localhost:5173
```

Inside Docker, Laravel connects to PostgreSQL by service name:

```env
DB_HOST=postgres
```

---

## Frontend Environment

Example `frontend/.env`:

```env
VITE_API_URL=http://localhost:8000/api
```

Only variables prefixed with `VITE_` are exposed to the frontend.

Do not put secrets into frontend env files.

---

## Docker Start

Start all services:

```bash
docker compose up -d
```

Stop all services:

```bash
docker compose down
```

View logs:

```bash
docker compose logs -f
```

Rebuild services:

```bash
docker compose up -d --build
```

---

## Docker Services

Expected services:

```txt
nginx      Laravel web server
php        PHP-FPM Laravel container
postgres   PostgreSQL database
frontend   Vue + Vite dev server using Bun
```

---

## Install Backend Dependencies

```bash
docker compose exec php composer install
```

Generate Laravel app key:

```bash
docker compose exec php php artisan key:generate
```

Run migrations:

```bash
docker compose exec php php artisan migrate
```

Optional seed:

```bash
docker compose exec php php artisan db:seed
```

---

## Install Frontend Dependencies

The frontend uses Bun.

```bash
docker compose exec frontend bun install
```

Run frontend dev server:

```bash
docker compose exec frontend bun run dev
```

Build frontend:

```bash
docker compose exec frontend bun run build
```

Type check:

```bash
docker compose exec frontend bun run type-check
```

Lint:

```bash
docker compose exec frontend bun run lint
```

Run tests:

```bash
docker compose exec frontend bun run test
```

---

## Local Development Without Docker

Backend:

```bash
cd backend
composer install
php artisan key:generate
php artisan migrate
php artisan serve
```

Frontend:

```bash
cd frontend
bun install
bun run dev
```

---

## Frontend Scripts

Expected `frontend/package.json` scripts:

```json
{
  "scripts": {
    "dev": "vite",
    "build": "vue-tsc -b && vite build",
    "preview": "vite preview",
    "type-check": "vue-tsc -b",
    "lint": "eslint .",
    "test": "vitest"
  }
}
```

Run scripts with Bun:

```bash
bun run dev
bun run build
bun run type-check
bun run lint
bun run test
```

Do not use npm, yarn, or pnpm unless the project is intentionally being migrated.

---

## Frontend Lockfile

The frontend uses:

```txt
frontend/bun.lock
```

Do not commit:

```txt
frontend/package-lock.json
frontend/yarn.lock
frontend/pnpm-lock.yaml
```

---

## API Client

Frontend HTTP client should live in:

```txt
frontend/src/shared/api/http.ts
```

Example:

```ts
import axios from 'axios';

export const http = axios.create({
  baseURL: import.meta.env.VITE_API_URL,
  withCredentials: true,
});
```

API functions should be isolated in feature/entity folders:

```txt
frontend/src/entities/user/api/getUser.ts
frontend/src/features/auth/api/login.ts
```

---

## API Contracts

API contracts live in:

```txt
contracts/
```

Recommended file:

```txt
contracts/openapi.yaml
```

For small projects, manual frontend API types are allowed:

```txt
frontend/src/shared/api/types/
```

Backend request validation should live in:

```txt
backend/app/Http/Requests/
```

Backend response resources should live in:

```txt
backend/app/Http/Resources/
```

When changing API request or response shape, update:

```txt
contracts/
backend/app/Http/Requests/
backend/app/Http/Resources/
frontend/src/shared/api/types/
frontend/src/shared/api/
```

---

## Backend Commands

Run tests:

```bash
docker compose exec php php artisan test
```

Clear Laravel cache:

```bash
docker compose exec php php artisan optimize:clear
```

Create migration:

```bash
docker compose exec php php artisan make:migration create_users_table
```

Create controller:

```bash
docker compose exec php php artisan make:controller UserController
```

Create request:

```bash
docker compose exec php php artisan make:request StoreUserRequest
```

Create resource:

```bash
docker compose exec php php artisan make:resource UserResource
```

---

## Frontend Commands

Add dependency:

```bash
docker compose exec frontend bun add axios
```

Add dev dependency:

```bash
docker compose exec frontend bun add -d vitest vue-tsc eslint
```

Remove dependency:

```bash
docker compose exec frontend bun remove axios
```

---

## Makefile Commands

If the project has a `Makefile`, recommended commands:

```makefile
up:
 docker compose up -d

down:
 docker compose down

restart:
 docker compose down
 docker compose up -d

logs:
 docker compose logs -f

backend-install:
 docker compose exec php composer install

backend-key:
 docker compose exec php php artisan key:generate

migrate:
 docker compose exec php php artisan migrate

backend-test:
 docker compose exec php php artisan test

frontend-install:
 docker compose exec frontend bun install

frontend-dev:
 docker compose exec frontend bun run dev

frontend-build:
 docker compose exec frontend bun run build

frontend-type-check:
 docker compose exec frontend bun run type-check

frontend-lint:
 docker compose exec frontend bun run lint
```

Usage:

```bash
make up
make backend-install
make backend-key
make migrate
make frontend-install
```

---

## CORS

Frontend usually runs on:

```txt
http://localhost:5173
```

Backend usually runs on:

```txt
http://localhost:8000
```

If the browser blocks API requests, check Laravel CORS settings.

For cookie-based authentication, backend should allow credentials, and frontend should use:

```ts
withCredentials: true
```

---

## Git Commit Rules

Use Conventional Commits.

Allowed scopes:

```txt
backend
frontend
contracts
docker
docs
repo
ci
```

Examples:

```txt
chore(repo): initialize project structure
chore(docker): add local development compose
feat(backend): add users api
feat(frontend): add users page
feat(contracts): add user schema
fix(backend): validate user email
fix(frontend): handle api validation errors
docs(api): describe auth endpoints
```

Bad commit messages:

```txt
fix
update
final
changes
some edits
```

---

## Branch Naming

Use clear branch names:

```txt
feature/auth
feature/users-page
fix/login-validation
chore/docker-setup
docs/api-contracts
```

---

## Before Commit Checklist

Before committing backend changes:

```bash
docker compose exec php php artisan test
```

Before committing frontend changes:

```bash
docker compose exec frontend bun run type-check
docker compose exec frontend bun run lint
docker compose exec frontend bun run build
```

Before committing API changes:

```txt
Update backend validation
Update backend resources
Update frontend API types/client
Update contracts
```

---

## Do Not Commit

Do not commit:

```txt
.env
backend/.env
frontend/.env
backend/vendor/
frontend/node_modules/
frontend/dist/
frontend/package-lock.json
frontend/yarn.lock
frontend/pnpm-lock.yaml
```

---

## Notes

Backend and frontend are independent applications.

Backend is responsible for:

```txt
business logic
validation
database
authorization
authentication
API responses
```

Frontend is responsible for:

```txt
UI
forms
routing
client-side state
API calls
displaying errors
```

The contract between them is:

```txt
JSON over HTTP
```
