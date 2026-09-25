<?php

namespace App\Engines\Sales\Models;

use App\Engines\Scheduling\Models\SlotHold;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Order extends Model
{
    public const CONFIRMED = 'CONFIRMED';
    public const CANCELLED = 'CANCELLED';

    protected $fillable = ['number', 'quotation_id', 'customer_user_id', 'slot_hold_id', 'status', 'confirmed_at'];

    protected function casts(): array { return ['confirmed_at' => 'datetime']; }

    public function quotation(): BelongsTo { return $this->belongsTo(Quotation::class); }
    public function customer(): BelongsTo { return $this->belongsTo(User::class, 'customer_user_id'); }
    public function slotHold(): BelongsTo { return $this->belongsTo(SlotHold::class); }
}
