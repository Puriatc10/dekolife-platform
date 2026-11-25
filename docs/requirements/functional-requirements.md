# Functional Requirements — Dekolife Platform

**Purpose:** the single requirement catalogue for the Dekolife web platform, derived clause by clause from the signed contract and its fourteen appendices.
**Audience:** engineering, QA, product owner (کارفرما) and the delivery manager who signs off appendix clauses.
**Status:** baseline v1 — complete for the contract as extracted. Six requirements covering the native mobile clauses of پیوست ۸ are `Deferred` by scope decision (RB-MOB-01); everything else is `Specified`, `Ambiguous` or `Conflicting` with an ambiguity ID.

**Source:** `docs/contract/contract-extracted-text.txt` (OCR of the eleven-page PDF `input/contract/`), read together with `docs/canonical-context.md`. Persian quotations reproduce the extracted text verbatim, including its OCR artefacts (`اطالعات` for `اطلاعات`, `الینفک` for `لاینفک`); they are not corrected, so a quote can be grepped in the source file.

**Appendix count note:** the extracted contract contains fourteen numbered appendices. `docs/canonical-context.md` and the task brief speak of thirteen; the fourteenth (`پیوست ۱۴ — قابلیت‌های مدیریت بازاریابان`, two clauses) appears at the tail of the extracted text in a reversed/garbled block but is legible and is treated as in scope. Both clauses are mapped (`REQ-GEO-001`, `REQ-GEO-002`).

---

## 0. How to read a requirement

| Field | Meaning |
|---|---|
| ID | `REQ-<AREA>-<NNN>`, stable, never renumbered (canonical-context §4) |
| Source | contract page and the exact clause: appendix + clause number, or article + clause |
| Quote | verbatim Persian clause text the requirement is derived from |
| Actors | who triggers and who is affected |
| Statement | one testable behaviour in English; a compound clause is split into several IDs |
| Rules / edge cases | constraints, precedence, failure behaviour, boundary conditions |
| Priority | P0 = contract-critical or blocking money/access; P1 = contractual feature; P2 = contractual but low risk |
| Depends on | requirement that must exist first |
| Acceptance | 2–5 criteria that a tester can check without reading the code |
| Label | `CONTRACT` (verbatim from the contract) · `AGENT_DECISION` (our reading where the contract is silent) · `TECHNICAL_CONSTRAINT` (implementation-level obligation that the contract implies) |
| Status | `Specified` · `Ambiguous` (needs owner confirmation, see ambiguity ID) · `Conflicting` (two clauses disagree) · `Deferred` (out of this mission's scope, tracked in the roadmap) |

Every requirement traces to exactly one primary clause; where two appendix clauses demand the same behaviour the requirement lists the second clause under *Also satisfies* rather than duplicating the requirement (see `docs/contract/contract-requirements-matrix.md`).

**Composition:** 366 requirements are labelled `CONTRACT`, 16 `TECHNICAL_CONSTRAINT` (obligations the contract implies but does not state as behaviour) and 8 `AGENT_DECISION` (our reading where the contract is silent — each listed with its reasoning). Thirty requirements trace to contract articles rather than appendix clauses (materials 1–16), which is why the appendix tables sum to 360.

**Totals:** 390 requirements across 29 areas; 211 numbered contract clauses (182 appendix clauses plus 28 sub-items of پیوست ۳ and the appendix-5 reservation note) mapped; 0 unmapped.

| Area | Domain | Requirements | P0 | P1 | P2 |
|---|---|---:|---:|---:|---:|---:|
| [CTR](#ctr) | Contract, architecture and delivery obligations | 60 | 18 | 25 | 17 |
| [IAM](#iam) | Identity and access | 15 | 11 | 3 | 1 |
| [USR](#usr) | Users and profiles | 12 | 1 | 8 | 3 |
| [MER](#mer) | Merchants and suppliers | 20 | 7 | 11 | 2 |
| [CAT](#cat) | Catalog | 16 | 5 | 7 | 4 |
| [PRI](#pri) | Pricing | 11 | 6 | 5 | 0 |
| [STK](#stk) | Inventory | 6 | 4 | 1 | 1 |
| [CRT](#crt) | Cart and checkout | 8 | 4 | 3 | 1 |
| [ORD](#ord) | Orders | 20 | 7 | 10 | 3 |
| [INV](#inv) | Invoices | 11 | 3 | 8 | 0 |
| [PAY](#pay) | Payments | 15 | 5 | 9 | 1 |
| [FIN](#fin) | Finance and settlement | 16 | 6 | 9 | 1 |
| [MKT](#mkt) | Field sales (marketers) | 16 | 2 | 12 | 2 |
| [CMP](#cmp) | Compensation and violations | 9 | 1 | 7 | 1 |
| [LOY](#loy) | Loyalty, points and rewards | 19 | 7 | 11 | 1 |
| [PRM](#prm) | Promotions | 11 | 2 | 5 | 4 |
| [SHP](#shp) | Shipping | 8 | 3 | 5 | 0 |
| [RTN](#rtn) | Returns | 7 | 3 | 3 | 1 |
| [TND](#tnd) | Tenders and auctions | 10 | 0 | 8 | 2 |
| [CLS](#cls) | Classifieds (دیوار) | 7 | 1 | 5 | 1 |
| [PTG](#ptg) | Patogh services (پتوق) | 8 | 0 | 4 | 4 |
| [SMP](#smp) | Samples and catalogues | 4 | 0 | 3 | 1 |
| [MSG](#msg) | Messaging and tickets | 10 | 1 | 6 | 3 |
| [NTF](#ntf) | Notifications | 12 | 3 | 7 | 2 |
| [CMS](#cms) | Content management | 13 | 1 | 6 | 6 |
| [RVW](#rvw) | Reviews and ratings | 9 | 1 | 5 | 3 |
| [GEO](#geo) | Field operations and geolocation | 7 | 2 | 3 | 2 |
| [ADM](#adm) | Admin console | 19 | 3 | 13 | 3 |
| [RPT](#rpt) | Reporting | 11 | 1 | 8 | 2 |
| **Total** | | **390** | **108** | **210** | **72** |

---

<a id="ctr"></a>
## CTR — Contract, architecture and delivery obligations

Contract-level, legal, architecture, infrastructure and delivery obligations. These requirements are not features: they bind the delivery (scope, architecture, security, handover, warranty) and are the anchor for every other area.

### REQ-CTR-001 — Full-scope delivery of web platform plus mobile app per all appendices

- **Source:** `page 1 / ماده ۱`

> مجری متعهد است تمامی خدمات مندرج در پیوست‌های این قرارداد )که جزء الینفک آنها به‌شمار می‌روند( را انیعً طبق مشخصات مصوب طراحی، پیاده‌سازی، آزمون و به‌صورت کامل، پایدار و بدون نقص تحویل کارفرما نماید.

- **Actors:** Client (کارفرما), Vendor (مجری)
- **Statement:** The delivered platform implements every capability, option and filter named in the fourteen appendices; no appendix clause is partially delivered without a recorded addendum or a documented deferral.
- **Rules / edge cases:** Each numbered appendix clause is individually demonstrable in acceptance testing; a clause skipped without written addendum is a contract breach (see REQ-CTR-020).
- **Priority:** P0 · **Depends on:** —
- **Acceptance:**
  1. A clause-by-clause demo script exists covering all 182 numbered appendix clauses
  2. Every clause in the traceability matrix resolves to at least one tested requirement
  3. No capability named in appendices is absent from the delivered UI without a deferral record
- **Label:** CONTRACT · **Status:** Specified

### REQ-CTR-002 — Documented, editable source code handover

- **Source:** `page 1 / ماده ۱`

> تحویل سورس کامل برنامه به‌صورت داکیومنت‌شده، قابل ویرایش و توسعه توسط کارفرما؛

- **Actors:** Vendor, Client
- **Statement:** The full source repository is handed over with install documentation and folder structure so the client or a nominated expert can edit and extend it.
- **Rules / edge cases:** Handover includes dependency lock files, environment template, migrations and seeders; no compiled-only artifacts.
- **Priority:** P0 · **Depends on:** REQ-CTR-005
- **Acceptance:**
  1. Repository clones and boots from README on a clean machine
  2. Folder structure and module boundaries are documented
  3. No private credentials in the repository
- **Label:** CONTRACT · **Status:** Specified

### REQ-CTR-003 — Production deployment on client infrastructure with domain and SSL

- **Source:** `page 1 / ماده ۱`

> راه‌اندازی کامل روی سرور کارفرما، تنظیم دامنه و گواهی SSL و آزمون عملکرد در محیط عملیاتی؛

- **Actors:** Vendor, Client (hosting owner)
- **Statement:** The platform is deployed on the client's server with domain, TLS certificate and a performance test executed in the production environment.
- **Rules / edge cases:** Client supplies hosting, domain, SSL and gateway accounts (ماده ۵ بند ۳); vendor configures. TLS must be forced for all admin routes.
- **Priority:** P0 · **Depends on:** REQ-CTR-007, REQ-CTR-011
- **Acceptance:**
  1. HTTPS serves the app with a valid certificate and HTTP redirects to HTTPS
  2. A performance test report from the production environment is attached to delivery
  3. Deployment runbook reproduces the environment from scratch
- **Label:** CONTRACT · **Status:** Specified

### REQ-CTR-004 — Payment gateway accounts configured and live-verified

- **Source:** `page 1 / ماده ۱`

> اتصال و پیکربندی درگاه )های( پرداخت اینترنتی مطابق پیوست‌ها؛

- **Actors:** Vendor, Client
- **Statement:** All contracted bank gateways are configured as working drivers and verified with a real sandbox/production transaction before final delivery.
- **Rules / edge cases:** Credential storage is encrypted at rest; gateway callbacks are idempotent; see REQ-PAY-001.
- **Priority:** P0 · **Depends on:** REQ-PAY-001
- **Acceptance:**
  1. Each activated gateway completes a test payment and a verified callback
  2. Failed and cancelled payments are recorded and reconciled
  3. Gateway credentials are never exposed to the browser
- **Label:** CONTRACT · **Status:** Specified

### REQ-CTR-005 — Laravel + MySQL three-layer MVC with modular structure

- **Source:** `page 5 / پیوست ۱ بند ۱`

> پیاده‌سازی با فریم‌ورک الراول )Laravel )و پایگاه داده MySQL، با معماری اموی‌سی سه‌لایه و ساختار ماژوالر قابل توسعه؛

- **Actors:** Engineering
- **Statement:** The backend is Laravel (PHP 8.4) on MySQL 8.4, organised as a modular monolith with domain/application/infrastructure/http layers per module.
- **Rules / edge cases:** Pinned versions live in composer.json; no SQLite for feature tests; module boundaries enforced by review and static analysis.
- **Priority:** P0 · **Depends on:** —
- **Acceptance:**
  1. Framework and database versions match the pinned stack in canonical-context
  2. Each canonical module has Domain/Application/Infrastructure/Http folders
  3. Cross-module writes are impossible without going through an application service
- **Label:** CONTRACT · **Status:** Specified

### REQ-CTR-006 — Code documentation set: README, API reference, schema description

- **Source:** `page 5 / پیوست ۱ بند ۲`

> کل کد پروژه کام الً داکیومنت‌شده )فایل README، مستند API و توضیح ساختار جداول( تحویل می‌شود؛

- **Actors:** Engineering, Client
- **Statement:** Documentation ships with the code: a README, an API reference for all public endpoints, and a description of every table and relationship.
- **Rules / edge cases:** API reference is generated from route definitions, not hand-maintained prose; schema doc is regenerated on migration change.
- **Priority:** P1 · **Depends on:** REQ-CTR-002
- **Acceptance:**
  1. README covers install, env, queue, backup and test commands
  2. Every api.php route appears in the API reference with auth requirements
  3. Schema doc lists all tables with columns and FKs
- **Label:** CONTRACT · **Status:** Specified

### REQ-CTR-007 — Responsive layout for mobile, tablet and desktop

- **Source:** `page 5 / پیوست ۱ بند ۳`

> سایت به‌صورت کام الً واکنش‌گرا )Responsive )برای موبایل، تبلت و دسکتاپ طراحی می‌شود؛

- **Actors:** All users
- **Statement:** Every screen is usable at 360 px, 768 px and 1280 px widths with RTL layout and no horizontal scrolling of primary content.
- **Rules / edge cases:** Admin data grids may scroll horizontally inside their own container; navigation must remain reachable on mobile.
- **Priority:** P0 · **Depends on:** —
- **Acceptance:**
  1. Home, category, product, cart, checkout and panel screens pass at three breakpoints
  2. No fixed-width layout breaks on a 360 px viewport
  3. Touch targets are at least 44 px on mobile
- **Label:** CONTRACT · **Status:** Specified

### REQ-CTR-008 — Installable PWA with icon, splash screen and smart caching

- **Source:** `page 5 / پیوست ۱ بند ۴`

> خروجی PWA فعال )قابل نصب روی اندروید و iOS از مرورگر( همراه با آیکون، اسپلش‌اسکرین و کش هوشمند؛

- **Also satisfies:** `پیوست ۵ بند ۲۷`
- **Actors:** Visitor, Consumer
- **Statement:** The web app ships a valid web manifest and service worker so it can be installed from the browser on Android and iOS with branded icon and splash screen.
- **Rules / edge cases:** Offline fallback page required; cart/checkout mutations must never be served from cache; cache version bumps on deploy.
- **Priority:** P1 · **Depends on:** REQ-CTR-007
- **Acceptance:**
  1. Lighthouse installability audit passes
  2. Manifest provides 192/512 px icons, name, theme colour and start_url
  3. Authenticated pages are not cached; an offline fallback is shown
- **Label:** CONTRACT · **Status:** Specified

### REQ-CTR-009 — Twelve independent bank gateways supported concurrently

- **Source:** `page 5 / پیوست ۱ بند ۵`

> پشتیبانی هم‌زمان از ۱۲ درگاه پرداخت بانکی مجزا و امکان استفاده هم‌زمان از دو درگاه در یک تراکنش با انتخاب کاربر؛

- **Actors:** Admin, Consumer, Store
- **Statement:** The payment layer supports twelve gateway drivers that can coexist; admin activates or deactivates each, and a client may choose a gateway per payment.
- **Rules / edge cases:** Gateways are plugins behind one driver contract; per-gateway credentials, currency and fee metadata; dual-gateway split per transaction is a separate requirement (REQ-PAY-002).
- **Priority:** P0 · **Depends on:** REQ-PAY-001
- **Acceptance:**
  1. Twelve driver slots exist and at least two are fully implemented and tested
  2. Enabling a gateway needs no code change
  3. A payment records which gateway processed it
- **Label:** CONTRACT · **Status:** Specified

### REQ-CTR-010 — Compatibility with standard desktop and mobile browsers

- **Source:** `page 5 / پیوست ۱ بند ۶`

> سازگاری کامل با مرورگرهای استاندارد )Edge ،Safari ،Firefox ،Chrome )و نسخه‌های موبایلی آن‌ها؛

- **Actors:** All users
- **Statement:** Core journeys work on current Chrome, Firefox, Safari and Edge plus their mobile versions, including iOS Safari.
- **Rules / edge cases:** Graceful degradation for unsupported features (e.g. push on iOS Safari < 16.4); no browser-sniffing gates except documented fallbacks.
- **Priority:** P1 · **Depends on:** REQ-CTR-007
- **Acceptance:**
  1. Checkout and payment complete on Safari iOS and Chrome Android
  2. No JavaScript console errors on any supported browser
  3. Unsupported APIs degrade to a functional fallback
- **Label:** CONTRACT · **Status:** Specified

### REQ-CTR-011 — Admin panel on a dedicated path/domain with two-factor authentication

- **Source:** `page 5 / پیوست ۱ بند ۷`

> پنل مدیریت مجزا روی دامنه/مسیر اختصاصی با احراز هویت دو مرحله‌ای برای نقش ادمین؛

- **Actors:** Admin
- **Statement:** The admin panel is served from a dedicated path or subdomain behind a separate guard, and admin sign-in requires a second factor.
- **Rules / edge cases:** Separate session cookie scope; 2FA enforced for every admin account, not optional; failed 2FA attempts rate-limited and audited.
- **Priority:** P0 · **Depends on:** REQ-IAM-012
- **Acceptance:**
  1. Admin guard is distinct from the user guard, with separate cookie name
  2. An admin cannot reach the panel without a valid TOTP/OTP code
  3. Non-admin roles receive 403 on the admin path
- **Label:** CONTRACT · **Status:** Specified

### REQ-CTR-012 — Data model extensible for new user levels and new product types without core rewrite

- **Source:** `page 5 / پیوست ۱ بند ۸`

> ساختار پایگاه داده به‌گونه‌ای که امکان افزودن سطح کاربری جدید یا نوع محصول تازه بدون بازنویسی هسته فراهم باشد؛

- **Actors:** Engineering, Admin
- **Statement:** User levels and product types are data-driven (tables + policies), so an admin-created level or product type works through configuration rather than schema changes.
- **Rules / edge cases:** Roles are many-to-many with a policy layer; product types own attribute sets defined as data. New level must inherit a permission template.
- **Priority:** P1 · **Depends on:** REQ-IAM-004, REQ-CAT-003
- **Acceptance:**
  1. Creating a new level in admin exposes it in registration/permissions without deployment
  2. A new product type with its own attributes renders in catalog and product form
  3. No hard-coded role enum in business logic
- **Label:** CONTRACT · **Status:** Specified

### REQ-CTR-013 — Daily automatic database backup with restore capability

- **Source:** `page 5 / پیوست ۱ بند ۹`

> پشتیبان‌گیری خودکار روزانه از دیتابیس به‌همراه امکان بازیابی؛

- **Actors:** Operations, Admin
- **Statement:** A scheduled job takes a daily database backup to off-container storage, retention is configurable, and a documented restore procedure is tested.
- **Rules / edge cases:** Backups encrypted; failure alerts the operator; retention default 30 days; restore drill recorded before go-live.
- **Priority:** P1 · **Depends on:** REQ-CTR-003
- **Acceptance:**
  1. Scheduler produces a dated dump every day and a failure raises an alert
  2. A restore into a clean database reproduces data to the backup timestamp
  3. Retention policy deletes old dumps automatically
- **Label:** CONTRACT · **Status:** Specified

### REQ-CTR-014 — Audit log for sensitive events

- **Source:** `page 5 / پیوست ۱ بند ۱۰`

> الگ کامل رویدادهای حساس )ورود، تغییر قیمت، تغییر سطح کاربری، تسویه( برای پیگیری.

- **Actors:** Admin, Engineering
- **Statement:** Login, price change, role/level change and settlement events are written to an append-only audit log with actor, subject, before/after values, IP and timestamp.
- **Rules / edge cases:** Log is append-only and retained; visible to admin with filters; identity-data changes also audited.
- **Priority:** P0 · **Depends on:** REQ-ADM-018
- **Acceptance:**
  1. Each of the four event types produces an audit row with before/after values
  2. Audit rows cannot be edited or deleted from the UI
  3. Admin can filter the log by actor, event type and date
- **Label:** CONTRACT · **Status:** Specified

### REQ-CTR-015 — Weekly written progress report with visual evidence

- **Source:** `page 2 / ماده ۳`

> مجری موظف است گزارش پیشرفت منظم هفتگی )متنی، تصویری یا ویدئویی( ارائه دهد.

- **Actors:** Vendor, Client
- **Statement:** A weekly progress report is produced with screenshots or a demo link, delivered on a fixed weekday for the whole project duration.
- **Rules / edge cases:** Client responds within 3 business days; 7 business days of silence counts as approval of the proposed design (REQ-CTR-016).
- **Priority:** P1 · **Depends on:** —
- **Acceptance:**
  1. A report exists for every project week with dated evidence
  2. Each report states completed, in-progress and blocked items
  3. Reports are archived in the shared channel
- **Label:** CONTRACT · **Status:** Specified

### REQ-CTR-016 — Client review window and tacit approval after seven business days

- **Source:** `page 2 / ماده ۳`

> کارفرما ظرف حداکثر ۳ روز کاری پاسخ ثبت کند؛ در صورت عدم پاسخ طی ۷ روز کاری، طرح پیشنهادی مجری در همان مرحله مورد تأیید شمرده می‌شود.

- **Actors:** Client, Vendor
- **Statement:** Design/acceptance checkpoints are answered within 3 business days; if no answer arrives within 7 business days the vendor's proposal is recorded as approved and the schedule proceeds.
- **Rules / edge cases:** Client-side delay in supplying content extends the delivery date day-for-day and is noted in the progress report (REQ-CTR-017).
- **Priority:** P2 · **Depends on:** REQ-CTR-015
- **Acceptance:**
  1. Checkpoint record stores request date, response date and approver
  2. Silence past 7 business days flips status to 'approved by default' with a timestamp
  3. The rule is reflected in the delivery schedule calculation
- **Label:** CONTRACT · **Status:** Specified

### REQ-CTR-017 — Schedule extension for client-side delays

- **Source:** `page 2 / ماده ۳`

> تأخیر کارفرما در ارائه اطالعات یا محتوا، موعد تحویل را روزبه‌روز به همان اندازه تسری می‌کند و در گزارش پیشرفت درج می‌گردد.

- **Actors:** Vendor, Client
- **Statement:** Each day of client-caused delay in content or feedback shifts the delivery date by one day and is recorded in the progress log.
- **Rules / edge cases:** Only delays attributable to client inputs count; vendor-caused delay is penalised instead (REQ-CTR-018).
- **Priority:** P2 · **Depends on:** REQ-CTR-016
- **Acceptance:**
  1. A delay ledger exists with cause, days and resulting delivery date
  2. The ledger is quoted in the progress report
  3. Delivery date shown to the client matches the ledger
- **Label:** CONTRACT · **Status:** Specified

### REQ-CTR-018 — Delay penalty of 0.5% per day capped at 20%

- **Source:** `page 2 / ماده ۴ بند ۲`

> در ازای هر روز تأخیر، معادل ۰٫۵ درصد از مبلغ کل قرارداد به‌عنوان جریمه تأخیر از مطالبات مجری کسر می‌شود )سقف کل جریمه ۲۰ درصد مبلغ قرارداد(؛

- **Actors:** Vendor, Client
- **Statement:** Late delivery accrues a penalty of 0.5% of the contract value per day, capped at 20%, deducted from vendor receivables.
- **Rules / edge cases:** Computed on 90,000,000 Toman; penalty does not remove the client's right to terminate after 15 business days (REQ-CTR-019).
- **Priority:** P2 · **Depends on:** REQ-CTR-017
- **Acceptance:**
  1. Penalty = 450,000 Toman per day (0.5% of 90,000,000), capped at 18,000,000
  2. Days attributable to client delay are excluded
  3. The calculation is reproducible from the delay ledger
- **Label:** CONTRACT · **Status:** Specified

### REQ-CTR-019 — Termination rights for delay beyond 15 business days or material breach

- **Source:** `page 3 / ماده ۹`

> در صورت فسخ به‌دلیل تأخیر تحویل بیش از ۱۵ روز کاری از موعد، مجری متعهد است جمع پرداختی‌های دریافتی کارفرما را ظرف ۱۵ روز، پس از کسر جریمه‌های مقرر در ماده ۴ )بند ۲(، واریز کند؛

- **Actors:** Client, Vendor
- **Statement:** If delivery is more than 15 business days late, or a material breach is not cured within 7 business days of written notice, the client may terminate and the vendor refunds payments minus accrued penalties within 15 days.
- **Rules / edge cases:** Material breach includes failure to deliver, breach of confidentiality, non-delivery of source, and quality collapse.
- **Priority:** P2 · **Depends on:** REQ-CTR-018
- **Acceptance:**
  1. Termination notice templates and cure-period tracking exist
  2. Refund calculation shows payments received minus penalty
  3. Obligations survive termination (confidentiality, IP)
- **Label:** CONTRACT · **Status:** Specified

### REQ-CTR-020 — Individual assessability of every numbered appendix clause

- **Source:** `page 3 / ماده ۴ بند ۱۰`

> در صورت اجرا نکردن هر یک از سرفصل‌های اصلی پیوست‌ها، ۵ درصد از مبلغ کل قرارداد به‌عنوان جریمه به نفع کارفرما بازگردانده یا از مطالبات مجری کسر می‌شود؛ سقف مجموع این جریمه ۱۰ درصد مبلغ قرارداد است

- **Actors:** Client, Vendor
- **Statement:** Each numbered appendix clause is individually verifiable in an acceptance checklist, because a missing clause triggers a 5% penalty capped at 10% overall.
- **Rules / edge cases:** Traceability matrix maps every clause to requirement IDs and test evidence; unmapped clauses must be zero.
- **Priority:** P0 · **Depends on:** REQ-CTR-001
- **Acceptance:**
  1. A machine-checkable checklist enumerates all 182 appendix clauses
  2. Each row links to at least one requirement and one verification artifact
  3. Clauses with no implementation are listed with reason and addendum status
- **Label:** CONTRACT · **Status:** Specified

### REQ-CTR-021 — Bespoke UX/UI design from wireframe to implemented CSS, no bought template

- **Source:** `page 5 / پیوست ۲ بند ۱`

> طراحی اختصاصی UX/UI از مرحله طرح اولیه تا پیاده‌سازی SASS/CSS/HTML بدون استفاده از قالب آماده برای بخش کاربری؛

- **Actors:** Design, Engineering
- **Statement:** The customer-facing UI is an original design delivered from wireframes through implemented HTML/CSS, not a purchased theme, and documented as a reusable component set.
- **Rules / edge cases:** Design tokens (colour, spacing, type) live in one place; RTL is the default direction; admin panel may use a base kit but must be themed to match.
- **Priority:** P1 · **Depends on:** REQ-CTR-007
- **Acceptance:**
  1. A component inventory exists and matches implemented Blade components
  2. No third-party storefront theme files are present in the repo
  3. Design tokens drive colours and typography
- **Label:** CONTRACT · **Status:** Specified

### REQ-CTR-022 — Three test layers executed before any delivery claim

- **Source:** `page 2 / ماده ۴ بند ۳`

> انجام آزمون کامل همه بخش‌ها )تست واحد، تست یکپارچگی، تست پذیرش با سناریوهای واقعی( پیش از اعالم تحویل نهایی؛

- **Actors:** Vendor
- **Statement:** Unit, integration and acceptance tests with realistic scenarios all pass in CI before delivery is announced.
- **Rules / edge cases:** Money, points and settlement rules each carry a dedicated test; negative authorization tests are mandatory per scoped resource.
- **Priority:** P0 · **Depends on:** REQ-CTR-005
- **Acceptance:**
  1. CI runs unit + feature tests against MySQL and blocks merge on failure
  2. Every money/points/settlement rule has a test
  3. Each scoped resource has a negative authorization test
- **Label:** CONTRACT · **Status:** Specified

### REQ-CTR-023 — Free correction of programming defects during the warranty period

- **Source:** `page 2 / ماده ۴ بند ۵`

> رفع رایگان کل عیوب و باگ‌های ناشی از خطای برنامه‌نویسی در دوره پشتیبانی مطابق ماده ۱۲؛

- **Actors:** Vendor, Client
- **Statement:** Defects caused by programming error are fixed at no charge for the five-year warranty window defined in ماده ۱۲.
- **Rules / edge cases:** Distinguishing defect vs change request is recorded per ticket; change requests go through the addendum mechanism (REQ-CTR-034).
- **Priority:** P1 · **Depends on:** REQ-CTR-037
- **Acceptance:**
  1. Every support ticket is classified defect or change request
  2. Defect tickets have no invoice attached
  3. Warranty end date is stored and displayed
- **Label:** CONTRACT · **Status:** Specified

### REQ-CTR-024 — Confidentiality of data, graphics and technical knowledge after contract end

- **Source:** `page 2 / ماده ۴ بند ۶`

> حفظ کامل رازداری اطالعات، داده‌ها، گرافیک و دانش فنی پروژه حتی پس از خاتمه قرارداد؛

- **Actors:** Vendor
- **Statement:** Project data, graphics and know-how stay confidential indefinitely, including after termination, with access limited to staff who need it.
- **Rules / edge cases:** Production data is never copied to developer machines without anonymisation; access to client documents is logged.
- **Priority:** P1 · **Depends on:** —
- **Acceptance:**
  1. A confidentiality clause is in every staff/contractor agreement
  2. Staging data is anonymised or synthetic
  3. Access to identity documents is logged and role-restricted
- **Label:** CONTRACT · **Status:** Specified

### REQ-CTR-025 — No reuse of this project's bespoke code or design elsewhere

- **Source:** `page 2 / ماده ۴ بند ۷`

> عدم استفاده از قالب‌ها و کدهای اختصاصی این پروژه در هیچ پروژه دیگر و برای هیچ شخص یا سازمان دیگری؛

- **Actors:** Vendor
- **Statement:** Bespoke code, templates and design produced for Dekolife are not reused for any other client or project.
- **Rules / edge cases:** Generic open-source libraries remain usable; the prohibition covers project-specific modules, copy and graphics.
- **Priority:** P1 · **Depends on:** —
- **Acceptance:**
  1. Repository licence header states client ownership
  2. Third-party assets are inventoried with licences
  3. No project-specific asset appears in the vendor portfolio without written permission
- **Label:** CONTRACT · **Status:** Specified

### REQ-CTR-026 — No subcontracting of the engagement without written consent

- **Source:** `page 3 / ماده ۴ بند ۸`

> عدم واگذاری کل یا بخشی از پیمان به شخص ثالث بدون رضایت کتبی کارفرما؛

- **Actors:** Vendor, Client
- **Statement:** The engagement is performed by the vendor's own operators; any delegation requires prior written client consent.
- **Rules / edge cases:** Mirrors ماده ۱۳ which grants immediate termination on breach.
- **Priority:** P2 · **Depends on:** —
- **Acceptance:**
  1. Delivery team roster is documented
  2. Any third-party involvement has a written consent record
  3. Breach triggers the termination path in REQ-CTR-035
- **Label:** CONTRACT · **Status:** Specified

### REQ-CTR-027 — Security baseline: data and key protection, injection, brute-force and upload defences

- **Source:** `page 3 / ماده ۴ بند ۹`

> رعایت استانداردهای فنی و امنیتی: حفاظت داده‌های کاربران و کلیدهای API، پشتیبان‌گیری منظم از دیتابیس، جلوگیری از حمالت متداول )تزریق SQL، بروت‌فورس، آپلود مخرب( و به‌روز نگه‌داشتن اجزای نرم‌افزاری؛

- **Actors:** Engineering, Security
- **Statement:** The application protects user data and API keys, resists SQL injection, brute force and malicious uploads, and keeps dependencies patched.
- **Rules / edge cases:** Prepared statements/ORM only; login throttling; MIME+extension validation with stored-outside-webroot files; dependency audit in CI.
- **Priority:** P0 · **Depends on:** REQ-CTR-003
- **Acceptance:**
  1. SQL injection probes on search/filter endpoints return no data leakage
  2. Repeated failed logins trigger throttling and audit entries
  3. Uploaded files are validated by MIME and stored on a private disk
- **Label:** CONTRACT · **Status:** Specified

### REQ-CTR-028 — Custody of client-supplied documents and prohibition on disclosure

- **Source:** `page 3 / ماده ۴ بند ۱۱`

> نگهداری امانت تمامی مدارک و فایل‌های دریافتی از کارفرما و عدم ارائه آن‌ها به غیر بدون اجازه کتبی.

- **Actors:** Vendor, Client
- **Statement:** Documents and files received from the client are held in custody, stored securely, and never disclosed to third parties without written permission.
- **Rules / edge cases:** Includes product images, price lists and customer lists; deletion on request after handover.
- **Priority:** P1 · **Depends on:** REQ-CTR-024
- **Acceptance:**
  1. Client files live in an access-controlled store with an owner record
  2. Download of client files is logged
  3. A deletion procedure exists for post-handover removal
- **Label:** CONTRACT · **Status:** Specified

### REQ-CTR-029 — Client content and information supplied within five business days of request

- **Source:** `page 3 / ماده ۵ بند ۱`

> ارائه اطالعات و محتوای موردنیاز )لوگو، تصاویر، متون، فهرست محصوالت/خدمات، سیاست‌های فروش و...( ظرف حداکثر ۵ روز کاری پس از درخواست مجری؛

- **Actors:** Client, Vendor
- **Statement:** Each content request from the vendor is answered by the client within five business days, tracked as an item with a due date.
- **Rules / edge cases:** Unanswered requests feed the schedule-extension ledger (REQ-CTR-017).
- **Priority:** P2 · **Depends on:** REQ-CTR-017
- **Acceptance:**
  1. A content request register exists with request/due/received dates
  2. Overdue items appear in the weekly report
  3. Receipt of each asset type is acknowledged
- **Label:** CONTRACT · **Status:** Specified

### REQ-CTR-030 — Client supplies hosting, domain, TLS and gateway accounts

- **Source:** `page 3 / ماده ۵ بند ۳`

> تأمین هاست، دامنه، SSL و درگاه پرداخت طبق استانداردهای اعالمی مجری؛

- **Actors:** Client
- **Statement:** The client provides hosting, domain, TLS certificate and payment-gateway merchant accounts matching the vendor's published standards.
- **Rules / edge cases:** Environment prerequisites are documented before build; missing prerequisites block go-live, not development.
- **Priority:** P1 · **Depends on:** REQ-CTR-003
- **Acceptance:**
  1. A prerequisites checklist is signed off before deployment
  2. Server meets the documented PHP/MySQL/queue requirements
  3. Merchant credentials are delivered through a secure channel
- **Label:** CONTRACT · **Status:** Specified

### REQ-CTR-031 — Intellectual property transfers to the client on full settlement

- **Source:** `page 3 / ماده ۷ بند ۱`

> کل حقوق مادی و معنوی سورس کد، طراحی گرافیکی، نشانه‌ها و داده‌های پروژه پس از تسویه کامل به کارفرما منتقل می‌شود؛

- **Actors:** Client, Vendor
- **Statement:** All economic and moral rights in source code, graphics, marks and project data pass to the client upon full payment.
- **Rules / edge cases:** Until settlement the vendor holds a licence to operate; portfolio use requires written permission (REQ-CTR-032).
- **Priority:** P1 · **Depends on:** REQ-CTR-002
- **Acceptance:**
  1. Ownership statement and licence inventory are part of handover
  2. Source is transferred without escrow conditions after final payment
  3. Third-party licences remain compatible with client ownership
- **Label:** CONTRACT · **Status:** Specified

### REQ-CTR-032 — Client owns all user data; vendor may not exploit it competitively

- **Source:** `page 3 / ماده ۷ بند ۴`

> همه داده‌های کاربران به‌سوی سامانه متعلق به کارفرماست و مجری حق استفاده رقابتی از آنها ندارد.

- **Actors:** Client, Vendor
- **Statement:** All user data belongs to the client, is exportable in a documented format, and is never used by the vendor for competing purposes.
- **Rules / edge cases:** Export includes users, merchants, orders, invoices, ledger; excludes secrets.
- **Priority:** P1 · **Depends on:** REQ-CTR-031
- **Acceptance:**
  1. A full data export command produces documented CSV/SQL dumps
  2. No analytics or telemetry ships data to vendor infrastructure
  3. Access to production data is limited and audited
- **Label:** CONTRACT · **Status:** Specified

### REQ-CTR-033 — Changes only through a written addendum with cost and schedule

- **Source:** `page 3 / ماده ۸`

> هر نوع تغییر یا اطالعیه به مفاد این قرارداد افرصً با توافق کتبی دو طرف در قالب الحاقیه صورت می‌گیرد؛

- **Actors:** Client, Vendor
- **Statement:** Any new capability added after signature enters through a written addendum stating cost and delivery time; verbal agreements have no effect.
- **Rules / edge cases:** Addendum register mirrors the table in ماده ۱۵ (row, description, cost, delivery, agreement).
- **Priority:** P2 · **Depends on:** REQ-CTR-034
- **Acceptance:**
  1. An addendum register exists with signed rows
  2. Features built without a register row are not treated as contractual
  3. Each addendum links to the requirements it adds
- **Label:** CONTRACT · **Status:** Specified

### REQ-CTR-034 — Addendum register table governs out-of-scope features

- **Source:** `page 4 / ماده ۱۵`

> هر قابلیت یا سرویس جدیدی که پس از امضا به پروژه اضافه شود، موضوع این جدول الحاقیه است و با توافق کتبی دو طرف )هزینه و زمان تحویل( وارد می‌شود؛

- **Actors:** Client, Vendor
- **Statement:** A feature added after signature is only binding once its addendum row records description, cost and delivery time and both parties agree.
- **Rules / edge cases:** Rows without agreement do not create obligations and carry no ماده ۴ rights.
- **Priority:** P2 · **Depends on:** —
- **Acceptance:**
  1. Register table has columns row/description/cost/time/agreement
  2. Unagreed rows are visibly marked as non-binding
  3. Scope boundary document references the register
- **Label:** CONTRACT · **Status:** Specified

### REQ-CTR-035 — Immediate termination on uncured material breach

- **Source:** `page 3 / ماده ۹ بند ۲`

> نقض مواد اساسی )عدم تحویل در موعد، نقض رازداری، عدم تحویل سورس، افت کیفیت( به شرط ابالغ کتبی اصالح از سوی زیان‌دیده و عدم رفع ظرف ۷ روز کاری، حق فسخ فوری و مطالبه خسارات را برای او ایجاد می‌کند؛

- **Actors:** Client, Vendor
- **Statement:** Failure to deliver, breach of confidentiality, non-delivery of source or quality collapse gives the injured party a right to immediate termination if not cured within seven business days of written notice.
- **Rules / edge cases:** Cure notice must be written and dated; termination preserves accrued rights.
- **Priority:** P2 · **Depends on:** REQ-CTR-019
- **Acceptance:**
  1. A cure-notice template with a 7-business-day clock exists
  2. Termination record lists the breach category
  3. Post-termination obligations (confidentiality, data return) are executed
- **Label:** CONTRACT · **Status:** Specified

### REQ-CTR-036 — Support SLA: 24h first response, 3 days medium, 24h critical

- **Source:** `page 4 / ماده ۱۲ بند ۱`

> پاسخ اول به درخواست پشتیبانی حداکثر ۲۴ ساعت کاری؛ رفع باگ‌های متوسط حداکثر ۳ روز کاری و باگ‌های بحرانی )توقف سامانه، از دست رفتن داده‌ها( ظرف ۲۴ ساعت؛

- **Actors:** Vendor, Client
- **Statement:** Support requests receive a first response within 24 business hours, medium defects are fixed within 3 business days, and critical defects (outage, data loss) within 24 hours.
- **Rules / edge cases:** Severity classification is recorded on the ticket and drives the SLA clock; outage triggers an incident record.
- **Priority:** P1 · **Depends on:** REQ-CTR-023
- **Acceptance:**
  1. Ticket severity is mandatory at creation
  2. SLA timers are computed in business hours
  3. Breached SLAs are reportable
- **Label:** CONTRACT · **Status:** Specified

### REQ-CTR-037 — Five-year free technical support and training window

- **Source:** `page 4 / ماده ۱۲`

> پشتیبانی و آموزش فنی رایگان از خیرات تحویل نهایی به مدت ۵ سال برای رفع باگ‌ها و خطاهای ناشی از برنامه‌نویسی، بدون هیچ شرط یا هزینه افزوده؛

- **Actors:** Vendor, Client
- **Statement:** Bug fixes and related technical training are free for five years from final delivery, with no additional conditions or fees.
- **Rules / edge cases:** After the free window, annual support continues under a separate agreement (REQ-CTR-038).
- **Priority:** P1 · **Depends on:** —
- **Acceptance:**
  1. Warranty start and end dates are stored
  2. Support tickets within the window carry zero charge
  3. Renewal notice is generated before expiry
- **Label:** CONTRACT · **Status:** Specified

### REQ-CTR-038 — Annual paid support and updates after the free period

- **Source:** `page 4 / ماده ۱۲ بند ۳`

> پس از پایان دوره رایگان، پشتیبانی و به‌روزرسانی ساالنه با قرارداد مستقل و هزینه توافقی ادامه می‌یابد؛

- **Actors:** Client, Vendor
- **Statement:** After the five-year free period, support and updates continue under a separate annual contract at an agreed price.
- **Rules / edge cases:** No automatic renewal; service continuity requires a signed annual agreement.
- **Priority:** P2 · **Depends on:** REQ-CTR-037
- **Acceptance:**
  1. Expiry generates a renewal proposal 60 days ahead
  2. Lapsed support hides SLA guarantees in the ticket UI
  3. Annual price is configurable per contract
- **Label:** CONTRACT · **Status:** Deferred

### REQ-CTR-039 — Profit-share statement: 15% of net profit, monthly revenue and cost reporting

- **Source:** `page 4 / ماده ۱۶`

> مجری علاوه رب غلبم مقرر در ماده ،۲ حقتسم پانزده دصد) ٪۱۵( از سود صلاخ کسب‌وکار موضوع نیا قرارداد تسا و در لباقم، پانزده دصد) ٪۱۵( از هزینه‌های آینده هعسوت، نگهداری و تخاسریز هناماس) سرور، هنماد، تبلیغات و افزودن قابلیت‌های دیدج( را زین لبقتم می‌شود.

- **Actors:** Client, Vendor
- **Statement:** The platform produces a monthly statement of gross sales revenue, cost of goods, gateway fees, shipping cost and tax, from which net profit and the vendor's 15% share are derived.
- **Rules / edge cases:** Share is payable within 15 days of quarter end; client supplies written revenue and cost figures monthly.
- **Priority:** P2 · **Depends on:** REQ-RPT-002
- **Acceptance:**
  1. Statement breaks out revenue, COGS, gateway fees, shipping, tax
  2. Net profit and 15% share are computed and stored per period
  3. Report is exportable for the quarterly settlement
- **Label:** CONTRACT · **Status:** Specified

### REQ-CTR-040 — Technical SEO: meta, sitemap, clean URLs, structured data, load speed

- **Source:** `page 10 / پیوست ۹ بند ۱`

> سئوی فنی: ساختار استاندارد متا )description ،title)، نقشه سایت، URL تمیز، داده ساخت‌یافته و سرعت بارگذاری بهینه؛

- **Actors:** Visitor, Admin
- **Statement:** Public pages emit correct title/description meta, a generated sitemap, clean URLs, JSON-LD structured data and optimised load speed.
- **Rules / edge cases:** Product pages emit Product+Offer schema with price and availability; canonical tags on filtered listings; robots.txt excludes panels.
- **Priority:** P1 · **Depends on:** REQ-CAT-008
- **Acceptance:**
  1. Every public page has unique title and description
  2. sitemap.xml includes all published products, categories and articles
  3. Product pages pass Google's structured-data test
- **Label:** CONTRACT · **Status:** Specified

### REQ-CTR-041 — Performance target of grade B/A on GTmetrix-class tools

- **Source:** `page 10 / پیوست ۹ بند ۲`

> بهینه‌سازی سرعت: دریافت نمره قابل قبول در GTmetrix و ابزارهای مشابه )هدف: نمره B/A در بخش‌های اصلی(؛

- **Actors:** Engineering
- **Statement:** Home, category and product pages reach grade B or A on GTmetrix (or equivalent) for the main performance sections.
- **Rules / edge cases:** Targets measured against production build, not dev; image compression, asset bundling and lazy loading are required means.
- **Priority:** P1 · **Depends on:** REQ-CTR-040
- **Acceptance:**
  1. Lighthouse performance ≥ 80 on mobile for home/category/product
  2. Images are served responsive and lazy-loaded below the fold
  3. Measurement report is attached to acceptance
- **Label:** CONTRACT · **Status:** Specified

### REQ-CTR-042 — Anti-abuse controls: CAPTCHA, brute-force and spam protection, role access, input validation

- **Source:** `page 10 / پیوست ۹ بند ۳`

> امنیت: نصب SSL، جلوگیری از حمالت Force-Brute و اسپم با کپچا، کنترل دسترسی نقش‌ها و اعتبارسنجی ورودی‌ها؛

- **Actors:** Visitor, Admin
- **Statement:** Public forms are CAPTCHA-protected, authentication is throttled, role permissions are enforced server-side and all input is validated.
- **Rules / edge cases:** CAPTCHA on registration, contact, complaint and comment forms; validation errors returned in Persian; permission checks in policies not views.
- **Priority:** P0 · **Depends on:** REQ-CTR-027, REQ-IAM-004
- **Acceptance:**
  1. A bot without CAPTCHA token cannot submit registration or contact forms
  2. Six failed logins in a minute lock the account/IP temporarily
  3. Invalid input is rejected with field-level Persian messages
- **Label:** CONTRACT · **Status:** Specified

### REQ-CTR-043 — Initial content import for base items from client data

- **Source:** `page 10 / پیوست ۹ بند ۴`

> محتواگذاری اولیه )Import Data )برای اقالم پایه )دسته‌ها، برندها، محصوالت نمونه( طبق داده‌های ارسالی کارفرما؛

- **Actors:** Vendor, Client
- **Statement:** Categories, brands and sample products supplied by the client are imported into the live catalogue through a repeatable import process.
- **Rules / edge cases:** Import is idempotent and validated; duplicate slugs/codes are reported, not silently overwritten.
- **Priority:** P1 · **Depends on:** REQ-CAT-003
- **Acceptance:**
  1. Import runs from a documented file format and can be re-run safely
  2. A report lists created/updated/skipped rows
  3. Mandatory fields are validated before commit
- **Label:** CONTRACT · **Status:** Specified

### REQ-CTR-044 — Branding services delivered across UX/UI and both panels

- **Source:** `page 10 / پیوست ۹ بند ۵`

> خدمات برندسازی: پیاده‌سازی UX/UI در تمامی مراحل، ارائه نکات هویت بصری، تجربه پنل کاربری و مدیریت، آزمایش و نمایش کلیه مراحل و امکانات؛

- **Actors:** Design, Client
- **Statement:** A visual identity guide is delivered and applied consistently to storefront, user panel and admin panel, with each stage demonstrated to the client.
- **Rules / edge cases:** Includes logo usage, palette, typography, icon set and component states.
- **Priority:** P2 · **Depends on:** REQ-CTR-021
- **Acceptance:**
  1. Identity guide documents palette, type scale and components
  2. Storefront and panels use the same tokens
  3. Each design stage is demonstrated and signed off
- **Label:** CONTRACT · **Status:** Specified

### REQ-CTR-045 — Training the client team on content, products, orders and settings

- **Source:** `page 10 / پیوست ۹ بند ۶`

> آموزش تیم کارفرما برای مدیریت محتوا، محصوالت، سفارش‌ها و تنظیمات سامانه.

- **Actors:** Client staff, Vendor
- **Statement:** Client staff are trained to manage content, products, orders and system settings without vendor assistance.
- **Rules / edge cases:** Training material is Persian and role-specific (admin, content editor, support).
- **Priority:** P1 · **Depends on:** REQ-CTR-048
- **Acceptance:**
  1. Training agenda covers the four named domains
  2. Each trainee completes a practical exercise
  3. Recordings are handed over
- **Label:** CONTRACT · **Status:** Specified

### REQ-CTR-046 — Staging deployment on client server for acceptance testing

- **Source:** `page 10 / پیوست ۱۰ بند ۱`

> تحویل نسخه آزمایشی روی سرور کارفرما برای آزمون پذیرش؛

- **Actors:** Vendor, Client
- **Statement:** A pilot build is deployed to the client's server for acceptance testing before final delivery.
- **Rules / edge cases:** Staging uses separate database and gateway sandbox credentials; seeded with representative data.
- **Priority:** P0 · **Depends on:** REQ-CTR-003
- **Acceptance:**
  1. Pilot environment is reachable on the client server
  2. It is isolated from production data and live payments
  3. Access credentials are issued to named client testers
- **Label:** CONTRACT · **Status:** Specified

### REQ-CTR-047 — Acceptance scenarios executed by the client with defect logging in three business days

- **Source:** `page 10 / پیوست ۱۰ بند ۲`

> اجرای سناریوهای آزمون پذیرش توسط کارفرما و ثبت ایرادها ظرف ۳ روز کاری؛

- **Actors:** Client, Vendor
- **Statement:** The client runs the acceptance scenarios and records defects within three business days of pilot handover.
- **Rules / edge cases:** Defects are classified by severity; the run is closed with a signed acceptance report.
- **Priority:** P0 · **Depends on:** REQ-CTR-046
- **Acceptance:**
  1. A written acceptance scenario list exists before the pilot starts
  2. Defects are logged with steps, evidence and severity
  3. The report is signed or rejected with reasons
- **Label:** CONTRACT · **Status:** Specified

### REQ-CTR-048 — Defect remediation and retest until acceptance

- **Source:** `page 10 / پیوست ۱۰ بند ۳`

> رفع ایرادها توسط مجری و آزمون مجدد؛

- **Actors:** Vendor, Client
- **Statement:** Logged acceptance defects are fixed and the affected scenarios retested until the client accepts.
- **Rules / edge cases:** Each defect gets a resolution note and a retest result; regressions reopen the defect.
- **Priority:** P0 · **Depends on:** REQ-CTR-047
- **Acceptance:**
  1. Every logged defect has a resolution and retest outcome
  2. Closed defects cannot be reopened silently — a new defect row is created
  3. A final zero-open-defects state is recorded
- **Label:** CONTRACT · **Status:** Specified

### REQ-CTR-049 — Final delivery only after written client approval

- **Source:** `page 10 / پیوست ۱۰ بند ۴`

> تحویل نهایی پس از نامه رضایت کتبی کارفرما؛

- **Actors:** Client, Vendor
- **Statement:** Final delivery, and the start of the warranty clock, occur after the client's written satisfaction letter.
- **Rules / edge cases:** The letter references the acceptance report; delivery date drives the five-year warranty (REQ-CTR-037).
- **Priority:** P0 · **Depends on:** REQ-CTR-048
- **Acceptance:**
  1. Approval letter is archived and dated
  2. Warranty start equals the letter date
  3. Final handover checklist is complete
- **Label:** CONTRACT · **Status:** Specified

### REQ-CTR-050 — Training programme: four in-person and twenty-five online sessions

- **Source:** `page 10 / پیوست ۱۰ بند ۵`

> آموزش ۴ جلسه حضوری و ۲۵ جلسه آنالین برای ادمین‌ها و کاربران کلیدی؛

- **Actors:** Vendor, Client staff
- **Statement:** Four in-person and twenty-five online sessions are delivered to admins and key users, with attendance and materials recorded.
- **Rules / edge cases:** Sessions are role-based; recordings are handed over for reuse.
- **Priority:** P1 · **Depends on:** REQ-CTR-045
- **Acceptance:**
  1. 29 sessions are scheduled and attended
  2. Attendance sheet exists per session
  3. Recordings and slides are in the handover package
- **Label:** CONTRACT · **Status:** Specified

### REQ-CTR-051 — Handover package: technical and user docs, training videos, source bundle

- **Source:** `page 10 / پیوست ۱۰ بند ۶`

> تحویل مستندات فنی و کاربری، ویدئوهای آموزشی و بسته سورس.

- **Actors:** Vendor, Client
- **Statement:** The final package contains technical documentation, a Persian user manual, training videos and the source bundle.
- **Rules / edge cases:** Versioned and checksummed; stored in a client-controlled repository.
- **Priority:** P1 · **Depends on:** REQ-CTR-002, REQ-CTR-050
- **Acceptance:**
  1. Package inventory lists every artifact with version
  2. User manual covers each role's main journeys
  3. Source bundle builds from a clean checkout
- **Label:** CONTRACT · **Status:** Specified

### REQ-CTR-052 — Native Android app with full customer feature parity (APK)

- **Source:** `page 9 / پیوست ۸ بند ۱`

> اپلیکیشن اندروید کامل با تمام قابلیت‌های سایت برای مشتری؛ تحویل فایل APK قابل نصب مستقیم؛

- **Actors:** Consumer
- **Statement:** An Android app delivering all customer-facing site capabilities is built and an installable APK is delivered.
- **Rules / edge cases:** Deferred in this mission (web-only scope, RB-MOB-01); contractually still owed — see REQ-CTR-053/054 for the web equivalents.
- **Priority:** P2 · **Depends on:** REQ-CTR-058
- **Acceptance:**
  1. APK installs on a clean Android device
  2. Customer journeys match the web feature set
  3. Build is reproducible from the source bundle
- **Label:** CONTRACT · **Status:** Deferred

### REQ-CTR-053 — iOS app as native build or installable PWA

- **Source:** `page 9 / پیوست ۸ بند ۲`

> اپلیکیشن iOS( در قالب نسخه بومی یا PWA نصب‌شدنی( برای آیفون و آیپد؛

- **Actors:** Consumer
- **Statement:** iOS users get either a native build or the installable PWA on iPhone and iPad, with equivalent customer features.
- **Rules / edge cases:** The PWA route satisfies this clause without a native binary (REQ-CTR-008).
- **Priority:** P2 · **Depends on:** REQ-CTR-008
- **Acceptance:**
  1. App installs on iOS from Safari
  2. Same journeys as Android/web
  3. Splash and icon render correctly
- **Label:** CONTRACT · **Status:** Deferred

### REQ-CTR-054 — PWA with push notifications for both operating systems

- **Source:** `page 9 / پیوست ۸ بند ۳`

> نسخه PWA برای نصب آسان از مرورگر در هر دو سیستم‌عامل، با نوتیفیکیشن Push؛

- **Actors:** Consumer
- **Statement:** The installable PWA receives push notifications on Android and (where supported) iOS.
- **Rules / edge cases:** Push tied to order status and admin campaigns (REQ-NTF-001); opt-in consent stored.
- **Priority:** P1 · **Depends on:** REQ-CTR-008, REQ-NTF-001
- **Acceptance:**
  1. Installed PWA can receive a test push
  2. Opt-out stops delivery
  3. Notification click opens the relevant page
- **Label:** CONTRACT · **Status:** Specified

### REQ-CTR-055 — Full data synchronisation between web and app surfaces

- **Source:** `page 9 / پیوست ۸ بند ۴`

> سینک کامل داده‌ها با نسخه وب )منو، سفارش، امتیاز، کیف پول(؛

- **Actors:** Consumer
- **Statement:** Menu, orders, points and wallet are identical across web and app because both read the same API.
- **Rules / edge cases:** No separate app database; single source of truth with cache invalidation.
- **Priority:** P1 · **Depends on:** REQ-CTR-054
- **Acceptance:**
  1. App and web show identical order/points/wallet values after a mutation
  2. Logout clears cached personal data
  3. Offline state does not show stale balances as current
- **Label:** CONTRACT · **Status:** Specified

### REQ-CTR-056 — App identity: splash, icon, login and home screens; build and store package

- **Source:** `page 10 / پیوست ۸ بند ۵`

> طراحی اختصاصی اپ شامل اسپلش، آیکون، صفحه ورود و صفحه اصلی؛

- **Also satisfies:** `پیوست ۸ بند ۶`
- **Actors:** Design, Vendor
- **Statement:** App assets (splash, icon, login, home) are bespoke and delivered together with source, keys and publishing instructions for store submission.
- **Rules / edge cases:** Store accounts and release approval remain the client's responsibility (REQ-CTR-057).
- **Priority:** P2 · **Depends on:** REQ-CTR-021
- **Acceptance:**
  1. All icon sizes and splash variants exist
  2. Publishing guide covers both stores
  3. Signing keys are delivered to the client
- **Label:** CONTRACT · **Status:** Deferred

### REQ-CTR-057 — Store submission readiness, client-owned developer accounts

- **Source:** `page 10 / پیوست ۸ بند ۷`

> آماده‌سازی نسخه برای انتشار در پلی‌استور و اپ‌استور )هزینه حساب‌های توسعه‌دهنده و تأیید انتشار بر عهده کارفرماست(؛

- **Actors:** Vendor, Client
- **Statement:** Release builds are prepared for Play Store and App Store; developer accounts and release approval are supplied by the client.
- **Rules / edge cases:** Requires app privacy declarations, screenshots and version metadata.
- **Priority:** P2 · **Depends on:** REQ-CTR-056
- **Acceptance:**
  1. Release bundle and metadata are prepared
  2. Client confirms account ownership
  3. Rejection reasons are tracked to resolution
- **Label:** CONTRACT · **Status:** Deferred

### REQ-CTR-058 — App updates delivered through later versions under the support contract

- **Source:** `page 10 / پیوست ۸ بند ۸`

> به‌روزرسانی اپ از طریق نسخه‌های بعدی مطابق قرارداد پشتیبانی.

- **Actors:** Vendor
- **Statement:** App versions are updated under the support contract, with backward-compatible APIs.
- **Rules / edge cases:** Update distribution follows store rules; forced-update screen for breaking API changes.
- **Priority:** P2 · **Depends on:** REQ-CTR-037
- **Acceptance:**
  1. Version check endpoint reports minimum supported version
  2. Forced update screen blocks unsupported clients
  3. Release notes accompany each version
- **Label:** CONTRACT · **Status:** Deferred

### REQ-CTR-059 — Reservation/appointment module explicitly excluded from scope

- **Source:** `page 9 / پیوست ۵ (توجه)`

> توجه: ماژول نوبت‌دهی/رزرواسیون موضوع این قرارداد نیست و مطابق قرارداد شماره ۲ )مبلغ ۳۵٬۰۰۰٬۰۰۰ تومان، مدت ۱ ماه( به‌صورت مستقل طراحی و پیاده‌سازی می‌شود.

- **Actors:** Client, Vendor
- **Statement:** No appointment/reservation feature is built under this contract; any single sign-on integration with that separate system requires an addendum.
- **Rules / edge cases:** Boundary is enforced in the scope document and in navigation; no reservation menu items exist.
- **Priority:** P1 · **Depends on:** REQ-CTR-033
- **Acceptance:**
  1. Scope boundary document lists the exclusion
  2. No reservation routes or tables exist in the codebase
  3. Any SSO work is gated behind an addendum
- **Label:** CONTRACT · **Status:** Specified

### REQ-CTR-060 — Whole product runs as a web-based app on Android, iOS and Windows

- **Source:** `page 8 / پیوست ۵ بند ۲۵`

> کل نرم‌افزار باید به‌صورت یک اپلیکیشن برپایه وب روی کلیه گوشی‌های اندروید و iOS و ویندوز قابل اجرا باشد؛

- **Actors:** All users
- **Statement:** The entire product is usable from a browser on Android, iOS and Windows without installing a native binary.
- **Rules / edge cases:** Contradicts پیوست ۸ بند ۱ (native APK) — resolved in favour of the web-based route for this mission (AMB-CTR-003).
- **Priority:** P0 · **Depends on:** REQ-CTR-007
- **Acceptance:**
  1. All roles complete their core journey in a mobile browser
  2. Windows desktop browsers work without plugins
  3. No feature requires a native-only API
- **Label:** CONTRACT · **Status:** Specified

<a id="iam"></a>
## IAM — Identity and access

Identity and access: registration, login, recovery, five user levels, secondary level, 2FA, scoped authorization.

### REQ-IAM-001 — Self-registration with mobile number verified by SMS code

- **Source:** `page 5 / پیوست ۲ بند ۸`

> صفحات ورود، ثبت‌نام با تأیید پیامک، بازیابی رمز عبور و ویرایش اطالعات کاربری؛

- **Actors:** Visitor
- **Statement:** A visitor registers with mobile number, name and password, and the account becomes active only after the SMS code is verified.
- **Rules / edge cases:** Code validity 2 minutes, max 5 attempts, resend throttled; duplicate mobile rejected with a Persian message; unverified accounts expire.
- **Priority:** P0 · **Depends on:** REQ-NTF-004
- **Acceptance:**
  1. Registration without a valid code creates no active account
  2. Duplicate mobile number is refused with a clear message
  3. Expired code cannot be reused
- **Label:** CONTRACT · **Status:** Specified

### REQ-IAM-002 — Login with mobile number and password

- **Source:** `page 5 / پیوست ۲ بند ۸`

> صفحات ورود، ثبت‌نام با تأیید پیامک، بازیابی رمز عبور و ویرایش اطالعات کاربری؛

- **Actors:** All registered users
- **Statement:** A registered user signs in with mobile number and password and lands on the panel for their level.
- **Rules / edge cases:** Throttled after repeated failures; inactive accounts are refused with the reason; blocklisted users cannot sign in.
- **Priority:** P0 · **Depends on:** REQ-IAM-001
- **Acceptance:**
  1. Valid credentials open the correct panel
  2. Wrong password increments the throttle counter
  3. Deactivated account sees an explicit block message
- **Label:** CONTRACT · **Status:** Specified

### REQ-IAM-003 — Password recovery through an SMS one-time code

- **Source:** `page 5 / پیوست ۲ بند ۸`

> صفحات ورود، ثبت‌نام با تأیید پیامک، بازیابی رمز عبور و ویرایش اطالعات کاربری؛

- **Actors:** Registered user
- **Statement:** A user who forgot the password requests an SMS code, verifies it and sets a new password.
- **Rules / edge cases:** Code single-use; the new password must meet the policy; all other sessions are invalidated after reset.
- **Priority:** P0 · **Depends on:** REQ-IAM-001
- **Acceptance:**
  1. Reset completes only with a valid code
  2. Old password stops working immediately
  3. Reset event is written to the audit log
- **Label:** CONTRACT · **Status:** Specified

### REQ-IAM-004 — Five independent user levels with separated permissions

- **Source:** `page 5 / پیوست ۳`

> سامانه دارای پنج سطح کاربری مستقل با دسترسی‌های تفکیک‌شده است:

- **Actors:** Admin, all users
- **Statement:** The system defines five levels — store (goods provider), marketer, consumer, supplier/producer, admin — each with a distinct permission set enforced server-side.
- **Rules / edge cases:** Implemented as many-to-many roles plus policies; permissions are data, not code branches; admin is not a marketplace level.
- **Priority:** P0 · **Depends on:** REQ-CTR-012
- **Acceptance:**
  1. Each level sees only its own navigation and authorised routes
  2. A direct URL to a foreign level's resource returns 403
  3. Permission matrix is readable in admin
- **Label:** CONTRACT · **Status:** Specified

### REQ-IAM-005 — Optional secondary level granted by admin

- **Source:** `page 9 / پیوست ۷ بند ۶`

> هر سطح کاربری می‌تواند یک سطح ثانویه داشته باشد )مث لاً سطح فروشگاه نتواند بازاریاب باشد — مطابق تنظیم ادمین(؛

- **Actors:** Admin, user
- **Statement:** An admin may grant a user one secondary level, and may forbid specific combinations; the user then holds the union of allowed capabilities.
- **Rules / edge cases:** The clause's own example forbids store+marketer while پیوست ۱۲ بند ۱۱ requires marketer+consumer — combinations are configuration, default deny (AMB-IAM-001).
- **Priority:** P1 · **Depends on:** REQ-IAM-004
- **Acceptance:**
  1. Admin can grant and revoke a secondary level with an audit entry
  2. A forbidden combination cannot be saved
  3. Switching active context changes navigation and scope
- **Label:** CONTRACT · **Status:** Ambiguous

### REQ-IAM-006 — Store-level capabilities enforced as a permission set

- **Source:** `page 5 / پیوست ۳-۱`

> فروشگاه )ارائه‌دهنده کاال(

- **Actors:** Store user
- **Statement:** A store account can publish content and ads, manage its cart basket, view its orders, message users and stores, request settlement, view statements, register store info, place ads, appear in Patogh, join tenders and auctions, place orders, open tickets, view invoices, see wholesale prices, report violations, request samples and keep a favourites box.
- **Rules / edge cases:** Capabilities 1-10 of ۳-۱ are individually enforceable; ordering at wholesale price requires an approved store status.
- **Priority:** P0 · **Depends on:** REQ-IAM-004
- **Acceptance:**
  1. Each of the ten capabilities is reachable for an active store
  2. A suspended store loses purchasing and publishing rights
  3. Negative tests exist per scoped resource
- **Label:** CONTRACT · **Status:** Specified

### REQ-IAM-007 — Marketer-level capabilities enforced as a permission set

- **Source:** `page 6 / پیوست ۳-۲`

> بازاریاب

- **Also satisfies:** `پیوست ۳-۲ بند ۶`
- **Actors:** Marketer
- **Statement:** A marketer can ticket users and admin, request settlement and view statements, register stores, use a personal code/link/QR, chat with authorised users, browse stores and Patogh, file complaints, register customer and project orders, see stores activated by admin, and view invoices of stores they visited.
- **Rules / edge cases:** Scope limited to own network: only stores attributed to the marketer (REQ-IAM-015).
- **Priority:** P0 · **Depends on:** REQ-IAM-004
- **Acceptance:**
  1. A marketer cannot read another marketer's stores
  2. Own-network invoice list matches attribution data
  3. Commission-related actions require an active plan
- **Label:** CONTRACT · **Status:** Specified

### REQ-IAM-008 — Consumer-level capabilities enforced as a permission set

- **Source:** `page 6 / پیوست ۳-۳`

> مصرف‌کننده / مشتری

- **Also satisfies:** `پیوست ۳-۳ بند ۱`
- **Actors:** Consumer
- **Statement:** A consumer can register and use the full panel, keep a cart, message users and stores, view past purchases, add stores or products to favourites, edit profile and view recent activity, submit a tender or order through a form, read purchase rules and track order status.
- **Rules / edge cases:** Consumers see consumer prices only; wholesale price lists are never exposed.
- **Priority:** P0 · **Depends on:** REQ-IAM-004
- **Acceptance:**
  1. Consumer never sees colleague/wholesale prices
  2. All six capabilities reachable from the panel
  3. Order tracking works with the tracking code
- **Label:** CONTRACT · **Status:** Specified

### REQ-IAM-009 — Supplier/producer level gets an independent full panel

- **Source:** `page 6 / پیوست ۳-۴`

> پنل مستقل و کامل مطابق پیوست ۵ که امکان مدیریت محصول، قیمت، موجودی، سفارش‌ها و گزارش‌های فروش را دارد.

- **Actors:** Supplier
- **Statement:** A supplier signs into an independent panel that manages its products, prices, stock, orders and sales reports.
- **Rules / edge cases:** The cross-reference to پیوست ۵ is wrong — the supplier panel is specified in پیوست ۱۱ (AMB-MER-001).
- **Priority:** P0 · **Depends on:** REQ-MER-019
- **Acceptance:**
  1. Supplier panel is separate from the store panel
  2. Product, price, stock, order and report screens exist
  3. Supplier sees only its own catalogue
- **Label:** CONTRACT · **Status:** Specified

### REQ-IAM-010 — Admin level has full access, user approval and supervision

- **Source:** `page 6 / پیوست ۳-۵`

> مطابق پیوست ۷؛ دسترسی کامل به همه بخش‌ها، تأیید کاربران و نظارت بر عملکرد سایر سطوح.

- **Also satisfies:** `پیوست ۳-۵ بند ۱`
- **Actors:** Admin
- **Statement:** An admin can reach every module, approve users and their information, and supervise the activity of all other levels.
- **Rules / edge cases:** Every admin action on another user is audited (REQ-CTR-014); admin panel requires 2FA (REQ-CTR-011).
- **Priority:** P0 · **Depends on:** REQ-CTR-011
- **Acceptance:**
  1. Admin navigation covers all modules
  2. Approval queue exists for pending users and profile changes
  3. Supervision views show per-level activity
- **Label:** CONTRACT · **Status:** Specified

### REQ-IAM-011 — Forced password change on first login for accounts created by staff

- **Source:** `page 7 / پیوست ۵ بند ۲`

> ثبت‌نام فروشگاه توسط بازاریاب در فرمی مجزا از فرم ثبتنام عمومی؛ ارسال نام کاربری و رمز عبور پس از ثبت و الزام به تغییر رمز در ورود اول؛

- **Actors:** Marketer-created store, Supplier-created user
- **Statement:** Accounts provisioned by a marketer or admin receive a generated username/password and must set a new password at first sign-in before any other action.
- **Rules / edge cases:** All routes except password change and logout are blocked until changed; the generated password expires in 7 days.
- **Priority:** P1 · **Depends on:** REQ-MER-008
- **Acceptance:**
  1. First login redirects to the password change screen
  2. Direct navigation elsewhere is blocked
  3. Completed change is audited
- **Label:** CONTRACT · **Status:** Specified

### REQ-IAM-012 — Two-factor authentication for every admin account

- **Source:** `page 5 / پیوست ۱ بند ۷`

> پنل مدیریت مجزا روی دامنه/مسیر اختصاصی با احراز هویت دو مرحله‌ای برای نقش ادمین؛

- **Actors:** Admin
- **Statement:** Admin sign-in requires a second factor (TOTP or SMS) in addition to the password, with recovery codes issued at enrolment.
- **Rules / edge cases:** Enforced, not optional; failed second factor throttled; enrolment is mandatory before first admin session.
- **Priority:** P0 · **Depends on:** REQ-CTR-011
- **Acceptance:**
  1. Password-only admin login cannot reach the panel
  2. Recovery codes are single-use
  3. 2FA enrolment state is visible per admin
- **Label:** CONTRACT · **Status:** Specified

### REQ-IAM-013 — Membership rights can be sold or transferred to another person with admin approval

- **Source:** `page 8 / پیوست ۵ بند ۳۹`

> امکان فروش یا واگذاری حق عضویت به شخص دیگر با گزینه ادیت و تأیید ادمین؛

- **Actors:** Store/consumer, Admin
- **Statement:** A user can request transfer of their membership to another person; the recipient's data is editable and the transfer takes effect only after admin approval.
- **Rules / edge cases:** Financial balances and open orders must be settled before transfer; history stays with the original entity for audit.
- **Priority:** P2 · **Depends on:** REQ-IAM-004, REQ-FIN-001
- **Acceptance:**
  1. Transfer request captures recipient identity data
  2. Account access switches only after approval
  3. Open orders/balances block the transfer until resolved
- **Label:** CONTRACT · **Status:** Specified

### REQ-IAM-014 — Profile changes that affect identity require SMS confirmation

- **Source:** `page 6 / پیوست ۳-۱ بند ۱۰`

> داشتن صندوق عالقهمندی‌ها و صفحه اطالعات کاربری با تغییر رمز و مشخصات با تأیید پیامک.

- **Actors:** All users
- **Statement:** Changes to password or identifying profile fields require an SMS code confirmation before they are saved.
- **Rules / edge cases:** Mobile-number change requires confirming both old and new numbers; the change is audited.
- **Priority:** P1 · **Depends on:** REQ-IAM-001
- **Acceptance:**
  1. Saving a new mobile number without a code is rejected
  2. Both numbers receive a confirmation step
  3. Change appears in the audit log
- **Label:** CONTRACT · **Status:** Specified

### REQ-IAM-015 — Server-side data scoping: users only reach their own records

- **Source:** `page 8 / پیوست ۵ بند ۲۹`

> امکان ثبت اطالعات و گزارش‌گیری از فروشگاه‌ها و مشتریان ویزیت‌شده با تأیید ادمین و دسترسی محدود بازاریاب به اطالعات فروشگاه‌های مربوط به خود؛

- **Actors:** Marketer, Store, Consumer
- **Statement:** Every scoped resource is filtered by ownership or attribution server-side, so a marketer sees only their own stores and a store only its own catalogue and orders.
- **Rules / edge cases:** Policies plus query scopes, never view-level hiding; admin override is audited.
- **Priority:** P0 · **Depends on:** REQ-IAM-004
- **Acceptance:**
  1. Changing an ID in a URL returns 403 or 404, never foreign data
  2. Negative authorization tests exist for every scoped model
  3. Admin override writes an audit entry
- **Label:** CONTRACT · **Status:** Specified

<a id="usr"></a>
## USR — Users and profiles

User profile, addresses, favourites, activity history, public profile and stars.

### REQ-USR-001 — User panel home with orders, addresses, interests, wallet and points

- **Source:** `page 5 / پیوست ۲ بند ۷`

> صفحه پنل کاربری شامل سفارش‌ها، آدرس‌ها، عالقهمندی‌ها، کیف پول و امتیازات؛

- **Actors:** All logged-in users
- **Statement:** The panel home shows five blocks — orders, addresses, favourites, wallet balance and points balance — each linking to its detail screen.
- **Rules / edge cases:** Blocks adapt to the level: suppliers see catalogue/stock shortcuts; counts are live, not cached for more than a minute.
- **Priority:** P0 · **Depends on:** REQ-IAM-004
- **Acceptance:**
  1. All five blocks render for a consumer with data
  2. Each block links to a working detail page
  3. A user with no data sees empty states, not errors
- **Label:** CONTRACT · **Status:** Specified

### REQ-USR-002 — Address book with add, edit, delete and default address

- **Source:** `page 5 / پیوست ۲ بند ۷`

> صفحه پنل کاربری شامل سفارش‌ها، آدرس‌ها، عالقهمندی‌ها، کیف پول و امتیازات؛

- **Actors:** Consumer, Store
- **Statement:** A user maintains multiple delivery addresses (province, city, address text, postal code, phone, map pin) and marks one as default for checkout.
- **Rules / edge cases:** Deleting an address used by an open order is blocked; map pin optional but required for installation services.
- **Priority:** P1 · **Depends on:** REQ-CTR-007
- **Acceptance:**
  1. CRUD works and persists
  2. Checkout preselects the default address
  3. Deleting an address referenced by an open order is refused
- **Label:** CONTRACT · **Status:** Specified

### REQ-USR-003 — Favourites box for products, stores and producers

- **Source:** `page 6 / پیوست ۳-۳ بند ۳`

> افزودن فروشگاه یا محصول به عالقهمندی‌ها؛

- **Also satisfies:** `پیوست ۱۲ بند ۱۵`
- **Actors:** Consumer, Store
- **Statement:** A user adds or removes products, stores and producers from a favourites list that persists across sessions and levels.
- **Rules / edge cases:** Duplicates prevented by a unique (user, favouritable) key; list paginated; removal immediate.
- **Priority:** P1 · **Depends on:** REQ-USR-001
- **Acceptance:**
  1. Add/remove works from product and store pages
  2. Favourites survive logout and device change
  3. Adding the same item twice does not duplicate
- **Label:** CONTRACT · **Status:** Specified

### REQ-USR-004 — Editable user profile page

- **Source:** `page 6 / پیوست ۳-۳ بند ۴`

> تغییر مشخصات کاربری، مشاهده آخرین فعالیت‌ها؛

- **Actors:** All users
- **Statement:** A user edits profile fields (name, mobile, email, photo, city) and sees the last activity entries on their account.
- **Rules / edge cases:** Identity-affecting fields require SMS confirmation (REQ-IAM-014); email change requires a verification link.
- **Priority:** P1 · **Depends on:** REQ-IAM-014
- **Acceptance:**
  1. Editable fields save and reload correctly
  2. Mobile change without a code is refused
  3. Activity list shows at least login and order events
- **Label:** CONTRACT · **Status:** Specified

### REQ-USR-005 — Recent activity view in the user panel

- **Source:** `page 6 / پیوست ۳-۳ بند ۴`

> تغییر مشخصات کاربری، مشاهده آخرین فعالیت‌ها؛

- **Actors:** All users
- **Statement:** The panel lists the user's recent activity: logins, orders, invoices, points changes and settlement requests, newest first.
- **Rules / edge cases:** Twelve-month retention in the panel; older history available through support export.
- **Priority:** P2 · **Depends on:** REQ-CTR-014
- **Acceptance:**
  1. Activity list is chronological and paginated
  2. Only the owner's events appear
  3. Point changes show delta and reason
- **Label:** CONTRACT · **Status:** Specified

### REQ-USR-006 — Purchase history with order and invoice drill-down

- **Source:** `page 6 / پیوست ۳-۳ بند ۲`

> سبد خرید، ارسال پیام به کاربران و فروشگاه‌ها و مشاهده خریدهای انجام‌شده؛

- **Actors:** Consumer, Store
- **Statement:** A user views past purchases grouped by order and drills into each order's lines, invoice and status history.
- **Rules / edge cases:** Own purchases only; paid, unpaid and returned states are distinguishable.
- **Priority:** P1 · **Depends on:** REQ-ORD-001
- **Acceptance:**
  1. History matches the user's orders exactly
  2. Drill-down shows invoice and status timeline
  3. Another user's order is inaccessible
- **Label:** CONTRACT · **Status:** Specified

### REQ-USR-007 — Professional profile fields editable only with admin approval

- **Source:** `page 7 / پیوست ۵ بند ۷`

> اضافه کردن امکان درج آدرس سایت، شبکه‌های اجتماعی، گالری تصاویر، نمایش امتیاز، ساعات کاری و انتخاب حوزه کاری در پروفایل کاربر فقط با تأیید ادمین؛

- **Actors:** Store, Supplier, Marketer
- **Statement:** Users may submit site address, social links, image gallery, rating visibility, working hours and business domain; the changes go live only after admin approval.
- **Rules / edge cases:** Pending changes are stored as a diff against the live profile so the public page never shows unapproved content.
- **Priority:** P1 · **Depends on:** REQ-USR-008
- **Acceptance:**
  1. Submitted change is invisible publicly until approved
  2. Admin sees a before/after diff
  3. Rejection keeps the previous values live
- **Label:** CONTRACT · **Status:** Specified

### REQ-USR-008 — Public profile shows phone, address, level, username and points

- **Source:** `page 7 / پیوست ۵ بند ۸`

> نمایش شماره تلفن، آدرس متنی، سطح کاربری، نام کاربری و امتیاز کاربر در پروفایل عمومی و در صفحات مرتبط )با تأیید ادمین(؛

- **Actors:** Visitor, all users
- **Statement:** A public profile page shows the approved phone, text address, user level, username and points, and each field's visibility is admin-controlled.
- **Rules / edge cases:** Hidden fields are omitted from the HTML response, not merely visually hidden; points shown as a rounded figure.
- **Priority:** P1 · **Depends on:** REQ-USR-007
- **Acceptance:**
  1. Public page renders the five fields when approved
  2. A field switched off by admin is absent from the response
  3. Profile is reachable from product and classified pages
- **Label:** CONTRACT · **Status:** Specified

### REQ-USR-009 — Profile photo captured or uploaded by the customer or the marketer

- **Source:** `page 8 / پیوست ۵ بند ۳۱`

> قابلیت گرفتن عکس و افزودن به فرم ثبت‌نام مشتریان توسط خود مشتری و بازاریاب، همراه با امکان ویرایش اطالعات در پروفایل؛

- **Actors:** Consumer, Marketer
- **Statement:** During registration or later from the profile, a photo can be taken with the device camera or uploaded, and the user can edit their information afterwards.
- **Rules / edge cases:** Camera capture via a file input with the capture attribute (no native app needed); images resized and stored privately until approved.
- **Priority:** P2 · **Depends on:** REQ-CTR-007
- **Acceptance:**
  1. Camera capture works on Android and iOS browsers
  2. Uploaded photo appears after save
  3. Replacing the photo keeps only the latest file
- **Label:** CONTRACT · **Status:** Specified

### REQ-USR-010 — Birth date captured for birthday and occasion greetings

- **Source:** `page 11 / پیوست ۱۲ بند ۷`

> ارسال تبریک اعیاد و تولد با ثبت تاریخ تولد مشتری؛

- **Actors:** Consumer, Store
- **Statement:** A user can record a birth date (Jalali) which drives birthday greetings and occasion campaigns, with opt-out.
- **Rules / edge cases:** Stored as a date, not a string; leap-year 30 Esfand handled; one greeting per user per year.
- **Priority:** P2 · **Depends on:** REQ-NTF-001
- **Acceptance:**
  1. Birth date saved in Jalali and converted correctly for storage
  2. A birthday campaign targets only users with a matching date
  3. Opted-out users receive nothing
- **Label:** CONTRACT · **Status:** Specified

### REQ-USR-011 — Star rating displayed on the buyer's profile

- **Source:** `page 7 / پیوست ۴ بند ۹`

> خریداران همکار در ازای هر ۱۰۰ امتیاز خرید، یک ستاره دریافت می‌کنند که در پروفایل آن‌ها نمایش داده می‌شود؛ تعداد ستاره‌ها نامحدود است؛

- **Actors:** Store, Consumer
- **Statement:** Each 100 purchase points earned converts into one star shown on the profile; the star count has no cap.
- **Rules / edge cases:** Stars are derived from lifetime points at a fixed divisor, recalculated on points change, and decrease only through point deductions.
- **Priority:** P1 · **Depends on:** REQ-LOY-001
- **Acceptance:**
  1. 100 points yields 1 star; 250 yields 2
  2. Star count rendered on the public profile
  3. No upper bound is enforced
- **Label:** CONTRACT · **Status:** Specified

### REQ-USR-012 — Report content or a user from any profile page

- **Source:** `page 9 / پیوست ۷ بند ۷`

> گزینه گزارش محتوا یا کاربر در صفحات کاربری؛

- **Actors:** All users, Admin
- **Statement:** Any user can report a profile, product or content item with a reason, and the report enters the admin moderation queue.
- **Rules / edge cases:** Rate-limited per reporter; the report stores a snapshot of the target; duplicates are merged.
- **Priority:** P1 · **Depends on:** REQ-ADM-014
- **Acceptance:**
  1. Report form available on profile and product pages
  2. Report reaches the admin queue with a link to the target
  3. Repeated identical reports are deduplicated
- **Label:** CONTRACT · **Status:** Specified

<a id="mer"></a>
## MER — Merchants and suppliers

Merchant and supplier lifecycle: field onboarding, store data, attribution, activation, panels and brand pages.

### REQ-MER-001 — Store registration by a marketer through a dedicated form

- **Source:** `page 7 / پیوست ۵ بند ۲`

> ثبت‌نام فروشگاه توسط بازاریاب در فرمی مجزا از فرم ثبتنام عمومی؛ ارسال نام کاربری و رمز عبور پس از ثبت و الزام به تغییر رمز در ورود اول؛

- **Also satisfies:** `پیوست ۳-۲ بند ۳`
- **Actors:** Marketer
- **Statement:** A marketer registers a store through a form separate from public sign-up, and the store account is created under that marketer's attribution.
- **Rules / edge cases:** Form is only reachable by an authenticated marketer; public sign-up cannot create a store-level account; attribution is recorded immutably at creation.
- **Priority:** P0 · **Depends on:** REQ-IAM-007
- **Acceptance:**
  1. Store created via the marketer form is attributed to that marketer
  2. Public registration offers no store-level option
  3. Attribution cannot be edited by the marketer
- **Label:** CONTRACT · **Status:** Specified

### REQ-MER-002 — Complete store data capture on the field form

- **Source:** `page 9 / پیوست ۶ بند ۱`

> ثبت‌نام حضوری فروشگاه و مشتری توسط بازاریاب با ثبت کامل اطالعات: آدرس، نام فروشگاه، نام مالک، کدپستی، کدملی، شماره‌های تماس )واتساپ‌دار و بدون واتساپ(، خدمات فروشگاه، نوع دسته و رسته، شهر، استان؛

- **Actors:** Marketer
- **Statement:** The in-person registration form captures address, store name, owner name, postal code, national ID, phone numbers (with and without WhatsApp), store services, category and trade, city and province.
- **Rules / edge cases:** National ID and postal code validated by checksum and format; at least one phone required; province/city cascading select.
- **Priority:** P0 · **Depends on:** REQ-MER-001
- **Acceptance:**
  1. All eleven data groups are mandatory or explicitly optional
  2. Invalid national ID or postal code is rejected with a Persian message
  3. Province selection filters the city list
- **Label:** CONTRACT · **Status:** Specified

### REQ-MER-003 — Store image gallery: owner photo, storefront and business card

- **Source:** `page 9 / پیوست ۶ بند ۲`

> آپلود گالری تصاویر صاحب فروشگاه، سردر فروشگاه و کارت ویزیت؛ ثبت لوکیشن و آدرس گوگل مپ؛

- **Actors:** Marketer
- **Statement:** The marketer uploads three image types per store — owner photo, storefront and business card — each stored as a labelled gallery item.
- **Rules / edge cases:** Images compressed and stored on a private disk until approved; max 5 MB each; camera capture allowed on mobile.
- **Priority:** P1 · **Depends on:** REQ-MER-002
- **Acceptance:**
  1. Three labelled slots exist and accept uploads
  2. Oversized or non-image files are rejected
  3. Images are visible to admin for verification
- **Label:** CONTRACT · **Status:** Specified

### REQ-MER-004 — Store geolocation and Google Maps address recorded at visit

- **Source:** `page 9 / پیوست ۶ بند ۲`

> آپلود گالری تصاویر صاحب فروشگاه، سردر فروشگاه و کارت ویزیت؛ ثبت لوکیشن و آدرس گوگل مپ؛

- **Actors:** Marketer
- **Statement:** The form captures the store's coordinates and a Google Maps address so the store can be located for future visits and referrals.
- **Rules / edge cases:** Coordinate captured from the device with manual override; storefront pin is shown on a map in admin and on the store page.
- **Priority:** P1 · **Depends on:** REQ-MER-003, REQ-GEO-001
- **Acceptance:**
  1. Saving records latitude and longitude
  2. The pin renders on the store map
  3. Manual address correction is possible
- **Label:** CONTRACT · **Status:** Specified

### REQ-MER-005 — Duplicate store prevention by national ID, postal code and mobile

- **Source:** `page 7 / پیوست ۵ بند ۳`

> امکان جستجوی فروشگاه‌های ثبت‌شده بر اساس سه شناسه کدملی، کدپستی و شماره موبایل برای جلوگیری از ثبت تکراری؛

- **Actors:** Marketer, Admin
- **Statement:** Before creating a store, the marketer searches existing stores by national ID, postal code or mobile number and is blocked from creating a duplicate.
- **Rules / edge cases:** Exact-match search across the three identifiers; a match shows the existing store owner and marketer; override only by admin with a recorded reason.
- **Priority:** P0 · **Depends on:** REQ-MER-002
- **Acceptance:**
  1. Searching any of the three identifiers returns matching stores
  2. Saving a store with an existing national ID is refused
  3. Admin override writes an audit entry
- **Label:** CONTRACT · **Status:** Specified

### REQ-MER-006 — Personal QR code per marketer with automatic attribution on scan

- **Source:** `page 7 / پیوست ۵ بند ۱`

> ساخت QR اختصاصی برای هر بازاریاب توسط سامانه؛ کد در پروفایل بازاریاب، روی کارت ویزیت و لینک اختصاصی ارسالی قابل استفاده است؛ با اسکن آن، فروشگاه ثبت‌شده زیرمجموعه همان بازاریاب ثبت می‌شود؛

- **Actors:** Marketer, Store
- **Statement:** The system generates a unique QR per marketer that, when scanned, opens the registration flow pre-attributed to that marketer.
- **Rules / edge cases:** Attribution applies to the created store and to any subsequent visitor conversion within the cookie/claim window; QR is printable and downloadable as an image.
- **Priority:** P0 · **Depends on:** REQ-MER-001
- **Acceptance:**
  1. Scanning a marketer QR opens registration in that marketer's context
  2. The created store's attribution matches the QR owner
  3. A QR image is downloadable for business cards
- **Label:** CONTRACT · **Status:** Specified

### REQ-MER-007 — Dedicated marketer code and invitation link

- **Source:** `page 6 / پیوست ۳-۲ بند ۴`

> داشتن کد اختصاصی، لینک اختصاصی و QR اختصاصی برای دعوت به همکاری؛

- **Actors:** Marketer
- **Statement:** Each marketer has a short code and a shareable link that identify them as referrer for store and colleague invitations.
- **Rules / edge cases:** Code is unique and immutable; link redirects to the registration page with the referrer recorded; expired or revoked codes are refused.
- **Priority:** P1 · **Depends on:** REQ-MER-006
- **Acceptance:**
  1. Code and link shown in the marketer panel
  2. Using the link records the referrer
  3. A revoked code no longer attributes anyone
- **Label:** CONTRACT · **Status:** Specified

### REQ-MER-008 — Credential handover after in-person registration

- **Source:** `page 9 / پیوست ۶ بند ۸`

> ارسال لینک نصب اپلیکیشن و نام کاربری و پسورد پس از رجیستر حضوری؛ الزام تغییر پسورد در ورود اول؛

- **Actors:** Marketer, Store
- **Statement:** After registration the store receives the app install link, username and temporary password by SMS, and must change the password at first login.
- **Rules / edge cases:** Credentials are generated server-side and never shown in plain text after the SMS; the SMS is sent to the owner's primary number.
- **Priority:** P1 · **Depends on:** REQ-IAM-011
- **Acceptance:**
  1. SMS contains install link, username and temporary password
  2. First login forces a password change
  3. The temporary password expires after 7 days
- **Label:** CONTRACT · **Status:** Specified

### REQ-MER-009 — Five-minute self-edit window, then admin-approved edits only

- **Source:** `page 9 / پیوست ۶ بند ۹`

> امکان ویرایش اطالعات فرم رجیستر توسط بازاریاب فقط در پنج دقیقه اول؛ پس از آن ویرایش و حذف و تأیید تغییرات فقط با تأیید ادمین؛

- **Actors:** Marketer, Admin
- **Statement:** A marketer may edit the registration form freely for five minutes after submission; afterwards edits and deletions become admin-approved requests.
- **Rules / edge cases:** Timer starts at creation; the window status is visible in the UI; after it closes the form is read-only with a 'request change' action.
- **Priority:** P1 · **Depends on:** REQ-MER-001
- **Acceptance:**
  1. Edit is allowed at minute 4 and blocked at minute 6
  2. Post-window edits create a pending request
  3. Deletion requires admin approval
- **Label:** CONTRACT · **Status:** Specified

### REQ-MER-010 — Admin activation state visible to the marketer

- **Source:** `page 6 / پیوست ۳-۲ بند ۹`

> مشاهده فروشگاه‌هایی که از طرف ادمین فعال/غیرفعال شناخته شده‌اند؛

- **Actors:** Marketer, Admin
- **Statement:** The marketer sees each of their stores with the admin's activation state (active, inactive, suspended) and the reason.
- **Rules / edge cases:** Only own stores listed; state changes are time-stamped with the admin actor.
- **Priority:** P1 · **Depends on:** REQ-IAM-015
- **Acceptance:**
  1. List shows state per store
  2. State change timestamp and actor are visible
  3. Another marketer's stores do not appear
- **Label:** CONTRACT · **Status:** Specified

### REQ-MER-011 — Automatic deactivation of stores with no purchase or invoice in a period

- **Source:** `page 7 / پیوست ۴ بند ۲۳`

> امکان تغییر خودکار فروشگاه‌های غیرفعال )بدون خرید یا ثبت فاکتور در بازه معین( به سطح غیرفعال و خروجی گرفتن از فهرست آن‌ها؛

- **Also satisfies:** `پیوست ۶ بند ۴`
- **Actors:** System, Admin
- **Statement:** A scheduled job marks a store inactive when it records no purchase or invoice within the admin-defined window, and removes it from active listings.
- **Rules / edge cases:** Window configurable in days; reactivation on the next invoice; the store is notified before deactivation; export of the inactive list is available.
- **Priority:** P1 · **Depends on:** REQ-MER-010
- **Acceptance:**
  1. A store with no invoices beyond the window flips to inactive
  2. Issuing an invoice reactivates it
  3. Inactive stores disappear from the public store list
- **Label:** CONTRACT · **Status:** Specified

### REQ-MER-012 — Inactive-store visit queue and second presentation form

- **Source:** `page 9 / پیوست ۶ بند ۷`

> در پرزنت فروشگاه‌های غیرفعال، امکان ثبت تصاویر صاحب مغازه، نمای بیرونی و کارت ویزیت؛ وجود فرم ثبت‌نام مجزا به‌عنوان فرم پرزن دوم؛

- **Actors:** Marketer
- **Statement:** For inactive stores the marketer can record owner, exterior and business-card photos and complete a second presentation form distinct from the first registration.
- **Rules / edge cases:** Second form links to the existing store record; both forms' data are compared in admin; re-visit outcome (reopened / refused) is recorded.
- **Priority:** P2 · **Depends on:** REQ-MER-011
- **Acceptance:**
  1. Second form reachable from an inactive store
  2. Photos stored as visit evidence
  3. Visit outcome recorded with a timestamp
- **Label:** CONTRACT · **Status:** Specified

### REQ-MER-013 — Store account changes surfaced to admin for re-visit assignment

- **Source:** `page 7 / پیوست ۴ بند ۲۴`

> نمایش تغییرات حساب‌های کاربری در پنل ادمین برای تأیید ویزیت مجدد فروشگاه‌ها توسط بازاریاب جدید؛

- **Actors:** Admin
- **Statement:** Account changes on store records appear in the admin panel so a new marketer can be assigned to re-visit the store.
- **Rules / edge cases:** Change list shows field, old/new value, actor and date; assignment creates a visit task for the chosen marketer.
- **Priority:** P2 · **Depends on:** REQ-MER-010
- **Acceptance:**
  1. Changed store accounts listed with diffs
  2. Admin can assign a new marketer from the list
  3. Assignment notifies the marketer
- **Label:** CONTRACT · **Status:** Specified

### REQ-MER-014 — Store panel for publishing content, managing basket and viewing orders

- **Source:** `page 5 / پیوست ۳-۱ بند ۱`

> ارسال محتوا و اعالن در سایت، مدیریت سبد خرید و مشاهده سفارش‌های انجام‌شده؛

- **Actors:** Store
- **Statement:** A store publishes products and announcements, manages its purchase basket and views the orders it placed.
- **Rules / edge cases:** Publishing rights require an active store; drafts stay private until approved where moderation applies.
- **Priority:** P0 · **Depends on:** REQ-CAT-005
- **Acceptance:**
  1. Store creates a product and it appears in its own catalogue
  2. Basket persists across sessions
  3. Order list shows only the store's own purchases
- **Label:** CONTRACT · **Status:** Specified

### REQ-MER-015 — Cross-store purchasing with wholesale and private price visibility

- **Source:** `page 6 / پیوست ۳-۱ بند ۸`

> ورود به فروشگاه‌ها و مشاهده قیمت‌های اختصاصی و قیمت عمده؛

- **Actors:** Store
- **Statement:** A store can browse other stores' catalogues and see its tier-specific and wholesale prices instead of consumer prices.
- **Rules / edge cases:** Price resolution by role and tier (REQ-PRI-001); consumers never see these prices; a store without an approved tier sees base wholesale only.
- **Priority:** P0 · **Depends on:** REQ-PRI-001
- **Acceptance:**
  1. Store sees colleague price on another store's product
  2. Consumer sees consumer price on the same product
  3. Price difference is explained by the applied price list
- **Label:** CONTRACT · **Status:** Specified

### REQ-MER-016 — Standalone producer page: services, map, facilities, history, media

- **Source:** `page 10 / پیوست ۱۱ بند ۳`

> صفحه اختصاصی هر تولیدکننده برای معرفی خدمات و محصوالت با لوکیشن نقشه، امکانات، سابقه شرکت، آپلود تصویر و فیلم و فضای هاست مشخص؛

- **Actors:** Supplier
- **Statement:** Each producer has a dedicated page showing services and products, a map location, facilities, company history, uploaded images and video, within an allocated hosting quota.
- **Rules / edge cases:** Media quota enforced per producer; page is indexable; video hosted via stream URL rather than binary storage to protect quota.
- **Priority:** P1 · **Depends on:** REQ-USR-007
- **Acceptance:**
  1. Page renders all six content blocks
  2. Upload beyond quota is refused with the limit shown
  3. Map pin matches the recorded coordinates
- **Label:** CONTRACT · **Status:** Specified

### REQ-MER-017 — Producer converted to colleague buyer with a fixed cross-supplier discount

- **Source:** `page 10 / پیوست ۱۱ بند ۲`

> امکان تبدیل هر تولیدکننده به همکار خریدار با تخفیف ثابت خرید از سایر عرضه‌کنندگان )درصد و نوع محصول با تعیین ادمین(؛

- **Actors:** Admin, Supplier
- **Statement:** Admin can flag a producer as a colleague buyer, giving it a fixed discount when buying from other suppliers, per product type and percentage.
- **Rules / edge cases:** Discount applies only to other suppliers' products; the producer's own products are excluded to prevent circular margin loss.
- **Priority:** P1 · **Depends on:** REQ-PRI-003
- **Acceptance:**
  1. Flagged producer sees discounted prices at other suppliers
  2. Own products show no self-discount
  3. Discount percentage is admin-editable per product type
- **Label:** CONTRACT · **Status:** Specified

### REQ-MER-018 — Nationwide active dealership directory with location

- **Source:** `page 11 / پیوست ۱۱ بند ۱۹`

> درج نمایندگی‌های فعال کشوری هر تولیدکننده همراه لوکیشن و مشخصات، جهت ارجاع مشتری برای نصب و اندازه‌گیری؛

- **Actors:** Supplier, Consumer
- **Statement:** Each producer lists its active nationwide dealerships with location and contact details, so consumers can be referred for installation and measurement.
- **Rules / edge cases:** Dealership entries carry city, address, phone and map pin; consumers can search by city; only admin-approved entries are public.
- **Priority:** P1 · **Depends on:** REQ-MER-016
- **Acceptance:**
  1. Dealerships listed per producer with map pins
  2. Filtering by city returns the right entries
  3. Unapproved entries stay hidden
- **Label:** CONTRACT · **Status:** Specified

### REQ-MER-019 — Independent supplier panel for products, prices, stock, orders and reports

- **Source:** `page 10 / پیوست ۱۱ بند ۱`

> پنل مستقل هر تولیدکننده و عرضه‌کننده برای مدیریت موجودی انبار، ثبت سفارش، قیمت‌گذاری و تغییر قیمت‌ها به‌صورت کامل و مشابه پنل فروشندگان حرفه‌ای؛

- **Also satisfies:** `پیوست ۱۱ بند ۱۱` · `پیوست ۳-۴` · `پیوست ۳-۴ بند ۱`
- **Actors:** Supplier
- **Statement:** Each supplier operates an independent panel to manage warehouse stock, place and receive orders, set prices and change them, with the same depth as the professional store panel.
- **Rules / edge cases:** Supplier sees only its own catalogue and orders; price history retained per change for audit.
- **Priority:** P0 · **Depends on:** REQ-IAM-009
- **Acceptance:**
  1. Supplier edits price and stock and sees the result on the storefront
  2. Order inbox shows supplier sub-orders only
  3. Price changes appear in the audit log
- **Label:** CONTRACT · **Status:** Specified

### REQ-MER-020 — Store accounting: transactions, sold-products report and manual invoice

- **Source:** `page 9 / پیوست ۵ بند ۴۴`

> حسابداری فروشگاه شامل تراکنش‌ها، گزارش محصوالت فروخته‌شده و ایجاد فاکتور دستی؛

- **Actors:** Store
- **Statement:** A store sees its transactions, a report of sold products and can issue a manual invoice for an offline sale.
- **Rules / edge cases:** Manual invoice consumes stock and is marked as manual in reports; manual invoices count toward store activity for the inactivity rule.
- **Priority:** P1 · **Depends on:** REQ-INV-002
- **Acceptance:**
  1. Transaction list reconciles with order payments
  2. Sold-products report totals match invoices
  3. Manual invoice reduces stock and appears in reports
- **Label:** CONTRACT · **Status:** Specified

<a id="cat"></a>
## CAT — Catalog

Catalogue: homepage merchandising, categories, product detail, comparison, product form and attribute-driven filtering.

### REQ-CAT-001 — Homepage with search bar, categories, banner slider, special offers, best sellers and newest products

- **Source:** `page 5 / پیوست ۲ بند ۲`

> صفحه اصلی در سبک دیجی‌کاال شامل نوار جستجو، دسته‌بندی‌ها، بنر اسالیدر، پیشنهاد ویژه، پرفروش‌ترین‌ها و جدیدترین محصوالت؛

- **Actors:** Visitor
- **Statement:** The homepage presents a search bar, category navigation, a banner slider, special offers, best sellers and newest products in a Digikala-style layout.
- **Rules / edge cases:** Each block is admin-ordered and can be hidden; best sellers computed over a rolling window; newest sorted by publish date.
- **Priority:** P0 · **Depends on:** REQ-CAT-003
- **Acceptance:**
  1. All six blocks render and are individually hideable
  2. Search from the homepage returns matching products
  3. Best-seller order matches sales in the window
- **Label:** CONTRACT · **Status:** Specified

### REQ-CAT-002 — Category page with dynamic filters and sortable ordering

- **Source:** `page 5 / پیوست ۲ بند ۳`

> صفحه دسته‌بندی محصوالت با فیلتر پویا )قیمت، برند، موجودی، امتیاز، تخفیف‌دار( و مرتب‌سازی دلخواه؛

- **Actors:** Visitor
- **Statement:** A category page offers live filters for price range, brand, availability, rating and discounted-only, plus sorting by price, newest, rating and popularity.
- **Rules / edge cases:** Filters combine (AND) and are reflected in the URL for shareable results; counts update with each filter; empty results show a recovery hint.
- **Priority:** P0 · **Depends on:** REQ-CAT-001
- **Acceptance:**
  1. All five filters work alone and combined
  2. Sorting changes the result order and survives reload
  3. Filtered URL is shareable and restores the same state
- **Label:** CONTRACT · **Status:** Specified

### REQ-CAT-003 — Category tree with admin-managed add, remove and ordering

- **Source:** `page 9 / پیوست ۷ بند ۴`

> فرم اختصاصی مدیریت محصوالت هنگام ثبت محصول توسط ادمین: تعریف مبلغ محصول برای هر سطح کاربری به‌صورت مجزا، ایجاد ویژگی‌های محصول با پارامترهای متفاوت و فیلترینگ متفاوت، توضیح کوتاه و توضیح کامل، افزودن و حذف دسته‌بندی، گالری تصاویر، مدیریت انبار؛

- **Actors:** Admin
- **Statement:** Admins create, rename, reorder and delete categories in a tree that drives navigation, product assignment and filtering.
- **Rules / edge cases:** Deletion blocked while products reference the category; slugs regenerate on rename with redirects; depth limited to three levels.
- **Priority:** P0 · **Depends on:** REQ-CTR-012
- **Acceptance:**
  1. Category CRUD works from the admin panel
  2. Deleting a category with products is refused
  3. Renamed slug redirects the old URL
- **Label:** CONTRACT · **Status:** Specified

### REQ-CAT-004 — Product detail page with gallery, video, specs, reviews, related items and compare action

- **Source:** `page 5 / پیوست ۲ بند ۴`

> صفحه تک‌محصول با گالری تصاویر، ویدئو، مشخصات فنی، نظرات و امتیاز خریداران، محصوالت مرتبط و امکان افزودن به مقایسه؛

- **Actors:** Visitor
- **Statement:** A product page shows an image gallery, optional video, technical specifications, buyer reviews and rating, related products and an add-to-compare action.
- **Rules / edge cases:** Specs come from the product type's attribute set; reviews only from verified buyers; structured data emitted for SEO.
- **Priority:** P0 · **Depends on:** REQ-RVW-001, REQ-CAT-006
- **Acceptance:**
  1. All blocks render for a fully populated product
  2. Only verified buyers can submit a review
  3. Add-to-compare respects the five-item limit
- **Label:** CONTRACT · **Status:** Specified

### REQ-CAT-005 — Product create/edit form with per-level prices, attributes, descriptions, gallery and warehouse

- **Source:** `page 9 / پیوست ۷ بند ۴`

> فرم اختصاصی مدیریت محصوالت هنگام ثبت محصول توسط ادمین: تعریف مبلغ محصول برای هر سطح کاربری به‌صورت مجزا، ایجاد ویژگی‌های محصول با پارامترهای متفاوت و فیلترینگ متفاوت، توضیح کوتاه و توضیح کامل، افزودن و حذف دسته‌بندی، گالری تصاویر، مدیریت انبار؛

- **Actors:** Admin, Supplier, Store
- **Statement:** The product form captures a separate price for each user level, type-specific attributes with their filter behaviour, short and long descriptions, categories, image gallery and warehouse stock.
- **Rules / edge cases:** At least one price per active level required; attribute definitions belong to the product type; saving is transactional with stock initialisation.
- **Priority:** P0 · **Depends on:** REQ-CAT-003, REQ-PRI-002, REQ-STK-001
- **Acceptance:**
  1. A product saves with distinct prices per level
  2. Custom attributes appear on the product page and as filters
  3. Missing required attribute blocks save with a field error
- **Label:** CONTRACT · **Status:** Specified

### REQ-CAT-006 — Product types own independent attribute sets and filter definitions

- **Source:** `page 5 / پیوست ۱ بند ۸`

> ساختار پایگاه داده به‌گونه‌ای که امکان افزودن سطح کاربری جدید یا نوع محصول تازه بدون بازنویسی هسته فراهم باشد؛

- **Actors:** Admin
- **Statement:** Each product type defines its own attributes, units and filterability, so a new type can be added without changing core code.
- **Rules / edge cases:** Attribute values are typed (number, enum, boolean, text) and filterable flags drive the category sidebar.
- **Priority:** P1 · **Depends on:** REQ-CAT-003
- **Acceptance:**
  1. A new product type with new attributes is created from the panel
  2. Its attributes appear as filters on its category page
  3. No deployment is needed
- **Label:** CONTRACT · **Status:** Specified

### REQ-CAT-007 — Comparison of up to five products on the same parameters

- **Source:** `page 5 / پیوست ۲ بند ۵`

> امکان مقایسه هم‌زمان تا پنج محصول با پارامترهای یکسان؛

- **Actors:** Visitor
- **Statement:** A visitor adds up to five products to a comparison view that aligns them on the shared attribute set.
- **Rules / edge cases:** Sixth product is refused with a prompt to remove one; only attributes common to all compared products are aligned; comparison link is shareable.
- **Priority:** P1 · **Depends on:** REQ-CAT-004
- **Acceptance:**
  1. Five products compare side by side
  2. Adding a sixth is refused
  3. Attributes missing on one product show as empty, not shifted
- **Label:** CONTRACT · **Status:** Specified

### REQ-CAT-008 — Product star rating submitted only by buyers of that product

- **Source:** `page 7 / پیوست ۵ بند ۱۱`

> امکان ارسال امتیاز و نظرسنجی برای محصوالت افرصً توسط خریداران همان محصول )ستاره محصول(؛

- **Actors:** Consumer, Store
- **Statement:** Only a user with a delivered order containing the product may rate it, once per purchase, producing the product star score.
- **Rules / edge cases:** One rating per user per product (latest wins); rating window closes 7 days after delivery where the contract sets a window; average rounded to one decimal.
- **Priority:** P1 · **Depends on:** REQ-RVW-001
- **Acceptance:**
  1. A user without a purchase sees no rating form
  2. Rating submits and updates the average
  3. Second rating replaces the first, not adds
- **Label:** CONTRACT · **Status:** Specified

### REQ-CAT-009 — Campaign and festival product showcase on the homepage

- **Source:** `page 10 / پیوست ۱۱ بند ۴`

> بخش ارائه محصوالت در جشنواره‌ها و تخفیفات ویژه و نمایش آن در صفحه اصلی؛

- **Actors:** Supplier, Admin
- **Statement:** Suppliers submit products to festivals and special discounts; approved entries appear in a homepage campaign block with the campaign window.
- **Rules / edge cases:** Campaign has start/end; products auto-exit at end; discount price must be lower than the base price.
- **Priority:** P1 · **Depends on:** REQ-PRM-006
- **Acceptance:**
  1. Approved campaign products appear on the homepage
  2. Block disappears after the end date
  3. A non-discounted entry is rejected
- **Label:** CONTRACT · **Status:** Specified

### REQ-CAT-010 — Buyer work photos displayed under each product description

- **Source:** `page 11 / پیوست ۱۲ بند ۱۶`

> نمایش تصاویر نمونه کارهای انجام‌شده توسط مشتریان زیر توضیحات هر محصول؛

- **Actors:** Consumer, Admin
- **Statement:** Photos of completed installations submitted by customers are shown under the product description after admin approval.
- **Rules / edge cases:** Upload requires a delivered order for that product; images moderated before display; max 6 per product with pagination beyond.
- **Priority:** P2 · **Depends on:** REQ-CAT-004
- **Acceptance:**
  1. Customer uploads a work photo from their order
  2. Approved photos render under the description
  3. Unapproved photos are not publicly visible
- **Label:** CONTRACT · **Status:** Specified

### REQ-CAT-011 — Bulk purchase with a discount percentage declared in the product description

- **Source:** `page 11 / پیوست ۱۲ بند ۶`

> ثبت خرید حجمی با درصد تخفیف تعیین‌شده در معرفی محصول؛

- **Actors:** Store, Consumer
- **Statement:** A product can declare volume discount tiers; when the ordered quantity or amount crosses a tier the line price is reduced accordingly.
- **Rules / edge cases:** Tiers stored per product with min quantity and percent; the best applicable tier applies, not cumulative; shown on the product page before cart.
- **Priority:** P1 · **Depends on:** REQ-PRI-004
- **Acceptance:**
  1. Crossing a tier reduces the line price by the declared percent
  2. Only one tier applies per line
  3. Declared tiers are visible on the product page
- **Label:** CONTRACT · **Status:** Specified

### REQ-CAT-012 — Catalogue-wide search across products, stores and articles

- **Source:** `page 11 / پیوست ۱۲ بند ۵`

> امکانات کامل فروشگاهی: مقایسه محصوالت، جستجو، ارسال پستی، امتیازدهی و نظرسنجی و صفحات استاندارد؛

- **Actors:** Visitor
- **Statement:** A single search box returns matching products, stores and articles with type facets and typo tolerance.
- **Rules / edge cases:** Results ranked by relevance then availability; Persian/Arabic character normalisation; empty state suggests categories.
- **Priority:** P1 · **Depends on:** REQ-CAT-002
- **Acceptance:**
  1. Search finds a product by partial Persian name
  2. Arabic yeh/kaf variants match Persian forms
  3. Results can be filtered by type
- **Label:** CONTRACT · **Status:** Specified

### REQ-CAT-013 — Product video and media on the detail page

- **Source:** `page 5 / پیوست ۲ بند ۴`

> صفحه تک‌محصول با گالری تصاویر، ویدئو، مشخصات فنی، نظرات و امتیاز خریداران، محصوالت مرتبط و امکان افزودن به مقایسه؛

- **Actors:** Supplier, Admin
- **Statement:** A product may carry one or more videos that play inside the gallery without leaving the page.
- **Rules / edge cases:** Video by URL or upload with size cap; poster image required; unsupported formats rejected.
- **Priority:** P2 · **Depends on:** REQ-CAT-004
- **Acceptance:**
  1. Video plays in the gallery on mobile and desktop
  2. Oversized upload is refused
  3. Removing the video restores the image-only gallery
- **Label:** CONTRACT · **Status:** Specified

### REQ-CAT-014 — Availability filter reflects real stock across warehouses

- **Source:** `page 5 / پیوست ۲ بند ۳`

> صفحه دسته‌بندی محصوالت با فیلتر پویا )قیمت، برند، موجودی، امتیاز، تخفیف‌دار( و مرتب‌سازی دلخواه؛

- **Actors:** Visitor
- **Statement:** The availability filter returns only products with sellable stock, computed from warehouse balances minus reservations.
- **Rules / edge cases:** Reserved units excluded; out-of-stock products remain visible but marked; filter excludes them.
- **Priority:** P1 · **Depends on:** REQ-STK-001
- **Acceptance:**
  1. Reserving all stock removes the product from the in-stock filter
  2. Out-of-stock items show a badge
  3. Balance changes reflect within one request
- **Label:** CONTRACT · **Status:** Specified

### REQ-CAT-015 — Standard informational pages for the shop front

- **Source:** `page 11 / پیوست ۱۲ بند ۵`

> امکانات کامل فروشگاهی: مقایسه محصوالت، جستجو، ارسال پستی، امتیازدهی و نظرسنجی و صفحات استاندارد؛

- **Actors:** Visitor
- **Statement:** The shop front ships the standard pages (about, contact, rules, FAQ, blog index) reachable from the footer and sitemap.
- **Rules / edge cases:** Content is CMS-managed (REQ-CMS-001); each page carries SEO metadata.
- **Priority:** P2 · **Depends on:** REQ-CMS-001
- **Acceptance:**
  1. All five standard pages resolve
  2. Each is linked from the footer
  3. Each has title and description metadata
- **Label:** CONTRACT · **Status:** Specified

### REQ-CAT-016 — Product photography service or guidance to standardise imagery

- **Source:** `page 11 / پیوست ۱۱ بند ۲۰`

> خدمات عکاسی محصوالت به‌صورت آموزش به عرضه‌کننده یا خدمات ویژه، برای یکسان‌سازی ساختار تصاویر محصوالت.

- **Actors:** Supplier, Admin
- **Statement:** Suppliers are given photography guidance or a special service so product images follow one aspect ratio, background and naming structure.
- **Rules / edge cases:** Guided upload crops to the standard ratio; non-conforming images flagged for review rather than silently rejected.
- **Priority:** P2 · **Depends on:** REQ-CAT-005
- **Acceptance:**
  1. Upload guidance lists ratio, size and background rules
  2. Images are cropped to the standard ratio
  3. Non-conforming uploads are flagged to admin
- **Label:** CONTRACT · **Status:** Specified

<a id="pri"></a>
## PRI — Pricing

Pricing: multi-level price lists, consumer vs colleague spread, volume ladders, price history and price snapshots.

### REQ-PRI-001 — Price resolved server-side from the buyer's level and tier

- **Source:** `page 6 / پیوست ۳-۱ بند ۸`

> ورود به فروشگاه‌ها و مشاهده قیمت‌های اختصاصی و قیمت عمده؛

- **Actors:** System, all buyers
- **Statement:** Every displayed and charged price is resolved on the server from the product's price list, the buyer's level and tier, and the active volume ladder — never from client input.
- **Rules / edge cases:** Precedence: base price → level price list → volume tier → promotion → coupon; the resolution is order-independent and logged on the order line.
- **Priority:** P0 · **Depends on:** REQ-CAT-005
- **Acceptance:**
  1. Tampering with a posted price does not change the charged amount
  2. Two levels see different prices for the same product
  3. Resolution order is documented and enforced in one service
- **Label:** CONTRACT · **Status:** Specified

### REQ-PRI-002 — A separate price per user level on the product record

- **Source:** `page 9 / پیوست ۷ بند ۴`

> تعریف مبلغ محصول برای هر سطح کاربری به‌صورت مجزا

- **Actors:** Admin, Supplier
- **Statement:** Each product stores an explicit price for every active user level, and the storefront shows the price belonging to the viewer's level.
- **Rules / edge cases:** A missing level price falls back to the level marked as default and is flagged in admin; prices are integer Toman.
- **Priority:** P0 · **Depends on:** REQ-CAT-005
- **Acceptance:**
  1. Four level prices saved and displayed to the matching level
  2. A missing level price is flagged, never shown as zero
  3. Prices stored as integers without decimals
- **Label:** CONTRACT · **Status:** Specified

### REQ-PRI-003 — Consumer price set above colleague price by a percentage factor or manual override

- **Source:** `page 11 / پیوست ۱۲ بند ۱`

> تفاوت قیمت مصرف‌کننده با قیمت همکار، با امکان اعمال دستی توسط ادمین یا ضریب درصدی روی همه محصوالت )مث الً ۳۵ درصد باالتر(؛

- **Actors:** Admin
- **Statement:** The consumer price is derived from the colleague price by a configurable percentage factor applied globally, with per-product manual override.
- **Rules / edge cases:** Factor change does not retroactively alter issued invoices; override wins over the factor; rounding applied after the factor.
- **Priority:** P0 · **Depends on:** REQ-PRI-002
- **Acceptance:**
  1. Setting 35% raises all consumer prices by 35% (rounded)
  2. A manual override survives a factor change
  3. Existing invoices keep their snapshotted prices
- **Label:** CONTRACT · **Status:** Specified

### REQ-PRI-004 — Order lines snapshot price and applied rules

- **Source:** `page 11 / پیوست ۱۲ بند ۱`

> تفاوت قیمت مصرف‌کننده با قیمت همکار، با امکان اعمال دستی توسط ادمین یا ضریب درصدی روی همه محصوالت )مث الً ۳۵ درصد باالتر(؛

- **Actors:** System
- **Statement:** Each order line stores the unit price charged, the base price, the price-list source and every rule applied, so later price changes never alter history.
- **Rules / edge cases:** Snapshot is immutable after invoice issuance; disputable only through a corrective document.
- **Priority:** P0 · **Depends on:** REQ-PRI-001
- **Acceptance:**
  1. Changing a product price leaves existing orders untouched
  2. The order line shows the price source
  3. A corrective invoice references the original line
- **Label:** TECHNICAL_CONSTRAINT · **Status:** Specified

### REQ-PRI-005 — Price history with effective dates and audit entries

- **Source:** `page 5 / پیوست ۱ بند ۱۰`

> الگ کامل رویدادهای حساس )ورود، تغییر قیمت، تغییر سطح کاربری، تسویه( برای پیگیری.

- **Actors:** Admin, Supplier
- **Statement:** Every price change stores the old and new value, the actor, the timestamp and the effective date, and is written to the audit log.
- **Rules / edge cases:** Scheduled future prices allowed; the effective price at any past date is reconstructable.
- **Priority:** P1 · **Depends on:** REQ-CTR-014
- **Acceptance:**
  1. A price change appears in history with before/after
  2. A future-dated change applies automatically at its time
  3. History is queryable by product and date
- **Label:** CONTRACT · **Status:** Specified

### REQ-PRI-006 — Volume discount ladder applied by the pricing engine

- **Source:** `page 11 / پیوست ۱۲ بند ۶`

> ثبت خرید حجمی با درصد تخفیف تعیین‌شده در معرفی محصول؛

- **Actors:** System
- **Statement:** When a line's quantity or amount reaches a declared ladder step, the engine applies that step's percentage to the line price and records which step was used.
- **Rules / edge cases:** Only the highest reached step applies; ladders are per product; a cancelled line releases the discount.
- **Priority:** P1 · **Depends on:** REQ-CAT-011
- **Acceptance:**
  1. Order at the tier threshold receives exactly the declared percentage
  2. Two tiers do not stack
  3. The applied step is shown on the order line
- **Label:** CONTRACT · **Status:** Specified

### REQ-PRI-007 — Consumer pricing exposed only to the consumer level

- **Source:** `page 8 / پیوست ۵ بند ۱۸`

> فعال‌سازی بخش مصرف‌کننده با امکان رویت قیمت مصرف‌کننده و امکان پرداخت مبلغ باالتر از قیمت خرید به تأمین‌کننده در بخش حسابداری؛

- **Actors:** System, Consumer
- **Statement:** Consumer prices are displayed and chargeable only to consumer accounts; colleague accounts and guests never receive the consumer price list.
- **Rules / edge cases:** Guest sees consumer price for browsing but checkout requires a level; colleague price is never shown to consumers.
- **Priority:** P0 · **Depends on:** REQ-PRI-002
- **Acceptance:**
  1. A colleague account never sees the consumer price
  2. A guest cannot check out at colleague price
  3. Price lists are selected by an explicit policy check
- **Label:** CONTRACT · **Status:** Specified

### REQ-PRI-008 — Original and discounted price shown together with a discount badge

- **Source:** `page 5 / پیوست ۲ بند ۳`

> صفحه دسته‌بندی محصوالت با فیلتر پویا )قیمت، برند، موجودی، امتیاز، تخفیف‌دار( و مرتب‌سازی دلخواه؛

- **Actors:** Visitor
- **Statement:** When a product has an active discount, listings show the original price struck through, the final price and a discount badge; the 'discounted only' filter matches exactly those products.
- **Rules / edge cases:** Badge computed from effective price vs base price; discounts scheduled in the future do not show early.
- **Priority:** P1 · **Depends on:** REQ-PRI-001
- **Acceptance:**
  1. Discounted product shows both prices
  2. The discounted-only filter excludes non-discounted items
  3. A future discount is not displayed before its start
- **Label:** CONTRACT · **Status:** Specified

### REQ-PRI-009 — Supplier can change prices at any time with immediate effect

- **Source:** `page 10 / پیوست ۱۱ بند ۱`

> پنل مستقل هر تولیدکننده و عرضه‌کننده برای مدیریت موجودی انبار، ثبت سفارش، قیمت‌گذاری و تغییر قیمت‌ها به‌صورت کامل و مشابه پنل فروشندگان حرفه‌ای؛

- **Actors:** Supplier
- **Statement:** A supplier edits its own product prices from its panel and the new price takes effect on the storefront immediately.
- **Rules / edge cases:** Cannot price below an admin-set floor; every change is audited; concurrent edits use optimistic locking.
- **Priority:** P1 · **Depends on:** REQ-MER-019
- **Acceptance:**
  1. Price change is visible on the product page immediately
  2. A price below the floor is refused
  3. Concurrent edit by another user is detected
- **Label:** CONTRACT · **Status:** Specified

### REQ-PRI-010 — Pre-invoice shows per-level prices before purchase

- **Source:** `page 8 / پیوست ۵ بند ۴۲`

> نمایش پیش‌فاکتور برای مصرف‌کننده و امکان خرید با قیمت‌های متفاوت بر اساس سطح کاربری؛

- **Actors:** Consumer, Store
- **Statement:** Before confirming, the buyer sees a pre-invoice whose line prices match their level, including the discount breakdown and shipping estimate.
- **Rules / edge cases:** Pre-invoice is recalculated on any cart change; it is not a financial document and expires with the cart session.
- **Priority:** P1 · **Depends on:** REQ-CRT-003
- **Acceptance:**
  1. Pre-invoice totals equal the level prices
  2. Changing quantity updates the pre-invoice
  3. It is clearly labelled as non-final
- **Label:** CONTRACT · **Status:** Specified

### REQ-PRI-011 — Money stored as integer Toman with a single arithmetic service

- **Source:** `page 2 / ماده ۲`

> مبلغ کل این قرارداد معادل ۹۰٬۰۰۰٬۰۰۰ تومان است.

- **Actors:** Engineering
- **Statement:** All monetary amounts are integers in Toman stored in BIGINT columns and every calculation goes through one Money value object.
- **Rules / edge cases:** No floats anywhere in the money path; rounding only at defined points (factor application, tax); formatting at the presentation layer.
- **Priority:** P0 · **Depends on:** —
- **Acceptance:**
  1. No float column exists for money
  2. A static check blocks arithmetic on raw amount fields
  3. Percentages round consistently in tests
- **Label:** TECHNICAL_CONSTRAINT · **Status:** Specified

<a id="stk"></a>
## STK — Inventory

Inventory: warehouse balances, supplier stock, reservations, oversell prevention and adjustment audit.

### REQ-STK-001 — Warehouse stock maintained per product and variant

- **Source:** `page 9 / پیوست ۷ بند ۴`

> افزودن و حذف دسته‌بندی، گالری تصاویر، مدیریت انبار؛

- **Actors:** Admin, Store, Supplier
- **Statement:** Each product variant has a stock balance per warehouse, with on-hand, reserved and available quantities.
- **Rules / edge cases:** Available = on-hand − reserved; negative on-hand blocked; balances are per warehouse and summed for display.
- **Priority:** P0 · **Depends on:** REQ-CAT-005
- **Acceptance:**
  1. Admin sets a balance and the storefront reflects it
  2. Available subtracts reservations
  3. A negative balance cannot be stored
- **Label:** CONTRACT · **Status:** Specified

### REQ-STK-002 — Supplier manages its own warehouse stock from its panel

- **Source:** `page 10 / پیوست ۱۱ بند ۱`

> پنل مستقل هر تولیدکننده و عرضه‌کننده برای مدیریت موجودی انبار، ثبت سفارش، قیمت‌گذاری و تغییر قیمت‌ها به‌صورت کامل و مشابه پنل فروشندگان حرفه‌ای؛

- **Actors:** Supplier
- **Statement:** A supplier increases or corrects stock for its own products and sees the resulting availability on its listings.
- **Rules / edge cases:** Supplier scope limited to own products; corrections require a reason code; changes audited.
- **Priority:** P0 · **Depends on:** REQ-STK-001
- **Acceptance:**
  1. Supplier stock update reflects in availability
  2. Another supplier's product is inaccessible
  3. Each correction records a reason
- **Label:** CONTRACT · **Status:** Specified

### REQ-STK-003 — Atomic decrement and release of stock on order lifecycle events

- **Source:** `page 10 / پیوست ۱۱ بند ۱۵`

> ثبت تأخیر ارسال کاال طبق زمان‌بندی سفارش با درج شماره بارنامه و ارسال با پیک؛ کسر خودکار درصد خسارت از فاکتور و واریز به کیف پول خریدار؛

- **Actors:** System
- **Statement:** Stock is reserved when an order is placed, decremented when the invoice is issued and released when the order is cancelled or returned, all inside a transaction with row locking.
- **Rules / edge cases:** Idempotent: replaying the same event does not decrement twice; concurrent checkouts cannot oversell.
- **Priority:** P0 · **Depends on:** REQ-STK-001
- **Acceptance:**
  1. Two simultaneous checkouts for the last unit yield one success
  2. Cancelling releases the reservation
  3. Duplicate event replay leaves the balance unchanged
- **Label:** TECHNICAL_CONSTRAINT · **Status:** Specified

### REQ-STK-004 — Oversell prevention at checkout

- **Source:** `page 5 / پیوست ۲ بند ۳`

> صفحه دسته‌بندی محصوالت با فیلتر پویا )قیمت، برند، موجودی، امتیاز، تخفیف‌دار( و مرتب‌سازی دلخواه؛

- **Actors:** Consumer, Store
- **Statement:** Checkout re-validates availability for every line and blocks confirmation when requested quantity exceeds available stock.
- **Rules / edge cases:** Message names the affected line and offers the available quantity; partial fulfilment is not automatic.
- **Priority:** P0 · **Depends on:** REQ-STK-003
- **Acceptance:**
  1. Requesting more than available blocks checkout
  2. The error names the line and the maximum
  3. Reducing quantity allows confirmation
- **Label:** CONTRACT · **Status:** Specified

### REQ-STK-005 — Manual stock adjustment by admin with reason and audit

- **Source:** `page 11 / پیوست ۱۱ بند ۱۶`

> امکان اعمال هزینه یا پرداخت جدید در حساب عرضه‌کننده توسط ادمین به‌صورت دستی؛

- **Actors:** Admin
- **Statement:** An admin adjusts a stock balance manually with a mandatory reason; the adjustment is recorded with actor, delta and resulting balance.
- **Rules / edge cases:** Used for shrinkage and counting corrections; never for sales; visible in the product's stock history.
- **Priority:** P1 · **Depends on:** REQ-STK-001
- **Acceptance:**
  1. Adjustment without a reason is refused
  2. History shows delta and resulting balance
  3. Adjustment does not create an order or invoice
- **Label:** CONTRACT · **Status:** Specified

### REQ-STK-006 — Sold-out products remain visible but not purchasable

- **Source:** `page 5 / پیوست ۲ بند ۳`

> صفحه دسته‌بندی محصوالت با فیلتر پویا )قیمت، برند، موجودی، امتیاز، تخفیف‌دار( و مرتب‌سازی دلخواه؛

- **Actors:** Visitor
- **Statement:** A product with zero available stock stays in the catalogue with a sold-out badge, its price visible but the add-to-cart action disabled.
- **Rules / edge cases:** Excluded from the in-stock filter and from best-seller purchase flows; back-in-stock notification offered.
- **Priority:** P2 · **Depends on:** REQ-STK-003
- **Acceptance:**
  1. Sold-out product shows a badge and no add-to-cart
  2. It is absent from in-stock results
  3. Restocking re-enables purchase
- **Label:** CONTRACT · **Status:** Specified

<a id="crt"></a>
## CRT — Cart and checkout

Cart and checkout: basket persistence, coupon application, shipping estimate and the single-page payment handoff.

### REQ-CRT-001 — Persistent cart per buyer, usable across devices

- **Source:** `page 5 / پیوست ۲ بند ۶`

> سبد خرید، اعمال کد تخفیف، محاسبه هزینه ارسال و صفحه پرداخت یکپارچه با درگاه بانکی؛

- **Actors:** Consumer, Store
- **Statement:** A signed-in buyer's cart is stored server-side and restored on any device until it is converted to an order or emptied.
- **Rules / edge cases:** Guest carts are keyed by session and merged at login (REQ-CRT-006); carts expire after 30 days of inactivity.
- **Priority:** P0 · **Depends on:** REQ-IAM-002
- **Acceptance:**
  1. Cart survives logout and login on another browser
  2. Expired cart is emptied with a notice
  3. Only own cart items are readable
- **Label:** CONTRACT · **Status:** Specified

### REQ-CRT-002 — Add, update and remove cart lines with live totals

- **Source:** `page 5 / پیوست ۲ بند ۶`

> سبد خرید، اعمال کد تخفیف، محاسبه هزینه ارسال و صفحه پرداخت یکپارچه با درگاه بانکی؛

- **Actors:** Consumer, Store
- **Statement:** A buyer adds items, changes quantity and removes lines; line totals and the cart total recalculate immediately with the resolved price.
- **Rules / edge cases:** Quantity bounded by available stock; removing the last line shows an empty state; totals recalculated server-side on every change.
- **Priority:** P0 · **Depends on:** REQ-PRI-001
- **Acceptance:**
  1. Totals update within the same request
  2. Quantity above stock is clamped with a message
  3. Empty cart shows an empty state, not a zero invoice
- **Label:** CONTRACT · **Status:** Specified

### REQ-CRT-003 — Single checkout page combining address, coupon, shipping and gateway handoff

- **Source:** `page 5 / پیوست ۲ بند ۶`

> سبد خرید، اعمال کد تخفیف، محاسبه هزینه ارسال و صفحه پرداخت یکپارچه با درگاه بانکی؛

- **Actors:** Consumer, Store
- **Statement:** One checkout page collects the delivery address, applies the coupon, computes shipping and hands off to the chosen bank gateway.
- **Rules / edge cases:** Address required before payment; shipping recalculated when the city changes; the order is created as pending before the gateway redirect.
- **Priority:** P0 · **Depends on:** REQ-CRT-002, REQ-SHP-002, REQ-PAY-001
- **Acceptance:**
  1. Checkout completes end-to-end on a mobile browser
  2. Changing the city updates the shipping cost
  3. Missing address blocks the payment button
- **Label:** CONTRACT · **Status:** Specified

### REQ-CRT-004 — Coupon code applied at checkout with server-side validation

- **Source:** `page 5 / پیوست ۲ بند ۶`

> سبد خرید، اعمال کد تخفیف، محاسبه هزینه ارسال و صفحه پرداخت یکپارچه با درگاه بانکی؛

- **Actors:** Consumer, Store
- **Statement:** A buyer enters a coupon code and the discount is applied only if the code is active, in its date window, within its usage limit and eligible for the cart.
- **Rules / edge cases:** Invalid, expired, exhausted or ineligible codes return a specific Persian reason; one coupon per order; the discount is stored on the order.
- **Priority:** P1 · **Depends on:** REQ-PRM-001
- **Acceptance:**
  1. Valid code reduces the total by the declared amount
  2. Expired code is refused with 'expired'
  3. Second coupon on the same order is refused
- **Label:** CONTRACT · **Status:** Specified

### REQ-CRT-005 — Multi-seller cart split into per-seller sub-orders

- **Source:** `page 5 / پیوست ۳-۱ بند ۱`

> ارسال محتوا و اعالن در سایت، مدیریت سبد خرید و مشاهده سفارش‌های انجام‌شده؛

- **Actors:** System
- **Statement:** A cart containing items from several sellers is split into one parent order and one sub-order per seller, each with its own status, invoice and settlement.
- **Rules / edge cases:** Payment is collected once on the parent; shipping and commission computed per sub-order; partial cancellation allowed per sub-order.
- **Priority:** P0 · **Depends on:** REQ-CRT-002
- **Acceptance:**
  1. A two-seller cart produces one parent and two sub-orders
  2. Each sub-order has its own invoice
  3. Cancelling one sub-order leaves the other intact
- **Label:** TECHNICAL_CONSTRAINT · **Status:** Specified

### REQ-CRT-006 — Guest cart merged into the account cart at login

- **Source:** `page 5 / پیوست ۲ بند ۶`

> سبد خرید، اعمال کد تخفیف، محاسبه هزینه ارسال و صفحه پرداخت یکپارچه با درگاه بانکی؛

- **Actors:** Consumer
- **Statement:** Items added before login are merged into the account cart on authentication, combining quantities for identical lines.
- **Rules / edge cases:** Stock limits applied after merge; conflicts larger than stock are clamped with a notice.
- **Priority:** P2 · **Depends on:** REQ-CRT-001
- **Acceptance:**
  1. Guest item appears after login
  2. Duplicate line quantities are summed
  3. Clamped quantity is announced
- **Label:** TECHNICAL_CONSTRAINT · **Status:** Specified

### REQ-CRT-007 — Cart re-prices when a product price or availability changes

- **Source:** `page 5 / پیوست ۲ بند ۶`

> سبد خرید، اعمال کد تخفیف، محاسبه هزینه ارسال و صفحه پرداخت یکپارچه با درگاه بانکی؛

- **Actors:** Consumer
- **Statement:** When a cart line's price or availability changes before checkout, the cart refreshes the line and tells the buyer what changed.
- **Rules / edge cases:** Price increase requires explicit acknowledgement; unavailable line is flagged and can be removed in one click.
- **Priority:** P1 · **Depends on:** REQ-PRI-001
- **Acceptance:**
  1. Price change is reflected before payment
  2. Buyer sees a 'price changed' notice
  3. Unavailable line is marked and removable
- **Label:** TECHNICAL_CONSTRAINT · **Status:** Specified

### REQ-CRT-008 — Store basket used for wholesale purchasing

- **Source:** `page 5 / پیوست ۳-۱ بند ۱`

> ارسال محتوا و اعالن در سایت، مدیریت سبد خرید و مشاهده سفارش‌های انجام‌شده؛

- **Actors:** Store
- **Statement:** A store builds a wholesale basket from other sellers' catalogues and completes it with its level prices.
- **Rules / edge cases:** Same checkout as consumer flow but with colleague price list and no consumer-only promotions.
- **Priority:** P1 · **Depends on:** REQ-MER-015
- **Acceptance:**
  1. Store basket totals use colleague prices
  2. Consumer-only coupon is refused
  3. Order is attributed to the store
- **Label:** CONTRACT · **Status:** Specified

<a id="ord"></a>
## ORD — Orders

Orders: placement, sub-orders, status machine, tracking, incomplete/cancel/refund flows and order attachments.

### REQ-ORD-001 — Order placement with delivery address and chosen shipping method

- **Source:** `page 8 / پیوست ۵ بند ۴۰`

> ثبت نام و نوع باربری و روش حمل هنگام ثبت سفارش و اعالم آن در فرم ارسالی به پنل توزیع‌کننده و عرضه‌کننده؛

- **Actors:** Consumer, Store
- **Statement:** At checkout the buyer records the recipient name, carrier type and shipping method; both appear on the order form sent to the distributor and supplier panels.
- **Rules / edge cases:** Carrier list is admin-managed; a method unavailable for the destination is disabled; the choice is immutable after payment except by admin.
- **Priority:** P0 · **Depends on:** REQ-SHP-001
- **Acceptance:**
  1. Order stores recipient, carrier and method
  2. Supplier panel shows the same values
  3. Unavailable method is not selectable
- **Label:** CONTRACT · **Status:** Specified

### REQ-ORD-002 — Parent order with per-seller sub-orders and independent status

- **Source:** `page 11 / پیوست ۱۳ بند ۱`

> گزارش دقیق و فیلترپذیر فروش برای تسویه دستی با تولیدکنندگان بر اساس بازه زمانی، مبلغ، تاریخ و نوع محصول؛

- **Actors:** System
- **Statement:** Each purchase creates a parent order for the buyer and one sub-order per seller, each carrying its own status, invoice, shipment and settlement record.
- **Rules / edge cases:** Parent status is derived from sub-orders (all delivered / any cancelled); buyer sees one order number, sellers see only their sub-order.
- **Priority:** P0 · **Depends on:** REQ-CRT-005
- **Acceptance:**
  1. Parent aggregates sub-order states correctly
  2. Seller cannot see other sub-orders
  3. Settlement is computed per sub-order
- **Label:** TECHNICAL_CONSTRAINT · **Status:** Specified

### REQ-ORD-003 — Order tracking by tracking code without login

- **Source:** `page 5 / پیوست ۲ بند ۱۱`

> صفحات درباره ما، تماس با ما با فرم و نقشه، و پیگیری سفارش با کد رهگیری؛

- **Also satisfies:** `پیوست ۳-۳ بند ۶`
- **Actors:** Consumer, Store
- **Statement:** Anyone holding the tracking code sees the order's current status and history on a public tracking page, without exposing personal data.
- **Rules / edge cases:** Code is unguessable; page shows status timeline and shipment events only; rate-limited against enumeration.
- **Priority:** P1 · **Depends on:** REQ-ORD-004
- **Acceptance:**
  1. Tracking code returns status and timeline
  2. No phone, address or price is exposed
  3. Brute-force enumeration is throttled
- **Label:** CONTRACT · **Status:** Specified

### REQ-ORD-004 — Explicit order state machine with audited transitions

- **Source:** `page 11 / پیوست ۱۳ بند ۵`

> ثبت و پیگیری گردش مالی هر سطح کاربری )کیف پول، بدهکار/بستانکار، تسویه‌های انجام‌شده(؛

- **Actors:** System, Admin
- **Statement:** Orders move through a documented set of states, each transition permitted only to specific actors and each recorded with actor, time and reason.
- **Rules / edge cases:** States: pending payment → paid → preparing → shipped → delivered / cancelled / returned; no skipping; illegal transitions return 409.
- **Priority:** P0 · **Depends on:** REQ-ORD-002
- **Acceptance:**
  1. Illegal transition is rejected with 409
  2. Every transition appears in the order history
  3. State diagram matches the implementation
- **Label:** TECHNICAL_CONSTRAINT · **Status:** Specified

### REQ-ORD-005 — Marketer registers customer and project orders on their behalf

- **Source:** `page 6 / پیوست ۳-۲ بند ۸`

> فرم ثبت سفارش مشتریان و پروژه‌ها؛

- **Actors:** Marketer
- **Statement:** A marketer fills an order form for a customer or a project, and the order is attributed to that customer and to the marketer's network.
- **Rules / edge cases:** Customer must exist or be created in the same flow; marketer cannot set prices below the level price; attribution drives commission.
- **Priority:** P1 · **Depends on:** REQ-MER-001
- **Acceptance:**
  1. Order created by marketer appears in the customer's account
  2. Commission attribution recorded
  3. Price tampering is refused
- **Label:** CONTRACT · **Status:** Specified

### REQ-ORD-006 — Approved-order form with a reverse order number on distributor and buyer copies

- **Source:** `page 8 / پیوست ۵ بند ۲۴`

> امکان ساخت فرم ثبت سفارش‌های تأییدشده با نشانگر شماره سفارش معکوس برای تحویل به خریدار در دو صفحه توزیع‌کننده و خریدار؛

- **Actors:** System, Supplier
- **Statement:** For each approved order the system renders two printable pages — distributor and buyer — each carrying the order number in reverse marking for matching.
- **Rules / edge cases:** Printable A4 layout, RTL; reverse marker rendered from the order number deterministically; PDF downloadable from both panels.
- **Priority:** P2 · **Depends on:** REQ-ORD-004
- **Acceptance:**
  1. Two distinct pages print for one order
  2. Reverse marker is identical on both
  3. PDF matches the on-screen data
- **Label:** CONTRACT · **Status:** Specified

### REQ-ORD-007 — Supplier marks an order incomplete and refers it back to the buyer

- **Source:** `page 10 / پیوست ۱۱ بند ۱۲`

> دکمه ثبت ناقص سفارش برای عرضه‌کننده جهت ارجاع سفارش ناقص به خریدار و جلوگیری از خسارت تأخیر؛

- **Actors:** Supplier
- **Statement:** A supplier can flag an order as incomplete, which returns it to the buyer for correction and pauses the delay clock.
- **Rules / edge cases:** A reason and the missing items are mandatory; the buyer is notified; the pause is limited to a configurable number of days.
- **Priority:** P1 · **Depends on:** REQ-ORD-004
- **Acceptance:**
  1. Flagging returns the order to the buyer with reasons
  2. Delay clock stops while incomplete
  3. Repeated misuse accrues negative points (REQ-CMP-005)
- **Label:** CONTRACT · **Status:** Specified

### REQ-ORD-008 — Seller or producer may reject an order and refund to the buyer's SHEBA

- **Source:** `page 8 / پیوست ۵ بند ۱۵`

> دکمه عدم تکمیل سفارش برای تأمین‌کننده تا از پرداخت خسارت تأخیر جلوگیری شود؛ امکان رد سفارش ثبت‌شده توسط فروشنده یا تولیدکننده و برگشت مبلغ پرداختی خریدار به شبا او؛

- **Actors:** Supplier, Store
- **Statement:** A seller may reject a placed order before preparation starts; the paid amount is returned to the buyer's registered SHEBA account.
- **Rules / edge cases:** Rejection needs a reason; refund instruction is generated automatically; the buyer is notified and stock released.
- **Priority:** P0 · **Depends on:** REQ-FIN-004
- **Acceptance:**
  1. Rejection triggers a refund instruction to the buyer's SHEBA
  2. Stock is released
  3. Buyer receives a notification with the reason
- **Label:** CONTRACT · **Status:** Specified

### REQ-ORD-009 — Supplier cancels an order from its panel and returns funds

- **Source:** `page 10 / پیوست ۱۱ بند ۱۴`

> امکان کنسل کردن سفارش و دستور مرجوعی وجه به حساب خریدار از پنل عرضه‌کننده؛

- **Actors:** Supplier
- **Statement:** A supplier cancels a sub-order from its panel, which produces a refund instruction to the buyer's account and releases the stock.
- **Rules / edge cases:** Allowed only before shipment; after shipment the return flow applies (REQ-RTN-001).
- **Priority:** P0 · **Depends on:** REQ-ORD-008
- **Acceptance:**
  1. Cancellation before shipment issues a refund
  2. Cancellation after shipment is refused with a redirect to returns
  3. Buyer is notified
- **Label:** CONTRACT · **Status:** Specified

### REQ-ORD-010 — Complaint suspends order preparation and supplier payout for up to one week after shipment

- **Source:** `page 7 / پیوست ۴ بند ۱۵`

> امکان ثبت شکایت و توقف مراحل آماده‌سازی سفارش تا یک هفته پس از ارسال بار فراهم است و پرداخت به عرضه‌کننده تا تعیین تکلیف متوقف می‌ماند؛

- **Also satisfies:** `پیوست ۵ بند ۳۲`
- **Actors:** Consumer, Store, Admin
- **Statement:** A buyer files a complaint up to one week after shipment; preparation and the supplier's payout freeze until an admin decides.
- **Rules / edge cases:** Freeze blocks settlement for that sub-order; window is 7 days from shipment timestamp; admin decision releases or upholds the freeze.
- **Priority:** P0 · **Depends on:** REQ-ORD-004, REQ-FIN-006
- **Acceptance:**
  1. Complaint inside the window freezes the sub-order settlement
  2. Complaint after the window is refused
  3. Admin resolution releases the payout or refunds
- **Label:** CONTRACT · **Status:** Specified

### REQ-ORD-011 — Stop-preparation control after invoice and payment until a defined period

- **Source:** `page 7 / پیوست ۴ بند ۱۶`

> وجود آیکون توقف آماده‌سازی سفارش پس از صدور فاکتور و پرداخت تا مدت معین؛ با تأیید تولیدکننده یا عرضه‌کننده، سفارش کنسل و وجه به خریدار مرجوع می‌شود؛

- **Actors:** Consumer, Store, Supplier
- **Statement:** After the invoice is issued and paid, the buyer can stop preparation within a defined period; with the supplier's confirmation the order is cancelled and refunded.
- **Rules / edge cases:** Stop requires a reason; the period default is 24 hours and is admin-configurable; supplier confirmation is mandatory for the refund.
- **Priority:** P1 · **Depends on:** REQ-ORD-010
- **Acceptance:**
  1. Stop icon available only in the defined window
  2. Refund occurs only after supplier confirmation
  3. Stop and outcome are recorded on the order
- **Label:** CONTRACT · **Status:** Specified

### REQ-ORD-012 — Voice, text and image attachments on order and invoice notes

- **Source:** `page 8 / پیوست ۵ بند ۴۱`

> امکان ضبط صدا و ارسال متن توضیحات هنگام ثبت هر فاکتور با دکمه پیوست، همراه با امکان پیوست عکس و فایل صوتی دو دقیقه‌ای؛

- **Actors:** Consumer, Store, Supplier
- **Statement:** When registering an order or invoice the actor may attach a text note, images and an audio recording of up to two minutes via one attach control.
- **Rules / edge cases:** Audio recorded in-browser and capped at 120 s; attachments stored privately and streamed to authorised viewers; types and sizes validated.
- **Priority:** P1 · **Depends on:** REQ-INV-002
- **Acceptance:**
  1. Audio over 120 seconds is truncated with a warning
  2. Attachments are visible to buyer and supplier only
  3. Unsupported file types are rejected
- **Label:** CONTRACT · **Status:** Specified

### REQ-ORD-013 — Order notes prevent shipping errors by pinning instructions

- **Source:** `page 11 / پیوست ۱۱ بند ۱۸`

> امکان پیوست فایل صوتی، متن یا عکس در توضیحات سفارش برای جلوگیری از خطای ارسال؛

- **Actors:** Consumer, Store
- **Statement:** Text, image or audio notes attached to an order are shown prominently on the supplier's picking sheet to prevent wrong-item shipment.
- **Rules / edge cases:** Notes are per order (not per line) and appear on the printed picking document.
- **Priority:** P1 · **Depends on:** REQ-ORD-012
- **Acceptance:**
  1. Note appears on the supplier's order view
  2. Note printed on the picking sheet
  3. Editing after shipment is blocked
- **Label:** CONTRACT · **Status:** Specified

### REQ-ORD-014 — Customer order taken by a local intermediary with address and location

- **Source:** `page 11 / پیوست ۱۲ بند ۳`

> دریافت سفارش مشتری توسط رابط )مانند فروشگاه محلی( همراه آدرس و لوکیشن برای نصب و اندازه‌گیری؛

- **Actors:** Store, Consumer
- **Statement:** A local store acting as intermediary registers a consumer order including the installation address and map location for measurement.
- **Rules / edge cases:** Intermediary commission attributed to the store; the consumer receives confirmation and tracking.
- **Priority:** P2 · **Depends on:** REQ-ORD-005
- **Acceptance:**
  1. Order records address and coordinates
  2. Consumer receives confirmation
  3. Intermediary attribution stored
- **Label:** CONTRACT · **Status:** Specified

### REQ-ORD-015 — Deposit payment with balance settled on delivery

- **Source:** `page 11 / پیوست ۱۲ بند ۱۳`

> پرداخت بیعانه و تسویه باقیمانده هنگام ارسال یا در محل مشتری؛

- **Actors:** Consumer
- **Statement:** A buyer may pay a deposit at checkout and settle the balance on shipment or at the customer's location, with both amounts recorded on the order.
- **Rules / edge cases:** Deposit percentage admin-configurable per product or category; balance collection recorded as a separate payment; order releases only when fully settled.
- **Priority:** P1 · **Depends on:** REQ-PAY-006
- **Acceptance:**
  1. Order shows deposit paid and balance due
  2. Balance payment closes the receivable
  3. Release blocked while a balance is outstanding
- **Label:** CONTRACT · **Status:** Specified

### REQ-ORD-016 — Order submission through a form for consumers

- **Source:** `page 6 / پیوست ۳-۳ بند ۵`

> ثبت مناقصه و ثبت سفارش از طریق فرم؛

- **Actors:** Consumer
- **Statement:** A consumer submits an order through a structured form when the catalogue path is unsuitable, and it enters the same order pipeline.
- **Rules / edge cases:** Form orders are flagged as manual, require admin confirmation of price before payment, and support attachments.
- **Priority:** P2 · **Depends on:** REQ-ORD-004
- **Acceptance:**
  1. Form order appears in admin review
  2. Price confirmed before payment link is sent
  3. Order continues through the normal state machine
- **Label:** CONTRACT · **Status:** Specified

### REQ-ORD-017 — Supplier violation history shown at the moment of ordering

- **Source:** `page 7 / پیوست ۴ بند ۱۹`

> نمایش گزارش تخلفات عرضه‌کننده هنگام ثبت سفارش )مث الً تخلفات دو ماه گذشته( برای آگاهی همکاران؛

- **Also satisfies:** `پیوست ۵ بند ۳۴`
- **Actors:** Store, Consumer
- **Statement:** While adding a supplier's product to the cart and at checkout, the buyer sees that supplier's violation count for the recent period.
- **Rules / edge cases:** Window default two months, admin-configurable; shows count and category, not personal data; hidden when zero.
- **Priority:** P1 · **Depends on:** REQ-CMP-004
- **Acceptance:**
  1. Supplier with violations shows a warning at checkout
  2. Zero-violation supplier shows nothing
  3. Window length follows admin setting
- **Label:** CONTRACT · **Status:** Specified

### REQ-ORD-018 — Buyer confirms receipt to close the order

- **Source:** `page 11 / پیوست ۱۲ بند ۹`

> ثبت نظر و امتیاز برای محصول و عرضه‌کننده تا یک هفته پس از دریافت؛

- **Actors:** Consumer, Store
- **Statement:** The buyer confirms delivery, which closes the order, starts the review window and schedules settlement release.
- **Rules / edge cases:** Auto-confirm after a configurable number of days from the delivery event; confirmation is idempotent.
- **Priority:** P1 · **Depends on:** REQ-ORD-004
- **Acceptance:**
  1. Confirmation moves the sub-order to delivered
  2. Review form unlocks for seven days
  3. Auto-confirm fires after the configured delay
- **Label:** CONTRACT · **Status:** Specified

### REQ-ORD-019 — Shipment recorded with waybill number and courier dispatch

- **Source:** `page 10 / پیوست ۱۱ بند ۱۵`

> ثبت تأخیر ارسال کاال طبق زمانبندی سفارش با درج شماره بارنامه و ارسال با پیک؛ کسر خودکار درصد خسارت از فاکتور و واریز به کیف پول خریدار؛

- **Actors:** Supplier
- **Statement:** The supplier records dispatch with the waybill number and courier, which timestamps the shipment and starts the delivery clock.
- **Rules / edge cases:** Waybill number required and unique per carrier; dispatch after the promised date triggers the delay compensation rule (REQ-CMP-002).
- **Priority:** P0 · **Depends on:** REQ-ORD-004
- **Acceptance:**
  1. Dispatch without a waybill number is refused
  2. Shipment timestamp recorded
  3. Late dispatch triggers compensation
- **Label:** CONTRACT · **Status:** Specified

### REQ-ORD-020 — Partial fulfilment and partial cancellation per sub-order

- **Source:** `page 11 / پیوست ۱۳ بند ۱`

> گزارش دقیق و فیلترپذیر فروش برای تسویه دستی با تولیدکنندگان بر اساس بازه زمانی، مبلغ، تاریخ و نوع محصول؛

- **Actors:** Supplier, Admin
- **Statement:** A supplier can ship fewer units than ordered; the sub-order splits into a fulfilled part and a cancelled part with a proportional refund.
- **Rules / edge cases:** Refund proportional to the cancelled quantity including its share of shipping; buyer must accept partial fulfilment or cancel the remainder.
- **Priority:** P1 · **Depends on:** REQ-ORD-002
- **Acceptance:**
  1. Partial shipment creates a remainder line
  2. Refund equals the cancelled lines' value
  3. Buyer is notified and may cancel the remainder
- **Label:** CONTRACT · **Status:** Specified

<a id="inv"></a>
## INV — Invoices

Invoices: issuance, manual and supplementary invoices, numbering, attachments and per-role visibility.

### REQ-INV-001 — Invoice issued per sub-order with full line detail

- **Source:** `page 9 / پیوست ۷ بند ۲`

> مدیریت مالی: نمایش تراکنش‌های انجام‌شده، گزارش محصوالت فروخته‌شده، ایجاد فاکتور خرید دستی، جمع مبالغ و فاکتورهای کاربران؛

- **Actors:** System
- **Statement:** Paying a sub-order issues one invoice listing each line's product, quantity, unit price, discount, tax and line total, plus the seller and buyer identity.
- **Rules / edge cases:** Invoice is immutable once issued; corrections happen through a credit note (REQ-INV-006); totals must equal the sub-order total.
- **Priority:** P0 · **Depends on:** REQ-ORD-004
- **Acceptance:**
  1. Invoice lines sum to the sub-order total
  2. Editing an issued invoice is impossible
  3. Buyer and seller can both open the invoice
- **Label:** CONTRACT · **Status:** Specified

### REQ-INV-002 — Manual invoice creation by admin and by store

- **Source:** `page 9 / پیوست ۷ بند ۲`

> مدیریت مالی: نمایش تراکنش‌های انجام‌شده، گزارش محصوالت فروخته‌شده، ایجاد فاکتور خرید دستی، جمع مبالغ و فاکتورهای کاربران؛

- **Actors:** Admin, Store
- **Statement:** An admin or a store issues an invoice manually for an offline or phone sale, choosing the buyer, lines and price.
- **Rules / edge cases:** Manual invoices are flagged as such in every report; they consume stock; a store may only invoice against its own products.
- **Priority:** P1 · **Depends on:** REQ-INV-001
- **Acceptance:**
  1. Manual invoice created with customer and lines
  2. Flagged 'manual' in reports
  3. Store cannot invoice another seller's product
- **Label:** CONTRACT · **Status:** Specified

### REQ-INV-003 — Issued-invoice list per role with filters

- **Source:** `page 6 / پیوست ۳-۱ بند ۷`

> ثبت سفارش، ارسال تیکت و مشاهده فاکتورهای صادرشده؛

- **Actors:** Store, Supplier, Consumer, Admin
- **Statement:** Each role sees the invoices it is entitled to, filtered by date, status, seller and amount.
- **Rules / edge cases:** Buyer sees own invoices; seller sees invoices it issued; admin sees all; marketer sees invoices of visited stores only.
- **Priority:** P0 · **Depends on:** REQ-IAM-015
- **Acceptance:**
  1. Buyer sees only own invoices
  2. Seller sees only its issued invoices
  3. Date and status filters work
- **Label:** CONTRACT · **Status:** Specified

### REQ-INV-004 — Supplementary invoice for costs above the original invoice

- **Source:** `page 11 / پیوست ۱۱ بند ۱۷`

> امکان ثبت هزینه مازاد بر فاکتور توسط تولیدکننده و ارسال فاکتور مکمل برای خریدار؛

- **Actors:** Supplier
- **Statement:** A supplier may register an additional cost after the original invoice and issue a supplementary invoice to the buyer for that amount.
- **Rules / edge cases:** Supplementary invoice references the original; requires buyer acknowledgement before it becomes payable; capped by an admin-set percentage of the original.
- **Priority:** P1 · **Depends on:** REQ-INV-001
- **Acceptance:**
  1. Supplementary invoice links to the original
  2. Unacknowledged supplement is not payable
  3. A supplement above the cap is refused
- **Label:** CONTRACT · **Status:** Specified

### REQ-INV-005 — Printable invoice document with attachments and reverse number

- **Source:** `page 8 / پیوست ۵ بند ۴۱`

> امکان ضبط صدا و ارسال متن توضیحات هنگام ثبت هر فاکتور با دکمه پیوست، همراه با امکان پیوست عکس و فایل صوتی دو دقیقه‌ای؛

- **Actors:** Buyer, Seller
- **Statement:** Every invoice renders as a printable RTL document that includes attached notes, images and audio links.
- **Rules / edge cases:** PDF generated server-side; attachments referenced by signed URLs valid for a short time; the document shows the invoice number and Jalali date.
- **Priority:** P1 · **Depends on:** REQ-ORD-012
- **Acceptance:**
  1. PDF opens and matches the on-screen invoice
  2. Attachments are reachable from the PDF
  3. Signed links expire
- **Label:** CONTRACT · **Status:** Specified

### REQ-INV-006 — Credit note for invoice correction or cancellation

- **Source:** `page 9 / پیوست ۷ بند ۲`

> ایجاد فاکتور خرید دستی، جمع مبالغ و فاکتورهای کاربران؛

- **Actors:** Admin
- **Statement:** A wrong or cancelled invoice is corrected by a credit note that references it, leaving the original untouched.
- **Rules / edge cases:** Credit note reverses stock and ledger entries in the same transaction; a fully credited invoice is marked cancelled.
- **Priority:** P1 · **Depends on:** REQ-INV-001
- **Acceptance:**
  1. Credit note references the original invoice
  2. Stock and ledger are reversed exactly once
  3. Original remains readable
- **Label:** TECHNICAL_CONSTRAINT · **Status:** Specified

### REQ-INV-007 — Sequential gapless invoice numbering per fiscal period

- **Source:** `page 9 / پیوست ۷ بند ۲`

> ایجاد فاکتور خرید دستی، جمع مبالغ و فاکتورهای کاربران؛

- **Actors:** System
- **Statement:** Invoice numbers are sequential per year with no gaps, allocated inside the issuing transaction.
- **Rules / edge cases:** Format YYYY-NNNNNN; concurrency-safe via row lock; voided invoices keep their number and are marked void.
- **Priority:** P0 · **Depends on:** REQ-INV-001
- **Acceptance:**
  1. Concurrent issuance produces unique sequential numbers
  2. A voided invoice does not free its number
  3. No gap appears in a year's sequence
- **Label:** TECHNICAL_CONSTRAINT · **Status:** Specified

### REQ-INV-008 — Invoice prices reflect the buyer's level, never the consumer price for colleagues

- **Source:** `page 11 / پیوست ۱۲ بند ۱`

> تفاوت قیمت مصرف‌کننده با قیمت همکار، با امکان اعمال دستی توسط ادمین یا ضریب درصدی روی همه محصوالت )مث الً ۳۵ درصد باالتر(؛

- **Actors:** System
- **Statement:** The invoice prints the price list applied to that buyer, so a colleague invoice never shows the consumer spread as profit.
- **Rules / edge cases:** Invoice footer names the applied price list version; consumer invoices show the consumer price only.
- **Priority:** P1 · **Depends on:** REQ-PRI-004
- **Acceptance:**
  1. Colleague invoice shows colleague prices
  2. Price list version recorded on the invoice
  3. Consumer invoice shows no colleague figures
- **Label:** CONTRACT · **Status:** Specified

### REQ-INV-009 — Marketer views invoices of the stores they visited

- **Source:** `page 6 / پیوست ۳-۲ بند ۱۰`

> مشاهده فاکتورهای ثبت‌شده توسط همکارانی که خودش ویزیت کرده است؛

- **Actors:** Marketer
- **Statement:** A marketer sees the invoices issued to stores attributed to them, to follow up on activity and commission.
- **Rules / edge cases:** Read-only, own network only; amounts visible but buyer personal data minimised.
- **Priority:** P1 · **Depends on:** REQ-IAM-015
- **Acceptance:**
  1. Marketer sees own-network invoices
  2. Other networks are invisible
  3. No edit actions are offered
- **Label:** CONTRACT · **Status:** Specified

### REQ-INV-010 — Invoice payment state visible to both parties

- **Source:** `page 9 / پیوست ۷ بند ۲`

> مدیریت مالی: نمایش تراکنش‌های انجام‌شده، گزارش محصوالت فروخته‌شده، ایجاد فاکتور خرید دستی، جمع مبالغ و فاکتورهای کاربران؛

- **Actors:** Buyer, Seller, Admin
- **Statement:** Each invoice shows whether it is unpaid, partially paid, paid or credited, with the linked payment references.
- **Rules / edge cases:** Partial state derives from the sum of successful payments; state changes only from payment events, never manually.
- **Priority:** P1 · **Depends on:** REQ-PAY-003
- **Acceptance:**
  1. Paying updates the invoice state
  2. Partial payment shows the remaining amount
  3. Manual state change is impossible
- **Label:** CONTRACT · **Status:** Specified

### REQ-INV-011 — Aggregated amount per user for accounting review

- **Source:** `page 9 / پیوست ۷ بند ۲`

> جمع مبالغ و فاکتورهای کاربران؛

- **Actors:** Admin
- **Statement:** Admin can view per-user totals of invoiced, paid and outstanding amounts with a drill-down to each invoice.
- **Rules / edge cases:** Totals exclude credited invoices; exportable to Excel (REQ-RPT-004).
- **Priority:** P1 · **Depends on:** REQ-INV-003
- **Acceptance:**
  1. User total equals the sum of their invoices
  2. Credited invoices excluded
  3. Drill-down lists the contributing invoices
- **Label:** CONTRACT · **Status:** Specified

<a id="pay"></a>
## PAY — Payments

Payments: gateway drivers, dual-gateway choice, callback integrity, wallet and refund execution, paid services.

### REQ-PAY-001 — Gateway driver contract with admin activation and per-gateway credentials

- **Source:** `page 5 / پیوست ۱ بند ۵`

> پشتیبانی هم‌زمان از ۱۲ درگاه پرداخت بانکی مجزا و امکان استفاده هم‌زمان از دو درگاه در یک تراکنش با انتخاب کاربر؛

- **Actors:** Admin, Buyer
- **Statement:** Each bank gateway is a driver implementing one contract (initiate, verify, refund) with its own encrypted credentials and an admin on/off switch.
- **Rules / edge cases:** Inactive gateway disappears from checkout; credentials never rendered to the client; driver errors surface as a Persian failure message, not a stack trace.
- **Priority:** P0 · **Depends on:** REQ-CTR-004
- **Acceptance:**
  1. Enabling a gateway exposes it at checkout
  2. Disabling hides it immediately
  3. A driver failure produces a clean error and a failed transaction row
- **Label:** CONTRACT · **Status:** Specified

### REQ-PAY-002 — Dual-gateway payment for one transaction chosen by the buyer

- **Source:** `page 5 / پیوست ۱ بند ۵`

> پشتیبانی هم‌زمان از ۱۲ درگاه پرداخت بانکی مجزا و امکان استفاده هم‌زمان از دو درگاه در یک تراکنش با انتخاب کاربر؛

- **Actors:** Buyer, Admin
- **Statement:** Where two gateways are paired, a buyer may split one order's payment across both, choosing the amount for each.
- **Rules / edge cases:** Split is recorded as two linked payment rows summing to the order total; the order is paid only when both succeed; a failed leg leaves the order pending with a retry link.
- **Priority:** P1 · **Depends on:** REQ-PAY-001
- **Acceptance:**
  1. Two payment rows are created and linked
  2. Order flips to paid only after both succeed
  3. A failed leg offers a retry for that leg only
- **Label:** CONTRACT · **Status:** Ambiguous

### REQ-PAY-003 — Verified, idempotent gateway callback

- **Source:** `page 11 / پیوست ۱۳ بند ۷`

> اتصال ۱۲ درگاه بانکی با امکان انتخاب دو درگاه هم‌زمان و ثبت الگ کامل تراکنش‌ها.

- **Actors:** System
- **Statement:** A gateway callback is verified by signature or server-side inquiry before the payment is accepted, and repeated callbacks cannot double-credit an order.
- **Rules / edge cases:** Reference uniqueness enforced in the database; mismatch between amount and order is rejected and flagged; every callback stored raw for audit.
- **Priority:** P0 · **Depends on:** REQ-PAY-001
- **Acceptance:**
  1. Forged callback is rejected
  2. Replayed callback leaves balances unchanged
  3. Amount mismatch creates an exception record
- **Label:** CONTRACT · **Status:** Specified

### REQ-PAY-004 — Failed and returned transactions recorded for reconciliation

- **Source:** `page 11 / پیوست ۱۳ بند ۶`

> گزارش تراکنش‌های ناموفق، برگشتی و مغایرت‌گیری با درگاه‌های پرداخت؛

- **Actors:** Admin, Finance
- **Statement:** Every failed, cancelled or returned transaction is stored with its gateway code and reason, and appears in a reconciliation report against gateway statements.
- **Rules / edge cases:** Reconciliation matches by gateway reference and amount; unmatched rows require manual resolution with a note.
- **Priority:** P0 · **Depends on:** REQ-PAY-003
- **Acceptance:**
  1. Failed payment rows appear with gateway reason codes
  2. Reconciliation lists unmatched entries
  3. Resolving an exception requires a note
- **Label:** CONTRACT · **Status:** Specified

### REQ-PAY-005 — Paid notification purchase flow

- **Source:** `page 8 / پیوست ۵ بند ۱۲`

> بخش خرید نوتیفیکیشن: کاربر تعداد ارسال، متن، عکس و لینک را وارد می‌کند، مبلغ محاسبه‌شده را پرداخت می‌کند و ادمین پس از بررسی آن را از بخش نوتیفیکیشن سفارشی ارسال می‌کند؛

- **Actors:** Store, Supplier, Consumer
- **Statement:** A user enters send count, text, image and link, sees the computed price, pays it, and the request queues for admin review before sending.
- **Rules / edge cases:** Price = count × admin rate; unpaid request expires in 24 h; admin may reject with refund to wallet.
- **Priority:** P1 · **Depends on:** REQ-NTF-003
- **Acceptance:**
  1. Price updates with the send count
  2. Payment moves the request to 'pending review'
  3. Rejection refunds to the wallet
- **Label:** CONTRACT · **Status:** Specified

### REQ-PAY-006 — Wallet balance used as a payment method at checkout

- **Source:** `page 11 / پیوست ۱۲ بند ۱۳`

> پرداخت بیعانه و تسویه باقیمانده هنگام ارسال یا در محل مشتری؛

- **Actors:** Buyer
- **Statement:** A buyer may pay all or part of an order from the wallet balance, with the remainder charged to a gateway.
- **Rules / edge cases:** Wallet debit and order credit in one transaction; insufficient balance falls back to gateway for the remainder; wallet payments cannot be reversed except by refund entry.
- **Priority:** P1 · **Depends on:** REQ-FIN-001
- **Acceptance:**
  1. Full wallet payment completes without a gateway
  2. Mixed payment splits correctly
  3. Balance never goes negative
- **Label:** CONTRACT · **Status:** Specified

### REQ-PAY-007 — Refund execution to the buyer's registered SHEBA

- **Source:** `page 8 / پیوست ۵ بند ۱۵`

> امکان رد سفارش ثبت‌شده توسط فروشنده یا تولیدکننده و برگشت مبلغ پرداختی خریدار به شبا او؛

- **Actors:** Finance, System
- **Statement:** Approved refunds produce a bank transfer instruction to the buyer's registered SHEBA account and a ledger entry, with status tracked until confirmed.
- **Rules / edge cases:** SHEBA validated by IBAN check; instruction file exportable for group payment; failed transfers retried and reported.
- **Priority:** P0 · **Depends on:** REQ-FIN-004
- **Acceptance:**
  1. Refund instruction carries a valid SHEBA
  2. Ledger reflects the refund immediately
  3. Failed transfer appears in the exception report
- **Label:** CONTRACT · **Status:** Specified

### REQ-PAY-008 — SMS panel usage charged per admin tariff

- **Source:** `page 10 / پیوست ۱۱ بند ۷`

> استفاده از پنل پیامک با فیلتر و دسته‌بندی مشتریان و پرداخت کارمزد یا ارسال رایگان طبق تنظیم ادمین؛

- **Actors:** Supplier, Store
- **Statement:** Suppliers and stores use the SMS panel with audience filters; each send either deducts the admin tariff from their wallet or is free per admin setting.
- **Rules / edge cases:** Tariff per message configurable; insufficient balance blocks sending with a top-up prompt; send log retained per campaign.
- **Priority:** P1 · **Depends on:** REQ-MSG-001
- **Acceptance:**
  1. Send deducts the tariff from the wallet
  2. Free mode charges nothing but logs the send
  3. Insufficient balance blocks the send
- **Label:** CONTRACT · **Status:** Specified

### REQ-PAY-009 — Banner reservation request with paid placement

- **Source:** `page 10 / پیوست ۱۱ بند ۵`

> درخواست رزرو بنر تبلیغاتی در بازه زمانی مشخص و ارسال به ادمین؛

- **Actors:** Supplier, Admin
- **Statement:** A supplier requests a banner slot for a date range; on admin approval the slot is reserved and the placement fee is charged or invoiced.
- **Rules / edge cases:** Availability checked against overlapping reservations; approval requires payment or credit approval; slot auto-releases at end date.
- **Priority:** P2 · **Depends on:** REQ-PRM-005
- **Acceptance:**
  1. Overlapping request for the same slot is refused
  2. Approval reserves the slot for the range
  3. Banner disappears after the end date
- **Label:** CONTRACT · **Status:** Specified

### REQ-PAY-010 — Payment link issued for manually confirmed orders

- **Source:** `page 6 / پیوست ۳-۲ بند ۸`

> فرم ثبت سفارش مشتریان و پروژه‌ها؛

- **Actors:** Admin, Marketer
- **Statement:** After an admin or marketer confirms a manually created order, the buyer receives a payment link that completes the order through the normal gateway flow.
- **Rules / edge cases:** Link is single-use per order and expires; paying it triggers the same callback verification as checkout.
- **Priority:** P1 · **Depends on:** REQ-ORD-016
- **Acceptance:**
  1. Link opens the gateway for the exact amount
  2. Second use is refused
  3. Expired link shows a clear message
- **Label:** TECHNICAL_CONSTRAINT · **Status:** Specified

### REQ-PAY-011 — Retry of a failed payment without duplicating the order

- **Source:** `page 11 / پیوست ۱۳ بند ۶`

> گزارش تراکنش‌های ناموفق، برگشتی و مغایرت‌گیری با درگاه‌های پرداخت؛

- **Actors:** Buyer
- **Statement:** A buyer whose payment failed can retry from the order page; the retry creates a new payment row against the same order.
- **Rules / edge cases:** Order stays pending for a configurable hold window then expires and releases stock; multiple successful payments on one order are impossible.
- **Priority:** P1 · **Depends on:** REQ-PAY-003
- **Acceptance:**
  1. Retry succeeds and pays the order once
  2. Expired order releases stock
  3. Two successful payments cannot coexist
- **Label:** CONTRACT · **Status:** Specified

### REQ-PAY-012 — Full transaction audit trail per gateway

- **Source:** `page 11 / پیوست ۱۳ بند ۷`

> اتصال ۱۲ درگاه بانکی با امکان انتخاب دو درگاه هم‌زمان و ثبت الگ کامل تراکنش‌ها.

- **Actors:** Admin
- **Statement:** Every payment attempt stores gateway, amount, reference, request and response payload, actor and timestamps for audit.
- **Rules / edge cases:** Payloads redact card data; retention at least five years; searchable by reference and order.
- **Priority:** P0 · **Depends on:** REQ-PAY-003
- **Acceptance:**
  1. Each attempt has a searchable row
  2. Card data never stored
  3. Retention policy enforced
- **Label:** CONTRACT · **Status:** Specified

### REQ-PAY-013 — Cash or on-site settlement recorded as a payment

- **Source:** `page 11 / پیوست ۱۲ بند ۱۳`

> پرداخت بیعانه و تسویه باقیمانده هنگام ارسال یا در محل مشتری؛

- **Actors:** Supplier, Admin
- **Statement:** When the balance is collected on delivery the supplier or admin records a cash/on-site payment against the order, which closes the receivable.
- **Rules / edge cases:** Requires confirmation by both parties; recorded as a distinct payment method; no gateway fee applied.
- **Priority:** P1 · **Depends on:** REQ-ORD-015
- **Acceptance:**
  1. On-site payment closes the balance
  2. Method shown as cash in reports
  3. Party confirmation is stored
- **Label:** CONTRACT · **Status:** Specified

### REQ-PAY-014 — Gateway fee captured on every successful payment

- **Source:** `page 11 / پیوست ۱۳ بند ۶`

> گزارش تراکنش‌های ناموفق، برگشتی و مغایرت‌گیری با درگاه‌های پرداخت؛

- **Actors:** Finance
- **Statement:** Each successful payment stores the gateway fee so net settlement and the profit-share statement exclude it.
- **Rules / edge cases:** Fee rate per gateway, admin-configurable; fee recomputed never — recorded at payment time.
- **Priority:** P1 · **Depends on:** REQ-PAY-003
- **Acceptance:**
  1. Fee stored per payment
  2. Settlement report deducts fees
  3. Profit-share statement uses net amounts
- **Label:** CONTRACT · **Status:** Specified

### REQ-PAY-015 — Unpaid orders expire and release reserved stock

- **Source:** `page 5 / پیوست ۲ بند ۶`

> سبد خرید، اعمال کد تخفیف، محاسبه هزینه ارسال و صفحه پرداخت یکپارچه با درگاه بانکی؛

- **Actors:** System
- **Statement:** An order left unpaid beyond the configured hold window is cancelled automatically and its reserved stock released.
- **Rules / edge cases:** Window default 30 minutes for gateway payments, 24 hours for payment links; expiry is idempotent.
- **Priority:** P1 · **Depends on:** REQ-PAY-011
- **Acceptance:**
  1. Unpaid order cancels after the window
  2. Stock returns to available
  3. Buyer is notified
- **Label:** TECHNICAL_CONSTRAINT · **Status:** Specified

<a id="fin"></a>
## FIN — Finance and settlement

Finance: append-only ledger, wallet, statements, settlement to suppliers, compensation transfer and admin manual entries.

### REQ-FIN-001 — Wallet per user with balance derived from an append-only ledger

- **Source:** `page 11 / پیوست ۱۳ بند ۵`

> ثبت و پیگیری گردش مالی هر سطح کاربری )کیف پول، بدهکار/بستانکار، تسویه‌های انجام‌شده(؛

- **Actors:** All users
- **Statement:** Every user has a wallet whose balance is the sum of immutable ledger entries; no balance is ever written directly.
- **Rules / edge cases:** Entries carry type, amount (signed), reference, actor and timestamp; corrections are reversal entries; balance is cached but rebuildable.
- **Priority:** P0 · **Depends on:** REQ-PRI-011
- **Acceptance:**
  1. Balance equals the sum of entries in a replay test
  2. Direct balance update is impossible in code review/static check
  3. A reversal restores the previous balance exactly
- **Label:** CONTRACT · **Status:** Specified

### REQ-FIN-002 — Settlement request by store, supplier or marketer

- **Source:** `page 5 / پیوست ۳-۱ بند ۴`

> درخواست تسویه حساب و مشاهده صورتحساب و گردش مالی؛

- **Also satisfies:** `پیوست ۳-۲ بند ۲`
- **Actors:** Store, Supplier, Marketer
- **Statement:** An eligible account submits a settlement request for its available balance; finance approves and records the bank transfer.
- **Rules / edge cases:** Only settled funds are withdrawable; request is blocked while a complaint freeze is active; each request is idempotent by reference.
- **Priority:** P0 · **Depends on:** REQ-FIN-001
- **Acceptance:**
  1. Request stores amount, SHEBA and status
  2. Double submission creates one request
  3. Frozen balance cannot be requested
- **Label:** CONTRACT · **Status:** Specified

### REQ-FIN-003 — Statement and financial flow view per level

- **Source:** `page 5 / پیوست ۳-۱ بند ۴`

> درخواست تسویه حساب و مشاهده صورتحساب و گردش مالی؛

- **Actors:** Store, Supplier, Marketer, Admin
- **Statement:** Each account sees a statement listing sales, commissions, fees, compensations, payouts and the running balance for any date range.
- **Rules / edge cases:** Statement reconciles to the ledger to the Toman; exportable; Jalali dates displayed with Gregorian storage.
- **Priority:** P0 · **Depends on:** REQ-FIN-001
- **Acceptance:**
  1. Statement total equals ledger sum for the range
  2. Export produces a file matching the screen
  3. Date range filtering is exact
- **Label:** CONTRACT · **Status:** Specified

### REQ-FIN-004 — Debtor and creditor positions tracked per account

- **Source:** `page 11 / پیوست ۱۳ بند ۵`

> ثبت و پیگیری گردش مالی هر سطح کاربری )کیف پول، بدهکار/بستانکار، تسویه‌های انجام‌شده(؛

- **Actors:** Finance
- **Statement:** Each account shows whether it is a debtor or creditor, with the open receivable/payable amounts and their due dates.
- **Rules / edge cases:** Receivable = invoiced but unpaid; payable = earned but unsettled; ageing buckets 30/60/90 days.
- **Priority:** P1 · **Depends on:** REQ-FIN-001
- **Acceptance:**
  1. Position sign matches ledger arithmetic
  2. Ageing buckets sum to the open amount
  3. Fully settled account shows zero
- **Label:** CONTRACT · **Status:** Specified

### REQ-FIN-005 — Admin manual charge or credit on a supplier account

- **Source:** `page 11 / پیوست ۱۱ بند ۱۶`

> امکان اعمال هزینه یا پرداخت جدید در حساب عرضه‌کننده توسط ادمین به‌صورت دستی؛

- **Actors:** Admin
- **Statement:** An admin applies a manual debit or credit to a supplier's account with a mandatory reason and supporting note.
- **Rules / edge cases:** Creates a ledger entry, never edits existing ones; requires a second admin approval above a threshold; appears on the supplier statement.
- **Priority:** P1 · **Depends on:** REQ-FIN-001
- **Acceptance:**
  1. Manual entry appears on the statement
  2. Reason is mandatory
  3. Above-threshold entry needs a second approver
- **Label:** CONTRACT · **Status:** Specified

### REQ-FIN-006 — Supplier payout held until delivery is confirmed and complaints resolved

- **Source:** `page 11 / پیوست ۱۳ بند ۱`

> گزارش دقیق و فیلترپذیر فروش برای تسویه دستی با تولیدکنندگان بر اساس بازه زمانی، مبلغ، تاریخ و نوع محصول؛

- **Actors:** Finance
- **Statement:** A sub-order's amount becomes payable to the supplier only after delivery confirmation and after any complaint window or freeze has closed.
- **Rules / edge cases:** Hold period admin-configurable; freeze from REQ-ORD-010 overrides the schedule; release is a ledger entry.
- **Priority:** P0 · **Depends on:** REQ-ORD-018, REQ-ORD-010
- **Acceptance:**
  1. Undelivered sub-order has no payable
  2. Freeze blocks release
  3. Release posts a ledger entry with the sub-order reference
- **Label:** CONTRACT · **Status:** Specified

### REQ-FIN-007 — Daily purchased-products payment report to supplier and admin

- **Source:** `page 11 / پیوست ۱۳ بند ۲`

> ارسال گزارش پرداخت روزانه محصوالت خریداری‌شده به تولیدکننده یا عرضه‌کننده و ادمین؛

- **Actors:** Finance, Supplier
- **Statement:** A daily report of purchased products and their payments is sent to each supplier and to admin.
- **Rules / edge cases:** Delivered as notification plus downloadable file; per-supplier scoping; generated after the daily cut-off.
- **Priority:** P1 · **Depends on:** REQ-FIN-006
- **Acceptance:**
  1. Report is generated daily and delivered
  2. Each supplier sees only its rows
  3. Totals match that day's invoices
- **Label:** CONTRACT · **Status:** Specified

### REQ-FIN-008 — Excel export for group bank payment to suppliers

- **Source:** `page 11 / پیوست ۱۳ بند ۳`

> خروجی اکسل برای ارائه به بانک جهت پرداخت گروهی به عرضه‌کنندگان در تاریخ‌های معین؛

- **Actors:** Finance
- **Statement:** Finance exports a bank-format Excel file of supplier payouts for a chosen date, ready for group payment submission.
- **Rules / edge cases:** Columns match the bank template; IBAN validated; totals reconcile to the settlement batch; export is logged.
- **Priority:** P0 · **Depends on:** REQ-FIN-006
- **Acceptance:**
  1. File opens in Excel with the bank's columns
  2. Batch total equals the sum of rows
  3. Invalid SHEBA rows are excluded and listed
- **Label:** CONTRACT · **Status:** Specified

### REQ-FIN-009 — Delay compensation deducted from the supplier and credited to the buyer's wallet

- **Source:** `page 10 / پیوست ۱۱ بند ۱۵`

> کسر خودکار درصد خسارت از فاکتور و واریز به کیف پول خریدار؛

- **Actors:** System
- **Statement:** When a shipment is late beyond the promised date, the configured compensation percentage is deducted from the supplier's payable and credited to the buyer's wallet automatically.
- **Rules / edge cases:** Percentage admin-set per contract; computed once per sub-order; capped so the supplier payable never goes negative without a flag.
- **Priority:** P1 · **Depends on:** REQ-CMP-002, REQ-FIN-001
- **Acceptance:**
  1. Late sub-order produces a debit and a credit of equal value
  2. Buyer wallet increases immediately
  3. Repeated job run does not double-credit
- **Label:** CONTRACT · **Status:** Specified

### REQ-FIN-010 — Marketer commission payable accrued per attributed invoice

- **Source:** `page 11 / پیوست ۱۳ بند ۱`

> گزارش دقیق و فیلترپذیر فروش برای تسویه دستی با تولیدکنندگان بر اساس بازه زمانی، مبلغ، تاریخ و نوع محصول؛

- **Actors:** Finance, Marketer
- **Statement:** Each invoice belonging to an attributed store accrues a commission payable to that marketer according to the active plan.
- **Rules / edge cases:** Accrual is a ledger entry at invoice issuance; reversal on credit note; plan change applies only to future invoices.
- **Priority:** P1 · **Depends on:** REQ-MKT-002
- **Acceptance:**
  1. Invoice accrues exactly one commission entry
  2. Credit note reverses it
  3. Plan change does not rewrite history
- **Label:** CONTRACT · **Status:** Specified

### REQ-FIN-011 — Payments above the purchase price credited to the supplier account

- **Source:** `page 8 / پیوست ۵ بند ۱۸`

> فعال‌سازی بخش مصرف‌کننده با امکان رویت قیمت مصرف‌کننده و امکان پرداخت مبلغ باالتر از قیمت خرید به تأمین‌کننده در بخش حسابداری؛

- **Actors:** Finance
- **Statement:** When a consumer pays more than the colleague price, the difference is credited to the supplier's account as a receivable settlement in accounting.
- **Rules / edge cases:** Difference computed per line; posted to the supplier ledger at payment settlement, not at order creation.
- **Priority:** P1 · **Depends on:** REQ-FIN-001
- **Acceptance:**
  1. Consumer order credits the price difference to the supplier
  2. Difference shown on the supplier statement
  3. No difference is posted for colleague orders
- **Label:** CONTRACT · **Status:** Specified

### REQ-FIN-012 — Periodic settlement run with producers and suppliers

- **Source:** `page 11 / پیوست ۱۳ بند ۱`

> گزارش دقیق و فیلترپذیر فروش برای تسویه دستی با تولیدکنندگان بر اساس بازه زمانی، مبلغ، تاریخ و نوع محصول؛

- **Actors:** Finance
- **Statement:** Finance runs a settlement cycle for a date range, reviews a per-supplier summary, approves it and posts payouts in one batch.
- **Rules / edge cases:** Cycle is idempotent per (supplier, period); approved batches are immutable; partial approval allowed per supplier.
- **Priority:** P1 · **Depends on:** REQ-FIN-008
- **Acceptance:**
  1. Summary lists amount per supplier for the range
  2. Approval posts one payout entry per supplier
  3. Re-running the same period creates no duplicates
- **Label:** CONTRACT · **Status:** Specified

### REQ-FIN-013 — Wallet credit granted by admin with expiry support

- **Source:** `page 6 / پیوست ۴ بند ۷`

> در صورت رسیدن خریدار به امتیاز مشخص، پاداش نقدی مازاد در کیف پول او ثبت می‌شود و افرصً برای خرید و ثبت فاکتور جدید در سایت قابل استفاده است؛ این پاداش می‌تواند مدت‌دار یا دائمی باشد؛

- **Actors:** Admin, System
- **Statement:** Wallet credits can be created by admin or by a loyalty rule and may carry an expiry date after which the credit is reversed.
- **Rules / edge cases:** Expiry job posts a reversal entry; credits are spendable only on new purchases; a per-credit reference prevents double spend.
- **Priority:** P1 · **Depends on:** REQ-FIN-001
- **Acceptance:**
  1. Expiring credit disappears at its date with a reversal entry
  2. Expired credit cannot be spent
  3. Admin can issue a permanent credit
- **Label:** CONTRACT · **Status:** Specified

### REQ-FIN-014 — Idempotent financial commands with unique references

- **Source:** `page 11 / پیوست ۱۳ بند ۵`

> ثبت و پیگیری گردش مالی هر سطح کاربری )کیف پول، بدهکار/بستانکار، تسویه‌های انجام‌شده(؛

- **Actors:** Engineering
- **Statement:** Every financial command requires a unique reference and is safe to retry, so a duplicated job or webhook cannot move money twice.
- **Rules / edge cases:** Unique index on (type, reference); duplicate submission returns the original result; tests cover retry paths.
- **Priority:** P0 · **Depends on:** REQ-FIN-001
- **Acceptance:**
  1. Replaying a command leaves balances unchanged
  2. Unique constraint exists in the schema
  3. Retry tests pass
- **Label:** TECHNICAL_CONSTRAINT · **Status:** Specified

### REQ-FIN-015 — Receivables ageing and outstanding balance report

- **Source:** `page 11 / پیوست ۱۳ بند ۵`

> ثبت و پیگیری گردش مالی هر سطح کاربری )کیف پول، بدهکار/بستانکار، تسویه‌های انجام‌شده(؛

- **Actors:** Finance
- **Statement:** A report lists outstanding receivables and payables by account and ageing bucket, with a drill-down to the source invoices.
- **Rules / edge cases:** Buckets 0-30, 31-60, 61-90, 90+; excludes credited documents; exportable.
- **Priority:** P2 · **Depends on:** REQ-FIN-004
- **Acceptance:**
  1. Buckets sum to the open total
  2. Drill-down lists contributing documents
  3. Export matches the screen
- **Label:** CONTRACT · **Status:** Specified

### REQ-FIN-016 — Negative-balance guard on supplier payouts

- **Source:** `page 11 / پیوست ۱۱ بند ۱۶`

> امکان اعمال هزینه یا پرداخت جدید در حساب عرضه‌کننده توسط ادمین به‌صورت دستی؛

- **Actors:** System
- **Statement:** A payout never exceeds the account's available balance; any deficit from compensations is carried forward as a debt on the next settlement.
- **Rules / edge cases:** Payout clamped to available balance; debt visible on the statement and in the ageing report.
- **Priority:** P1 · **Depends on:** REQ-FIN-009
- **Acceptance:**
  1. Payout larger than the balance is refused or clamped
  2. Deficit carries to the next period
  3. Statement shows the carried debt
- **Label:** TECHNICAL_CONSTRAINT · **Status:** Specified

<a id="mkt"></a>
## MKT — Field sales (marketers)

Field-sales (marketer) operations: plans, commissions, targets, network reporting and referral bonuses.

### REQ-MKT-001 — Marketer panel scoped to the marketer's own network

- **Source:** `page 9 / پیوست ۶ بند ۱۰`

> رخداد اطالعات و گزارش‌گیری از فروشگاه‌ها و مشتریان ویزیت‌شده افرصً با تأیید و تنظیم ادمین و دسترسی محدود به اطالعات فروشگاه‌های مربوط؛

- **Actors:** Marketer
- **Statement:** A marketer works in a panel that lists only the stores, customers and orders attributed to them, with reporting on visits and outcomes.
- **Rules / edge cases:** Attribution is immutable; admin can reassign a store which moves future activity to the new marketer; historical commission stays with the original.
- **Priority:** P0 · **Depends on:** REQ-IAM-015
- **Acceptance:**
  1. Panel lists only attributed stores
  2. Reassignment moves future invoices only
  3. Another marketer's store returns 403
- **Label:** CONTRACT · **Status:** Specified

### REQ-MKT-002 — Four commission models: sales percentage, fixed per visit, hybrid, fixed salary

- **Source:** `page 9 / پیوست ۶ بند ۳`

> پرداخت به بازاریاب به‌صورت پورسانت فروش افراد ویزیت‌شده، هزینه ثابت در ازای ویزیت، ترکیب هر دو یا حقوق ثابت؛

- **Actors:** Admin, Marketer
- **Statement:** Admin configures a marketer's remuneration as sales-percentage, fixed fee per visit, a combination of both, or a fixed salary.
- **Rules / edge cases:** Model is per marketer per period; a period cannot mix two models; changes apply from the next period.
- **Priority:** P0 · **Depends on:** REQ-FIN-010
- **Acceptance:**
  1. Each model produces the expected payable for a test period
  2. Switching models mid-period is refused
  3. Statement shows the model used
- **Label:** CONTRACT · **Status:** Specified

### REQ-MKT-003 — Marketer plan tiers A/B/C/D with distinct commission methods

- **Source:** `page 9 / پیوست ۶ بند ۵`

> چند مدل بازاریاب با چند روش دریافت پورسانت )پلن‌های D/C/B/A )و امکان استفاده همکاران خریدار از همان پلن‌ها؛

- **Actors:** Admin, Marketer
- **Statement:** Four plan tiers define different commission rates and conditions, and each marketer is assigned one plan.
- **Rules / edge cases:** Plan parameters are data (rate, visit fee, target); a plan change is effective-dated and audited.
- **Priority:** P1 · **Depends on:** REQ-MKT-002
- **Acceptance:**
  1. Four plans exist and are assignable
  2. Rate change applies from its effective date
  3. Assignment is visible to the marketer
- **Label:** CONTRACT · **Status:** Ambiguous

### REQ-MKT-004 — Colleague buyers can subscribe to the same commission plans

- **Source:** `page 9 / پیوست ۶ بند ۵`

> و امکان استفاده همکاران خریدار از همان پلن‌ها؛

- **Actors:** Admin, Store
- **Statement:** A colleague buyer can be enrolled in the same plan tiers as marketers, earning the plan's benefits on its own purchases or referrals.
- **Rules / edge cases:** Enrolment requires admin approval; the same plan engine applies; a user cannot hold two conflicting plans.
- **Priority:** P2 · **Depends on:** REQ-MKT-003
- **Acceptance:**
  1. Store enrolled in plan A receives plan benefits
  2. Two plans cannot be active simultaneously
  3. Benefits appear on the statement
- **Label:** CONTRACT · **Status:** Ambiguous

### REQ-MKT-005 — Commission release conditioned on activity and points targets

- **Source:** `page 9 / پیوست ۶ بند ۶`

> مشروط کردن دریافت پورسانت، حقوق یا کارمزد به فعالیت و کسب امتیاز مشخص در پلن )مث الً پلن A برای بازاریاب نوع B)؛

- **Actors:** Admin, System
- **Statement:** A plan may require a minimum activity or points threshold before its commission, salary or fee is released.
- **Rules / edge cases:** Threshold evaluated per period; unmet target withholds the payment as a pending accrual, not a deletion; admin may override with a reason.
- **Priority:** P1 · **Depends on:** REQ-MKT-003
- **Acceptance:**
  1. Below-threshold period shows withheld commission
  2. Meeting the threshold releases it automatically
  3. Override requires a written reason
- **Label:** CONTRACT · **Status:** Specified

### REQ-MKT-006 — Admin can disable a marketer's commission or change their level

- **Source:** `page 7 / پیوست ۴ بند ۲۲`

> امکان غیرفعال‌سازی پورسانت بازاریاب یا تغییر سطح کاربری او؛

- **Actors:** Admin
- **Statement:** Admin can switch off a marketer's commission or change their user level, with immediate effect on future accruals.
- **Rules / edge cases:** Existing accrued amounts remain payable unless explicitly voided; change is audited; the marketer is notified.
- **Priority:** P1 · **Depends on:** REQ-FIN-010
- **Acceptance:**
  1. Disabling stops new accruals
  2. Accrued balance stays payable
  3. Change recorded in the audit log
- **Label:** CONTRACT · **Status:** Specified

### REQ-MKT-007 — Commission or payout can be cut when points conditions are not met

- **Source:** `page 8 / پیوست ۵ بند ۱۹`

> امکان دادن امتیاز به بازاریاب در ازای ثبت سفارش در بخش مصرف‌کننده؛ در صورت عدم وصول شرایط امتیازی، امکان قطع پورسانت یا پرداختی بازاریاب؛

- **Actors:** Admin
- **Statement:** When a marketer fails the points condition of their plan, admin can suspend or cut the commission or payout for that period.
- **Rules / edge cases:** Cut produces a negative adjustment entry referencing the original accrual; reason mandatory; reversible by admin.
- **Priority:** P1 · **Depends on:** REQ-MKT-005
- **Acceptance:**
  1. Cut reduces the payable with a traceable entry
  2. Reason is stored
  3. Restoring creates a compensating entry
- **Label:** CONTRACT · **Status:** Specified

### REQ-MKT-008 — Points awarded to a marketer for orders registered in the consumer section

- **Source:** `page 8 / پیوست ۵ بند ۱۹`

> امکان دادن امتیاز به بازاریاب در ازای ثبت سفارش در بخش مصرف‌کننده؛

- **Actors:** Marketer
- **Statement:** A marketer earns points for each consumer-section order they register, according to the loyalty rule set.
- **Rules / edge cases:** One award per order; reversed if the order is cancelled; counted toward the plan target.
- **Priority:** P1 · **Depends on:** REQ-LOY-001
- **Acceptance:**
  1. Registered order credits the marketer's points
  2. Cancellation reverses the award
  3. Points count toward the plan threshold
- **Label:** CONTRACT · **Status:** Specified

### REQ-MKT-009 — Commission assigned to marketers who registered invoices

- **Source:** `page 8 / پیوست ۵ بند ۱۸`

> اختصاص پورسانت مشخص به بازاریابانی که ثبت فاکتور کرده‌اند؛

- **Actors:** Admin, System
- **Statement:** A defined commission is attributed to the marketer who registered the invoice, independent of the sales-percentage plan.
- **Rules / edge cases:** Fixed amount per invoice configurable; paid once per invoice; visible as a separate statement line.
- **Priority:** P1 · **Depends on:** REQ-FIN-010
- **Acceptance:**
  1. Invoice creates the fixed commission line
  2. Only one such line per invoice
  3. Amount follows the admin setting
- **Label:** CONTRACT · **Status:** Specified

### REQ-MKT-010 — Invitation bonus points for bringing in a new colleague

- **Source:** `page 7 / پیوست ۴ بند ۱۳`

> با ورود هر همکار جدید از طریق لینک ارسالی یک همکار دیگر، امتیاز مشخصی به فرد دعوت‌کننده افزوده می‌شود؛

- **Actors:** Store, Marketer
- **Statement:** When a new colleague joins through an existing colleague's invitation link, a configured number of points is credited to the inviter.
- **Rules / edge cases:** One bonus per new member; self-invitation blocked; bonus reversed if the new account is deleted within a fraud window.
- **Priority:** P1 · **Depends on:** REQ-LOY-001
- **Acceptance:**
  1. New signup through the link credits the inviter
  2. Same device/self signup earns nothing
  3. Deletion within 30 days reverses the bonus
- **Label:** CONTRACT · **Status:** Specified

### REQ-MKT-011 — Visit records with admin approval and limited data access

- **Source:** `page 8 / پیوست ۵ بند ۲۹`

> امکان ثبت اطالعات و گزارش‌گیری از فروشگاه‌ها و مشتریان ویزیت‌شده با تأیید ادمین و دسترسی محدود بازاریاب به اطالعات فروشگاه‌های مربوط به خود؛

- **Actors:** Marketer, Admin
- **Statement:** A visit record captures date, location, outcome, photos and notes; the store's data becomes fully visible only after admin approval.
- **Rules / edge cases:** Unapproved visits are visible to the marketer only; approval unlocks reporting; rejected visits keep the audit trail.
- **Priority:** P1 · **Depends on:** REQ-GEO-001
- **Acceptance:**
  1. Visit saved with location and outcome
  2. Store data unlocked after approval
  3. Rejected visit remains logged
- **Label:** CONTRACT · **Status:** Specified

### REQ-MKT-012 — Marketer files complaints and violation reports to admin

- **Source:** `page 6 / پیوست ۳-۲ بند ۷`

> ثبت و ارسال گزارش شکایات به ادمین؛

- **Actors:** Marketer
- **Statement:** A marketer files a complaint or violation report about a store or supplier, which routes to the admin moderation queue with the evidence.
- **Rules / edge cases:** Attachments allowed; status visible to the reporter; resolution note required from admin.
- **Priority:** P1 · **Depends on:** REQ-ADM-014
- **Acceptance:**
  1. Report reaches the admin queue
  2. Reporter sees status changes
  3. Resolution requires a note
- **Label:** CONTRACT · **Status:** Specified

### REQ-MKT-013 — Concurrent marketer and consumer activity for one person

- **Source:** `page 11 / پیوست ۱۲ بند ۱۱`

> فعالیت همزمان در نقش بازاریاب و مصرف‌کننده؛

- **Actors:** Marketer
- **Statement:** A person may act as a marketer and as a consumer at the same time, switching context without a second account.
- **Rules / edge cases:** Conflicts with پیوست ۷ بند ۶'s example that a store cannot be a marketer (AMB-IAM-001); requires role switching and separate scopes.
- **Priority:** P1 · **Depends on:** REQ-IAM-005
- **Acceptance:**
  1. Same account shows both panels
  2. Switching does not leak marketer scope into consumer views
  3. Commission still accrues correctly
- **Label:** CONTRACT · **Status:** Conflicting

### REQ-MKT-014 — Marketer reports on active and inactive stores by invoice window

- **Source:** `page 8 / پیوست ۵ بند ۳۰`

> امکان مشاهده و گزارش‌گیری از فروشگاه‌های فعال و غیرفعال بر مبنای فاکتورهای سفارش داده‌شده در بازه تعیین‌شده توسط ادمین؛

- **Actors:** Marketer, Admin
- **Statement:** The marketer sees which of their stores are active or inactive based on invoices issued in the admin-defined window, and exports the list.
- **Rules / edge cases:** Window configurable; classification recomputed nightly; export includes last invoice date.
- **Priority:** P1 · **Depends on:** REQ-MER-011
- **Acceptance:**
  1. List classifies each store correctly
  2. Changing the window reclassifies
  3. Export matches the on-screen list
- **Label:** CONTRACT · **Status:** Specified

### REQ-MKT-015 — Marketer performance dashboard

- **Source:** `page 9 / پیوست ۶ بند ۱۱`

> مشاهده و گزارش‌گیری از فروشگاه‌های فعال و غیرفعال بر مبنای فاکتورهای سفارش داده‌شده در بازه تعیین‌شده؛

- **Actors:** Marketer
- **Statement:** The panel summarises visits, registrations, active stores, invoiced volume and earned commission for a chosen period.
- **Rules / edge cases:** Numbers derive from the same queries as reports (no separate aggregates that can drift); period comparison allowed.
- **Priority:** P2 · **Depends on:** REQ-MKT-014
- **Acceptance:**
  1. Dashboard totals match the underlying lists
  2. Period selector works
  3. No data from other marketers appears
- **Label:** CONTRACT · **Status:** Specified

### REQ-MKT-016 — Marketer registration of stores and customers in person

- **Source:** `page 9 / پیوست ۶ بند ۱۲`

> امکان گرفتن عکس و افزودن به فرم ثبت‌نام مشتریان توسط خود مشتری و بازاریاب با قابلیت ویرایش در پروفایل.

- **Actors:** Marketer
- **Statement:** The marketer registers a customer in person, including a photo, and the customer can later edit their profile.
- **Rules / edge cases:** Customer receives credentials by SMS; duplicate mobile blocked; photo optional but recommended.
- **Priority:** P1 · **Depends on:** REQ-USR-009
- **Acceptance:**
  1. Customer created with photo and mobile
  2. Credentials delivered by SMS
  3. Customer can edit the profile later
- **Label:** CONTRACT · **Status:** Specified

<a id="cmp"></a>
## CMP — Compensation and violations

Compensation and violation handling: delay damages, warning reports, negative points and approval workflow.

### REQ-CMP-001 — Delay compensation paid to the colleague when shipment is late

- **Source:** `page 7 / پیوست ۴ بند ۱۴`

> امکان پرداخت خسارت به همکار در صورت تأخیر در ارسال وجود دارد؛ درصد یا مبلغ خسارت توسط ادمین تأیید می‌شود؛

- **Actors:** Admin, Store, Supplier
- **Statement:** A store whose shipment is late is entitled to compensation; the percentage or amount is confirmed by an admin before it is paid.
- **Rules / edge cases:** Claim window opens at the promised ship date and closes after delivery; compensation is a ledger credit to the store and a debit to the supplier.
- **Priority:** P1 · **Depends on:** REQ-FIN-001
- **Acceptance:**
  1. Late shipment creates a compensation claim
  2. Payment occurs only after admin confirmation
  3. Supplier account is debited by the same amount
- **Label:** CONTRACT · **Status:** Specified

### REQ-CMP-002 — Automatic delay compensation computed on the promised ship date

- **Source:** `page 11 / پیوست ۱۲ بند ۸`

> پرداخت خسارت تأخیر ارسال به‌صورت خودکار با کسر از عرضه‌کننده؛

- **Actors:** System
- **Statement:** The system detects a shipment past its promised date, computes the configured compensation and applies it automatically without manual entry.
- **Rules / edge cases:** Percentage from the compensation rule set; one compensation per sub-order; suspended while an incomplete-order flag is open.
- **Priority:** P0 · **Depends on:** REQ-ORD-019
- **Acceptance:**
  1. Late sub-order triggers automatic compensation
  2. Duplicate job run does not double-apply
  3. Incomplete-flagged order does not trigger it
- **Label:** CONTRACT · **Status:** Specified

### REQ-CMP-003 — Warning button on every invoice to report a distributor or producer violation

- **Source:** `page 7 / پیوست ۴ بند ۱۸`

> وجود دکمه وارن )اخطار( در هر فاکتور برای ثبت تخلفات توزیع‌کننده یا تولیدکننده و انتقال آن به ادمین؛

- **Actors:** Buyer
- **Statement:** Every invoice carries a warning button that lets the buyer record a distributor or producer violation and send it to admin.
- **Rules / edge cases:** Requires a category, description and optional evidence; one warning per invoice per buyer; admin decides uphold or dismiss.
- **Priority:** P1 · **Depends on:** REQ-INV-001
- **Acceptance:**
  1. Warning button present on every invoice
  2. Submission reaches admin with invoice context
  3. Duplicate warning on the same invoice is refused
- **Label:** CONTRACT · **Status:** Specified

### REQ-CMP-004 — Violation score on the producer profile

- **Source:** `page 8 / پیوست ۵ بند ۱۴`

> وجود بخش وارن در پروفایل تولیدکنندگان برای گزارش تخلف؛ وجود امتیاز منفی »امتیاز تخلف« که با تأیید ادمین نمایش داده می‌شود؛

- **Actors:** Visitor, Admin
- **Statement:** Producers' profiles carry a warning section and a negative violation score that becomes public only after admin approval.
- **Rules / edge cases:** Score = sum of upheld violations; pending reports do not affect the score; score decays after a configurable period.
- **Priority:** P1 · **Depends on:** REQ-CMP-003
- **Acceptance:**
  1. Upheld violation increases the public score
  2. Pending report leaves the score unchanged
  3. Score visible on the producer page
- **Label:** CONTRACT · **Status:** Specified

### REQ-CMP-005 — Automatic negative points for repeated misuse of the incomplete-order button

- **Source:** `page 10 / پیوست ۱۱ بند ۱۳`

> امتیاز منفی خودکار برای استفاده نادرست و مکرر از دکمه ثبت ناقص؛

- **Actors:** System
- **Statement:** When a supplier marks orders incomplete repeatedly and admin dismisses those flags, automatic negative points are applied to the supplier.
- **Rules / edge cases:** Threshold count per period configurable; each deduction is a ledger-visible points entry with reason; an admin can reset.
- **Priority:** P1 · **Depends on:** REQ-ORD-007, REQ-LOY-001
- **Acceptance:**
  1. Three dismissed flags in a month deduct the configured points
  2. Deduction appears in the points history
  3. Admin reset restores the balance
- **Label:** CONTRACT · **Status:** Specified

### REQ-CMP-006 — Admin approval queue for compensation amounts

- **Source:** `page 7 / پیوست ۴ بند ۱۴`

> درصد یا مبلغ خسارت توسط ادمین تأیید می‌شود؛

- **Actors:** Admin
- **Statement:** All compensation claims wait in an admin queue where the amount can be adjusted, approved or rejected with a reason.
- **Rules / edge cases:** Rejection notifies the claimant; approved claims post immediately to the ledger; decisions are audited.
- **Priority:** P1 · **Depends on:** REQ-CMP-001
- **Acceptance:**
  1. Queue lists open claims with amount
  2. Approval posts the ledger entry
  3. Rejection requires a reason
- **Label:** CONTRACT · **Status:** Specified

### REQ-CMP-007 — Penalty waived when the supplier flags the order incomplete or out of stock

- **Source:** `page 8 / پیوست ۵ بند ۱۵`

> دکمه عدم تکمیل سفارش برای تأمین‌کننده تا از پرداخت خسارت تأخیر جلوگیری شود؛

- **Actors:** Supplier
- **Statement:** A timely incomplete-order or rejection flag suspends the delay penalty for that sub-order until the buyer resolves it.
- **Rules / edge cases:** Flag must be raised before the promised ship date; repeated flags are reviewed for misuse (REQ-CMP-005).
- **Priority:** P1 · **Depends on:** REQ-ORD-007
- **Acceptance:**
  1. Flagged order accrues no compensation
  2. Late flag after ship date does not waive
  3. Resolution restarts the clock
- **Label:** CONTRACT · **Status:** Specified

### REQ-CMP-008 — Violation history window shown to buyers for a recent period

- **Source:** `page 7 / پیوست ۴ بند ۱۹`

> نمایش گزارش تخلفات عرضه‌کننده هنگام ثبت سفارش )مث الً تخلفات دو ماه گذشته( برای آگاهی همکاران؛

- **Actors:** Buyer, Admin
- **Statement:** Violations are counted over a rolling recent window (default two months) and that count is what buyers are shown.
- **Rules / edge cases:** Window length admin-configurable per violation category; upheld only; expired violations drop out automatically.
- **Priority:** P2 · **Depends on:** REQ-CMP-004
- **Acceptance:**
  1. Count reflects only the window
  2. Aged violation disappears
  3. Admin can change the window
- **Label:** CONTRACT · **Status:** Specified

### REQ-CMP-009 — Compensation rules configured per supplier or category

- **Source:** `page 7 / پیوست ۴ بند ۱۴`

> درصد یا مبلغ خسارت توسط ادمین تأیید می‌شود؛

- **Actors:** Admin
- **Statement:** Admin defines the delay compensation percentage or fixed amount per supplier, category or globally, and the most specific rule wins.
- **Rules / edge cases:** Precedence supplier → category → global; changes are effective-dated; historical claims keep the rule they were computed with.
- **Priority:** P1 · **Depends on:** REQ-CMP-002
- **Acceptance:**
  1. Supplier-specific rule overrides the global one
  2. Past claim keeps its original percentage
  3. Effective-dated change applies to new claims only
- **Label:** CONTRACT · **Status:** Specified

<a id="loy"></a>
## LOY — Loyalty, points and rewards

Loyalty engine: points accrual rules, volume coefficients, reward conversion, deductions and tiered discounts.

### REQ-LOY-001 — Points per invoice computed from amount, product type and purchase volume

- **Source:** `page 6 / پیوست ۴ بند ۱`

> امتیاز هر فاکتور بر اساس مبلغ فاکتور، نوع محصول داخل آن و حجم خرید محاسبه می‌شود؛

- **Actors:** System
- **Statement:** Each invoice accrues points from its amount, the product type of each line and the purchase volume, using the active rule set.
- **Rules / edge cases:** Rule set is versioned with effective dates; computation is reproducible by replaying the rule set against the invoice.
- **Priority:** P0 · **Depends on:** REQ-INV-001
- **Acceptance:**
  1. Invoice points equal the sum of line computations
  2. Replay with the same rule version yields the same number
  3. Rule change affects only new invoices
- **Label:** CONTRACT · **Status:** Specified

### REQ-LOY-002 — Per-product point rate varying by producer and volume, summed per invoice

- **Source:** `page 6 / پیوست ۴ بند ۲`

> امتیاز هر محصول بسته به نوع محصول، نام تولیدکننده یا عرضه‌کننده و حجم خرید متفاوت است و مجموع امتیاز فاکتور از جمع این اقالم به‌دست می‌آید؛

- **Also satisfies:** `پیوست ۱۳ بند ۴`
- **Actors:** System, Admin
- **Statement:** The points for each product depend on its type, its producer/supplier and the purchased volume; the invoice total is the sum of these line values.
- **Rules / edge cases:** Producer-specific override beats the product-type rate; rounding to whole points per line then summed.
- **Priority:** P0 · **Depends on:** REQ-LOY-001
- **Acceptance:**
  1. Two producers with different overrides yield different line points
  2. Invoice total equals the line sum
  3. Rounding is applied per line, consistently
- **Label:** CONTRACT · **Status:** Specified

### REQ-LOY-003 — Volume coefficient set of 0.75, 0.5, 0.25 and 0.2 percent

- **Source:** `page 6 / پیوست ۴ بند ۳`

> ضریب‌های امتیاز خرید حجمی: ۰٫۷۵ درصد، ۰٫۵ درصد، ۰٫۲۵ درصد و ۰٫۲ درصد — بر اساس حجم خرید و مطابق تنظیم ادمین؛

- **Actors:** Admin, System
- **Statement:** Volume purchases use one of four coefficients — 0.75%, 0.5%, 0.25%, 0.2% — selected by volume band and editable by admin.
- **Rules / edge cases:** Bands are threshold-based; exactly one coefficient applies per line; coefficients are stored as basis points to avoid float error.
- **Priority:** P0 · **Depends on:** REQ-LOY-002
- **Acceptance:**
  1. Each band maps to its coefficient
  2. Admin change applies to new invoices
  3. Coefficient arithmetic uses basis points
- **Label:** CONTRACT · **Status:** Specified

### REQ-LOY-004 — Reward offset on the next invoice requires four times the reward value

- **Source:** `page 6 / پیوست ۴ بند ۴`

> پاداش تعیین‌شده روی هر محصول در فاکتور بعدی فقط زمانی کسر می‌شود که مبلغ فاکتور بعدی حداقل چهار برابر مبلغ پاداش باشد؛ در غیر این صورت افرصً نیمی از پاداش به‌عنوان تخفیف کسر می‌گردد؛

- **Actors:** System
- **Statement:** A product-level reward is deducted from the next invoice only when that invoice is at least four times the reward; otherwise exactly half of the reward is deducted as a discount.
- **Rules / edge cases:** The unused remainder stays available; the 4x test uses the invoice amount before other discounts; the rule is evaluated per reward, not per invoice.
- **Priority:** P0 · **Depends on:** REQ-LOY-001
- **Acceptance:**
  1. Invoice at 4x deducts the full reward
  2. Invoice below 4x deducts half
  3. Remainder is retained for a later invoice
- **Label:** CONTRACT · **Status:** Specified

### REQ-LOY-005 — Points always computed and retained even when the reward is not claimed

- **Source:** `page 6 / پیوست ۴ بند ۵`

> امتیازدهی همه فاکتورها انجام می‌شود؛ حتی اگر خریدار پاداش خود را در مهلت تعیین‌شده دریافت نکند، امتیازها محاسبه و حفظ می‌شوند؛

- **Actors:** System
- **Statement:** Every invoice accrues points regardless of whether the buyer claims the reward within its validity window; unclaimed rewards do not delete points.
- **Rules / edge cases:** Reward expiry voids only the reward, never the underlying points; points balance is monotonic except for explicit deductions.
- **Priority:** P0 · **Depends on:** REQ-LOY-001
- **Acceptance:**
  1. Expired reward leaves the points balance intact
  2. Every paid invoice has a points entry
  3. No points are silently zeroed
- **Label:** CONTRACT · **Status:** Specified

### REQ-LOY-006 — Points deducted for inactivity over a defined period

- **Source:** `page 6 / پیوست ۴ بند ۶`

> در صورت عدم ثبت فاکتور و خرید در یک بازه معین )مث الً یک ماه(، مبلغی امتیاز )مث الً چهار امتیاز در ماه( از امتیاز کل خریدار کسر می‌شود؛

- **Actors:** System, Admin
- **Statement:** When a buyer records no invoice within the configured period, a configured number of points per month is deducted from their balance.
- **Rules / edge cases:** Period and amount are admin settings (examples given as one month and four points); deduction stops at zero; each deduction is logged with reason.
- **Priority:** P1 · **Depends on:** REQ-LOY-001
- **Acceptance:**
  1. Inactive account loses the configured points monthly
  2. Balance never goes below zero
  3. Deduction reason is visible to the user
- **Label:** CONTRACT · **Status:** Specified

### REQ-LOY-007 — Cash reward credited to the wallet on reaching a points threshold

- **Source:** `page 6 / پیوست ۴ بند ۷`

> در صورت رسیدن خریدار به امتیاز مشخص، پاداش نقدی مازاد در کیف پول او ثبت می‌شود و افرصً برای خرید و ثبت فاکتور جدید در سایت قابل استفاده است؛ این پاداش می‌تواند مدت‌دار یا دائمی باشد؛

- **Actors:** System
- **Statement:** When the buyer reaches a defined points level, a cash reward is credited to their wallet, spendable only on a new purchase and invoice, either time-limited or permanent.
- **Rules / edge cases:** Threshold list admin-defined; reward may be capped per period; expiry configured per reward; wallet credit references the rule that produced it.
- **Priority:** P0 · **Depends on:** REQ-FIN-013
- **Acceptance:**
  1. Crossing the threshold credits the wallet
  2. Expired reward is reversed
  3. Reward can only be spent on a new order
- **Label:** CONTRACT · **Status:** Specified

### REQ-LOY-008 — Window-based reward for a number of invoices or points within a short period

- **Source:** `page 7 / پیوست ۴ بند ۸`

> در صورت رسیدن به تعداد مشخصی فاکتور یا امتیاز در یک بازه کوتاه، پاداش نقدی در کیف پول شارژ می‌شود؛ بازه‌های زمانی کوتاه‌مدت، میان‌مدت و بلندمدت توسط ادمین قابل تعریف است؛

- **Actors:** Admin, System
- **Statement:** Reaching a configured number of invoices or points inside a short, medium or long window credits a cash reward to the wallet.
- **Rules / edge cases:** Three window types with independent targets; a reward is granted once per window per user; windows are rolling, not calendar-bound unless configured.
- **Priority:** P1 · **Depends on:** REQ-LOY-007
- **Acceptance:**
  1. Hitting the target in the window credits the wallet
  2. A second identical achievement in the same window is refused
  3. Admin can define all three window lengths
- **Label:** CONTRACT · **Status:** Specified

### REQ-LOY-009 — Tiered next-invoice discount of 4% and 5% with SMS notice

- **Source:** `page 7 / پیوست ۴ بند ۲۰`

> تخفیف پلکانی روی فاکتور بعدی: کاربران با امتیاز ۱ تا ۱۰۰ چهار درصد تخفیف و کاربران با امتیاز ۱۰۰ تا ۲۰۰ پنج درصد تخفیف دریافت می‌کنند؛ این تخفیف با پیامک اطالع‌رسانی می‌شود؛

- **Actors:** System
- **Statement:** Users with 1-100 points get 4% and users with 100-200 points get 5% off their next invoice, and the discount is announced by SMS.
- **Rules / edge cases:** Boundary 100 appears in both bands and must be resolved explicitly (AMB-LOY-001); above 200 the fixed-amount rule applies (REQ-LOY-010).
- **Priority:** P1 · **Depends on:** REQ-LOY-001, REQ-NTF-004
- **Acceptance:**
  1. A 50-point user sees 4% on the next invoice
  2. A 150-point user sees 5%
  3. An SMS is sent when the band is earned
- **Label:** CONTRACT · **Status:** Conflicting

### REQ-LOY-010 — Fixed system discount in Toman for users above 200 points

- **Source:** `page 7 / پیوست ۴ بند ۲۱`

> امکان تعیین تخفیف ثابت سیستم برای کاربران دارای امتیاز باالتر از ۲۰۰ به‌میزان n تومان؛

- **Actors:** Admin
- **Statement:** For users above 200 points admin sets a fixed discount amount in Toman that applies to their next invoice.
- **Rules / edge cases:** Amount is admin-configurable; applies instead of the percentage band, not in addition; capped at the invoice total.
- **Priority:** P1 · **Depends on:** REQ-LOY-009
- **Acceptance:**
  1. 300-point user receives the fixed amount
  2. Discount never exceeds the invoice total
  3. Percentage band is not applied simultaneously
- **Label:** CONTRACT · **Status:** Specified

### REQ-LOY-011 — Cash discount on the first invoice or any admin-selected invoice

- **Source:** `page 7 / پیوست ۴ بند ۱۰`

> در فاکتور خرید اول یا هر فاکتور دیگری که ادمین تعیین کند، امکان کسر تخفیف نقدی عالوه بر پاداش روی فاکتور بعد وجود دارد؛

- **Also satisfies:** `پیوست ۱۲ بند ۱۰`
- **Actors:** Admin, System
- **Statement:** A cash discount can be applied to the buyer's first invoice or to any invoice admin designates, in addition to the next-invoice reward.
- **Rules / edge cases:** Discount and reward are separate order-line adjustments; the combination cannot exceed the invoice subtotal; admin designation is auditable.
- **Priority:** P1 · **Depends on:** REQ-PRM-001
- **Acceptance:**
  1. First invoice applies the configured discount
  2. Reward still applies on the following invoice
  3. Combined adjustment never makes the total negative
- **Label:** CONTRACT · **Status:** Specified

### REQ-LOY-012 — Higher cash discount on volume invoices with independent coefficients

- **Source:** `page 7 / پیوست ۴ بند ۱۱`

> در فاکتورهای خرید حجمی باال، تخفیف نقدی باالتر مستقل از امتیاز خریدار قابل ارائه است و ضریب امتیاز آن فاکتور برای فاکتور بعد با ضرایب ،۰٫۷۵ ،۰٫۵ ۰٫۲۵ و ۰٫۲ درصد محاسبه می‌شود؛

- **Actors:** Admin, System
- **Statement:** Large volume invoices may receive a higher cash discount independent of the buyer's points, and their next-invoice coefficient follows the 0.75/0.5/0.25/0.2 set.
- **Rules / edge cases:** Independent of points tier; coefficient from the volume band; discount and coefficient stored on the invoice for audit.
- **Priority:** P1 · **Depends on:** REQ-LOY-003
- **Acceptance:**
  1. Volume invoice gets the higher discount with zero points
  2. Next-invoice coefficient matches the band
  3. Both values are recorded on the invoice
- **Label:** CONTRACT · **Status:** Specified

### REQ-LOY-013 — Reduced next-invoice coefficients (0.2 and 0.1 percent) for wholesale-discounted invoices

- **Source:** `page 7 / پیوست ۴ بند ۱۲`

> در فاکتورهای خرید حجمی که تخفیف عمده نقدی دریافت کرده‌اند، پاداش فاکتور بعد با ضرایب ۰٫۲ و ۰٫۱ درصد )یا ضرایب قابل تغییر توسط ادمین( محاسبه می‌شود؛

- **Actors:** Admin, System
- **Statement:** Invoices that already received a wholesale cash discount compute the next-invoice reward at 0.2% or 0.1%, or at admin-overridden values.
- **Rules / edge cases:** Overlaps the 0.2 coefficient of بند ۳ without distinguishing which applies — resolved by a separate 'wholesale-discounted' band set (AMB-LOY-002).
- **Priority:** P1 · **Depends on:** REQ-LOY-012
- **Acceptance:**
  1. Wholesale-discounted invoice uses 0.2 or 0.1
  2. Non-discounted volume invoice keeps the 0.75-0.2 set
  3. Admin override changes the applied value
- **Label:** CONTRACT · **Status:** Ambiguous

### REQ-LOY-014 — Admin deduction of points for inactivity or missing invoices

- **Source:** `page 8 / پیوست ۵ بند ۱۷`

> امکان کسر امتیاز از خریداران و همکاران بابت عدم فعالیت در بازه خاص یا عدم ثبت فاکتور در بازه زمانی مشخص؛

- **Actors:** Admin
- **Statement:** Admin can deduct points from buyers and colleagues for inactivity in a period or for not registering invoices in a defined window.
- **Rules / edge cases:** Deduction requires a reason code and cannot take a balance below zero; manual deductions are separate from the automatic inactivity rule.
- **Priority:** P1 · **Depends on:** REQ-LOY-006
- **Acceptance:**
  1. Admin deduction reduces the balance with a reason
  2. Balance floors at zero
  3. Entry appears in the user's points history
- **Label:** CONTRACT · **Status:** Specified

### REQ-LOY-015 — Decreasing point coefficient as purchase volume grows

- **Source:** `page 8 / پیوست ۵ بند ۲۱`

> ضریب کاهش امتیاز: در فاکتورهای با حجم خرید باالتر، امتیاز کمتری نسبت به حجم پرداخت شده تعلق می‌گیرد )مکانیزم تشویق خرید مستمر به‌جای خرید یکباره بزرگ(؛

- **Actors:** System
- **Statement:** Higher purchase volumes earn a lower points coefficient, so repeat buying earns more than one large purchase of the same total value.
- **Rules / edge cases:** Coefficients monotonic decreasing across bands; implemented as the same band table as REQ-LOY-003.
- **Priority:** P1 · **Depends on:** REQ-LOY-003
- **Acceptance:**
  1. A 10x invoice earns less than 10 small invoices
  2. Coefficients decrease monotonically
  3. Band table is the single source
- **Label:** CONTRACT · **Status:** Specified

### REQ-LOY-016 — Different point rates for products from different stores

- **Source:** `page 8 / پیوست ۵ بند ۲۲`

> تفاوت امتیاز خرید روی محصوالت مختلف از فروشگاه‌های مختلف )مث الً یک میلیون تومان خرید در یک فروشگاه امتیاز متفاوت از محصول دیگر داشته باشد(؛

- **Actors:** Admin
- **Statement:** The same spend can earn different points depending on the product and the store it was bought from.
- **Rules / edge cases:** Store-level override supported alongside product and producer overrides; precedence producer → store → product type.
- **Priority:** P1 · **Depends on:** REQ-LOY-002
- **Acceptance:**
  1. Equal spend at two stores yields different points
  2. Override precedence is deterministic
  3. Admin can set a store override
- **Label:** CONTRACT · **Status:** Specified

### REQ-LOY-017 — Points earned by stores from sales, articles, comments and likes

- **Source:** `page 5 / پیوست ۳-۱ بند ۲`

> دریافت امتیاز از فروش، مقاله، دریافت کامنت و الیک؛

- **Actors:** Store
- **Statement:** A store earns points from sales, publishing articles, receiving comments and receiving likes, each per the loyalty rule set.
- **Rules / edge cases:** Content points capped per day to prevent farming; reversed when the content is removed for violation.
- **Priority:** P2 · **Depends on:** REQ-LOY-001
- **Acceptance:**
  1. Publishing an approved article credits points
  2. Removed content reverses the points
  3. Daily cap is enforced
- **Label:** CONTRACT · **Status:** Specified

### REQ-LOY-018 — Versioned, replayable loyalty rule sets

- **Source:** `page 6 / پیوست ۴ بند ۱`

> امتیاز هر فاکتور بر اساس مبلغ فاکتور، نوع محصول داخل آن و حجم خرید محاسبه می‌شود؛

- **Actors:** Engineering
- **Statement:** Loyalty rules are stored as versioned sets with an effective date, and a replay command recomputes any historical invoice to prove reproducibility.
- **Rules / edge cases:** Rule sets are immutable once effective; a change creates a new version; replay output is compared in tests.
- **Priority:** P0 · **Depends on:** REQ-LOY-001
- **Acceptance:**
  1. Two versions coexist with different dates
  2. Replay reproduces the original points
  3. Tests assert replay equality
- **Label:** TECHNICAL_CONSTRAINT · **Status:** Specified

### REQ-LOY-019 — Points-tier discount percentage displayed per tier and announced by SMS

- **Source:** `page 8 / پیوست ۵ بند ۱۶`

> اعمال تخفیف خودکار پلکانی )۱ تا ۱۰۰ امتیاز: ۴ درصد؛ ۱۰۰ تا ۲۰۰ امتیاز: ۵ درصد( روی فاکتور بعدی با اطالع‌رسانی پیامکی و نمایش درصد تخفیف متفاوت در سطح‌های مختلف امتیازی؛

- **Actors:** Buyer
- **Statement:** The buyer sees the discount percentage of their current points tier on the product page and cart, and receives an SMS when the tier changes.
- **Rules / edge cases:** Percentage is read from the same rule that applies the discount (no duplicated constants); tier change triggers one SMS; above 200 shows the fixed amount (REQ-LOY-010).
- **Priority:** P1 · **Depends on:** REQ-LOY-009
- **Acceptance:**
  1. Each tier displays its own percentage
  2. Tier change sends exactly one SMS
  3. Displayed percentage equals the amount applied at checkout
- **Label:** CONTRACT · **Status:** Specified

<a id="prm"></a>
## PRM — Promotions

Promotions: coupons, buy-bons, campaigns, banners, seasonal showcases and discount stacking rules.

### REQ-PRM-001 — Coupon creation with code, value, window and usage limits

- **Source:** `page 9 / پیوست ۵ بند ۴۳`

> امکان ساخت بن و کوپن تخفیف، غیرفعال‌سازی کلی کاربران توسط ادمین و استانداردهای سئو؛

- **Actors:** Admin, Supplier
- **Statement:** Admin or an authorised supplier creates a coupon with a code, a percentage or fixed amount, a validity window and total/per-user usage limits.
- **Rules / edge cases:** Codes unique and case-insensitive; percentage coupons may carry a maximum discount; deactivation is immediate; usage counted on successful payment only.
- **Priority:** P0 · **Depends on:** REQ-CRT-004
- **Acceptance:**
  1. Coupon saves and is redeemable within its window
  2. Usage beyond the limit is refused
  3. Deactivated coupon stops working at once
- **Label:** CONTRACT · **Status:** Specified

### REQ-PRM-002 — Coupon eligibility restricted by level, category and basket minimum

- **Source:** `page 9 / پیوست ۵ بند ۴۳`

> امکان ساخت بن و کوپن تخفیف، غیرفعال‌سازی کلی کاربران توسط ادمین و استانداردهای سئو؛

- **Actors:** Admin
- **Statement:** A coupon can be limited to specific user levels, categories or a minimum basket value, and is refused for carts that do not match.
- **Rules / edge cases:** All constraints are AND-ed; a refusal states which condition failed; ineligible coupons are hidden from the cart banner.
- **Priority:** P1 · **Depends on:** REQ-PRM-001
- **Acceptance:**
  1. Level-restricted coupon is refused for another level
  2. Basket below the minimum is refused with the reason
  3. Eligible cart applies it
- **Label:** CONTRACT · **Status:** Specified

### REQ-PRM-003 — Buy-bon issued after an invoice and redeemable on the next invoice within a window

- **Source:** `page 11 / پیوست ۱۲ بند ۴`

> اختصاص بن خرید در صورت ثبت فاکتور و اعمال آن روی فاکتور بعدی در بازه مشخص؛

- **Actors:** Consumer, Store
- **Statement:** Registering an invoice issues a buy-bon that can be applied to the buyer's next invoice inside the defined window.
- **Rules / edge cases:** Issued per invoice above a threshold; one bon per invoice; unused bons expire at the window end; cannot be combined with another bon on the same invoice.
- **Priority:** P1 · **Depends on:** REQ-LOY-011
- **Acceptance:**
  1. Invoice issues a bon visible in the panel
  2. Bon applies to the next invoice
  3. Expired bon is refused
- **Label:** CONTRACT · **Status:** Specified

### REQ-PRM-004 — Permanent discount for suppliers buying from other suppliers

- **Source:** `page 8 / پیوست ۵ بند ۲۰`

> امکان ارائه تخفیف دائمی برای تأمین‌کنندگان در صورت خرید از سایر تأمین‌کنندگان سایت روی هر فاکتور؛

- **Actors:** Admin, Supplier
- **Statement:** A supplier may be granted a permanent discount on every invoice it buys from other suppliers on the platform.
- **Rules / edge cases:** Percentage per supplier; excluded on its own products; applies automatically without a code.
- **Priority:** P2 · **Depends on:** REQ-MER-017
- **Acceptance:**
  1. Supplier purchase shows the permanent discount
  2. Own products carry no discount
  3. Discount appears as a line on the invoice
- **Label:** CONTRACT · **Status:** Specified

### REQ-PRM-005 — Banner management with scheduling and click tracking

- **Source:** `page 9 / پیوست ۷ بند ۹`

> مدیریت بنرها، صفحات ثابت، منوها، درگاه‌ها و تنظیمات عمومی سایت؛

- **Actors:** Admin
- **Statement:** Admin uploads banners with placement, start/end dates, target link and ordering; the storefront shows only banners inside their window.
- **Rules / edge cases:** Image size validated per placement; expired banners never render; click and impression counts stored for reports.
- **Priority:** P1 · **Depends on:** REQ-PAY-009
- **Acceptance:**
  1. Banner appears only within its dates
  2. Placement order is respected
  3. Expired banner disappears
- **Label:** CONTRACT · **Status:** Specified

### REQ-PRM-006 — Campaign definition with date window and participating products

- **Source:** `page 10 / پیوست ۱۱ بند ۴`

> بخش ارائه محصوالت در جشنواره‌ها و تخفیفات ویژه و نمایش آن در صفحه اصلی؛

- **Actors:** Admin, Supplier
- **Statement:** Admin defines a campaign with a name, window and discount rules; suppliers submit products and admin approves participation.
- **Rules / edge cases:** A product can join overlapping campaigns only once; conflicting discounts resolved by the highest single discount, not stacked.
- **Priority:** P1 · **Depends on:** REQ-CAT-009
- **Acceptance:**
  1. Campaign created with window and rules
  2. Product cannot join two overlapping campaigns
  3. Approval required before display
- **Label:** CONTRACT · **Status:** Specified

### REQ-PRM-007 — Supplier-specific seasonal slideshow for defined periods

- **Source:** `page 10 / پیوست ۱۱ بند ۸`

> قابلیت اسالیدشو اختصاصی در بازه‌های مشخص سال؛

- **Actors:** Supplier
- **Statement:** A supplier manages its own slideshow with images and links active only during the periods it defines.
- **Rules / edge cases:** Per-supplier slot on its page and optionally the homepage; images validated; schedule stored with Jalali dates displayed.
- **Priority:** P2 · **Depends on:** REQ-PRM-005
- **Acceptance:**
  1. Slideshow visible only in its period
  2. Supplier can reorder slides
  3. Images outside the size rule are rejected
- **Label:** CONTRACT · **Status:** Specified

### REQ-PRM-008 — Single discount-combination policy applied by the pricing engine

- **Source:** `page 7 / پیوست ۴ بند ۱۱`

> در فاکتورهای خرید حجمی باال، تخفیف نقدی باالتر مستقل از امتیاز خریدار قابل ارائه است و ضریب امتیاز آن فاکتور برای فاکتور بعد با ضرایب ،۰٫۷۵ ،۰٫۵ ۰.۲۵ و ۰٫۲ درصد محاسبه می‌شود؛

- **Actors:** System
- **Statement:** One policy defines which discounts may stack (coupon, campaign, loyalty, bon, volume) and enforces the order and the cap.
- **Rules / edge cases:** Order: volume → campaign → coupon → bon → reward; total discount cannot exceed the invoice subtotal; the applied sequence is stored on the order.
- **Priority:** P0 · **Depends on:** REQ-PRI-006
- **Acceptance:**
  1. Two stackable discounts apply in the documented order
  2. Non-stackable combination keeps only the higher
  3. Total discount never exceeds the subtotal
- **Label:** CONTRACT · **Status:** Ambiguous

### REQ-PRM-009 — Bulk deactivation of coupons and promotions

- **Source:** `page 9 / پیوست ۵ بند ۴۳`

> امکان ساخت بن و کوپن تخفیف، غیرفعال‌سازی کلی کاربران توسط ادمین و استانداردهای سئو؛

- **Actors:** Admin
- **Statement:** Admin can deactivate several coupons or promotions at once, with immediate effect on checkout.
- **Rules / edge cases:** Bulk action is audited per object; already-issued bons remain spendable unless explicitly voided.
- **Priority:** P2 · **Depends on:** REQ-PRM-001
- **Acceptance:**
  1. Bulk deactivation affects all selected objects
  2. Checkout refuses them immediately
  3. Each object records who deactivated it
- **Label:** CONTRACT · **Status:** Specified

### REQ-PRM-010 — Promotion targeting by user segment

- **Source:** `page 7 / پیوست ۵ بند ۱۰`

> ایجاد نظرسنجی در سایت برای موضوعات مختلف؛

- **Actors:** Admin
- **Statement:** A promotion can target a saved user segment (level, city, points band, inactivity) instead of all users.
- **Rules / edge cases:** Segment evaluated at send/apply time; users outside the segment see no badge and cannot redeem.
- **Priority:** P2 · **Depends on:** REQ-NTF-002
- **Acceptance:**
  1. Segment-limited promo visible only to members
  2. Non-members cannot redeem the code
  3. Segment size is previewed before activation
- **Label:** AGENT_DECISION · **Status:** Specified

### REQ-PRM-011 — Discount floor guard: no line below the supplier's minimum

- **Source:** `page 7 / پیوست ۴ بند ۱۱`

> در فاکتورهای خرید حجمی باال، تخفیف نقدی باالتر مستقل از امتیاز خریدار قابل ارائه است

- **Actors:** System
- **Statement:** No combination of discounts may push a line's net price below the supplier's configured minimum price.
- **Rules / edge cases:** Guard applied after all discounts; excess discount is trimmed and reported to the buyer; guard violations are logged.
- **Priority:** P1 · **Depends on:** REQ-PRM-008
- **Acceptance:**
  1. Stacked discounts stop at the floor
  2. Buyer sees a reduced discount with an explanation
  3. Log records the trimmed amount
- **Label:** AGENT_DECISION · **Status:** Specified

<a id="shp"></a>
## SHP — Shipping

Shipping: method and carrier configuration, cost calculation, free-shipping rules and tracking events.

### REQ-SHP-001 — Shipping methods and carriers configured by admin

- **Source:** `page 8 / پیوست ۵ بند ۴۰`

> ثبت نام و نوع باربری و روش حمل هنگام ثبت سفارش و اعالم آن در فرم ارسالی به پنل توزیع‌کننده و عرضه‌کننده؛

- **Actors:** Admin
- **Statement:** Admin defines carrier types (courier, post, freight) and methods with their cost model, coverage and delivery estimate.
- **Rules / edge cases:** Method availability depends on destination city and product restrictions; disabled methods vanish from checkout.
- **Priority:** P0 · **Depends on:** REQ-ORD-001
- **Acceptance:**
  1. New method appears at checkout
  2. Disabling removes it
  3. Coverage limits it to listed cities
- **Label:** CONTRACT · **Status:** Specified

### REQ-SHP-002 — Shipping cost calculated at checkout per destination and basket

- **Source:** `page 5 / پیوست ۲ بند ۶`

> سبد خرید، اعمال کد تخفیف، محاسبه هزینه ارسال و صفحه پرداخت یکپارچه با درگاه بانکی؛

- **Actors:** System
- **Statement:** Checkout computes the shipping cost from the destination city, basket weight or value and the chosen method, and shows it before payment.
- **Rules / edge cases:** Per-seller shipping summed for multi-seller carts; free-shipping rules applied before display; recalculation on any basket change.
- **Priority:** P0 · **Depends on:** REQ-SHP-001
- **Acceptance:**
  1. Cost changes with the selected city
  2. Multi-seller cart shows one cost per shipment
  3. Total equals the sum of shipment costs
- **Label:** CONTRACT · **Status:** Specified

### REQ-SHP-003 — Free shipping within one city or nationwide

- **Source:** `page 11 / پیوست ۱۲ بند ۱۲`

> انتخاب نوع حمل کاال و رایگان شدن ارسال در یک شهر یا سراسر کشور؛

- **Actors:** Admin, Supplier
- **Statement:** Admin or a supplier can make shipping free for a specific city or for the whole country, optionally above a basket threshold.
- **Rules / edge cases:** Rule precedence city → nationwide; applied as a zero-cost shipment line, not a discount; scheduled windows supported.
- **Priority:** P1 · **Depends on:** REQ-SHP-002
- **Acceptance:**
  1. Order to the free city shows zero shipping
  2. Other cities keep their cost
  3. Threshold rule applies only above the amount
- **Label:** CONTRACT · **Status:** Specified

### REQ-SHP-004 — Buyer chooses the carrier type and shipping method

- **Source:** `page 11 / پیوست ۱۲ بند ۱۲`

> انتخاب نوع حمل کاال و رایگان شدن ارسال در یک شهر یا سراسر کشور؛

- **Actors:** Buyer
- **Statement:** At checkout the buyer selects among the carriers available for that basket and destination, and sees the cost and estimate of each.
- **Rules / edge cases:** Unavailable options are disabled with the reason; the choice is stored on the shipment; changing it recalculates the total.
- **Priority:** P0 · **Depends on:** REQ-SHP-002
- **Acceptance:**
  1. All available carriers listed with cost
  2. Unavailable option disabled with reason
  3. Selection stored on the shipment record
- **Label:** CONTRACT · **Status:** Specified

### REQ-SHP-005 — Postal delivery as a selectable method

- **Source:** `page 11 / پیوست ۱۲ بند ۵`

> امکانات کامل فروشگاهی: مقایسه محصوالت، جستجو، ارسال پستی، امتیازدهی و نظرسنجی و صفحات استاندارد؛

- **Actors:** Buyer
- **Statement:** Postal delivery is offered as a method with its own cost and tracking, where the product permits it.
- **Rules / edge cases:** Oversized or fragile items exclude post; postal tracking number recorded like any carrier.
- **Priority:** P1 · **Depends on:** REQ-SHP-001
- **Acceptance:**
  1. Post option selectable for eligible items
  2. Ineligible item hides the post option
  3. Postal tracking number recorded
- **Label:** CONTRACT · **Status:** Specified

### REQ-SHP-006 — Shipment tracking events visible to buyer and admin

- **Source:** `page 5 / پیوست ۲ بند ۱۱`

> صفحات درباره ما، تماس با ما با فرم و نقشه، و پیگیری سفارش با کد رهگیری؛

- **Actors:** Buyer, Admin
- **Statement:** Shipment events (dispatched, in transit, delivered) are recorded and shown on the order tracking page and in admin.
- **Rules / edge cases:** Events are append-only with source (manual or carrier callback); out-of-order events are sorted by timestamp.
- **Priority:** P1 · **Depends on:** REQ-ORD-019
- **Acceptance:**
  1. Each event appears on the tracking page
  2. Admin sees the same timeline
  3. Events cannot be edited, only appended
- **Label:** CONTRACT · **Status:** Specified

### REQ-SHP-007 — Promised ship date per sub-order drives the delay clock

- **Source:** `page 10 / پیوست ۱۱ بند ۱۵`

> ثبت تأخیر ارسال کاال طبق زمانبندی سفارش با درج شماره بارنامه و ارسال با پیک؛

- **Actors:** System
- **Statement:** Each sub-order carries a promised ship date derived from the preparation SLA and the delivery estimate, and lateness is measured against it.
- **Rules / edge cases:** Promise excludes weekends/holidays per calendar setting; holidays configurable in Jalali; promise shown to the buyer at checkout.
- **Priority:** P1 · **Depends on:** REQ-ORD-019
- **Acceptance:**
  1. Promised date shown at checkout
  2. Lateness computed against the promise
  3. Holiday calendar affects the promise
- **Label:** CONTRACT · **Status:** Specified

### REQ-SHP-008 — Shipping cost allocation across refunds and settlements

- **Source:** `page 11 / پیوست ۱۳ بند ۱`

> گزارش دقیق و فیلترپذیر فروش برای تسویه دستی با تولیدکنندگان بر اساس بازه زمانی، مبلغ، تاریخ و نوع محصول؛

- **Actors:** Finance
- **Statement:** Shipping cost is attributed to the shipment and, on partial cancellation, refunded proportionally while the supplier settlement excludes it.
- **Rules / edge cases:** Supplier payable excludes shipping; platform revenue includes the shipping margin; refunds split line value and shipping.
- **Priority:** P1 · **Depends on:** REQ-ORD-020
- **Acceptance:**
  1. Supplier settlement excludes shipping
  2. Partial refund splits value and shipping
  3. Reports separate shipping revenue
- **Label:** CONTRACT · **Status:** Specified

<a id="rtn"></a>
## RTN — Returns

Returns: return windows, approval, refund execution, restocking and non-returnable configuration.

### REQ-RTN-001 — Return request accepted within a defined window for approved items

- **Source:** `page 11 / پیوست ۱۲ بند ۱۴`

> پذیرش مرجوعی کاال در بازه مشخص برای اقالم تأییدشده ادمین؛

- **Actors:** Consumer, Store
- **Statement:** A buyer requests a return within the defined window after delivery, for items whose category is admin-approved as returnable.
- **Rules / edge cases:** Window measured from delivery confirmation; per-category window; reason and photos required; one open request per line.
- **Priority:** P0 · **Depends on:** REQ-ORD-018
- **Acceptance:**
  1. Request inside the window is accepted
  2. Request after the window is refused
  3. Category excluded by admin cannot be returned
- **Label:** CONTRACT · **Status:** Specified

### REQ-RTN-002 — Return approval by supplier or admin

- **Source:** `page 11 / پیوست ۱۲ بند ۱۴`

> پذیرش مرجوعی کاال در بازه مشخص برای اقالم تأییدشده ادمین؛

- **Actors:** Supplier, Admin
- **Statement:** The supplier approves or rejects the return with a reason; admin can override either decision.
- **Rules / edge cases:** No response within the SLA auto-approves; rejection reasons are from a controlled list; decisions are audited.
- **Priority:** P0 · **Depends on:** REQ-RTN-001
- **Acceptance:**
  1. Approval moves the return to 'awaiting goods'
  2. Silence past SLA auto-approves
  3. Admin override is recorded
- **Label:** CONTRACT · **Status:** Specified

### REQ-RTN-003 — Refund executed to wallet or SHEBA after return acceptance

- **Source:** `page 11 / پیوست ۱۲ بند ۱۴`

> پذیرش مرجوعی کاال در بازه مشخص برای اقالم تأییدشده ادمین؛

- **Actors:** Finance
- **Statement:** Once the returned goods are received, the refund is issued to the buyer's wallet or SHEBA account per their choice, with a ledger entry.
- **Rules / edge cases:** Refund equals the line value plus its shipping share where the whole order is returned; supplier payable reversed in the same transaction.
- **Priority:** P0 · **Depends on:** REQ-PAY-007
- **Acceptance:**
  1. Refund posts after goods receipt
  2. Method follows the buyer's choice
  3. Supplier payable is reversed
- **Label:** CONTRACT · **Status:** Specified

### REQ-RTN-004 — Stock restocked when returned goods pass inspection

- **Source:** `page 11 / پیوست ۱۲ بند ۱۴`

> پذیرش مرجوعی کاال در بازه مشخص برای اقالم تأییدشده ادمین؛

- **Actors:** Supplier, Admin
- **Statement:** Returned goods are inspected; accepted units return to sellable stock, rejected units go to a damaged bucket.
- **Rules / edge cases:** Restock is idempotent per return line; damaged units never enter available stock; both outcomes recorded with photos.
- **Priority:** P1 · **Depends on:** REQ-STK-001
- **Acceptance:**
  1. Accepted return increases available stock
  2. Damaged unit does not
  3. Replaying the restock does not double the quantity
- **Label:** CONTRACT · **Status:** Specified

### REQ-RTN-005 — Return shipping responsibility recorded per case

- **Source:** `page 11 / پیوست ۱۲ بند ۱۴`

> پذیرش مرجوعی کاال در بازه مشخص برای اقالم تأییدشده ادمین؛

- **Actors:** Admin, Supplier
- **Statement:** Each return records who bears the shipping cost — buyer, supplier or platform — and the amount is reflected in the refund or the supplier debit.
- **Rules / edge cases:** Default follows the rejection reason (supplier fault → supplier pays); admin can override with a note.
- **Priority:** P2 · **Depends on:** REQ-RTN-003
- **Acceptance:**
  1. Return shows the responsible party
  2. Supplier-fault return debits the supplier
  3. Refund excludes buyer-borne shipping
- **Label:** CONTRACT · **Status:** Specified

### REQ-RTN-006 — Return status tracked from request to closure

- **Source:** `page 11 / پیوست ۱۲ بند ۱۴`

> پذیرش مرجوعی کاال در بازه مشخص برای اقالم تأییدشده ادمین؛

- **Actors:** Buyer, Supplier, Admin
- **Statement:** A return moves through request → approved/rejected → in transit → received → refunded/closed, visible to all three parties.
- **Rules / edge cases:** Illegal transitions refused; each transition stores actor and timestamp; buyer is notified at each step.
- **Priority:** P1 · **Depends on:** REQ-RTN-002
- **Acceptance:**
  1. Timeline visible to buyer and supplier
  2. Illegal transition returns an error
  3. Notifications sent per transition
- **Label:** CONTRACT · **Status:** Specified

### REQ-RTN-007 — Non-returnable categories configured by admin

- **Source:** `page 11 / پیوست ۱۲ بند ۱۴`

> پذیرش مرجوعی کاال در بازه مشخص برای اقالم تأییدشده ادمین؛

- **Actors:** Admin
- **Statement:** Admin marks categories or products as non-returnable and the buyer sees that condition on the product page before purchase.
- **Rules / edge cases:** Condition shown at checkout too; hygiene/custom-cut items default to non-returnable; the flag is part of the product snapshot on the order.
- **Priority:** P1 · **Depends on:** REQ-RTN-001
- **Acceptance:**
  1. Flagged product shows the condition before purchase
  2. Return request for it is refused
  3. Order snapshot keeps the flag it was sold under
- **Label:** CONTRACT · **Status:** Specified

<a id="tnd"></a>
## TND — Tenders and auctions

Tenders and auctions: creation, distribution, bidding, clarification and award.

### REQ-TND-001 — Tender creation through a structured form

- **Source:** `page 7 / پیوست ۵ بند ۶`

> فرم مناقصه و ارسال آن به تولیدکنندگان و عرضه‌کنندگان برای شرکت در پروژه‌های بزرگ؛

- **Actors:** Store, Consumer, Marketer
- **Statement:** A buyer creates a tender with title, description, items or quantities, deadline, delivery place and attachments.
- **Rules / edge cases:** Deadline must be in the future; at least one item or a free-text scope required; the tender is editable only before the first bid.
- **Priority:** P1 · **Depends on:** REQ-IAM-006
- **Acceptance:**
  1. Tender saves with all required fields
  2. Editing after the first bid is blocked
  3. Deadline in the past is refused
- **Label:** CONTRACT · **Status:** Specified

### REQ-TND-002 — Tender distributed to selected producers and suppliers

- **Source:** `page 8 / پیوست ۵ بند ۳۷`

> امکان ارسال فرم مناقصه به تولیدکنندگان و عرضه‌کنندگان توسط همکاران؛

- **Actors:** Buyer, System
- **Statement:** The creator selects producers/suppliers (by category or explicit list) and the tender is delivered to their panels and notifications.
- **Rules / edge cases:** Category-based targeting plus manual selection; invited suppliers see it in a dedicated inbox; non-invited suppliers cannot bid unless the tender is public.
- **Priority:** P1 · **Depends on:** REQ-TND-001
- **Acceptance:**
  1. Invited suppliers receive the tender
  2. A non-invited supplier cannot bid on a closed tender
  3. Delivery is logged per recipient
- **Label:** CONTRACT · **Status:** Specified

### REQ-TND-003 — Supplier bid submission with price and delivery terms

- **Source:** `page 8 / پیوست ۵ بند ۳۶`

> امکان شرکت در مناقصات اعالمی با تکمیل فرم و استعالم؛

- **Actors:** Supplier
- **Statement:** An invited supplier submits a bid containing unit prices, total, delivery time and validity, and may revise it before the deadline.
- **Rules / edge cases:** One active bid per supplier per tender (revision replaces); bids are sealed until the deadline; withdrawal allowed before deadline.
- **Priority:** P1 · **Depends on:** REQ-TND-002
- **Acceptance:**
  1. Bid saved and editable before deadline
  2. Other suppliers' bids are invisible
  3. Bid after deadline is refused
- **Label:** CONTRACT · **Status:** Specified

### REQ-TND-004 — Clarification questions and answers on a tender

- **Source:** `page 8 / پیوست ۵ بند ۳۶`

> امکان شرکت در مناقصات اعالمی با تکمیل فرم و استعالم؛

- **Actors:** Supplier, Buyer
- **Statement:** Suppliers post clarification questions; the buyer answers publicly so all participants see the same information.
- **Rules / edge cases:** Q&A closes a configurable time before the deadline; answers are immutable once posted; the Q&A list is part of the tender record.
- **Priority:** P2 · **Depends on:** REQ-TND-003
- **Acceptance:**
  1. Question appears to all participants
  2. Answer visible with timestamp
  3. Questions blocked after the cut-off
- **Label:** CONTRACT · **Status:** Specified

### REQ-TND-005 — Bid comparison and award decision

- **Source:** `page 8 / پیوست ۵ بند ۳۶`

> امکان شرکت در مناقصات اعالمی با تکمیل فرم و استعالم؛

- **Actors:** Buyer, Admin
- **Statement:** The buyer compares bids side by side and awards the tender to one bid, which notifies all participants and creates a draft order.
- **Rules / edge cases:** Award is final and audited; losing bidders see the outcome but not competitors' prices unless the tender is public; award creates an order shell the buyer must confirm.
- **Priority:** P1 · **Depends on:** REQ-TND-003
- **Acceptance:**
  1. Comparison table shows bids after the deadline
  2. Award notifies every participant
  3. Awarded bid creates a draft order
- **Label:** CONTRACT · **Status:** Specified

### REQ-TND-006 — Auction section open to suppliers, colleagues and consumers

- **Source:** `page 10 / پیوست ۱۱ بند ۱۰`

> بخش مزایده در پنل تولیدکننده و عرضه‌کننده با حضور تأمین‌کنندگان، همکاران و مصرف‌کنندگان؛

- **Actors:** Supplier, Store, Consumer
- **Statement:** An auction module in the supplier panel lets the three roles participate as sellers or bidders on listed lots.
- **Rules / edge cases:** Auction has a start price, minimum increment, start/end time and a reserve price; participation requires an accepted deposit or credit.
- **Priority:** P1 · **Depends on:** REQ-TND-001
- **Acceptance:**
  1. Three roles can join an auction
  2. Bid below the increment is refused
  3. Auction closes exactly at the end time
- **Label:** CONTRACT · **Status:** Specified

### REQ-TND-007 — Live descending bids with anti-sniping extension

- **Source:** `page 10 / پیوست ۱۱ بند ۱۰`

> بخش مزایده در پنل تولیدکننده و عرضه‌کننده با حضور تأمین‌کنندگان، همکاران و مصرف‌کنندگان؛

- **Actors:** Bidder
- **Statement:** Bids are accepted in real time, each must beat the current best by the increment, and a bid in the final minutes extends the auction briefly.
- **Rules / edge cases:** Server clock is authoritative; extension window configurable; identical timestamps resolved by the first accepted request.
- **Priority:** P1 · **Depends on:** REQ-TND-006
- **Acceptance:**
  1. Outbid user is notified immediately
  2. Late-minute bid extends the end time
  3. Server time decides the winner
- **Label:** CONTRACT · **Status:** Specified

### REQ-TND-008 — Tender and auction notifications to participants

- **Source:** `page 8 / پیوست ۵ بند ۳۶`

> امکان شرکت در مناقصات اعالمی با تکمیل فرم و استعالم؛

- **Actors:** System
- **Statement:** Participants are notified on invitation, on Q&A answers, on award and on outbid events through the notification channels.
- **Rules / edge cases:** One notification per event per recipient; digest option for Q&A; opt-out respected except for award results.
- **Priority:** P2 · **Depends on:** REQ-NTF-001
- **Acceptance:**
  1. Invitation notification received
  2. Outbid notification received
  3. Award notification cannot be opted out
- **Label:** CONTRACT · **Status:** Specified

### REQ-TND-009 — Store participation in announced tenders and auctions

- **Source:** `page 5 / پیوست ۳-۱ بند ۶`

> شرکت در مناقصه‌ها و مزایده‌های اعالمی؛

- **Actors:** Store
- **Statement:** A store sees announced tenders and auctions and can submit bids as a seller or a buyer where the announcement allows it.
- **Rules / edge cases:** Role in each announcement is explicit; a store cannot bid against itself; participation history retained.
- **Priority:** P1 · **Depends on:** REQ-TND-006
- **Acceptance:**
  1. Store sees the announcement list
  2. Bid submitted as a participant
  3. Self-bidding is blocked
- **Label:** CONTRACT · **Status:** Specified

### REQ-TND-010 — Tender closure with published results

- **Source:** `page 8 / پیوست ۵ بند ۳۶`

> امکان شرکت در مناقصات اعالمی با تکمیل فرم و استعالم؛

- **Actors:** Buyer, Admin
- **Statement:** At the deadline the tender closes automatically, bids become readable to the buyer, and the result is published to participants after award or cancellation.
- **Rules / edge cases:** Cancellation requires a reason and notifies all bidders; a closed tender cannot be reopened, only cloned.
- **Priority:** P1 · **Depends on:** REQ-TND-005
- **Acceptance:**
  1. Tender closes at the deadline without manual action
  2. Result published to all bidders
  3. Cancellation requires a reason
- **Label:** CONTRACT · **Status:** Specified

<a id="cls"></a>
## CLS — Classifieds (دیوار)

Classifieds (دیوار): ad posting under admin-defined taxonomy, search and profile linkage.

### REQ-CLS-001 — Classified ad with title, body and images

- **Source:** `page 7 / پیوست ۵ بند ۴`

> بخش دیوار: آگهی‌های صنفی با عنوان، متن، تصویر و امکان جستجو؛ حضور با پروفایل کاربری در آگهی؛ درج آگهی فقط پس از تعریف طبقه‌بندی توسط ادمین؛

- **Actors:** Store, Consumer
- **Statement:** A user posts a trade classified with title, body text and up to several images, attached to their profile.
- **Rules / edge cases:** Title and body length limits; images validated and compressed; price field optional; contact details follow the profile visibility setting.
- **Priority:** P1 · **Depends on:** REQ-CLS-002
- **Acceptance:**
  1. Ad saves with title, text and images
  2. Ad shows the poster's public profile
  3. Images beyond the limit are refused
- **Label:** CONTRACT · **Status:** Specified

### REQ-CLS-002 — Classified taxonomy must exist before any ad is posted

- **Source:** `page 7 / پیوست ۵ بند ۴`

> درج آگهی فقط پس از تعریف طبقه‌بندی توسط ادمین؛

- **Actors:** Admin
- **Statement:** Posting is possible only when admin has defined the classification tree for that section; an empty taxonomy blocks the post form with an explanation.
- **Rules / edge cases:** Two levels minimum; categories can be disabled without deleting existing ads; every ad belongs to exactly one leaf category.
- **Priority:** P0 · **Depends on:** REQ-CAT-003
- **Acceptance:**
  1. Empty taxonomy disables posting
  2. Ad cannot be saved without a leaf category
  3. Disabling a category keeps its ads readable
- **Label:** CONTRACT · **Status:** Specified

### REQ-CLS-003 — Search and filter across classifieds

- **Source:** `page 7 / پیوست ۵ بند ۴`

> بخش دیوار: آگهی‌های صنفی با عنوان، متن، تصویر و امکان جستجو؛

- **Actors:** Visitor
- **Statement:** Visitors search classifieds by keyword and filter by category, city and date, with results ordered by freshness.
- **Rules / edge cases:** Full-text search over title and body; Persian normalisation; expired ads excluded.
- **Priority:** P1 · **Depends on:** REQ-CLS-001
- **Acceptance:**
  1. Keyword search finds an ad by a body word
  2. City filter narrows results
  3. Expired ads never appear
- **Label:** CONTRACT · **Status:** Specified

### REQ-CLS-004 — Ad owner identity visible through the profile link

- **Source:** `page 7 / پیوست ۵ بند ۴`

> حضور با پروفایل کاربری در آگهی؛

- **Actors:** Visitor
- **Statement:** Every ad links to the poster's public profile showing level, city and rating so buyers can judge the seller.
- **Rules / edge cases:** Profile fields respect the admin visibility switch (REQ-USR-008); anonymous posting is not allowed.
- **Priority:** P1 · **Depends on:** REQ-USR-008
- **Acceptance:**
  1. Ad links to a live profile
  2. Hidden fields stay hidden
  3. Deleted user's ads are removed
- **Label:** CONTRACT · **Status:** Specified

### REQ-CLS-005 — Ad moderation, expiry and renewal

- **Source:** `page 7 / پیوست ۵ بند ۴`

> بخش دیوار: آگهی‌های صنفی با عنوان، متن، تصویر و امکان جستجو؛

- **Actors:** Admin, Store
- **Statement:** Ads are moderated before publication where the category requires it, expire after a configured period and can be renewed by the owner.
- **Rules / edge cases:** Rejection carries a reason; renewal resets the expiry without changing the ad id; expired ads are read-only.
- **Priority:** P1 · **Depends on:** REQ-CLS-001
- **Acceptance:**
  1. Moderated category holds the ad until approved
  2. Expiry hides the ad from search
  3. Renewal restores it with the same id
- **Label:** CONTRACT · **Status:** Specified

### REQ-CLS-006 — Stores may publish classifieds and announcements

- **Source:** `page 5 / پیوست ۳-۱ بند ۵`

> ثبت اطالعات فروشگاه، ثبت آگهی و حضور در بخش پاتوق؛

- **Actors:** Store
- **Statement:** A store records its information, publishes classifieds and appears in the Patogh section from one place.
- **Rules / edge cases:** Requires an active store; publishing counts toward store activity; ads respect the same taxonomy.
- **Priority:** P1 · **Depends on:** REQ-CLS-001
- **Acceptance:**
  1. Store publishes an ad under its profile
  2. Store appears in Patogh
  3. Suspended store cannot publish
- **Label:** CONTRACT · **Status:** Specified

### REQ-CLS-007 — Buyer response to a classified ad

- **Source:** `page 7 / پیوست ۵ بند ۴`

> بخش دیوار: آگهی‌های صنفی با عنوان، متن، تصویر و امکان جستجو؛

- **Actors:** Visitor
- **Statement:** An interested visitor contacts the advertiser through the internal messenger, which opens a conversation linked to the ad.
- **Rules / edge cases:** Contact details hidden behind the messenger to prevent scraping; conversation retains the ad reference.
- **Priority:** P2 · **Depends on:** REQ-MSG-001
- **Acceptance:**
  1. Contact button opens a chat with the ad linked
  2. Phone number not exposed in page source
  3. Chat shows the ad title
- **Label:** CONTRACT · **Status:** Specified

<a id="ptg"></a>
## PTG — Patogh services (پتوق)

Patogh (پتوق): local services directory for craftsmen and specialists with cost requests.

### REQ-PTG-001 — City-services directory of installers and repairers with local cost request

- **Source:** `page 7 / پیوست ۵ بند ۵`

> بخش پتوق: معرفی خدمات شهری و استادکاران )نصاب، تعمیرکار و مشابه( با امکان درخواست و اعالم هزینه محلی؛

- **Actors:** Consumer, Provider
- **Statement:** A directory lists local services and craftsmen by city and skill; a visitor requests a service and receives a local cost estimate.
- **Rules / edge cases:** Provider must be verified by admin; request captures address, description and photos; estimate is a message, not a binding order.
- **Priority:** P1 · **Depends on:** REQ-PTG-005
- **Acceptance:**
  1. Directory filters by city and skill
  2. Request reaches the chosen provider
  3. Provider can reply with a cost estimate
- **Label:** CONTRACT · **Status:** Specified

### REQ-PTG-002 — Second Patogh section for individuals and their skills

- **Source:** `page 8 / پیوست ۵ بند ۱۳`

> بخش پتوق دوم: معرفی افراد و توانایی‌ها )مث لاً نصاب پارکت در تهران( با فرم تیکت و اعالم هزینه؛

- **Actors:** Consumer, Provider
- **Statement:** A second directory lists individuals and their skills (for example a parquet installer in Tehran) with a ticket form and cost announcement.
- **Rules / edge cases:** Distinct from the business directory; individual profiles have skills, service area and hourly or per-job pricing.
- **Priority:** P2 · **Depends on:** REQ-PTG-001
- **Acceptance:**
  1. Individual profile shows skills and area
  2. Ticket form creates a request
  3. Cost can be announced in the ticket thread
- **Label:** CONTRACT · **Status:** Specified

### REQ-PTG-003 — Store presence in Patogh from the store profile

- **Source:** `page 5 / پیوست ۳-۱ بند ۵`

> ثبت اطالعات فروشگاه، ثبت آگهی و حضور در بخش پاتوق؛

- **Actors:** Store
- **Statement:** A store can enable a Patogh presence from its profile, reusing its address, phone and services.
- **Rules / edge cases:** Presence requires admin approval; unapproved presence is hidden; edits go through the same approval.
- **Priority:** P2 · **Depends on:** REQ-CLS-006
- **Acceptance:**
  1. Enabling presence creates a directory entry
  2. Approval makes it public
  3. Edits require re-approval
- **Label:** CONTRACT · **Status:** Specified

### REQ-PTG-004 — Service request ticket routed to the provider

- **Source:** `page 8 / پیوست ۵ بند ۱۳`

> بخش پتوق دوم: معرفی افراد و توانایی‌ها )مث لاً نصاب پارکت در تهران( با فرم تیکت و اعالم هزینه؛

- **Actors:** Consumer, Provider, Admin
- **Statement:** A service request becomes a ticket visible to the provider and admin, with threaded messages until the job is accepted or declined.
- **Rules / edge cases:** Ticket stores location and description; admin can reassign to another provider; closed tickets are read-only.
- **Priority:** P1 · **Depends on:** REQ-MSG-001
- **Acceptance:**
  1. Ticket appears in the provider's inbox
  2. Thread messages are ordered
  3. Reassignment by admin is possible
- **Label:** CONTRACT · **Status:** Specified

### REQ-PTG-005 — Service provider profile with portfolio and verification

- **Source:** `page 8 / پیوست ۵ بند ۳۵`

> پروفایل حرفه‌ای کامل با مشخصات، عکس‌ها، توضیحات خدمات، آدرس، لینک صفحات و شماره‌های تماس — همه با تأیید ادمین؛

- **Actors:** Provider, Admin
- **Statement:** A provider profile carries details, photos, service description, address, page links and contact numbers, all published only after admin approval.
- **Rules / edge cases:** Verification badge shown when identity documents are approved; documents stored privately.
- **Priority:** P1 · **Depends on:** REQ-USR-007
- **Acceptance:**
  1. Profile shows all six blocks after approval
  2. Unverified provider has no badge
  3. Documents are not publicly reachable
- **Label:** CONTRACT · **Status:** Specified

### REQ-PTG-006 — Filtering by city, area and skill

- **Source:** `page 7 / پیوست ۵ بند ۵`

> بخش پتوق: معرفی خدمات شهری و استادکاران )نصاب، تعمیرکار و مشابه( با امکان درخواست و اعالم هزینه محلی؛

- **Actors:** Visitor
- **Statement:** Visitors filter providers by province, city, district and skill, and see how many providers match.
- **Rules / edge cases:** District optional; distance sort when the visitor shares a location; empty result suggests nearby cities.
- **Priority:** P2 · **Depends on:** REQ-PTG-001
- **Acceptance:**
  1. City filter returns local providers only
  2. Skill filter narrows correctly
  3. Result count matches the list
- **Label:** CONTRACT · **Status:** Specified

### REQ-PTG-007 — Rating and review for completed service jobs

- **Source:** `page 11 / پیوست ۱۲ بند ۹`

> ثبت نظر و امتیاز برای محصول و عرضه‌کننده تا یک هفته پس از دریافت؛

- **Actors:** Consumer
- **Statement:** After a service job is marked complete, the customer can rate and review the provider within one week.
- **Rules / edge cases:** One review per ticket; provider can reply once; reviews feed the provider's public score.
- **Priority:** P2 · **Depends on:** REQ-RVW-001
- **Acceptance:**
  1. Review form opens only after completion
  2. Second review for the same ticket is refused
  3. Average score updates
- **Label:** CONTRACT · **Status:** Specified

### REQ-PTG-008 — Distributors and agencies listed by city for direct referral

- **Source:** `page 11 / پیوست ۱۲ بند ۲`

> مشاهده عوامل توزیع و نمایندگی‌ها در شهرها برای مراجعه مستقیم؛

- **Actors:** Consumer
- **Statement:** A consumer sees distributors and agencies per city with contact and location to visit directly for installation and measurement.
- **Rules / edge cases:** Data comes from approved agency records (REQ-MER-018); map view and list view both available.
- **Priority:** P1 · **Depends on:** REQ-MER-018
- **Acceptance:**
  1. Agency list filters by city
  2. Each entry shows contact and map link
  3. Unapproved agencies are hidden
- **Label:** CONTRACT · **Status:** Specified

<a id="smp"></a>
## SMP — Samples and catalogues

Samples: sample and catalogue requests from colleagues and customers to suppliers.

### REQ-SMP-001 — Sample and work-model request form with dimensions

- **Source:** `page 8 / پیوست ۵ بند ۳۸`

> امکان درخواست سمپل و نمونه کار و مدل با فرم توضیحات و ذکر ابعاد؛

- **Actors:** Store, Consumer
- **Statement:** A requester submits a sample or work-model request with a description and the required dimensions.
- **Rules / edge cases:** Dimensions structured (length/width/thickness) plus free text; product reference optional; attachments allowed.
- **Priority:** P1 · **Depends on:** REQ-IAM-006
- **Acceptance:**
  1. Request saves with dimensions
  2. Structured dimension fields validated
  3. Attachment attaches to the request
- **Label:** CONTRACT · **Status:** Specified

### REQ-SMP-002 — Sample requests delivered to the supplier panel

- **Source:** `page 10 / پیوست ۱۱ بند ۹`

> فرم درخواست سمپل و نمونه کار که توسط همکاران یا مشتریان تکمیل و به پنل عرضه‌کننده ارسال می‌شود؛

- **Actors:** Supplier
- **Statement:** Completed sample requests arrive in the supplier's panel inbox with requester details and can be accepted, priced or declined.
- **Rules / edge cases:** Supplier sees only requests for its own products; response time tracked; declining requires a reason.
- **Priority:** P1 · **Depends on:** REQ-SMP-001
- **Acceptance:**
  1. Request appears in the supplier inbox
  2. Accept/decline recorded with reason
  3. Another supplier cannot see it
- **Label:** CONTRACT · **Status:** Specified

### REQ-SMP-003 — Store can request samples, work samples and catalogues

- **Source:** `page 6 / پیوست ۳-۱ بند ۹`

> ثبت گزارش تخلف و درخواست ارسال سمپل، نمونه کار و کاتالوگ؛

- **Actors:** Store
- **Statement:** A store requests a sample, work sample or catalogue from a supplier next to the violation-report action.
- **Rules / edge cases:** Catalogue requests return a downloadable file where the supplier uploaded one; physical samples follow REQ-SMP-001.
- **Priority:** P1 · **Depends on:** REQ-SMP-001
- **Acceptance:**
  1. Three request types available
  2. Catalogue downloads when available
  3. Request recorded under the store
- **Label:** CONTRACT · **Status:** Specified

### REQ-SMP-004 — Sample request status tracking for requester and supplier

- **Source:** `page 8 / پیوست ۵ بند ۳۸`

> امکان درخواست سمپل و نمونه کار و مدل با فرم توضیحات و ذکر ابعاد؛

- **Actors:** Store, Supplier
- **Statement:** Each sample request shows its status — submitted, accepted, priced, shipped, closed — to both parties.
- **Rules / edge cases:** Status changes notify the other party; closed requests are read-only; history retained for reporting.
- **Priority:** P2 · **Depends on:** REQ-SMP-002
- **Acceptance:**
  1. Requester sees status changes
  2. Supplier update notifies the requester
  3. Closed request cannot be edited
- **Label:** CONTRACT · **Status:** Specified

<a id="msg"></a>
## MSG — Messaging and tickets

Messaging: persistent buyer-to-any-user chat with attachments, plus the ticket channel and admin moderation.

### REQ-MSG-001 — Persistent one-to-one chat between buyers and any authorised user

- **Source:** `page 8 / پیوست ۵ بند ۲۸`

> قابلیت ایجاد چت آنالین با حافظه ماندگار بین خریداران با هر کاربر، شامل فروشندگان، ادمین و بازاریاب، با پیوست صوت، تصویر، متن و فایل برای ثبت و ماندگاری فرآیند سفارش و کاهش اختالفات؛

- **Actors:** Consumer, Store, Supplier, Marketer, Admin
- **Statement:** A buyer can open a chat with any seller, supplier, marketer or admin; the conversation persists indefinitely and is searchable by both sides.
- **Rules / edge cases:** Conversation is bound to the two participants; history survives logout and device change; retention aligned with the dispute window; no anonymous chat.
- **Priority:** P0 · **Depends on:** REQ-IAM-004
- **Acceptance:**
  1. Chat opens with any permitted role
  2. History persists after logout
  3. Conversation is searchable by both parties
- **Label:** CONTRACT · **Status:** Specified

### REQ-MSG-002 — Chat attachments: audio, image, text and file

- **Source:** `page 8 / پیوست ۵ بند ۲۸`

> با پیوست صوت، تصویر، متن و فایل برای ثبت و ماندگاری فرآیند سفارش و کاهش اختالفات؛

- **Actors:** All chat participants
- **Statement:** Messages may carry text, an image, a file or a voice recording, stored durably so a dispute can be resolved from the conversation record.
- **Rules / edge cases:** Audio capped (2 minutes for order notes, 5 for chat); file types whitelist; size caps; attachments served via signed URLs to participants only.
- **Priority:** P1 · **Depends on:** REQ-MSG-001
- **Acceptance:**
  1. Voice note records and plays in the thread
  2. Image and file uploads render inline
  3. Non-participant cannot open the attachment URL
- **Label:** CONTRACT · **Status:** Specified

### REQ-MSG-003 — Chat restricted to authorised counterparties

- **Source:** `page 6 / پیوست ۳-۲ بند ۵`

> صفحه چت اختصاصی با کاربران مجاز؛

- **Actors:** Marketer
- **Statement:** The marketer's chat page lists only conversations with users it is allowed to contact, and starting a chat with an unauthorised user is refused.
- **Rules / edge cases:** Authorisation derived from attribution and level; new conversation requires an existing relationship (order, visit, ticket) unless admin-enabled.
- **Priority:** P1 · **Depends on:** REQ-MSG-001
- **Acceptance:**
  1. Marketer sees only permitted conversations
  2. Starting a chat with a stranger is refused
  3. Admin override is audited
- **Label:** CONTRACT · **Status:** Specified

### REQ-MSG-004 — Store messaging to users, admins and other stores

- **Source:** `page 5 / پیوست ۳-۱ بند ۳`

> پیام‌رسانی به کاربران، مدیران و سایر فروشگاه‌ها؛

- **Actors:** Store
- **Statement:** A store starts and continues conversations with customers, admins and other stores from a single inbox.
- **Rules / edge cases:** One inbox with filters by counterparty type; unread counts per thread; blocklist respected.
- **Priority:** P1 · **Depends on:** REQ-MSG-001
- **Acceptance:**
  1. Three counterparty types reachable
  2. Unread count shown
  3. Blocked counterparty cannot send
- **Label:** CONTRACT · **Status:** Specified

### REQ-MSG-005 — Ticket channel separate from chat with status and assignment

- **Source:** `page 6 / پیوست ۳-۲ بند ۱`

> ارسال پیام به کاربران و مدیر سایت از طریق تیکت؛

- **Actors:** Marketer, Store, Consumer, Admin
- **Statement:** A ticket carries a subject, category, priority and threaded messages, and has a status (open, pending, closed) with an assigned admin.
- **Rules / edge cases:** Tickets are not real-time chat; each reply reopens a closed ticket; SLA timers apply to admin replies.
- **Priority:** P1 · **Depends on:** REQ-MSG-001
- **Acceptance:**
  1. Ticket created with category and priority
  2. Reply to a closed ticket reopens it
  3. Assignment visible to the requester
- **Label:** CONTRACT · **Status:** Specified

### REQ-MSG-006 — Admin initiates tickets to any level and receives replies

- **Source:** `page 9 / پیوست ۷ بند ۸`

> امکان ارسال تیکت از طرف ادمین به کلیه سطوح مشتریان و کاربری‌ها و پاسخ آنها به‌صورت بلعکس؛

- **Actors:** Admin, all users
- **Statement:** Admin sends a ticket to any user level or a filtered group, and recipients reply in the same thread.
- **Rules / edge cases:** Bulk ticket creates one thread per recipient; replies are private to that recipient; templates supported.
- **Priority:** P1 · **Depends on:** REQ-MSG-005
- **Acceptance:**
  1. Admin ticket reaches a selected level
  2. Recipient reply lands in the same thread
  3. One thread per recipient for a bulk send
- **Label:** CONTRACT · **Status:** Specified

### REQ-MSG-007 — Admin moderation of internal messages

- **Source:** `page 9 / پیوست ۷ بند ۵`

> مدیریت محتوا، مدیریت پیام‌ها و پیام‌رسان داخلی؛

- **Actors:** Admin
- **Statement:** Admin can review reported conversations, remove a message and suspend a participant, with the action recorded.
- **Rules / edge cases:** Moderation only via a report or a legal request, not blanket reading; removed content is soft-deleted with a reason.
- **Priority:** P1 · **Depends on:** REQ-MSG-001
- **Acceptance:**
  1. Reported thread visible to admin
  2. Removal keeps an audit record
  3. Suspended user cannot send messages
- **Label:** CONTRACT · **Status:** Specified

### REQ-MSG-008 — Unread counts and read receipts

- **Source:** `page 8 / پیوست ۵ بند ۲۸`

> قابلیت ایجاد چت آنالین با حافظه ماندگار بین خریداران با هر کاربر

- **Actors:** All chat participants
- **Statement:** Each participant sees unread counts per conversation and whether their message was delivered and read.
- **Rules / edge cases:** Receipts are per message and monotonic; counts reset on opening the thread; no receipt for deleted messages.
- **Priority:** P2 · **Depends on:** REQ-MSG-001
- **Acceptance:**
  1. Unread badge matches unread messages
  2. Receipt flips once the peer opens the thread
  3. Counts survive page reload
- **Label:** CONTRACT · **Status:** Specified

### REQ-MSG-009 — Profile-linked conversation context for orders

- **Source:** `page 8 / پیوست ۵ بند ۲۸`

> با پیوست صوت، تصویر، متن و فایل برای ثبت و ماندگاری فرآیند سفارش و کاهش اختالفات؛

- **Actors:** Buyer, Seller
- **Statement:** Starting a chat from an order or invoice links the conversation to that document, so both parties see the context while talking.
- **Rules / edge cases:** Context chip shows order number and status; the link is immutable; a thread may reference several documents.
- **Priority:** P2 · **Depends on:** REQ-MSG-001
- **Acceptance:**
  1. Chat opened from an order shows the order chip
  2. Chip links to the order page
  3. Removing the order does not delete the chat
- **Label:** CONTRACT · **Status:** Specified

### REQ-MSG-010 — Block and report a chat counterparty

- **Source:** `page 8 / پیوست ۵ بند ۲۸`

> قابلیت ایجاد چت آنالین با حافظه ماندگار بین خریداران با هر کاربر، شامل فروشندگان، ادمین و بازاریاب

- **Actors:** All chat participants
- **Statement:** A user can block a counterparty, which stops new messages, and report the conversation to admin for moderation.
- **Rules / edge cases:** Blocking is per user pair; reports create a moderation item with the thread snapshot; admin can lift a block.
- **Priority:** P2 · **Depends on:** REQ-MSG-007
- **Acceptance:**
  1. Blocked user cannot send
  2. Report creates a moderation item
  3. Admin can unblock
- **Label:** CONTRACT · **Status:** Specified

<a id="ntf"></a>
## NTF — Notifications

Notifications: channel abstraction (SMS, WhatsApp, email, push, in-app), campaigns, templates and delivery logs.

### REQ-NTF-001 — Multi-channel notification delivery with per-channel adapters

- **Source:** `page 9 / پیوست ۷ بند ۳`

> گزارش فروش و ارسال پیام خودکار پس از واریز، ارسال محصول یا ثبت سفارش از طریق پیامک، واتساپ و نوتیفیکیشن؛

- **Also satisfies:** `پیوست ۵ بند ۲۶`
- **Actors:** System
- **Statement:** One notification service delivers through SMS, WhatsApp, email, web push and in-app inbox, each behind an adapter with its own credentials.
- **Rules / edge cases:** Channel failure falls through to the next configured channel; each attempt is logged with provider response.
- **Priority:** P0 · **Depends on:** REQ-CTR-054
- **Acceptance:**
  1. Same event delivered on two channels
  2. A provider outage falls through
  3. Every attempt logged
- **Label:** CONTRACT · **Status:** Specified

### REQ-NTF-002 — Custom notification with audience, count and schedule

- **Source:** `page 7 / پیوست ۵ بند ۹`

> ارسال نوتیفیکیشن سفارشی )متن، عکس، لینک، ویدئو( با تعیین مخاطب، تعداد و زمان ارسال توسط کارفرما؛

- **Actors:** Admin
- **Statement:** Admin composes a notification with text, image, link and video, chooses the audience and count, and schedules the send time.
- **Rules / edge cases:** Audience by level, city, segment or explicit list; count enforced as a hard cap; scheduled sends can be cancelled before dispatch.
- **Priority:** P0 · **Depends on:** REQ-NTF-001
- **Acceptance:**
  1. Notification composes with all four content types
  2. Scheduled send fires at the chosen time
  3. Cancel before dispatch works
- **Label:** CONTRACT · **Status:** Specified

### REQ-NTF-003 — Admin review of paid notification requests before sending

- **Source:** `page 8 / پیوست ۵ بند ۱۲`

> ادمین پس از بررسی آن را از بخش نوتیفیکیشن سفارشی ارسال می‌کند؛

- **Actors:** Admin
- **Statement:** A paid notification request waits in a review queue; admin approves, edits or rejects it, and only approved requests are dispatched.
- **Rules / edge cases:** Approval uses the sender's audience but admin may trim it; rejection refunds to wallet; every decision is logged.
- **Priority:** P1 · **Depends on:** REQ-PAY-005
- **Acceptance:**
  1. Request appears in the review queue
  2. Approved request is dispatched
  3. Rejection refunds the wallet
- **Label:** CONTRACT · **Status:** Specified

### REQ-NTF-004 — SMS provider for one-time codes and transactional messages

- **Source:** `page 5 / پیوست ۲ بند ۸`

> صفحات ورود، ثبت‌نام با تأیید پیامک، بازیابی رمز عبور و ویرایش اطالعات کاربری؛

- **Actors:** System
- **Statement:** OTP and transactional SMS are sent through a configured Iranian SMS provider with delivery status stored per message.
- **Rules / edge cases:** OTP constrained to a pattern; rate limits per number and IP; provider fallback supported.
- **Priority:** P0 · **Depends on:** REQ-NTF-001
- **Acceptance:**
  1. OTP arrives within seconds in staging
  2. Delivery status stored
  3. Rate limit blocks abuse
- **Label:** CONTRACT · **Status:** Specified

### REQ-NTF-005 — Automatic messages after payment, shipment or order registration

- **Source:** `page 9 / پیوست ۷ بند ۳`

> ارسال پیام خودکار پس از واریز، ارسال محصول یا ثبت سفارش از طریق پیامک، واتساپ و نوتیفیکیشن؛

- **Actors:** System
- **Statement:** Payment received, product shipped and order registered each trigger an automatic message to the buyer on SMS, WhatsApp and in-app notification.
- **Rules / edge cases:** One message per event per recipient; templates hold order number and amount; duplicate suppression by event id.
- **Priority:** P1 · **Depends on:** REQ-NTF-001
- **Acceptance:**
  1. Each of the three events sends its message
  2. Duplicate event sends once
  3. Message includes the order number
- **Label:** CONTRACT · **Status:** Specified

### REQ-NTF-006 — Order status push notifications to app and PWA

- **Source:** `page 8 / پیوست ۵ بند ۲۳`

> امکان ارسال نوتیفیکیشن وضعیت سفارش برای اپلیکیشن، از جمله ارسال مرسوله یا سایر اطالعات موردنیاز؛

- **Actors:** System
- **Statement:** Order status changes and shipment events push to installed app/PWA clients with a link to the order page.
- **Rules / edge cases:** Push only to subscribed devices; payload carries no sensitive data; click tracking recorded.
- **Priority:** P1 · **Depends on:** REQ-CTR-054
- **Acceptance:**
  1. Status change pushes to a subscribed device
  2. Click opens the order page
  3. Unsubscribed device receives nothing
- **Label:** CONTRACT · **Status:** Specified

### REQ-NTF-007 — Admin-configurable site-wide notification bar

- **Source:** `page 5 / پیوست ۲ بند ۱۲`

> بخش‌های قابل تنظیم توسط ادمین )فوتر، صفحات ثابت، نوتیفیکیشن باالی سایت، ویجت‌های صفحه اصلی(؛

- **Actors:** Admin
- **Statement:** Admin sets a notification bar at the top of the site with text, link, colour and an active window.
- **Rules / edge cases:** Dismissible per visitor for the day; scheduled start/end; one active bar at a time.
- **Priority:** P2 · **Depends on:** REQ-CMS-001
- **Acceptance:**
  1. Bar appears on all public pages
  2. Dismissal lasts for the day
  3. Expired bar disappears
- **Label:** CONTRACT · **Status:** Specified

### REQ-NTF-008 — Supplier notifications to filtered customers within an admin cap

- **Source:** `page 10 / پیوست ۱۱ بند ۶`

> ارسال نوتیفیکیشن کلی یا فیلترشده به مشتریان با سقف تعداد تعیین‌شده توسط ادمین، به‌صورت پرداختی یا رایگان؛

- **Actors:** Supplier
- **Statement:** A supplier sends a notification to all customers or a filtered subset, capped at the admin-defined count, either free or paid.
- **Rules / edge cases:** Cap enforced per supplier per period; audience limited to customers who interacted with that supplier; paid sends debit the wallet.
- **Priority:** P1 · **Depends on:** REQ-NTF-002
- **Acceptance:**
  1. Filtered audience reaches only matching customers
  2. Cap blocks the send above the limit
  3. Paid mode debits the wallet
- **Label:** CONTRACT · **Status:** Specified

### REQ-NTF-009 — User notification preferences and opt-out

- **Source:** `page 7 / پیوست ۵ بند ۹`

> ارسال نوتیفیکیشن سفارشی )متن، عکس، لینک، ویدئو( با تعیین مخاطب، تعداد و زمان ارسال توسط کارفرما؛

- **Actors:** All users
- **Statement:** Each user controls which channels accept marketing messages, while transactional messages always deliver.
- **Rules / edge cases:** Opt-out stored per channel; transactional classes are not opt-out-able; preference changes take effect immediately.
- **Priority:** P1 · **Depends on:** REQ-NTF-001
- **Acceptance:**
  1. Opting out stops marketing SMS
  2. Transactional messages still arrive
  3. Preference persists
- **Label:** CONTRACT · **Status:** Specified

### REQ-NTF-010 — Occasion and birthday greeting campaigns

- **Source:** `page 11 / پیوست ۱۲ بند ۷`

> ارسال تبریک اعیاد و تولد با ثبت تاریخ تولد مشتری؛

- **Actors:** Admin, System
- **Statement:** Admin schedules greetings for occasions; birthday greetings fire from the stored birth dates on the matching day.
- **Rules / edge cases:** Jalali occasion calendar maintained by admin; one greeting per user per occasion; segment filters supported.
- **Priority:** P2 · **Depends on:** REQ-USR-010
- **Acceptance:**
  1. Birthday greeting sent on the user's date
  2. Occasion campaign sends once
  3. Duplicate run sends once per user
- **Label:** CONTRACT · **Status:** Specified

### REQ-NTF-011 — Notification templates with variables and preview

- **Source:** `page 9 / پیوست ۷ بند ۳`

> ارسال پیام خودکار پس از واریز، ارسال محصول یا ثبت سفارش از طریق پیامک، واتساپ و نوتیفیکیشن؛

- **Actors:** Admin
- **Statement:** Message templates per event and channel support variables (name, order number, amount, date) with a live preview.
- **Rules / edge cases:** Missing variable blocks save; SMS length counted in Persian characters; template versions retained.
- **Priority:** P1 · **Depends on:** REQ-NTF-001
- **Acceptance:**
  1. Preview substitutes sample values
  2. Unknown variable fails validation
  3. Template edit keeps the previous version
- **Label:** CONTRACT · **Status:** Specified

### REQ-NTF-012 — Delivery log with failure retry and dead-letter handling

- **Source:** `page 9 / پیوست ۷ بند ۳`

> گزارش فروش و ارسال پیام خودکار پس از واریز، ارسال محصول یا ثبت سفارش از طریق پیامک، واتساپ و نوتیفیکیشن؛

- **Actors:** Admin, Operations
- **Statement:** Every send is logged with channel, recipient, status and provider id; failures retry with backoff and finally land in a dead-letter list.
- **Rules / edge cases:** Three attempts then dead-letter; admin can resend manually; logs retained 12 months.
- **Priority:** P1 · **Depends on:** REQ-NTF-001
- **Acceptance:**
  1. Failed send retries three times
  2. Dead-letter entry visible to admin
  3. Manual resend succeeds
- **Label:** CONTRACT · **Status:** Specified

<a id="cms"></a>
## CMS — Content management

Content: static pages, blog, rules/FAQ, navigation, widgets and polls, all admin-managed.

### REQ-CMS-001 — Static page management with a rich editor

- **Source:** `page 9 / پیوست ۷ بند ۹`

> مدیریت بنرها، صفحات ثابت، منوها، درگاه‌ها و تنظیمات عمومی سایت؛

- **Actors:** Admin
- **Statement:** Admin creates and edits static pages with a rich text editor, a slug, a title and publish state, and they render in the site layout.
- **Rules / edge cases:** Slug unique and editable with a redirect from the old one; draft pages are hidden; images uploaded through the editor are stored on the public disk.
- **Priority:** P1 · **Depends on:** REQ-CTR-021
- **Acceptance:**
  1. Page created and visible at its slug
  2. Draft is invisible publicly
  3. Old slug redirects after rename
- **Label:** CONTRACT · **Status:** Specified

### REQ-CMS-002 — Blog with index, categories and single-article pages carrying SEO metadata

- **Source:** `page 5 / پیوست ۲ بند ۱۰`

> بالگ )مقاالت( با صفحه فهرست، دسته‌بندی و صفحه تک‌مقاله با متادیتای سئو؛

- **Actors:** Admin, Visitor
- **Statement:** The blog provides an article index, category filtering and single-article pages with title, description, canonical and Open Graph metadata.
- **Rules / edge cases:** Pagination on the index; related articles by category; article author and publish date displayed in Jalali.
- **Priority:** P1 · **Depends on:** REQ-CMS-001
- **Acceptance:**
  1. Index lists published articles with pagination
  2. Category filter narrows the list
  3. Article page emits full SEO metadata
- **Label:** CONTRACT · **Status:** Specified

### REQ-CMS-003 — Purchase rules and FAQ pages before and after purchase

- **Source:** `page 5 / پیوست ۲ بند ۹`

> صفحه قوانین خرید و سؤاالت متداول قبل و بعد از خرید؛

- **Actors:** Admin, Visitor
- **Statement:** Two content blocks cover purchase rules and frequently asked questions, shown before checkout and after purchase.
- **Rules / edge cases:** FAQ items are editable individually with ordering; the rules page is linked from checkout and from the footer.
- **Priority:** P2 · **Depends on:** REQ-CMS-001
- **Acceptance:**
  1. Rules page linked at checkout
  2. FAQ renders with collapsible items
  3. Admin edits an FAQ item without a deploy
- **Label:** CONTRACT · **Status:** Specified

### REQ-CMS-004 — About and contact pages with form and map

- **Source:** `page 5 / پیوست ۲ بند ۱۱`

> صفحات درباره ما، تماس با ما با فرم و نقشه، و پیگیری سفارش با کد رهگیری؛

- **Actors:** Visitor, Admin
- **Statement:** The about page describes the business, and the contact page offers a form plus an embedded map of the office location.
- **Rules / edge cases:** Contact submissions are CAPTCHA-protected and stored as tickets; map coordinates configurable in settings.
- **Priority:** P1 · **Depends on:** REQ-CMS-001
- **Acceptance:**
  1. Contact form submits and creates a ticket
  2. Map shows the configured coordinates
  3. Spam submission is blocked by CAPTCHA
- **Label:** CONTRACT · **Status:** Specified

### REQ-CMS-005 — Configurable footer, menus and homepage widgets

- **Source:** `page 5 / پیوست ۲ بند ۱۲`

> بخش‌های قابل تنظیم توسط ادمین )فوتر، صفحات ثابت، نوتیفیکیشن باالی سایت، ویجت‌های صفحه اصلی(؛

- **Actors:** Admin
- **Statement:** Admin edits footer columns, header/footer menus and homepage widgets with ordering and visibility toggles.
- **Rules / edge cases:** Menu items point to pages, categories or external URLs; changes are live without deployment; broken links are flagged.
- **Priority:** P1 · **Depends on:** REQ-CMS-001
- **Acceptance:**
  1. Footer columns editable
  2. Menu ordering persists
  3. Hidden widget disappears from the homepage
- **Label:** CONTRACT · **Status:** Specified

### REQ-CMS-006 — Homepage block visibility and order controlled by admin

- **Source:** `page 5 / پیوست ۲ بند ۱۲`

> بخش‌های قابل تنظیم توسط ادمین )فوتر، صفحات ثابت، نوتیفیکیشن باالی سایت، ویجت‌های صفحه اصلی(؛

- **Actors:** Admin
- **Statement:** Each homepage block (banner, offers, best sellers, newest, campaigns) can be reordered, retitled or hidden by admin.
- **Rules / edge cases:** At least one block must remain visible; order stored as a sortable list; hidden blocks skip their queries entirely.
- **Priority:** P2 · **Depends on:** REQ-CAT-001
- **Acceptance:**
  1. Reordering changes the homepage
  2. Hidden block renders nothing
  3. Attempt to hide all blocks is refused
- **Label:** CONTRACT · **Status:** Specified

### REQ-CMS-007 — Polls and surveys with results

- **Source:** `page 7 / پیوست ۵ بند ۱۰`

> ایجاد نظرسنجی در سایت برای موضوعات مختلف؛

- **Actors:** Admin, Visitor
- **Statement:** Admin creates a poll with multiple options and a window; visitors vote once and see results according to the admin's settings.
- **Rules / edge cases:** One vote per user or per session as configured; results hidden until the poll closes if configured; totals and percentages displayed.
- **Priority:** P2 · **Depends on:** REQ-CMS-001
- **Acceptance:**
  1. Poll created with options and window
  2. Second vote from the same user is refused
  3. Results match the votes cast
- **Label:** CONTRACT · **Status:** Specified

### REQ-CMS-008 — Store-authored articles with moderation

- **Source:** `page 5 / پیوست ۳-۱ بند ۲`

> دریافت امتیاز از فروش، مقاله، دریافت کامنت و الیک؛

- **Actors:** Store
- **Statement:** A store writes and submits articles that appear on the blog after moderation, and earns points for approved articles.
- **Rules / edge cases:** Author profile linked; rejected article keeps the draft; points awarded on publication only.
- **Priority:** P2 · **Depends on:** REQ-CMS-002
- **Acceptance:**
  1. Store article enters moderation
  2. Publication awards points
  3. Rejection returns an editable draft
- **Label:** CONTRACT · **Status:** Specified

### REQ-CMS-009 — General settings: gateways, tariffs and site parameters

- **Source:** `page 9 / پیوست ۷ بند ۹`

> مدیریت بنرها، صفحات ثابت، منوها، درگاه‌ها و تنظیمات عمومی سایت؛

- **Actors:** Admin
- **Statement:** Admin maintains site parameters — gateway credentials and activation, commission and compensation rates, inactivity windows, page titles and contact data.
- **Rules / edge cases:** Settings are typed and validated; changes audited with old/new values; secrets masked in the UI.
- **Priority:** P0 · **Depends on:** REQ-PAY-001
- **Acceptance:**
  1. Saving a setting persists and takes effect
  2. Reads are audited for secrets
  3. Invalid value is rejected
- **Label:** CONTRACT · **Status:** Specified

### REQ-CMS-010 — Menu builder for header, footer and panel navigation

- **Source:** `page 9 / پیوست ۷ بند ۹`

> مدیریت بنرها، صفحات ثابت، منوها، درگاه‌ها و تنظیمات عمومی سایت؛

- **Actors:** Admin
- **Statement:** Admin builds menus as ordered trees of links to pages, categories or URLs, with per-menu assignment to a location.
- **Rules / edge cases:** Two-level maximum; drag ordering; disabled items hidden without deletion.
- **Priority:** P2 · **Depends on:** REQ-CMS-005
- **Acceptance:**
  1. Menu tree saved with order
  2. Item hidden without deletion
  3. Location assignment changes the rendered menu
- **Label:** CONTRACT · **Status:** Specified

### REQ-CMS-011 — Per-content SEO fields with preview

- **Source:** `page 5 / پیوست ۲ بند ۱۰`

> بالگ )مقاالت( با صفحه فهرست، دسته‌بندی و صفحه تک‌مقاله با متادیتای سئو؛

- **Actors:** Admin
- **Statement:** Every page, article and category carries editable SEO title, description, canonical and social image, with a search-result preview.
- **Rules / edge cases:** Defaults derived from content when empty; length guidance shown; duplicates warned but not blocked.
- **Priority:** P1 · **Depends on:** REQ-CTR-040
- **Acceptance:**
  1. Fields save per content item
  2. Preview reflects the entered values
  3. Empty fields fall back to defaults
- **Label:** CONTRACT · **Status:** Specified

### REQ-CMS-012 — Draft and publish workflow with revision history

- **Source:** `page 9 / پیوست ۷ بند ۵`

> مدیریت محتوا، مدیریت پیام‌ها و پیام‌رسان داخلی؛

- **Actors:** Admin
- **Statement:** Content moves through draft, review and published states, and each save keeps a revision that can be compared or restored.
- **Rules / edge cases:** Publishing requires a complete SEO title; unpublishing keeps the slug reserved; revisions retained 90 days.
- **Priority:** P2 · **Depends on:** REQ-CMS-001
- **Acceptance:**
  1. Draft not publicly visible
  2. Publish flips visibility
  3. Previous revision restorable
- **Label:** CONTRACT · **Status:** Specified

### REQ-CMS-013 — Rich text editor with safe HTML sanitiser

- **Source:** `page 9 / پیوست ۷ بند ۵`

> مدیریت محتوا، مدیریت پیام‌ها و پیام‌رسان داخلی؛

- **Actors:** Admin, Store
- **Statement:** Authors write formatted content (headings, lists, links, images, tables) and the stored HTML is sanitised against a whitelist.
- **Rules / edge cases:** Scripts and event handlers stripped; external images proxied or blocked; pasted content cleaned.
- **Priority:** P1 · **Depends on:** REQ-CMS-001
- **Acceptance:**
  1. Script tag is stripped on save
  2. Table and image render correctly
  3. Pasted Word markup is cleaned
- **Label:** CONTRACT · **Status:** Specified

<a id="rvw"></a>
## RVW — Reviews and ratings

Reviews and ratings: product, supplier and received-goods feedback with parameters, windows and moderation.

### REQ-RVW-001 — Product review with rating, comment and moderation

- **Source:** `page 5 / پیوست ۲ بند ۴`

> صفحه تک‌محصول با گالری تصاویر، ویدئو، مشخصات فنی، نظرات و امتیاز خریداران، محصوالت مرتبط و امکان افزودن به مقایسه؛

- **Actors:** Consumer, Store
- **Statement:** A buyer submits a star rating and written review on a product; the review appears after moderation and updates the product average.
- **Rules / edge cases:** Only verified buyers; one review per purchase; profanity filter; seller reply allowed once.
- **Priority:** P0 · **Depends on:** REQ-IAM-008
- **Acceptance:**
  1. Verified buyer can post a review
  2. Average recalculates
  3. Unverified user sees no form
- **Label:** CONTRACT · **Status:** Specified

### REQ-RVW-002 — Satisfaction rating for received goods on five to ten parameters

- **Source:** `page 7 / پیوست ۴ بند ۱۷`

> امکان ثبت امتیاز و نظردهی درباره رضایت از روتکافٔ دریافتی تا یک هفته پس از ارسال، با پنج تا ده پارامتر امتیازدهی؛

- **Also satisfies:** `پیوست ۵ بند ۳۳`
- **Actors:** Consumer, Store
- **Statement:** Within one week of shipment the buyer rates satisfaction with the received goods across five to ten configurable parameters plus free text.
- **Rules / edge cases:** Parameter set admin-defined per product type; all parameters required; window closes seven days after the shipment date.
- **Priority:** P1 · **Depends on:** REQ-ORD-018
- **Acceptance:**
  1. Form shows all configured parameters
  2. Submission after seven days is refused
  3. Scores aggregate per parameter
- **Label:** CONTRACT · **Status:** Ambiguous

### REQ-RVW-003 — Review of the supplier as well as the product

- **Source:** `page 11 / پیوست ۱۲ بند ۹`

> ثبت نظر و امتیاز برای محصول و عرضه‌کننده تا یک هفته پس از دریافت؛

- **Actors:** Consumer, Store
- **Statement:** The same review flow rates the supplier separately from the product, and both scores are published on their pages.
- **Rules / edge cases:** Supplier score aggregates across products; one supplier rating per order; supplier cannot rate itself.
- **Priority:** P1 · **Depends on:** REQ-RVW-001
- **Acceptance:**
  1. Supplier score appears on its page
  2. Review captures both scores
  3. Supplier cannot self-rate
- **Label:** CONTRACT · **Status:** Specified

### REQ-RVW-004 — Points awarded for comments and likes received

- **Source:** `page 5 / پیوست ۳-۱ بند ۲`

> دریافت امتیاز از فروش، مقاله، دریافت کامنت و الیک؛

- **Actors:** Store
- **Statement:** Receiving a comment or a like on published content awards loyalty points according to the rule set.
- **Rules / edge cases:** Daily cap; self-likes excluded; points reversed if the content is removed.
- **Priority:** P2 · **Depends on:** REQ-LOY-017
- **Acceptance:**
  1. A like awards the configured points
  2. Self-like awards nothing
  3. Removal reverses the points
- **Label:** CONTRACT · **Status:** Specified

### REQ-RVW-005 — Seven-day review window after delivery

- **Source:** `page 11 / پیوست ۱۲ بند ۹`

> ثبت نظر و امتیاز برای محصول و عرضه‌کننده تا یک هفته پس از دریافت؛

- **Actors:** Consumer
- **Statement:** The review form is available for seven days after delivery confirmation and then closes, while existing reviews stay visible.
- **Rules / edge cases:** Window measured from the delivery event, not the order date; admin can reopen a specific window for a dispute.
- **Priority:** P1 · **Depends on:** REQ-ORD-018
- **Acceptance:**
  1. Form open on day 6, closed on day 8
  2. Existing reviews remain visible
  3. Admin reopen is logged
- **Label:** CONTRACT · **Status:** Specified

### REQ-RVW-006 — Review moderation queue with abuse handling

- **Source:** `page 5 / پیوست ۲ بند ۴`

> نظرات و امتیاز خریداران

- **Actors:** Admin
- **Statement:** Reviews enter a moderation queue where admin approves, rejects with a reason, or removes an abusive review after a report.
- **Rules / edge cases:** Auto-approve can be enabled per category; rejected reviews notify the author; moderated actions are audited.
- **Priority:** P1 · **Depends on:** REQ-RVW-001
- **Acceptance:**
  1. Queue lists pending reviews
  2. Rejection requires a reason
  3. Removal is audited
- **Label:** CONTRACT · **Status:** Specified

### REQ-RVW-007 — Verified-purchase badge on reviews

- **Source:** `page 5 / پیوست ۲ بند ۴`

> نظرات و امتیاز خریداران

- **Actors:** Visitor
- **Statement:** Reviews from buyers with a delivered order carry a verified-purchase badge, distinguishing them from other feedback.
- **Rules / edge cases:** Badge derives from the order link, never from a manual flag; badge survives later deletion of the order record (soft delete).
- **Priority:** P2 · **Depends on:** REQ-RVW-001
- **Acceptance:**
  1. Verified review shows the badge
  2. Non-purchase feedback has no badge
  3. Badge not editable from the panel
- **Label:** CONTRACT · **Status:** Specified

### REQ-RVW-008 — Aggregate scores for product, supplier and service

- **Source:** `page 11 / پیوست ۱۲ بند ۹`

> ثبت نظر و امتیاز برای محصول و عرضه‌کننده تا یک هفته پس از دریافت؛

- **Actors:** Visitor
- **Statement:** Product, supplier and service-provider pages show a weighted average score and a distribution of ratings.
- **Rules / edge cases:** Average recomputed on approval; recalculated nightly as a safety net; shown to one decimal with the count.
- **Priority:** P1 · **Depends on:** REQ-RVW-003
- **Acceptance:**
  1. Average equals the arithmetic mean of approved ratings
  2. Distribution sums to the count
  3. Supplier page shows its own average
- **Label:** CONTRACT · **Status:** Specified

### REQ-RVW-009 — Seller reply to a review

- **Source:** `page 5 / پیوست ۲ بند ۴`

> نظرات و امتیاز خریداران

- **Actors:** Store, Supplier
- **Statement:** The seller may post one public reply to a review of its product, shown beneath the review.
- **Rules / edge cases:** Reply is editable for 24 hours then locked; reply is moderated like a review; no reply to a reply.
- **Priority:** P2 · **Depends on:** REQ-RVW-001
- **Acceptance:**
  1. Seller reply appears under the review
  2. Second reply is refused
  3. Reply locked after 24 hours
- **Label:** CONTRACT · **Status:** Specified

<a id="geo"></a>
## GEO — Field operations and geolocation

Field operations: visit geolocation, live marketer tracking and performance/route analysis.

### REQ-GEO-001 — Capture the marketer's location at every store visit

- **Source:** `page 11 / پیوست ۱۴ بند ۱`

> امکان تبث تیعقوم یناکم) Location )برای ییاهناکم هک بازاریاب تیزیو میکند؛

- **Actors:** Marketer
- **Statement:** Every visit records the marketer's coordinates, timestamp and accuracy, captured from the device at the moment of the visit.
- **Rules / edge cases:** Browser geolocation permission required; manual pin fallback with a flag; accuracy worse than 200 m is flagged for review.
- **Priority:** P0 · **Depends on:** REQ-MER-004
- **Acceptance:**
  1. Visit stores latitude, longitude, accuracy and time
  2. Permission denial blocks submission with guidance
  3. Low-accuracy visit is flagged
- **Label:** CONTRACT · **Status:** Specified

### REQ-GEO-002 — Live marketer tracking during working hours

- **Source:** `page 11 / پیوست ۱۴ بند ۲`

> امکان رهگیری) Tracking )اهبایرازاب به‌صورت زنده) Live )و لیلحت دادههای عملکرد و ریسم) Analysis Data)؛

- **Actors:** Admin
- **Statement:** While a marketer is on shift the system records periodic location pings so admin can see the current position on a map.
- **Rules / edge cases:** Tracking is opt-in per shift and visible to the marketer; pings stop at shift end; history retained 90 days.
- **Priority:** P1 · **Depends on:** REQ-GEO-001
- **Acceptance:**
  1. Map shows the marketer's latest position
  2. Pings stop when the shift ends
  3. Marketer can see that tracking is active
- **Label:** CONTRACT · **Status:** Ambiguous

### REQ-GEO-003 — Performance and route analysis per marketer

- **Source:** `page 11 / پیوست ۱۴ بند ۲`

> لیلحت دادههای عملکرد و ریسم) Analysis Data)؛

- **Actors:** Admin
- **Statement:** The system analyses visits per marketer and per period — count, coverage, conversion to registration and distance travelled — and reports it.
- **Rules / edge cases:** Distance from consecutive pings with a minimum-interval filter; period comparison; exportable.
- **Priority:** P1 · **Depends on:** REQ-GEO-002
- **Acceptance:**
  1. Report shows visits, conversions and distance
  2. Period comparison works
  3. Export matches the screen
- **Label:** CONTRACT · **Status:** Specified

### REQ-GEO-004 — Geofence check that a visit happened at the store

- **Source:** `page 9 / پیوست ۶ بند ۲`

> ثبت لوکیشن و آدرس گوگل مپ؛

- **Actors:** System, Marketer
- **Statement:** A visit is accepted only when the captured coordinates fall within a configurable radius of the store's recorded pin.
- **Rules / edge cases:** Radius default 150 m, admin-configurable; exceptions require a reason and admin approval; fraud attempts are logged.
- **Priority:** P1 · **Depends on:** REQ-GEO-001
- **Acceptance:**
  1. Visit outside the radius is held for approval
  2. Inside the radius auto-accepts
  3. Override requires a reason
- **Label:** AGENT_DECISION · **Status:** Specified

### REQ-GEO-005 — Map view of the marketer's stores and planned route

- **Source:** `page 11 / پیوست ۱۴ بند ۲`

> امکان رهگیری) Tracking )اهبایرازاب به‌صورت زنده) Live )و لیلحت دادههای عملکرد و ریسم) Analysis Data)؛

- **Actors:** Marketer, Admin
- **Statement:** The panel plots the marketer's assigned stores on a map and proposes a visiting order for a working day.
- **Rules / edge cases:** Order optimised by nearest-neighbour; max stops per day configurable; the route is a suggestion, not a constraint.
- **Priority:** P2 · **Depends on:** REQ-MER-004
- **Acceptance:**
  1. All assigned stores appear on the map
  2. Suggested order is geographically coherent
  3. Marketer can ignore the suggestion
- **Label:** AGENT_DECISION · **Status:** Specified

### REQ-GEO-006 — Store coverage heat map by city and district

- **Source:** `page 8 / پیوست ۵ بند ۲۹`

> امکان ثبت اطالعات و گزارش‌گیری از فروشگاه‌ها و مشتریان ویزیت‌شده با تأیید ادمین و دسترسی محدود بازاریاب به اطالعات فروشگاه‌های مربوط به خود؛

- **Actors:** Admin
- **Statement:** Admin views a heat map of visited and registered stores by city and district to direct field effort.
- **Rules / edge cases:** Aggregated only, no personal data; filter by period and marketer; exportable as an image.
- **Priority:** P2 · **Depends on:** REQ-MER-004
- **Acceptance:**
  1. Map renders density per district
  2. Filters change the map
  3. No personal data exposed
- **Label:** AGENT_DECISION · **Status:** Specified

### REQ-GEO-007 — Location permission and privacy notice for tracking

- **Source:** `page 11 / پیوست ۱۴ بند ۱`

> امکان تبث تیعقوم یناکم) Location )برای ییاهناکم هک بازاریاب تیزیو میکند؛

- **Actors:** Marketer
- **Statement:** Before any location capture the marketer sees a Persian privacy notice and explicitly consents; consent is recorded with a timestamp.
- **Rules / edge cases:** Refusal prevents field-visit submission but not panel access; consent revocable; notice states retention period.
- **Priority:** P0 · **Depends on:** REQ-GEO-001
- **Acceptance:**
  1. Consent screen shown on first capture
  2. Refusal blocks visit submission
  3. Consent and revocation are stored
- **Label:** AGENT_DECISION · **Status:** Specified

<a id="adm"></a>
## ADM — Admin console

Admin console: member and content management, financial oversight, moderation, settings and audit.

### REQ-ADM-001 — Member management: deactivate, delete, edit and approve

- **Source:** `page 9 / پیوست ۷ بند ۱`

> مدیریت اعضا: غیرفعال‌سازی، حذف، تغییر اطالعات کاربری، تأیید اعضا و اطالعات آنها؛

- **Actors:** Admin
- **Statement:** Admin lists members with search and filters and can deactivate, delete (soft), edit their information or approve a pending account.
- **Rules / edge cases:** Deletion is soft with anonymisation after a retention period; deactivation blocks login without deleting data; every action is audited.
- **Priority:** P0 · **Depends on:** REQ-IAM-010
- **Acceptance:**
  1. Each of the four actions works from the member list
  2. Deactivated user cannot log in
  3. Deletion keeps financial history intact
- **Label:** CONTRACT · **Status:** Specified

### REQ-ADM-002 — Approval queue for new members and profile changes

- **Source:** `page 6 / پیوست ۳-۵`

> دسترسی کامل به همه بخش‌ها، تأیید کاربران و نظارت بر عملکرد سایر سطوح.

- **Actors:** Admin
- **Statement:** Pending registrations and profile-change requests appear in one queue with the submitted data and supporting documents.
- **Rules / edge cases:** Approve or reject with reason; SLA visible; bulk approve for low-risk changes.
- **Priority:** P0 · **Depends on:** REQ-USR-007
- **Acceptance:**
  1. Pending items listed with evidence
  2. Approval publishes the change
  3. Rejection notifies the user
- **Label:** CONTRACT · **Status:** Specified

### REQ-ADM-003 — Financial overview: transactions, sold products and per-user invoice totals

- **Source:** `page 9 / پیوست ۷ بند ۲`

> مدیریت مالی: نمایش تراکنش‌های انجام‌شده، گزارش محصوالت فروخته‌شده، ایجاد فاکتور خرید دستی، جمع مبالغ و فاکتورهای کاربران؛

- **Actors:** Admin
- **Statement:** A financial screen shows transactions, a sold-products report and per-user invoice totals with drill-down to documents.
- **Rules / edge cases:** Figures derive from the ledger; date range and level filters; export available.
- **Priority:** P0 · **Depends on:** REQ-FIN-001
- **Acceptance:**
  1. Totals reconcile with the ledger
  2. Sold-products report matches invoices
  3. Drill-down opens the source document
- **Label:** CONTRACT · **Status:** Specified

### REQ-ADM-004 — Sales report with automatic post-event messaging

- **Source:** `page 9 / پیوست ۷ بند ۳`

> گزارش فروش و ارسال پیام خودکار پس از واریز، ارسال محصول یا ثبت سفارش از طریق پیامک، واتساپ و نوتیفیکیشن؛

- **Actors:** Admin
- **Statement:** The sales report is filterable by period and seller, and each of the three events triggers an automatic message configured in the notification module.
- **Rules / edge cases:** Report and messages share the same event source; message failures do not block the report.
- **Priority:** P1 · **Depends on:** REQ-NTF-005
- **Acceptance:**
  1. Report shows sales for the period
  2. Each event produces a message
  3. Message failure is logged separately
- **Label:** CONTRACT · **Status:** Specified

### REQ-ADM-005 — Content and internal messenger management from one console

- **Source:** `page 9 / پیوست ۷ بند ۵`

> مدیریت محتوا، مدیریت پیام‌ها و پیام‌رسان داخلی؛

- **Actors:** Admin
- **Statement:** Admin reaches content editing, message moderation and the internal messenger from one console with consistent permissions.
- **Rules / edge cases:** Each sub-console respects its own permission; actions are audited; no cross-module direct table access.
- **Priority:** P1 · **Depends on:** REQ-CMS-001, REQ-MSG-007
- **Acceptance:**
  1. All three consoles reachable
  2. Permissions enforced per console
  3. Audit entries per action
- **Label:** CONTRACT · **Status:** Specified

### REQ-ADM-006 — Moderation console for user and content reports

- **Source:** `page 9 / پیوست ۷ بند ۷`

> گزینه گزارش محتوا یا کاربر در صفحات کاربری؛

- **Actors:** Admin
- **Statement:** Reports from users, marketers and invoices land in a moderation console where admin can warn, suspend, remove content or dismiss.
- **Rules / edge cases:** Every decision requires a note; repeat offenders escalate automatically; the reporter sees the outcome.
- **Priority:** P1 · **Depends on:** REQ-USR-012
- **Acceptance:**
  1. All report sources appear in one queue
  2. Decision recorded with a note
  3. Reporter notified
- **Label:** CONTRACT · **Status:** Specified

### REQ-ADM-007 — Admin-issued tickets to any level with reply tracking

- **Source:** `page 9 / پیوست ۷ بند ۸`

> امکان ارسال تیکت از طرف ادمین به کلیه سطوح مشتریان و کاربری‌ها و پاسخ آنها به‌صورت بلعکس؛

- **Actors:** Admin
- **Statement:** Admin composes a ticket to a level, a segment or a single user and tracks replies and resolution per thread.
- **Rules / edge cases:** Templates and attachments supported; resolution requires closing with a category.
- **Priority:** P1 · **Depends on:** REQ-MSG-006
- **Acceptance:**
  1. Ticket delivered to the chosen audience
  2. Replies visible in the same thread
  3. Closing requires a category
- **Label:** CONTRACT · **Status:** Specified

### REQ-ADM-008 — Operational reports for marketers, stores, transactions and settlements with Excel export

- **Source:** `page 9 / پیوست ۷ بند ۱۰`

> گزارش فعالیت بازاریاب‌ها، فروشگاه‌ها، تراکنش‌ها و وضعیت تسویه‌ها با خروجی اکسل؛

- **Actors:** Admin
- **Statement:** Four operational reports — marketer activity, store activity, transactions and settlement status — each export to Excel with the applied filters.
- **Rules / edge cases:** Exports stream for large ranges; the file header records filters and generation time.
- **Priority:** P1 · **Depends on:** REQ-RPT-004
- **Acceptance:**
  1. Each of the four reports exports
  2. Export respects active filters
  3. File header states filters and date
- **Label:** CONTRACT · **Status:** Specified

### REQ-ADM-009 — Audit log viewer with before/after values

- **Source:** `page 5 / پیوست ۱ بند ۱۰`

> الگ کامل رویدادهای حساس )ورود، تغییر قیمت، تغییر سطح کاربری، تسویه( برای پیگیری.

- **Actors:** Admin
- **Statement:** Admin searches the audit log by actor, event type, subject and date and sees before/after values for each entry.
- **Rules / edge cases:** Read-only; exportable; retention at least five years; personal data masked where required.
- **Priority:** P1 · **Depends on:** REQ-CTR-014
- **Acceptance:**
  1. Search by actor returns their entries
  2. Before/after values shown
  3. Export produces the filtered set
- **Label:** CONTRACT · **Status:** Specified

### REQ-ADM-010 — Bulk deactivation of users by admin

- **Source:** `page 9 / پیوست ۵ بند ۴۳`

> غیرفعال‌سازی کلی کاربران توسط ادمین

- **Actors:** Admin
- **Statement:** Admin deactivates a filtered set of users in one action, with a confirmation step showing the affected count.
- **Rules / edge cases:** Excludes admins and users with open financial obligations; each deactivation is audited individually.
- **Priority:** P2 · **Depends on:** REQ-ADM-001
- **Acceptance:**
  1. Bulk action shows the affected count
  2. Admin accounts are excluded
  3. Each user gets an audit row
- **Label:** CONTRACT · **Status:** Specified

### REQ-ADM-011 — Role and permission matrix editor

- **Source:** `page 5 / پیوست ۱ بند ۸`

> ساختار پایگاه داده به‌گونه‌ای که امکان افزودن سطح کاربری جدید یا نوع محصول تازه بدون بازنویسی هسته فراهم باشد؛

- **Actors:** Admin
- **Statement:** Admin edits which capabilities each level holds and creates a new level from a template, without a deployment.
- **Rules / edge cases:** Core capabilities are protected from removal; changes take effect on next request; the matrix is versioned.
- **Priority:** P1 · **Depends on:** REQ-IAM-004
- **Acceptance:**
  1. Toggling a capability changes access immediately
  2. New level created from a template
  3. Protected capabilities cannot be removed
- **Label:** CONTRACT · **Status:** Specified

### REQ-ADM-012 — Identity document review for store and supplier verification

- **Source:** `page 9 / پیوست ۷ بند ۱`

> تأیید اعضا و اطالعات آنها؛

- **Actors:** Admin
- **Statement:** Uploaded identity documents are reviewed in the panel and a verified badge is granted or refused with a reason.
- **Rules / edge cases:** Documents on a private disk with signed URLs; access logged; documents never exposed on public pages.
- **Priority:** P1 · **Depends on:** REQ-CTR-028
- **Acceptance:**
  1. Documents open only for authorised admins
  2. Verification state shown on the profile
  3. Access to each document is logged
- **Label:** CONTRACT · **Status:** Specified

### REQ-ADM-013 — Wallet and account adjustments with approval threshold

- **Source:** `page 11 / پیوست ۱۱ بند ۱۶`

> امکان اعمال هزینه یا پرداخت جدید در حساب عرضه‌کننده توسط ادمین به‌صورت دستی؛

- **Actors:** Admin
- **Statement:** Admin credits or debits a user's wallet or supplier account; amounts above a threshold require a second admin's approval.
- **Rules / edge cases:** Reason mandatory; the adjustment is a ledger entry; the affected user sees it on their statement.
- **Priority:** P1 · **Depends on:** REQ-FIN-005
- **Acceptance:**
  1. Adjustment below threshold posts immediately
  2. Above threshold waits for a second approver
  3. User statement shows the entry
- **Label:** CONTRACT · **Status:** Specified

### REQ-ADM-014 — Violation and complaint registry with status flow

- **Source:** `page 8 / پیوست ۵ بند ۱۴`

> وجود بخش وارن در پروفایل تولیدکنندگان برای گزارش تخلف؛

- **Actors:** Admin
- **Statement:** Complaints and warnings are stored in a registry with category, evidence, subject and a status flow from open to resolved or dismissed.
- **Rules / edge cases:** Each entry links to the source document (invoice, profile, chat); resolved entries feed the violation score.
- **Priority:** P1 · **Depends on:** REQ-CMP-003
- **Acceptance:**
  1. Registry lists entries with source links
  2. Status flow enforced
  3. Resolved entries affect the score
- **Label:** CONTRACT · **Status:** Specified

### REQ-ADM-015 — Dashboard KPIs for orders, revenue, registrations and open issues

- **Source:** `page 9 / پیوست ۷ بند ۱۰`

> گزارش فعالیت بازاریاب‌ها، فروشگاه‌ها، تراکنش‌ها و وضعیت تسویه‌ها با خروجی اکسل؛

- **Actors:** Admin
- **Statement:** The admin home shows today's orders, revenue, new registrations, pending approvals and open complaints with links to each list.
- **Rules / edge cases:** Numbers cached for five minutes; timezone Asia/Tehran; each tile links to a filtered list.
- **Priority:** P1 · **Depends on:** REQ-RPT-001
- **Acceptance:**
  1. Tiles show today's figures
  2. Each tile links to its list
  3. Figures match the underlying report
- **Label:** CONTRACT · **Status:** Specified

### REQ-ADM-016 — Manual order and invoice creation by admin

- **Source:** `page 9 / پیوست ۷ بند ۲`

> ایجاد فاکتور خرید دستی، جمع مبالغ و فاکتورهای کاربران؛

- **Actors:** Admin
- **Statement:** Admin creates an order on a user's behalf, choosing products, prices and address, then issues its invoice and payment link.
- **Rules / edge cases:** Prices cannot go below the level price without a reason; the order follows the normal state machine; the acting admin is recorded.
- **Priority:** P1 · **Depends on:** REQ-ORD-016
- **Acceptance:**
  1. Order created for a chosen user
  2. Invoice generated from it
  3. Admin identity recorded on both
- **Label:** CONTRACT · **Status:** Specified

### REQ-ADM-017 — Supplier and store profile administration

- **Source:** `page 9 / پیوست ۷ بند ۱`

> مدیریت اعضا: غیرفعال‌سازی، حذف، تغییر اطالعات کاربری، تأیید اعضا و اطالعات آنها؛

- **Actors:** Admin
- **Statement:** Admin edits a store's or supplier's business information, changes its activation state and reassigns its marketer.
- **Rules / edge cases:** Edits require a reason when the owner did not request them; reassignment keeps historical commission intact.
- **Priority:** P1 · **Depends on:** REQ-MER-013
- **Acceptance:**
  1. Admin edit persists and is audited
  2. Reassignment moves future invoices only
  3. Owner is notified of the change
- **Label:** CONTRACT · **Status:** Specified

### REQ-ADM-018 — Segments builder for targeting and reporting

- **Source:** `page 7 / پیوست ۵ بند ۹`

> ارسال نوتیفیکیشن سفارشی )متن، عکس، لینک، ویدئو( با تعیین مخاطب، تعداد و زمان ارسال توسط کارفرما؛

- **Actors:** Admin
- **Statement:** Admin builds reusable audience segments from level, city, points band, activity and purchase history, and previews the size.
- **Rules / edge cases:** Segments evaluated at use time; size preview capped for performance; saved segments editable.
- **Priority:** P2 · **Depends on:** REQ-PRM-010
- **Acceptance:**
  1. Segment saves with its rules
  2. Size preview matches the actual send
  3. Editing a segment affects future uses only
- **Label:** CONTRACT · **Status:** Specified

### REQ-ADM-019 — Support view of a user's account state without impersonation

- **Source:** `page 9 / پیوست ۷ بند ۱`

> مدیریت اعضا: غیرفعال‌سازی، حذف، تغییر اطالعات کاربری، تأیید اعضا و اطالعات آنها؛

- **Actors:** Admin
- **Statement:** Admin inspects a user's orders, wallet, points and tickets read-only from one screen without logging in as them.
- **Rules / edge cases:** No impersonation in v1 (security decision); every inspection is logged.
- **Priority:** P2 · **Depends on:** REQ-ADM-001
- **Acceptance:**
  1. Single screen shows the four data sets
  2. No impersonation endpoint exists
  3. Inspection is logged
- **Label:** AGENT_DECISION · **Status:** Specified

<a id="rpt"></a>
## RPT — Reporting

Reporting: filterable sales and operational reports, profit-and-cost statement, exports and read-only analytics.

### REQ-RPT-001 — Filterable sales report by period, amount, date and product type

- **Source:** `page 11 / پیوست ۱۳ بند ۱`

> گزارش دقیق و فیلترپذیر فروش برای تسویه دستی با تولیدکنندگان بر اساس بازه زمانی، مبلغ، تاریخ و نوع محصول؛

- **Actors:** Admin, Finance
- **Statement:** A sales report filters by date range, amount band, seller and product type and totals sales, returns and net for settlement.
- **Rules / edge cases:** Totals reconcile to invoices; filters combinable; results paginated with a stable sort.
- **Priority:** P0 · **Depends on:** REQ-INV-003
- **Acceptance:**
  1. Each filter changes the result correctly
  2. Net total equals sales minus returns
  3. Export matches the screen
- **Label:** CONTRACT · **Status:** Specified

### REQ-RPT-002 — Revenue and cost statement with net profit for profit sharing

- **Source:** `page 4 / ماده ۱۶`

> مبنای سود صلاخ، لک دمآرد فروش هناماس سپ از رسک بهای تمام‌شده الاک، کارمزد درگاه، هنیزه ارسال و مالیات تسا.

- **Actors:** Finance
- **Statement:** A monthly statement computes gross sales revenue, cost of goods sold, gateway fees, shipping cost and tax, then net profit and the vendor's 15% share.
- **Rules / edge cases:** Costs sourced from invoices, gateway fee records and shipping lines; tax is a configured rate; the statement is reproducible per period.
- **Priority:** P1 · **Depends on:** REQ-PAY-014
- **Acceptance:**
  1. Statement lists all five cost components
  2. Net profit equals revenue minus costs
  3. 15% share computed and stored per period
- **Label:** CONTRACT · **Status:** Specified

### REQ-RPT-003 — Sold-products report

- **Source:** `page 9 / پیوست ۷ بند ۲`

> گزارش محصوالت فروخته‌شده

- **Actors:** Admin, Supplier, Store
- **Statement:** A report lists sold products with quantity, revenue, returns and margin for the selected period, scoped to the viewer's own catalogue where applicable.
- **Rules / edge cases:** Aggregated per product and variant; returns netted; margin uses the snapshot cost at sale time.
- **Priority:** P1 · **Depends on:** REQ-INV-001
- **Acceptance:**
  1. Report totals equal invoiced quantities
  2. Supplier sees only its products
  3. Returns reduce the quantity
- **Label:** CONTRACT · **Status:** Specified

### REQ-RPT-004 — Excel export service shared by all reports

- **Source:** `page 9 / پیوست ۷ بند ۱۰`

> گزارش فعالیت بازاریاب‌ها، فروشگاه‌ها، تراکنش‌ها و وضعیت تسویه‌ها با خروجی اکسل؛

- **Actors:** Admin
- **Statement:** Every report offers an Excel export that streams the full filtered result rather than only the visible page.
- **Rules / edge cases:** RTL headers in Persian; numbers as numeric cells; large exports queued and delivered by notification.
- **Priority:** P1 · **Depends on:** REQ-RPT-001
- **Acceptance:**
  1. Export contains all filtered rows
  2. Numbers are numeric cells
  3. Large export completes via queue
- **Label:** CONTRACT · **Status:** Specified

### REQ-RPT-005 — Marketer activity report

- **Source:** `page 9 / پیوست ۷ بند ۱۰`

> گزارش فعالیت بازاریاب‌ها

- **Actors:** Admin
- **Statement:** A report shows per-marketer visits, registrations, active stores, invoiced volume and commission for a period.
- **Rules / edge cases:** Metrics from the same queries as the panel; zero-activity marketers included; exportable.
- **Priority:** P1 · **Depends on:** REQ-MKT-015
- **Acceptance:**
  1. Each marketer has a row
  2. Figures match the marketer panel
  3. Zero-activity rows present
- **Label:** CONTRACT · **Status:** Specified

### REQ-RPT-006 — Store activity report

- **Source:** `page 9 / پیوست ۷ بند ۱۰`

> گزارش فعالیت فروشگاه‌ها

- **Actors:** Admin
- **Statement:** A report classifies stores as active or inactive by invoice activity in the admin window and shows last-invoice date and volume.
- **Rules / edge cases:** Window configurable; classification recomputed nightly; inactive list exportable.
- **Priority:** P1 · **Depends on:** REQ-MER-011
- **Acceptance:**
  1. Classification matches the rule
  2. Last invoice date shown
  3. Window change reclassifies
- **Label:** CONTRACT · **Status:** Specified

### REQ-RPT-007 — Transaction report with gateway breakdown

- **Source:** `page 9 / پیوست ۷ بند ۱۰`

> گزارش فعالیت بازاریاب‌ها، فروشگاه‌ها، تراکنش‌ها و وضعیت تسویه‌ها با خروجی اکسل؛

- **Actors:** Admin
- **Statement:** A transaction report lists every payment with gateway, amount, fee, status and order reference, filterable by all of them.
- **Rules / edge cases:** Includes failed and refunded rows with a status filter; totals per gateway.
- **Priority:** P1 · **Depends on:** REQ-PAY-012
- **Acceptance:**
  1. All statuses present
  2. Gateway subtotals correct
  3. Filters combine
- **Label:** CONTRACT · **Status:** Specified

### REQ-RPT-008 — Settlement status report

- **Source:** `page 9 / پیوست ۷ بند ۱۰`

> گزارش فعالیت بازاریاب‌ها، فروشگاه‌ها، تراکنش‌ها و وضعیت تسویه‌ها با خروجی اکسل؛

- **Actors:** Admin, Finance
- **Statement:** A report shows per-account settlement state: accrued, requested, approved, paid and frozen amounts with ageing.
- **Rules / edge cases:** Frozen amounts excluded from payable; each row links to the settlement request.
- **Priority:** P1 · **Depends on:** REQ-FIN-012
- **Acceptance:**
  1. States per account are correct
  2. Frozen amounts separated
  3. Row links to the request
- **Label:** CONTRACT · **Status:** Specified

### REQ-RPT-009 — Visit and customer acquisition report

- **Source:** `page 8 / پیوست ۵ بند ۲۹`

> امکان ثبت اطالعات و گزارش‌گیری از فروشگاه‌ها و مشتریان ویزیت‌شده با تأیید ادمین

- **Actors:** Admin
- **Statement:** A report counts visited stores and customers, approved versus rejected visits, and conversion to registration by marketer and city.
- **Rules / edge cases:** Only approved visits count in conversion; period and city filters; exportable.
- **Priority:** P2 · **Depends on:** REQ-MKT-011
- **Acceptance:**
  1. Counts match approved visits
  2. Conversion rate computed correctly
  3. City filter works
- **Label:** CONTRACT · **Status:** Specified

### REQ-RPT-010 — Points liability and reward cost report

- **Source:** `page 6 / پیوست ۴ بند ۱`

> امتیاز هر فاکتور بر اساس مبلغ فاکتور، نوع محصول داخل آن و حجم خرید محاسبه می‌شود؛

- **Actors:** Finance
- **Statement:** A report shows outstanding points, their estimated monetary liability and rewards paid per period, to control loyalty cost.
- **Rules / edge cases:** Liability uses the configured conversion rate; issued versus redeemed rewards separated.
- **Priority:** P2 · **Depends on:** REQ-LOY-007
- **Acceptance:**
  1. Outstanding points reported
  2. Liability equals points × rate
  3. Redeemed rewards shown separately
- **Label:** AGENT_DECISION · **Status:** Specified

### REQ-RPT-011 — Read-only reporting module with scheduled delivery

- **Source:** `page 11 / پیوست ۱۳ بند ۲`

> ارسال گزارش پرداخت روزانه محصوالت خریداری‌شده به تولیدکننده یا عرضه‌کننده و ادمین؛

- **Actors:** System, Admin
- **Statement:** The reporting module only reads data (no writes) and can email a scheduled report to a configured recipient list.
- **Rules / edge cases:** Reports run on a read-only connection; schedule per report; failures notify the operator.
- **Priority:** P1 · **Depends on:** REQ-RPT-004
- **Acceptance:**
  1. No report writes to business tables
  2. Scheduled report arrives by email
  3. Failure raises an alert
- **Label:** TECHNICAL_CONSTRAINT · **Status:** Specified

