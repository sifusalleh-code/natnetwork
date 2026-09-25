@extends('layouts.portal', ['title' => 'Profile', 'active' => 'profile'])

@section('content')
<header class="prt-top"><div><h1>Profile</h1><p>Maklumat akaun anda.</p></div></header>
<article class="card">
    <form method="post" action="{{ route('client.profile.update') }}" class="prt-form">
        @csrf @method('PUT')
        <label>Nama penuh<input name="name" value="{{ old('name', $user->name) }}" required maxlength="255"></label>
        <label>Syarikat<input name="company" value="{{ old('company', $user->company) }}" maxlength="255"></label>
        <label>Emel (disahkan)<input value="{{ $user->email }}" readonly></label>
        <label>No. Telefon / WhatsApp<input name="phone" type="tel" value="{{ old('phone', $user->phone) }}" required maxlength="50"></label>
        <button class="button" type="submit">Simpan</button>
    </form>
</article>
<nav class="card prt-more" aria-label="Menu lain">
    <a href="{{ route('client.quotations') }}">Quotations</a> · <a href="{{ route('client.files') }}">Files</a> · <a href="{{ route('client.support') }}">Support</a>
    <form method="post" action="{{ route('client.logout') }}" style="margin-top:.6rem">@csrf<button class="button button-secondary" type="submit">Logout</button></form>
</nav>
@endsection
