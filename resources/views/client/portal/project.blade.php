@extends('layouts.portal', ['title' => $project->number, 'active' => 'projects'])

@section('content')
<header class="prt-top">
    <div><h1>{{ $project->name }}</h1><p>{{ $project->number }} · <span class="prt-badge">{{ $project->clientLabel() }}</span></p></div>
    <a class="button button-secondary" href="{{ route('client.projects') }}">← Projects</a>
</header>

<article class="card">
    <h2>Status</h2>
    <p>{{ $project->clientExplanation() }}</p>
    @if ($project->status_note && in_array($project->status, ['WAITING_FOR_CLIENT', 'ON_HOLD', 'CANCELLED'], true))<p class="prt-empty">Nota: {{ $project->status_note }}</p>@endif
    @include('client.portal.partials.progress', ['value' => $project->progress])
    <dl class="prt-dl" style="margin-top: 1rem;">
        <dt>Mula dirancang</dt><dd>{{ $project->planned_start_date?->format('d/m/Y') ?? '-' }}</dd>
        <dt>Dimulakan</dt><dd>{{ $project->started_at?->format('d/m/Y') ?? 'Belum' }}</dd>
        @if ($project->completed_at)<dt>Sokongan percuma</dt><dd>Sehingga {{ $project->supportEndsAt()->format('d/m/Y') }} ({{ $project->support_days }} hari)</dd>@endif
    </dl>
</article>

<article class="card">
    <h2>Milestone</h2>
    @foreach ($project->milestones as $m)
        <div class="prt-action"><div><b>{{ $m->completed_at ? '✓' : '○' }} {{ $m->client_label }}</b><span>{{ $m->completed_at ? 'Selesai '.$m->completed_at->format('d/m/Y') : 'Belum selesai' }}</span></div></div>
    @endforeach
</article>

<article class="card" id="bahan">
    <h2>Bahan & maklumat diperlukan</h2>
    <p class="prt-empty">Setiap bahan disemak oleh pasukan kami. Item bertanda <b>wajib</b> diperlukan sebelum kerja berkaitan diteruskan. Fail dibenarkan: {{ implode(', ', config('project_templates.upload_extensions')) }} (maks {{ (int) (config('project_templates.upload_max_kb') / 1024) }}MB).</p>
    @forelse ($project->contentItems as $item)
        @php([$label, $tone] = \App\Engines\ProjectContent\Models\ProjectContentItem::LABELS[$item->status] ?? [$item->status, ''])
        <div class="prt-action" style="align-items: flex-start;">
            <div style="flex: 1; min-width: 0;">
                <b>{{ $item->title }}</b>
                <span><span @class(['prt-badge', $tone])>{{ $label }}</span>@if ($item->blocking) <span class="prt-badge">Wajib</span>@endif @if ($item->help_requested) <span class="prt-badge warn">Bantuan diminta</span>@endif</span>
                @if ($item->description)<p class="prt-empty" style="margin: .3rem 0 0;">{{ $item->description }}</p>@endif
                @if ($item->status === 'NEEDS_UPDATE' && $item->admin_note)<p class="prt-errors" style="margin: .5rem 0 0;">{{ $item->admin_note }}</p>@endif
                @if ($item->info_text)<p style="white-space: pre-line; margin: .4rem 0 0;">{{ $item->info_text }}</p>@endif
                @foreach ($item->files as $f)<div><a href="{{ route('client.files.download', $f) }}">{{ $f->original_name }}</a> <span class="prt-empty">v{{ $f->version }}</span></div>@endforeach

                @if (! $project->isClosed() && in_array($item->status, ['REQUESTED', 'NEEDS_UPDATE', 'SUBMITTED'], true))
                    <details style="margin-top: .5rem;" @if ($item->needsClientAction()) open @endif>
                        <summary>{{ $item->status === 'SUBMITTED' ? 'Hantar semula' : 'Hantar' }}</summary>
                        @if ($item->kind === 'INFO')
                            <form method="post" action="{{ route('client.content.info', $item) }}" class="prt-form" style="margin-top: .5rem;">
                                @csrf
                                <textarea name="info_text" rows="4" maxlength="5000" required style="width: 100%; padding: .55rem .75rem; border: 1px solid #aacde7; border-radius: .6rem; font: inherit;">{{ old('info_text', $item->info_text) }}</textarea>
                                <button class="button" type="submit">Hantar maklumat</button>
                            </form>
                        @else
                            <form method="post" action="{{ route('client.content.file', $item) }}" enctype="multipart/form-data" class="prt-form" style="margin-top: .5rem;">
                                @csrf
                                <input type="file" name="file" required>
                                <button class="button" type="submit">Muat naik</button>
                            </form>
                        @endif
                        @unless ($item->help_requested)
                            <form method="post" action="{{ route('client.content.help', $item) }}" style="margin-top: .5rem;">@csrf<button class="button button-secondary button-compact" type="submit">Saya perlukan bantuan</button></form>
                        @endunless
                    </details>
                @endif
            </div>
        </div>
    @empty
        <p class="prt-empty">Tiada bahan diminta.</p>
    @endforelse
</article>

<article class="card" id="perubahan">
    <h2>Permintaan perubahan</h2>
    <p class="prt-empty">Skop projek ikut quotation yang diterima. Perubahan dinilai oleh pasukan kami sebagai revision dalam skop (tiada caj) atau kerja tambahan (sebut harga berasingan untuk kelulusan anda).</p>
    @foreach ($changes as $cr)
        <div class="prt-action" style="align-items: flex-start;">
            <div style="flex: 1; min-width: 0;">
                <b>{{ $cr->number }} · {{ $cr->title }}</b>
                <span><span @class(['prt-badge', $cr->tone()])>{{ $cr->label() }}</span> · {{ $cr->created_at->format('d/m/Y') }}</span>
                <p style="white-space: pre-line; margin: .3rem 0 0;">{{ $cr->description }}</p>
                @if ($cr->admin_note)<p class="prt-empty" style="margin: .3rem 0 0;">Nota kami: {{ $cr->admin_note }}</p>@endif
                @if ($cr->amount)<p style="margin: .3rem 0 0;"><b>Kerja tambahan: RM {{ number_format((float) $cr->amount, 2) }}</b>@if ($cr->extra_weeks) · tambahan tempoh {{ $cr->extra_weeks }} minggu @endif</p>@endif
                @if ($cr->invoice)<p style="margin: .3rem 0 0;"><a href="{{ route('client.billing.invoice', $cr->invoice) }}">Invois {{ $cr->invoice->number }}</a> · {{ $cr->invoice->status }}</p>@endif
                @if ($cr->status === 'QUOTED' && ! $project->isClosed())
                    <form method="post" action="{{ route('client.changes.decide', $cr) }}" class="prt-form" style="margin-top: .5rem;">
                        @csrf
                        <label class="prt-check"><input type="checkbox" name="agree" value="1"> Saya bersetuju dengan harga dan tempoh kerja tambahan ini. Invois 100% akan dikeluarkan.</label>
                        <div style="display: flex; gap: .5rem; flex-wrap: wrap;"><button class="button" name="decision" value="approve">Luluskan</button><button class="button button-secondary" name="decision" value="reject">Tolak</button></div>
                    </form>
                @endif
            </div>
        </div>
    @endforeach
    @unless ($project->isClosed())
        <details style="margin-top: .75rem;">
            <summary>Mohon perubahan baharu</summary>
            <form method="post" action="{{ route('client.projects.changes.store', $project) }}" class="prt-form" style="margin-top: .5rem;">
                @csrf
                <label>Tajuk<input name="title" maxlength="200" value="{{ old('title') }}" required></label>
                <label>Penerangan perubahan<textarea name="description" rows="4" maxlength="5000" required style="padding: .55rem .75rem; border: 1px solid #aacde7; border-radius: .6rem; font: inherit;">{{ old('description') }}</textarea></label>
                <button class="button" type="submit">Hantar permintaan</button>
            </form>
        </details>
    @endunless
</article>

<article class="card">
    <h2>Fail & deliverable daripada kami</h2>
    @forelse ($deliverables as $f)
        <div class="prt-action"><div><b>{{ $f->original_name }}</b><span>{{ $f->is_deliverable ? 'Deliverable' : 'Fail' }} · {{ $f->created_at->format('d/m/Y') }} · {{ number_format($f->size / 1024, 0) }} KB</span></div><a class="button button-secondary" href="{{ route('client.files.download', $f) }}">Muat turun</a></div>
    @empty
        <p class="prt-empty">Belum ada fail daripada kami.</p>
    @endforelse
</article>
@endsection
