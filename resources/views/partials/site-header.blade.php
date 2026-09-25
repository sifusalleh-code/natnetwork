@php($zNav = [['home', 'Home'], ['services', 'Servis'], ['opportunity', 'Peluang'], ['gallery.examples', 'Demo'], ['contact', 'Hubungi'], ['register', 'Daftar'], ['client.login', 'Log Masuk']])
<header class="z-header" x-data="{ open: false }" @keydown.escape.window="open = false">
    <div class="z-wrap">
        <a class="z-logo" href="{{ route('home') }}" aria-label="NatNetwork Synergy — laman utama"><img class="z-logo-image" src="{{ asset('images/brand/natnetwork-synergy-primary-240.webp') }}" width="48" height="48" alt="NatNetwork Synergy"><span class="z-logo-text"><b>NatNetwork Synergy</b><small>Digital Technology Solutions</small></span></a>
        <nav class="z-nav" aria-label="Navigasi utama">@foreach ($zNav as [$zRoute, $zLabel])<a href="{{ route($zRoute) }}"{!! request()->routeIs($zRoute) ? ' aria-current="page"' : '' !!}>{{ $zLabel }}</a>@endforeach</nav>
        <a class="z-button" href="{{ route('builder.start') }}">Mulakan Projek <svg class="z-icon" viewBox="0 0 24 24" aria-hidden="true"><path d="M5 12h14"/><path d="m12 5 7 7-7 7"/></svg></a>
        <button class="z-menu" type="button" @click="open = !open" :aria-expanded="open.toString()" aria-controls="z-mobile-nav" aria-label="Buka menu" x-text="open ? '×' : '☰'">☰</button>
    </div>
    <nav id="z-mobile-nav" class="z-mobile-nav" :class="{ 'is-open': open }" aria-label="Navigasi mudah alih">@foreach ($zNav as [$zRoute, $zLabel])<a href="{{ route($zRoute) }}" @click="open = false"{!! request()->routeIs($zRoute) ? ' aria-current="page"' : '' !!}>{{ $zLabel }}</a>@endforeach<a class="z-button" href="{{ route('builder.start') }}">Mulakan Projek</a></nav>
</header>
