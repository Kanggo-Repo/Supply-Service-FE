# Supply Service FE

`Supply Service FE` adalah frontend owner untuk workspace supply. Repo ini mengelola halaman material, unit, store, store location, material recycle bin, material history restore, dan store search radius setting. Semua operasi browser user masuk ke repo ini untuk domain supply.

## Tanggung Jawab Utama

- login browser via Keycloak
- memanggil `platform-service-be` untuk identity dan navigation lintas service
- memanggil `supply-service-be` untuk semua CRUD domain supply
- merender UI donor monolith untuk:
  - material catalog dan family-specific forms
  - recycle bin material
  - store dan store location
  - units
  - radius pencarian toko
- menjaga feel sidebar dan topbar seragam dengan service lain

## Posisi Dalam Arsitektur

```text
browser
  -> supply-service-fe
    -> Keycloak
    -> platform-service-be (/api/v1/me, /api/v1/navigation)
    -> supply-service-be (owner API supply)
    -> platform-service-fe / calculation-service-fe (via cross-service navigation)
```

## Halaman dan Route Utama

### Auth

- `GET /login`
- `GET /auth/redirect`
- `GET /auth/consume`
- `POST /logout`
- `GET /access-pending`

### Profile

- `GET /profile`

Profile tidak di-owner oleh supply FE. Bila `PLATFORM_FE_BASE_URL` tersedia, route ini akan redirect ke owner page `platform-fe /profile`.

### Material Workspace

- `GET /materials`
- `GET /materials/create`
- `POST /materials`
- `GET /materials/{family}/{id}/edit`
- `PUT /materials/{family}/{id}`
- `DELETE /materials/{family}/{id}`
- `GET /materials/recycle-bin`
- `POST /materials/{type}/{id}/restore`
- `DELETE /materials/{type}/{id}/force-delete`
- `POST /materials/bulk/restore`
- `POST /materials/bulk/force-delete`

### Family-Specific Donor Pages

Route owner form/create/show/edit tersedia untuk:

- `bricks`
- `cements`
- `nats`
- `sands`
- `cats`
- `ceramics`
- `steels`
- `kasa_gypsums`
- `paku_tembaks`
- `pakus`

Contoh:

- `GET /bricks/create`
- `POST /bricks`
- `GET /bricks/{id}`
- `GET /bricks/{id}/edit`
- `POST /bricks/{id}/history/{historyLog}/restore`

### Stores dan Locations

- `GET /stores`
- `GET /stores/create`
- `POST /stores`
- `GET /stores/{store}`
- `GET /stores/{store}/edit`
- `PUT /stores/{store}`
- `DELETE /stores/{store}`
- `GET /stores/{store}/locations/create`
- `POST /stores/{store}/locations`
- `GET /stores/{store}/locations/{location}/edit`
- `PUT /stores/{store}/locations/{location}`
- `DELETE /stores/{store}/locations/{location}`
- `GET /stores/{store}/locations/{location}/materials`

### Units dan Settings

- `GET /units`
- `GET /units/create`
- `POST /units`
- `GET /units/{id}/edit`
- `PUT /units/{id}`
- `DELETE /units/{id}`
- `GET /settings/store-search-radius`
- `POST /settings/store-search-radius`

## Auth dan Permission Model

Repo ini memakai:

- Keycloak untuk login browser
- `platform.auth` middleware untuk menjaga session user sinkron dengan identitas aktif lintas service
- `service.access:supply` untuk mengecek akses user ke service supply
- `supply.permission:*` untuk visibility route dan tombol per halaman

### Cross-Service Identity Sync

Supply FE ikut shared auth subject cookie sehingga:

- login akun baru di service lain akan menggeser session lokal yang sudah stale
- logout dari platform akan menjatuhkan session supply pada request berikutnya

### Permission-Aware Sidebar

Sidebar di supply FE sudah mengikuti behavior monolith yang diperhalus:

- hanya menu yang sesuai permission user yang tampil
- jika user memaksa masuk ke route yang tidak boleh, dia akan diarahkan balik dengan alert `Akses Ditolak`
- badge draft proyek dan badge store incomplete mengikuti permission yang relevan

## Integrasi Keluar

### Platform Backend

Dipakai untuk:

- identity projection
- permission snapshot
- allowed services
- access pending flow

### Supply Backend

Owner API utama untuk semua domain supply.

### Platform FE

Dipakai sebagai owner untuk:

- profile
- user management
- role management
- workers
- skills

### Calculation FE

Dipakai sebagai owner untuk domain calculation saat navigasi lintas service.

## Konfigurasi Environment Penting

Salin `.env.example` menjadi `.env`, lalu isi minimal grup berikut.

### App

- `APP_NAME`
- `APP_ENV`
- `APP_DEBUG`
- `APP_URL`
- `SESSION_DOMAIN`

### Keycloak

- `KEYCLOAK_BASE_URL`
- `KEYCLOAK_REALM`
- `KEYCLOAK_CLIENT_ID=supply-fe`
- `KEYCLOAK_VERIFY_SSL`
- `KEYCLOAK_CA_BUNDLE`
- `KEYCLOAK_SHARED_SUBJECT_COOKIE`

### Platform dan Supply APIs

- `PLATFORM_SERVICE_BASE_URL`
- `SUPPLY_SERVICE_BASE_URL`
- `SUPPLY_SERVICE_VERIFY_SSL`
- `SUPPLY_SERVICE_CA_BUNDLE`
- `INTERNAL_CALLER_NAME`
- `INTERNAL_SERVICE_TOKEN`

### Cross-Service Links

- `PLATFORM_FE_BASE_URL`
- `CALCULATION_FE_BASE_URL`
- `CALCULATION_FE_CONSUME_PATH`
- `CALCULATION_SERVICE_BASE_URL`
- `CALCULATION_SERVICE_VERIFY_SSL`
- `CALCULATION_SERVICE_CA_BUNDLE`

### Optional

- `GOOGLE_MAPS_API_KEY`

## Local Development Setup

### Prasyarat

- PHP 8.3+
- Composer
- Node.js dan npm
- `platform-service-be` aktif
- `supply-service-be` aktif
- Keycloak realm `kanggo`

### Instalasi

```bash
composer install
npm install
cp .env.example .env
php artisan key:generate
```

### Menjalankan Aplikasi

```bash
composer run dev
```

Atau manual:

```bash
php artisan serve --host=supplyfe.lvh.me --port=8009
npm run dev
```

Gunakan host yang konsisten dengan setup Keycloak tim agar callback auth tetap valid.

## Development Commands

```bash
php artisan test
vendor/bin/pint
npm run build
```

## UI dan Flow Penting

### Material Workspace

- tab material mengikuti setting visibility dan order owner supply backend
- route family-specific dipakai agar form donor bisa tetap 1:1 dengan monolith
- global material state dipakai untuk pengalaman lintas service yang seragam

### Embedded Material Modal

Owner modal tambah material bisa dibuka dari service lain, tetapi CSS dan JS tetap berasal dari supply FE karena halaman owner di-embed dengan layout owner supply.

### Alerts dan Confirmation

- confirmation memakai modal tengah ala monolith
- alert memakai toast kanan bawah ala monolith
- inline bootstrap alert lama sudah dinormalisasi ke pola ini

## Testing Strategy

Test utama repo ini meliputi:

- Keycloak auth flow
- permission-aware sidebar
- material management page
- donor material family pages
- recycle bin donor page
- unit management page
- store workspace page
- store donor page
- store location donor page
- store location materials page
- store search radius setting page

## Dokumen Terkait

Dokumen lama yang masih berguna sebagai konteks bootstrap awal:

- [SUPPLY_FE_BOOTSTRAP.md](./SUPPLY_FE_BOOTSTRAP.md)
- [WAVE_7_UAT_CHECKLIST.md](./WAVE_7_UAT_CHECKLIST.md)

Catatan: README ini menggambarkan kondisi runtime **terkini**. Beberapa bagian di dokumen bootstrap lama masih menyebut fase transisi yang sudah diganti oleh Keycloak flow final.

## Docker dan Deploy

Repo ini memiliki:

- `compose.yml`
- `compose.staging.yml`
- `compose.production.yml`
- `Dockerfile`
- `Dockerfile.production`
- `.dockerignore`
- `docker/entrypoint.sh`

Pola production mengikuti base monolith-style untuk service split:

- build asset di image
- `php-fpm`
- blue/green app service
- worker profile dan scheduler profile bila diperlukan
- external network `frontend` dan `backend`

## CI

Workflow `.github/workflows/ci.yml` mencakup:

- `vendor/bin/pint --test`
- `php artisan test`
- `npm run build`
- validasi compose

## Struktur Folder Penting

- `app/Http/Controllers/KeycloakAuthController.php` flow auth browser
- `app/Http/Middleware/EnsurePlatformAuthenticated.php` sinkronisasi identitas lintas service
- `app/Http/Middleware/EnsureServiceAccess.php` guard akses service supply
- `app/Support/Auth/SupplyPermissionGate.php` gate permission sidebar dan page actions
- `resources/views/layouts/app.blade.php` shell global supply
- `resources/views/materials` UI owner material
- `resources/views/stores` UI owner toko
- `resources/views/units` UI owner satuan

## Troubleshooting

### 403 saat membuka halaman supply

Cek:

- session user dari platform auth
- allowed services user di platform backend
- permission snapshot user
- token internal caller ke platform backend

### Login sukses tapi halaman kembali ke akun lama

Cek:

- shared auth subject cookie
- logout total lalu login ulang
- middleware auth sync pada request pertama setelah login

### Modal material terbuka tapi style rusak

Cek owner route embedded supply dan asset build terbaru di FE.

### Sidebar tidak sesuai role

Cek response identity dari platform backend dan `SupplyPermissionGate`.

## Related Repositories

- `supply-service-be`
- `platform-service-fe`
- `platform-service-be`
- `calculation-service-fe`