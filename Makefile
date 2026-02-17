.PHONY: up down build shell migrate seed fresh test install logs test-frontend test-e2e

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
	docker compose up -d
	@echo "Waiting for database to be ready..."
	@until docker compose exec db mysqladmin ping -h localhost -u root -ppassword >/dev/null 2>&1; do echo "Database is unavailable - sleeping"; sleep 2; done
	@echo "Database is ready!"
	docker compose exec app composer install
	docker compose exec node npm install
	docker compose exec app php artisan migrate --seed
	@echo "Installation complete!"

logs:
	docker compose logs -f

test-frontend:
	npm run test:frontend

test-e2e:
	npx playwright test
