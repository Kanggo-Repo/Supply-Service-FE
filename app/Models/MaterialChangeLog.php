<?php

namespace App\Models;

use App\Helpers\NumberHelper;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

class MaterialChangeLog
{
    public static function labelForField(string $field): string
    {
        return [
            'material_name' => 'Material',
            'cat_name' => 'Nama Cat',
            'cement_name' => 'Nama Semen',
            'nat_name' => 'Nama Nat',
            'sand_name' => 'Nama Pasir',
            'type' => 'Jenis',
            'brand' => 'Merek',
            'sub_brand' => 'Sub Merek',
            'form' => 'Bentuk',
            'surface' => 'Permukaan',
            'code' => 'Kode',
            'color' => 'Warna',
            'color_code' => 'Kode Warna',
            'color_name' => 'Nama Warna',
            'package_type' => 'Tipe Kemasan',
            'package_unit' => 'Satuan Kemasan',
            'packaging' => 'Kemasan',
            'dimension_length' => 'Dimensi P',
            'dimension_length_mm' => 'Dimensi P (mm)',
            'dimension_width' => 'Dimensi L',
            'dimension_height' => 'Dimensi T',
            'dimension_thickness' => 'Ketebalan',
            'dimension_body_diameter' => 'Diameter Badan',
            'dimension_head_diameter' => 'Diameter Kepala',
            'package_volume' => 'Volume Kemasan',
            'package_weight_gross' => 'Berat Kotor',
            'package_weight_net' => 'Berat Bersih',
            'volume' => 'Volume',
            'volume_unit' => 'Satuan Volume',
            'pieces_per_package' => 'Isi per Kemasan',
            'coverage_per_package' => 'Luas per Kemasan',
            'store' => 'Toko',
            'address' => 'Alamat',
            'store_location_id' => 'Lokasi Toko',
            'store_location_links' => 'Ketersediaan Lokasi',
            'price_per_piece' => 'Harga per Buah',
            'package_price' => 'Harga Kemasan',
            'purchase_price' => 'Harga Beli',
            'price_per_package' => 'Harga per Kemasan',
            'comparison_price_per_m3' => 'Harga Komparasi / M3',
            'comparison_price_per_m' => 'Harga Komparasi / M',
            'comparison_price' => 'Harga Komparasi',
            'comparison_price_per_kg' => 'Harga Komparasi / Kg',
            'comparison_price_per_m2' => 'Harga Komparasi / M2',
            'mesiu_code' => 'Kode Mesiu',
            'mesiu_size' => 'Ukuran Mesiu',
            'mesiu_content' => 'Isi Mesiu',
            'paku_code' => 'Kode Paku',
            'paku_size' => 'Ukuran Paku',
            'paku_content' => 'Isi Paku',
            'package_weight' => 'Berat Isi',
            'package_content' => 'Jumlah Isi',
            'deleted_by' => 'Dihapus Oleh',
            'user_id' => 'Pengguna',
            'photo' => 'Foto',
        ][$field] ?? Str::headline(str_replace('_id', '', $field));
    }

    public static function formatFieldValue(string $field, mixed $value): string
    {
        if (in_array($field, ['deleted_by', 'user_id'], true)) {
            $resolvedUserName = self::resolveUserName($value);
            if ($resolvedUserName !== null) {
                return $resolvedUserName;
            }
        }

        return self::formatValue($value);
    }

    public static function formatValue(mixed $value): string
    {
        if ($value instanceof Carbon) {
            return $value->format('d M Y H:i:s');
        }

        if (is_null($value) || $value === '') {
            return '-';
        }

        if (is_bool($value)) {
            return $value ? 'Ya' : 'Tidak';
        }

        if (is_numeric($value)) {
            return NumberHelper::formatPlain((float) $value);
        }

        if (is_array($value)) {
            return implode(', ', array_map(static fn ($item) => is_scalar($item) ? (string) $item : json_encode($item), $value));
        }

        return (string) $value;
    }

    protected static function resolveUserName(mixed $value): ?string
    {
        if (is_string($value) && trim($value) !== '' && ! is_numeric($value)) {
            return trim($value);
        }

        if (! is_numeric($value)) {
            return null;
        }

        return User::query()->find((int) $value)?->name;
    }
}
