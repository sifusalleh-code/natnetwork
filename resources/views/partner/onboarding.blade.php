@extends('layouts.client', ['title' => 'Program Partnership | NatNetwork Synergy', 'wide' => true])

@section('content')
@if ($step === 3)
@php($inv = $pending?->invoice)
@php($seller = $inv?->seller_snapshot ?? config('company'))
@php($cust = $inv?->customer_snapshot ?? ['name' => $partner->company_name ?: $partner->name, 'email' => $partner->email, 'phone' => $partner->phone, 'company' => $partner->company_name])
@php($statusLabel = ['ISSUED' => 'Belum Dibayar', 'PARTIALLY_PAID' => 'Dibayar Sebahagian', 'PAID' => 'Dibayar', 'VOID' => 'Dibatalkan'])
@php($pi = [
    'server' => '<rect x="3" y="4" width="18" height="7" rx="1.5"/><rect x="3" y="13" width="18" height="7" rx="1.5"/><path d="M7 7.5h.01M7 16.5h.01M11 7.5h6M11 16.5h6"/>',
    'globe' => '<circle cx="12" cy="12" r="9"/><path d="M3 12h18M12 3a14 14 0 0 1 0 18M12 3a14 14 0 0 0 0 18"/>',
    'gear' => '<circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.7 1.7 0 0 0 .3 1.8l.1.1a2 2 0 1 1-2.8 2.8l-.1-.1a1.7 1.7 0 0 0-1.8-.3 1.7 1.7 0 0 0-1 1.5V21a2 2 0 1 1-4 0v-.1a1.7 1.7 0 0 0-1.1-1.5 1.7 1.7 0 0 0-1.8.3l-.1.1a2 2 0 1 1-2.8-2.8l.1-.1a1.7 1.7 0 0 0 .3-1.8 1.7 1.7 0 0 0-1.5-1H3a2 2 0 1 1 0-4h.1a1.7 1.7 0 0 0 1.5-1.1 1.7 1.7 0 0 0-.3-1.8l-.1-.1a2 2 0 1 1 2.8-2.8l.1.1a1.7 1.7 0 0 0 1.8.3H9a1.7 1.7 0 0 0 1-1.5V3a2 2 0 1 1 4 0v.1a1.7 1.7 0 0 0 1 1.5 1.7 1.7 0 0 0 1.8-.3l.1-.1a2 2 0 1 1 2.8 2.8l-.1.1a1.7 1.7 0 0 0-.3 1.8V9a1.7 1.7 0 0 0 1.5 1H21a2 2 0 1 1 0 4h-.1a1.7 1.7 0 0 0-1.5 1Z"/>',
    'shield' => '<path d="M12 3l8 3v6c0 5-3.5 8-8 9-4.5-1-8-4-8-9V6l8-3z"/><path d="M9 12l2 2 4-4"/>',
    'edit' => '<path d="M4 20h4L19 9l-4-4L4 16v4Z"/><path d="m13.5 6.5 4 4"/>',
    'lock' => '<rect x="5" y="11" width="14" height="10" rx="2"/><path d="M8 11V7a4 4 0 0 1 8 0v4"/>',
    'arrow' => '<path d="M5 12h14M13 6l6 6-6 6"/>',
    'arrow-left' => '<path d="M19 12H5M11 6l-6 6 6 6"/>',
])
@php($svg = fn (string $n) => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">'.$pi[$n].'</svg>')
<section class="ppay">
    <aside class="ppay-side" aria-label="Maklumat bayaran modal">
        <span class="ppay-pill">PROGRAM PARTNERSHIP</span>
        <h2 class="ppay-title">Bayaran Modal <span>Penyediaan Perkhidmatan Hosting &amp; Domain</span></h2>
        <p class="ppay-lead">Modal partnership digunakan untuk penyediaan perkhidmatan hosting dan domain kepada pelanggan NatNetwork — bagi mengembangkan skop perniagaan, meneroka permintaan pasaran dan meningkatkan potensi keuntungan.</p>
        <ul class="ppay-points">
            <li><span class="ppay-ic">{!! $svg('server') !!}</span><span><b>Hosting Berprestasi Tinggi</b><small>Laman web pelanggan dihoskan menggunakan server yang stabil dan selamat.</small></span></li>
            <li><span class="ppay-ic">{!! $svg('globe') !!}</span><span><b>Domain .com</b><small>Pendaftaran domain untuk jenama pelanggan.</small></span></li>
            <li><span class="ppay-ic">{!! $svg('gear') !!}</span><span><b>Persediaan Lengkap</b><small>Pemasangan, konfigurasi dan tetapan asas diuruskan untuk pelanggan.</small></span></li>
            <li><span class="ppay-ic">{!! $svg('shield') !!}</span><span><b>Sokongan Penuh</b><small>Pasukan teknikal menyokong pelanggan selepas perkhidmatan diaktifkan.</small></span></li>
        </ul>
        <img class="ppay-photo" src="{{ asset('images/partnership/partnership-hosting.webp') }}" alt="Komputer riba memaparkan WWW di sebelah pelayan dan blok Domain, Hosting, Setup, Support" width="600" height="318" loading="lazy">
    </aside>

    <div class="ppay-card">
        <p class="eyebrow">Program Partnership</p>
        @include('partner.partials.steps', ['current' => $step])
        @if (session('status'))<p class="notice">{{ session('status') }}</p>@endif
        @if ($errors->any())<div class="prt-errors">@foreach ($errors->all() as $error)<div>{{ $error }}</div>@endforeach</div>@endif

        <h1 class="ppay-h1">Bayaran modal</h1>
        @if ($pending && $inv)
            <p class="ppay-sub">Selesaikan bayaran untuk mengaktifkan penyertaan anda. Status dikemas kini secara automatik selepas bayaran disahkan.</p>
            <article class="ppay-inv" aria-label="Invois {{ $inv->number }}">
                <header class="ppay-inv-head">
                    <div class="ppay-seller">
                        <img src="{{ asset('images/brand/natnetwork-synergy-primary-240.webp') }}" alt="" width="84" height="84">
                        <div>
                            <p class="ppay-brand">NatNetwork Synergy</p>
                            <p class="ppay-tag">Digital Technology Solutions</p>
                            <p class="ppay-legal">{{ $seller['name'] ?? 'NATNETWORK SYNERGY' }} ({{ $seller['registration_number'] ?? '' }})</p>
                            <p class="ppay-addr">{{ implode(', ', $seller['address_lines'] ?? []) }}.<br>Tel: {{ $seller['phone'] ?? '' }} &nbsp; Emel: {{ $seller['email'] ?? '' }}</p>
                        </div>
                    </div>
                    <dl class="ppay-meta">
                        <p class="ppay-meta-title">INVOIS</p>
                        <div><dt>No. Invois</dt><dd>{{ $inv->number }}</dd></div>
                        <div><dt>Tarikh Invois</dt><dd>{{ $inv->issued_at?->format('d M Y') }}</dd></div>
                        @if ($inv->due_at)<div><dt>Tarikh Tamat</dt><dd>{{ $inv->due_at->format('d M Y') }}</dd></div>@endif
                        <div><dt>No. Penyertaan</dt><dd>{{ $pending->number }}</dd></div>
                        <div><dt>Status</dt><dd><span @class(['ppay-badge', 'is-paid' => $inv->status === 'PAID', 'is-void' => $inv->status === 'VOID'])>{{ $statusLabel[$inv->status] ?? $inv->status }}</span></dd></div>
                    </dl>
                </header>
                <div class="ppay-parties">
                    <dl>
                        <p class="ppay-part-title">Maklumat Penerima</p>
                        <div><dt>Nama Penuh</dt><dd>{{ $partner->name }}</dd></div>
                        @if (! empty($cust['company']))<div><dt>Syarikat</dt><dd>{{ $cust['company'] }}</dd></div>@endif
                        <div><dt>Emel</dt><dd>{{ $cust['email'] ?? $partner->email }}</dd></div>
                        <div><dt>No. Telefon</dt><dd>{{ $cust['phone'] ?? $partner->phone }}</dd></div>
                    </dl>
                </div>
                <div class="ppay-table-wrap">
                    <table class="ppay-table">
                        <thead><tr><th>No.</th><th>Perkara / Perkhidmatan</th><th>Kuantiti</th><th>Harga Seunit (RM)</th><th>Jumlah (RM)</th></tr></thead>
                        <tbody>
                            @foreach ($inv->items_snapshot ?? [] as $i => $line)
                                <tr><td>{{ $i + 1 }}</td><td>{{ $line['description'] }}</td><td>{{ $line['quantity'] }}</td><td>{{ number_format((float) $line['unit_price'], 2) }}</td><td>{{ number_format((float) $line['line_total'], 2) }}</td></tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <dl class="ppay-totals">
                    <div><dt>Jumlah Sebelum Cukai</dt><dd>{{ number_format((float) $inv->subtotal, 2) }}</dd></div>
                    <div><dt>Cukai (0%)</dt><dd>{{ number_format((float) $inv->tax_amount, 2) }}</dd></div>
                    @if ((float) $inv->amount_paid > 0)<div><dt>Telah Dibayar</dt><dd>{{ number_format((float) $inv->amount_paid, 2) }}</dd></div>@endif
                    <div class="ppay-grand"><dt>Jumlah Bayaran</dt><dd>RM {{ number_format($inv->outstandingCents() / 100, 2) }}</dd></div>
                </dl>
            </article>
            <div class="ppay-actions">
                <form method="post" action="{{ route('partner.capital.cancel', $pending) }}">@csrf<button class="ppay-btn is-ghost" type="submit">{!! $svg('edit') !!} Tukar amaun</button></form>
                @if (in_array($inv->status, ['ISSUED', 'PARTIALLY_PAID'], true))
                    <form method="post" action="{{ route('partner.pay', $inv) }}" class="ppay-pay">@csrf<button class="ppay-btn is-primary" type="submit">{!! $svg('lock') !!} Bayar RM {{ number_format($inv->outstandingCents() / 100, 2) }} melalui Billplz {!! $svg('arrow') !!}</button></form>
                @endif
                <form method="post" action="{{ route('partner.logout') }}">@csrf<button class="ppay-btn is-ghost" type="submit">{!! $svg('arrow-left') !!} Log keluar</button></form>
            </div>
        @else
            <p class="ppay-sub">Masukkan amaun modal untuk menjana invois bayaran.</p>
            <form method="post" action="{{ route('partner.capital.store') }}" class="form-stack">
                @csrf
                <input type="hidden" name="agree" value="1">
                <label>Amaun modal (RM) <small>Minimum RM{{ number_format((float) $settings->min_capital, 2) }}</small>
                    <input name="amount" type="number" step="0.01" min="{{ (float) $settings->min_capital }}" value="{{ old('amount', (float) $settings->min_capital) }}" required>
                </label>
                <button class="button" type="submit">Jana invois bayaran</button>
            </form>
            <form method="post" action="{{ route('partner.logout') }}" class="top-gap">@csrf<button class="button button-secondary button-compact" type="submit">Log keluar</button></form>
        @endif
    </div>
</section>
@else
<section class="auth-card">
    <p class="eyebrow">Program Partnership</p>
    @include('partner.partials.steps', ['current' => $step])

    @if (session('status'))<p class="notice">{{ session('status') }}</p>@endif
    @if ($errors->any())<div class="prt-errors">@foreach ($errors->all() as $error)<div>{{ $error }}</div>@endforeach</div>@endif

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

    <form method="post" action="{{ route('partner.logout') }}" class="top-gap">@csrf<button class="button button-secondary button-compact" type="submit">Log keluar</button></form>
</section>
@endif
@endsection
