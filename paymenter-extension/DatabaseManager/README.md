# 🔌 Paymenter Server Extension — Database Manager

Extension ini menghubungkan billing **Paymenter** ke **Database Manager Panel** sehingga setiap kali customer membeli paket database dan invoice terbayar, database akan langsung dibuat secara otomatis (auto-provision).

---

## 📂 Cara Pasang di Paymenter

1. Buka folder instalasi Paymenter kamu di VPS:
   ```bash
   cd /var/www/paymenter/app/Extensions/Servers/
   ```

2. Buat folder `DatabaseManager` dan letakkan file `DatabaseManager.php` di dalamnya:
   ```
   app/Extensions/Servers/DatabaseManager/DatabaseManager.php
   ```

3. Buka **Paymenter Admin Panel** → menu **Extensions** → tab **Servers**.
4. Cari **DatabaseManager**, klik **Enable / Configure**.
5. Isi konfigurasi:
   - **Database Panel URL**: Contoh `http://ip-vps-kamu:8000` atau `https://dbpanel.domainmu.com`
   - **API Key Token**: Masukkan API Key yang sudah digenerate dari menu **Admin → API Keys** di Database Manager Panel.
6. Simpan konfigurasi.

---

## 🛒 Menambahkan Produk di Paymenter

1. Masuk ke **Paymenter Admin** → **Products** → **Create Product**.
2. Pada tab **Server**, pilih server extension: **DatabaseManager**.
3. Pilih tipe database:
   - `MySQL 8.4`
   - `MariaDB 11`
   - `PostgreSQL 16`
   - `MongoDB 7`
   - `Redis Cache`
4. Simpan produk.

---

## ⚡ Lifecycle Hooks yang Didukung

- **`createServer`**: Otomatis dipanggil saat invoice dibayar. Database dibuat, user diberi hak akses, credentials disimpan.
- **`suspendServer`**: Dipanggil saat tagihan jatuh tempo / terlambat bayar. User database di-lock.
- **`unsuspendServer`**: Dipanggil saat tagihan dibayar. User database di-unlock kembali.
- **`terminateServer`**: Dipanggil saat layanan dibatalkan / expired. Database dan user di-drop.
