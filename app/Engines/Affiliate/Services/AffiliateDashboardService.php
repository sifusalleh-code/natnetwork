<?php

namespace App\Engines\Affiliate\Services;

use App\Engines\Affiliate\Models\Affiliate;
use App\Engines\Affiliate\Models\AffiliateClick;
use App\Engines\Affiliate\Models\AffiliateClientLink;
use App\Engines\Affiliate\Models\AffiliateCommission;
use Illuminate\Support\Carbon;

class AffiliateDashboardService
{
    public function __construct(private readonly AffiliateWalletService $wallet, private readonly AffiliateWeeklyTaskService $tasks)
    {
    }

    public function forAffiliate(Affiliate $affiliate): array
    {
        $clicks = AffiliateClick::query()->where('affiliate_id', $affiliate->id);
        $tz = AffiliateWeeklyTaskService::TIMEZONE;
        $from = now()->timezone($tz)->subDays(13)->startOfDay();
        $daily = AffiliateClick::query()->where('affiliate_id', $affiliate->id)->where('clicked_at', '>=', $from->copy()->utc())->get(['clicked_at'])
            ->groupBy(fn ($c) => $c->clicked_at->timezone($tz)->format('Y-m-d'))->map->count();
        $traffic = collect(range(0, 13))->map(function (int $i) use ($from, $daily): array {
            $day = $from->copy()->addDays($i);

            return ['label' => $day->format('d/m'), 'count' => (int) ($daily[$day->format('Y-m-d')] ?? 0)];
        });

        $recent = AffiliateCommission::query()->with(['customer:id,name', 'invoice:id,number,issued_at'])->where('affiliate_id', $affiliate->id)->latest('id')->limit(6)->get()
            ->map(fn (AffiliateCommission $c): array => [
                'date' => $c->invoice?->issued_at ?? $c->created_at,
                'customer' => AffiliateCustomerService::maskName((string) $c->customer?->name),
                'invoice' => $c->invoice?->number,
                'gross' => $c->gross_amount,
                'amount' => $c->amount,
                'status' => $c->status,
                'status_label' => AffiliateCustomerService::COMMISSION_STATUS_LABELS[$c->status] ?? $c->status,
            ]);

        return [
            'total_clicks' => (clone $clicks)->count(),
            'unique_visitors' => (clone $clicks)->distinct()->count('visitor_token'),
            'referrals' => AffiliateClientLink::query()->where('affiliate_id', $affiliate->id)->count(),
            'wallet' => $this->wallet->summary($affiliate),
            'task' => $this->tasks->progress($affiliate),
            'traffic' => $traffic,
            'traffic_max' => max(1, $traffic->max('count')),
            'recent' => $recent,
        ];
    }
}
