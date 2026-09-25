<?php

namespace App\Engines\Affiliate\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AffiliateClientLink extends Model
{
    protected $fillable = ['affiliate_id', 'customer_user_id', 'affiliate_click_id', 'linked_at'];

    protected function casts(): array { return ['linked_at' => 'datetime']; }

    public function affiliate(): BelongsTo { return $this->belongsTo(Affiliate::class); }
    public function customer(): BelongsTo { return $this->belongsTo(User::class, 'customer_user_id'); }
    public function click(): BelongsTo { return $this->belongsTo(AffiliateClick::class, 'affiliate_click_id'); }
}
