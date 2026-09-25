@php($affiliateUser = auth('affiliate')->user())
@php($portalOpen = $affiliateUser?->isProfileComplete())
<!doctype html>
<html lang="ms">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title>{{ $title ?? 'Affiliate' }} · NatNetwork Synergy</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @stack('styles')
</head>
<body>
@include('partials.impersonation-banner')
<div class="aff-app">
    <aside class="aff-side">
        <a class="aff-brand" href="{{ $portalOpen ? route('affiliate.dashboard') : route('affiliate.profile') }}"><img src="{{ asset('images/brand/natnetwork-synergy-primary-240.webp') }}" width="40" height="40" alt="NatNetwork Synergy">Affiliate</a>
        <nav class="aff-nav" aria-label="Menu affiliate">
            @if ($portalOpen)
                <a @class(['is-active' => ($active ?? '') === 'dashboard']) href="{{ route('affiliate.dashboard') }}">@include('partials.portal-icon', ['name' => 'dashboard'])Dashboard</a>
                <a @class(['is-active' => ($active ?? '') === 'customers']) href="{{ route('affiliate.customers') }}">@include('partials.portal-icon', ['name' => 'customers'])Pelanggan</a>
                <a @class(['is-active' => ($active ?? '') === 'wallet']) href="{{ route('affiliate.wallet') }}">@include('partials.portal-icon', ['name' => 'wallet'])Wallet</a>
                <a @class(['is-active' => ($active ?? '') === 'studio-poster']) href="{{ route('affiliate.studio-poster') }}">@include('partials.portal-icon', ['name' => 'studio-poster'])Studio Poster</a>
            @endif
            <a @class(['is-active' => ($active ?? '') === 'profile']) href="{{ route('affiliate.profile') }}">@include('partials.portal-icon', ['name' => 'profile'])Profil Saya</a>
            <form method="post" action="{{ route('affiliate.logout') }}">@csrf<button type="submit">@include('partials.portal-icon', ['name' => 'logout'])Log keluar</button></form>
        </nav>
    </aside>
    <main class="aff-main" id="content">
        @if (session('status'))<p class="notice">{{ session('status') }}</p>@endif
        @yield('content')
    </main>
</div>
<nav class="aff-bottom" aria-label="Menu affiliate (mudah alih)">
    @if ($portalOpen)
        <a @class(['is-active' => ($active ?? '') === 'dashboard']) href="{{ route('affiliate.dashboard') }}">Dashboard</a>
        <a @class(['is-active' => ($active ?? '') === 'customers']) href="{{ route('affiliate.customers') }}">Pelanggan</a>
        <a @class(['is-active' => ($active ?? '') === 'wallet']) href="{{ route('affiliate.wallet') }}">Wallet</a>
        <a @class(['is-active' => ($active ?? '') === 'studio-poster']) href="{{ route('affiliate.studio-poster') }}">Poster</a>
    @endif
    <a @class(['is-active' => ($active ?? '') === 'profile']) href="{{ route('affiliate.profile') }}">Profil</a>
</nav>
</body>
</html>
