<?php

namespace App\Engines\Pricing\Models;

use Illuminate\Database\Eloquent\Model;

class Addon extends Model
{
    protected $fillable = ['slug', 'name', 'summary', 'price_type', 'price_amount', 'price_label', 'display_order', 'catalogue_order', 'is_active'];

    protected function casts(): array
    {
        return ['price_amount' => 'decimal:2', 'is_active' => 'boolean'];
    }
}
