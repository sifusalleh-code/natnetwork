<!doctype html>
<html lang="ms">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    @include('partials.seo', ['title' => $title ?? 'NatNetwork Synergy'])
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body>
    <a class="skip-link" href="#content">Terus ke kandungan</a>
    @include('partials.site-header')
    <main id="content">
        @if (session('status'))<div class="flash-notice">{{ session('status') }}</div>@endif
        @yield('content')
    </main>
    @include('partials.site-footer')
    @include('partials.web-analytics')
</body>
</html>
