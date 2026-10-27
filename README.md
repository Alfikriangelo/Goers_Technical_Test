# Goers RestoFinder - Fullstack Restaurant Directory & Schedule Engine

Aplikasi full-stack web untuk manajemen dan pencarian direktori restoran dengan engine jadwal operasional cerdas (*smart schedule parsing & midnight crossover filtering*), dibangun untuk memenuhi **Trial Day - Fullstack Engineer (Goers)**.

![Explore Directory](docs/screenshots/01_explore_directory.png)

---

## 🚀 Fitur Utama & Kapabilitas

1. **User - Melihat Daftar Semua Restoran**:
   - Menampilkan katalog restoran dalam format kartu modern dengan indikator status live (*Open Now / Closed*), rating bintang, kategori kuliner, dan ringkasan jadwal.
   - Modal detail jadwal mingguan per hari (Senin s/d Minggu).
2. **User - Filter Berdasarkan Nama, Tanggal, atau Jam Tertentu**:
   - Pencarian teks nama restoran real-time (*debounced*).
   - Filter tanggal spesifik dengan kalender interaktif.
   - Filter jam operasional spesifik.
   - Presets instan: **Open Right Now**, **Today**, **Tomorrow**, **Lunch (12:00)**, **Dinner (19:00)**, dan **Late Night (01:00 AM)**.
   - **Midnight Crossover Logic**: Secara akurat menangani restoran yang buka melintasi tengah malam (contoh: shift Senin 11:00 AM - 04:00 AM tetap terdeteksi buka pada Selasa 02:00 AM).
3. **Admin - Menambahkan Data Restoran Baru**:
   - Form modal pembuatan restoran baru.
   - Mendukung input jadwal natural language (contoh: `"Mon-Fri 10 am - 10 pm / Sat-Sun 11 am - 12 am"`), yang secara otomatis diuraikan (*parsed*) oleh backend ke dalam record relasional terstruktur.
4. **Admin - Melihat List Restoran**:
   - Portal administrasi khusus berbasis tabel data dengan sorting, pagination, tombol edit, dan delete restoran.

---

## 🛠️ Tech Stack & Architecture

- **Backend**: **Laravel 11** (PHP 8.3)
  - Arsitektur: Clean MVC & Service Pattern (`ScheduleParserService`, Eloquent Query Scopes, API Resources, Form Requests).
  - Database: Relational Database (SQLite / MySQL ready).
  - Autentikasi: Laravel Sanctum.
  - Testing: PHPUnit Feature & Unit Test Suites (100% test passing).
- **Frontend**: **React 18** (Vite SPA)
  - Styling: Vanilla CSS Modern Design System (Dark mode, glassmorphism, micro-animations, Plus Jakarta Sans typography).
  - Icons: Lucide React.
  - HTTP Client: Axios dengan reverse proxy setup.

---

## 📸 Screenshot Hasil Pengetesan

Berikut adalah bukti pengetesan end-to-end aplikasi:

### 1. Katalog Direktori & Live Status
![Katalog Direktori](docs/screenshots/01_explore_directory.png)

### 2. Filter Pencarian Nama ("Cheesecake")
![Filter Nama](docs/screenshots/02_search_cheesecake.png)

### 3. Filter Jam Melintasi Tengah Malam (Late Night 01:00 AM)
Menampilkan restoran yang buka melewati tengah malam (seperti *Naan 'N' Curry* dan *Goers Luxury Lounge*):
![Filter Late Night](docs/screenshots/03_late_night_midnight_filter.png)

### 4. Admin Portal - Manajemen List Restoran
![Admin Table](docs/screenshots/04_admin_portal_table.png)

### 5. Admin Portal - Form Tambah Restoran Baru
![Admin Add Modal](docs/screenshots/05_admin_add_modal.png)

---

## 🗄️ Desain Database & ERD

Detail lengkap mengenai desain database, indexing, dan formula query terdapat pada [docs/DATABASE_DESIGN.md](docs/DATABASE_DESIGN.md).

```mermaid
erDiagram
    RESTAURANTS ||--o{ RESTAURANT_SCHEDULES : "has many (1:N)"
    USERS ||--o{ PERSONAL_ACCESS_TOKENS : "has many tokens"

    RESTAURANTS {
        bigint unsigned id PK
        varchar name "Indexed"
        text raw_schedule
        varchar cuisine_type
        varchar address
        decimal rating
        text description
        timestamp created_at
        timestamp updated_at
    }

    RESTAURANT_SCHEDULES {
        bigint unsigned id PK
        bigint unsigned restaurant_id FK
        tinyint unsigned day_of_week "1=Mon .. 7=Sun"
        time open_time
        time close_time
        boolean spans_next_day "Past midnight flag"
        timestamp created_at
        timestamp updated_at
    }
```

---

## 📡 Desain REST API

Dokumentasi lengkap seluruh endpoint dan contoh payload JSON dapat diakses pada [docs/API_DOCUMENTATION.md](docs/API_DOCUMENTATION.md).

Ringkasan endpoint:
- `GET /api/restaurants` - List restoran & filter (nama, date, time, open_now)
- `GET /api/restaurants/{id}` - Detail restoran beserta shifts jadwal
- `POST /api/restaurants` - Tambah restoran baru (Admin)
- `PUT /api/restaurants/{id}` - Update restoran (Admin)
- `DELETE /api/restaurants/{id}` - Hapus restoran (Admin)
- `GET /api/stats` - Statistik agregat
- `POST /api/auth/login` - Login admin (Sanctum)

---

## 📋 Asumsi-Asumsi

Seluruh asumsi perancangan sistem dan parsing data telah didokumentasikan di [docs/ASSUMPTIONS.md](docs/ASSUMPTIONS.md).

---

## ⚡ Panduan Instalasi & Menjalankan Aplikasi

### 1. Prasyarat
- PHP >= 8.2 (dengan extension `pdo_sqlite` atau `pdo_mysql`)
- Composer
- Node.js >= 18 & npm

### 2. Setup Backend (Laravel)
```bash
cd backend

# Install dependensi (jika belum)
composer install

# Siapkan file environment dan database
copy .env.example .env
php artisan key:generate

# Jalankan migrasi dan seeder data awal (52 data restoran)
php artisan migrate --seed

# Jalankan unit & feature tests
php artisan test

# Jalankan server API backend
php artisan serve --port=8000
```
Server backend akan aktif di `http://127.0.0.1:8000`.

### 3. Setup Frontend (React + Vite)
Buka terminal baru:
```bash
cd frontend

# Install dependensi
npm install

# Jalankan development server
npm run dev
```
Aplikasi frontend akan terbuka di `http://localhost:5173`.

---

## 🧪 Menjalankan Automated Tests

Seluruh skenario pengujian unit (parser jadwal) dan feature (filtering nama, tanggal, jam, midnight rollover, CRUD admin) dapat dijalankan dengan:
```bash
cd backend
php artisan test
```

Hasil pengujian:
```text
   PASS  Tests\Unit\ExampleTest
  ✓ that true is true

   PASS  Tests\Unit\ScheduleParserServiceTest
  ✓ parses simple range
  ✓ parses multiple sections with comma and dash
  ✓ parses midnight rollover
  ✓ parses 12 am midnight
  ✓ parses complex parallel 37 schedule

   PASS  Tests\Feature\RestaurantApiTest
  ✓ can get restaurant list
  ✓ can filter by name
  ✓ can filter by date and time midnight crossover
  ✓ can filter by only time
  ✓ admin can create new restaurant
  ✓ admin can update restaurant
  ✓ admin can delete restaurant
  ✓ stats endpoint

  Tests:    15 passed (130 assertions)
  Duration: 4.84s
```
