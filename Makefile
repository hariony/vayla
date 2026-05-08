.PHONY: help install init up down restart build logs shell shell-db tinker migrate fresh seed rollback npm-dev npm-build npm-install artisan composer test cache clean network configure-env

# Colors
GREEN  := \033[0;32m
YELLOW := \033[0;33m
CYAN   := \033[0;36m
RESET  := \033[0m

help: ## Show this help
	@echo ""
	@echo "$(CYAN)╔══════════════════════════════════════╗$(RESET)"
	@echo "$(CYAN)║      corekit - Project Commands      ║$(RESET)"
	@echo "$(CYAN)╚══════════════════════════════════════╝$(RESET)"
	@echo ""
	@grep -E '^[a-zA-Z_-]+:.*?## .*$$' $(MAKEFILE_LIST) | sort | \
		awk 'BEGIN {FS = ":.*?## "}; {printf "  $(GREEN)%-18s$(RESET) %s\n", $$1, $$2}'
	@echo ""

# ── Network ───────────────────────────

network: ## Create external Docker network
	docker network inspect corekit-network >/dev/null 2>&1 || docker network create corekit-network
	@echo "$(GREEN)✔ corekit-network ready$(RESET)"

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
	@sleep 3
	@echo "$(CYAN)▶ Generating app key...$(RESET)"
	docker compose exec app php artisan key:generate --ansi
	@echo "$(CYAN)▶ Installing Inertia (server-side)...$(RESET)"
	docker compose exec app composer require inertiajs/inertia-laravel
	docker compose exec app php artisan inertia:middleware
	@echo "$(CYAN)▶ Installing Vue 3 + Bootstrap + Sass...$(RESET)"
	docker compose exec node npm install vue@3 @inertiajs/vue3 @vitejs/plugin-vue bootstrap @popperjs/core sass
	@echo "$(CYAN)▶ Building assets...$(RESET)"
	docker compose exec node npm run build
	@echo "$(CYAN)▶ Running migrations...$(RESET)"
	docker compose exec app php artisan migrate
	@echo ""
	@echo "$(GREEN)✅ Installation complete!$(RESET)"
	@echo "$(YELLOW)   App:  http://localhost:8040$(RESET)"
	@echo "$(YELLOW)   Dev:  run 'make npm-dev' for HMR$(RESET)"
	@echo ""

configure-env: ## Configure Laravel .env for PostgreSQL
	@sed -i 's/DB_CONNECTION=.*/DB_CONNECTION=pgsql/' laravel/.env
	@sed -i 's/DB_HOST=.*/DB_HOST=postgres/' laravel/.env
	@sed -i 's/DB_PORT=.*/DB_PORT=5432/' laravel/.env
	@sed -i 's/DB_DATABASE=.*/DB_DATABASE=corekit/' laravel/.env
	@sed -i 's/DB_USERNAME=.*/DB_USERNAME=corekit/' laravel/.env
	@sed -i 's/DB_PASSWORD=.*/DB_PASSWORD=corekit/' laravel/.env
	@echo "$(GREEN)✔ .env configured$(RESET)"

init: network ## Re-initialize (after git clone)
	docker compose build
	docker compose up -d
	docker compose exec app composer install
	docker compose exec app cp -n /var/www/html/.env.example /var/www/html/.env || true
	@$(MAKE) --no-print-directory configure-env
	docker compose exec app php artisan key:generate
	docker compose exec node npm install
	docker compose exec node npm run build
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
	docker compose exec postgres psql -U corekit -d corekit

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