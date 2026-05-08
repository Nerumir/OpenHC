ENV_FILE ?= .env.docker
COMPOSE   = docker compose --env-file $(ENV_FILE)

.PHONY: help up down build rebuild logs shell migrate seed fresh artisan assets uninstall

help: ## Show this help
	@grep -E '^[a-zA-Z_-]+:.*?## .*$$' $(MAKEFILE_LIST) \
	| awk 'BEGIN {FS = ":.*?## "}; {printf "  \033[36m%-14s\033[0m %s\n", $$1, $$2}'

up: ## Start all services (builds image if needed)
	$(COMPOSE) up -d --build

down: ## Stop and remove containers
	$(COMPOSE) down

build: ## Build the Docker image without starting
	$(COMPOSE) build

rebuild: ## Force rebuild and restart
	$(COMPOSE) up -d --build --force-recreate

logs: ## Tail logs from the app container
	$(COMPOSE) logs -f app

shell: ## Open a shell in the app container
	$(COMPOSE) exec app sh

migrate: ## Run database migrations
	$(COMPOSE) exec app php artisan migrate --force

seed: ## Run database seeders
	$(COMPOSE) exec app php artisan db:seed --force

fresh: ## Drop all tables and re-run migrations
	$(COMPOSE) exec app php artisan migrate:fresh --force

artisan: ## Run an artisan command — usage: make artisan CMD="route:list"
	$(COMPOSE) exec app php artisan $(CMD)

assets: ## Force rebuild front-end assets (npm ci + npm run build)
	$(COMPOSE) exec app sh -c "npm ci && npm run build"

uninstall: ## ⚠️  Remove containers, images, volumes and networks (destroys all data)
	$(COMPOSE) down --rmi all --volumes --remove-orphans
