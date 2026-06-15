# .ai/skills/vue.md

## Vue.js + Bun Skill

Use this skill when working inside:

```txt
frontend/
```

The frontend is a Vue.js application using:

* Bun
* Vue 3
* Vite
* TypeScript

---

## Frontend Responsibilities

Vue is responsible for:

* UI rendering
* pages
* layouts
* routing
* forms
* client-side validation
* local UI state
* API calls
* displaying backend validation errors

Vue must not contain backend business logic.

Backend business rules belong to Laravel.

---

## Package Manager

Use Bun as the package manager and runtime for frontend tasks.

Use Bun commands instead of npm, yarn, or pnpm.

Correct commands:

```bash
bun install
bun run dev
bun run build
bun run lint
bun run type-check
bun run test
```

Do not use:

```bash
npm install
npm run dev
yarn
pnpm
```

unless the project explicitly asks for migration or compatibility work.

---

## Recommended Structure

For a small or medium project:

```txt
frontend/
├─ src/
│  ├─ app/
│  │  ├─ router/
│  │  ├─ store/
│  │  └─ providers/
│  │
│  ├─ pages/
│  ├─ widgets/
│  ├─ features/
│  ├─ entities/
│  ├─ shared/
│  │  ├─ api/
│  │  ├─ ui/
│  │  ├─ lib/
│  │  └─ config/
│  │
│  ├─ App.vue
│  └─ main.ts
│
├─ public/
├─ bun.lock
├─ package.json
├─ tsconfig.json
├─ tsconfig.app.json
├─ tsconfig.node.json
├─ vite.config.ts
└─ index.html
```

Simpler structure is allowed for small projects:

```txt
src/
├─ api/
├─ components/
├─ composables/
├─ layouts/
├─ pages/
├─ router/
├─ stores/
├─ types/
└─ main.ts
```

Do not over-engineer the structure if the project is small.

---

## Bun Lockfile

The frontend should use:

```txt
bun.lock
```

Do not commit other package manager lockfiles unless the project intentionally supports them.

Avoid committing:

```txt
package-lock.json
yarn.lock
pnpm-lock.yaml
```

If such files appear accidentally, remove them unless there is a documented reason to keep them.

---

## package.json

Expected scripts:

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

Use `bun run <script>` to execute scripts.

Examples:

```bash
bun run dev
bun run build
bun run type-check
```

---

## TypeScript Rules

Use TypeScript by default.

Prefer explicit types for:

* API responses
* API request payloads
* props
* emits
* composables
* store state
* complex computed values

Avoid `any`.

Use `unknown` when the type is truly unknown and narrow it later.

Example:

```ts
type User = {
  id: number;
  name: string;
  email: string;
};
```

---

## Vue Component Style

Use Single File Components:

```vue
<script setup lang="ts">
</script>

<template>
</template>

<style scoped>
</style>
```

Prefer Composition API.

Avoid Options API unless the project already uses it.

---

## Props and Emits

Use typed props:

```vue
<script setup lang="ts">
type Props = {
  title: string;
  loading?: boolean;
};

defineProps<Props>();
</script>
```

Use typed emits:

```vue
<script setup lang="ts">
const emit = defineEmits<{
  submit: [value: string];
  cancel: [];
}>();
</script>
```

---

## Component Rules

Components should be:

* small
* readable
* focused
* typed
* easy to delete
* reusable only when reuse is real

Do not move code to shared components too early.

Bad abstraction is worse than duplication.

---

## API Layer

All HTTP client setup should live in:

```txt
frontend/src/shared/api/
```

Example with Axios:

```ts
// frontend/src/shared/api/http.ts

import axios from 'axios';

export const http = axios.create({
  baseURL: import.meta.env.VITE_API_URL,
  withCredentials: true,
});
```

Do not create random axios instances across the project.

If using native `fetch`, wrap it in one shared client instead of scattering raw `fetch` calls everywhere.

---

## Environment

Required frontend env example:

```env
VITE_API_URL=http://localhost:8000/api
```

Only variables prefixed with `VITE_` are exposed to the frontend.

Do not put secrets into frontend environment files.

Anything in frontend code can be seen by the user.

---

## API Types

API types should live in:

```txt
frontend/src/shared/api/types/
```

Example:

```ts
export type User = {
  id: number;
  name: string;
  email: string;
};

export type CreateUserRequest = {
  name: string;
  email: string;
};
```

For larger projects, prefer generated types from:

```txt
contracts/openapi.yaml
```

---

## API Functions

Keep API functions close to the feature/entity that uses them.

Example:

```txt
src/entities/user/api/getUser.ts
src/features/auth/api/login.ts
```

Example:

```ts
import { http } from '@/shared/api/http';
import type { User } from '@/shared/api/types/user';

export async function getUser(id: number): Promise<User> {
  const response = await http.get<User>(`/users/${id}`);
  return response.data;
}
```

Do not call API endpoints directly from random components if the call can be isolated into an API function.

---

## Pages

Pages should compose features, widgets, and entities.

Pages should not contain too much business logic.

Example:

```txt
src/pages/users/UsersPage.vue
src/pages/auth/LoginPage.vue
```

---

## Features

Features are user actions or flows.

Examples:

```txt
features/auth/login
features/user/create-user
features/profile/update-profile
```

A feature may contain:

```txt
api/
model/
ui/
```

Example:

```txt
features/auth/login/
├─ api/
│  └─ login.ts
├─ model/
│  └─ types.ts
└─ ui/
   └─ LoginForm.vue
```

---

## Entities

Entities are domain objects.

Examples:

```txt
entities/user
entities/order
entities/product
```

An entity may contain:

```txt
api/
model/
types.ts
ui/
```

Example:

```txt
entities/user/
├─ api/
│  └─ getUser.ts
├─ model/
│  └─ types.ts
└─ ui/
   └─ UserCard.vue
```

---

## Shared

Shared contains reusable technical pieces:

```txt
shared/api
shared/ui
shared/lib
shared/config
```

Do not put domain-specific logic into `shared/`.

Good candidates for `shared/`:

* HTTP client
* base UI components
* date helpers
* formatting helpers
* app config
* common composables

Bad candidates for `shared/`:

* user-specific business logic
* order calculation rules
* authentication workflows
* page-specific components

---

## Router

Router should live in:

```txt
src/app/router/
```

Example:

```ts
import { createRouter, createWebHistory } from 'vue-router';

export const router = createRouter({
  history: createWebHistory(),
  routes: [],
});
```

Use lazy loading for pages when appropriate:

```ts
const LoginPage = () => import('@/pages/auth/LoginPage.vue');
```

---

## Store

Use Pinia if global state is needed.

Do not put everything into global state.

Use local component state when possible.

Good candidates for global state:

* authenticated user
* theme
* permissions
* app-level settings

Bad candidates:

* every form field
* temporary modal state
* one-page-only filters

---

## Composables

Composables should live in:

```txt
src/shared/lib/
src/features/*/model/
src/entities/*/model/
```

Depending on their responsibility.

Example:

```ts
export function useBoolean(initialValue = false) {
  const value = ref(initialValue);

  function setTrue() {
    value.value = true;
  }

  function setFalse() {
    value.value = false;
  }

  return {
    value,
    setTrue,
    setFalse,
  };
}
```

Do not create composables for code that is only used once unless it improves readability.

---

## Forms

Frontend validation is for user convenience.

Backend validation is the source of truth.

Always handle backend validation errors.

Example backend validation error:

```json
{
  "message": "The given data was invalid.",
  "errors": {
    "email": [
      "The email field is required."
    ]
  }
}
```

Frontend should map these errors to form fields where possible.

---

## Error Handling

Centralize common API error handling.

Do not silently swallow errors.

Show useful messages to the user.

Prefer typed error helpers.

Example:

```ts
type ValidationErrors = Record<string, string[]>;

export function isValidationError(error: unknown): error is {
  response: {
    status: 422;
    data: {
      message: string;
      errors: ValidationErrors;
    };
  };
} {
  return Boolean(
    typeof error === 'object' &&
      error !== null &&
      'response' in error
  );
}
```

---

## Styling

Use one styling approach consistently.

Allowed options:

* plain CSS/SCSS
* CSS modules
* Tailwind
* UI library

Do not mix many styling systems without a reason.

---

## Import Rules

Prefer aliases instead of deep relative imports.

Good:

```ts
import { http } from '@/shared/api/http';
```

Bad:

```ts
import { http } from '../../../shared/api/http';
```

Alias should be configured in:

```txt
vite.config.ts
tsconfig.json
```

---

## Useful Bun Commands

Install dependencies:

```bash
bun install
```

Run dev server:

```bash
bun run dev
```

Build:

```bash
bun run build
```

Preview production build:

```bash
bun run preview
```

Lint:

```bash
bun run lint
```

Type check:

```bash
bun run type-check
```

Run tests:

```bash
bun run test
```

Add dependency:

```bash
bun add axios
```

Add dev dependency:

```bash
bun add -d vitest vue-tsc eslint
```

Remove dependency:

```bash
bun remove axios
```

---

## Docker Commands

From repository root:

```bash
docker compose exec frontend bun install
docker compose exec frontend bun run dev
docker compose exec frontend bun run build
docker compose exec frontend bun run type-check
```

The frontend Docker image should use Bun, not Node npm, if the project is standardized on Bun.

---

## Docker Compose Frontend Service

Recommended dev service:

```yaml
frontend:
  image: oven/bun:1
  container_name: my_project_frontend
  working_dir: /app
  ports:
    - "5173:5173"
  volumes:
    - ./frontend:/app
  command: sh -c "bun install && bun run dev -- --host 0.0.0.0"
  networks:
    - my_project_network
```

Do not use a Node image for the frontend service unless Bun is unavailable or compatibility requires it.

---

## Vite Config

Expected `vite.config.ts`:

```ts
import { fileURLToPath, URL } from 'node:url';
import { defineConfig } from 'vite';
import vue from '@vitejs/plugin-vue';

export default defineConfig({
  plugins: [vue()],
  resolve: {
    alias: {
      '@': fileURLToPath(new URL('./src', import.meta.url)),
    },
  },
  server: {
    host: '0.0.0.0',
    port: 5173,
  },
});
```

---

## tsconfig Alias

Expected alias support in `tsconfig.json` or related config:

```json
{
  "compilerOptions": {
    "baseUrl": ".",
    "paths": {
      "@/*": ["src/*"]
    }
  }
}
```

---

## When Changing API Usage

When frontend starts using a new endpoint:

1. Add or update API type.
2. Add API function.
3. Use API function inside feature/entity.
4. Handle loading state.
5. Handle error state.
6. Handle backend validation errors.
7. Update contracts if needed.

---

## Do Not Do

Do not:

* use npm/yarn/pnpm commands in a Bun project
* commit `package-lock.json`, `yarn.lock`, or `pnpm-lock.yaml`
* hardcode API base URLs
* put secrets in frontend env
* create many axios instances
* duplicate backend business logic
* ignore backend validation errors
* put everything into global store
* overuse shared components
* create abstractions before they are needed
* change API payload assumptions without checking backend contract
