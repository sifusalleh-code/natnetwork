@extends('layouts.admin', ['title' => 'Orders'])

@section('content')
<header class="adm-top"><div><h1>Orders</h1><p>Order disahkan secara automatik selepas deposit 50% disahkan melalui callback Billplz.</p></div></header>
<article class="card adm-scroll">
    <table class="adm-table">
        <thead><tr><th>No. Order</th><th>Pelanggan</th><th>Quotation</th><th>Slot mula</th><th>Status</th><th>Disahkan</th></tr></thead>
        <tbody>
        @forelse ($orders as $o)
            <tr>
                <td>{{ $o->number }}</td>
                <td>{{ $o->customer?->name }}<br><span class="adm-help">{{ $o->customer?->email }}</span></td>
                <td><a href="{{ route('admin.sales.quotation', $o->quotation_id) }}">{{ $o->quotation?->number }}</a></td>
                <td>{{ $o->slotHold?->start_date?->format('d/m/Y') ?? '-' }}@if ($o->slotHold?->conflict) <span class="adm-badge FAILED">KONFLIK</span>@endif</td>
                <td><span class="adm-badge">{{ $o->status }}</span></td>
                <td>{{ $o->confirmed_at?->format('d/m/Y H:i') }}</td>
            </tr>
        @empty
            <tr><td colspan="6">Tiada order.</td></tr>
        @endforelse
        </tbody>
    </table>
    {{ $orders->links() }}
</article>
@endsection
