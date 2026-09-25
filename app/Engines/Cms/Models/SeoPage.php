<?php

namespace App\Engines\Cms\Models;

use Illuminate\Database\Eloquent\Model;

class SeoPage extends Model
{
    protected $fillable = ['route_name', 'title', 'description', 'og_image', 'noindex'];

    protected function casts(): array
    {
        return ['noindex' => 'boolean'];
    }
}
