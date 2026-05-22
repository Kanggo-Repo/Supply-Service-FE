<?php

namespace App\Models;

use App\Support\Supply\SupplyMaterialCatalog;
use Illuminate\Database\Eloquent\Model;

class MaterialSetting extends Model
{
    public $timestamps = false;

    protected $guarded = [];

    public static function getMaterialLabel(string $materialType): string
    {
        $normalizedType = $materialType === 'nat' ? 'cement' : $materialType;

        return (string) data_get(SupplyMaterialCatalog::families(), "{$normalizedType}.label", ucfirst($normalizedType));
    }
}
