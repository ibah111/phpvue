# .ai/skills/php-laravel.md

## PHP Laravel Skill

Use this skill when working inside:

```txt
backend/
```

The backend is a Laravel JSON API application.

---

## Backend Responsibilities

Laravel is responsible for:

* API endpoints
* request validation
* authentication
* authorization
* business logic
* database operations
* migrations
* seeders
* jobs
* events
* API resources
* tests

Frontend must not duplicate backend business rules.

---

## Recommended Structure

```txt
backend/
├─ app/
│  ├─ Http/
│  │  ├─ Controllers/
│  │  ├─ Middleware/
│  │  ├─ Requests/
│  │  └─ Resources/
│  │
│  ├─ Models/
│  ├─ Services/
│  ├─ Actions/
│  ├─ Repositories/
│  ├─ Policies/
│  ├─ Jobs/
│  ├─ Events/
│  └─ Listeners/
│
├─ routes/
│  ├─ api.php
│  └─ web.php
│
├─ database/
│  ├─ migrations/
│  ├─ seeders/
│  └─ factories/
│
├─ config/
├─ tests/
├─ composer.json
└─ artisan
```

---

## Controller Rules

Controllers should be thin.

Good controller responsibilities:

* receive request
* call FormRequest validation
* call Service or Action
* return Resource or JSON response

Avoid putting business logic directly in controllers.

Example:

```php
public function store(StoreUserRequest $request): UserResource
{
    $user = $this->createUserAction->handle($request->validated());

    return new UserResource($user);
}
```

---

## Request Validation

Use FormRequest classes for validation.

Location:

```txt
backend/app/Http/Requests/
```

Example:

```php
namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreUserRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
        ];
    }
}
```

Do not validate large payloads directly inside controllers unless the endpoint is very small.

---

## API Resources

Use Laravel Resources for API responses.

Location:

```txt
backend/app/Http/Resources/
```

Example:

```php
namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
        ];
    }
}
```

Do not expose raw models directly if the response shape matters.

---

## Services and Actions

Use Actions for single use-case operations.

Example:

```txt
app/Actions/Auth/LoginUserAction.php
app/Actions/Users/CreateUserAction.php
```

Use Services for reusable domain operations.

Example:

```txt
app/Services/AuthService.php
app/Services/UserService.php
```

Do not create services just for the sake of creating services.

---

## Models

Models should contain:

* relationships
* casts
* fillable/guarded
* scopes
* simple domain helpers

Avoid putting large procedural workflows inside models.

---

## Repositories

Repositories are optional.

Use repositories only if:

* data access logic is complex
* multiple data sources are involved
* query logic is reused
* testing requires abstraction

Do not create repositories for every model automatically.

---

## Routes

API routes should live in:

```txt
routes/api.php
```

Example:

```php
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\UserController;

Route::get('/users/{id}', [UserController::class, 'show']);
Route::post('/users', [UserController::class, 'store']);
```

Keep route groups clear:

```php
Route::prefix('auth')->group(function () {
    Route::post('/login', [AuthController::class, 'login']);
    Route::post('/logout', [AuthController::class, 'logout']);
});
```

---

## Database

Use migrations for schema changes.

Do not edit old migrations after they have been shared with other developers unless the project is still in early local-only development.

Use seeders for test/demo data.

Use factories for test data generation.

---

## API Response Format

Prefer consistent API responses.

Success example:

```json
{
  "data": {
    "id": 1,
    "name": "Ivan"
  }
}
```

Validation error example:

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

---

## Error Handling

Use Laravel exceptions and validation errors.

Do not return random response formats from different controllers.

For known business errors, use explicit exceptions or clear response objects.

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

Configure CORS properly.

If using cookie-based auth:

```php
'supports_credentials' => true,
```

Frontend must also use:

```ts
withCredentials: true
```

---

## Environment

Required backend env example:

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

Inside Docker, use service names:

```env
DB_HOST=postgres
```

Do not use real secrets in committed files.

---

## Useful Commands

Install dependencies:

```bash
composer install
```

Generate app key:

```bash
php artisan key:generate
```

Run migrations:

```bash
php artisan migrate
```

Run tests:

```bash
php artisan test
```

Clear caches:

```bash
php artisan optimize:clear
```

---

## Docker Commands

From repository root:

```bash
docker compose exec php composer install
docker compose exec php php artisan key:generate
docker compose exec php php artisan migrate
docker compose exec php php artisan test
```

---

## Testing

Prefer feature tests for API endpoints.

Example areas to test:

* validation
* authentication
* authorization
* resource response structure
* database writes
* error cases

---

## When Changing API

When changing a request or response:

1. Update FormRequest.
2. Update Resource.
3. Update controller/action/service.
4. Update tests.
5. Update `contracts/`.
6. Update frontend API types/client.

---

## Do Not Do

Do not:

* put business logic in controllers
* return raw database models when response shape matters
* skip validation
* hardcode frontend URLs
* hardcode secrets
* create repositories for every model automatically
* mix unrelated concerns inside one service
* change API response shape without updating frontend and contracts
