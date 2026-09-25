@extends('layouts.public', ['title' => 'Servis | NatNetwork Synergy'])

@section('content')
<div class="svc-page">
    @include('public.partials.svc-hero', [
        'id' => 'servis-atas',
        'eyebrow' => 'Servis Digital',
        'title' => 'Penyelesaian digital yang dibina mengikut keperluan anda.',
        'lead' => 'Terangkan hasil yang anda mahu capai. NatNetwork akan menterjemahkan keperluan itu kepada skop projek yang jelas.',
        'actions' => true,
    ])
    @include('public.partials.service-nav', ['topAnchor' => 'servis-atas', 'prefix' => 'servis-'])

    <div class="service-list">
        @foreach ($services as $service)
            @include('public.partials.service-section', ['service' => $service, 'index' => $loop->index, 'anchor' => 'servis-'.$service->slug, 'link' => null, 'showAddons' => true,
                'note' => $service->slug === 'ai-automation' ? 'Nota: kos penggunaan pihak ketiga seperti AI API, WhatsApp API, Telegram infrastructure, email/SMS dan SaaS tidak termasuk dalam harga pembangunan, melainkan dinyatakan secara khusus.' : null])
        @endforeach
    </div>

    <section id="add-ons" @class(['svc-section', 'is-alt' => $services->count() % 2 === 1]) aria-labelledby="add-ons-title">
        <div class="z-wrap">
            <div class="svc-head svc-head-simple svc-reveal">
                <div class="svc-head-copy">
                    <span class="svc-num" aria-hidden="true">+</span>
                    <div>
                        <p class="svc-label">Tambahan</p>
                        <h2 id="add-ons-title">Shared Add-on Catalogue</h2>
                        <p class="svc-sub">Senarai harga standard add-on. Harga add-on bagi setiap pakej dipaparkan pada kad pakej. Add-on yang dipilih akan disenaraikan bersama skop sebenar dalam Master Specification dan quotation anda.</p>
                    </div>
                </div>
            </div>
            <div class="addon-grid">
                @foreach ($addons as $addon)
                    <article class="addon-card">
                        <span class="pkg-icon">@include('public.partials.svc-icon', ['name' => 'plus'])</span>
                        <div><h3>{{ $addon->name }}</h3>@if ($addon->summary)<p>{{ $addon->summary }}</p>@endif</div>
                        <p class="addon-price">{{ $addon->price_label }}</p>
                    </article>
                @endforeach
            </div>
        </div>
    </section>

    <section class="svc-cta" aria-labelledby="svc-cta-title">
        <div class="z-wrap svc-cta-inner svc-reveal">
            <p class="svc-eyebrow">Tidak pasti pakej mana?</p>
            <h2 id="svc-cta-title">Jawab soalan mudah dan kami bantu susun keperluan anda.</h2>
            <a class="z-button svc-cta-button" href="{{ route('builder.start') }}">Bantu saya pilih @include('public.partials.svc-icon', ['name' => 'arrow'])</a>
        </div>
    </section>
</div>
@include('public.partials.svc-script')
@endsection
