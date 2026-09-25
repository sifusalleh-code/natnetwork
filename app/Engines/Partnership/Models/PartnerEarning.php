<?php

namespace App\Engines\Partnership\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PartnerEarning extends Model
{
    public const UPDATED_AT = null;
    protected $fillable = ['partner_pool_entry_id', 'partner_id', 'capital', 'amount', 'created_at'];
    protected function casts(): array { return ['capital' => 'decimal:2', 'amount' => 'decimal:2', 'created_at' => 'datetime']; }

    public function entry(): BelongsTo { return $this->belongsTo(PartnerPoolEntry::class, 'partner_pool_entry_id'); }
}
