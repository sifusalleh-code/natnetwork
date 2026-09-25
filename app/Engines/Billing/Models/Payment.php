<?php

namespace App\Engines\Billing\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Payment extends Model
{
    public const STATUS_PENDING = 'PENDING';
    public const STATUS_PROCESSING = 'PROCESSING';
    public const STATUS_PAID = 'PAID';
    public const STATUS_FAILED = 'FAILED';
    public const STATUS_EXPIRED = 'EXPIRED';
    public const STATUS_CANCELLED = 'CANCELLED';
    public const STATUS_REFUNDED = 'REFUNDED';
    public const STATUS_PARTIALLY_REFUNDED = 'PARTIALLY_REFUNDED';
    public const STATUS_REVIEW_REQUIRED = 'REVIEW_REQUIRED';

    protected $fillable = [
        'invoice_id', 'is_sandbox', 'gateway', 'gateway_mode', 'gateway_bill_id', 'gateway_url', 'gateway_collection_id',
        'amount_cents', 'paid_amount_cents', 'status', 'review_reason', 'callback_payload', 'verified_at', 'paid_at',
    ];

    protected function casts(): array
    {
        return [
            'is_sandbox' => 'boolean',
            'amount_cents' => 'integer',
            'paid_amount_cents' => 'integer',
            'callback_payload' => 'array',
            'verified_at' => 'datetime',
            'paid_at' => 'datetime',
        ];
    }

    public function scopeProduction(Builder $query): Builder { return $query->where('is_sandbox', false); }
    public function scopeSandbox(Builder $query): Builder { return $query->where('is_sandbox', true); }

    public function amount(): string { return number_format($this->amount_cents / 100, 2, '.', ''); }

    public function invoice(): BelongsTo { return $this->belongsTo(Invoice::class); }
    public function receipt(): HasOne { return $this->hasOne(Receipt::class); }
}
