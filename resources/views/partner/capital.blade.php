@extends('layouts.partner', ['title' => 'Modal', 'active' => 'capital'])

@section('content')
<header class="prt-top"><div><h1>Modal</h1><p>Modal aktif menentukan nisbah bahagian anda dalam pool jualan.</p></div></header>

@if ($partner->isApproved() && $settings->program_enabled)
    <article class="card">
        <h2>Tambah modal</h2>
        <form method="post" action="{{ route('partner.capital.store') }}" class="prt-form">
            @csrf
            <label>Amaun (RM) — minimum RM {{ number_format((float) $settings->min_capital, 2) }} · had kumpulan RM {{ number_format((float) $settings->max_total_capital, 2) }}<input name="amount" type="number" step="0.01" min="{{ (float) $settings->min_capital }}" value="{{ old('amount') }}" required></label>
            <details><summary>{{ config('partnership_terms.title') }} (versi {{ config('partnership_terms.version') }})</summary>
                @foreach (config('partnership_terms.terms') as $t)<p><b>{{ $t['title'] }}.</b> {{ $t['body'] }}</p>@endforeach
            </details>
            <label class="prt-check"><input type="checkbox" name="agree" value="1" required> Saya telah membaca dan bersetuju dengan terma dan syarat Program Partnership. Saya faham dan jelas pulangan bergantung kepada prestasi marketing dan hasil jualan sebenar dan yakin pihak syarikat lakukan yang terbaik untuk partnership.</label>
            <button class="button" type="submit">Teruskan ke bayaran</button>
        </form>
    </article>
@else
    <article class="card"><p class="prt-empty">{{ $settings->program_enabled ? 'Akaun anda perlu diluluskan sebelum menambah modal.' : 'Penyertaan modal baharu belum dibuka.' }}</p></article>
@endif

<article class="card prt-scroll">
    <h2>Rekod modal</h2>
    <table class="prt-table">
        <thead><tr><th>No.</th><th>Amaun (RM)</th><th>Status</th><th>Tarikh</th><th></th></tr></thead>
        <tbody>
        @forelse ($capitals as $c)
            <tr>
                <td>{{ $c->number }}@if ($c->is_sandbox) <span class="prt-badge">UJIAN</span>@endif</td>
                <td>{{ number_format((float) $c->amount, 2) }}</td>
                <td><span @class(['prt-badge', $c->tone()])>{{ $c->label() }}</span></td>
                <td>{{ ($c->activated_at ?? $c->created_at)->format('d/m/Y') }}</td>
                <td>
                    @if ($c->invoice)<a href="{{ route('partner.invoice', $c->invoice_id) }}">Invois</a>@endif
                    @if ($c->status === 'PENDING_PAYMENT')
                        <form method="post" action="{{ route('partner.capital.cancel', $c) }}" style="display: inline;">@csrf<button class="button button-secondary button-compact" type="submit">Batal</button></form>
                    @endif
                </td>
            </tr>
        @empty
            <tr><td colspan="5" class="prt-empty">Belum ada modal.</td></tr>
        @endforelse
        </tbody>
    </table>
</article>
@endsection
