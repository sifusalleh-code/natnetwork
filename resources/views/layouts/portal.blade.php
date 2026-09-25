@php
    $menu = [
        'dashboard' => ['Dashboard', 'client.dashboard', '⌂'],
        'projects' => ['Projects', 'client.projects', '▤'],
        'quotations' => ['Quotations', 'client.quotations', '✎'],
        'billing' => ['Billing', 'client.billing', '◒'],
        'files' => ['Files', 'client.files', '▣'],
        'support' => ['Support', 'client.support', '☏'],
        'notifications' => ['Notifications', 'client.notifications', '◔'],
        'profile' => ['Profile', 'client.profile', '◉'],
    ];
    $mobileMenu = ['dashboard', 'projects', 'billing', 'notifications', 'profile'];
    // Sebelum bayaran pertama berjaya: hanya menu langkah Start Project.
    $portalReady = auth('client')->check() && \App\Http\Middleware\EnsureClientAuthenticated::portalReady((int) auth('client')->id());
    if (! $portalReady) {
        $menu = ['start' => ['Start Project', 'builder.start', '▶'], 'quotations' => $menu['quotations'], 'billing' => $menu['billing'], 'notifications' => $menu['notifications'], 'profile' => $menu['profile']];
        $mobileMenu = ['start', 'quotations', 'billing', 'profile'];
    }
    $unread = auth('client')->check() ? app(\App\Engines\Communication\Services\NotificationService::class)->unreadForClient((int) auth('client')->id()) : 0;
@endphp
<!doctype html>
<html lang="ms">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title>{{ $title ?? 'Portal' }} · NatNetwork Synergy</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @stack('styles')
</head>
<body>
@include('partials.impersonation-banner')
<div class="prt-app">
    <aside class="prt-side">
        <a class="prt-brand" href="{{ route($portalReady ? 'client.dashboard' : 'builder.start') }}"><img src="{{ asset('images/brand/natnetwork-synergy-primary-240.webp') }}" width="40" height="40" alt="NatNetwork Synergy">Portal Pelanggan</a>
        <nav class="prt-nav" aria-label="Menu portal">
            @foreach ($menu as $key => [$label, $route, $icon])
                <a @class(['is-active' => ($active ?? '') === $key]) href="{{ route($route) }}">@include('partials.portal-icon', ['name' => $key]){{ $label }}@if ($key === 'notifications' && $unread) <span class="prt-badge warn" style="margin-left: auto;">{{ $unread }}</span>@endif</a>
            @endforeach
            <form method="post" action="{{ route('client.logout') }}">@csrf<button type="submit">@include('partials.portal-icon', ['name' => 'logout'])Logout</button></form>
        </nav>
    </aside>
    <main class="prt-main" id="content">
        @if (session('status'))<p class="notice">{{ session('status') }}</p>@endif
        @if ($errors->any())<div class="prt-errors">@foreach ($errors->all() as $error)<div>{{ $error }}</div>@endforeach</div>@endif
        @yield('content')
    </main>
</div>
<nav class="prt-bottom" aria-label="Menu portal (mudah alih)">
    @foreach ($mobileMenu as $key)
        <a @class(['is-active' => ($active ?? '') === $key]) href="{{ route($menu[$key][1]) }}">{{ $menu[$key][0] }}@if ($key === 'notifications' && $unread) ({{ $unread }})@endif</a>
    @endforeach
</nav>
</body>
</html>
