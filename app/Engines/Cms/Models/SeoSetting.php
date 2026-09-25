<?php

namespace App\Engines\Cms\Models;

use Illuminate\Database\Eloquent\Model;

class SeoSetting extends Model
{
    protected $fillable = ['site_name', 'default_description', 'default_og_image', 'google_site_verification', 'bing_site_verification'];

    /** Baca tanpa menulis ke database (selamat untuk halaman awam). */
    public static function currentOrDefault(): self
    {
        return static::query()->first() ?? new static(static::defaults());
    }

    public static function current(): self
    {
        return static::query()->firstOrCreate([], static::defaults());
    }

    public static function defaults(): array
    {
        return ['site_name' => config('seo.site_name'), 'default_description' => config('seo.default_description')];
    }
}
