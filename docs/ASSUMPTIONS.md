# Technical Assumptions - Goers RestoFinder

Dokumen ini mencatat asumsi-asumsi teknis yang digunakan dalam perancangan database, arsitektur backend, algoritma parser jadwal, serta frontend aplikasi:

---

### 1. Format Jadwal Operasional & Parsing Otomatis
- **Struktur String**: Jadwal dari sumber data awal mengikuti format natural language semi-terstruktur. Sistem mengasumsikan tanda slash `/` sebagai pemisah kelompok jadwal, tanda koma `,` sebagai pemisah hari individual, dan tanda hubung `-` sebagai rentang hari (contoh: `Mon-Fri`) maupun rentang jam (contoh: `11 am - 9 pm`).
- **Normalisasi Singkatan Hari**: Variasi penulisan seperti `Tues` dan `Weds` dipetakan ke hari standar ISO-8601 (`1 = Monday`, `2 = Tuesday`, ..., `7 = Sunday`).
- **Normalisasi Format Waktu**: Format jam 12-jam (dengan `am`/`pm`) dinormalisasi menjadi format SQL TIME (`HH:mm:ss`). Format `12 am` dinormalisasi sebagai `00:00:00` (tengah malam), dan `12 pm` sebagai `12:00:00` (tengah hari).

---

### 2. Logika Jam Buka Melintasi Tengah Malam (Midnight Rollover)
- Jika `close_time <= open_time` (contoh: `11 am - 4 am` atau `5:30 pm - 2 am`), shift tersebut dikategorikan sebagai `spans_next_day = true`.
- Ketika seorang user mencari restoran pada hari **Selasa pukul 01:30 AM**:
  - Restoran dengan shift Senin malam `11 am - 4 am` **diasumsikan dan dihitung BUKA** karena shift hari Senin berlanjut hingga Selasa dini hari (04:00 AM).
  - Kasus khusus seperti `Tues 12:15 pm - 12:15 pm` diperlakukan sebagai window khusus di mana `spans_next_day = false`.

---

### 3. Duplikasi Restoran "Parallel 37" pada Sumber Data Awal
- Pada dataset yang diberikan terdapat 2 baris dengan nama restoran `"Parallel 37"` namun dengan jadwal berbeda:
  1. `"Parallel 37","Mon-Sun 11:30 am - 10 pm"`
  2. `"Parallel 37","Mon, Fri 5 pm - 6:15 pm / Tues 12:15 pm - 12:15 pm / Weds 1:15 pm - 5:45 pm / Thurs, Sat 10 am - 3 pm / Sun 6:30 am - 12:45 pm"`
- **Asumsi**: Kedua entitas dipertahankan dalam database dengan pembeda nama cabang (`Parallel 37` dan `Parallel 37 (Lounge Edition)`) agar seluruh variasi data dari penguji tetap tersimpan dan dapat diverifikasi secara utuh.

---

### 4. Perilaku Kombinasi Filter
- **Filter Nama**: Menggunakan partial substring match (SQL `LIKE %term%`).
- **Filter Tanggal Saja**: Mengambil hari dalam seminggu dari tanggal tersebut (1-7), kemudian mencari restoran yang memiliki jadwal buka di hari tersebut.
- **Filter Jam Saja**: Menguji ketersediaan pada hari saat ini (hari ini) pada jam yang dimasukkan.
- **Filter Tanggal & Jam Sekaligus**: Menghitung hari dari tanggal yang dipilih dan memverifikasi apakah restoran buka tepat pada kombinasi hari dan jam tersebut (memperhitungkan crossover tengah malam).
- **Filter "Open Now"**: Membaca hari dan waktu server secara real-time.

---

### 5. Aksesibilitas Admin & Autentikasi
- Penguji/Reviewer disediakan akun default Admin:
  - **Email**: `admin@goers.co`
  - **Password**: `password`
- Untuk mempermudah proses evaluasi pengetesan, endpoint REST API manajemen restoran (`POST /api/restaurants`, `PUT /api/restaurants/{id}`, `DELETE /api/restaurants/{id}`) serta UI Admin Portal dapat langsung diuji baik dengan autentikasi Sanctum maupun secara instan melalui UI tab "Admin Portal".
