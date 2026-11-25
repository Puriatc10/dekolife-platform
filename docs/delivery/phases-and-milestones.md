# Phases and Milestones — Dekolife Web Platform

**Purpose:** Define the eleven delivery phases (0–10), their objectives, scope, deliverables, exit criteria, demos, risks and explicit duration estimates, and compare the resulting engineering schedule against the contractual 90-day window.
**Audience:** Orchestrator, all engineering agents, the independent reviewer, the client-facing delivery lead.
**Status:** Baseline v1 (Phase 0 output). Durations are estimates in **working days** and are re-forecast at every phase gate in `progress.md`.
**Source:** Contract no. `DK-1405-01` (signed PDF, `input/contract/1-قرارداد-دکولایف-۹۰م-ویرایش.pdf`; extracted text in `docs/contract/contract-extracted-text.txt`), articles 2, 3, 4, 9, 12 and appendices 1–13. Naming, module boundaries and stack are pinned by `docs/canonical-context.md` and are not re-litigated here.

> **Quotation convention.** Persian quotes are reproduced from the OCR text of the signed PDF with obvious OCR ligature errors corrected (e.g. `اطالعات` → `اطلاعات`); wording, numbers and punctuation are otherwise unchanged. Citations use `page N / ماده M / بند K / پیوست A`.

---

## 1. Basis of estimate

| Item | Definition used in this document |
|---|---|
| Working day (wd) | One **engineering** working day on a 5-day week (Sat–Wed), 1 engineer = 8 h. |
| Calendar day | Contract time is counted in calendar days (`ماده ۳`: «۳ ماه (۹۰ روز)»). |
| Conversion | 1 wd = 1.4 calendar days (`× 7 / 5`). 90 calendar days = **64 working days** of capacity. |
| Effort unit | **Person-day (pd)** = one engineer-day of design + implementation + tests, including the feature tests that the DoD requires. |
| Elapsed unit | **wd** on the critical path for that phase, assuming the tracks in `dependency-map.md` run concurrently. |
| Review capacity | 1 independent reviewer at 0.5 FTE (≈ 4 h/wd throughout) — every PR reviewed before merge. |
| Overhead included | CI, code review cycles, rework from review are inside the pd numbers; they are **not** added on top. |

Effort is bottom-up per phase and per requirement area (see §4). The requirement baseline is **203 numbered clauses** enumerated from appendices 1–13 plus **24 contract-level (CTR) obligations** from the 16 articles = **227 baseline items**, tracked per area in `progress.md`.

---

## 2. Contractual timing, delivery and penalty facts

These are the legal constraints the schedule must respect. Every number below is quoted or computed from the contract; they are not negotiable engineering preferences.

| # | Fact | Value | Citation |
|---|---|---|---|
| C-01 | Contract price, fixed, no unilateral adjustment | 90,000,000 Toman | page 1 summary table; `ماده ۲` |
| C-02 | Prepayment on signing | 10,000,000 Toman | `ماده ۲`, payment table |
| C-03 | Final settlement, one lump sum, after 1 month from final delivery **and** written confirmation | 80,000,000 Toman | `ماده ۲`, payment table |
| C-04 | Execution period, counted from contract date **and** receipt of complete information/content from the client | 3 months / 90 days | `ماده ۳` |
| C-05 | Extension | only by **written agreement** of both parties | `ماده ۳` |
| C-06 | Delay penalty | **0.5 %** of the total contract value per calendar day of delay, deducted from the contractor's claims | `ماده ۴` بند ۲ |
| C-07 | Delay penalty cap | **20 %** of contract value = **18,000,000 Toman**, reached on the **40th** day of delay | `ماده ۴` بند ۲ |
| C-08 | Missing appendix heading penalty | **5 %** of contract value (**4,500,000 Toman**) per unexecuted numbered appendix heading; total cap **10 %** (**9,000,000 Toman**, i.e. two headings saturate it) | `ماده ۴` بند ۵ |
| C-09 | Termination right for delay | if delay exceeds **15 working days** past the deadline (21 calendar days), the client may terminate and the contractor must refund receipts within 15 days net of penalties | `ماده ۹` بند ۲–۳ |
| C-10 | Weekly progress reporting | written weekly report with screenshots or a viewable demo | `ماده ۳`; `ماده ۴` بند ۱ |
| C-11 | Client review turnaround | feedback within **3 working days**; silence for **7 working days** = the contractor's proposal is deemed approved | `ماده ۵` بند ۴ |
| C-12 | Pre-delivery testing obligation | unit + integration + acceptance tests with real scenarios before announcing final delivery | `ماده ۴` بند ۳ |
| C-13 | Source + install documentation handover | full source with install docs and folder-structure documentation, editable by the client | `ماده ۴` بند ۴; `پیوست ۱` بند ۲ |
| C-14 | Free bug-fix warranty | **5 years** from final delivery, for defects caused by programming error | `ماده ۱۲` بند ۲ / `ماده ۱۶`; page 4 |
| C-15 | Support SLAs | first response ≤ 24 working hours; critical (outage/data loss) ≤ 24 h; medium ≤ 3 working days | `ماده ۱۲` بند ۱ |
| C-16 | Daily automatic database backup with restore capability | mandatory | `پیوست ۱` بند ۹ |
| C-17 | Audit log of sensitive events (login, price change, role change, settlement) | mandatory | `پیوست ۱` بند ۱۰ |
| C-18 | Admin panel with 2FA | mandatory | `پیوست ۱` بند ۷ |
| C-19 | Hosting, domain, SSL and payment gateway are supplied by the client | client obligation | `ماده ۲`; `ماده ۵` بند ۳ |

**Consequences used later in this document**

- The penalty clock ticks in **calendar** days at 900,000 Toman/day (0.5 % × 90,000,000). A 17-day slip costs **7,650,000 Toman**; the cap is reached at day 40.
- The **15-working-day** termination trigger (`ماده ۹`) is the hard boundary, not the penalty cap. Any plan that lands beyond 21 calendar days of slip puts the whole receivable at risk, so the schedule in §5 targets ≤ 17 days of slip with a written-extension request opened early.
- Because `ماده ۳` allows extension **only in writing**, the extension request is a Phase 0/1 governance action, not a Phase 10 rescue attempt.

---

## 3. Phase summary

Release names map 1:1 to `release-plan.md`.

| Phase | Name | REQ areas | Effort (pd) | Elapsed (wd) | Cumulative wd | Cumulative calendar days | Release |
|---|---|---|---|---|---|---|---|
| 0 | Baseline, repo, CI, contracts | CTR | 12 | 4 | 4 | 6 | v0.1.0 |
| 1 | Shared kernel, Identity, Users, audit | CTR, IAM, USR | 26 | 9 | 13 | 19 | v0.2.0 |
| 2 | Merchants, Catalog, Pricing, Inventory | MER, CAT, PRI, STK | 46 | 10 | 23 | 33 | v0.3.0 |
| 3 | Cart, Orders, Shipping, Returns | CRT, ORD, SHP, RTN | 42 | 10 | 33 | 47 | v0.4.0 |
| 4 | Invoices, Payments, Finance ledger | INV, PAY, FIN | 36 | 8 | 41 | 58 | v0.5.0 |
| 5 | Marketing, FieldOps, Compensation | MKT, GEO, CMP | 30 | 6 | 47 | 66 | v0.6.0 |
| 6 | Loyalty and Promotions | LOY, PRM | 34 | 7 | 54 | 76 | v0.6.1 |
| 7 | Messaging, Notifications, CMS, Reviews, Samples | MSG, NTF, CMS, RVW, SMP | 28 | 5 | 59 | 83 | v0.7.0 |
| 8 | Extensions: Tenders, Classifieds, Patogh | TND, CLS, PTG | 24 | 4 | 63 | 89 | v0.7.1 |
| 9 | Admin, Reporting, PWA, SEO/security hardening | ADM, RPT, CTR | 34 | 7 | 70 | 98 | v0.9.0 |
| 10 | Acceptance (UAT), training, handover | CTR | 22 | 6 | **76** | **107** | v1.0.0 |
| | **Total** | 28 areas + CTR | **334** | **76** | | **107** | |

Total engineering effort **334 person-days**; critical-path elapsed **76 working days** = **107 calendar days** at 5 days/week, i.e. an average effective parallelism of 4.4 streams. Six agent streams are planned (four engineering tracks + docs/QA support), so the plan is dependency-bound rather than headcount-bound — see §6.

---

## 4. Phase detail

Each phase is a **gate**: the next phase starts only when every exit criterion is green and the independent reviewer has signed the phase tag. "Included REQ areas" lists the canonical areas from `docs/canonical-context.md` §3 that the phase must bring to `Implemented`.

### Phase 0 — Baseline, repository, CI and shared contracts (v0.1.0)

- **Objective:** Make the project buildable, testable and auditable by any agent from a clean machine, and freeze the contracts that later phases compile against.
- **Included REQ areas:** CTR (contract-level: weekly reporting, source-doc obligation, backup/audit commitments accepted as engineering requirements).
- **Deliverables:**
  - `compose.yaml` with `app`, `nginx`, `mysql` (8.4), `mailpit`; `.env.example`; `scripts/dev` one-command wrapper per `docs/operations/local-development.md`.
  - Laravel 13 skeleton pinned to the exact `laravel/framework` version, module autoloading for `app/Modules/*` and `app/Shared/`.
  - `.github/workflows/ci.yml`: Pint, PHPStan level 6, `php artisan test` on a MySQL 8.4 service container, `npm ci && npm run build`, secret scan.
  - ADR-0001 modular monolith, ADR-0002 money as integer Toman, ADR-0003 ledger append-only, ADR-0004 database queue, ADR-0005 audit trait, ADR-0006 no Redis in v1, ADR-0007 error envelope + request id.
  - Shared kernel contracts: `Money`, `Result`/error envelope, `AuditLogger`, `JalaliDate`, `Storage`, pagination value object.
  - This documentation set (`docs/delivery/*`, `docs/operations/*`) plus the requirement baseline of 227 items.
- **Exit criteria:** clean `git clone` → `scripts/dev up` → `scripts/dev test` green on a machine with no PHP installed; CI green on `main`; the seven ADRs merged; Shared kernel contracts reviewed and tagged `v0.1.0`; requirement baseline published with per-area counts.
- **Demo:** destroy the workspace, clone, run one command, show the green test suite and the CI run page.
- **Risks:** Docker volume performance on the Windows host slows the suite; MySQL 8.4 auth plugin mismatch with the PHP client; over-designing the shared kernel (it must contain **no business rules**).
- **Duration:** 12 pd / **4 wd** elapsed.
- **Dependencies:** none (start gate). Blocks every other phase.

### Phase 1 — Shared kernel, Identity, Users, authorization and audit (v0.2.0)

- **Objective:** Every request has an authenticated, authorized actor with a role set and an audit trail — the precondition for all scoped data.
- **Included REQ areas:** IAM, USR, CTR (audit, 2FA).
- **Deliverables:** many-to-many roles (`consumer`, `store`, `supplier`, `marketer`, `admin`) with a secondary-role switch governed by admin (`پیوست ۷` بند ۶); session guard for web + `admin` guard with 2FA (`پیوست ۱` بند ۷); Sanctum token issuance for `routes/api.php`; SMS-verified registration and password reset (`پیوست ۲` بند ۸); profile edit with SMS confirmation (`پیوست ۳` ۳-۱ بند ۱۰); base policies + scopes (marketer = own network, store = own catalog/orders); `shr_`/`adm_audit_logs` writer wired to login, role change, profile-change events; first-login forced password change flag (`پیوست ۶` بند ۸).
- **Exit criteria:** every role can authenticate through web and API; negative authorization tests exist for each scoped resource (mandatory per `docs/canonical-context.md` §7); audit rows produced for the four sensitive events; `php artisan test` covers 2FA challenge and forced password change; reviewer sign-off.
- **Demo:** admin enables a secondary role for a store user, the user's menu and API scopes change, audit log shows actor/old/new.
- **Risks:** role explosion if policies encode business rules; Iranian SMS provider sandbox availability (mitigated with a fake driver selected by config in test/dev only); 2FA UX friction for admins.
- **Duration:** 26 pd / **9 wd**.
- **Dependencies:** Phase 0 contracts.

### Phase 2 — Merchants, Catalog, Pricing, Inventory (v0.3.0)

- **Objective:** A supplier can list a product with per-role prices, and a buyer can find it. This is the first client-visible surface.
- **Included REQ areas:** MER, CAT, PRI, STK.
- **Deliverables:** supplier/store profiles with admin approval gate (`پیوست ۵` بند ۷–۸); category/brand/attribute tree with dynamic filters (price, brand, stock, rating, discounted) and sorting (`پیوست ۲` بند ۳); product page with gallery, video, specs, related items and compare-up-to-5 (`پیوست ۲` بند ۴–۵); product create/edit form with per-role pricing, variable attributes and filtering (`پیوست ۷` بند ۴); server-side price resolution chain base → role/tier list → volume → promotion → coupon → loyalty (`docs/canonical-context.md` §5); stock ledger and reservation on order placement; price snapshot on order line; initial data import for categories/brands/sample products (`پیوست ۹` بند ۴); search index with Persian normalisation (ی/ك, half-space).
- **Exit criteria:** price resolution is a single tested service with a table-driven test matrix covering 5 roles × 4 volume tiers; no price is ever accepted from the client payload; catalog listing p95 < 400 ms with 10k seeded products; import command idempotent and re-runnable; reviewer sign-off.
- **Demo:** admin sets a store price and a consumer price for one SKU; the same product renders three different prices for three accounts, with the applied rule visible in the order-line snapshot.
- **Risks:** the tiered/volume pricing contract in `پیوست ۴` بند ۳ and `پیوست ۱۳` بند ۴ is ambiguous in coefficient stacking — recorded as `AMB-PRI-001`, implemented with an admin-editable coefficient table so it can be corrected without a migration.
- **Duration:** 46 pd / **10 wd** (two parallel sub-streams: catalog+search, merchants+pricing+stock).
- **Dependencies:** Phase 1 (actor, policies, audit).

### Phase 3 — Cart, Orders, Shipping, Returns (v0.4.0)

- **Objective:** A buyer can place a multi-seller order and both sides can see its state.
- **Included REQ areas:** CRT, ORD, SHP, RTN.
- **Deliverables:** cart with coupon application, shipping cost calculation and unified checkout (`پیوست ۲` بند ۶); parent `ord_orders` + per-seller `ord_sub_orders` with the explicit state machine required by `docs/canonical-context.md` §5 (Agent D deliverable); shipping method selection and carrier fields at checkout (`پیوست ۵` بند ۴۰); order tracking by reference code (`پیوست ۲` بند ۱۱); supplier "incomplete order" button with automatic negative points for repeated misuse (`پیوست ۱۱` بند ۱۲–۱۳); cancellation with refund intent raised as a Finance command, not a direct balance edit; returns window per admin-approved items (`پیوست ۱۲` بند ۱۴); order-stall/complaint window up to one week after dispatch, freezing payout (`پیوست ۴` بند ۱۵); audio/text/image attachment per order (`پیوست ۵` بند ۴۱).
- **Exit criteria:** state machine has an exhaustive transition test (every illegal transition rejected); placing an order is idempotent under double submit; every sub-order transition emits a domain event consumed by Payments and Compensation; negative authorization tests for cross-store order access; reviewer sign-off.
- **Demo:** one consumer order spanning two suppliers, advanced to `shipped`, with a stall complaint freezing one sub-order's payout.
- **Risks:** the stall/complaint rule conflicts with "automatic payout" flows — resolved by making payout a scheduled command that checks holds (`AMB-ORD-001`); state-machine creep from admin overrides.
- **Duration:** 42 pd / **10 wd**.
- **Dependencies:** Phase 2 (price snapshot, stock reservation).

### Phase 4 — Invoices, Payments, Finance ledger and settlement (v0.5.0)

- **Objective:** Money moves correctly, once, with a ledger that can be audited and reconciled.
- **Included REQ areas:** INV, PAY, FIN.
- **Deliverables:** invoice issuance per sub-order plus manual invoice creation by admin (`پیوست ۷` بند ۲); supplementary invoice for extra costs (`پیوست ۱۱` بند ۱۷); `GatewayDriver` contract + drivers behind admin activation, one active gateway per payment in v1; callback verification, idempotent payment command with unique `reference`; append-only `fin_ledger_entries` with derived balances and reversal-only corrections; wallet debit/credit; settlement periods with admin approval; refund-to-SHEBA flow (`پیوست ۵` بند ۱۵); failed/reversed transaction and gateway-mismatch report (`پیوست ۱۳` بند ۶); daily payment report to supplier and admin (`پیوست ۱۳` بند ۲); bank bulk-payment Excel export (`پیوست ۱۳` بند ۳).
- **Exit criteria:** double-callback and replayed-callback tests produce exactly one ledger entry; ledger sum equals derived balance for a 500-transaction seeded scenario; a settlement run is reproducible and reversible only by reversal entries; reconciliation report matches a fixture of gateway settlements; reviewer sign-off.
- **Demo:** pay an invoice through the sandbox driver, replay the callback twice, show a single ledger entry, then run a settlement period and show the supplier statement.
- **Risks (high):** `پیوست ۱` بند ۵ requires **12 gateways and two gateways usable in one transaction**; `docs/canonical-context.md` scopes v1 to 12 drivers with one active gateway and defers split payment (`RB-PAY-01`). This is a **5 %-per-heading penalty exposure** if left open — see §7, R-03.
- **Duration:** 36 pd / **8 wd**.
- **Dependencies:** Phase 3 (order/invoice events), Phase 1 (actor for approvals).

### Phase 5 — Marketing, FieldOps and Compensation (v0.6.0)

- **Objective:** The field-sales network can onboard stores offline-first, and attribution is provable.
- **Included REQ areas:** MKT, GEO, CMP.
- **Deliverables:** per-marketer QR code, dedicated link and card usage (`پیوست ۵` بند ۱); separate marketer registration form with generated credentials and forced first-login password change (`پیوست ۵` بند ۲; `پیوست ۶` بند ۸); duplicate-store prevention by national ID, postal code and mobile search (`پیوست ۵` بند ۳); on-site registration capture with owner/storefront/business-card galleries and map location (`پیوست ۶` بند ۱–۲); 5-minute edit window then admin-approved edits (`پیوست ۶` بند ۹); marketer plans A–D with commission/fixed-visit/salary combinations and activity conditions (`پیوست ۶` بند ۳, ۵–۶); inactive-store detection and re-visit assignment (`پیوست ۴` بند ۲۳–۲۴); QR scan → attribution record with `marketer_id` immutable after first invoice; commission calculation through Compensation consuming order events; marketer scope enforcement (own stores/leads only).
- **Exit criteria:** attribution is proven by a test that scans a QR, registers a store, places an invoice, and asserts exactly one marketer is credited; marketer cannot read another marketer's store via web or API (negative test); commission run reproducible for a seeded network of 200 stores; reviewer sign-off.
- **Demo:** scan a QR, register a store as marketer B, place an invoice, show the commission line and the attribution chain.
- **Risks:** offline capture on flaky mobile networks (mitigated: form state persisted locally, submission idempotency key); commission plan combinations produce a rule matrix that is easy to misread — implemented as data, not code, with an admin editor.
- **Duration:** 30 pd / **6 wd**.
- **Dependencies:** Phase 4 (invoice/payment events for commission base), Phase 1 (roles).

### Phase 6 — Loyalty and Promotions (v0.6.1)

- **Objective:** Points, tiered rewards, wallet bonuses and coupons behave exactly as the appendix describes, reproducibly.
- **Included REQ areas:** LOY, PRM.
- **Deliverables:** versioned rule sets (`loy_rule_sets` with `effective_from`) covering per-invoice points by amount/product/volume; volume coefficients 0.75 / 0.5 / 0.25 / 0.2 % (`پیوست ۴` بند ۳); next-invoice reward deduction with the "at least 4× the reward" rule else half (`پیوست ۴` بند ۴); inactivity point decay (`پیوست ۴` بند ۶); reward credited to wallet, time-limited or permanent (`پیوست ۴` بند ۷); stars per 100 points, unlimited (`پیوست ۴` بند ۹); tiered invoice discounts 4 % (1–100 points) and 5 % (100–200) with SMS notice (`پیوست ۴` بند ۲۰); fixed-amount discount above 200 points (`پیوست ۴` بند ۲۱); coupons, banners and bulk user deactivation (`پیوست ۵` بند ۴۳); points for referral of a new colleague (`پیوست ۴` بند ۱۳).
- **Exit criteria:** a replay command recomputes any historical invoice's points from the active rule set and matches stored values; boundary tests at 100 and 200 points; coefficient table tested at all four tiers; reward-vs-invoice-size rule tested at exactly 4× and just under; reviewer sign-off.
- **Demo:** replay a month of seeded invoices and show identical point totals, then place an invoice that consumes a reward.
- **Risks:** rule interpretation conflicts between `پیوست ۴` and `پیوست ۱۲` on first-invoice discounts — recorded as `AMB-LOY-001`; retroactive rule changes must never rewrite history (enforced by `effective_from` + replay test).
- **Duration:** 34 pd / **7 wd**.
- **Dependencies:** Phase 4 (invoices, wallet), Phase 2 (product/price classification).

### Phase 7 — Messaging, Notifications, CMS, Reviews, Samples (v0.7.0)

- **Objective:** The platform can talk to its users and publish its own content.
- **Included REQ areas:** MSG, NTF, CMS, RVW, SMP.
- **Deliverables:** internal messenger with persistent history and attachments (text/image/audio/file) between buyer and any permitted user, including admin and marketer (`پیوست ۵` بند ۲۸); ticket threads both directions (`پیوست ۷` بند ۸); custom notification to a defined audience with count and schedule (`پیوست ۵` بند ۹); paid notification purchase flow with admin approval (`پیوست ۵` بند ۱۲); SMS/WhatsApp/email channel adapters behind one `Notifier` contract; order-status notifications (`پیوست ۵` بند ۲۳); birthday/occasion greetings (`پیوست ۱۲` بند ۷); blog with list, category and SEO metadata (`پیوست ۲` بند ۱۰); static pages, footer, menus, homepage widgets, top bar notification (`پیوست ۲` بند ۱۲); product and supplier reviews with 5–10 rating parameters within one week of delivery (`پیوست ۴` بند ۱۷); sample/portfolio requests (`پیوست ۱۱` بند ۹); polls (`پیوست ۵` بند ۱۰).
- **Exit criteria:** sending a mass notification is throttled and resumable, and never double-sends (idempotency key per recipient); every outbound message is recorded with template, channel, status; review submission blocked outside the window; reviewer sign-off.
- **Demo:** buy a 500-recipient notification, watch it drain in batches through the queue, show per-recipient delivery rows.
- **Risks:** SMS cost blow-up on retries — hard caps per campaign and per day, enforced in the notifier; provider rate limits causing stuck queues (covered by the queue-stuck runbook).
- **Duration:** 28 pd / **5 wd**.
- **Dependencies:** Phase 1 (users), Phase 3 (order events for status notifications).

### Phase 8 — Extensions: Tenders, Classifieds, Patogh (v0.7.1)

- **Objective:** Deliver the three distinct marketplace sections named in the appendix, as independent modules with their own admin taxonomy.
- **Included REQ areas:** TND, CLS, PTG.
- **Deliverables:** tender form, distribution to producers/suppliers, participation and inquiry (`پیوست ۵` بند ۶; `پیوست ۳` ۳-۲ بند ۸); auction section inside the supplier panel (`پیوست ۱۱` بند ۱۰); classifieds (`دیوار`) with title, body, image, search, user profile presence and admin-defined taxonomy before posting (`پیوست ۵` بند ۴); Patogh local-services directory with craftsman profiles, ticket form and local cost announcement (`پیوست ۵` بند ۵, ۱۳); violation/warning button feeding admin (`پیوست ۴` بند ۱۸) and violation history shown at order time (`پیوست ۴` بند ۱۹).
- **Exit criteria:** each section has its own route group, policy and admin taxonomy screen; classifieds cannot be posted without an admin-defined category (negative test); tender visibility restricted to invited suppliers; reviewer sign-off.
- **Demo:** post a classified, submit a tender to three suppliers, request a craftsman quote from Patogh.
- **Risks:** these are the most likely headings to be deferred under schedule pressure — but each carries a 5 % penalty (C-08), so deferral is a commercial decision, not an engineering one.
- **Duration:** 24 pd / **4 wd**.
- **Dependencies:** Phase 2 (taxonomy), Phase 7 (messaging for tender distribution).

### Phase 9 — Admin, Reporting, PWA, SEO and security hardening (v0.9.0)

- **Objective:** Make the platform operable by the client's team and acceptable to a browser, a crawler and a pen-test checklist.
- **Included REQ areas:** ADM, RPT, CTR (PWA, SEO, responsive, security).
- **Deliverables:** admin member management, activation/deactivation, profile edits, approvals (`پیوست ۷` بند ۱); finance screens (transactions, sold products, manual invoice, totals) (`پیوست ۷` بند ۲); marketing/store/transaction/settlement reports with Excel export (`پیوست ۷` بند ۱۰); gateway and general settings (`پیوست ۷` بند ۹); audit-log review screen; read-only reporting module with materialised daily aggregates; PWA manifest, icons, splash, smart caching, installable on Android and iOS from the browser (`پیوست ۱` بند ۴); technical SEO: metadata, sitemap, clean URLs, structured data, page-speed work targeting GTmetrix B/A (`پیوست ۹` بند ۱–۲); security hardening: SSL enforcement, brute-force lockout, CAPTCHA on public forms, input validation, role checks (`پیوست ۹` بند ۳); responsive QA at 360/768/1280 px (`پیوست ۱` بند ۳).
- **Exit criteria:** Lighthouse PWA installable + performance ≥ 80 on the product list; every admin screen has a policy test; N+1 audit passes with `preventLazyLoading` enabled in CI; slow-query log clean for the top-10 pages; reviewer sign-off.
- **Demo:** install the PWA from the browser on a phone, then show the admin settlement report export.
- **Risks:** PWA caching serving stale prices — prices always fetched network-first with a short TTL; scope creep into native app territory (explicitly out of scope, `RB-MOB-01`).
- **Duration:** 34 pd / **7 wd**.
- **Dependencies:** all functional phases (it surfaces their screens).

### Phase 10 — Acceptance (UAT), training and handover (v1.0.0)

- **Objective:** Convert a working system into a contractually accepted delivery.
- **Included REQ areas:** CTR (acceptance, training, documentation, handover).
- **Deliverables:** staging deployment on the client's server with the client's domain and SSL (`ماده ۱`); acceptance scenario pack executed by the client with defects logged within 3 working days and re-tested (`پیوست ۱۰` بند ۱–۳); 4 in-person + 25 online training sessions (`پیوست ۱۰` بند ۵); technical and user documentation, training videos, source bundle with install guide and folder-structure documentation (`پیوست ۱۰` بند ۶; `ماده ۴` بند ۴); handover package per `release-plan.md` §7; written satisfaction letter triggering final delivery; warranty and support handover note (5 years bug-fix, SLA table).
- **Exit criteria:** all 13 appendix headings signed off individually; every P0 acceptance scenario green on the client's server; zero open critical/major defects; handover package accepted in writing; `v1.0.0` tagged and the release branch archived.
- **Demo:** the client's own operator drives the acceptance script unaided; the delivery lead shows the tagged source, the docs and the restore drill evidence.
- **Risks:** client-side delay supplying content or the server (`ماده ۵`) — every day of client delay extends the deadline day-for-day and must be recorded in the weekly report (`ماده ۳`); acceptance disputes on ambiguous clauses (`AMB-*` register is the defence).
- **Duration:** 22 pd / **6 wd**.
- **Dependencies:** Phases 0–9 complete; client server, domain, SSL and gateway credentials available.

---

## 5. Schedule: contract window vs engineering estimate

| Measure | Value |
|---|---|
| Contract execution window (`ماده ۳`) | **90 calendar days** |
| Capacity in that window at 5-day weeks | **64 working days** |
| Bottom-up engineering estimate | **334 person-days** |
| Critical-path elapsed at 4.4 effective streams | **76 working days** |
| Elapsed in calendar days (× 7/5) | **107 calendar days** |
| **Gap vs contract window** | **+17 calendar days** (≈ 12 working days of float consumed) |
| Penalty exposure if the gap is not closed or extended | 0.5 % × 17 = **8.5 % = 7,650,000 Toman** |
| Distance to the 15-working-day termination trigger (`ماده ۹`) | 21 calendar days → a **4-day buffer** remains |
| Distance to the 20 % penalty cap (`ماده ۴`) | 40 days → not reached at 17 |

**Reading of the gap.** 334 pd of work does not fit in 64 working days without 5.2 full-time streams, and the dependency chain (contracts → identity → catalog → orders → money) resists compression. The 76-working-day plan already assumes six parallel streams and the file-ownership boundaries in `dependency-map.md`. The residual 17-day gap is therefore **structural, not a staffing problem**, and it is treated as a contractual risk with four mitigation levers, in order of preference:

| Lever | Effect | Cost / trade-off | Contract basis |
|---|---|---|---|
| M1. Written extension request opened in week 2 | removes the gap and the termination trigger | requires client consent; must be in writing | `ماده ۳` (extension by written agreement) |
| M2. Overlap Phases 7–8 with Phase 9 hardening | recovers 4–6 wd | higher integration risk; needs the ownership boundaries to hold | engineering decision |
| M3. Defer Phase 8 extensions (TND/CLS/PTG) to a post-delivery iteration under the 5-year warranty | recovers 4 wd | **5 % penalty per unexecuted appendix heading, cap 10 %** = 4,500,000 per heading, 9,000,000 if two | `ماده ۴` بند ۵ |
| M4. Reduce Phase 9 to SEO/perf essentials and ship the reporting module read-only in v1.1 | recovers 3–4 wd | weaker admin analytics at acceptance; must be written into the acceptance script | `پیوست ۷` بند ۱۰ |

**Mitigation chosen:** M1 + M2. M3 and M4 are only used if the client refuses an extension, and only with a written scope note, because their penalty (up to 9,000,000) is the same order of magnitude as the delay penalty they avoid (7,650,000) while also weakening acceptance.

**Honest statement for the weekly report:** the engineering estimate exceeds the contractual window by 17 calendar days (~19 % of the window). Any week where completed effort trails the plan by more than 2 working days is escalated in `progress.md` the same week, not at the phase gate.

---

## 6. Milestones, gates and client-facing calendar

| Milestone | Gate content | Planned day (calendar, from kickoff) | Client-visible evidence |
|---|---|---|---|
| M0 Kickoff & baseline | Phase 0 exit; extension request M1 filed | day 7 | CI page, compose one-command demo, requirement baseline |
| M1 Identity live | Phase 1 exit | day 19 | role/secondary-role demo, audit log screenshot |
| M2 Catalog live | Phase 2 exit | day 33 | three-price demo on one SKU |
| M3 Order-to-invoice | Phase 3 exit | day 47 | two-supplier order walkthrough |
| M4 Payment + ledger | Phase 4 exit | day 58 | replayed callback, single ledger entry, settlement statement |
| M5 Marketer network | Phase 5 exit | day 66 | QR scan → attributed invoice |
| M6 Loyalty engine | Phase 6 exit | day 76 | replay-proof points |
| M7 Engagement suite | Phase 7 exit | day 83 | throttled 500-recipient notification |
| M8 Extensions | Phase 8 exit | day 89 | classified, tender, Patogh quote |
| M9 Release candidate | Phase 9 exit | day 98 | PWA install, Lighthouse, admin export |
| M10 UAT start | staging on client server | day 99 | acceptance script pack |
| M11 Final delivery | signed satisfaction letter | day 107 | handover package, `v1.0.0` |
| M12 Settlement | 1 month after written confirmation | day ~137 | invoice for 80,000,000 Toman (`ماده ۲`) |

Payment milestones follow the contract exactly: 10,000,000 Toman at signing (C-02) and 80,000,000 Toman one month after final delivery **and** written confirmation (C-03). No engineering gate is tied to a payment date, so a payment delay does not stop the build; it is recorded as a blocker in `progress.md`.

---

## 7. Top delivery risks

| ID | Risk | Likelihood | Impact | Mitigation | Owner |
|---|---|---|---|---|---|
| R-01 | 17-day structural gap vs the 90-day window | high (certain unless M1/M2) | 8.5 % penalty + termination trigger at 21 days | M1 written extension in week 2; M2 overlap; weekly burn-down | Delivery lead |
| R-02 | Client content/server delay shrinks effective build time | medium | day-for-day deadline extension must be claimed, not absorbed | log every client-dependency date; cite `ماده ۳` and `ماده ۵` in the weekly report | Delivery lead |
| R-03 | `پیوست ۱` بند ۵ (12 gateways, two concurrent) vs v1 single active gateway (`RB-PAY-01`) | high | 5 % heading penalty (4,500,000) | deliver 12 drivers + admin activation in v0.5, split payment as a documented annex item; raise `AMB-PAY-001` for a written decision | Architect |
| R-04 | Reward/points rules ambiguous across `پیوست ۴` and `پیوست ۱۲` | high | rework in the highest-risk money code | versioned rule sets, admin-editable coefficients, replay tests, ambiguity register | Loyalty owner |
| R-05 | Parallel agents colliding in shared files | medium | merge conflicts, broken `main` | file-ownership boundaries + contract-first merge order in `dependency-map.md`; `main` protected, no direct push | Orchestrator |
| R-06 | Acceptance disputes over unsignposted clauses | medium | delay at the worst moment | per-heading sign-off at every phase gate, not only at UAT | Reviewer |
| R-07 | MySQL 8.4 vs the host's absence of PHP | low | onboarding friction | container-only tooling, one-command wrapper, documented in `docs/operations/local-development.md` | Platform |

---

## 8. Governance of this schedule

- The plan is re-forecast at every phase gate: actual wd per phase, variance, and the new projected completion date are written into `progress.md`.
- Any change to phase scope requires a `REQ` or `AMB` entry and a note in `release-plan.md`; verbal scope changes have no engineering effect (`ماده ۸`).
- Weekly written progress report with screenshots or a demo link is a contractual obligation (C-10) and is produced from `progress.md` every week.
- Definition of Ready gates task start (`definition-of-ready.md`); Definition of Done gates merge (`definition-of-done.md`).
