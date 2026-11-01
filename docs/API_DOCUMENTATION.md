# REST API Specification & Contract — Goers RestoFinder

> **Standard:** Enterprise REST API Specification (OpenAPI 3.1 & RFC 7807 Compliant)  
> **Protocol:** HTTP/1.1 over TLS / Plain HTTP in Development  
> **Base URLs:**
> - Local Backend: `http://localhost:8000/api`
> - Docker Reverse Proxy: `http://localhost:8081/api`
> - Vite Development Proxy: `http://localhost:5173/api`

---

## 1. Global Architectural Standards

### 1.1 Content Negotiation & Headers
Setiap request ke API wajib menyertakan HTTP Headers standar:

```http
Accept: application/json
Content-Type: application/json
```

Untuk endpoint mutasi yang dilindungi (Admin Role), tambahkan header otorisasi Bearer Token:
```http
Authorization: Bearer <sanctum_personal_access_token>
```

### 1.2 Status Codes Standard
| HTTP Status | Semantics | Keterangan |
| :--- | :--- | :--- |
| `200 OK` | Success | Request berhasil dieksekusi dan mengembalikan data. |
| `201 Created` | Resource Created | Entitas restoran baru berhasil dibuat dan disimpan. |
| `401 Unauthorized` | Auth Required | Token Sanctum hilang, kedaluwarsa, atau tidak valid. |
| `404 Not Found` | Resource Absent | ID restoran tidak ditemukan di database. |
| `422 Unprocessable` | Validation Error | Payload tidak lolos validasi form request / format jam keliru. |
| `500 Server Error` | Internal Failure | Terjadi kesalahan runtime internal server. |

### 1.3 Standardized Error Response Structure
Sesuai prinsip pertahanan mendalam (*defense-in-depth*), respons kegagalan disajikan terstruktur:

```json
{
  "message": "The given data was invalid.",
  "errors": {
    "schedules.0.close_time": [
      "A service must end at a different time than it starts."
    ]
  }
}
```

---   

## 2. API Endpoints Catalog

| HTTP Method | Route Endpoint | Access Scope | Deskripsi Fungsional |
| :--- | :--- | :---: | :--- |
| `GET` | `/api/restaurants` | **Public** | List direktori restoran dengan filter nama, tanggal, jam, status buka, dan paginasi |
| `GET` | `/api/restaurants/{id}` | **Public** | Mengambil data rincian satu restoran beserta jadwal mingguan lengkap |
| `GET` | `/api/stats` | **Public** | Statistik agregat metrik direktori (total restoran, buka saat ini, kategori masakan) |
| `POST` | `/api/auth/login` | **Public** | Otentikasi akun admin untuk menerbitkan token Bearer Sanctum |
| `GET` | `/api/auth/me` | **Admin** | Memverifikasi profil admin yang sedang terautentikasi |
| `POST` | `/api/auth/logout` | **Admin** | Mencabut (*revoke*) token akses saat ini |
| `POST` | `/api/restaurants` | **Admin** | Menambahkan restoran baru beserta jadwal operasional terstruktur |
| `PUT` | `/api/restaurants/{id}` | **Admin** | Memperbarui atribut restoran & mengganti jadwal mingguan |
| `DELETE` | `/api/restaurants/{id}` | **Admin** | Menghapus restoran beserta seluruh jadwalnya secara *cascade delete* |

---

## 3. Detailed Endpoint Specifications

### 3.1 GET `/api/restaurants` — Search & Filter Directory
Mengambil daftar restoran dengan kapabilitas filter multi-kombinasi (nama, tanggal, jam, buka sekarang, kategori, serta pengurutan).

#### Query Parameters:
| Parameter | Tipe Data | Format / Contoh | Wajib | Deskripsi |
| :--- | :--- | :--- | :---: | :--- |
| `name` / `q` | `string` | `Cheesecake` | Tidak | Pencarian nama sebagian (*case-insensitive partial search*). |
| `date` | `string` | `YYYY-MM-DD` (`2026-10-27`) | Tidak | Filter hari operasional berdasarkan tanggal ISO-8601. |
| `time` | `string` | `HH:mm` atau `HH:mm:ss` (`01:00`) | Tidak | Filter ketersediaan pada jam tertentu (mendukung midnight rollover). |
| `open_now` | `boolean` | `true` / `false` | Tidak | Filter restoran yang buka detik ini pada waktu server real-time. |
| `cuisine` | `string` | `Japanese` | Tidak | Filter kategori kuliner. |
| `sort_by` | `string` | `name` \| `rating` \| `created_at` | Tidak | Kolom pengurutan (default: `name`). |
| `sort_order` | `string` | `asc` \| `desc` | Tidak | Arah pengurutan (default: `asc`). |
| `page` | `integer` | `1` | Tidak | Nomor halaman aktif (default: `1`). |
| `per_page` | `integer` | `12` (Maks: `100`, `-1` = all) | Tidak | Ukuran halaman (terdapat *clamp safety* 1–100 untuk mencegah dump DoS). |

#### Contoh cURL Request:
```bash
curl -X GET "http://localhost:8000/api/restaurants?name=Naan&date=2026-10-27&time=01:00" \
     -H "Accept: application/json"
```

#### Contoh Response (`200 OK`):
```json
{
  "data": [
    {
      "id": 26,
      "name": "Naan 'N' Curry",
      "formatted_schedule": "Daily 11 am – 4 am (+1)",
      "cuisine_type": "Pakistani / Indian",
      "address": "478 O'Farrell St, San Francisco, CA",
      "rating": 4.6,
      "description": "Late night curry house running until 4:00 AM.",
      "is_open_now": true,
      "open_days_count": 7,
      "shifts_per_week": 7,
      "weekly_hours": [
        {
          "day_of_week": 1,
          "day_name": "Monday",
          "day_name_short": "Mon",
          "is_closed": false,
          "shift_count": 1,
          "summary": "11 am – 4 am (+1)",
          "shifts": [
            {
              "open_time": "11:00",
              "close_time": "04:00",
              "spans_next_day": true,
              "service_label": "Lunch, dinner & late night",
              "formatted_time": "11 am – 4 am (+1)"
            }
          ]
        }
      ],
      "created_at": "2026-10-27T15:23:32.000000Z",
      "updated_at": "2026-10-27T15:23:32.000000Z"
    }
  ],
  "links": {
    "first": "http://localhost:8000/api/restaurants?page=1",
    "last": "http://localhost:8000/api/restaurants?page=1",
    "prev": null,
    "next": null
  },
  "meta": {
    "current_page": 1,
    "from": 1,
    "last_page": 1,
    "per_page": 12,
    "to": 1,
    "total": 1,
    "applied_filters": {
      "name": "Naan",
      "date": "2026-10-27",
      "time": "01:00",
      "open_now": false,
      "cuisine": null
    }
  }
}
```

---

### 3.2 GET `/api/restaurants/{id}` — Retrieve Single Restaurant
Mengambil representasi lengkap dari satu restoran berdasarkan Primary Key-nya.

#### Contoh Response (`200 OK`):
```json
{
  "data": {
    "id": 1,
    "name": "Kushi Tsuru",
    "formatted_schedule": "Daily 11:30 am – 9 pm",
    "cuisine_type": "Japanese",
    "address": "1737 Post St, San Francisco, CA",
    "rating": 4.5,
    "description": null,
    "is_open_now": false,
    "open_days_count": 7,
    "shifts_per_week": 7,
    "weekly_hours": [...]
  }
}
```

---

### 3.3 POST `/api/restaurants` — Create New Restaurant (Admin)
Membuat record restoran baru beserta jadwal operasional terstruktur.

#### Request Headers:
```http
Authorization: Bearer <sanctum_token>
Content-Type: application/json
```

#### Request Body Schema:
```json
{
  "name": "Goers Signature Bistro",
  "cuisine_type": "French Contemporary",
  "address": "Jl. Senopati No. 45, Jakarta",
  "rating": 4.9,
  "description": "Exclusive dining experience with artisanal dishes.",
  "schedules": [
    {
      "day_of_week": 1,
      "open_time": "11:30",
      "close_time": "14:30"
    },
    {
      "day_of_week": 1,
      "open_time": "17:00",
      "close_time": "22:00"
    },
    {
      "day_of_week": 5,
      "open_time": "18:00",
      "close_time": "02:00"
    }
  ]
}
```

#### Field Validation Rules:
* `name`: `required|string|max:255`
* `cuisine_type`: `nullable|string|max:100`
* `address`: `nullable|string|max:255`
* `rating`: `nullable|numeric|min:0|max:5`
* `description`: `nullable|string|max:2000` *(Security Hardening: anti DoS buffer)*
* `schedules`: `nullable|array`
* `schedules.*.day_of_week`: `required_with:schedules|integer|between:1,7`
* `schedules.*.open_time`: `required_with:schedules|regex:/^([01]?\d|2[0-3]):[0-5]\d(:[0-5]\d)?$/`
* `schedules.*.close_time`: `required_with:schedules|regex:/^([01]?\d|2[0-3]):[0-5]\d(:[0-5]\d)?$/`
* **Custom Invariant:** `open_time <> close_time` ditolak dengan kode `422`.

#### Contoh Response (`201 Created`):
Mengembalikan entity restoran yang baru terbuat beserta calculated metadata dan status `201`.

---

### 3.4 PUT `/api/restaurants/{id}` — Update Restaurant (Admin)
Memperbarui atribut profil restoran dan melakukan sinkronisasi jadwal secara idempoten.

#### Semantik Penggantian Jadwal (*Atomic Schedule Replacement*):
* Jika properti `schedules` dikirimkan (berisi 1 atau lebih item), seluruh jadwal lama dihapus dan digantikan oleh array baru di dalam satu **Database Transaction** (`DB::transaction`).
* Jika `schedules: []` dikirimkan, seluruh jadwal dihapus (restoran menjadi libur/tutup permanen sepanjang minggu).
* Jika properti `schedules` tidak dikirimkan, shift jadwal yang ada dipertahankan tanpa perubahan.
* Properti `spans_next_day` dihitung secara independen di sisi backend (`close_time < open_time`).

---

### 3.5 DELETE `/api/restaurants/{id}` — Delete Restaurant (Admin)
Menghapus restoran dari sistem. Foreign key dengan aksi `ON DELETE CASCADE` akan menghapus seluruh data shift terkait secara otomatis di level database PostgreSQL.

#### Contoh Response (`200 OK`):
```json
{
  "message": "Restaurant deleted successfully"
}
```

---

### 3.6 GET `/api/stats` — Operational Dashboard Aggregates
Mengembalikan statistik direktori secara real-time.

#### Contoh Response (`200 OK`):
```json
{
  "total_restaurants": 51,
  "open_now_count": 36,
  "cuisines_count": 22,
  "current_day": "Sunday",
  "current_time": "18:45"
}
```

---

### 3.7 POST `/api/auth/login` — Admin Authentication
Melakukan validasi kredensial pengguna dan menerbitkan personal access token berbasis Laravel Sanctum.

#### Request Body:
```json
{
  "email": "admin@goers.co",
  "password": "password"
}
```

#### Contoh Response (`200 OK`):
```json
{
  "message": "Signed in successfully",
  "user": {
    "id": 1,
    "name": "Goers Admin",
    "email": "admin@goers.co"
  },
  "token": "1|qWfN0H72X...sha256_bearer_token..."
}
```
*(Token memiliki masa kedaluwarsa 7 hari sesuai pengaturan keamanan `SANCTUM_TOKEN_EXPIRATION`)*.
