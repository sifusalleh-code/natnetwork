@extends('layouts.partner', ['title' => 'Dashboard', 'active' => 'dashboard'])

@section('content')
<header class="prt-top"><div><h1>Hai, {{ $partner->name }}</h1><p>Program Partnership · <span @class(['prt-badge', $partner->tone()])>{{ $partner->label() }}</span></p></div></header>

@if ($partner->status === 'PENDING_REVIEW')
    <article class="card"><h2>Permohonan sedang disemak</h2><p class="prt-empty">Pasukan kami sedang menyemak maklumat anda. Anda akan dimaklumkan melalui emel dan notifikasi apabila akaun diluluskan.</p></article>
@elseif (in_array($partner->status, ['REJECTED', 'SUSPENDED'], true))
    <article class="card"><h2>Akaun {{ strtolower($partner->label()) }}</h2><p class="prt-empty">{{ $partner->review_note }}</p></article>
@endif

@if ($pending)
    <article class="card"><div class="prt-action"><div><b>Modal RM {{ number_format((float) $pending->amount, 2) }} menunggu bayaran</b><span>{{ $pending->number }} · invois {{ $pending->invoice?->number }}</span></div><a class="button" href="{{ route('partner.invoice', $pending->invoice_id) }}">Bayar</a></div></article>
@endif

<div class="prt-grid">
    <article class="card prt-stat"><span>Modal aktif</span><b>RM {{ number_format($capitalCents / 100, 2) }}</b><span>Nisbah pool anda: {{ $sharePercent }}%</span></article>
    <article class="card prt-stat"><span>Jumlah pulangan</span><b>RM {{ number_format($earningsCents / 100, 2) }}</b><span>{{ rtrim(rtrim(number_format((float) $settings->pool_percent, 2), '0'), '.') }}% setiap jualan diagih kepada partner</span></article>
    <article class="card prt-stat"><span>Baki belum dikeluarkan</span><b>RM {{ number_format($balanceCents / 100, 2) }}</b><span><a href="{{ route('partner.returns') }}">Lihat pulangan →</a></span></article>
</div>

<article class="card">
    <h2>Pulangan terkini</h2>
    @forelse ($recent as $e)
        <div class="prt-action"><div><b>{{ (float) $e->amount < 0 ? 'Pelarasan refund' : 'Bahagian jualan' }} · RM {{ number_format((float) $e->amount, 2) }}</b><span>{{ $e->entry?->invoice?->number }} · {{ $e->created_at->format('d/m/Y H:i') }}</span></div></div>
    @empty
        <p class="prt-empty">Belum ada pulangan. Pulangan direkod secara automatik setiap kali jualan pelanggan disahkan selepas modal anda aktif.</p>
    @endforelse
</article>
@endsection
