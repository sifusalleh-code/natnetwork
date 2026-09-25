@extends('layouts.affiliate')

@section('content')
<header class="aff-top"><div><h1>Dashboard</h1><p>Prestasi referral, komisen dan task mingguan anda.</p></div><a class="z-button" href="{{ route('affiliate.studio-poster') }}">Buka Studio Poster</a></header>

<div class="afx-stats">
    <article class="card"><p>Total clicks</p><b>{{ number_format($data['total_clicks']) }}</b></article>
    <article class="card"><p>Unique visitors</p><b>{{ number_format($data['unique_visitors']) }}</b></article>
    <article class="card"><p>Referral (client)</p><b>{{ number_format($data['referrals']) }}</b></article>
    <article class="card is-amber"><p>Komisen pending</p><b>RM{{ number_format((float) $data['wallet']['pending'], 2) }}</b></article>
    <article class="card is-green"><p>Baki boleh dikeluarkan</p><b>RM{{ number_format((float) $data['wallet']['available'], 2) }}</b></article>
</div>

@include('affiliate.partials.link', ['link' => $link])

<div class="afx-grid">
    <article class="card afx-chart">
        <h2>Trafik 14 hari</h2>
        <div class="afx-bars" role="img" aria-label="Klik harian 14 hari terakhir">
            @foreach ($data['traffic'] as $d)
                <div class="afx-bar-col" title="{{ $d['label'] }}: {{ $d['count'] }} klik"><span>{{ $d['count'] ?: '' }}</span><i style="height: {{ max(2, round($d['count'] / $data['traffic_max'] * 100)) }}%"></i><small>{{ $d['label'] }}</small></div>
            @endforeach
        </div>
    </article>
    @include('affiliate.partials.task', ['task' => $data['task']])
</div>

<article class="card">
    <h2>Aktiviti referral terkini</h2>
    <div class="afx-scroll">
        <table class="afx-table">
            <thead><tr><th>Tarikh</th><th>Pelanggan</th><th>Invois</th><th>Jumlah</th><th>Komisen</th><th>Status</th></tr></thead>
            <tbody>
                @forelse ($data['recent'] as $r)
                    <tr><td>{{ $r['date']?->format('d/m/Y') }}</td><td>{{ $r['customer'] }}</td><td>{{ $r['invoice'] }}</td><td>RM{{ number_format((float) $r['gross'], 2) }}</td><td>RM{{ number_format((float) $r['amount'], 2) }}</td><td><span class="afx-badge is-{{ strtolower($r['status']) }}">{{ $r['status_label'] }}</span></td></tr>
                @empty
                    <tr><td colspan="6" class="afx-empty">Belum ada komisen. Kongsi poster dan referral link anda untuk bermula.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</article>
@endsection
