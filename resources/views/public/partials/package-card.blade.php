{{--
    Kad pakej. Kandungan asal ($package->summary) dipecahkan untuk bacaan sahaja:
    bahagian sebelum ";" → senarai ciri, selepas ";" → maklumat revision/support. Tiada perkataan ditambah atau dibuang.
--}}
@php
    $pkgIcons = [
        'landing-page' => 'landing', 'starter-website' => 'starter', 'business-website' => 'business', 'corporate-website' => 'corporate', 'custom-website' => 'custom',
        'product-catalogue' => 'catalogue', 'e-commerce-starter' => 'payment', 'e-commerce-business' => 'store', 'custom-e-commerce' => 'custom',
        'simple-web-system' => 'grid', 'business-web-app' => 'layers', 'advanced-web-app' => 'code', 'enterprise-complex' => 'server',
        'telegram-business-bot' => 'send', 'ai-website-chatbot' => 'chat', 'ai-knowledge-bot' => 'bot', 'workflow-automation' => 'flow', 'custom-ai-business-assistant' => 'ai', 'advanced-ai-solution' => 'custom',
        'basic-api' => 'api', 'payment-gateway' => 'payment', 'email-integration' => 'mail', 'telegram-integration' => 'send', 'whatsapp-integration' => 'chat', 'external-business-system' => 'server', 'complex-multiple-integration' => 'network',
        'basic-api-integration' => 'api', 'business-integration' => 'server', 'advanced-integration' => 'network', 'custom-integration' => 'custom',
        'basic-care' => 'shield', 'business-care' => 'shield', 'e-commerce-care' => 'cart', 'web-app-care' => 'app', 'custom-sla' => 'support',
    ];
    $summary = trim((string) $package->summary);
    $features = [];
    $meta = [];
    $body = rtrim($summary, '.');
    if (str_contains($body, ';')) {
        [$main, $rest] = array_map('trim', explode(';', $body, 2));
        $meta = array_values(array_filter(array_map('trim', preg_split('/\s+dan\s+/u', $rest))));
    } else {
        $main = $body;
    }
    $lead = null;
    $parts = array_map('trim', explode(', ', $main));
    if (count($parts) >= 3) {
        $last = array_pop($parts);
        if (str_starts_with($last, 'serta ')) {
            $parts[] = mb_substr($last, 6);
        } else {
            array_push($parts, ...array_map('trim', preg_split('/\s+dan\s+/u', $last, 2)));
        }
        $words = fn ($t) => count(preg_split('/\s+/u', $t));
        if ($words($parts[0]) > 4) {
            $lead = array_shift($parts);
        }
        $features = collect($parts)->every(fn ($t) => $words($t) <= 4) ? $parts : [];
    }
    if ($features === []) {
        $lead = null;
        $meta = [];
    }
    if ($features && $lead) {
        array_unshift($features, $lead); // paparan rujukan: ayat pertama sebagai item pertama senarai
        $lead = null;
    }
    if (! empty($package->inclusions)) {
        // Senarai "Included dalam Pakej" daripada katalog Pricing.
        $features = array_values($package->inclusions);
        $lead = null;
        $meta = [];
    }
    $showParagraph = $features === [];
    $packageAddons = ($showAddons ?? false) && $package->relationLoaded('packageAddons') ? $package->packageAddons->filter(fn ($pa) => $pa->addon && $pa->addon->is_active)->values() : collect();
    $isQuote = ($package->price_type ?? null) === 'quote' || str_starts_with((string) $package->slug, 'custom-');
    $cta = $isQuote ? 'Bincang Projek' : ($ctaLabel ?? 'Pilih Pakej');
@endphp
@if ($wide ?? false)
<article class="pkg-card pkg-wide">
    <div class="pkg-wide-main">
        <span class="pkg-icon">@include('public.partials.svc-icon', ['name' => $pkgIcons[$package->slug] ?? 'grid'])</span>
        <div class="pkg-wide-copy">
            <h3>{{ $package->name }}</h3>
            @if ($showParagraph && $summary !== '')
                <p class="pkg-wide-line">@include('public.partials.svc-icon', ['name' => 'check', 'class' => 'pkg-check']){{ $summary }}</p>
            @else
                @if ($lead)<p class="pkg-desc">{{ $lead }}</p>@endif
                <ul class="pkg-features is-inline" aria-label="Skop {{ $package->name }}">
                    @foreach ($features as $f)<li>@include('public.partials.svc-icon', ['name' => 'check', 'class' => 'pkg-check'])<span>{!! str_replace('/', '/<wbr>', e($f)) !!}</span></li>@endforeach
                </ul>
            @endif
            @if ($package->use_case)<p class="pkg-suit"><b>Sesuai untuk:</b> {{ $package->use_case }}</p>@endif
        </div>
    </div>
    @if ($meta || $package->delivery_estimate)
        <ul class="pkg-meta pkg-wide-meta">
            @foreach ($meta as $m)<li>@include('public.partials.svc-icon', ['name' => 'info', 'class' => 'pkg-meta-icon']){{ $m }}</li>@endforeach
            @if ($package->delivery_estimate)<li>@include('public.partials.svc-icon', ['name' => 'info', 'class' => 'pkg-meta-icon'])Anggaran pembangunan aktif: {{ $package->delivery_estimate }}</li>@endif
        </ul>
    @endif
    <div class="pkg-wide-foot">
        <p class="pkg-price">{{ $package->price_label }}</p>
        <a class="z-button pkg-cta" href="{{ route('builder.start', ['package' => $package->slug]) }}" aria-label="{{ $cta }}: {{ $package->name }}">{{ $cta }} @include('public.partials.svc-icon', ['name' => 'arrow'])</a>
    </div>
    @if ($packageAddons->isNotEmpty())
        <details class="pkg-addons">
            <summary>Add-on pilihan ({{ $packageAddons->count() }})</summary>
            <ul>@foreach ($packageAddons as $pa)<li><span>{{ $pa->displayName() }}</span><b>{{ $pa->price_label }}</b></li>@endforeach</ul>
        </details>
    @endif
</article>
@else
<article class="pkg-card">
    <div class="pkg-head">
        <span class="pkg-icon">@include('public.partials.svc-icon', ['name' => $pkgIcons[$package->slug] ?? 'grid'])</span>
        <h3>{{ $package->name }}</h3>
    </div>
    @if ($showParagraph && $summary !== '')
        <p class="pkg-desc">{{ $summary }}</p>
    @elseif ($lead)
        <p class="pkg-desc">{{ $lead }}</p>
    @endif
    @if ($features)
        <ul class="pkg-features" aria-label="Skop {{ $package->name }}">
            @foreach ($features as $f)<li>@include('public.partials.svc-icon', ['name' => 'check', 'class' => 'pkg-check'])<span>{!! str_replace('/', '/<wbr>', e($f)) !!}</span></li>@endforeach
        </ul>
    @endif
    @if ($meta || $package->delivery_estimate)
        <ul class="pkg-meta">
            @foreach ($meta as $m)<li>@include('public.partials.svc-icon', ['name' => 'info', 'class' => 'pkg-meta-icon']){{ $m }}</li>@endforeach
            @if ($package->delivery_estimate)<li>@include('public.partials.svc-icon', ['name' => 'info', 'class' => 'pkg-meta-icon'])Anggaran pembangunan aktif: {{ $package->delivery_estimate }}</li>@endif
        </ul>
    @endif
    @if ($package->use_case)
        <p class="pkg-suit"><b>Sesuai untuk:</b> {{ $package->use_case }}</p>
    @endif
    @if ($packageAddons->isNotEmpty())
        <details class="pkg-addons">
            <summary>Add-on pilihan ({{ $packageAddons->count() }})</summary>
            <ul>@foreach ($packageAddons as $pa)<li><span>{{ $pa->displayName() }}</span><b>{{ $pa->price_label }}</b></li>@endforeach</ul>
        </details>
    @endif
    <div class="pkg-foot">
        <p class="pkg-price">{{ $package->price_label }}</p>
        <a class="z-button pkg-cta" href="{{ route('builder.start', ['package' => $package->slug]) }}" aria-label="{{ $cta }}: {{ $package->name }}">{{ $cta }} @include('public.partials.svc-icon', ['name' => 'arrow'])</a>
    </div>
</article>
@endif
