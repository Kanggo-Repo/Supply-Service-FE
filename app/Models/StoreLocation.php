<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;

class StoreLocation extends Model
{
    public $timestamps = false;

    protected $guarded = [];

    protected $casts = [
        'latitude' => 'float',
        'longitude' => 'float',
    ];

    protected function isIncomplete(): Attribute
    {
        return Attribute::make(
            get: fn (): bool => empty($this->city) || empty($this->province) || empty($this->contact_phone),
        );
    }

    protected function fullAddress(): Attribute
    {
        return Attribute::make(
            get: fn (): string => implode(', ', array_filter([$this->address, $this->district, $this->city, $this->province])),
        );
    }

    protected function resolvedAddress(): Attribute
    {
        return Attribute::make(
            get: fn (): string => trim((string) ($this->formatted_address ?: $this->full_address ?: $this->address)),
        );
    }

    protected function materialSnapshotAddress(): Attribute
    {
        return Attribute::make(
            get: fn (): string => trim((string) ($this->address ?: $this->full_address ?: $this->formatted_address)),
        );
    }
}
