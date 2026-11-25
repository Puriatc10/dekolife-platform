# Scope Boundaries — What This Mission Builds, What It Defers, What It Refuses

| | |
|---|---|
| **Purpose** | Draw the line between the contract's full obligation and *this* mission's deliverable, so nothing is silently dropped and nothing is silently invented. Every exclusion here has an ID, a reason and a home. |
| **Audience** | Orchestrator, planning/architecture agents, anyone tempted to add a mobile build, a booking calendar or a feature the contract never asked for. |
| **Status** | Baseline v1.0 — derives from [`../canonical-context.md`](../canonical-context.md) §0 and the contract; conflicts are flagged rather than smoothed over. |
| **Source** | `input/contract/1-قرارداد-دکولایف-۹۰م-ویرایش.pdf` pages 1–11; `docs/canonical-context.md` §0 (mission) and §5 (baselines). |

## 1. The three-way split

The contract obliges the contractor to deliver web **and** mobile, in 90 days, for 90,000,000 Toman. This mission delivers the **web application**. That difference is not a scope reduction of the contract — it is a sequencing decision of the owner, recorded here so that the gap is visible, priced and reversible.

| Class | Meaning | Where it goes |
|---|---|---|
| **In scope (now)** | Built, tested and shipped in this mission | this document §2 |
| **Deferred** | Contractually required, deliberately postponed, with an ID and a roadmap slot | §3 + `RB-*` items |
| **Out of scope (structural)** | Not part of this contract at all | §4 |
| **Not ours to build** | Client-side/third-party obligations | §5 |

Rule from [`../canonical-context.md`](../canonical-context.md) §0: *no requirement may be silently dropped; anything postponed gets an ID, a reason and a roadmap entry.* This document is the enforcement point for that rule at the contract level.

## 2. In scope for this mission

### 2.1 Platform foundation

| Item | Contract basis |
|---|---|
| Laravel + MySQL implementation, three-layer architecture, modular structure extensible without core rewrites | page 5 / پیوست ۱ بند ۱, بند ۸ |
| Fully documented code: README, API docs, table-structure documentation | page 5 / پیوست ۱ بند ۲; page 3 / ماده ۴ بند ۴ |
| Fully responsive UI for mobile, tablet, desktop; RTL-first Persian | page 5 / پیوست ۱ بند ۳; page 5 / پیوست ۲ بند ۱ |
| Browser compatibility incl. mobile variants (Chrome, Firefox, Safari, Edge) | page 5 / پیوست ۱ بند ۶ |
| Separate admin panel on a dedicated path with 2FA for the admin role | page 5 / پیوست ۱ بند ۷ |
| Automatic daily database backup with restore | page 5 / پیوست ۱ بند ۹ |
| Audit log of sensitive events (login, price change, level change, settlement) | page 5 / پیوست ۱ بند ۱۰ |
| Security baseline: SSL, brute-force and spam protection with CAPTCHA, role access control, input validation, API-key protection, component updates | page 10 / پیوست ۹ بند ۳; page 3 / ماده ۴ بند ۱۰ |
| Deployment on the client's server, domain + SSL, production smoke test | page 1 / ماده ۱ deliverable 3 |
| Technical SEO: meta, sitemap, clean URLs, structured data; GTmetrix target B/A | page 10 / پیوست ۹ بند ۱–۲ |
| Initial data import for base items (categories, brands, sample products) from client data | page 10 / پیوست ۹ بند ۴ |

### 2.2 Identity, roles, merchants

| Item | Contract basis |
|---|---|
| Five user levels: consumer, store, supplier, marketer, admin | page 5–6 / پیوست ۳ |
| Secondary level per user, enabled/adjusted by admin | page 9 / پیوست ۷ بند ۶ |
| Registration with SMS verification, password recovery, profile editing | page 5 / پیوست ۲ بند ۸ |
| Store registration by marketer in a separate form, credentials issued, forced password change at first login | page 7 / پیوست ۵ بند ۲; page 9 / پیوست ۶ بند ۸ |
| Duplicate-store prevention by national ID + postal code + mobile | page 7 / پیوست ۵ بند ۳ |
| Marketer dedicated code, link and QR that attributes the registering store to that marketer | page 7 / پیوست ۵ بند ۱; page 6 / پیوست ۳-۲ بند ۴ |
| Store profile enrichment with admin approval (site, socials, gallery, hours, field of work) and public display of phone/address/level/points | page 7 / پیوست ۵ بند ۷–۸ |
| Member management: deactivate, delete, edit, verify | page 9 / پیوست ۷ بند ۱ |
| Selling/transferring a membership right with admin approval | page 8 / پیوست ۵ بند ۳۹ |

### 2.3 Catalog, pricing, inventory

| Item | Contract basis |
|---|---|
| Product CRUD with per-user-level price, attributes, short/long description, categories, gallery, warehouse | page 9 / پیوست ۷ بند ۴ |
| Category page with dynamic filters (price, brand, stock, rating, discounted) and sorting | page 5 / پیوست ۲ بند ۳ |
| Product page: gallery, video, specs, reviews, related, compare | page 5 / پیوست ۲ بند ۴ |
| Compare up to 5 products on identical parameters | page 5 / پیوست ۲ بند ۵ |
| Store entry with dedicated and wholesale prices | page 6 / پیوست ۳-۱ بند ۸ |
| Consumer price vs colleague price, manual or percentage uplift (default example 35 %) | page 11 / پیوست ۱۲ بند ۱ |
| Level-based pricing on the pre-invoice / consumer view | page 8 / پیوست ۵ بند ۴۲ |
| Supplier price change and stock management in the supplier panel | page 10 / پیوست ۱۱ بند ۱ |
| Fixed purchase discount for producers who also buy from other suppliers | page 10 / پیوست ۱۱ بند ۲; page 8 / پیوست ۵ بند ۲۰ |

### 2.4 Cart, orders, invoices, payments, finance

| Item | Contract basis |
|---|---|
| Cart, coupon application, shipping-cost calculation, integrated checkout | page 5 / پیوست ۲ بند ۶ |
| Order, ticket, invoice view for stores | page 6 / پیوست ۳-۱ بند ۷ |
| Shipping type and method captured at order time and forwarded to the distributor panel | page 8 / پیوست ۵ بند ۴۰ |
| Pre-invoice for the consumer | page 8 / پیوست ۵ بند ۴۲ |
| Approved-order form with reverse order-number marker, in two views (distributor and buyer) | page 8 / پیوست ۵ بند ۲۴ |
| Payment via gateway, integrated into checkout | page 5 / پیوست ۲ بند ۶; page 1 / ماده ۱ deliverable 4 |
| Supporter of 12 gateways, admin-activatable, behind one driver interface | page 5 / پیوست ۱ بند ۵; page 11 / پیوست ۱۳ بند ۷ — **see §3.2 for the split-transaction part** |
| Wallet, points balance, financial flow per user (debtor/creditor, completed settlements) | page 5 / پیوست ۲ بند ۷; page 11 / پیوست ۱۳ بند ۵ |
| Refund to the buyer's شبا on supplier rejection | page 8 / پیوست ۵ بند ۱۵; page 11 / پیوست ۱۱ بند ۱۴ |
| Deposit payment and settlement of the balance at shipment or on site | page 11 / پیوست ۱۲ بند ۱۳ |
| Manual invoice creation by admin | page 9 / پیوست ۷ بند ۲; page 9 / پیوست ۵ بند ۴۴ |
| Supplier supplementary invoice for extra cost | page 11 / پیوست ۱۱ بند ۱۷ |
| Manual charge/payment on a supplier account by admin | page 11 / پیوست ۱۱ بند ۱۶ |
| Failed/returned transaction reporting and gateway reconciliation | page 11 / پیوست ۱۳ بند ۶ |
| Filterable sales report for manual settlement; daily payment report; Excel export for bank group payment | page 11 / پیوست ۱۳ بند ۱–۳ |
| Marketer/store/transaction/settlement reports with Excel export | page 9 / پیوست ۷ بند ۱۰ |

### 2.5 Marketing, loyalty, compensation

| Item | Contract basis |
|---|---|
| Invoice points from amount, product type, volume; per-product variation | page 6 / پیوست ۴ بند ۱–۲ |
| Volume coefficients 0.75 / 0.5 / 0.25 / 0.2 % | page 6 / پیوست ۴ بند ۳ |
| Next-invoice reward with the 4× threshold and half-reward fallback | page 6 / پیوست ۴ بند ۴ |
| Points preserved regardless of claim | page 6 / پیوست ۴ بند ۵ |
| Inactivity point deduction | page 6 / پیوست ۴ بند ۶; page 8 / پیوست ۵ بند ۱۷ |
| Wallet cash reward on threshold, time-limited or permanent | page 6 / پیوست ۴ بند ۷ |
| Short/medium/long reward windows defined by admin | page 7 / پیوست ۴ بند ۸ |
| Star per 100 points on the profile | page 7 / پیوست ۴ بند ۹ |
| First-invoice/admin-designated invoice discount on top of next-invoice reward | page 7 / پیوست ۴ بند ۱۰; page 11 / پیوست ۱۲ بند ۱۰ |
| Higher cash discount on volume invoices, independent of points | page 7 / پیوست ۴ بند ۱۱ |
| Next-invoice coefficients 0.2 / 0.1 % after wholesale cash discount | page 7 / پیوست ۴ بند ۱۲ |
| Referral points for inviting a colleague | page 7 / پیوست ۴ بند ۱۳ |
| Tiered next-invoice discount 4 % / 5 % with SMS notice | page 7 / پیوست ۴ بند ۲۰; page 8 / پیوست ۵ بند ۱۶ |
| Fixed Toman discount for >200-point users | page 7 / پیوست ۴ بند ۲۱ |
| Decreasing points coefficient for larger volumes | page 8 / پیوست ۵ بند ۲۱ |
| Points differing per product and per store | page 8 / پیوست ۵ بند ۲۲; page 11 / پیوست ۱۳ بند ۴ |
| Coupon and voucher creation | page 9 / پیوست ۵ بند ۴۳; page 11 / پیوست ۱۲ بند ۴ |
| Marketer commission models A/B/C/D, contingent on activity and points | page 9 / پیوست ۶ بند ۵–۶ |
| Marketer pay as sales commission, fixed per visit, mix, or salary | page 9 / پیوست ۶ بند ۳ |
| Commission for marketers who issued consumer-section invoices; points for consumer orders; commission cut when conditions are not met | page 8 / پیوست ۵ بند ۱۸–۱۹ |
| Disabling a marketer's commission or changing their level | page 7 / پیوست ۴ بند ۲۲ |
| Auto-inactivation of stores with no activity in the admin window, and re-visit lists | page 7 / پیوست ۴ بند ۲۳–۲۴; page 9 / پیوست ۶ بند ۴ |
| Money owed for each visit / report of visited stores | page 9 / پیوست ۶ بند ۲, بند ۱۰–۱۱ |

### 2.6 Field operations

| Item | Contract basis |
|---|---|
| Marketer in-person registration with the full field set (owner, national ID, postal code, two phone types, services, category, city, province) | page 9 / پیوست ۶ بند ۱ |
| Photo galleries (owner, storefront, business card) and Google Maps location | page 9 / پیوست ۶ بند ۲; page 9 / پیوست ۶ بند ۷ |
| Customer/lead photos captured by marketer or customer, editable in profile | page 8 / پیوست ۵ بند ۳۲; page 9 / پیوست ۶ بند ۱۲ |
| Location capture for visited places | page 11 / پیوست ۱۴ بند ۱ |
| Live tracking of marketers + performance and route analysis | page 11 / پیوست ۱۴ بند ۲ |
| Edit window of 5 minutes after registration, then admin approval | page 9 / پیوست ۶ بند ۹ |
| Marketer sees only their own stores' data | page 8 / پیوست ۵ بند ۳۰; page 9 / پیوست ۶ بند ۱۰ |

### 2.7 Community features (contract features, not extras)

| Item | Contract basis |
|---|---|
| **دیوار — classifieds**: ads with title, text, image, search, poster profile; categories defined by admin; posting gated on category definitions | page 7 / پیوست ۵ بند ۴; page 5 / پیوست ۳-۱ بند ۵ |
| **پتوق — local services directory**: installers/repairers with request-and-quote; second section for people and skills (e.g. "parquet installer in Tehran") with ticket form | page 7 / پیوست ۵ بند ۵, بند ۱۳ |
| **مناقصه — tenders**: creation by consumer/store, form broadcast to producers and suppliers, participation with form and enquiry | page 6 / پیوست ۳-۳ بند ۵; page 8 / پیوست ۵ بند ۳۶–۳۷; page 7 / پیوست ۵ بند ۶ |
| **مزایده — auctions**: auction section in the supplier panel with suppliers, colleagues and consumers present | page 10 / پیوست ۱۱ بند ۱۰ |
| Blog with list, categories, single article and SEO metadata | page 5 / پیوست ۲ بند ۱۰ |
| Static pages, FAQ, purchase rules, about/contact with map, order tracking by code | page 5 / پیوست ۲ بند ۹, بند ۱۱ |
| Admin-editable footer, static pages, top notification, home widgets | page 5 / پیوست ۲ بند ۱۲ |
| Surveys on the site; product star ratings by verified buyers | page 7 / پیوست ۵ بند ۱۰–۱۱ |
| Reviews and ratings on products, suppliers and invoices (5–10 parameters) | page 5 / پیوست ۲ بند ۴; page 7 / پیوست ۴ بند ۱۷; page 11 / پیوست ۱۲ بند ۹ |
| Favourites for products, stores and producers | page 6 / پیوست ۳-۳ بند ۳; page 11 / پیوست ۱۲ بند ۱۵ |
| Report-content / report-user buttons on profile pages | page 9 / پیوست ۷ بند ۷ |
| Warn button on invoices and producer profiles + negative «امتیاز تخلف» shown after approval | page 7 / پیوست ۴ بند ۱۸; page 8 / پیوست ۵ بند ۱۴ |
| Supplier violation history visible to colleagues at order time | page 7 / پیوست ۴ بند ۱۹; page 8 / پیوست ۵ بند ۳۵ |

### 2.8 Communication

| Item | Contract basis |
|---|---|
| Persistent-memory online chat with any user (seller, admin, marketer) with voice, image, text and files | page 8 / پیوست ۵ بند ۲۸ |
| Dedicated marketer chat page | page 6 / پیوست ۳-۲ بند ۵ |
| Messaging between users, stores, marketers and admin | page 5 / پیوست ۳-۱ بند ۳; page 6 / پیوست ۳-۳ بند ۲ |
| Ticket system both directions (user→admin, admin→all levels) | page 9 / پیوست ۷ بند ۸; page 6 / پیوست ۳-۲ بند ۱ |
| Custom notifications (text, image, link, video) with audience, count and time | page 7 / پیوست ۵ بند ۹ |
| Paid notification purchase with admin review | page 8 / پیوست ۵ بند ۱۲ |
| Supplier notifications to customers (broad or filtered, capped, paid or free) | page 10 / پیوست ۱۱ بند ۶ |
| SMS panel with customer filtering, free or fee-based | page 10 / پیوست ۱۱ بند ۷ |
| Automatic messages after deposit, shipment or order via SMS, WhatsApp and push | page 9 / پیوست ۷ بند ۳ |
| WhatsApp and email as alternative notification channels | page 8 / پیوست ۵ بند ۲۷ |
| Order-status notifications including shipment | page 8 / پیوست ۵ بند ۲۳ |
| SMS notice for the tiered discount | page 7 / پیوست ۴ بند ۲۰ |
| Festival and birthday greetings from a stored birth date | page 11 / پیوست ۱۲ بند ۷ |

### 2.9 Admin, CMS, reporting, delivery

| Item | Contract basis |
|---|---|
| Admin finance view: transactions, sold products, manual invoices, totals | page 9 / پیوست ۷ بند ۲ |
| Banners, static pages, menus, gateway settings, general settings | page 9 / پیوست ۷ بند ۹ |
| Banner reservation requests by suppliers, approved by admin | page 10 / پیوست ۱۱ بند ۵ |
| Festivals/special discounts section shown on the home page; yearly slideshow windows | page 10 / پیوست ۱۱ بند ۴, بند ۸ |
| Product photography service/training offering | page 11 / پیوست ۱۱ بند ۲۰ |
| Producer agencies by city with location, for installation referrals | page 11 / پیوست ۱۱ بند ۱۹; page 11 / پیوست ۱۲ بند ۲ |
| Branding services: UX/UI throughout, visual identity notes | page 10 / پیوست ۹ بند ۵ |
| Acceptance cycle: trial deploy, client logs defects in 3 working days, fix, re-test, written satisfaction letter | page 10 / پیوست ۱۰ بند ۱–۴ |
| Training: 4 in-person + 25 online sessions for admins/key users; training videos | page 10 / پیوست ۱۰ بند ۵–۶ |
| Technical + user documentation package | page 10 / پیوست ۱۰ بند ۶; page 3 / ماده ۴ بند ۴ |
| **PWA**: manifest, icons, splash, service worker with smart caching, installable on Android and iOS, push notifications | page 5 / پیوست ۱ بند ۴; page 8 / پیوست ۵ بند ۲۷; page 9 / پیوست ۸ بند ۳ |

The PWA line is in scope as a **web** deliverable — it is the same codebase served with a manifest and service worker, not a native app, and it is scheduled in a later phase (§3.3).

## 3. Deferred — contractually owed, deliberately not in this mission's first delivery

Each item keeps its ID, its contract basis, its reason and a roadmap slot. Deferral is a **schedule** statement, not a waiver; ماده ۴ بند ۱۱ keeps the 5 %-per-heading exposure alive until the heading is actually executed.

### 3.1 Native mobile applications — `RB-MOB-01`

| | |
|---|---|
| **Contract basis** | page 1 / ماده ۱ (subject includes «اپلیکیشن موبایل (اندروید و iOS و PWA)»); page 9–10 / پیوست ۸ بند ۱–۸ (APK, iOS native or PWA, store release prep) |
| **What is deferred** | Native Android build + APK artefact; native iOS build; Play Store / App Store submission packages; app-specific splash/icon/login screens as native assets |
| **Reason** | The mission deliverable is the web application (canonical §0 non-negotiable 2). A native client multiplies surface area (two codebases, two release pipelines, store policies, device QA) for no functional gain while the web product is still stabilising |
| **Not deferred** | PWA (§2.9) — installable, push-capable, same codebase. Every screen must be built mobile-first so that a future native shell can wrap the API |
| **Consequences to design for now** | All customer capabilities must be reachable through the JSON API (`routes/api.php`, Sanctum), not only through Blade; push notifications must be provider-agnostic; deep links/QR must use web URLs that survive a future app |
| **Reversal cost** | Low-to-medium: a native client is additive if the API and notifications were kept client-agnostic. It becomes expensive only if business logic leaks into Blade views |
| **Reversal trigger** | Owner asks for store release, or the web product passes acceptance and the remaining contract months are available |

### 3.2 Two gateways in one transaction — `RB-PAY-01`

| | |
|---|---|
| **Contract basis** | page 5 / پیوست ۱ بند ۵ and page 11 / پیوست ۱۳ بند ۷: «امکان استفاده همزمان از دو درگاه در یک تراکنش با انتخاب کاربر» |
| **What is deferred** | Splitting a single payment across two gateways in one checkout |
| **What ships in v1** | All 12 drivers behind one `GatewayDriver` contract, admin activation, one active gateway per payment, full transaction log, reconciliation report |
| **Conflict to report** | [`../canonical-context.md`](../canonical-context.md) §5 states "one active gateway per payment in v1; split payment deferred (`RB-PAY-01`)" while the contract mandates two-gateway transactions. This is a **documented conflict, not an oversight** — see `AMB-PAY-001` |
| **Risk** | This is one numbered clause inside پیوست ۱. If the whole appendix were judged unexecuted, the penalty would be 4,500,000 Toman (5 %), capped overall at 9,000,000. The mitigation is a written addendum (ماده ۱۵) or a written waiver, not silence |
| **Reversal cost** | Medium: splitting requires idempotent partial-payment state, two settlement legs and reconciliation rules. The ledger model (append-only, one `reference` per command) already accommodates it |

### 3.3 PWA — later phase, in scope

| | |
|---|---|
| **Contract basis** | page 5 / پیوست ۱ بند ۴; page 8 / پیوست ۵ بند ۲۷; page 9 / پیوست ۸ بند ۳ |
| **Status** | In scope, **phase 2 of the web product**, not part of the first vertical slice |
| **What must be true before it ships** | stable URLs and layouts, icon/splash assets, offline strategy that never shows stale prices or wallet balances, push subscription storage, HTTPS-only cookie policy |
| **Constraint** | A PWA is never described as a native app in documentation, UI or client communication (canonical §0 non-negotiable 3) |

### 3.4 Other deferrals that follow from the baselines

| Item | Contract basis | Note |
|---|---|---|
| 15 % net-profit share accounting (ماده ۱۶) | page 4 / ماده ۱۶ | Owner-level accounting obligation; the platform must expose revenue and cost reports that make it computable, but the settlement itself is not a v1 feature — `AMB-FIN-002` |
| Redis-backed queues/cache | none (technical) | `database` driver in v1 (canonical §2, ADR-0006). Not a contract item; reversible by config |
| Multi-language UI | none | Persian only in v1 — `AMB-CTR-002` |
| Native mobile push via FCM/APNs | page 9 / پیوست ۸ بند ۳ | Web push in v1; FCM/APNs arrival is part of `RB-MOB-01` |

## 4. Out of scope — structurally not this contract

### 4.1 Reservation / appointment module (نوبتدهی / رزرواسیون)

> «توجه: ماژول نوبتدهی/رزرواسیون موضوع این قرارداد نیست و مطابق قرارداد شماره ۲ (مبلغ ۳۵٬۰۰۰٬۰۰۰ تومان، مدت ۱ ماه) بهصورت مستقل طراحی و پیادهسازی میشود. اتصال فنی این دو سامانه (ورود مشترک) در صورت درخواست کارفرما با الحاقیه انجام میشود.» — page 9 / پیوست ۵

**Decision: excluded, permanently, from this repository's scope.** Not deferred-with-an-ID inside our backlog — it belongs to a different contract (35,000,000 Toman, 1 month) with a different delivery. Our obligations touch it in exactly two ways:

1. **Do not build calendar/slot/booking primitives.** No appointment tables, no availability engine, no practitioner schedules. If a screen hints at "book a time", it belongs to the other system.
2. **Keep the door open cheaply.** If shared login is later requested by addendum, it is an SSO handshake: our identity module must be able to issue/consume a token or trust an external session, and user identifiers must be stable public IDs, not internal auto-increments leaked into URLs.

Any request to "just add a booking screen" is a scope change under ماده ۱۵ and requires an addendum with cost and delivery time — say so, do not implement it.

### 4.2 Things the contract asks for that this mission will not fake

| Temptation | Why not | Correct move |
|---|---|---|
| A store-release build "so it looks like an app" | `RB-MOB-01`; store accounts and approvals are the client's cost anyway (page 10 / پیوست ۸ بند ۷) | Ship PWA; document the deferral |
| Real gateway credentials in the repo | Security baseline; client owns gateway contracts (page 2 / ماده ۲) | Sandbox drivers + admin-entered credentials, never committed |
| Real product/customer data for "realistic" demos | PII rules (`AMB-CTR-003`); user data belongs to the client (page 3 / ماده ۷) | Factories and seeders with synthetic Persian data |
| A "12 gateways" claim backed by 12 stub classes | The contract says *support*, not *scaffold* (`AMB-PAY-002`) | One fully implemented driver + 11 registered-but-inactive drivers, and say exactly that |
| Bank file formats invented per bank | No bank is named (page 11 / پیوست ۱۳ بند ۳); `AMB-FIN-004` | Excel export with a configurable column map; a bank-specific file is an addendum |
| A profit-share calculator in the admin panel | ماده ۱۶ is an accounting obligation, not a product feature (`AMB-FIN-002`) | Revenue/cost reporting that a human can use to settle |

## 5. Not ours to build (client / third-party obligations)

Explicitly the client's, per the contract — worth stating because they appear as blockers in delivery:

| Item | Basis |
|---|---|
| Hosting, domain, SSL certificate, payment gateway contracts and merchant credentials | page 2 / ماده ۲; page 3 / ماده ۵ بند ۳ |
| Developer accounts for Play Store / App Store and release approval | page 10 / پیوست ۸ بند ۷ |
| Logo, images, texts, product/service lists, sales policies, sample data for import | page 3 / ماده ۵ بند ۱; page 10 / پیوست ۹ بند ۴ |
| Stage feedback within 3 working days (silence for 7 = approval) | page 3 / ماده ۵ بند ۴ |
| Written satisfaction letter that gates final delivery and the final payment | page 10 / پیوست ۱۰ بند ۴; page 2 / ماده ۲ |
| SMS/WhatsApp sender lines and message tariffs (provider choice is ours, the account is theirs) | page 9 / پیوست ۷ بند ۳; page 10 / پیوست ۱۱ بند ۷ — see `AMB-NTF-001` |
| Real bank relationships for group payment files | page 11 / پیوست ۱۳ بند ۳ |

## 6. Scope-change protocol

The contract already defines the only legal path for adding anything:

1. New capability → ماده ۱۵ addendum table row with description, cost (Toman) and delivery time, signed by both parties (page 4 / ماده ۱۵).
2. ماده ۸ repeats the rule: oral agreements have no legal effect; changes exist only as signed annexes (page 3 / ماده ۸).
3. Without that row, «اجرای هر ردیف بدون ثبت این توافق، الزامآور نیست و حقوق ماده ۴ پیرامون آن منتفی خواهد بود» (page 4 / ماده ۱۵) — i.e. unagreed work earns no rights and no payment.

Engineering rule that follows: a feature request that is not in the appendices becomes an **ambiguity/roadmap entry** first, a **code change** only after an addendum exists. The register entry is how we prove we did not silently absorb unpaid scope.

## 7. Boundary conflicts on record

| # | Conflict | Where | Resolution |
|---|---|---|---|
| 1 | Contract requires 12 gateways + two in one transaction; baseline ships one active gateway | page 5 / پیوست ۱ بند ۵ vs canonical §5 | `AMB-PAY-001`; `RB-PAY-01`; addendum or written waiver |
| 2 | Contract requires APK + iOS + store prep; mission is web-only | page 9–10 / پیوست ۸ vs canonical §0.2 | `RB-MOB-01`; PWA in scope; deferral documented |
| 3 | Contract's subject is one 90-day delivery of everything; mission is phased | page 1–2 vs canonical §0 | `AMB-CTR-004`; phases in [`../delivery/phases-and-milestones.md`](../delivery/phases-and-milestones.md) |
| 4 | Article 1 forbids omitting *any* appendix clause; the plan defers several | page 1 / ماده ۱ | Deferral ≠ omission only if written; tracked as contractual risk, not as done |
| 5 | Every appendix says «اپلیکیشن» while the deliverable is a website | page 5–11 | Read as "the product, multi-client"; web first, native later (`AMB-CTR-001`) |
