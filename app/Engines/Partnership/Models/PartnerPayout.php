<?php

namespace App\Engines\Partnership\Models;

use Illuminate\Database\Eloquent\Model;

class PartnerPayout extends Model
{
    protected $fillable = ['number', 'partner_id', 'amount', 'transfer_reference', 'note', 'paid_by_admin_id', 'paid_at'];
    protected function casts(): array { return ['amount' => 'decimal:2', 'paid_at' => 'datetime']; }
}
