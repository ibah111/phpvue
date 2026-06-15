# Backend

Laravel JSON API for the Yandex Reviews test app.

Main parts:

- Sanctum SPA authentication.
- PostgreSQL migrations for organizations and cached reviews.
- `App\Services\YandexMaps\YandexMapsScraper` for Yandex.Maps parsing.
- `App\Services\Organizations\OrganizationSyncService` for persistence.
- `App\Services\Logging\AppLogger` for structured logging.

Run through the repository root `docker-compose.yml`.
