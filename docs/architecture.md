# Architecture

The repository contains two independent apps:

- `backend/`: Laravel JSON API, Sanctum SPA auth, PostgreSQL persistence.
- `frontend/`: Vue 3 + Vite + TypeScript SPA.

The frontend never launches the backend directly. It calls `VITE_API_URL` with credentials enabled.

## Data Flow

1. User opens the SPA and logs in through Sanctum cookie auth.
2. User saves a Yandex.Maps organization URL.
3. `OrganizationController` validates the URL and calls `OrganizationSyncService`.
4. `OrganizationSyncService` calls `YandexMapsScraper`, then upserts organization stats and reviews.
5. The SPA displays organization stats and paginates cached reviews with `GET /api/organization/reviews?page=N`.

## Persistence

- `organizations`: one parsed Yandex organization per user/business id, with exact rating and review counters.
- `organization_reviews`: cached review rows keyed by `organization_id + yandex_review_id`.
- `sessions`: Laravel session storage for Sanctum SPA auth.

## Failure Handling

Parser failures are returned as HTTP 422 with a human-readable message. Validation errors use Laravel's standard 422 format. Unauthenticated requests return 401.
