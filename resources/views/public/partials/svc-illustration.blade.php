{{-- Ilustrasi teknologi ringan (SVG) mengikut kategori servis. Hiasan sahaja. --}}
@php($ill = $slug ?? 'website-development')
<svg class="svc-illus" viewBox="0 0 360 220" role="presentation" aria-hidden="true" focusable="false">
    <defs>
        <linearGradient id="g-{{ $ill }}" x1="0" y1="0" x2="1" y2="1"><stop offset="0" stop-color="#2a93d6"/><stop offset="1" stop-color="#0b4f8a"/></linearGradient>
        <linearGradient id="s-{{ $ill }}" x1="0" y1="0" x2="0" y2="1"><stop offset="0" stop-color="#ffffff"/><stop offset="1" stop-color="#eef6fd"/></linearGradient>
    </defs>
    <ellipse cx="200" cy="120" rx="150" ry="92" fill="#e3f0fb"/>
    <path d="M20 190c60-30 140 10 220-12s100-40 120-34" fill="none" stroke="#cfe4f6" stroke-width="2"/>
    @switch($ill)
        @case('e-commerce')
            <rect x="140" y="38" width="170" height="118" rx="12" fill="url(#s-{{ $ill }})" stroke="#c9dff2"/>
            <rect x="154" y="54" width="44" height="40" rx="7" fill="#dcecf9"/><rect x="204" y="54" width="44" height="40" rx="7" fill="#dcecf9"/><rect x="254" y="54" width="44" height="40" rx="7" fill="#dcecf9"/>
            <rect x="154" y="100" width="30" height="6" rx="3" fill="#b9d6ee"/><rect x="204" y="100" width="30" height="6" rx="3" fill="#b9d6ee"/><rect x="254" y="100" width="30" height="6" rx="3" fill="#b9d6ee"/>
            <rect x="154" y="112" width="20" height="6" rx="3" fill="#2a93d6"/><rect x="204" y="112" width="20" height="6" rx="3" fill="#2a93d6"/><rect x="254" y="112" width="20" height="6" rx="3" fill="#2a93d6"/>
            <path d="M60 92h16l14 58h56l12-40H84" fill="none" stroke="url(#g-{{ $ill }})" stroke-width="8" stroke-linecap="round" stroke-linejoin="round"/>
            <circle cx="98" cy="166" r="8" fill="#0b66a7"/><circle cx="140" cy="166" r="8" fill="#0b66a7"/>
            <path d="M92 118h60" stroke="#7cc4f5" stroke-width="6" stroke-linecap="round"/>
            <rect x="268" y="132" width="52" height="58" rx="8" fill="#d5a928" opacity=".9"/><path d="M280 132v-8a14 14 0 0 1 28 0v8" fill="none" stroke="#b88f16" stroke-width="4"/>
            @break
        @case('custom-web-applications')
            <rect x="70" y="30" width="240" height="150" rx="12" fill="url(#s-{{ $ill }})" stroke="#c9dff2"/>
            <rect x="70" y="30" width="56" height="150" rx="12" fill="#0b3d6e"/><rect x="82" y="48" width="32" height="6" rx="3" fill="#7cc4f5"/><rect x="82" y="64" width="26" height="5" rx="2.5" fill="#3f7fb5"/><rect x="82" y="76" width="30" height="5" rx="2.5" fill="#3f7fb5"/><rect x="82" y="88" width="22" height="5" rx="2.5" fill="#3f7fb5"/>
            <rect x="140" y="46" width="72" height="44" rx="8" fill="#fff" stroke="#dcebf7"/><path d="M148 80l14-12 12 8 16-16 14 10" fill="none" stroke="#2a93d6" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"/>
            <rect x="222" y="46" width="74" height="44" rx="8" fill="#fff" stroke="#dcebf7"/><circle cx="259" cy="68" r="15" fill="none" stroke="#dcebf7" stroke-width="7"/><path d="M259 53a15 15 0 0 1 14 20" fill="none" stroke="#0b66a7" stroke-width="7"/>
            <rect x="140" y="100" width="156" height="64" rx="8" fill="#fff" stroke="#dcebf7"/>
            <rect x="154" y="136" width="12" height="18" rx="2" fill="#b9d6ee"/><rect x="174" y="124" width="12" height="30" rx="2" fill="#7cc4f5"/><rect x="194" y="116" width="12" height="38" rx="2" fill="#2a93d6"/><rect x="214" y="128" width="12" height="26" rx="2" fill="#7cc4f5"/><rect x="234" y="112" width="12" height="42" rx="2" fill="#0b66a7"/><rect x="254" y="122" width="12" height="32" rx="2" fill="#2a93d6"/>
            @break
        @case('ai-automation')
            <rect x="150" y="36" width="170" height="130" rx="14" fill="url(#s-{{ $ill }})" stroke="#c9dff2"/>
            <rect x="166" y="54" width="100" height="22" rx="11" fill="#e6f1fa"/><rect x="200" y="84" width="104" height="22" rx="11" fill="url(#g-{{ $ill }})"/><rect x="166" y="114" width="84" height="22" rx="11" fill="#e6f1fa"/>
            <circle cx="180" cy="65" r="4" fill="#2a93d6"/><circle cx="192" cy="65" r="4" fill="#7cc4f5"/>
            <circle cx="92" cy="104" r="42" fill="url(#g-{{ $ill }})"/><rect x="72" y="92" width="40" height="28" rx="9" fill="#fff"/><circle cx="84" cy="106" r="4" fill="#0b66a7"/><circle cx="100" cy="106" r="4" fill="#0b66a7"/><path d="M92 62v10" stroke="#0b66a7" stroke-width="4" stroke-linecap="round"/><circle cx="92" cy="60" r="5" fill="#d5a928"/>
            <path d="M136 104h12" stroke="#7cc4f5" stroke-width="3" stroke-dasharray="4 4"/>
            @break
        @case('system-api-integration')
            <circle cx="180" cy="110" r="36" fill="url(#g-{{ $ill }})"/><path d="M168 110a8 8 0 0 0 11 0l6-6a8 8 0 0 0-11-11l-2 2M192 110a8 8 0 0 0-11 0l-6 6a8 8 0 0 0 11 11l2-2" fill="none" stroke="#fff" stroke-width="4" stroke-linecap="round"/>
            @foreach ([[70, 50], [290, 50], [60, 170], [300, 168], [180, 26]] as [$nx, $ny])
                <path d="M180 110L{{ $nx }} {{ $ny }}" stroke="#9cc9ec" stroke-width="2.5" stroke-dasharray="5 5"/>
                <rect x="{{ $nx - 24 }}" y="{{ $ny - 16 }}" width="48" height="32" rx="9" fill="#fff" stroke="#c9dff2"/>
                <rect x="{{ $nx - 12 }}" y="{{ $ny - 4 }}" width="24" height="8" rx="4" fill="#b9d6ee"/>
            @endforeach
            <circle cx="290" cy="50" r="5" fill="#d5a928"/>
            @break
        @case('maintenance-support')
            <rect x="76" y="34" width="210" height="140" rx="12" fill="url(#s-{{ $ill }})" stroke="#c9dff2"/>
            <rect x="92" y="52" width="80" height="10" rx="5" fill="#b9d6ee"/>
            <rect x="92" y="76" width="178" height="22" rx="8" fill="#e8f7ef"/><circle cx="106" cy="87" r="5" fill="#15803d"/><rect x="118" y="83" width="80" height="8" rx="4" fill="#9fd8b8"/>
            <rect x="92" y="106" width="178" height="22" rx="8" fill="#e8f7ef"/><circle cx="106" cy="117" r="5" fill="#15803d"/><rect x="118" y="113" width="60" height="8" rx="4" fill="#9fd8b8"/>
            <rect x="92" y="136" width="178" height="22" rx="8" fill="#eef5fb"/><circle cx="106" cy="147" r="5" fill="#2a93d6"/><rect x="118" y="143" width="96" height="8" rx="4" fill="#b9d6ee"/>
            <circle cx="296" cy="150" r="34" fill="url(#g-{{ $ill }})"/><path d="M282 154v-4a14 14 0 0 1 28 0v4" fill="none" stroke="#fff" stroke-width="4"/><rect x="278" y="152" width="8" height="12" rx="3" fill="#fff"/><rect x="306" y="152" width="8" height="12" rx="3" fill="#fff"/>
            @break
        @default
            <rect x="96" y="34" width="210" height="140" rx="12" fill="url(#s-{{ $ill }})" stroke="#c9dff2"/>
            <rect x="96" y="34" width="210" height="24" rx="12" fill="#0b3d6e"/><circle cx="112" cy="46" r="4" fill="#7cc4f5"/><circle cx="124" cy="46" r="4" fill="#7cc4f5"/><circle cx="136" cy="46" r="4" fill="#d5a928"/>
            <rect x="114" y="72" width="100" height="62" rx="8" fill="url(#g-{{ $ill }})"/><path d="M124 124l24-24 18 16 12-10 26 18" fill="none" stroke="#fff" stroke-width="4" stroke-linecap="round" stroke-linejoin="round"/><circle cx="192" cy="88" r="7" fill="#fff" opacity=".85"/>
            <rect x="226" y="74" width="62" height="8" rx="4" fill="#b9d6ee"/><rect x="226" y="90" width="48" height="8" rx="4" fill="#dcebf7"/><rect x="226" y="106" width="56" height="8" rx="4" fill="#dcebf7"/><rect x="226" y="124" width="40" height="14" rx="7" fill="#2a93d6"/>
            <rect x="114" y="146" width="174" height="10" rx="5" fill="#e6f1fa"/>
            <path d="M52 150l28-70 28 70" fill="#cfe4f6"/><path d="M40 150l22-50 22 50" fill="#b9d6ee"/>
    @endswitch
</svg>
