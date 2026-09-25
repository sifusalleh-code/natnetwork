@extends('layouts.partner', ['title' => 'Pulangan', 'active' => 'returns'])

@section('content')
<header class="prt-top"><div><h1>Pulangan</h1><p>Bahagian pool setiap jualan, pelarasan refund dan pengeluaran.</p></div></header>

<div class="prt-grid">
    <article class="card prt-stat"><span>Baki belum dikeluarkan</span><b>RM {{ number_format($balanceCents / 100, 2) }}</b><span>Dipindahkan ke {{ $partner->bank_name }} ••••{{ $partner->bank_account_last4 }}</span></article>
</div>

<article class="card prt-scroll">
    <h2>Pengeluaran</h2>
    <table class="prt-table">
        <thead><tr><th>No.</th><th>Amaun (RM)</th><th>Rujukan</th><th>Tarikh</th></tr></thead>
        <tbody>@forelse ($payouts as $p)<tr><td>{{ $p->number }}</td><td>{{ number_format((float) $p->amount, 2) }}</td><td>{{ $p->transfer_reference }}</td><td>{{ $p->paid_at->format('d/m/Y') }}</td></tr>@empty<tr><td colspan="4" class="prt-empty">Belum ada pengeluaran.</td></tr>@endforelse</tbody>
    </table>
</article>

<article class="card prt-scroll">
    <h2>Penyata pulangan</h2>
    <table class="prt-table">
        <thead><tr><th>Tarikh</th><th>Jenis</th><th>Jualan</th><th>Pool</th><th>Modal anda</th><th>Amaun (RM)</th></tr></thead>
        <tbody>
        @forelse ($earnings as $e)
            <tr>
                <td>{{ $e->created_at->format('d/m/Y H:i') }}</td>
                <td>{{ $e->entry->type === 'REVERSAL' ? 'Pelarasan refund' : 'Bahagian jualan' }}</td>
                <td>{{ $e->entry->invoice?->number }} · RM {{ number_format((float) $e->entry->sale_amount, 2) }}</td>
                <td>{{ rtrim(rtrim(number_format((float) $e->entry->pool_percent, 2), '0'), '.') }}% = RM {{ number_format((float) $e->entry->pool_amount, 2) }}</td>
                <td>{{ (float) $e->capital > 0 ? 'RM '.number_format((float) $e->capital, 2).' / RM '.number_format((float) $e->entry->total_capital, 2) : '-' }}</td>
                <td><b>{{ number_format((float) $e->amount, 2) }}</b></td>
            </tr>
        @empty
            <tr><td colspan="6" class="prt-empty">Belum ada pulangan.</td></tr>
        @endforelse
        </tbody>
    </table>
    {{ $earnings->links() }}
</article>
@endsection
