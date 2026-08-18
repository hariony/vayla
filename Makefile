.PHONY: help install init wait-db db-schema up down restart build logs shell shell-db tinker migrate fresh seed rollback npm-dev npm-build npm-install artisan composer test cache clean network configure-env

# Project
PROJECT   := vayla
NETWORK   := $(PROJECT)-network
DB_NAME   := vayla_db
DB_USER   := corekit
DB_PASS   := corekit
DB_SCHEMA := vayla
APP_PORT  ?= 8070

# Colors
GREEN  := \033[0;32m
YELLOW := \033[0;33m
CYAN   := \033[0;36m
RESET  := \033[0m

help: ## Show this help
	@echo ""
	@echo "$(CYAN)╔══════════════════════════════════════╗$(RESET)"
	@echo "$(CYAN)║       $(PROJECT) - Project Commands       ║$(RESET)"
	@echo "$(CYAN)╚══════════════════════════════════════╝$(RESET)"
	@echo ""
	@grep -E '^[a-zA-Z_-]+:.*?## .*$$' $(MAKEFILE_LIST) | sort | \
		awk 'BEGIN {FS = ":.*?## "}; {printf "  $(GREEN)%-18s$(RESET) %s\n", $$1, $$2}'
	@echo ""

# ── Network ───────────────────────────

network: ## Create external Docker network
	docker network inspect $(NETWORK) >/dev/null 2>&1 || docker network create $(NETWORK)
	@echo "$(GREEN)✔ $(NETWORK) ready$(RESET)"

# ── Installation ──────────────────────

install: network ## Full install: Laravel 13 + Inertia + Vue 3 + Bootstrap
	@echo "$(CYAN)▶ Building Docker image...$(RESET)"
	docker compose build
	@echo "$(CYAN)▶ Creating Laravel project in ./laravel ...$(RESET)"
	@touch laravel/.env
	@if [ ! -f laravel/artisan ]; then \
		echo "$(CYAN)▶ Creating Laravel project (v13)...$(RESET)"; \
		docker compose run --rm --no-deps app sh -c "composer create-project laravel/laravel:^13.0 /tmp/laravel --prefer-dist --no-scripts && cp -a /tmp/laravel/. /var/www/html/ && rm -rf /tmp/laravel"; \
	else \
		echo "$(YELLOW)✔ Laravel already exists, skipping creation$(RESET)"; \
	fi
	@echo "$(CYAN)▶ Configuring .env for PostgreSQL...$(RESET)"
	@$(MAKE) --no-print-directory configure-env
	@echo "$(CYAN)▶ Starting services...$(RESET)"
	docker compose up -d
	@$(MAKE) --no-print-directory wait-db
	@echo "$(CYAN)▶ Installing PHP dependencies...$(RESET)"
	@if [ ! -f laravel/vendor/autoload.php ]; then \
		docker compose exec -T app composer install --no-interaction --prefer-dist; \
	else \
		echo "$(YELLOW)✔ vendor/ already present, skipping$(RESET)"; \
	fi
	@echo "$(CYAN)▶ Generating app key...$(RESET)"
	docker compose exec app php artisan key:generate --ansi
	@echo "$(CYAN)▶ Installing Inertia (server-side)...$(RESET)"
	docker compose exec app composer require inertiajs/inertia-laravel
	docker compose exec app php artisan inertia:middleware
	@echo "$(CYAN)▶ Installing Vue 3 + Bootstrap + Sass...$(RESET)"
	docker compose exec node npm install vue@3 @inertiajs/vue3 @vitejs/plugin-vue bootstrap @popperjs/core sass
	@echo "$(CYAN)▶ Building assets...$(RESET)"
	docker compose exec node npm run build
	@$(MAKE) --no-print-directory db-schema
	@echo "$(CYAN)▶ Running migrations...$(RESET)"
	docker compose exec -T app php artisan migrate --force
	@echo ""
	@echo "$(GREEN)✅ Installation complete!$(RESET)"
	@echo "$(YELLOW)   App:  http://localhost:$(APP_PORT)$(RESET)"
	@echo "$(YELLOW)   Dev:  run 'make npm-dev' for HMR$(RESET)"
	@echo ""

configure-env: ## Configure Laravel .env for PostgreSQL
	@if [ ! -s laravel/.env ] && [ -f laravel/.env.example ]; then \
		cp laravel/.env.example laravel/.env; \
		echo "$(YELLOW)✔ .env created from .env.example$(RESET)"; \
	fi
	@sed -i.bak \
		-e 's|^DB_CONNECTION=.*|DB_CONNECTION=pgsql|' \
		-e 's|^DB_HOST=.*|DB_HOST=postgres|' \
		-e 's|^DB_PORT=.*|DB_PORT=5432|' \
		-e 's|^DB_DATABASE=.*|DB_DATABASE=$(DB_NAME)|' \
		-e 's|^DB_USERNAME=.*|DB_USERNAME=$(DB_USER)|' \
		-e 's|^DB_PASSWORD=.*|DB_PASSWORD=$(DB_PASS)|' \
		-e 's|^DB_SCHEMA=.*|DB_SCHEMA=$(DB_SCHEMA)|' \
		laravel/.env
	@rm -f laravel/.env.bak
	@echo "$(GREEN)✔ .env configured$(RESET)"

wait-db: ## Wait until PostgreSQL accepts connections
	@echo "$(CYAN)▶ Waiting for PostgreSQL...$(RESET)"
	@i=0; until docker compose exec -T postgres pg_isready -U $(DB_USER) -d $(DB_NAME) >/dev/null 2>&1; do \
		i=$$((i+1)); \
		if [ $$i -gt 30 ]; then echo "$(YELLOW)⚠ PostgreSQL still not ready after 60s$(RESET)"; exit 1; fi; \
		sleep 2; \
	done
	@echo "$(GREEN)✔ PostgreSQL ready$(RESET)"

db-schema: ## Create the PostgreSQL schema used by DB_SCHEMA
	@docker compose exec -T postgres psql -q -U $(DB_USER) -d $(DB_NAME) \
		-c 'SET client_min_messages TO WARNING; CREATE SCHEMA IF NOT EXISTS "$(DB_SCHEMA)" AUTHORIZATION $(DB_USER);' >/dev/null
	@echo "$(GREEN)✔ schema $(DB_SCHEMA) ready$(RESET)"

init: network ## Re-initialize (after git clone)
	docker compose build
	docker compose up -d
	@$(MAKE) --no-print-directory wait-db
	docker compose exec -T app composer install --no-interaction --prefer-dist
	docker compose exec app cp -n /var/www/html/.env.example /var/www/html/.env || true
	@$(MAKE) --no-print-directory configure-env
	docker compose exec app php artisan key:generate
	docker compose exec node npm install
	docker compose exec node npm run build
	@$(MAKE) --no-print-directory db-schema
	docker compose exec -T app php artisan migrate --force
	@echo "$(GREEN)✅ Project initialized!$(RESET)"

# ── Docker ────────────────────────────

up: network ## Start containers
	docker compose up -d

down: ## Stop containers
	docker compose down

restart: ## Restart containers
	docker compose restart

build: ## Rebuild images
	docker compose build --no-cache

logs: ## Tail all logs
	docker compose logs -f

logs-app: ## Tail app + nginx logs (dans le même container)
	docker compose logs -f app

logs-db: ## Tail PostgreSQL logs
	docker compose logs -f postgres

# ── Shell ─────────────────────────────

shell: ## Shell into app container (Alpine → sh)
	docker compose exec app sh

shell-db: ## Shell into PostgreSQL
	docker compose exec postgres psql -U $(DB_USER) -d $(DB_NAME)

tinker: ## Laravel Tinker
	docker compose exec app php artisan tinker

# ── Database ──────────────────────────

migrate: ## Run migrations
	docker compose exec app php artisan migrate

fresh: ## Fresh migrate + seed
	docker compose exec app php artisan migrate:fresh --seed

seed: ## Run seeders
	docker compose exec app php artisan db:seed

rollback: ## Rollback last migration
	docker compose exec app php artisan migrate:rollback

# ── Frontend ──────────────────────────

npm-dev: ## Restart Vite dev server (node container)
	docker compose restart node
	docker compose logs -f node

npm-build: ## Build assets for production
	docker compose exec node npm run build

npm-install: ## Install npm dependencies
	docker compose exec node npm install

# ── Artisan & Composer ────────────────

artisan: ## Run artisan command (usage: make artisan cmd="migrate:status")
	docker compose exec app php artisan $(cmd)

composer: ## Run composer command (usage: make composer cmd="require package/name")
	docker compose exec app composer $(cmd)

# ── Testing ───────────────────────────

test: ## Run tests
	docker compose exec app php artisan test

# ── Maintenance ───────────────────────

cache: ## Clear all caches
	docker compose exec app php artisan optimize:clear

clean: ## Stop containers + remove volumes
	docker compose down -v
	@echo "$(YELLOW)⚠ Volumes removed (database data lost)$(RESET)"