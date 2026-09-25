<?php

namespace App\Engines\Identity\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmailOtpChallenge extends Model
{
    public const PURPOSE_CLIENT_LOGIN = 'CLIENT_LOGIN';
    public const PURPOSE_AFFILIATE_LOGIN = 'AFFILIATE_LOGIN';
    public const PURPOSE_PARTNER_LOGIN = 'PARTNER_LOGIN';

    protected $fillable = [
        'user_id', 'email', 'purpose', 'code_hash', 'attempts', 'sent_at',
        'expires_at', 'consumed_at', 'invalidated_at',
    ];

    protected $hidden = ['code_hash'];

    protected function casts(): array
    {
        return [
            'sent_at' => 'datetime',
            'expires_at' => 'datetime',
            'consumed_at' => 'datetime',
            'invalidated_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
