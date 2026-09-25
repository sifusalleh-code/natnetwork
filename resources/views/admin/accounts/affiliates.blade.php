@extends('layouts.admin', ['title' => 'Affiliate'])

@section('content')
<header class="adm-top"><div><h1>Affiliate</h1><p>Akaun affiliate. "Log masuk sebagai" membuka portal affiliate dalam Mod Admin.</p></div></header>
<form method="get" class="adm-form" style="max-width: 28rem; margin-bottom: 1rem;"><input name="q" value="{{ $q }}" placeholder="Cari nama / emel / username"></form>
<article class="card adm-scroll">
    <table class="adm-table">
        <thead><tr><th>Nama</th><th>Emel</th><th>Username</th><th>Profil</th><th>Daftar</th><th></th></tr></thead>
        <tbody>
        @forelse ($affiliates as $a)
            <tr><td>{{ $a->name }}</td><td>{{ $a->email }}</td><td>{{ $a->username ?? '-' }}</td><td>{{ $a->isProfileComplete() ? 'Lengkap' : 'Belum lengkap' }}</td><td>{{ $a->created_at->format('d/m/Y') }}</td><td>@include('admin.partials.impersonate', ['type' => 'affiliate', 'id' => $a->id])</td></tr>
        @empty
            <tr><td colspan="6">Tiada affiliate.</td></tr>
        @endforelse
        </tbody>
    </table>
    {{ $affiliates->links() }}
</article>
@endsection
