@extends('layouts.client', ['title' => 'Status Pembayaran Partnership | NatNetwork Synergy', 'wide' => true])

@php
    $inv = $payment->invoice;
    $seller = $inv->seller_snapshot ?? config('company');
    $cust = $inv->customer_snapshot ?? [];
    $pi = [
        'chart' => '<path d="M4 20V11M10 20V5M16 20v-8M22 20H2"/>',
        'megaphone' => '<path d="M3 11v2a1 1 0 0 0 1 1h3l9 5V5L7 10H4a1 1 0 0 0-1 1z"/><path d="M19 9a3 3 0 0 1 0 6M8 14l1.5 5"/>',
        'pie' => '<path d="M21 12A9 9 0 1 1 12 3v9z"/><path d="M15 3.5A9 9 0 0 1 20.5 9H15z"/>',
        'users' => '<circle cx="9" cy="8" r="3.5"/><path d="M2.5 20a6.5 6.5 0 0 1 13 0"/><circle cx="17" cy="9" r="2.5"/><path d="M16 14.2a5 5 0 0 1 5.5 5.8"/>',
        'doc' => '<path d="M14 3H6a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V9z"/><path d="M14 3v6h6M8 13h8M8 17h6"/>',
        'coins' => '<ellipse cx="12" cy="6" rx="7" ry="3"/><path d="M5 6v4c0 1.7 3.1 3 7 3s7-1.3 7-3V6M5 10v4c0 1.7 3.1 3 7 3s7-1.3 7-3v-4M5 14v4c0 1.7 3.1 3 7 3s7-1.3 7-3v-4"/>',
        'check-circle' => '<circle cx="12" cy="12" r="9"/><path d="m8 12 3 3 5-6"/>',
        'calendar' => '<rect x="3" y="5" width="18" height="16" rx="2"/><path d="M3 10h18M8 3v4M16 3v4M8 14h.01M12 14h.01M16 14h.01M8 17h.01M12 17h.01"/>',
        'clock' => '<circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/>',
        'grid' => '<rect x="4" y="4" width="6" height="6" rx="1"/><rect x="14" y="4" width="6" height="6" rx="1"/><rect x="4" y="14" width="6" height="6" rx="1"/><rect x="14" y="14" width="6" height="6" rx="1"/>',
        'send' => '<path d="M21 3 3 10.5l7 2.5 2.5 7z"/><path d="m10 13 5-5"/>',
        'arrow' => '<path d="M5 12h14M13 6l6 6-6 6"/>',
        'print' => '<path d="M6 9V3h12v6M6 18H4a1 1 0 0 1-1-1v-6a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2v6a1 1 0 0 1-1 1h-2"/><rect x="6" y="14" width="12" height="7" rx="1"/>',
        'x' => '<path d="M6 6l12 12M18 6 6 18"/>',
    ];
    $svg = fn (string $n) => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">'.$pi[$n].'</svg>';
@endphp

@section('content')
<div class="pps" x-data="partnerPayStatus(@js($state))" x-init="init()" @keydown.escape.window="receipt = false">
    {{-- Kiri --}}
    <section class="pps-left">
        <span class="pps-pill">PROGRAM PARTNERSHIP</span>
        <h1 class="pps-title" x-show="isPaid()" x-cloak>Pembayaran <span>Berjaya!</span></h1>
        <h1 class="pps-title" x-show="isPending()">Pembayaran <span>Sedang Disahkan</span></h1>
        <h1 class="pps-title" x-show="isReview()" x-cloak>Pembayaran <span>Dalam Semakan</span></h1>
        <h1 class="pps-title is-fail" x-show="isFailed()" x-cloak>Pembayaran <span>Tidak Berjaya</span></h1>
        <p class="pps-lead" x-show="isPaid()" x-cloak>Terima kasih kerana menyertai Program Partnership NatNetwork. Pembayaran modal anda telah disahkan dan penyertaan anda sedang diaktifkan.</p>
        <p class="pps-lead" x-show="! isPaid()">Terima kasih kerana menyertai Program Partnership NatNetwork. Status pembayaran modal anda dipaparkan di sebelah dan dikemas kini secara automatik.</p>
        <ul class="pps-points">
            <li><span class="pps-ic">{!! $svg('chart') !!}</span><span><b>Agihan Hasil Jualan</b><small>Bahagian hasil berdasarkan penyertaan yang disahkan.</small></span></li>
            <li><span class="pps-ic">{!! $svg('megaphone') !!}</span><span><b>Sokongan Marketing</b><small>Aktiviti marketing berterusan oleh team syarikat.</small></span></li>
            <li><span class="pps-ic">{!! $svg('pie') !!}</span><span><b>Pemantauan Prestasi</b><small>Prestasi jualan dan aktiviti partner dipantau.</small></span></li>
            <li><span class="pps-ic">{!! $svg('users') !!}</span><span><b>Sokongan Partner</b><small>Pasukan syarikat membantu sepanjang penyertaan.</small></span></li>
        </ul>
        <img class="pps-script" src="{{ asset('images/partnership/bersama-membangun.png') }}" alt="Bersama Membangun Peluang Lebih Besar" width="294" height="150" loading="lazy">
    </section>

    {{-- Tengah: status --}}
    <section class="pps-card" aria-live="polite">
        <div class="pps-tags"><span>PEMBAYARAN</span><em>PROGRAM PARTNERSHIP</em></div>
        <div class="pps-icon" :class="{ 'is-pending': isPending(), 'is-ok': isPaid(), 'is-warn': isReview(), 'is-fail': isFailed() }">
            <svg x-show="isPending()" class="pps-spin" viewBox="0 0 24 24" fill="none" aria-hidden="true"><circle cx="12" cy="12" r="9" stroke="currentColor" stroke-width="2.5" stroke-opacity=".25"/><path d="M21 12a9 9 0 0 0-9-9" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"/></svg>
            <svg x-show="isPaid()" x-cloak viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="m5 12.5 4.5 4.5L19 7" stroke="currentColor" stroke-width="3.2" stroke-linecap="round" stroke-linejoin="round"/></svg>
            <svg x-show="isFailed()" x-cloak viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M6 6l12 12M18 6 6 18" stroke="currentColor" stroke-width="3" stroke-linecap="round"/></svg>
            <svg x-show="isReview()" x-cloak viewBox="0 0 24 24" fill="none" aria-hidden="true"><circle cx="12" cy="12" r="9" stroke="currentColor" stroke-width="2.5"/><path d="M12 7v6l4 2" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"/></svg>
        </div>
        <h2 class="pps-h2" x-show="isPaid()" x-cloak>Bayaran berjaya!</h2>
        <h2 class="pps-h2" x-show="isPending()">Bayaran sedang diproses dan disahkan.</h2>
        <h2 class="pps-h2" x-show="isReview()" x-cloak>Bayaran sedang disemak</h2>
        <h2 class="pps-h2" x-show="isFailed()" x-cloak>Bayaran tidak berjaya</h2>
        <p class="pps-msg" x-show="isPaid()" x-cloak>Bayaran <b>RM <span x-text="state.invoice.amount_paid"></span></b> untuk invois <b x-text="state.invoice.number"></b> telah disahkan.<br><small>Terima kasih kerana menyertai Program Partnership NatNetwork.</small></p>
        <p class="pps-msg" x-show="isPending()">Kami sedang menunggu pengesahan rasmi daripada Billplz. Halaman ini <b>bertukar secara automatik</b> sebaik sahaja bayaran disahkan — tiada perlu muat semula.</p>
        <p class="pps-msg" x-show="isReview()" x-cloak>Bayaran untuk invois <b x-text="state.invoice.number"></b> memerlukan semakan pasukan kami. Kami akan menghubungi anda.</p>
        <p class="pps-msg" x-show="isFailed()" x-cloak>Bayaran untuk invois <b x-text="state.invoice.number"></b> tidak berjaya. Anda boleh cuba semula dengan selamat.</p>

        <dl class="pps-rows">
            <div><dt>{!! $svg('doc') !!} No. Invois</dt><dd x-text="state.invoice.number"></dd></div>
            <div><dt>{!! $svg('coins') !!} Jumlah Invois</dt><dd>RM <span x-text="state.invoice.total"></span></dd></div>
            <div><dt class="is-ok">{!! $svg('check-circle') !!} Telah Dibayar</dt><dd class="is-ok">RM <span x-text="state.invoice.amount_paid"></span></dd></div>
            <div><dt>{!! $svg('calendar') !!} Tarikh Bayaran</dt><dd class="is-plain" x-text="state.paid_at || '—'"></dd></div>
            <div><dt>{!! $svg('clock') !!} Status</dt><dd><span class="pps-badge" :class="{ 'is-ok': isPaid(), 'is-pending': isPending(), 'is-warn': isReview(), 'is-fail': isFailed() }"><span x-text="statusLabel()"></span> <svg x-show="isPaid()" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" aria-hidden="true"><path d="m5 12.5 4.5 4.5L19 7"/></svg></span></dd></div>
        </dl>

        <div class="pps-actions">
            <button type="button" class="pps-btn is-ghost" x-show="isPaid()" x-cloak @click="receipt = true">{!! $svg('doc') !!} Lihat Invois</button>
            <a class="pps-btn is-ghost" x-show="! isPaid()" href="{{ route('partner.invoice', $inv) }}">{!! $svg('doc') !!} Lihat Invois</a>
            <a class="pps-btn is-primary" x-show="! isFailed()" href="{{ route('partner.onboarding') }}">{!! $svg('users') !!} Lengkapkan Profil {!! $svg('arrow') !!}</a>
            <a class="pps-btn is-primary" x-show="isFailed()" x-cloak href="{{ route('partner.onboarding') }}">Cuba bayar semula {!! $svg('arrow') !!}</a>
        </div>
    </section>

    {{-- Kanan --}}
    <section class="pps-right">
        <div class="pps-next">
            <h2>Apa seterusnya?</h2>
            <ol>
                <li><b>1</b><span class="pps-next-ic">{!! $svg('doc') !!}</span><p>Penyertaan sedang diproses dan diaktifkan selepas bayaran disahkan.</p></li>
                <li><b>2</b><span class="pps-next-ic">{!! $svg('users') !!}</span><p>Lengkapkan profil, kemudian akses maklumat program di dashboard.</p></li>
                <li><b>3</b><span class="pps-next-ic">{!! $svg('send') !!}</span><p>Team akan menghubungi anda untuk panduan seterusnya.</p></li>
            </ol>
        </div>
        <img class="pps-photo" src="{{ asset('images/partnership/partnership-success.webp') }}" alt="Folder Partnership NatNetwork Synergy di atas meja mesyuarat bersama laporan prestasi" width="500" height="499" loading="lazy">
    </section>

    {{-- Resit lengkap --}}
    <div class="pps-modal" x-show="receipt" x-cloak x-transition.opacity @click.self="receipt = false" role="dialog" aria-modal="true" aria-labelledby="pps-rcp-title">
        <article class="pps-receipt">
            <header class="pps-rcp-head">
                <div class="pps-rcp-seller">
                    <img src="{{ asset('images/brand/natnetwork-synergy-primary-240.webp') }}" alt="" width="64" height="64">
                    <div>
                        <p class="pps-rcp-brand">{{ $seller['name'] ?? 'NATNETWORK SYNERGY' }}</p>
                        <p>{{ $seller['registration_number'] ?? '' }}</p>
                        <p>{{ implode(', ', $seller['address_lines'] ?? []) }}</p>
                        <p>Tel: {{ $seller['phone'] ?? '' }} · {{ $seller['email'] ?? '' }}</p>
                    </div>
                </div>
                <div class="pps-rcp-title">
                    <h3 id="pps-rcp-title">RESIT BAYARAN</h3>
                    <p>No. Resit: <b x-text="state.receipt?.number || '—'"></b></p>
                    <p>Tarikh: <b x-text="state.receipt?.issued_at || state.paid_at || '—'"></b></p>
                    <span class="pps-badge is-ok">Dibayar</span>
                </div>
            </header>
            <div class="pps-rcp-grid">
                <dl>
                    <p class="pps-rcp-sub">Diterima daripada</p>
                    <div><dt>Nama</dt><dd>{{ $cust['name'] ?? '' }}</dd></div>
                    @if (! empty($cust['company']))<div><dt>Syarikat</dt><dd>{{ $cust['company'] }}</dd></div>@endif
                    <div><dt>Emel</dt><dd>{{ $cust['email'] ?? '' }}</dd></div>
                    <div><dt>No. Telefon</dt><dd>{{ $cust['phone'] ?? '' }}</dd></div>
                </dl>
                <dl>
                    <p class="pps-rcp-sub">Butiran bayaran</p>
                    <div><dt>No. Invois</dt><dd x-text="state.invoice.number"></dd></div>
                    @if ($capital)<div><dt>No. Penyertaan</dt><dd>{{ $capital->number }}</dd></div>@endif
                    <div><dt>Kaedah</dt><dd>Billplz (FPX / Kad)</dd></div>
                    <div><dt>Rujukan Bil</dt><dd x-text="state.bill_id || '—'"></dd></div>
                    <div><dt>Tarikh Bayaran</dt><dd x-text="state.paid_at || '—'"></dd></div>
                </dl>
            </div>
            <table class="pps-rcp-table">
                <thead><tr><th>Perkara</th><th>Jumlah (RM)</th></tr></thead>
                <tbody>@foreach ($inv->items_snapshot ?? [] as $line)<tr><td>{{ $line['description'] }}</td><td>{{ number_format((float) $line['line_total'], 2) }}</td></tr>@endforeach</tbody>
                <tfoot>
                    <tr><th>Jumlah invois</th><td>RM <span x-text="state.invoice.total"></span></td></tr>
                    <tr class="is-paid"><th>Jumlah diterima</th><td>RM <span x-text="state.receipt?.amount || state.invoice.amount_paid"></span></td></tr>
                </tfoot>
            </table>
            <p class="pps-rcp-note">{!! $svg('check-circle') !!} <span>Salinan resit ini telah direkod dalam <b>portal Partnership</b> anda (Modal → Invois <span x-text="state.invoice.number"></span>) untuk rujukan pada bila-bila masa.</span></p>
            <div class="pps-rcp-actions">
                <button type="button" class="pps-btn is-ghost" onclick="window.print()">{!! $svg('print') !!} Cetak / PDF</button>
                <button type="button" class="pps-btn is-primary" @click="receipt = false">{!! $svg('x') !!} Tutup</button>
            </div>
        </article>
    </div>
</div>

<script>
function partnerPayStatus(initial) {
    return {
        state: initial,
        receipt: false,
        timer: null,
        init() { if (this.isPending()) this.poll(); },
        isPending() { return ['PENDING', 'PROCESSING'].includes(this.state?.status); },
        isPaid() { return this.state?.status === 'PAID'; },
        isReview() { return this.state?.status === 'REVIEW_REQUIRED'; },
        isFailed() { return ['FAILED', 'EXPIRED', 'CANCELLED'].includes(this.state?.status); },
        statusLabel() {
            return { PENDING: 'Menunggu', PROCESSING: 'Sedang diproses', PAID: 'Dibayar', REVIEW_REQUIRED: 'Dalam semakan', FAILED: 'Tidak berjaya', EXPIRED: 'Tamat tempoh', CANCELLED: 'Dibatalkan' }[this.state?.status] || this.state?.status;
        },
        poll() {
            this.timer = setTimeout(async () => {
                try {
                    const res = await fetch('{{ route('billing.billplz.status') }}' + window.location.search, { headers: { Accept: 'application/json' } });
                    const data = await res.json();
                    if (data.found) { delete data.found; this.state = data; }
                } catch (e) { /* cuba lagi pusingan seterusnya */ }
                if (this.isPending()) this.poll();
            }, 3000);
        },
    };
}
</script>
@endsection
