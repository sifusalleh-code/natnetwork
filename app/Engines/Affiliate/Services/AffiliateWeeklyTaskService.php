<?php

namespace App\Engines\Affiliate\Services;

use App\Engines\Affiliate\Models\Affiliate;
use App\Engines\Affiliate\Models\AffiliateClick;
use App\Engines\Affiliate\Models\AffiliateSetting;
use App\Engines\Affiliate\Models\AffiliateShare;
use Illuminate\Support\Carbon;

/** Task mingguan kelayakan withdrawal: minggu semasa Isnin 00:00 – Ahad 23:59 (waktu Malaysia). */
class AffiliateWeeklyTaskService
{
    public const TIMEZONE = 'Asia/Kuala_Lumpur';

    /** @return array{start: Carbon, end: Carbon} */
    public function currentWeek(?Carbon $at = null): array
    {
        $local = ($at ?? now())->copy()->timezone(self::TIMEZONE);

        return ['start' => $local->copy()->startOfWeek(Carbon::MONDAY), 'end' => $local->copy()->endOfWeek(Carbon::SUNDAY)];
    }

    /** @return array{shares: int, clicks: int, share_target: int, click_target: int, met: bool, start: Carbon, end: Carbon} */
    public function progress(Affiliate $affiliate, ?Carbon $at = null): array
    {
        $week = $this->currentWeek($at);
        $targets = AffiliateSetting::weeklyTargets();
        $range = [$week['start']->copy()->utc(), $week['end']->copy()->utc()];

        $shares = AffiliateShare::query()->where('affiliate_id', $affiliate->id)->whereBetween('shared_at', $range)->count();
        $clicks = AffiliateClick::query()->where('affiliate_id', $affiliate->id)->whereBetween('clicked_at', $range)->distinct()->count('visitor_token');

        return [
            'shares' => $shares, 'clicks' => $clicks,
            'share_target' => $targets['shares'], 'click_target' => $targets['clicks'],
            'met' => $shares >= $targets['shares'] && $clicks >= $targets['clicks'],
            'start' => $week['start'], 'end' => $week['end'],
        ];
    }
}
