@extends('layouts.admin', ['title' => $project->number])

@section('content')
<header class="adm-top">
    <div><h1>{{ $project->number }} · {{ $project->name }}</h1><p><span class="adm-badge">{{ str_replace('_', ' ', $project->status) }}</span> · Progress {{ $project->progress }}% · template {{ $project->template }}</p></div>
    <a class="button button-secondary" href="{{ route('admin.projects.index') }}">← Senarai</a>
</header>

<div class="adm-grid">
    <article class="card">
        <h2>Ringkasan</h2>
        <dl class="adm-dl">
            <dt>Pelanggan</dt><dd>{{ $project->customer?->name }} · {{ $project->customer?->email }}</dd>
            <dt>Order</dt><dd>{{ $project->order?->number }}</dd>
            <dt>Quotation</dt><dd><a href="{{ route('admin.sales.quotation', $project->quotation_id) }}">{{ $project->quotation?->number }}</a> · RM {{ number_format((float) $project->quotation?->total_amount, 2) }}</dd>
            <dt>Slot</dt><dd>{{ $project->order?->slotHold?->start_date?->format('d/m/Y') ?? '-' }} ({{ $project->order?->slotHold?->weeks }} minggu)@if ($project->order?->slotHold?->conflict) <span class="adm-badge FAILED">KONFLIK</span>@endif</dd>
            <dt>Dimulakan</dt><dd>{{ $project->started_at?->format('d/m/Y H:i') ?? 'Belum' }}</dd>
            <dt>Invois akhir</dt><dd>@if ($project->finalInvoice)<a href="{{ route('admin.billing.invoice', $project->finalInvoice) }}">{{ $project->finalInvoice->number }}</a> · {{ $project->finalInvoice->status }}@else Dijana automatik bila progress ≥ 80% @endif</dd>
            <dt>Sokongan</dt><dd>{{ $project->support_days }} hari selepas selesai @if ($project->completed_at)(tamat {{ $project->supportEndsAt()->format('d/m/Y') }})@endif</dd>
            @if ($project->status_note)<dt>Nota status</dt><dd>{{ $project->status_note }}</dd>@endif
        </dl>
    </article>

    <article class="card">
        <h2>Tindakan status</h2>
        @if ($project->status === 'WAITING_TO_START')
            <form method="post" action="{{ route('admin.projects.start', $project) }}" class="adm-form" onsubmit="return confirm('START PROJECT? Kelayakan refund standard tamat selepas ini.')">
                @csrf
                <p class="adm-warn">START PROJECT menamatkan kelayakan refund standard pelanggan.</p>
                <button class="button" type="submit">START PROJECT</button>
            </form>
        @endif
        @if ($transitions)
            <form method="post" action="{{ route('admin.projects.transition', $project) }}" class="adm-form" style="margin-top: 1rem;">
                @csrf
                <label>Tukar status
                    <select name="to">@foreach ($transitions as $t)<option value="{{ $t }}">{{ str_replace('_', ' ', $t) }}</option>@endforeach</select>
                </label>
                <label>Sebab / nota (wajib untuk WAITING FOR CLIENT, ON HOLD, CANCELLED)<input name="reason" maxlength="500"></label>
                <button class="button button-secondary" type="submit">Kemas kini status</button>
            </form>
        @endif
        @if ($project->status === 'READY_FOR_HANDOVER')
            <form method="post" action="{{ route('admin.projects.complete', $project) }}" class="adm-form" style="margin-top: 1rem;">
                @csrf
                <p class="adm-help">Syarat: progress 100% dan tiada invois projek tertunggak.</p>
                <label class="check"><input type="checkbox" name="confirm" value="1" required> Saya sahkan projek telah diserahkan.</label>
                <button class="button" type="submit">Tandakan SELESAI</button>
            </form>
        @endif
        @if (! $transitions && ! in_array($project->status, ['WAITING_TO_START', 'READY_FOR_HANDOVER'], true))<p class="adm-help">Tiada tindakan status tersedia.</p>@endif
    </article>
</div>

@if ($project->status === 'WAITING_TO_START')
<article class="card adm-scroll" style="margin-top: 1rem;">
    <h2>Ubah milestone (sebelum START PROJECT)</h2>
    <form method="post" action="{{ route('admin.projects.milestones.update', $project) }}" class="adm-form" style="max-width: none;">
        @csrf @method('PUT')
        <table class="adm-table" id="ms-edit">
            <thead><tr><th>Milestone (dalaman)</th><th>Label pelanggan</th><th>Berat (%)</th><th></th></tr></thead>
            <tbody>
            @foreach (old('milestones', $project->milestones->map->only(['name', 'client_label', 'weight'])->all()) as $i => $m)
                <tr>
                    <td><input name="milestones[{{ $i }}][name]" value="{{ $m['name'] }}" maxlength="120" required></td>
                    <td><input name="milestones[{{ $i }}][client_label]" value="{{ $m['client_label'] }}" maxlength="120"></td>
                    <td><input name="milestones[{{ $i }}][weight]" type="number" min="1" max="100" value="{{ $m['weight'] }}" required style="max-width: 6rem"></td>
                    <td><button type="button" class="button button-secondary button-compact ms-remove">Buang</button></td>
                </tr>
            @endforeach
            </tbody>
        </table>
        <p class="adm-help">Jumlah berat: <b id="ms-total">{{ $project->milestones->sum('weight') }}</b>% (mesti 100%). Milestone dikunci selepas START PROJECT.</p>
        <div style="display: flex; gap: .5rem;"><button type="button" class="button button-secondary" id="ms-add">+ Tambah milestone</button><button class="button" type="submit">Simpan milestone</button></div>
    </form>
    <script>
    (function () {
        var body = document.querySelector('#ms-edit tbody'), total = document.getElementById('ms-total');
        function sync() { var t = 0; Array.prototype.forEach.call(body.rows, function (row, i) { row.querySelectorAll('input').forEach(function (el) { el.name = el.name.replace(/milestones\[\d+\]/, 'milestones[' + i + ']'); if (el.name.indexOf('weight') > -1) t += parseInt(el.value || 0, 10); }); }); total.textContent = t; }
        document.getElementById('ms-add').onclick = function () { var r = body.rows[body.rows.length - 1].cloneNode(true); r.querySelectorAll('input').forEach(function (el) { el.value = ''; }); body.appendChild(r); sync(); };
        body.addEventListener('click', function (e) { if (e.target.classList.contains('ms-remove') && body.rows.length > 1) { e.target.closest('tr').remove(); sync(); } });
        body.addEventListener('input', sync);
    })();
    </script>
</article>
@endif

<article class="card adm-scroll" style="margin-top: 1rem;">
    <h2>Milestone</h2>
    <table class="adm-table">
        <thead><tr><th>#</th><th>Milestone</th><th>Label pelanggan</th><th>Berat</th><th>Status</th><th></th></tr></thead>
        <tbody>
        @foreach ($project->milestones as $m)
            <tr>
                <td>{{ $m->position }}</td><td>{{ $m->name }}</td><td>{{ $m->client_label }}</td><td>{{ $m->weight }}%</td>
                <td>{{ $m->completed_at ? 'Selesai '.$m->completed_at->format('d/m/Y') : 'Belum' }}</td>
                <td>@if (! $m->completed_at && $project->isStarted() && ! $project->isClosed())
                    <form method="post" action="{{ route('admin.projects.milestones.complete', $m) }}" onsubmit="return confirm('Tandakan milestone ini selesai?')">@csrf<button class="button button-secondary button-compact" type="submit">Selesai</button></form>
                @endif</td>
            </tr>
        @endforeach
        </tbody>
    </table>
</article>

<article class="card adm-scroll" style="margin-top: 1rem;">
    <h2>Bahan & maklumat pelanggan</h2>
    <table class="adm-table">
        <thead><tr><th>Item</th><th>Jenis</th><th>Status</th><th>Kandungan</th><th>Semakan</th></tr></thead>
        <tbody>
        @forelse ($project->contentItems as $item)
            <tr>
                <td>{{ $item->title }}@if ($item->blocking) <span class="adm-badge">WAJIB</span>@endif @if ($item->help_requested)<span class="adm-badge FAILED">PERLU BANTUAN</span>@endif @if ($item->description)<br><span class="adm-help">{{ $item->description }}</span>@endif</td>
                <td>{{ $item->kind }}</td>
                <td>{{ \App\Engines\ProjectContent\Models\ProjectContentItem::LABELS[$item->status][0] ?? $item->status }}@if ($item->admin_note)<br><span class="adm-help">{{ $item->admin_note }}</span>@endif</td>
                <td>
                    @if ($item->info_text)<div style="white-space: pre-line; max-width: 28rem;">{{ $item->info_text }}</div>@endif
                    @foreach ($item->files as $f)<div><a href="{{ route('admin.projects.files.download', $f) }}">{{ $f->original_name }}</a> <span class="adm-help">v{{ $f->version }}</span></div>@endforeach
                </td>
                <td>@if ($item->status === 'SUBMITTED')
                    <form method="post" action="{{ route('admin.projects.content.review', $item) }}" class="adm-form">
                        @csrf
                        <input name="reason" maxlength="500" placeholder="Sebab (wajib jika perlu kemas kini)">
                        <div style="display: flex; gap: .5rem;"><button class="button button-compact" name="decision" value="accept">Terima</button><button class="button button-secondary button-compact" name="decision" value="update">Perlu kemas kini</button></div>
                    </form>
                @endif</td>
            </tr>
        @empty
            <tr><td colspan="5">Tiada item.</td></tr>
        @endforelse
        </tbody>
    </table>
    @unless ($project->isClosed())
        <form method="post" action="{{ route('admin.projects.content.request', $project) }}" class="adm-form" style="margin-top: 1rem;">
            @csrf
            <h3>Minta bahan tambahan</h3>
            <label>Tajuk<input name="title" maxlength="200" required></label>
            <label>Jenis<select name="kind"><option value="FILE">Fail</option><option value="INFO">Maklumat</option></select></label>
            <label>Penerangan<input name="description" maxlength="1000"></label>
            <label class="check"><input type="checkbox" name="blocking" value="1"> Wajib sebelum kerja diteruskan</label>
            <button class="button button-secondary" type="submit">Hantar permintaan</button>
        </form>
    @endunless
</article>

<article class="card adm-scroll" style="margin-top: 1rem;">
    <h2>Fail projek</h2>
    <table class="adm-table">
        <thead><tr><th>Fail</th><th>Oleh</th><th>Visibility</th><th>Saiz</th><th>Tarikh</th></tr></thead>
        <tbody>
        @forelse ($project->files as $f)
            <tr>
                <td><a href="{{ route('admin.projects.files.download', $f) }}">{{ $f->original_name }}</a>@if ($f->is_deliverable) <span class="adm-badge PAID">DELIVERABLE</span>@endif</td>
                <td>{{ $f->uploaded_by }}</td><td><span class="adm-badge">{{ $f->visibility }}</span></td>
                <td>{{ number_format($f->size / 1024, 0) }} KB</td><td>{{ $f->created_at->format('d/m/Y H:i') }}</td>
            </tr>
        @empty
            <tr><td colspan="5">Tiada fail.</td></tr>
        @endforelse
        </tbody>
    </table>
    <form method="post" action="{{ route('admin.projects.files.upload', $project) }}" enctype="multipart/form-data" class="adm-form" style="margin-top: 1rem;">
        @csrf
        <h3>Muat naik fail</h3>
        <input type="file" name="file" required>
        <label>Visibility<select name="visibility"><option value="INTERNAL">INTERNAL (admin sahaja)</option><option value="CLIENT">CLIENT (pelanggan boleh lihat)</option></select></label>
        <label class="check"><input type="checkbox" name="is_deliverable" value="1"> Deliverable (CLIENT sahaja)</label>
        <button class="button button-secondary" type="submit">Muat naik</button>
    </form>
</article>

<article class="card adm-scroll" style="margin-top: 1rem;" id="perubahan">
    <h2>Change Requests</h2>
    @forelse ($changes as $cr)
        <div style="padding: .75rem 0; border-bottom: 1px solid rgba(127,127,127,.2);">
            <p><b>{{ $cr->number }} · {{ $cr->title }}</b> <span class="adm-badge">{{ $cr->status }}</span> <span class="adm-help">{{ $cr->created_at->format('d/m/Y H:i') }}</span></p>
            <div style="white-space: pre-line;">{{ $cr->description }}</div>
            @if ($cr->amount)<p class="adm-help">Kerja tambahan RM {{ number_format((float) $cr->amount, 2) }} · +{{ $cr->extra_weeks }} minggu @if ($cr->invoice) · invois <a href="{{ route('admin.billing.invoice', $cr->invoice) }}">{{ $cr->invoice->number }}</a> ({{ $cr->invoice->status }})@endif</p>@endif
            @if ($cr->admin_note)<p class="adm-help">Nota: {{ $cr->admin_note }}</p>@endif
            @if ($cr->status === 'SUBMITTED')
                <form method="post" action="{{ route('admin.change-requests.assess', $cr) }}" class="adm-form">
                    @csrf
                    <label>Keputusan
                        <select name="decision">
                            <option value="IN_SCOPE">Revision dalam skop (tiada caj)</option>
                            <option value="ADDITIONAL_WORK">Additional Work (sebut harga)</option>
                            <option value="DECLINE">Tolak</option>
                        </select>
                    </label>
                    <label>Harga kerja tambahan (RM) — untuk Additional Work<input name="amount" type="number" step="0.01" min="0"></label>
                    <label>Tambahan tempoh (minggu)<input name="extra_weeks" type="number" min="0" max="52" value="0"></label>
                    <label>Nota kepada pelanggan (wajib jika tolak)<textarea name="admin_note" rows="2" maxlength="2000"></textarea></label>
                    <button class="button button-secondary" type="submit">Simpan penilaian</button>
                </form>
            @endif
        </div>
    @empty
        <p class="adm-help">Tiada permintaan perubahan.</p>
    @endforelse
</article>

@if ($project->order?->slotHold && ! $project->isClosed())
<article class="card" style="margin-top: 1rem;">
    <h2>Jadual semula slot</h2>
    <form method="post" action="{{ route('admin.projects.reschedule', $project) }}" class="adm-form">
        @csrf
        <label>Minggu mula baharu (Isnin)<input name="start_date" type="date" value="{{ old('start_date', $project->order->slotHold->start_date->toDateString()) }}" required></label>
        <label>Tempoh (minggu)<input name="weeks" type="number" min="1" max="104" value="{{ old('weeks', $project->order->slotHold->weeks) }}" required></label>
        <label>Sebab (dipaparkan kepada pelanggan)<input name="reason" maxlength="500" required></label>
        <label class="check"><input type="checkbox" name="override" value="1"> Saya faham kapasiti mungkin melebihi had (override)</label>
        <button class="button button-secondary" type="submit">Jadual semula</button>
    </form>
</article>
@endif

@if ($refundable->isNotEmpty())
<article class="card" style="margin-top: 1rem;">
    <h2>Refund (sebelum START PROJECT)</h2>
    <form method="post" action="{{ route('admin.projects.refund', $project) }}" class="adm-form" onsubmit="return confirm('Rekod refund? Projek akan DIBATALKAN dan slot dilepaskan. Tindakan ini tidak boleh dibatalkan.')">
        @csrf
        <p class="adm-danger">Billplz tiada API refund. Pindahkan wang kepada pelanggan secara manual dahulu, kemudian rekod di sini. Projek, order dan Change Request terbuka akan dibatalkan; komisyen affiliate projek dibatalkan; invois belum dibayar di-void.</p>
        @foreach ($refundable as $inv)
            <label>{{ $inv->number }} ({{ $inv->type }}) — boleh refund sehingga RM {{ number_format($inv->refundableCents() / 100, 2) }}
                <input name="amounts[{{ $inv->id }}]" type="number" step="0.01" min="0" max="{{ number_format($inv->refundableCents() / 100, 2, '.', '') }}" value="{{ number_format($inv->refundableCents() / 100, 2, '.', '') }}">
            </label>
        @endforeach
        <label>Sebab refund<input name="reason" maxlength="500" required></label>
        <label>Rujukan pemindahan bank<input name="transfer_reference" maxlength="120" required></label>
        <label class="check"><input type="checkbox" name="confirm" value="1" required> Saya sahkan wang telah dipindahkan kepada pelanggan.</label>
        <button class="button" type="submit">Rekod refund & batal projek</button>
    </form>
</article>
@endif

@if ($refunds->isNotEmpty())
<article class="card adm-scroll" style="margin-top: 1rem;">
    <h2>Refund</h2>
    <table class="adm-table">
        <thead><tr><th>No.</th><th>Invois</th><th>Amaun (RM)</th><th>Rujukan</th><th>Sebab</th><th>Tarikh</th></tr></thead>
        <tbody>@foreach ($refunds as $rf)<tr><td>{{ $rf->number }}</td><td>{{ $rf->invoice->number }}</td><td>{{ number_format((float) $rf->amount, 2) }}</td><td>{{ $rf->transfer_reference }}</td><td>{{ $rf->reason }}</td><td>{{ $rf->refunded_at->format('d/m/Y H:i') }}</td></tr>@endforeach</tbody>
    </table>
</article>
@endif

<div class="adm-grid" style="margin-top: 1rem;">
    <article class="card adm-scroll">
        <h2>Invois projek</h2>
        <table class="adm-table">
            <thead><tr><th>Invois</th><th>Jenis</th><th>Jumlah (RM)</th><th>Status</th></tr></thead>
            <tbody>@forelse ($invoices as $inv)<tr><td><a href="{{ route('admin.billing.invoice', $inv) }}">{{ $inv->number }}</a></td><td>{{ $inv->type }}</td><td>{{ number_format((float) $inv->total, 2) }}</td><td><span class="adm-badge {{ $inv->status }}">{{ $inv->status }}</span></td></tr>@empty<tr><td colspan="4">Tiada invois.</td></tr>@endforelse</tbody>
        </table>
    </article>
    <article class="card adm-scroll">
        <h2>Sejarah status</h2>
        <table class="adm-table">
            <thead><tr><th>Tarikh</th><th>Status</th><th>Sebab</th></tr></thead>
            <tbody>@foreach ($project->statusLogs as $log)<tr><td>{{ $log->created_at->format('d/m/Y H:i') }}</td><td>{{ $log->from_status ? str_replace('_', ' ', $log->from_status).' → ' : '' }}{{ str_replace('_', ' ', $log->to_status) }}</td><td>{{ $log->reason }}</td></tr>@endforeach</tbody>
        </table>
    </article>
</div>
@endsection
