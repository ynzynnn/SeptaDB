# 📚 SeptaDB API Documentation & Integration Guide

Dokumentasi lengkap REST API **SeptaDB Panel** untuk integrasi dengan sistem eksternal seperti **Paymenter**, **WHMCS**, bot Discord, atau skrip otomasi kustom.

---

## 🔐 Autentikasi API

Semua request ke endpoint eksternal (`/api/external/*`) wajib menyertakan header HTTP:

| Header | Nilai / Contoh | Keterangan |
| :--- | :--- | :--- |
| `Content-Type` | `application/json` | Format request |
| `Accept` | `application/json` | Format response |
| `X-API-KEY` | `sk_live_a1b2c3d4e5f6...` | Token API yang dibuat di menu **Admin > API Keys** |

> Alternatif: Anda juga dapat menggunakan header `Authorization: Bearer <API_KEY>`

---

## 🌐 Base URL

* **Production (HTTPS)**: `https://database.septacloud.net/api`
* **Local Development**: `http://localhost:8000/api`

---

## 📌 Daftar Endpoint Eksternal (Paymenter / Billing)

### 1. Health Check
Mengecek status ketersediaan API SeptaDB.

- **Method**: `GET`
- **Endpoint**: `/health`
- **Auth**: Tidak diperlukan

#### Contoh Response (200 OK):
```json
{
  "status": "online",
  "panel": "SeptaDB Management API",
  "timestamp": "2026-10-04T09:30:00+00:00"
}
```

---

### 2. Provision Database (Create & Deploy)
Membuat database baru untuk user secara otomatis saat pesanan/invoice dibayar di Paymenter. Jika email user belum terdaftar, akun user akan otomatis dibuatkan.

- **Method**: `POST`
- **Endpoint**: `/external/provision`
- **Auth**: `X-API-KEY`

#### Request Body (JSON):
| Field | Tipe | Wajib? | Keterangan |
| :--- | :--- | :--- | :--- |
| `email` | `string` | **Ya** | Email pelanggan (misal: `client@customer.com`) |
| `name` | `string` | Tidak | Nama pelanggan |
| `external_id` | `string` | **Ya** | ID Layanan / ID Pesanan unik dari Paymenter/WHMCS |
| `product_type` | `string` | Tidak | Tipe database: `mysql`, `postgresql`, `mariadb`, `mongodb`, `redis` (default: produk pertama yang aktif) |
| `product_id` | `integer`| Tidak | ID paket database spesifik jika ada |
| `database_name`| `string`| Tidak | Request nama database kustom (opsional) |
| `expires_at` | `string` | Tidak | Tanggal expired format `YYYY-MM-DD HH:mm:ss` |
| `notes` | `string` | Tidak | Catatan tambahan |

#### Contoh cURL:
```bash
curl -X POST "https://database.septacloud.net/api/external/provision" \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -H "X-API-KEY: sk_live_your_secret_token" \
  -d '{
    "email": "customer@gmail.com",
    "name": "Budi Santoso",
    "external_id": "PAY-SERVICE-10293",
    "product_type": "mysql",
    "database_name": "ecommerce_prod"
  }'
```

#### Contoh Response (201 Created):
```json
{
  "success": true,
  "message": "Database successfully provisioned for Paymenter service.",
  "data": {
    "id": 14,
    "uuid": "8f3b2c1a-5d6e-4f7a-8b9c-1234567890ab",
    "external_id": "PAY-SERVICE-10293",
    "user_id": 8,
    "user_email": "customer@gmail.com",
    "host": "103.xxx.xxx.xxx",
    "port": 3306,
    "type": "mysql",
    "database": "db_customer_ecommerce_prod",
    "username": "u_customer_ecommerce_prod",
    "password": "StrongGeneratedPassword123!",
    "connection_string": "mysql://u_customer_ecommerce_prod:StrongGeneratedPassword123!@103.xxx.xxx.xxx:3306/db_customer_ecommerce_prod",
    "phpmyadmin_url": "https://database.septacloud.net/phpmyadmin",
    "status": "active",
    "created_at": "2026-10-04T09:30:00.000000Z"
  }
}
```

---

### 3. Suspend Database (Bekukan Layanan)
Membekukan hak akses database saat invoice jatuh tempo (overdue). Hak akses login user MySQL akan dicabut sementara tanpa menghapus data.

- **Method**: `POST`
- **Endpoint**: `/external/suspend`
- **Auth**: `X-API-KEY`

#### Request Body (JSON):
| Field | Tipe | Wajib? | Keterangan |
| :--- | :--- | :--- | :--- |
| `identifier` | `string` | **Ya** | `external_id` (Paymenter Service ID), `uuid`, atau numeric `id` |

#### Contoh cURL:
```bash
curl -X POST "https://database.septacloud.net/api/external/suspend" \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -H "X-API-KEY: sk_live_your_secret_token" \
  -d '{
    "identifier": "PAY-SERVICE-10293"
  }'
```

#### Contoh Response (200 OK):
```json
{
  "success": true,
  "message": "Database db_customer_ecommerce_prod has been suspended.",
  "status": "suspended"
}
```

---

### 4. Unsuspend Database (Aktifkan Kembali)
Mengaktifkan kembali hak akses database setelah invoice dibayar lunas.

- **Method**: `POST`
- **Endpoint**: `/external/unsuspend`
- **Auth**: `X-API-KEY`

#### Request Body (JSON):
| Field | Tipe | Wajib? | Keterangan |
| :--- | :--- | :--- | :--- |
| `identifier` | `string` | **Ya** | `external_id` (Paymenter Service ID), `uuid`, atau numeric `id` |

#### Contoh cURL:
```bash
curl -X POST "https://database.septacloud.net/api/external/unsuspend" \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -H "X-API-KEY: sk_live_your_secret_token" \
  -d '{
    "identifier": "PAY-SERVICE-10293"
  }'
```

#### Contoh Response (200 OK):
```json
{
  "success": true,
  "message": "Database db_customer_ecommerce_prod has been unsuspended.",
  "status": "active"
}
```

---

### 5. Terminate Database (Hapus Permanen)
Menghapus user dan database secara permanen dari server MySQL/PostgreSQL ketika layanan dibatalkan (cancelled) atau kadaluarsa.

- **Method**: `POST`
- **Endpoint**: `/external/terminate`
- **Auth**: `X-API-KEY`

#### Request Body (JSON):
| Field | Tipe | Wajib? | Keterangan |
| :--- | :--- | :--- | :--- |
| `identifier` | `string` | **Ya** | `external_id` (Paymenter Service ID), `uuid`, atau numeric `id` |

#### Contoh cURL:
```bash
curl -X POST "https://database.septacloud.net/api/external/terminate" \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -H "X-API-KEY: sk_live_your_secret_token" \
  -d '{
    "identifier": "PAY-SERVICE-10293"
  }'
```

#### Contoh Response (200 OK):
```json
{
  "success": true,
  "message": "Database db_customer_ecommerce_prod has been terminated.",
  "status": "terminated"
}
```

---

### 6. Get Database Status & Connection Info
Mengambil rincian kredensial dan status database berdasarkan ID layanan eksternal atau UUID.

- **Method**: `GET`
- **Endpoint**: `/external/status/{identifier}`
- **Auth**: `X-API-KEY`

#### Contoh cURL:
```bash
curl -X GET "https://database.septacloud.net/api/external/status/PAY-SERVICE-10293" \
  -H "Accept: application/json" \
  -H "X-API-KEY: sk_live_your_secret_token"
```

#### Contoh Response (200 OK):
```json
{
  "success": true,
  "data": {
    "id": 14,
    "uuid": "8f3b2c1a-5d6e-4f7a-8b9c-1234567890ab",
    "status": "active",
    "host": "103.xxx.xxx.xxx",
    "port": 3306,
    "database": "db_customer_ecommerce_prod",
    "username": "u_customer_ecommerce_prod",
    "password": "StrongGeneratedPassword123!",
    "connection_string": "mysql://u_customer_ecommerce_prod:StrongGeneratedPassword123!@103.xxx.xxx.xxx:3306/db_customer_ecommerce_prod",
    "phpmyadmin_url": "https://database.septacloud.net/phpmyadmin"
  }
}
```

---

## 🔒 HTTP Status Codes

| Code | Arti | Keterangan |
| :--- | :--- | :--- |
| `200` | OK | Permintaan berhasil diproses |
| `201` | Created | Database baru berhasil dibuat & dialokasikan |
| `400` | Bad Request | Parameter input tidak valid |
| `401` | Unauthorized | API Key tidak dikirim atau format salah |
| `403` | Forbidden | API Key dinonaktifkan oleh Administrator |
| `404` | Not Found | Database/Layanan tidak ditemukan |
| `422` | Unprocessable Entity | Validasi data gagal (misal email tidak valid) |
| `500` | Server Error | Terjadi kesalahan pada daemon database server |

---

## ⚙️ Integrasi dengan Paymenter Extension

Untuk mengintegrasikan dengan Paymenter:
1. Buat folder ekstensi di Paymenter:
   `app/Extensions/Servers/SeptaDB/`
2. Konfigurasi kredensial pada server Paymenter:
   - **Host/URL**: `https://database.septacloud.net/api`
   - **API Key**: `sk_live_...` (Dihasilkan dari menu Admin SeptaDB)
3. Ekstensi Paymenter cukup memanggil endpoint di atas sesuai event lifecycle layanan:
   - Event `Create` -> `POST /external/provision`
   - Event `Suspend` -> `POST /external/suspend`
   - Event `Unsuspend` -> `POST /external/unsuspend`
   - Event `Terminate` -> `POST /external/terminate`
