@extends('layouts.portal', ['title' => $ticket->number, 'active' => 'support'])

@section('content')
@php([$label, $tone] = \App\Engines\Communication\Models\SupportTicket::LABELS[$ticket->status] ?? [$ticket->status, ''])
<header class="prt-top">
    <div><h1>{{ $ticket->subject }}</h1><p>{{ $ticket->number }}@if ($ticket->project) · {{ $ticket->project->number }}@endif · <span @class(['prt-badge', $tone])>{{ $label }}</span></p></div>
    <a class="button button-secondary" href="{{ route('client.support') }}">← Support</a>
</header>

<article class="card">
    @foreach ($ticket->messages as $m)
        <div class="prt-action" style="align-items: flex-start;">
            <div><span>{{ $m->author_type === 'ADMIN' ? 'NatNetwork' : 'Anda' }} · {{ $m->created_at->format('d/m/Y H:i') }}</span><p style="white-space: pre-line; margin: .3rem 0 0;">{{ $m->body }}</p></div>
        </div>
    @endforeach
</article>

@if ($ticket->status !== 'CLOSED')
    <article class="card">
        <form method="post" action="{{ route('client.support.reply', $ticket) }}" class="prt-form">
            @csrf
            <label>Balas<textarea name="body" rows="4" maxlength="5000" required style="padding: .55rem .75rem; border: 1px solid #aacde7; border-radius: .6rem; font: inherit;"></textarea></label>
            <button class="button" type="submit">Hantar balasan</button>
        </form>
    </article>
@else
    <p class="prt-empty">Tiket ini telah ditutup. Buka tiket baharu jika anda memerlukan bantuan lanjut.</p>
@endif
@endsection
