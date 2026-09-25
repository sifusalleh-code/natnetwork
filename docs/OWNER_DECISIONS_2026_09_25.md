# Keputusan Owner — 25 September 2026

Dokumen ini merekodkan keputusan rasmi Owner terhadap 13 isu yang dikesan dalam **Audit Projek NatNetwork**
(sesi Claude Code, 25 Sep 2026), dan status pelaksanaannya. Ia adalah rekod perubahan (change log),
bukan pengganti `AGENTS.md`, `MASTER_SPECIFICATION.md` atau `NATNETWORK_V1_MASTER_SPECIFICATION.md`.
Mengikut peraturan kawalan perubahan (`MASTER_SPECIFICATION.md` §1), keputusan eksplisit yang lebih baharu
mengatasi keputusan/andaian lama yang bercanggah dalam dokumen tersebut.

## Ringkasan isu dan keputusan

| # | Isu (audit 25 Sep 2026) | Keputusan Owner | Status |
|---|---|---|---|
| 1 | Modul Partnership/Pelabur di luar skop `NATNETWORK_V1_MASTER_SPECIFICATION.md` asal | **Diluluskan.** MS terkini Owner telah mengiktiraf modul ini. Semakan undang-undang draf terma pelabur diabaikan buat masa ini (program kekal `program_enabled=false` sehingga diaktifkan admin). | Direkodkan sahaja — tiada perubahan kod diperlukan |
| 2 | Rekod sandbox (mod ujian Billplz) boleh bercampur dengan rekod production pada quotation/order/projek | Pisahkan rekod sandbox dan production sepenuhnya. Rekod sandbox boleh dipadam oleh admin; rekod production tidak boleh dipadam sama sekali. Guna pendekatan paling mudah untuk kegunaan folder tempatan dan cloud. | **Dilaksanakan** — lihat "Perubahan teknikal" |
| 3 | Quotation lama tidak dibatalkan apabila spesifikasi baharu diluluskan; reset Start Project tidak jelas | Reset spec hanya dibenarkan **sebelum bayaran dibuat**. Apabila reset (eksplisit atau melalui kelulusan spesifikasi baharu), semua rekod spec lama (quotation, slot hold, Master Specification, Project Request) **dipadam**, bukan sekadar dikunci/dibatalkan. Invois yang telah dikeluarkan kekal VOID (rekod kewangan tidak dipadam). | **Dilaksanakan** |
| 4 | Stack deploy (PHP/DB) tidak konsisten dengan baseline lama; tiada sandaran sebelum migrasi | Ikut pendekatan deploy yang **mudah**; **sandaran wajib sebelum migrasi**. | **Dilaksanakan** — SQLite dikekalkan (fail tunggal, mudah untuk folder tempatan & cloud), `deploy.sh` kini membuat sandaran automatik sebelum `migrate --force` |
| 5 | Admin impersonation (log masuk sebagai ahli/affiliate/partner) tiada dalam spesifikasi asal | Admin boleh log masuk sebagai ahli/pelanggan/affiliate/partner **READONLY sahaja** — tiada sebarang tindakan (bukan hanya bayaran/kelulusan) dibenarkan bagi pihak pengguna. | **Dilaksanakan** |
| 6 | Order boleh disahkan tanpa slot hold dalam kes tepi | Order **wajib** ada slot yang diluluskan/ditempah. Kapasiti: 1 slot lungguh maksimum **3 projek** (boleh diubah admin — sudah wujud sebagai `max_active_projects`). | **Dilaksanakan** — order tidak lagi disahkan secara senyap tanpa slot; direkod sebagai `SCHEDULING_EXCEPTION_NO_SLOT` untuk tindakan admin |
| 7 | Nombor refund guna prefix `NAT-RF-`, tidak sepadan dengan MS §9 (`NAT-REF-`) | Pastikan setiap jenis nombor rujukan berbeza dan mudah dikenali. | **Dilaksanakan** — dibetulkan kepada `NAT-REF-`/`TEST-REF-` |
| 8 | `purgeSandbox()` tidak memadam keseluruhan rantaian rekod sandbox (order, projek, slot hold, refund, change request tertinggal) | Padam semua rekod sandbox berkaitan tanpa tinggal satu pun. | **Dilaksanakan** |
| 9 | Sesetengah nama fail migration bertarikh selepas tarikh audit | Perbetulkan dan kemas kini. | Dinilai — migration sedia ada **tidak dinamakan semula** kerana risiko kepada susunan migrasi yang telah wujud lebih besar daripada faedah kosmetik; migration baharu (termasuk untuk kerja audit ini) menggunakan tarikh yang betul dan berturutan |
| 10 | `README.md` masih README lalai Laravel; `composer.json` masih bernama `laravel/laravel` | Audit, perbetulkan dan kemas kini. | **Dilaksanakan** — lihat "Perubahan dokumentasi" |
| 11 | Tiada struktur/bukti pembahagian fasa mengikut `AGENTS.md` §19 | Bina struktur fasa dan validator. | **Dilaksanakan** — lihat `docs/PHASE_TRACKER.md` dan `php artisan phase:status` |
| 12 | Laluan `GET /admin/impersonate/stop` mengubah state melalui GET | Teruskan (boleh diterima). | Dinilai — laluan ini ialah **destinasi redirect** selepas pelanggan/affiliate/partner log keluar semasa mod admin (`Location:` header pelayar sentiasa GET, tiada cara lain). Ia dilindungi oleh sesi admin yang sah (bukan token awam) dan hanya menamatkan sesi impersonation admin sendiri — bukan tindakan kewangan/kontraktual. Dikekalkan sebagai pengecualian yang didokumenkan, bukan bug |
| 13 | `public/documents/KT0604088-M_CERT.pdf` boleh dicapai awam | Teruskan (boleh diterima). | Dinilai — fail ini ialah sijil pendaftaran syarikat (SSM) yang disengajakan untuk kredibiliti awam; tiada data sulit. Dikekalkan tanpa perubahan |

## Perubahan teknikal (ringkasan)

### Pemisahan sandbox/production (#2, #6, #7, #8)
- `quotations`, `slot_holds`, `orders`, `projects`, `change_requests` kini membawa lajur `is_sandbox`,
  dikunci daripada mod Billplz semasa quotation dihantar (`QuotationWorkflowService::send()`) dan
  mengalir sepanjang rantaian (slot → order → projek → change request).
- `SlotHold::scopeOccupying()` menapis sandbox — hold ujian tidak pernah mengambil kapasiti production.
- Nombor dokumen (`QT`, `ORD`, `PRJ`, `CR`) guna prefix `TEST-` yang betul untuk sandbox.
- `BillplzPaymentService::purgeSandbox()` kini memadam refund → change request → resit → bayaran →
  projek → order → slot hold → quotation → invois sandbox, mengikut susunan kekangan foreign key.
- `OrderService::confirmIfDepositMet()` tidak lagi mengesahkan order secara senyap jika slot hold tiada;
  ia direkod sebagai audit `SCHEDULING_EXCEPTION_NO_SLOT`.

### Reset spec dan spesifikasi baharu (#3)
- `StartProjectResetService` memadam (bukan cancel) slot hold, quotation payment plan, quotation,
  Master Specification dan Project Request apabila reset disahkan tiada bayaran.
- `MasterSpecificationService::approve()` turut memadam quotation lama yang belum diterima apabila
  spesifikasi baharu diluluskan, dan menyekat penjanaan draf baharu selepas quotation ACCEPTED
  (perubahan skop selepas terima mesti melalui Request Change).

### Impersonation readonly (#5)
- `GuardImpersonation` menyekat semua permintaan bukan-GET/HEAD semasa mod admin, bukan senarai
  laluan terpilih. Percubaan disekat direkod sebagai `IMPERSONATED_ACTION_BLOCKED`.

### Deploy (#4)
- `deploy/deploy.sh` membuat sandaran fail SQLite (`storage/app/backups/`) sebelum `migrate --force`,
  dengan pengekalan 30 hari.

## Perubahan dokumentasi (#10)
- `README.md` digantikan dengan penerangan projek sebenar (bukan README lalai Laravel).
- `composer.json`: nama pakej ditukar daripada `laravel/laravel` kepada `natnetwork/synergy`.
- `docs/PHASE_TRACKER.md` (baharu, #11): status setiap 10 fasa `AGENTS.md` §19 berdasarkan audit.
- `app/Console/Commands/PhaseStatus.php` (baharu, #11): `php artisan phase:status` — pemeriksaan
  automatik ringkas (migrasi terkini, ujian lulus, direktori engine wujud) sebagai validator fasa.

`AGENTS.md` dan kedua-dua Master Specification (`MASTER_SPECIFICATION.md`,
`NATNETWORK_V1_MASTER_SPECIFICATION.md`) **tidak diubah** oleh kerja ini — ia kekal sebagai rujukan
sejarah/berkunci; dokumen ini menjadi lapisan keputusan terkini di atasnya, mengikut peraturan
kawalan perubahan yang dinyatakan di §1 Master Specification.
