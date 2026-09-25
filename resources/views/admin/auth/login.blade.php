@extends('layouts.client', ['title' => 'Log masuk Admin | NatNetwork Synergy'])

@section('content')
<section class="auth-card">
    <p class="eyebrow">Admin</p>
    <h1>Log masuk Admin</h1>
    <p class="lead">Masukkan emel dan password. Kod 2FA daripada aplikasi authenticator diperlukan selepas ini.</p>
    <form method="post" action="{{ route('admin.login.attempt') }}" class="form-stack">
        @csrf
        <label for="email">Emel</label>
        <input id="email" name="email" type="email" value="{{ old('email') }}" autocomplete="username" required>
        @error('email')<p class="form-error">{{ $message }}</p>@enderror
        <label for="password">Password</label>
        <input id="password" name="password" type="password" autocomplete="current-password" required>
        @error('password')<p class="form-error">{{ $message }}</p>@enderror
        <button class="button" type="submit">Teruskan</button>
    </form>
</section>
@endsection
