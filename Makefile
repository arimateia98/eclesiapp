.PHONY: setup up down migrate seed test lint typecheck api-docs web mobile

setup:
	cp .env.example .env
	cp apps/api/.env.example apps/api/.env
	docker compose build
	docker compose run --rm api php artisan key:generate
	docker compose run --rm api php artisan migrate
	docker compose run --rm api php artisan db:seed

up:
	docker compose up -d

down:
	docker compose down

migrate:
	docker compose run --rm api php artisan migrate

seed:
	docker compose run --rm api php artisan db:seed

test:
	docker compose up -d --wait postgres_test
	docker compose run --rm --no-deps -e APP_ENV=testing -e DB_CONNECTION=pgsql -e DB_HOST=postgres_test -e DB_PORT=5432 -e DB_DATABASE=eclezapp_test -e DB_USERNAME=eclezapp_test -e DB_PASSWORD=test api php artisan test
	pnpm test

lint:
	docker compose run --rm api ./vendor/bin/pint --test
	docker compose run --rm api ./vendor/bin/phpstan analyse
	pnpm lint

typecheck:
	pnpm typecheck

api-docs:
	pnpm dlx @redocly/cli lint docs/api/openapi.yaml

web:
	pnpm --filter @eclezapp/web dev

mobile:
	pnpm --filter @eclezapp/mobile start
