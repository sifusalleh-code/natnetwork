@extends('layouts.client', ['title' => 'Daftar 2FA Admin | NatNetwork Synergy'])

@section('content')
<section class="auth-card">
    <p class="eyebrow">Admin · Keselamatan</p>
    <h1>Daftarkan 2FA</h1>
    <p class="lead">2FA wajib untuk akaun Admin. Buka Google Authenticator atau Microsoft Authenticator, pilih <b>Masukkan kunci persediaan</b>, dan masukkan kunci di bawah (jenis: berasaskan masa).</p>
    <p class="notice" style="font-family: ui-monospace, monospace; font-size: 1.05rem; letter-spacing: .08em; overflow-wrap: anywhere;">{{ trim(chunk_split($secret, 4, ' ')) }}</p>
    <details><summary>Pautan otpauth (untuk pengurus kata laluan)</summary><p style="overflow-wrap: anywhere; font-size: .85rem;">{{ $uri }}</p></details>
    <form method="post" action="{{ route('admin.two-factor.setup.confirm') }}" class="form-stack">
        @csrf
        <label for="code">Kod enam digit daripada aplikasi</label>
        <input id="code" name="code" type="text" inputmode="numeric" pattern="[0-9]{6}" autocomplete="one-time-code" required>
        @error('code')<p class="form-error">{{ $message }}</p>@enderror
        <button class="button" type="submit">Sahkan & log masuk</button>
    </form>
</section>
@endsection
