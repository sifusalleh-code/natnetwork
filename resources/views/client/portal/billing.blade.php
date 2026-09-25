@extends('layouts.portal', ['title' => 'Billing', 'active' => 'billing'])

@section('content')
<header class="prt-top"><div><h1>Billing</h1><p>Invois, bayaran dan resit anda.</p></div></header>

<div class="prt-grid">
    <article class="card prt-stat"><span>Nilai semasa</span><b>RM {{ number_format($total, 2) }}</b></article>
    <article class="card prt-stat"><span>Telah dibayar</span><b>RM {{ number_format($paid, 2) }}</b></article>
    <article class="card prt-stat"><span>Baki</span><b>RM {{ number_format($outstanding, 2) }}</b></article>
</div>

<article class="card prt-scroll">
    <h2>Invois & resit</h2>
    <table class="prt-table">
        <thead><tr><th>No. invois</th><th>Tarikh</th><th>Jumlah (RM)</th><th>Status</th><th>Resit</th><th></th></tr></thead>
        <tbody>
        @forelse ($invoices as $inv)
            <tr>
                <td><b>{{ $inv->number }}</b></td>
                <td>{{ $inv->issued_at->format('d/m/Y') }}</td>
                <td>{{ number_format((float) $inv->total, 2) }}</td>
                <td>@include('client.portal.partials.invoice-status', ['inv' => $inv])</td>
                <td>@forelse ($inv->receipts as $r)<a href="{{ route('client.billing.receipt', $r) }}">{{ $r->number }}</a><br>@empty - @endforelse</td>
                <td><a href="{{ route('client.billing.invoice', $inv) }}">{{ in_array($inv->status, ['ISSUED', 'PARTIALLY_PAID']) ? 'Bayar' : 'Lihat' }}</a></td>
            </tr>
        @empty
            <tr><td colspan="6" class="prt-empty">Belum ada invois.</td></tr>
        @endforelse
        </tbody>
    </table>
</article>
@endsection
