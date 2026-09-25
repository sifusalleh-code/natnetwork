@extends('layouts.client')

@section('content')
<section class="auth-card">
    <p class="eyebrow">Client Portal</p>
    <h1>Log masuk dengan email</h1>
    <p class="lead">Kami akan hantar kod enam digit ke email anda. Tiada kata laluan diperlukan.</p>

    @php($otpEmail = session('otp_email') ?? (old('code') !== null ? old('email') : null))
    @if (session('status'))<p class="notice">{{ session('status') }}</p>@endif

    <form method="post" action="{{ route('client.otp.send') }}" class="form-stack">
        @csrf
        <label for="email">Email</label>
        <input id="email" name="email" type="email" value="{{ $otpEmail ?? old('email') }}" autocomplete="email" required>
        @error('email')<p class="form-error">{{ $message }}</p>@enderror
        <button class="button" type="submit">Hantar kod</button>
    </form>

    @if ($otpEmail)
        <form method="post" action="{{ route('client.otp.verify') }}" class="form-stack top-gap">
            @csrf
            <input name="email" type="hidden" value="{{ $otpEmail }}">
            <label for="code">Kod enam digit</label>
            <input id="code" name="code" type="text" inputmode="numeric" pattern="[0-9]{6}" autocomplete="one-time-code" required>
            @error('code')<p class="form-error">{{ $message }}</p>@enderror
            <button class="button" type="submit">Log masuk</button>
        </form>
    @endif
</section>
@endsection
