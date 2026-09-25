@extends('layouts.admin', ['title' => 'Withdrawal Affiliate'])

@section('content')
<header class="adm-top"><div><h1>Withdrawal Affiliate</h1><p>Semak permohonan, buat transfer bank secara manual, kemudian tanda Dibayar dengan rujukan transfer.</p></div></header>

<nav class="adm-tabs" aria-label="Tapis status">
    @foreach (['REQUESTED' => 'Dalam proses', 'PAID' => 'Dibayar', 'REJECTED' => 'Ditolak', 'ALL' => 'Semua'] as $key => $label)
        <a @class(['is-active' => $status === $key]) href="{{ route('admin.affiliate.withdrawals', ['status' => $key]) }}">{{ $label }}@if ($key !== 'ALL') ({{ $counts[$key] ?? 0 }})@endif</a>
    @endforeach
</nav>

<form method="post" action="{{ route('admin.affiliate.withdrawals.bulk-reject') }}" x-data="{ selected: [] }">
    @csrf
    @forelse ($withdrawals as $w)
        <article class="card">
            <div class="afx-wd-admin">
                <div style="display: flex; gap: .6rem; align-items: flex-start;">
                    @if ($w->status === 'REQUESTED')
                        <input type="checkbox" name="ids[]" value="{{ $w->id }}" x-model.number="selected" style="margin-top: .3rem;">
                    @endif
                    <div>
                        <h2>#{{ $w->id }} · RM{{ number_format((float) $w->amount, 2) }} <span @class(['adm-badge', 'PAID' => $w->status === 'PAID', 'FAILED' => $w->status === 'REJECTED'])>{{ $w->label() }}</span></h2>
                        <dl class="adm-dl">
                            <dt>Affiliate</dt><dd>{{ $w->affiliate?->name }} ({{ $w->affiliate?->username }}) · {{ $w->affiliate?->email }}</dd>
                            <dt>Bank</dt><dd>{{ $w->bank_name }}</dd>
                            <dt>No. akaun</dt><dd>{{ $w->status === 'REQUESTED' ? $w->bank_account_number : '•••• '.$w->bank_account_last4 }}</dd>
                            <dt>Nama pemegang</dt><dd>{{ $w->account_holder }}</dd>
                            <dt>Dimohon</dt><dd>{{ $w->requested_at->format('d/m/Y H:i') }} · task minggu: {{ $w->week_shares }} perkongsian, {{ $w->week_unique_clicks }} unique clicks</dd>
                            @if ($w->processed_at)<dt>Diproses</dt><dd>{{ $w->processed_at->format('d/m/Y H:i') }} oleh {{ $w->processedBy?->name }} · {{ $w->status === 'PAID' ? 'Rujukan: '.$w->reference : 'Sebab: '.$w->note }}</dd>@endif
                        </dl>
                    </div>
                </div>
                @if ($w->status === 'REQUESTED')
                    <div class="afx-wd-actions">
                        <form method="post" action="{{ route('admin.affiliate.withdrawals.paid', $w) }}" class="adm-form">
                            @csrf
                            <label>Rujukan transfer<input name="reference" maxlength="120" required></label>
                            <label class="check"><input type="checkbox" name="confirm" value="1" required> Saya sahkan transfer RM{{ number_format((float) $w->amount, 2) }} telah dibuat</label>
                            <button class="button" type="submit">Tanda Dibayar</button>
                        </form>
                        <form method="post" action="{{ route('admin.affiliate.withdrawals.reject', $w) }}" class="adm-form">
                            @csrf
                            <label>Sebab tolak<input name="reason" maxlength="500" required></label>
                            <button class="button button-secondary" type="submit">Tolak</button>
                        </form>
                    </div>
                @endif
            </div>
        </article>
    @empty
        <article class="card"><p>Tiada permohonan.</p></article>
    @endforelse
    {{ $withdrawals->links() }}
    @include('admin.partials.bulk-toolbar', ['actions' => [
        ['key' => 'reject', 'label' => 'Tolak Terpilih', 'needsReason' => true, 'reasonLabel' => 'Sebab tolak untuk semua permohonan terpilih (wajib):', 'confirm' => 'Tolak %d permohonan withdrawal terpilih? Baki akan dikembalikan ke setiap affiliate.', 'danger' => true],
    ]])
</form>
@endsection
