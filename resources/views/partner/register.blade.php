@extends('layouts.client', ['title' => 'Daftar Program Partnership | NatNetwork Synergy', 'wide' => true])

@section('content')
@php($minCapital = (float) $settings->min_capital)
@php($poolPercent = rtrim(rtrim(number_format((float) $settings->pool_percent, 2), '0'), '.'))
@php($startStep = $full ? 1 : ($pending ? 3 : ($errors->any() && ! $errors->has('registration') ? 3 : 1)))
@php($pIcon = [
    'chart' => '<path d="M4 20V10M10 20V4M16 20v-7M22 20H2"/>',
    'shield' => '<path d="M12 3l8 3v6c0 5-3.5 8-8 9-4.5-1-8-4-8-9V6l8-3z"/><path d="M9 12l2 2 4-4"/>',
    'doc' => '<path d="M14 3H6a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V9z"/><path d="M14 3v6h6M8 13h8M8 17h6"/>',
    'coins' => '<ellipse cx="12" cy="6" rx="7" ry="3"/><path d="M5 6v4c0 1.7 3.1 3 7 3s7-1.3 7-3V6M5 10v4c0 1.7 3.1 3 7 3s7-1.3 7-3v-4M5 14v4c0 1.7 3.1 3 7 3s7-1.3 7-3v-4"/>',
    'lock' => '<rect x="5" y="11" width="14" height="10" rx="2"/><path d="M8 11V7a4 4 0 0 1 8 0v4"/>',
    'users' => '<circle cx="9" cy="8" r="3.5"/><path d="M2.5 20a6.5 6.5 0 0 1 13 0"/><circle cx="17" cy="9" r="2.5"/><path d="M16 14.2a5 5 0 0 1 5.5 5.8"/>',
    'megaphone' => '<path d="M3 11v2a1 1 0 0 0 1 1h3l9 5V5L7 10H4a1 1 0 0 0-1 1z"/><path d="M19 9a3 3 0 0 1 0 6M8 14l1.5 5"/>',
    'arrow' => '<path d="M5 12h14M13 6l6 6-6 6"/>',
    'arrow-left' => '<path d="M19 12H5M11 6l-6 6 6 6"/>',
    'target' => '<circle cx="12" cy="12" r="9"/><circle cx="12" cy="12" r="5"/><circle cx="12" cy="12" r="1.5"/>',
    'portal' => '<rect x="3" y="4" width="18" height="13" rx="2"/><path d="M8 21h8M12 17v4M3 9h18"/>',
    'refund' => '<path d="M3 12a9 9 0 1 0 3-6.7L3 8"/><path d="M3 3v5h5M12 8v4l3 2"/>',
    'bank' => '<path d="M3 10l9-6 9 6M5 10v8M9.5 10v8M14.5 10v8M19 10v8M3 21h18"/>',
    'alert' => '<path d="M12 3l10 18H2z"/><path d="M12 10v5M12 18h.01"/>',
    'list' => '<path d="M9 6h12M9 12h12M9 18h12M4 6h.01M4 12h.01M4 18h.01"/>',
    'user' => '<circle cx="12" cy="8" r="4"/><path d="M4 21a8 8 0 0 1 16 0"/>',
    'mail' => '<rect x="3" y="5" width="18" height="14" rx="2"/><path d="m3 7 9 6 9-6"/>',
    'phone' => '<path d="M5 4h4l2 5-2.5 1.5a11 11 0 0 0 5 5L15 13l5 2v4a2 2 0 0 1-2 2A16 16 0 0 1 3 6a2 2 0 0 1 2-2"/>',
    'building' => '<path d="M4 21V5a1 1 0 0 1 1-1h9a1 1 0 0 1 1 1v16M15 9h4a1 1 0 0 1 1 1v11M2 21h20M8 8h3M8 12h3M8 16h3"/>',
    'wallet' => '<rect x="3" y="6" width="18" height="13" rx="2.5"/><path d="M3 10h18"/><circle cx="12" cy="14.5" r="2"/><path d="M7 6l3-3h4l3 3"/>',
    'bolt' => '<path d="M13 2 4 14h7l-1 8 9-12h-7z"/>',
    'headset' => '<path d="M4 14v-2a8 8 0 0 1 16 0v2"/><rect x="3" y="13" width="4" height="6" rx="1.5"/><rect x="17" y="13" width="4" height="6" rx="1.5"/><path d="M19 19c0 1.5-1.8 2.5-4.5 2.5"/>',
    'chevron' => '<path d="m6 9 6 6 6-6"/>',
])
@php($svg = fn (string $name, string $class = '') => '<svg class="'.$class.'" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">'.$pIcon[$name].'</svg>')
<section @class(['auth-card', 'pship', 'is-closed' => $full]) :class="{ 'is-hero': step === 1 || step === 2, 'is-s3': step === 3 }" x-data="{ step: {{ $startStep }}, agree: {{ $errors->any() || $pending ? 'true' : 'false' }}, type: '{{ old('id_type', $pending['id_type'] ?? 'IC') }}' }">
    <div class="pship-main">
    <p class="eyebrow">Program Partnership</p>

    @if (! $open)
        <h1>Program Partnership belum dibuka.</h1>
        <p class="lead">Terma dan proses Program Partnership sedang dimuktamadkan. Pendaftaran akan dibuka tidak lama lagi.</p>
        <p class="top-gap">Sudah berdaftar? <a href="{{ route('partner.login') }}">Log masuk Partnership</a></p>
    @else
        <ol class="nn-steps" aria-label="Langkah pendaftaran Partnership">
            @foreach (['Daftar', 'Terma & syarat', 'Maklumat & bayaran', 'Lengkapkan profil', 'Dashboard'] as $i => $label)
                <li :class="{ 'is-done': step > {{ $i + 1 }}, 'is-current': step === {{ $i + 1 }} }">{{ $label }}</li>
            @endforeach
        </ol>

        @if (session('status'))<p class="notice">{{ session('status') }}</p>@endif

        {{-- Langkah 1: Daftar --}}
        <div x-show="step === 1" x-cloak class="pship-intro">
            @if ($full)
                <p class="pship-closed" role="status">{!! $svg('lock') !!} Pendaftaran ditutup</p>
            @endif
            <h1 class="pship-title">Sertai Program <span>Partnership</span></h1>
            <p class="pship-lead">Kongsi hasil jualan NatNetwork dan nikmati agihan berdasarkan nisbah modal yang ditetapkan kepada partner. Bersama-sama kita membina pertumbuhan yang berterusan.</p>
            <ul class="pship-features">
                <li><span class="pship-ic is-green">{!! $svg('chart') !!}</span><span><b>Agihan automatik</b><small>Setiap kali jualan disahkan</small></span></li>
                <li><span class="pship-ic is-blue">{!! $svg('shield') !!}</span><span><b>Pembayaran selamat</b><small>Melalui Billplz yang dipercayai</small></span></li>
                <li><span class="pship-ic is-purple">{!! $svg('doc') !!}</span><span><b>Penyata yang jelas</b><small>Laporan lengkap dalam portal</small></span></li>
            </ul>
            <div class="pship-capital">
                <span class="pship-capital-ic">{!! $svg('coins') !!}</span>
                <div>
                    <p>Modal minimum</p>
                    <strong>RM{{ number_format($minCapital) }}</strong>
                    <small>{!! $svg('lock') !!} Bayaran selamat melalui <b>Billplz</b></small>
                </div>
            </div>
            @if ($full)
                <p class="pship-closed-note">Modal terkumpul Program Partnership telah mencapai RM{{ number_format((float) $settings->max_total_capital) }}. Pendaftaran partner baharu ditutup. Terima kasih atas minat anda.</p>
            @endif
            @error('registration')<p class="form-error">{{ $message }}</p>@enderror
            <div class="pship-actions">
                @if ($full)
                    <button type="button" class="button pship-cta" disabled aria-disabled="true">Pendaftaran ditutup</button>
                @else
                    <button type="button" class="button pship-cta" @click="step = 2; $nextTick(() => $refs.s2.focus())">Daftar sekarang {!! $svg('arrow') !!}</button>
                @endif
                <a class="button button-secondary pship-cta-alt" href="{{ route('partner.login') }}">Saya sudah berdaftar</a>
            </div>
        </div>

        {{-- Langkah 2: Terma & syarat --}}
        <div x-show="step === 2" x-cloak class="pship-intro">
            <h1 class="pship-title is-plain" x-ref="s2" tabindex="-1">Terma &amp; syarat</h1>
            <p class="pship-lead is-tight">Sila baca terma berikut sebelum meneruskan pendaftaran.</p>
            <div class="pship-terms" tabindex="0" aria-label="Terma dan syarat Program Partnership">
                <h3>{{ config('partnership_terms.title') }} <small>(versi {{ config('partnership_terms.version') }})</small></h3>
                <ol>
                    @foreach (config('partnership_terms.terms') as $t)
                        <li><span class="pship-term-ic">{!! $svg($t['icon'] ?? 'doc') !!}</span><div><b>{{ $t['title'] }}</b><p>{{ $t['body'] }}</p></div></li>
                    @endforeach
                </ol>
            </div>
            <label class="pship-agree"><input type="checkbox" x-model="agree"> <span>Saya telah membaca dan bersetuju dengan terma dan syarat Program Partnership. Saya faham dan jelas pulangan bergantung kepada prestasi marketing dan hasil jualan sebenar dan yakin pihak syarikat lakukan yang terbaik untuk partnership.</span></label>
            <div class="pship-actions is-inline">
                <button type="button" class="button button-secondary pship-back" @click="step = 1">{!! $svg('arrow-left') !!} Kembali</button>
                <button type="button" class="button pship-cta" :disabled="! agree" :aria-disabled="(! agree).toString()" @click="if (agree) { step = 3; $nextTick(() => $refs.s3 && $refs.s3.focus()) }">Daftar {!! $svg('arrow') !!}</button>
            </div>
        </div>

        {{-- Langkah 3: Maklumat & bayaran --}}
        <div x-show="step === 3" x-cloak class="pship-s3">
            @if (! $pending)
                <h1 class="pship-s3-title" x-ref="s3" tabindex="-1">Maklumat pendaftaran</h1>
                <p class="pship-s3-lead">Isi maklumat asas dan amaun modal. Maklumat IC dan akaun bank diisi selepas bayaran berjaya.</p>
                <form method="post" action="{{ route('partner.register.send') }}" class="pship-form">
                    @csrf
                    <input type="hidden" name="agree" :value="agree ? '1' : ''">
                    @error('agree')<p class="form-error">{{ $message }}</p>@enderror
                    @error('registration')<p class="form-error">{{ $message }}</p>@enderror
                    <label class="pship-label">Nama penuh
                        <span class="pship-field"><span class="pship-field-ic">{!! $svg('user') !!}</span><input name="name" value="{{ old('name') }}" required maxlength="255" autocomplete="name" placeholder="Masukkan nama penuh anda"></span>
                    </label>
                    @error('name')<p class="form-error">{{ $message }}</p>@enderror
                    <div class="pship-grid-2">
                        <label class="pship-label">Emel
                            <span class="pship-field"><span class="pship-field-ic">{!! $svg('mail') !!}</span><input name="email" type="email" value="{{ old('email') }}" required maxlength="255" autocomplete="email" placeholder="contoh: anda@email.com"></span>
                        </label>
                        <label class="pship-label">No. Telefon / WhatsApp
                            <span class="pship-field"><span class="pship-field-ic">{!! $svg('phone') !!}</span><input name="phone" type="tel" value="{{ old('phone') }}" required maxlength="50" autocomplete="tel" placeholder="contoh: 012-345 6789"></span>
                        </label>
                    </div>
                    @error('email')<p class="form-error">{{ $message }}</p>@enderror
                    @error('phone')<p class="form-error">{{ $message }}</p>@enderror
                    <label class="pship-label">Jenis penyertaan
                        <span class="pship-field is-select"><span class="pship-field-ic">{!! $svg('users') !!}</span><select name="id_type" x-model="type"><option value="IC">Individu</option><option value="COMPANY">Syarikat</option></select><span class="pship-chev">{!! $svg('chevron') !!}</span></span>
                    </label>
                    <label class="pship-label" x-show="type === 'COMPANY'" x-cloak>Nama syarikat
                        <span class="pship-field"><span class="pship-field-ic">{!! $svg('building') !!}</span><input name="company_name" value="{{ old('company_name') }}" maxlength="255" :required="type === 'COMPANY'" placeholder="Nama syarikat berdaftar"></span>
                    </label>
                    @error('company_name')<p class="form-error">{{ $message }}</p>@enderror
                    <label class="pship-label">Amaun modal (RM)
                        <span class="pship-amount"><span class="pship-amount-ic">{!! $svg('wallet') !!}</span><span class="pship-amount-in"><small>Minimum RM{{ number_format($minCapital, 2) }}</small><input name="amount" type="number" step="0.01" min="{{ $minCapital }}" max="{{ (float) $settings->max_total_capital }}" value="{{ old('amount', $minCapital) }}" required inputmode="decimal"></span></span>
                    </label>
                    @error('amount')<p class="form-error">{{ $message }}</p>@enderror
                    <div class="pship-actions is-inline">
                        <button type="button" class="button button-secondary pship-back" @click="step = 2">{!! $svg('arrow-left') !!} Kembali</button>
                        <button class="button pship-cta" type="submit">Sahkan emel &amp; teruskan ke bayaran {!! $svg('arrow') !!}</button>
                    </div>
                </form>
            @else
                <h1 class="pship-s3-title">Sahkan emel anda</h1>
                <p class="pship-s3-lead">Kod enam digit telah dihantar ke <b>{{ $pending['email'] }}</b>. Selepas disahkan, anda akan terus ke bayaran modal.</p>
                <dl class="nn-summary">
                    <div><dt>Nama</dt><dd>{{ $pending['name'] }}</dd></div>
                    <div><dt>Penyertaan</dt><dd>{{ $pending['id_type'] === 'COMPANY' ? 'Syarikat · '.($pending['company_name'] ?? '') : 'Individu' }}</dd></div>
                    <div class="nn-total"><dt>Modal</dt><dd>RM {{ number_format((float) $pending['amount'], 2) }}</dd></div>
                </dl>
                <form method="post" action="{{ route('partner.register.verify') }}" class="pship-form">
                    @csrf
                    <label class="pship-label">Kod enam digit
                        <span class="pship-field"><span class="pship-field-ic">{!! $svg('lock') !!}</span><input name="code" inputmode="numeric" pattern="[0-9]{6}" autocomplete="one-time-code" required autofocus placeholder="000000"></span>
                    </label>
                    @error('code')<p class="form-error">{{ $message }}</p>@enderror
                    @error('registration')<p class="form-error">{{ $message }}</p>@enderror
                    <div class="pship-actions is-inline"><button class="button pship-cta" type="submit">Sahkan &amp; teruskan ke bayaran {!! $svg('arrow') !!}</button></div>
                </form>
                <form method="post" action="{{ route('partner.register.cancel') }}" style="margin-top: .75rem;">@csrf<button type="submit" class="button button-secondary button-compact">Tukar maklumat</button></form>
            @endif
        </div>

        <p class="top-gap pship-login">Sudah berdaftar? <a href="{{ route('partner.login') }}">Log masuk Partnership {!! $svg('arrow') !!}</a></p>
    @endif
    </div>

    @if ($open)
        {{-- Paparan premium langkah 1 sahaja: foto + sokongan syarikat. --}}
        <aside class="pship-aside" x-show="step === 1" x-cloak aria-label="Sokongan Program Partnership">
            <img class="pship-photo" src="{{ asset('images/partnership/partnership-hero.webp') }}" alt="Partner NatNetwork Synergy berjabat tangan selepas menandatangani perjanjian" width="621" height="465" loading="eager">
            <div class="pship-support">
                <h2>Sokongan penuh daripada pihak syarikat</h2>
                <p>Team affiliate di bawah pemantauan pihak syarikat memastikan marketing berterusan bagi meningkatkan prestasi jualan.</p>
                <ul>
                    <li><span class="pship-ic is-blue">{!! $svg('users') !!}</span><b>Team Affiliate Berdedikasi</b><small>Strategi marketing dirancang dan dipantau oleh pihak syarikat.</small></li>
                    <li><span class="pship-ic is-blue">{!! $svg('megaphone') !!}</span><b>Aktiviti Marketing Berterusan</b><small>Kempen berterusan untuk meningkatkan jualan produk dan servis.</small></li>
                    <li><span class="pship-ic is-blue">{!! $svg('chart') !!}</span><b>Prestasi Dipantau</b><small>Laporan prestasi berkala untuk memastikan pertumbuhan stabil dan konsisten.</small></li>
                </ul>
            </div>
        </aside>
        <aside class="pship-s3-aside" x-show="step === 3" x-cloak aria-label="Maklumat pendaftaran Partnership">
            <p class="eyebrow">Program Partnership</p>
            <h2 class="pship-title">Maklumat <span>pendaftaran</span></h2>
            <p class="pship-lead">Isi maklumat asas dan amaun modal untuk melengkapkan pendaftaran. Maklumat IC dan akaun bank akan diisi selepas bayaran berjaya.</p>
            <ul class="pship-s3-points">
                <li><span class="pship-ic is-soft">{!! $svg('shield') !!}</span><span><b>Proses selamat</b><small>Maklumat anda dilindungi dengan teknologi keselamatan terkini.</small></span></li>
                <li><span class="pship-ic is-soft">{!! $svg('bolt') !!}</span><span><b>Mudah dan cepat</b><small>Lengkapkan pendaftaran dalam beberapa minit sahaja.</small></span></li>
                <li><span class="pship-ic is-soft">{!! $svg('doc') !!}</span><span><b>Maklumat jelas</b><small>Maklumat IC dan akaun bank diisi selepas bayaran disahkan.</small></span></li>
                <li><span class="pship-ic is-soft">{!! $svg('headset') !!}</span><span><b>Sokongan penuh</b><small>Pasukan kami sedia membantu jika anda memerlukan bantuan.</small></span></li>
            </ul>
            <img class="pship-s3-script" src="{{ asset('images/partnership/satu-platform.png') }}" alt="Satu Platform Pelbagai Peluang" width="205" height="104" loading="lazy">
            <img class="pship-s3-desk" src="{{ asset('images/partnership/partnership-desk.webp') }}" alt="Komputer riba NatNetwork Partner di atas meja dengan buku Partnership, Growth, Success" width="641" height="285" loading="lazy">
        </aside>
        <aside class="pship-aside" x-show="step === 2" x-cloak aria-label="Kenapa perlu baca terma">
            <img class="pship-photo is-sharp" src="{{ asset('images/partnership/partnership-terms.webp') }}" alt="Komputer riba memaparkan Terma &amp; Syarat Program Partnership NatNetwork" width="610" height="490" loading="lazy">
            <div class="pship-support">
                <h2>Kenapa perlu baca terma ini?</h2>
                <p>Terma ini membantu anda memahami hak, tanggungjawab dan cara pengiraan pulangan dalam Program Partnership.</p>
                <ul>
                    <li><span class="pship-ic is-soft">{!! $svg('shield') !!}</span><b>Ketelusan</b><small>Semua terma dinyatakan secara jelas dan terbuka.</small></li>
                    <li><span class="pship-ic is-soft">{!! $svg('users') !!}</span><b>Perlindungan</b><small>Melindungi kedua-dua pihak, syarikat dan partner.</small></li>
                    <li><span class="pship-ic is-soft">{!! $svg('chart') !!}</span><b>Keputusan bijak</b><small>Anda membuat pertimbangan dengan maklumat yang lengkap.</small></li>
                </ul>
            </div>
        </aside>
    @endif
</section>
@endsection
