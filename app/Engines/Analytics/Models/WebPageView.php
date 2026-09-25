<?php

namespace App\Engines\Analytics\Models;

use Illuminate\Database\Eloquent\Model;

class WebPageView extends Model
{
    public $timestamps = false;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['is_entry' => 'boolean', 'is_new_visitor' => 'boolean', 'viewed_at' => 'datetime', 'last_seen_at' => 'datetime'];
    }
}
