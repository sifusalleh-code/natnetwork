@extends('layouts.admin', ['title' => 'Change Requests'])

@section('content')
<header class="adm-top"><div><h1>Change Requests</h1><p>Permintaan perubahan pelanggan selepas quotation diterima. Nilai di halaman projek.</p></div></header>
<nav class="adm-tabs">
    @foreach (['SUBMITTED' => 'Perlu dinilai', 'QUOTED' => 'Menunggu pelanggan', 'APPROVED' => 'Diluluskan', 'IN_SCOPE' => 'Dalam skop', 'ALL' => 'Semua'] as $s => $label)
        <a @class(['is-active' => $status === $s]) href="{{ route('admin.change-requests.index', ['status' => $s]) }}">{{ $label }}</a>
    @endforeach
</nav>
<article class="card adm-scroll">
    <table class="adm-table">
        <thead><tr><th>No.</th><th>Tajuk</th><th>Pelanggan</th><th>Projek</th><th>Status</th><th>Harga (RM)</th><th>Tarikh</th></tr></thead>
        <tbody>
        @forelse ($changes as $cr)
            <tr>
                <td><a href="{{ route('admin.projects.show', $cr->project_id) }}#perubahan">{{ $cr->number }}</a></td>
                <td>{{ $cr->title }}</td>
                <td>{{ $cr->customer?->name }}</td>
                <td>{{ $cr->project?->number }}</td>
                <td><span class="adm-badge">{{ $cr->status }}</span></td>
                <td>{{ $cr->amount ? number_format((float) $cr->amount, 2) : '-' }}</td>
                <td>{{ $cr->created_at->format('d/m/Y') }}</td>
            </tr>
        @empty
            <tr><td colspan="7">Tiada permintaan.</td></tr>
        @endforelse
        </tbody>
    </table>
    {{ $changes->links() }}
</article>
@endsection
