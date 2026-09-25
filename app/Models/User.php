<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    public const ROLE_ADMIN = 'ADMIN';
    public const ROLE_CUSTOMER = 'CUSTOMER';

    protected $fillable = ['name', 'company', 'phone', 'email', 'suspended_at', 'suspended_reason', 'suspended_by_admin_id'];

    protected $hidden = ['password', 'remember_token'];

    protected function casts(): array
    {
        return ['email_verified_at' => 'datetime', 'suspended_at' => 'datetime'];
    }

    public function isSuspended(): bool
    {
        return $this->suspended_at !== null;
    }
}
