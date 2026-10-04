#!/usr/bin/env bash

# ==============================================================================
#  NexusDB - Automated Production VPS Installer
#  Architecture: Ubuntu 22.04/24.04 & Debian 11/12
#  Components: Nginx, MariaDB, PHP 8.3-FPM, Node.js 20, Composer, Certbot SSL
# ==============================================================================

set -e

# ANSI Color Codes
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
CYAN='\033[0;36m'
BOLD='\033[1m'
NC='\033[0m' # No Color

# Helper output functions
print_info() { echo -e "${CYAN}[INFO]${NC} $1"; }
print_success() { echo -e "${GREEN}[SUCCESS]${NC} $1"; }
print_warning() { echo -e "${YELLOW}[WARNING]${NC} $1"; }
print_error() { echo -e "${RED}[ERROR]${NC} $1"; }

clear
echo -e "${BOLD}"
echo "===================================================================="
echo "    _   __                     ____  ____                           "
echo "   / | / /__  _  ____  _______/ __ \/ __ )                          "
echo "  /  |/ / _ \| |/_/ / / / ___/ / / / __  |                          "
echo " / /|  /  __/>  </ /_/ (__  ) /_/ / /_/ /                           "
echo "/_/ |_/\___/_/|_|\__,_/____/_____/_____/   Pterodactyl Model Panel  "
echo "                                                                    "
echo "  Official Automated VPS Installer (Linux / Ubuntu / Debian)        "
echo "===================================================================="
echo -e "${NC}"

# 1. Root Check
if [ "$EUID" -ne 0 ]; then
    print_error "Installer must be run as root. Please run with sudo: sudo bash install.sh"
    exit 1
fi

# Detect Server Public IP
SERVER_IP=$(curl -s -4 ifconfig.me || curl -s -4 icanhazip.com || hostname -I | awk '{print $1}')

print_info "Detected Server Public IP: ${BOLD}${SERVER_IP}${NC}"
echo ""

# 2. Interactive Configuration
echo -e "${BOLD}--- [1/4] Panel Configuration ---${NC}"
read -p "Enter Panel Domain or IP [Default: ${SERVER_IP}]: " INPUT_DOMAIN
DOMAIN="${INPUT_DOMAIN:-$SERVER_IP}"

read -p "Enter Panel Brand Name [Default: NexusDB]: " INPUT_BRAND
PANEL_BRAND="${INPUT_BRAND:-NexusDB}"

read -p "Enter Support Email [Default: support@${DOMAIN}]: " INPUT_EMAIL
SUPPORT_EMAIL="${INPUT_EMAIL:-support@$DOMAIN}"

echo ""
echo -e "${BOLD}--- [2/4] Admin Account Setup ---${NC}"
read -p "Enter Administrator Username [Default: admin]: " INPUT_ADMIN_USER
ADMIN_USER="${INPUT_ADMIN_USER:-admin}"

read -p "Enter Administrator Email [Default: admin@${DOMAIN}]: " INPUT_ADMIN_EMAIL
ADMIN_EMAIL="${INPUT_ADMIN_EMAIL:-admin@$DOMAIN}"

while true; do
    read -s -p "Enter Administrator Password (min 8 chars): " ADMIN_PASS
    echo ""
    if [ ${#ADMIN_PASS} -ge 8 ]; then
        break
    else
        print_warning "Password must be at least 8 characters long. Please try again."
    fi
done

echo ""
echo -e "${BOLD}--- [3/4] Database Engine Configuration ---${NC}"
# Generate random secure passwords for MySQL
DB_PANEL_PASS=$(tr -dc A-Za-z0-9_#@! 2>/dev/null < /dev/urandom | head -c 20 || openssl rand -base64 16)
read -p "Enter Panel MySQL Password [Press Enter to auto-generate]: " INPUT_DB_PASS
DB_PASSWORD="${INPUT_DB_PASS:-$DB_PANEL_PASS}"

read -p "Enter Public IP/Hostname for Client Databases [Default: ${SERVER_IP}]: " INPUT_DB_HOST
DB_PUBLIC_HOST="${INPUT_DB_HOST:-$SERVER_IP}"

echo ""
echo -e "${BOLD}--- [4/4] SSL (HTTPS) Configuration ---${NC}"
ENABLE_SSL="n"
if [[ "$DOMAIN" != "$SERVER_IP" && "$DOMAIN" =~ \. ]]; then
    read -p "Domain detected ($DOMAIN). Configure free Let's Encrypt SSL HTTPS? (Y/n): " INPUT_SSL
    ENABLE_SSL="${INPUT_SSL:-y}"
fi

echo ""
echo "===================================================================="
echo "                   INSTALLATION SUMMARY                             "
echo "===================================================================="
echo " Panel URL        : http$( [[ "$ENABLE_SSL" =~ ^[Yy]$ ]] && echo "s" )://${DOMAIN}"
echo " Admin Username   : ${ADMIN_USER}"
echo " Admin Email      : ${ADMIN_EMAIL}"
echo " Client DB Host   : ${DB_PUBLIC_HOST}:3306"
echo " SSL HTTPS        : $( [[ "$ENABLE_SSL" =~ ^[Yy]$ ]] && echo "Enabled (Let's Encrypt)" || echo "Disabled (HTTP)" )"
echo " Install Location : /var/www/nexusdb"
echo "===================================================================="
read -p "Proceed with installation? (Y/n): " CONFIRM
if [[ "$CONFIRM" =~ ^[Nn]$ ]]; then
    print_warning "Installation aborted by user."
    exit 0
fi

echo ""
print_info "Starting automated setup. This may take 3-5 minutes..."

# 3. Update System & Install Dependencies
print_info "Updating system packages..."
export DEBIAN_FRONTEND=noninteractive
apt update -y && apt upgrade -y

print_info "Installing core tools & libraries..."
apt install -y software-properties-common curl wget git unzip zip ufw certbot python3-certbot-nginx

# Add PHP 8.3 PPA
print_info "Setting up PHP 8.3 repository..."
if grep -q "Ubuntu" /etc/os-release; then
    add-apt-repository -y ppa:ondrej/php
    apt update -y
elif grep -q "Debian" /etc/os-release; then
    apt install -y lsb-release ca-certificates apt-transport-https
    curl -sSLo /etc/apt/trusted.gpg.d/php.gpg https://packages.sury.org/php/apt.gpg
    echo "deb https://packages.sury.org/php/ $(lsb_release -sc) main" > /etc/apt/sources.list.d/php.list
    apt update -y
fi

# Fix potential cloud template systemd mount namespacing issues (e.g., missing /var/www/vhosts)
print_info "Ensuring system directories & systemd namespace compatibility..."
mkdir -p /var/www/vhosts /var/www/html /var/www/nexusdb
if [ -d "/etc/systemd/system/php8.3-fpm.service.d" ]; then
    rm -rf /etc/systemd/system/php8.3-fpm.service.d
    systemctl daemon-reload || true
fi

# Fix broken or orphaned third-party pool configs in /etc/php/8.3/fpm/pool.d
if [ -d "/etc/php/8.3/fpm/pool.d" ]; then
    mkdir -p /var/backups/php8.3-fpm-pools
    for pool in /etc/php/8.3/fpm/pool.d/*.conf; do
        if [ -f "$pool" ] && [ "$(basename "$pool")" != "www.conf" ]; then
            mv "$pool" /var/backups/php8.3-fpm-pools/
        fi
    done
fi

# Install PHP 8.3 & Modules
print_info "Installing PHP 8.3-FPM and required extensions..."
dpkg --configure -a || true
apt --fix-broken install -y || true
apt install -y php8.3 php8.3-fpm php8.3-mysql php8.3-mbstring php8.3-xml \
    php8.3-bcmath php8.3-curl php8.3-zip php8.3-intl php8.3-cli php8.3-sqlite3

# Install Web Server & Database
print_info "Installing Nginx & MariaDB Server..."
apt install -y nginx mariadb-server

# Install Composer
if ! command -v composer &> /dev/null; then
    print_info "Installing Composer..."
    curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer
fi

# Install Node.js 20 LTS
if ! command -v node &> /dev/null || [ "$(node -v | cut -d'.' -f1 | tr -d 'v')" -lt 20 ]; then
    print_info "Installing Node.js 20 LTS..."
    curl -fsSL https://deb.nodesource.com/setup_20.x | bash -
    apt install -y nodejs
fi

# 4. Configure MariaDB & Remote Access
print_info "Configuring MariaDB for local and client remote connections..."
systemctl start mariadb
systemctl enable mariadb

# Enable bind-address = 0.0.0.0 in MariaDB configs
CNF_FILES=(
    "/etc/mysql/mariadb.conf.d/50-server.cnf"
    "/etc/mysql/my.cnf"
    "/etc/mysql/mysql.conf.d/mysqld.cnf"
)

for cnf in "${CNF_FILES[@]}"; do
    if [ -f "$cnf" ]; then
        sed -i 's/^bind-address\s*=\s*.*/bind-address = 0.0.0.0/' "$cnf"
        sed -i 's/^#bind-address\s*=\s*.*/bind-address = 0.0.0.0/' "$cnf"
    fi
done

systemctl restart mariadb

# Create Database and Privileged Provisioning User
print_info "Initializing panel database and root provisioning credentials..."
mysql -e "CREATE DATABASE IF NOT EXISTS nexusdb_panel CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
mysql -e "CREATE USER IF NOT EXISTS 'nexusdb_user'@'localhost' IDENTIFIED BY '${DB_PASSWORD}';"
mysql -e "CREATE USER IF NOT EXISTS 'nexusdb_user'@'127.0.0.1' IDENTIFIED BY '${DB_PASSWORD}';"
mysql -e "GRANT ALL PRIVILEGES ON *.* TO 'nexusdb_user'@'localhost' WITH GRANT OPTION;"
mysql -e "GRANT ALL PRIVILEGES ON *.* TO 'nexusdb_user'@'127.0.0.1' WITH GRANT OPTION;"
mysql -e "FLUSH PRIVILEGES;"

# 5. Deploy Project Files
INSTALL_DIR="/var/www/nexusdb"
mkdir -p "$INSTALL_DIR"

SCRIPT_DIR="$( cd "$( dirname "${BASH_SOURCE[0]}" )" &> /dev/null && pwd )"
if [ "$SCRIPT_DIR" != "$INSTALL_DIR" ]; then
    print_info "Copying project files to ${INSTALL_DIR}..."
    cp -r "$SCRIPT_DIR"/* "$INSTALL_DIR"/
fi

cd "$INSTALL_DIR"

# 6. Configure Backend
print_info "Configuring Laravel Backend..."
cd "$INSTALL_DIR/server"

cat <<EOF > .env
APP_NAME="${PANEL_BRAND}"
APP_ENV=production
APP_KEY=
APP_DEBUG=false
APP_URL=http$( [[ "$ENABLE_SSL" =~ ^[Yy]$ ]] && echo "s" )://${DOMAIN}

APP_LOCALE=en
APP_FALLBACK_LOCALE=en
APP_FAKER_LOCALE=en_US

LOG_CHANNEL=stack
LOG_STACK=single
LOG_LEVEL=error

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=nexusdb_panel
DB_USERNAME=nexusdb_user
DB_PASSWORD=${DB_PASSWORD}

DB_PUBLIC_HOST=${DB_PUBLIC_HOST}
PHPMYADMIN_PUBLIC_URL=http$( [[ "$ENABLE_SSL" =~ ^[Yy]$ ]] && echo "s" )://${DOMAIN}/phpmyadmin

SESSION_DRIVER=database
SESSION_LIFETIME=120
SESSION_ENCRYPT=false
SESSION_PATH=/
SESSION_DOMAIN=null

SANCTUM_STATEFUL_DOMAINS=${DOMAIN}
FRONTEND_URL=http$( [[ "$ENABLE_SSL" =~ ^[Yy]$ ]] && echo "s" )://${DOMAIN}

MAIL_MAILER=log
MAIL_FROM_ADDRESS="${SUPPORT_EMAIL}"
MAIL_FROM_NAME="${PANEL_BRAND}"
EOF

print_info "Installing PHP dependencies via Composer..."
composer install --no-dev --optimize-autoloader --no-interaction

print_info "Generating Laravel Application Key..."
php artisan key:generate --force

print_info "Running database migrations..."
php artisan migrate --force

print_info "Seeding base database engines..."
php artisan db:seed --force

print_info "Creating Administrator Account (${ADMIN_USER})..."
php artisan panel:admin "${ADMIN_EMAIL}" "${ADMIN_USER}" "${ADMIN_PASS}" "Administrator"

# Permissions
print_info "Setting folder permissions..."
chown -R www-data:www-data "$INSTALL_DIR/server/storage" "$INSTALL_DIR/server/bootstrap/cache"
chmod -R 775 "$INSTALL_DIR/server/storage" "$INSTALL_DIR/server/bootstrap/cache"

# 7. Configure and Build Frontend
print_info "Configuring and building React Frontend..."
cd "$INSTALL_DIR/client"

cat <<EOF > .env
VITE_API_URL=http$( [[ "$ENABLE_SSL" =~ ^[Yy]$ ]] && echo "s" )://${DOMAIN}/api
EOF

npm install --silent
npm run build

# 8. Configure Nginx
print_info "Configuring Nginx Virtual Host..."
NGINX_CONF="/etc/nginx/sites-available/nexusdb.conf"

cat <<EOF > "$NGINX_CONF"
server {
    listen 80;
    server_name ${DOMAIN};

    # Frontend Static Distribution
    root ${INSTALL_DIR}/client/dist;
    index index.html;

    client_max_body_size 64M;

    # Frontend SPA Route handling
    location / {
        try_files \$uri \$uri/ /index.html;
    }

    # Backend API Routing
    location /api {
        alias ${INSTALL_DIR}/server/public;
        try_files \$uri \$uri/ @laravel;

        location ~ \.php\$ {
            include snippets/fastcgi-php.conf;
            fastcgi_param SCRIPT_FILENAME ${INSTALL_DIR}/server/public/index.php;
            fastcgi_pass unix:/run/php/php8.3-fpm.sock;
        }
    }

    location @laravel {
        rewrite /api/(.*)\$ /api/index.php?/\$1 last;
    }

    # Hide sensitive files
    location ~ /\.(?!well-known).* {
        deny all;
    }
}
EOF

# Enable Nginx Site
ln -sf "$NGINX_CONF" /etc/nginx/sites-enabled/nexusdb.conf
rm -f /etc/nginx/sites-enabled/default

nginx -t
systemctl reload nginx

# 9. Configure SSL if requested
if [[ "$ENABLE_SSL" =~ ^[Yy]$ ]]; then
    print_info "Obtaining Let's Encrypt SSL Certificate for ${DOMAIN}..."
    certbot --nginx -d "${DOMAIN}" --non-interactive --agree-tos -m "${SUPPORT_EMAIL}" --redirect || {
        print_warning "Certbot was unable to obtain SSL automatically. Please verify DNS A-Record points to this VPS and run: certbot --nginx -d ${DOMAIN}"
    }
fi

# 10. Firewall Configuration
print_info "Configuring UFW Firewall..."
ufw allow 22/tcp >/dev/null 2>&1
ufw allow 80/tcp >/dev/null 2>&1
ufw allow 443/tcp >/dev/null 2>&1
ufw allow 3306/tcp >/dev/null 2>&1
ufw --force enable >/dev/null 2>&1

# 11. Create Systemd Queue Worker & Optimization
print_info "Configuring Background Worker Service..."
cat <<EOF > /etc/systemd/system/nexusdb-worker.service
[Unit]
Description=NexusDB Background Worker
After=network.target

[Service]
User=www-data
Group=www-data
Restart=always
ExecStart=/usr/bin/php ${INSTALL_DIR}/server/artisan queue:work --sleep=3 --tries=3 --max-time=3600

[Install]
WantedBy=multi-user.target
EOF

systemctl daemon-reload
systemctl enable --now nexusdb-worker.service >/dev/null 2>&1 || true

# Optimize Laravel
cd "$INSTALL_DIR/server"
php artisan config:cache >/dev/null 2>&1 || true
php artisan route:cache >/dev/null 2>&1 || true
php artisan view:cache >/dev/null 2>&1 || true

# Completion Output
clear
echo -e "${GREEN}${BOLD}"
echo "===================================================================="
echo "         🎉 NEXUSDB PANEL SUCCESSFULLY INSTALLED!                   "
echo "===================================================================="
echo -e "${NC}"
echo -e " Panel URL        : ${BOLD}http$( [[ "$ENABLE_SSL" =~ ^[Yy]$ ]] && echo "s" )://${DOMAIN}${NC}"
echo -e " Admin Username   : ${BOLD}${ADMIN_USER}${NC}"
echo -e " Admin Email      : ${BOLD}${ADMIN_EMAIL}${NC}"
echo -e " Admin Password   : ${BOLD}${ADMIN_PASS}${NC}"
echo -e " Client DB Host   : ${BOLD}${DB_PUBLIC_HOST}:3306${NC}"
echo ""
echo -e "${CYAN}${BOLD}Paymenter Integration Steps:${NC}"
echo " 1. Log in to your panel at http$( [[ "$ENABLE_SSL" =~ ^[Yy]$ ]] && echo "s" )://${DOMAIN}"
echo " 2. Navigate to 'Paymenter API Keys' in the sidebar."
echo " 3. Click '+ Create New API Key' and copy your plaintext token."
echo " 4. In Paymenter, install the 'DatabaseManager' server extension from:"
echo "    ${INSTALL_DIR}/paymenter-extension/DatabaseManager"
echo " 5. Enter Panel URL: http$( [[ "$ENABLE_SSL" =~ ^[Yy]$ ]] && echo "s" )://${DOMAIN} and paste the API Key."
echo ""
echo -e "${YELLOW}Need to update in the future? Simply run:${NC}"
echo " sudo bash ${INSTALL_DIR}/update.sh"
echo "===================================================================="
