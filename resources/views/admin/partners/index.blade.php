@extends('layouts.admin', ['title' => 'Partnership'])

@section('content')
<header class="adm-top"><div><h1>Program Partnership</h1><p>{{ rtrim(rtrim(number_format((float) $settings->pool_percent, 2), '0'), '.') }}% setiap jualan pelanggan yang dibayar diagih kepada partner ikut nisbah modal aktif.</p></div><a class="button button-secondary" href="{{ route('admin.partners.pool') }}">Lejar pool</a></header>

<div class="adm-grid">
    <article class="card">
        <h2>Ringkasan</h2>
        <dl class="adm-dl">
            <dt>Modal aktif</dt><dd>RM {{ number_format($totals['capital'], 2) }}</dd>
            <dt>Jumlah pool</dt><dd>RM {{ number_format($totals['pool'], 2) }}</dd>
            <dt>Telah diagih</dt><dd>RM {{ number_format($totals['allocated'], 2) }}</dd>
            <dt>Tidak diagih</dt><dd>RM {{ number_format($totals['pool'] - $totals['allocated'], 2) }} <span class="adm-help">(jualan ketika tiada modal aktif)</span></dd>
        </dl>
    </article>
    <article class="card">
        <h2>Tetapan</h2>
        <form method="post" action="{{ route('admin.partners.settings') }}" class="adm-form">
            @csrf @method('PUT')
            <label>Peratus pool setiap jualan (%)<input name="pool_percent" type="number" step="0.01" min="0" max="50" value="{{ old('pool_percent', (float) $settings->pool_percent) }}" required></label>
            <label>Modal minimum (RM)<input name="min_capital" type="number" step="0.01" min="1" value="{{ old('min_capital', (float) $settings->min_capital) }}" required></label>
            <label>Had kumpulan modal (RM)<input name="max_total_capital" type="number" step="0.01" min="1" value="{{ old('max_total_capital', (float) $settings->max_total_capital) }}" required></label>
            <label class="check"><input type="checkbox" name="program_enabled" value="1" @checked($settings->program_enabled)> Program Partnership dibuka (pendaftaran & modal baharu)</label>
            <button class="button" type="submit">Simpan</button>
        </form>
    </article>
</div>

<nav class="adm-tabs" style="margin-top: 1rem;">
    @foreach (['ALL' => 'Semua', 'PENDING_REVIEW' => 'Menunggu semakan', 'APPROVED' => 'Diluluskan', 'REJECTED' => 'Ditolak', 'SUSPENDED' => 'Digantung'] as $s => $label)
        <a @class(['is-active' => $status === $s]) href="{{ route('admin.partners.index', ['status' => $s]) }}">{{ $label }}</a>
    @endforeach
</nav>
<form method="post" action="{{ route('admin.partners.bulk') }}" x-data="{ selected: [] }">
    @csrf
    <article class="card adm-scroll">
        <table class="adm-table">
            <thead><tr>
                <th><input type="checkbox" @change="selected = $event.target.checked ? [{{ $partners->pluck('id')->implode(',') }}] : []"></th>
                <th>Nama</th><th>Emel</th><th>Status</th><th>Modal aktif (RM)</th><th>Daftar</th><th></th>
            </tr></thead>
            <tbody>
            @forelse ($partners as $p)
                <tr>
                    <td><input type="checkbox" name="ids[]" value="{{ $p->id }}" x-model.number="selected"></td>
                    <td><a href="{{ route('admin.partners.show', $p) }}">{{ $p->name }}</a>@if ($p->company_name)<br><span class="adm-help">{{ $p->company_name }}</span>@endif</td>
                    <td>{{ $p->email }}</td>
                    <td><span class="adm-badge">{{ $p->status }}</span></td>
                    <td>{{ number_format($p->activeCapitalCents() / 100, 2) }}</td>
                    <td>{{ $p->created_at->format('d/m/Y') }}</td>
                    <td>@include('admin.partials.impersonate', ['type' => 'partner', 'id' => $p->id])</td>
                </tr>
            @empty
                <tr><td colspan="7">Tiada partner.</td></tr>
            @endforelse
            </tbody>
        </table>
        {{ $partners->links() }}
    </article>
    @include('admin.partials.bulk-toolbar', ['actions' => [
        ['key' => 'approve', 'label' => 'Luluskan', 'needsReason' => false, 'confirm' => 'Luluskan %d partner terpilih?'],
        ['key' => 'reject', 'label' => 'Tolak', 'needsReason' => true, 'reasonLabel' => 'Sebab tolak (wajib):', 'confirm' => 'Tolak %d partner terpilih?', 'danger' => true],
        ['key' => 'suspend', 'label' => 'Gantung', 'needsReason' => true, 'reasonLabel' => 'Sebab gantung (wajib):', 'confirm' => 'Gantung %d partner terpilih?', 'danger' => true],
        ['key' => 'activate', 'label' => 'Aktifkan Semula', 'needsReason' => false, 'confirm' => 'Aktifkan semula %d partner terpilih?'],
        ['key' => 'delete', 'label' => 'Padam (jika tiada modal)', 'needsReason' => false, 'confirm' => 'Padam %d partner terpilih? Partner yang mempunyai modal/earning/payout akan dilangkau secara automatik.', 'danger' => true],
    ]])
</form>
@endsection
