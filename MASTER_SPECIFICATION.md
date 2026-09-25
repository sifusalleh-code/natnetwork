# NatNetwork Master Specification v1.0

| Metadata | Value |
|---|---|
| Status | **BASELINE / APPROVED REQUIREMENTS COMPILATION** |
| Date | 2026-09-20 |
| Authority | Official NatNetwork requirements baseline |
| Requirement status | **REQUIREMENT LOCKED** unless explicitly marked TBD or Phase 2 |
| Implementation status | Separate from requirement status; see section 30 |
| Related document | `NATNETWORK_V1_MASTER_SPECIFICATION.md` (V1, 2026-09-21). Both documents are valid; any conflict between them must be reported to the Owner before implementation. |

## 1. Purpose, authority, and change control

This document compiles only the agreed NatNetwork decisions into one implementation baseline. It is the single source of truth for the product requirements captured here. It does **not** state that any feature is implemented, deployed, tested, or live.

For each client project, developers, Codex, Claude, and NatNetwork staff must build from the **Latest Approved Project Master Specification**, not chat, an unapproved draft, a superseded version, an old quotation, or an assumption.

Authority order for a client project is: Latest Approved Project Master Specification; Technical Logic Contract; engine ownership; approved task scope; existing verified code. `AGENTS.md` remains the project constitution for implementation conduct and must not be changed by this MS.

Change control is mandatory:

- The latest explicit user-approved decision overrides an older conflicting decision.
- A conflict, unclear business rule, database impact, or scope expansion must be reported before implementation.
- A requirement that has not been decided remains **TBD**. It must not be filled with a guessed value or feature.
- Approved historical specifications, accepted quotation snapshots, and production financial history are not overwritten or hard-deleted.

## 2. Positioning and product principles

NatNetwork is a digital development company for customers who may not have technical knowledge. Customers describe the result they need; NatNetwork translates it into technical scope. The public website must use the six service categories below rather than fragmenting services into excessive subcategories.

The six categories are **Website Development**, **E-Commerce**, **Custom Web Applications**, **AI & Automation**, **System/API Integration**, and **Maintenance & Support**.

The V1 product is a public company website plus Guided Project Builder, quotation/order/payment flow, project management, Client Portal, and Admin system. It is a **Modular Monolith** with one central database and external providers behind adapters.

Core principles:

- No technical knowledge required for customers.
- Customer-facing language is simple; technical mapping stays internal.
- One business fact has one owner engine. UI is never the source of truth for business logic.
- Server-side rules decide money, authorization, status transitions, and progress.
- Internal development cost, tools, provider cost, and margin are confidential.
- Known/simple requirements may be quoted automatically; complex or unknown work requires manual review.

## 3. Service catalogue and initial package data

All pricing below is approved initial/default data, not hard-coded pricing. The current sellable price is owned by the Pricing Engine and is admin-editable. `+` means **Starting From**; “Quotation” means no automatic fixed price.

### 3.1 Website Development

| Package | Initial price | Active development estimate | Agreed scope default |
|---|---:|---|---|
| Landing Page | RM1,500 | 2–4 days | Single landing page, responsive/mobile, CTA WhatsApp/contact, basic enquiry form, basic SEO, deployment; 1 revision and 7-day complimentary support |
| Starter Website | RM2,500 | 3–5 days | Basic company information, services, contact, WhatsApp, Maps, enquiry form, responsive and basic SEO; 1 revision and 14-day support |
| Business Website | RM3,900 | 5–10 days | Company profile, services, portfolio/gallery, testimonials, FAQ, contact/enquiry, WhatsApp, Maps, basic SEO and relevant content management; 2 revisions and 14-day support |
| Corporate Website | RM6,900 | 7–14 days | Larger structure, services/divisions, projects/portfolio, management/team, blog/news, careers, advanced enquiry and content management; 3 revisions and 30-day support |
| Custom Website | RM8,000+ | Quotation | Custom review/quotation |

### 3.2 E-Commerce

| Package | Initial price | Active development estimate | Agreed scope default |
|---|---:|---|---|
| Product Catalogue | RM4,500 | 5–10 days | Product/category/detail/search/filter, WhatsApp/enquiry, admin product management; online checkout is not required |
| E-Commerce Starter | RM6,900 | 7–14 days | Adds cart, checkout, online payment, basic shipping, order management/notification, product and order management |
| E-Commerce Business | RM9,900 | 10–20 days | Adds customer account/order history, variants, stock management, coupon/promotion, enhanced shipping, sales/order reporting |
| Custom E-Commerce | RM15,000+ | Quotation | Complex inventory, multi-vendor, wholesale/dealer, commission, ERP, or similarly complex requirement |

Product entry is not priced as a standard “NatNetwork enters N products” package. The customer uses the product-management system. Product capacity is a hosting-plan quota; permanent product deletion can free quota, but historical order/payment records remain.

### 3.3 Custom Web Applications

| Package | Initial price | Estimate |
|---|---:|---|
| Simple Web System | RM6,000+ | 7–14 days |
| Business Web App | RM12,000+ | 2–4 weeks |
| Advanced Web App | RM25,000+ | 4–8+ weeks |
| Enterprise / Complex | Quotation | Review |

Web-app pricing is based on modules, users/roles, workflow, data, payment, files, reporting, integration, automation, and complexity—not pages alone.

### 3.4 AI & Automation

| Service | Initial development price |
|---|---:|
| Telegram Business Bot | RM1,500+ |
| AI Website Chatbot | RM2,500+ |
| AI Knowledge Bot | RM4,500+ |
| Workflow Automation | RM2,500+ |
| Custom AI Business Assistant | RM5,000+ |
| Advanced AI Solution | Quotation |

Development fee is distinct from recurring or usage-based AI service. Customers are not shown token/API jargon. Managed AI quota, usage unit, warning/lock threshold, top-up and customer price are configurable, but no specific managed-AI/OTP/WhatsApp usage price is locked.

### 3.5 System/API Integration

| Integration | Initial price |
|---|---:|
| Basic API | RM1,000+ |
| Payment Gateway | RM1,500+ |
| Email Integration | RM800+ |
| Telegram Integration | RM1,000+ |
| WhatsApp Integration | RM1,500+ |
| External Business System | RM2,500+ |
| Complex / Multiple Integration | Quotation |

Known integrations may be priced automatically. Unknown integrations require compatibility review. One-time integration cost is separate from recurring/usage cost.

### 3.6 Add-ons

| Add-on | Initial price |
|---|---:|
| Additional Page | RM300+ |
| Additional Language | RM500+ |
| Blog / News | RM600+ |
| Portfolio Module | RM500+ |
| Advanced Form | RM400+ |
| Appointment / Booking | RM800+ |
| Customer Login | RM1,500+ |
| Online Payment Integration | RM1,500+ |
| Basic Chatbot | RM1,500+ |
| External API Integration | RM1,000+ |

If the package/add-on combination becomes complex, it becomes Custom Solution / Manual Review instead of an uncontrolled sum of prices.

### 3.7 Maintenance, hosting, and support

| Maintenance plan | Initial price |
|---|---:|
| Basic Care | RM199/month |
| Business Care | RM399/month |
| E-Commerce Care | RM699/month |
| Web App Care | RM999+/month |
| Custom SLA | Quotation |

Maintenance is not unlimited development. Hosting, complimentary support, maintenance, and additional development are separate services.

| Retail hosting plan | Storage | Product limit | Initial price |
|---|---:|---:|---:|
| Starter | 5GB | 100 | RM180/year |
| Business | 10GB | 500 | RM300/year |
| Pro | 20GB | 1,500 | RM480/year |
| Custom | Custom | Custom | Quotation |

NatNetwork reseller upstream regular costs are confidential internal costing: RC1 RM95/month, RC2 RM135/month, RC3 RM190/month. Start with RC1 and upgrade when necessary; use regular, not promotional, cost for internal calculation.

## 4. Pricing, internal costing, and snapshots

Each service/package/add-on supports name, description, original/current/promo price, price type (**Fixed**, **Starting From**, **Estimated Range**, **Quotation**), charge type (**ONE_TIME**, **RECURRING**, **USAGE_BASED**, **TOP_UP**, **CUSTOM**), promo start/end, deposit, delivery estimate, revision/support entitlement, active/inactive, show/hide price, featured, display order, features, and add-ons. Hosting additionally supports storage, product/media limits, billing cycle and resource policy. Promotion reverts to current/default price after its end.

Admin can change configuration, but price change affects only new/relevant future quotations or renewals. Every quotation stores a price and terms snapshot, so an accepted quotation never changes retrospectively.

NatNetwork may record estimated internal cost, actual cost, revenue, and margin, including development resources, Codex/Claude/tools, API/infrastructure, testing/debugging, revisions, project management, support/warranty, contingency, and margin. This information—including provider upstream cost, internal development cost, tool/token cost and margin—must not appear in client-facing UI or documents.

## 5. Customer acquisition and Guided Project Builder

Two entry paths produce the same project requirement:

1. **Saya Dah Tahu** — customer selects a service/package directly.
2. **Bantu Saya Pilih** — customer answers a Guided Project Builder.

Builder order is project type, purpose, functions, information/sections, content readiness, visual preference, budget, summary, then confirmation. It must use conditional questions and a real **Saya tidak pasti** option; irrelevant questions are skipped. Builder sessions may be saved/resumed: a temporary session becomes linked to the client account and project request after email verification.

### 5.1 Project type and website questions

Initial choices are Website syarikat, Kedai online, Landing page promosi, Sistem urus bisnes, AI/Chatbot/Automation, Upgrade website/sistem sedia ada, and Saya tidak pasti.

For a company website, purpose choices are: introduce company, show services, obtain enquiry, portfolio, booking, sell products, other, or unsure. Functions use customer language: WhatsApp, enquiry form, Maps, gallery, portfolio, testimonials, blog/news, customer login, online payment, booking, chatbot, notifications, or unsure.

Instead of asking page count, ask what information is wanted: About, Services, Products, Portfolio, Testimonials, FAQ, Contact, Team, Careers, Blog/News, or unsure. The system maps answers to technical scope internally. If customer selects customer login, ask only then: Email + Password, Email + OTP, Phone/SMS OTP, or recommend for me. Client-system authentication is separate from NatNetwork Client Portal authentication.

### 5.2 E-Commerce, Web App, AI, and upgrade branches

E-commerce asks product quantity: 1–100, 101–500, 501–1,500, more than 1,500, or unsure. This can recommend Starter/Business/Pro hosting respectively, with >1,500 requiring custom review. Functions: cart, payment, account, order history, variants, stock, promotion/coupon, shipping, reviews, wishlist, multilingual. Do not ask payment-gateway questions when online payment is not selected or shipping questions when shipping is not selected.

Business-system questions ask who uses it—owner, staff, customer, supplier, agent, management, other—and what they do: login, save/search data, upload, booking, payment, status, approval, reporting, notification, etc.

AI/automation choices are customer Q&A, company knowledge, Telegram bot, website chatbot, workflow automation, document/data analysis, AI-system connection, or unsure. Usage is Low/Medium/High/Not Sure or conversations/messages, never tokens.

Upgrade branch requests existing URL and redesign, new functions, problem fix, payment, login, e-commerce, AI, performance, or other. Complex upgrades go to manual review.

### 5.3 Design, content, budget, recommendation

Design choices: Modern, Corporate, Minimal, Premium, Creative, Technology, or recommend; colour follows logo/branding, customer choice, or recommend. Optional reference website and what the customer likes may be collected.

Content readiness asks logo, text/content, images, domain, and hosting; each supports the agreed availability options. Budget ranges are <RM2,500, RM2,500–5,000, RM5,001–10,000, RM10,001–20,000, >RM20,000, or unsure. Budget helps recommendation but must never manipulate quotation price.

Rules may recommend a known package; they must not force a complex request into a standard package. Internal complexity is not client-facing and is not an absolute price calculator. Unknown requirements or free text marked **ADDITIONAL CUSTOMER REQUIREMENT** yield **MANUAL REVIEW REQUIRED**.

Builder questions/options are data-configurable: question, type, options, service category, required flag, display order, conditional rule, active flag and internal mapping. Core pricing, payment, and security rules remain controlled system logic.

## 6. Project Master Specification lifecycle

The customer-facing Basic Master Specification is generated from Builder Answers + configured requirement rules + package/feature definitions. AI must not freely add contractual requirements; at most it may later assist wording or recommendations.

```text
Builder Answers → Requirement Draft → Master Specification Draft
→ Customer Approval → Approved Master Specification → Quotation
→ Accepted Quotation → Contract Baseline → Order → Project
→ Approved Change Requests → Latest Approved Project Master Specification
```

The customer sees plain-language project summary, selected structure, functions, design, content readiness, selected integrations, relevant hosting/recurring services and relevant exclusions. The internal technical mapping is kept separately, e.g. customer account maps to authentication, role/profile, persistence, order history, authorization and admin management. Customer wording/answers remain available to Admin; internal architecture is not client-facing.

Statuses are **DRAFT → APPROVED → SUPERSEDED**. Draft can be edited by returning to structured Builder sections, saving, regenerating, and reviewing again; it is not a free-form Word-like editor. Approval requires verified email, current specification, no unsaved Builder changes, and required answers completed. An approved version stores version, customer, request, requirement snapshot, specification snapshot, approver and approval timestamp. It cannot be overwritten or deleted.

Before quotation acceptance, a new approved specification supersedes the old one and invalidates/supersedes an existing quote that priced the older scope. After acceptance, customer has **REQUEST CHANGE**, not ordinary edit. Admin classifies it as within-scope Revision or outside-scope Additional Work. Approved changes can create a new approved specification version.

Each project retains both `baseline_specification_version` (what was bought) and `latest_approved_specification_version` (what is currently to be built). Both are needed; the first is contractual history and the latter is current build authority.

## 7. Identity and client authentication

NatNetwork Client Portal uses passwordless login only:

```text
Email → Send Code → 6-digit email OTP → Login
```

There is no customer password, password confirmation, or forgot-password flow. OTP defaults agreed: 6 digits, about 10-minute expiry, single use, a new OTP invalidates the old one, attempt limit, resend cooldown, and rate limiting. Resend delivers email; NatNetwork owns OTP business logic.

First-time flow: Builder → MS draft → name, optional company, email, phone/WhatsApp → OTP → account auto-created or linked → approve MS → quotation. One verified email maps to one Client Account; existing verified email links to the existing account rather than creating duplicates. Admin authentication is separate, secure password + 2FA architecture. V1 roles are ADMIN and CUSTOMER only.

## 8. Quotation, capacity, slots, orders, and refund cutoff

Quotation is bound to one exact approved MS version. It contains scope, package, add-ons, development and recurring fees, total, estimated active development duration, revision/support, terms, validity, and specification version snapshot. Known/simple scope can be auto-quoted; complex/unknown scope is **MANUAL REVIEW REQUIRED**. Quotation statuses: DRAFT, REVIEW REQUIRED, SENT, VIEWED, ACCEPTED, EXPIRED, DECLINED, CANCELLED. Accepted quotation is locked.

After acceptance: check live development queue → customer selects available start slot → temporary hold → deposit. Default maximum concurrent active projects is **3**, configurable. The server revalidates a hold before redirecting to payment. Paid within the valid hold reserves it; unpaid expired hold is released. Payment-processing grace for an entered payment process is configurable. A delayed/expired or conflicting slot never becomes a silent overwrite.

The exact confirmation rule is:

> Quotation, slot hold, and payment request are not an order. An order becomes **ORDER CONFIRMED** only when cumulative deposit reaches **50%** and the payment has been server-verified.

Flow:

```text
Quotation Accepted → Slot Selection → Slot Held → 50% Deposit
→ Verified Payment → Slot Reserved → Order Confirmed → Waiting To Start
→ START PROJECT → In Progress
```

Deposit payment does not start development. Only controlled Admin **START PROJECT** changes WAITING TO START to IN PROGRESS, records `started_at`/actor, starts the development timer and ends standard refund eligibility. Deposit paid while project is not started is standard-refund eligible. After START PROJECT, standard refund is unavailable; duplicate/wrong payment or NatNetwork cancellation remains an audited exceptional correction. A successful pre-start refund updates payment to refunded, cancels order/project, and releases the slot while retaining history.

## 9. Billing, Billplz, and financial records

Billing Engine is the sole financial source of truth and is shared by Admin and Client Portal. It owns invoices, payments, receipts, additional charges, credits/discounts, refunds and adjustments. Calculation is server-side:

```text
Original Project Value + Additional Work − Discount/Credit = Current Project Value
Current Project Value − Paid = Outstanding
```

No UI may edit the project total directly. Paid payment, receipt, accepted quotation, confirmed order, production financial transaction, approved charge and critical audit history must not be hard-deleted. Use Void, Refund, Credit, Adjustment, Archive/Cancel as appropriate; reason, actor, timestamp and immutable history are required.

Billplz is V1's gateway through an adapter, not the financial authority. Browser return URL is not payment proof. Server callback verification must validate signature, reference and amount before financial effects. Payment types: **DEPOSIT, FINAL_PAYMENT, ADDITIONAL_CHARGE, HOSTING_RENEWAL, MAINTENANCE, MANAGED_SERVICE, PAID_SUPPORT, OTHER**. Payment statuses: **PENDING, PROCESSING, PAID, FAILED, EXPIRED, CANCELLED, REFUNDED, PARTIALLY_REFUNDED, REVIEW_REQUIRED**.

Additional Charge starts **AWAITING CLIENT APPROVAL** and requires title, reason, description, amount and timing/due. Client Portal offers **SAHKAN** or **PERTANYAKAN**; informal WhatsApp/email approval is not formal approval. Approved work goes through Billing without altering the accepted original quote.

When progress crosses the threshold exactly once—`previous_progress < 80 AND new_progress >= 80` and no final invoice exists—Project emits the event and Billing creates the final 50% payment request/invoice once. Payment need must not lower progress. The project may enter client review at 80%; final deployment/handover waits for required balance payment.

Document numbers are: `NAT-QT-YYYY-NNNN`, `NAT-ORD-YYYY-NNNN`, `NAT-PRJ-YYYY-NNNN`, `NAT-INV-YYYY-NNNN`, `NAT-RCP-YYYY-NNNN`, `NAT-REF-YYYY-NNNN`, `NAT-AC-YYYY-NNNN`, `NAT-SUP-YYYY-NNNN`. Sandbox uses `TEST-*`. MyInvois/e-Invoice is excluded from V1.

## 10. Projects, states, milestones, revisions, and completion

Project Engine controls states and rejects invalid transitions. V1 states are: **WAITING TO START, IN PROGRESS, WAITING FOR CLIENT, WAITING FOR PAYMENT, READY TO RESUME, CLIENT REVIEW, IN REVISION, FINAL APPROVAL, READY FOR HANDOVER, COMPLETED, ON HOLD, CANCELLED**.

Progress is the sum of completed milestone weights, never manually entered. Technical Task → Milestone → Project Progress. Technical tasks may show readiness, but Admin verifies and completes milestones; Codex/integrations cannot set 80% directly. All template weights total 100%. A template is snapped into each project; global edits do not change prior projects. Admin may customize before start while preserving 100%. Later material changes must be controlled and audited.

### 10.1 Locked milestone templates

| Template | Milestones and weights |
|---|---|
| Website Development | Requirement Confirmed 5%; Project Planning 5%; Structure & UI Design 15%; Core Development 30%; Content/Main Functions 20%; Internal Testing 5%; then Client Review Ready at 80% cumulative; Revision 10%; Deployment 5%; Final Completion 5% |
| E-Commerce | Requirement Confirmed 5%; Store Planning 5%; UI/Store Structure 10%; Product System 15%; Cart & Checkout 15%; Order Management 10%; Payment/Shipping Integration 15%; Internal Testing 5%; then Client Review Ready at 80%; Revision 10%; Deployment 5%; Completion 5% |
| Custom Web Application | Requirements & Workflow 10%; Architecture/Planning 10%; UI/Core Structure 10%; Core Modules 25%; Main Workflow 15%; Integration/Supporting Functions 10%; then Client Review Ready at 80%; Testing & Revision 10%; Deployment 5%; Completion 5% |
| AI / Automation | Requirement/Use Case 10%; Workflow Planning 10%; Core Integration 20%; Knowledge/Automation Setup 20%; Main Functions 15%; Internal Testing 5%; then Client Review Ready at 80%; Refinement/Revision 10%; Deployment 5%; Completion 5% |
| System/API Integration | Requirement 10%; Compatibility/API Review 10%; Integration Setup 20%; Core Integration 30%; Data/Workflow Validation 10%; then Testing Complete/Review Ready at 80%; Client Verification 10%; Production Deployment 5%; Completion 5% |
| Landing Page | Requirement 10%; Design/Structure 20%; Development 40%; Content & Testing 10%; then Review Ready at 80%; Revision 10%; Deployment & Completion 10% |

For a large Web App, Core Modules may be split (for example User, Order, Payment, Admin) while total template weight remains 100%. AI Knowledge Bot/Workflow Automation templates may be adjusted before project start to reflect knowledge or workflow emphasis. Customer labels are simplified (Maklumat Projek, Perancangan, Reka Bentuk, Pembangunan, Semakan Anda, Revision, Pelancaran), distinct from internal milestone names.

Client waiting: Admin records requested item, requested date, response, resume date, waiting duration and reason. A blocking client action moves IN PROGRESS to WAITING FOR CLIENT; development timer pauses and active capacity may be released. Customer submission is not enough: Admin verifies accepted content, then project becomes READY TO RESUME and re-enters the next available schedule. It never ejects another project or silently reclaims a slot. Progress is retained. Revised estimates account for valid customer waiting time and queue delay, not NatNetwork internal delay.

Revision is a small change inside approved scope. One revision round is one consolidated batch. Defaults by project value: <RM2,500 = 1; RM2,500–4,999 = 2; RM5,000–9,999 = 3; RM10,000–19,999 = 4; RM20,000+ = quotation. Customer sees included, used and remaining. Outside scope follows Request → Admin Review → Additional Work → Price → Customer Approval → Billing → MS version update where relevant.

Completion validation requires completed required milestones, zero outstanding required payment, resolved required revision, required client approval, and required deliverables. Then record COMPLETED/`completed_at`; complimentary support starts only then.

## 11. Content, files, deliverables, and access control

Every project has a centralized Content & File workspace for requirements, information, uploads, files, versions, review and deliverables. Templates differ by project. Examples: Business Website requires logo, company name/introduction, contact information and services, with images/portfolio conditional; E-Commerce includes logo/company information, product information/images/prices/categories, shipping/payment information and terms/policies.

Customer can **Fill Information**, **Upload File**, or **Need Help**. Customer-facing content status is REQUESTED → SUBMITTED → ACCEPTED or NEEDS UPDATE. Internal NOT REQUESTED and UNDER REVIEW may be used. Upload never automatically means accepted or resumes a project. Admin can flag each item blocking/non-blocking and must give a reason for NEEDS UPDATE.

Files are versioned; latest is current while prior versions remain in history according to retention policy. Required validation: allowed extension, actual MIME/type, size, safe filename, project relationship, customer ownership and visibility. Upload limits are configurable by category and may have package/project override; no MB value is locked. Visibility is **CLIENT** or **INTERNAL**. Access verifies authentication, ownership, project relationship, visibility and authorization; guessing a URL cannot expose a file. Project workspace storage is separate from customer hosting storage quota.

Final deliverables are only those agreed in accepted quotation—such as deployment, source package if included, documentation, admin guide, credentials/handover information. Do not assume all internal source/tools/notes are deliverables. Handover requires development, final payment, required revision and client approval as applicable.

## 12. Client Portal V1

Final V1 navigation is **Dashboard, Projects, Quotations, Billing, Files, Support, Notifications, Profile, Logout**. It is mobile-first and simple, rather than technical.

Dashboard priority is: **ACTION REQUIRED → Active Project → Payment → Recent Activity**. It answers “Apa yang saya perlu buat sekarang?” before “Apa status projek saya?”. Action cards include required upload/information, review, additional-charge approval, or payment. Reading a notification does not itself resolve an action.

Project detail presents overview, customer-friendly progress, requirements, files, revisions, billing, messages and support. It explains WAITING FOR CLIENT and its consequences plainly. Quotations show scope/price/terms and acceptance. Billing shows original value, additional work, discount, current project value, paid/outstanding and invoice/receipt history. Files show item status and fill/upload/help actions. Support displays current entitlement/expiry and support request. Notifications show payment, progress, file, revision, charge, renewal and support events. Profile contains name, company, verified email, phone/WhatsApp and billing information when needed.

The Portal must never expose internal cost/margin, Codex/Claude or development-AI costs, provider credentials, database structure, technical notes, Admin-only files or internal project problems.

## 13. Admin V1 and action framework

Final V1 top-level navigation: **Dashboard; Sales (Requests, Quotations, Orders); Projects (Projects, Development Queue); Customers; Billing; Services; Support; Website; Notifications; Settings; Activity.** V1 has one Admin; no staff-management UI.

Dashboard prioritizes production revenue, outstanding, active projects, new confirmed orders, active capacity, and Action Required. Sandbox transactions do not enter production KPIs. Development Queue shows active/upcoming/resume projects and scheduling conflicts. Project Control Center brings together overview, requirements, progress, billing, files, revisions, messages and activity.

Admin uses a consistent contextual action framework:

- View, Edit, Save, Save & Close, Cancel, Confirm/OK, Delete only where permitted, Archive, Deactivate, Restore, Duplicate, relevant batch actions, Search/Filter/Sort.
- Primary action is obvious and actions appear only when valid for record type + current status + business rules.
- Lists use consistent search/filter/sort, selected-record actions, clear statuses, informative empty states and clear success/failure feedback.
- Safe batch examples include activate/deactivate, mark read, archive draft or display-order change; financial/contractual/project-critical action requires the relevant detail and confirmation.
- Critical actions—including START PROJECT, refund, void invoice, cancel order, production setting change and completion—show effect and require confirmation; high-risk financial action also requires reason and audit.
- Processing UI prevents duplicate clicks; backend idempotency remains mandatory.
- Responsive Admin avoids unreadable wide tables on mobile/tablet.

Delete is context-aware. Draft builder data, unused draft package, draft CMS content, eligible sandbox/test data and unpublished portfolio may be deleted according to rule. Paid payment, receipt, accepted quotation, confirmed order, production financial transaction, approved charge and critical audit record cannot be hard-deleted. UI permissions are not a security boundary; backend validates again.

## 14. Communication, support, subscriptions, and managed third parties

Communication Engine centralizes project messages, portal notifications, Action Required and support communication. Email is a delivery channel, not the owner of communication state. V1 reminders: Payment Due, Client Action Required, Quotation Expiring, Project Due, Hosting/Subscription Renewal. Resend is the Email/Communication Adapter for OTP, quotation, invoice, receipt, payment, order, project action, additional charge, final payment, revision, completion, support and renewal messages.

Support entitlement begins only at project completion. Default periods are <RM2,500 = 7 days; RM2,500–4,999 = 14 days; RM5,000–9,999 = 30 days; RM10,000–19,999 = 60 days; RM20,000+ = 90 days / quotation. Final entitlement is snapped through package/quotation and can be overridden there. Complimentary support covers original-scope defects, not features, pages, design/workflow changes, new integrations, major content changes, third-party/client-caused problems, or post-provider API change.

Subscription Engine owns Hosting, Maintenance and Managed Services. Basic statuses: **ACTIVE, EXPIRING, EXPIRED, SUSPENDED**. Hosting expiry and maintenance expiry have different dependencies: maintenance expiry does not itself shut down a website. Failed renewal follows reminder/grace/overdue/suspension according to configurable grace rule; do not silently delete service or website. V1 manual hosting provisioning is allowed; WHM automatic provisioning is Phase 2. Managed third-party provider availability/change is distinct from NatNetwork's implementation responsibility and may become maintenance/additional work where appropriate.

## 15. CMS, SEO, analytics, legal

CMS manages Homepage, Services, Packages, Pricing, Portfolio, FAQ, About, Contact, Legal/Policies and SEO. It changes content but not application layout or business logic; no CMS drag/drop page builder in V1. Public V1 pages are Home, Services, Pricing, Portfolio, About, FAQ, Contact, Start Project and Legal/Policies.

SEO V1: clean URLs, title, meta description, canonical, social metadata, index/noindex, sitemap, robots, image alt, redirects and basic structured data. Private pages and staging are noindex.

Analytics records funnel and source/UTM, not finance: Visitor → Builder Started → Builder Completed → Email Verified → MS Approved → Quote Generated → Quote Accepted → Deposit Paid → Order Confirmed. It may also track project/recurring events, builder drop-off and source attribution after verified identity. Billing's verified production ledger remains financial truth.

Required legal/policy documents before production: Terms of Service, Privacy Notice, Payment & Refund Policy, Project Terms, Support & Maintenance Terms, and Hosting/Managed Service Terms where applicable. Quotation must show scope/inclusions/exclusions, development and recurring fees, deposit/final payment, estimate, revision/support, deliverables, validity and project terms. Acceptance stores quotation/document/terms version, snapshot, client, date/time and relevant security metadata. Final legal wording requires real company details and professional review before production. Marketing consent is separate from transactional communications.

## 16. Security, audit, failure handling, backup, and environments

Required controls: HTTPS; separate admin/customer authentication; server-side authorization and per-customer ownership; validation/output escaping; CSRF/XSS/SQL-injection protection; OTP and endpoint rate limiting; secure sessions; secrets only in server/environment configuration, never Git/frontend/logs/Admin display; secure uploads; payment signature verification; immutable financial/contract history; safe production errors without stack trace, SQL, path, secret or credentials.

Critical audit record includes actor, action, entity, prior/new state where appropriate, timestamp, reason where required and suitable security metadata. Audit payment, refund, quotation acceptance, MS approval, charge approval, START PROJECT, financial adjustment and critical Admin actions.

Idempotency is required for Billplz callback, quotation/specification/charge approval as applicable, final-invoice trigger, order confirmation, receipt creation, refund, double-click and retry. Important operations are atomic where appropriate, particularly payment/invoice/receipt/slot/order/ledger effects.

Failure rule: **preserve valid state → do not guess → do not duplicate → retry only when safe → reconcile uncertain state**. Callback delay is PROCESSING, not presumed failure. Amount/reference mismatch is REVIEW_REQUIRED. Payment verified but no slot reservation is an audited scheduling exception; do not silently reverse valid payment. OTP email failure does not authenticate. Notification failure does not roll back a valid charge/payment/order. Incomplete uploads do not become SUBMITTED. External-provider outages are isolated when possible. Customer-facing errors use a reference ID; Admin receives technical detail without secrets. Blind retries are not used for refunds, adjustments, payment creation, START PROJECT or approvals.

Backup/DR: daily database and project/file backups, full weekly backup, configurable retention, off-site copy, pre-migration/pre-risk-deploy backup, restore procedure and periodic restore test. Code rollback and database rollback are separate. Environments are **Development → Staging/Test → Production**; data does not mix. Billplz Sandbox and Production are separate; sandbox records never enter production revenue/capacity and sandbox documents use `TEST-*`.

## 17. Architecture, state contracts, and data invariants

NatNetwork V1 is a Modular Monolith:

```text
Public / Client / Admin UI → Application & Business Engines → Database → External Adapters
```

The 12 logical engines and one-owner facts are:

| Engine | Owns |
|---|---|
| Identity & Access | Admin/client authentication, sessions, authorization |
| Sales | Leads, Builder requirements, Master Specifications, quotations, acceptance |
| Pricing | Current services/packages/add-ons/promo pricing |
| Scheduling | Capacity, slots, holds, reservations, queue |
| Billing | Invoice, payment, receipt, outstanding, refund, charge, credit/adjustment |
| Project | Lifecycle, milestones, progress, pause/resume, completion |
| Project Content | Requirements, files, uploads, deliverables |
| Communication | Messages, notifications, Action Required, email delivery intent |
| Subscription | Hosting, maintenance, recurring/managed services |
| CMS | Public content and SEO |
| Audit | Critical immutable history |
| Analytics | Funnel and UTM/source |

Billplz, Resend and Storage are adapters—not engines—and cannot decide quotation amount, payment amount, progress or financial truth. Cross-engine work follows controlled command/service boundaries: approve specification, accept quotation, hold/reserve/release slot, confirm order, start/pause/resume project, complete milestone, approve charge, process payment/refund and complete project. Invalid transitions are rejected.

Non-negotiable invariants: accepted quotation snapshot and approved MS history are immutable; all schema changes use reviewed migrations, reversible where practical and tested before production; no production drop/recreate/manual destructive schema change; milestone totals equal 100; only Project calculates progress; only Billing creates final original-project invoice once from Project's crossing event; only Scheduling owns capacity; only a verified 50% cumulative deposit confirms order; financial records are append/audit corrected rather than erased; each client resource validates ownership; client/internal file visibility is enforced server-side.

## 18. V1 boundary and Phase 2 exclusions

V1 includes the required public/CMS, services/pricing, Builder, OTP Client Account, quotation/acceptance, capacity/slot/deposit/order, Billplz/billing, project management, Client Portal, Admin, security/audit/backup, subscriptions/hosting/maintenance and basic analytics described above.

Do not implement without a new approval: staff/role management; Codex Project API; automatic Git/project milestone updates or development telemetry; advanced CRM/lead scoring; advanced analytics/BI/CAC; pricing intelligence; advanced capacity optimization; automatic WHM provisioning; full AI usage metering/top-up/provider reconciliation; advanced SLA; advanced SEO dashboard/Search Console/AI SEO; enterprise system-health dashboard; complex reminder orchestration; CMS drag/drop builder; mobile app; accounting/e-Invoice integration; WhatsApp automation; Telegram automation; automated maintenance task engine; advanced quotation negotiation; full blog/news CMS; or enterprise CRM.

## 19. Implementation phases and quality gates

Implementation order is locked:

1. Foundation
2. Public & Commercial
3. Builder & Client
4. Sales
5. Billing
6. Orders & Projects
7. Client Portal
8. Recurring Services
9. Admin Completion
10. Production Readiness

For every phase: audit existing code → confirm scope → identify engines/database impact → implement only approved scope → migration if required → function test → security test → regression test → report → approval. Do not jump phase without approval.

Definition of Done requires function, validation, authorization, error handling, relevant tests and no relevant regression—not merely visible UI or compiling code. Minimum testing includes happy path, invalid input, authorization, invalid state, duplicate request, failure handling and regression.

Golden Path Test before launch:

```text
Visitor → Guided Builder → requirement/MS approval → Email OTP → quotation
→ accept → available slot/hold → 50% deposit → Billplz verification
→ order confirmed → waiting to start → Admin START PROJECT → milestones
→ 80% final invoice → final payment → client review/revision → final approval
→ deployment/handover → completed → support begins
```

Test wrong/expired/repeated OTP; expired quote/slot and slot conflict; cancelled/failed/delayed/duplicate payment callback; wrong amount/reference; invalid/oversize upload; rejected/questioned charge; duplicate final invoice; customer delay; invalid Admin transition; cross-customer authorization; sandbox/production separation; capacity/resume contention; financial reconciliation; responsive major flows; and backup restore. Payment, authorization, data-integrity, financial-ledger and backup/restore failures block launch.

## 20. Infrastructure baseline

### REQUIREMENT LOCKED

| Area | Baseline |
|---|---|
| Local workspace | `E:\NatNetwork` |
| Source hosting | No GitHub currently; local folder workflow |
| VPS OS | AlmaLinux 8.10 |
| Web server | Nginx |
| Present runtime observed | Node 22.23.2 / npm 10.9.8 |
| Application | Laravel 12 |
| Target PHP | PHP 8.2 |
| Target database | MariaDB 10.11 |
| UI/build stack | Blade, Tailwind CSS, Alpine.js, Vite |
| Deployment target | `/var/www/natnetwork` |
| Laravel public root | `/var/www/natnetwork/public` |
| Integrations | Laravel Queue, Laravel Scheduler, Resend API, Billplz API |

### IMPLEMENTATION STATUS — OBSERVED / NOT YET VERIFIED OR COMPLETED

The NatNetwork workspace was observed as greenfield/empty apart from `AGENTS.md`; it is not currently a GitHub project. Do not infer a deployment or application implementation from this document.

- PHP 8.2, its Laravel extensions, PHP-FPM and Composer: **NOT YET VERIFIED/COMPLETED** on the VPS.
- MariaDB 10.11/server/client/database/user: **NOT YET VERIFIED/COMPLETED**.
- Laravel application, NatNetwork database, deployment directory, Nginx vhost, DNS, SSL, queue worker, scheduler and production configuration: **NOT YET VERIFIED/COMPLETED**.
- Nginx, Node and npm were observed present; existing GrowBiz, PostgreSQL and Nginx must not be disrupted. Existing `saapp` remained inactive at the observation point.
- PHP AppStream preflight was attempted only with dry-run/`--assumeno`; it indicated PHP 8.2 transaction/module path would pull `httpd`, `httpd-tools` and `mod_http2`. No installation/module change was made. A safe PHP 8.2 + PHP-FPM path that avoids Apache/httpd remains unresolved.
- MariaDB dry-run without selecting its stream would choose 10.3 rather than target 10.11; no MariaDB installation was performed.
- Do not treat observed/recommended targets as completed infrastructure. DNS/egress for providers, vhost, SSL, queue and production setup all remain to be verified separately.

## 21. Validation statement

This baseline is consistent with the existing `AGENTS.md`: Modular Monolith; 12 engine owners; immutable approved MS/accepted quote/financial history; 50% verified-deposit confirmation; controlled START PROJECT; milestone-owned progress and one-time 80% trigger; secure OTP/files; idempotency, audit, migrations, V1 exclusions and the ten-phase process. No application code, VPS, database, package, configuration, or `AGENTS.md` is changed by this document.
