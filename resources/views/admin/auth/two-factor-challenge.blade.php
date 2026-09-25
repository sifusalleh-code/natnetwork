@extends('layouts.client', ['title' => 'Kod 2FA Admin | NatNetwork Synergy'])

@section('content')
<section class="auth-card">
    <p class="eyebrow">Admin · 2FA</p>
    <h1>Masukkan kod 2FA</h1>
    <p class="lead">Buka aplikasi authenticator anda dan masukkan kod enam digit semasa.</p>
    <form method="post" action="{{ route('admin.two-factor.verify') }}" class="form-stack">
        @csrf
        <label for="code">Kod enam digit</label>
        <input id="code" name="code" type="text" inputmode="numeric" pattern="[0-9]{6}" autocomplete="one-time-code" required autofocus>
        @error('code')<p class="form-error">{{ $message }}</p>@enderror
        <button class="button" type="submit">Log masuk</button>
    </form>
</section>
@endsection
