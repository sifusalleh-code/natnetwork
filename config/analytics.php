<?php

return [
    // Halaman awam yang direkod (nama route => label paparan).
    'pages' => [
        'home' => 'Laman Utama',
        'services' => 'Servis',
        'opportunity' => 'Peluang',
        'gallery.examples' => 'Demo / Projek',
        'contact' => 'Hubungi',
        'builder.start' => 'Builder Projek',
        'register' => 'Daftar',
        'client.login' => 'Log Masuk Client',
        'affiliate.register' => 'Daftar Affiliate',
        'affiliate.login' => 'Log Masuk Affiliate',
        'partner.register' => 'Daftar Partnership',
        'partner.login' => 'Log Masuk Partnership',
    ],

    // Butang CTA yang direkod (kunci => label). Dikesan daripada pautan butang di page awam.
    'ctas' => [
        'mulakan-projek' => 'Mulakan Projek',
        'lihat-servis' => 'Lihat Servis',
        'hubungi' => 'Hubungi Kami',
        'whatsapp' => 'WhatsApp',
        'telefon' => 'Telefon',
        'email' => 'Emel',
        'daftar-affiliate' => 'Daftar Affiliate',
        'daftar-client' => 'Daftar Client',
        'demo' => 'Lihat Demo / Projek',
        'peluang' => 'Peluang',
    ],

    'session_minutes' => 30,
    'live_minutes' => 5,
    'max_duration_seconds' => 1800,

    'sources' => [
        'organic' => 'Organic Search',
        'social' => 'Social Media',
        'direct' => 'Direct',
        'referral' => 'Referral',
        'affiliate' => 'Affiliate',
        'campaign' => 'Campaign',
    ],

    'search_hosts' => ['google.', 'bing.com', 'yahoo.', 'duckduckgo.com', 'yandex.', 'baidu.com', 'ecosia.org', 'search.brave.com'],
    'social_hosts' => ['facebook.com', 'fb.com', 'fb.me', 'instagram.com', 'whatsapp.com', 'wa.me', 'tiktok.com', 'telegram.org', 't.me', 'x.com', 'twitter.com', 't.co', 'linkedin.com', 'lnkd.in', 'youtube.com', 'youtu.be', 'threads.net', 'pinterest.com', 'reddit.com'],

    'countries' => [
        'MY' => 'Malaysia', 'SG' => 'Singapura', 'ID' => 'Indonesia', 'BN' => 'Brunei', 'TH' => 'Thailand', 'PH' => 'Filipina', 'VN' => 'Vietnam',
        'US' => 'Amerika Syarikat', 'GB' => 'United Kingdom', 'AU' => 'Australia', 'IN' => 'India', 'CN' => 'China', 'JP' => 'Jepun', 'SA' => 'Arab Saudi', 'AE' => 'UAE',
    ],
];
