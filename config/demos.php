<?php

/*
| Galeri Contoh (/demo). Satu sumber data untuk kad demo.
| Susunan senarai = susunan "Terkini" (item pertama paling baharu).
| 'image': ID foto Unsplash (images.unsplash.com, Unsplash License) atau laluan fail dalam public/.
| 'headline': tajuk hero sebenar daripada demo tersebut (dipaparkan atas imej kad).
*/
return [
    'categories' => [
        'landing-page' => 'Landing Page',
        'website-bisnes' => 'Website Bisnes',
        'e-commerce' => 'E-Commerce',
        'hospitaliti' => 'Hospitaliti',
        'pendidikan' => 'Pendidikan',
        'organisasi' => 'Organisasi',
        'lain-lain' => 'Lain-lain',
    ],

    'hero_photo' => 'photo-1497215728101-856f4ea42174',

    'items' => [
        [
            'id' => 'promosi-service',
            'title' => 'Promosi Service',
            'category' => 'landing-page',
            'description' => 'Contoh halaman promosi yang fokus kepada penerangan servis, keyakinan pelanggan dan tindakan seterusnya.',
            'url' => '/demo/web/landing-page/design1/',
            'image' => 'photo-1460925895917-afdab827c52f',
            'image_alt' => 'Laptop di atas meja kerja memaparkan website',
            'headline' => 'Solusi digital yang buat bisnes anda menang online',
            'tags' => ['landing page', 'promosi', 'servis', 'jualan'],
        ],
        [
            'id' => 'growbiz',
            'title' => 'GrowBiz.my',
            'category' => 'website-bisnes',
            'description' => 'Contoh website bisnes untuk menerangkan nilai produk, fungsi platform, pakej dan kelebihan kepada pelanggan.',
            'url' => '/demo/web/business/design1/',
            'image' => 'photo-1523477593243-78bbf626fd3b',
            'image_alt' => 'Bangunan pejabat moden berdinding kaca',
            'headline' => 'Kembangkan bisnes anda tanpa pening kepala',
            'tags' => ['business website', 'korporat', 'platform', 'pakej'],
        ],
        [
            'id' => 'rumah-rimba-homestay',
            'title' => 'Rumah Rimba Homestay',
            'category' => 'hospitaliti',
            'description' => 'Contoh website permulaan yang kemas untuk memperkenalkan perniagaan, suasana dan pilihan pelanggan.',
            'url' => '/demo/web/starter/design1/',
            'image' => 'images/demo/rumah-rimba-homestay.webp',
            'image_small' => 'images/demo/rumah-rimba-homestay-640.webp',
            'image_alt' => 'Homestay kayu di kaki bukit ketika senja',
            'headline' => 'Rehat tenang di pangkuan hutan hijau',
            'tags' => ['starter website', 'homestay', 'tempahan', 'pelancongan'],
        ],
    ],
];
