# Yandex Reviews Test App

Laravel API + Vue 3 SPA for connecting a Yandex.Maps organization card, parsing reviews, and showing cached rating/review data.

## Ports

- Backend API: http://localhost:25200/api
- Frontend SPA: http://localhost:25300
- PostgreSQL: localhost:5432

## Demo Login

- Email: `demo@example.com`
- Password: `ReviewsDemo!2026#7pQz`

## Run

```bash
docker compose up -d --build
```

Open http://localhost:25300.

On backend start, the container waits for PostgreSQL, runs `php artisan migrate --force`, then runs `php artisan db:seed --force`. Laravel stores applied migrations in the standard `migrations` table and skips migrations that are already recorded there.

## Checks

```bash
docker compose exec php php artisan test
docker compose exec frontend bun run type-check
docker compose exec frontend bun run lint
docker compose exec frontend bun run build
```

## Parsing Approach

The parser is implemented in `backend/app/Services/YandexMaps/YandexMapsScraper.php`, outside controllers. It loads the organization page, extracts the `state-view` JSON, finds the business card, then calls the same internal Yandex.Maps reviews endpoint used by the web client: `/maps/api/business/fetchReviews`.

The request includes the page `csrfToken`, session id, business `reqId`, paging params, and the `s` query signature reproduced from Yandex frontend code. This avoids fragile DOM scrolling while still handling Yandex's script-loaded review pagination. If Yandex changes the page state or internal endpoint shape, the service raises a clear parser exception.

By default the app fetches up to `YANDEX_MAPS_MAX_REVIEWS=600` reviews in pages of 50. The parsed result is cached in PostgreSQL (`organizations`, `organization_reviews`) on save. UI pagination reads from the backend cache, so switching pages does not re-parse Yandex.

## Logging

Backend logging goes through `App\Services\Logging\AppLogger`.

Frontend logging goes through `src/shared/lib/logger.ts`.

Both use the requested structure:

```txt
log ( { method-name }, detailed_data )
```
