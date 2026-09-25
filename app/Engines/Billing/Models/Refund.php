<?php

namespace App\Engines\Billing\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Refund direkod manual oleh admin selepas wang dipindahkan (Billplz tiada API refund). Tidak boleh dipadam. */
class Refund extends Model
{
    protected $fillable = ['number', 'is_sandbox', 'invoice_id', 'payment_id', 'project_id', 'customer_user_id', 'amount', 'reason', 'transfer_reference', 'refunded_by_admin_id', 'refunded_at'];

    protected function casts(): array
    {
        return ['is_sandbox' => 'boolean', 'amount' => 'decimal:2', 'refunded_at' => 'datetime'];
    }

    public function invoice(): BelongsTo { return $this->belongsTo(Invoice::class); }
}
