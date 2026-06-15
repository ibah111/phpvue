# Frontend

Vue 3 + Vite + TypeScript SPA for the Yandex Reviews test app.

Main parts:

- `src/app/router` for protected routes.
- `src/app/store/session.ts` for simple auth state.
- `src/shared/api/http.ts` for Axios + Sanctum cookies.
- `src/shared/lib/logger.ts` for structured frontend logging.

Run through the repository root `docker-compose.yml` or locally with:

```bash
bun install
bun run dev
```
