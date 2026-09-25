@extends('layouts.partner', ['title' => 'Notifications', 'active' => 'notifications'])

@section('content')
<header class="prt-top">
    <div><h1>Notifications</h1><p>Bayaran, kemajuan projek, fail dan sokongan.</p></div>
    @if ($notifications->contains(fn ($n) => ! $n->read_at))
        <form method="post" action="{{ route('partner.notifications.read-all') }}">@csrf<button class="button button-secondary" type="submit">Tanda semua dibaca</button></form>
    @endif
</header>

<article class="card">
    @forelse ($notifications as $n)
        <div class="prt-action">
            <div><b>{{ $n->read_at ? '' : '● ' }}{{ $n->title }}</b><span>{{ $n->body }}{{ $n->body ? ' · ' : '' }}{{ $n->created_at->format('d/m/Y H:i') }}</span></div>
            <a @class(['button', 'button-secondary' => (bool) $n->read_at]) href="{{ route('partner.notifications.open', $n) }}">Buka</a>
        </div>
    @empty
        <p class="prt-empty">Tiada notifikasi.</p>
    @endforelse
    {{ $notifications->links() }}
</article>
@endsection
