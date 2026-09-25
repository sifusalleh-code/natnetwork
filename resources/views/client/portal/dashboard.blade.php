@extends('layouts.portal', ['title' => 'Dashboard', 'active' => 'dashboard'])

@section('content')
<header class="prt-top"><div><h1>Hai, {{ auth('client')->user()->name }}</h1><p>Apa yang anda perlu buat sekarang?</p></div></header>

<article class="card">
    <h2>Tindakan diperlukan</h2>
    @forelse ($actions as $action)
        <div class="prt-action">
            <div><b>{{ $action['title'] }}</b><span>{{ $action['detail'] }}</span></div>
            <a class="button" href="{{ $action['url'] }}">{{ $action['cta'] }}</a>
        </div>
    @empty
        <p class="prt-empty">Tiada tindakan diperlukan buat masa ini. ✓</p>
    @endforelse
</article>

<div class="prt-grid">
    <article class="card">
        <h2>Projek aktif</h2>
        @if ($activeProject)
            <p><b>{{ $activeProject->name }}</b><br><span class="prt-empty">{{ $activeProject->number }} · {{ $activeProject->clientLabel() }}</span></p>
            @include('client.portal.partials.progress', ['value' => $activeProject->progress])
            <a href="{{ route('client.projects.show', $activeProject) }}">Buka projek →</a>
        @else
            <p class="prt-empty">Belum ada projek aktif. Projek bermula selepas deposit disahkan.</p>
            <a href="{{ route('client.projects') }}">Lihat Projects →</a>
        @endif
    </article>
    <article class="card">
        <h2>Bayaran</h2>
        <div class="prt-grid">
            <div class="prt-stat"><span>Baki perlu dibayar</span><b>RM {{ number_format($outstanding, 2) }}</b></div>
            <div class="prt-stat"><span>Telah dibayar</span><b>RM {{ number_format($paid, 2) }}</b></div>
        </div>
        <a href="{{ route('client.billing') }}">Lihat Billing →</a>
    </article>
</div>

<article class="card">
    <h2>Aktiviti terkini</h2>
    @forelse ($activity as $item)
        <div class="prt-action"><div><b>{{ $item['text'] }}</b><span>{{ $item['at']?->format('d/m/Y H:i') }}</span></div></div>
    @empty
        <p class="prt-empty">Belum ada aktiviti. <a href="{{ route('builder.start') }}">Mula projek baharu →</a></p>
    @endforelse
</article>

<x-affiliate-offer-card :email="auth('client')->user()->email" />
@endsection
