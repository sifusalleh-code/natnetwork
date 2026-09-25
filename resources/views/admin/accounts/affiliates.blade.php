@extends('layouts.admin', ['title' => 'Affiliate'])

@section('content')
<header class="adm-top"><div><h1>Affiliate</h1><p>Akaun affiliate. "Log masuk sebagai" membuka portal affiliate dalam Mod Admin.</p></div></header>
<form method="get" class="adm-form" style="max-width: 28rem; margin-bottom: 1rem;"><input name="q" value="{{ $q }}" placeholder="Cari nama / emel / username"></form>

<form method="post" action="{{ route('admin.affiliates.bulk') }}" x-data="{ selected: [] }">
    @csrf
    <article class="card adm-scroll">
        <table class="adm-table">
            <thead><tr>
                <th><input type="checkbox" @change="selected = $event.target.checked ? [{{ $affiliates->pluck('id')->implode(',') }}] : []"></th>
                <th>Nama</th><th>Emel</th><th>Username</th><th>Profil</th><th>Status</th><th>Daftar</th><th></th>
            </tr></thead>
            <tbody>
            @forelse ($affiliates as $a)
                <tr>
                    <td><input type="checkbox" name="ids[]" value="{{ $a->id }}" x-model.number="selected"></td>
                    <td>{{ $a->name }}</td><td>{{ $a->email }}</td><td>{{ $a->username ?? '-' }}</td>
                    <td>{{ $a->isProfileComplete() ? 'Lengkap' : 'Belum lengkap' }}</td>
                    <td><span @class(['adm-badge', 'PAID' => ! $a->isSuspended(), 'FAILED' => $a->isSuspended()])>{{ $a->isSuspended() ? 'Digantung' : 'Aktif' }}</span></td>
                    <td>{{ $a->created_at->format('d/m/Y') }}</td>
                    <td>@include('admin.partials.impersonate', ['type' => 'affiliate', 'id' => $a->id])</td>
                </tr>
            @empty
                <tr><td colspan="8">Tiada affiliate.</td></tr>
            @endforelse
            </tbody>
        </table>
        {{ $affiliates->links() }}
    </article>
    @include('admin.partials.bulk-toolbar', ['actions' => [
        ['key' => 'suspend', 'label' => 'Gantung', 'needsReason' => true, 'reasonLabel' => 'Sebab gantung akaun (wajib):', 'confirm' => 'Gantung %d akaun terpilih? Mereka tidak boleh log masuk sehingga diaktifkan semula.', 'danger' => true],
        ['key' => 'activate', 'label' => 'Aktifkan', 'needsReason' => false, 'confirm' => 'Aktifkan semula %d akaun terpilih?'],
        ['key' => 'delete', 'label' => 'Padam (jika tiada transaksi)', 'needsReason' => false, 'confirm' => 'Padam %d akaun terpilih? Akaun yang mempunyai sebarang transaksi/komisen akan dilangkau secara automatik.', 'danger' => true],
    ]])
</form>
@endsection
