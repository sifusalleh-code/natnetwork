@extends('layouts.admin', ['title' => 'Tetapan Emel'])

@section('content')
<header class="adm-top"><div><h1>Tetapan Emel (Resend)</h1><p>Setiap notifikasi portal dan kod OTP dihantar ke emel pelanggan melalui Resend.</p></div></header>

@if (! $settings->resendReady() && ! \App\Engines\Communication\Services\EmailService::mailerDelivers())
    <div class="adm-danger" role="alert"><strong>Emel TIDAK dihantar kepada pengguna.</strong> Resend belum aktif dan server menggunakan MAIL_MAILER=log (emel hanya ditulis ke fail log). Kod OTP log masuk / pendaftaran, notifikasi dan borang Hubungi tidak akan sampai. Isi API key Resend, emel pengirim (domain mesti disahkan di Resend), tandakan "Aktifkan", simpan, kemudian hantar emel ujian.</div><br>
@endif
<div class="adm-grid">
    <article class="card">
        <h2>Resend</h2>
        <p>Status: <b>{{ $settings->resendReady() ? 'AKTIF — emel dihantar melalui Resend' : 'TIDAK AKTIF — emel guna mailer .env (MAIL_MAILER)' }}</b></p>
        <form method="post" action="{{ route('admin.communication.email.update') }}" class="adm-form" autocomplete="off">
            @csrf @method('PUT')
            <label>API key Resend
                <input name="resend_api_key" type="password" maxlength="200" placeholder="{{ \App\Engines\Billing\Models\BillingSetting::mask($settings->resend_api_key) ?? 'Belum diisi (re_...)' }}">
            </label>
            <span class="adm-help">Medan kosong mengekalkan kunci tersimpan. Kunci disimpan secara encrypted.</span>
            <label>Emel pengirim<input name="from_email" type="email" maxlength="190" value="{{ old('from_email', $settings->from_email) }}" placeholder="noreply@natnetwork.net" required></label>
            <span class="adm-help">Domain emel pengirim mesti disahkan (verified) dalam akaun Resend.</span>
            <label>Nama pengirim<input name="from_name" maxlength="100" value="{{ old('from_name', $settings->from_name) }}"></label>
            <label class="check"><input type="checkbox" name="email_enabled" value="1" @checked(old('email_enabled', $settings->email_enabled))> Aktifkan penghantaran melalui Resend</label>
            <button class="button" type="submit">Simpan</button>
        </form>
        <form method="post" action="{{ route('admin.communication.email.test') }}" style="margin-top: 1rem;">
            @csrf
            <button class="button button-secondary" type="submit">Hantar emel ujian ke {{ auth('admin')->user()->email }}</button>
        </form>
    </article>

    <article class="card adm-scroll">
        <h2>Log emel terkini</h2>
        <table class="adm-table">
            <thead><tr><th>Masa</th><th>Penerima</th><th>Subjek</th><th>Saluran</th><th>Status</th></tr></thead>
            <tbody>
            @forelse ($logs as $log)
                <tr>
                    <td>{{ $log->created_at->format('d/m H:i') }}</td>
                    <td>{{ $log->recipient }}</td>
                    <td>{{ $log->subject }}<br><span class="adm-help">{{ $log->category }}</span></td>
                    <td>{{ $log->channel }}</td>
                    <td><span @class(['adm-badge', 'FAILED' => $log->status === 'FAILED', 'PAID' => $log->status === 'SENT'])>{{ $log->status }}</span>@if ($log->error)<br><span class="adm-help">{{ $log->error }}</span>@endif</td>
                </tr>
            @empty
                <tr><td colspan="5">Belum ada emel.</td></tr>
            @endforelse
            </tbody>
        </table>
    </article>
</div>
@endsection
