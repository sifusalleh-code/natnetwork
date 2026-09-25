@extends('layouts.public', ['title' => 'Harga | NatNetwork Synergy'])

@section('content')
<div class="svc-page">
    @include('public.partials.svc-hero', [
        'id' => 'harga-atas',
        'eyebrow' => 'Harga',
        'title' => 'Pilihan yang jelas sebelum skop sebenar dimuktamadkan.',
        'lead' => 'Harga dan anggaran di bawah ialah titik mula berdasarkan pakej. Keperluan custom atau kompleks akan melalui quotation berdasarkan Master Specification anda.',
    ])
    @include('public.partials.service-nav', ['topAnchor' => 'harga-atas', 'prefix' => ''])

    @foreach ($services as $service)
        @include('public.partials.service-section', ['service' => $service, 'index' => $loop->index, 'anchor' => $service->slug, 'link' => null, 'ctaLabel' => 'Pilih pakej ini'])
    @endforeach

    <section id="add-ons" @class(['svc-section', 'is-alt' => $services->count() % 2 === 1]) aria-labelledby="add-ons-title">
        <div class="z-wrap">
            <div class="svc-head svc-head-simple svc-reveal">
                <div class="svc-head-copy">
                    <span class="svc-num" aria-hidden="true">+</span>
                    <div>
                        <p class="svc-label">Tambahan</p>
                        <h2 id="add-ons-title">Add-ons</h2>
                        <p class="svc-sub">Add-on yang dipilih akan disenaraikan bersama skop sebenar dalam Master Specification dan quotation anda.</p>
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
            <p class="svc-eyebrow">Perlukan bantuan memilih?</p>
            <h2 id="svc-cta-title">Mulakan dengan Guided Project Builder.</h2>
            <p>Jawab dalam bahasa mudah. Soalan yang tidak berkaitan akan dilangkau.</p>
            <a class="z-button svc-cta-button" href="{{ route('builder.start') }}">Bantu saya pilih @include('public.partials.svc-icon', ['name' => 'arrow'])</a>
        </div>
    </section>
</div>
@include('public.partials.svc-script')
@endsection
