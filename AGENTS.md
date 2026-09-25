# NatNetwork Project Constitution

## 1. Project Principle

NatNetwork ialah greenfield project yang menggunakan **Modular Monolith**.

Semua pembangunan mesti berdasarkan, mengikut turutan keutamaan:

1. Latest Approved Master Specification
2. Technical Logic Contract
3. Engine Ownership
4. Approved Task Scope
5. Existing verified code

Jangan membuat andaian apabila requirement tidak jelas.

Berhenti dan laporkan sebelum implementasi jika terdapat:

- requirement bercanggah;
- business rule tidak jelas;
- perubahan di luar scope;
- risiko kepada module lain; atau
- perubahan database yang tidak dijangka.

Jangan implement sehingga arahan atau approval diterima.

## 2. Codex Team Roles

### LEAD / ARCHITECT

- Menjaga architecture, Master Specification, dan Technical Logic Contract.
- Menentukan module boundaries dan menyelaras cross-engine changes.
- Review database design, pecahkan task, dan menyelaras kerja pasukan.
- Tidak menambah feature tanpa approval.

### BACKEND & DATABASE

- Bertanggungjawab terhadap business engines, database schema, migrations, models, server-side validation, authorization, business rules, serta API/application services.
- Pemilik utama perubahan schema.

### FRONTEND & UX

- Bertanggungjawab terhadap Public Website, Guided Project Builder UI, Client Portal, Admin UI, dan responsive UX.
- Menggunakan backend contracts.
- Tidak mencipta business logic sendiri atau mengubah schema sendiri.

### INTEGRATION & SERVICES

- Bertanggungjawab terhadap Billplz Adapter, Resend Adapter, Storage Adapter, callback/webhook, dan external integrations.
- Tidak menentukan quotation amount, payment amount, project progress, atau financial truth sendiri.

### QA / REVIEWER

- Menjalankan code review, security review, regression, business-rule compliance, permission testing, payment testing, dan state-transition testing.
- Tidak menjadi primary implementer bagi code yang sedang direview.

## 3. Engine Ownership

Gunakan 12 logical engines:

1. Identity & Access
2. Sales
3. Pricing
4. Scheduling
5. Billing
6. Project
7. Project Content
8. Communication
9. Subscription
10. CMS
11. Audit
12. Analytics

Adapters:

- Billplz Adapter
- Resend Adapter
- Storage Adapter

Single source of truth setiap fakta perniagaan:

| Business fact | Owner engine |
| --- | --- |
| Current Price | Pricing |
| Builder / Requirements / Master Specification / Quotation / Acceptance | Sales |
| Slot / Capacity / Scheduling / Queue | Scheduling |
| Invoice / Payment / Receipt / Refund / Outstanding | Billing |
| Project Status / Milestone / Progress | Project |
| Files / Content / Deliverables | Project Content |
| Messages / Notifications / Action Required | Communication |
| Hosting / Maintenance / Recurring Services | Subscription |
| Public Content / SEO | CMS |
| Critical History | Audit |
| Funnel / UTM | Analytics |

UI tidak boleh menjadi source of truth untuk business logic. Jangan duplicate calculation atau business rule. One business fact mempunyai satu owner engine.

## 4. Master Specification Rule

Lifecycle specification yang sah:

```text
Builder Answers
→ Requirement Draft
→ Master Specification Draft
→ Customer Approval
→ Approved Master Specification
→ Quotation
→ Accepted Quotation
→ Contract Baseline
→ Order
→ Project
→ Approved Change Requests
→ Latest Approved Master Specification
```

Developer/Codex hanya boleh membina berdasarkan **LATEST APPROVED PROJECT MASTER SPECIFICATION**.

Jangan bina berdasarkan draft, rejected change, old superseded version, informal message, atau assumption.

- Approved specification version tidak boleh ditimpa atau dipadam.
- Accepted quotation mesti terikat kepada exact specification version.
- Selepas quotation accepted, scope change mesti melalui Change Request, Revision, atau Additional Work mengikut business rule.

## 5. Billing & Financial Safety

Billing Engine ialah source of truth kewangan.

- Semua calculation kewangan mesti dibuat server-side.
- Browser/client tidak dipercayai untuk amount, payment status, discount, outstanding, refund, atau financial approval.
- Billplz browser return bukan bukti payment.
- Payment mesti disahkan melalui server callback, signature verification, dan amount/reference validation.
- Critical payment processing mesti idempotent.
- Production financial records tidak boleh hard-delete.
- Accepted quotation locked.
- Additional work tidak boleh mengubah accepted quotation.
- Gunakan Additional Charge, Credit, Adjustment, Refund, atau Void mengikut business rule.
- Final original-project invoice hanya boleh dijana sekali.
- Internal development cost, margin, Codex/Claude/tool cost tidak boleh dihantar ke customer-facing frontend atau document.

## 6. Order & Payment Rule

```text
Quotation Accepted
→ Slot Selection
→ Slot Held
→ 50% Deposit
→ Verified Payment
→ Slot Reserved
→ Order Confirmed
→ Waiting To Start
→ START PROJECT
→ In Progress
```

- Tanpa verified cumulative 50% deposit, order tidak confirmed.
- Payment tidak bermaksud project sudah bermula.
- Project hanya bermula melalui controlled `START PROJECT` command.
- Standard refund eligibility tamat apabila project secara rasmi `STARTED`.

## 7. Project Progress Rule

```text
Technical Task → Milestone → Project Progress
```

- Project progress dikira daripada completed milestone weights.
- Jumlah milestone weights mesti `100%`.
- Codex atau integration tidak boleh terus menetapkan project kepada `80%`.
- Apabila `previous progress < 80`, `new progress >= 80`, dan final invoice belum pernah dijana, Project menghasilkan event.
- Billing menentukan dan menjana final payment request sekali sahaja.
- Payment requirement tidak boleh menurunkan project progress.
- `100%` hanya selepas completion requirements dipenuhi.

## 8. Database Rules

Semua schema changes menggunakan migration.

Dilarang:

- production drop/recreate;
- manual destructive schema changes;
- mengubah historical financial records;
- mengubah approved specification history; atau
- mengubah accepted quotation snapshot.

Migration mesti reviewed, reversible jika praktikal, dan diuji sebelum production.

Backend & Database ialah pemilik utama schema changes. Cross-engine schema change memerlukan Lead review.

## 9. Security Rules

- Customer dan Admin authentication berasingan.
- Customer menggunakan passwordless email OTP.
- Admin menggunakan separate secure authentication; password + 2FA architecture.
- Authorization mesti server-side.
- Setiap customer resource mesti verify ownership.
- Secrets hanya dalam environment/server configuration: jangan commit, jangan frontend, jangan log, dan jangan paparkan penuh di Admin.
- Gunakan input validation, output escaping, CSRF protection where applicable, XSS protection, SQL injection protection, rate limiting, secure session handling, dan secure file authorization.
- Production errors tidak boleh mendedahkan stack trace, SQL, server path, secret, atau internal credentials.

## 10. File Security

Uploaded files mesti validate:

- extension;
- actual MIME/type;
- size;
- safe filename;
- ownership;
- project relationship; dan
- visibility.

CLIENT dan INTERNAL visibility mesti berasingan. Internal file/note tidak boleh dihantar kepada customer. File access mesti melalui authorization, bukan sekadar public upload URL.

## 11. State Transitions

Jangan update critical status secara arbitrary. Gunakan controlled commands/services untuk:

- approve specification;
- accept quotation;
- hold, reserve, atau release slot;
- confirm order;
- start project;
- complete milestone;
- pause atau resume project;
- approve additional charge;
- process payment;
- refund; dan
- complete project.

Invalid state transition mesti ditolak.

## 12. Idempotency

Idempotency wajib untuk operasi kritikal:

- payment callback;
- quotation acceptance;
- specification approval apabila sesuai;
- additional charge approval;
- invoice generation;
- receipt generation;
- order confirmation;
- final-payment trigger; dan
- refund processing.

Double click atau retry tidak boleh menghasilkan duplicate record.

## 13. Audit

Critical actions mesti direkodkan. Audit sekurang-kurangnya menyimpan:

- actor;
- action;
- record/entity;
- previous state apabila sesuai;
- new state;
- timestamp;
- reason apabila diperlukan; dan
- security metadata yang sesuai.

Critical audit history immutable.

## 14. Admin UX Rule

Admin actions berdasarkan record type, current status, dan business rules.

- UI permission bukan security boundary; backend mesti validate semula.
- Jangan letakkan Edit/Delete pada semua record secara membuta tuli.
- Production financial/contractual records menggunakan Archive, Cancel, Void, Refund, Adjustment, atau Deactivate mengikut business rule, bukan hard-delete.
- Critical action mesti mempunyai confirmation.

## 15. Testing Rule

Setiap implementation task mesti melalui:

```text
AUDIT EXISTING
→ IMPLEMENT APPROVED SCOPE
→ MIGRATION jika perlu
→ FUNCTION TEST
→ SECURITY TEST
→ REGRESSION TEST
→ REPORT
→ APPROVAL
```

Jangan anggap task selesai hanya kerana code compile.

Uji sekurang-kurangnya:

- happy path;
- invalid input;
- authorization;
- invalid state;
- duplicate request;
- failure handling; dan
- relevant regression.

## 16. Change Control

Jangan refactor module lain tanpa sebab yang diperlukan oleh approved task.

Jangan:

- redesign unrelated UI;
- rename unrelated modules;
- replace architecture;
- upgrade dependencies tanpa sebab;
- add library hanya kerana lebih mudah; atau
- add feature yang tidak diminta.

Jika perubahan di luar scope diperlukan, laporkan dahulu.

## 17. V1 Boundary

Jangan implement tanpa approval baru:

- Staff/role management;
- Codex Project API;
- automatic Git/project milestone updates;
- advanced CRM/lead scoring;
- advanced analytics/BI/CAC;
- pricing intelligence;
- advanced capacity optimization;
- automatic WHM provisioning;
- full AI usage metering/top-up;
- advanced SLA;
- advanced SEO dashboard/Search Console/AI SEO;
- enterprise system health dashboard;
- complex reminder orchestration;
- CMS drag/drop builder;
- mobile app;
- accounting/e-Invoice integration;
- WhatsApp automation;
- Telegram automation; atau
- automated maintenance task engine.

Architecture boleh future-ready, tetapi feature tersebut jangan dibina dalam V1.

## 18. Development Style

Utamakan code yang simple, explicit, maintainable, secure, dan testable. Elakkan over-engineering.

NatNetwork ialah **MODULAR MONOLITH**, bukan microservices.

## 19. Phase Control

Jangan lompat phase tanpa approval.

Implementation order:

1. Phase 1 Foundation
2. Phase 2 Public & Commercial
3. Phase 3 Builder & Client
4. Phase 4 Sales
5. Phase 5 Billing
6. Phase 6 Orders & Projects
7. Phase 7 Client Portal
8. Phase 8 Recurring Services
9. Phase 9 Admin Completion
10. Phase 10 Production Readiness

Selepas setiap phase: test → regression → report → tunggu approval.

## 20. Current State

`E:\NatNetwork` kini ialah **GREENFIELD / EMPTY PROJECT**.

Jangan andaikan framework atau database stack telah dipilih melainkan terdapat keputusan yang diluluskan.

## Operating Instruction for Every Agent

Sebelum memulakan kerja, baca constitution ini dan sahkan task mempunyai approved scope. Apabila arahan bercanggah dengan constitution ini atau dokumen yang lebih berautoriti, berhenti dan laporkan konflik untuk keputusan Lead/approval.
