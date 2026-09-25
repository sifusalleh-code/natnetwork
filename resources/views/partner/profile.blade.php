@extends('layouts.partner', ['title' => 'Profil', 'active' => 'profile'])

@section('content')
<header class="prt-top"><div><h1>Profil</h1><p><span @class(['prt-badge', $partner->tone()])>{{ $partner->label() }}</span></p></div></header>
<article class="card">
    <dl class="prt-dl">
        <dt>Nama</dt><dd>{{ $partner->name }}</dd>
        <dt>Emel</dt><dd>{{ $partner->email }}</dd>
        <dt>{{ $partner->id_type === 'COMPANY' ? 'Syarikat' : 'No. IC' }}</dt><dd>{{ $partner->company_name ? $partner->company_name.' · ' : '' }}••••{{ $partner->id_last4 }}</dd>
    </dl>
    <p class="prt-empty">Untuk menukar nama, emel atau No. IC/pendaftaran, sila hubungi kami.</p>
</article>
<article class="card">
    <h2>Telefon & akaun bank</h2>
    <form method="post" action="{{ route('partner.profile.update') }}" class="prt-form">
        @csrf @method('PUT')
        <label>No. Telefon<input name="phone" value="{{ old('phone', $partner->phone) }}" required maxlength="50"></label>
        <label>Bank<select name="bank_name" required style="min-height: 2.7rem; padding: .55rem .75rem; border: 1px solid #aacde7; border-radius: .6rem; font: inherit;">@foreach ($banks as $b)<option @selected(old('bank_name', $partner->bank_name) === $b)>{{ $b }}</option>@endforeach</select></label>
        <label>Nama pemegang akaun<input name="bank_account_holder" value="{{ old('bank_account_holder', $partner->bank_account_holder) }}" required maxlength="120"></label>
        <label>No. akaun baharu (kosongkan untuk kekal ••••{{ $partner->bank_account_last4 }})<input name="bank_account_number" inputmode="numeric" maxlength="30"></label>
        <button class="button" type="submit">Simpan</button>
    </form>
</article>
@endsection
