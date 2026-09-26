@extends('layouts.client', ['title' => 'Daftar Program Partnership | NatNetwork Synergy', 'wide' => true])

@section('content')
@php($minCapital = (float) $settings->min_capital)
@php($poolPercent = rtrim(rtrim(number_format((float) $settings->pool_percent, 2), '0'), '.'))
@php($startStep = $pending ? 3 : ($errors->any() ? 3 : 1))
@php($pIcon = [
    'chart' => '<path d="M4 20V10M10 20V4M16 20v-7M22 20H2"/>',
    'shield' => '<path d="M12 3l8 3v6c0 5-3.5 8-8 9-4.5-1-8-4-8-9V6l8-3z"/><path d="M9 12l2 2 4-4"/>',
    'doc' => '<path d="M14 3H6a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V9z"/><path d="M14 3v6h6M8 13h8M8 17h6"/>',
    'coins' => '<ellipse cx="12" cy="6" rx="7" ry="3"/><path d="M5 6v4c0 1.7 3.1 3 7 3s7-1.3 7-3V6M5 10v4c0 1.7 3.1 3 7 3s7-1.3 7-3v-4M5 14v4c0 1.7 3.1 3 7 3s7-1.3 7-3v-4"/>',
    'lock' => '<rect x="5" y="11" width="14" height="10" rx="2"/><path d="M8 11V7a4 4 0 0 1 8 0v4"/>',
    'users' => '<circle cx="9" cy="8" r="3.5"/><path d="M2.5 20a6.5 6.5 0 0 1 13 0"/><circle cx="17" cy="9" r="2.5"/><path d="M16 14.2a5 5 0 0 1 5.5 5.8"/>',
    'megaphone' => '<path d="M3 11v2a1 1 0 0 0 1 1h3l9 5V5L7 10H4a1 1 0 0 0-1 1z"/><path d="M19 9a3 3 0 0 1 0 6M8 14l1.5 5"/>',
    'arrow' => '<path d="M5 12h14M13 6l6 6-6 6"/>',
])
@php($svg = fn (string $name, string $class = '') => '<svg class="'.$class.'" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">'.$pIcon[$name].'</svg>')
<section class="auth-card pship" :class="{ 'is-hero': step === 1 }" x-data="{ step: {{ $startStep }}, agree: {{ $errors->any() || $pending ? 'true' : 'false' }}, type: '{{ old('id_type', $pending['id_type'] ?? 'IC') }}' }">
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
            <div class="pship-actions">
                <button type="button" class="button pship-cta" @click="step = 2; $nextTick(() => $refs.s2.focus())">Daftar sekarang {!! $svg('arrow') !!}</button>
                <a class="button button-secondary pship-cta-alt" href="{{ route('partner.login') }}">Saya sudah berdaftar</a>
            </div>
        </div>

        {{-- Langkah 2: Terma & syarat --}}
        <div x-show="step === 2" x-cloak>
            <h1 x-ref="s2" tabindex="-1">Terma &amp; syarat</h1>
            <p class="lead">Sila baca terma berikut sebelum meneruskan pendaftaran.</p>
            <div class="nn-terms" tabindex="0" aria-label="Terma dan syarat Program Partnership">
                <h3>{{ config('partnership_terms.title') }} <small class="muted">(versi {{ config('partnership_terms.version') }})</small></h3>
                @foreach (config('partnership_terms.terms') as $t)
                    <p><b>{{ $t['title'] }}.</b> {{ $t['body'] }}</p>
                @endforeach
            </div>
            <label class="nn-check"><input type="checkbox" x-model="agree"> Saya telah membaca dan bersetuju dengan terma dan syarat Program Partnership. Saya faham pulangan bergantung kepada jualan sebenar dan tidak dijamin.</label>
            <div class="button-row">
                <button type="button" class="button button-secondary" @click="step = 1">Kembali</button>
                <button type="button" class="button" :disabled="! agree" :aria-disabled="(! agree).toString()" @click="if (agree) { step = 3; $nextTick(() => $refs.s3 && $refs.s3.focus()) }">Daftar</button>
            </div>
        </div>

        {{-- Langkah 3: Maklumat & bayaran --}}
        <div x-show="step === 3" x-cloak>
            @if (! $pending)
                <h1 x-ref="s3" tabindex="-1">Maklumat pendaftaran</h1>
                <p class="lead">Isi maklumat asas dan amaun modal. Maklumat IC dan akaun bank diisi selepas bayaran berjaya.</p>
                <form method="post" action="{{ route('partner.register.send') }}" class="form-stack">
                    @csrf
                    <input type="hidden" name="agree" :value="agree ? '1' : ''">
                    @error('agree')<p class="form-error">{{ $message }}</p>@enderror
                    <label>Nama penuh<input name="name" value="{{ old('name') }}" required maxlength="255" autocomplete="name"></label>
                    @error('name')<p class="form-error">{{ $message }}</p>@enderror
                    <div class="form-grid-2">
                        <label>Emel<input name="email" type="email" value="{{ old('email') }}" required maxlength="255" autocomplete="email"></label>
                        <label>No. Telefon / WhatsApp<input name="phone" type="tel" value="{{ old('phone') }}" required maxlength="50" autocomplete="tel"></label>
                    </div>
                    @error('email')<p class="form-error">{{ $message }}</p>@enderror
                    @error('phone')<p class="form-error">{{ $message }}</p>@enderror
                    <label>Jenis penyertaan
                        <select name="id_type" x-model="type"><option value="IC">Individu</option><option value="COMPANY">Syarikat</option></select>
                    </label>
                    <label x-show="type === 'COMPANY'" x-cloak>Nama syarikat<input name="company_name" value="{{ old('company_name') }}" maxlength="255" :required="type === 'COMPANY'"></label>
                    @error('company_name')<p class="form-error">{{ $message }}</p>@enderror
                    <label>Amaun modal (RM) <small>Minimum RM{{ number_format($minCapital, 2) }}</small>
                        <input name="amount" type="number" step="0.01" min="{{ $minCapital }}" max="{{ (float) $settings->max_total_capital }}" value="{{ old('amount', $minCapital) }}" required inputmode="decimal">
                    </label>
                    @error('amount')<p class="form-error">{{ $message }}</p>@enderror
                    <div class="button-row">
                        <button type="button" class="button button-secondary" @click="step = 2">Kembali</button>
                        <button class="button" type="submit">Sahkan emel &amp; teruskan ke bayaran</button>
                    </div>
                </form>
            @else
                <h1>Sahkan emel anda</h1>
                <p class="lead">Kod enam digit telah dihantar ke <b>{{ $pending['email'] }}</b>. Selepas disahkan, anda akan terus ke bayaran modal.</p>
                <dl class="nn-summary">
                    <div><dt>Nama</dt><dd>{{ $pending['name'] }}</dd></div>
                    <div><dt>Penyertaan</dt><dd>{{ $pending['id_type'] === 'COMPANY' ? 'Syarikat · '.($pending['company_name'] ?? '') : 'Individu' }}</dd></div>
                    <div class="nn-total"><dt>Modal</dt><dd>RM {{ number_format((float) $pending['amount'], 2) }}</dd></div>
                </dl>
                <form method="post" action="{{ route('partner.register.verify') }}" class="form-stack">
                    @csrf
                    <label>Kod enam digit<input name="code" inputmode="numeric" pattern="[0-9]{6}" autocomplete="one-time-code" required autofocus></label>
                    @error('code')<p class="form-error">{{ $message }}</p>@enderror
                    <button class="button" type="submit">Sahkan &amp; teruskan ke bayaran</button>
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
    @endif
</section>
@endsection
