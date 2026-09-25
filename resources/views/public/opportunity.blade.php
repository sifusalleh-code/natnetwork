@extends('layouts.public', ['title' => 'Peluang Affiliate | NatNetwork Synergy'])

@php
    $money = fn ($v) => 'RM'.number_format((float) $v, fmod((float) $v, 1) ? 2 : 0);
    $pct = fn ($r) => rtrim(rtrim(number_format((float) $r, 2, '.', ''), '0'), '.').'%';
    $tiers = $rateVersion->tiers;
    $ranges = [];
    $lower = 0;
    foreach ($tiers as $t) {
        $ranges[] = ['label' => $t['up_to'] === null ? 'Melebihi '.$money($lower) : $money($lower).' – '.$money($t['up_to']), 'rate' => $pct($t['rate'])];
        $lower = $t['up_to'] ?? $lower;
    }
    $oi = function (string $name) {
        $p = [
            'send' => '<path d="M22 2 11 13"/><path d="m22 2-7 20-4-9-9-4Z"/>',
            'arrow' => '<path d="M5 12h14M13 6l6 6-6 6"/>',
            'user-plus' => '<circle cx="9" cy="8" r="4"/><path d="M2 21a7 7 0 0 1 14 0M19 8v6M16 11h6"/>',
            'image' => '<rect x="3" y="4" width="18" height="16" rx="2"/><circle cx="9" cy="9.5" r="1.8"/><path d="m21 16-5-5-9 9"/>',
            'monitor' => '<rect x="3" y="4" width="18" height="12" rx="2"/><path d="M8 20h8M12 16v4"/>',
            'coins' => '<ellipse cx="12" cy="6" rx="7" ry="3"/><path d="M5 6v4c0 1.7 3.1 3 7 3s7-1.3 7-3V6M5 10v4c0 1.7 3.1 3 7 3s7-1.3 7-3v-4M5 14v4c0 1.7 3.1 3 7 3s7-1.3 7-3v-4"/>',
            'chart' => '<path d="M4 20V10M10 20V4M16 20v-7M22 20H2"/>',
            'check' => '<path d="m5 12.5 4.5 4.5L19 7.5"/>',
            'check-circle' => '<circle cx="12" cy="12" r="9"/><path d="m8 12.5 2.8 2.8L16.5 9.5"/>',
            'share' => '<circle cx="18" cy="5" r="3"/><circle cx="6" cy="12" r="3"/><circle cx="18" cy="19" r="3"/><path d="m8.6 13.5 6.8 4M15.4 6.5l-6.8 4"/>',
            'users' => '<circle cx="9" cy="8" r="3.5"/><path d="M2.5 20a6.5 6.5 0 0 1 13 0"/><path d="M16 4.5a3.5 3.5 0 0 1 0 7M18 14a6 6 0 0 1 3.5 6"/>',
            'receipt' => '<path d="M5 3h14v18l-3-2-2 2-2-2-2 2-2-2-3 2V3Z"/><path d="M9 8h6M9 12h6"/>',
            'split' => '<path d="M4 6h16M4 12h10M4 18h6"/>',
            'calc' => '<rect x="5" y="3" width="14" height="18" rx="2"/><path d="M8 7h8M8 12h.01M12 12h.01M16 12h.01M8 16h.01M12 16h.01M16 16h.01"/>',
            'wallet' => '<path d="M19 7V5a2 2 0 0 0-2-2H5a2 2 0 0 0 0 4h14a2 2 0 0 1 2 2v10a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5"/><path d="M16 14h.01"/>',
            'info' => '<circle cx="12" cy="12" r="9"/><path d="M12 11v5M12 8h.01"/>',
            'gift' => '<rect x="3" y="8" width="18" height="4" rx="1"/><path d="M12 8v13M5 12v9h14v-9M12 8S10.5 3 8 4s-1 4 4 4c5 0 6.5-3 4-4s-4 4-4 4"/>',
        ][$name] ?? '';

        return '<svg class="op-i" viewBox="0 0 24 24" aria-hidden="true" focusable="false">'.$p.'</svg>';
    };
@endphp

@section('content')
<div class="op">
    {{-- HERO --}}
    <section class="op-hero" aria-labelledby="op-title">
        <div class="op-hero-bg" style="background-image:url('https://images.unsplash.com/photo-1497366754035-f200968a6e72?auto=format&fit=crop&q=60&w=1600&h=900')" aria-hidden="true"></div>
        <div class="op-wrap op-hero-grid">
            <div class="op-hero-copy">
                <p class="op-pill">Peluang Affiliate</p>
                <h1 id="op-title">Kongsi Peluang<span class="op-dot">.</span><br><span class="op-gold">Bina Pendapatan.</span></h1>
                <p class="op-lead">Sertai Affiliate NatNetwork Synergy dan gunakan bahan promosi ready-made, referral link dan sistem lengkap untuk membina pendapatan melalui setiap rujukan anda.</p>
                <div class="op-actions">
                    <a class="op-btn op-btn-gold" href="{{ route('affiliate.register') }}">Daftar Sekarang {!! $oi('arrow') !!}</a>
                    <a class="op-btn op-btn-line" href="#cara">Lihat Cara Ia Berfungsi</a>
                </div>
                <ul class="op-badges">
                    <li><span>{!! $oi('user-plus') !!}</span><b>Daftar Percuma<small>Mudah &amp; Pantas</small></b></li>
                    <li><span>{!! $oi('image') !!}</span><b>Bahan Promosi<small>Ready-Made</small></b></li>
                    <li><span>{!! $oi('monitor') !!}</span><b>Sistem Lengkap<small>Pantau &amp; Jana Komisen</small></b></li>
                </ul>
            </div>

            <div class="op-hero-visual" aria-hidden="true">
                <div class="op-laptop">
                    <div class="op-screen">
                        <aside class="op-dash-side"><b>NatNetwork</b><small>Affiliate Dashboard</small><i class="is-on">Dashboard</i><i>Studio Poster</i><i>Referral Link</i><i>Statistik</i><i>Komisen</i><i>Withdrawal</i><i>Profil</i></aside>
                        <div class="op-dash-main">
                            <p class="op-dash-title">Affiliate Dashboard</p>
                            <div class="op-dash-stats"><span><b>1,248</b>Total Clicks</span><span><b>856</b>Unique Visitors</span><span><b>42</b>Referral</span><span class="is-green"><b>RM1,250</b>Komisen (Pending)</span></div>
                            <div class="op-dash-chart"><p>Traffic Overview</p><div class="op-bars">@foreach ([30, 45, 38, 60, 52, 70, 48, 82, 66, 90, 74, 95, 80, 88] as $h)<i style="height:{{ $h }}%"></i>@endforeach</div></div>
                            <div class="op-dash-table"><p>Recent Referral Activity</p>
                                <div class="op-row is-head"><span>Tarikh</span><span>Pelanggan</span><span>Jumlah</span><span>Status</span></div>
                                <div class="op-row"><span>12 Sep</span><span>Client A</span><span>RM1,500</span><span><em class="is-pending">Pending</em></span></div>
                                <div class="op-row"><span>9 Sep</span><span>Client B</span><span>RM3,900</span><span><em class="is-ok">Released</em></span></div>
                                <div class="op-row"><span>5 Sep</span><span>Client C</span><span>RM2,000</span><span><em class="is-pending">Pending</em></span></div>
                            </div>
                        </div>
                    </div>
                    <div class="op-laptop-base"></div>
                </div>
                <div class="op-phone">
                    <p>POSTER PROMOSI<br>READY-MADE</p>
                    <div class="op-poster" style="background-image:url('https://images.unsplash.com/photo-1515378791036-0648a3ef77b2?auto=format&fit=crop&q=60&w=360&h=440')"><b>Bina Website Profesional</b><small>NatNetwork Synergy</small></div>
                    <span class="op-phone-btn">{!! $oi('share') !!} Share</span>
                    <span class="op-phone-btn is-light">Download</span>
                </div>
                <span class="op-plane">{!! $oi('send') !!}</span>
                <p class="op-flow-pill">PILIH → SHARE → DAPAT KLIK<br>→ JANA KOMISEN</p>
            </div>
        </div>
    </section>

    {{-- 4 SEBAB --}}
    <section class="op-section op-reasons" aria-labelledby="op-why">
        <div class="op-wrap op-reasons-grid">
            <div class="op-intro">
                <p class="op-pill">Mengapa sertai affiliate?</p>
                <h2 id="op-why">4 Sebab Utama Anda Patut Sertai</h2>
                <p>Sistem yang lengkap, bahan promosi disediakan dan potensi komisen yang menarik.</p>
            </div>
            @php($reasons = [
                ['01', 'Studio Poster Lengkap', 'image', 'is-blue', ['Poster ready-made', 'Copywriting disediakan', 'Referral link tersedia', 'Klik untuk share atau download']],
                ['02', 'Promosi Lebih Mudah', 'send', 'is-blue', ['Pilih bahan promosi', 'Gunakan link anda', 'Kongsi di media sosial', 'Pantau hasil perkongsian']],
                ['03', 'Komisen Berpotensi Tinggi', 'coins', 'is-gold', ['Kadar komisen menarik secara berperingkat', 'Setiap bayaran dikreditkan', 'Potensi pendapatan berterusan']],
                ['04', 'Semua Boleh Dipantau', 'chart', 'is-blue', ['Statistik trafik', 'Jumlah referral', 'Status komisen', 'Rekod pembayaran']],
            ])
            @foreach ($reasons as [$no, $title, $icon, $tone, $points])
                <article class="op-reason {{ $tone }}">
                    <span class="op-reason-icon">{!! $oi($icon) !!}</span>
                    <p class="op-no">{{ $no }}</p>
                    <h3>{{ $title }}</h3>
                    <ul>@foreach ($points as $pt)<li>{!! $oi('check') !!}{{ $pt }}</li>@endforeach</ul>
                </article>
            @endforeach
        </div>
    </section>

    {{-- KADAR KOMISEN --}}
    <section class="op-section op-rates" id="kadar" aria-labelledby="op-rate-title">
        <div class="op-wrap">
            <div class="op-rates-top">
                <div class="op-intro">
                    <p class="op-pill">Kadar komisen rasmi</p>
                    <h2 id="op-rate-title">Komisen Berdasarkan Nilai Jualan</h2>
                    <p>Komisen dikira secara berperingkat berdasarkan jumlah terkumpul pelanggan. Semakin tinggi jumlah transaksi, lebih banyak komisen yang anda peroleh.</p>
                    <p class="op-note">{!! $oi('info') !!}<span>Kadar ini digunakan untuk invois yang dikeluarkan selepas {{ $rateVersion->effective_from?->timezone(config('app.timezone'))->format('d/m/Y H:i') }}.@if ($hasPreviousVersion) Komisen bagi invois sebelum waktu tersebut kekal berdasarkan kadar versi sebelumnya.@endif</span></p>
                </div>
                <div class="op-card op-tiers">
                    <p class="op-card-title">Julat jumlah terkumpul</p>
                    <ul>@foreach ($ranges as $r)<li><span>{{ $r['label'] }}</span><b>{{ $r['rate'] }}</b></li>@endforeach</ul>
                </div>
                <div class="op-card op-steps-mini">
                    <p class="op-card-title">Cara kiraan</p>
                    <ol>
                        <li><span>{!! $oi('receipt') !!}</span>Nilai transaksi pelanggan</li>
                        <li><span>{!! $oi('split') !!}</span>Pecahkan mengikut julat kadar</li>
                        <li><span>{!! $oi('calc') !!}</span>Kira komisen setiap julat</li>
                        <li><span>{!! $oi('wallet') !!}</span>Jumlah komisen Affiliate</li>
                    </ol>
                </div>
            </div>

            <div class="op-rates-bottom">
                <div>
                    <p class="op-card-title">Contoh potensi komisen</p>
                    <div class="op-examples">
                        @foreach ($examples as $ex)
                            <article class="op-ex">
                                <p class="op-ex-amount">{{ $money($ex['amount']) }}</p>
                                <p class="op-ex-label">Komisen</p>
                                <p class="op-ex-value">{{ $money($ex['commission']) }}</p>
                                <p class="op-ex-label">Kadar purata</p>
                                <p class="op-ex-rate">{{ number_format($ex['commission'] / $ex['amount'] * 100, 2) }}%</p>
                            </article>
                        @endforeach
                    </div>
                </div>
                <div class="op-card op-sample">
                    <p class="op-sample-title">Contoh kiraan RM12,000</p>
                    <table>
                        <tbody>
                            @foreach ($sample['breakdown'] as $row)
                                <tr><td>{{ $money($row['portion']) }}</td><td>× {{ $pct($row['rate']) }}</td><td>=</td><td>{{ $money((float) $row['portion'] * (float) $row['rate'] / 100) }}</td></tr>
                            @endforeach
                        </tbody>
                        <tfoot><tr><td colspan="2">Jumlah</td><td>=</td><td>{{ $money($sample['amount_cents'] / 100) }}</td></tr></tfoot>
                    </table>
                </div>
            </div>
        </div>
    </section>

    {{-- CARA IA BERFUNGSI --}}
    <section class="op-section op-how" id="cara" aria-labelledby="op-how-title">
        <div class="op-wrap op-how-grid">
            <div class="op-intro">
                <p class="op-pill">Cara ia berfungsi</p>
                <h2 id="op-how-title">Dari Pendaftaran Hingga Komisen</h2>
                <p>Langkah demi langkah yang mudah untuk memulakan dan menjana komisen sebagai Affiliate.</p>
            </div>
            @php($steps = [
                ['01', 'is-green', 'user-plus', 'Daftar Affiliate', 'Daftar sebagai ahli dan lengkapkan profil.'],
                ['02', 'is-blue', 'monitor', 'Akses Dashboard', 'Akses dashboard dan Studio Poster untuk mendapatkan bahan promosi.'],
                ['03', 'is-violet', 'share', 'Task Mingguan', 'Wajib share 20 kali dengan minimum 100 unique clicks untuk kelayakan withdraw.'],
                ['04', 'is-orange', 'users', 'Audien Jadi Client', 'Audien klik link anda, daftar sebagai client dan buat deposit 50%. Mereka akan mendapat portal projek.'],
                ['05', 'is-red', 'coins', 'Komisen Dikreditkan', 'Setiap invois client dikreditkan sebagai komisen Pending selagi baki bayaran projek belum jelas.'],
                ['06', 'is-blue', 'check-circle', 'Projek Selesai', 'Apabila bayaran projek selesai dan admin release, status komisen bertukar kepada Released dan layak untuk withdrawal.'],
            ])
            <ol class="op-timeline">
                @foreach ($steps as [$no, $tone, $icon, $title, $desc])
                    <li class="{{ $tone }}">
                        <span class="op-step-no">{{ $no }}</span>
                        <span class="op-step-icon">{!! $oi($icon) !!}</span>
                        <h3>{{ $title }}</h3>
                        <p>{{ $desc }}</p>
                    </li>
                @endforeach
            </ol>
        </div>
    </section>

    {{-- SYARAT WITHDRAWAL --}}
    <section class="op-withdraw" aria-labelledby="op-wd-title">
        <div class="op-wrap op-withdraw-grid">
            <div>
                <p class="op-pill">Syarat withdrawal</p>
                <h2 id="op-wd-title">Task Mingguan Untuk Kelayakan Withdrawal</h2>
                <p>Bagi memastikan aktiviti Affiliate aktif, terdapat task mingguan yang perlu dipenuhi sebelum komisen boleh dikeluarkan.</p>
            </div>
            <div class="op-wd-stat"><span>{!! $oi('share') !!}</span><p>Minimum<b>20 Perkongsian</b>setiap minggu.</p></div>
            <div class="op-wd-stat"><span>{!! $oi('chart') !!}</span><p>Minimum<b>100 Unique Clicks</b>setiap minggu.</p></div>
            <div class="op-wd-done"><span>{!! $oi('check') !!}</span><p>Lengkapkan task mingguan untuk layak membuat withdrawal.</p></div>
        </div>
    </section>

    {{-- CTA --}}
    <section class="op-cta" aria-labelledby="op-cta-title">
        <div class="op-cta-bg" style="background-image:url('https://images.unsplash.com/photo-1522071820081-009f0129c71c?auto=format&fit=crop&q=60&w=1600&h=600')" aria-hidden="true"></div>
        <div class="op-wrap op-cta-grid">
            <div class="op-cta-copy">
                <p class="op-pill is-small">Bersama NatNetwork Synergy</p>
                <h2 id="op-cta-title">Sedia Membina Peluang Anda?</h2>
                <p>Sertai Affiliate NatNetwork Synergy hari ini dan mulakan perjalanan anda untuk menjana komisen melalui setiap rujukan.</p>
            </div>
            <div class="op-cta-actions">
                <a class="op-btn op-btn-blue" href="{{ route('affiliate.register') }}">Daftar Sebagai Affiliate {!! $oi('arrow') !!}</a>
                <a class="op-btn op-btn-outline" href="{{ route('affiliate.login') }}">Sudah Ada Akaun? Log Masuk</a>
            </div>
        </div>
    </section>
</div>
@endsection
