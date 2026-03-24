#!/usr/bin/env bash
# =============================================================================
# SIDAInfo Update Server — deployment script
# =============================================================================
# Usage:
#   ./deploy.sh [staging|production] [--first-run]
#
# Options:
#   staging     Deploy to the staging environment  (PHP-FPM :9001, project: sidainfo-staging)
#   production  Deploy to the production environment (PHP-FPM :9000, project: sidainfo-production)
#   --first-run Seed the database (admin user + 497 sites). Only needed once on a fresh server.
#
# What this script does:
#   1. Validates prerequisites (docker, git)
#   2. Checks the .env file is filled in
#   3. Pulls the latest code from git (skipped with --first-run)
#   4. Builds Docker images and restarts containers
#   5. Waits for PHP-FPM to be ready
#   6. Seeds the database (only with --first-run)
#   7. Installs / updates the nginx site config and reloads nginx
# =============================================================================

set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"

# ---------------------------------------------------------------------------
# Argument parsing
# ---------------------------------------------------------------------------
ENV="${1:-production}"
case "$ENV" in
  staging|production) ;;
  --first-run) ENV="production" ;;
  *)
    echo "Usage: $0 [staging|production] [--first-run]" >&2
    exit 1
    ;;
esac

FIRST_RUN=false
for arg in "${@:2}"; do
  case "$arg" in
    --first-run) FIRST_RUN=true ;;
  esac
done
# Allow first arg to also be --first-run when no env was given
[ "${1:-}" = "--first-run" ] && FIRST_RUN=true

# ---------------------------------------------------------------------------
# Pretty output
# ---------------------------------------------------------------------------
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
CYAN='\033[0;36m'
BOLD='\033[1m'
NC='\033[0m'

log()  { echo -e "${GREEN}  ✔${NC}  $*"; }
warn() { echo -e "${YELLOW}  ⚠${NC}  $*"; }
step() { echo -e "\n${CYAN}${BOLD}── $* ──${NC}"; }
die()  { echo -e "\n${RED}  ✘  $*${NC}" >&2; exit 1; }

# ---------------------------------------------------------------------------
# Environment-specific defaults
# ---------------------------------------------------------------------------
COMPOSE_FILE="$SCRIPT_DIR/docker-compose.prod.yml"
PROJECT_NAME="sidainfo-$ENV"
NGINX_SITE_NAME="sidainfo-$ENV"

if [ "$ENV" = "staging" ]; then
  ENV_FILE="$SCRIPT_DIR/.env.staging.local"
  ENV_TEMPLATE="$SCRIPT_DIR/.env.staging"
  DEFAULT_FPM_PORT=9001
else
  ENV_FILE="$SCRIPT_DIR/.env"
  ENV_TEMPLATE="$SCRIPT_DIR/.env.production"
  DEFAULT_FPM_PORT=9000
fi

# ---------------------------------------------------------------------------
# Helper: read a value from the env file
# ---------------------------------------------------------------------------
env_val() {
  grep -E "^${1}=" "$ENV_FILE" 2>/dev/null \
    | head -1 \
    | cut -d= -f2- \
    | sed 's/[[:space:]]*#.*//' \
    | xargs 2>/dev/null \
    || true
}

# ---------------------------------------------------------------------------
# Export so docker-compose.prod.yml can resolve ${COMPOSE_ENV_FILE} and load
# the correct env_file into the container (staging vs production)
# ---------------------------------------------------------------------------
export COMPOSE_ENV_FILE="$ENV_FILE"

# ---------------------------------------------------------------------------
# Helper: run docker compose with the right project, file, and env
# ---------------------------------------------------------------------------
dc() {
  docker compose \
    --project-name "$PROJECT_NAME" \
    -f "$COMPOSE_FILE" \
    --env-file "$ENV_FILE" \
    "$@"
}

# ===========================================================================
echo ""
echo -e "${BOLD}SIDAInfo Update Server${NC} — deploying ${CYAN}[$ENV]${NC}"
[ "$FIRST_RUN" = true ] && echo -e "                         ${YELLOW}first-run mode (DB will be seeded)${NC}"
echo ""

# ===========================================================================
step "1/7  Checking prerequisites"
# ===========================================================================
command -v docker >/dev/null 2>&1 \
  || die "Docker not found. Install it: https://docs.docker.com/engine/install/"

docker compose version >/dev/null 2>&1 \
  || die "Docker Compose v2 not available. Run: apt install docker-compose-plugin"

command -v git >/dev/null 2>&1 \
  || die "Git not found."

command -v nginx >/dev/null 2>&1 \
  || warn "nginx not found on PATH — nginx setup step will be skipped."

log "Prerequisites satisfied."

# ===========================================================================
step "1b/7 Checking for conflicting containers"
# ===========================================================================
# The production stack is PHP-FPM + MySQL only. The host nginx handles HTTP.
# If someone previously ran `docker compose up` (dev stack), an nginx container
# named `sidainfo-updater-webserver` may still be running — stop it now.
DEV_NGINX="sidainfo-updater-webserver"
if docker inspect "$DEV_NGINX" >/dev/null 2>&1; then
  warn "Found dev-stack nginx container ($DEV_NGINX) — stopping and removing it."
  warn "The host nginx handles all HTTP in production; no nginx container is needed."
  docker stop "$DEV_NGINX" && docker rm "$DEV_NGINX"
  log "Removed $DEV_NGINX."
else
  log "No conflicting nginx container found."
fi

# ===========================================================================
step "2/7  Checking environment file ($ENV_FILE)"
# ===========================================================================
if [ ! -f "$ENV_FILE" ]; then
  if [ -f "$ENV_TEMPLATE" ]; then
    cp "$ENV_TEMPLATE" "$ENV_FILE"
    warn "No .env file found — copied from $(basename "$ENV_TEMPLATE")."
    warn "Edit $ENV_FILE and fill in all required values, then re-run this script."
    exit 1
  else
    die "No .env file at $ENV_FILE and no template at $ENV_TEMPLATE.\nCreate $ENV_FILE manually (see .env.production for reference)."
  fi
fi

for key in APP_KEY DB_PASSWORD DB_ROOT_PASSWORD ADMIN_PASSWORD; do
  val=$(env_val "$key")
  [ -n "$val" ] || die "$key is empty in $ENV_FILE. Fill in all required values before deploying."
done
log "Environment file is valid."

# ===========================================================================
step "3/7  Updating source code"
# ===========================================================================
if [ "$FIRST_RUN" = true ]; then
  log "First run — skipping git pull (assuming repo was just cloned)."
else
  BRANCH=$(git -C "$SCRIPT_DIR" branch --show-current)
  git -C "$SCRIPT_DIR" pull origin "$BRANCH"
  log "Code updated from origin/$BRANCH."
fi

# ===========================================================================
step "4/7  Building Docker images and (re)starting containers"
# ===========================================================================
dc up -d --build --remove-orphans
log "Containers are up."

# ===========================================================================
step "5/7  Waiting for PHP-FPM to be ready"
# ===========================================================================
READY=false
echo -n "       "
for i in $(seq 1 40); do
  if dc exec -T app pgrep php-fpm >/dev/null 2>&1; then
    READY=true
    break
  fi
  printf "."
  sleep 3
done
echo ""

if [ "$READY" = false ]; then
  warn "Application did not become ready within ~2 minutes."
  warn "Check logs with:"
  warn "  docker compose --project-name $PROJECT_NAME -f docker-compose.prod.yml logs -f app"
  exit 1
fi
log "PHP-FPM is running."

# ===========================================================================
step "6/7  Database seeding"
# ===========================================================================
if [ "$FIRST_RUN" = true ]; then
  echo "       Seeding admin user and 497 site records..."
  dc exec -T app php artisan db:seed --force
  log "Database seeded."
else
  log "Skipping seed (not --first-run). Migrations ran automatically on startup."
fi

# ===========================================================================
step "7/7  Installing nginx configuration"
# ===========================================================================
APP_URL=$(env_val "APP_URL")
SERVER_NAME=$(echo "$APP_URL" | sed -E 's|https?://||' | sed 's|[/:?].*||')
FPM_PORT=$(env_val "PHP_FPM_PORT")
FPM_PORT="${FPM_PORT:-$DEFAULT_FPM_PORT}"

NGINX_TEMPLATE="$SCRIPT_DIR/nginx/app.conf.template"

if ! command -v nginx >/dev/null 2>&1; then
  warn "nginx not found — skipping nginx setup."
  warn "Configure it manually using $NGINX_TEMPLATE"
elif [ -z "$SERVER_NAME" ]; then
  warn "APP_URL not set in $ENV_FILE — skipping nginx setup."
  warn "Set APP_URL=http://your-subdomain, then re-run this script."
else
  [ -f "$NGINX_TEMPLATE" ] || die "nginx template not found at $NGINX_TEMPLATE"

  NGINX_CONF="/etc/nginx/sites-available/$NGINX_SITE_NAME"
  NGINX_ENABLED="/etc/nginx/sites-enabled/$NGINX_SITE_NAME"

  GENERATED=$(sed \
    -e "s|__SERVER_NAME__|$SERVER_NAME|g" \
    -e "s|__APP_ROOT__|$SCRIPT_DIR/public|g" \
    -e "s|__PHP_FPM_PORT__|$FPM_PORT|g" \
    "$NGINX_TEMPLATE")

  NEEDS_WRITE=true
  if [ -f "$NGINX_CONF" ]; then
    if diff <(echo "$GENERATED") "$NGINX_CONF" >/dev/null 2>&1; then
      log "nginx config unchanged."
      NEEDS_WRITE=false
    fi
  fi

  if [ "$NEEDS_WRITE" = true ]; then
    echo "$GENERATED" | sudo tee "$NGINX_CONF" >/dev/null
    log "nginx config written to $NGINX_CONF"
  fi

  if [ ! -L "$NGINX_ENABLED" ]; then
    sudo ln -sf "$NGINX_CONF" "$NGINX_ENABLED"
    log "Site enabled (symlink created in sites-enabled)."
  fi

  sudo nginx -t 2>/dev/null \
    || die "nginx config test failed. Check $NGINX_CONF and fix before retrying."

  sudo systemctl reload nginx
  log "nginx reloaded."
fi

# ===========================================================================
echo ""
echo -e "${GREEN}${BOLD}  Deployment complete!${NC}"
echo ""
echo -e "  Environment  : ${BOLD}$ENV${NC}"
echo -e "  App URL      : ${BOLD}${APP_URL:-not set}${NC}"
echo -e "  PHP-FPM port : 127.0.0.1:${BOLD}$FPM_PORT${NC}"
echo ""
echo "  Running containers:"
dc ps --format "table {{.Name}}\t{{.Status}}" 2>/dev/null || dc ps
echo ""
echo "  Useful commands:"
echo "    Logs    : docker compose --project-name $PROJECT_NAME -f docker-compose.prod.yml logs -f app"
echo "    Shell   : docker compose --project-name $PROJECT_NAME -f docker-compose.prod.yml exec app bash"
echo "    DB CLI  : docker compose --project-name $PROJECT_NAME -f docker-compose.prod.yml exec db mysql -u\${DB_USERNAME} -p"
echo "    Restart : docker compose --project-name $PROJECT_NAME -f docker-compose.prod.yml restart app"
echo ""
