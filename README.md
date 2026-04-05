# SIDAInfo Update Server

Central update distribution server for the **SIDAInfo** health facility information system in Burundi. Clinic sites running SIDAInfo automatically check this server for new releases, download them, and report their installed version back.

Built with **Laravel 12**, **MySQL 8**, and **PHP 8.2-FPM** in Docker. In production, the VPS nginx on the main host serves `public/` and forwards PHP requests to the app container.

---

## Table of Contents

- [Architecture](#architecture)
- [Features](#features)
- [Prerequisites](#prerequisites)
- [Development Setup](#development-setup)
- [Server Deployment](#server-deployment)
  - [First-time setup](#first-time-setup)
  - [Staging vs production](#staging-vs-production)
  - [Subsequent deployments](#subsequent-deployments)
  - [Manual nginx setup](#manual-nginx-setup)
- [Upgrading a Live Server](#upgrading-a-live-server)
  - [How data is preserved](#how-data-is-preserved)
  - [Standard upgrade procedure](#standard-upgrade-procedure)
  - [After specific releases](#after-specific-releases)
  - [Rollback procedure](#rollback-procedure)
- [Environment Variables](#environment-variables)
- [Admin UI](#admin-ui)
- [API Reference](#api-reference)
- [Backup & Restore](#backup--restore)
- [Running Tests](#running-tests)

---

## Architecture

```
┌──────────────────────────────────────────────────────────┐
│                       VPS host                           │
│                                                          │
│  ┌──────────────┐      fastcgi      ┌─────────────────┐ │
│  │ Host nginx   │ ─────────────────▶│ PHP-FPM app     │ │
│  │ serves public│                   │ container :9000 │ │
│  └──────────────┘                   └────────┬────────┘ │
│         ▲                                     │          │
└─────────┼─────────────────────────────────────┼──────────┘
          │                                     │
   SIDAInfo site instances                Docker network
                                                │
                                           ┌────▼────┐
                                           │ MySQL 8 │
                                           │   db    │
                                           └─────────┘
```

| Container | Image | Role |
|-----------|-------|------|
| `app` | `php:8.2-fpm` (custom) | Laravel application |
| `db` | `mysql:8.0` | Database |

**Persistent data** lives in named Docker volumes — not on the host filesystem:

| Volume | Contains |
|--------|----------|
| `dbdata` | MySQL database files |
| `releases_storage` | Uploaded release ZIP archives |

Both staging and production run as separate Docker Compose projects (`sidainfo-staging`, `sidainfo-production`) on the same VPS, each with their own isolated volumes, network, and PHP-FPM port.

---

## Features

- **Release management** — Upload new SIDAInfo release ZIPs (up to 1 GB), activate a release as the current update target, view release history.
- **Site tracking** — 497 Burundian health facility sites pre-loaded from CSV; track each site's current version, last check-in, and install status.
- **Resumable downloads** — HTTP `Range` header support for interrupted ZIP downloads over slow connections.
- **Install reporting** — Bearer-token-authenticated API endpoint for site instances to report successful installs.
- **Event timeline** — Append-only log of every manifest check and install event per site.
- **Admin dashboard** — Stats overview, version adoption breakdown, recent activity.

---

## Prerequisites

**Local development:**
- [Docker Desktop](https://www.docker.com/products/docker-desktop/) (or Docker Engine + Docker Compose v2)
- Git

**VPS / server:**
- Docker Engine + Docker Compose v2
- nginx (already installed on the VPS)
- Git

No PHP, Composer, or npm needed on any machine.

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

## Server Deployment

All server deployments are handled by a single script: **`deploy.sh`**

```
./deploy.sh [staging|production] [--first-run]
```

The script:
1. Validates Docker and git are available
2. Checks the `.env` file is filled in (copies the template and exits if not)
3. Pulls the latest code from git
4. Builds Docker images and restarts containers
5. Waits for PHP-FPM to be ready (migrations run automatically inside the container)
6. Seeds the database on `--first-run`
7. Writes / updates the nginx site config and reloads nginx

### First-time setup

```bash
# 1. Clone the repository
git clone https://github.com/BURUNDI-HIV-INFO-SYSTEM/sidainfo-updater.git
cd sidainfo-updater

# 2. Make the deploy script executable
chmod +x deploy.sh

# 3. Run for the target environment — it will copy the .env template and exit
./deploy.sh production --first-run
# OR
./deploy.sh staging --first-run

# 4. Fill in the generated .env file
#    Production → .env
#    Staging    → .env.staging.local
nano .env   # (or .env.staging.local for staging)

# 5. Generate and paste in an app key
docker compose --project-name sidainfo-production \
  -f docker-compose.prod.yml run --rm app php artisan key:generate --show
# Copy the output → APP_KEY=... in your .env

# 6. Run deploy again — this time it will go all the way through
./deploy.sh production --first-run
```

### Staging vs production

Both environments run independently on the same VPS:

| | Staging | Production |
|---|---------|------------|
| env file | `.env.staging.local` | `.env` |
| template | `.env.staging` | `.env.production` |
| Docker project | `sidainfo-staging` | `sidainfo-production` |
| PHP-FPM port | `9001` | `9000` |
| nginx site | `sidainfo-staging` | `sidainfo-production` |
| Docker volumes | `sidainfo-staging_dbdata` etc. | `sidainfo-production_dbdata` etc. |

> Data is fully isolated — a staging deploy never touches the production database or release files.

### Subsequent deployments

```bash
# Deploy latest code to production
./deploy.sh production

# Deploy latest code to staging
./deploy.sh staging
```

Database migrations run automatically inside the container on startup — no separate step needed.

### Manual nginx setup

If you prefer to manage nginx yourself, use the template at `nginx/app.conf.template`. Replace the three placeholders and copy it to `/etc/nginx/sites-available/`:

| Placeholder | Replace with |
|-------------|--------------|
| `__SERVER_NAME__` | Your subdomain, e.g. `updater.example.org` |
| `__APP_ROOT__` | Absolute path to the project's `public/` directory |
| `__PHP_FPM_PORT__` | `9000` for production, `9001` for staging |

```bash
sed \
  -e 's|__SERVER_NAME__|updater.example.org|g' \
  -e 's|__APP_ROOT__|/srv/sidainfo-updater/public|g' \
  -e 's|__PHP_FPM_PORT__|9000|g' \
  nginx/app.conf.template \
  | sudo tee /etc/nginx/sites-available/sidainfo-production

sudo ln -s /etc/nginx/sites-available/sidainfo-production /etc/nginx/sites-enabled/
sudo nginx -t && sudo systemctl reload nginx
```

### Production notes

- **PHP-FPM port** — set `PHP_FPM_PORT=` in `.env` to override the default. The compose file binds it to `127.0.0.1` only (not reachable from outside the VPS).
- **Upload size** — the application defaults to a 1 GB release upload limit. The nginx template and `docker/php/php.ini` are already configured to match.
- **Database** — MySQL is not exposed outside the Docker network. Access it via:
  ```bash
  docker compose --project-name sidainfo-production -f docker-compose.prod.yml exec db mysql -u${DB_USERNAME} -p
  ```
- **Logs** — go to `stderr` and are visible via:
  ```bash
  docker compose --project-name sidainfo-production -f docker-compose.prod.yml logs -f app
  ```
- **Sessions and cache** — stored in MySQL; no Redis required.

---

## Upgrading a Live Server

This section explains how to apply a new version of the Update Server to an already-running production (or staging) deployment without losing any data.

### How data is preserved

All persistent data lives in **named Docker volumes**, not inside the image or in the checkout directory. This is the key safety property of the Docker setup:

| Volume | What it contains | Survives image rebuild? |
|--------|-----------------|------------------------|
| `sidainfo-production_dbdata` | MySQL database (all tables, all rows) | Yes |
| `sidainfo-production_releases_storage` | Uploaded release ZIP archives | Yes |

When `docker compose up -d --build` rebuilds the image, Docker creates a **new image** with the updated source code but **re-attaches the existing volumes**. No data is touched.

The only way to destroy data is to run `docker compose down -v` — the `-v` flag explicitly deletes volumes. Never run it on a live server unless you intend to wipe everything.

### Standard upgrade procedure

This is what `./deploy.sh production` does for a routine code update. Run it from the repository directory on the VPS:

```bash
# Step 1 — (Optional but strongly recommended) back up the database first
docker compose --project-name sidainfo-production -f docker-compose.prod.yml exec db \
  mysqldump -uroot -p${DB_ROOT_PASSWORD} sidainfo_updater > backup-pre-upgrade-$(date +%Y%m%d-%H%M).sql

# Step 2 — Deploy
./deploy.sh production
```

`deploy.sh` will:
1. Pull the latest code (`git pull`)
2. Rebuild the Docker image with the new source code
3. Restart the `app` container (the `db` container is **not** restarted — data is untouched)
4. On container startup, the entrypoint automatically runs `php artisan migrate --force` — this applies any new database migrations

> **Downtime:** the `app` container restarts for roughly 5–15 seconds during the rebuild. The database is up the entire time.

### After specific releases

Some releases add new database tables or reference data that require a one-time seeder run after the migration. Check the release notes. The table below lists every release that needs extra steps:

| Release | New tables | Extra step required |
|---------|-----------|---------------------|
| Tarifs + Examens config feature | `tarifs_centraux`, `examens_config` | Run the exam seeder (see below) |

#### Seeding exam codes after the Tarifs/Examens config release

The `examens_config` table must be pre-populated with the 42 valid exam codes. The seeder is **idempotent** — running it again on an already-populated table only updates names, never deletes rows.

```bash
# After ./deploy.sh production has completed:
docker compose --project-name sidainfo-production -f docker-compose.prod.yml \
  exec app php artisan db:seed --class=ExamenConfigSeeder --force
```

Verify it worked:

```bash
docker compose --project-name sidainfo-production -f docker-compose.prod.yml \
  exec db mysql -u${DB_USERNAME} -p${DB_PASSWORD} sidainfo_updater \
  -e "SELECT COUNT(*) AS exam_count FROM examens_config;"
# Expected: 42
```

Then open `/examens-config` in the admin UI and `/tarifs` to set prices for the current year.

### Rollback procedure

If the new release causes a problem and you need to revert:

**1. Restore the pre-upgrade database backup**

```bash
# Stop the app container so nothing is writing to the DB
docker compose --project-name sidainfo-production -f docker-compose.prod.yml stop app

# Restore
cat backup-pre-upgrade-YYYYMMDD-HHMM.sql | \
  docker compose --project-name sidainfo-production -f docker-compose.prod.yml exec -T db \
  mysql -uroot -p${DB_ROOT_PASSWORD} sidainfo_updater

echo "Database restored."
```

**2. Check out the previous version of the code**

```bash
# Find the previous commit or tag
git log --oneline -10

# Roll back the working tree (replace <previous-sha> with the commit you want)
git checkout <previous-sha>
```

**3. Rebuild and restart from the old code**

```bash
docker compose --project-name sidainfo-production -f docker-compose.prod.yml \
  up -d --build
```

The entrypoint will run `php artisan migrate --force` against the restored database — this is safe because the restored DB is already at the migration state that matches the old code.

**4. Verify, then return to main when ready**

```bash
# Test the old version is working, then restore the branch pointer
git checkout main
```

> **Important:** if the failed release added new migrations that ran before you noticed the problem, the database may already have the new tables. Restoring the SQL dump from before the upgrade is the safest path — it resets the schema to the known-good state.

---

## Environment Variables

| Variable | Required | Description |
|----------|----------|-------------|
| `APP_KEY` | Yes | Laravel encryption key — generate with `php artisan key:generate --show` |
| `APP_URL` | Yes | Public URL of the server, e.g. `http://updater.example.org` — used by `deploy.sh` to configure nginx |
| `PHP_FPM_PORT` | No | Host loopback port exposed for nginx `fastcgi_pass` (default: `9000` for production, `9001` for staging) |
| `APP_ENV` | — | `production` or `staging` |
| `APP_DEBUG` | — | Must be `false` in production |
| `DB_PASSWORD` | Yes | MySQL password for the application user |
| `DB_ROOT_PASSWORD` | Yes | MySQL root password (used by the healthcheck) |
| `ADMIN_EMAIL` | Yes | Admin account email, created on first `db:seed` |
| `ADMIN_PASSWORD` | Yes | Admin account password, created on first `db:seed` |
| `LARAUPDATER_STATUS_REPORT_TOKEN` | Recommended | Bearer token site instances must include when calling `/api/site-install-status`. Generate: `openssl rand -hex 32`. Leave empty to disable verification. |

See `.env.production` and `.env.staging` for fully annotated templates.

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

### Tarifs `/tarifs`
- Set the unit price (in BIF) for each biological exam, per year
- Select any year via the year dropdown; prices for different years are stored independently
- Only exams with a price > 0 are exposed by the public `GET /api/tarifs` endpoint
- **Manual entry** — edit any number of rows in the table then click **Enregistrer**
- **Excel import** — download the pre-filled template, fill in the `prix_bif` column, upload it:
  - Template has all 42 exam codes and names pre-filled; existing prices for the selected year are included
  - Only the `prix_bif` column is imported — `code_examen`, `nom_examen`, and `annee` are read-only reference columns
  - Rows with an empty `prix_bif` are skipped; rows with unknown `code_examen` values are skipped and listed in the result message
  - Both `.xlsx` and `.xls` formats accepted

### Configuration des examens `/examens-config`
- Edit the French display name and normal / critical reference ranges for each of the 42 exam types
- Changes are exposed immediately by the public `GET /api/examens` endpoint
- Codes are fixed (they map 1:1 to columns in the `bilans` table on site instances) — names and ranges can be freely updated

---

## API Reference

These endpoints are called automatically by SIDAInfo site instances — not by humans.

### `GET /laraupdater.json`

Returns the active release manifest. Called by site instances on startup to check for available updates.

**Query parameters**

| Parameter | Description |
|-----------|-------------|
| `siteid` | 8-digit site ID (e.g. `01010103`) — if provided, a `manifest_check` event is logged for the site |
| `current_version` | Version string currently installed at the site |

**Response `200 OK`**
```json
{
  "version": "2.1.0",
  "archive": "RELEASE-2.1.0.zip",
  "description": "Bug fixes and performance improvements",
  "sha256": "a3f2c8d9...",
  "size_bytes": 157286400,
  "published_at": "2026-03-13T10:00:00+00:00",
  "minimum_supported_version": "1.5.0"
}
```

Fields `sha256`, `size_bytes`, `published_at`, and `minimum_supported_version` are only present when set on the release.

Returns `404` when no active release exists.

> **Client requirement:** the client validates that both `version` and `archive` are non-empty before proceeding with the update.

---

### `GET /RELEASE-{version}.zip`

Downloads the release ZIP archive. Handled by Laravel (not nginx directly) to support HTTP `Range` headers for resumable downloads over unreliable connections.

**Example with range:**
```
GET /RELEASE-2.1.0.zip HTTP/1.1
Range: bytes=10485760-
```

Returns `206 Partial Content` with a `Content-Range` header when a range is requested.

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

### `GET /api/tarifs`

Returns the exam price list for a given year. Called by SIDAInfo site instances during tarif synchronisation.

**Query parameters**

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `annee` | integer | yes | Pricing year (e.g. `2026`) |

**Response `200 OK`**
```json
[
  { "code_examen": "nfs_hemoglobine", "prix": 2500.00, "devise": "BIF", "annee": 2026 },
  { "code_examen": "glycemie",        "prix": 1500.00, "devise": "BIF", "annee": 2026 }
]
```

Only exams with a configured price > 0 are included. Returns an empty array `[]` if no prices are set for the requested year.

No authentication required.

---

### `GET /api/examens`

Returns the full list of exam types with their display names and normal/critical reference ranges. Called by SIDAInfo site instances during exam configuration synchronisation.

**No parameters.**

**Response `200 OK`**
```json
[
  {
    "code":            "nfs_hemoglobine",
    "nom_examen":      "Hémoglobine",
    "valeur_usuelle1": 12.0,
    "valeur_usuelle2": 17.0,
    "limite1":         8.0,
    "limite2":         20.0
  },
  {
    "code":            "glycemie",
    "nom_examen":      "Glycémie",
    "valeur_usuelle1": 3.9,
    "valeur_usuelle2": 6.1,
    "limite1":         null,
    "limite2":         null
  }
]
```

| Field | Type | Description |
|-------|------|-------------|
| `code` | string | Fixed exam code — maps to a column in `bilans` on site instances |
| `nom_examen` | string | French display name shown in lab result reports |
| `valeur_usuelle1` | number \| null | Lower bound of normal range |
| `valeur_usuelle2` | number \| null | Upper bound of normal range |
| `limite1` | number \| null | Lower critical / alerting threshold |
| `limite2` | number \| null | Upper critical / alerting threshold |

No authentication required. Returns all 42 exam types regardless of whether ranges are configured.

---

## Backup & Restore

> Run backup commands regularly. Both the database and release files must be backed up.

Set the project name (`sidainfo-production` or `sidainfo-staging`) to match your environment.

### Database

**Backup**
```bash
docker compose --project-name sidainfo-production -f docker-compose.prod.yml exec db \
  mysqldump -uroot -p${DB_ROOT_PASSWORD} sidainfo_updater > backup-$(date +%Y%m%d).sql
```

**Restore**
```bash
cat backup-20260101.sql | \
  docker compose --project-name sidainfo-production -f docker-compose.prod.yml exec -T db \
  mysql -uroot -p${DB_ROOT_PASSWORD} sidainfo_updater
```

### Release ZIPs

**Backup**
```bash
docker run --rm \
  -v sidainfo-production_releases_storage:/data \
  -v $(pwd):/backup \
  alpine tar czf /backup/releases-$(date +%Y%m%d).tar.gz -C /data .
```

**Restore**
```bash
docker run --rm \
  -v sidainfo-production_releases_storage:/data \
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
