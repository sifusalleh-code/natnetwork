@extends('layouts.admin', ['title' => 'Projects'])

@section('content')
<header class="adm-top"><div><h1>Projects</h1><p>Projek dicipta automatik selepas order disahkan. Mula hanya melalui START PROJECT.</p></div><a class="button button-secondary" href="{{ route('admin.projects.queue') }}">Development Queue</a></header>
<nav class="adm-tabs">
    <a @class(['is-active' => ! $status]) href="{{ route('admin.projects.index') }}">Semua</a>
    @foreach (['WAITING_TO_START', 'IN_PROGRESS', 'WAITING_FOR_CLIENT', 'CLIENT_REVIEW', 'READY_FOR_HANDOVER', 'COMPLETED', 'ON_HOLD'] as $s)
        <a @class(['is-active' => $status === $s]) href="{{ route('admin.projects.index', ['status' => $s]) }}">{{ str_replace('_', ' ', $s) }}</a>
    @endforeach
</nav>
<article class="card adm-scroll">
    <table class="adm-table">
        <thead><tr><th>No.</th><th>Projek</th><th>Pelanggan</th><th>Status</th><th>Progress</th><th>Mula (rancang)</th></tr></thead>
        <tbody>
        @forelse ($projects as $p)
            <tr>
                <td><a href="{{ route('admin.projects.show', $p) }}">{{ $p->number }}</a></td>
                <td>{{ $p->name }}<br><span class="adm-help">{{ $p->template }}</span></td>
                <td>{{ $p->customer?->name }}<br><span class="adm-help">{{ $p->customer?->email }}</span></td>
                <td><span class="adm-badge">{{ str_replace('_', ' ', $p->status) }}</span></td>
                <td>{{ $p->progress }}%</td>
                <td>{{ $p->planned_start_date?->format('d/m/Y') ?? '-' }}</td>
            </tr>
        @empty
            <tr><td colspan="6">Tiada projek.</td></tr>
        @endforelse
        </tbody>
    </table>
    {{ $projects->links() }}
</article>
@endsection
