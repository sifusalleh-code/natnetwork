# BILLING & BILLPLZ — TERAS (V1)

Status: **Dilaksanakan 24 Sep 2026.** Skop: teras Billing sahaja. Sambungan kepada Quotation Accepted, Slot Hold dan Order dibuat apabila modul tersebut dibina.

## Keputusan disahkan

| Perkara | Keputusan |
|---|---|
| Login Admin | Berasingan (jadual `admins`, guard `admin`), **password + 2FA TOTP wajib** (AGENTS.md §9). 2FA didaftarkan semasa log masuk pertama. |
| Mod pembayaran | Admin pilih **SANDBOX** atau **PRODUCTION** di Admin → Billing → Tetapan Billplz. Default: SANDBOX. |
| Rekod sandbox | Disimpan **berasingan** (`is_sandbox = true`), nombor **TEST-INV / TEST-RCP**. Tidak masuk hasil, laporan, kapasiti atau komisyen affiliate. Boleh dipadam oleh admin. |
| Rekod production | Nombor **NAT-INV-YYYY-NNNN / NAT-RCP-YYYY-NNNN**. Tidak boleh dipadam. |
| Kunci API | Diisi di halaman admin, disimpan **encrypted** dalam pangkalan data, dipaparkan bertopeng (••••1234). |
| Bukti bayaran | **Hanya callback Billplz bertandatangan (X-Signature) di pelayan.** Redirect pelayar tidak mengubah status. |

## Aliran

```
Invois (ISSUED) → Bil Billplz (Payment PENDING) → pelanggan bayar
→ callback POST /billing/billplz/callback
   → sahkan X-Signature (kunci ikut mod bayaran itu)
   → sahkan collection_id + amount + paid_amount
   → PAID + invois PAID/PARTIALLY_PAID + resit (sekali sahaja, idempotent)
   → jumlah tidak sepadan → REVIEW_REQUIRED (tiada resit)
   → paid=false → FAILED (bil yang sama masih boleh dibayar)
→ pelanggan kembali ke /billing/billplz/return (paparan status sahaja)
```

- Klik berganda menggunakan semula bil PENDING yang sama.
- Invois dicipta dalam satu mod hanya boleh dibayar semasa mod itu aktif.
- Perubahan mod: wajib kunci lengkap, sebab dan pengesahan; direkod dalam `audit_logs`.
- Perubahan kunci, log masuk admin, daftar 2FA dan pemadaman sandbox direkod dalam `audit_logs` (tidak boleh diubah/dipadam).

## Setup

1. `php artisan migrate`
2. `php artisan admin:create emel@domain.com "Nama Admin"` (password min. 12 aksara, diminta secara interaktif)
3. Log masuk `/admin/login` → daftar 2FA dengan Google/Microsoft Authenticator (masukkan kunci persediaan).
4. Billplz Sandbox (billplz-sandbox.com) → Settings: salin **API Secret Key**, **X Signature Key** (aktifkan X Signature), cipta **Collection** dan salin ID-nya.
5. Admin → Billing → Tetapan Billplz → isi Kunci SANDBOX.
6. Admin → Billing → Uji Aliran Bayaran → cipta invois ujian & bayar.
7. Untuk go-live: isi Kunci PRODUCTION (billplz.com) → Aktifkan PRODUCTION (sebab + pengesahan).

**Penting:** callback memerlukan domain awam HTTPS (`APP_URL` mesti betul). Callback tidak sampai ke `localhost`; untuk ujian tempatan guna tunnel (contoh: ngrok / Cloudflare Tunnel) dan tetapkan `APP_URL` kepada URL tunnel.

2FA hilang: `php artisan admin:reset-2fa emel@domain.com`.

## Belum termasuk (ikut skop "teras sahaja")

- Invois automatik daripada Quotation Accepted + Slot Held; order confirmed pada deposit 50%.
- Invois akhir 80%, Additional Charge, credit/adjustment, void.
- Refund (Billplz tiada API refund bil; refund akan direkod sebagai tindakan admin terkawal).
- PDF invois/resit, emel invois/resit (Resend), halaman Billing dalam portal pelanggan.
- Tetapan cukai (tax_amount = 0 buat masa ini).
- Pelepasan komisyen affiliate (langkah 4 AFFILIATE_SPEC) — hanya bayaran production PAID akan mencetuskannya.
