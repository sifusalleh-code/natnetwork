<?php

namespace App\Engines\Partnership\Models;

use App\Engines\Billing\Models\Invoice;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PartnerPoolEntry extends Model
{
    public const ALLOCATION = 'ALLOCATION';
    public const REVERSAL = 'REVERSAL';
    public const UPDATED_AT = null;

    protected $fillable = ['type', 'invoice_id', 'refund_id', 'sale_amount', 'pool_percent', 'pool_amount', 'allocated_amount', 'total_capital', 'created_at'];
    protected function casts(): array
    {
        return ['sale_amount' => 'decimal:2', 'pool_percent' => 'decimal:2', 'pool_amount' => 'decimal:2', 'allocated_amount' => 'decimal:2', 'total_capital' => 'decimal:2', 'created_at' => 'datetime'];
    }

    public function invoice(): BelongsTo { return $this->belongsTo(Invoice::class); }
    public function earnings(): HasMany { return $this->hasMany(PartnerEarning::class); }
}
