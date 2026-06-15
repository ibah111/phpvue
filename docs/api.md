# API

Base URL: `http://localhost:25200/api`

Sanctum CSRF endpoint: `GET http://localhost:25200/sanctum/csrf-cookie`

## Auth

`POST /auth/login`

```json
{
  "email": "demo@example.com",
  "password": "password"
}
```

`GET /auth/me`

`POST /auth/logout`

## Organization

`GET /organization`

Returns the latest connected organization or `{"data": null}`.

`POST /organization`

```json
{
  "url": "https://yandex.ru/maps/org/krasnaya_ploshchad/10661349235/reviews/"
}
```

Validates, parses, saves and returns organization stats.

`GET /organization/reviews?page=1`

Returns cached reviews, 50 per page.
