<?php

return [
    'title' => 'Draf Terma Jualan Projek NatNetwork',
    'version' => '0.1',
    'approval_status' => 'DRAFT / OWNER REVIEW REQUIRED',
    'currency' => 'MYR',
    'terms' => [
        ['title' => 'Harga dan skop', 'status' => 'REQUIREMENT LOCKED', 'body' => 'Harga quotation ialah snapshot pakej, add-on dan caj berulang yang dinyatakan pada quotation tersebut. Skop kontrak ialah Approved Master Specification versi yang diikat pada quotation, termasuk inclusions dan exclusions yang disenaraikan.'],
        ['title' => 'Deposit dan pengesahan pesanan', 'status' => 'REQUIREMENT LOCKED', 'body' => 'Deposit sebanyak 50% diperlukan. Quotation, slot hold atau permintaan bayaran sahaja belum menjadi pesanan. Pesanan hanya ORDER CONFIRMED selepas deposit kumulatif mencapai 50% dan bayaran disahkan oleh server.'],
        ['title' => 'Baki bayaran', 'status' => 'REQUIREMENT LOCKED', 'body' => 'Baki 50% projek asal dicetuskan apabila kemajuan milestone melepasi 80%, mengikut rekod Billing dan Project yang sah.'],
        ['title' => 'Perubahan skop', 'status' => 'REQUIREMENT LOCKED', 'body' => 'Selepas quotation diterima, perubahan bukan ordinary edit. Pelanggan membuat Request Change; Admin menilai sama ada ia revision dalam skop atau Additional Work. Additional Work memerlukan kelulusan dan rekod harga yang berasingan.'],
        ['title' => 'Maklumat dan bahan pelanggan', 'status' => 'REQUIREMENT LOCKED', 'body' => 'Bahan yang diminta melalui Content & File workspace perlu melalui semakan Admin. Upload atau submission sahaja tidak mengesahkan bahan diterima dan tidak secara automatik menyambung jadual projek.'],
        ['title' => 'Caj pihak ketiga dan perkhidmatan berulang', 'status' => 'REQUIREMENT LOCKED', 'body' => 'Yuran pembangunan adalah berasingan daripada hosting, maintenance, managed service, penggunaan AI/API dan caj pihak ketiga yang relevan. Hanya caj yang dinyatakan pada quotation atau rekod berasingan yang diluluskan menjadi tanggungan pelanggan.'],
        ['title' => 'Cukai', 'status' => 'DRAFT / OWNER REVIEW REQUIRED', 'body' => 'Quotation perlu menyatakan rawatan cukai yang benar pada tarikh dikeluarkan. Sebarang cukai hanya dipaparkan atau dicaj apabila NatNetwork diwajibkan atau berdaftar untuk mengenakannya di bawah undang-undang yang terpakai.'],
        ['title' => 'Tempoh sah quotation', 'status' => 'DRAFT / OWNER REVIEW REQUIRED', 'body' => 'Tempoh sah mestilah dinyatakan pada setiap quotation sebelum ia dihantar. Tiada tempoh hari standard ditetapkan dalam draf ini.'],
        ['title' => 'Hak milik dan terma undang-undang', 'status' => 'DRAFT / OWNER REVIEW REQUIRED', 'body' => 'Hak penggunaan, pemindahan hak milik, had liabiliti, pembatalan dan terma undang-undang perlu dimuktamadkan dalam Project Terms yang disemak secara profesional sebelum quotation boleh diterima secara production.'],
    ],
];
