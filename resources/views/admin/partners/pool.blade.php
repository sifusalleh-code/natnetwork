@extends('layouts.admin', ['title' => 'Pool Partnership'])

@section('content')
<header class="adm-top"><div><h1>Lejar Pool Partnership</h1><p>Satu rekod bagi setiap jualan pelanggan yang dibayar (ALLOCATION) dan setiap refund (REVERSAL).</p></div><a class="button button-secondary" href="{{ route('admin.partners.index') }}">← Partnership</a></header>
<article class="card adm-scroll">
    <table class="adm-table">
        <thead><tr><th>Tarikh</th><th>Jenis</th><th>Invois</th><th>Jualan (RM)</th><th>%</th><th>Pool (RM)</th><th>Diagih (RM)</th><th>Jumlah modal (RM)</th></tr></thead>
        <tbody>
        @forelse ($entries as $e)
            <tr>
                <td>{{ $e->created_at->format('d/m/Y H:i') }}</td><td><span class="adm-badge">{{ $e->type }}</span></td>
                <td>@if ($e->invoice)<a href="{{ route('admin.billing.invoice', $e->invoice) }}">{{ $e->invoice->number }}</a>@endif</td>
                <td>{{ number_format((float) $e->sale_amount, 2) }}</td><td>{{ (float) $e->pool_percent }}</td>
                <td>{{ number_format((float) $e->pool_amount, 2) }}</td><td>{{ number_format((float) $e->allocated_amount, 2) }}</td><td>{{ number_format((float) $e->total_capital, 2) }}</td>
            </tr>
        @empty
            <tr><td colspan="8">Belum ada rekod.</td></tr>
        @endforelse
        </tbody>
    </table>
    {{ $entries->links() }}
</article>
@endsection
