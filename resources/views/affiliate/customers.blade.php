@extends('layouts.affiliate')

@push('styles')
<style>
    .aff-cust { margin: 0 0 1rem; }
    .aff-cust-head { display: flex; flex-wrap: wrap; justify-content: space-between; gap: .5rem 1rem; margin-bottom: .8rem; }
    .aff-cust-head h2 { margin: 0; font-size: 1.15rem; }
    .aff-cust-head p { margin: .15rem 0 0; color: var(--muted); font-size: .82rem; }
    .aff-cust-sum { display: flex; gap: 1.2rem; font-size: .85rem; }
    .aff-cust-sum b { display: block; font-size: 1rem; color: var(--ink); }
    .aff-cust-sum span { color: var(--muted); }
    .aff-bills { width: 100%; border-collapse: collapse; font-size: .9rem; }
    .aff-bills th, .aff-bills td { padding: .6rem .5rem; border-bottom: 1px solid var(--line); text-align: left; }
    .aff-bills th { color: var(--muted); font-size: .74rem; text-transform: uppercase; letter-spacing: .04em; }
    .aff-scroll { overflow-x: auto; }
    .aff-badge { display: inline-block; padding: .12rem .5rem; border-radius: 99rem; font-size: .74rem; font-weight: 800; background: var(--soft-strong); color: var(--ink); white-space: nowrap; }
    .aff-badge.PAID, .aff-badge.RELEASED { background: #e5f8f0; color: #07614a; }
    .aff-badge.PARTIALLY_PAID, .aff-badge.PENDING { background: #fff5d9; color: #795000; }
    .aff-badge.VOID, .aff-badge.CANCELLED { background: #fff0f2; color: #a72c42; }
    .aff-empty { color: var(--muted); }
    @media (max-width: 44rem) {
        .aff-bills thead { display: none; }
        .aff-bills tr { display: grid; grid-template-columns: 1fr auto; gap: .2rem .6rem; padding: .6rem 0; border-bottom: 1px solid var(--line); }
        .aff-bills td { padding: 0; border: 0; }
        .aff-bills td[data-label]::before { content: attr(data-label) ': '; color: var(--muted); font-size: .78rem; }
    }
</style>
@endpush

@section('content')
<header class="aff-top"><div><h1>Pelanggan</h1><p>Pelanggan yang dikaitkan dengan anda, bil mereka dan komisyen setiap bil.</p></div></header>

@forelse ($customers as $customer)
    <article class="card aff-cust">
        <div class="aff-cust-head">
            <div>
                <h2>{{ $customer['name'] }}</h2>
                <p>Dikaitkan sejak {{ $customer['linked_at']->format('d/m/Y') }}</p>
            </div>
            <div class="aff-cust-sum">
                <div><span>Jumlah jualan</span><b>RM {{ number_format((float) $customer['total_sales'], 2) }}</b></div>
                <div><span>Jumlah komisyen</span><b>RM {{ number_format((float) $customer['total_commission'], 2) }}</b></div>
            </div>
        </div>
        @if ($customer['bills']->isEmpty())
            <p class="aff-empty">Belum ada bil.</p>
        @else
            <div class="aff-scroll">
                <table class="aff-bills">
                    <thead><tr><th>No. bil</th><th>Tarikh</th><th>Jumlah (RM)</th><th>Status bayaran</th><th>Komisyen (RM)</th><th>Status komisyen</th></tr></thead>
                    <tbody>
                    @foreach ($customer['bills'] as $bill)
                        <tr>
                            <td><b>{{ $bill['number'] }}</b></td>
                            <td>{{ $bill['date']->format('d/m/Y') }}</td>
                            <td data-label="Jumlah">{{ number_format((float) $bill['total'], 2) }}</td>
                            <td><span class="aff-badge {{ $bill['status'] }}">{{ $bill['status_label'] }}</span></td>
                            <td data-label="Komisyen">{{ $bill['commission'] !== null ? number_format((float) $bill['commission'], 2) : '-' }}</td>
                            <td>@if ($bill['commission_status'])<span class="aff-badge {{ $bill['commission_status'] }}">{{ $bill['commission_status_label'] }}</span>@else - @endif</td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </article>
@empty
    <section class="card"><p class="aff-empty">Belum ada pelanggan dikaitkan dengan anda. Kongsi link affiliate anda untuk mula.</p></section>
@endforelse
@endsection
