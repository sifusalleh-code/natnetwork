<?php

namespace App\Engines\Billing\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class QuotationPaymentPlan extends Model
{
    public const DEPOSIT = 'DEPOSIT';
    public const FULL = 'FULL';
    public const PLANS = [self::DEPOSIT, self::FULL];

    protected $fillable = ['quotation_id', 'plan', 'percent', 'reward_snapshot', 'discount_amount', 'invoice_id', 'selected_at'];

    protected function casts(): array
    {
        return ['reward_snapshot' => 'array', 'discount_amount' => 'decimal:2', 'selected_at' => 'datetime'];
    }

    public function invoice(): BelongsTo { return $this->belongsTo(Invoice::class); }

    public function discountCents(): int { return (int) round((float) $this->discount_amount * 100); }
}
