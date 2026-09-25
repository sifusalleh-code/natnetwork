<?php

namespace App\Engines\Partnership\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;

class Partner extends Authenticatable
{
    public const PENDING_REVIEW = 'PENDING_REVIEW';
    public const APPROVED = 'APPROVED';
    public const REJECTED = 'REJECTED';
    public const SUSPENDED = 'SUSPENDED';

    public const LABELS = [
        self::PENDING_REVIEW => ['Menunggu semakan', 'warn'],
        self::APPROVED => ['Aktif', 'ok'],
        self::REJECTED => ['Ditolak', 'bad'],
        self::SUSPENDED => ['Digantung', 'bad'],
    ];

    protected $fillable = [
        'name', 'email', 'phone', 'id_type', 'id_number', 'id_last4', 'company_name',
        'bank_name', 'bank_account_holder', 'bank_account_number', 'bank_account_last4', 'email_verified_at', 'status',
        'terms_accepted_at', 'profile_completed_at',
    ];

    protected $hidden = ['id_number', 'bank_account_number', 'remember_token'];

    protected function casts(): array
    {
        return ['id_number' => 'encrypted', 'bank_account_number' => 'encrypted', 'email_verified_at' => 'datetime', 'reviewed_at' => 'datetime', 'terms_accepted_at' => 'datetime', 'profile_completed_at' => 'datetime'];
    }

    public function isApproved(): bool { return $this->status === self::APPROVED; }
    public function isSuspended(): bool { return $this->status === self::SUSPENDED; }
    public function hasPaidCapital(): bool { return $this->capitals()->where('status', PartnerCapital::ACTIVE)->exists(); }
    public function isProfileComplete(): bool { return $this->profile_completed_at !== null; }

    /** Langkah semasa aliran Partnership: 3 = bayaran, 4 = profil, 5 = dashboard. */
    public function onboardingStep(): int
    {
        if (! $this->hasPaidCapital()) {
            return 3;
        }

        return $this->isProfileComplete() ? 5 : 4;
    }
    public function label(): string { return self::LABELS[$this->status][0] ?? $this->status; }
    public function tone(): string { return self::LABELS[$this->status][1] ?? ''; }

    public function capitals(): HasMany { return $this->hasMany(PartnerCapital::class)->latest('id'); }
    public function earnings(): HasMany { return $this->hasMany(PartnerEarning::class); }
    public function payouts(): HasMany { return $this->hasMany(PartnerPayout::class); }

    public function activeCapitalCents(): int
    {
        return (int) round((float) $this->capitals()->where('status', PartnerCapital::ACTIVE)->sum('amount') * 100);
    }

    public function earningsCents(): int { return (int) round((float) $this->earnings()->sum('amount') * 100); }
    public function payoutsCents(): int { return (int) round((float) $this->payouts()->sum('amount') * 100); }
    public function balanceCents(): int { return $this->earningsCents() - $this->payoutsCents(); }
}
