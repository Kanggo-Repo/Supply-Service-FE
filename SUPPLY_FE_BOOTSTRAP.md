# Supply FE Bootstrap

Dokumen ini mencatat hasil bootstrap sampai `Wave 7` untuk `supply-service-fe`.

## Scope yang sudah hidup

- monolith auth bridge transisi
- local session shell user di FE
- permission snapshot transisi per user
- route guard workspace supply
- server-side proxy FE ke `supply-service-be`
- donor UI 1:1 yang sudah hidup:
  - material catalog list + tab donor + inline CRUD
  - material family create/show/edit donor flow
  - material history restore
  - material recycle bin + bulk actions
  - unit list/create/edit/delete
  - stores list/create/show/edit/delete
  - store locations create/edit/delete
  - store location materials donor page
  - store search radius setting

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
- `GET /materials/recycle-bin`
- `POST /materials/{type}/{id}/restore`
- `DELETE /materials/{type}/{id}/force-delete`
- `POST /materials/bulk/restore`
- `POST /materials/bulk/force-delete`
- `GET /bricks/create`
- `POST /bricks`
- `GET /bricks/{id}`
- `GET /bricks/{id}/edit`
- `POST /bricks/{id}/history/{historyLog}/restore`
- `GET /cements/create`
- `POST /cements`
- `GET /cements/{id}`
- `GET /cements/{id}/edit`
- `POST /cements/{id}/history/{historyLog}/restore`
- `GET /nats/create`
- `POST /nats`
- `GET /nats/{id}`
- `GET /nats/{id}/edit`
- `POST /nats/{id}/history/{historyLog}/restore`
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
- `GET /settings/store-search-radius`
- `POST /settings/store-search-radius`
- `GET /units`
- `GET /units/create`
- `POST /units`
- `GET /units/{id}/edit`
- `PUT /units/{id}`
- `DELETE /units/{id}`

## Environment minimum

```env
APP_URL=http://supplyfe.lvh.me:8009

KEYCLOAK_BASE_URL=https://sso.example.com
KEYCLOAK_REALM=kanggo
KEYCLOAK_CLIENT_ID=supply-fe
KEYCLOAK_VERIFY_SSL=true

SUPPLY_SERVICE_BASE_URL=http://127.0.0.1:8008
INTERNAL_CALLER_NAME=supply-fe
SUPPLY_SERVICE_TOKEN=change-me
SUPPLY_SERVICE_BE_VERIFY_SSL=true
```

## Local host yang direkomendasikan

Untuk flow auth bridge, akses `supply-service-fe` sebaiknya memakai host khusus seperti:

- `http://supplyfe.lvh.me:8009`

Jangan mulai dari:

- `http://127.0.0.1:8009`
- `http://localhost:8009`

Alasannya:

- monolith auth handoff memvalidasi `return_to` terhadap allowlist URL
- di monolith lokal saat ini allowlist yang sudah hidup masih mengikuti pola `calcfe.lvh.me`
- jika FE dibuka dari `127.0.0.1`, maka `route('auth.consume')` akan ikut terbentuk sebagai `127.0.0.1` dan monolith akan menolak dengan `403`

## Konfigurasi monolith yang harus ikut ditambah

Di monolith, env `AUTH_HANDOFF_ALLOWED_RETURN_URLS` perlu memuat URL `supply-fe` juga. Contoh:

```env
AUTH_HANDOFF_ALLOWED_RETURN_URLS=http://calcfe.lvh.me:8001/auth/consume,http://calcfe.lvh.me:8001/login,http://supplyfe.lvh.me:8009/auth/consume,http://supplyfe.lvh.me:8009/login
```

Kalau supply FE akan dibuka dari shortcut monolith nantinya, host yang dipakai di shortcut itu juga harus konsisten dengan `supplyfe.lvh.me:8009`.

## Cara menjalankan local FE

Contoh yang aman untuk fase transisi auth bridge:

```bash
php artisan serve --host=supplyfe.lvh.me --port=8009
```

## Header proxy FE ke Supply BE

Header service:

- `X-Service-Name`
- `X-Service-Token`

Header actor yang saat ini diteruskan:

- `X-Actor-Id`
- `X-Actor-Name`
- `X-Actor-Email`
- `X-Actor-Auth-Provider`
- `X-Actor-Auth-Subject`
- `X-Actor-Roles`
- `X-Actor-Permissions`

## Verifikasi minimum

1. guest membuka `/materials` lalu diarahkan ke `/auth/redirect`
2. login page FE menampilkan CTA monolith bridge
3. `/auth/redirect` mengarah ke monolith handoff start
4. `/auth/consume` membuat local session shell user
5. halaman `/materials` berhasil membaca material list + filter metadata dari `supply-service-be`
6. inline create/update/delete material berjalan lewat FE proxy ke `supply-service-be`
7. history restore, recycle bin, dan bulk action material berjalan dari FE donor
8. halaman `/stores` dan `/stores/{store}` mengikuti donor monolith, termasuk modal location dan map preview
9. create/update/delete store dan store location berjalan lewat FE proxy ke `supply-service-be`
10. halaman `/stores/{store}/locations/{location}/materials` mengikuti tab/search donor monolith
11. halaman `/settings/store-search-radius` membaca dan menyimpan setting ke `supply-service-be`
12. halaman `/units` berhasil membaca unit list + material types dari `supply-service-be`
13. form unit create/update/delete berjalan lewat FE proxy ke `supply-service-be`

## Test yang menutup bootstrap ini

- `tests/Feature/KeycloakAuthFlowTest.php`
- `tests/Feature/MaterialManagementPageTest.php`
- `tests/Feature/MaterialDonorCompatibilityTest.php`
- `tests/Feature/MaterialRecycleBinDonorPageTest.php`
- `tests/Feature/UnitManagementPageTest.php`
- `tests/Feature/StoreWorkspacePageTest.php`
- `tests/Feature/StoreDonorPageTest.php`
- `tests/Feature/StoreLocationDonorPageTest.php`
- `tests/Feature/StoreLocationMaterialsDonorPageTest.php`
- `tests/Feature/StoreSearchRadiusSettingPageTest.php`

## Langkah berikut

- browser/UAT parity untuk memastikan seluruh JS donor runtime benar-benar 1:1 dengan monolith
- verifikasi semua flow di atas data import subset supply nyata, bukan hanya data factory/test
- mulai pakai permission snapshot yang lebih kaya saat payload handoff monolith sudah membawa role/permission lengkap
