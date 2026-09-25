<?php

use App\Engines\Affiliate\Models\Affiliate;
use App\Engines\Identity\Models\Admin;
use App\Engines\Partnership\Models\Partner;
use App\Models\User;

return [
    'defaults' => [
        'guard' => env('AUTH_GUARD', 'web'),
        'passwords' => env('AUTH_PASSWORD_BROKER', 'users'),
    ],
    'guards' => [
        'web' => ['driver' => 'session', 'provider' => 'users'],
        'client' => ['driver' => 'session', 'provider' => 'users'],
        'affiliate' => ['driver' => 'session', 'provider' => 'affiliates'],
        'admin' => ['driver' => 'session', 'provider' => 'admins'],
        'partner' => ['driver' => 'session', 'provider' => 'partners'],
    ],
    'providers' => [
        'users' => ['driver' => 'eloquent', 'model' => env('AUTH_MODEL', User::class)],
        'affiliates' => ['driver' => 'eloquent', 'model' => Affiliate::class],
        'admins' => ['driver' => 'eloquent', 'model' => Admin::class],
        'partners' => ['driver' => 'eloquent', 'model' => Partner::class],
    ],
    'passwords' => [
        'users' => [
            'provider' => 'users',
            'table' => env('AUTH_PASSWORD_RESET_TOKEN_TABLE', 'password_reset_tokens'),
            'expire' => 60,
            'throttle' => 60,
        ],
    ],
    'password_timeout' => env('AUTH_PASSWORD_TIMEOUT', 10800),
];
