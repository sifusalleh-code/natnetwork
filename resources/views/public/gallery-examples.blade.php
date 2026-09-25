@extends('layouts.public', ['title' => 'Galeri Contoh Website | NatNetwork Synergy'])

@php
    $gal = config('demos');
    $cdn = 'https://images.unsplash.com/';
    $img = fn (string $src, int $w, int $h) => str_starts_with($src, 'photo-') ? $cdn.$src.'?auto=format&fit=crop&q=72&w='.$w.'&h='.$h : asset($src);
    $catIcons = ['landing-page' => 'megaphone', 'website-bisnes' => 'building', 'e-commerce' => 'cart', 'hospitaliti' => 'home', 'pendidikan' => 'book', 'organisasi' => 'users', 'lain-lain' => 'grid'];
    $icon = function (string $name) {
        $p = [
            'grid' => '<rect x="3" y="3" width="7" height="7" rx="1.5"/><rect x="14" y="3" width="7" height="7" rx="1.5"/><rect x="3" y="14" width="7" height="7" rx="1.5"/><rect x="14" y="14" width="7" height="7" rx="1.5"/>',
            'megaphone' => '<path d="M3 11v2a1 1 0 0 0 1 1h3l6 5V5L7 10H4a1 1 0 0 0-1 1Z"/><path d="M17 8a5 5 0 0 1 0 8"/>',
            'building' => '<path d="M4 21V5a2 2 0 0 1 2-2h8a2 2 0 0 1 2 2v16"/><path d="M16 9h2a2 2 0 0 1 2 2v10M3 21h18M8 7h4M8 11h4M8 15h4"/>',
            'cart' => '<circle cx="9" cy="20" r="1.4"/><circle cx="18" cy="20" r="1.4"/><path d="M2.5 3h2.2l2.4 12h11.4l2-8H6"/>',
            'home' => '<path d="m3 11 9-7 9 7"/><path d="M5 10v10h14V10M10 20v-6h4v6"/>',
            'book' => '<path d="M4 5a2 2 0 0 1 2-2h13v16H6a2 2 0 0 0-2 2V5Z"/><path d="M4 19a2 2 0 0 1 2-2h13"/>',
            'users' => '<circle cx="9" cy="8" r="3.5"/><path d="M2.5 20a6.5 6.5 0 0 1 13 0"/><path d="M16 4.5a3.5 3.5 0 0 1 0 7M18 14a6 6 0 0 1 3.5 6"/>',
            'monitor' => '<rect x="3" y="4" width="18" height="12" rx="2"/><path d="M8 20h8M12 16v4"/>',
            'layers' => '<path d="m12 3 9 5-9 5-9-5 9-5Z"/><path d="m3 13 9 5 9-5"/>',
            'gear' => '<circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.7 1.7 0 0 0 .3 1.8l.1.1a2 2 0 1 1-2.8 2.8l-.1-.1a1.7 1.7 0 0 0-1.8-.3 1.7 1.7 0 0 0-1 1.5V21a2 2 0 1 1-4 0v-.1a1.7 1.7 0 0 0-1.1-1.5 1.7 1.7 0 0 0-1.8.3l-.1.1a2 2 0 1 1-2.8-2.8l.1-.1a1.7 1.7 0 0 0 .3-1.8 1.7 1.7 0 0 0-1.5-1H3a2 2 0 1 1 0-4h.1a1.7 1.7 0 0 0 1.5-1.1 1.7 1.7 0 0 0-.3-1.8l-.1-.1a2 2 0 1 1 2.8-2.8l.1.1a1.7 1.7 0 0 0 1.8.3H9a1.7 1.7 0 0 0 1-1.5V3a2 2 0 1 1 4 0v.1a1.7 1.7 0 0 0 1 1.5 1.7 1.7 0 0 0 1.8-.3l.1-.1a2 2 0 1 1 2.8 2.8l-.1.1a1.7 1.7 0 0 0-.3 1.8V9a1.7 1.7 0 0 0 1.5 1H21a2 2 0 1 1 0 4h-.1a1.7 1.7 0 0 0-1.5 1Z"/>',
            'search' => '<circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/>',
            'arrow' => '<path d="M5 12h14M13 6l6 6-6 6"/>',
        ][$name] ?? '';

        return '<svg class="dg-i" viewBox="0 0 24 24" aria-hidden="true" focusable="false">'.$p.'</svg>';
    };
    $items = collect($gal['items'])->values()->map(fn ($d, $i) => $d + ['order' => $i]);
@endphp

@section('content')
<div class="dg" x-data="demoGallery({{ $items->count() }})">
    <section class="dg-hero" aria-labelledby="dg-title">
        <div class="dg-hero-photo" style="background-image:url('{{ $img($gal['hero_photo'], 1500, 760) }}')" aria-hidden="true"></div>
        <div class="dg-wrap dg-hero-inner">
            <p class="dg-eyebrow">Galeri Contoh</p>
            <h1 id="dg-title">Lihat contoh website mengikut <span>kategori servis.</span></h1>
            <p class="dg-lead">Pilih mana-mana contoh untuk membuka demo website sebenar secara penuh. Dapatkan idea reka bentuk, fungsi dan gaya yang sesuai untuk projek anda.</p>
            <ul class="dg-values">
                <li><span class="dg-value-icon">{!! $icon('monitor') !!}</span><span><b>Demo live sebenar</b><small>Buka dan lihat terus</small></span></li>
                <li><span class="dg-value-icon is-green">{!! $icon('layers') !!}</span><span><b>Reka bentuk moden</b><small>Mengikut trend terkini</small></span></li>
                <li><span class="dg-value-icon is-violet">{!! $icon('gear') !!}</span><span><b>Pelbagai industri</b><small>Sesuai untuk pelbagai bisnes</small></span></li>
            </ul>
        </div>
    </section>

    <div class="dg-wrap">
        <div class="dg-toolbar" role="search">
            <div class="dg-cats" role="group" aria-label="Tapis mengikut kategori">
                <button type="button" class="dg-cat" :class="{ 'is-active': cat === 'all' }" :aria-pressed="(cat === 'all').toString()" @click="cat = 'all'">{!! $icon('grid') !!} Semua Kategori</button>
                @foreach ($gal['categories'] as $key => $label)
                    <button type="button" class="dg-cat" :class="{ 'is-active': cat === '{{ $key }}' }" :aria-pressed="(cat === '{{ $key }}').toString()" @click="cat = '{{ $key }}'">{!! $icon($catIcons[$key] ?? 'grid') !!} {{ $label }}</button>
                @endforeach
            </div>
            <div class="dg-tools">
                <label class="dg-search">
                    <span class="dg-sr">Cari contoh website</span>
                    {!! $icon('search') !!}
                    <input type="search" placeholder="Cari contoh website..." x-model.debounce.150ms="q" autocomplete="off">
                </label>
                <label class="dg-sort">
                    <span class="dg-sr">Susun mengikut</span>
                    <select x-model="sort">
                        <option value="latest">Terkini</option>
                        <option value="az">A-Z</option>
                    </select>
                </label>
            </div>
        </div>

        <p class="dg-count" aria-live="polite"><span x-text="shown"></span> contoh dipaparkan</p>

        <section class="dg-grid" aria-label="Contoh website" x-ref="grid">
            @foreach ($items as $demo)
                @php($label = $gal['categories'][$demo['category']] ?? $demo['category'])
                @php($href = preg_match('#^https?://#i', $demo['url']) ? $demo['url'] : rtrim(url('/'), '/').$demo['url'])
                <article class="dg-card" data-cat="{{ $demo['category'] }}" data-title="{{ $demo['title'] }}" data-order="{{ $demo['order'] }}"
                    data-search="{{ mb_strtolower($demo['title'].' '.$label.' '.$demo['description'].' '.implode(' ', $demo['tags'] ?? [])) }}"
                    x-show="match($el)" :style="`order:${orderOf($el)}`">
                    <a class="dg-media" href="{{ $href }}" tabindex="-1" aria-hidden="true">
                        <img src="{{ $img($demo['image_small'] ?? $demo['image'], 640, 360) }}"
                             @if (str_starts_with($demo['image'], 'photo-')) srcset="{{ $img($demo['image'], 640, 360) }} 640w, {{ $img($demo['image'], 960, 540) }} 960w" @else srcset="{{ asset($demo['image_small'] ?? $demo['image']) }} 640w, {{ asset($demo['image']) }} 960w" @endif
                             sizes="(max-width: 48rem) 100vw, (max-width: 75rem) 50vw, 30rem" width="640" height="360" loading="lazy" decoding="async" alt="{{ $demo['image_alt'] }}">
                        <span class="dg-shade"></span>
                        @if (! empty($demo['headline']))<span class="dg-headline">{{ $demo['headline'] }}</span>@endif
                        <span class="dg-badge">{!! $icon($catIcons[$demo['category']] ?? 'grid') !!} {{ $label }}</span>
                    </a>
                    <div class="dg-body">
                        <div class="dg-copy">
                            <h2>{{ $demo['title'] }}</h2>
                            <p>{{ $demo['description'] }}</p>
                        </div>
                        <a class="dg-cta" href="{{ $href }}" aria-label="Buka demo live {{ $demo['title'] }}">Buka demo live {!! $icon('arrow') !!}</a>
                    </div>
                </article>
            @endforeach
        </section>

        <div class="dg-empty" x-show="shown === 0" x-cloak role="status">
            <span class="dg-empty-icon">{!! $icon('search') !!}</span>
            <h2>Tiada contoh ditemui</h2>
            <p>Cuba kategori atau kata kunci lain.</p>
            <button type="button" class="dg-cat" @click="cat = 'all'; q = ''">Papar semua contoh</button>
        </div>
    </div>
</div>

<script>
function demoGallery(total) {
    return {
        cat: 'all', q: '', sort: 'latest', shown: total,
        init() { this.$watch('cat', () => this.count()); this.$watch('q', () => this.count()); },
        match(el) {
            const q = this.q.trim().toLowerCase();
            return (this.cat === 'all' || el.dataset.cat === this.cat) && (! q || q.split(/\s+/).every(w => el.dataset.search.includes(w)));
        },
        orderOf(el) {
            if (this.sort !== 'az') return el.dataset.order;
            const titles = [...this.$refs.grid.children].map(c => c.dataset.title).sort((a, b) => a.localeCompare(b, 'ms'));
            return titles.indexOf(el.dataset.title);
        },
        count() { this.$nextTick(() => { this.shown = [...this.$refs.grid.children].filter(c => this.match(c)).length; }); },
    };
}
</script>
@endsection
