@php($catIcons = ['website-development' => 'website', 'e-commerce' => 'cart', 'custom-web-applications' => 'app', 'ai-automation' => 'ai', 'system-api-integration' => 'api', 'maintenance-support' => 'support'])
<section class="svc-hero" id="{{ $id }}" aria-labelledby="{{ $id }}-title">
    <div class="z-wrap svc-hero-grid">
        <div class="svc-hero-copy">
            <p class="svc-eyebrow">{{ $eyebrow }}</p>
            <h1 id="{{ $id }}-title">{{ $title }}</h1>
            <p class="svc-hero-lead">{{ $lead }}</p>
            <ul class="svc-hero-cats" aria-label="Kategori servis">
                @foreach ($services as $service)<li>@include('public.partials.svc-icon', ['name' => $catIcons[$service->slug] ?? 'grid']){{ $service->name }}</li>@endforeach
            </ul>
            @if ($actions ?? false)
                <div class="svc-hero-actions">
                    <a class="z-button" href="{{ route('builder.start') }}">Mulakan Projek @include('public.partials.svc-icon', ['name' => 'arrow'])</a>
                    <a class="svc-demo" href="{{ route('gallery.examples') }}"><span class="svc-demo-icon">@include('public.partials.svc-icon', ['name' => 'play'])</span><span>Lihat Demo</span></a>
                </div>
            @endif
        </div>
        <div class="svc-hero-visual" aria-hidden="true">
            <svg viewBox="0 0 520 380" focusable="false">
                <defs>
                    <linearGradient id="hv-screen" x1="0" y1="0" x2="1" y2="1"><stop offset="0" stop-color="#0b3d6e"/><stop offset="1" stop-color="#0b66a7"/></linearGradient>
                    <linearGradient id="hv-card" x1="0" y1="0" x2="0" y2="1"><stop offset="0" stop-color="#fff"/><stop offset="1" stop-color="#eef6fd"/></linearGradient>
                </defs>
                <circle cx="300" cy="190" r="170" fill="#e1eefa"/>
                <circle cx="420" cy="80" r="46" fill="#d7e9f8"/>
                <path d="M40 330c90-40 200 20 300-10s130-50 170-40" fill="none" stroke="#cfe4f6" stroke-width="2"/>
                <rect x="150" y="60" width="300" height="196" rx="14" fill="#1b2533"/>
                <rect x="160" y="70" width="280" height="176" rx="8" fill="url(#hv-screen)"/>
                <rect x="176" y="88" width="120" height="12" rx="6" fill="#7cc4f5"/>
                <rect x="176" y="110" width="160" height="10" rx="5" fill="#ffffff" opacity=".85"/>
                <rect x="176" y="128" width="130" height="10" rx="5" fill="#ffffff" opacity=".6"/>
                <rect x="176" y="152" width="64" height="18" rx="9" fill="#2a93d6"/>
                <rect x="332" y="100" width="92" height="76" rx="8" fill="#ffffff" opacity=".12"/>
                <path d="M342 164l18-20 14 10 16-18 22 14" fill="none" stroke="#7cc4f5" stroke-width="4" stroke-linecap="round" stroke-linejoin="round"/>
                <rect x="176" y="190" width="72" height="42" rx="6" fill="#ffffff" opacity=".12"/><rect x="258" y="190" width="72" height="42" rx="6" fill="#ffffff" opacity=".12"/><rect x="340" y="190" width="84" height="42" rx="6" fill="#ffffff" opacity=".12"/>
                <path d="M280 256h40l8 40h-56z" fill="#9aa7b6"/><rect x="236" y="294" width="128" height="10" rx="5" fill="#c3ccd6"/>
                <rect x="84" y="170" width="120" height="150" rx="12" fill="#1b2533"/><rect x="92" y="178" width="104" height="134" rx="6" fill="url(#hv-card)"/>
                <rect x="102" y="192" width="60" height="8" rx="4" fill="#0b66a7"/><rect x="102" y="208" width="84" height="44" rx="6" fill="#dcecf9"/><rect x="102" y="262" width="70" height="7" rx="3.5" fill="#b9d6ee"/><rect x="102" y="276" width="50" height="7" rx="3.5" fill="#b9d6ee"/><rect x="102" y="292" width="40" height="12" rx="6" fill="#2a93d6"/>
                <rect x="420" y="190" width="64" height="120" rx="12" fill="#1b2533"/><rect x="426" y="198" width="52" height="104" rx="7" fill="url(#hv-card)"/>
                <rect x="432" y="208" width="36" height="6" rx="3" fill="#0b66a7"/><rect x="432" y="222" width="40" height="30" rx="5" fill="#dcecf9"/><rect x="432" y="260" width="30" height="6" rx="3" fill="#b9d6ee"/><rect x="432" y="284" width="28" height="10" rx="5" fill="#2a93d6"/>
                <circle cx="130" cy="96" r="22" fill="#fff" stroke="#d6e8f6"/><path d="M121 96l6 6 12-12" fill="none" stroke="#15803d" stroke-width="4" stroke-linecap="round" stroke-linejoin="round"/>
                <circle cx="470" cy="150" r="8" fill="#d5a928"/>
            </svg>
        </div>
    </div>
</section>
