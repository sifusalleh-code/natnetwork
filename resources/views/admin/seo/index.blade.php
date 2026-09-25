@extends('layouts.admin', ['title' => 'Tetapan SEO'])

@section('content')
<header class="adm-top"><div><h1>Tetapan SEO</h1><p>Urus tajuk, huraian, imej perkongsian (Open Graph) dan status index halaman awam. Medan kosong menggunakan nilai asal.</p></div></header>

<div class="adm-grid">
    <article class="card">
        <h2>Tetapan umum</h2>
        <form method="post" action="{{ route('admin.seo.settings') }}" enctype="multipart/form-data" class="adm-form" x-data="{ d: @js(old('default_description', $settings->default_description) ?? '') }">
            @csrf @method('PUT')
            <label>Nama laman<input name="site_name" maxlength="80" value="{{ old('site_name', $settings->site_name) }}" required></label>
            <label>Huraian lalai
                <textarea name="default_description" maxlength="300" x-model="d">{{ old('default_description', $settings->default_description) }}</textarea>
            </label>
            <span class="adm-help"><span x-text="d.length">0</span> aksara — disyorkan 120–160. Digunakan jika halaman tiada huraian sendiri.</span>
            <label>Imej perkongsian lalai (1200×630)<input type="file" name="default_og_image" accept="image/jpeg,image/png,image/webp"></label>
            <span class="adm-help">JPG/PNG/WebP, min 600×315, maks 4MB. Imej dipotong automatik kepada 1200×630.</span>
            <div class="seo-og"><img src="{{ $seo->imageUrl($settings->default_og_image) ?? asset(config('seo.default_og_image')) }}" alt="Imej perkongsian lalai" width="240" height="126" loading="lazy"></div>
            @if ($settings->default_og_image)<label class="check"><input type="checkbox" name="remove_og_image" value="1"> Guna semula imej asal sistem</label>@endif
            <label>Google Search Console (kod verifikasi)<input name="google_site_verification" maxlength="120" value="{{ old('google_site_verification', $settings->google_site_verification) }}" placeholder="Nilai content sahaja"></label>
            <label>Bing Webmaster (kod verifikasi)<input name="bing_site_verification" maxlength="120" value="{{ old('bing_site_verification', $settings->bing_site_verification) }}" placeholder="Nilai content sahaja"></label>
            <button class="button" type="submit">Simpan tetapan umum</button>
        </form>
    </article>

    <article class="card">
        <h2>Fail untuk enjin carian</h2>
        <dl class="adm-dl">
            <div><dt>Sitemap</dt><dd><a href="{{ route('seo.sitemap') }}" target="_blank" rel="noopener">{{ $seo->absolute('/sitemap.xml') }}</a></dd></div>
            <div><dt>Robots</dt><dd>{{ $seo->absolute('/robots.txt') }}</dd></div>
            <div><dt>Halaman dalam sitemap</dt><dd>{{ $pages->where('indexable', true)->count() }} / {{ $pages->count() }}</dd></div>
        </dl>
        <p class="adm-help">Hantar URL sitemap di Google Search Console dan Bing Webmaster selepas kod verifikasi disimpan. Portal, admin, login, billing dan builder specification sentiasa noindex.</p>
    </article>
</div>

<h2 style="margin-top: 2rem;">Halaman awam</h2>
<div class="seo-pages">
    @foreach ($pages as $page)
        @php($o = $page['override'])
        @php($pid = 'seo-'.str_replace('.', '-', $page['route']))
        <article class="card seo-page" id="{{ $pid }}" x-data="{ t: @js($o?->title ?? ''), d: @js($o?->description ?? ''), dt: @js($page['default_title']), dd: @js($page['default_description']) }">
            <div class="seo-page-head">
                <h3>{{ $page['label'] }}</h3>
                <span @class(['adm-badge', 'PAID' => $page['indexable']])>{{ $page['indexable'] ? 'INDEX' : 'NOINDEX' }}</span>
            </div>
            <div class="seo-serp" aria-hidden="true">
                <span class="seo-serp-url">{{ $page['url'] }}</span>
                <span class="seo-serp-title" x-text="t || dt"></span>
                <span class="seo-serp-desc" x-text="d || dd"></span>
            </div>
            <form method="post" action="{{ route('admin.seo.page', $page['route']) }}" enctype="multipart/form-data" class="adm-form">
                @csrf @method('PUT')
                <label>Tajuk (title)<input name="title" maxlength="90" x-model="t" placeholder="{{ $page['default_title'] }}"></label>
                <span class="adm-help"><span x-text="t.length">0</span> aksara — disyorkan 50–60.</span>
                <label>Huraian (meta description)<textarea name="description" maxlength="300" x-model="d" placeholder="{{ $page['default_description'] }}"></textarea></label>
                <span class="adm-help"><span x-text="d.length">0</span> aksara — disyorkan 120–160.</span>
                <label>Status enjin carian
                    <select name="index_mode">
                        <option value="default" @selected($o?->noindex === null)>Ikut asal ({{ $page['default_index'] ? 'index' : 'noindex' }})</option>
                        <option value="index" @selected($o?->noindex === false)>Index — papar dalam carian & sitemap</option>
                        <option value="noindex" @selected($o?->noindex === true)>Noindex — sembunyikan daripada carian</option>
                    </select>
                </label>
                <label>Imej perkongsian halaman<input type="file" name="og_image" accept="image/jpeg,image/png,image/webp"></label>
                @if ($o?->og_image)
                    <div class="seo-og"><img src="{{ $seo->imageUrl($o->og_image) }}" alt="Imej perkongsian {{ $page['label'] }}" width="240" height="126" loading="lazy"></div>
                    <label class="check"><input type="checkbox" name="remove_og_image" value="1"> Buang imej halaman (guna imej lalai)</label>
                @endif
                <button class="button" type="submit">Simpan {{ $page['label'] }}</button>
            </form>
        </article>
    @endforeach
</div>
@endsection
