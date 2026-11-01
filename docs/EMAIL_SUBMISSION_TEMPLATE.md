# Email Submission Template — Goers Trial Day

Dokumen ini disediakan sebagai draf surat pengantar/email resmi siap kirim ke Tim Goers sesuai dengan instruksi pada **Prosedur Pengumpulan** di file `Requirement_test.pdf`:
> *"3. Berikan screenshot hasil pengetesan aplikasi"*
> *"4. Desain REST API dan Database, dapat dilampirkan di dalam email pengumpulan"*
> *"Dalam proses pengerjaan, bila memerlukan asumsi-asumsi tersebut, silahkan anda menuliskan asumsi tersebut pada saat mengumpulkan."*

---

## ✉️ Draft Email Pengumpulan

**Kepada:** Tim Recruitment / Engineering Goers (`career@goersapp.com` / PIC Contact Point)  
**Subjek:** Pengumpulan Trial Day - Fullstack Engineer — [Nama Anda]

---

Yth. Tim Engineering Goers,

Terima kasih atas kesempatan yang diberikan untuk mengikuti tahapan **Trial Day - Fullstack Engineer**. Bersama email ini, saya melampirkan hasil pengerjaan technical assessment aplikasi **Restaurant Directory & Schedule Engine** yang dibangun menggunakan **Laravel 11 (Backend)** dan **React 19 (Frontend)** sesuai dengan seluruh ketentuan pada dokumen `Requirement_test.pdf`.

---

### 1. Tautan Repository & Akses Source Code
* **URL Repository Public:** `https://github.com/[username-anda]/[nama-repo]`
* **Docker Zero-Config:** Aplikasi dapat langsung dijalankan dalam 1 baris perintah:
  ```bash
  docker compose up --build -d
  ```
  *(Otomatis memigrasikan database PostgreSQL 16, menjalankan seeder 51 restoran & 352 shift mingguan, membangun bundle React, dan menyalakan pgAdmin 4 pada port `5051`)*.

---

### 2. Ringkasan Pemenuhan Kapabilitas (Requirements Compliance)

| Kapabilitas yang Diminta | Status | Implementasi & Catatan Teknis |
| :--- | :---: | :--- |
| **1. User dapat melihat daftar semua restoran** | **TERPENUHI** | Direktori restoran disajikan dalam bentuk ledger editorial interaktif dengan status live (*Open now / Closed now*), rating, kategori kuliner, dan ringkasan jadwal mingguan terstruktur. |
| **2. User dapat melakukan filter nama, tanggal, atau jam** | **TERPENUHI** | Filter nama (*debounced search*, case-insensitive pada PostgreSQL), filter tanggal (resolusi ke ISO-8601 day-of-week), filter jam dengan **Midnight Crossover Engine** (shift malam yang tembus ke subuh hari berikutnya terdeteksi akurat). Tersedia juga shortcut preset (*Open now*, *Today*, *Tomorrow*, *Lunch*, *Dinner*, *Late night 01:00*). |
| **3. Admin dapat menambahkan data restoran baru** | **TERPENUHI** | Form modal dengan editor mingguan terstruktur (7 hari, multi-service lunch & dinner, kalkulasi otomatis `spans_next_day` `+1 day`). Disimpan langsung sebagai baris relasional tanpa string mentah. |
| **4. Admin dapat melihat list restoran** | **TERPENUHI** | Portal tabel administrasi dengan sorting, paginasi, tombol view shift breakdown, form edit, dan aksi delete dengan konfirmasi. Dilindungi autentikasi Laravel Sanctum. |

---

### 3. Lampiran Dokumen Desain & Asumsi Teknis

Sesuai dengan instruksi pengumpulan, dokumen arsitektur dan spesifikasi teknis lengkap telah disertakan di dalam repository dan dirangkum pada lampiran berikut:

1. **Desain Database (3NF Relational Schema):**
   * Lokasi file: [`docs/DATABASE_DESIGN.md`](docs/DATABASE_DESIGN.md)
   * Menggunakan PostgreSQL 16 dengan memisahkan tabel `restaurants` dan `restaurant_schedules` (Relasi 1:N).
   * **Integritas Level Database:** Menggunakan `CHECK (spans_next_day = (close_time < open_time))`, `CHECK (open_time <> close_time)`, serta `UNIQUE (restaurant_id, day_of_week, open_time, close_time)` untuk menjamin validitas data tanpa bergantung hanya pada validasi aplikasi.
   * **Composite Index:** `(day_of_week, open_time, close_time)` untuk menjamin query availability berjalan konstan dalam orde milidetik ($O(\log N)$).

2. **Desain REST API:**
   * Lokasi file: [`docs/API_DOCUMENTATION.md`](docs/API_DOCUMENTATION.md)
   * Seluruh endpoint publik dan privat terdokumentasi lengkap dengan skema request/response JSON, query parameters, dan kode status HTTP (RFC 7807).
   * Endpoint mutasi (`POST`, `PUT`, `DELETE`) dilindungi Bearer Token **Laravel Sanctum**.

3. **Daftar Asumsi Teknis & Engineering Trade-offs:**
   * Lokasi file: [`docs/ASSUMPTIONS.md`](docs/ASSUMPTIONS.md)
   * **Asumsi Midnight Rollover:** Jika restoran buka Senin 11:00 AM – 04:00 AM, pencarian pada Selasa pukul 02:00 AM secara matematis dan operasional dihitung **BUKA**.
   * **Asumsi Entitas Parallel 37:** Sesuai klarifikasi penguji, entitas baris reguler dihapus dan hanya jadwal lounge eksak yang dipertahankan. Window berdurasi nol (`Tues 12:15 pm - 12:15 pm`) ditangani secara aman sebagai hari tutup.
   * **Asumsi Timezone:** Jam operasional restoran San Francisco dievaluasi menggunakan zona waktu aplikasi `Asia/Jakarta` (WIB) untuk simulasi live ketersediaan.

---

### 4. Ringkasan Bukti Pengujian (Test Suite & Screenshots)

* **Automated Tests:** Telah diuji dengan **35 unit & feature test cases** (total **240 assertions**) yang mencakup parsing jadwal, midnight rollover, proteksi Sanctum, dan idempotensi sinkronisasi jadwal. Seluruh test **100% PASSING**.
* **Screenshot Aplikasi:** Terlampir pada folder `docs/screenshots/`:
  1. `01_explore_directory.png` — Tampilan direktori & live status buka/tutup.
  2. `02_search_cheesecake.png` — Hasil pencarian teks nama restoran.
  3. `03_late_night_midnight_filter.png` — Bukti keakuratan filter Midnight Crossover pada pukul 01:00 AM.
  4. `04_admin_portal_table.png` — Portal administrasi daftar restoran.
  5. `05_admin_add_modal.png` — Modal input data restoran & editor jadwal terstruktur.
  6. `06_mobile_index.png` — Uji responsivitas pada layar mobile.
  7. `07_admin_login.png` — Gate autentikasi admin.
  8. `08_weekly_shifts_split_service.png` — Rincian jam operasional mingguan tanpa overlap.

---

### 5. Kredensial Pengujian (Seeded Account)
* **Admin Email:** `admin@goers.co`
* **Admin Password:** `password`
* **pgAdmin 4 (Database UI):** `http://localhost:5051` (Email: `admin@goers.co` / Pass: `password`)

Demikian penyerahan tugas ini saya sampaikan. Saya sangat terbuka untuk mendiskusikan keputusan arsitektural maupun rincian kode lebih lanjut dalam sesi review.

Hormat saya,

**[Nama Anda]**  
[No. Kontak / LinkedIn / Portfolio]

