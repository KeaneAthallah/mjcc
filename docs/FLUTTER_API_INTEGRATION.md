# Integrasi Flutter dengan API MJCC

Panduan menghubungkan aplikasi **Flutter (Android)** ke REST API `/api/v1` pada project Laravel ini. Baca `docs/API.md` untuk detail endpoint, envelope, roll, dan kode status.

---

## 1. Dependensi yang disarankan

Tambah ke `pubspec.yaml`:

- [`dio`](https://pub.dev/packages/dio) — HTTP client (interceptor, error handling).
- [`flutter_secure_storage`](https://pub.dev/packages/flutter_secure_storage) — simpan token dengan aman (Keychain/Keystore).
- [`google_maps_flutter`](https://pub.dev/packages/google_maps_flutter) — peta dari endpoint `/maps` (opsional).
- [`provider`](https://pub.dev/packages/provider) atau `riverpod` — state management (pilih sesuai preferensi/konvensi tim).

> Gunakan `flutter_secure_storage`, **bukan** `shared_preferences`, untuk token — jangan pernah menyimpan bearer token dalam bentuk teks biasa.

```yaml
dependencies:
  dio: ^5.7.0
  flutter_secure_storage: ^9.2.2
  google_maps_flutter: ^2.9.0
  provider: ^6.1.2
```

---

## 2. Bonus: base URL

Untuk memperbanyak koneksi antara **file koneksi** dan **env**, buat file `lib/core/config.dart`:

```dart
class AppConfig {
  // Emulator: http://10.0.2.2:8000  |  Device fisik: http://<IP-LAN-mesin-server>:8000
  static const String baseUrl = 'http://10.0.2.2:8000';
  static const String apiPrefix = '/api/v1';
}
```

Samakan dengan `APP_URL` di `.env` Laravel bila perlu.

---

## 3. HTTP client + interceptor token

`lib/core/network/api_client.dart`:

```dart
import 'package:dio/dio.dart';
import 'package:flutter_secure_storage/flutter_secure_storage.dart';
import '../config.dart';

class ApiClient {
  ApiClient._();
  static final ApiClient instance = ApiClient._();

  static const _storage = FlutterSecureStorage();
  static const _tokenKey = 'auth_token';

  late final Dio dio;

  Future<void> init() async {
    dio = Dio(
      BaseOptions(
        baseUrl: AppConfig.baseUrl + AppConfig.apiPrefix,
        connectTimeout: const Duration(seconds: 15),
        receiveTimeout: const Duration(seconds: 15),
        headers: {'Accept': 'application/json'},
      ),
    );
    dio.interceptors.add(InterceptorsWrapper(
      onRequest: (options, handler) async {
        final token = await _storage.read(key: _tokenKey);
        if (token != null) {
          options.headers['Authorization'] = 'Bearer $token';
        }
        handler.next(options);
      },
      onError: (e, handler) async {
        if (e.response?.statusCode == 401) {
          // Token tidak valid / dicabut (logout server).
        }
        handler.next(e);
      },
    ));
  }

  Future<void> saveToken(String token) => _storage.write(key: _tokenKey, value: token);
  Future<String?> readToken() => _storage.read(key: _tokenKey);
  Future<void> clearToken() => _storage.delete(key: _tokenKey);
}
```

---

## 4. Model respons (envelope)

Envelope konsisten `{ success, message, data, meta? , errors? }`. Buat model generik:

`lib/core/network/api_response.dart`:

```dart
class ApiResponse<T> {
  final bool success;
  final String message;
  final T? data;
  final Map<String, dynamic>? meta;
  final Map<String, dynamic>? errors;

  ApiResponse({
    required this.success,
    required this.message,
    this.data,
    this.meta,
    this.errors,
  });

  factory ApiResponse.fromJson(Map<String, dynamic> json, T? data) => ApiResponse(
        success: json['success'] as bool? ?? false,
        message: json['message'] as String? ?? '',
        data: data,
        meta: json['meta'] as Map<String, dynamic>?,
        errors: json['errors'] as Map<String, dynamic>?,
      );
}
```

**Paginasi:** `meta` berisi `current_page`, `last_page`, `per_page`, `total`. Untuk daftar:

`lib/core/network/paginated.dart`:

```dart
class Paginated<T> {
  final List<T> items;
  final int currentPage, lastPage, perPage, total;
  Paginated(this.items, this.currentPage, this.lastPage, this.perPage, this.total);
}
```

---

## 5. Login / logout / profil

`lib/features/auth/auth_repository.dart`:

```dart
class AuthRepository {
  final Dio _dio = ApiClient.instance.dio;
  final ApiClient _client = ApiClient.instance;

  Future<User> login(String email, String password) async {
    final res = await _dio.post('/login', data: {'email': email, 'password': password});
    final body = res.data as Map<String, dynamic>;
    final data = body['data'] as Map<String, dynamic>;
    await _client.saveToken(data['token'] as String);
    return User.fromJson(data['user'] as Map<String, dynamic>);
  }

  Future<void> logout() async {
    await _dio.post('/logout');   // membatalkan token di server
    await _client.clearToken();   // hapus lokal
  }

  Future<User> me() async {
    final res = await _dio.get('/me');
    final data = (res.data as Map<String, dynamic>)['data'] as Map<String, dynamic>;
    return User.fromJson(data);
  }
}
```

Login gagal → `DioException` dengan `.response?.statusCode == 422`; pesan di `errors.email`. Rate limit → status **429**.

---

## 6. Sesi & penanganan error

- **`401`** → token hilang/cabut. Paksa ke halaman login dan kosongkan secure storage.
- **`403`** → user tidak punya akses (lihat role). Nonaktifkan tombol/tindakan tulis.
- **`422`** → validasi gagal; tampilkan `errors` per field.
- **`404`** → data tidak ditemukan.
- **`429`** → tunda dan coba lagi (login).

> Jangan pernah menampilkan `token` mentah ke UI. Simpan rahasia dan hanya pakai pada header.

---

## 7. Menangani role

`role` ada di objek `User` (`admin`, `operator`, `viewer`). Simpan di state aplikasi setelah login.

```dart
enum UserRole {
  admin, operator, viewer;

  bool get canWrite => this != UserRole.viewer;
  bool get isAdmin => this == UserRole.admin;
}
```

- **viewer** → mode hanya baca: sembunyikan form/tombol simpan/hapus.
- **operator** → boleh CRUD data operasional, tapi tidak bisa kelola user/kecamatan/audit/force-delete.
- **admin** → akses penuh.

Server tetap menjadi penentu utama; UI hanya menyembunyikan aksi agar lebih ramah.

---

## 8. Contoh: daftar sekolah (paginasi)

`lib/features/schools/school.dart`:

```dart
class School {
  final int id;
  final String name;
  final String schoolType;
  final int? totalStudents;
  final String? kecamatan;
  final String? kelurahan;

  School({required this.id, required this.name, required this.schoolType,
    this.totalStudents, this.kecamatan, this.kelurahan});

  factory School.fromJson(Map<String, dynamic> j) => School(
        id: j['id'] as int,
        name: j['name'] as String,
        schoolType: j['school_type'] as String? ?? '',
        totalStudents: j['total_students'] as int?,
        kecamatan: (j['kecamatan'] as Map<String, dynamic>?)?['name'] as String?,
        kelurahan: (j['kelurahan'] as Map<String, dynamic>?)?['name'] as String?,
      );
}
```

`lib/features/schools/school_repository.dart`:

```dart
class SchoolRepository {
  final Dio _dio = ApiClient.instance.dio;

  Future<Paginated<School>> index({int? page, int? perPage, String? search}) async {
    final res = await _dio.get('/schools', queryParameters: {
      'page': page,
      'per_page': perPage,
      'search': search,
    });
    final body = res.data as Map<String, dynamic>;
    final list = (body['data'] as List)
        .map((e) => School.fromJson(e as Map<String, dynamic>)).toList();
    final meta = body['meta'] as Map<String, dynamic>;
    return Paginated<School>(
      list,
      meta['current_page'] as int,
      meta['last_page'] as int,
      meta['per_page'] as int,
      meta['total'] as int,
    );
  }

  Future<void> destroy(int id) => _dio.delete('/schools/$id');
}
```

---

## 9. Dashboard & peta

**Dashboard:** `GET /dashboard`, `/dashboard/education`, `/dashboard/security`, `/dashboard/health` (opsional `?kecamatan_id=`). Parsing `data.statistics` dan `data.alerts` untuk halaman ringkas. `alerts` memakai sektor `pendidikan`/`kesehatan`/`ketertiban` (kondisi `baik/bagus/layak` dianggap aman).

**Peta:** `GET /maps?kecamatan_id=&sector=&type=` → `data.markers` berisi `{id, type, sector, name, latitude, longitude, status, kecamatan, kelurahan}`. Tambah `CameraPosition`/`Marker` per item:

```dart
markers.map((m) => Marker(
  markerId: MarkerId('${m['type']}-${m['id']}'),
  position: LatLng(m['latitude'] as double, m['longitude'] as double),
  infoWindow: InfoWindow(title: m['name'] as String),
));
```

Tip: sediakan filter `sector`/`type` di UI untuk mengurangi jumlah marker sekaligus.

---

## 10. Struktur `lib/` yang disarankan

```
lib/
├── main.dart
├── core/
│   ├── config.dart
│   └── network/
│       ├── api_client.dart
│       ├── api_response.dart
│       └── paginated.dart
├── features/
│   ├── auth/           # login, logout, me, profil, ganti sandi
│   ├── dashboard/      # ringkasan lintas sektor
│   ├── map/            # peta marker
│   ├── schools/
│   ├── health/
│   ├── security/       # polsek, tipkamtikmas, poskamling, pasar
│   ├── master_data/    # kecamatan, kelurahan, subjek
│   └── users/          # admin
└── shared/             # widget & utilitas bersama
```

---

## 11. Catatan server (tim Laravel)

- Pastikan `.env` `APP_ENV=production` & `APP_DEBUG=false` pada rilis agar error 500 tidak bocor ke klien.
- Token Sanctum tidak kedaluwarsa otomatis; gunakan `DELETE /logout` atau cabut token di panel admin bila perlu.
- Aplikasi web dan API memakai database & otorisasi yang sama — perubahan data dari Flutter langsung tercermin di web (dan tercatat di audit log).
