<?php

namespace App\Engines\Analytics\Services;

use App\Engines\Affiliate\Models\Affiliate;
use App\Engines\Affiliate\Models\AffiliateClick;
use App\Engines\Affiliate\Models\AffiliateClientLink;
use App\Engines\Analytics\Models\WebEvent;
use App\Engines\Analytics\Models\WebPageView;
use App\Engines\Communication\Models\EmailLog;
use App\Engines\Sales\Models\BuilderSession;
use App\Models\User;
use Carbon\CarbonImmutable;
use Carbon\CarbonPeriod;

/**
 * Analytics Engine: laporan Statistik Web untuk Admin. Semua angka dikira daripada rekod sebenar dalam julat tarikh.
 * Pengiraan dibuat dalam PHP (bebas jenis pangkalan data).
 */
class WebStatsService
{
    /** @return array{from: CarbonImmutable, to: CarbonImmutable, preset: string} */
    public function range(?string $preset, ?string $from, ?string $to): array
    {
        $today = CarbonImmutable::today();
        $preset = in_array($preset, ['today', '7', '30', '90', 'custom'], true) ? $preset : '30';

        if ($preset === 'custom') {
            try {
                $start = CarbonImmutable::parse((string) $from)->startOfDay();
                $end = CarbonImmutable::parse((string) $to)->endOfDay();
            } catch (\Throwable) {
                $start = $end = null;
            }
            if (! $start || ! $end || $start->greaterThan($end) || $start->diffInDays($end) > 366) {
                $preset = '30';
            } else {
                return ['from' => $start, 'to' => min($end, $today->endOfDay()), 'preset' => 'custom'];
            }
        }
        $days = $preset === 'today' ? 1 : (int) $preset;

        return ['from' => $today->subDays($days - 1)->startOfDay(), 'to' => $today->endOfDay(), 'preset' => $preset];
    }

    public function report(CarbonImmutable $from, CarbonImmutable $to, string $granularity = 'day'): array
    {
        $length = (int) ceil($from->diffInSeconds($to) / 86400);
        $prevTo = $from->subSecond();
        $prevFrom = $prevTo->subDays(max(1, $length))->addSecond();

        $current = $this->aggregate($from, $to);
        $previous = $this->aggregate($prevFrom, $prevTo);
        $bucket = $from->isSameDay($to) ? 'hour' : ($granularity === 'week' ? 'week' : 'day');

        return [
            'kpis' => $this->kpis($current, $previous),
            'series' => $this->series($current['views'], $from, $to, $bucket),
            'bucket' => $bucket,
            'sources' => $this->sources($current),
            'pages' => $this->pages($current),
            'landings' => $this->landings($current),
            'funnel' => $this->funnel($current),
            'ctas' => $this->ctas($current),
            'affiliate' => $this->affiliate($from, $to, $current),
            'countries' => $this->share($current['visitorCountry'], fn ($c) => $c ? (config('analytics.countries')[$c] ?? $c) : 'Tidak diketahui'),
            'devices' => $this->share($current['visitorDevice'], fn ($d) => ['mobile' => 'Mobile', 'desktop' => 'Desktop', 'tablet' => 'Tablet'][$d] ?? $d),
            'browsers' => $this->share($current['visitorBrowser'], fn ($b) => $b),
        ];
    }

    public function live(): array
    {
        $views = WebPageView::query()->where('last_seen_at', '>=', now()->subMinutes((int) config('analytics.live_minutes')))
            ->orderBy('viewed_at')->get(['visitor_token', 'route_name']);
        $current = $views->keyBy('visitor_token'); // paparan terkini setiap pelawat
        $pages = $current->countBy(fn ($v) => config('analytics.pages')[$v->route_name] ?? 'Lain-lain')->sortDesc();

        return ['count' => $current->count(), 'pages' => $pages->map(fn ($n, $label) => ['label' => $label, 'count' => $n])->values()->all()];
    }

    /** Baris CSV: ringkasan harian + halaman. */
    public function exportRows(CarbonImmutable $from, CarbonImmutable $to): array
    {
        $report = $this->report($from, $to, 'day');
        $rows = [['Statistik Web NatNetwork', $from->format('Y-m-d').' hingga '.$to->format('Y-m-d')], [], ['Tarikh', 'Visitors', 'New Visitors', 'Sessions', 'Page Views']];
        foreach ($report['series']['labels'] as $i => $label) {
            $rows[] = [$report['series']['keys'][$i], $report['series']['visitors'][$i], $report['series']['new'][$i], $report['series']['sessions'][$i], $report['series']['pageViews'][$i]];
        }
        $rows[] = [];
        $rows[] = ['Halaman', 'Page Views', 'Unique Visitors', 'Purata Masa (saat)', 'Conversion (%)'];
        foreach ($report['pages'] as $p) {
            $rows[] = [$p['label'], $p['views'], $p['visitors'], $p['avgSeconds'] ?? '', $p['conversion']];
        }
        $rows[] = [];
        $rows[] = ['Sumber Trafik', 'Sessions', '%'];
        foreach ($report['sources']['items'] as $s) {
            $rows[] = [$s['label'], $s['count'], $s['percent']];
        }

        return $rows;
    }

    private function aggregate(CarbonImmutable $from, CarbonImmutable $to): array
    {
        $views = [];
        $sessions = [];
        $visitorDays = [];
        $visitors = [];
        $newVisitors = [];
        $visitorCountry = $visitorDevice = $visitorBrowser = [];

        $query = WebPageView::query()->whereBetween('viewed_at', [$from, $to])->orderBy('viewed_at')
            ->select(['visitor_token', 'session_token', 'route_name', 'is_entry', 'is_new_visitor', 'source', 'device_type', 'browser', 'country', 'duration_seconds', 'viewed_at']);
        foreach ($query->cursor() as $v) {
            $at = CarbonImmutable::instance($v->viewed_at);
            $views[] = ['at' => $at, 'visitor' => $v->visitor_token, 'session' => $v->session_token, 'new' => $v->is_new_visitor, 'entry' => $v->is_entry];
            $s = $sessions[$v->session_token] ??= ['visitor' => $v->visitor_token, 'views' => 0, 'seconds' => 0, 'pages' => [], 'source' => null, 'landing' => null, 'cta' => false];
            $s['views']++;
            $s['seconds'] += (int) $v->duration_seconds;
            $s['pages'][$v->route_name]['views'] = ($s['pages'][$v->route_name]['views'] ?? 0) + 1;
            $s['pages'][$v->route_name]['durations'][] = $v->duration_seconds;
            if ($v->is_entry) {
                $s['source'] = $v->source ?: 'direct';
                $s['landing'] = $v->route_name;
            }
            $sessions[$v->session_token] = $s;
            $visitors[$v->visitor_token] = true;
            $visitorDays[$v->visitor_token.'|'.$at->toDateString()] = true;
            if ($v->is_new_visitor) {
                $newVisitors[$v->visitor_token] = true;
            }
            $visitorCountry[$v->visitor_token] ??= $v->country;
            $visitorDevice[$v->visitor_token] ??= $v->device_type;
            $visitorBrowser[$v->visitor_token] ??= $v->browser;
        }

        $events = WebEvent::query()->where('name', 'cta_click')->whereBetween('occurred_at', [$from, $to])->get(['visitor_token', 'session_token', 'label']);
        foreach ($events as $e) {
            if (isset($sessions[$e->session_token])) {
                $sessions[$e->session_token]['cta'] = true;
            }
        }
        $engagedVisitors = [];
        $engaged = 0;
        foreach ($sessions as $s) {
            if ($s['views'] >= 2 || $s['seconds'] >= 10 || $s['cta']) {
                $engaged++;
                $engagedVisitors[$s['visitor']] = true;
            }
        }

        $leads = BuilderSession::query()->whereNotNull('contact_email')->whereBetween('created_at', [$from, $to])->count()
            + EmailLog::query()->where('category', 'CONTACT')->whereBetween('created_at', [$from, $to])->count();

        return [
            'views' => $views,
            'sessions' => $sessions,
            'pageViews' => count($views),
            'visitors' => count($visitorDays),
            'uniqueVisitors' => count($visitors),
            'newVisitors' => count($newVisitors),
            'sessionCount' => count($sessions),
            'engaged' => $engaged,
            'engagedVisitors' => count($engagedVisitors),
            'events' => $events,
            'ctaVisitors' => $events->pluck('visitor_token')->unique()->count(),
            'leads' => $leads,
            'clients' => User::query()->where('role', User::ROLE_CUSTOMER)->whereBetween('created_at', [$from, $to])->count(),
            'visitorCountry' => $visitorCountry,
            'visitorDevice' => $visitorDevice,
            'visitorBrowser' => $visitorBrowser,
        ];
    }

    private function kpis(array $c, array $p): array
    {
        $rate = fn ($a, $b) => $b > 0 ? round($a / $b * 100, 2) : 0.0;
        $rows = [
            ['key' => 'visitors', 'label' => 'Visitors', 'value' => $c['visitors'], 'prev' => $p['visitors'], 'icon' => 'users', 'tone' => 'blue', 'hint' => 'Jumlah pelawat unik setiap hari'],
            ['key' => 'unique', 'label' => 'Unique Visitors', 'value' => $c['uniqueVisitors'], 'prev' => $p['uniqueVisitors'], 'icon' => 'user', 'tone' => 'blue', 'hint' => 'Pelawat berbeza dalam julat tarikh'],
            ['key' => 'pageViews', 'label' => 'Page Views', 'value' => $c['pageViews'], 'prev' => $p['pageViews'], 'icon' => 'file', 'tone' => 'violet', 'hint' => 'Jumlah paparan halaman'],
            ['key' => 'sessions', 'label' => 'Sessions', 'value' => $c['sessionCount'], 'prev' => $p['sessionCount'], 'icon' => 'target', 'tone' => 'green', 'hint' => 'Lawatan (tamat selepas 30 minit tidak aktif)'],
            ['key' => 'new', 'label' => 'New Visitors', 'value' => $c['newVisitors'], 'prev' => $p['newVisitors'], 'icon' => 'user-plus', 'tone' => 'blue', 'hint' => 'Pelawat kali pertama'],
            ['key' => 'engagement', 'label' => 'Engagement Rate', 'value' => $rate($c['engaged'], $c['sessionCount']), 'prev' => $rate($p['engaged'], $p['sessionCount']), 'percent' => true, 'icon' => 'bars', 'tone' => 'blue', 'hint' => 'Sesi ≥ 2 halaman, ≥ 10 saat atau klik CTA'],
            ['key' => 'leads', 'label' => 'Leads / Enquiry', 'value' => $c['leads'], 'prev' => $p['leads'], 'icon' => 'chat', 'tone' => 'blue', 'hint' => 'Maklumat hubungan di Builder + borang Hubungi Kami'],
            ['key' => 'conversion', 'label' => 'Conversion Rate', 'value' => $rate($c['leads'], $c['sessionCount']), 'prev' => $rate($p['leads'], $p['sessionCount']), 'percent' => true, 'icon' => 'funnel', 'tone' => 'orange', 'hint' => 'Leads ÷ Sessions'],
        ];

        return array_map(function ($k) {
            $k['change'] = $k['prev'] > 0 ? round(($k['value'] - $k['prev']) / $k['prev'] * 100, 1) : null;

            return $k;
        }, $rows);
    }

    private function series(array $views, CarbonImmutable $from, CarbonImmutable $to, string $bucket): array
    {
        $keyOf = fn (CarbonImmutable $d) => match ($bucket) { 'hour' => $d->format('Y-m-d H'), 'week' => $d->startOfWeek()->toDateString(), default => $d->toDateString() };
        $buckets = [];
        $period = match ($bucket) {
            'hour' => CarbonPeriod::create($from->startOfHour(), '1 hour', $to),
            'week' => CarbonPeriod::create($from->startOfWeek(), '1 week', $to),
            default => CarbonPeriod::create($from->startOfDay(), '1 day', $to),
        };
        foreach ($period as $d) {
            $d = CarbonImmutable::instance($d);
            $buckets[$keyOf($d)] = ['label' => match ($bucket) { 'hour' => $d->format('H:00'), 'week' => $d->format('j M'), default => $d->format('j M') }, 'visitors' => [], 'new' => [], 'sessions' => [], 'pv' => 0];
        }
        foreach ($views as $v) {
            $k = $keyOf($v['at']);
            if (! isset($buckets[$k])) {
                continue;
            }
            $buckets[$k]['pv']++;
            $buckets[$k]['visitors'][$v['visitor']] = true;
            if ($v['new']) {
                $buckets[$k]['new'][$v['visitor']] = true;
            }
            if ($v['entry']) {
                $buckets[$k]['sessions'][$v['session']] = true;
            }
        }

        return [
            'keys' => array_keys($buckets),
            'labels' => array_column($buckets, 'label'),
            'visitors' => array_map(fn ($b) => count($b['visitors']), array_values($buckets)),
            'new' => array_map(fn ($b) => count($b['new']), array_values($buckets)),
            'sessions' => array_map(fn ($b) => count($b['sessions']), array_values($buckets)),
            'pageViews' => array_column($buckets, 'pv'),
        ];
    }

    private function sources(array $c): array
    {
        $labels = config('analytics.sources') + ['other' => 'Lain-lain'];
        $counts = array_fill_keys(array_keys($labels), 0);
        foreach ($c['sessions'] as $s) {
            // Sesi yang bermula sebelum julat tarikh tiada halaman masuk dalam julat → Lain-lain.
            $key = $s['source'] ?? 'other';
            $counts[isset($counts[$key]) ? $key : 'other']++;
        }
        $total = array_sum($counts);
        $items = [];
        foreach ($counts as $key => $n) {
            if ($key === 'other' && $n === 0) {
                continue;
            }
            $items[] = ['key' => $key, 'label' => $labels[$key], 'count' => $n, 'percent' => $total ? round($n / $total * 100, 1) : 0];
        }

        return ['total' => $total, 'visitors' => $c['uniqueVisitors'], 'items' => $items];
    }

    private function pages(array $c): array
    {
        $pages = [];
        foreach ($c['sessions'] as $s) {
            foreach ($s['pages'] as $route => $p) {
                $row = $pages[$route] ??= ['views' => 0, 'visitors' => [], 'durations' => [], 'sessions' => 0, 'ctaSessions' => 0];
                $row['views'] += $p['views'];
                $row['visitors'][$s['visitor']] = true;
                array_push($row['durations'], ...array_filter($p['durations'], fn ($d) => $d !== null));
                $row['sessions']++;
                $row['ctaSessions'] += $s['cta'] ? 1 : 0;
                $pages[$route] = $row;
            }
        }
        uasort($pages, fn ($a, $b) => $b['views'] <=> $a['views']);

        return collect($pages)->map(fn ($p, $route) => [
            'label' => config('analytics.pages')[$route] ?? $route,
            'views' => $p['views'],
            'visitors' => count($p['visitors']),
            'avgSeconds' => $p['durations'] ? (int) round(array_sum($p['durations']) / count($p['durations'])) : null,
            'conversion' => $p['sessions'] ? round($p['ctaSessions'] / $p['sessions'] * 100, 1) : 0,
        ])->values()->all();
    }

    private function landings(array $c): array
    {
        $counts = collect($c['sessions'])->filter(fn ($s) => $s['landing'])->countBy('landing')->sortDesc();
        $total = $counts->sum();

        return $counts->map(fn ($n, $route) => ['label' => config('analytics.pages')[$route] ?? $route, 'count' => $n, 'percent' => $total ? round($n / $total * 100, 1) : 0])->values()->all();
    }

    private function funnel(array $c): array
    {
        $base = $c['uniqueVisitors'];
        $pct = fn ($n) => $base ? round($n / $base * 100, 2) : 0;

        return [
            ['label' => 'Visitors', 'value' => $base, 'percent' => $base ? 100 : 0, 'icon' => 'user-plus', 'tone' => 'blue'],
            ['label' => 'Engagement', 'value' => $c['engagedVisitors'], 'percent' => $pct($c['engagedVisitors']), 'icon' => 'bars', 'tone' => 'green'],
            ['label' => 'CTA Click', 'value' => $c['ctaVisitors'], 'percent' => $pct($c['ctaVisitors']), 'icon' => 'cursor', 'tone' => 'orange'],
            ['label' => 'Enquiry / Lead', 'value' => $c['leads'], 'percent' => $pct($c['leads']), 'icon' => 'chat', 'tone' => 'violet'],
            ['label' => 'Client', 'value' => $c['clients'], 'percent' => $pct($c['clients']), 'icon' => 'badge', 'tone' => 'red'],
        ];
    }

    private function ctas(array $c): array
    {
        $counts = $c['events']->countBy('label')->sortDesc();

        return $counts->map(fn ($n, $key) => ['label' => config('analytics.ctas')[$key] ?? $key, 'clicks' => $n, 'ctr' => $c['pageViews'] ? round($n / $c['pageViews'] * 100, 1) : 0])->values()->all();
    }

    private function affiliate(CarbonImmutable $from, CarbonImmutable $to, array $c): array
    {
        $clicks = AffiliateClick::query()->whereBetween('clicked_at', [$from, $to]);
        $total = (clone $clicks)->count();
        $rows = [
            ['label' => 'Total Clicks', 'value' => $total],
            ['label' => 'Unique Clicks', 'value' => (clone $clicks)->distinct()->count('visitor_token')],
            ['label' => 'Affiliate Visitors', 'value' => collect($c['sessions'])->where('source', 'affiliate')->pluck('visitor')->unique()->count()],
            ['label' => 'Registrations', 'value' => Affiliate::query()->whereBetween('created_at', [$from, $to])->count()],
            ['label' => 'Client Referrals', 'value' => AffiliateClientLink::query()->whereBetween('linked_at', [$from, $to])->count()],
        ];

        return array_map(fn ($r) => $r + ['percent' => $total ? round($r['value'] / $total * 100, 1) : 0], $rows);
    }

    private function share(array $byVisitor, callable $label): array
    {
        $counts = collect($byVisitor)->countBy(fn ($v) => $v ?? '')->sortDesc();
        $total = $counts->sum();

        return $counts->map(fn ($n, $key) => ['key' => $key, 'label' => $label($key === '' ? null : $key), 'count' => $n, 'percent' => $total ? round($n / $total * 100, 1) : 0])->values()->all();
    }
}
