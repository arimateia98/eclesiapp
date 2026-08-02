.PHONY: env build key up down logs migrate test lint analyse frontend-quality quality setup

env:
	@test -f .env || cp .env.example .env
	@test -f backend/.env || cp backend/.env.example backend/.env
	@test -f frontend/.env || cp frontend/.env.example frontend/.env

build:
	docker compose build

key:
	@if grep -q '^APP_KEY=$$' backend/.env; then \
		docker compose run --rm app php artisan key:generate; \
	else \
		echo "APP_KEY já configurada; nenhuma rotação foi realizada."; \
	fi

up:
	docker compose up -d

down:
	docker compose down

logs:
	docker compose logs -f app nginx frontend queue scheduler

migrate:
	docker compose exec app php artisan migrate --force

test:
	docker compose run --rm app composer test

lint:
	docker compose run --rm app composer lint

analyse:
	docker compose run --rm app composer analyse

frontend-quality:
	docker compose run --rm frontend npm run lint
	docker compose run --rm frontend npm run test
	docker compose run --rm frontend npm run build

quality: lint analyse test frontend-quality

setup: env build key up migrate
