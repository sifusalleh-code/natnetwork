<?php

namespace App\Engines\Affiliate\Models;

use Illuminate\Database\Eloquent\Model;

class AffiliateSetting extends Model
{
    public const DEFAULT_COOKIE_DAYS = 30;

    public const DEFAULT_WEEKLY_SHARES = 20;
    public const DEFAULT_WEEKLY_UNIQUE_CLICKS = 100;

    protected $fillable = ['cookie_days', 'weekly_share_target', 'weekly_unique_click_target'];

    /** @return array{shares: int, clicks: int} */
    public static function weeklyTargets(): array
    {
        $row = static::query()->first();

        return [
            'shares' => (int) ($row?->weekly_share_target ?? self::DEFAULT_WEEKLY_SHARES),
            'clicks' => (int) ($row?->weekly_unique_click_target ?? self::DEFAULT_WEEKLY_UNIQUE_CLICKS),
        ];
    }

    public static function cookieDays(): int
    {
        return (int) (static::query()->value('cookie_days') ?? self::DEFAULT_COOKIE_DAYS);
    }
}
