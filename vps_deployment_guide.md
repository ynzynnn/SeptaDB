# 🌐 Panduan Deployment NexusDB ke VPS Linux (Ubuntu / Debian)

Dokumentasi lengkap untuk memindahkan panel dari localhost ke **VPS (Virtual Private Server)** dengan domain atau IP Publik.

---

## ⚡ METODE 1: Auto Installer Otomatis (Sangat Direkomendasikan)

Telah disediakan skrip installer otomatis interaktif ala **Pterodactyl** (`install.sh`) yang akan mengonfigurasi seluruh server Anda dari nol (install PHP 8.3, Nginx, MariaDB, Node.js, Composer, Firewall, Database Panel, SSL Let's Encrypt, dan akun Admin) secara otomatis.

### Cara Menjalankan di VPS:
1. Upload atau clone folder proyek ini ke VPS Anda.
2. Masuk ke direktori proyek dan jalankan installer dengan hak akses `root`:
   ```bash
   sudo bash install.sh
   ```
3. Ikuti panduan interaktif di layar:
   - Masukkan **Domain** (misal `panel.domainanda.com`) atau tekan Enter untuk memakai IP VPS.
   - Masukkan **Username & Email Administrator**.
   - Masukkan **Password Administrator**.
   - Pilih **Y** jika ingin memasang SSL HTTPS otomatis via Let's Encrypt.
4. Skrip akan menyelesaikan instalasi dalam 3-5 menit dan menampilkan URL serta detail login di akhir.

### Cara Memperbarui Panel di VPS (Update):
Kapan pun ada pembaruan kode, jalankan 1 perintah ini di VPS:
```bash
sudo bash update.sh
```

---

## 🛠️ METODE 2: Instalasi Manual Langkah Demi Langkah

Rekomendasi OS: **Ubuntu 22.04 LTS atau 24.04 LTS**.

Jalankan perintah berikut di VPS:
```bash
sudo apt update && sudo apt upgrade -y

# 1. Install Web Server & Database
sudo apt install -y nginx mariadb-server curl git unzip ufw

# 2. Install PHP 8.3 dan ekstensi yang dibutuhkan Laravel
sudo apt install -y software-properties-common
sudo add-apt-repository ppa:ondrej/php -y
sudo apt update
sudo apt install -y php8.3-fpm php8.3-mysql php8.3-mbstring php8.3-xml php8.3-bcmath php8.3-curl php8.3-zip php8.3-intl

# 3. Install Composer
curl -sS https://getcomposer.org/installer | sudo php -- --install-dir=/usr/local/bin --filename=composer

# 4. Install Node.js (v20 LTS) & npm
curl -fsSL https://deb.nodesource.com/setup_20.x | sudo -E bash -
sudo apt install -y nodejs
```

---

## 🗄️ 2. Konfigurasi MySQL / MariaDB di VPS

### A. Aktifkan Akses Remote (Agar database klien bisa dihubungkan dari luar)
Secara default, MySQL di VPS hanya menerima koneksi lokal (`127.0.0.1`). Klien Anda yang membeli database butuh akses dari website/aplikasi luar ke port `3306`.

1. Buka file konfigurasi MySQL:
   ```bash
   sudo nano /etc/mysql/mariadb.conf.d/50-server.cnf
   # atau untuk MySQL: sudo nano /etc/mysql/mysql.conf.d/mysqld.cnf
   ```
2. Cari baris `bind-address` dan ubah menjadi:
   ```ini
   bind-address = 0.0.0.0
   ```
3. Restart MySQL:
   ```bash
   sudo systemctl restart mariadb # atau sudo systemctl restart mysql
   ```
4. Buka port firewall 3306, 80 (HTTP), dan 443 (HTTPS):
   ```bash
   sudo ufw allow 3306/tcp
   sudo ufw allow 80/tcp
   sudo ufw allow 443/tcp
   sudo ufw allow 22/tcp
   sudo ufw enable
   ```

### B. Buat Database Internal Panel
Login ke root MySQL di VPS:
```bash
sudo mysql -u root
```
Eksekusi query berikut:
```sql
CREATE DATABASE nexusdb_panel CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'nexusdb_user'@'localhost' IDENTIFIED BY 'PasswordKuatPanel2026!';
GRANT ALL PRIVILEGES ON *.* TO 'nexusdb_user'@'localhost' WITH GRANT OPTION;
FLUSH PRIVILEGES;
EXIT;
```
*(Catatan: `GRANT ALL PRIVILEGES ON *.*` diperlukan agar panel memiliki hak akses membuat database dan user baru secara fisik untuk klien).*

---

## 🚀 3. Setup Backend Laravel

1. Upload atau clone folder proyek ke VPS, misalnya di `/var/www/nexusdb`:
   ```bash
   sudo mkdir -p /var/www/nexusdb
   sudo chown -R $USER:$USER /var/www/nexusdb
   ```
2. Masuk ke folder backend:
   ```bash
   cd /var/www/nexusdb/server
   composer install --no-dev --optimize-autoloader
   ```
3. Buat file `.env` dari `.env.example`:
   ```bash
   cp .env.example .env
   nano .env
   ```
4. Sesuaikan variabel berikut di dalam `.env`:
   ```ini
   APP_NAME="NexusDB Panel"
   APP_ENV=production
   APP_DEBUG=false
   APP_URL=https://panel.domainanda.com

   # Koneksi Database Panel
   DB_CONNECTION=mysql
   DB_HOST=127.0.0.1
   DB_PORT=3306
   DB_DATABASE=nexusdb_panel
   DB_USERNAME=nexusdb_user
   DB_PASSWORD=PasswordKuatPanel2026!

   # Host yang dibagikan ke Klien (IP Publik VPS Anda)
   DB_PUBLIC_HOST=103.xxx.xxx.xxx
   PHPMYADMIN_PUBLIC_URL=https://pma.domainanda.com

   SANCTUM_STATEFUL_DOMAINS=panel.domainanda.com
   FRONTEND_URL=https://panel.domainanda.com
   ```
5. Generate APP_KEY dan jalankan migrasi database:
   ```bash
   php artisan key:generate
   php artisan migrate --seed --force
   ```
6. Atur hak akses folder storage:
   ```bash
   sudo chown -R www-data:www-data /var/www/nexusdb/server/storage /var/www/nexusdb/server/bootstrap/cache
   sudo chmod -R 775 /var/www/nexusdb/server/storage /var/www/nexusdb/server/bootstrap/cache
   ```

---

## ⚛️ 4. Setup Frontend React (Vite)

1. Masuk ke folder client:
   ```bash
   cd /var/www/nexusdb/client
   ```
2. Buat file `.env`:
   ```bash
   nano .env
   ```
   Isi dengan URL API backend Anda:
   ```ini
   VITE_API_URL=https://panel.domainanda.com/api
   ```
3. Install dependensi dan build aplikasi produksi:
   ```bash
   npm install
   npm run build
   ```
   Hasil build akan berada di `/var/www/nexusdb/client/dist`.

---

## 🌐 5. Konfigurasi Nginx (Virtual Host)

Buat file konfigurasi Nginx untuk domain Anda:
```bash
sudo nano /etc/nginx/sites-available/nexusdb.conf
```

Tempelkan konfigurasi berikut:
```nginx
server {
    listen 80;
    server_name panel.domainanda.com;

    # 1. Frontend React (Single Page Application)
    root /var/www/nexusdb/client/dist;
    index index.html;

    location / {
        try_files $uri $uri/ /index.html;
    }

    # 2. Backend Laravel API
    location /api {
        alias /var/www/nexusdb/server/public;
        try_files $uri $uri/ @laravel;

        location ~ \.php$ {
            include snippets/fastcgi-php.conf;
            fastcgi_param SCRIPT_FILENAME /var/www/nexusdb/server/public/index.php;
            fastcgi_pass unix:/run/php/php8.3-fpm.sock;
        }
    }

    location @laravel {
        rewrite /api/(.*)$ /api/index.php?/$1 last;
    }

    # Keamanan file tersembunyi
    location ~ /\.ht {
        deny all;
    }
}
```

Aktifkan konfigurasi dan restart Nginx:
```bash
sudo ln -s /etc/nginx/sites-available/nexusdb.conf /etc/nginx/sites-enabled/
sudo nginx -t
sudo systemctl reload nginx
```

---

## 🔒 6. Pasang SSL Gratis (HTTPS) dengan Certbot

```bash
sudo apt install -y certbot python3-certbot-nginx
sudo certbot --nginx -d panel.domainanda.com
```
Certbot akan otomatis mengonfigurasi sertifikat SSL HTTPS dan pembaharuan otomatis (*auto-renewal*).

---

## ⚡ 7. Checklist Setelah Deploy

- [ ] Tes akses browser ke `https://panel.domainanda.com`.
- [ ] Login dengan akun default (`admin` / `admin123`).
- [ ] Buat akun administrator baru dan hapus / ganti password akun demo bawaan.
- [ ] Buat instance database baru di panel dan coba koneksikan dari aplikasi luar (misalnya via DBeaver, Navicat, atau terminal `mysql -h 103.xxx.xxx.xxx -u username -p`).
- [ ] Salin API Key dari menu **Paymenter API Keys** di panel admin dan tempelkan ke modul server extension di Paymenter Anda.
