@extends('layouts.admin', ['title' => 'Statistik Web', 'wide' => true])

@php
    $n = fn ($v) => number_format((float) $v);
    $pct = fn ($v, $d = 1) => number_format((float) $v, $d).'%';
    $dur = fn (?int $s) => $s === null ? '—' : ($s >= 60 ? intdiv($s, 60).'m '.str_pad((string) ($s % 60), 2, '0', STR_PAD_LEFT).'s' : $s.'s');
    $ws = function (string $name) {
        $p = [
            'users' => '<circle cx="9" cy="8" r="3.5"/><path d="M2.5 20a6.5 6.5 0 0 1 13 0"/><path d="M16 4.5a3.5 3.5 0 0 1 0 7M18 14a6 6 0 0 1 3.5 6"/>',
            'user' => '<circle cx="12" cy="8" r="4"/><path d="M4 21a8 8 0 0 1 16 0"/>',
            'user-plus' => '<circle cx="10" cy="8" r="4"/><path d="M2 21a8 8 0 0 1 14.5-4.6M19 14v6M16 17h6"/>',
            'file' => '<path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8Z"/><path d="M14 2v6h6M8 13h8M8 17h5"/>',
            'target' => '<circle cx="12" cy="12" r="9"/><circle cx="12" cy="12" r="5"/><circle cx="12" cy="12" r="1.5"/>',
            'bars' => '<path d="M5 20V12M12 20V5M19 20v-9"/>',
            'chat' => '<path d="M21 12a8 8 0 0 1-11.6 7.1L4 20l1-4.6A8 8 0 1 1 21 12Z"/><path d="M8.5 12h.01M12 12h.01M15.5 12h.01"/>',
            'funnel' => '<path d="M3 4h18l-7 8.5V19l-4 2v-8.5Z"/>',
            'chart' => '<path d="M4 20V10M10 20V4M16 20v-7M22 20H2"/>',
            'pie' => '<path d="M21 12A9 9 0 1 1 12 3v9Z"/><path d="M15 3.5A9 9 0 0 1 20.5 9H15Z"/>',
            'live' => '<circle cx="12" cy="12" r="3"/><path d="M6.3 6.3a8 8 0 0 0 0 11.4M17.7 6.3a8 8 0 0 1 0 11.4"/>',
            'layout' => '<rect x="3" y="3" width="18" height="18" rx="2"/><path d="M3 9h18M9 21V9"/>',
            'cursor' => '<path d="m4 4 7 17 2.5-7.5L21 11Z"/>',
            'badge' => '<path d="M12 3 4 6v6c0 5 3.4 8.3 8 9 4.6-.7 8-4 8-9V6Z"/><path d="m8.5 12 2.5 2.5 4.5-5"/>',
            'search' => '<circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/>',
            'share' => '<circle cx="18" cy="5" r="3"/><circle cx="6" cy="12" r="3"/><circle cx="18" cy="19" r="3"/><path d="m8.6 13.5 6.8 4M15.4 6.5l-6.8 4"/>',
            'pin' => '<path d="M12 21s-7-6.2-7-11.5a7 7 0 0 1 14 0C19 14.8 12 21 12 21Z"/><circle cx="12" cy="9.5" r="2.5"/>',
            'device' => '<rect x="5" y="2" width="14" height="20" rx="2"/><path d="M11 18h2"/>',
            'desktop' => '<rect x="3" y="4" width="18" height="12" rx="2"/><path d="M8 20h8M12 16v4"/>',
            'tablet' => '<rect x="4" y="2" width="16" height="20" rx="2"/><path d="M11 18h2"/>',
            'globe' => '<circle cx="12" cy="12" r="9"/><path d="M3 12h18M12 3a14 14 0 0 1 0 18M12 3a14 14 0 0 0 0 18"/>',
            'calendar' => '<rect x="3" y="5" width="18" height="16" rx="2"/><path d="M3 10h18M8 3v4M16 3v4"/>',
            'refresh' => '<path d="M20 11a8 8 0 1 0-2.3 5.7"/><path d="M20 4v7h-7"/>',
            'up' => '<path d="M12 19V5M6 11l6-6 6 6"/>',
            'down' => '<path d="M12 5v14M6 13l6 6 6-6"/>',
            'arrow-down' => '<path d="M12 5v14M7 14l5 5 5-5"/>',
            'download' => '<path d="M12 3v12M7 10l5 5 5-5M4 21h16"/>',
        ][$name] ?? '';

        return '<svg class="ws-i" viewBox="0 0 24 24" aria-hidden="true" focusable="false">'.$p.'</svg>';
    };
    $flag = function (?string $code) {
        $f = [
            'MY' => '<rect width="30" height="20" fill="#fff"/>'.collect(range(0, 13))->map(fn ($i) => $i % 2 === 0 ? '<rect y="'.round($i * 20 / 14, 3).'" width="30" height="'.round(20 / 14, 3).'" fill="#cc0001"/>' : '')->implode('').'<rect width="15" height="11.43" fill="#010066"/><circle cx="6" cy="5.7" r="3.6" fill="#fc0"/><circle cx="7" cy="5.7" r="3" fill="#010066"/><circle cx="11" cy="5.7" r="1.5" fill="#fc0"/>',
            'SG' => '<rect width="30" height="10" fill="#ef3340"/><rect y="10" width="30" height="10" fill="#fff"/><circle cx="6" cy="5" r="3.2" fill="#fff"/><circle cx="7.2" cy="5" r="3" fill="#ef3340"/>',
            'ID' => '<rect width="30" height="10" fill="#ce1126"/><rect y="10" width="30" height="10" fill="#fff"/>',
            'BN' => '<rect width="30" height="20" fill="#f7e017"/><path d="M0 5 30 11v4L0 9Z" fill="#fff"/><path d="M0 9 30 15v4L0 13Z" fill="#000"/><circle cx="15" cy="10" r="3" fill="#cf1126"/>',
            'TH' => '<rect width="30" height="20" fill="#a51931"/><rect y="3.3" width="30" height="13.4" fill="#f4f5f8"/><rect y="6.7" width="30" height="6.6" fill="#2d2a4a"/>',
        ][$code ?? ''] ?? null;

        return $f ? '<svg class="ws-flag" viewBox="0 0 30 20" aria-hidden="true">'.$f.'</svg>' : '<span class="ws-flag is-code" aria-hidden="true">'.e($code ?: '?').'</span>';
    };

    // ---- Traffic chart (SVG) ----
    $s = $report['series'];
    $count = count($s['labels']);
    $W = 720; $H = 250; $L = 46; $R = 14; $T = 14; $B = 30;
    $plotW = $W - $L - $R; $plotH = $H - $T - $B;
    $maxVal = max(1, ...array_merge($s['pageViews'], $s['visitors'], $s['sessions'], $s['new']));
    $mag = 10 ** floor(log10($maxVal));
    $yMax = collect([1, 2, 2.5, 5, 10])->map(fn ($m) => $m * $mag)->first(fn ($v) => $v >= $maxVal) ?? 10 * $mag;
    $yMax = max(4, $yMax);
    $xAt = fn ($i) => $count > 1 ? $L + $i * $plotW / ($count - 1) : $L + $plotW / 2;
    $yAt = fn ($v) => $T + $plotH - ($v / $yMax) * $plotH;
    $path = fn (array $vals) => collect($vals)->map(fn ($v, $i) => ($i ? 'L' : 'M').round($xAt($i), 1).' '.round($yAt($v), 1))->implode(' ');
    $area = fn (array $vals) => $path($vals).' L'.round($xAt($count - 1), 1).' '.($T + $plotH).' L'.round($xAt(0), 1).' '.($T + $plotH).' Z';
    $lines = [['pageViews', 'Page Views', '#8b5cf6'], ['visitors', 'Visitors', '#2563eb'], ['sessions', 'Sessions', '#f59e0b'], ['new', 'New Visitors', '#10b981']];
    $tickEvery = max(1, (int) ceil($count / 7));
    $hasTraffic = array_sum($s['pageViews']) > 0;

    // ---- Donut ----
    $srcColors = ['organic' => '#2563eb', 'social' => '#10b981', 'direct' => '#f59e0b', 'referral' => '#0891b2', 'affiliate' => '#8b5cf6', 'campaign' => '#e11d48', 'other' => '#94a3b8'];
    $sources = $report['sources'];
    $segments = [];
    $offset = 0;
    $nonZero = collect($sources['items'])->where('count', '>', 0)->count();
    foreach ($sources['items'] as $item) {
        if ($item['count'] <= 0) continue;
        $len = $sources['total'] ? $item['count'] / $sources['total'] * 100 : 0;
        $gap = $nonZero > 1 ? min(1, $len / 3) : 0;
        $segments[] = ['color' => $srcColors[$item['key']] ?? '#94a3b8', 'dash' => round(max(0, $len - $gap), 3), 'offset' => round(-$offset, 3), 'label' => $item['label'], 'count' => $item['count'], 'percent' => $item['percent']];
        $offset += $len;
    }

    $rangeQuery = fn (array $extra = []) => array_filter(array_merge(['range' => $range['preset'], 'from' => $range['preset'] === 'custom' ? $range['from']->toDateString() : null, 'to' => $range['preset'] === 'custom' ? $range['to']->toDateString() : null, 'group' => $granularity !== 'day' ? $granularity : null], $extra), fn ($v) => $v !== null);
    $maxLanding = max(1, ...array_column($report['landings'] ?: [['count' => 0]], 'count'));
    $landColors = ['#2563eb', '#10b981', '#f59e0b', '#8b5cf6', '#e11d48', '#0891b2'];
    $toneBg = ['blue' => '#e8f0fe', 'green' => '#e3f7ee', 'orange' => '#fff2df', 'violet' => '#f1eafe', 'red' => '#fde8ec'];
    $toneFg = ['blue' => '#1d6fe0', 'green' => '#0f9f6e', 'orange' => '#e08a00', 'violet' => '#7c4ddb', 'red' => '#d6334f'];
@endphp

@section('topbar')
    <span class="ws-range-pill">{!! $ws('calendar') !!} {{ $range['from']->translatedFormat('j M Y') }} – {{ $range['to']->translatedFormat('j M Y') }}</span>
@endsection

@section('content')
<div class="ws">
    <header class="ws-head" id="ringkasan">
        <div class="ws-title">
            <span class="ws-title-icon">{!! $ws('chart') !!}</span>
            <div><h1>Statistik Web</h1><p>Analitik keseluruhan prestasi website NatNetwork.</p></div>
        </div>
        <div class="ws-controls" x-data="{ custom: {{ $range['preset'] === 'custom' ? 'true' : 'false' }} }">
            <nav class="ws-seg" aria-label="Julat tarikh">
                @foreach (['today' => 'Hari Ini', '7' => '7 Hari', '30' => '30 Hari', '90' => '90 Hari'] as $key => $label)
                    <a @class(['is-active' => $range['preset'] === (string) $key]) href="{{ route('admin.web-stats', array_filter(['range' => $key, 'group' => $granularity !== 'day' ? $granularity : null])) }}">{{ $label }}</a>
                @endforeach
            </nav>
            <div class="ws-custom">
                <button type="button" @class(['ws-btn-light', 'is-active' => $range['preset'] === 'custom']) @click="custom = !custom" :aria-expanded="custom.toString()">{!! $ws('calendar') !!} Custom</button>
                <form class="ws-custom-pop" method="get" action="{{ route('admin.web-stats') }}" x-show="custom" x-cloak @click.outside="custom = false">
                    <input type="hidden" name="range" value="custom">
                    <label>Dari<input type="date" name="from" value="{{ $range['from']->toDateString() }}" max="{{ now()->toDateString() }}" required></label>
                    <label>Hingga<input type="date" name="to" value="{{ $range['to']->toDateString() }}" max="{{ now()->toDateString() }}" required></label>
                    <button class="ws-btn-primary" type="submit">Papar</button>
                </form>
            </div>
            <a class="ws-btn-primary" href="{{ route('admin.web-stats', $rangeQuery()) }}">{!! $ws('refresh') !!} Refresh</a>
        </div>
    </header>

    <section class="ws-kpis" aria-label="Ringkasan">
        @foreach ($report['kpis'] as $k)
            <article class="ws-kpi" title="{{ $k['hint'] }}">
                <span class="ws-kpi-icon" style="--bg: {{ $toneBg[$k['tone']] }}; --fg: {{ $toneFg[$k['tone']] }}">{!! $ws($k['icon']) !!}</span>
                <div>
                    <p class="ws-kpi-label">{{ $k['label'] }}</p>
                    <p class="ws-kpi-value">{{ ! empty($k['percent']) ? $pct($k['value'], 2) : $n($k['value']) }}</p>
                    @if ($k['change'] === null)
                        <p class="ws-kpi-change is-flat">— <small>tiada data banding</small></p>
                    @else
                        <p @class(['ws-kpi-change', 'is-down' => $k['change'] < 0])><span class="ws-delta">{!! $ws($k['change'] < 0 ? 'down' : 'up') !!}{{ number_format(abs($k['change']), 1) }}%</span> <small>vs tempoh lepas</small></p>
                    @endif
                </div>
            </article>
        @endforeach
    </section>

    <div class="ws-row ws-row-1" id="trafik">
        <section class="ws-card ws-traffic" aria-labelledby="ws-traffic-title"
            x-data="{ i: null, d: @js(['labels' => $s['labels'], 'visitors' => $s['visitors'], 'new' => $s['new'], 'sessions' => $s['sessions'], 'pageViews' => $s['pageViews']]), x: @js(array_map(fn ($i) => round($xAt($i) / $W * 100, 3), array_keys($s['labels']))) }">
            <header class="ws-card-head">
                <span class="ws-card-icon">{!! $ws('chart') !!}</span>
                <div><h2 id="ws-traffic-title">Traffic Overview</h2><p>Trend pengunjung, sesi dan paparan halaman dalam tempoh terpilih.</p></div>
                @if ($report['bucket'] !== 'hour')
                    <form method="get" action="{{ route('admin.web-stats') }}" class="ws-group-form">
                        @foreach ($rangeQuery(['group' => null]) as $key => $value)<input type="hidden" name="{{ $key }}" value="{{ $value }}">@endforeach
                        <label class="ws-sr" for="ws-group">Kumpulan</label>
                        <select id="ws-group" name="group" onchange="this.form.submit()"><option value="day" @selected($granularity === 'day')>Setiap Hari</option><option value="week" @selected($granularity === 'week')>Setiap Minggu</option></select>
                    </form>
                @else
                    <span class="ws-chip">Setiap Jam</span>
                @endif
            </header>
            <ul class="ws-legend">
                @foreach ([['visitors', 'Visitors', '#2563eb'], ['new', 'New Visitors', '#10b981'], ['sessions', 'Sessions', '#f59e0b'], ['pageViews', 'Page Views', '#8b5cf6']] as [$key, $label, $color])
                    <li><i style="background: {{ $color }}"></i>{{ $label }}</li>
                @endforeach
            </ul>
            <div class="ws-chart">
                <svg viewBox="0 0 {{ $W }} {{ $H }}" role="img" aria-label="Graf trafik: {{ $n(array_sum($s['pageViews'])) }} paparan halaman dalam tempoh ini">
                    <defs><linearGradient id="ws-pv" x1="0" y1="0" x2="0" y2="1"><stop offset="0" stop-color="#8b5cf6" stop-opacity=".28"/><stop offset="1" stop-color="#8b5cf6" stop-opacity="0"/></linearGradient><linearGradient id="ws-vi" x1="0" y1="0" x2="0" y2="1"><stop offset="0" stop-color="#2563eb" stop-opacity=".16"/><stop offset="1" stop-color="#2563eb" stop-opacity="0"/></linearGradient></defs>
                    @foreach (range(0, 4) as $g)
                        @php($gy = $T + $plotH - $g * $plotH / 4)
                        <line x1="{{ $L }}" x2="{{ $W - $R }}" y1="{{ $gy }}" y2="{{ $gy }}" class="ws-grid"/>
                        <text x="{{ $L - 8 }}" y="{{ $gy + 4 }}" text-anchor="end" class="ws-axis">{{ $n($yMax * $g / 4) }}</text>
                    @endforeach
                    @foreach ($s['labels'] as $i => $label)
                        @if ($i % $tickEvery === 0 || $i === $count - 1)
                            <text x="{{ round($xAt($i), 1) }}" y="{{ $H - 8 }}" text-anchor="{{ $i === 0 ? 'start' : ($i === $count - 1 ? 'end' : 'middle') }}" class="ws-axis">{{ $label }}</text>
                        @endif
                    @endforeach
                    @if ($count > 1)
                        <path d="{{ $area($s['pageViews']) }}" fill="url(#ws-pv)"/>
                        <path d="{{ $area($s['visitors']) }}" fill="url(#ws-vi)"/>
                    @endif
                    @foreach ($lines as [$key, $label, $color])
                        @if ($count > 1)
                            <path d="{{ $path($s[$key]) }}" fill="none" stroke="{{ $color }}" stroke-width="2" stroke-linejoin="round" stroke-linecap="round"/>
                        @else
                            <circle cx="{{ $xAt(0) }}" cy="{{ $yAt($s[$key][0] ?? 0) }}" r="4" fill="{{ $color }}"/>
                        @endif
                    @endforeach
                    <g x-show="i !== null" x-cloak>
                        <line :x1="x[i ?? 0] * {{ $W }} / 100" :x2="x[i ?? 0] * {{ $W }} / 100" y1="{{ $T }}" y2="{{ $T + $plotH }}" class="ws-cross"/>
                        @foreach ($lines as [$key, $label, $color])
                            <circle :cx="x[i ?? 0] * {{ $W }} / 100" :cy="{{ $T + $plotH }} - ((d.{{ $key }}[i ?? 0] || 0) / {{ $yMax }}) * {{ $plotH }}" r="4.5" fill="{{ $color }}" stroke="#fff" stroke-width="2"/>
                        @endforeach
                    </g>
                </svg>
                <div class="ws-hit" @mouseleave="i = null">
                    @foreach ($s['labels'] as $i => $label)
                        <span style="left: {{ round(($count > 1 ? $xAt($i) - $plotW / ($count - 1) / 2 : $L) / $W * 100, 3) }}%; width: {{ round(($count > 1 ? $plotW / ($count - 1) : $plotW) / $W * 100, 3) }}%" @mouseenter="i = {{ $i }}" @touchstart.passive="i = {{ $i }}"></span>
                    @endforeach
                </div>
                <div class="ws-tip" x-show="i !== null" x-cloak :style="`left: ${x[i ?? 0]}%`" :class="{ 'is-left': x[i ?? 0] > 60 }">
                    <b x-text="d.labels[i ?? 0]"></b>
                    <span><i style="background:#2563eb"></i>Visitors <em x-text="(d.visitors[i ?? 0] || 0).toLocaleString()"></em></span>
                    <span><i style="background:#10b981"></i>New Visitors <em x-text="(d.new[i ?? 0] || 0).toLocaleString()"></em></span>
                    <span><i style="background:#f59e0b"></i>Sessions <em x-text="(d.sessions[i ?? 0] || 0).toLocaleString()"></em></span>
                    <span><i style="background:#8b5cf6"></i>Page Views <em x-text="(d.pageViews[i ?? 0] || 0).toLocaleString()"></em></span>
                </div>
                @unless ($hasTraffic)<p class="ws-empty-over">Belum ada trafik direkodkan dalam tempoh ini.</p>@endunless
            </div>
        </section>

        <section class="ws-card ws-sources" aria-labelledby="ws-src-title">
            <header class="ws-card-head">
                <span class="ws-card-icon is-green">{!! $ws('pie') !!}</span>
                <div><h2 id="ws-src-title">Sumber Trafik</h2><p>Dari mana pengunjung datang ke website.</p></div>
            </header>
            <div class="ws-donut-wrap">
                <div class="ws-donut">
                    <svg viewBox="0 0 42 42" role="img" aria-label="Pecahan sumber trafik mengikut sesi">
                        <circle cx="21" cy="21" r="15.9155" fill="none" stroke="#eef3f8" stroke-width="7"/>
                        @foreach ($segments as $seg)
                            <circle cx="21" cy="21" r="15.9155" fill="none" stroke="{{ $seg['color'] }}" stroke-width="7" stroke-dasharray="{{ $seg['dash'] }} {{ 100 - $seg['dash'] }}" stroke-dashoffset="{{ $seg['offset'] }}" transform="rotate(-90 21 21)"><title>{{ $seg['label'] }}: {{ $n($seg['count']) }} sesi ({{ $seg['percent'] }}%)</title></circle>
                        @endforeach
                    </svg>
                    <div class="ws-donut-center"><b>{{ $n($sources['visitors']) }}</b><small>Total Visitors</small></div>
                </div>
                <ul class="ws-src-list">
                    @foreach ($sources['items'] as $item)
                        <li><i style="background: {{ $srcColors[$item['key']] ?? '#94a3b8' }}"></i><span>{{ $item['label'] }}</span><b>{{ $pct($item['percent']) }}</b></li>
                    @endforeach
                </ul>
            </div>
        </section>

        <section class="ws-card ws-live" aria-labelledby="ws-live-title" x-data="wsLive(@js($live), @js(route('admin.web-stats.live')))" x-init="start()">
            <header class="ws-card-head is-compact">
                <div><h2 id="ws-live-title">Pengunjung Semasa <small>(Live)</small></h2></div>
                <span class="ws-live-tag"><i></i> Auto 30s</span>
            </header>
            <p class="ws-live-count"><i class="ws-pulse"></i><b x-text="count">{{ $live['count'] }}</b><span>Pengunjung dalam talian<br><small>aktif dalam {{ config('analytics.live_minutes') }} minit lepas</small></span></p>
            <h3>Halaman sedang dilihat</h3>
            <ul class="ws-live-list">
                <template x-for="p in pages" :key="p.label"><li><span x-text="p.label"></span><b x-text="p.count"></b></li></template>
                <li x-show="pages.length === 0" class="is-empty">Tiada pengunjung aktif sekarang.</li>
            </ul>
        </section>
    </div>

    <div class="ws-row ws-row-2">
        <section class="ws-card" id="halaman" aria-labelledby="ws-pages-title">
            <header class="ws-card-head">
                <span class="ws-card-icon">{!! $ws('chart') !!}</span>
                <div><h2 id="ws-pages-title">Halaman Popular</h2><p>Halaman yang paling banyak dilawati.</p></div>
            </header>
            <div class="ws-table-wrap">
                <table class="ws-table">
                    <thead><tr><th>#</th><th>Halaman</th><th class="num">Page Views</th><th class="num">Unique Visitors</th><th class="num">Purata Masa</th><th class="num" title="Peratus sesi yang melihat halaman ini dan klik CTA">Conversion</th></tr></thead>
                    <tbody>
                        @forelse ($report['pages'] as $i => $page)
                            <tr><td>{{ $i + 1 }}</td><td class="ws-name">{{ $page['label'] }}</td><td class="num">{{ $n($page['views']) }}</td><td class="num">{{ $n($page['visitors']) }}</td><td class="num">{{ $dur($page['avgSeconds']) }}</td><td class="num is-good">{{ $pct($page['conversion']) }}</td></tr>
                        @empty
                            <tr><td colspan="6" class="ws-empty">Belum ada data.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>

        <section class="ws-card" aria-labelledby="ws-landing-title">
            <header class="ws-card-head">
                <span class="ws-card-icon">{!! $ws('layout') !!}</span>
                <div><h2 id="ws-landing-title">Landing Page Performance</h2><p>Halaman pertama yang dilawati pengunjung.</p></div>
            </header>
            <div class="ws-table-wrap">
                <table class="ws-table">
                    <thead><tr><th>Landing Page</th><th class="num">Sessions</th><th class="ws-bar-col"><span class="ws-sr">Bar</span></th><th class="num">%</th></tr></thead>
                    <tbody>
                        @forelse ($report['landings'] as $i => $land)
                            <tr><td class="ws-name">{{ $land['label'] }}</td><td class="num">{{ $n($land['count']) }}</td><td class="ws-bar-col"><span class="ws-bar"><i style="width: {{ round($land['count'] / $maxLanding * 100, 1) }}%; background: {{ $landColors[$i] ?? '#94a3b8' }}"></i></span></td><td class="num">{{ $pct($land['percent'], 0) }}</td></tr>
                        @empty
                            <tr><td colspan="4" class="ws-empty">Belum ada data.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>

        <section class="ws-card" id="penukaran" aria-labelledby="ws-funnel-title">
            <header class="ws-card-head">
                <span class="ws-card-icon">{!! $ws('funnel') !!}</span>
                <div><h2 id="ws-funnel-title">Penukaran / Conversion Funnel</h2><p>Peratus daripada Unique Visitors.</p></div>
            </header>
            <ol class="ws-funnel">
                @foreach ($report['funnel'] as $step)
                    <li style="--bg: {{ $toneBg[$step['tone']] }}; --fg: {{ $toneFg[$step['tone']] }}">
                        <span class="ws-funnel-icon">{!! $ws($step['icon']) !!}</span>
                        <span class="ws-funnel-label">{{ $step['label'] }}</span>
                        <b>{{ $n($step['value']) }}</b>
                        <em>{{ $pct($step['percent'], $step['percent'] >= 10 || $step['percent'] == 0 ? 1 : 2) }}</em>
                    </li>
                @endforeach
            </ol>
        </section>
    </div>

    <div class="ws-row ws-row-3">
        <section class="ws-card" aria-labelledby="ws-cta-title">
            <header class="ws-card-head">
                <span class="ws-card-icon is-violet">{!! $ws('search') !!}</span>
                <div><h2 id="ws-cta-title">Prestasi CTA</h2><p>Klik pada butang utama di website.</p></div>
            </header>
            <table class="ws-table">
                <thead><tr><th>CTA</th><th class="num">Clicks</th><th class="num" title="Klik ÷ Page Views">CTR</th></tr></thead>
                <tbody>
                    @forelse ($report['ctas'] as $cta)
                        <tr><td class="ws-name">{{ $cta['label'] }}</td><td class="num">{{ $n($cta['clicks']) }}</td><td class="num">{{ $pct($cta['ctr']) }}</td></tr>
                    @empty
                        <tr><td colspan="3" class="ws-empty">Belum ada klik CTA.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </section>

        <section class="ws-card" id="affiliate-trafik" aria-labelledby="ws-aff-title">
            <header class="ws-card-head">
                <span class="ws-card-icon is-red">{!! $ws('share') !!}</span>
                <div><h2 id="ws-aff-title">Trafik Affiliate</h2><p>Pengunjung melalui link affiliate.</p></div>
            </header>
            <table class="ws-table">
                <thead><tr><th>Metrik</th><th class="num">Jumlah</th><th class="num" title="Berbanding Total Clicks">%</th></tr></thead>
                <tbody>
                    @foreach ($report['affiliate'] as $row)
                        <tr><td class="ws-name">{{ $row['label'] }}</td><td class="num">{{ $n($row['value']) }}</td><td class="num">{{ $pct($row['percent']) }}</td></tr>
                    @endforeach
                </tbody>
            </table>
        </section>

        <section class="ws-card" id="lokasi" aria-labelledby="ws-geo-title">
            <header class="ws-card-head">
                <span class="ws-card-icon is-green">{!! $ws('pin') !!}</span>
                <div><h2 id="ws-geo-title">Lokasi Pengunjung</h2><p>Negara utama pengunjung.</p></div>
            </header>
            <ul class="ws-meter-list">
                @forelse (array_slice($report['countries'], 0, 6) as $c)
                    <li>{!! $flag($c['key'] ?: null) !!}<span>{{ $c['label'] }}</span><span class="ws-bar"><i style="width: {{ $c['percent'] }}%"></i></span><b>{{ $pct($c['percent']) }}</b></li>
                @empty
                    <li class="ws-empty">Belum ada data.</li>
                @endforelse
            </ul>
        </section>

        <section class="ws-card" id="peranti" aria-labelledby="ws-dev-title">
            <header class="ws-card-head">
                <span class="ws-card-icon">{!! $ws('device') !!}</span>
                <div><h2 id="ws-dev-title">Peranti &amp; Browser</h2><p>Jenis peranti dan pelayar pengunjung.</p></div>
            </header>
            <div class="ws-dev-grid">
                <div>
                    <h3>Peranti</h3>
                    <ul class="ws-meter-list is-compact">
                        @forelse ($report['devices'] as $d)
                            <li>{!! $ws(['mobile' => 'device', 'desktop' => 'desktop', 'tablet' => 'tablet'][$d['key']] ?? 'device') !!}<span>{{ $d['label'] }}</span><span class="ws-bar"><i style="width: {{ $d['percent'] }}%"></i></span><b>{{ $pct($d['percent']) }}</b></li>
                        @empty
                            <li class="ws-empty">Belum ada data.</li>
                        @endforelse
                    </ul>
                </div>
                <div>
                    <h3>Browser</h3>
                    <ul class="ws-meter-list is-compact">
                        @forelse (array_slice($report['browsers'], 0, 6) as $b)
                            <li>{!! $ws('globe') !!}<span>{{ $b['label'] }}</span><span class="ws-bar"><i style="width: {{ $b['percent'] }}%"></i></span><b>{{ $pct($b['percent']) }}</b></li>
                        @empty
                            <li class="ws-empty">Belum ada data.</li>
                        @endforelse
                    </ul>
                </div>
            </div>
        </section>
    </div>

    <p class="ws-foot">Data dijejak oleh website sendiri sejak modul Statistik Web dipasang (tiada data sebelum itu). Paparan oleh Admin dan bot tidak dikira. Lokasi daripada Cloudflare.
        <a href="{{ route('admin.web-stats.export', $rangeQuery()) }}">{!! $ws('download') !!} Export Laporan (CSV)</a></p>
</div>

<script>
function wsLive(initial, url) {
    return {
        count: initial.count, pages: initial.pages,
        start() {
            setInterval(() => {
                if (document.visibilityState !== 'visible') return;
                fetch(url, { headers: { Accept: 'application/json' }, credentials: 'same-origin' })
                    .then(r => r.ok ? r.json() : null).then(d => { if (d) { this.count = d.count; this.pages = d.pages; } }).catch(() => {});
            }, 30000);
        },
    };
}
</script>
@endsection
