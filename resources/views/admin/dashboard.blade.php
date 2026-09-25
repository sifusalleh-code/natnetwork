@extends('layouts.admin', ['title' => 'Dashboard'])

@section('content')
<header class="adm-top"><div><h1>Dashboard</h1><p>Selamat datang, {{ auth('admin')->user()->name }}.</p></div></header>
<div class="adm-grid">
    <article class="card">
        <h2>Billing</h2>
        <p>Mod aktif: <b>{{ $settings->active_mode }}</b>.</p>
        <p class="adm-help">Kunci sandbox: {{ $settings->hasCredentials('SANDBOX') ? 'lengkap' : 'belum lengkap' }} · Kunci production: {{ $settings->hasCredentials('PRODUCTION') ? 'lengkap' : 'belum lengkap' }}</p>
        <a class="button" href="{{ route('admin.billing.settings') }}">Tetapan Billplz</a>
    </article>
</div>
@endsection
