@extends('layouts.portal', ['title' => 'Files', 'active' => 'files'])

@section('content')
<header class="prt-top"><div><h1>Files</h1><p>Bahan yang diminta, fail yang anda hantar dan deliverable projek.</p></div></header>

<article class="card">
    <h2>Bahan & maklumat</h2>
    @forelse ($items as $item)
        @php([$label, $tone] = \App\Engines\ProjectContent\Models\ProjectContentItem::LABELS[$item->status] ?? [$item->status, ''])
        <div class="prt-action">
            <div><b>{{ $item->title }}</b><span>{{ $item->project->number }} · <span @class(['prt-badge', $tone])>{{ $label }}</span>@if ($item->blocking) <span class="prt-badge">Wajib</span>@endif</span></div>
            <a @class(['button', 'button-secondary' => ! $item->needsClientAction()]) href="{{ route('client.projects.show', $item->project) }}#bahan">{{ $item->needsClientAction() ? 'Hantar' : 'Lihat' }}</a>
        </div>
    @empty
        <p class="prt-empty">Belum ada bahan diminta.</p>
    @endforelse
</article>

<article class="card prt-scroll">
    <h2>Semua fail</h2>
    <table class="prt-table">
        <thead><tr><th>Fail</th><th>Projek</th><th>Daripada</th><th>Tarikh</th><th></th></tr></thead>
        <tbody>
        @forelse ($files as $f)
            <tr>
                <td>{{ $f->original_name }}@if ($f->is_deliverable) <span class="prt-badge ok">Deliverable</span>@endif @if ($f->item)<br><span class="prt-empty">{{ $f->item->title }} · v{{ $f->version }}</span>@endif</td>
                <td>{{ $f->project->number }}</td>
                <td>{{ $f->uploaded_by === 'CLIENT' ? 'Anda' : 'NatNetwork' }}</td>
                <td>{{ $f->created_at->format('d/m/Y') }}</td>
                <td><a href="{{ route('client.files.download', $f) }}">Muat turun</a></td>
            </tr>
        @empty
            <tr><td colspan="5" class="prt-empty">Belum ada fail.</td></tr>
        @endforelse
        </tbody>
    </table>
</article>
@endsection
