# Dokumentasi Backend & API — KST Cangar Backoffice
**Kelompok 3 | Universitas Brawijaya | 2026**

---

## 1. Setup Environment

### Prasyarat
| Software | Keterangan |
|----------|------------|
| XAMPP | Apache + MySQL harus berjalan (status hijau) |
| WordPress | Terinstall di `C:\xampp\htdocs\kstcangar\wordpress` |
| Plugin aktif | KST Cangar Backoffice versi 1.4.0 |

### Langkah Setup
1. Buka XAMPP Control Panel → klik **Start** pada **Apache** dan **MySQL**
2. Buka `http://localhost/kstcangar/wordpress/wp-admin`
3. Login dengan kredensial Administrator
4. Pastikan plugin **KST Cangar Backoffice** berstatus **Active** di menu Plugins

### Struktur Folder Plugin
```
wp-content/plugins/kstcangar-backoffice/
├── kstcangar-backoffice.php    ← entry point plugin
├── includes/
│   ├── class-database.php      ← setup tabel MySQL otomatis
│   ├── class-roles.php         ← RBAC & custom roles
│   ├── class-helpers.php       ← fungsi utilitas
│   ├── class-jwt.php           ← generate & verifikasi JWT
│   └── class-api.php           ← semua REST API endpoint
├── modules/
│   ├── stok/                   ← modul stok opname
│   ├── booking/                ← modul booking
│   └── keuangan/               ← modul keuangan
└── assets/css/admin.css
```

### Database
- **DBMS**: MySQL (via XAMPP)
- **Nama Database**: `kstcangar`
- Tabel dibuat otomatis saat plugin diaktifkan

| Tabel | Fungsi |
|-------|--------|
| `wp_kst_items` | Master barang café |
| `wp_kst_stock_movements` | Pergerakan stok harian |
| `wp_kst_stock_opname` | Rekap stok mingguan |
| `wp_kst_bookings` | Data booking layanan |
| `wp_kst_finances` | Data transaksi keuangan |

---

## 2. Manajemen User & Role

### Cara Buat User Baru
1. Login sebagai Administrator ke `wp-admin`
2. **Users → Add New**
3. Isi **Username**, **Email**, **Password**
4. Pilih **Role** yang sesuai di bagian Role
5. Klik **Add New User**

### Daftar Role & Akses
| Role | Menu yang Bisa Diakses | Bisa Validasi Data? |
|------|------------------------|---------------------|
| `administrator` | Semua | ✅ |
| `admin_kst` | Semua | ✅ |
| `operator_stok` | Stok saja | ❌ |
| `operator_booking` | Booking saja | ❌ |
| `operator_keuangan` | Keuangan saja | ❌ |
| `manajemen` | Semua (read-only) | ❌ |

### URL Login
```
http://localhost/kstcangar/wordpress/wp-login.php
```

---

## 3. REST API

### Base URL
```
http://localhost/kstcangar/wordpress/wp-json/kstcangar/v1
```

### Format Response — Kontrak Pusat KST Dashboard

**Response Sukses:**
```json
{
    "timestamp": "2026-05-17T10:00:00+07:00",
    "response": { ... }
}
```

**Response Error:**
```json
{
    "timestamp": "2026-05-17T10:00:00+07:00",
    "response": null,
    "error": {
        "code": 401,
        "message": "Token tidak valid atau sudah expired."
    }
}
```

### Autentikasi
Semua endpoint (kecuali Login dan Health Check) membutuhkan JWT token di header:
```
Authorization: Bearer {token}
```

**Alur autentikasi:**
1. Hit `POST /auth/login` dengan username & password → dapat `accessToken`
2. Simpan token
3. Sertakan token di header setiap request berikutnya
4. Token berlaku **8 jam** — jika expired, login ulang

---

## 4. Daftar Endpoint

### Auth

| Method | Endpoint | Auth | Deskripsi |
|--------|----------|------|-----------|
| `POST` | `/auth/login` | ❌ | Login, mendapatkan JWT token |
| `GET` | `/auth/me` | ✅ | Cek token aktif & info user |

#### `POST /auth/login`
**Request:**
```json
{
    "username": "admin",
    "password": "password_kamu"
}
```
**Response `200`:**
```json
{
    "timestamp": "2026-05-17T10:00:00+07:00",
    "response": {
        "accessToken": "eyJ0eXAiOiJKV1Q...",
        "expDate": 1747454400,
        "user": {
            "userid": "1",
            "username": "admin",
            "name": "Administrator",
            "roles": { "cangar": ["administrator"] }
        }
    }
}
```

---

### Health & Contract

| Method | Endpoint | Auth | Deskripsi |
|--------|----------|------|-----------|
| `GET` | `/health` | ❌ | Status server & koneksi DB |
| `GET` | `/contract` | ✅ | Kontrak data KST Cangar |

---

### Stok

| Method | Endpoint | Auth | Deskripsi |
|--------|----------|------|-----------|
| `GET` | `/stok` | ✅ | Rekap stok opname mingguan |
| `GET` | `/stok/items` | ✅ | Daftar master barang aktif |

#### `GET /stok`
| Query Param | Tipe | Wajib | Keterangan |
|-------------|------|-------|------------|
| `week` | string | Tidak | Format `YYYY-WXX`. Default: minggu berjalan |
| `item_id` | int | Tidak | Filter by ID barang |

**Response `200`:**
```json
{
    "timestamp": "...",
    "response": {
        "code": "1f0ca001-0001-6000-8000-000000000001",
        "createdAt": "2026-05-17T10:00:00+07:00",
        "updatedAt": null,
        "data": {
            "typeName": "table",
            "offset": 0,
            "limit": 3,
            "hasNext": false,
            "items": [
                {
                    "rowId": "1",
                    "createdAt": "2026-05-12 08:00:00",
                    "updatedAt": null,
                    "colValues": [
                        { "colIdx": 0,  "value": "2026-W20" },
                        { "colIdx": 1,  "value": "Kopi Sachet" },
                        { "colIdx": 2,  "value": "pcs" },
                        { "colIdx": 3,  "value": 100 },
                        { "colIdx": 4,  "value": 50 },
                        { "colIdx": 5,  "value": 30 },
                        { "colIdx": 6,  "value": 2 },
                        { "colIdx": 7,  "value": 122 },
                        { "colIdx": 8,  "value": 120 },
                        { "colIdx": 9,  "value": 2 },
                        { "colIdx": 10, "value": "validated" }
                    ]
                }
            ]
        }
    }
}
```

**Mapping kolom stok:**
| colIdx | Nama Kolom |
|--------|------------|
| 0 | Minggu |
| 1 | Nama Barang |
| 2 | Satuan |
| 3 | Stok Awal |
| 4 | Masuk |
| 5 | Keluar |
| 6 | Retur |
| 7 | Stok Sistem |
| 8 | Stok Fisik |
| 9 | Selisih |
| 10 | Status |

---

### Booking

| Method | Endpoint | Auth | Deskripsi |
|--------|----------|------|-----------|
| `GET` | `/booking` | ✅ | Daftar semua booking |
| `GET` | `/booking/{id}` | ✅ | Detail satu booking |

#### `GET /booking`
| Query Param | Tipe | Wajib | Keterangan |
|-------------|------|-------|------------|
| `status` | string | Tidak | `pending` / `confirmed` / `cancelled` |
| `service_type` | string | Tidak | `glamping` / `cafe` / `camping` |
| `date` | string | Tidak | Format `YYYY-MM-DD` |
| `month` | string | Tidak | Format `YYYY-MM` |

**Mapping kolom booking:**
| colIdx | Nama Kolom |
|--------|------------|
| 0 | Nama Customer |
| 1 | No. HP |
| 2 | Layanan |
| 3 | Tanggal |
| 4 | Jumlah |
| 5 | Status |

---

### Keuangan

| Method | Endpoint | Auth | Deskripsi |
|--------|----------|------|-----------|
| `GET` | `/keuangan` | ✅ | Daftar transaksi keuangan |
| `GET` | `/keuangan/rekap` | ✅ | Rekap per hari dalam satu bulan |

#### `GET /keuangan`
| Query Param | Tipe | Wajib | Keterangan |
|-------------|------|-------|------------|
| `type` | string | Tidak | `INCOME` / `EXPENSE` |
| `date` | string | Tidak | Format `YYYY-MM-DD` |
| `month` | string | Tidak | Format `YYYY-MM` |
| `status` | string | Tidak | `draft` / `validated` / `rejected` |

**Mapping kolom keuangan:**
| colIdx | Nama Kolom |
|--------|------------|
| 0 | Tanggal |
| 1 | Jenis (INCOME/EXPENSE) |
| 2 | Kategori |
| 3 | Nominal (IDR) |
| 4 | Keterangan |
| 5 | Status |

#### `GET /keuangan/rekap`
| Query Param | Tipe | Wajib | Keterangan |
|-------------|------|-------|------------|
| `month` | string | Tidak | Format `YYYY-MM`. Default: bulan berjalan |

---

### Summary

| Method | Endpoint | Auth | Deskripsi |
|--------|----------|------|-----------|
| `GET` | `/summary` | ✅ | Ringkasan semua modul untuk dashboard |

---

## 5. Test API via Postman

### Import Collection
1. Download file `KST-Cangar-API.postman_collection.json`
2. Buka Postman → klik **Import** (pojok kiri atas)
3. Drag & drop file JSON ke window Import → klik **Import**
4. Collection **KST Cangar Backoffice API** akan muncul di sidebar

### Setup Environment
Collection sudah menggunakan variable bawaan — tidak perlu buat environment terpisah. Variable tersedia langsung di collection:

| Variable | Default Value | Keterangan |
|----------|---------------|------------|
| `base_url` | `http://localhost/kstcangar/wordpress/wp-json/kstcangar/v1` | Base URL API |
| `token` | _(kosong)_ | Diisi otomatis setelah login |

Jika base URL berbeda, klik kanan collection → **Edit** → tab **Variables** → ubah nilai `base_url`.

### Cara Pakai
1. Expand folder **Auth** → klik request **Login**
2. Ubah `username` dan `password` di tab **Body** sesuai akun kamu
3. Klik **Send**
4. Jika sukses, token otomatis tersimpan ke variable `token` via script di tab **Tests**
5. Sekarang semua request lain bisa langsung dijalankan — token sudah terpasang otomatis di header

### Tips Testing
- Semua query parameter di setiap request sudah disiapkan tapi dalam kondisi **disabled** — aktifkan sesuai kebutuhan dengan mencentang checkbox di tab **Params**
- Ada beberapa request contoh siap pakai seperti **"Get Booking — Filter Pending"** dan **"Get Transaksi — Filter Pemasukan Validated"**
- Jika muncul error `401` berarti token expired — jalankan ulang request **Login**

---

## 6. Error Codes

| HTTP Code | Keterangan |
|-----------|------------|
| `200` | Sukses |
| `201` | Data berhasil dibuat |
| `204` | Sukses tanpa response body |
| `400` | Request tidak valid / field wajib kosong |
| `401` | Token tidak ditemukan, tidak valid, atau expired |
| `403` | Terautentikasi tapi tidak punya akses |
| `404` | Data tidak ditemukan |
| `409` | Konflik data (duplikasi) |
| `422` | Data valid secara sintaks tapi tidak bisa diproses |
| `429` | Terlalu banyak request (rate limit) |
| `500` | Internal server error |
| `503` | Server tidak tersedia / DB tidak terkoneksi |
