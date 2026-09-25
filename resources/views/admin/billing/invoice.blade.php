@extends('layouts.admin', ['title' => $invoice->number])

@section('content')
<header class="adm-top">
    <div><h1>{{ $invoice->number }} @if ($invoice->is_sandbox)<span class="adm-badge TEST">SANDBOX</span>@endif</h1><p>{{ $invoice->type }} · <span class="adm-badge {{ $invoice->status }}">{{ $invoice->status }}</span></p></div>
    <a class="button button-secondary" href="{{ route('admin.billing.invoices', $invoice->is_sandbox ? ['mode' => 'sandbox'] : []) }}">← Senarai invois</a>
</header>

<div class="adm-grid">
    <article class="card">
        <h2>Butiran</h2>
        <dl class="adm-dl">
            <dt>Pelanggan</dt><dd>{{ $invoice->customer_snapshot['name'] ?? '-' }} ({{ $invoice->customer_snapshot['email'] ?? '-' }})</dd>
            <dt>Tarikh</dt><dd>{{ $invoice->issued_at->format('d/m/Y H:i') }}</dd>
            <dt>Jumlah</dt><dd>RM {{ number_format((float) $invoice->total, 2) }}</dd>
            <dt>Dibayar</dt><dd>RM {{ number_format((float) $invoice->amount_paid, 2) }}</dd>
            <dt>Baki</dt><dd>RM {{ number_format($invoice->outstandingCents() / 100, 2) }}</dd>
        </dl>
        @if ($invoice->is_sandbox && $invoice->outstandingCents() > 0)
            <form method="post" action="{{ route('admin.billing.invoice.pay', $invoice) }}">@csrf<button class="button" type="submit">Bayar melalui Billplz Sandbox</button></form>
        @endif
    </article>
    <article class="card adm-scroll">
        <h2>Item</h2>
        <table class="adm-table">
            <thead><tr><th>Penerangan</th><th>Kuantiti</th><th>Harga (RM)</th><th>Jumlah (RM)</th></tr></thead>
            <tbody>@foreach ($invoice->items_snapshot as $item)<tr><td>{{ $item['description'] }}</td><td>{{ $item['quantity'] }}</td><td>{{ $item['unit_price'] }}</td><td>{{ $item['line_total'] }}</td></tr>@endforeach</tbody>
        </table>
    </article>
</div>

<article class="card adm-scroll" style="margin-top: 1rem;">
    <h2>Bayaran</h2>
    <table class="adm-table">
        <thead><tr><th>ID Bil Billplz</th><th>Jumlah (RM)</th><th>Status</th><th>Disahkan</th><th>Resit</th><th>Nota</th></tr></thead>
        <tbody>
        @forelse ($invoice->payments as $payment)
            <tr>
                <td>@if ($payment->gateway_url)<a href="{{ $payment->gateway_url }}" target="_blank" rel="noopener">{{ $payment->gateway_bill_id }}</a>@else - @endif</td>
                <td>{{ $payment->amount() }}</td>
                <td><span class="adm-badge {{ $payment->status }}">{{ $payment->status }}</span></td>
                <td>{{ $payment->verified_at?->format('d/m/Y H:i:s') ?? 'Menunggu callback' }}</td>
                <td>{{ $payment->receipt?->number ?? '-' }}</td>
                <td>{{ $payment->review_reason }}</td>
            </tr>
        @empty
            <tr><td colspan="6">Tiada bayaran.</td></tr>
        @endforelse
        </tbody>
    </table>
</article>
@endsection
