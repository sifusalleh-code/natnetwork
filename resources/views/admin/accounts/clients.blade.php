@extends('layouts.admin', ['title' => 'Pelanggan'])

@section('content')
<header class="adm-top"><div><h1>Pelanggan</h1><p>Akaun portal pelanggan. "Log masuk sebagai" membuka portal pelanggan dalam Mod Admin.</p></div></header>
<form method="get" class="adm-form" style="max-width: 28rem; margin-bottom: 1rem;"><input name="q" value="{{ $q }}" placeholder="Cari nama / emel"></form>
<article class="card adm-scroll">
    <table class="adm-table">
        <thead><tr><th>Nama</th><th>Emel</th><th>Telefon</th><th>Syarikat</th><th>Daftar</th><th></th></tr></thead>
        <tbody>
        @forelse ($users as $u)
            <tr><td>{{ $u->name }}</td><td>{{ $u->email }}</td><td>{{ $u->phone ?? '-' }}</td><td>{{ $u->company ?? '-' }}</td><td>{{ $u->created_at->format('d/m/Y') }}</td><td>@include('admin.partials.impersonate', ['type' => 'client', 'id' => $u->id])</td></tr>
        @empty
            <tr><td colspan="6">Tiada pelanggan.</td></tr>
        @endforelse
        </tbody>
    </table>
    {{ $users->links() }}
</article>
@endsection
