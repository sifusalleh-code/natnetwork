<?php

// Template milestone (master spec §10.1) dan item kandungan (§11). Jumlah weight setiap template = 100.
// client_label: Maklumat Projek, Perancangan, Reka Bentuk, Pembangunan, Semakan Anda, Revision, Pelancaran.
return [
    'website' => [
        'label' => 'Website Development',
        'milestones' => [
            ['Requirement Confirmed', 'Maklumat Projek', 5], ['Project Planning', 'Perancangan', 5], ['Structure & UI Design', 'Reka Bentuk', 15],
            ['Core Development', 'Pembangunan', 30], ['Content/Main Functions', 'Pembangunan', 20], ['Internal Testing', 'Semakan Anda', 5],
            ['Revision', 'Revision', 10], ['Deployment', 'Pelancaran', 5], ['Final Completion', 'Pelancaran', 5],
        ],
        'content' => [
            ['Logo syarikat', 'FILE', true], ['Nama & pengenalan syarikat', 'INFO', true], ['Maklumat hubungan', 'INFO', true],
            ['Senarai servis / produk', 'INFO', true], ['Gambar / portfolio', 'FILE', false],
        ],
    ],
    'landing' => [
        'label' => 'Landing Page',
        'milestones' => [
            ['Requirement', 'Maklumat Projek', 10], ['Design/Structure', 'Reka Bentuk', 20], ['Development', 'Pembangunan', 40],
            ['Content & Testing', 'Semakan Anda', 10], ['Revision', 'Revision', 10], ['Deployment & Completion', 'Pelancaran', 10],
        ],
        'content' => [
            ['Logo syarikat', 'FILE', true], ['Tawaran / mesej utama', 'INFO', true], ['Maklumat hubungan / WhatsApp', 'INFO', true], ['Gambar produk / servis', 'FILE', false],
        ],
    ],
    'ecommerce' => [
        'label' => 'E-Commerce',
        'milestones' => [
            ['Requirement Confirmed', 'Maklumat Projek', 5], ['Store Planning', 'Perancangan', 5], ['UI/Store Structure', 'Reka Bentuk', 10],
            ['Product System', 'Pembangunan', 15], ['Cart & Checkout', 'Pembangunan', 15], ['Order Management', 'Pembangunan', 10],
            ['Payment/Shipping Integration', 'Pembangunan', 15], ['Internal Testing', 'Semakan Anda', 5], ['Revision', 'Revision', 10],
            ['Deployment', 'Pelancaran', 5], ['Completion', 'Pelancaran', 5],
        ],
        'content' => [
            ['Logo & maklumat syarikat', 'FILE', true], ['Maklumat produk (nama, harga, kategori)', 'INFO', true], ['Gambar produk', 'FILE', true],
            ['Maklumat penghantaran & pembayaran', 'INFO', true], ['Terma & polisi kedai', 'INFO', false],
        ],
    ],
    'webapp' => [
        'label' => 'Custom Web Application',
        'milestones' => [
            ['Requirements & Workflow', 'Maklumat Projek', 10], ['Architecture/Planning', 'Perancangan', 10], ['UI/Core Structure', 'Reka Bentuk', 10],
            ['Core Modules', 'Pembangunan', 25], ['Main Workflow', 'Pembangunan', 15], ['Integration/Supporting Functions', 'Pembangunan', 10],
            ['Testing & Revision', 'Revision', 10], ['Deployment', 'Pelancaran', 5], ['Completion', 'Pelancaran', 5],
        ],
        'content' => [
            ['Aliran kerja / proses semasa', 'INFO', true], ['Contoh borang / dokumen sedia ada', 'FILE', false], ['Senarai pengguna & peranan', 'INFO', true],
        ],
    ],
    'ai' => [
        'label' => 'AI / Automation',
        'milestones' => [
            ['Requirement/Use Case', 'Maklumat Projek', 10], ['Workflow Planning', 'Perancangan', 10], ['Core Integration', 'Pembangunan', 20],
            ['Knowledge/Automation Setup', 'Pembangunan', 20], ['Main Functions', 'Pembangunan', 15], ['Internal Testing', 'Semakan Anda', 5],
            ['Refinement/Revision', 'Revision', 10], ['Deployment', 'Pelancaran', 5], ['Completion', 'Pelancaran', 5],
        ],
        'content' => [
            ['Bahan pengetahuan (FAQ, dokumen)', 'FILE', true], ['Aliran kerja yang hendak diautomasi', 'INFO', true], ['Akses akaun platform berkaitan', 'INFO', false],
        ],
    ],
    'integration' => [
        'label' => 'System/API Integration',
        'milestones' => [
            ['Requirement', 'Maklumat Projek', 10], ['Compatibility/API Review', 'Perancangan', 10], ['Integration Setup', 'Pembangunan', 20],
            ['Core Integration', 'Pembangunan', 30], ['Data/Workflow Validation', 'Semakan Anda', 10], ['Client Verification', 'Revision', 10],
            ['Production Deployment', 'Pelancaran', 5], ['Completion', 'Pelancaran', 5],
        ],
        'content' => [
            ['Dokumentasi API / sistem luar', 'FILE', true], ['Akses sandbox / kredensial ujian', 'INFO', true],
        ],
    ],

    // Pemetaan pakej/servis → template (boleh diubah admin semasa menghantar quotation).
    'package_map' => ['landing-page' => 'landing'],
    'service_map' => [
        'website-development' => 'website', 'e-commerce' => 'ecommerce', 'custom-web-applications' => 'webapp',
        'ai-automation' => 'ai', 'system-api-integration' => 'integration',
    ],

    // Hak sokongan (hari) mengikut nilai projek (master spec §14).
    'support_days' => [[2500, 7], [5000, 14], [10000, 30], [20000, 60], [null, 90]],

    // Muat naik fail projek.
    'upload_max_kb' => 20480,
    'upload_extensions' => ['jpg', 'jpeg', 'png', 'webp', 'gif', 'pdf', 'doc', 'docx', 'xls', 'xlsx', 'csv', 'txt', 'zip', 'ppt', 'pptx'],
];
