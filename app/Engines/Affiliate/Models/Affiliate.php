<?php

namespace App\Engines\Affiliate\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;

class Affiliate extends Authenticatable
{
    public const STATUS_ACTIVE = 'ACTIVE';

    protected $fillable = [
        'name', 'email', 'phone', 'username', 'state', 'avatar_path', 'bank_name',
        'bank_account_number', 'bank_account_last4', 'facebook_url', 'instagram_url', 'twitter_url',
    ];

    protected $hidden = ['bank_account_number', 'remember_token'];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'profile_completed_at' => 'datetime',
            'bank_account_number' => 'encrypted',
        ];
    }

    public function getAuthPassword(): string
    {
        return '';
    }

    public function isProfileComplete(): bool
    {
        return $this->profile_completed_at !== null;
    }

    public function maskedBankAccount(): ?string
    {
        return $this->bank_account_last4 ? '•••• '.$this->bank_account_last4 : null;
    }

    public function clicks(): HasMany { return $this->hasMany(AffiliateClick::class); }
    public function clientLinks(): HasMany { return $this->hasMany(AffiliateClientLink::class); }
}
