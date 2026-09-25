<?php

namespace App\Engines\Affiliate\Models;

use App\Engines\Billing\Models\Invoice;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AffiliateCommission extends Model
{
    public const STATUS_PENDING = 'PENDING';
    public const STATUS_RELEASED = 'RELEASED';
    public const STATUS_CANCELLED = 'CANCELLED';

    protected $fillable = [
        'affiliate_id', 'customer_user_id', 'invoice_id', 'affiliate_rate_version_id', 'gross_amount',
        'cumulative_before', 'cumulative_after', 'amount', 'breakdown', 'status', 'released_at', 'cancelled_at',
    ];

    protected function casts(): array
    {
        return [
            'gross_amount' => 'decimal:2', 'cumulative_before' => 'decimal:2', 'cumulative_after' => 'decimal:2',
            'amount' => 'decimal:2', 'breakdown' => 'array', 'released_at' => 'datetime', 'cancelled_at' => 'datetime',
        ];
    }

    public function affiliate(): BelongsTo { return $this->belongsTo(Affiliate::class); }
    public function customer(): BelongsTo { return $this->belongsTo(User::class, 'customer_user_id'); }
    public function invoice(): BelongsTo { return $this->belongsTo(Invoice::class); }
    public function rateVersion(): BelongsTo { return $this->belongsTo(AffiliateRateVersion::class, 'affiliate_rate_version_id'); }
}
