<?php

namespace App\Engines\Affiliate\Models;

use Illuminate\Database\Eloquent\Model;

class AffiliateShare extends Model
{
    public const CHANNELS = ['download' => 'Download', 'copy' => 'Salin caption', 'whatsapp' => 'WhatsApp', 'facebook' => 'Facebook', 'telegram' => 'Telegram'];

    public $timestamps = false;

    protected $fillable = ['affiliate_id', 'affiliate_poster_id', 'channel', 'shared_at'];

    protected function casts(): array
    {
        return ['shared_at' => 'datetime'];
    }
}
