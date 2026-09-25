@extends('layouts.admin', ['title' => $partner->name])

@section('content')
<header class="adm-top">
    <div><h1>{{ $partner->name }}</h1><p><span class="adm-badge">{{ $partner->status }}</span> · {{ $partner->email }}</p></div>
    <div style="display: flex; gap: .5rem;">@include('admin.partials.impersonate', ['type' => 'partner', 'id' => $partner->id]) <a class="button button-secondary" href="{{ route('admin.partners.index') }}">← Senarai</a></div>
</header>

<div class="adm-grid">
    <article class="card">
        <h2>Maklumat (KYC)</h2>
        <dl class="adm-dl">
            <dt>Telefon</dt><dd>{{ $partner->phone }}</dd>
            <dt>{{ $partner->id_type === 'COMPANY' ? 'No. pendaftaran' : 'No. IC' }}</dt><dd>{{ $partner->id_number }}</dd>
            @if ($partner->company_name)<dt>Syarikat</dt><dd>{{ $partner->company_name }}</dd>@endif
            <dt>Bank</dt><dd>{{ $partner->bank_name }} · {{ $partner->bank_account_number }}</dd>
            <dt>Pemegang akaun</dt><dd>{{ $partner->bank_account_holder }}</dd>
            <dt>Emel disahkan</dt><dd>{{ $partner->email_verified_at?->format('d/m/Y H:i') }}</dd>
            @if ($partner->review_note)<dt>Nota semakan</dt><dd>{{ $partner->review_note }}</dd>@endif
        </dl>
    </article>
    <article class="card">
        <h2>Semakan akaun</h2>
        <form method="post" action="{{ route('admin.partners.review', $partner) }}" class="adm-form">
            @csrf
            <label>Keputusan<select name="decision"><option value="approve">Luluskan</option><option value="reject">Tolak</option><option value="suspend">Gantung</option></select></label>
            <label>Nota / sebab (wajib jika tolak atau gantung)<input name="review_note" maxlength="500"></label>
            <button class="button" type="submit">Simpan keputusan</button>
        </form>
    </article>
</div>

<div class="adm-grid" style="margin-top: 1rem;">
    <article class="card">
        <h2>Baki pulangan: RM {{ number_format($balanceCents / 100, 2) }}</h2>
        @if ($balanceCents > 0)
            <form method="post" action="{{ route('admin.partners.payout', $partner) }}" class="adm-form">
                @csrf
                <p class="adm-help">Pindahkan wang ke {{ $partner->bank_name }} {{ $partner->bank_account_number }} ({{ $partner->bank_account_holder }}) dahulu, kemudian rekod di sini.</p>
                <label>Amaun (RM)<input name="amount" type="number" step="0.01" min="0.01" max="{{ number_format($balanceCents / 100, 2, '.', '') }}" value="{{ number_format($balanceCents / 100, 2, '.', '') }}" required></label>
                <label>Rujukan pemindahan<input name="transfer_reference" maxlength="120" required></label>
                <label>Nota<input name="note" maxlength="500"></label>
                <label class="check"><input type="checkbox" name="confirm" value="1" required> Wang telah dipindahkan.</label>
                <button class="button" type="submit">Rekod pengeluaran</button>
            </form>
        @endif
    </article>
    <article class="card adm-scroll">
        <h2>Modal</h2>
        <table class="adm-table">
            <thead><tr><th>No.</th><th>RM</th><th>Status</th><th>Invois</th></tr></thead>
            <tbody>@forelse ($capitals as $c)<tr><td>{{ $c->number }}</td><td>{{ number_format((float) $c->amount, 2) }}</td><td>{{ $c->status }}</td><td>@if ($c->invoice)<a href="{{ route('admin.billing.invoice', $c->invoice) }}">{{ $c->invoice->number }}</a> ({{ $c->invoice->status }})@endif</td></tr>@empty<tr><td colspan="4">Tiada.</td></tr>@endforelse</tbody>
        </table>
    </article>
</div>

<div class="adm-grid" style="margin-top: 1rem;">
    <article class="card adm-scroll">
        <h2>Pulangan terkini</h2>
        <table class="adm-table">
            <thead><tr><th>Tarikh</th><th>Invois jualan</th><th>RM</th></tr></thead>
            <tbody>@forelse ($earnings as $e)<tr><td>{{ $e->created_at->format('d/m/Y H:i') }}</td><td>{{ $e->entry->invoice?->number }} ({{ $e->entry->type }})</td><td>{{ number_format((float) $e->amount, 2) }}</td></tr>@empty<tr><td colspan="3">Tiada.</td></tr>@endforelse</tbody>
        </table>
    </article>
    <article class="card adm-scroll">
        <h2>Pengeluaran</h2>
        <table class="adm-table">
            <thead><tr><th>No.</th><th>RM</th><th>Rujukan</th><th>Tarikh</th></tr></thead>
            <tbody>@forelse ($payouts as $p)<tr><td>{{ $p->number }}</td><td>{{ number_format((float) $p->amount, 2) }}</td><td>{{ $p->transfer_reference }}</td><td>{{ $p->paid_at->format('d/m/Y') }}</td></tr>@empty<tr><td colspan="4">Tiada.</td></tr>@endforelse</tbody>
        </table>
    </article>
</div>
@endsection
