@extends('layouts.admin', ['title' => 'Invois'])

@section('content')
<header class="adm-top"><div><h1>Invois</h1><p>Rekod production dan sandbox dipaparkan berasingan.</p></div></header>
<nav class="adm-tabs">
    <a @class(['is-active' => $mode === 'production']) href="{{ route('admin.billing.invoices') }}">Production</a>
    <a @class(['is-active' => $mode === 'sandbox']) href="{{ route('admin.billing.invoices', ['mode' => 'sandbox']) }}">Sandbox (TEST)</a>
</nav>
<article class="card adm-scroll">
    <table class="adm-table">
        <thead><tr><th>No. Invois</th><th>Jenis</th><th>Pelanggan</th><th>Jumlah (RM)</th><th>Dibayar (RM)</th><th>Status</th><th>Tarikh</th></tr></thead>
        <tbody>
        @forelse ($invoices as $invoice)
            <tr>
                <td><a href="{{ route('admin.billing.invoice', $invoice) }}">{{ $invoice->number }}</a></td>
                <td>{{ $invoice->type }}</td>
                <td>{{ $invoice->customer_snapshot['name'] ?? '-' }}<br><span class="adm-help">{{ $invoice->customer_snapshot['email'] ?? '' }}</span></td>
                <td>{{ number_format((float) $invoice->total, 2) }}</td>
                <td>{{ number_format((float) $invoice->amount_paid, 2) }}</td>
                <td><span class="adm-badge {{ $invoice->status }}">{{ $invoice->status }}</span></td>
                <td>{{ $invoice->issued_at->format('d/m/Y H:i') }}</td>
            </tr>
        @empty
            <tr><td colspan="7">Tiada invois.</td></tr>
        @endforelse
        </tbody>
    </table>
    {{ $invoices->links() }}
</article>

@if ($mode === 'sandbox')
    <article class="card" style="margin-top: 1rem; max-width: 36rem;">
        <h2>Padam data sandbox</h2>
        <p>Padam semua invois, bayaran dan resit TEST-*. Rekod production tidak terjejas.</p>
        <form method="post" action="{{ route('admin.billing.sandbox.purge') }}" class="adm-form">
            @csrf
            <label class="check"><input type="checkbox" name="confirm" value="1" required> Saya sahkan untuk memadam semua data sandbox.</label>
            <button class="button button-secondary" type="submit">Padam data sandbox</button>
        </form>
    </article>
@endif
@endsection
