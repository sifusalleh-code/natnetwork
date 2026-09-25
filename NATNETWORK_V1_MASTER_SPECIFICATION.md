# NATNETWORK V1 — MASTER SPECIFICATION

**Versi:** 1.0  
**Tarikh:** 21 September 2026  
**Status:** Baseline V1 yang direkonstruksi daripada keputusan dan skop yang telah dipersetujui.  
**Kedudukan dokumen:** Rujukan utama untuk pembinaan NatNetwork V1. Keputusan Owner selepas tarikh ini mengatasi dokumen ini.  
**Dokumen berkaitan:** `MASTER_SPECIFICATION.md` (v1.0, 20 September 2026). Kedua-dua dokumen adalah sah; sebarang percanggahan antara keduanya mesti dilaporkan kepada Owner sebelum implementasi.

## 1. Tujuan produk

NatNetwork V1 ialah platform operasi digital untuk perniagaan perkhidmatan web dan teknologi. Ia menggabungkan laman web awam, Guided Project Builder, quotation dan pembayaran, pengurusan projek, portal pelanggan, serta pentadbiran operasi dalam satu aplikasi Laravel modular monolith.

Aliran utama:

~~~
Pelawat → Lead → Builder → Pengesahan e-mel → Master Specification diluluskan
→ Quotation → Slot mula dipegang → Deposit disahkan → Order/Projek
→ Pelaksanaan → Baki bayaran → Handover → Sokongan/Perkhidmatan berulang
~~~

## 2. Skop V1

V1 mesti meliputi:

- Laman web syarikat dan CMS asas.
- Katalog perkhidmatan, pakej, add-on, harga dan promosi yang dikawal admin.
- Guided Project Builder bagi mendapatkan requirement pelanggan.
- Basic Master Specification berasaskan jawapan Builder.
- Pengesahan pelanggan melalui e-mel OTP tanpa kata laluan.
- Lead, quotation automatik atau manual-review, dan acceptance pelanggan.
- Kapasiti projek, slot mula, temporary hold dan reservation selepas deposit sah.
- Invois, resit, deposit 50%, bayaran akhir, caj tambahan, refund/credit terkawal melalui Billplz.
- Projek, milestone, requirement, fail, semakan dan handover.
- Portal pelanggan, notifikasi dan e-mel transaksi melalui Resend.
- Pentadbiran seorang Admin V1, audit trail dan analytics funnel asas.
- Hosting dan maintenance sebagai perkhidmatan berulang yang boleh dikonfigurasi.

## 3. Batasan V1

Perkara berikut bukan skop implementasi V1:

- UI pengurusan staff/role lengkap.
- Integrasi Codex atau kemas kini milestone automatik oleh Codex.
- CRM, lead scoring atau automasi WhatsApp/Telegram yang lengkap.
- BI lanjutan, pricing intelligence atau optimasi kapasiti lanjutan.
- Provisioning hosting/WHM automatik.
- Metering penggunaan AI, top-up dan billing usage penuh.
- SLA, reminder orchestration dan system-health dashboard enterprise.
- Drag-and-drop CMS/page builder, aplikasi mudah alih dan blog penuh.
- Integrasi accounting, MyInvois/e-Invoice atau provider portal.
- Search Console, AI SEO dan dashboard SEO lanjutan.
- Enjin tugasan maintenance automatik.

## 4. Seni bina dan runtime

| Perkara | Keputusan |
|---|---|
| Corak aplikasi | Modular Monolith, satu aplikasi dan satu pangkalan data utama |
| Framework | Laravel 12 |
| UI | Blade, Tailwind CSS, Alpine.js, Vite |
| Runtime | PHP 8.2, PHP-FPM, Nginx |
| Database | MariaDB 10.11 |
| Async | Laravel Queue dan Laravel Scheduler |
| E-mel | Resend melalui adapter Communication |
| Bayaran | Billplz melalui adapter Billing |
| Source | Workspace lokal sahaja; tiada GitHub |
| Server | AlmaLinux 8.10; GrowBiz, saapp dan PostgreSQL kekal terasing |

Deployment NatNetwork yang diluluskan ialah `/var/www/natnetwork`, dengan document root `/var/www/natnetwork/public`.

Semua logik perniagaan mesti berada dalam engine/modul NatNetwork. Provider luar tidak boleh menjadi sumber kebenaran bagi data atau state perniagaan.

## 5. Engine dan pemilikan sumber kebenaran

| Engine | Pemilikan eksklusif |
|---|---|
| Identity & Access | Identity pelanggan/admin, session, authentication dan authorization |
| Sales | Lead, Builder, requirement, Master Specification, quotation dan acceptance |
| Pricing | Service, package, add-on, promo, harga semasa dan rules harga |
| Scheduling | Kapasiti, slot, hold, reservation dan queue mula projek |
| Billing | Invois, bayaran, resit, refund, credit, adjustment dan ledger berkaitan |
| Project | Lifecycle projek, milestone, progress, pause, review dan completion |
| Project Content | Requirement, upload, fail, deliverable dan semakan kandungan |
| Communication | Message, notification, action-required, e-mel dan delivery record |
| Subscription | Hosting, maintenance, renewal dan perkhidmatan berulang |
| CMS | Kandungan awam, SEO dan penerbitan laman |
| Audit | Rekod tindakan kritikal yang immutable |
| Analytics | Event funnel, sumber dan UTM asas |

Adapter luaran ialah Billplz, Resend dan Storage. Adapter hanya melaksanakan integrasi; ia tidak menentukan business state.

## 6. Pengguna dan akses

### Pelawat

Pelawat boleh melihat kandungan awam, menghantar Contact/Start Project, dan memulakan Builder. Rekod Builder anonim tidak mendapat akses portal atau dokumen peribadi.

### Pelanggan

Pelanggan menggunakan passwordless OTP e-mel. Selepas e-mel disahkan, e-mel sama mesti dipetakan kepada client account sedia ada; jangan cipta duplikasi. OTP mesti mempunyai expiry, had percubaan, rate limit dan penggunaan sekali sahaja di server.

### Admin

V1 mempunyai seorang Admin operasi. Admin authentication berasingan daripada pelanggan dan direka untuk password + 2FA. UI staff/role management ditangguhkan.

Authorization sentiasa diperiksa di backend. Hiding action dalam UI bukan kawalan keselamatan.

## 7. Laman awam, CMS dan SEO

Laman awam V1:

- Home, Services, Pricing, Portfolio, About, FAQ, Contact, Start Project dan Legal/Policies.
- Service category: Website Development, E-Commerce, Custom Web Applications, AI & Automation, System/API Integration, Maintenance & Support.
- Admin mengurus kandungan, harga, portfolio dan FAQ; tiada page builder drag-and-drop.

SEO asas meliputi slug bersih, title/meta description, canonical, social metadata/image, status index, sitemap dinamik untuk halaman aktif/indexable, structured data yang sah, image alt, redirect dan true HTTP 404. Default boleh dijana tetapi admin boleh override.

Halaman admin, portal pelanggan, OTP, payment, quotation, invoice, receipt, project dan file mestilah `noindex`. Portfolio tidak diterbitkan automatik; persetujuan pelanggan diperlukan.

## 8. Pricing dan katalog

Pricing Engine ialah pemilik tunggal harga semasa. Admin boleh mengurus nama, penerangan, price type, charge type, harga asal/semasa/promo dan tempohnya, deposit, anggaran tempoh, revision/support, feature, add-on, active/featured dan display order.

Price type: `FIXED`, `STARTING_FROM`, `ESTIMATED_RANGE`, `QUOTATION`.  
Charge type: `ONE_TIME`, `RECURRING`, `USAGE_BASED`, `TOP_UP`, `CUSTOM`.

Snapshot quotation yang sudah accepted tidak boleh berubah apabila katalog/harga semasa berubah.

### 8.1 Harga default Website

| Pakej | Harga | Anggaran | Revision | Support |
|---|---:|---|---:|---:|
| Landing | RM1,500 | 2–4 hari aktif | 1 | 7 hari |
| Starter | RM2,500 | 3–5 hari aktif | 1 | 14 hari |
| Business | RM3,900 | 5–10 hari aktif | 2 | 14 hari |
| Corporate | RM6,900 | 7–14 hari aktif | 3 | 30 hari |
| Custom | RM8,000+ | quotation | quotation | quotation |

Add-on bermula dengan: extra page RM300, language RM500, blog/news RM600, portfolio RM500, advanced form RM400, booking RM800, customer login RM1,500, online payment RM1,500, basic chatbot RM1,500, external API RM1,000. Keperluan kompleks mewajibkan manual review.

### 8.2 Harga default E-Commerce

| Pakej | Harga | Anggaran | Skop minimum |
|---|---:|---|---|
| Product Catalogue | RM4,500 | 5–10 hari aktif | produk, kategori, detail, carian/filter, enquiry; tiada checkout |
| E-Commerce Starter | RM6,900 | 7–14 hari aktif | cart, checkout, payment, basic shipping, order dan admin produk |
| E-Commerce Business | RM9,900 | 10–20 hari aktif | account, order history, variants, stock, coupon, enhanced shipping/reporting |
| Custom | RM15,000+ | quotation | stock/multivendor/wholesale/ERP dan kompleksiti lain |

### 8.3 Harga default lain

| Kategori | Default |
|---|---|
| Custom Web App | Simple RM6,000+ (7–14 hari), Business RM12,000+ (2–4 minggu), Advanced RM25,000+ (4–8+ minggu), Enterprise quotation |
| AI & Automation | Telegram bot RM1,500+, chatbot web RM2,500+, knowledge bot RM4,500+, workflow RM2,500+, custom assistant RM5,000+ |
| System/API | Basic API RM1,000+, payment RM1,500+, e-mel RM800+, Telegram RM1,000+, WhatsApp RM1,500+, external business system RM2,500+ |
| Maintenance | Basic Care RM199/bulan, Business RM399/bulan, E-Commerce RM699/bulan, Web App RM999+/bulan |
| Hosting | Starter 5GB/100 products RM180/tahun; Business 10GB/500 RM300/tahun; Pro 20GB/1,500 RM480/tahun; Custom quotation |

Development fee dan managed AI/usage cost mestilah diasingkan. Kapasiti produk datang daripada pakej hosting, bukan bilangan data entry. Maintenance tidak termasuk feature baharu; kerja tambahan diproses sebagai Additional Work.

Support default mengikut nilai projek: bawah RM2,500 = 7 hari; RM2,500–4,999 = 14 hari; RM5,000–9,999 = 30 hari; RM10,000–19,999 = 60 hari; RM20,000+ = 90 hari atau quotation. Revision default: 1, 2, 3, 4 dan quotation bagi jalur nilai yang sama. Satu revision round ialah satu kumpulan maklum balas terkumpul. Snapshot quotation mengatasi default ini.

## 9. Guided Project Builder dan Master Specification pelanggan

Start Project menyediakan dua laluan: **Saya Dah Tahu** (pilih service/package) dan **Bantu Saya Pilih** (guided questions).

Builder menggunakan bahasa bukan teknikal dan soalan bersyarat tentang jenis projek, tujuan, fungsi diperlukan, maklumat/section, kesediaan kandungan, visual preference, bajet dan ringkasan. Ia menawarkan pilihan “Saya tidak pasti”. Free text direkod sebagai `Additional Customer Requirement`; keperluan yang tidak dapat ditentukan menjadi `MANUAL_REVIEW_REQUIRED`.

Sales Engine menjana **Basic Master Specification** daripada jawapan Builder, configured rules dan definisi package/feature. AI boleh membantu wording pada masa depan tetapi tidak boleh menambah scope kontrak.

Master Specification pelanggan mempunyai state `DRAFT`, `APPROVED`, `SUPERSEDED`.

- Pelanggan mengedit section requirement asal, bukan dokumen WYSIWYG.
- Sebelum approval: e-mel disahkan, semua requirement wajib lengkap dan tiada data belum disimpan.
- Approval menyimpan version, pelanggan, masa approved, requirement snapshot dan specification snapshot.
- Dokumen sejarah tidak boleh dipadam.
- Kandungan pelanggan: ringkasan projek, struktur/pages, fungsi terpilih, design preference, content/responsibility, integrations, hosting/recurring dan exclusion relevan.
- Internal technical mapping kekal admin-only.

Sebelum quotation accepted, specification baharu yang diluluskan menggantikan versi lama dan quotation lama mesti invalid/superseded. Quotation sentiasa mengikat versi approved yang tepat.

Quotation acceptance menjadikan specification tersebut contractual baseline. Selepas itu, perubahan hanya melalui Change Request: scope review, keputusan within-scope atau additional work, approval, dan versi approved baharu jika perlu. Project menyimpan `baseline_specification_version` dan `latest_approved_specification_version`; team pembangunan hanya bekerja pada versi terbaru yang approved.

## 10. Lead dan quotation

Lead boleh datang daripada Contact, Builder, Start Project, request assistance, quotation, referral atau campaign. Contact form mengandungi nama, e-mel, telefon/WhatsApp, subject dan message serta validation, rate limit dan anti-spam. Ia mewujudkan `NEW ENQUIRY` dan notifikasi admin.

Lead state: `NEW`, `CONTACTED`, `IN_DISCUSSION`, `QUALIFIED`, `QUOTATION`, `CONVERTED`, `CLOSED`, `NOT_SUITABLE`. Nota, follow-up date, source dan UTM direkodkan. E-mel verified ialah key deduplication; telefon tidak boleh memicu auto-merge yang tidak selamat.

Quotation known-price boleh dijana automatik. Keperluan kompleks atau tidak pasti masuk `MANUAL_REVIEW_REQUIRED` dan admin sediakan quotation manual.

Quotation memaparkan scope/package/add-on, fee development, recurring fee, anggaran, revision, support, terms dan validity. State:

~~~
DRAFT → REVIEW_REQUIRED → READY → SENT → VIEWED → ACCEPTED
                         └──────────────────────────────→ EXPIRED / DECLINED / CANCELLED
~~~

Quotation accepted dikunci. Quotation expired/changed specification tidak boleh diterima; ia mesti diprice semula dan dikeluarkan semula.

## 11. Scheduling dan kapasiti

Default maksimum projek aktif serentak ialah **3**, dengan konfigurasi admin.

Flow selepas quotation diterima:

~~~
Capacity check → pelanggan pilih slot AVAILABLE → HOLD sementara → deposit sah → RESERVED
~~~

Scheduling state: `AVAILABLE`, `HELD`, `RESERVED`.

- Hold tamat dan melepaskan slot secara automatik.
- Hold sahaja tidak menjadi reservation kekal.
- Checkout mesti revalidate hold; payment-in-progress grace boleh dikonfigurasi.
- Hanya deposit sah boleh reserve slot.
- Bayaran berjaya tetapi slot gagal reserved ialah scheduling exception untuk tindakan admin; jangan batal bayaran secara senyap.
- `WAITING_FOR_CLIENT` menghentikan development timer dan boleh melepaskan active capacity. Apabila pelanggan lengkapkan tindakan, state menjadi `READY_TO_RESUME`; Scheduling menentukan sambungan tanpa mencuri slot pelanggan lain.
- Konflik kapasiti memerlukan penyelesaian admin; tiada silent overlap.

## 12. Billing dan pembayaran

Billing ialah pemilik tunggal dokumen dan state kewangan. V1 menggunakan dokumen dalaman, bukan MyInvois/e-Invoice atau accounting portal.

Dokumen: quotation, invoice, receipt, refund dan credit. PDF dijana daripada rekod sebenar. Rekod kontrak/kewangan/approved/audit tidak boleh hard delete dalam production; gunakan void, refund, credit, adjustment, cancel atau archive dengan sejarah kekal.

Deposit default ialah 50%.

~~~
Accepted quote + held slot
→ deposit invoice
→ Billplz payment
→ signed callback disahkan di server
→ PAID + invoice paid + receipt
→ slot reserved + order confirmed
~~~

Browser return bukan bukti bayaran. Callback Billplz mesti mengesahkan signature, reference dan amount; ia idempotent. Callback duplicate mengembalikan kejayaan sedia ada tanpa menduplikasi receipt/order/reservation/ledger. Amount mismatch masuk `REVIEW_REQUIRED`.

Payment state: `PENDING`, `PROCESSING`, `PAID`, `FAILED`, `EXPIRED`, `CANCELLED`, `REFUNDED`, `PARTIALLY_REFUNDED`, `REVIEW_REQUIRED`.

Final invoice dicipta tepat sekali apabila progress melepasi 80% untuk kali pertama dan belum ada final invoice. Handover/completion tidak boleh berlaku sehingga balance, requirement, revision, client approval dan deliverable yang diperlukan diselesaikan.

Additional Work tidak mengubah kontrak asal. Ia mempunyai title, reason, description, amount, timing bayaran dan flow `AWAITING_CLIENT_APPROVAL` → approval → charge/invoice jika perlu. Ia boleh mempunyai work/milestone sendiri tetapi tidak reset progress projek utama.

Refund standard layak sebelum `START PROJECT`. Selepas projek bermula, refund hanya exceptional controlled action dengan reason, confirmation dan audit. Refund pre-start yang berjaya mesti mengekalkan sejarah asal, refund payment, cancel order/project dan lepaskan slot.

Tax setting kekal fleksibel/configurable; snapshot invoice menyimpan seller, customer, item, price, tax dan terms secara historical.

## 13. Projek dan milestone

Order confirmed mencipta projek `WAITING_TO_START`. Hanya tindakan Admin `START PROJECT` boleh memulakan kerja selepas semakan status order/deposit/reservation; ia merekod `started_at/started_by`, memulakan timer dan menamatkan standard refund eligibility.

Project states:

~~~
WAITING_TO_START, IN_PROGRESS, WAITING_FOR_CLIENT, WAITING_FOR_PAYMENT,
READY_TO_RESUME, CLIENT_REVIEW, IN_REVISION, FINAL_APPROVAL,
READY_FOR_HANDOVER, COMPLETED, ON_HOLD, CANCELLED
~~~

Transition berlaku melalui command terkawal, bukan field status bebas.

Milestone dijana daripada template service dan di-snapshot ke projek. Jumlah weight mesti 100%; progress ialah jumlah milestone completed, bukan manual percentage. Template website default:

| Milestone | Weight |
|---|---:|
| Requirements | 5% |
| Planning | 5% |
| Structure/UI | 15% |
| Core Development | 30% |
| Content/main functions | 20% |
| Internal Testing | 5% |
| Revision | 10% |
| Deployment | 5% |
| Completion | 5% |

Milestone 80% menandakan review-ready. E-commerce, app, AI, integration dan landing boleh mempunyai template sendiri yang admin ubah sebelum project start; selepas start perubahan mesti dikawal dan diaudit.

## 14. Project Content, fail dan deliverable

Project Content mengurus requirement, maklumat pelanggan, uploads, files, version, review dan deliverable.

Client requirement state: `REQUESTED`, `SUBMITTED`, `ACCEPTED`, `NEEDS_UPDATE`.

Semua upload mesti menyemak extension, true MIME type, saiz, safe filename, ownership, project dan visibility. `CLIENT` dan `INTERNAL` mestilah terasing. Tiada pautan public terus untuk fail terlindung; authorization diperiksa untuk setiap akses. Storage adapter mesti mengesahkan simpanan sebelum submission dianggap lengkap dan temp upload tidak lengkap perlu dibersihkan dengan selamat.

## 15. Communication dan notification

Communication ialah pemilik tunggal messages, notification, action-required, support dan e-mel delivery. Engine lain menerbitkan event; mereka tidak menghantar e-mel secara terus.

E-mel transaksi: OTP, quotation, invoice, receipt, payment, project, support dan reminder. Rekod e-mel menyimpan recipient, type, provider ID, delivery status dan reason. Resend digunakan sebagai provider. Kegagalan e-mel tidak boleh rollback source record; portal/admin menunjukkan status dan membolehkan retry terkawal.

Transactional e-mel dan marketing consent mesti berasingan. WhatsApp click-to-chat boleh ada sebagai link, bukan automasi/CRM WhatsApp V1.

## 16. Client portal

Navigation V1:

- Dashboard
- Projects
- Quotations
- Billing
- Files
- Support
- Notifications
- Profile
- Logout

Dashboard mengutamakan Action Required, projek aktif, bayaran dan aktiviti terbaru serta mobile-first. Halaman projek menggunakan label mesra pelanggan dan memaparkan progress, requirement, revision dan messages relevan. Pelanggan boleh melihat/muat turun quotation, invoice dan receipt serta membayar. Mereka tidak boleh melihat kos dalaman, provider secret, kos AI, nota internal atau detail teknikal terlindung.

## 17. Subscription dan recurring services

Hosting dan maintenance ialah recurring services di bawah Subscription Engine. V1 menyokong service record, harga snapshot, renewal invoice/reminder dan lifecycle yang configurable. Provisioning automatik dan metering/top-up usage bukan V1.

## 18. Admin V1

Modul Admin:

- Dashboard
- Sales: Requests, Quotations, Orders
- Projects: Projects, Development Queue
- Customers
- Billing
- Services
- Support
- Website
- Notifications
- Settings
- Activity

UI mesti menggunakan action berdasarkan record type, state dan business rules: View, Edit, Save, Save + Close, Cancel, Archive, Void, Refund, Start Project dan lain-lain yang sah. Critical action memerlukan confirmation dan reason apabila relevan. Senarai menyokong carian/filter/sort dan batch action yang selamat. Tindakan backend tetap authoritative.

## 19. Audit, keselamatan dan kebolehpercayaan

Audit record bagi tindakan kritikal mesti menyimpan actor, action, record, previous/new values, masa, reason dan security metadata. Audit production untuk tindakan kritikal immutable.

- Tiada secret, stack trace atau maklumat teknikal sensitif dipaparkan kepada pelanggan.
- Error pengguna menggunakan safe message dan reference ID.
- Double-click/duplicate request perlu ditahan dengan processing/idempotency pattern.
- Provider failure diasingkan; ia tidak boleh menjatuhkan keseluruhan aplikasi.
- Draft/unpublished content boleh dihapuskan secara terkawal; rekod contractual/financial/approved/audit tidak boleh.
- Backup dan preflight wajib dilakukan sebelum perubahan deployment/database yang berisiko.

## 20. Analytics V1

Analytics menyimpan funnel/source/UTM asas sahaja. Event minimum:

`page_view`, `start_project_clicked`, `builder_started`, `builder_step_completed`, `builder_completed`, `email_verified`, `quotation_generated`, `quotation_viewed`, `quotation_accepted`, `slot_selected`, `deposit_started`, `deposit_verified`, `order_confirmed`, `project_started`, `project_80_percent`, `final_paid`, `project_completed`, dan event recurring relevan.

Critical event mesti dipancarkan daripada backend. Anonymous journey boleh dipautkan kepada client selepas e-mel verified. Analytics tidak menyimpan OTP, dokumen sensitif, data payment mentah atau kos dalaman dan bukan sumber kebenaran kewangan.

## 21. Data dan invariants utama

- Satu customer boleh mempunyai banyak lead, specification, quotation, order, project, invoice dan recurring service.
- Satu quotation mengikat satu versi approved Master Specification dan snapshot pricingnya.
- Satu project menyimpan baseline specification dan latest approved specification.
- Satu accepted quotation boleh menghasilkan satu confirmed order sahaja.
- Satu payment provider reference hanya boleh menghasilkan satu financial outcome NatNetwork.
- Satu slot reservation memerlukan deposit `PAID` yang sah.
- Progress project sentiasa dikira daripada milestone snapshot.
- Price, tax, terms dan scope historical datang daripada snapshot dokumen, bukan katalog semasa.

## 22. Urutan implementasi V1

1. Foundation: modular structure, auth baseline, audit/events, CMS shell dan settings.
2. Public & Commercial: public pages, services/pricing, contact dan lead intake.
3. Builder/Client: Builder, OTP, client account dan Master Specification.
4. Sales: quotation, manual review dan acceptance.
5. Billing & Scheduling: pricing snapshots, Billplz, deposit, slot/hold/reservation.
6. Orders & Projects: order conversion, project lifecycle, milestone dan content/files.
7. Client Portal & Communication: dashboard, documents, action-required, Resend delivery.
8. Recurring & Admin: subscriptions, support, admin operations dan analytics funnel.
9. Production: hardening, backup/restore verification, monitoring, deployment checks dan launch verification.

## 23. Konfigurasi yang perlu dilengkapkan sebelum go-live

Nilai ini tidak boleh direka; ia perlu dimasukkan oleh Owner/Admin dalam setting selamat sebelum payment/production use:

- Entiti undang-undang NatNetwork, alamat, nombor pendaftaran, invoice identity dan polisi/terms muktamad.
- Billplz production collection ID, secret, callback secret dan mode.
- Resend sender name/from/reply-to yang diluluskan.
- Storage driver/bucket, retention dan backup destination.
- Payment/hold/reminder grace period, tax setting dan notification copy.
- Domain/DNS/Cloudflare state, SSL, canonical domain dan mail DNS records.

## 24. Definition of done untuk V1

V1 diterima apabila aliran lengkap boleh diuji tanpa tindakan manual yang tidak direkodkan:

1. Pelawat menghantar Builder dan menjadi lead.
2. Pelawat mengesahkan e-mel, melihat dan meluluskan Master Specification.
3. Sistem atau admin mengeluarkan quotation yang terikat kepada versi specification dan price snapshot.
4. Pelanggan menerima quotation, memegang slot dan membayar deposit Billplz.
5. Callback sah secara idempotent mengesahkan bayaran, menjana receipt, reserve slot dan confirm order.
6. Admin memulakan projek; milestone mengira progress dan trigger final invoice sekali pada 80%.
7. Pelanggan menyelesaikan requirement, upload fail, review, payment dan handover melalui portal.
8. Semua tindakan kritikal, perubahan state dan transaksi boleh diaudit; data pelanggan terasing dan private.
9. Public domain menggunakan HTTPS dengan konfigurasi Cloudflare/Nginx yang tidak mengganggu GrowBiz, saapp atau PostgreSQL.

