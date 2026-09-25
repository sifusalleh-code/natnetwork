@extends('layouts.admin', ['title' => 'Support'])

@section('content')
<header class="adm-top"><div><h1>Support</h1><p>Tiket sokongan daripada pelanggan.</p></div></header>
<nav class="adm-tabs">
    @foreach (['OPEN' => 'Dibuka', 'ANSWERED' => 'Dibalas', 'CLOSED' => 'Ditutup', 'ALL' => 'Semua'] as $s => $label)
        <a @class(['is-active' => $status === $s]) href="{{ route('admin.support.index', ['status' => $s]) }}">{{ $label }}</a>
    @endforeach
</nav>
<article class="card adm-scroll">
    <table class="adm-table">
        <thead><tr><th>No.</th><th>Subjek</th><th>Pelanggan</th><th>Projek</th><th>Status</th><th>Aktiviti terakhir</th></tr></thead>
        <tbody>
        @forelse ($tickets as $t)
            <tr>
                <td><a href="{{ route('admin.support.show', $t) }}">{{ $t->number }}</a></td>
                <td>{{ $t->subject }}</td>
                <td>{{ $t->customer?->name }}<br><span class="adm-help">{{ $t->customer?->email }}</span></td>
                <td>{{ $t->project?->number ?? '-' }}</td>
                <td><span class="adm-badge">{{ $t->status }}</span></td>
                <td>{{ $t->last_activity_at?->format('d/m/Y H:i') }}</td>
            </tr>
        @empty
            <tr><td colspan="6">Tiada tiket.</td></tr>
        @endforelse
        </tbody>
    </table>
    {{ $tickets->links() }}
</article>
@endsection
