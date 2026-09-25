@extends('layouts.portal', ['title' => $quotation->number, 'active' => 'quotations'])

@section('content')
<header class="prt-top">
    <div><h1>{{ $quotation->number }}</h1><p>@include('client.portal.partials.quotation-status', ['q' => $quotation]) · Sah sehingga {{ $quotation->valid_until?->format('d/m/Y') }}</p></div>
    <a class="button button-secondary no-print" href="{{ route('client.quotations') }}">← Quotations</a>
</header>

<article class="card prt-scroll">
    <h2>Skop & harga</h2>
    <table class="prt-table">
        <thead><tr><th>Perkara</th><th>Kuantiti</th><th>Harga (RM)</th><th>Jumlah (RM)</th></tr></thead>
        <tbody>@foreach ($quotation->items() as $item)<tr><td>{{ $item['description'] }}</td><td>{{ $item['quantity'] }}</td><td>{{ $item['unit_price'] }}</td><td>{{ $item['line_total'] }}</td></tr>@endforeach</tbody>
        <tfoot><tr><th colspan="3">Jumlah</th><th>RM {{ number_format((float) $quotation->total_amount, 2) }}</th></tr></tfoot>
    </table>
    <p class="prt-empty">Deposit {{ config('billing.deposit_percent') }}% (RM {{ number_format((float) $quotation->total_amount * config('billing.deposit_percent') / 100, 2) }}) diperlukan untuk mengesahkan pesanan.</p>
</article>

@if ($quotation->terms_snapshot)
    <article class="card">
        <h2>{{ $quotation->terms_snapshot['title'] ?? 'Terma' }} (versi {{ $quotation->terms_snapshot['version'] ?? '-' }})</h2>
        @foreach ($quotation->terms_snapshot['terms'] ?? [] as $term)
            <p><b>{{ $term['title'] }}.</b> {{ $term['body'] }}</p>
        @endforeach
    </article>
@endif

@if ($quotation->isAwaitingCustomer())
    <article class="card no-print">
        <h2>Terima quotation</h2>
        <form method="post" action="{{ route('client.quotations.accept', $quotation) }}" class="prt-form">
            @csrf
            <label class="prt-check"><input type="checkbox" name="agree" value="1" required> Saya bersetuju dengan skop, harga dan terma quotation {{ $quotation->number }}.</label>
            <button class="button" type="submit">Terima & pilih slot projek</button>
        </form>
    </article>
@elseif ($quotation->status === 'ACCEPTED')
    <article class="card">
        <p>Diterima pada {{ $quotation->accepted_at?->format('d/m/Y H:i') }}.</p>
        <a class="button button-secondary" href="{{ route('client.quotations.slot', $quotation) }}">Slot projek</a>
        @if ($depositInvoice)<a class="button" href="{{ route('client.billing.invoice', $depositInvoice) }}">Lihat invois deposit {{ $depositInvoice->number }}</a>@endif
    </article>
@elseif ($quotation->isExpired())
    <article class="card"><p>Tempoh sah quotation ini telah tamat. Sila <a href="{{ route('contact') }}">hubungi kami</a> untuk quotation baharu.</p></article>
@endif
@endsection
