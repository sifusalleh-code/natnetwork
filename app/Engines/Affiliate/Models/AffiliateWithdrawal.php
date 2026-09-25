<?php

namespace App\Engines\Affiliate\Models;

use App\Engines\Identity\Models\Admin;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AffiliateWithdrawal extends Model
{
    public const STATUS_REQUESTED = 'REQUESTED';
    public const STATUS_PAID = 'PAID';
    public const STATUS_REJECTED = 'REJECTED';

    public const LABELS = [self::STATUS_REQUESTED => 'Dalam proses', self::STATUS_PAID => 'Dibayar', self::STATUS_REJECTED => 'Ditolak'];

    protected $fillable = [
        'affiliate_id', 'amount', 'status', 'bank_name', 'bank_account_number', 'bank_account_last4', 'account_holder',
        'week_shares', 'week_unique_clicks', 'reference', 'note', 'processed_by_admin_id', 'requested_at', 'processed_at',
    ];

    protected $hidden = ['bank_account_number'];

    protected function casts(): array
    {
        return ['amount' => 'decimal:2', 'bank_account_number' => 'encrypted', 'requested_at' => 'datetime', 'processed_at' => 'datetime'];
    }

    public function affiliate(): BelongsTo { return $this->belongsTo(Affiliate::class); }
    public function processedBy(): BelongsTo { return $this->belongsTo(Admin::class, 'processed_by_admin_id'); }

    public function label(): string { return self::LABELS[$this->status] ?? $this->status; }
}
