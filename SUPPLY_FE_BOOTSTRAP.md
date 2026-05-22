# Supply FE Bootstrap

Dokumen ini mencatat hasil bootstrap `Wave 5` dan `Wave 6` untuk `supply-service-fe`.

## Scope yang sudah hidup

- monolith auth bridge transisi
- local session shell user di FE
- permission snapshot transisi per user
- route guard workspace supply
- server-side proxy FE ke `supply-service-be`
- screen admin awal yang sudah hidup:
  - material catalog list
  - material create/edit/delete
  - unit list
  - unit create/edit/delete
  - stores summary shell

## Route yang aktif

- `GET /login`
- `GET /auth/redirect`
- `GET /auth/consume`
- `POST /logout`
- `GET /materials`
- `GET /materials/create`
- `POST /materials`
- `GET /materials/{family}/{id}/edit`
- `PUT /materials/{family}/{id}`
- `DELETE /materials/{family}/{id}`
- `GET /stores`
- `GET /units`
- `GET /units/create`
- `POST /units`
- `GET /units/{id}/edit`
- `PUT /units/{id}`
- `DELETE /units/{id}`

## Environment minimum

```env
APP_URL=http://supplyfe.lvh.me:8008

MONOLITH_AUTH_ENABLED=true
MONOLITH_AUTH_BASE_URL=https://monolith.example.com
MONOLITH_AUTH_HANDOFF_START_PATH=/auth/handoff/start
MONOLITH_AUTH_HANDOFF_REDEEM_PATH=/api/internal/auth/handoffs/redeem
MONOLITH_AUTH_HANDOFF_LOGOUT_PATH=/auth/handoff/logout
MONOLITH_AUTH_VERIFY_SSL=true

SUPPLY_SERVICE_BE_URL=http://127.0.0.1:8000
SUPPLY_SERVICE_FE_CALLER_NAME=supply-fe
SUPPLY_SERVICE_BE_TOKEN=change-me
SUPPLY_SERVICE_BE_VERIFY_SSL=true
```

## Local host yang direkomendasikan

Untuk flow auth bridge, akses `supply-service-fe` sebaiknya memakai host khusus seperti:

- `http://supplyfe.lvh.me:8008`

Jangan mulai dari:

- `http://127.0.0.1:8008`
- `http://localhost:8008`

Alasannya:

- monolith auth handoff memvalidasi `return_to` terhadap allowlist URL
- di monolith lokal saat ini allowlist yang sudah hidup masih mengikuti pola `calcfe.lvh.me`
- jika FE dibuka dari `127.0.0.1`, maka `route('auth.consume')` akan ikut terbentuk sebagai `127.0.0.1` dan monolith akan menolak dengan `403`

## Konfigurasi monolith yang harus ikut ditambah

Di monolith, env `AUTH_HANDOFF_ALLOWED_RETURN_URLS` perlu memuat URL `supply-fe` juga. Contoh:

```env
AUTH_HANDOFF_ALLOWED_RETURN_URLS=http://calcfe.lvh.me:8001/auth/consume,http://calcfe.lvh.me:8001/login,http://supplyfe.lvh.me:8008/auth/consume,http://supplyfe.lvh.me:8008/login
```

Kalau supply FE akan dibuka dari shortcut monolith nantinya, host yang dipakai di shortcut itu juga harus konsisten dengan `supplyfe.lvh.me:8008`.

## Cara menjalankan local FE

Contoh yang aman untuk fase transisi auth bridge:

```bash
php artisan serve --host=supplyfe.lvh.me --port=8008
```

## Header proxy FE ke Supply BE

Header service:

- `X-Service-Name`
- `X-Service-Token`

Header actor yang saat ini diteruskan:

- `X-Actor-Name`
- `X-Actor-Email`
- `X-Actor-Auth-Provider`
- `X-Actor-Auth-Subject`
- `X-Actor-Roles`
- `X-Actor-Permissions`

Catatan:

- `X-Actor-Id` sengaja belum diteruskan di bootstrap FE ini karena jalur transisi user sync ke `supply-service-be` belum dibekukan penuh.
- untuk read flow bootstrap ini tidak dibutuhkan.

## Verifikasi minimum

1. guest membuka `/materials` lalu diarahkan ke `/auth/redirect`
2. login page FE menampilkan CTA monolith bridge
3. `/auth/redirect` mengarah ke monolith handoff start
4. `/auth/consume` membuat local session shell user
5. halaman `/materials` berhasil membaca material list + filter metadata dari `supply-service-be`
6. form material create/update/delete berjalan lewat FE proxy ke `supply-service-be`
7. halaman `/units` berhasil membaca unit list + material types dari `supply-service-be`
8. form unit create/update/delete berjalan lewat FE proxy ke `supply-service-be`
9. halaman `/stores` hanya bisa diakses jika permission snapshot mengizinkan

## Test yang menutup bootstrap ini

- `tests/Feature/MonolithAuthBridgeTest.php`
- `tests/Feature/MaterialManagementPageTest.php`
- `tests/Feature/UnitManagementPageTest.php`
- `tests/Feature/StoreWorkspacePageTest.php`

## Langkah berikut

- donor UI recycle/history material
- donor UI store, store location, dan location materials
- mulai pakai permission snapshot yang lebih kaya saat payload handoff monolith sudah membawa role/permission lengkap
