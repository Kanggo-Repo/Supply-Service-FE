<?php

namespace App\Support\Supply;

class SupplyMaterialCatalog
{
    /**
     * @return array<string, array<string, mixed>>
     */
    public static function families(): array
    {
        return [
            'brick' => [
                'label' => 'Bata',
                'description' => 'Bata, roster, dan material sejenis berbasis piece.',
            ],
            'cement' => [
                'label' => 'Semen',
                'description' => 'Semen, perekat, dan turunan berbasis berat kemasan.',
            ],
            'sand' => [
                'label' => 'Pasir',
                'description' => 'Pasir dan agregat halus berbasis volume atau kemasan.',
            ],
            'cat' => [
                'label' => 'Cat',
                'description' => 'Cat dan pelapis dengan atribut warna dan volume.',
            ],
            'ceramic' => [
                'label' => 'Keramik',
                'description' => 'Keramik dan penutup permukaan berbasis paket/m2.',
            ],
            'nat' => [
                'label' => 'Nat',
                'description' => 'Nat dan filler sambungan berbasis kemasan.',
            ],
            'steel' => [
                'label' => 'Besi',
                'description' => 'Besi dan profil logam dengan atribut dimensi lengkap.',
            ],
            'kasa_gypsum' => [
                'label' => 'Kasa Gypsum',
                'description' => 'Kasa dan bahan pendukung gypsum.',
            ],
            'paku_tembak' => [
                'label' => 'Paku Tembak',
                'description' => 'Mesiu dan paku tembak per paket.',
            ],
            'paku' => [
                'label' => 'Paku',
                'description' => 'Paku umum dengan atribut ukuran dan isi kemasan.',
            ],
        ];
    }

    public static function exists(string $family): bool
    {
        return array_key_exists($family, self::families());
    }

    /**
     * @return array<string, mixed>
     */
    public static function family(string $family): array
    {
        return self::families()[$family];
    }

    public static function label(string $family): string
    {
        return (string) data_get(self::family($family), 'label', $family);
    }

    public static function nameField(string $family): string
    {
        return [
            'brick' => 'material_name',
            'cement' => 'cement_name',
            'sand' => 'sand_name',
            'cat' => 'cat_name',
            'ceramic' => 'material_name',
            'nat' => 'nat_name',
            'steel' => 'material_name',
            'kasa_gypsum' => 'material_name',
            'paku_tembak' => 'material_name',
            'paku' => 'material_name',
        ][$family];
    }

    /**
     * @return list<string>
     */
    public static function writableFields(string $family): array
    {
        return match ($family) {
            'brick' => [
                'material_name', 'type', 'photo', 'brand', 'form',
                'dimension_length', 'dimension_width', 'dimension_height',
                'package_volume', 'store', 'address', 'short_address', 'store_location_id',
                'price_per_piece', 'comparison_price_per_m3', 'package_type',
            ],
            'cement' => [
                'cement_name', 'type', 'photo', 'brand', 'sub_brand', 'code', 'color',
                'package_unit', 'package_weight_gross', 'package_weight_net', 'package_volume',
                'store', 'address', 'short_address', 'store_location_id', 'package_price',
                'price_unit', 'comparison_price_per_kg',
            ],
            'sand' => [
                'sand_name', 'type', 'photo', 'brand', 'package_unit', 'package_weight_gross',
                'package_weight_net', 'dimension_length', 'dimension_width', 'dimension_height',
                'package_volume', 'store', 'address', 'short_address', 'store_location_id',
                'package_price', 'comparison_price_per_m3',
            ],
            'cat' => [
                'cat_name', 'type', 'photo', 'brand', 'sub_brand', 'color_code', 'color_name',
                'form', 'package_unit', 'package_weight_gross', 'package_weight_net', 'volume',
                'volume_unit', 'store', 'address', 'short_address', 'store_location_id',
                'purchase_price', 'price_unit', 'comparison_price_per_kg',
            ],
            'ceramic' => [
                'material_name', 'type', 'photo', 'brand', 'sub_brand', 'code', 'color',
                'form', 'dimension_length', 'dimension_width', 'dimension_thickness',
                'packaging', 'pieces_per_package', 'coverage_per_package', 'surface',
                'store', 'address', 'store_location_id', 'price_per_package',
                'comparison_price_per_m2',
            ],
            'nat' => [
                'nat_name', 'type', 'photo', 'brand', 'sub_brand', 'code', 'color',
                'package_unit', 'package_weight_gross', 'package_weight_net', 'package_volume',
                'store', 'address', 'store_location_id', 'package_price', 'price_unit',
                'comparison_price_per_kg',
            ],
            'steel' => [
                'material_name', 'type', 'brand', 'quality', 'term', 'form',
                'dimension_length', 'dimension_width', 'dimension_height', 'dimension_thickness',
                'package_volume', 'store', 'address', 'store_location_id', 'package_unit',
                'package_price', 'comparison_price_per_m3', 'photo',
            ],
            'kasa_gypsum' => [
                'material_name', 'type', 'brand', 'dimension_length', 'dimension_width',
                'store', 'address', 'store_location_id', 'package_unit', 'package_price',
                'comparison_price_per_m', 'photo',
            ],
            'paku_tembak' => [
                'material_name', 'type', 'brand', 'package_unit', 'mesiu_code', 'mesiu_size',
                'mesiu_content', 'paku_code', 'paku_size', 'paku_content', 'store', 'address',
                'store_location_id', 'package_price', 'comparison_price', 'photo',
            ],
            'paku' => [
                'material_name', 'type', 'brand', 'dimension_length', 'dimension_length_mm',
                'dimension_body_diameter', 'dimension_head_diameter', 'color', 'package_unit',
                'package_weight', 'package_content', 'store', 'address', 'store_location_id',
                'package_price', 'comparison_price', 'photo',
            ],
            default => [],
        };
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    public static function formFields(string $family): array
    {
        $customLabels = [
            'material_name' => 'Nama Material',
            'cement_name' => 'Nama Semen',
            'sand_name' => 'Nama Pasir',
            'cat_name' => 'Nama Cat',
            'nat_name' => 'Nama Nat',
            'sub_brand' => 'Sub Brand',
            'color_code' => 'Kode Warna',
            'color_name' => 'Nama Warna',
            'package_unit' => 'Unit Kemasan',
            'package_weight_gross' => 'Berat Kotor',
            'package_weight_net' => 'Berat Bersih',
            'package_volume' => 'Volume Kemasan',
            'short_address' => 'Alamat Ringkas',
            'store_location_id' => 'Store Location ID',
            'price_per_piece' => 'Harga per Pcs',
            'comparison_price_per_m3' => 'Harga Banding per m3',
            'package_type' => 'Tipe Kemasan',
            'package_price' => 'Harga Kemasan',
            'price_unit' => 'Unit Harga',
            'comparison_price_per_kg' => 'Harga Banding per Kg',
            'dimension_length' => 'Panjang',
            'dimension_width' => 'Lebar',
            'dimension_height' => 'Tinggi',
            'dimension_thickness' => 'Ketebalan',
            'pieces_per_package' => 'Jumlah per Paket',
            'coverage_per_package' => 'Coverage per Paket',
            'price_per_package' => 'Harga per Paket',
            'comparison_price_per_m2' => 'Harga Banding per m2',
            'comparison_price_per_m' => 'Harga Banding per m',
            'mesiu_code' => 'Kode Mesiu',
            'mesiu_size' => 'Ukuran Mesiu',
            'mesiu_content' => 'Isi Mesiu',
            'paku_code' => 'Kode Paku',
            'paku_size' => 'Ukuran Paku',
            'paku_content' => 'Isi Paku',
            'dimension_length_mm' => 'Panjang (mm)',
            'dimension_body_diameter' => 'Diameter Badan',
            'dimension_head_diameter' => 'Diameter Kepala',
            'package_weight' => 'Berat Kemasan',
            'package_content' => 'Isi Kemasan',
            'comparison_price' => 'Harga Banding',
        ];

        $textareas = ['address'];
        $decimalFields = [
            'dimension_length', 'dimension_width', 'dimension_height', 'dimension_thickness',
            'package_volume', 'package_weight_gross', 'package_weight_net', 'price_per_piece',
            'comparison_price_per_m3', 'package_price', 'comparison_price_per_kg', 'volume',
            'purchase_price', 'price_per_package', 'comparison_price_per_m2',
            'comparison_price_per_m', 'mesiu_content', 'paku_content', 'package_weight',
            'comparison_price', 'coverage_per_package',
        ];
        $integerFields = ['store_location_id', 'pieces_per_package'];

        $definitions = [];

        foreach (self::writableFields($family) as $field) {
            $definitions[$field] = [
                'label' => $customLabels[$field] ?? str($field)->replace('_', ' ')->headline()->toString(),
                'type' => in_array($field, $textareas, true)
                    ? 'textarea'
                    : (in_array($field, $integerFields, true)
                        ? 'number'
                        : (in_array($field, $decimalFields, true) ? 'decimal' : 'text')),
                'step' => in_array($field, $integerFields, true) ? '1' : '0.01',
            ];
        }

        return $definitions;
    }

    /**
     * @return list<string>
     */
    public static function suggestionFields(string $family): array
    {
        $candidates = ['brand', 'type', 'sub_brand', 'form', 'color', 'quality', 'surface'];
        $fields = self::writableFields($family);

        return array_values(array_filter(
            $candidates,
            static fn (string $field): bool => in_array($field, $fields, true),
        ));
    }

    /**
     * @return list<string>
     */
    public static function tableFields(string $family): array
    {
        $candidates = ['brand', 'type', 'sub_brand', 'color', 'form', 'store'];
        $fields = self::writableFields($family);

        return array_values(array_filter(
            $candidates,
            static fn (string $field): bool => in_array($field, $fields, true),
        ));
    }
}
