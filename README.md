# SIDAInfo Update Server

Central update distribution server for the **SIDAInfo** health facility information system in Burundi. Clinic sites running SIDAInfo automatically check this server for new releases, download them, and report their installed version back.

Built with **Laravel 12**, **MySQL 8**, **Nginx**, and **PHP 8.2-FPM** — all running inside Docker. No PHP, Composer, or npm required on the host machine.

---

## Table of Contents

- [Architecture](#architecture)
- [Features](#features)
- [Prerequisites](#prerequisites)
- [Development Setup](#development-setup)
- [Production Deployment](#production-deployment)
- [Environment Variables](#environment-variables)
- [Admin UI](#admin-ui)
- [API Reference](#api-reference)
- [Backup & Restore](#backup--restore)
- [Running Tests](#running-tests)

---

## Architecture

```
┌────────────────────────────────────────────────────┐
│                  Docker network                    │
│                                                    │
│  ┌──────────┐   ┌──────────────┐   ┌───────────┐  │
│  │  Nginx   │──▶│  PHP-FPM     │──▶│  MySQL 8  │  │
│  │  :80     │   │  (app)       │   │  (db)     │  │
│  └──────────┘   └──────────────┘   └───────────┘  │
│       ▲                                            │
└───────┼────────────────────────────────────────────┘
        │
   SIDAInfo site instances (poll for updates)
```

| Container | Image | Role |
|-----------|-------|------|
| `webserver` | `nginx:alpine` | Reverse proxy + static files |
| `app` | `php:8.2-fpm` (custom) | Laravel application |
| `db` | `mysql:8.0` | Database |

**Persistent data** lives in named Docker volumes — not on the host filesystem:

| Volume | Contains |
|--------|----------|
| `dbdata` | MySQL database files |
| `releases_storage` | Uploaded release ZIP archives |

---

## Features

- **Release management** — Upload new SIDAInfo release ZIPs (up to 2 GB), activate a release as the current update target, view release history.
- **Site tracking** — 497 Burundian health facility sites pre-loaded from CSV; track each site's current version, last check-in, and install status.
- **Resumable downloads** — HTTP `Range` header support for interrupted ZIP downloads over slow connections.
- **Install reporting** — Bearer-token-authenticated API endpoint for site instances to report successful installs.
- **Event timeline** — Append-only log of every manifest check and install event per site.
- **Admin dashboard** — Stats overview, version adoption breakdown, recent activity.

---

## Prerequisites

- [Docker Desktop](https://www.docker.com/products/docker-desktop/) (or Docker Engine + Docker Compose v2)
- Git

That's it. No PHP, Composer, or npm needed on the host.

---

## Development Setup

### 1. Clone and configure

```bash
git clone https://github.com/BURUNDI-HIV-INFO-SYSTEM/sidainfo-updater.git
cd sidainfo-updater
cp .env.example .env
```

The default `.env` works out of the box for local development.

### 2. Build and start

```bash
docker compose up -d --build
```

This will:
- Build the PHP-FPM image with all required extensions
- Start MySQL and wait for it to be healthy
- Run all database migrations automatically (via the container entrypoint)
- Start Nginx on **http://localhost:8000**

### 3. Seed the database

```bash
docker compose exec app php artisan db:seed --force
```

Creates the admin user and imports all 497 sites from the bundled CSV.

### 4. Open the admin panel

Visit **http://localhost:8000** and log in with the default credentials:

| Field | Value |
|-------|-------|
| Email | `admin@sidainfo.local` |
| Password | `changeme` |

> Change these values via `ADMIN_EMAIL` and `ADMIN_PASSWORD` in your `.env` before seeding.

### Common dev commands

```bash
# View application logs
docker compose logs -f app

# Run all tests
docker compose exec app php artisan test

# Open a shell inside the app container
docker compose exec app bash

# Re-run migrations
docker compose exec app php artisan migrate

# Stop all containers (data is preserved in volumes)
docker compose down

# Stop and wipe all data (destructive)
docker compose down -v
```

---

## Production Deployment

Production uses `docker-compose.prod.yml`. The application code is **baked into the Docker image** — no source bind-mounts needed on the server.

### First-time setup

```bash
# 1. Clone the repository
git clone https://github.com/BURUNDI-HIV-INFO-SYSTEM/sidainfo-updater.git
cd sidainfo-updater

# 2. Create the production environment file
cp .env.production .env
nano .env   # fill in all required values (see Environment Variables below)

# 3. Generate a Laravel app key
docker compose -f docker-compose.prod.yml run --rm app php artisan key:generate --show
# Copy the output and set it as APP_KEY= in your .env

# 4. Build images and start all services
docker compose -f docker-compose.prod.yml up -d --build

# 5. Seed the database (first deploy only)
docker compose -f docker-compose.prod.yml exec app php artisan db:seed --force
```

### Subsequent deployments

```bash
git pull origin main
docker compose -f docker-compose.prod.yml up -d --build
```

Database migrations run automatically on container startup — no manual step required.

### Production notes

- **Port** — set `APP_PORT=` in `.env` to control which host port Nginx binds to (default `80`).
- **Database** — MySQL is not exposed outside the Docker network. Access it via:
  ```bash
  docker compose -f docker-compose.prod.yml exec db mysql -u${DB_USERNAME} -p
  ```
- **Logs** — go to `stderr` and are visible via:
  ```bash
  docker compose -f docker-compose.prod.yml logs -f app
  ```
- **Sessions and cache** — stored in MySQL; no Redis required.

---

## Environment Variables

| Variable | Required | Description |
|----------|----------|-------------|
| `APP_KEY` | Yes | Laravel encryption key — generate with `php artisan key:generate --show` |
| `APP_URL` | Yes | Public URL of the server, e.g. `http://192.168.1.10` |
| `APP_PORT` | No | Host port for Nginx (default: `80`) |
| `APP_DEBUG` | — | Must be `false` in production |
| `DB_PASSWORD` | Yes | MySQL password for the application user |
| `DB_ROOT_PASSWORD` | Yes | MySQL root password (used by the healthcheck) |
| `ADMIN_EMAIL` | Yes | Admin account email, created on first `db:seed` |
| `ADMIN_PASSWORD` | Yes | Admin account password, created on first `db:seed` |
| `LARAUPDATER_STATUS_REPORT_TOKEN` | Recommended | Bearer token site instances must include when calling `/api/site-install-status`. Generate: `openssl rand -hex 32`. Leave empty to disable verification. |

See `.env.production` for the full annotated template.

---

## Admin UI

### Dashboard `/`
- Total sites, sites checked in within the last 7 days, sites running the latest version
- Version adoption breakdown
- Recent event activity feed

### Releases `/releases`
- Upload a new release ZIP (SHA-256 checksum computed automatically on upload)
- Activate a release to make it the current update target (deactivates the previous one)
- View adoption count per release (number of sites that have installed it)
- Delete inactive releases

### Sites `/sites`
- Full list of all 497 health facility sites
- Filter by province, district, status (`up-to-date`, `pending`, `unknown`), or version
- Full-text search by site name or ID
- Per-site detail page: complete event timeline of every manifest check and install report

---

## API Reference

These endpoints are called automatically by SIDAInfo site instances — not by humans.

### `GET /laraupdater.json`

Returns the active release manifest. Called by site instances on startup to check for available updates.

**Query parameters**

| Parameter | Description |
|-----------|-------------|
| `siteid` | 8-digit site ID (e.g. `01010103`) |
| `current_version` | Version string currently installed at the site |

**Response `200 OK`**
```json
{
  "version": "2.1.0",
  "archive_name": "RELEASE-2.1.0.zip",
  "download_url": "http://your-server/RELEASE-2.1.0.zip",
  "sha256": "a3f2c8d9...",
  "size_bytes": 157286400,
  "minimum_required_version": null,
  "notes": "Bug fixes and performance improvements"
}
```

Returns `204 No Content` when no active release exists.

---

### `GET /RELEASE-{version}.zip`

Downloads the release ZIP archive. Supports HTTP `Range` headers for resumable downloads over unreliable connections.

**Example with range:**
```
GET /RELEASE-2.1.0.zip HTTP/1.1
Range: bytes=10485760-
```

---

### `POST /api/site-install-status`

Called by a site instance after successfully installing an update.

**Headers**
```
Authorization: Bearer <LARAUPDATER_STATUS_REPORT_TOKEN>
Content-Type: application/json
```

**Body**
```json
{
  "siteid": "01010103",
  "installed_version": "2.1.0",
  "status": "success"
}
```

**Responses**

| Code | Meaning |
|------|---------|
| `200` | Event recorded, site record updated |
| `401` | Missing or invalid bearer token |
| `404` | Unknown `siteid` |
| `422` | Validation error |

---

## Backup & Restore

> Run backup commands regularly. Both the database and release files must be backed up.

### Database

**Backup**
```bash
docker compose -f docker-compose.prod.yml exec db \
  mysqldump -uroot -p${DB_ROOT_PASSWORD} sidainfo_updater > backup-$(date +%Y%m%d).sql
```

**Restore**
```bash
cat backup-20260101.sql | \
  docker compose -f docker-compose.prod.yml exec -T db \
  mysql -uroot -p${DB_ROOT_PASSWORD} sidainfo_updater
```

### Release ZIPs

**Backup**
```bash
docker run --rm \
  -v sidainfo-updater_releases_storage:/data \
  -v $(pwd):/backup \
  alpine tar czf /backup/releases-$(date +%Y%m%d).tar.gz -C /data .
```

**Restore**
```bash
docker run --rm \
  -v sidainfo-updater_releases_storage:/data \
  -v $(pwd):/backup \
  alpine tar xzf /backup/releases-20260101.tar.gz -C /data
```

---

## Running Tests

```bash
docker compose exec app php artisan test
```

**34 tests, 71 assertions** across four suites:

| Suite | Coverage |
|-------|----------|
| `Admin/AuthTest` | Login, logout, auth guards, redirect behaviour |
| `Admin/ReleaseTest` | Upload, activate, delete, adoption count |
| `Api/ManifestTest` | Manifest content, event logging, version tracking |
| `Api/SiteInstallStatusTest` | Token auth, validation, DB updates |

Tests use an in-memory SQLite database and do not affect the running MySQL instance.
