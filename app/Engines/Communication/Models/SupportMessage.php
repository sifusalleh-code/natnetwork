<?php

namespace App\Engines\Communication\Models;

use Illuminate\Database\Eloquent\Model;

class SupportMessage extends Model
{
    public const UPDATED_AT = null;
    protected $fillable = ['support_ticket_id', 'author_type', 'author_id', 'body', 'created_at'];
    protected function casts(): array { return ['created_at' => 'datetime']; }
}
