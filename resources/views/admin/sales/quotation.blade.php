@extends('layouts.admin', ['title' => $quotation->number ?? 'Quotation #'.$quotation->id])

@section('content')
<header class="adm-top">
    <div><h1>{{ $quotation->number ?? 'Quotation #'.$quotation->id }}</h1><p><span class="adm-badge">{{ $quotation->status }}</span> · Master Specification v{{ $quotation->specification?->version }}</p></div>
    <a class="button button-secondary" href="{{ route('admin.sales.quotations') }}">← Senarai</a>
</header>

<div class="adm-grid">
    <article class="card">
        <h2>Pelanggan</h2>
        <dl class="adm-dl">
            <dt>Nama</dt><dd>{{ $quotation->request?->customer?->name }}</dd>
            <dt>Emel</dt><dd>{{ $quotation->request?->customer?->email }}</dd>
            <dt>Telefon</dt><dd>{{ $quotation->request?->customer?->phone ?? '-' }}</dd>
            <dt>Syarikat</dt><dd>{{ $quotation->request?->customer?->company ?? '-' }}</dd>
            <dt>Pakej dipilih</dt><dd>{{ $quotation->price_snapshot['selected_package']['name'] ?? '-' }} ({{ $quotation->price_snapshot['selected_package']['price_label'] ?? '-' }})</dd>
        </dl>
    </article>

    @if ($quotation->specification)
        <article class="card">
            <h2>Ringkasan Master Specification</h2>
            <dl class="adm-dl">
                @foreach ($quotation->specification->specification_snapshot['project_summary'] ?? [] as $item)
                    <dt>{{ $item['label'] }}</dt><dd>{{ is_array($item['value']) ? implode(', ', $item['value']) : $item['value'] }}</dd>
                @endforeach
                @foreach (collect($quotation->specification->specification_snapshot['files'] ?? [])->groupBy('kind_label') as $kindLabel => $kindFiles)
                    <dt>Fail {{ $kindLabel }}</dt><dd>@foreach ($kindFiles as $f)<a href="{{ route('admin.builder-files.show', $f['id']) }}" target="_blank" rel="noopener">{{ $f['name'] }}</a>@if (! $loop->last), @endif @endforeach</dd>
                @endforeach
            </dl>
        </article>
    @endif
</div>

@if ($editable)
    <article class="card" style="margin-top: 1rem;">
        <h2>Lengkapkan & hantar</h2>
        <form method="post" action="{{ route('admin.sales.quotation.send', $quotation) }}" class="adm-form">
            @csrf
            <div class="adm-scroll">
                <table class="adm-table" id="q-items">
                    <thead><tr><th>Penerangan</th><th>Kuantiti</th><th>Harga seunit (RM)</th><th></th></tr></thead>
                    <tbody>
                    @foreach ($items as $i => $item)
                        <tr>
                            <td><input name="items[{{ $i }}][description]" value="{{ $item['description'] }}" maxlength="200" required></td>
                            <td><input name="items[{{ $i }}][quantity]" type="number" min="1" value="{{ $item['quantity'] }}" required style="max-width: 6rem"></td>
                            <td><input name="items[{{ $i }}][unit_price]" type="number" step="0.01" min="0.01" value="{{ $item['unit_price'] }}" required></td>
                            <td><button type="button" class="button button-secondary button-compact q-remove">Buang</button></td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
            <button type="button" class="button button-secondary" id="q-add">+ Tambah item</button>
            <label>Sah sehingga<input name="valid_until" type="date" value="{{ old('valid_until', now()->addDays(14)->toDateString()) }}" min="{{ now()->toDateString() }}" required></label>
            <label>Anggaran tempoh pembangunan (minggu)<input name="estimated_weeks" type="number" min="1" max="52" value="{{ old('estimated_weeks', $quotation->estimated_weeks ?? 4) }}" required></label>
            <label>Template projek (milestone & bahan)
                <select name="project_template">@foreach ($templates as $key => $label)<option value="{{ $key }}" @selected(old('project_template', $defaultTemplate) === $key)>{{ $label }}</option>@endforeach</select>
            </label>
            <p class="adm-warn">Selepas dihantar, harga dan item <b>dikunci</b>. Bila pelanggan menerima dan memilih slot, invois deposit {{ config('billing.deposit_percent') }}% dicipta secara automatik.</p>
            <label class="check"><input type="checkbox" name="confirm" value="1" required> Saya sahkan harga dan skop quotation ini.</label>
            <button class="button" type="submit">Hantar kepada pelanggan</button>
        </form>
    </article>
    <script>
    (function () {
        var body = document.querySelector('#q-items tbody');
        function renumber() { Array.prototype.forEach.call(body.rows, function (row, i) { row.querySelectorAll('input').forEach(function (el) { el.name = el.name.replace(/items\[\d+\]/, 'items[' + i + ']'); }); }); }
        document.getElementById('q-add').onclick = function () { var r = body.rows[body.rows.length - 1].cloneNode(true); r.querySelectorAll('input').forEach(function (el) { el.value = el.type === 'number' && el.name.indexOf('quantity') > -1 ? 1 : ''; }); body.appendChild(r); renumber(); };
        body.addEventListener('click', function (e) { if (e.target.classList.contains('q-remove') && body.rows.length > 1) { e.target.closest('tr').remove(); renumber(); } });
    })();
    </script>
@else
    <article class="card adm-scroll" style="margin-top: 1rem;">
        <h2>Item</h2>
        <table class="adm-table">
            <thead><tr><th>Penerangan</th><th>Kuantiti</th><th>Harga (RM)</th><th>Jumlah (RM)</th></tr></thead>
            <tbody>@foreach ($quotation->items() as $item)<tr><td>{{ $item['description'] }}</td><td>{{ $item['quantity'] }}</td><td>{{ $item['unit_price'] }}</td><td>{{ $item['line_total'] }}</td></tr>@endforeach</tbody>
        </table>
        <dl class="adm-dl" style="margin-top: 1rem;">
            <dt>Jumlah</dt><dd>RM {{ number_format((float) $quotation->total_amount, 2) }}</dd>
            <dt>Sah sehingga</dt><dd>{{ $quotation->valid_until?->format('d/m/Y') ?? '-' }}</dd>
            <dt>Anggaran tempoh</dt><dd>{{ $quotation->estimated_weeks ?? '-' }} minggu · template {{ $quotation->price_snapshot['project_template'] ?? '-' }}</dd>
            <dt>Dihantar</dt><dd>{{ $quotation->sent_at?->format('d/m/Y H:i') ?? '-' }}</dd>
            <dt>Dilihat</dt><dd>{{ $quotation->viewed_at?->format('d/m/Y H:i') ?? '-' }}</dd>
            <dt>Diterima</dt><dd>{{ $quotation->accepted_at?->format('d/m/Y H:i') ?? '-' }}</dd>
        </dl>
    </article>

    @if ($quotation->status === 'ACCEPTED')
        <article class="card adm-scroll" style="margin-top: 1rem;">
            <h2>Bayaran projek</h2>
            <table class="adm-table">
                <thead><tr><th>Invois</th><th>Jenis</th><th>Jumlah (RM)</th><th>Dibayar (RM)</th><th>Status</th><th>Komisyen affiliate</th></tr></thead>
                <tbody>
                @forelse ($projectInvoices as $inv)
                    <tr>
                        <td><a href="{{ route('admin.billing.invoice', $inv) }}">{{ $inv->number }}</a></td>
                        <td>{{ $inv->type }}</td>
                        <td>{{ number_format((float) $inv->total, 2) }}</td>
                        <td>{{ number_format((float) $inv->amount_paid, 2) }}</td>
                        <td><span class="adm-badge {{ $inv->status }}">{{ $inv->status }}</span></td>
                        <td>@if ($c = $commissions->get($inv->id)) RM {{ $c->amount }} · {{ $c->status }} @else - @endif</td>
                    </tr>
                @empty
                    <tr><td colspan="6">Tiada invois.</td></tr>
                @endforelse
                </tbody>
            </table>
            <dl class="adm-dl" style="margin-top: 1rem;">
                <dt>Nilai quotation</dt><dd>RM {{ number_format($summary['total_cents'] / 100, 2) }}</dd>
                @if ($summary['additional_cents'])<dt>Kerja tambahan (CR)</dt><dd>RM {{ number_format($summary['additional_cents'] / 100, 2) }}</dd>@endif
                <dt>Telah diinvois</dt><dd>RM {{ number_format($summary['invoiced_cents'] / 100, 2) }}</dd>
                <dt>Telah dibayar</dt><dd>RM {{ number_format($summary['paid_cents'] / 100, 2) }}</dd>
                <dt>Status projek</dt><dd>{{ $quotation->payment_completed_at ? 'Bayaran selesai (disahkan '.$quotation->payment_completed_at->format('d/m/Y H:i').')' : 'Belum selesai' }}</dd>
            </dl>

            @if (! $quotation->payment_completed_at || $summary['needs_additional_confirmation'])
                <form method="post" action="{{ route('admin.sales.quotation.confirm-payment', $quotation) }}" class="adm-form" style="margin-top: 1rem;">
                    @csrf
                    <p class="adm-danger">Pengesahan ini melepaskan semua komisyen affiliate projek ini (Pending → Released) dan tidak boleh dibatalkan.</p>
                    <label class="check"><input type="checkbox" name="confirm" value="1" required @disabled(! $summary['can_confirm'])> Saya sahkan bayaran keseluruhan projek ini telah selesai.</label>
                    <button class="button" type="submit" @disabled(! $summary['can_confirm'])>Sahkan bayaran projek selesai</button>
                    @unless ($summary['can_confirm'])<span class="adm-help">Aktif bila semua invois projek PAID dan jumlah dibayar sama dengan nilai quotation (termasuk kerja tambahan).</span>@endunless
                </form>
            @endif
        </article>
    @endif
@endif
@endsection
