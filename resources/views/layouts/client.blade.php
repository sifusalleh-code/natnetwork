<!doctype html>
<html lang="ms">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    @include('partials.seo', ['title' => $title ?? 'Log masuk pelanggan | NatNetwork Synergy'])
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="auth-body">
    <a class="skip-link" href="#content">Terus ke kandungan</a>
    @include('partials.site-header')
    <main @class(["auth-page", "is-wide" => $wide ?? false]) id="content">@yield('content')</main>
    @include('partials.web-analytics')
</body>
</html>
