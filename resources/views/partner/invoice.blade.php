@extends('layouts.partner', ['title' => $invoice->number, 'active' => 'capital'])

@section('content')
<header class="prt-top">
    <div><h1>Invois {{ $invoice->number }}</h1><p>{{ $capital->number }} · <span @class(['prt-badge', $capital->tone()])>{{ $capital->label() }}</span></p></div>
    <div class="no-print"><a class="button button-secondary" href="{{ route('partner.capital') }}">← Modal</a> <button class="button button-secondary" type="button" onclick="window.print()">Cetak / PDF</button></div>
</header>
<article class="card prt-scroll">
    <p><b>{{ $invoice->seller_snapshot['name'] ?? '' }}</b> → <b>{{ $invoice->customer_snapshot['name'] ?? '' }}</b> ({{ $invoice->customer_snapshot['email'] ?? '' }})</p>
    <table class="prt-table">
        <thead><tr><th>Perkara</th><th>Jumlah (RM)</th></tr></thead>
        <tbody>@foreach ($invoice->items_snapshot as $item)<tr><td>{{ $item['description'] }}</td><td>{{ $item['line_total'] }}</td></tr>@endforeach</tbody>
    </table>
    <dl class="prt-dl" style="margin-top: 1rem;">
        <dt>Jumlah</dt><dd>RM {{ number_format((float) $invoice->total, 2) }}</dd>
        <dt>Dibayar</dt><dd>RM {{ number_format((float) $invoice->amount_paid, 2) }}</dd>
        <dt>Status</dt><dd>{{ $invoice->status }}</dd>
    </dl>
</article>
@if ($capital->status === 'PENDING_PAYMENT' && in_array($invoice->status, ['ISSUED', 'PARTIALLY_PAID'], true))
    <article class="card no-print">
        <p class="prt-empty">Anda akan dibawa ke Billplz (FPX / kad). Modal aktif secara automatik selepas bayaran disahkan.</p>
        <form method="post" action="{{ route('partner.pay', $invoice) }}">@csrf<button class="button" type="submit">Bayar RM {{ number_format($invoice->outstandingCents() / 100, 2) }}</button></form>
    </article>
@endif
@foreach ($invoice->receipts as $r)<article class="card"><p>Resit <b>{{ $r->number }}</b> · RM {{ number_format((float) $r->amount, 2) }} · {{ $r->issued_at->format('d/m/Y H:i') }}</p></article>@endforeach
@endsection
