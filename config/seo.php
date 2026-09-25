<?php

/*
| Halaman awam yang diurus SEO. Route yang tiada dalam senarai ini sentiasa noindex
| (login, OTP, portal, admin, billing). Tajuk asal datang daripada view; admin boleh
| menggantikan tajuk/huraian/imej dan status index melalui Admin → Tetapan SEO.
*/
return [
    'site_name' => 'NatNetwork Synergy',
    'default_description' => 'NatNetwork Synergy menyediakan pembangunan website, e-commerce, aplikasi web, AI, automasi dan integrasi untuk perniagaan.',
    'default_og_image' => 'images/seo/og-default.png',
    'theme_color' => '#082b52',
    'locale' => 'ms_MY',
    'language' => 'ms-MY',

    'pages' => [
        'home' => ['title' => 'NatNetwork Synergy — Penyelesaian Digital untuk Bisnes Anda', 'label' => 'Home', 'index' => true, 'priority' => '1.0', 'changefreq' => 'weekly',
            'description' => 'NatNetwork Synergy menyediakan pembangunan website, e-commerce, aplikasi web, AI, automasi dan integrasi untuk perniagaan.'],
        'services' => ['title' => 'Servis Website, E-Commerce & AI Automation | NatNetwork Synergy', 'label' => 'Servis', 'index' => true, 'priority' => '0.9', 'changefreq' => 'weekly',
            'description' => 'Servis digital NatNetwork Synergy: Website Development, E-Commerce, Custom Web Applications, AI & Automation, System/API Integration serta Maintenance & Support.'],
        'opportunity' => ['title' => 'Peluang Affiliate — Kongsi Peluang, Bina Pendapatan | NatNetwork Synergy', 'label' => 'Peluang', 'index' => true, 'priority' => '0.8', 'changefreq' => 'monthly',
            'description' => 'Sertai Affiliate NatNetwork Synergy. Kongsi referral link, bantu pelanggan mula projek digital dan jana komisen berperingkat bagi setiap invois pelanggan.'],
        'gallery.examples' => ['title' => 'Galeri Contoh Website | NatNetwork Synergy', 'label' => 'Demo', 'index' => true, 'priority' => '0.7', 'changefreq' => 'monthly',
            'description' => 'Lihat contoh website mengikut kategori servis. Pilih mana-mana contoh untuk membuka demo website sebenar secara penuh.'],
        'contact' => ['title' => 'Hubungi Kami | NatNetwork Synergy', 'label' => 'Hubungi', 'index' => true, 'priority' => '0.7', 'changefreq' => 'monthly',
            'description' => 'Hubungi NatNetwork Synergy melalui email enquiry@natnetwork.net, telefon 011-1670 05857 atau borang pertanyaan.'],
        'builder.start' => ['title' => 'Mula Projek | NatNetwork Synergy', 'label' => 'Mula Projek', 'index' => true, 'priority' => '0.8', 'changefreq' => 'monthly',
            'description' => 'Ceritakan apa yang anda mahu capai. Pilih pakej sendiri atau jawab soalan dalam bahasa mudah untuk menyusun skop projek anda.'],
        'affiliate.register' => ['title' => 'Daftar Affiliate | NatNetwork Synergy', 'label' => 'Daftar Affiliate', 'index' => true, 'priority' => '0.5', 'changefreq' => 'monthly',
            'description' => 'Daftar sebagai affiliate NatNetwork Synergy dan jana pendapatan melalui pemasaran affiliate.'],
        'partner.register' => ['title' => 'Daftar Program Partnership | NatNetwork Synergy', 'label' => 'Program Partnership', 'index' => true, 'priority' => '0.5', 'changefreq' => 'monthly',
            'description' => 'Sertai Program Partnership NatNetwork Synergy. Sebahagian daripada setiap jualan pelanggan yang disahkan diagih kepada partner mengikut nisbah modal.'],
        'register' => ['title' => 'Daftar | NatNetwork Synergy', 'label' => 'Daftar', 'index' => false, 'priority' => '0.3', 'changefreq' => 'monthly',
            'description' => 'Daftar akaun NatNetwork Synergy sebagai client, affiliate atau partner.'],
    ],

    // Laluan yang tidak perlu dirangkak (robots.txt). Halaman login dibiarkan boleh dirangkak supaya tag noindex dibaca.
    'robots' => [
        'disallow' => ['/admin', '/client/', '/affiliate/', '/partner/', '/billing/', '/start-project/specification'],
        'allow' => ['/client/login', '/affiliate/login', '/affiliate/register', '/partner/login', '/partner/register'],
    ],
];
