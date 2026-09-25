@extends('layouts.client', ['title' => 'Daftar Affiliate | NatNetwork Synergy'])

@section('content')
<section class="auth-card">
    <p class="eyebrow">Program Affiliate</p>
    <h1>Daftar sebagai affiliate</h1>
    <p class="lead">Isi maklumat anda. Kami akan hantar kod enam digit untuk mengesahkan emel. Tiada kata laluan diperlukan.</p>

    @if (session('status'))<p class="notice">{{ session('status') }}</p>@endif

    <form method="post" action="{{ route('affiliate.register.send') }}" class="form-stack">
        @csrf
        <label for="name">Nama penuh</label>
        <input id="name" name="name" type="text" value="{{ old('name', $pending['name'] ?? '') }}" autocomplete="name" required maxlength="255">
        @error('name')<p class="form-error">{{ $message }}</p>@enderror
        <label for="email">Emel</label>
        <input id="email" name="email" type="email" value="{{ old('email', $pending['email'] ?? '') }}" autocomplete="email" required maxlength="255">
        @error('email')<p class="form-error">{{ $message }}</p>@enderror
        <label for="phone">No. Telefon / WhatsApp</label>
        <input id="phone" name="phone" type="tel" inputmode="tel" value="{{ old('phone', $pending['phone'] ?? '') }}" autocomplete="tel" required maxlength="50">
        @error('phone')<p class="form-error">{{ $message }}</p>@enderror
        <button class="button" type="submit">{{ $pending ? 'Hantar semula kod' : 'Hantar kod pengesahan' }}</button>
    </form>

    @if ($pending)
        <form method="post" action="{{ route('affiliate.register.verify') }}" class="form-stack top-gap">
            @csrf
            <label for="code">Kod enam digit</label>
            <input id="code" name="code" type="text" inputmode="numeric" pattern="[0-9]{6}" autocomplete="one-time-code" required>
            @error('code')<p class="form-error">{{ $message }}</p>@enderror
            <button class="button" type="submit">Sahkan emel</button>
        </form>
    @endif

    <p class="top-gap">Sudah berdaftar? <a href="{{ route('affiliate.login') }}">Log masuk affiliate</a></p>
</section>
@endsection
