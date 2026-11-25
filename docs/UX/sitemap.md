# Sitemap — Dekolife Web Platform

**Purpose:** define every navigable route of the web product, who may reach it, and which
requirement it serves, so route files, policies and the UI backlog are derived from one tree.
**Audience:** backend and frontend implementers, QA (route coverage), the Orchestrator.
**Status:** baseline, derived from the signed contract and its appendices.

Source: contract pages 1–11 (`input/contract/1-قرارداد-دکولایف-۹۰م-ویرایش.pdf`), appendix 2
(pages & UX) clauses 1–12, appendix 3 (user levels) clauses 3-1…3-5, appendix 5 clauses 1–44,
appendix 6 clauses 1–12, appendix 7 clauses 1–10, appendix 11 clauses 1–20, appendix 12
clauses 1–16, appendix 13 clauses 1–7, appendix 14 clauses 1–2. Module names, role names and
REQ format follow `docs/canonical-context.md` §3–§4.

---

## 1. How to read this document

| Column / marker | Meaning |
|---|---|
| Path | The URL path as routed by Laravel. Route segments are Latin; the **slug** of a product, category, store, article or static page is stored per record and may be Persian. |
| Role | Minimum role required to render the screen. `Public` = no session. Combination roles are written `store+supplier`. |
| REQ | Requirement IDs the screen exists to satisfy (`REQ-<AREA>-<NNN>`, canonical-context §4). A screen may carry several. |
| Priority | `P0` = contract-mandatory for first acceptance; `P1` = contract-mandatory, may ship in the second release train of the same contract; `P2` = contract-mentioned refinement or admin-configurable extra. Nothing here is out of contract scope. |

**Panel prefixes (fixed):**

| Panel | Prefix | Guard | Notes |
|---|---|---|---|
| Public storefront | `/` | none | RTL, server-rendered, indexable |
| Authentication | `/auth/*` | none / `web` | shared by every role |
| Customer (consumer) panel | `/account/*` | `web` + `role:consumer` | appendix 2 clause 7, appendix 3 §3-3 |
| Store panel (همکار) | `/store/*` | `web` + `role:store` | appendix 3 §3-1 |
| Supplier panel (تأمین‌کننده) | `/supplier/*` | `web` + `role:supplier` | appendix 11 |
| Marketer panel (بازاریاب) | `/marketer/*` | `web` + `role:marketer` | appendix 6 |
| Admin panel | `/admin/*` | `admin` guard + 2FA | appendix 1 clause 7, appendix 7 |

`ADMIN_HOST` may bind the admin panel to `admin.dekolife.ir`; the `/admin` prefix stays valid in
every environment so acceptance tests never depend on DNS. The two guards use different session
cookies and different login screens; an admin session never authorises a storefront route
(REQ-IAM-004, REQ-ADM-010).

A user holding a secondary role (canonical-context §1, appendix 7 clause 6) sees **one** extra
panel entry per enabled role. Panel switching is a link, never an in-place role assumption:
`/account` and `/marketer` both exist for a marketer+consumer user (REQ-USR-008, REQ-IAM-005).

**Priority of the whole tree:** every P0 node is reachable from the public header, a panel
sidebar, or a documented deep link in `docs/UX/navigation-and-layout.md` §4. No orphan routes.

**Requirement IDs.** `REQ-<AREA>-<NNN>` per canonical-context §4, using the module area codes of
canonical-context §3 (`IAM`, `USR`, `MER`, `CAT`, `PRI`, `STK`, `CRT`, `ORD`, `INV`, `PAY`, `FIN`,
`MKT`, `CMP`, `LOY`, `PRM`, `SHP`, `RTN`, `TND`, `CLS`, `PTG`, `SMP`, `MSG`, `NTF`, `CMS`, `RVW`,
`GEO`, `ADM`, `RPT`, `CTR`). Each section header states the appendix and clause a group of rows
comes from, so the appendix pointer is the join key if the functional requirement register
assigns a different number to the same clause.

---

## 2. Public storefront (anonymous + any signed-in role)

- `/` — Public — REQ-CAT-007, REQ-CAT-008, REQ-PRM-002, REQ-CMS-005, REQ-NTF-005 — **P0**
  - Home hero slider — Public — REQ-CAT-007, REQ-PRM-002 — **P0**
  - Category rail (top-level) — Public — REQ-CAT-001, REQ-CAT-007 — **P0**
  - Special offers strip (پیشنهاد ویژه) — Public — REQ-PRM-002, REQ-LOY-016 — **P0**
  - Bestsellers rail (پرفروش‌ترین‌ها) — Public — REQ-CAT-007, REQ-RPT-001 — **P0**
  - Newest products rail (جدیدترین محصولات) — Public — REQ-CAT-007 — **P0**
  - Supplier festival banner slot — Public — REQ-MER-008, REQ-PRM-002 — **P1**
  - Blog teaser + site notification bar — Public — REQ-CMS-001, REQ-NTF-005 — **P1**
- `/search` — Public — REQ-CAT-006, REQ-CAT-003 — **P0**
  - Query results with facets (same facet rail as category page) — Public — REQ-CAT-003 — **P0**
  - Empty-query / zero-result state — Public — REQ-CAT-006 — **P0**
- `/c/{category}` — Public — REQ-CAT-003, REQ-CAT-001 — **P0**
  - `/c/{category}/{subcategory}` — Public — REQ-CAT-001, REQ-CAT-003 — **P0**
  - Nested sub-category landing — Public — REQ-CAT-001, REQ-CAT-003 — **P0**
  - Dynamic filter panel: price, brand, availability, rating, discounted — Public — REQ-CAT-003 — **P0**
  - Sort control (newest, cheapest, most expensive, best rated, best selling, discount) — Public — REQ-CAT-003 — **P0**
- `/brand/{brand}` — Public — REQ-CAT-001, REQ-CAT-003 — **P1**
- `/categories` — Public — REQ-CAT-001, REQ-CAT-007 — **P1** (mobile category index; no desktop route use)
- `/offers` — Public — REQ-PRM-002, REQ-LOY-016 — **P0**
- `/festival/{festival}` — Public — REQ-PRM-002, REQ-MER-008 — **P1**
- `/p/{product}` — Public — REQ-CAT-004, REQ-RVW-003, REQ-PRI-001 — **P0**
  - Gallery + video — Public — REQ-CAT-004 — **P0**
  - Price block by viewer role (consumer / colleague / volume tiers) — Public — REQ-PRI-001, REQ-PRI-003, REQ-CAT-011 — **P0**
  - Specs table, short vs full description — Public — REQ-CAT-002, REQ-CAT-004 — **P0**
  - Reviews + buyer rating (verified purchase only) — Public — REQ-RVW-001, REQ-RVW-003 — **P0**
  - Related products, add-to-compare, add-to-favorites — Public — REQ-CAT-004, REQ-USR-002 — **P0**
  - Supplier violation notice block (۲ ماه گذشته) — Public — REQ-MER-011 — **P1**
  - Customer work photos (نمونه کار مشتریان) — Public — REQ-RVW-001, REQ-CMS-002 — **P2**
- `/compare` — Public — REQ-CAT-005 — **P0**
  - Compare tray (max 5, same parameter rows) — Public — REQ-CAT-005 — **P0**
- `/cart` — Public (session cart) — REQ-CRT-001, REQ-CRT-002, REQ-CRT-003 — **P0**
  - Coupon field — Public — REQ-CRT-002, REQ-PRM-001 — **P0**
  - Shipping estimator (city, method) — Public — REQ-CRT-003, REQ-SHP-001 — **P0**
- `/checkout` — Customer — REQ-CRT-004, REQ-CRT-005, REQ-CRT-006, REQ-CRT-008 — **P0**
  - Address picker / new address — Customer — REQ-USR-001 — **P0**
  - Carrier and shipping method, free-shipping zone check — Customer — REQ-SHP-001, REQ-SHP-002 — **P0**
  - Deposit (بیعانه) split — Customer — REQ-CRT-006 — **P1**
  - Pre-invoice review (پیش‌فاکتور) — Customer — REQ-CRT-008 — **P0**
- `/checkout/pay/{payment}` — Customer — REQ-PAY-001, REQ-PAY-002 — **P0**
  - Gateway chooser (12 gateways, two-at-once split where enabled) — Customer — REQ-PAY-002 — **P1**
- `/checkout/result/{payment}` — Customer — REQ-PAY-001, REQ-PAY-003 — **P0**
- `/checkout/failure` — Customer — REQ-PAY-003 — **P0** (hard failure, no gateway reference)
- `/track` — Public — REQ-ORD-001, REQ-CMS-002 — **P0**
  - Tracking-code result (order status timeline) — Public — REQ-ORD-001, REQ-ORD-003 — **P0**
- `/stores` — Public — REQ-MER-003, REQ-MER-004 — **P1**
  - `/stores/{store}` — Public — REQ-MER-003, REQ-USR-003 — **P1**
- `/suppliers` — Public — REQ-MER-007, REQ-MER-003 — **P1**
  - `/suppliers/{supplier}` — Public — REQ-MER-003, REQ-MER-007, REQ-PTG-001 — **P1**
- `/classifieds` (دیوار) — Public — REQ-CLS-001 — **P1**
  - `/classifieds/{ad}` — Public — REQ-CLS-001 — **P1**
  - `/classifieds/new` — Customer — REQ-CLS-001, REQ-ADM-012 — **P1**
  - `/classifieds/mine` — Customer — REQ-CLS-001 — **P2**
- `/patogh` (پتوق) — Public — REQ-PTG-001 — **P1**
  - `/patogh/{provider}` — Public — REQ-PTG-001, REQ-USR-003 — **P1**
  - `/patogh/request/{provider}` — Customer — REQ-PTG-001, REQ-MSG-004 — **P1**
  - `/patogh/skills` — Public — REQ-PTG-002 — **P1**
- `/tenders` — Public — REQ-TND-001 — **P1**
  - `/tenders/{tender}` — Public — REQ-TND-001, REQ-TND-003 — **P1**
  - `/tenders/new` — Customer / Store — REQ-TND-001 — **P1**
- `/samples/request` — Public — REQ-SMP-001 — **P1**
- `/blog` — Public — REQ-CMS-001 — **P0**
  - `/blog/category/{category}` — Public — REQ-CMS-001 — **P0**
  - `/blog/{article}` — Public — REQ-CMS-001, REQ-CAT-009 — **P0**
- `/pages/{page}` — Public — REQ-CMS-002 — **P0** (about, rules, FAQ, delivery, returns policy are pages)
- `/faq` — Public — REQ-CMS-003 — **P0**
- `/rules` — Public — REQ-CMS-003 — **P0**
- `/about` — Public — REQ-CMS-004 — **P0**
- `/contact` — Public — REQ-CMS-004, REQ-MSG-004 — **P0**
- `/r/{referral_code}` — Public — REQ-MKT-001, REQ-MKT-002 — **P0** (marketer QR / link landing, sets attribution cookie)
- `/invite/{invite_code}` — Public — REQ-LOY-011, REQ-MKT-001 — **P1** (colleague-to-colleague invite, awards points to inviter)
- `/sitemap.xml`, `/robots.txt` — Public (system) — REQ-CAT-009 — **P0**
- `/manifest.webmanifest`, `/offline` — Public (PWA shell) — REQ-CTR-003 — **P1**

---

## 3. Authentication

- `/auth/login` — Public — REQ-IAM-001, REQ-IAM-007 — **P0**
- `/auth/register` — Public — REQ-IAM-001, REQ-USR-001 — **P0**
- `/auth/verify` — Public (pending session) — REQ-IAM-001 — **P0** (SMS OTP, 6 digits, resend cooldown)
- `/auth/password/forgot` — Public — REQ-IAM-002 — **P0**
- `/auth/password/reset` — Public (signed token) — REQ-IAM-002 — **P0**
- `/auth/password/change` — Any signed-in role — REQ-IAM-003, REQ-IAM-006 — **P0** (forced on first login for marketer-registered stores)
- `/auth/two-factor` — Admin only — REQ-IAM-004, REQ-ADM-010 — **P0**
- `/auth/two-factor/recovery` — Admin only — REQ-IAM-004 — **P1**
- `/auth/logout` — Any signed-in role — REQ-IAM-001 — **P0**
- `/auth/sessions` — Any signed-in role — REQ-IAM-008 — **P2** (active devices, revoke)
- `/auth/locked` — Public — REQ-IAM-007 — **P0** (brute-force lockout notice, captcha retry)

---

## 4. Customer panel — `/account`

- `/account` — Customer — REQ-USR-001, REQ-LOY-001 — **P0** (dashboard: open orders, wallet balance, points, stars)
- `/account/orders` — Customer — REQ-USR-001, REQ-ORD-001 — **P0**
  - `/account/orders/{order}` — Customer — REQ-ORD-001, REQ-ORD-003, REQ-ORD-008 — **P0**
  - `/account/orders/{order}/invoice` — Customer — REQ-INV-001, REQ-INV-005 — **P0**
  - `/account/orders/{order}/return` — Customer — REQ-RTN-001, REQ-ORD-010 — **P1**
  - `/account/orders/{order}/complaint` — Customer — REQ-ORD-007, REQ-MSG-004 — **P1**
  - `/account/orders/{order}/review` — Customer — REQ-RVW-002 — **P1**
- `/account/addresses` — Customer — REQ-USR-001 — **P0**
- `/account/favorites` — Customer — REQ-USR-002 — **P0** (products, stores, suppliers tabs)
- `/account/wallet` — Customer — REQ-FIN-001, REQ-PAY-005 — **P0**
  - `/account/wallet/transactions` — Customer — REQ-FIN-003 — **P0**
  - `/account/wallet/top-up` — Customer — REQ-PAY-001 — **P1**
- `/account/points` — Customer — REQ-LOY-001, REQ-LOY-008 — **P0**
  - `/account/points/history` — Customer — REQ-LOY-004, REQ-LOY-005 — **P0**
  - `/account/points/rewards` — Customer — REQ-LOY-006, REQ-LOY-007, REQ-LOY-018 — **P1**
  - `/account/stars` — Customer — REQ-LOY-008 — **P1**
- `/account/reviews` — Customer — REQ-RVW-001, REQ-RVW-002 — **P1**
- `/account/returns` — Customer — REQ-RTN-001, REQ-RTN-002 — **P1**
- `/account/messages` — Customer — REQ-MSG-001, REQ-MSG-003 — **P0**
  - `/account/messages/{thread}` — Customer — REQ-MSG-003 — **P0**
- `/account/tickets` — Customer — REQ-MSG-004 — **P1**
  - `/account/tickets/new` — Customer — REQ-MSG-004 — **P1**
- `/account/notifications` — Customer — REQ-NTF-004 — **P1**
- `/account/profile` — Customer — REQ-IAM-003, REQ-USR-003, REQ-USR-007 — **P0**
- `/account/security` — Customer — REQ-IAM-003, REQ-IAM-008 — **P0**
- `/account/activity` — Customer — REQ-USR-004 — **P2**
- `/account/tenders` — Customer — REQ-TND-001 — **P2**
- `/account/samples` — Customer — REQ-SMP-001 — **P2**
- `/account/classifieds` — Customer — REQ-CLS-001 — **P2**
- `/account/reports` — Customer — REQ-USR-005 — **P2** (my submitted reports + status)

---

## 5. Store panel — `/store` (همکار / goods provider)

- `/store` — Store — REQ-MER-003, REQ-RPT-004 — **P0** (dashboard: today's orders, pending invoices, settlement balance, points)
- `/store/products` — Store — REQ-CAT-002 — **P0**
  - `/store/products/new` — Store — REQ-CAT-002, REQ-PRI-001 — **P0**
  - `/store/products/{product}/edit` — Store — REQ-CAT-002 — **P0**
  - `/store/products/{product}/media` — Store — REQ-CAT-004, REQ-CAT-010 — **P1**
  - `/store/products/import` — Store — REQ-CAT-002, REQ-ADM-004 — **P2**
- `/store/prices` — Store — REQ-PRI-005, REQ-PRI-001 — **P0** (bulk price update, tier preview)
- `/store/inventory` — Store — REQ-STK-001, REQ-STK-003 — **P0**
- `/store/orders` — Store — REQ-ORD-002, REQ-ORD-001 — **P0**
  - `/store/orders/{subOrder}` — Store — REQ-ORD-002, REQ-ORD-008 — **P0**
  - `/store/orders/{subOrder}/invoice` — Store — REQ-INV-001, REQ-INV-006 — **P0**
  - `/store/orders/{subOrder}/ship` — Store — REQ-ORD-009, REQ-SHP-001 — **P0**
- `/store/invoices` — Store — REQ-INV-004, REQ-INV-001 — **P0**
  - `/store/invoices/{invoice}` — Store — REQ-INV-001, REQ-INV-005 — **P0**
- `/store/settlements` — Store — REQ-FIN-002, REQ-FIN-003 — **P0**
  - `/store/settlements/request` — Store — REQ-FIN-002 — **P0**
  - `/store/settlements/statements` — Store — REQ-FIN-008 — **P1**
- `/store/wallet` — Store — REQ-FIN-001, REQ-FIN-003 — **P1**
- `/store/tenders` — Store — REQ-TND-001, REQ-TND-003 — **P1**
  - `/store/tenders/{tender}/bid` — Store — REQ-TND-003 — **P1**
- `/store/auctions` — Store — REQ-TND-002 — **P2**
- `/store/samples` — Store — REQ-SMP-001 — **P1** (incoming sample requests)
- `/store/classifieds` — Store — REQ-CLS-001 — **P2**
- `/store/patogh` — Store — REQ-PTG-001 — **P2**
- `/store/reports/sales` — Store — REQ-RPT-001 — **P1**
- `/store/reports/points` — Store — REQ-LOY-001, REQ-LOY-008 — **P1**
- `/store/messages` — Store — REQ-MSG-001, REQ-MSG-003 — **P0**
- `/store/tickets` — Store — REQ-MSG-004 — **P1**
- `/store/reviews` — Store — REQ-RVW-001, REQ-RVW-002 — **P1**
- `/store/profile` — Store — REQ-MER-003, REQ-MER-005 — **P0** (edit → admin approval after 5-minute window)
- `/store/settings/notifications` — Store — REQ-NTF-003 — **P2**

---

## 6. Supplier panel — `/supplier` (تأمین‌کننده / تولیدکننده)

- `/supplier` — Supplier — REQ-MER-008, REQ-RPT-004 — **P0**
- `/supplier/products` — Supplier — REQ-CAT-002 — **P0**
  - `/supplier/products/new` — Supplier — REQ-CAT-002, REQ-PRI-001 — **P0**
  - `/supplier/products/{product}/edit` — Supplier — REQ-CAT-002 — **P0**
  - `/supplier/products/{product}/media` — Supplier — REQ-CAT-010 — **P1**
- `/supplier/prices` — Supplier — REQ-PRI-005, REQ-PRI-006 — **P0**
- `/supplier/inventory` — Supplier — REQ-STK-001 — **P0**
- `/supplier/orders` — Supplier — REQ-ORD-002 — **P0**
  - `/supplier/orders/{subOrder}` — Supplier — REQ-ORD-002, REQ-ORD-008 — **P0**
  - `/supplier/orders/{subOrder}/reject` — Supplier — REQ-ORD-004, REQ-PAY-006 — **P0**
  - `/supplier/orders/{subOrder}/incomplete` — Supplier — REQ-ORD-005 — **P1**
  - `/supplier/orders/{subOrder}/ship` — Supplier — REQ-ORD-009, REQ-CMP-002 — **P0**
  - `/supplier/orders/{subOrder}/cancel` — Supplier — REQ-ORD-004 — **P1**
- `/supplier/invoices` — Supplier — REQ-INV-001, REQ-INV-004 — **P0**
  - `/supplier/invoices/{invoice}/supplementary` — Supplier — REQ-INV-002 — **P1**
- `/supplier/settlements` — Supplier — REQ-FIN-002, REQ-FIN-005 — **P0**
  - `/supplier/settlements/statements` — Supplier — REQ-FIN-008, REQ-FIN-007 — **P0**
- `/supplier/compensations` — Supplier — REQ-CMP-001, REQ-CMP-002 — **P1** (delay penalties deducted)
- `/supplier/agencies` — Supplier — REQ-MER-007 — **P1** (نمایندگی‌های کشوری + map)
- `/supplier/festivals` — Supplier — REQ-MER-008, REQ-PRM-002 — **P1**
- `/supplier/banners` — Supplier — REQ-MER-009, REQ-ADM-012 — **P1**
  - `/supplier/banners/new` — Supplier — REQ-MER-009 — **P1**
- `/supplier/slideshows` — Supplier — REQ-PRM-003 — **P2**
- `/supplier/notifications` — Supplier — REQ-NTF-002, REQ-NTF-001 — **P1**
  - `/supplier/notifications/new` — Supplier — REQ-NTF-002, REQ-PAY-001 — **P1**
- `/supplier/sms` — Supplier — REQ-NTF-003 — **P1**
- `/supplier/samples` — Supplier — REQ-SMP-001 — **P1**
- `/supplier/tenders` — Supplier — REQ-TND-001, REQ-TND-003 — **P1**
- `/supplier/auctions` — Supplier — REQ-TND-002 — **P1**
- `/supplier/reports/sales` — Supplier — REQ-RPT-001, REQ-RPT-002 — **P0**
- `/supplier/reports/points` — Supplier — REQ-LOY-019 — **P2**
- `/supplier/messages` — Supplier — REQ-MSG-001, REQ-MSG-003 — **P0**
- `/supplier/tickets` — Supplier — REQ-MSG-004 — **P1**
- `/supplier/reviews` — Supplier — REQ-RVW-002 — **P1**
- `/supplier/profile` — Supplier — REQ-MER-003, REQ-MER-007 — **P0**
- `/supplier/page` — Supplier — REQ-MER-003, REQ-CMS-002 — **P1** (public supplier page content: video, facilities, history, map)

---

## 7. Marketer panel — `/marketer` (بازاریاب)

- `/marketer` — Marketer — REQ-MKT-004, REQ-GEO-002 — **P0** (dashboard: network size, visits today, commissions pending)
- `/marketer/network` — Marketer — REQ-MKT-003, REQ-GEO-003 — **P0**
  - `/marketer/network/{store}` — Marketer — REQ-MKT-003, REQ-MER-005 — **P0**
  - `/marketer/network/{store}/edit` — Marketer — REQ-MER-005 — **P0** (5-minute window, then approval request)
- `/marketer/register-store` — Marketer — REQ-MER-001, REQ-MER-002 — **P0**
  - Duplicate check by national ID / postal code / mobile — Marketer — REQ-MER-002 — **P0**
  - Gallery + card scan upload, map location — Marketer — REQ-MER-003, REQ-GEO-005 — **P0**
- `/marketer/customers` — Marketer — REQ-MER-001, REQ-GEO-003 — **P1**
  - `/marketer/customers/new` — Marketer — REQ-ORD-011 — **P1**
  - `/marketer/customers/{customer}` — Marketer — REQ-GEO-003, REQ-GEO-004 — **P1**
- `/marketer/visits` — Marketer — REQ-GEO-001, REQ-GEO-003 — **P0**
  - `/marketer/visits/new` — Marketer — REQ-GEO-001, REQ-GEO-004 — **P0**
  - `/marketer/visits/{visit}` — Marketer — REQ-GEO-001, REQ-GEO-003 — **P1**
- `/marketer/orders` — Marketer — REQ-ORD-011 — **P1** (customer/project order forms submitted for the network)
- `/marketer/invoices` — Marketer — REQ-INV-004, REQ-MKT-003 — **P0** (invoices of colleagues the marketer visited — read-only)
- `/marketer/commissions` — Marketer — REQ-MKT-004, REQ-MKT-006 — **P0**
- `/marketer/settlements` — Marketer — REQ-FIN-002, REQ-CMP-003 — **P0**
  - `/marketer/settlements/request` — Marketer — REQ-FIN-002 — **P0**
- `/marketer/qr` — Marketer — REQ-MKT-001 — **P0** (download PNG/SVG, copy link, business-card print sheet)
- `/marketer/performance` — Marketer — REQ-GEO-002, REQ-RPT-004 — **P1**
- `/marketer/map` — Marketer — REQ-GEO-002 — **P1** (own stores on map, offline/pending sync)
- `/marketer/reports` — Marketer — REQ-USR-005, REQ-MER-011 — **P1** (complaint/violation submission)
- `/marketer/tickets` — Marketer — REQ-MSG-004, REQ-MSG-001 — **P0**
- `/marketer/messages` — Marketer — REQ-MSG-002, REQ-MSG-003 — **P0**
- `/marketer/profile` — Marketer — REQ-IAM-003, REQ-MKT-001 — **P0**
- `/marketer/plans` — Marketer — REQ-MKT-004 — **P1** (own plan A/B/C/D conditions and payout mix)

---

## 8. Admin panel — `/admin`

### 8.1 Dashboard & operations

- `/admin` — Admin — REQ-RPT-004, REQ-ADM-003 — **P0**
- `/admin/approvals` — Admin — REQ-ADM-012 — **P0** (unified queue: stores, profile edits, banners, samples, tenders, ads, reviews)
- `/admin/audit-log` — Admin — REQ-ADM-009, REQ-IAM-008 — **P0**
- `/admin/reports` — Admin — REQ-RPT-001, REQ-ADM-008 — **P0**
  - `/admin/reports/sales` — Admin — REQ-RPT-001 — **P0**
  - `/admin/reports/settlements` — Admin — REQ-ADM-008, REQ-FIN-008 — **P0**
  - `/admin/reports/marketers` — Admin — REQ-GEO-002, REQ-MKT-004 — **P1**
  - `/admin/reports/stores` — Admin — REQ-GEO-003, REQ-MER-004 — **P1**
  - `/admin/reports/points` — Admin — REQ-LOY-001, REQ-LOY-013 — **P1**
  - `/admin/reports/payments` — Admin — REQ-PAY-004, REQ-FIN-008 — **P0**
  - Export (Excel/CSV) — Admin — REQ-RPT-003 — **P0**

### 8.2 Identity & members

- `/admin/users` — Admin — REQ-ADM-001 — **P0**
  - `/admin/users/{user}` — Admin — REQ-ADM-001, REQ-USR-003 — **P0**
  - `/admin/users/{user}/roles` — Admin — REQ-IAM-005, REQ-ADM-006 — **P0**
  - `/admin/users/{user}/wallet` — Admin — REQ-FIN-005, REQ-FIN-003 — **P0**
  - `/admin/users/{user}/points` — Admin — REQ-LOY-005 — **P1**
  - `/admin/users/{user}/documents` — Admin — REQ-MER-006, REQ-IAM-008 — **P1** (private disk, signed URLs)
  - `/admin/users/{user}/activity` — Admin — REQ-USR-004, REQ-ADM-009 — **P1**
- `/admin/users/import` — Admin — REQ-CTR-007 — **P2**
- `/admin/roles` — Admin — REQ-IAM-005, REQ-ADM-011 — **P0** (role catalogue, secondary-role policy)
- `/admin/roles/{role}/permissions` — Admin — REQ-ADM-011 — **P0**
- `/admin/admins` — Admin — REQ-ADM-010, REQ-IAM-004 — **P1** (admin accounts, 2FA enforcement state)
- `/admin/merchants/pending` — Admin — REQ-MER-006, REQ-ADM-012 — **P0**

### 8.3 Catalog

- `/admin/categories` — Admin — REQ-CAT-001 — **P0**
  - `/admin/categories/{category}` — Admin — REQ-CAT-001, REQ-CAT-003 — **P0** (filter set, attribute assignment)
- `/admin/brands` — Admin — REQ-CAT-001 — **P0**
- `/admin/attributes` — Admin — REQ-CAT-002, REQ-CAT-003 — **P0** (attribute groups, filterable flags, units)
- `/admin/products` — Admin — REQ-ADM-004 — **P0**
  - `/admin/products/new` — Admin — REQ-ADM-004, REQ-CAT-002 — **P0**
  - `/admin/products/{product}` — Admin — REQ-ADM-004, REQ-PRI-001 — **P0**
  - `/admin/products/{product}/prices` — Admin — REQ-PRI-001, REQ-PRI-003 — **P0**
  - `/admin/products/{product}/media` — Admin — REQ-CAT-004, REQ-CAT-010 — **P1**
  - `/admin/products/import` — Admin — REQ-CTR-007 — **P2**
- `/admin/inventory` — Admin — REQ-STK-003 — **P1**
  - `/admin/inventory/movements` — Admin — REQ-STK-003 — **P2**
- `/admin/pricing/rules` — Admin — REQ-PRI-002, REQ-LOY-014 — **P1** (volume tiers, coefficient curves)
- `/admin/pricing/consumer-markup` — Admin — REQ-PRI-003 — **P0** (percent or manual per product, e.g. +35%)

### 8.4 Sales operations

- `/admin/orders` — Admin — REQ-ORD-001, REQ-ADM-002 — **P0**
  - `/admin/orders/{order}` — Admin — REQ-ORD-001, REQ-ORD-007 — **P0**
  - `/admin/orders/{order}/sub-orders` — Admin — REQ-ORD-002 — **P1**
- `/admin/invoices` — Admin — REQ-INV-003 — **P0**
  - `/admin/invoices/new` — Admin — REQ-INV-003, REQ-ADM-002 — **P0**
  - `/admin/invoices/{invoice}` — Admin — REQ-INV-002, REQ-INV-005 — **P0**
- `/admin/payments` — Admin — REQ-PAY-003 — **P0**
  - `/admin/payments/gateways` — Admin — REQ-PAY-001, REQ-PAY-002 — **P0** (12 drivers, activation, split-payment toggle)
  - `/admin/payments/transactions` — Admin — REQ-PAY-003 — **P0**
  - `/admin/payments/reconciliation` — Admin — REQ-PAY-004 — **P0**
  - `/admin/payments/gateway-logs` — Admin — REQ-PAY-003 — **P1**
- `/admin/settlements` — Admin — REQ-FIN-002, REQ-FIN-008 — **P0**
  - `/admin/settlements/{settlement}` — Admin — REQ-FIN-003, REQ-FIN-005 — **P0**
  - `/admin/settlements/bank-export` — Admin — REQ-FIN-006, REQ-RPT-003 — **P0**
- `/admin/ledger` — Admin — REQ-FIN-003 — **P0** (append-only, balance derivation, reversal entry)
- `/admin/commissions` — Admin — REQ-MKT-004, REQ-MKT-005 — **P1** (plans, payout runs, disable commission)
- `/admin/compensations` — Admin — REQ-CMP-001, REQ-CMP-002 — **P1**
- `/admin/returns` — Admin — REQ-RTN-001, REQ-RTN-002 — **P1**

### 8.5 Marketing, loyalty, promotions

- `/admin/marketers` — Admin — REQ-MKT-003, REQ-MKT-004 — **P0**
  - `/admin/marketers/{marketer}` — Admin — REQ-MKT-003, REQ-GEO-002 — **P0**
  - `/admin/marketers/{marketer}/network` — Admin — REQ-MKT-003 — **P0**
  - `/admin/marketers/{marketer}/commission` — Admin — REQ-MKT-005, REQ-CMP-003 — **P0**
- `/admin/marketers/plans` — Admin — REQ-MKT-004 — **P1** (A/B/C/D plan matrix, fixed-visit/salary/mixed)
- `/admin/fieldops/live` — Admin — REQ-GEO-002 — **P1** (live tracking board)
- `/admin/fieldops/visits` — Admin — REQ-GEO-001, REQ-GEO-003 — **P1**
- `/admin/fieldops/inactive-stores` — Admin — REQ-MER-004, REQ-GEO-003 — **P1**
- `/admin/loyalty/rules` — Admin — REQ-LOY-001, REQ-LOY-002 — **P0** (versioned rule sets, effective_from)
- `/admin/loyalty/rewards` — Admin — REQ-LOY-006, REQ-LOY-007 — **P0**
- `/admin/loyalty/tiers` — Admin — REQ-LOY-009, REQ-LOY-010 — **P0** (4%/5% bands, fixed n-Toman band)
- `/admin/loyalty/adjustments` — Admin — REQ-LOY-005 — **P0** (manual grant/deduct with reason)
- `/admin/loyalty/replay` — Admin — REQ-LOY-001 — **P2** (recompute points for a date range, dry-run)
- `/admin/promotions/coupons` — Admin — REQ-PRM-001, REQ-CRT-002 — **P0**
- `/admin/promotions/banners` — Admin — REQ-PRM-001, REQ-MER-009 — **P0**
- `/admin/promotions/festivals` — Admin — REQ-PRM-002, REQ-MER-008 — **P1**
- `/admin/promotions/slideshows` — Admin — REQ-PRM-003 — **P2**
- `/admin/notifications` — Admin — REQ-NTF-001 — **P0**
  - `/admin/notifications/new` — Admin — REQ-NTF-001 — **P0** (text/image/link/video, audience, count, schedule)
  - `/admin/notifications/paid-orders` — Admin — REQ-NTF-002 — **P1** (review purchased notification campaigns)
  - `/admin/notifications/sms` — Admin — REQ-NTF-003 — **P1** (segments, tariff, delivery report)
  - `/admin/notifications/templates` — Admin — REQ-NTF-004 — **P1** (order-status triggers for SMS/WhatsApp/email)
- `/admin/polls` — Admin — REQ-NTF-006 — **P2**

### 8.6 Content, community, services

- `/admin/cms/pages` — Admin — REQ-CMS-002 — **P0**
- `/admin/cms/menus` — Admin — REQ-CMS-002 — **P0**
- `/admin/cms/footer` — Admin — REQ-CMS-002 — **P0**
- `/admin/cms/widgets` — Admin — REQ-CAT-008, REQ-CMS-005 — **P0** (home rails, order, visibility window)
- `/admin/cms/notification-bar` — Admin — REQ-NTF-005 — **P1**
- `/admin/cms/faq` — Admin — REQ-CMS-003 — **P1**
- `/admin/blog` — Admin — REQ-CMS-001 — **P0**
  - `/admin/blog/{article}` — Admin — REQ-CMS-001, REQ-CAT-009 — **P0**
  - `/admin/blog/categories` — Admin — REQ-CMS-001 — **P2**
- `/admin/reviews` — Admin — REQ-RVW-004 — **P1**
- `/admin/classifieds` — Admin — REQ-CLS-001, REQ-ADM-012 — **P1**
  - `/admin/classifieds/categories` — Admin — REQ-CLS-001 — **P1** (ads only after admin defines a category)
- `/admin/patogh` — Admin — REQ-PTG-001, REQ-ADM-012 — **P1**
- `/admin/tenders` — Admin — REQ-TND-001 — **P1**
  - `/admin/tenders/{tender}` — Admin — REQ-TND-001, REQ-TND-003 — **P1**
- `/admin/auctions` — Admin — REQ-TND-002 — **P2**
- `/admin/samples` — Admin — REQ-SMP-001 — **P1**
- `/admin/messages` — Admin — REQ-MSG-003 — **P1** (chat monitor, retention)
- `/admin/tickets` — Admin — REQ-MSG-004 — **P0**
  - `/admin/tickets/{ticket}` — Admin — REQ-MSG-004 — **P0**
- `/admin/reports/abuse` — Admin — REQ-USR-005, REQ-MER-011 — **P1**
- `/admin/shipping/methods` — Admin — REQ-SHP-001, REQ-SHP-003 — **P0**
- `/admin/shipping/zones` — Admin — REQ-SHP-002 — **P1** (free-shipping city / nationwide)
- `/admin/shipping/tariffs` — Admin — REQ-CRT-003, REQ-SHP-003 — **P0**

### 8.7 Platform settings

- `/admin/settings/general` — Admin — REQ-ADM-007 — **P0** (site name, contact, currency display, Jalali locale)
- `/admin/settings/security` — Admin — REQ-ADM-010, REQ-IAM-007 — **P0** (2FA policy, session lifetime, captcha)
- `/admin/settings/roles` — Admin — REQ-ADM-006 — **P0** (which secondary combos are allowed, e.g. store × marketer)
- `/admin/settings/payments` — Admin — REQ-PAY-001 — **P0** (credentials referenced by env keys only, never rendered)
- `/admin/settings/notifications` — Admin — REQ-NTF-004 — **P1**
- `/admin/settings/import` — Admin — REQ-CTR-007 — **P2** (base data: categories, brands, sample products)
- `/admin/settings/modules` — Admin — REQ-ADM-007 — **P2** (toggle classifieds/Patogh per deployment)
- `/admin/settings/print` — Admin — REQ-ADM-007, REQ-INV-003 — **P2** (invoice and statement header)
- `/admin/system/backups` — Admin — REQ-CTR-007 — **P1** (daily backup status, restore request)
- `/admin/system/health` — Admin — REQ-CTR-005 — **P2** (queue depth, failed jobs, storage)

---

## 9. System & error screens (shared shell)

- `/403` — any — REQ-CTR-006 — **P0** (unauthorized; explains which role is required, offers role switch)
- `/404` — any — REQ-CTR-006 — **P0**
- `/419` — any — REQ-CTR-006 — **P0** (session expired, re-submit guard)
- `/429` — any — REQ-IAM-007 — **P0** (rate limited, retry-after)
- `/500` — any — REQ-CTR-006 — **P0**
- `/maintenance` — any — REQ-CTR-006 — **P1**
- `/offline` — any (PWA) — REQ-CTR-003 — **P1**

---

## 10. Cross-panel route inventory (counts)

| Panel | Routes / screens | P0 | P1 | P2 |
|---|---|---|---|---|
| Public storefront | 72 | 45 | 25 | 2 |
| Authentication | 11 | 9 | 1 | 1 |
| Customer panel | 30 | 14 | 11 | 5 |
| Store panel | 31 | 16 | 10 | 5 |
| Supplier panel | 36 | 16 | 18 | 2 |
| Marketer panel | 26 | 17 | 9 | 0 |
| Admin panel | 112 | 63 | 37 | 12 |
| System/error | 7 | 5 | 2 | 0 |
| **Total** | **325** | **185** | **113** | **27** |

Counts include nested child nodes; the screen-level backlog in
`docs/UX/screen-inventory.md` merges purely decorative children (e.g. rails inside the home
page) into one screen and therefore reports a smaller, buildable number.

---

## 11. Routing conventions

1. **Resource identifiers.** Public product/category/article URLs use a slug column, never a raw
   numeric ID: `/p/parquet-oak-3layer`. Panel routes use the numeric key for precision
   (`/store/orders/1042`). A changed slug 301-redirects to the current one (REQ-CAT-009).
2. **Scope in every panel route.** Panel controllers resolve the resource through the actor's
   scope (`store_id`, `supplier_id`, `marketer_id`), never from a request parameter alone — see
   `docs/requirements/permissions-matrix.md` §5. A route that accepts a child ID must also
   assert the parent chain.
3. **Verbs.** Only `GET` renders; every mutation is `POST/PUT/PATCH/DELETE` with CSRF and a
   policy check; no destructive `GET` (REQ-CTR-006).
4. **Admin isolation.** `admin` guard, separate session cookie, mandatory 2FA enrolment before
   first render, `X-Robots-Tag: noindex` on every `/admin` response, and no admin session on the
   storefront. The storefront never links to `/admin` (REQ-IAM-004, REQ-ADM-010).
5. **Deep-link contract for QA.** Every route in §2–§9 appears in the route-coverage test
   (`tests/Feature/Routing/RouteInventoryTest.php`) and a 200/302/403 assertion with the
   expected role fixture. Missing routes fail CI.
6. **Deferred, not routed.** Native apps, APK delivery, store publishing, and the
   reservation/appointment module have no routes in this tree (`RB-MOB-01`, canonical-context
   §0 non-negotiable 4). PWA install/offline routes are present because they are web routes.

---

## 12. Requirement area coverage check

| Area | Where it lives in the tree |
|---|---|
| IAM | §3 entirely; `/admin/roles`, `/admin/settings/security` |
| USR | §4 `/account/*` |
| MER | `/marketers/network`, `/register-store`, `/supplier/agencies`, `/stores/*` |
| CAT | `/c/*`, `/p/*`, `/search`, `/admin/categories`, `/admin/attributes` |
| PRI | `/p/{product}` price block, `/store/prices`, `/admin/pricing/*` |
| STK | `/store/inventory`, `/supplier/inventory`, `/admin/inventory` |
| CRT | `/cart`, `/checkout` |
| ORD | `/account/orders`, `/store/orders`, `/supplier/orders`, `/admin/orders` |
| INV | `/account/orders/{order}/invoice`, panel invoice lists, `/admin/invoices` |
| PAY | `/checkout/pay`, `/admin/payments/*` |
| FIN | `/account/wallet`, `/store/settlements`, `/admin/ledger` |
| MKT | §7 entirely, `/admin/marketers/*` |
| CMP | `/supplier/compensations`, `/admin/compensations` |
| LOY | `/account/points`, `/admin/loyalty/*` |
| PRM | `/offers`, `/festival/*`, `/admin/promotions/*` |
| SHP | `/checkout` shipping step, `/admin/shipping/*` |
| RTN | `/account/returns`, `/admin/returns` |
| TND | `/tenders`, `/store/tenders`, `/supplier/auctions`, `/admin/tenders` |
| CLS | `/classifieds/*`, `/admin/classifieds` |
| PTG | `/patogh/*`, `/admin/patogh` |
| SMP | `/samples/request`, `/supplier/samples`, `/admin/samples` |
| MSG | `/account/messages`, `/store/messages`, `/admin/messages`, `/tickets` |
| NTF | `/admin/notifications/*`, `/supplier/notifications`, `/account/notifications` |
| CMS | `/blog/*`, `/pages/*`, `/faq`, `/rules`, `/about`, `/contact`, `/admin/cms/*` |
| RVW | `/p/{product}` reviews, `/account/reviews`, `/admin/reviews` |
| GEO | `/marketer/visits`, `/marketer/map`, `/admin/fieldops/*` |
| ADM | §8 entirely |
| RPT | panel report screens, `/admin/reports/*` |
| CTR | `/sitemap.xml`, `/auth/locked`, error shell, PWA shell |

Every area has at least one P0 screen except `TND`, `CLS`, `PTG` and `SMP`, whose contract
clauses land in the P1 band of the acceptance plan in `docs/UX/screen-inventory.md` §3 (they are
still contract-mandatory; nothing is dropped).
