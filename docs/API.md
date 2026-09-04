# MJCC REST API v1

Public API untuk aplikasi **MOROWALI JUARA COMMAND CENTER (MJCC)** versi Flutter (Android). Semua endpoint berada di bawah prefix `/api/v1` kecuali auth (termasuk `/api/v1/login`).

- **Base URL (production):** `https://your-domain.com/api/v1`
- **Base URL (lokal/dev):** `http://10.0.2.2:8000/api/v1` (dari emulator Android). Ganti dengan IP LAN jika memakai perangkat fisik.
- **Format:** JSON. Kirim/terima `Content-Type: application/json`; beralur aplikasi web tetap berjalan apa adanya (tidak ada perubahan).

---

## 1. Autentikasi (Sanctum Bearer Token)

Semua endpoint **kecuali** `POST /api/v1/login`, `POST /api/v1/register`, `POST /api/v1/email/verify`, dan `POST /api/v1/email/verification/resend` membutuhkan header:

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
- **Email belum diverifikasi** → **403** `{ "success": false, "message": "Email Anda belum diverifikasi. ...", "data": null, "errors": null, "extra": { "verification_required": true } }` — tidak ada token diterbitkan. Flutter mengarahkan ke layar verifikasi email.
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

### 1.4 Registrasi publik (viewer)

**Publik — tidak butuh token.**

```
POST /api/v1/register
```

Body:

```json
{
  "name": "Budi Santoso",
  "email": "budi@example.com",
  "password": "rahasia123",
  "password_confirmation": "rahasia123"
}
```

- **Role selalu diset `viewer` oleh backend** — payload klien tidak pernah memengaruhi role.
- Sukses **201** (bentuk sama untuk akun baru maupun email yang sudah terdaftar → mencegah enumerasi akun):

```json
{
  "success": true,
  "message": "Registrasi berhasil. Silakan verifikasi email Anda dengan kode yang telah dikirim.",
  "data": { "email_masked": "b***@example.com" }
}
```

- Registrasi tidak menerbitkan token; lanjut ke verifikasi email dulu.
- `name` minimal 3 karakter; `password` minimal 8 karakter (aturan `Password::defaults()`), wajib `confirmed`.
- **Rate limit 5 request/menit** per IP → **429**.
- Email sudah terdaftar & terverifikasi → tetap **201** tapi **tidak** mengirim email lagi. Email terdaftar belum terverifikasi → kode baru dikirim ulang.

### 1.5 Verifikasi kode email

**Publik — tidak butuh token.**

```
POST /api/v1/email/verify
```

Body:

```json
{ "email": "budi@example.com", "code": "123456" }
```

- Sukses **200**: `{ "success": true, "message": "Email berhasil diverifikasi. Silakan masuk menggunakan akun Anda.", "data": null }`.
- Email sudah diverifikasi → **200** dengan pesan `"Email sudah diverifikasi. Silakan masuk menggunakan akun Anda."` (idempoten).
- Kode salah/kedaluwarsa/berlebih percobaan (5x) → **422** `errors.code` dengan pesan generik `"Kode verifikasi tidak valid."` — tidak membedakan email terdaftar/tdk.
- Kode berlaku **10 menit**, sekali pakai (langsung tidak aktif setelah berhasil).
- **Rate limit 10 request/menit** per email+IP → **429**.
- Kode verifikasi **tidak pernah** dikembalikan dalam respons.

### 1.6 Kirim ulang kode verifikasi

**Publik — tidak butuh token.**

```
POST /api/v1/email/verification/resend
```

Body:

```json
{ "email": "budi@example.com" }
```

- Sukses **200**: `{ "success": true, "message": "Kode verifikasi telah dikirim.", "data": null }` — identik untuk email terdaftar maupun tidak (anti-enumerasi).
- **Cooldown 60 detik** antar kirim → **422** dengan pesan `errors.email` berisi sisa waktu tunggu.
- **Rate limit 3 request/menit** per email+IP → **429**.

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
| 403 | `Anda tidak memiliki akses.` / `Email Anda belum diverifikasi. ...` | tidak punya izin, atau email belum diverifikasi (`extra.verification_required: true`) |
| 404 | `Data tidak ditemukan.` | resource tidak ada / route tidak ada |
| 422 | `Validasi gagal` + `errors` | validasi input gagal (termasuk kode verifikasi salah / cooldown kirim ulang) |
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

## 8. Data Eksternal (Crawler) — read-only

Endpoints untuk membaca data yang di-crawl dari sumber data pemerintah eksternal (DAPO, ATS, PIHPS/BI, BPS, Kemenkes). **Semua read-only** — tersedia untuk setiap role yang sudah login (`viewer`, `operator`, `admin`).

```
GET /api/v1/crawler                                # daftar sumber crawl + statistik agregat
GET /api/v1/crawler/sources/{slug}                 # detail satu sumber
GET /api/v1/crawler/sources/{slug}/runs            # riwayat crawl per sumber (paginasi)
GET /api/v1/crawler/runs                           # semua riwayat crawl (paginasi)
GET /api/v1/crawler/runs/{runId}                   # detail run + errors + records (50)
GET /api/v1/crawler/records                        # daftar rekaman (filter + search + paginasi)
GET /api/v1/crawler/records/{recordId}             # detail satu rekaman
```

Catatan parameter:
- `{slug}` adalah **slug** sumber, bukan ID — mis. `dapo`, `sp2kp`, `ats`, `bps`, `kesehatan`.
- `{runId}` / `{recordId}` adalah ID numerik.

### 8.1 Sumber (`crawler`)

`GET /api/v1/crawler` → daftar sumber dengan `runs_count`, `records_count`, dan `last_run_at` (timestamp run terakhir). `GET /api/v1/crawler/sources/{slug}` → satu sumber dengan statistik sama.

```json
{
  "success": true,
  "message": "Data sumber crawling berhasil diambil.",
  "data": [
    {
      "id": 1,
      "name": "DAPO",
      "slug": "dapo",
      "base_url": "https://dapo.kemdikbud.go.id",
      "source_type": "sekolah",
      "is_active": true,
      "configuration": null,
      "runs_count": 12,
      "records_count": 340,
      "created_at": "...",
      "updated_at": "..."
    }
  ]
}
```

### 8.2 Riwayat run (`runs`)

`GET /api/v1/crawler/runs` — semua run, disortir `started_at` desc (terbaru dulu).

Query:
| Param | Deskripsi |
|-------|-----------|
| `status` | filter per status (`success`, `failed`, `partial`, ...) |
| `per_page` | 1–100 (default 20) |
| `page` | nomor halaman |

`GET /api/v1/crawler/runs/{runId}` → detail run termasuk `errors` (daftar error) dan `records` (hingga 50 terbaru).

### 8.3 Rekaman (`records`)

`GET /api/v1/crawler/records` — daftar rekaman yang di-crawl.

Query:
| Param | Deskripsi | Contoh |
|-------|-----------|--------|
| `source` | slug sumber | `?source=dapo` |
| `record_type` | tipe rekaman | `?record_type=sekolah` |
| `kabupaten_code` | kode kabupaten | `?kabupaten_code=7206` |
| `kecamatan_code` | kode kecamatan | `?kecamatan_code=...` |
| `search` | cari `name` / `external_id` | `?search=bungku` |
| `sort` | `name`, `record_type`, `kabupaten_code`, `last_seen_at`, `created_at` | `?sort=name` |
| `sort_direction` | `asc` / `desc` (default `desc`) | `?sort_direction=asc` |
| `per_page` / `page` | paginasi | `?per_page=50` |

Daftar rekaman berpaginasi mengembalikan `data` + `meta` (`current_page`, `last_page`, `per_page`, `total`).

```json
{
  "success": true,
  "message": "Data rekaman crawling berhasil diambil.",
  "data": [
    {
      "id": 1,
      "crawl_source_id": 1,
      "source": { "id": 1, "name": "DAPO", "slug": "dapo" },
      "external_id": "sekolah-123",
      "record_type": "sekolah",
      "name": "SDN 1 Bungku",
      "province_code": "72",
      "kabupaten_code": "7206",
      "kabupaten_name": "Morowali",
      "kecamatan_code": "720603",
      "kecamatan_name": "Bungku Tengah",
      "desa_code": null,
      "desa_name": null,
      "latitude": -2.555,
      "longitude": 122.0,
      "data": { },
      "source_url": "...",
      "source_updated_at": "...",
      "first_seen_at": "...",
      "last_seen_at": "...",
      "content_hash": "...",
      "created_at": "...",
      "updated_at": "..."
    }
  ],
  "meta": { "current_page": 1, "last_page": 1, "per_page": 20, "total": 1 }
}
```

Non-existing source (slug) / run / record yang tidak ditemukan → **404**.

---

## 9. Profil & kata sandi

```
GET  /api/v1/profile
PUT  /api/v1/profile
PUT  /api/v1/profile/password
```

- `PUT /profile` — body `{ "name": "...", "email": "..." }`. Email duplikat → **422** (`errors.email`).
- `PUT /profile/password` — body `{ "current_password": "...", "password": "...", "password_confirmation": "..." }`. `current_password` salah → **422** `errors.current_password`. Sukses: `{ "success": true, "message": "Kata sandi berhasil diperbarui.", "data": null }`.

---

## 10. Audit log (admin-only)

```
GET /api/v1/audit
```

Query: `search` (cari deskripsi), `user` (user_id), `action` (login, logout, create, update, delete, restore, force_delete, role_change), `resource` (mis. `School`), `from` / `to` (rentang tanggal), `per_page`.

Non-admin → **403**. Setiap mutasi data melalui API otomatis menghasilkan entri audit (create/update/delete). **Password tidak pernah tersimpan di audit log.**

---

## 11. Daftar lengkap endpoint

| Method | URI | Auth | Peran |
|--------|-----|------|-------|
| POST | `/api/v1/login` | throttle:login | publik |
| POST | `/api/v1/logout` | sanctum | semua |
| GET | `/api/v1/me` | sanctum | semua |
| POST | `/api/v1/register` | throttle:register | publik |
| POST | `/api/v1/email/verify` | throttle:email_verify | publik |
| POST | `/api/v1/email/verification/resend` | throttle:email_resend | publik |
| GET | `/api/v1/profile` | sanctum | semua |
| PUT | `/api/v1/profile` | sanctum | semua |
| PUT | `/api/v1/profile/password` | sanctum | semua |
| GET | `/api/v1/dashboard` | sanctum | semua |
| GET | `/api/v1/dashboard/education` | sanctum | semua |
| GET | `/api/v1/dashboard/security` | sanctum | semua |
| GET | `/api/v1/dashboard/health` | sanctum | semua |
| GET | `/api/v1/maps` | sanctum | semua |
| GET | `/api/v1/crawler` | sanctum | semua |
| GET | `/api/v1/crawler/sources/{slug}` | sanctum | semua |
| GET | `/api/v1/crawler/sources/{slug}/runs` | sanctum | semua |
| GET | `/api/v1/crawler/runs` | sanctum | semua |
| GET | `/api/v1/crawler/runs/{runId}` | sanctum | semua |
| GET | `/api/v1/crawler/records` | sanctum | semua |
| GET | `/api/v1/crawler/records/{recordId}` | sanctum | semua |
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

## 12. Contoh alur Flutter

**Login & sesi:**

1. **Login** `POST /api/v1/login` → simpan `token`.
2. Setiap request kirim `Authorization: Bearer <token>`.
3. Saat **401** → token kedaluwarsa/di-revoke → minta login ulang.
4. Saat **403** dengan `extra.verification_required === true` → arahkan ke **layar verifikasi email** (email belum diverifikasi).
5. Saat **403** lainnya → sembunyikan/matikan aksi tulis sesuai role.
6. **Logout** `POST /api/v1/logout` → hapus token lokal.

**Registrasi publik & verifikasi email:**

1. `POST /api/v1/register` → sukses (201) → tampilkan email termask (opsional) → arahkan ke **layar verifikasi email**.
2. `POST /api/v1/email/verify` (kode 6 digit) → sukses → kembali ke login.
3. Gagal → tampilkan pesan `errors` (kode salah / cooldown). Tombol **"Kirim ulang kode"** memanggil `POST /api/v1/email/verification/resend` (tunduk pada cooldown 60 detik di sisi klien maupun server).

Panduan implementasi lengkap (Dio, secure storage, model JSON, paginasi, role) ada di `docs/FLUTTER_API_INTEGRATION.md`.
