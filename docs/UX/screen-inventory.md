# Screen Inventory — Dekolife Web Platform

**Purpose:** the build backlog for the UI: one row per screen, with route, actor, purpose,
components, required states, requirements and priority. A screen is "done" only when every state
listed for it exists.
**Audience:** implementers, QA (screen-by-screen acceptance), the Orchestrator for sprint slicing.
**Status:** v1 baseline; 267 screens across five role panels, the public storefront, auth and system screens.

Source: appendix 2 (pages & UX) clauses 1–12; appendix 3 §3-1…§3-5 (per-level capabilities);
appendix 5 (owner-requested features) clauses 1–44; appendix 6 (marketer panel) 1–12; appendix 7
(admin) 1–10; appendix 11 (supplier panel) 1–20; appendix 12 (consumer club) 1–16; appendix 13
(finance/settlement) 1–7; appendix 14 (field-sales management) 1–2. Routes match
`docs/UX/sitemap.md`; visual rules match `docs/UX/design-system.md`; visibility rules match
`docs/requirements/permissions-matrix.md`.

---

## 1. Conventions

**ID:** `SCR-<PANEL>-<NNN>` — `PUB` storefront, `AUT` auth, `CUS` customer, `STR` store,
`SUP` supplier, `MKT` marketer, `ADM` admin, `SYS` system. IDs are stable and never renumbered; a
dropped screen keeps its ID retired (no reuse) and the removal is recorded in §14.

**State codes** (every listed state must be implemented and testable):

| Code | State | Meaning |
|---|---|---|
| `E` | empty | no data yet — explain the value, offer the creating action |
| `F` | filtered-empty | filters exclude everything — offer «حذف فیلترها» |
| `L` | loading | skeleton matching final layout, `aria-busy` |
| `X` | error | inline retry, page stays usable |
| `U` | unauthorized | 403 shell naming the required role, no data leak |
| `S` | success | explicit confirmation (toast, banner, or result screen) |
| `D` | draft / offline | local draft or pending sync indicator |

**Priority bands** and the release they belong to (acceptance plan):

- **P0** — required for first acceptance. Core commerce path, all five panels' primary tasks,
  admin 2FA and approvals, money and points correctness.
- **P1** — required by the contract, delivered in the second release train of the same contract
  (classifieds, Patogh, tenders, auctions, supplier marketing tools, advanced reports).
- **P2** — contract-mentioned refinements, imports, analytics extras; shipped when P0/P1 are
  green. Nothing in this list is out of contract scope (canonical-context §0).

**Role column:** `Public`, `Customer`, `Store`, `Supplier`, `Marketer`, `Admin`,
`Customer+Store` (secondary role), `Store+Supplier`.

---

## 2. Public storefront — 36 core screens (45 with §9.1)

| ID | Screen | Route | Role | Purpose | Key components | States | REQ | Pri |
|---|---|---|---|---|---|---|---|---|
| SCR-PUB-001 | صفحه اصلی | `/` | Public | Land the visitor and route them into the catalog | hero carousel, category rail, offer strip, product rails, notification bar, footer | E L X | CAT-007, CAT-008, PRM-002, CMS-005, NTF-005 | P0 |
| SCR-PUB-002 | دسته‌بندی محصولات | `/c/{category}` | Public | Browse a category with dynamic filters and sorting | breadcrumbs, filter rail, sort bar, product grid, pagination | F L X S | CAT-003, CAT-001 | P0 |
| SCR-PUB-003 | زیردسته | `/c/{category}/{sub}` | Public | Narrow to a leaf category | inherited filter rail, sub-category chips, product grid | E F L X | CAT-001, CAT-003 | P0 |
| SCR-PUB-004 | نتایج جستجو | `/search?q=` | Public | Show matches for a query with the same facets | search field, scope chip, filter rail, result count, product grid | E F L X | CAT-006, CAT-003 | P0 |
| SCR-PUB-005 | جستجوی سریع (موبایل) | overlay on any page | Public | Type-ahead search on small screens | full-screen overlay, suggestion listbox, recent searches | E L X | CAT-006 | P0 |
| SCR-PUB-006 | فهرست دسته‌بندی‌ها | `/categories` | Public | Mobile category index replacing the mega menu | accordion tree, icons, count badges | L X | CAT-001, CAT-007 | P1 |
| SCR-PUB-007 | صفحه برند | `/brand/{brand}` | Public | All products of one brand | brand header, product grid, filters, SEO copy block | E F L | CAT-001, CAT-003 | P1 |
| SCR-PUB-008 | پیشنهادهای ویژه | `/offers` | Public | Aggregate active discounts with a countdown | offer hero, countdown, discounted grid, sort | E F L X | PRM-002, LOY-016 | P0 |
| SCR-PUB-009 | جشنواره | `/festival/{festival}` | Public | Seasonal campaign landing for supplier offers | campaign hero, curated rails, supplier logos | E L X | PRM-002, MER-008 | P1 |
| SCR-PUB-010 | صفحه محصول | `/p/{product}` | Public | Convert: gallery, price by role, specs, reviews | gallery, video, price block, spec table, reviews, related, compare tray, sticky buy bar | E L X S | CAT-004, PRI-001, RVW-003, USR-002 | P0 |
| SCR-PUB-011 | گالری تصاویر محصول | modal on product | Public | Inspect images at full size | lightbox, thumbnails, zoom, keyboard nav | L X | CAT-004 | P0 |
| SCR-PUB-012 | مقایسه محصولات | `/compare` | Public | Compare up to five products on identical parameters | compare table, sticky parameter column, diff highlight, remove slot | E L X | CAT-005 | P0 |
| SCR-PUB-013 | سبد خرید | `/cart` | Public | Review lines, apply coupon, estimate shipping | line table, quantity steppers, coupon input, shipping estimator, totals, deposit note | E L X S | CRT-001, CRT-002, CRT-003 | P0 |
| SCR-PUB-014 | تسویه — آدرس و ارسال | `/checkout` step 1 | Customer | Choose address, carrier and shipping method | address cards, new-address form, carrier select, free-shipping notice, stepper | E L X | CRT-004, CRT-005, SHP-001, SHP-002 | P0 |
| SCR-PUB-015 | تسویه — بازبینی پیش‌فاکتور | `/checkout` step 2 | Customer | Confirm lines, discounts and totals before payment | pre-invoice table, discount breakdown, deposit/remainder, terms checkbox | L X | CRT-008, CRT-006 | P0 |
| SCR-PUB-016 | انتخاب درگاه پرداخت | `/checkout/pay/{payment}` | Customer | Pick a gateway (or two, when split is enabled) | gateway cards, amount split, fee note, 15-min timer | L X | PAY-001, PAY-002 | P0 |
| SCR-PUB-017 | نتیجه پرداخت | `/checkout/result/{payment}` | Customer | Tell the buyer exactly what happened | status hero, gateway reference, retry, alternate gateway, order link | X S | PAY-001, PAY-003, ORD-001 | P0 |
| SCR-PUB-018 | پیگیری سفارش — فرم | `/track` | Public | Find an order without logging in | tracking-code input, phone input, captcha, help link | X | ORD-001, CTR-006 | P0 |
| SCR-PUB-019 | پیگیری سفارش — نتیجه | `/track?code=` | Public | Show status timeline and carrier info | timeline, sub-order statuses, waybill link, support CTA | E X | ORD-001, ORD-003, ORD-009 | P0 |
| SCR-PUB-020 | فهرست فروشگاه‌ها | `/stores` | Public | Discover stores and their catalogues | store cards, city filter, map toggle, search | E F L | MER-003, MER-004 | P1 |
| SCR-PUB-021 | صفحه عمومی فروشگاه | `/stores/{store}` | Public | Present a store and its products | cover, logo, level badge, contact block, product grid, map pin | E L X | MER-003, USR-003 | P1 |
| SCR-PUB-022 | فهرست تأمین‌کنندگان | `/suppliers` | Public | Browse producers by category and city | supplier cards, filters, logo wall | E F L | MER-007, MER-003 | P1 |
| SCR-PUB-023 | صفحه عمومی تأمین‌کننده | `/suppliers/{supplier}` | Public | Brand page: services, video, history, agencies | hero video, facility list, history, agency map, product rails | E L X | MER-003, MER-007, PTG-001 | P1 |
| SCR-PUB-024 | دیوار — فهرست آگهی‌ها | `/classifieds` | Public | Trade ads with search and categories | category chips, ad cards, search, city filter | E F L | CLS-001 | P1 |
| SCR-PUB-025 | دیوار — جزئیات آگهی | `/classifieds/{ad}` | Public | Show an ad and contact the poster | gallery, price, poster card, contact actions, report link | E L X | CLS-001, USR-005 | P1 |
| SCR-PUB-026 | دیوار — ثبت آگهی | `/classifieds/new` | Customer | Publish an ad in an admin-defined category | category select, title/body, image uploader, preview, submit-for-review | L X S | CLS-001, ADM-012 | P1 |
| SCR-PUB-027 | پتوق — فهرست خدمات | `/patogh` | Public | Find local installers and repair services | service category grid, provider cards, city filter | E F L | PTG-001 | P1 |
| SCR-PUB-028 | پتوق — پروفایل ارائه‌دهنده | `/patogh/{provider}` | Public | Present a provider with portfolio and pricing | portfolio gallery, skills, service area, rating, request CTA | E L X | PTG-001, USR-003 | P1 |
| SCR-PUB-029 | پتوق — درخواست خدمات | `/patogh/request/{provider}` | Customer | Request a quote with address and photos | request form, map picker, photo uploader, price expectation | L X S | PTG-001, MSG-004 | P1 |
| SCR-PUB-030 | پتوق — افراد و توانایی‌ها | `/patogh/skills` | Public | Directory of people by skill and city | skill chips, person cards, city filter | E F L | PTG-002 | P1 |
| SCR-PUB-031 | مناقصه‌ها — فهرست | `/tenders` | Public | Open tenders for large projects | tender cards, status badges, deadline countdown, filters | E F L | TND-001 | P1 |
| SCR-PUB-032 | مناقصه — جزئیات | `/tenders/{tender}` | Public | Full brief and how to participate | requirements list, attachments, timeline, participate CTA | E L X | TND-001, TND-003 | P1 |
| SCR-PUB-033 | مناقصه — ثبت درخواست | `/tenders/new` | Customer | Submit a tender to producers and suppliers | multi-step form, line items, attachments, review step | L X S | TND-001 | P1 |
| SCR-PUB-034 | درخواست سمپل و کاتالوگ | `/samples/request` | Public | Ask for a sample, work sample or catalogue | product picker, dimensions/quantity, address, note | L X S | SMP-001 | P1 |
| SCR-PUB-035 | بلاگ — فهرست | `/blog` | Public | Content hub for SEO and education | featured article, category chips, article cards, pagination | E F L | CMS-001, CAT-009 | P0 |
| SCR-PUB-036 | بلاگ — دسته و تک‌مقاله | `/blog/category/{c}`, `/blog/{article}` | Public | Read and share an article | article body, TOC, share, related, JSON-LD, author box | E L X | CMS-001, CAT-009 | P0 |

Static and utility public screens are listed in §9 (pages, FAQ, about, contact, referral landing).

---

## 3. Authentication — 10 screens

| ID | Screen | Route | Role | Purpose | Key components | States | REQ | Pri |
|---|---|---|---|---|---|---|---|---|
| SCR-AUT-001 | ورود | `/auth/login` | Public | Sign in by mobile + password or OTP | phone field, password, OTP tab, captcha after 3 failures, register link | X S | IAM-001, IAM-007 | P0 |
| SCR-AUT-002 | ثبت‌نام | `/auth/register` | Public | Create a consumer account | name, mobile, password strength, terms checkbox, level note | X S | IAM-001, USR-001 | P0 |
| SCR-AUT-003 | تأیید پیامک | `/auth/verify` | Public | Verify the mobile number with an OTP | 6-box OTP, 2-min timer, resend 60s, edit number | X S | IAM-001 | P0 |
| SCR-AUT-004 | فراموشی رمز | `/auth/password/forgot` | Public | Start a password reset by SMS | mobile field, captcha, sent confirmation | X S | IAM-002 | P0 |
| SCR-AUT-005 | تعیین رمز جدید | `/auth/password/reset` | Public | Set a new password from a signed link/code | password + confirm, strength meter, expiry notice | X S | IAM-002 | P0 |
| SCR-AUT-006 | تغییر اجباری رمز | `/auth/password/change` | Any signed-in | Force first-login password change for marketer-registered stores | current/new password, policy hints, cannot-skip notice | X | IAM-006 | P0 |
| SCR-AUT-007 | ورود ادمین — مرحله دوم | `/auth/two-factor` | Admin | Complete 2FA before any admin screen renders | 6-digit TOTP, recovery-code link, device note | X | IAM-004, ADM-010 | P0 |
| SCR-AUT-008 | کدهای بازیابی ادمین | `/auth/two-factor/recovery` | Admin | Regain access with a one-time recovery code | code list input, remaining count, regenerate flow | X S | IAM-004 | P1 |
| SCR-AUT-009 | قفل موقت ورود | `/auth/locked` | Public | Explain a brute-force lockout and the way out | countdown, captcha retry, support contact | X | IAM-007 | P0 |
| SCR-AUT-010 | دستگاه‌های فعال | `/auth/sessions` | Any signed-in | Review and revoke sessions | session rows, last-seen, revoke button, current marker | E L S | IAM-008 | P2 |

---

## 4. Customer panel — 24 core screens (30 with §9.2)

| ID | Screen | Route | Role | Purpose | Key components | States | REQ | Pri |
|---|---|---|---|---|---|---|---|---|
| SCR-CUS-001 | داشبورد مشتری | `/account` | Customer | One glance at orders, wallet, points, stars | KPI cards, active-order card, points ring, wallet balance, shortcuts | E L X | USR-001, LOY-001, FIN-001 | P0 |
| SCR-CUS-002 | سفارش‌های من | `/account/orders` | Customer | Find and filter past and open orders | status tabs, order cards, date filter, search by code | E F L | USR-001, ORD-001 | P0 |
| SCR-CUS-003 | جزئیات سفارش | `/account/orders/{order}` | Customer | Track lines, sub-orders, shipment and actions | timeline, sub-order groups, line table, attachments, action bar | L X | ORD-001, ORD-003, ORD-008 | P0 |
| SCR-CUS-004 | مشاهده فاکتور | `/account/orders/{order}/invoice` | Customer | Read and print the invoice | invoice document, points earned, print/PDF, seller block | L X | INV-001, INV-005 | P0 |
| SCR-CUS-005 | درخواست مرجوعی | `/account/orders/{order}/return` | Customer | Return eligible items inside the window | item picker with eligibility, reason select, photos, submit | X S | RTN-001, ORD-010 | P1 |
| SCR-CUS-006 | ثبت شکایت سفارش | `/account/orders/{order}/complaint` | Customer | Freeze preparation and escalate | reason, description, attachments, policy notice («تا یک هفته پس از ارسال») | X S | ORD-007, MSG-004 | P1 |
| SCR-CUS-007 | ثبت نظر و امتیاز | `/account/orders/{order}/review` | Customer | Rate product and supplier after delivery | product stars, 5–10 supplier parameters, text, photo upload | X S | RVW-001, RVW-002 | P1 |
| SCR-CUS-008 | آدرس‌های من | `/account/addresses` | Customer | Manage delivery addresses | address cards, default marker, map preview, add/edit/delete | E L X S | USR-001 | P0 |
| SCR-CUS-009 | افزودن/ویرایش آدرس | modal/page | Customer | Capture a complete, mappable address | province/city selects, postal code, map picker, receiver phone | X S | USR-001 | P0 |
| SCR-CUS-010 | علاقه‌مندی‌ها | `/account/favorites` | Customer | Saved products, stores and suppliers | tabs, product cards, store cards, remove action, move-to-cart | E F L | USR-002 | P0 |
| SCR-CUS-011 | کیف پول | `/account/wallet` | Customer | Balance and quick actions | balance card, top-up CTA, last transactions, expiring rewards | E L X | FIN-001, PAY-005 | P0 |
| SCR-CUS-012 | گردش کیف پول | `/account/wallet/transactions` | Customer | Audit every wallet movement | ledger rows, type filter, date range, running balance, export | E F L | FIN-003 | P0 |
| SCR-CUS-013 | شارژ کیف پول | `/account/wallet/top-up` | Customer | Add funds through a gateway | amount presets, custom amount, gateway select, result state | X S | PAY-001, FIN-001 | P1 |
| SCR-CUS-014 | امتیازهای من | `/account/points` | Customer | Explain the points engine and current tier | points total, tier band (۱–۱۰۰ / ۱۰۰–۲۰۰ / ۲۰۰+), next-reward progress, rules link | E L X | LOY-001, LOY-009, LOY-010 | P0 |
| SCR-CUS-015 | تاریخچه امتیاز | `/account/points/history` | Customer | Show every earn and deduction with reason | timeline rows, invoice link, deduction reasons, period filter | E F L | LOY-004, LOY-005 | P0 |
| SCR-CUS-016 | پاداش‌ها و بن‌ها | `/account/points/rewards` | Customer | Show rewards, vouchers and expiry | reward cards, expiry countdown, applied-on-invoice note, terms | E L | LOY-006, LOY-007, LOY-018 | P1 |
| SCR-CUS-017 | ستاره‌های من | `/account/stars` | Customer | Show the 1-star-per-100-points status | star grid, points-to-next-star, profile preview | E L | LOY-008 | P1 |
| SCR-CUS-018 | نظرات من | `/account/reviews` | Customer | Manage own reviews and ratings | review cards, status badges, edit/delete while pending | E L S | RVW-001, RVW-002 | P1 |
| SCR-CUS-019 | مرجوعی‌های من | `/account/returns` | Customer | Track return requests and refunds | status timeline, refund destination (wallet/sheba), amounts | E L X | RTN-001, RTN-002 | P1 |
| SCR-CUS-020 | پیام‌ها | `/account/messages` | Customer | Chat with stores, suppliers, marketers, admin | thread list, unread badges, search, new-chat picker | E L X | MSG-001, MSG-003 | P0 |
| SCR-CUS-021 | گفتگو | `/account/messages/{thread}` | Customer | Persistent conversation with attachments | chat thread, composer (text/image/file/voice), read receipts | L X | MSG-003 | P0 |
| SCR-CUS-022 | تیکت‌ها | `/account/tickets` | Customer | Formal support requests | ticket list, status filters, new-ticket CTA | E F L | MSG-004 | P1 |
| SCR-CUS-023 | اعلان‌ها | `/account/notifications` | Customer | Inbox of order, points and system notices | notification rows, mark-read, filter by type | E L | NTF-004 | P1 |
| SCR-CUS-024 | اطلاعات کاربری | `/account/profile` | Customer | Edit identity with SMS confirmation | avatar upload, name, birth date (Jalali), email, SMS confirm, public-profile preview | L X S | IAM-003, USR-003, USR-007 | P0 |

Additional customer screens: `/account/security` (SCR-CUS-025, P0), `/account/activity`
(SCR-CUS-026, P2), `/account/tenders` (SCR-CUS-027, P2), `/account/samples` (SCR-CUS-028, P2),
`/account/classifieds` (SCR-CUS-029, P2), `/account/reports` (SCR-CUS-030, P2) — tabulated in §9.2
to keep this table on the P0/P1 spine.

## 5. Store panel — 25 core screens (28 with §9.2)

| ID | Screen | Route | Role | Purpose | Key components | States | REQ | Pri |
|---|---|---|---|---|---|---|---|---|
| SCR-STR-001 | داشبورد فروشگاه | `/store` | Store | Today's work: orders to confirm, low stock, balance | KPI strip, pending-order list, stock alerts, settlement card, points card | E L X | MER-003, RPT-004, LOY-001 | P0 |
| SCR-STR-002 | محصولات فروشگاه | `/store/products` | Store | Manage own catalogue | data table (image, title, price, stock, status), filters, bulk actions, export | E F L X | CAT-002 | P0 |
| SCR-STR-003 | افزودن محصول | `/store/products/new` | Store | Create a product with attributes and tier prices | wizard: basics → attributes → media → prices → stock, draft autosave | L X S | CAT-002, PRI-001 | P0 |
| SCR-STR-004 | ویرایش محصول | `/store/products/{p}/edit` | Store | Update content, status and attributes | tabbed form (content, specs, media, pricing, stock), status switch, audit note | L X S | CAT-002 | P0 |
| SCR-STR-005 | رسانه محصول | `/store/products/{p}/media` | Store | Upload and order images and video | uploader, cropper, drag-sort, alt-text per image, video poster | L X S | CAT-004, CAT-010 | P1 |
| SCR-STR-006 | به‌روزرسانی گروهی قیمت | `/store/prices` | Store | Change many prices with a preview | editable table, percent/absolute mode, consumer-price preview, confirmation with count | F L X S | PRI-005, PRI-001 | P0 |
| SCR-STR-007 | موجودی انبار | `/store/inventory` | Store | Keep stock accurate per SKU | inventory table, inline edit, stock status chips, movement history drawer | E F L S | STK-001, STK-003 | P0 |
| SCR-STR-008 | سفارش‌های دریافتی | `/store/orders` | Store | Work the order queue | status tabs, sub-order rows, SLA marker, bulk confirm | E F L X | ORD-002 | P0 |
| SCR-STR-009 | جزئیات سفارش | `/store/orders/{sub}` | Store | Fulfil one sub-order correctly | line table, buyer block, addresses, attachments (voice/image), action bar | L X | ORD-002, ORD-008 | P0 |
| SCR-STR-010 | ارسال و بارنامه | `/store/orders/{sub}/ship` | Store | Register shipment and courier details | carrier select, waybill number, package count, tracking link, confirm dialog | X S | ORD-009, SHP-001 | P0 |
| SCR-STR-011 | فاکتورهای من | `/store/invoices` | Store | Find issued and received invoices | invoice table, type filter, date range, download | E F L | INV-004, INV-001 | P0 |
| SCR-STR-012 | جزئیات فاکتور | `/store/invoices/{inv}` | Store | Read the invoice and its points | invoice document, points breakdown per line, print | L X | INV-001, INV-005 | P0 |
| SCR-STR-013 | تسویه و گردش مالی | `/store/settlements` | Store | Understand what is owed and paid | balance summary, settlement list, status badges, ledger link | E L X | FIN-002, FIN-003 | P0 |
| SCR-STR-014 | درخواست تسویه | `/store/settlements/request` | Store | Request a payout against available balance | amount with max, IBAN display, statement range, confirm + audit reason | X S | FIN-002, FIN-006 | P0 |
| SCR-STR-015 | صورت‌حساب دوره‌ای | `/store/settlements/statements` | Store | Reconcile a period | period picker, sales/deductions table, net payable, print/Excel | E F L | FIN-008 | P1 |
| SCR-STR-016 | کیف پول فروشگاه | `/store/wallet` | Store | Wallet balance and movements | balance card, transaction rows, top-up link | E L | FIN-001, FIN-003 | P1 |
| SCR-STR-017 | مناقصه‌های در دسترس | `/store/tenders` | Store | Find tenders to bid on | tender cards, eligibility, deadline, bid CTA | E F L | TND-001 | P1 |
| SCR-STR-018 | ثبت پیشنهاد مناقصه | `/store/tenders/{t}/bid` | Store | Submit a priced bid | line-item pricing, validity, attachments, review step | L X S | TND-003 | P1 |
| SCR-STR-019 | درخواست‌های سمپل | `/store/samples` | Store | Send samples and catalogues | request list, status flow, shipping fields, attachment | E L S | SMP-001 | P1 |
| SCR-STR-020 | پیام‌ها | `/store/messages` | Store | Talk to buyers, suppliers, admin | thread list, chat thread, attachment composer | L X | MSG-001, MSG-003 | P0 |
| SCR-STR-021 | تیکت‌ها | `/store/tickets` | Store | Formal support and disputes | ticket table, new ticket, status filters | E F L | MSG-004 | P1 |
| SCR-STR-022 | نظرات مشتریان | `/store/reviews` | Store | Read and reply to ratings | review cards, rating summary, reply form, report-abuse link | E L S | RVW-001, RVW-002 | P1 |
| SCR-STR-023 | گزارش فروش | `/store/reports/sales` | Store | See what sells, by period and product | date presets, chart with table alternative, top products, Excel export | E F L | RPT-001, RPT-003 | P1 |
| SCR-STR-024 | گزارش امتیاز | `/store/reports/points` | Store | Understand points earned by sales | points per invoice, tier progress, stars | E L | LOY-001, LOY-008 | P1 |
| SCR-STR-025 | پروفایل فروشگاه | `/store/profile` | Store | Maintain store identity and documents | logo/cover upload, address + map, contact, documents, 5-minute edit notice, approval-request mode | L X S | MER-003, MER-005 | P0 |

---

## 6. Supplier panel — 30 core screens (35 with §9.2)

| ID | Screen | Route | Role | Purpose | Key components | States | REQ | Pri |
|---|---|---|---|---|---|---|---|---|
| SCR-SUP-001 | داشبورد تأمین‌کننده | `/supplier` | Supplier | Today's orders, delays, stock, settlements | KPI strip, order queue, delay warnings, stock alerts, settlement card | E L X | MER-008, RPT-004 | P0 |
| SCR-SUP-002 | محصولات | `/supplier/products` | Supplier | Manage the producer catalogue | data table, filters, bulk status, export | E F L X | CAT-002 | P0 |
| SCR-SUP-003 | افزودن محصول | `/supplier/products/new` | Supplier | Create a product with full specification | wizard (basics, attributes, media, tier prices, stock), draft autosave | L X S | CAT-002, PRI-001 | P0 |
| SCR-SUP-004 | ویرایش محصول | `/supplier/products/{p}/edit` | Supplier | Update content and availability | tabbed form, status switch, audit note | L X S | CAT-002 | P0 |
| SCR-SUP-005 | رسانه محصول | `/supplier/products/{p}/media` | Supplier | Deliver consistent product photography | uploader, cropper, shot-list guidance, alt text, video | L X S | CAT-004, CAT-010 | P1 |
| SCR-SUP-006 | قیمت‌گذاری | `/supplier/prices` | Supplier | Set colleague, volume and consumer-facing bases | price table by tier, percent/absolute bulk edit, preview, history drawer | F L X S | PRI-005, PRI-006 | P0 |
| SCR-SUP-007 | موجودی انبار | `/supplier/inventory` | Supplier | Keep warehouse counts truthful | inventory table, inline edit, movement history | E F L S | STK-001 | P0 |
| SCR-SUP-008 | سفارش‌ها | `/supplier/orders` | Supplier | Process incoming orders | status tabs, SLA column, bulk actions, search by buyer | E F L X | ORD-002 | P0 |
| SCR-SUP-009 | جزئیات سفارش | `/supplier/orders/{sub}` | Supplier | Decide and fulfil | lines, buyer, shipping method, attachments, action bar (confirm/reject/incomplete) | L X | ORD-002, ORD-008 | P0 |
| SCR-SUP-010 | رد سفارش و بازگشت وجه | `/supplier/orders/{sub}/reject` | Supplier | Refuse an order and refund to the buyer's IBAN | reason select, refund preview, IBAN confirmation, audit reason | X S | ORD-004, PAY-006 | P0 |
| SCR-SUP-011 | ثبت سفارش ناقص | `/supplier/orders/{sub}/incomplete` | Supplier | Refer an incomplete order instead of paying delay penalty | missing-items picker, message to buyer, misuse warning (negative points) | X S | ORD-005 | P1 |
| SCR-SUP-012 | ارسال و بارنامه | `/supplier/orders/{sub}/ship` | Supplier | Register dispatch and courier | carrier, waybill, dispatch date, delay-penalty preview | X S | ORD-009, CMP-002 | P0 |
| SCR-SUP-013 | لغو سفارش | `/supplier/orders/{sub}/cancel` | Supplier | Cancel and refund when unavoidable | reason, refund route (wallet/sheba), impact note | X S | ORD-004 | P1 |
| SCR-SUP-014 | فاکتورها | `/supplier/invoices` | Supplier | Locate issued invoices | table with buyer, amount, status, print | E F L | INV-001, INV-004 | P0 |
| SCR-SUP-015 | فاکتور تکمیلی | `/supplier/invoices/{inv}/supplementary` | Supplier | Bill an extra cost after the fact | line editor, reason, evidence upload, send-to-buyer confirm | L X S | INV-002 | P1 |
| SCR-SUP-016 | تسویه | `/supplier/settlements` | Supplier | See payable balance and payout status | balance, settlement rows, hold reasons (open complaints) | E L X | FIN-002, ORD-007 | P0 |
| SCR-SUP-017 | صورت‌حساب | `/supplier/settlements/statements` | Supplier | Reconcile daily sales and deductions | period picker, daily rows, commission/penalty columns, Excel | E F L | FIN-007, FIN-008 | P0 |
| SCR-SUP-018 | خسارت‌های تأخیر | `/supplier/compensations` | Supplier | See every penalty applied and why | penalty rows, order link, percent, dispute CTA | E L X | CMP-001, CMP-002 | P1 |
| SCR-SUP-019 | نمایندگی‌ها | `/supplier/agencies` | Supplier | Publish nationwide agencies for referral | agency table, map view, add/edit, service scope | E L S | MER-007 | P1 |
| SCR-SUP-020 | جشنواره‌ها | `/supplier/festivals` | Supplier | Put products into seasonal campaigns | festival list, product picker, discount, approval status | E L S | MER-008, PRM-002 | P1 |
| SCR-SUP-021 | بنر تبلیغاتی | `/supplier/banners` | Supplier | Request a banner slot for a date range | request list, slot calendar, creative upload, status | E L X S | MER-009 | P1 |
| SCR-SUP-022 | درخواست بنر جدید | `/supplier/banners/new` | Supplier | Specify dates, slot and creative | date range, placement preview, creative upload with size guide, price note | L X S | MER-009, ADM-012 | P1 |
| SCR-SUP-023 | نوتیفیکیشن‌ها | `/supplier/notifications` | Supplier | Reach customers with paid or free sends | campaign list, quota meter, status, results | E L X | NTF-002, NTF-001 | P1 |
| SCR-SUP-024 | ساخت نوتیفیکیشن | `/supplier/notifications/new` | Supplier | Compose and pay for a targeted notification | audience filter with estimated count, message builder (text/image/link/video), schedule, cost preview, pay | L X S | NTF-002, PAY-001 | P1 |
| SCR-SUP-025 | پنل پیامک | `/supplier/sms` | Supplier | Send SMS to filtered customer segments | segment builder, template picker, credit balance, delivery report | E L X S | NTF-003 | P1 |
| SCR-SUP-026 | سمپل و کاتالوگ | `/supplier/samples` | Supplier | Answer sample and catalogue requests | request table, status flow, shipping, attachment reply | E L S | SMP-001 | P1 |
| SCR-SUP-027 | مناقصه‌ها | `/supplier/tenders` | Supplier | Find and answer tenders | tender cards, invitation marker, bid form, status | E F L | TND-001, TND-003 | P1 |
| SCR-SUP-028 | مزایده‌ها | `/supplier/auctions` | Supplier | Run or join auctions | auction cards, countdown, bid history, rules | E L X | TND-002 | P1 |
| SCR-SUP-029 | گزارش فروش | `/supplier/reports/sales` | Supplier | Analyse sales by product, period, buyer type | date presets, chart + table, top SKUs, Excel export | E F L | RPT-001, RPT-002 | P0 |
| SCR-SUP-030 | پیام‌ها و تیکت‌ها | `/supplier/messages`, `/supplier/tickets` | Supplier | Communicate with buyers, colleagues, admin | thread list, chat, ticket table | E L X | MSG-001, MSG-003, MSG-004 | P0 |

Supplier screens beyond this table: `/supplier/reviews` (SCR-SUP-031, P1), `/supplier/profile`
(SCR-SUP-032, P0), `/supplier/page` public-page editor (SCR-SUP-033, P1), `/supplier/slideshows`
(SCR-SUP-034, P2), `/supplier/reports/points` (SCR-SUP-035, P2) — see §9.2.

---

## 7. Marketer panel — 21 core screens (22 with §9.2)

| ID | Screen | Route | Role | Purpose | Key components | States | REQ | Pri |
|---|---|---|---|---|---|---|---|---|
| SCR-MKT-001 | داشبورد بازاریاب | `/marketer` | Marketer | Today's field plan and earnings | KPI strip (network, visits, commission), visit-today list, CTA «ثبت فروشگاه», QR shortcut | E L X | MKT-004, GEO-002 | P0 |
| SCR-MKT-002 | شبکه فروشگاه‌ها | `/marketer/network` | Marketer | Manage only the stores this marketer owns | data table, active/inactive filter, search, map toggle, add-store CTA | E F L X | MKT-003, GEO-003 | P0 |
| SCR-MKT-003 | جزئیات فروشگاه | `/marketer/network/{store}` | Marketer | Full record of a visited store | profile block, owner photos, gallery, map, invoices, visit history, edit CTA | L X | MKT-003, MER-005 | P0 |
| SCR-MKT-004 | ویرایش فروشگاه (۵ دقیقه) | `/marketer/network/{store}/edit` | Marketer | Correct a registration immediately after creating it | form with countdown chip, save, then «درخواست ویرایش» mode | L X S | MER-005 | P0 |
| SCR-MKT-005 | ثبت فروشگاه جدید | `/marketer/register-store` | Marketer | Onboard a store door-to-door | 4-step wizard: identity & duplicate check → shop details → photos & location → credentials | L X S D | MER-001, MER-002, MER-003 | P0 |
| SCR-MKT-006 | بررسی تکراری بودن فروشگاه | step in wizard | Marketer | Prevent duplicate stores before data entry | three-field lookup (national ID, postal code, mobile), conflict card with owner marketer | E X | MER-002 | P0 |
| SCR-MKT-007 | مشتریان | `/marketer/customers` | Marketer | Keep the customer list of the network | table, city filter, add CTA, last-visit column | E F L | MER-001, GEO-003 | P1 |
| SCR-MKT-008 | ثبت مشتری | `/marketer/customers/new` | Marketer | Capture a customer with photo and location | identity fields, shop photo, map picker, project note | L X S | ORD-011, GEO-004 | P1 |
| SCR-MKT-009 | ویزیت‌ها | `/marketer/visits` | Marketer | Prove and review field activity | visit list with sync state, date filter, map, outcome chips | E F L D | GEO-001, GEO-003 | P0 |
| SCR-MKT-010 | ثبت ویزیت | `/marketer/visits/new` | Marketer | Log a visit with GPS and evidence | store picker, auto location, outcome select, photos, voice note, offline draft | L X S D | GEO-001, GEO-004 | P0 |
| SCR-MKT-011 | سفارش مشتریان | `/marketer/orders` | Marketer | Record customer orders and projects for the network | order form, product picker, customer link, status list | E L S | ORD-011 | P1 |
| SCR-MKT-012 | فاکتورهای شبکه | `/marketer/invoices` | Marketer | See invoices of stores he visited (read-only) | invoice table scoped to own network, buyer, amount, status, print | E F L | INV-004, MKT-003 | P0 |
| SCR-MKT-013 | پورسانت | `/marketer/commissions` | Marketer | Understand earnings line by line | commission rows (visit fee, sales %, salary), period filter, plan badge, rule tooltip | E L X | MKT-004, MKT-006 | P0 |
| SCR-MKT-014 | تسویه | `/marketer/settlements` | Marketer | Request and track payouts | balance, request form, payout history, hold reasons | E L X S | FIN-002, CMP-003 | P0 |
| SCR-MKT-015 | کد و QR اختصاصی | `/marketer/qr` | Marketer | Get the referral asset for field use | QR card, download PNG/SVG, copy link, print business-card sheet, scan stats | L | MKT-001 | P0 |
| SCR-MKT-016 | عملکرد | `/marketer/performance` | Marketer | Improve the next visit with data | charts + table alternative, conversion by store, activity timeline, target progress | E L | GEO-002, RPT-004 | P1 |
| SCR-MKT-017 | نقشه شبکه | `/marketer/map` | Marketer | Plan a route across own stores | map with pins, status colors, cluster, list sync, «مسیریابی» link | E L X D | GEO-002, GEO-005 | P1 |
| SCR-MKT-018 | گزارش تخلف | `/marketer/reports` | Marketer | Escalate a supplier or store problem | report form, category, evidence upload, status history | E L S | USR-005, MER-011 | P1 |
| SCR-MKT-019 | تیکت‌ها | `/marketer/tickets` | Marketer | Message the admin formally | ticket list, new ticket, thread | E F L | MSG-004, MSG-001 | P0 |
| SCR-MKT-020 | پیام‌ها | `/marketer/messages` | Marketer | Chat with permitted users | thread list, chat, attachments | L X | MSG-002, MSG-003 | P0 |
| SCR-MKT-021 | پروفایل و پلن | `/marketer/profile` | Marketer | See identity, plan conditions and payout mix | profile form, plan card (A/B/C/D), commission rules, bank info | L X S | MKT-001, MKT-004 | P0 |

---

## 8. Admin panel — 58 core screens (90 with §9.3)

| ID | Screen | Route | Role | Purpose | Key components | States | REQ | Pri |
|---|---|---|---|---|---|---|---|---|
| SCR-ADM-001 | داشبورد مدیریت | `/admin` | Admin | Operate the marketplace from one screen | KPI strip (GMV, orders, new stores, pending approvals), approval queue, alerts, live map link | E L X | RPT-004, ADM-003 | P0 |
| SCR-ADM-002 | صف تأییدها | `/admin/approvals` | Admin | Clear every blocked item in one place | tabs (stores, profile edits, banners, samples, tenders, ads, reviews), bulk approve/reject with reason | E F L X S | ADM-012 | P0 |
| SCR-ADM-003 | گزارش رویدادها | `/admin/audit-log` | Admin | Answer "who changed what" | audit table, actor/action/target filters, before/after diff drawer, export | E F L | ADM-009, IAM-008 | P0 |
| SCR-ADM-004 | کاربران | `/admin/users` | Admin | Find and act on any account | data table with role/level filters, status, last login, bulk actions | E F L X | ADM-001 | P0 |
| SCR-ADM-005 | جزئیات کاربر | `/admin/users/{user}` | Admin | Full 360° of one account | identity block, roles, orders, wallet, points, documents, activity tabs | L X | ADM-001, USR-003 | P0 |
| SCR-ADM-006 | نقش‌های کاربر | `/admin/users/{user}/roles` | Admin | Grant or revoke primary and secondary roles | role checkboxes with allowed-combination hints, effect preview, type-to-confirm | L X S | IAM-005, ADM-006 | P0 |
| SCR-ADM-007 | کیف پول و امتیاز کاربر | `/admin/users/{user}/wallet` | Admin | Correct balances with an audit trail | balance card, adjust form (amount, reason, reference), ledger rows | L X S | FIN-005, LOY-005 | P0 |
| SCR-ADM-008 | مدارک کاربر | `/admin/users/{user}/documents` | Admin | Verify identity and business documents | document list, signed-URL preview, approve/reject with reason | E L X | MER-006, IAM-008 | P1 |
| SCR-ADM-009 | نقش‌ها | `/admin/roles` | Admin | Define the role catalogue | role cards, member counts, create role, secondary-combo matrix | E L | ADM-011, IAM-005 | P0 |
| SCR-ADM-010 | ویرایش دسترسی نقش | `/admin/roles/{role}/permissions` | Admin | Set capabilities per role | permission matrix by area, inherited locks, diff preview before save | L X S | ADM-011 | P0 |
| SCR-ADM-011 | ادمین‌ها | `/admin/admins` | Admin | Control who holds admin access | admin table, 2FA state, last login, suspend, invite | E L X S | ADM-010, IAM-004 | P1 |
| SCR-ADM-012 | دسته‌بندی‌ها | `/admin/categories` | Admin | Shape the catalogue tree | tree editor, drag order, visibility, product counts | E L S | CAT-001 | P0 |
| SCR-ADM-013 | ویرایش دسته | `/admin/categories/{c}` | Admin | Configure filters and attributes per category | attribute assignment, filterable flags, SEO fields, banner slot | L X S | CAT-001, CAT-003 | P0 |
| SCR-ADM-014 | برندها | `/admin/brands` | Admin | Maintain the brand list | table, logo upload, merge duplicates | E F L S | CAT-001 | P0 |
| SCR-ADM-015 | ویژگی‌ها | `/admin/attributes` | Admin | Define product parameters and filter behaviour | attribute groups, types, units, filterable/compare flags | E L X S | CAT-002, CAT-003 | P0 |
| SCR-ADM-016 | محصولات | `/admin/products` | Admin | Oversee every product | data table, supplier filter, status, price range, bulk actions, export | E F L X | ADM-004 | P0 |
| SCR-ADM-017 | فرم محصول (ادمین) | `/admin/products/new`, `/{p}` | Admin | Create or correct any product | tabs: content, attributes, per-level prices, media, stock, SEO | L X S | ADM-004, CAT-002 | P0 |
| SCR-ADM-018 | قیمت‌های محصول | `/admin/products/{p}/prices` | Admin | Set per-level and volume prices | price matrix by role/tier, volume bands, consumer markup preview, history | L X S | PRI-001, PRI-003 | P0 |
| SCR-ADM-019 | انبار | `/admin/inventory` | Admin | Fix stock discrepancies | inventory table, adjust with reason, movement log | E F L S | STK-003 | P1 |
| SCR-ADM-020 | قواعد قیمت‌گذاری | `/admin/pricing/rules` | Admin | Manage volume tiers and coefficient curves | rule table, effective_from, simulation panel | E L X S | PRI-002, LOY-014 | P1 |
| SCR-ADM-021 | ضریب قیمت مصرف‌کننده | `/admin/pricing/consumer-markup` | Admin | Set consumer price above colleague price | percent field, per-product overrides, preview list, apply-to-all confirm | L X S | PRI-003 | P0 |
| SCR-ADM-022 | سفارش‌ها | `/admin/orders` | Admin | Supervise the order pipeline | table, status/seller/date filters, SLA breach marker, export | E F L X | ORD-001, ADM-002 | P0 |
| SCR-ADM-023 | جزئیات سفارش (ادمین) | `/admin/orders/{order}` | Admin | Resolve a problem order | header, sub-orders, payments, timeline, internal notes, intervention actions | L X S | ORD-001, ORD-007 | P0 |
| SCR-ADM-024 | فاکتورها | `/admin/invoices` | Admin | Find any invoice | table, type filter, seller/buyer, export | E F L | INV-003 | P0 |
| SCR-ADM-025 | صدور فاکتور دستی | `/admin/invoices/new` | Admin | Bill outside the normal flow | buyer picker, line editor, price source, tax/shipping, issue + audit | L X S | INV-003, ADM-002 | P0 |
| SCR-ADM-026 | جزئیات فاکتور | `/admin/invoices/{inv}` | Admin | Inspect amounts, points and applied rules | invoice document, rule trace, points, reversal action | L X S | INV-002, INV-005 | P0 |
| SCR-ADM-027 | درگاه‌های پرداخت | `/admin/payments/gateways` | Admin | Activate and order the 12 gateways | gateway cards, credential-state badge (set/missing, never the secret), priority, split-payment toggle | E L X S | PAY-001, PAY-002 | P0 |
| SCR-ADM-028 | تراکنش‌ها | `/admin/payments/transactions` | Admin | Trace any payment | table with gateway ref, status, amount, order, retry/reconcile actions | E F L | PAY-003 | P0 |
| SCR-ADM-029 | مغایرت‌گیری | `/admin/payments/reconciliation` | Admin | Match gateway statements to our ledger | import file, matched/unmatched split, resolve action, export | E L X S | PAY-004 | P0 |
| SCR-ADM-030 | تسویه‌ها | `/admin/settlements` | Admin | Pay sellers correctly and on time | settlement table, status, hold reasons, batch actions | E F L X | FIN-002, FIN-008 | P0 |
| SCR-ADM-031 | جزئیات تسویه | `/admin/settlements/{s}` | Admin | Approve, hold or reject one payout | statement lines, deductions, approvals trail, confirm with reason | L X S | FIN-003, FIN-005 | P0 |
| SCR-ADM-032 | خروجی بانکی | `/admin/settlements/bank-export` | Admin | Produce the batch payment file for the bank | date range, seller selection, format check, generated-file history | L X S | FIN-006, RPT-003 | P0 |
| SCR-ADM-033 | دفتر کل | `/admin/ledger` | Admin | Immutable financial truth | ledger table, account filter, running balance, reversal entry form | E F L | FIN-003 | P0 |
| SCR-ADM-034 | پورسانت بازاریابان | `/admin/commissions` | Admin | Run and audit commission payouts | plan filter, computed rows, manual override with reason, payout run | E F L X S | MKT-004, MKT-005 | P1 |
| SCR-ADM-035 | خسارت‌ها | `/admin/compensations` | Admin | Approve delay penalties | penalty rows, percent or fixed-amount editor, buyer wallet credit, approval | E F L S | CMP-001, CMP-002 | P1 |
| SCR-ADM-036 | مرجوعی‌ها | `/admin/returns` | Admin | Decide return eligibility and refunds | return table, evidence, approve/reject, refund route | E F L X S | RTN-001, RTN-002 | P1 |
| SCR-ADM-037 | بازاریابان | `/admin/marketers` | Admin | Manage the field network | table with plan, network size, commission state, disable action | E F L X | MKT-003, MKT-005 | P0 |
| SCR-ADM-038 | جزئیات بازاریاب | `/admin/marketers/{m}` | Admin | One marketer's performance and scope | profile, plan, KPIs, network map, commission ledger, audit | L X S | MKT-003, GEO-002 | P0 |
| SCR-ADM-039 | شبکه یک بازاریاب | `/admin/marketers/{m}/network` | Admin | Inspect or reassign visited stores | store table, reassignment action (re-visit by new marketer), history | E L X S | MKT-003, MER-004 | P0 |
| SCR-ADM-040 | پورسانت یک بازاریاب | `/admin/marketers/{m}/commission` | Admin | Adjust a single marketer's payout | plan parameters, computed preview, override with reason, disable | L X S | MKT-005, CMP-003 | P0 |
| SCR-ADM-041 | پلن‌های بازاریابی | `/admin/marketers/plans` | Admin | Define plan A/B/C/D and payout mixes | plan matrix, conditions (visits, points, sales), assignment counts | E L S | MKT-004 | P1 |
| SCR-ADM-042 | رهگیری زنده | `/admin/fieldops/live` | Admin | See field activity as it happens | map with live pins, last-seen list, filter by marketer, refresh cadence | E L X | GEO-002 | P1 |
| SCR-ADM-043 | ویزیت‌ها | `/admin/fieldops/visits` | Admin | Audit visit evidence | visit table, photo drawer, GPS accuracy, approve/flag | E F L X S | GEO-001, GEO-003 | P1 |
| SCR-ADM-044 | فروشگاه‌های غیرفعال | `/admin/fieldops/inactive-stores` | Admin | Trigger re-visits | inactivity rule display, store list, assign-to-marketer | E F L S | MER-004, GEO-003 | P1 |
| SCR-ADM-045 | قواعد امتیاز | `/admin/loyalty/rules` | Admin | Control how points are earned | versioned rule sets, coefficients, effective_from, diff, publish | E L X S | LOY-001, LOY-002 | P0 |
| SCR-ADM-046 | پاداش‌ها | `/admin/loyalty/rewards` | Admin | Configure thresholds and reward types | reward table, threshold, expiry, wallet credit rule, 4× invoice condition | E L S | LOY-006, LOY-007 | P0 |
| SCR-ADM-047 | سطح‌های تخفیف | `/admin/loyalty/tiers` | Admin | Set the 4 % / 5 % bands and fixed discount | band editor (1–100, 100–200, >200), fixed n Toman, SMS notice toggle | L X S | LOY-009, LOY-010 | P0 |
| SCR-ADM-048 | تعدیل امتیاز | `/admin/loyalty/adjustments` | Admin | Grant or deduct points with a reason | user picker, amount, reason code, effective date, batch mode | L X S | LOY-005 | P0 |
| SCR-ADM-049 | کدهای تخفیف | `/admin/promotions/coupons` | Admin | Create and cap coupons | coupon builder (code, type, value, min basket, usage cap, dates, audience), usage report | E L X S | PRM-001, CRT-002 | P0 |
| SCR-ADM-050 | بنرها | `/admin/promotions/banners` | Admin | Run and schedule banner inventory | slot calendar, creative preview per breakpoint, approve requests, click stats | E L X S | PRM-001, MER-009 | P0 |
| SCR-ADM-051 | جشنواره‌ها | `/admin/promotions/festivals` | Admin | Group products into campaigns | festival list, product/ supplier assignment, period, home placement | E L S | PRM-002, MER-008 | P1 |
| SCR-ADM-052 | ساخت نوتیفیکیشن | `/admin/notifications/new` | Admin | Send a precise broadcast | audience builder with count, content builder, schedule, test-send, cost | L X S | NTF-001 | P0 |
| SCR-ADM-053 | سفارش‌های نوتیفیکیشن | `/admin/notifications/paid-orders` | Admin | Review and release paid campaigns | order list, payment state, content preview, approve/send | E F L X S | NTF-002 | P1 |
| SCR-ADM-054 | کمپین پیامکی | `/admin/notifications/sms` | Admin | Operate the SMS channel | segment builder, template, credit, delivery report, tariff settings | E L X S | NTF-003 | P1 |
| SCR-ADM-055 | قالب‌های اعلان | `/admin/notifications/templates` | Admin | Define order-status messages per channel | template list, channel tabs (SMS/WhatsApp/email/push), variables, preview | E L S | NTF-004, MSG-005 | P1 |
| SCR-ADM-056 | نظرسنجی‌ها | `/admin/polls` | Admin | Run site polls | poll builder, options, results chart + table | E L S | NTF-006 | P2 |
| SCR-ADM-057 | صفحات ثابت | `/admin/cms/pages` | Admin | Edit legal and informational pages | page table, rich-text editor, SEO fields, publish state | E L X S | CMS-002, CMS-003 | P0 |
| SCR-ADM-058 | منو و فوتر | `/admin/cms/menus` | Admin | Control navigation without deploys | menu tree editor, link picker, footer columns, preview | L X S | CMS-002 | P0 |

Admin screens 059–090 (content, services, shipping, settings, system) are tabulated in §9.3 to
keep this table scannable.

---

## 9. Remaining screens (static, utility, secondary)

### 9.1 Public static & utility — 9 screens

| ID | Screen | Route | Role | Purpose | States | REQ | Pri |
|---|---|---|---|---|---|---|---|
| SCR-PUB-037 | درباره ما | `/about` | Public | Company story and credibility | L | CMS-004 | P0 |
| SCR-PUB-038 | تماس با ما | `/contact` | Public | Reach the team with form and map | L X S | CMS-004, MSG-004 | P0 |
| SCR-PUB-039 | قوانین خرید | `/rules` | Public | Purchase rules before and after buying | L | CMS-003 | P0 |
| SCR-PUB-040 | سؤالات متداول | `/faq` | Public | Answer pre- and post-purchase questions | E L | CMS-003 | P0 |
| SCR-PUB-041 | صفحه ثابت عمومی | `/pages/{page}` | Public | Delivery, returns policy, warranty pages | L X | CMS-002 | P0 |
| SCR-PUB-042 | ورود از لینک بازاریاب | `/r/{code}` | Public | Attribute the visit and offer registration | L X | MKT-001, MKT-002 | P0 |
| SCR-PUB-043 | دعوت همکار | `/invite/{code}` | Public | Colleague-to-colleague invite with points | L X S | LOY-011 | P1 |
| SCR-PUB-044 | نصب PWA | install prompt | Public | Install the web app without an app store | — | CTR-003 | P1 |
| SCR-PUB-045 | حالت آفلاین | `/offline` | Public | Useful page when the network drops | — | CTR-003 | P1 |

### 9.2 Secondary panel screens — 15 screens

| ID | Screen | Route | Role | Purpose | States | REQ | Pri |
|---|---|---|---|---|---|---|---|
| SCR-CUS-025 | امنیت حساب | `/account/security` | Customer | Change password, review sessions | L X S | IAM-003, IAM-008 | P0 |
| SCR-CUS-026 | آخرین فعالیت‌ها | `/account/activity` | Customer | Personal activity log | E L | USR-004 | P2 |
| SCR-CUS-027 | مناقصه‌های من | `/account/tenders` | Customer | Track submitted tenders | E L | TND-001 | P2 |
| SCR-CUS-028 | سمپل‌های من | `/account/samples` | Customer | Track sample requests | E L | SMP-001 | P2 |
| SCR-CUS-029 | آگهی‌های من | `/account/classifieds` | Customer | Manage own ads | E L S | CLS-001 | P2 |
| SCR-CUS-030 | گزارش‌های من | `/account/reports` | Customer | Track submitted reports | E L | USR-005 | P2 |
| SCR-STR-026 | پیام‌ها و تیکت‌ها (فروشگاه) | `/store/messages`, `/store/tickets` | Store | Combined communication hub | L X | MSG-001, MSG-004 | P0 |
| SCR-STR-027 | آگهی‌های فروشگاه | `/store/classifieds` | Store | Post shop ads | E L S | CLS-001 | P2 |
| SCR-STR-028 | حضور در پتوق | `/store/patogh` | Store | List the shop as a service provider | E L S | PTG-001 | P2 |
| SCR-SUP-031 | نظرات و امتیازها | `/supplier/reviews` | Supplier | Read buyer satisfaction scores | E L | RVW-002 | P1 |
| SCR-SUP-032 | پروفایل تأمین‌کننده | `/supplier/profile` | Supplier | Identity, documents, bank details | L X S | MER-003, IAM-003 | P0 |
| SCR-SUP-033 | ویرایش صفحه عمومی | `/supplier/page` | Supplier | Curate the public brand page | L X S | MER-003, CMS-002 | P1 |
| SCR-SUP-034 | اسلایدشو اختصاصی | `/supplier/slideshows` | Supplier | Seasonal slides for the public page | E L S | PRM-003 | P2 |
| SCR-SUP-035 | گزارش امتیاز محصول | `/supplier/reports/points` | Supplier | Points awarded per product | E L | LOY-019 | P2 |
| SCR-MKT-022 | پیام‌ها و تیکت‌ها (ادمین) | `/marketer/tickets`, `/marketer/messages` | Marketer | Single communication entry point | L X | MSG-004 | P1 |

### 9.3 Admin screens 059–090 — 32 screens

| ID | Screen | Route | Role | Purpose | States | REQ | Pri |
|---|---|---|---|---|---|---|---|
| SCR-ADM-059 | ویجت‌های صفحه اصلی | `/admin/cms/widgets` | Admin | Order and schedule home sections | L X S | CAT-008, CMS-005 | P0 |
| SCR-ADM-060 | نوار اعلان سایت | `/admin/cms/notification-bar` | Admin | Broadcast a site-wide strip | L X S | NTF-005 | P1 |
| SCR-ADM-061 | مدیریت سؤالات متداول | `/admin/cms/faq` | Admin | Maintain FAQ entries | E L S | CMS-003 | P1 |
| SCR-ADM-062 | بلاگ — فهرست | `/admin/blog` | Admin | Manage articles | E F L | CMS-001 | P0 |
| SCR-ADM-063 | بلاگ — ویرایشگر | `/admin/blog/{article}` | Admin | Write with SEO metadata | L X S | CMS-001, CAT-009 | P0 |
| SCR-ADM-064 | مدیریت نظرات | `/admin/reviews` | Admin | Moderate reviews and reports | E F L X S | RVW-004 | P1 |
| SCR-ADM-065 | مدیریت دیوار | `/admin/classifieds` | Admin | Approve and remove ads | E F L S | CLS-001, ADM-012 | P1 |
| SCR-ADM-066 | دسته‌بندی آگهی‌ها | `/admin/classifieds/categories` | Admin | Define categories that gate ad creation | E L S | CLS-001 | P1 |
| SCR-ADM-067 | مدیریت پتوق | `/admin/patogh` | Admin | Approve provider profiles | E F L S | PTG-001, ADM-012 | P1 |
| SCR-ADM-068 | مناقصه‌ها | `/admin/tenders` | Admin | Oversee tender lifecycle | E F L X | TND-001 | P1 |
| SCR-ADM-069 | مزایده‌ها | `/admin/auctions` | Admin | Oversee auctions | E F L | TND-002 | P2 |
| SCR-ADM-070 | سمپل‌ها | `/admin/samples` | Admin | Track sample fulfilment | E F L | SMP-001 | P1 |
| SCR-ADM-071 | پایش گفتگوها | `/admin/messages` | Admin | Support quality and abuse review | E F L X | MSG-003 | P1 |
| SCR-ADM-072 | تیکت‌ها | `/admin/tickets` | Admin | Answer all user levels | E F L X S | MSG-004 | P0 |
| SCR-ADM-073 | گزارش تخلف‌ها | `/admin/reports/abuse` | Admin | Act on supplier and store violations | E F L X S | USR-005, MER-011 | P1 |
| SCR-ADM-074 | روش‌های ارسال | `/admin/shipping/methods` | Admin | Enable carriers and methods | E L S | SHP-001 | P0 |
| SCR-ADM-075 | مناطق ارسال | `/admin/shipping/zones` | Admin | Define free-shipping cities and regions | E L S | SHP-002 | P1 |
| SCR-ADM-076 | تعرفه‌های ارسال | `/admin/shipping/tariffs` | Admin | Price shipping by weight and zone | E L X S | SHP-003, CRT-003 | P0 |
| SCR-ADM-077 | تنظیمات عمومی | `/admin/settings/general` | Admin | Site identity, contact, locale | L X S | ADM-007 | P0 |
| SCR-ADM-078 | تنظیمات امنیت | `/admin/settings/security` | Admin | 2FA policy, session lifetime, captcha | L X S | ADM-010, IAM-007 | P0 |
| SCR-ADM-079 | نقش‌های ثانویه | `/admin/settings/roles` | Admin | Choose which role combinations are allowed | L X S | ADM-006 | P0 |
| SCR-ADM-080 | تنظیمات پرداخت | `/admin/settings/payments` | Admin | Gateway keys by env reference only | L X S | PAY-001 | P0 |
| SCR-ADM-081 | تنظیمات اعلان | `/admin/settings/notifications` | Admin | Channels, quotas, sender identities | L X S | NTF-004 | P1 |
| SCR-ADM-082 | ورود داده پایه | `/admin/settings/import` | Admin | Import categories, brands, sample products | L X S | CTR-007 | P2 |
| SCR-ADM-083 | پشتیبان‌گیری | `/admin/system/backups` | Admin | Verify daily backups and restores | L X S | CTR-007 | P1 |
| SCR-ADM-084 | سلامت سامانه | `/admin/system/health` | Admin | Queue, storage, failed jobs | L X | CTR-005 | P2 |
| SCR-ADM-085 | بازپخش امتیاز | `/admin/loyalty/replay` | Admin | Recompute points for a range, dry-run first | L X S | LOY-001 | P2 |
| SCR-ADM-086 | انبار ادمین — تاریخچه | `/admin/inventory/movements` | Admin | Trace stock movements | E F L | STK-003 | P2 |
| SCR-ADM-087 | ورود داده کاربران | `/admin/users/import` | Admin | Import colleagues in bulk | L X S | CTR-007 | P2 |
| SCR-ADM-088 | ماژول‌های غیرفعال | `/admin/settings/modules` | Admin | Toggle optional sections (دیوار، پتوق) | L X S | ADM-007 | P2 |
| SCR-ADM-089 | قالب‌های چاپ | `/admin/settings/print` | Admin | Invoice/statement header and footer | L X S | ADM-007, INV-003 | P2 |
| SCR-ADM-090 | لاگ درگاه‌ها | `/admin/payments/gateway-logs` | Admin | Inspect request/response traces | E F L | PAY-003 | P1 |

---

## 10. System screens — 7 screens

| ID | Screen | Route | Role | Purpose | States | REQ | Pri |
|---|---|---|---|---|---|---|---|
| SCR-SYS-001 | دسترسی غیرمجاز | `/403` | Any | Explain the required role, no data leak | U | CTR-006 | P0 |
| SCR-SYS-002 | صفحه یافت نشد | `/404` | Any | Recover from a bad link | — | CTR-006 | P0 |
| SCR-SYS-003 | نشست منقضی | `/419` | Any | Re-submit safely after session expiry | X | CTR-006 | P0 |
| SCR-SYS-004 | درخواست بیش از حد | `/429` | Any | Explain rate limiting and retry window | X | IAM-007 | P0 |
| SCR-SYS-005 | خطای سرور | `/500` | Any | Apologise with a correlation ID | X | CTR-006 | P0 |
| SCR-SYS-006 | حالت تعمیر | `/maintenance` | Any | Announce planned downtime | — | CTR-006 | P1 |
| SCR-SYS-007 | نتیجه پرداخت ناموفق | `/checkout/failure` | Customer | Same shell, different copy for hard failures | X | PAY-003 | P0 |

---

## 11. Totals

| Panel | Screens | P0 | P1 | P2 |
|---|---|---|---|---|
| Public storefront | 45 | 24 | 21 | 0 |
| Authentication | 10 | 8 | 1 | 1 |
| Customer | 30 | 15 | 10 | 5 |
| Store | 28 | 16 | 10 | 2 |
| Supplier | 35 | 16 | 17 | 2 |
| Marketer | 22 | 15 | 7 | 0 |
| Admin | 90 | 52 | 29 | 9 |
| System | 7 | 6 | 1 | 0 |
| **Total** | **267** | **152** | **96** | **19** |

Status: every row is `in design`; no row is deferred. Clauses without a dedicated screen are
cross-checked in §12.

## 12. Clause coverage and deliberately merged screens

Some contract clauses are behaviours inside a listed screen rather than separate pages. They are
mapped here so nothing is silently dropped:

| Clause | Where it is implemented |
|---|---|
| Appendix 2 cl.5 — compare up to five | SCR-PUB-012 (slots) + compare tray in SCR-PUB-010 |
| Appendix 2 cl.12 — admin-editable footer/pages/notification bar/widgets | SCR-ADM-057, 058, 059, 060 |
| Appendix 5 cl.9 — notification with audience, count, time | SCR-ADM-052 |
| Appendix 5 cl.11 — product star only from buyers of that product | SCR-CUS-007 eligibility rules |
| Appendix 5 cl.14 — negative violation score on producer profile | SCR-PUB-023 badge + SCR-ADM-073 |
| Appendix 5 cl.24 — reverse order number, buyer + distributor copies | SCR-SUP-014 print + SCR-CUS-004 (numbering service) |
| Appendix 5 cl.28 — persistent chat memory with audio/image/file | SCR-CUS-021, SCR-STR-020, SCR-SUP-030, SCR-MKT-020 |
| Appendix 5 cl.31 — photo capture during registration by customer/marketer | SCR-MKT-008 + SCR-CUS-024 |
| Appendix 5 cl.41 — voice note up to 2 minutes on invoices | `audio-recorder` in SCR-STR-009, SCR-SUP-009 |
| Appendix 5 cl.44 — store accounting incl. manual invoice | SCR-ADM-025 + SCR-STR-011 |
| Appendix 6 cl.7 — second presentation form for inactive stores | SCR-MKT-005 step 3 variant |
| Appendix 6 cl.9 — 5-minute edit window | SCR-MKT-004 countdown + approval-request mode |
| Appendix 7 cl.6 — one secondary level per user | SCR-ADM-006, SCR-ADM-079 |
| Appendix 11 cl.13 — automatic negative points for repeated "incomplete order" | SCR-SUP-011 warning + SCR-ADM-048 |
| Appendix 11 cl.17 — supplementary invoice | SCR-SUP-015 |
| Appendix 11 cl.19 — nationwide agencies on the producer page | SCR-SUP-019 → SCR-PUB-023 |
| Appendix 12 cl.3 — order capture by a local intermediary with location | SCR-MKT-011 |
| Appendix 12 cl.13 — deposit + settle on delivery | SCR-PUB-015 |
| Appendix 13 cl.2 — daily payment report to supplier and admin | SCR-SUP-017, SCR-ADM-030 (scheduled notification) |
| Appendix 13 cl.7 — two gateways in one transaction | SCR-PUB-016 |
| Appendix 14 cl.1–2 — location capture, live tracking, performance analysis | SCR-MKT-010, SCR-ADM-042, SCR-MKT-016 |
| Contract art.1 — PWA install, responsive, browser support | SCR-PUB-044, shell in `docs/UX/navigation-and-layout.md` §1 |

Out of scope by contract: reservation/appointment module (separate contract no. 2), native APK and
store publishing (`RB-MOB-01`). No screen exists for them by design (canonical-context §0).

## 13. Definition of done per screen

1. All listed states implemented and covered by a Pest browser/feature test.
2. Permission enforced by policy on the server, with a negative test for the foreign-tenant case
   (`docs/requirements/permissions-matrix.md` §5).
3. RTL check: logical properties only, no mirrored non-directional icon, Persian digits, LTR
   isolation for identifiers.
4. Keyboard reachable, visible focus, axe-core clean, one H1, breadcrumbs where §6 of the
   navigation doc requires them.
5. Empty copy, error copy and button labels use the fixed vocabularies in
   `docs/UX/design-system.md` §8.
6. Analytics/audit event emitted where the requirement demands traceability (price change, role
   change, settlement, refund).

## 14. Change log

| Version | Change | Count |
|---|---|---|
| v1 | Initial issue: public storefront, auth, five role panels, admin, system screens | 267 |

A screen removed after v1 keeps its ID (retired, never reused) and is recorded here with the
reason and the date.
