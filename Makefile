.PHONY: up down build shell migrate seed fresh test install key logs test-frontend test-e2e

up:
	docker compose up -d

down:
	docker compose down

build:
	docker compose build --no-cache

shell:
	docker compose exec app bash

migrate:
	docker compose exec app php artisan migrate

seed:
	docker compose exec app php artisan db:seed

fresh:
	docker compose exec app php artisan migrate:fresh --seed

test:
	docker compose exec app php artisan test

install:
	docker compose build --no-cache
	docker compose up -d db
	@echo "Waiting for database to be ready..."
	@until docker compose exec db mysqladmin ping -h localhost -u root -ppassword >/dev/null 2>&1; do echo "Database is unavailable - sleeping"; sleep 2; done
	@echo "Database is ready!"
	docker compose run --rm app sh -lc "find vendor -mindepth 1 -delete && composer install --no-interaction"
	$(MAKE) key
	docker compose run --rm node sh -lc "find node_modules -mindepth 1 -delete && npm install"
	docker compose up -d
	docker compose run --rm app php artisan migrate --seed
	@echo "Installation complete!"

key:
	docker compose run --rm --no-deps app sh -lc 'test -f .env || cp .env.example .env; key="$$(grep -E "^APP_KEY=" .env 2>/dev/null | head -n1 | cut -d= -f2- | tr -d "\r")"; if [ -z "$$key" ]; then php artisan key:generate --ansi; else echo "APP_KEY already set"; fi'

logs:
	docker compose logs -f

test-frontend:
	npm run test:frontend

test-e2e:
	npx playwright test
