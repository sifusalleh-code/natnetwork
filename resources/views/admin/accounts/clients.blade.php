@extends('layouts.admin', ['title' => 'Pelanggan'])

@section('content')
<header class="adm-top"><div><h1>Pelanggan</h1><p>Akaun portal pelanggan. "Log masuk sebagai" membuka portal pelanggan dalam Mod Admin.</p></div></header>
<form method="get" class="adm-form" style="max-width: 28rem; margin-bottom: 1rem;"><input name="q" value="{{ $q }}" placeholder="Cari nama / emel"></form>

<form method="post" action="{{ route('admin.clients.bulk') }}" x-data="{ selected: [] }">
    @csrf
    <article class="card adm-scroll">
        <table class="adm-table">
            <thead><tr>
                <th><input type="checkbox" @change="selected = $event.target.checked ? [{{ $users->pluck('id')->implode(',') }}] : []"></th>
                <th>Nama</th><th>Emel</th><th>Telefon</th><th>Syarikat</th><th>Status</th><th>Daftar</th><th></th>
            </tr></thead>
            <tbody>
            @forelse ($users as $u)
                <tr>
                    <td><input type="checkbox" name="ids[]" value="{{ $u->id }}" x-model.number="selected"></td>
                    <td>{{ $u->name }}</td><td>{{ $u->email }}</td><td>{{ $u->phone ?? '-' }}</td><td>{{ $u->company ?? '-' }}</td>
                    <td><span @class(['adm-badge', 'PAID' => ! $u->isSuspended(), 'FAILED' => $u->isSuspended()])>{{ $u->isSuspended() ? 'Digantung' : 'Aktif' }}</span></td>
                    <td>{{ $u->created_at->format('d/m/Y') }}</td>
                    <td>@include('admin.partials.impersonate', ['type' => 'client', 'id' => $u->id])</td>
                </tr>
            @empty
                <tr><td colspan="8">Tiada pelanggan.</td></tr>
            @endforelse
            </tbody>
        </table>
        {{ $users->links() }}
    </article>
    @include('admin.partials.bulk-toolbar', ['actions' => [
        ['key' => 'suspend', 'label' => 'Gantung', 'needsReason' => true, 'reasonLabel' => 'Sebab gantung akaun (wajib):', 'confirm' => 'Gantung %d akaun terpilih? Mereka tidak boleh log masuk sehingga diaktifkan semula.', 'danger' => true],
        ['key' => 'activate', 'label' => 'Aktifkan', 'needsReason' => false, 'confirm' => 'Aktifkan semula %d akaun terpilih?'],
        ['key' => 'delete', 'label' => 'Padam (jika tiada transaksi)', 'needsReason' => false, 'confirm' => 'Padam %d akaun terpilih? Akaun yang mempunyai sebarang transaksi akan dilangkau secara automatik.', 'danger' => true],
    ]])
</form>
@endsection
