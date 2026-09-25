<?php

namespace App\Engines\Sales\Models;

use App\Engines\Pricing\Models\ServicePackage;
use App\Models\User;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class BuilderSession extends Model
{
    use HasUuids;

    public $incrementing = false;
    protected $keyType = 'string';
    protected $fillable = ['resume_token', 'user_id', 'service_package_id', 'entry_path', 'contact_name', 'contact_company', 'contact_email', 'contact_phone', 'email_verified_at', 'completed_at', 'current_step', 'reset_at', 'addon_ids'];

    protected function casts(): array
    {
        return ['email_verified_at' => 'datetime', 'completed_at' => 'datetime', 'reset_at' => 'datetime', 'addon_ids' => 'array'];
    }

    protected static function booted(): void
    {
        static::creating(function (self $session): void {
            $session->resume_token ??= (string) Str::uuid();
        });
    }

    public function user(): BelongsTo { return $this->belongsTo(User::class); }
    public function servicePackage(): BelongsTo { return $this->belongsTo(ServicePackage::class); }
    public function answers(): HasMany { return $this->hasMany(BuilderAnswer::class); }
    public function files(): HasMany { return $this->hasMany(BuilderFile::class); }
    public function projectRequest(): \Illuminate\Database\Eloquent\Relations\HasOne { return $this->hasOne(ProjectRequest::class); }
}
