.DEFAULT_GOAL := help

.PHONY: help up down restart build test test-php test-py e2e migrate shell shell-sidecar logs ps clean

help: ## List available targets
	@awk 'BEGIN {FS = ":.*?## "} /^[a-zA-Z_-]+:.*?## / {printf "  \033[36m%-16s\033[0m %s\n", $$1, $$2}' $(MAKEFILE_LIST)

up: ## Start stack (app, nginx, postgres, redis, sidecar)
	@test -f .env || cp .env.example .env
	docker compose up -d --wait

down: ## Stop stack
	docker compose down

restart: down up ## Restart stack

build: ## Rebuild images
	docker compose build

test: test-php test-py ## Run all backend tests

test-php: ## Run Pest
	docker compose exec -T app vendor/bin/pest --testdox

test-py: ## Run pytest
	docker compose exec -T sidecar pytest

e2e: ## Run Playwright E2E (requires Node)
	cd app && npx playwright test

migrate: ## Apply Doctrine migrations
	docker compose exec -T app php bin/console doctrine:migrations:migrate -n

shell: ## Shell into app container
	docker compose exec app bash

shell-sidecar: ## Shell into sidecar container
	docker compose exec sidecar bash

logs: ## Tail all logs
	docker compose logs -f

ps: ## List running services
	docker compose ps

clean: ## Stop stack + drop volumes (DESTRUCTIVE)
	docker compose down -v
