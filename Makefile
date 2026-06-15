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

seed:
	docker compose exec php php artisan db:seed

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
