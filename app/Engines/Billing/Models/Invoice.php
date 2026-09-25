<?php

namespace App\Engines\Billing\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Invoice extends Model
{
    public const STATUS_ISSUED = 'ISSUED';
    public const STATUS_PARTIALLY_PAID = 'PARTIALLY_PAID';
    public const STATUS_PAID = 'PAID';
    public const STATUS_VOID = 'VOID';

    public const TYPES = ['DEPOSIT', 'FINAL_PAYMENT', 'ADDITIONAL_CHARGE', 'HOSTING_RENEWAL', 'MAINTENANCE', 'MANAGED_SERVICE', 'PAID_SUPPORT', 'PARTNER_CAPITAL', 'OTHER'];

    protected $fillable = [
        'number', 'is_sandbox', 'type', 'status', 'customer_user_id', 'source_type', 'source_id',
        'seller_snapshot', 'customer_snapshot', 'items_snapshot', 'subtotal', 'tax_amount', 'total',
        'amount_paid', 'amount_refunded', 'issued_at', 'due_at', 'paid_at', 'voided_at',
    ];

    protected function casts(): array
    {
        return [
            'is_sandbox' => 'boolean',
            'seller_snapshot' => 'array',
            'customer_snapshot' => 'array',
            'items_snapshot' => 'array',
            'subtotal' => 'decimal:2',
            'tax_amount' => 'decimal:2',
            'total' => 'decimal:2',
            'amount_paid' => 'decimal:2',
            'amount_refunded' => 'decimal:2',
            'issued_at' => 'datetime',
            'due_at' => 'date',
            'paid_at' => 'datetime',
            'voided_at' => 'datetime',
        ];
    }

    /** Hanya rekod production — untuk laporan, hasil, kapasiti dan komisyen. */
    public function scopeProduction(Builder $query): Builder { return $query->where('is_sandbox', false); }
    public function scopeSandbox(Builder $query): Builder { return $query->where('is_sandbox', true); }

    public function outstandingCents(): int
    {
        return max(0, (int) round(((float) $this->total - (float) $this->amount_paid) * 100));
    }

    public function customer(): BelongsTo { return $this->belongsTo(User::class, 'customer_user_id'); }
    public function payments(): HasMany { return $this->hasMany(Payment::class); }
    public function receipts(): HasMany { return $this->hasMany(Receipt::class); }
    public function refunds(): HasMany { return $this->hasMany(Refund::class); }

    public function refundableCents(): int
    {
        return max(0, (int) round(((float) $this->amount_paid - (float) $this->amount_refunded) * 100));
    }
}
