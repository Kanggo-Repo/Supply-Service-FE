# Wave 7 UAT Checklist

Checklist ini dipakai untuk menutup `Wave 7` sebelum masuk `Wave 8`.

## Tujuan

- memastikan `supply-service-fe` terasa 1:1 dengan monolith
- memastikan semua JS donor berjalan di browser nyata, bukan hanya lulus feature test
- memastikan contract `supply-service-fe -> supply-service-be` stabil di runtime

## Prasyarat

- `supply-service-be` dan `supply-service-fe` berjalan lokal
- auth handoff monolith sudah mengizinkan host `supplyfe.lvh.me`
- `supply-service-be` berisi data supply yang cukup untuk semua family utama
- browser dibuka dengan hard refresh terbaru

## Auth dan shell

- login dari `http://supplyfe.lvh.me:8009/login` berhasil via monolith bridge
- logout dari `supply-service-fe` kembali bersih
- topbar, sidebar, dropdown material, dan menu store tampil seperti monolith
- visibility menu mengikuti permission snapshot

## Material index

- tab family material tampil lengkap
- tab `cement` tetap memuat item `cement` dan `nat`
- sticky header dan sticky column berjalan
- pagination huruf Kanggo aktif dan hash tab tidak rusak
- search, sort, dan reset filter berjalan
- tombol `Recycle Bin` tampil sesuai permission

## Inline CRUD material

- inline create `brick` berjalan
- inline edit `brick` berjalan
- inline delete `brick` berjalan
- autosuggest field umum muncul:
  - `brand`
  - `type`
  - `form`
- autosuggest store/address muncul:
  - `store`
  - `address`
- quick-create store/address dari inline row tidak memecahkan submit

## Modal donor per family

Ulangi untuk minimal:

- `brick`
- `cement`
- `nat`
- `sand`
- `cat`
- `ceramic`
- `steel`
- `kasa_gypsum`
- `paku_tembak`
- `paku`

Untuk tiap family:

- modal `create` terbuka
- JS family form termuat
- store autocomplete berjalan
- save berhasil
- modal `show` terbuka
- modal `edit` terbuka
- update berhasil
- history muncul
- restore dari history berhasil

## Recycle bin

- halaman recycle bin render penuh
- pindah tab recycle family berjalan
- checkbox bulk memilih item dengan benar
- bulk restore berjalan
- bulk force delete berjalan
- kolom `Dihapus Oleh` terisi untuk data baru yang dihapus setelah patch actor sync

## Stores

- halaman `/stores` render seperti monolith
- map preview tampil
- create store berjalan
- show store berjalan
- edit store berjalan
- delete store berjalan

## Store locations

- modal create location terbuka
- Google Maps picker/load script berjalan jika API key tersedia
- create location berjalan
- edit location berjalan
- delete location berjalan

## Store location materials

- halaman `/stores/{store}/locations/{location}/materials` render penuh
- tab family donor berjalan
- search berjalan
- sort berjalan
- material count per tab benar

## Units

- halaman `/units` render donor
- create unit berjalan
- edit unit berjalan
- delete unit berjalan

## Settings

- halaman `/settings/store-search-radius` render donor
- update `project_store_radius_default_km` berhasil
- update `project_store_radius_final_km` berhasil

## Exit criteria

Wave 7 dianggap siap ditutup jika:

- seluruh automated test FE dan BE hijau
- tidak ada lagi route donor yang fallback ke screen family lain
- seluruh flow di checklist ini lolos pada browser nyata
- tidak ada lagi bug blocker di `supply-service-fe` dan `supply-service-be` yang terkait domain supply
