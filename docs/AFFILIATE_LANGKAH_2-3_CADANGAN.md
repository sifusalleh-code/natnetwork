# CADANGAN LANGKAH 2–3 — ASAS DATA, PENJEJAKAN, PENDAFTARAN & PROFIL AFFILIATE

Status: **Cadangan untuk kelulusan. Tiada kod, migration atau fail projek diubah.**
Rujukan: `AFFILIATE_SPEC.md`, `AGENTS.md`, audit projek 24 Sep 2026, GrowBiz `src/member-profile-page*.mjs` + `migrations/004, 027, 028`.

---

## A. Keputusan disahkan (24 Sep 2026)

| Perkara | Keputusan |
|---|---|
| Pencetus komisyen Released | Bayaran penuh disahkan melalui **callback Billplz di server** (Billing Engine), bukan tindakan browser pelanggan. Menggantikan ayat "pelanggan sahkan bayaran penuh melalui portal" dalam spec 1.5 / 6.3. |
| Susunan kerja | Bina langkah 2–3 dahulu. Enjin komisyen (langkah 4) disambung selepas Billing wujud. |
| Login affiliate | OTP emel, guna semula `EmailOtpService` sedia ada. |
| Akaun affiliate | Jadual `affiliates` + guard `affiliate` berasingan daripada `users`. Satu emel boleh jadi pelanggan dan affiliate. |

---

## B. Perubahan database dicadangkan (migration baharu sahaja, jadual sedia ada tidak diubah)

### B1. `affiliates` — akaun + profil
| Kolum | Jenis | Nota |
|---|---|---|
| id | bigint PK | |
| name | string | Nama penuh (readonly selepas daftar, ikut GrowBiz) |
| email | string unique | |
| email_verified_at | timestamp null | Diisi selepas OTP pertama |
| username | string(12) unique null | 3–12 huruf kecil, **dikunci selepas disimpan** (GrowBiz). Dicadang sebagai kod link affiliate: `/?ref={username}` |
| phone | string null | No. Telefon / WhatsApp |
| state | string(40) null | Negeri (senarai 16 negeri GrowBiz) |
| avatar_path | string null | Gambar diri sebenar — **wajib**. Disimpan di disk private, bukan base64 dalam DB (AGENTS.md §10) |
| bank_account_holder | string(120) null | |
| bank_name | string(120) null | Senarai bank GrowBiz |
| bank_account_number | text null | **Encrypted** (Laravel `encrypted` cast) |
| bank_account_last4 | string(4) null | Untuk paparan bertopeng |
| facebook_url / instagram_url / twitter_url | string(300) null | Pilihan |
| status | string default `ACTIVE` | |
| profile_completed_at | timestamp null | Diisi server bila semua medan wajib lengkap |
| timestamps | | |

### B2. `affiliate_clicks` — rekod setiap klik link
id, affiliate_id FK, visitor_token (uuid dari cookie), source (facebook/instagram/whatsapp/tiktok/telegram/x/linkedin/direct/other), device_type (mobile/desktop), referrer (null), landing_path, ip_hash, user_agent (null), clicked_at. Index: (affiliate_id, clicked_at).

Nota: kolum `affiliate_poster_id` ditambah dalam migration langkah 6 (Pengurusan Poster), bukan sekarang.

### B3. `affiliate_client_links` — kaitan kekal pelanggan ↔ affiliate
id, affiliate_id FK, customer_user_id FK `users` **unique** (seorang pelanggan = seorang affiliate, kekal), affiliate_click_id FK null, linked_at.

### B4. `affiliate_settings` — tetapan admin
Satu baris: `cookie_days` (default 30). Jadual kadar komisyen bertingkat ditambah dalam langkah 4.

---

## C. Perubahan kod sedia ada yang diperlukan (minimum)

| Fail | Perubahan | Kesan kepada fungsi sedia ada |
|---|---|---|
| `config/auth.php` | Tambah guard `affiliate` + provider `affiliates` | Tiada; guard `web`/`client` kekal |
| `EmailOtpChallenge` | Tambah pemalar `PURPOSE_AFFILIATE_LOGIN` | Tiada |
| `EmailOtpService` | Terima parameter `purpose` (default `CLIENT_LOGIN`). Untuk purpose affiliate: `user_id` = null, cari `Affiliate` bukan `User` | Panggilan sedia ada tidak berubah kerana default kekal `CLIENT_LOGIN` |
| `ProjectBuilderController::verify` | Selepas `User::firstOrCreate`, **jika akaun pelanggan baru dicipta** dan cookie affiliate sah → cipta `affiliate_client_links` | Aliran builder kekal; hanya tambah satu panggilan servis |
| `routes/web.php` | Tambah kumpulan route `/affiliate/*` | Route sedia ada tidak disentuh |
| `bootstrap/app.php` | Daftar middleware `affiliate.profile` (redirect ke profil jika `profile_completed_at` null) + middleware tangkap `?ref=` | Tiada |

Kod baharu: `app/Engines/Affiliate/` (Models, Services), `AffiliateAuthController`, `AffiliateProfileController`, view `resources/views/affiliate/*`, ujian `tests/Feature/Affiliate*`.

---

## D. Aliran

1. `/affiliate/register` — nama + emel → OTP dihantar
2. `/affiliate/verify` — OTP sah → `email_verified_at` diisi → log masuk guard `affiliate` → redirect ke profil
3. `/affiliate/profile` — medan wajib GrowBiz: gambar diri, username, telefon, negeri, penama akaun, bank, no. akaun. Server kira `profile_completed_at`.
4. `/affiliate/dashboard`, `/studio-poster`, `/wallet` — dilindungi `auth:affiliate` + `affiliate.profile`. Halaman ini hanya rangka kosong dalam langkah ini; isi dibina dalam langkah 6, 8, 9.
5. `/affiliate/login` — emel → OTP (sama seperti pelanggan).

Penjejakan: pelawat buka mana-mana halaman awam dengan `?ref={username}` → rekod `affiliate_clicks` → cookie `nn_aff` (affiliate_id + click_id, tempoh `cookie_days`) ditimpa oleh klik terbaru → pelanggan baru sahkan emel di Builder → kaitan kekal.

---

## E. Perkara yang perlu pengesahan sebelum implementasi

1. **Engine baharu.** Affiliate tiada dalam 12 engine `AGENTS.md` §3. Cadangan: engine baharu `Affiliate`. Atau letak di bawah `Sales`?
2. **Format link.** `?ref={username}` (ikut GrowBiz), atau kod rawak?
3. **"Buka portal".** Dicadang bermaksud: kali pertama akaun pelanggan dicipta dan emel disahkan melalui Builder. Pelanggan sedia ada yang klik link kemudian **tidak** dikaitkan. Setuju?
4. **Penama akaun bank.** Spec: "atas nama pemilik akaun affiliate". Perlukah sistem menyekat jika penama ≠ nama penuh? GrowBiz tidak menyekat. Tanpa arahan, tiada semakan ditambah.
5. **Medan pendaftaran.** Nama + emel sahaja? Atau tambah telefon (telefon juga ada dalam profil)?
6. **Layout portal affiliate.** Guna sidebar gaya GrowBiz (Dashboard, Wallet, Studio Poster, Profil Saya) dengan warna/brand NatNetwork sedia ada (`resources/css/app.css`)?

---

## F. Isu dikesan di luar skop (tiada perubahan dibuat)

- `AGENTS.md` §20 masih menyatakan projek "GREENFIELD / EMPTY PROJECT" sedangkan Laravel 12 sudah dibina. Isu dikesan tetapi berada di luar skop. Tiada perubahan dibuat.

---

## G. STATUS PELAKSANAAN (24 Sep 2026)

Diluluskan dan dilaksanakan dengan pindaan berikut:
- Engine baharu `Affiliate`. Link `?ref={username}`.
- Kolum `bank_account_holder` **tidak** dicipta — penama akaun = nama profil; notis wajib atas nama pemilik dipaparkan.
- Pendaftaran: nama, emel, no. telefon. Emel affiliate sedia ada → terus ke login. Akaun affiliate hanya dicipta selepas OTP disahkan.
- Pelanggan sedia ada tidak dikaitkan dengan affiliate. Card affiliate dipaparkan di **semua dashboard portal bukan affiliate** — komponen `<x-affiliate-offer-card>` sedia; dipasang bila dashboard Client/Investor dibina.
- Dashboard, Studio Poster dan Wallet: rangka halaman sahaja (isi dalam langkah 6, 8, 9).
