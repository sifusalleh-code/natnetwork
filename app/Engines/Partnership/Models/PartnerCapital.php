<?php

namespace App\Engines\Partnership\Models;

use App\Engines\Billing\Models\Invoice;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PartnerCapital extends Model
{
    public const PENDING_PAYMENT = 'PENDING_PAYMENT';
    public const ACTIVE = 'ACTIVE';
    public const CANCELLED = 'CANCELLED';
    public const LABELS = [self::PENDING_PAYMENT => ['Menunggu bayaran', 'warn'], self::ACTIVE => ['Aktif', 'ok'], self::CANCELLED => ['Dibatalkan', '']];

    protected $fillable = ['number', 'is_sandbox', 'partner_id', 'amount', 'status', 'invoice_id', 'terms_snapshot', 'acceptance_metadata', 'activated_at'];
    protected function casts(): array
    {
        return ['is_sandbox' => 'boolean', 'amount' => 'decimal:2', 'terms_snapshot' => 'array', 'acceptance_metadata' => 'array', 'activated_at' => 'datetime'];
    }

    public function label(): string { return self::LABELS[$this->status][0] ?? $this->status; }
    public function tone(): string { return self::LABELS[$this->status][1] ?? ''; }
    public function partner(): BelongsTo { return $this->belongsTo(Partner::class); }
    public function invoice(): BelongsTo { return $this->belongsTo(Invoice::class); }
}
