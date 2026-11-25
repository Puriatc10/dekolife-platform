# Product Requirements Document — Dekolife Web Platform

| | |
|---|---|
| **Purpose** | Define what is being built in this mission, for whom, in what order, and what is explicitly *not* being built — with a traceable contract pointer for every claim. |
| **Audience** | Client (scope sign-off), engineering leads, UX, QA, delivery management. |
| **Status** | Baseline v1 — 2026-11-25. Feature scope is frozen here; requirement-level detail continues in the requirements matrix owned by the requirements agent, and rule detail in [business-rules.md](./business-rules.md). |
| **Mission scope** | **Web application only** (server-rendered, RTL-first). Native Android/iOS deferred (`RB-MOB-01`); PWA ships as a web deliverable. Reservation/appointment module out of scope (separate contract no. 2). |

> **Source:** Contract `DK-1405-01` (pages 1–11; appendices 1–14) and [`../canonical-context.md`](../canonical-context.md). Companions: [vision](./product-vision.md), [personas](./personas-and-roles.md), [journeys](./user-journeys.md), [use cases](./use-cases.md), [rulebook](./business-rules.md), [glossary](./glossary.md).

---

## 1. What the product is

A multi-vendor marketplace for decoration/building-finish materials (parquet and flooring first) where **producers/suppliers sell to stores at tiered wholesale prices and to consumers at a consumer price**, while a **field-sales network** onboards stores door-to-door with QR attribution, and a **points → tier → wallet** engine plus **12 payment gateways**, **commissions**, **messaging**, **classifieds (دیوار)**, **local services (پتوق)**, **tenders/auctions** and **admin accounting** sit around that core (page 1 / ماده ۱; page 5–6 / پیوست ۳; canonical §1).

**Product objectives for this release**

| # | Objective | Measured by |
|---|---|---|
| O1 | A store can see its real price and order without a phone call | store order placed entirely in-app; `M1`, `M3` ([vision §6](./product-vision.md#6-success-metrics)) |
| O2 | A marketer can register a store in ≤5 minutes on a phone and prove ownership | registration completion time; `M2` |
| O3 | Every Toman is traceable from gateway to wallet to bank file | reconciliation exceptions; `M10` |
| O4 | Loyalty is automatic, visible and auditable | `M4`, `M5` |
| O5 | Disputes resolve with money held, not money lost | `M7` |
| O6 | Admin can run the marketplace without a developer | rule-set changes shipped with zero releases |

## 2. Scope

### 2.1 In scope (this mission)

| Area | Included | Contract anchor |
|---|---|---|
| Identity & roles | registration with SMS verification, login, password reset, profile edit, 5 levels, optional secondary level, admin 2FA on a separate panel path | page 5 / پیوست ۱ / بند ۷; پیوست ۲ / بند ۸; پیوست ۳; پیوست ۷ / بند ۶ |
| Catalogue | categories, attributes/filters, product page with gallery/video/specs/ratings/related, compare ≤5, search & sort | page 5 / پیوست ۲ / بند ۲–۵ |
| Pricing | per-role price matrix, volume bands, consumer uplift, promotions, coupons/vouchers, cost-floor flag | page 9 / پیوست ۷ / بند ۴; page 11 / پیوست ۱۲ / بند ۱, بند ۴ |
| Cart & checkout | cart, coupon/voucher, shipping choice/fee/free-shipping, deposit or full payment, pre-invoice | page 5 / پیوست ۲ / بند ۶; page 8 / پیوست ۵ / بند ۴۰, ۴۲; page 11 / پیوست ۱۲ / بند ۱۲–۱۳ |
| Orders | parent + per-seller sub-orders, accept/dispatch/deliver, holds, incomplete orders, cancellation, returns, supplementary invoices | page 7 / پیوست ۴ / بند ۱۵–۱۶; page 10 / پیوست ۱۱ / بند ۱۲–۱۵; page 11 / پیوست ۱۱ / بند ۱۷ |
| Payments | 12 gateway drivers, admin activation, idempotent capture, refunds, daily reconciliation | page 5 / پیوست ۱ / بند ۵; page 11 / پیوست ۱۳ / بند ۶–۷ |
| Finance | append-only ledger, wallet, statement, settlement requests, Excel bank file | page 6 / پیوست ۳ / ۳-۱ / ۴, ۳-۲ / ۲; page 11 / پیوست ۱۳ / بند ۱–۵ |
| Loyalty | points formula, coefficients, 4× redemption, reward credit, inactivity, stars, tiered discount, campaign windows, first-invoice discount | page 6–7 / پیوست ۴ / بند ۱–۱۰, بند ۲۰–۲۱ |
| Marketing & field ops | QR/code/link attribution, on-site registration form, duplicate checks, 5-minute edit window, presentation form, geo visits, live tracking, performance analysis, plans A–D | page 7 / پیوست ۵ / بند ۱–۳; page 9 / پیوست ۶; page 11 / پیوست ۱۴ |
| Catalogue-adjacent services | classifieds (taxonomy-gated), Patogh directory + request/quote, samples/work portfolio/catalogue requests, tenders, auctions | page 7 / پیوست ۵ / بند ۴–۶, بند ۱۳; page 10 / پیوست ۱۱ / بند ۹–۱۰ |
| Messaging & notifications | internal messenger with persistent history + attachments, tickets both ways, SMS/WhatsApp/email/push, custom & paid notifications, polls | page 8 / پیوست ۵ / بند ۹–۱۲, بند ۲３, بند ۲۶, بند ۲۸ |
| Content & SEO | homepage widgets, banners, static pages, menus, blog/articles, ad taxonomy, technical SEO, sitemap, structured data | page 5 / پیوست ۲ / بند ۱۰–۱۲; page 9 / پیوست ۷ / بند ۹; page 10 / پیوست ۹ / بند ۱ |
| Admin | members/approvals, financial reports, manual invoices, rule sets, disputes, content, banners, gateways, Excel exports, audit log | page 9 / پیوست ۷ / بند ۱–۱۰; page 5 / پیوست ۱ / بند ۱۰ |
| Cross-cutting | RTL-first responsive UI, Jalali dates, integer-Toman money, audit trail, daily DB backup, PWA manifest + offline shell | page 5 / پیوست ۱ / بند ۳–۴, بند ۹; page 10 / پیوست ۹ |

### 2.2 Out of scope (this mission)

| Excluded | Reason | Tracked as |
|---|---|---|
| Native Android APK and iOS app, store publishing | Web-only mission; contract requires them but they are a later deliverable | `RB-MOB-01` (deferred; roadmap in [vision §7](./product-vision.md#7-three-year-direction)) |
| Appointment/reservation module (نوبتدهی/رزرواسیون) | Explicitly a **separate contract** (no. 2, 35,000,000 Toman, 1 month); shared login is a future addendum | page 9 note under پیوست ۵ |
| Split payment / two gateways in one transaction | Conflicts with the v1 one-gateway-per-payment constraint | `RB-PAY-01`; conflict logged in [rulebook §14](./business-rules.md#14-known-conflicts-and-open-questions) |
| Carrier/logistics integration, proof of delivery, carrier settlement | No carrier role or API is specified; dispatch is a supplier statement plus waybill text | [personas §5](./personas-and-roles.md#5-what-no-persona-covers) |
| Marketplace lending / BNPL, insurance, escrow products | Not in contract; adjacent to the finance module but commercially separate | — |
| Multi-language UI (English/Arabic) | Contract specifies a Persian product; architecture keeps strings extractable but no second locale ships | — |
| Real-money crypto/PSP beyond the 12 named bank gateways | Contract fixes bank gateways | — |

## 3. Release phases

The contract fixes the commercial envelope — **90 days**, three build months plus an analysis/UX phase, 5-year bug-fix warranty, support first response ≤24 h (page 1; page 2 / ماده ۳; page 4 / ماده ۱۲). The phases below are the **feature cut**; mapping them onto calendar sprints is the delivery plan's job (delivery agent), and the cut is what QA signs off per phase.

| Phase | Theme | Contents | Exit criteria |
|---|---|---|---|
| **P0 — Trade core** | "A store can buy, a marketer can sign it, the money reconciles" | Identity/roles + 2FA; catalogue & product page; pricing matrix + volume bands; cart/checkout with shipping & deposit; orders + sub-orders + state machine; payments (first live gateways + driver contract); invoices; ledger + wallet + settlement + Excel; points/rewards/tiers/stars; QR attribution + on-site registration + approvals; complaints/holds/incomplete/delay compensation/violations; admin console + rule sets + audit | End-to-end journey `J-01`, `J-02`, `J-04`, `J-05`, `J-06`, `J-07`, `J-08`, `J-12` pass on staging with real gateway sandbox; money reconciles to the Toman |
| **P1 — Network services & depth** | "The marketplace around the trade" | Classifieds (دیوار) with taxonomy gate; Patogh directory + request/quote; tenders + auctions; samples/catalogue/work portfolio; reviews & polls; internal chat with attachments; custom + paid notifications; supplier promotion surfaces (festival, slideshow, banner requests, SMS panel, agency map); CMS/blog/SEO; wishlist; supplier panel depth (feeds, photography service) | Journeys `J-03`, `J-09`, `J-10`, `J-11`, `J-13`, `J-14` pass; SEO crawl clean; export files accepted by the client's bank |
| **P2 — Reach & polish** | "Every browser, every phone, every operator" | PWA (manifest, offline shell, push), performance to GTmetrix B/A target, accessibility pass, browser matrix (Chrome/Firefox/Safari/Edge + mobile), bulk data import, training material, hardened rate-limiting/CAPTCHA, reporting polish | Lighthouse/GTmetrix target met; 4 in-person + 25 online training sessions delivered; acceptance run by the client with defects logged within 3 working days |

**Deliberately sequenced this way** because pricing, orders and money are the only areas where a late change is expensive; classifieds/Patogh/tenders are additive surfaces whose data model is already covered by the module boundaries.

## 4. Role-based feature areas

Each cell states the capability the role gets; the module is where it lives; P0/P1/P2 is the phase. Anchors are contract clauses (see [personas](./personas-and-roles.md) for the full permission narrative).

### 4.1 Store (فروشگاه / همکار)

| Capability | Module | Phase | Anchor |
|---|---|---|---|
| Browse catalogue, filters, compare, wishlist | Catalog | P0 | پیوست ۲ / بند ۳–۵ |
| See colleague + wholesale price | Pricing | P0 | پیوست ۳ / ۳-۱ / ۸ |
| Cart, coupon, shipping, deposit, checkout | Cart / Payments | P0 | پیوست ۲ / بند ۶; پیوست ۱۲ / بند ۱۲–۱۳ |
| Orders, invoices, tracking code, reorder | Orders / Invoices | P0 | پیوست ۳ / ۳-۱ / ۱, ۷ |
| Points, stars, tier discount, wallet | Loyalty / Finance | P0 | پیوست ۴ / بند ۹, ۲۰–۲۱ |
| Complaint, freeze, `وارن`, review | Orders / Reviews | P0 | پیوست ۴ / بند ۱۵–۱۹ |
| Statement + settlement request | Finance | P0 | پیوست ۳ / ۳-۱ / ۴ |
| Publish store profile & announcements, classifieds, Patogh presence | Merchants / Classifieds / Patogh | P1 | پیوست ۳ / ۳-۱ / ۱, ۵ |
| Tenders, auctions, samples/catalogue requests | Tenders / Samples | P1 | پیوست ۳ / ۳-۱ / ۶, ۹ |
| Messaging, tickets, internal chat | Messaging | P1 | پیوست ۳ / ۳-۱ / ۳, ۷ |

### 4.2 Marketer (بازاریاب)

| Capability | Module | Phase | Anchor |
|---|---|---|---|
| Personal code, link, QR | Marketing | P0 | پیوست ۵ / بند ۱ |
| On-site registration form (full store data + photos + map pin) | Marketing / Merchants | P0 | پیوست ۵ / بند ۲; پیوست ۶ / بند ۱–۲ |
| Duplicate detection (national ID / postal / mobile) | Marketing | P0 | پیوست ۵ / بند ۳ |
| 5-minute edit window, then change requests | Marketing | P0 | پیوست ۶ / بند ۹ |
| My stores, active/inactive view, invoices of visited stores | Marketing / Reporting | P0 | پیوست ۳ / ۳-۲ / ۹–۱۰; پیوست ۶ / بند ۱۱ |
| Commission & plan status, settlement request, statement | Compensation / Finance | P0 | پیوست ۶ / بند ۳, ۵–۶; پیوست ۳ / ۳-۲ / ۲ |
| Geo visit logging, route, scorecard | FieldOps | P1 | پیوست ۱۴ / بند ۱–۲ |
| Presentation form for inactive stores | Marketing | P1 | پیوست ۶ / بند ۷ |
| Tickets, chat, complaints to admin, customer/project order forms | Messaging / Orders | P1 | پیوست ۳ / ۳-۲ / ۱, ۵, ۷–۸ |

### 4.3 Consumer (مصرفکننده)

| Capability | Module | Phase | Anchor |
|---|---|---|---|
| Register, panel, orders, addresses, wishlist | Identity / Users | P0 | پیوست ۲ / بند ۷–۸; پیوست ۳ / ۳-۳ |
| Consumer price plane, pre-invoice, deposit | Pricing / Invoices / Payments | P0 | پیوست ۱۲ / بند ۱, ۱۳; پیوست ۵ / بند ۴۲ |
| Compare, search, postal shipping, free-shipping rules | Catalog / Shipping | P0 | پیوست ۱۲ / بند ۵, ۱۲ |
| Points, vouchers, birthday/occasion messages | Loyalty / Promotions | P1 | پیوست ۱۲ / بند ۴, ۷ |
| Reviews (1 week window, 5–10 params) | Reviews | P1 | پیوست ۱۲ / بند ۹ |
| Patogh installer search + request | Patogh | P1 | پیوست ۵ / بند ۵, ۱۳ |
| Tender submission, intermediary order | Tenders / Orders | P1 | پیوست ۳ / ۳-۳ / ۵; پیوست ۱۲ / بند ۳ |
| Act as marketer simultaneously | Identity / Marketing | P1 | پیوست ۱۲ / بند ۱۱ |

### 4.4 Supplier / producer (تأمینکننده / تولیدکننده)

| Capability | Module | Phase | Anchor |
|---|---|---|---|
| Independent panel: products, per-role prices, stock, orders, sales reports | Catalog / Pricing / Inventory / Reporting | P0 | پیوست ۳ / ۳-۴; پیوست ۷ / بند ۴; پیوست ۱۱ / بند ۱ |
| Accept, dispatch, waybill, delay recording | Orders / Shipping | P0 | پیوست ۱۱ / بند ۱۵ |
| Incomplete order + cancel/refund | Orders / Payments | P0 | پیوست ۱۱ / بند ۱۲–۱۴ |
| Supplementary invoice, extra cost, manual admin adjustment | Invoices / Finance | P1 | پیوست ۱۱ / بند ۱۶–۱۷ |
| Brand page, agency map, festivals, slideshow, banner request | Merchants / Promotions / Cms | P1 | پیوست ۱۱ / بند ۳–۵, ۸, ۱۹ |
| Notifications & SMS panel (paid or free, capped) | Notifications / Messaging | P1 | پیوست ۱۱ / بند ۶–۷ |
| Auctions, sample requests, product photography service | Tenders / Samples | P1 | پیوست ۱۱ / بند ۹–۱۰, ۲۰ |
| Buying-colleague mode (fixed discount from other suppliers) | Pricing / Merchants | P2 | پیوست ۱۱ / بند ۲ |

### 4.5 Admin (ادمین)

| Capability | Module | Phase | Anchor |
|---|---|---|---|
| Member management, approvals, secondary levels, deactivation | Admin / Identity | P0 | پیوست ۷ / بند ۱, ۶ |
| Product form incl. per-role pricing, attributes, categories, gallery, stock | Admin / Catalog | P0 | پیوست ۷ / بند ۴ |
| Rule sets: points, tiers, rewards, commissions, uplift, caps | Admin / Loyalty / Compensation | P0 | پیوست ۴ / بند ۳, ۲۰–۲۱; پیوست ۶ / بند ۵–۶ |
| Finance: transactions, sold-product report, manual invoice, per-user totals | Finance / Invoices | P0 | پیوست ۷ / بند ۲ |
| Disputes: complaints, holds, delay compensation, violations | Orders / Reviews | P0 | پیوست ۴ / بند ۱۴–۱۹ |
| Settlement runs + Excel/bank export | Finance / Reporting | P0 | پیوست ۷ / بند ۱۰; پیوست ۱۳ / بند ۳ |
| Content: banners, pages, menus, gateways, site settings, notifications composer | Cms / Notifications | P1 | پیوست ۷ / بند ۵, ۹; پیوست ۵ / بند ۹, ۱۲ |
| Reports: marketer/store/transaction/settlement, inactive-store lists | Reporting | P1 | پیوست ۷ / بند ۱۰; پیوست ۴ / بند ۲۳–۲۴ |
| Tickets both ways, content/user reports, audits | Messaging / Admin | P1 | پیوست ۷ / بند ۷–۸ |

## 5. Key flows (normative summaries)

Full step tables are in [user-journeys.md](./user-journeys.md); this is the shape engineering plans against.

1. **Purchase** — catalogue → product/compare → cart (server-resolved role price, coupon, shipping) → pre-invoice → gateway → parent order + sub-orders + invoices → dispatch → delivery → review/points. Anchors: پیوست ۲ / بند ۲–۶; پیوست ۴ / بند ۱.
2. **Field acquisition** — marketer opens app at the shop → QR scan or on-site form → duplicate check → credentials issued → admin approval → store active under that marketer. Anchors: پیوست ۵ / بند ۱–۳; پیوست ۶ / بند ۱–۲, ۸.
3. **Fulfilment** — sub-order → accept → pack → dispatch with waybill → delay recorded if late → compensation auto-credited to buyer wallet. Anchors: پیوست ۱۱ / بند ۱۵.
4. **Dispute** — complaint within 7 days of dispatch → hold + supplier payment suspended → evidence → admin decision (compensate / cancel / reject) → release. Anchors: پیوست ۴ / بند ۱۵–۱۶.
5. **Loyalty** — paid invoice → line points by product & volume coefficient → points total → tier (4% / 5% / fixed n) + star count → next invoice auto-discount + SMS → reward credited at threshold → 4× rule or half. Anchors: پیوست ۴ / بند ۱–۱۰, ۲۰–۲۱.
6. **Money** — capture → ledger → commission accrual & vesting → settlement request → validation/reserve → admin approval → Excel bank batch → paid + audit. Anchors: پیوست ۳ / ۳-۱ / ۴; پیوست ۱۳ / بند ۱–۵.

## 6. Cross-cutting requirements

| # | Requirement | Acceptance shape | Anchor |
|---|---|---|---|
| X1 | **RTL-first, responsive** for mobile/tablet/desktop; Persian UI copy per [glossary](./glossary.md) | no horizontal scroll at 360 px; Jalali dates; Persian numerals where the client requires | پیوست ۱ / بند ۳; پیوست ۲ / بند ۱ |
| X2 | **Money** integer Toman, server-side resolution, snapshotted on every line | a price never originates client-side; every invoice reproducible from its snapshot | canonical §5 |
| X3 | **Authorization** enforced by policies, not by hiding UI | negative test per scoped resource (store cannot see another store's invoices; marketer cannot see unowned stores) | canonical §5; پیوست ۶ / بند ۱۰ |
| X4 | **Audit** on login, price change, role change, settlement | audit row with actor, before/after, reason, IP | پیوست ۱ / بند ۱۰ |
| X5 | **Payments** 12 gateway drivers, one active per payment, idempotent capture, refunds | duplicate callback = no-op; mismatch opens a reconciliation case | پیوست ۱ / بند ۵; پیوست ۱۳ / بند ۶–۷ |
| X6 | **Notifications** SMS/WhatsApp/email/push, per-user channel switch, templated | transactional messages cannot be suppressed by marketing prefs | پیوست ۵ / بند ۲۳, ۲۶; پیوست ۷ / بند ۳ |
| X7 | **PWA** installable from browser with manifest, icons, splash, offline shell | installable on Android + iOS browsers; no native wrapper claimed | پیوست ۱ / بند ۴; پیوست ۵ / بند ۲۵, ۲۷; پیوست ۸ / بند ۳ |
| X8 | **Performance & SEO** clean URLs, meta, sitemap, structured data, GTmetrix B/A | crawl audit clean; target score met on staging | page 10 / پیوست ۹ / بند ۱–۲ |
| X9 | **Security** SSL, brute-force and spam protection (CAPTCHA), input validation, admin 2FA, private storage for identity docs | OWASP-style checks; no secrets in repo | page 5 / پیوست ۱ / بند ۷; page 10 / پیوست ۹ / بند ۳; canonical §1 |
| X10 | **Backup** daily DB backup with restore drill | restore into staging succeeds | پیوست ۱ / بند ۹ |
| X11 | **Extensibility** a new user level or product type must not require core rewrite | add a role + capabilities without schema change | پیوست ۱ / بند ۸ |
| X12 | **Reproducibility** every points/commission/settlement computation replayable from the ledger + rule-set version | replay command yields identical totals | canonical §5; rulebook `BR-LOY-11` |

## 7. Non-goals

Explicitly **not** product goals, so nobody builds them by accident:

1. **Not a logistics platform.** Shipping is a choice plus a waybill string; no carrier API, no live tracking of goods, no proof of delivery (page 8 / پیوست ۵ / بند ۴۰).
2. **Not a general accounting system.** The ledger records platform money movements; it does not produce statutory financial statements or VAT returns.
3. **Not a CRM.** The marketer's visit log and store record are the whole CRM; no pipelines, no email sequences, no lead scoring beyond plan gates.
4. **Not a content marketplace.** Blog/CMS exists for SEO and announcements (پیوست ۲ / بند ۱۰), not as a media product.
5. **Not a native app in this mission** (`RB-MOB-01`).
6. **Not a reservation system** (separate contract).
7. **Not a price-comparison engine across other marketplaces.** Comparison is within the platform, up to 5 products (پیوست ۲ / بند ۵).
8. **Not an escrow bank.** The platform holds funds operationally and settles per period; it does not offer credit, interest or guarantees (`AMB-FIN-001`).

## 8. Open questions (blocking or shaping decisions)

| # | Question | Default taken so the build proceeds | Ambiguity |
|---|---|---|---|
| Q1 | Is a "point" a raw Toman value or a scaled unit? The contract fixes 100 points = 1 star, which only makes sense with a scaled unit. | 1 point = 1,000 Toman of reward value | `AMB-LOY-001` |
| Q2 | What are the volume bands behind the 0.75/0.5/0.25/0.2% coefficients? | 10M / 30M / 80M Toman thresholds | `AMB-PRI-003` |
| Q3 | Points tier boundary: does exactly 100 points earn 4% or 5%? | 100 → 5%; bands are 1–99 / 100–200 / ≥201 | `AMB-LOY-004` |
| Q4 | What is the fixed "n Toman" discount above 200 points? | 250,000 Toman, admin-editable | `AMB-LOY-005` |
| Q5 | At what point balance is the cash reward credited, and does it expire? | 500 points; 180-day validity | `AMB-LOY-004` |
| Q6 | When a reward cannot be fully deducted, does the remainder carry or lapse? | Carries forward (contract says points are preserved) | `AMB-LOY-003` |
| Q7 | Inactivity deduction values and pause conditions | −4 points / 30 days after 60 idle days; paused on open complaint | `AMB-LOY-002` |
| Q8 | Do referral/bonus points buy stars? | No — stars from purchase points only, no decay | `AMB-LOY-006` |
| Q9 | Referral points timing — "on entry" or on first order? | On the invitee's first paid invoice (deviation, flagged) | `AMB-MKT-001` |
| Q10 | Commission plan values for A/B/C/D and the "activity/score" gate | 2% / 150,000 per visit / 5M+1% / 12M salary with quarterly KPI gate | `AMB-CMP-001` |
| Q11 | Commission vesting and clawback on returns | Vests 30 days after delivery; reversed on cancel/refund/return | `AMB-CMP-002` |
| Q12 | Complaint window anchored to dispatch or delivery? | Dispatch (clause says "پس از ارسال") | `AMB-ORD-001` |
| Q13 | Delay compensation percentage and cap | 0.5%/day, cap 20% of sub-order, min 1 day | `AMB-ORD-004` |
| Q14 | Penalty values for incomplete-order misuse | −10 points per upheld misuse; 3 in 90 days → review | `AMB-ORD-003` |
| Q15 | Who holds funds between capture and settlement, and what is the platform take-rate? | Platform-collected, ledger, period settlement; 0% default | `AMB-FIN-001` |
| Q16 | Settlement cadence, minimum, credit terms | Weekly, 1,000,000 minimum, second approval above 100M | `AMB-FIN-002` |
| Q17 | May a store also be a marketer? The clause both forbids and says "per admin setting". | Blocked; only admin-tightenable | `AMB-USR-001` |
| Q18 | Do stores publish catalogue SKUs, or only suppliers/admin? | Stores publish profile content, classifieds and Patogh only | `AMB-CAT-001` |
| Q19 | Do classified ads require moderation, and how many may a user hold? | Moderated, 3 active, 5 images | `AMB-CLS-001` |
| Q20 | Does Patogh carry payment or commission? | No payment, no commission in v1 | `AMB-PTG-001` |
| Q21 | Paid notification pricing and audience caps | Per-1,000-recipient price set by admin; segment caps; content review | `AMB-NTF-001` |
| Q22 | Tender rules: sealed bids, minimum bidders, fees | Sealed, 3 bidders, no fee | `AMB-TND-001` |
| Q23 | Store ownership transfer: does the previous marketer consent? | No; admin decides with a reason | `AMB-MKT-002` |
| Q24 | Inactive-store window before downgrade | 90 days + 14-day warning; never during an open complaint | `AMB-ADM-002` |
| Q25 | Admin sub-roles (finance/support/auditor) | Proposed and seeded, pending approval | `AMB-USR-002` |
| Q26 | Approval SLA for stores/content | 2 business days | `AMB-ADM-001` |
| Q27 | Does an intermediary (رابط) order earn commission? | No in v1; order is tagged as placed on behalf of a consumer | `AMB-ORD-005` |
| Q28 | Violation visibility window and appeal | 60 days visible, 14-day appeal | `AMB-ORD-006` |
| Q29 | Auto-confirm delivery window | 7 days from dispatch | `AMB-ORD-007` |
| Q30 | Total discount ceiling and instrument stacking | 30% ceiling, 1 coupon + 1 voucher | `AMB-LOY-008` |
| Q31 | Cost floor: may a discount push a line below cost? | Allowed but flagged to admin | `AMB-PRI-004` |

## 9. Acceptance and definition of done

A feature is **done** when, on staging against MySQL with real gateway sandboxes:

1. Its journeys (`J-*`) or use cases (`UC-*`) pass as automated feature tests, including **negative authorization** tests for every scoped resource.
2. Every money, points, commission and settlement calculation has a test, and the replay command reproduces historical totals exactly.
3. Persian UI copy reviewed against the [glossary](./glossary.md); no English leakage in user-facing strings; RTL layout verified at 360/768/1280 px.
4. Every numeric rule in [business-rules.md](./business-rules.md) exists as a versioned rule-set row, not a constant in code.
5. Notifications for the feature's events are templated and logged; failures are visible to admin.
6. Admin can perform the feature's configuration and reporting without a deployment.
7. The client's acceptance run logs defects within 3 working days, and the final handover follows the written-approval step (page 10 / پیوست ۱۰ / بند ۱–۴).

## 10. Risks and mitigations

| Risk | Impact | Mitigation |
|---|---|---|
| Points-unit ambiguity (`AMB-LOY-001`) resolved wrongly | Every reward, star and tier is off by 1,000× | Rules are versioned rows with a replay command; changing the divisor + thresholds is a config migration, not a code rewrite |
| Gateway fragmentation (12 drivers) | Payments delay the launch | Ship 2 gateways in P0 behind the driver contract; the rest are configuration, not code changes |
| Client expects native apps in this mission | Scope dispute | `RB-MOB-01` is documented in scope, roadmap and this PRD; PWA is delivered and explicitly not called native |
| Dual-gateway clause in پیوست ۱ / بند ۵ | Conflict with canonical | Deferred as `RB-PAY-01`, logged in [rulebook §14](./business-rules.md#14-known-conflicts-and-open-questions), raised to the orchestrator |
| Settlement disputes due to unclear fund ownership | Financial/legal exposure | Conservative platform-collected model + append-only ledger + bank-file reconciliation (`AMB-FIN-001`) |
| Marketer fraud (fake registrations, duplicate claims) | Commission leakage | Duplicate keys, geo-verified visits, first-invoice referral credit, admin transfer control, self-dealing block |
| Discount stacking erodes margin | Unprofitable orders | 30% cap, cost-floor flag, per-campaign reporting |
