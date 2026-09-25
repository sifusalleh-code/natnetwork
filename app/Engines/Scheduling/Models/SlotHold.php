<?php

namespace App\Engines\Scheduling\Models;

use App\Engines\Sales\Models\Quotation;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SlotHold extends Model
{
    public const HELD = 'HELD';
    public const RESERVED = 'RESERVED';
    public const RELEASED = 'RELEASED';
    public const EXPIRED = 'EXPIRED';

    protected $fillable = ['quotation_id', 'is_sandbox', 'start_date', 'weeks', 'status', 'expires_at', 'reserved_at', 'released_at', 'conflict'];

    protected function casts(): array
    {
        return ['is_sandbox' => 'boolean', 'start_date' => 'date', 'expires_at' => 'datetime', 'reserved_at' => 'datetime', 'released_at' => 'datetime', 'conflict' => 'boolean'];
    }

    /**
     * Hold yang mengambil kapasiti: RESERVED, atau HELD yang belum tamat.
     * Hold sandbox (mod ujian Billplz) tidak pernah mengambil kapasiti production dan sebaliknya.
     */
    public function scopeOccupying(Builder $query): Builder
    {
        return $query->where('is_sandbox', false)
            ->where(fn ($q) => $q->where('status', self::RESERVED)->orWhere(fn ($q) => $q->where('status', self::HELD)->where('expires_at', '>', now())));
    }

    public function isActiveHold(): bool
    {
        return $this->status === self::HELD && $this->expires_at?->isFuture();
    }

    public function endDate(): \Carbon\CarbonInterface
    {
        return $this->start_date->copy()->addWeeks($this->weeks);
    }

    public function quotation(): BelongsTo { return $this->belongsTo(Quotation::class); }
}
