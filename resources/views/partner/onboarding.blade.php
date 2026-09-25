@extends('layouts.client', ['title' => 'Program Partnership | NatNetwork Synergy', 'wide' => true])

@section('content')
<section class="auth-card">
    <p class="eyebrow">Program Partnership</p>
    @include('partner.partials.steps', ['current' => $step])

    @if (session('status'))<p class="notice">{{ session('status') }}</p>@endif
    @if ($errors->any())<div class="prt-errors">@foreach ($errors->all() as $error)<div>{{ $error }}</div>@endforeach</div>@endif

    @if ($step === 3)
        <h1>Bayaran modal</h1>
        @if ($pending)
            <p class="lead">Selesaikan bayaran untuk mengaktifkan penyertaan anda. Status dikemas kini secara automatik selepas bayaran disahkan.</p>
            <dl class="nn-summary">
                <div><dt>No. penyertaan</dt><dd>{{ $pending->number }}</dd></div>
                <div><dt>Invois</dt><dd>{{ $pending->invoice?->number }}</dd></div>
                <div class="nn-total"><dt>Jumlah</dt><dd>RM {{ number_format((float) $pending->amount, 2) }}</dd></div>
            </dl>
            @if ($pending->invoice && in_array($pending->invoice->status, ['ISSUED', 'PARTIALLY_PAID'], true))
                <form method="post" action="{{ route('partner.pay', $pending->invoice) }}">@csrf<button class="button" type="submit" style="width: 100%;">Bayar RM {{ number_format($pending->invoice->outstandingCents() / 100, 2) }} melalui Billplz</button></form>
            @endif
            <form method="post" action="{{ route('partner.capital.cancel', $pending) }}" style="margin-top: .5rem;">@csrf<button class="button button-secondary button-compact" type="submit">Tukar amaun</button></form>
        @else
            <p class="lead">Masukkan amaun modal untuk menjana invois bayaran.</p>
            <form method="post" action="{{ route('partner.capital.store') }}" class="form-stack">
                @csrf
                <input type="hidden" name="agree" value="1">
                <label>Amaun modal (RM) <small>Minimum RM{{ number_format((float) $settings->min_capital, 2) }}</small>
                    <input name="amount" type="number" step="0.01" min="{{ (float) $settings->min_capital }}" value="{{ old('amount', (float) $settings->min_capital) }}" required>
                </label>
                <button class="button" type="submit">Jana invois bayaran</button>
            </form>
        @endif
    @else
        <h1>Lengkapkan profil</h1>
        <p class="lead">Bayaran anda telah diterima ✓. Lengkapkan maklumat pengenalan dan akaun bank untuk membuka Dashboard. Pulangan akan dipindahkan ke akaun ini.</p>
        <form method="post" action="{{ route('partner.profile.complete') }}" class="form-stack">
            @csrf
            <div class="form-grid-2">
                <label>Nama<input value="{{ $partner->name }}" readonly></label>
                <label>No. Telefon<input name="phone" value="{{ old('phone', $partner->phone) }}" required maxlength="50"></label>
            </div>
            @if ($partner->id_type === 'COMPANY')
                <label>Nama syarikat<input name="company_name" value="{{ old('company_name', $partner->company_name) }}" required maxlength="255"></label>
            @endif
            <label>{{ $partner->id_type === 'COMPANY' ? 'No. pendaftaran syarikat' : 'No. IC' }}<input name="id_number" value="{{ old('id_number') }}" required maxlength="30"></label>
            @error('id_number')<p class="form-error">{{ $message }}</p>@enderror
            <label>Bank<select name="bank_name" required><option value="">Pilih bank</option>@foreach ($banks as $b)<option @selected(old('bank_name') === $b)>{{ $b }}</option>@endforeach</select></label>
            <div class="form-grid-2">
                <label>Nama pemegang akaun<input name="bank_account_holder" value="{{ old('bank_account_holder', $partner->company_name ?: $partner->name) }}" required maxlength="120"></label>
                <label>No. akaun bank<input name="bank_account_number" inputmode="numeric" value="{{ old('bank_account_number') }}" required maxlength="30"></label>
            </div>
            @error('bank_account_number')<p class="form-error">{{ $message }}</p>@enderror
            <button class="button" type="submit">Simpan &amp; buka Dashboard</button>
        </form>
    @endif

    <form method="post" action="{{ route('partner.logout') }}" class="top-gap">@csrf<button class="button button-secondary button-compact" type="submit">Log keluar</button></form>
</section>
@endsection
