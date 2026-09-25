{{-- SEO head: title, meta, canonical, Open Graph, Twitter, ikon, verifikasi dan JSON-LD. --}}
@php($seo = app(\App\Engines\Cms\Services\SeoService::class)->resolve(request()->route()?->getName(), $title ?? null, ['services' => $services ?? null]))
<title>{{ $seo['title'] }}</title>
<meta name="description" content="{{ $seo['description'] }}">
<meta name="robots" content="{{ $seo['robots'] }}">
@if ($seo['indexable'])
<link rel="canonical" href="{{ $seo['canonical'] }}">
@endif
<meta property="og:type" content="website">
<meta property="og:site_name" content="{{ $seo['site_name'] }}">
<meta property="og:locale" content="{{ $seo['locale'] }}">
<meta property="og:title" content="{{ $seo['title'] }}">
<meta property="og:description" content="{{ $seo['description'] }}">
<meta property="og:url" content="{{ $seo['canonical'] }}">
<meta property="og:image" content="{{ $seo['image'] }}">
<meta property="og:image:width" content="{{ $seo['image_width'] }}">
<meta property="og:image:height" content="{{ $seo['image_height'] }}">
<meta property="og:image:alt" content="{{ $seo['site_name'] }}">
<meta name="twitter:card" content="summary_large_image">
<meta name="twitter:title" content="{{ $seo['title'] }}">
<meta name="twitter:description" content="{{ $seo['description'] }}">
<meta name="twitter:image" content="{{ $seo['image'] }}">
<meta name="theme-color" content="{{ $seo['theme_color'] }}">
<link rel="icon" href="{{ asset('favicon.ico') }}" sizes="48x48">
<link rel="icon" type="image/png" sizes="32x32" href="{{ asset('images/seo/favicon-32.png') }}">
<link rel="apple-touch-icon" href="{{ asset('images/seo/apple-touch-icon.png') }}">
<link rel="manifest" href="{{ asset('site.webmanifest') }}">
@if ($seo['google_verification'])
<meta name="google-site-verification" content="{{ $seo['google_verification'] }}">
@endif
@if ($seo['bing_verification'])
<meta name="msvalidate.01" content="{{ $seo['bing_verification'] }}">
@endif
@if ($seo['json_ld'])
<script type="application/ld+json">{!! json_encode($seo['json_ld'], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP) !!}</script>
@endif
