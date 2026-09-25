@extends('layouts.admin', ['title' => 'Quotations'])

@section('content')
<header class="adm-top"><div><h1>Quotations</h1><p>Lengkapkan quotation berstatus REVIEW REQUIRED dan hantar kepada pelanggan.</p></div></header>
<nav class="adm-tabs">
    <a @class(['is-active' => ! $status]) href="{{ route('admin.sales.quotations') }}">Semua</a>
    @foreach (['REVIEW REQUIRED', 'SENT', 'VIEWED', 'ACCEPTED', 'EXPIRED'] as $s)
        <a @class(['is-active' => $status === $s]) href="{{ route('admin.sales.quotations', ['status' => $s]) }}">{{ $s }}</a>
    @endforeach
</nav>
<article class="card adm-scroll">
    <table class="adm-table">
        <thead><tr><th>ID / No.</th><th>Pelanggan</th><th>Pakej</th><th>Jumlah (RM)</th><th>Status</th><th>Tarikh</th></tr></thead>
        <tbody>
        @forelse ($quotations as $q)
            <tr>
                <td><a href="{{ route('admin.sales.quotation', $q) }}">{{ $q->number ?? '#'.$q->id }}</a></td>
                <td>{{ $q->request?->customer?->name }}<br><span class="adm-help">{{ $q->request?->customer?->email }}</span></td>
                <td>{{ $q->price_snapshot['selected_package']['name'] ?? '-' }}</td>
                <td>{{ $q->total_amount !== null ? number_format((float) $q->total_amount, 2) : '-' }}</td>
                <td><span class="adm-badge">{{ $q->status }}</span></td>
                <td>{{ $q->created_at->format('d/m/Y') }}</td>
            </tr>
        @empty
            <tr><td colspan="6">Tiada quotation.</td></tr>
        @endforelse
        </tbody>
    </table>
    {{ $quotations->links() }}
</article>
@endsection
