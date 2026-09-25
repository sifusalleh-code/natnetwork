{{-- Satu seksyen kategori servis: nombor, label, tajuk, pautan, ilustrasi, dan kad pakej. --}}
@php($catIcons = ['website-development' => 'website', 'e-commerce' => 'cart', 'custom-web-applications' => 'app', 'ai-automation' => 'ai', 'system-api-integration' => 'api', 'maintenance-support' => 'support'])
<section id="{{ $anchor }}" @class(['svc-section', 'is-alt' => $index % 2 === 1]) aria-labelledby="{{ $anchor }}-title" data-svc-section="{{ $service->slug }}">
    <div class="z-wrap">
        <div class="svc-head svc-reveal">
            <div class="svc-head-copy">
                <span class="svc-num" aria-hidden="true">{{ str_pad((string) ($index + 1), 2, '0', STR_PAD_LEFT) }}</span>
                <div>
                    <p class="svc-label">{{ $service->name }}</p>
                    <h2 id="{{ $anchor }}-title">{{ $service->summary }}</h2>
                    @if ($link ?? null)<a class="svc-link" href="{{ $link }}">Lihat harga dan skop <span aria-hidden="true">→</span></a>@endif
                </div>
            </div>
            <div class="svc-head-visual">@include('public.partials.svc-illustration', ['slug' => $service->slug])</div>
        </div>
        @php($isWide = fn ($p) => ($p->price_type ?? null) === 'quote' || str_starts_with((string) $p->slug, 'custom-'))
        @php($gridPackages = $service->packages->reject($isWide)->values())
        @php($widePackages = $service->packages->filter($isWide)->values())
        @if ($gridPackages->isNotEmpty())
            <div @class(['pkg-grid', 'cols-'.($gridPackages->count() > 4 ? 3 : $gridPackages->count())])>
                @foreach ($gridPackages as $package)
                    @include('public.partials.package-card', ['package' => $package, 'ctaLabel' => $ctaLabel ?? null, 'showAddons' => $showAddons ?? false])
                @endforeach
            </div>
        @endif
        @foreach ($widePackages as $package)
            @include('public.partials.package-card', ['package' => $package, 'ctaLabel' => $ctaLabel ?? null, 'wide' => true, 'showAddons' => $showAddons ?? false])
        @endforeach
        @if ($note ?? null)<p class="svc-note">@include('public.partials.svc-icon', ['name' => 'info', 'class' => 'pkg-meta-icon']){{ $note }}</p>@endif
    </div>
</section>
