@extends('layouts.affiliate')

@section('content')
<header class="aff-top"><div><h1>Wallet</h1><p>Baki komisen, permohonan withdrawal dan sejarah bayaran.</p></div></header>

<div class="afx-stats">
    <article class="card is-green"><p>Baki boleh dikeluarkan</p><b>RM{{ number_format((float) $summary['available'], 2) }}</b></article>
    <article class="card is-amber"><p>Komisen pending</p><b>RM{{ number_format((float) $summary['pending'], 2) }}</b></article>
    <article class="card"><p>Dalam proses withdrawal</p><b>RM{{ number_format((float) $summary['processing'], 2) }}</b></article>
    <article class="card"><p>Telah dibayar</p><b>RM{{ number_format((float) $summary['withdrawn'], 2) }}</b></article>
</div>
<p class="afx-help">Komisen Pending bertukar kepada Released apabila bayaran projek client selesai dan disahkan admin. Hanya komisen Released boleh dikeluarkan.</p>

<div class="afx-grid">
    <article class="card">
        <h2>Mohon withdrawal</h2>
        @error('amount')<p class="afx-error">{{ $message }}</p>@enderror
        @error('impersonation')<p class="afx-error">{{ $message }}</p>@enderror
        @if ($hasOpenRequest)
            <p class="afx-help">Anda mempunyai permohonan yang sedang diproses. Permohonan baharu boleh dibuat selepas ia selesai.</p>
        @elseif (! $affiliate->bank_name || ! $affiliate->bank_account_number)
            <p class="afx-help">Lengkapkan maklumat bank dalam <a href="{{ route('affiliate.profile') }}">Profil Saya</a> sebelum membuat withdrawal.</p>
        @else
            <form method="post" action="{{ route('affiliate.withdrawals.store') }}" class="afx-form">
                @csrf
                <label>Amaun (RM)<input name="amount" type="number" step="0.01" min="0.01" max="{{ $summary['available'] }}" value="{{ old('amount', $summary['available'] > 0 ? $summary['available'] : '') }}" required></label>
                <p class="afx-help">Dibayar ke {{ $affiliate->bank_name }} {{ $affiliate->maskedBankAccount() }} atas nama {{ $affiliate->name }}.</p>
                <button class="z-button" type="submit" @disabled(! $task['met'] || (float) $summary['available'] <= 0)>Hantar permohonan</button>
                @if (! $task['met'])<p class="afx-help">Lengkapkan task mingguan untuk mengaktifkan withdrawal.</p>@endif
            </form>
        @endif
    </article>
    @include('affiliate.partials.task', ['task' => $task])
</div>

<article class="card">
    <h2>Sejarah withdrawal</h2>
    <div class="afx-scroll">
        <table class="afx-table">
            <thead><tr><th>#</th><th>Tarikh mohon</th><th>Amaun</th><th>Bank</th><th>Status</th><th>Catatan</th></tr></thead>
            <tbody>
                @forelse ($withdrawals as $w)
                    <tr><td>{{ $w->id }}</td><td>{{ $w->requested_at->format('d/m/Y H:i') }}</td><td>RM{{ number_format((float) $w->amount, 2) }}</td><td>{{ $w->bank_name }} •••• {{ $w->bank_account_last4 }}</td><td><span class="afx-badge is-{{ strtolower($w->status) }}">{{ $w->label() }}</span></td><td>{{ $w->status === 'PAID' ? 'Rujukan: '.$w->reference : ($w->note ?? '—') }}</td></tr>
                @empty
                    <tr><td colspan="6" class="afx-empty">Belum ada withdrawal.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</article>

<article class="card">
    <h2>Komisen</h2>
    <div class="afx-scroll">
        <table class="afx-table">
            <thead><tr><th>Tarikh</th><th>Pelanggan</th><th>Invois</th><th>Jumlah invois</th><th>Komisen</th><th>Status</th></tr></thead>
            <tbody>
                @forelse ($commissions as $c)
                    <tr><td>{{ $c->created_at->format('d/m/Y') }}</td><td>{{ \App\Engines\Affiliate\Services\AffiliateCustomerService::maskName((string) $c->customer?->name) }}</td><td>{{ $c->invoice?->number }}</td><td>RM{{ number_format((float) $c->gross_amount, 2) }}</td><td>RM{{ number_format((float) $c->amount, 2) }}</td><td><span class="afx-badge is-{{ strtolower($c->status) }}">{{ \App\Engines\Affiliate\Services\AffiliateCustomerService::COMMISSION_STATUS_LABELS[$c->status] ?? $c->status }}</span></td></tr>
                @empty
                    <tr><td colspan="6" class="afx-empty">Belum ada komisen.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</article>
@endsection
