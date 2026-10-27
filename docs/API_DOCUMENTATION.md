# REST API Documentation - Goers RestoFinder

Base URL: `http://localhost:8000/api` (or proxied via Vite `http://localhost:5173/api`)

Headers:
```http
Accept: application/json
Content-Type: application/json
Authorization: Bearer <sanctum_token> (Optional for public, required for protected endpoints)
```

---

## 1. Endpoints Summary

| Method | Endpoint | Access | Deskripsi |
| :--- | :--- | :--- | :--- |
| `GET` | `/restaurants` | Public | Menampilkan list semua restoran dengan filter nama, tanggal, jam |
| `GET` | `/restaurants/{id}` | Public | Menampilkan detail 1 restoran beserta jadwal lengkap |
| `POST` | `/restaurants` | Admin | Menambahkan data restoran baru (mendukung raw schedule parser) |
| `PUT` | `/restaurants/{id}` | Admin | Mengupdate data restoran & jam operasional |
| `DELETE`| `/restaurants/{id}` | Admin | Menghapus data restoran |
| `GET` | `/stats` | Public | Statistik agregat (total restoran, buka saat ini, total cuisine) |
| `POST` | `/auth/login` | Public | Login akun admin untuk mendapatkan token Bearer Sanctum |
| `POST` | `/auth/logout` | Protected | Logout & revoke personal access token |
| `GET` | `/auth/me` | Protected | Cek profil admin yang sedang login |

---

## 2. Detailed Endpoints Specification

### 2.1 GET `/api/restaurants`
Mengambil daftar restoran dengan kapabilitas filter pencarian, tanggal, jam, status buka, dan pagination.

#### Query Parameters:
| Parameter | Tipe | Contoh | Deskripsi |
| :--- | :--- | :--- | :--- |
| `name` / `q` / `search` | string | `Cheesecake` | Filter nama restoran (case-insensitive partial search). |
| `date` | string (YYYY-MM-DD) | `2026-10-27` | Filter restoran yang beroperasi pada tanggal tersebut (dikonversi ke day of week). |
| `time` | string (HH:mm) | `14:30` | Filter restoran yang buka pada jam tertentu. |
| `open_now` | boolean | `true` | Filter restoran yang sedang buka detik ini. |
| `cuisine` | string | `Japanese` | Filter kategori masakan. |
| `sort_by` | string | `name` / `rating` | Field pengurutan (default: `name`). |
| `sort_order` | string | `asc` / `desc` | Arah pengurutan (default: `asc`). |
| `page` | integer | `1` | Nomor halaman pagination. |
| `per_page` | integer | `12` | Jumlah per halaman (`-1` untuk mengambil semua). |

#### Contoh Request:
```bash
curl -X GET "http://localhost:8000/api/restaurants?date=2026-10-27&time=01:00&name=Naan"
```

#### Contoh Response:
```json
{
  "data": [
    {
      "id": 26,
      "name": "Naan 'N' Curry",
      "raw_schedule": "Mon-Sun 11 am - 4 am",
      "formatted_schedule": "Mon-Sun 11 am - 4 am",
      "cuisine_type": "Pakistani / Indian",
      "address": "478 O'Farrell St, San Francisco, CA",
      "rating": 4.6,
      "description": "Authentic Pakistani / Indian restaurant offering delicious dishes.",
      "is_open_now": true,
      "schedules": [
        {
          "id": 182,
          "day_of_week": 1,
          "day_name": "Monday",
          "day_name_short": "Mon",
          "open_time": "11:00",
          "close_time": "04:00",
          "spans_next_day": true
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

### 2.2 POST `/api/restaurants`
Admin menambahkan data restoran baru. Sistem secara otomatis mem-parsing string jadwal natural language (`raw_schedule`) ke dalam record terstruktur di tabel `restaurant_schedules`.

#### Request Body:
```json
{
  "name": "Goers Signature Bistro",
  "raw_schedule": "Mon-Fri 10 am - 10 pm / Sat-Sun 11 am - 12 am",
  "cuisine_type": "French Contemporary",
  "address": "Jl. Senopati No. 45, Jakarta",
  "rating": 4.9,
  "description": "Exclusive dining experience with artisanal dishes."
}
```

#### Response (Status 201 Created):
```json
{
  "data": {
    "id": 53,
    "name": "Goers Signature Bistro",
    "raw_schedule": "Mon-Fri 10 am - 10 pm / Sat-Sun 11 am - 12 am",
    "formatted_schedule": "Mon-Fri 10 am - 10 pm / Sat-Sun 11 am - 12 am",
    "cuisine_type": "French Contemporary",
    "address": "Jl. Senopati No. 45, Jakarta",
    "rating": 4.9,
    "description": "Exclusive dining experience with artisanal dishes.",
    "is_open_now": false,
    "schedules": [
      {
        "id": 361,
        "day_of_week": 1,
        "day_name": "Monday",
        "day_name_short": "Mon",
        "open_time": "10:00",
        "close_time": "22:00",
        "spans_next_day": false
      }
    ]
  }
}
```

---

### 2.3 PUT `/api/restaurants/{id}`
Admin memperbarui data restoran dan jadwal operasionalnya.

#### Request Body:
```json
{
  "name": "Goers Signature Bistro (Updated)",
  "raw_schedule": "Mon-Sun 9 am - 11 pm",
  "cuisine_type": "Fine Dining"
}
```

#### Response (Status 200 OK):
Mengembalikan resource restoran yang telah diperbarui beserta shifts jadwal baru.

---

### 2.4 DELETE `/api/restaurants/{id}`
Admin menghapus restoran. Menghapus data restoran sekaligus cascade delete seluruh jadwal terkait.

#### Response (Status 200 OK):
```json
{
  "message": "Restoran berhasil dihapus"
}
```

---

### 2.5 GET `/api/stats`
Mengembalikan indikator performa dan statistik operasional.

#### Response (Status 200 OK):
```json
{
  "total_restaurants": 52,
  "open_now_count": 38,
  "cuisines_count": 18,
  "current_day": "Tuesday",
  "current_time": "14:30"
}
```

---

### 2.6 POST `/api/auth/login`
Autentikasi admin untuk mendapatkan bearer token.

#### Request Body:
```json
{
  "email": "admin@goers.co",
  "password": "password"
}
```

#### Response (Status 200 OK):
```json
{
  "message": "Login berhasil",
  "user": {
    "id": 1,
    "name": "Goers Admin",
    "email": "admin@goers.co"
  },
  "token": "1|qWfN0H72X...bearer_token..."
}
```
