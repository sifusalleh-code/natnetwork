<?php

namespace App\Engines\Communication\Models;

use Illuminate\Database\Eloquent\Model;

class EmailLog extends Model
{
    public const UPDATED_AT = null;
    protected $fillable = ['recipient', 'subject', 'category', 'channel', 'status', 'provider_id', 'error', 'created_at'];
    protected function casts(): array { return ['created_at' => 'datetime']; }
}
