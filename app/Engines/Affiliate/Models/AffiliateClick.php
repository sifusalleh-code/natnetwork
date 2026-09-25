<?php

namespace App\Engines\Affiliate\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AffiliateClick extends Model
{
    public $timestamps = false;

    protected $fillable = ['affiliate_id', 'visitor_token', 'source', 'device_type', 'referrer', 'landing_path', 'ip_hash', 'user_agent', 'clicked_at'];

    protected function casts(): array { return ['clicked_at' => 'datetime']; }

    public function affiliate(): BelongsTo { return $this->belongsTo(Affiliate::class); }
}
