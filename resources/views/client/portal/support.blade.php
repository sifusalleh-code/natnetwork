@extends('layouts.portal', ['title' => 'Support', 'active' => 'support'])

@section('content')
<header class="prt-top"><div><h1>Support</h1><p>Hak sokongan dan permintaan sokongan anda.</p></div></header>

<article class="card">
    <h2>Hak sokongan</h2>
    @php($completed = $projects->where('status', 'COMPLETED'))
    @forelse ($completed as $p)
        <div class="prt-action"><div><b>{{ $p->name }} ({{ $p->number }})</b><span>
            @if ($p->supportEndsAt()->isFuture()) Sokongan percuma aktif sehingga {{ $p->supportEndsAt()->format('d/m/Y') }} @else Sokongan percuma tamat pada {{ $p->supportEndsAt()->format('d/m/Y') }} @endif
        </span></div></div>
    @empty
        <p class="prt-empty">Tempoh sokongan percuma bermula selepas projek selesai diserahkan.</p>
    @endforelse
</article>

<article class="card">
    <h2>Buka tiket baharu</h2>
    <form method="post" action="{{ route('client.support.store') }}" class="prt-form">
        @csrf
        <label>Subjek<input name="subject" maxlength="200" value="{{ old('subject') }}" required></label>
        @if ($projects->isNotEmpty())
            <label>Projek berkaitan
                <select name="project_id" style="min-height: 2.7rem; padding: .55rem .75rem; border: 1px solid #aacde7; border-radius: .6rem; font: inherit;">
                    <option value="">Umum</option>
                    @foreach ($projects as $p)<option value="{{ $p->id }}" @selected(old('project_id') == $p->id)>{{ $p->number }} · {{ $p->name }}</option>@endforeach
                </select>
            </label>
        @endif
        <label>Penerangan<textarea name="body" rows="5" maxlength="5000" required style="padding: .55rem .75rem; border: 1px solid #aacde7; border-radius: .6rem; font: inherit;">{{ old('body') }}</textarea></label>
        <button class="button" type="submit">Hantar</button>
    </form>
</article>

<article class="card">
    <h2>Tiket anda</h2>
    @forelse ($tickets as $t)
        @php([$label, $tone] = \App\Engines\Communication\Models\SupportTicket::LABELS[$t->status] ?? [$t->status, ''])
        <div class="prt-action">
            <div><b>{{ $t->subject }}</b><span>{{ $t->number }}@if ($t->project) · {{ $t->project->number }}@endif · <span @class(['prt-badge', $tone])>{{ $label }}</span> · {{ $t->last_activity_at?->format('d/m/Y H:i') }}</span></div>
            <a class="button button-secondary" href="{{ route('client.support.show', $t) }}">Buka</a>
        </div>
    @empty
        <p class="prt-empty">Tiada permintaan sokongan.</p>
    @endforelse
</article>
@endsection
