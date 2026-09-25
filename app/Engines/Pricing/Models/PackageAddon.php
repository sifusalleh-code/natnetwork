<?php

namespace App\Engines\Pricing\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Add-on yang ditawarkan oleh satu pakej, dengan harga ikut pakej (Pricing Engine). */
class PackageAddon extends Model
{
    protected $fillable = ['service_package_id', 'addon_id', 'name', 'price_type', 'price_amount', 'price_label', 'display_order', 'is_active'];

    protected function casts(): array
    {
        return ['price_amount' => 'decimal:2', 'is_active' => 'boolean'];
    }

    public function addon(): BelongsTo
    {
        return $this->belongsTo(Addon::class);
    }

    public function package(): BelongsTo
    {
        return $this->belongsTo(ServicePackage::class, 'service_package_id');
    }

    public function displayName(): string
    {
        return $this->name ?: (string) $this->addon?->name;
    }
}
