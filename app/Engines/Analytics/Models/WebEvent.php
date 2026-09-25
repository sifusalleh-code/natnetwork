<?php

namespace App\Engines\Analytics\Models;

use Illuminate\Database\Eloquent\Model;

class WebEvent extends Model
{
    public $timestamps = false;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['occurred_at' => 'datetime'];
    }
}
