#!/usr/bin/env bash

# ==============================================================================
#  SeptaDB - Updater Script
#  Pulls latest code, updates dependencies, runs migrations, and rebuilds assets.
# ==============================================================================

set -e

# ANSI Color Codes
GREEN='\033[0;32m'
CYAN='\033[0;36m'
RED='\033[0;31m'
BOLD='\033[1m'
NC='\033[0m'

print_info() { echo -e "${CYAN}[INFO]${NC} $1"; }
print_success() { echo -e "${GREEN}[SUCCESS]${NC} $1"; }
print_error() { echo -e "${RED}[ERROR]${NC} $1"; }

if [ "$EUID" -ne 0 ]; then
    print_error "Updater must be run as root: sudo bash update.sh"
    exit 1
fi

INSTALL_DIR="/var/www/septadb"
if [ ! -d "$INSTALL_DIR" ] && [ -d "/var/www/nexusdb" ]; then
    INSTALL_DIR="/var/www/nexusdb"
elif [ ! -d "$INSTALL_DIR" ]; then
    INSTALL_DIR="$( cd "$( dirname "${BASH_SOURCE[0]}" )" &> /dev/null && pwd )"
fi

cd "$INSTALL_DIR"

print_info "Checking for Git updates in $INSTALL_DIR..."
if [ -d ".git" ]; then
    git pull origin main || git pull || true
fi

print_info "Updating backend PHP dependencies..."
cd "$INSTALL_DIR/server"
composer install --no-dev --optimize-autoloader --no-interaction

print_info "Running database migrations..."
php artisan migrate --force

print_info "Rebuilding frontend assets..."
cd "$INSTALL_DIR/client"
npm install --silent
npm run build

print_info "Fixing permissions..."
chown -R www-data:www-data "$INSTALL_DIR/server/storage" "$INSTALL_DIR/server/bootstrap/cache"
chmod -R 775 "$INSTALL_DIR/server/storage" "$INSTALL_DIR/server/bootstrap/cache"

print_info "Clearing and caching Laravel configurations..."
cd "$INSTALL_DIR/server"
php artisan config:cache
php artisan route:cache
php artisan view:cache

print_info "Restarting services..."
systemctl restart php8.3-fpm
systemctl reload nginx
systemctl restart septadb-worker.service >/dev/null 2>&1 || true
systemctl restart nexusdb-worker.service >/dev/null 2>&1 || true

echo ""
print_success "SeptaDB Panel has been successfully updated to the latest version!"
