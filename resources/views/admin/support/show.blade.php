@extends('layouts.admin', ['title' => $ticket->number])

@section('content')
<header class="adm-top">
    <div><h1>{{ $ticket->number }} · {{ $ticket->subject }}</h1><p><span class="adm-badge">{{ $ticket->status }}</span> · {{ $ticket->customer?->name }} ({{ $ticket->customer?->email }})@if ($ticket->project) · <a href="{{ route('admin.projects.show', $ticket->project) }}">{{ $ticket->project->number }}</a>@endif</p></div>
    <a class="button button-secondary" href="{{ route('admin.support.index') }}">← Senarai</a>
</header>

<article class="card">
    @foreach ($ticket->messages as $m)
        <div style="padding: .75rem 0; border-bottom: 1px solid rgba(127,127,127,.2);">
            <p class="adm-help">{{ $m->author_type === 'ADMIN' ? 'NatNetwork' : $ticket->customer?->name }} · {{ $m->created_at->format('d/m/Y H:i') }}</p>
            <div style="white-space: pre-line;">{{ $m->body }}</div>
        </div>
    @endforeach

    <form method="post" action="{{ route('admin.support.reply', $ticket) }}" class="adm-form" style="margin-top: 1rem;">
        @csrf
        <label>Balasan<textarea name="body" rows="5" maxlength="5000" required></textarea></label>
        <label class="check"><input type="checkbox" name="close" value="1"> Tutup tiket selepas membalas</label>
        <button class="button" type="submit">Hantar balasan</button>
    </form>
</article>
@endsection
