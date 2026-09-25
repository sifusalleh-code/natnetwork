@extends('layouts.portal', ['title' => 'Pilih slot projek', 'active' => 'quotations'])

@section('content')
<header class="prt-top">
    <div><h1>Pilih minggu mula projek</h1><p>{{ $quotation->number }} · anggaran tempoh pembangunan {{ $weeks }} minggu</p></div>
    <a class="button button-secondary" href="{{ route('client.quotations.show', $quotation) }}">← Quotation</a>
</header>

@if ($hold && $hold->status === 'RESERVED')
    <article class="card">
        <h2>Slot telah ditempah ✓</h2>
        <p>Projek dijadualkan bermula minggu <b>{{ $hold->start_date->format('d/m/Y') }}</b>. Pasukan kami akan memulakan projek mengikut slot ini.</p>
        <a class="button" href="{{ route('client.projects') }}">Lihat Projects</a>
    </article>
@else
    @if ($hold)
        <article class="card">
            <h2>Slot sedang dipegang</h2>
            <p>Minggu <b>{{ $hold->start_date->format('d/m/Y') }}</b> dipegang untuk anda sehingga <b>{{ $hold->expires_at->format('d/m/Y H:i') }}</b>. Slot hanya ditempah selepas bayaran disahkan.</p>
            @if ($deposit && in_array($deposit->status, ['ISSUED', 'PARTIALLY_PAID'], true))<a class="button" href="{{ route('client.billing.invoice', $deposit) }}">Bayar invois {{ $deposit->number }} (RM {{ number_format((float) $deposit->total, 2) }})</a>@endif
        </article>
    @endif

    <article class="card">
        <h2>{{ $hold ? 'Tukar minggu mula' : 'Minggu tersedia' }}</h2>
        <p class="prt-empty">Kami mengehadkan bilangan projek serentak supaya setiap projek mendapat perhatian penuh. Pilih minggu mula dan cara bayaran; slot akan dipegang sementara semasa anda membuat bayaran.</p>
        <form method="post" action="{{ route('client.quotations.slot.hold', $quotation) }}" class="prt-form" style="max-width: none;">
            @csrf
            <div class="prt-grid" style="grid-template-columns: repeat(auto-fill, minmax(12rem, 1fr)); gap: .6rem;">
                @foreach ($starts as $s)
                    <label class="card prt-check" style="display: flex; margin: 0; padding: .8rem 1rem; {{ $s['available'] ? '' : 'opacity: .5;' }}">
                        <input type="radio" name="start_date" value="{{ $s['start']->toDateString() }}" @disabled(! $s['available']) @checked($hold && $hold->start_date->toDateString() === $s['start']->toDateString()) required style="width: auto; min-height: 0;">
                        <span><b>{{ $s['start']->format('d/m/Y') }}</b><br><span class="prt-empty">hingga {{ $s['end']->format('d/m/Y') }} · {{ $s['available'] ? 'Tersedia' : 'Penuh' }}</span></span>
                    </label>
                @endforeach
            </div>
            @include('client.portal.partials.payment-plan', ['plans' => $plans])
            <button class="button" type="submit">Pegang slot & teruskan ke bayaran</button>
        </form>
    </article>
@endif
@endsection
