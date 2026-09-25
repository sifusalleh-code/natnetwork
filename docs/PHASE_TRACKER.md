# Penjejak Fasa Pembangunan — NatNetwork

Struktur ini melaksanakan `AGENTS.md` §19 (Phase Control): 10 fasa mengikut susunan kebergantungan,
setiap fasa disemak (test → regression → report) sebelum menunggu kelulusan Owner untuk fasa
seterusnya. Status di bawah dikemas kini berdasarkan Audit Projek 25 Sep 2026 dan kerja susulan
mengikut `docs/OWNER_DECISIONS_2026_09_25.md`.

Legenda: ✅ Selesai & diuji · 🟡 Sebahagian · ⛔ Belum dibina

Jalankan `php artisan phase:status` untuk semakan automatik ringkas (migrasi, direktori engine,
ujian) — ini **bukan** pengganti kelulusan manual Owner, hanya validator teknikal asas.

| Fasa | Skop (`AGENTS.md` §19) | Status | Nota |
|---|---|---|---|
| 1. Foundation | Struktur modular, auth asas, audit/event, CMS shell, settings | ✅ | Identity (OTP client, admin password+2FA), AuditLog immutable, engine scaffold wujud |
| 2. Public & Commercial | Laman awam, services/pricing, contact & lead intake | 🟡 | Laman awam & katalog harga siap. Borang Contact hanya menghantar e-mel — **tiada Lead Engine/rekod status lead** (NEW→CONVERTED) seperti MS §10 |
| 3. Builder & Client | Builder, OTP, akaun client, Master Specification | ✅ | Dua laluan Builder, MS DRAFT→APPROVED→SUPERSEDED dengan snapshot, autosave 30 hari |
| 4. Sales | Quotation, manual review, acceptance | ✅ | Auto-quote untuk pakej tetap, REVIEW_REQUIRED untuk kompleks, acceptance idempotent |
| 5. Billing & Scheduling | Pricing snapshot, Billplz, deposit, slot/hold/reservation | ✅ | Callback disahkan (signature/amount/idempotent), kapasiti & hold berfungsi, sandbox/production diasingkan sepenuhnya (25 Sep 2026) |
| 6. Orders & Projects | Order conversion, project lifecycle, milestone, content/files | ✅ | START PROJECT terkawal, progress daripada milestone (100%), invois akhir sekali pada 80%, order wajib ada slot |
| 7. Client Portal & Communication | Dashboard, dokumen, action-required, penghantaran Resend | ✅ | 9 menu V1 wujud; notifikasi portal & e-mel transaksi (OTP, contact) berfungsi |
| 8. Recurring Services & Admin | Subscription (hosting/maintenance), admin operasi | ⛔ | **Subscription Engine tidak dibina langsung** — tiada rekod hosting/maintenance/renewal (MS §17). Admin: Sales/Projects/Billing/Support/Affiliate/Partner siap; Admin Services/Pricing UI, Notifications broadcast dan Activity (viewer audit log) belum wujud |
| 9. Production Readiness | Hardening, backup/restore, monitoring, deployment checks | 🟡 | Sandaran automatik sebelum migrasi ditambah (25 Sep 2026). Belum ada: ujian restore berkala, monitoring/alerting, laluan sitemap/robots disahkan produksi |

## Fasa yang telah dilangkau sebahagian (untuk makluman)

Kod semasa merangkumi kerja fasa 1–7 dan sebahagian fasa 6/9 secara serentak (bukan berturutan
mengikut kelulusan demi fasa seperti diwajibkan `AGENTS.md` §19). Ini direkodkan sebagai fakta
sejarah, bukan untuk dibongkar — kerja sedia ada telah diaudit dan berfungsi (130 ujian lulus).
Mulai kerja susulan (25 Sep 2026 seterusnya), setiap perubahan besar perlu kembali kepada disiplin
fasa: audit → skop diluluskan → migration → ujian → laporan → kelulusan sebelum fasa/skop seterusnya.

## Kerja tertunggak utama (di luar 13 keputusan 25 Sep 2026)

Item berikut dikesan semasa audit tetapi **di luar skop 13 keputusan** yang telah diluluskan —
memerlukan kelulusan skop berasingan sebelum dilaksanakan (`AGENTS.md` §1):

- Subscription Engine (hosting/maintenance/renewal) — Fasa 8.
- Lead Engine formal (status, dedup e-mel, UTM) — Fasa 2.
- Funnel Analytics penuh (`builder_started` … `project_completed`) — MS §20.
- Admin: Services/Pricing UI, Notifications broadcast, Activity (audit log viewer) — Fasa 8/9.
- PDF quotation/invoice/receipt.
- E-mel transaksi tambahan (quotation, invois, resit, reminder) selain OTP/contact.
- Laravel Scheduler untuk reminder (Payment Due, Quotation Expiring, dsb).
- `baseline_specification_version` / `latest_approved_specification_version` eksplisit pada projek.
