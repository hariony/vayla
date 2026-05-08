# CoreKit — Laravel 13 + Inertia + Vue 3 Starter

Squelette full-stack prêt à l'emploi pour démarrer rapidement des SaaS,
CRM, ERP ou back-offices, livré entièrement dockerisé.

![Laravel](https://img.shields.io/badge/Laravel-13-FF2D20?logo=laravel&logoColor=white)
![PHP](https://img.shields.io/badge/PHP-8.4-777BB4?logo=php&logoColor=white)
![Vue](https://img.shields.io/badge/Vue-3-42b883?logo=vue.js&logoColor=white)
![Inertia](https://img.shields.io/badge/Inertia.js-3-9553E9)
![Vite](https://img.shields.io/badge/Vite-8-646CFF?logo=vite&logoColor=white)
![PostgreSQL](https://img.shields.io/badge/PostgreSQL-16-336791?logo=postgresql&logoColor=white)
![Docker](https://img.shields.io/badge/Docker-ready-2496ED?logo=docker&logoColor=white)

---

## Sommaire

- [Stack](#stack)
- [Prérequis](#prérequis)
- [Démarrage rapide](#démarrage-rapide)
- [Architecture Docker](#architecture-docker)
- [Commandes Make](#commandes-make)
- [Variables d'environnement](#variables-denvironnement)
- [Accès aux services](#accès-aux-services)
- [Structure du projet](#structure-du-projet)
- [Dépannage](#dépannage)

---

## Stack

| Couche       | Technologie                                  |
| ------------ | -------------------------------------------- |
| Backend      | Laravel 13 (PHP 8.4)                         |
| Front        | Vue 3 + Inertia.js 3                         |
| UI           | Bootstrap 5 + Sass (Tailwind 4 dispo)        |
| Build        | Vite 8 + `laravel-vite-plugin`               |
| Base         | PostgreSQL 16                                |
| Runtime      | PHP-FPM 8.4 + Nginx + Supervisor (Alpine)    |
| Orchestration| Docker Compose                               |

---

## Prérequis

- Docker Desktop (ou Docker Engine + Compose v2)
- Make (déjà présent sur macOS / Linux)
- Git

> Aucun PHP, Composer ou Node n'est nécessaire en local : tout tourne
> dans les conteneurs.

---

## Démarrage rapide

### 1. Cloner le projet

```bash
git clone https://github.com/your-org/corekit.git
cd corekit
```

### 2. Première installation (setup complet)

```bash
make install
```

Cette cible enchaîne automatiquement :

1. Création du réseau Docker externe
2. Build des images
3. Création du projet Laravel 13 (si absent)
4. Configuration du `.env` pour PostgreSQL
5. Démarrage des conteneurs
6. Génération de l'`APP_KEY`
7. Installation d'Inertia, Vue 3, Bootstrap, Sass
8. Build des assets et exécution des migrations

### 3. Réinitialisation après un `git clone`

Si quelqu'un d'autre a déjà installé Laravel et tu clones simplement le
repo :

```bash
make init
```

### 4. Lancer le mode développement

```bash
make up        # démarre les services
make npm-dev   # redémarre Vite + suit les logs (HMR sur :5173)
```

---

## Architecture Docker

Trois services orchestrés par `docker-compose.yml` :

| Service    | Conteneur     | Image                       | Rôle                             |
| ---------- | ------------- | --------------------------- | -------------------------------- |
| `app`      | `corekit-app` | `corekit-laravel:dev` (build local) | PHP-FPM + Nginx + Supervisor (Laravel) |
| `postgres` | `corekit-db`  | `postgres:16`               | Base de données                  |
| `node`     | `corekit-node`| `node:20`                   | Vite dev server (HMR)            |

L'image applicative est construite en **multi-stage** :

1. `composer-deps` : install Composer optimisée, sans dev
2. `frontend-deps` : build Vite des assets dans `public/build`
3. Image finale : PHP 8.4 Alpine + Nginx + Supervisor avec extensions
   `pdo_pgsql`, `gd`, `intl`, `bcmath`, `opcache`, `mbstring`

---

## Commandes Make

`make help` affiche la liste complète. Les plus utilisées :

### Cycle de vie

| Commande       | Description                              |
| -------------- | ---------------------------------------- |
| `make install` | Setup initial complet (Laravel + deps)   |
| `make init`    | Ré-init après clone (sans recréer)       |
| `make up`      | Démarrer les conteneurs                  |
| `make down`    | Arrêter les conteneurs                   |
| `make restart` | Redémarrer                               |
| `make build`   | Reconstruire les images (`--no-cache`)   |
| `make logs`    | Suivre tous les logs                     |
| `make clean`   | Stopper + supprimer volumes (⚠ perte DB) |

### Shell & debug

| Commande         | Description                       |
| ---------------- | --------------------------------- |
| `make shell`     | Shell dans le conteneur app       |
| `make shell-db`  | `psql` dans PostgreSQL            |
| `make tinker`    | Laravel Tinker                    |
| `make logs-app`  | Logs Nginx + PHP-FPM              |
| `make logs-db`   | Logs PostgreSQL                   |

### Base de données

| Commande         | Description                          |
| ---------------- | ------------------------------------ |
| `make migrate`   | Lancer les migrations                |
| `make fresh`     | `migrate:fresh --seed`               |
| `make seed`      | Seeders uniquement                   |
| `make rollback`  | Annuler la dernière migration        |

### Frontend

| Commande           | Description                    |
| ------------------ | ------------------------------ |
| `make npm-dev`     | Redémarrer Vite + suivre logs  |
| `make npm-build`   | Build de production            |
| `make npm-install` | `npm install`                  |

### Génériques

```bash
make artisan cmd="route:list"
make composer cmd="require spatie/laravel-permission"
make test
make cache         # optimize:clear
```

---

## Variables d'environnement

Le `.env` est généré automatiquement par `make install` à partir de
`laravel/.env.example`. Les valeurs DB sont injectées par
`make configure-env` :

```env
DB_CONNECTION=pgsql
DB_HOST=postgres
DB_PORT=5432
DB_DATABASE=corekit
DB_USERNAME=corekit
DB_PASSWORD=corekit
```

Variables exposées au front via Vite :

```env
VITE_APP_NAME="${APP_NAME}"
```

Ports configurables dans `docker-compose.yml` (ou via `.env` racine) :

| Variable    | Défaut | Service          |
| ----------- | ------ | ---------------- |
| `APP_PORT`  | `8040` | App (Nginx)      |
| `DB_PORT`   | `5435` | PostgreSQL (host)|

---

## Accès aux services

| Service     | URL                       |
| ----------- | ------------------------- |
| Application | http://localhost:8040     |
| Vite HMR    | http://localhost:5173     |
| PostgreSQL  | `localhost:5435`          |

---

## Structure du projet

```
.
├── docker-compose.yml          # Orchestration (app, postgres, node)
├── Makefile                    # Commandes raccourcies
└── laravel/                    # Application Laravel
    ├── Dockerfile              # Multi-stage : composer → vite → php-fpm
    ├── docker/                 # nginx, php, supervisord, entrypoint
    ├── app/                    # Code applicatif
    ├── resources/              # Views, JS Vue, Sass
    ├── routes/                 # web.php, console.php
    └── database/               # migrations, seeders, factories
```

---

## Dépannage

**Le réseau Docker n'existe pas**

```bash
make network
```

**Les permissions de `storage/` ou `bootstrap/cache/` sont cassées**

```bash
make shell
chown -R www-data:www-data storage bootstrap/cache
chmod -R 775 storage bootstrap/cache
```

**Le HMR Vite ne se déclenche pas**

`CHOKIDAR_USEPOLLING=true` est déjà activé sur le service `node`. Si le
problème persiste, redémarrer le service :

```bash
make npm-dev
```

**Repartir de zéro (perte de données)**

```bash
make clean && make install
```

---

## Auteur

**Hariony** — [rakotobe.hariony@gmail.com](mailto:rakotobe.hariony@gmail.com)

Sous licence MIT.
