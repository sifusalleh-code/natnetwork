# NatNetwork Synergy

Platform operasi digital untuk perniagaan perkhidmatan web dan teknologi — laman syarikat awam,
Guided Project Builder, quotation & pembayaran (Billplz), pengurusan projek, portal pelanggan,
program affiliate, dan pentadbiran operasi — dalam satu aplikasi **Laravel 12 Modular Monolith**.

## Dokumen rujukan

Baca dahulu sebelum membangunkan apa-apa dalam repo ini:

1. [`AGENTS.md`](AGENTS.md) — perlembagaan projek (prinsip, peranan, engine ownership, peraturan
   wajib). Ini mengatasi semua dokumen lain apabila berlaku percanggahan.
2. [`MASTER_SPECIFICATION.md`](MASTER_SPECIFICATION.md) dan
   [`NATNETWORK_V1_MASTER_SPECIFICATION.md`](NATNETWORK_V1_MASTER_SPECIFICATION.md) — spesifikasi
   keperluan produk yang diluluskan.
3. [`AFFILIATE_SPEC.md`](AFFILIATE_SPEC.md) — spesifikasi program affiliate.
4. [`docs/OWNER_DECISIONS_2026_09_25.md`](docs/OWNER_DECISIONS_2026_09_25.md) — keputusan Owner
   terkini yang mengatasi bahagian dokumen di atas apabila bercanggah.
5. [`docs/PHASE_TRACKER.md`](docs/PHASE_TRACKER.md) — status pelaksanaan mengikut 10 fasa `AGENTS.md` §19.

Jangan membina berdasarkan andaian, chat, draf tidak diluluskan atau versi lapuk — lihat `AGENTS.md` §1.

## Seni bina

- **Corak:** Modular Monolith, satu pangkalan data.
- **Engine perniagaan** (`app/Engines/*`): Identity, Sales, Pricing, Scheduling, Billing, Project,
  ProjectContent, Communication, Cms, Audit, Analytics, Affiliate, Partnership.
- **Adapter luaran** (`app/Adapters/*`): Billplz (bayaran), Resend (e-mel), Email (OTP). Adapter
  tidak menentukan kebenaran perniagaan — lihat `AGENTS.md` §3.
- **Runtime:** PHP 8.2+, Laravel 12, SQLite (fail tunggal — lihat
  `docs/OWNER_DECISIONS_2026_09_25.md` #2/#4), Blade + Tailwind CSS + Alpine.js + Vite.

## Pemasangan pembangunan tempatan

```bash
composer install
cp .env.example .env
php artisan key:generate
touch database/database.sqlite
php artisan migrate --seed
npm install && npm run build   # atau `npm run dev` semasa membangun
composer dev                   # server + queue + log + vite serentak
```

## Ujian

```bash
composer test
# atau terus:
php artisan test
```

Setiap task pembangunan mesti melalui: audit sedia ada → laksana skop diluluskan → migration jika
perlu → function test → security test → regression test → laporan → kelulusan (`AGENTS.md` §15).
Jangan anggap task selesai hanya kerana kod compile.

## Deploy

Lihat [`deploy/deploy.sh`](deploy/deploy.sh) (VPS AlmaLinux, dijalankan sebagai root, boleh
dijalankan berulang kali dengan selamat). Ia membuat sandaran pangkalan data secara automatik
sebelum setiap migrasi.

## Status projek

Ini **bukan** projek greenfield/kosong lagi — lihat `docs/PHASE_TRACKER.md` untuk status sebenar
setiap fasa dan `docs/OWNER_DECISIONS_2026_09_25.md` untuk keputusan terkini yang mengatasi bahagian
lapuk dalam dokumen spesifikasi asal (contoh: §20 "greenfield/kosong" dalam
`NATNETWORK_V1_MASTER_SPECIFICATION.md` sudah tidak tepat).
