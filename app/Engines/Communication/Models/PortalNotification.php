<?php

namespace App\Engines\Communication\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class PortalNotification extends Model
{
    protected $fillable = ['recipient_type', 'recipient_id', 'category', 'title', 'body', 'url', 'read_at'];
    protected function casts(): array { return ['read_at' => 'datetime']; }

    public function scopeFor(Builder $query, string $type, int $id): Builder
    {
        return $query->where('recipient_type', $type)->where('recipient_id', $id);
    }
}
