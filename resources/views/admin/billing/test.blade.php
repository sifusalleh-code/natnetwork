@extends('layouts.admin', ['title' => 'Uji Aliran Bayaran'])

@section('content')
<header class="adm-top"><div><h1>Uji Aliran Bayaran</h1><p>Cipta invois ujian TEST-* dan bayar melalui Billplz Sandbox.</p></div></header>

@if (! $settings->isSandbox())
    <p class="adm-danger">Mod aktif ialah PRODUCTION. Tukar ke SANDBOX di <a href="{{ route('admin.billing.settings') }}">Tetapan Billplz</a> untuk membuat ujian.</p>
@elseif (! $settings->hasCredentials('SANDBOX'))
    <p class="adm-warn">Kunci sandbox belum lengkap. Isi di <a href="{{ route('admin.billing.settings') }}">Tetapan Billplz</a>.</p>
@else
    <article class="card" style="max-width: 36rem;">
        <form method="post" action="{{ route('admin.billing.test.start') }}" class="adm-form">
            @csrf
            <label>Nama pembayar<input name="name" value="{{ old('name', auth('admin')->user()->name) }}" required maxlength="120"></label>
            <label>Emel pembayar<input name="email" type="email" value="{{ old('email', auth('admin')->user()->email) }}" required></label>
            <label>Jenis invois
                <select name="type">@foreach (\App\Engines\Billing\Models\Invoice::TYPES as $type)<option value="{{ $type }}" @selected(old('type', 'DEPOSIT') === $type)>{{ $type }}</option>@endforeach</select>
            </label>
            <label>Jumlah (RM)<input name="amount" type="number" step="0.01" min="1" max="100000" value="{{ old('amount', '10.00') }}" required></label>
            <p class="adm-help">Anda akan dibawa ke halaman bayaran Billplz Sandbox. Selepas bayaran, status invois dikemas kini hanya apabila callback bertandatangan diterima di pelayan.</p>
            <button class="button" type="submit">Cipta invois ujian & bayar</button>
        </form>
    </article>
@endif
@endsection
