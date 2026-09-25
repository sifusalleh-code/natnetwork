@php
    $billingMode = \App\Engines\Billing\Models\BillingSetting::current()->active_mode;
    $admIcon = function (string $name) {
        $p = [
            'home' => '<path d="m3 11 9-7 9 7"/><path d="M5 10v10h14V10M10 20v-6h4v6"/>',
            'cart' => '<circle cx="9" cy="20" r="1.3"/><circle cx="18" cy="20" r="1.3"/><path d="M2.5 3h2.2l2.4 12h11.4l2-8H6"/>',
            'folder' => '<path d="M3 7a2 2 0 0 1 2-2h4l2 2h8a2 2 0 0 1 2 2v8a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2Z"/>',
            'receipt' => '<path d="M6 3h12v18l-3-2-3 2-3-2-3 2Z"/><path d="M9 8h6M9 12h6M9 16h3"/>',
            'chart' => '<path d="M4 20V10M10 20V4M16 20v-7M22 20H2"/>',
            'users' => '<circle cx="9" cy="8" r="3.5"/><path d="M2.5 20a6.5 6.5 0 0 1 13 0"/><path d="M16 4.5a3.5 3.5 0 0 1 0 7M18 14a6 6 0 0 1 3.5 6"/>',
            'share' => '<circle cx="18" cy="5" r="3"/><circle cx="6" cy="12" r="3"/><circle cx="18" cy="19" r="3"/><path d="m8.6 13.5 6.8 4M15.4 6.5l-6.8 4"/>',
            'chevron' => '<path d="m6 9 6 6 6-6"/>',
            'dot' => '<circle cx="12" cy="12" r="3"/>',
            'logout' => '<path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4M16 17l5-5-5-5M21 12H9"/>',
            'menu' => '<path d="M4 6h16M4 12h16M4 18h16"/>',
        ][$name] ?? '';

        return '<svg class="ads-i" viewBox="0 0 24 24" aria-hidden="true" focusable="false">'.$p.'</svg>';
    };
    $onStats = request()->routeIs('admin.web-stats');
    $statsLink = fn (string $anchor) => ($onStats ? '' : route('admin.web-stats')).'#'.$anchor;
    $admGroups = [
        ['Sales', 'cart', [['admin.sales.quotations', 'Quotations', 'admin.sales.*'], ['admin.orders', 'Orders', 'admin.orders']]],
        ['Projects', 'folder', [['admin.projects.index', 'Projects', ['admin.projects.index', 'admin.projects.show']], ['admin.projects.queue', 'Development Queue', 'admin.projects.queue'], ['admin.change-requests.index', 'Change Requests', 'admin.change-requests.*'], ['admin.support.index', 'Support', 'admin.support.*']]],
        ['Billing', 'receipt', [['admin.billing.invoices', 'Invois', 'admin.billing.invoice*'], ['admin.billing.payments', 'Bayaran', 'admin.billing.payments'], ['admin.billing.test', 'Uji Aliran Bayaran', 'admin.billing.test'], ['admin.billing.settings', 'Tetapan Billplz', 'admin.billing.settings'], ['admin.communication.email', 'Tetapan Emel', 'admin.communication.*'], ['admin.seo', 'Tetapan SEO', 'admin.seo*']]],
        ['Akaun pengguna', 'users', [['admin.clients.index', 'Pelanggan', 'admin.clients.*'], ['admin.affiliates.index', 'Affiliate', 'admin.affiliates.*'], ['admin.partners.index', 'Partnership', ['admin.partners.index', 'admin.partners.show']], ['admin.partners.pool', 'Pool Partnership', 'admin.partners.pool']]],
        ['Affiliate', 'share', [['admin.affiliate.withdrawals', 'Withdrawal Affiliate', 'admin.affiliate.withdrawals*'], ['admin.affiliate.posters', 'Poster Affiliate', 'admin.affiliate.posters*'], ['admin.affiliate.settings', 'Tetapan Affiliate', 'admin.affiliate.settings']]],
    ];
    $admin = auth('admin')->user();
@endphp
<!doctype html>
<html lang="ms">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title>{{ $title ?? 'Admin' }} · NatNetwork Admin</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body>
<div class="adm-app" x-data="{ nav: false }" @keydown.escape.window="nav = false">
    <div class="ads-backdrop" x-show="nav" x-cloak @click="nav = false"></div>
    <aside class="adm-side" :class="{ 'is-open': nav }" aria-label="Menu admin">
        <a class="adm-brand" href="{{ route('admin.dashboard') }}"><img src="{{ asset('images/brand/natnetwork-synergy-primary-240.webp') }}" width="40" height="40" alt=""><span><b>NatNetwork</b><small>Admin</small></span></a>
        <nav id="adm-nav" class="adm-nav">
            <a @class(['ads-link', 'is-active' => request()->routeIs('admin.dashboard')]) href="{{ route('admin.dashboard') }}">{!! $admIcon('home') !!}<span>Dashboard</span></a>

            <div class="ads-group" x-data="{ open: {{ $onStats ? 'true' : 'false' }} }">
                <button type="button" @class(['ads-link', 'is-active' => $onStats]) @click="open = !open" :aria-expanded="open.toString()">{!! $admIcon('chart') !!}<span>Statistik Web</span><span class="ads-chev" :class="{ 'is-open': open }">{!! $admIcon('chevron') !!}</span></button>
                <div class="ads-sub" x-show="open" @unless ($onStats) x-cloak @endunless>
                    @foreach ([['ringkasan', 'Ringkasan'], ['trafik', 'Trafik & Sumber'], ['halaman', 'Halaman Popular'], ['penukaran', 'Penukaran (Conversion)'], ['affiliate-trafik', 'Affiliate Trafik'], ['lokasi', 'Lokasi Pengunjung'], ['peranti', 'Peranti & Browser']] as [$anchor, $label])
                        <a href="{{ $statsLink($anchor) }}" @click="nav = false">{!! $admIcon('dot') !!}{{ $label }}</a>
                    @endforeach
                    <a href="{{ route('admin.web-stats.export', request()->only(['range', 'from', 'to'])) }}">{!! $admIcon('dot') !!}Export Laporan</a>
                </div>
            </div>

            @foreach ($admGroups as [$group, $icon, $links])
                @php($groupActive = collect($links)->contains(fn ($l) => request()->routeIs(...(array) $l[2])))
                <div class="ads-group" x-data="{ open: {{ $groupActive ? 'true' : 'false' }} }">
                    <button type="button" @class(['ads-link', 'is-active' => $groupActive]) @click="open = !open" :aria-expanded="open.toString()">{!! $admIcon($icon) !!}<span>{{ $group }}</span><span class="ads-chev" :class="{ 'is-open': open }">{!! $admIcon('chevron') !!}</span></button>
                    <div class="ads-sub" x-show="open" @unless ($groupActive) x-cloak @endunless>
                        @foreach ($links as [$route, $label, $pattern])
                            <a @class(['is-current' => request()->routeIs(...(array) $pattern)]) href="{{ route($route) }}">{!! $admIcon('dot') !!}{{ $label }}</a>
                        @endforeach
                    </div>
                </div>
            @endforeach
        </nav>
        <form class="ads-logout" method="post" action="{{ route('admin.logout') }}">@csrf<button type="submit">{!! $admIcon('logout') !!}<span>Log keluar</span></button></form>
        <p class="ads-copy">NatNetwork Synergy<br>© {{ now()->year }}. Hak cipta terpelihara.</p>
    </aside>
    <div class="ads-body">
        <header class="ads-topbar">
            <button type="button" class="ads-burger" @click="nav = true" aria-controls="adm-nav" :aria-expanded="nav.toString()" aria-label="Buka menu">{!! $admIcon('menu') !!}</button>
            <div class="ads-topbar-slot">@yield('topbar')</div>
            <div class="ads-user" x-data="{ open: false }" @click.outside="open = false">
                <button type="button" @click="open = !open" :aria-expanded="open.toString()"><span class="ads-avatar" aria-hidden="true">{{ mb_strtoupper(mb_substr((string) ($admin?->name ?? 'A'), 0, 1)) }}</span><span class="ads-user-name">{{ $admin?->name ?? 'Admin' }}</span>{!! $admIcon('chevron') !!}</button>
                <div class="ads-user-menu" x-show="open" x-cloak>
                    <form method="post" action="{{ route('admin.logout') }}">@csrf<button type="submit">{!! $admIcon('logout') !!} Log keluar</button></form>
                </div>
            </div>
        </header>
        <main @class(['adm-main', 'is-wide' => $wide ?? false]) id="content">
            <div @class(['adm-mode', 'is-sandbox' => $billingMode !== 'PRODUCTION', 'is-production' => $billingMode === 'PRODUCTION'])>
                <span>Mod pembayaran: {{ $billingMode }}</span>
                <span>{{ $billingMode === 'PRODUCTION' ? 'Transaksi sebenar direkodkan.' : 'Ujian sahaja — rekod TEST-*, tidak masuk hasil atau komisyen.' }}</span>
            </div>
            @if (session('status'))<p class="notice">{{ session('status') }}</p>@endif
            @if ($errors->any())<div class="adm-danger">@foreach ($errors->all() as $error)<div>{{ $error }}</div>@endforeach</div><br>@endif
            @yield('content')
        </main>
    </div>
</div>
</body>
</html>
