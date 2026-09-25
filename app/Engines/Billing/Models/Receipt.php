<?php

namespace App\Engines\Billing\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Receipt extends Model
{
    protected $fillable = ['number', 'is_sandbox', 'payment_id', 'invoice_id', 'amount', 'issued_at'];

    protected function casts(): array
    {
        return ['is_sandbox' => 'boolean', 'amount' => 'decimal:2', 'issued_at' => 'datetime'];
    }

    public function payment(): BelongsTo { return $this->belongsTo(Payment::class); }
    public function invoice(): BelongsTo { return $this->belongsTo(Invoice::class); }
}
