<?php

namespace App\Engines\Scheduling\Models;

use Illuminate\Database\Eloquent\Model;

class SchedulingSetting extends Model
{
    protected $fillable = ['max_active_projects', 'hold_minutes', 'weeks_ahead'];

    public static function current(): self
    {
        return static::query()->firstOrCreate([], ['max_active_projects' => 3, 'hold_minutes' => 60, 'weeks_ahead' => 12]);
    }
}
