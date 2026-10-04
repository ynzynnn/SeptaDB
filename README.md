# 🗄️ SeptaDB — Database Manager Panel

[![License: MIT](https://img.shields.io/badge/License-MIT-blue.svg)](LICENSE)
[![PHP](https://img.shields.io/badge/PHP-8.3-777BB4?logo=php&logoColor=white)](https://php.net)
[![Laravel](https://img.shields.io/badge/Laravel-12-FF2D20?logo=laravel&logoColor=white)](https://laravel.com)
[![React](https://img.shields.io/badge/React-19-61DAFB?logo=react&logoColor=black)](https://react.dev)
[![MySQL](https://img.shields.io/badge/MySQL-8.4-4479A1?logo=mysql&logoColor=white)](https://mysql.com)

Panel manajemen database online murni bergaya **Pterodactyl** (clean / utilitarian monochrome theme) dengan backend **PHP (Laravel)**, frontend **React (Vite)**, database **MySQL / MariaDB**, proteksi anti SQL injection berlapis, skrip auto-installer VPS, dan REST API integrasi otomatis untuk **Paymenter**.

---

## ✨ Fitur Utama

- **Model Manajemen Murni (Pterodactyl Model)**: Panel fokus untuk login pengguna (Admin & Client), manajemen instance database, alokasi pengguna, kredensial, dan pemantauan sistem. Billing ditangani oleh software eksternal seperti Paymenter.
- **Proteksi Anti SQL Injection Berlapis**:
  - Eloquent ORM + PDO parameter binding otomatis (`?` placeholder).
  - Validasi regex ketat pada nama database & username (`/^[a-zA-Z0-9_]+$/`).
  - Password di-escape aman dengan driver PDO.
  - Mass assignment guarding (`$fillable`).
- **Provisioning Database Nyata**:
  - Otomatis mengeksekusi `CREATE DATABASE`, `CREATE USER`, dan `GRANT ALL PRIVILEGES`.
  - Fitur **Suspend / Unsuspend** (kunci user MySQL dengan `ACCOUNT LOCK/UNLOCK`).
  - Fitur **Terminate** (drop database dan user fisik).
  - Reset password database langsung dari panel client / admin.
- **Integrasi Otomatis Paymenter**:
  - REST API eksternal aman dengan SHA-256 API Key hash (`/api/external/provision`, `/api/external/suspend`, `/api/external/unsuspend`, `/api/external/terminate`, `/api/external/status/{id}`).
  - Modul Server Extension Paymenter siap pakai di `paymenter-extension/DatabaseManager/`.
- **Desain Putih Hitam (Monochrome Minimalist)**:
  - Antislop compliant: cepat, kontras tinggi, elegan, tanpa floating glow / blur / animasi berlebihan.
- **1-Click Auto Installer VPS Linux**:
  - Skrip `install.sh` untuk instalasi otomatis dalam 3-5 menit di Ubuntu/Debian (Nginx, MariaDB, PHP 8.3, Composer, Node.js 20, SSL Let's Encrypt, UFW Firewall).

---

## ⚡ Instalasi Otomatis di VPS (Linux)

Jalankan perintah ini di VPS Anda (Ubuntu 22.04/24.04 atau Debian 11/12):

```bash
git clone https://github.com/ynzynnn/SeptaDB.git /var/www/nexusdb
cd /var/www/nexusdb
sudo bash install.sh
```

Ikuti instruksi interaktif di layar (masukkan Domain/IP, Email, dan Password Admin). Installer akan menyelesaikan seluruh setup server secara otomatis.

Untuk memperbarui panel di masa mendatang:
```bash
sudo bash update.sh
```

---

## 💻 Instalasi Lokal (Windows)

Pastikan telah menginstal Laragon / PHP 8.2+, Composer, Node.js, dan MySQL.

```powershell
git clone https://github.com/ynzynnn/SeptaDB.git
cd SeptaDB
.\install.bat
```

Atau jalankan manual:
```bash
# Terminal 1 - Backend
cd server
cp .env.example .env
composer install
php artisan key:generate
php artisan migrate --seed
php artisan serve

# Terminal 2 - Frontend
cd client
cp .env.example .env
npm install
npm run dev
```

Akun bawaan default (dapat diganti):
- **Admin**: `admin` / `admin123`
- **Client**: `johndoe` / `user123`

---

## 🔌 Integrasi Paymenter

1. Buat API Key baru di panel admin (**Paymenter API Keys**).
2. Salin folder `paymenter-extension/DatabaseManager` ke direktori extension Paymenter Anda di:
   `app/Extensions/Servers/DatabaseManager/`
3. Di panel admin Paymenter, tambahkan server baru dengan driver `DatabaseManager`, masukkan Panel URL (misal `https://panel.domainanda.com`), dan masukkan API Key.
4. Setiap pembelian paket database di Paymenter akan otomatis di-provisioning ke panel SeptaDB.

---

## 📄 Lisensi
Didistribusikan di bawah Lisensi MIT. Lihat `LICENSE` untuk informasi lebih lanjut.
