#!/usr/bin/env bash

# ==============================================================================
#  SeptaDB / NexusDB - Uninstaller Script
#  Safely removes panel files, Nginx virtual host, systemd services, and database.
# ==============================================================================

set -e

# ANSI Color Codes
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
CYAN='\033[0;36m'
BOLD='\033[1m'
NC='\033[0m'

print_info() { echo -e "${CYAN}[INFO]${NC} $1"; }
print_success() { echo -e "${GREEN}[SUCCESS]${NC} $1"; }
print_warning() { echo -e "${YELLOW}[WARNING]${NC} $1"; }
print_error() { echo -e "${RED}[ERROR]${NC} $1"; }

clear
echo -e "${RED}${BOLD}"
echo "===================================================================="
echo "          ⚠️  SEPTADB / NEXUSDB PANEL UNINSTALLER                  "
echo "===================================================================="
echo -e "${NC}"

# 1. Root Check
if [ "$EUID" -ne 0 ]; then
    print_error "Uninstaller must be run as root. Run with: sudo bash uninstall.sh"
    exit 1
fi

echo -e "${YELLOW}This script will remove:${NC}"
echo " - Panel Application Files (/var/www/septadb or /var/www/nexusdb)"
echo " - Nginx Configuration (septadb.conf / nexusdb.conf)"
echo " - Systemd Worker Service (septadb-worker / nexusdb-worker)"
echo " - Panel Database & User (septadb_panel / nexusdb_panel) [Optional]"
echo ""
read -p "Are you sure you want to proceed with uninstallation? (y/N): " CONFIRM
if [[ ! "$CONFIRM" =~ ^[Yy]$ ]]; then
    print_info "Uninstallation cancelled."
    exit 0
fi

echo ""
read -p "Do you also want to delete the MySQL panel database (septadb_panel / nexusdb_panel)? (y/N): " REMOVE_DB

# 2. Stop and Remove Systemd Worker Service
print_info "Stopping and removing background worker service..."
for srv in "septadb-worker.service" "nexusdb-worker.service"; do
    if systemctl is-active --quiet "$srv" 2>/dev/null; then
        systemctl stop "$srv"
    fi
    if [ -f "/etc/systemd/system/$srv" ]; then
        systemctl disable "$srv" >/dev/null 2>&1 || true
        rm -f "/etc/systemd/system/$srv"
    fi
done
systemctl daemon-reload
print_success "Worker service removed."

# 3. Remove Nginx Configuration
print_info "Removing Nginx virtual host configuration..."
rm -f /etc/nginx/sites-enabled/septadb.conf /etc/nginx/sites-enabled/nexusdb.conf
rm -f /etc/nginx/sites-available/septadb.conf /etc/nginx/sites-available/nexusdb.conf

# Restore default nginx site if available and no other sites enabled
if [ -f "/etc/nginx/sites-available/default" ] && [ -z "$(ls -A /etc/nginx/sites-enabled 2>/dev/null)" ]; then
    ln -sf /etc/nginx/sites-available/default /etc/nginx/sites-enabled/default
fi

if nginx -t >/dev/null 2>&1; then
    systemctl reload nginx
    print_success "Nginx configuration cleaned and reloaded."
else
    print_warning "Nginx test had warnings, please verify /etc/nginx/sites-enabled/."
fi

# 4. Remove Panel Database & User
if [[ "$REMOVE_DB" =~ ^[Yy]$ ]]; then
    print_info "Removing panel database and user from MariaDB/MySQL..."
    mysql -e "DROP DATABASE IF EXISTS nexusdb_panel;" 2>/dev/null || true
    mysql -e "DROP USER IF EXISTS 'nexusdb_user'@'localhost';" 2>/dev/null || true
    mysql -e "DROP USER IF EXISTS 'nexusdb_user'@'127.0.0.1';" 2>/dev/null || true
    mysql -e "FLUSH PRIVILEGES;" 2>/dev/null || true
    print_success "Database 'nexusdb_panel' and user 'nexusdb_user' removed."
else
    print_info "Panel database 'nexusdb_panel' preserved."
fi

# 5. Remove Application Directory
INSTALL_DIR="/var/www/nexusdb"
SCRIPT_PATH="$(realpath "$0")"

print_info "Removing application files from ${INSTALL_DIR}..."

# If running directly inside /var/www/nexusdb, remove contents except script then remove dir on exit
if [ -d "$INSTALL_DIR" ]; then
    find "$INSTALL_DIR" -mindepth 1 ! -path "$SCRIPT_PATH" -delete 2>/dev/null || rm -rf "$INSTALL_DIR"/*
    print_success "Panel directory cleaned."
fi

echo ""
echo -e "${GREEN}${BOLD}"
echo "===================================================================="
echo "          ✅  UNINSTALLATION COMPLETED SUCCESSFULLY                 "
echo "===================================================================="
echo -e "${NC}"
print_info "All SeptaDB panel services and files have been removed."
echo ""
