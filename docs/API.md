# MJCC REST API v1

Public API untuk aplikasi **MOROWALI JUARA COMMAND CENTER (MJCC)** versi Flutter (Android). Semua endpoint berada di bawah prefix `/api/v1` kecuali auth (termasuk `/api/v1/login`).

- **Base URL (production):** `https://your-domain.com/api/v1`
- **Base URL (lokal/dev):** `http://10.0.2.2:8000/api/v1` (dari emulator Android). Ganti dengan IP LAN jika memakai perangkat fisik.
- **Format:** JSON. Kirim/terima `Content-Type: application/json`; beralur aplikasi web tetap berjalan apa adanya (tidak ada perubahan).

---

## 1. Autentikasi (Sanctum Bearer Token)

Semua endpoint kecuali `POST /api/v1/login` membutuhkan header:

```
Authorization: Bearer <token>
Accept: application/json
```

Token diperoleh dari login. **Simpan token dengan aman di perangkat** (lihat `FLUTTER_API_INTEGRATION.md`). Token tidak punya tanggal kedaluwarsa; hapus dengan logout.

### 1.1 Login

```
POST /api/v1/login
```

Body:

```json
{ "email": "admin@morowali.go.id", "password": "password" }
```

Sukses **200**:

```json
{
  "success": true,
  "message": "Login berhasil",
  "data": {
    "token": "1|abc123...",
    "user": {
      "id": 1,
      "name": "Admin MJCC",
      "email": "admin@morowali.go.id",
      "role": "admin",
      "created_at": "2026-08-28T00:00:00.000000Z",
      "updated_at": "2026-08-28T00:00:00.000000Z"
    }
  }
}
```

- Salah kredensial → **422** `{ "success": false, "message": "Validasi gagal", "errors": { "email": ["Kredensial yang dimasukkan tidak cocok dengan data kami."] } }`
- **Rate limit 5 percobaan/menit** per email+IP → **429**.
- Login/logout otomatis tercatat di audit log.

### 1.2 Logout

```
POST /api/v1/logout
```

Membatalkan token saat ini. Sukses **200**: `{ "success": true, "message": "Logout berhasil", "data": null }`.

### 1.3 Profil (me)

```
GET /api/v1/me
```

Mengembalikan user yang terautentikasi (bentuk sama dengan `user` di login). Bergerak dengan pola resource `UserResource`.

---

## 2. Envelope respons

Semua respons mengikuti bentuk konsisten:

**Sukses (tunggal):**
```json
{ "success": true, "message": "Data berhasil diambil", "data": { ... } }
```

**Sukses (daftar berpaginasi):**
```json
{
  "success": true,
  "message": "Data berhasil diambil",
  "data": [ ... ],
  "meta": { "current_page": 1, "last_page": 5, "per_page": 20, "total": 96 }
}
```

**Error:**
```json
{ "success": false, "message": "...", "errors": null }
```

Kode status dan pesan standar:

| Status | Message | Kapan |
|--------|---------|-------|
| 200 | bervariasi | sukses |
| 201 | bervariasi | create |
| 401 | `Anda belum terautentikasi.` | token hilang/rusak |
| 403 | `Anda tidak memiliki akses.` | tidak punya izin |
| 404 | `Data tidak ditemukan.` | resource tidak ada / route tidak ada |
| 422 | `Validasi gagal` + `errors` | validasi input gagal |
| 429 | `Too Many Requests` | rate limit login |
| 500 | `Terjadi kesalahan pada server.` | error server (tanpa debug) |

---

## 3. Role & otorisasi

Role (`user.role`): **`admin`**, **`operator`**, **`viewer`** (semua tercatat di `user.role`).

| Akses | admin | operator | viewer |
|-------|:---:|:---:|:---:|
| Lihat data (semua modul, dashboard, map) | ✅ | ✅ | ✅ |
| CRUD data operasional (sekolah, polsek, tipkamtikmas, poskamling, pasar, faskes, kelurahan, subjek) | ✅ | ✅ | ❌ |
| Restore soft-deleted | ✅ | ✅ | ❌ |
| Kelola user + kecamatan + audit log | ✅ | ❌ | ❌ |
| Force-delete permanent | ✅ | ❌ | ❌ |

- `viewer` = read-only → semua operasi penulisan mengembalikan **403**.
- `operator` tidak bisa menyentuh users/kecamatan/audit/force-delete.
- Admin tidak bisa menghapus (soft-delete) dirinya sendiri.
- `kecamatan` (master) hanya untuk admin: operasi tulis oleh operator/viewer → **403**.

> Otorisasi dipakai ulang dari `App\Support\Access` dan `App\Policies\*` yang sama seperti aplikasi web — **tidak ada logika duplikat**, jadi perilaku selalu konsisten.

---

## 4. Filter, pencarian, urutan & paginasi

Endpoint index (resource) mendukung parameter query bersama:

| Param | Deskripsi | Contoh |
|-------|-----------|--------|
| `per_page` | Jumlah item per halaman (1–100, default 20) | `?per_page=50` |
| `page` | Nomor halaman | `?page=2` |
| `search` | Pencarian teks (kolom `searchable` per resource) | `?search=bungku` |
| `sort` | Kolom pengurutan (whitelist per resource) | `?sort=name` |
| `sort_direction` | `asc` (default) / `desc` | `?sort_direction=desc` |
| *filter* | Kolom yang difilter `=` (whitelist, mis. `kecamatan_id`) | `?kecamatan_id=3` |

Kolom yang tersedia bergantung pada resource (didefinisikan di controller terkait).

---

## 5. Dashboard

```
GET /api/v1/dashboard
GET /api/v1/dashboard/education
GET /api/v1/dashboard/security
GET /api/v1/dashboard/health
```

Semua menerima query `?kecamatan_id={id}` untuk cakupan per kecamatan.

- `/dashboard` → agregat lintas sektor: `stats` (`total_sekolah`, `total_siswa`, `total_guru`, `kecamatan`, `kelurahan`), `comparison`, `infra_composition`, `student_chart`, `health_workforce_chart`, `top_schools`, `top_poskamling`, `top_health`, `alerts` (`critical`, `warning`, `info`).
- `/dashboard/education` → `statistics` (`total_sd`, `total_smp`, `siswa_laki`, `siswa_perempuan`, `guru`, `kelas`, `mapel`), `student_per_kecamatan`, `teacher_ratio`, `facility_progress`, `table`.
- `/dashboard/security` → `statistics` (`kelurahan`, `polsek`, `tipkamtikmas`, `poskamling`, `pasar`), `compare_chart`, `poskamling_distribution`, `kelurahan_per_kecamatan`, `polseks`.
- `/dashboard/health` → `statistics` (`puskesmas`, `pustu`, `rs`, `posyandu`, `dokter`, `perawat`, `bidan`), `workforce_per_kecamatan`, `facility_proportion`, `capacity_per_kecamatan`, `table`.

Data dihitung ulang dari `App\Services\*DashboardService` (cache 60 detik). Semua mengembalikan `data` berisi struktur di atas.

---

## 6. Peta (Map)

```
GET /api/v1/maps
```

Query opsional:

| Param | Nilai |
|-------|-------|
| `kecamatan_id` | batasi ke satu kecamatan |
| `sector` | `pendidikan` / `ketertiban` / `kesehatan` (kosong = semua) |
| `type` | `school`, `polsek`, `tipkamtikmas`, `poskamling`, `market`, `health_facility` (kosong = semua) |

Respons:

```json
{
  "success": true,
  "message": "Data berhasil diambil",
  "data": {
    "markers": [
      {
        "id": 1,
        "type": "school",
        "sector": "pendidikan",
        "name": "SDN 1 Bungku",
        "latitude": -2.555, "longitude": 122.0,
        "status": "aktif",
        "kecamatan": "Bungku Tengah",
        "kelurahan": "Bungi"
      }
    ],
    "kecamatans": [ { "id": 1, "name": "Bungku Tengah" } ]
  }
}
```

> Hanya data dengan koordinat (`latitude` & `longitude` terisi) yang muncul sebagai marker.

---

## 7. Resource CRUD

Berikut pola umum untuk setiap resource. `PUT|PATCH` mendukung partial; `DELETE` = soft delete (kecuali Polsek & Subjek yang memang tidak menggunakan SoftDeletes).

Trash & force-delete **hanya tersedia** untuk model dengan SoftDeletes: `schools`, `health/facilities`, `kecamatans`, `kelurahans`, `markets`, `poskamlings`, `tipkamtikmas`.

Pola:

| Method | Endpoint | Keterangan |
|--------|----------|------------|
| GET | `/resource` | index (paginasi) |
| POST | `/resource` | create → **201** |
| GET | `/resource/{id}` | show |
| PUT/PATCH | `/resource/{id}` | update |
| DELETE | `/resource/{id}` | soft delete |
| GET | `/resource/trash` | daftar soft-deleted (hanya SoftDeletes) |
| PUT | `/resource/{id}/restore` | restore (operator/admin) |
| DELETE | `/resource/{id}/force` | hapus permanen (admin) |

Daftar resource & `{param}` yang harus dipakai Flutter pada URL:

| Resource | Route path | Route param |
|----------|-----------|-------------|
| School | `/api/v1/schools` | `school` |
| Health Facility | `/api/v1/health/facilities` | `health_facility` |
| Kecamatan | `/api/v1/kecamatans` | `kecamatan` |
| Kelurahan | `/api/v1/kelurahans` | `kelurahan` |
| Market | `/api/v1/markets` | `market` |
| Poskamling | `/api/v1/poskamlings` | `poskamling` |
| Tipkamtikmas | `/api/v1/tipkamtikmas` | `tipkamtikma` |
| Polsek | `/api/v1/polseks` | `polsek` |
| Subject | `/api/v1/subjects` | `subject` |
| User | `/api/v1/users` | `user` |

> Perhatikan: untuk `tipkamtikmas`, route param di URL adalah **`tipkamtikma`** (tunggal). Untuk faskes pakai **`health_facility`** (`/api/v1/health/facilities/{health_facility}`).

### Field input (create/update)

Field divalidasi oleh Form Request yang sama dengan aplikasi web. Ringkasan per resource:

**School** (`POST/PUT /schools`) — `name` (required, unique), `school_type` (in: SD,SMP,SMA,SMK), `kecamatan_id` (exists), `kelurahan_id` (nullable, exists), `students_male`/`students_female` (int ≥0), `teachers` (int), `classes` (int), `capacity` (int), `latitude`/`longitude` (nullable, latitude antara -90..90, longitude -180..180), `is_active`, `subjects` (array of subject ids, opsional). Respons resource menyertakan `total_students`, `subjects`, persentase.

**Health Facility** (`POST/PUT /health/facilities`) — `name`, `facility_type` (in: Puskesmas,Pustu,Rumah Sakit,Posyandu,klinik/lainnya), `kecamatan_id`, `kelurahan_id`, `doctors`/`nurses`/`midwives` (int), `latitude`, `longitude`, `status`.

**Kecamatan** (`POST/PUT /kecamatans`, admin-only writes) — `name` (unique), `is_active`. Sub-endpoint `GET /kecamatans/{kecamatan}/kelurahans` mengembalikan kelurahan milik kecamatan itu.

**Kelurahan** — `name`, `kecamatan_id`, `is_active`.

**Market / Poskamling / Tipkamtikmas** — `name`/`title`, `kecamatan_id`, `kelurahan_id`, `status`/`is_active`, `latitude`, `longitude`.

**Polsek** (tanpa trash) — `name`, `kecamatan_id`, `address`, `head`, `phone`, `latitude`, `longitude`, `status`.

**Subject** (tanpa trash) — `name`, `code` (unique). Resource berisi `id`, `name`, `code`.

**User** (admin-only) — `name`, `email` (unique), `role` (in: admin,operator,viewer), `password` + `password_confirmation` pada create (opsional pada update). Perubahan role dicatat di audit log. Admin tidak bisa menghapus akunnya sendiri.

---

## 8. Profil & kata sandi

```
GET  /api/v1/profile
PUT  /api/v1/profile
PUT  /api/v1/profile/password
```

- `PUT /profile` — body `{ "name": "...", "email": "..." }`. Email duplikat → **422** (`errors.email`).
- `PUT /profile/password` — body `{ "current_password": "...", "password": "...", "password_confirmation": "..." }`. `current_password` salah → **422** `errors.current_password`. Sukses: `{ "success": true, "message": "Kata sandi berhasil diperbarui.", "data": null }`.

---

## 9. Audit log (admin-only)

```
GET /api/v1/audit
```

Query: `search` (cari deskripsi), `user` (user_id), `action` (login, logout, create, update, delete, restore, force_delete, role_change), `resource` (mis. `School`), `from` / `to` (rentang tanggal), `per_page`.

Non-admin → **403**. Setiap mutasi data melalui API otomatis menghasilkan entri audit (create/update/delete). **Password tidak pernah tersimpan di audit log.**

---

## 10. Daftar lengkap endpoint

| Method | URI | Auth | Peran |
|--------|-----|------|-------|
| POST | `/api/v1/login` | throttle:login | publik |
| POST | `/api/v1/logout` | sanctum | semua |
| GET | `/api/v1/me` | sanctum | semua |
| GET | `/api/v1/profile` | sanctum | semua |
| PUT | `/api/v1/profile` | sanctum | semua |
| PUT | `/api/v1/profile/password` | sanctum | semua |
| GET | `/api/v1/dashboard` | sanctum | semua |
| GET | `/api/v1/dashboard/education` | sanctum | semua |
| GET | `/api/v1/dashboard/security` | sanctum | semua |
| GET | `/api/v1/dashboard/health` | sanctum | semua |
| GET | `/api/v1/maps` | sanctum | semua |
| GET | `/api/v1/audit` | sanctum | admin |
| CRUD | `/api/v1/schools` (+trash/restore/force) | sanctum | lihat:semua, tulis:op/admin |
| CRUD | `/api/v1/polseks` | sanctum | lihat:semua, tulis:op/admin |
| CRUD | `/api/v1/tipkamtikmas` (+trash/restore/force) | sanctum | lihat:semua, tulis:op/admin |
| CRUD | `/api/v1/poskamlings` (+trash/restore/force) | sanctum | lihat:semua, tulis:op/admin |
| CRUD | `/api/v1/markets` (+trash/restore/force) | sanctum | lihat:semua, tulis:op/admin |
| CRUD | `/api/v1/health/facilities` (+trash/restore/force) | sanctum | lihat:semua, tulis:op/admin |
| CRUD | `/api/v1/kecamatans` (+trash/restore/force, `kelurahans`) | sanctum | lihat:semua, tulis:admin |
| CRUD | `/api/v1/kelurahans` (+trash/restore/force) | sanctum | lihat:semua, tulis:op/admin |
| CRUD | `/api/v1/subjects` | sanctum | lihat:semua, tulis:op/admin |
| CRUD | `/api/v1/users` | sanctum | admin |

---

## 11. Contoh alur Flutter

1. **Login** `POST /api/v1/login` → simpan `token`.
2. Setiap request kirim `Authorization: Bearer <token>`.
3. Saat **401** → token kedaluwarsa/di-revoke → minta login ulang.
4. Saat **403** → sembunyikan/matikan aksi tulis sesuai role.
5. **Logout** `POST /api/v1/logout` → hapus token lokal.

Panduan implementasi lengkap (Dio, secure storage, model JSON, paginasi, role) ada di `docs/FLUTTER_API_INTEGRATION.md`.
