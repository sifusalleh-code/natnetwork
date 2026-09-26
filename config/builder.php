<?php

/*
| Paparan Guided Project Builder (UI sahaja). Soalan & pilihan jawapan kekal dalam
| database (builder_questions). Fail ini hanya menentukan susunan langkah, ikon dan
| visual. Soalan aktif yang tiada dalam mana-mana langkah dipaparkan dalam langkah "Nota".
|
| Foto: Unsplash (Unsplash License — bebas untuk kegunaan komersial). Nilai ialah ID foto
| images.unsplash.com; boleh diganti dengan laluan fail tempatan (contoh 'images/builder/x.webp').
*/
return [
    'photo_cdn' => 'https://images.unsplash.com/',

    // Jenis projek (soalan project_type) → servis dalam katalog Pricing, untuk menapis pakej yang dipaparkan.
    'project_type_services' => [
        'company-website' => ['website-development'],
        'promotion-landing-page' => ['website-development'],
        'online-store' => ['e-commerce'],
        'business-management-system' => ['custom-web-applications'],
        'ai-chatbot-automation' => ['ai-automation', 'system-api-integration'],
    ],

    'steps' => [
        ['key' => 'model', 'label' => 'Model', 'icon' => 'layers', 'section' => 'Pilih jenis projek',
            'subtitle' => 'Apakah yang anda mahu bina?',
            'info' => 'Pilih yang paling hampir dengan keperluan anda, kemudian pilih pakej.',
            'codes' => ['project_type']],
        ['key' => 'gaya', 'label' => 'Gaya', 'icon' => 'brush', 'section' => 'Pilih gaya visual',
            'subtitle' => 'Pilih gaya visual yang paling anda suka.',
            'info' => 'Gaya menentukan rupa keseluruhan projek — susun atur, tipografi dan suasana visual.',
            'codes' => ['design_style']],
        ['key' => 'warna', 'label' => 'Warna', 'icon' => 'palette', 'section' => 'Pilih warna tema',
            'subtitle' => 'Pilih warna utama yang anda suka untuk projek ini.',
            'info' => 'Warna akan digunakan pada reka bentuk keseluruhan seperti butang, tajuk, latar belakang dan elemen visual.',
            'codes' => ['colour_preference', 'colour_custom']],
        ['key' => 'logo', 'label' => 'Logo', 'icon' => 'badge', 'section' => 'Status logo',
            'subtitle' => 'Adakah anda sudah mempunyai logo?',
            'info' => 'Jika anda sudah ada logo, muat naik fail supaya reka bentuk dapat dipadankan dengan jenama anda.',
            'codes' => ['content_logo']],
        ['key' => 'image', 'label' => 'Image', 'icon' => 'image', 'section' => 'Pilih jenis gambar',
            'subtitle' => 'Gambar jenis apa yang akan digunakan dalam projek ini?',
            'info' => 'Anda boleh pilih lebih daripada satu.',
            'codes' => ['content_images', 'content_text']],
        ['key' => 'domain', 'label' => 'Domain', 'icon' => 'globe', 'section' => 'Status domain',
            'subtitle' => 'Adakah anda sudah mempunyai domain?',
            'info' => 'Domain ialah alamat website anda, contohnya namabisnes.com.my.',
            'codes' => ['content_domain', 'domain_name', 'content_hosting']],
        ['key' => 'rujukan', 'label' => 'Rujukan', 'icon' => 'link', 'section' => 'Rujukan',
            'subtitle' => 'Adakah anda mempunyai website atau design rujukan?',
            'info' => 'Rujukan membantu kami memahami gaya dan fungsi yang anda suka.',
            'codes' => ['reference_available', 'reference_website', 'reference_likes']],
        ['key' => 'addon', 'label' => 'Add-on', 'icon' => 'wallet', 'section' => 'Pilih add-on (pilihan)',
            'subtitle' => 'Tambah fungsi lain untuk projek anda.',
            'info' => 'Harga add-on dicampur terus kepada kos pakej. Anda boleh buang add-on atau tukar pakej bila-bila masa sebelum Master Specification diluluskan.',
            'codes' => []],
        ['key' => 'result', 'label' => 'Result', 'icon' => 'target', 'section' => null,
            'subtitle' => 'Apa hasil yang anda mahu capai?',
            'info' => 'Terangkan dalam bahasa mudah.',
            'placeholder' => 'Contoh: Tingkatkan jualan, dapatkan lebih banyak pelanggan, atau bina imej profesional.',
            'codes' => ['summary']],
        ['key' => 'nota', 'label' => 'Nota', 'icon' => 'note', 'section' => null,
            'subtitle' => 'Ada perkara lain yang kami perlu tahu?',
            'info' => 'Langkah ini pilihan — anda boleh teruskan tanpa mengisi.',
            'placeholder' => 'Contoh: tarikh sasaran, bahasa website, atau fungsi khas yang diperlukan.',
            'codes' => ['additional_customer_requirement']],
    ],

    // Paparan kad pilihan mengikut soalan: icon | photo | colour | chip (lalai).
    'option_display' => [
        'project_type' => 'icon', 'design_style' => 'photo', 'colour_preference' => 'colour',
        'content_logo' => 'icon', 'content_images' => 'icon', 'content_domain' => 'icon',
        'reference_available' => 'icon', 'budget' => 'icon',
    ],

    'option_icons' => [
        'project_type' => ['company-website' => 'building', 'online-store' => 'cart', 'promotion-landing-page' => 'megaphone', 'business-management-system' => 'grid', 'ai-chatbot-automation' => 'bot', 'upgrade-existing' => 'refresh', 'unsure' => 'help'],
        'content_logo' => ['available' => 'check-badge', 'new-logo' => 'sparkle', 'upgrade-logo' => 'refresh', 'unsure' => 'help'],
        'content_images' => ['own-photos' => 'camera', 'stock-photos' => 'image', 'product-photos' => 'box', 'location-photos' => 'building', 'people-photos' => 'users', 'recommend' => 'sparkle'],
        'content_domain' => ['have-domain' => 'globe', 'no-domain' => 'plus', 'suggest-domain' => 'sparkle', 'unsure' => 'help'],
        'reference_available' => ['yes' => 'check-badge', 'no' => 'close', 'recommend' => 'sparkle'],
        'budget' => ['under-1000' => 'wallet', '1000-3000' => 'wallet', '3000-5000' => 'wallet', '5000-10000' => 'wallet', 'over-10000' => 'wallet', 'unsure' => 'help'],
    ],

    'colours' => [
        'biru' => ['desc' => 'Profesional & dipercayai', 'swatch' => ['#0b5fd6', '#1683e6', '#43a6f0', '#a9d4fa'], 'photo' => 'photo-1617761141732-d481912af1a9'],
        'hijau' => ['desc' => 'Segar & moden', 'swatch' => ['#0f7a3d', '#1a9b52', '#34c077', '#a7e3c1'], 'photo' => 'photo-1516528387618-afa90b13e000'],
        'ungu' => ['desc' => 'Kreatif & unik', 'swatch' => ['#5b21b6', '#7c3aed', '#a78bfa', '#ddd0fd'], 'photo' => 'photo-1595562161314-4ed3f3503826'],
        'merah' => ['desc' => 'Berani & menyerlah', 'swatch' => ['#b91c1c', '#dc2626', '#ef4444', '#fca5a5'], 'photo' => 'photo-1499382926300-90a93d2d9990'],
        'oren' => ['desc' => 'Mesra & bertenaga', 'swatch' => ['#c2410c', '#ea580c', '#fb923c', '#fed7aa'], 'photo' => 'photo-1444090542259-0af8fa96557e'],
        'kelabu' => ['desc' => 'Minimal & elegan', 'swatch' => ['#374151', '#4b5563', '#9ca3af', '#d1d5db'], 'photo' => 'photo-1628744876497-eb30460be9f6'],
        'coklat' => ['desc' => 'Semula jadi & klasik', 'swatch' => ['#78350f', '#92400e', '#b45309', '#e7c9a9'], 'photo' => 'photo-1546484396-fb3fc6f95f98'],
        'lain-lain' => ['desc' => 'Warna tersuai', 'swatch' => ['#0b5fd6', '#1683e6', '#43a6f0', '#e8f1fb'], 'photo' => 'photo-1550684848-fac1c5b4e853'],
    ],

    'styles' => [
        'modern' => 'photo-1483366774565-c783b9f70e2c',
        'corporate' => 'photo-1497366754035-f200968a6e72',
        'minimal' => 'photo-1617326021886-53d6be1d7154',
        'premium' => 'photo-1455593984172-9f753a2e1ebd',
        'creative' => 'photo-1513077202514-c511b41bd4c7',
        'technology' => 'photo-1558494949-ef010cbdcc31',
        'recommend' => 'photo-1517048676732-d65bc937f952',
    ],

    'photos' => [
        'hero' => 'photo-1497215728101-856f4ea42174',
        'entry_direct' => 'photo-1460925895917-afdab827c52f',
        'entry_guided' => 'photo-1517048676732-d65bc937f952',
        // Foto utama "Contoh paparan" mengikut jenis projek / jenis gambar.
        'preview' => [
            'default' => 'photo-1523477593243-78bbf626fd3b',
            'company-website' => 'photo-1523477593243-78bbf626fd3b',
            'online-store' => 'photo-1441984904996-e0b6ba687e04',
            'promotion-landing-page' => 'photo-1505740420928-5e560c06d30e',
            'business-management-system' => 'photo-1551288049-bebda4e38f71',
            'ai-chatbot-automation' => 'photo-1515879218367-8466d910aaa4',
            'upgrade-existing' => 'photo-1460925895917-afdab827c52f',
            'product-photos' => 'photo-1505740420928-5e560c06d30e',
            'location-photos' => 'photo-1497366811353-6870744d04b2',
            'people-photos' => 'photo-1522071820081-009f0129c71c',
        ],
    ],
];
