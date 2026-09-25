@extends('layouts.affiliate', ['title' => 'Profil Saya', 'active' => 'profile'])

@push('styles')
<style>
    .aff-profile { display: grid; grid-template-columns: minmax(15rem, .72fr) minmax(0, 1.45fr); gap: 1.25rem; align-items: start; }
    .aff-profile .card { margin: 0; }
    .aff-summary { text-align: center; }
    .aff-avatar { display: grid; place-items: center; width: 9.75rem; height: 9.75rem; margin: 0 auto .9rem; overflow: hidden; border: 4px solid var(--soft-strong); border-radius: 50%; color: #fff; background: linear-gradient(135deg, var(--gold), var(--gold-strong)); font-size: 3rem; font-weight: 850; }
    .aff-avatar img { width: 100%; height: 100%; object-fit: cover; }
    .aff-warning { margin: 0 0 .75rem; padding: .6rem .7rem; color: #795000; background: #fff5d9; border-radius: .6rem; font-size: .8rem; text-align: left; }
    .aff-warning b, .aff-warning span { display: block; }
    .aff-photo-actions { display: grid; gap: .5rem; margin-bottom: 1rem; }
    .aff-photo-actions label { margin: 0; text-align: center; }
    .aff-summary h2 { margin: 0; font-size: 1.1rem; }
    .aff-meta { margin: .25rem 0 0; color: var(--muted); font-size: .8rem; }
    .aff-heading { display: flex; justify-content: space-between; gap: .6rem; margin: 0 0 1rem; color: var(--gold-strong); font-size: 1.05rem; }
    .aff-heading small { color: var(--muted); font-size: .72rem; font-weight: 600; }
    .aff-grid { display: grid; grid-template-columns: 1fr 1fr; gap: .9rem; }
    .aff-grid label, .aff-social label { display: grid; gap: .35rem; color: var(--ink); font-size: .8rem; font-weight: 800; }
    .aff-grid input, .aff-grid select, .aff-social input { width: 100%; min-height: 2.75rem; padding: .6rem .75rem; color: var(--ink); background: #fff; border: 1px solid #aacde7; border-radius: .6rem; font: inherit; }
    .aff-grid input[readonly] { color: var(--muted); background: var(--soft); }
    .aff-full { grid-column: 1 / -1; }
    .aff-help { color: var(--muted); font-size: .72rem; font-weight: 500; }
    .aff-bank-notice { grid-column: 1 / -1; margin: 0; padding: .65rem .75rem; color: #795000; background: #fff5d9; border-radius: .6rem; font-size: .82rem; }
    .aff-social { display: grid; grid-template-columns: repeat(3, 1fr); gap: .75rem; margin-top: 1.1rem; padding-top: 1.1rem; border-top: 1px solid var(--line); }
    .aff-actions { display: flex; justify-content: flex-end; gap: .6rem; margin-top: 1.3rem; }
    @media (max-width: 56rem) { .aff-social { grid-template-columns: 1fr 1fr; } }
    @media (max-width: 44rem) { .aff-profile, .aff-grid, .aff-social { grid-template-columns: 1fr; } .aff-actions { display: grid; } .aff-actions .button { width: 100%; text-align: center; } }
</style>
@endpush

@section('content')
<header class="aff-top">
    <div><h1>Profil Saya</h1><p>Kemaskini maklumat peribadi anda dengan lengkap dan tepat.</p></div>
    @if ($affiliate->isProfileComplete())<a class="button button-secondary" href="{{ route('affiliate.dashboard') }}">← Dashboard</a>@endif
</header>

@if ($missing)
    <p class="notice">Profil belum lengkap. Sila lengkapkan: {{ implode(', ', $missing) }}. Dashboard dan menu affiliate dibuka selepas profil lengkap.</p>
@endif

<div class="aff-profile">
    <article class="card aff-summary">
        <div class="aff-avatar">
            @if ($affiliate->avatar_path)
                <img src="{{ route('affiliate.profile.avatar') }}?v={{ $affiliate->updated_at?->timestamp }}" alt="Gambar profil">
            @else
                {{ mb_strtoupper(mb_substr($affiliate->name, 0, 1)) }}
            @endif
        </div>
        <p class="aff-warning"><b>⚠ Wajib gambar diri sebenar</b><span>{{ $affiliate->avatar_path ? 'Sila gunakan gambar wajah anda yang jelas.' : 'Gambar diri sebenar diperlukan. Tambah gambar profil.' }}</span></p>
        <div class="aff-photo-actions">
            <form method="post" action="{{ route('affiliate.profile.avatar.update') }}" enctype="multipart/form-data">
                @csrf
                <label class="button">▣ Ambil Gambar<input type="file" name="avatar" accept="image/png,image/jpeg,image/webp" capture="user" hidden onchange="this.form.submit()"></label>
            </form>
            <form method="post" action="{{ route('affiliate.profile.avatar.update') }}" enctype="multipart/form-data">
                @csrf
                <label class="button button-secondary">▧ Pilih dari Galeri<input type="file" name="avatar" accept="image/png,image/jpeg,image/webp" hidden onchange="this.form.submit()"></label>
            </form>
            @error('avatar')<p class="form-error">{{ $message }}</p>@enderror
        </div>
        <h2>{{ $affiliate->name }}</h2>
        <p class="aff-meta">Affiliate sejak {{ $affiliate->created_at?->translatedFormat('d M Y') }}</p>
    </article>

    <article class="card">
        <h2 class="aff-heading">◉ Maklumat Akaun <small>Semua medan wajib kecuali media sosial</small></h2>
        <form method="post" action="{{ route('affiliate.profile.update') }}">
            @csrf
            @method('PUT')
            <div class="aff-grid">
                <label>Username
                    @if ($affiliate->username)
                        <input value="{{ $affiliate->username }}" readonly>
                        <span class="aff-help">Username link affiliate telah dikunci.</span>
                    @else
                        <input name="username" value="{{ old('username') }}" required maxlength="12" pattern="[a-z]{3,12}" autocapitalize="none" autocomplete="username" spellcheck="false">
                        <span class="aff-help">3 hingga 12 huruf kecil tanpa ruang. Tidak boleh diubah selepas disimpan.</span>
                    @endif
                    @error('username')<span class="form-error">{{ $message }}</span>@enderror
                </label>
                <label>Nama Penuh<input value="{{ $affiliate->name }}" readonly></label>
                <label>Emel<input type="email" value="{{ $affiliate->email }}" readonly></label>
                <label>No. Telefon / WhatsApp
                    <input name="phone" type="tel" inputmode="tel" value="{{ old('phone', $affiliate->phone) }}" required maxlength="50">
                    @error('phone')<span class="form-error">{{ $message }}</span>@enderror
                </label>
                <label>Negeri
                    <select name="state" required>
                        <option value="" disabled @selected(! old('state', $affiliate->state))>Pilih negeri</option>
                        @foreach ($states as $state)<option value="{{ $state }}" @selected(old('state', $affiliate->state) === $state)>{{ $state }}</option>@endforeach
                    </select>
                    @error('state')<span class="form-error">{{ $message }}</span>@enderror
                </label>
                <label>Bank
                    <select name="bank_name" required>
                        <option value="" disabled @selected(! old('bank_name', $affiliate->bank_name))>Pilih bank</option>
                        @foreach ($banks as $bank)<option value="{{ $bank }}" @selected(old('bank_name', $affiliate->bank_name) === $bank)>{{ $bank }}</option>@endforeach
                    </select>
                    @error('bank_name')<span class="form-error">{{ $message }}</span>@enderror
                </label>
                <p class="aff-bank-notice">⚠ Akaun bank <b>wajib atas nama pemilik akaun affiliate</b> ({{ $affiliate->name }}). Pengeluaran komisyen hanya dibayar ke akaun atas nama ini.</p>
                <label class="aff-full">No. Akaun Bank
                    <input name="bank_account_number" inputmode="numeric" maxlength="30" autocomplete="off" @required(! $affiliate->bank_account_last4) placeholder="{{ $affiliate->bank_account_last4 ? 'Masukkan nombor baharu jika ingin tukar' : 'Masukkan nombor akaun bank' }}">
                    <span class="aff-help">{{ $affiliate->bank_account_last4 ? 'No. akaun tersimpan: '.$affiliate->maskedBankAccount().'. Dikekalkan jika tidak diubah.' : '6 hingga 30 digit tanpa simbol.' }}</span>
                    @error('bank_account_number')<span class="form-error">{{ $message }}</span>@enderror
                </label>
            </div>
            <div class="aff-social">
                <label>Facebook<input name="facebook_url" maxlength="300" placeholder="URL atau username" value="{{ old('facebook_url', $affiliate->facebook_url) }}"></label>
                <label>Instagram<input name="instagram_url" maxlength="300" placeholder="URL atau username" value="{{ old('instagram_url', $affiliate->instagram_url) }}"></label>
                <label>Twitter / X<input name="twitter_url" maxlength="300" placeholder="URL atau username" value="{{ old('twitter_url', $affiliate->twitter_url) }}"></label>
            </div>
            <div class="aff-actions">
                <a class="button button-secondary" href="{{ route('affiliate.profile') }}">Batal</a>
                <button class="button" type="submit">Simpan Perubahan</button>
            </div>
        </form>
    </article>
</div>
@endsection
