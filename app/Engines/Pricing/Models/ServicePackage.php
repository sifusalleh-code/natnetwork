<?php

namespace App\Engines\Pricing\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ServicePackage extends Model
{
    protected $fillable = ['service_id', 'slug', 'name', 'summary', 'inclusions', 'price_type', 'price_amount', 'price_label', 'delivery_estimate', 'display_order', 'is_active'];

    protected function casts(): array
    {
        return ['price_amount' => 'decimal:2', 'is_active' => 'boolean', 'inclusions' => 'array'];
    }

    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }

    public function packageAddons(): HasMany
    {
        return $this->hasMany(PackageAddon::class)->where('is_active', true)->orderBy('display_order');
    }
}
