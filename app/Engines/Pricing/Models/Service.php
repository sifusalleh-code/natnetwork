<?php

namespace App\Engines\Pricing\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Service extends Model
{
    protected $fillable = ['slug', 'name', 'summary', 'display_order', 'is_active'];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    public function packages(): HasMany
    {
        return $this->hasMany(ServicePackage::class)->orderBy('display_order');
    }
}
