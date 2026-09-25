@extends('layouts.client', ['title' => 'Daftar Program Partnership | NatNetwork Synergy', 'wide' => true])

@section('content')
@php($minCapital = (float) $settings->min_capital)
@php($poolPercent = rtrim(rtrim(number_format((float) $settings->pool_percent, 2), '0'), '.'))
@php($startStep = $pending ? 3 : ($errors->any() ? 3 : 1))
<section class="auth-card" x-data="{ step: {{ $startStep }}, agree: {{ $errors->any() || $pending ? 'true' : 'false' }}, type: '{{ old('id_type', $pending['id_type'] ?? 'IC') }}' }">
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
        <div x-show="step === 1" x-cloak>
            <h1>Sertai Program Partnership</h1>
            <p class="lead">Kongsi hasil jualan NatNetwork. {{ $poolPercent }}% daripada setiap jualan pelanggan yang disahkan diagih kepada partner mengikut nisbah modal.</p>
            <ul class="steps-list-simple" style="display: grid; gap: .55rem; padding: 0; margin: 1.25rem 0; list-style: none;">
                <li>✓ Modal minimum RM{{ number_format($minCapital) }}, bayaran selamat melalui Billplz</li>
                <li>✓ Agihan automatik setiap kali jualan disahkan</li>
                <li>✓ Penyata pulangan telus dalam portal</li>
            </ul>
            <div class="button-row">
                <button type="button" class="button" @click="step = 2; $nextTick(() => $refs.s2.focus())">Daftar sekarang</button>
                <a class="button button-secondary" href="{{ route('partner.login') }}">Saya sudah berdaftar</a>
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

        <p class="top-gap">Sudah berdaftar? <a href="{{ route('partner.login') }}">Log masuk Partnership</a></p>
    @endif
</section>
@endsection
