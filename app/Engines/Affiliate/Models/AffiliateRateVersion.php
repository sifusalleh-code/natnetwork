<?php

namespace App\Engines\Affiliate\Models;

use Illuminate\Database\Eloquent\Model;

class AffiliateRateVersion extends Model
{
    protected $fillable = ['tiers', 'created_by_admin_id', 'effective_from'];

    protected function casts(): array
    {
        return ['tiers' => 'array', 'effective_from' => 'datetime'];
    }

    public static function current(): self
    {
        return static::query()->where('effective_from', '<=', now())->latest('effective_from')->latest('id')->firstOrFail();
    }
}
