# Engineering Decision Records & Technical Assumptions — Goers RestoFinder

> **Standard:** Architecture Decision Records (ADR) & Technical Justifications  
> **Author:** Senior Fullstack Software Engineer  
> **Project:** Goers Fullstack Engineer Assessment (Restaurant Directory & Schedule Engine)

Dokumen ini memformalkan seluruh asumsi teknis, trade-offs rekayasa perangkat lunak (*engineering trade-offs*), serta rasionalisasi di balik keputusan desain sistem untuk memenuhi seluruh butir penugasan pada `Requirement_test.pdf`.

---

## ADR-001: Model Penyimpanan Jadwal — Relational 3NF vs String/JSONB

### Konteks:
Sumber data mentah berupa baris CSV dengan format string natural language yang sangat bervariasi (misal: `"Mon-Thu, Sun 11:30 am - 9 pm / Fri-Sat 11:30 am - 9:30 pm"`).

### Keputusan:
Database **TIDAK menyimpan string jadwal mentah sama sekali**. String jadwal hanya diproses satu kali (*one-time ingestion*) saat seeder dijalankan melalui `ScheduleParserService`, lalu dipecah menjadi baris-baris relasional terindeks di tabel `restaurant_schedules`.

### Rasionalisasi & Trade-offs:
* **Performa ($O(\log N)$ vs $O(N)$):** Menyimpan string mentah akan memaksa aplikasi melakukan deserialisasi regex di level PHP pada setiap kueri pencarian, menyebabkan $O(N)$ full table scan dan penggunaan memori yang tidak terukur. Dengan tabel relasional ternormalisasi (3NF), pencarian jam dilakukan langsung oleh PostgreSQL Query Engine memanfaatkan B-Tree composite index `(day_of_week, open_time, close_time)`.
* **Idempotensi & Data Cleanliness:** API tidak pernah mengekspos field mentah `raw_schedule`. Representasi tampilan (misal `"Daily 11:30 am – 9 pm"`) dihitung secara dinamis saat serialisasi API melalui `ScheduleFormatterService`.

---

## ADR-002: Pemodelan Matematis Midnight Crossover (Layanan Lewat Tengah Malam)

### Konteks:
Restoran seperti *Naan 'N' Curry* (`11 am - 4 am`), *Marrakech* (`5:30 pm - 2 am`), dan *Sudachi* (`5 pm - 1:30 am`) memiliki jam operasional yang melintasi tengah malam (pukul 24:00/00:00).

### Keputusan:
1. Sebuah shift dikategorikan sebagai `spans_next_day = true` jika dan hanya jika `close_time < open_time`. Flag ini dihitung secara deterministik oleh server dan ditegakkan oleh PostgreSQL Check Constraint:
   ```sql
   CHECK (spans_next_day = (close_time < open_time))
   ```
2. Ketika pengguna memfilter ketersediaan pada hari $D$ (misal: Selasa) pukul $T$ (misal: `01:30 AM`):
   * Restoran yang buka hari Senin malam (`11 am - 4 am`) secara operasional dan matematis **dihitung BUKA**.
   * Algoritma kueri mengevaluasi dua sumbu waktu:
     * **Sumbu Hari Berjalan:** Shift hari ini yang dimulai sebelum tengah malam dan melintasi $24:00$.
     * **Sumbu Hari Kemarin:** Shift hari kemarin ($D-1$) yang menyeberang ke subuh hari ini dan jam tutupnya belum terlampaui ($T \le \text{close\_time}$).

---

## ADR-003: Shift Kontinu vs Split Shifts (Lunch & Dinner Modeling)

### Konteks:
Data awal di PDF mencantumkan format rentang tunggal seperti `"Mon-Sun 11 am - 10 pm"`.

### Keputusan:
* Data sumber yang berkesinambungan **disimpan apa adanya sebagai satu shift kontinu** (tidak dipecah secara artifisial menjadi dua baris 11-14 dan 17-22). Ini menjamin bahwa jika ada pengguna yang mencari makan pada pukul `15:00` (sore hari), restoran yang bersangkutan tetap terdeteksi buka sesuai kenyataan data aslinya.
* **Label Kategori Layanan:** Di UI dan API Resource, rentang tersebut dianotasikan secara dinamis sebagai `Lunch & dinner` atau `Lunch, dinner & late night` berdasarkan spektrum jam yang dicakupnya.
* **Dukungan Multi-Service:** Model relasional tetap mendukung multi-service per hari untuk input restoran baru via Admin (misal: shift 1 `11:30–14:30` dan shift 2 `17:00–22:00`).

---

## ADR-004: Penanganan Anomali Dataset "Parallel 37" & Zero-Duration Shifts

### Konteks:
Pada dataset PDF terdapat dua entri untuk `"Parallel 37"`:
1. Baris reguler: `"Parallel 37","Mon-Sun 11:30 am - 10 pm"`
2. Baris lounge/event: `"Parallel 37","Mon, Fri 5 pm - 6:15 pm / Tues 12:15 pm - 12:15 pm / Weds 1:15 pm - 5:45 pm / Thurs, Sat 10 am - 3 pm / Sun 6:30 am - 12:45 pm"`

### Keputusan:
* Sesuai klarifikasi penguji, baris reguler dihapus dan hanya satu entitas `"Parallel 37"` dengan jadwal lounge khusus yang dipertahankan.
* **Zero-Duration Boundary:** Slot `Tues 12:15 pm - 12:15 pm` memiliki waktu buka identik dengan waktu tutup (durasi 0 menit). Shift ini **ditolak secara konsisten** baik di seeder maupun validasi form (`open_time <> close_time`). Konsekuensinya, hari Selasa bagi Parallel 37 secara logis dihitung sebagai **TUTUP** (*Closed*).

---

## ADR-005: Kombinasi Matriks Filter (Date, Time, Date+Time, Open Now)

### Keputusan Perilaku Filter:
Sistem mendukung 4 permutasi filter temporal yang independen dan saling melengkapi:

| Permutasi Input | Perilaku Backend (`RestaurantController`) | Logika Query SQL |
| :--- | :--- | :--- |
| **Hanya `name`** | Pencarian teks sebagian | `LOWER(name) LIKE '%term%'` |
| **Hanya `date`** | Mengambil hari ISO (`1..7`) dari tanggal yang dipilih | Restoran yang memiliki shift aktif pada hari tersebut (`openOnDay`) |
| **Hanya `time`** | Mengambil hari ini di waktu server | Restoran yang buka pada jam tersebut di hari ini (`openAt`) |
| **`date` + `time`** | Mengambil hari dari tanggal dan memvalidasi jam | Restoran yang buka tepat pada kombinasi hari dan jam tersebut (memperhitungkan crossover tengah malam) |
| **`open_now = true`** | Membaca jam & hari server saat ini secara presisi | Filter real-time detik ini pada timezone server |

---

## ADR-006: Zona Waktu & Kerangka Acuan Waktu (Timezone)

### Konteks:
Restoran pada data awal berlokasi di San Francisco (Pacific Time), sedangkan reviewer/evaluator berada di Indonesia.

### Keputusan:
* Aplikasi mengonfigurasi `APP_TIMEZONE=Asia/Jakarta` (WIB) pada Laravel.
* Jam operasional pada dataset (misal `11:00 AM – 10:00 PM`) diperlakukan sebagai **jam lokal acuan ketersediaan**. Status "Open now" mengevaluasi jam real-time server pada WIB terhadap jam operasional tersebut. Hal ini memastikan evaluator dapat menguji fitur secara langsung pada jam kerja Indonesia tanpa harus memanipulasi timezone lokal mesinnya.

---

## ADR-007: Keamanan & Zero-Trust Request Authorization

### Keputusan:
1. **Pemisahan Hak Akses:**
   * Operasi baca (`GET /restaurants`, `GET /restaurants/{id}`, `GET /stats`) bersifat **Public**.
   * Operasi tulis (`POST`, `PUT`, `DELETE`) **wajib menyertakan Bearer Token** yang sah via middleware `auth:sanctum`.
2. **Pertahanan Berlapis (Defense-in-Depth):**
   * Di samping route middleware, form request `StoreRestaurantRequest::authorize()` memvalidasi `$this->user() !== null` secara mandiri.
   * Parameter `per_page` di-clamp dengan batas atas `max(1, min($perPage, 100))` guna mencegah eksploitasi Memory Exhaustion / Database Dump DoS.
   * Field `description` dibatasi maksimal `2000` karakter.
   * Kebijakan CORS dibatasi hanya untuk origin tepercaya melalui environment variable `CORS_ALLOWED_ORIGINS` (bukan wildcard `*`).
   * Token Sanctum diberikan batas waktu kedaluwarsa eksplisit (default 7 hari via `SANCTUM_TOKEN_EXPIRATION=10080`).
