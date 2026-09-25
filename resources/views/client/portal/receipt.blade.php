@extends('layouts.portal', ['title' => $receipt->number, 'active' => 'billing'])

@section('content')
<header class="prt-top">
    <div><h1>Resit {{ $receipt->number }}</h1><p>@if ($receipt->is_sandbox)<span class="prt-badge">UJIAN</span> @endif{{ $receipt->issued_at->format('d/m/Y H:i') }}</p></div>
    <div class="no-print"><a class="button button-secondary" href="{{ route('client.billing.invoice', $receipt->invoice) }}">← Invois</a> <button class="button button-secondary" type="button" onclick="window.print()">Cetak / PDF</button></div>
</header>
<article class="card">
    <dl class="prt-dl">
        <dt>Diterima daripada</dt><dd>{{ $receipt->invoice->customer_snapshot['name'] ?? '' }}</dd>
        <dt>Untuk invois</dt><dd>{{ $receipt->invoice->number }}</dd>
        <dt>Jumlah diterima</dt><dd>RM {{ number_format((float) $receipt->amount, 2) }}</dd>
        <dt>Kaedah</dt><dd>Billplz ({{ $receipt->payment->gateway_bill_id }})</dd>
        <dt>Dikeluarkan oleh</dt><dd>{{ $receipt->invoice->seller_snapshot['name'] ?? '' }}</dd>
    </dl>
</article>
@endsection
