@extends('layouts.admin', ['title' => 'Tetapan Billplz'])

@section('content')
<header class="adm-top"><div><h1>Tetapan Billplz</h1><p>Pilih mod pembayaran dan simpan kunci API bagi setiap mod.</p></div></header>

<div class="adm-grid">
    <article class="card">
        <h2>Mod pembayaran</h2>
        <p>Mod aktif sekarang: <b>{{ $settings->active_mode }}</b></p>
        <ul class="adm-help">
            <li><b>SANDBOX</b> — untuk ujian aliran bayaran sahaja. Rekod bernombor TEST-*, tidak masuk hasil, laporan, kapasiti atau komisyen affiliate, dan boleh dipadam.</li>
            <li><b>PRODUCTION</b> — transaksi sebenar. Rekod kewangan kekal dan tidak boleh dipadam.</li>
        </ul>
        @php($target = $settings->isSandbox() ? 'PRODUCTION' : 'SANDBOX')
        <form method="post" action="{{ route('admin.billing.settings.mode') }}" class="adm-form">
            @csrf @method('PUT')
            <input type="hidden" name="mode" value="{{ $target }}">
            <div class="{{ $target === 'PRODUCTION' ? 'adm-danger' : 'adm-warn' }}">
                @if ($target === 'PRODUCTION')
                    Mengaktifkan PRODUCTION: semua invois dan bayaran baharu akan menggunakan akaun Billplz sebenar dan wang sebenar. Invois sandbox sedia ada tidak lagi boleh dibayar.
                @else
                    Menukar ke SANDBOX: semua invois baharu akan menjadi rekod ujian TEST-*. Invois production sedia ada tidak boleh dibayar sehingga mod PRODUCTION diaktifkan semula.
                @endif
            </div>
            <label>Sebab perubahan<textarea name="reason" rows="2" maxlength="500" required>{{ old('reason') }}</textarea></label>
            <label class="check"><input type="checkbox" name="confirm" value="1" required> Saya faham kesan perubahan ini.</label>
            <button class="button" type="submit" @disabled(! $settings->hasCredentials($target))>Aktifkan {{ $target }}</button>
            @unless ($settings->hasCredentials($target))<span class="adm-help">Lengkapkan kunci {{ $target }} dahulu.</span>@endunless
        </form>
    </article>

    <article class="card">
        <h2>URL untuk Billplz</h2>
        <dl class="adm-dl">
            <dt>Callback URL</dt><dd>{{ $callbackUrl }}</dd>
            <dt>Redirect URL</dt><dd>{{ $returnUrl }}</dd>
        </dl>
        <p class="adm-help">Sistem menghantar kedua-dua URL ini secara automatik semasa mencipta bil. URL dibina daripada <b>APP_URL</b> dalam .env. Callback memerlukan domain awam HTTPS; ia tidak akan sampai ke komputer tempatan (localhost / .test) — untuk ujian tempatan guna tunnel (ngrok) dan tetapkan APP_URL kepada URL tunnel. Aktifkan <b>X Signature</b> dalam tetapan akaun Billplz.</p>
    </article>

    <article class="card" x-data="{ type: '{{ old('type', $settings->full_payment_reward_type ?? 'percent') }}' }">
        <h2>Ganjaran bayar penuh (100%)</h2>
        <p class="adm-help">Pelanggan boleh pilih bayar {{ config('billing.deposit_percent') }}% atau 100%. Ganjaran ini dipaparkan pada pilihan 100% dan dikunci pada invois semasa pelanggan memilih. Invois sedia ada tidak berubah.</p>
        <form method="post" action="{{ route('admin.billing.settings.reward') }}" class="adm-form">
            @csrf @method('PUT')
            <label class="check"><input type="checkbox" name="enabled" value="1" @checked(old('enabled', $settings->full_payment_reward_enabled))> Aktifkan ganjaran bayar penuh</label>
            <label>Jenis ganjaran
                <select name="type" x-model="type">
                    @foreach (\App\Engines\Billing\Models\BillingSetting::REWARD_TYPES as $key => $label)<option value="{{ $key }}">{{ $label }}</option>@endforeach
                </select>
            </label>
            <label x-show="type !== 'addon'"><span x-text="type === 'percent' ? 'Diskaun (%)' : 'Diskaun (RM)'">Nilai</span>
                <input name="value" type="number" step="0.01" min="0.01" value="{{ old('value', $settings->full_payment_reward_value) }}">
            </label>
            <label x-show="type === 'addon'" x-cloak>Add-on percuma
                <select name="addon_id">
                    <option value="">— Pilih add-on —</option>
                    @foreach ($addons as $addon)<option value="{{ $addon->id }}" @selected((int) old('addon_id', $settings->full_payment_reward_addon_id) === $addon->id)>{{ $addon->name }} ({{ $addon->price_label }})</option>@endforeach
                </select>
            </label>
            <button class="button" type="submit">Simpan ganjaran</button>
        </form>
    </article>

    @foreach (['SANDBOX' => 'sandbox', 'PRODUCTION' => 'production'] as $mode => $prefix)
        <article class="card">
            <h2>Kunci {{ $mode }}</h2>
            <p class="adm-help">{{ $mode === 'SANDBOX' ? 'Daripada billplz-sandbox.com' : 'Daripada billplz.com' }} → Settings. Medan kosong mengekalkan nilai tersimpan.</p>
            <form method="post" action="{{ route('admin.billing.settings.credentials', strtolower($mode)) }}" class="adm-form" autocomplete="off">
                @csrf @method('PUT')
                <label>API Secret Key
                    <input name="api_key" type="password" maxlength="200" placeholder="{{ \App\Engines\Billing\Models\BillingSetting::mask($settings->{$prefix.'_api_key'}) ?? 'Belum diisi' }}">
                </label>
                <label>Collection ID
                    <input name="collection_id" maxlength="60" placeholder="{{ $settings->{$prefix.'_collection_id'} ?? 'Belum diisi' }}">
                </label>
                <label>X Signature Key
                    <input name="x_signature_key" type="password" maxlength="200" placeholder="{{ \App\Engines\Billing\Models\BillingSetting::mask($settings->{$prefix.'_x_signature_key'}) ?? 'Belum diisi' }}">
                </label>
                <span class="adm-help">Status: {{ $settings->hasCredentials($mode) ? 'Lengkap' : 'Belum lengkap' }}</span>
                <button class="button button-secondary" type="submit">Simpan kunci {{ $mode }}</button>
            </form>
        </article>
    @endforeach
</div>
@endsection
