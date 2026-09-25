@extends('layouts.admin', ['title' => 'Bayaran'])

@section('content')
<header class="adm-top"><div><h1>Bayaran</h1><p>Status hanya berubah melalui callback Billplz yang disahkan di pelayan.</p></div></header>
<nav class="adm-tabs">
    <a @class(['is-active' => $mode === 'production']) href="{{ route('admin.billing.payments') }}">Production</a>
    <a @class(['is-active' => $mode === 'sandbox']) href="{{ route('admin.billing.payments', ['mode' => 'sandbox']) }}">Sandbox (TEST)</a>
</nav>
<article class="card adm-scroll">
    <table class="adm-table">
        <thead><tr><th>Invois</th><th>ID Bil Billplz</th><th>Jumlah (RM)</th><th>Status</th><th>Disahkan</th><th>Nota</th></tr></thead>
        <tbody>
        @forelse ($payments as $payment)
            <tr>
                <td><a href="{{ route('admin.billing.invoice', $payment->invoice_id) }}">{{ $payment->invoice->number }}</a></td>
                <td>{{ $payment->gateway_bill_id ?? '-' }}</td>
                <td>{{ $payment->amount() }}</td>
                <td><span class="adm-badge {{ $payment->status }}">{{ $payment->status }}</span></td>
                <td>{{ $payment->verified_at?->format('d/m/Y H:i:s') ?? 'Menunggu callback' }}</td>
                <td>{{ $payment->review_reason }}</td>
            </tr>
        @empty
            <tr><td colspan="6">Tiada bayaran.</td></tr>
        @endforelse
        </tbody>
    </table>
    {{ $payments->links() }}
</article>
@endsection
