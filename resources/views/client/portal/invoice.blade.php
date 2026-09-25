@extends('layouts.portal', ['title' => $invoice->number, 'active' => 'billing'])

@section('content')
<header class="prt-top">
    <div><h1>Invois {{ $invoice->number }}</h1><p>@include('client.portal.partials.invoice-status', ['inv' => $invoice]) · {{ $invoice->issued_at->format('d/m/Y') }}</p></div>
    <div class="no-print"><a class="button button-secondary" href="{{ route('client.billing') }}">← Billing</a> <button class="button button-secondary" type="button" onclick="window.print()">Cetak / PDF</button></div>
</header>

<div class="prt-grid">
    <article class="card">
        <h2>Daripada</h2>
        <p><b>{{ $invoice->seller_snapshot['name'] ?? '' }}</b><br>{{ $invoice->seller_snapshot['registration_number'] ?? '' }}<br>{!! implode('<br>', array_map('e', $invoice->seller_snapshot['address_lines'] ?? [])) !!}</p>
    </article>
    <article class="card">
        <h2>Kepada</h2>
        <p><b>{{ $invoice->customer_snapshot['name'] ?? '' }}</b><br>{{ $invoice->customer_snapshot['company'] ?? '' }}<br>{{ $invoice->customer_snapshot['email'] ?? '' }}</p>
    </article>
</div>

<article class="card prt-scroll">
    <table class="prt-table">
        <thead><tr><th>Perkara</th><th>Kuantiti</th><th>Harga (RM)</th><th>Jumlah (RM)</th></tr></thead>
        <tbody>@foreach ($invoice->items_snapshot as $item)<tr><td>{{ $item['description'] }}</td><td>{{ $item['quantity'] }}</td><td>{{ $item['unit_price'] }}</td><td>{{ $item['line_total'] }}</td></tr>@endforeach</tbody>
    </table>
    <dl class="prt-dl" style="margin-top: 1rem;">
        <dt>Jumlah</dt><dd>RM {{ number_format((float) $invoice->total, 2) }}</dd>
        <dt>Dibayar</dt><dd>RM {{ number_format((float) $invoice->amount_paid, 2) }}</dd>
        <dt>Baki</dt><dd>RM {{ number_format($invoice->outstandingCents() / 100, 2) }}</dd>
        @if ((float) $invoice->amount_refunded > 0)<dt>Direfund</dt><dd>RM {{ number_format((float) $invoice->amount_refunded, 2) }}</dd>@endif
    </dl>
</article>

@if (in_array($invoice->status, ['ISSUED', 'PARTIALLY_PAID']) && $invoice->outstandingCents() > 0)
    <article class="card no-print">
        <h2>Bayar sekarang</h2>
        <p class="prt-empty">Anda akan dibawa ke halaman bayaran Billplz (FPX / kad). Status dikemas kini secara automatik selepas bayaran disahkan.</p>
        <form method="post" action="{{ route('client.billing.pay', $invoice) }}">@csrf<button class="button" type="submit">Bayar RM {{ number_format($invoice->outstandingCents() / 100, 2) }}</button></form>
    </article>
@endif

@if ($invoice->receipts->isNotEmpty())
    <article class="card">
        <h2>Resit</h2>
        @foreach ($invoice->receipts as $r)<p><a href="{{ route('client.billing.receipt', $r) }}">{{ $r->number }}</a> · RM {{ number_format((float) $r->amount, 2) }} · {{ $r->issued_at->format('d/m/Y H:i') }}</p>@endforeach
    </article>
@endif
@if ($invoice->refunds->isNotEmpty())
    <article class="card">
        <h2>Refund</h2>
        @foreach ($invoice->refunds as $rf)<p><b>{{ $rf->number }}</b> · RM {{ number_format((float) $rf->amount, 2) }} · {{ $rf->refunded_at->format('d/m/Y') }} · rujukan {{ $rf->transfer_reference }}<br><span class="prt-empty">{{ $rf->reason }}</span></p>@endforeach
    </article>
@endif
@endsection
