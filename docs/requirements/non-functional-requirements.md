# Non-Functional Requirements — Dekolife Platform

**Purpose.** Define every measurable non-functional obligation of the Dekolife web platform so that
build, test and acceptance are decidable without further negotiation.
**Audience.** Engineering, QA, DevOps, and the client's technical reviewer (کارفرما).
**Status.** Baseline v1 — approved for planning; changes require the Orchestrator and a changelog row.

**Source:** contract `1405-01DK` (`../contract/contract-extracted-text.txt`), appendices 1, 9, 10, 13 and
articles 4 and 12; stack and naming pinned by `../canonical-context.md`.

---

## 0. How to read this document

### 0.1 ID conventions

| Prefix | Meaning | Owner of numbering |
|---|---|---|
| `NFR-<AREA>-NNN` | Non-functional requirement. AREA ∈ `PERF, SCALE, AVAIL, DR, SEC, PRIV, A11Y, COMPAT, SEO, USE, L10N, MAINT, OBS`. Support-SLA requirements are numbered in the `REQ-CTR-21x` block (§11) because they are written in the contract itself. | this document |
| `REQ-CTR-2NN` | Contract-derived obligation of a non-functional nature. The `2xx` block is reserved for this document so it can never collide with the functional `REQ-CTR` block owned by the contract-requirements matrix. | this document |
| `AD-NN` | Recorded agent decision where the contract is silent or contradictory. Every one is listed in §1. | this document |
| `VT-NN` | Verification technique (how a requirement is proven). Register in §2. | this document |
| `AC-NN` | End-to-end acceptance scenario (`acceptance-criteria.md`). | acceptance document |

Every requirement below states **target**, **source** and **verification**. A requirement without a
number in the target column is a defect in this document.

### 0.2 Requirement status vocabulary

`CONTRACT` — the value is written in the contract or is a direct arithmetic consequence of it.
`AGENT_DECISION` — the contract is silent, or gives no number; the value is chosen here and marked.
`TECHNICAL_CONSTRAINT` — forced by the pinned stack (`../canonical-context.md` §2).

### 0.3 Code paths

Any `app/...` path named in this document is a **planned target** defined by
`../canonical-context.md` §3. The documentation baseline contains no application code; the paths are
created during implementation and the checklist in `../security/security-checklist.md` tracks them.

---

## 1. Decision register (where the contract is silent)

| ID | Decision | Rationale |
|---|---|---|
| AD-01 | **Reference deployment baseline**: 1 app/web host (4 vCPU, 8 GB RAM, NVMe) + 1 MySQL 8.4 host (4 vCPU, 16 GB RAM, NVMe), single region inside Iran, PHP-FPM 8.4 + nginx, queue worker ×2, scheduler ×1. All performance targets are stated *against this baseline*. | Hosting is the client's obligation (ماده ۲, ماده ۵ بند ۳) and its size is not specified. Targets without a baseline are untestable, so the baseline is fixed here and re-baselining is an explicit, documented event. |
| AD-02 | **Launch load profile L-A**: 300 concurrent sessions, 60 req/s sustained, 300 orders/day, 25,000 SKUs, 1,200 stores, 150 suppliers, 60,000 registered users. **Year-1 peak profile L-B** (campaign/Nowruz): 800 concurrent sessions, 150 req/s, 1,500 orders/day, 60,000 SKUs, 4,000 stores, 250 suppliers, 250,000 users. | The contract promises nationwide multi-vendor scale but gives no volume. L-B is the sizing case; L-A is the exit criterion for the first release. |
| AD-03 | **Peak concurrency for acceptance** = 200 concurrent sessions at L-A data volume. | Keeps the acceptance load test runnable on the client's server (AD-01) without a second environment. |
| AD-04 | **Availability** 99.5 % monthly for the storefront, 99.0 % for the admin panel, measured on the external probe (VT-07). | Contract is silent; a marketplace without a stated number cannot be operated or penalised. |
| AD-05 | **RPO** 15 minutes for financial tables, 24 hours for everything else; **RTO** 4 hours. | The contract requires a *daily* backup with restore (پیوست ۱ بند ۹); 24 h RPO alone would lose a full trading day of ledger entries, so the financial subset is tightened. |
| AD-06 | **Retention**: financial records 10 years, audit log 5 years, order/PII 5 years after last activity, geo points 90 days, ID-document images 12 months after verification, application logs 30 days. | Statutory accounting retention in Iran plus data-minimisation. See `../security/privacy-and-PII.md`. |
| AD-07 | **Accessibility target** WCAG 2.1 level AA for all consumer-facing templates. | Not in the contract; a public marketplace in Iran has a legal-exposure and SEO cost for inaccessible pages. |
| AD-08 | **Browser support matrix** = the last 2 stable major versions of Chrome, Edge, Firefox, Safari, plus iOS Safari ≥ 16.4 and Android Chrome ≥ 120; minimum viewport 360 × 640 px. | پیوست ۱ بند ۶ names browsers but no versions. |
| AD-09 | **Coverage floors** 75 % line / 65 % branch overall, with module floors in `../testing/test-matrix.md`. | Contract requires testing (ماده ۴ بند ۳) but no metric. |
| AD-10 | **Support calendar** Sat–Wed 09:00–17:00, Thu 09:00–13:00 `Asia/Tehran`; Friday and public holidays excluded from "working" clocks. | ماده ۱۲ says "۲۴ ساعت کاری" without defining the working week. |
| AD-11 | **SLA clocks**: critical defects run on *calendar* hours (24 h), medium on *working* days (3), first response on *working* hours (24). | ماده ۱۲ mixes the two units in one sentence; this removes the ambiguity in the client's favour for critical issues. |
| AD-12 | **Dual-gateway single transaction** is treated as deferred (`RB-PAY-01`, `../canonical-context.md` §5) although پیوست ۱ بند ۵ and پیوست ۱۳ بند ۷ require it. Flagged as a contract conflict, not silently dropped. | See §11.3. |
| AD-13 | **Native Android/iOS builds** are deferred (`RB-MOB-01`); PWA is the in-scope mobile deliverable. Conflicts with پیوست ۸. | See §11.3. |
| AD-14 | **Search engine** = MySQL 8.4 `FULLTEXT` (InnoDB) + a maintained denormalised `cat_search_index` table. | No Elasticsearch in the pinned stack; keeps one datastore for v1. |
| AD-15 | **File storage** = local disk behind `Storage` abstraction with a private disk for identity documents; object storage is an infrastructure swap, not a code change. | پیوست ۶ بند ۲ requires storefront/owner/card photos; پیوست ۱ بند ۹ requires restorable backups. |
| AD-16 | **Timezone**: store UTC, render `Asia/Tehran` as a fixed +03:30 offset. Iran has observed no DST since 2022; a regression test asserts the zone has no transition in the supported 1404–1410 window and fails loudly if tzdata changes. | Period arithmetic (Jalali months, settlement periods) must be stable; a silent DST change would move daily cut-offs. |

---

## 2. Verification techniques (register)

| VT | Technique | Where it runs | Produces |
|---|---|---|---|
| VT-01 | Pest unit test | CI, per PR | pass/fail |
| VT-02 | Pest feature test on MySQL 8.4 | CI, per PR | pass/fail + coverage |
| VT-03 | Pest browser test (Laravel Dusk-class/Pest browser) | CI nightly + pre-release | pass/fail + screenshots |
| VT-04 | k6 load test against staging at L-A/L-B | pre-release + quarterly | p50/p95/p99, error rate, throughput |
| VT-05 | Lighthouse CI (mobile preset, throttled) | CI nightly | Performance/SEO/A11y scores, LCP/CLS/TBT |
| VT-06 | axe-core scan of 12 key templates | CI nightly | violation count by impact |
| VT-07 | External uptime + synthetic probe (60 s interval) | production | uptime %, incident log |
| VT-08 | Backup restore drill on a clean host | quarterly | signed drill report with row-count reconciliation |
| VT-09 | Targeted exploit test suite (IDOR/CSRF/XSS/SQLi/SSRF/upload) | CI, per PR | pass/fail, one test per control |
| VT-10 | Static analysis (PHPStan level 6) + Pint + secret scan | CI, per PR | error count = 0 |
| VT-11 | Manual UAT script execution with the client | UAT window | signed scenario sheet |
| VT-12 | Report/export reconciliation against ledger | CI + monthly in production | delta = 0 |

---

## 3. Capacity model and sizing assumption

| Dimension | L-A (first release exit) | L-B (year-1 peak) | Source |
|---|---|---|---|
| Concurrent sessions | 300 | 800 | AD-02 |
| Sustained request rate | 60 req/s | 150 req/s | AD-02 |
| Peak burst (10 s) | 180 req/s | 450 req/s | AD-02 |
| Orders per day | 300 | 1,500 | AD-02 |
| SKUs | 25,000 | 60,000 | AD-02 |
| Stores / suppliers | 1,200 / 150 | 4,000 / 250 | AD-02 |
| Registered users | 60,000 | 250,000 | AD-02 |
| Marketers | 60 | 300 | AD-02 |
| Ledger rows / month | 150,000 | 900,000 | AD-02 |
| Stored media | 40 GB | 250 GB | AD-02 |

**Re-baseline triggers** (any one forces a written re-sizing, ADR and new load test): sustained
p95 above target for 3 consecutive days; > 1,000 orders/day; > 40,000 SKUs; media > 60 % of disk;
any single table > 5 M rows.

---

## 4. Performance — `NFR-PERF`

All server-side figures are measured at the **origin** (nginx `$request_time`), at L-B data volume with
L-A concurrency (AD-03), warm application cache, on the AD-01 baseline, excluding client network time.

| ID | Requirement | Target (measurable) | Source | Verified by |
|---|---|---|---|---|
| NFR-PERF-001 | Server-rendered **catalog/category page** response | p95 ≤ 800 ms, p99 ≤ 1,500 ms, error rate < 0.5 % at 200 concurrent sessions | AGENT_DECISION | VT-04 |
| NFR-PERF-002 | **Product detail page** response | p95 ≤ 700 ms with gallery of 12 images, 40 reviews, 8 related products | AGENT_DECISION | VT-04 |
| NFR-PERF-003 | **Search** (query → rendered result list) | p95 ≤ 600 ms for a 60,000-SKU corpus, 20 results, 3 filters; p95 ≤ 250 ms for type-ahead JSON (top 8) | AGENT_DECISION | VT-04, VT-02 |
| NFR-PERF-004 | **Home page** response | p95 ≤ 900 ms including 6 merchandising blocks | AGENT_DECISION | VT-04 |
| NFR-PERF-005 | **Cart mutation** (add/update/remove) JSON | p95 ≤ 400 ms | AGENT_DECISION | VT-04 |
| NFR-PERF-006 | **Checkout submit** (price resolution + order creation + gateway redirect) | p95 ≤ 900 ms, p99 ≤ 2,000 ms; 0 duplicate orders in 500-iteration concurrent run | AGENT_DECISION | VT-04, VT-02 |
| NFR-PERF-007 | **Payment callback** (verify + ledger posting + order transition) | p95 ≤ 400 ms; idempotent under 5 parallel duplicate callbacks | AGENT_DECISION | VT-04, VT-02 |
| NFR-PERF-008 | **Admin financial report** (30-day range, 4 filters, 5,000 rows paginated) | p95 ≤ 5,000 ms; 90-day range ≤ 12,000 ms | AGENT_DECISION | VT-04 |
| NFR-PERF-009 | **Excel export** | 20,000 rows produced ≤ 90 s, delivered asynchronously (queued job + download link + email); web request returns ≤ 300 ms | پیوست ۷ بند ۱۰, پیوست ۱۳ بند ۳ | VT-02, VT-12 |
| NFR-PERF-010 | **Bulk notification dispatch** (10,000 recipients, SMS + in-app) | enqueued ≤ 2 s; all handed to provider ≤ 15 min; per-recipient failure isolated (no batch abort) | پیوست ۵ بند ۹ | VT-02 |
| NFR-PERF-011 | **Points/commission recomputation** for 1,000 invoices | ≤ 5 min via queue, deterministic (same input ⇒ same output) | پیوست ۴ بند ۱ | VT-02 |
| NFR-PERF-012 | **Marketer live map** (200 store points + last position) | initial payload ≤ 2 s, position delta poll ≤ 300 ms | پیوست ۱۴ | VT-04 |
| NFR-PERF-013 | **Database work per page** | ≤ 50 queries per rendered page; 0 N+1 patterns in the 20 highest-traffic routes; no single non-report query > 100 ms at p95 | AGENT_DECISION | VT-02 (`DB::listen` assertion), VT-10 |
| NFR-PERF-014 | **Cache effectiveness** | ≥ 85 % hit ratio on catalog/category/pricing read paths over 24 h | AGENT_DECISION | production metric |
| NFR-PERF-015 | **Front-end Core Web Vitals**, mobile preset, 4G, mid-tier device, p75 | LCP ≤ 2.5 s, CLS ≤ 0.1, INP ≤ 200 ms, TBT ≤ 300 ms on home, category, product, cart | پیوست ۹ بند ۲ | VT-05 |
| NFR-PERF-016 | **First-load transfer** | ≤ 1,800 KB compressed critical path on the product page; images served as WebP/AVIF with width descriptors | پیوست ۹ بند ۲ | VT-05 |
| NFR-PERF-017 | **Queue latency** (payment-adjacent jobs: ledger post, invoice PDF, SMS) | p95 ≤ 30 s from enqueue to execution at L-B | AGENT_DECISION | queue metrics |
| NFR-PERF-018 | **Search index freshness** | product create/update/price change reflected in search ≤ 60 s | AGENT_DECISION | VT-02 |

Performance is a release gate: a regression of > 20 % on any NFR-PERF row blocks the release unless a
written waiver with a remediation date is recorded.

---

## 5. Scalability — `NFR-SCALE`

| ID | Requirement | Target | Source | Verified by |
|---|---|---|---|---|
| NFR-SCALE-001 | Horizontal web scaling | adding a second app node requires no code change; sessions in shared store, no local-only state | AGENT_DECISION | architecture review + VT-04 on 2 nodes |
| NFR-SCALE-002 | Catalog growth | 60,000 SKUs, 250 categories, 400 brands with no degradation beyond NFR-PERF-001/003 | AD-02 | VT-04 with seeded 60 k corpus |
| NFR-SCALE-003 | Order/ledger growth | 2 M orders and 10 M ledger rows keep report targets (NFR-PERF-008) and order search ≤ 1,500 ms p95 | AGENT_DECISION | seeded-volume VT-04 |
| NFR-SCALE-004 | Multi-vendor fan-out | one order split across 20 sub-orders completes within 1,500 ms and produces 20 consistent sub-orders | پیوست ۱۱ | VT-02 |
| NFR-SCALE-005 | Concurrency safety on stock | 50 concurrent checkouts for the last unit of a SKU ⇒ exactly 1 success, 49 clean "insufficient stock" responses, 0 negative stock | AGENT_DECISION | VT-02 (concurrent), VT-04 |
| NFR-SCALE-006 | Idempotency keys | every money-moving endpoint accepts an idempotency key; replay within 24 h returns the original result, never a second effect | AGENT_DECISION | VT-02 |
| NFR-SCALE-007 | Storage growth | media on a separate volume/mount; growth to 250 GB requires no redeploy | AD-01 | ops review |
| NFR-SCALE-008 | Tenant isolation | a store/supplier with 5,000 SKUs and 50,000 order lines does not slow another store's panel beyond 1.2× baseline | AGENT_DECISION | seeded VT-04 |

---

## 6. Availability and resilience — `NFR-AVAIL`

| ID | Requirement | Target | Source | Verified by |
|---|---|---|---|---|
| NFR-AVAIL-001 | Storefront monthly availability | ≥ 99.5 % (≤ 3 h 39 m unplanned downtime per 30-day month) | AD-04 | VT-07 |
| NFR-AVAIL-002 | Admin panel monthly availability | ≥ 99.0 % | AD-04 | VT-07 |
| NFR-AVAIL-003 | Planned maintenance | ≤ 4 h per month, window 02:00–04:00 `Asia/Tehran`, announced ≥ 48 h ahead | AGENT_DECISION | change log |
| NFR-AVAIL-004 | Payment path degradation | if one gateway is unreachable, checkout offers the remaining active gateways within 2 s and never shows a raw exception | پیوست ۱ بند ۵ | VT-02 (fault injection) |
| NFR-AVAIL-005 | Graceful dependency failure | SMS/WhatsApp/email provider outage queues messages for ≤ 24 h retry and never blocks order placement | پیوست ۵ بند ۲۶ | VT-02 |
| NFR-AVAIL-006 | Recovery from process crash | PHP-FPM/queue worker crash loses no accepted order; queue jobs are retried ≤ 3 times with backoff then dead-lettered and alerted | AGENT_DECISION | chaos run in staging |
| NFR-AVAIL-007 | Data integrity on restart | no partially written order/sub-order/ledger set after an abrupt kill during checkout (single DB transaction per aggregate) | AGENT_DECISION | VT-02 fault injection |
| NFR-AVAIL-008 | Health endpoints | `/health` (liveness) and `/health/ready` (DB + queue + storage) respond ≤ 100 ms and are not public-cacheable | AGENT_DECISION | VT-02 |

---

## 7. Backup, RPO and RTO — `NFR-DR`

| ID | Requirement | Target | Source | Verified by |
|---|---|---|---|---|
| NFR-DR-001 | Automated **daily** full database backup | runs 02:30 `Asia/Tehran`, completes ≤ 20 min at L-B, exit status recorded and alerted on failure | **پیوست ۱ بند ۹** (`REQ-CTR-203`) | backup job log |
| NFR-DR-002 | Backup **restore** capability | restore to a clean host inside 4 h; documented runbook executable by a non-author | **پیوست ۱ بند ۹** | VT-08 |
| NFR-DR-003 | Financial-table RPO | ≤ 15 min (binary-log shipping of `fin_*`, `pay_*`, `ord_*`, `inv_*` tables) | AD-05 | restore drill with point-in-time stop |
| NFR-DR-004 | Whole-system RPO | ≤ 24 h | AD-05 | VT-08 |
| NFR-DR-005 | RTO | ≤ 4 h full-stack; ≤ 1 h single-table point-in-time recovery | AD-05 | VT-08 |
| NFR-DR-006 | Backup encryption | AES-256 at rest, keys outside the backup bucket; restore requires the key | **ماده ۴ بند ۸** | key-custody review |
| NFR-DR-007 | Backup retention | 30 daily, 12 monthly, 5 yearly (financial years) | AD-06 | storage inventory |
| NFR-DR-008 | Backup verification | every backup produces a row-count + checksum manifest; a weekly automated restore-to-scratch checks 12 critical tables | AGENT_DECISION | CI job |
| NFR-DR-009 | Drill cadence | full restore drill every quarter, signed report; failed drill = release/ops blocker | AGENT_DECISION | VT-08 |
| NFR-DR-010 | Media backup | uploaded media (product images, storefront photos, identity documents) backed up daily with the same retention | پیوست ۶ بند ۲ | VT-08 |
| NFR-DR-011 | Off-host copies | backups stored on a different provider/region from the primary database | AGENT_DECISION | ops review |

---

## 8. Security — `NFR-SEC`

Full threat analysis and control mapping live in `../security/threat-model.md` and
`../security/security-checklist.md`; this section fixes the measurable obligations.

| ID | Requirement | Target | Source | Verified by |
|---|---|---|---|---|
| NFR-SEC-001 | Transport encryption | TLS 1.2 minimum, TLS 1.3 preferred; HTTP→HTTPS 301; HSTS `max-age=31536000`; no mixed content | **پیوست ۹ بند ۳** (`REQ-CTR-205`) | VT-10 (header test), external scan |
| NFR-SEC-002 | Admin authentication | separate `admin` guard on a dedicated path/domain, TOTP 2FA mandatory for every admin, 6-digit, 30 s step, ±1 window, replay rejected | **پیوست ۱ بند ۷** (`REQ-CTR-202`) | VT-02, VT-09 |
| NFR-SEC-003 | Password storage | Argon2id, memory ≥ 64 MB, time cost ≥ 3, parallelism 1; minimum length 10; no maximum below 128; reject the 10,000 most common passwords | AGENT_DECISION | VT-01, VT-10 |
| NFR-SEC-004 | Brute-force protection | login ≤ 5 attempts/min/IP and ≤ 10/hour/account; lockout 15 min after 10 failures; OTP ≤ 3 per 5 min per mobile and ≤ 10/day per IP; CAPTCHA after 3 failed logins from one IP | **پیوست ۹ بند ۳** | VT-09 |
| NFR-SEC-005 | Session security | regenerate ID on login; idle timeout 120 min (consumer) / 30 min (admin); absolute lifetime 12 h; `HttpOnly`, `Secure`, `SameSite=Lax`; server-side invalidation on password change and role change | AGENT_DECISION | VT-09 |
| NFR-SEC-006 | Authorization | every scoped resource enforces policy + scope server-side; hiding a UI control is never authorization; 100 % of scoped resources have a negative test | **`../canonical-context.md` §5**, پیوست ۹ بند ۳ | VT-09 |
| NFR-SEC-007 | Input validation | every write endpoint validates through a FormRequest; no raw `$request->all()` persistence; output escaped by default (Blade), no `{!! !!}` on user content | **پیوست ۹ بند ۳** | VT-09, VT-10 |
| NFR-SEC-008 | SQL injection | 0 raw interpolated queries; all `DB::raw` uses bind parameters; PHPStan custom rule forbids string-concatenated SQL | **ماده ۴ بند ۸** | VT-09, VT-10 |
| NFR-SEC-009 | File upload | extension + MIME + magic-byte check, max 5 MB image / 20 MB document, randomised stored name, private disk for identity documents, no execution path, image re-encode | **ماده ۴ بند ۸** | VT-09 |
| NFR-SEC-010 | Audit log coverage | 100 % of sensitive events listed in §8.1 recorded with actor, IP, user agent, before/after diff, correlation ID; append-only; tamper-evident hash chain | **پیوست ۱ بند ۱۰** (`REQ-CTR-204`) | VT-02, VT-12 |
| NFR-SEC-011 | Secrets management | no secret in git (0 findings in secret scan); `.env` outside webroot; app key rotation runbook; gateway credentials encrypted at rest | **ماده ۷**, canonical §0 non-negotiable 6 | VT-10 |
| NFR-SEC-012 | Dependency hygiene | `composer audit` and `npm audit --omit=dev` report 0 high/critical; patch SLA: critical ≤ 72 h, high ≤ 14 days | **ماده ۴ بند ۸** | CI job |
| NFR-SEC-013 | Security headers | CSP (nonce-based, no `unsafe-inline` for scripts), `X-Content-Type-Options`, `Referrer-Policy: strict-origin-when-cross-origin`, `X-Frame-Options: DENY`, `Permissions-Policy` | AGENT_DECISION | VT-10 |
| NFR-SEC-014 | CSRF | all state-changing web routes protected by token; API token routes exempt but not cookie-authenticated | AGENT_DECISION | VT-09 |
| NFR-SEC-015 | Rate limiting (general) | search 30/min/IP, checkout 10/min/user, messaging 20/min/user, API 120/min/token | AGENT_DECISION | VT-09 |
| NFR-SEC-016 | Penetration testing | one authenticated + one unauthenticated scan (OWASP ZAP baseline) per release candidate with 0 high findings; 0 critical at go-live | AGENT_DECISION | scan report |
| NFR-SEC-017 | Admin network hygiene | admin panel can be IP-allow-listed by configuration; 2FA reset requires two-person approval and is audited | AGENT_DECISION | VT-02 |
| NFR-SEC-018 | Error disclosure | production `APP_DEBUG=false`; no stack trace, SQL or path in any response; generic error envelope with correlation ID | AGENT_DECISION | VT-09 |

### 8.1 Sensitive events that must be audited (contract پیوست ۱ بند ۱۰)

1. Authentication: login success/failure, logout, 2FA enrol/reset, password change, password reset.
2. Identity: national-ID/legal-document upload or change, mobile change, address change, role grant/revoke, secondary-role enablement.
3. Commercial: base price change, tier-price change, discount/coupon creation, commission-plan change, settlement approval and payout.
4. Order lifecycle: cancel, reject, return approval, refund, complaint freeze, compensation write-off.
5. Data: Excel export of financial or PII data, bulk notification send, data-import run, backup/restore execution.
6. Admin: any impersonation ("login as"), user deletion/deactivation, gateway activation, maintenance-mode toggle.

---

## 9. Privacy and PII — `NFR-PRIV`

Detail, lawful basis and retention tables: `../security/privacy-and-PII.md`.

| ID | Requirement | Target | Source | Verified by |
|---|---|---|---|---|
| NFR-PRIV-001 | Minimum collection | no field collected without a row in the data-inventory table; national ID collected only for store/supplier/marketer onboarding and invoice issuance | AGENT_DECISION | data review, VT-02 |
| NFR-PRIV-002 | Field-level encryption | national ID, IBAN/Sheba, identity-document numbers encrypted at rest (`encrypted` cast / KMS-wrapped key) | AGENT_DECISION | VT-02 (ciphertext assertion), DB inspection |
| NFR-PRIV-003 | Log redaction | national ID, mobile, postal code, IBAN, tokens and OTP never appear in logs, queue payloads or error reports; automated assertion on the log processor | AGENT_DECISION | VT-01 |
| NFR-PRIV-004 | UI masking | national ID and IBAN masked to last 4 digits for every role except the record owner and an audited admin action | AGENT_DECISION | VT-03 |
| NFR-PRIV-005 | Geo consent | marketer location capture only after explicit consent, only inside an active visit/work session, with a persistent indicator and an off switch | پیوست ۱۴ | VT-02, VT-11 |
| NFR-PRIV-006 | Marketer data scope | a marketer sees only stores in own network; a PII export by a marketer is impossible by design (no export route for that role) | پیوست ۵ بند ۲۹, پیوست ۶ بند ۱۰ | VT-09 |
| NFR-PRIV-007 | Subject rights | export and deletion requests completed ≤ 30 days; deletion pseudonymises while retaining legally required financial records | AGENT_DECISION | VT-11 + procedure in privacy doc |
| NFR-PRIV-008 | Retention enforcement | scheduled job enforces AD-06 periods; deletions logged with counts | AD-06 | VT-02 |
| NFR-PRIV-009 | Third-party sharing | SMS/WhatsApp/email/gateway payloads carry only the minimum fields; no PII in URL query strings | AGENT_DECISION | code review + VT-09 |
| NFR-PRIV-010 | Consent record | consent (text version, timestamp, IP, user agent) stored for geo tracking and marketing messages, withdrawable in the panel | AGENT_DECISION | VT-02 |

---

## 10. Accessibility, compatibility, SEO, usability, localisation

### 10.1 Accessibility — `NFR-A11Y`

| ID | Requirement | Target | Source | Verified by |
|---|---|---|---|---|
| NFR-A11Y-001 | Conformance | WCAG 2.1 AA on 12 key templates (home, category, product, cart, checkout, login, register, order tracking, panel dashboard, store panel, marketer panel, static page) | AD-07 | VT-06 |
| NFR-A11Y-002 | Automated scan | 0 critical and 0 serious axe-core violations on those 12 templates | AD-07 | VT-06 |
| NFR-A11Y-003 | Keyboard operability | full keyboard-only completion of search → add to cart → checkout in ≤ 40 tab stops on the product page; visible focus ring (≥ 3:1 contrast) | AD-07 | VT-03, VT-11 |
| NFR-A11Y-004 | Contrast | text ≥ 4.5:1, large text ≥ 3:1, UI components ≥ 3:1 | AD-07 | VT-06 |
| NFR-A11Y-005 | Semantics | one `h1` per page, landmark regions, `lang="fa" dir="rtl"`, table headers, labelled form controls, `alt` text mandatory on product images (validated on upload) | AD-07 | VT-06, VT-02 |
| NFR-A11Y-006 | Error handling | every validation error has text (not colour-only), is announced in an ARIA live region, and is linked to its field | AD-07 | VT-03 |
| NFR-A11Y-007 | Motion and timing | `prefers-reduced-motion` respected; no auto-advancing carousel pause shorter than 5 s; no session timeout under 20 s without warning | AD-07 | VT-06 |

### 10.2 Compatibility — `NFR-COMPAT`

| ID | Requirement | Target | Source | Verified by |
|---|---|---|---|---|
| NFR-COMPAT-001 | Desktop browsers | Chrome, Edge, Firefox, Safari — last 2 stable majors; 0 blocking defects | **پیوست ۱ بند ۶** (`REQ-CTR-201`) | VT-03 matrix |
| NFR-COMPAT-002 | Mobile browsers | iOS Safari ≥ 16.4, Android Chrome ≥ 120; 0 blocking defects | **پیوست ۱ بند ۶** | VT-03 matrix |
| NFR-COMPAT-003 | Responsive | usable at 360, 390, 414, 768, 1024, 1280, 1440, 1920 px width; no horizontal scroll at 360 px | **پیوست ۱ بند ۳** | VT-05, VT-11 |
| NFR-COMPAT-004 | PWA | installable (manifest + service worker), offline fallback page, push notification opt-in, Lighthouse PWA installability checks pass | **پیوست ۱ بند ۴**, پیوست ۸ بند ۳ | VT-05 |
| NFR-COMPAT-005 | No-JS baseline | catalog, product and cart pages render server-side; the page is readable with JS disabled (interactive widgets degrade gracefully) | AGENT_DECISION | VT-03 |

### 10.3 SEO — `NFR-SEO`

| ID | Requirement | Target | Source | Verified by |
|---|---|---|---|---|
| NFR-SEO-001 | GTmetrix grade | ≥ **B** on home, one category, one product page at L-B; target **A**; Performance score ≥ 70 % and Structure ≥ 70 % | **پیوست ۹ بند ۲** (`REQ-CTR-206`) | VT-05 + GTmetrix report |
| NFR-SEO-002 | Lighthouse | SEO ≥ 95, Performance ≥ 80 (mobile), Accessibility ≥ 90 on the same three pages | **پیوست ۹ بند ۱** | VT-05 |
| NFR-SEO-003 | Clean URLs | `/category/{slug}`, `/p/{slug}-{sku}`, `/blog/{slug}`, `/store/{slug}`; lowercase, hyphenated, ASCII slug; no query-string identity | **پیوست ۹ بند ۱** | VT-02 |
| NFR-SEO-004 | Structured data | JSON-LD `Product` + `Offer` + `AggregateRating` on every product, `BreadcrumbList` on category/product, `Organization` site-wide, `LocalBusiness` on Patogh service pages; 0 errors in Rich Results test | **پیوست ۹ بند ۱** | VT-05 |
| NFR-SEO-005 | Sitemap | `/sitemap.xml` index + per-type sitemaps, ≤ 50,000 URLs / 50 MB each, regenerated daily, excludes out-of-stock and non-approved listings | **پیوست ۹ بند ۱** | VT-02 |
| NFR-SEO-006 | Metadata | unique `<title>` ≤ 60 chars and meta description 120–160 chars per product/category; template-generated when the seller omits them | **پیوست ۲ بند ۱۰** | VT-05 |
| NFR-SEO-007 | Canonicalisation | self-referencing canonical on all indexable pages; filtered/sorted/paginated variants canonical to the clean URL; `noindex` on internal search results and thin pages | AGENT_DECISION | VT-02 |
| NFR-SEO-008 | Redirects | slug change returns 301 old→new; deleted product returns 410 after 90 days of 301 to its category | AGENT_DECISION | VT-02 |
| NFR-SEO-009 | robots.txt | disallows `/admin`, `/panel`, `/api`, `/checkout`; allows catalog and blog | AGENT_DECISION | VT-02 |

### 10.4 Usability — `NFR-USE`

| ID | Requirement | Target | Source | Verified by |
|---|---|---|---|---|
| NFR-USE-001 | Task success | ≥ 90 % success on 5 core tasks (find a product by filter, add to cart, complete checkout, register a store as marketer, submit a ticket) with 5 moderated users | AGENT_DECISION | VT-11 |
| NFR-USE-002 | Checkout length | ≤ 4 steps after cart; ≤ 9 required fields for a returning consumer | AGENT_DECISION | VT-03 |
| NFR-USE-003 | Error recoverability | every failed form submission preserves entered data and shows a field-level Persian message | AGENT_DECISION | VT-03 |
| NFR-USE-004 | Admin efficiency | any of the 10 most frequent admin tasks is reachable in ≤ 3 clicks from the dashboard | AGENT_DECISION | VT-11 |
| NFR-USE-005 | Empty/loading/error states | every list view defines all three states; no raw exception text visible to users | AGENT_DECISION | VT-03 |
| NFR-USE-006 | Persian copy QA | 0 spelling/grammar errors on 12 key templates after client review; no untranslated English string in UI | AGENT_DECISION | VT-11 |

### 10.5 Localisation — `NFR-L10N`

| ID | Requirement | Target | Source | Verified by |
|---|---|---|---|---|
| NFR-L10N-001 | Locale | `fa-IR` UI, `dir="rtl"`, Toman currency, Persian digits at presentation only | **`../canonical-context.md` §5** | VT-02 |
| NFR-L10N-002 | Calendar | Jalali (Hijri Shamsi) display everywhere dates are shown; Gregorian ISO-8601 in API payloads; conversion through the single `app/Shared` helper | canonical §5 | VT-01 |
| NFR-L10N-003 | Timezone | store UTC, render `Asia/Tehran` (fixed +03:30; Iran has observed no DST since 2022 — a regression test asserts no transition in the supported 1404–1410 window) | AD-16 | VT-01 |
| NFR-L10N-004 | Money | integer Toman in `BIGINT *_amount`, never float; all arithmetic via `App\Shared\Money`; percentages in integer basis points with a single documented rounding rule | canonical §5 | VT-01, VT-12 |
| NFR-L10N-005 | Number/date input | accept Persian and Latin digits on input, normalise to Latin before validation; Jalali date picker emits ISO to the server | AGENT_DECISION | VT-01, VT-03 |
| NFR-L10N-006 | Identifiers | mobile `^09\d{9}$` normalised to E.164 `+989…`; postal code exactly 10 digits; national ID exactly 10 digits with check-digit validation | **پیوست ۵ بند ۳** | VT-01 |
| NFR-L10N-007 | Week/period boundaries | Jalali month boundaries for periods (29/30/31 days, Esfand 30 in leap years); period reports reconcile to the Jalali calendar, not Gregorian months | **پیوست ۱۳ بند ۱** | VT-02 |
| NFR-L10N-008 | RTL layout integrity | mirrored layout, logical CSS properties only (`margin-inline-start`), no hard-coded `left/right`; LTR fallback for Latin SKUs and phone numbers | AGENT_DECISION | VT-06 |

### 10.6 Maintainability — `NFR-MAINT`

| ID | Requirement | Target | Source | Verified by |
|---|---|---|---|---|
| NFR-MAINT-001 | Static analysis | PHPStan level 6 with Larastan: 0 errors, baseline frozen (no new entries) | canonical §2 | VT-10 |
| NFR-MAINT-002 | Style | Pint clean, PSR-12, `declare(strict_types=1)` in every PHP file | canonical §7 | VT-10 |
| NFR-MAINT-003 | Test coverage | ≥ 75 % line, ≥ 65 % branch overall; module floors per `../testing/test-matrix.md` | AD-09 | VT-02 (`--coverage`) |
| NFR-MAINT-004 | Module boundaries | 0 cross-module model/repository imports (only application services, contracts, events); enforced by an architecture test | canonical §3 | VT-02 (`tests/Architecture`) |
| NFR-MAINT-005 | Complexity | method cyclomatic complexity ≤ 10, class ≤ 400 lines, ≤ 15 public methods; violations need an ADR note | AGENT_DECISION | phpmetrics job |
| NFR-MAINT-006 | Configuration hygiene | 0 `env()` calls outside `config/`; all tunables in config with defaults | canonical §7 | VT-10 (custom rule) |
| NFR-MAINT-007 | Documentation | README, API reference, table dictionary, install/upgrade guide, ADRs for every architectural choice — delivered with the source | **پیوست ۱ بند ۲**, **ماده ۴ بند ۴** | delivery checklist |
| NFR-MAINT-008 | Migrations | forward-only, reversible where safe; every migration runs on an empty DB in ≤ 60 s | AGENT_DECISION | gate G6 |

### 10.7 Observability — `NFR-OBS`

| ID | Requirement | Target | Source | Verified by |
|---|---|---|---|---|
| NFR-OBS-001 | Structured logging | JSON lines with `timestamp, level, request_id, route, method, status, duration_ms, user_id_hash, module`; 30-day hot retention | AGENT_DECISION | log sample inspection |
| NFR-OBS-002 | Error tracking | every 5xx captured with stack trace, correlation ID and release tag; deduplicated; alert on new error signature | AGENT_DECISION | fault injection |
| NFR-OBS-003 | Uptime probing | external probe every 60 s from ≥ 2 locations on home, product, login, `/health` | AD-04 | VT-07 |
| NFR-OBS-004 | Alerting | page on: 5xx > 1 % over 5 min; p95 > 1.5 × target over 10 min; queue depth > 1,000 for 10 min; failed jobs > 20 in 10 min; disk > 80 %; backup job failure; payment success rate drop > 20 % vs 7-day baseline | AGENT_DECISION | alert drill |
| NFR-OBS-005 | Dashboards | request rate/latency/error by route group, queue throughput, DB slow queries, payment outcomes by gateway, order funnel | AGENT_DECISION | review |
| NFR-OBS-006 | Audit trail query | any sensitive event retrievable by actor, target, date range and IP in ≤ 3 s for a 30-day window | **پیوست ۱ بند ۱۰** | VT-02 |
| NFR-OBS-007 | Traceability to release | every log line carries the release SHA; a defect report maps to a deploy | AGENT_DECISION | release check |

---

## 11. Contractual support SLA and delivery reality

### 11.1 Support and warranty obligations (ماده ۱۲, پیوست ۱۰)

| ID | Obligation (contract) | Term | Operative definition adopted here |
|---|---|---|---|
| `REQ-CTR-210` | First response to a support request | ≤ 24 **working** hours | Measured from ticket creation to first human reply, within AD-10 hours. |
| `REQ-CTR-211` | Critical defect fix (system down, data loss) | ≤ 24 hours | Calendar hours, 7 days/week including Friday. Workaround within 8 h. |
| `REQ-CTR-212` | Medium defect fix | ≤ 3 **working** days | AD-10 calendar. |
| `REQ-CTR-213` | Low defect / cosmetic | next scheduled release, ≤ 30 days | Written into the release plan. |
| `REQ-CTR-214` | Free defect warranty | **5 years** from final delivery, no cost, no condition | Covers defects caused by programming error; excludes new features, third-party API changes and infrastructure failures (ماده ۱۲ تبصره, ماده ۱۶). |
| `REQ-CTR-215` | Defect log during acceptance | client logs defects within 3 **working** days of each test round | Round closes on day 3; late findings roll into the next round. |
| `REQ-CTR-216` | Training | 4 in-person + 25 online sessions | 29 sessions, each ≥ 90 min, recorded, with attendance sheets. |
| `REQ-CTR-217` | Documentation and videos | technical + user docs, install guide, training videos | Delivered as a versioned bundle with the source. |
| `REQ-CTR-218` | Testing before delivery | unit + integration + acceptance with real scenarios | Evidence = CI report + UAT sign-off sheet. |

### 11.2 Severity definitions used by the SLA

| Severity | Definition | Examples |
|---|---|---|
| P1 Critical | Storefront or checkout unavailable; data loss or corruption; payment double-charge; PII exposure; admin lockout | gateway callback loop duplicating ledger entries |
| P2 High | A core flow broken for a subset of users, no workaround | tier price not applied for one role |
| P3 Medium | Feature works with a workaround or affects a minority path | Excel export missing one column |
| P4 Low | Cosmetic, copy, RTL spacing, non-blocking UX | misaligned badge in Safari |

### 11.3 Contract obligations that are not deliverable as written in 90 days

These are recorded so they are neither silently dropped nor promised. Each needs a written addendum,
a phased plan, or a renegotiated term. They are reported to the Orchestrator.

1. **12 payment gateways live in 90 days** (پیوست ۱ بند ۵, پیوست ۱۳ بند ۷). Building 12 drivers is
   feasible; *activating* 12 merchant accounts is not — each PSP requires a merchant contract,
   sandbox credentials, and its own certification. Realistic: 2 gateways live at go-live, the other 10
   implemented and tested against sandbox, activation driven by the client's merchant onboarding.
2. **Two gateways in one transaction** (same clauses) contradicts `RB-PAY-01` (split payment deferred).
   Realistic: single active gateway per payment in v1, dual-gateway as a post-launch addendum.
3. **Native Android APK + iOS app + store publishing** (پیوست ۸) contradicts `RB-MOB-01`. Realistic:
   PWA at go-live; native builds are a separate contract.
4. **5-year free warranty with 24-hour critical fixes and no retainer** (ماده ۱۲) is an unbounded
   liability: it has no cap, no scope limit and no funding. Realistic: 5-year warranty limited to
   defects reproducible on the delivered release, with an annual support retainer for infrastructure
   and third-party changes, and a named on-call rota.
5. **29 training sessions (4 in-person + 25 online) plus docs and videos inside the same window**
   (پیوست ۱۰ بند ۵–۶) consumes roughly 10–12 working days of the same single team that is still
   fixing UAT defects. Realistic: sessions spread over the 30 days *after* acceptance, 2 per working
   day, recorded so attendees can re-watch.
6. **GTmetrix grade A on all core pages** from Iranian hosting with third-party PSP redirects and
   client-supplied imagery. Grade B is achievable and is what this document gates on; A depends on
   assets and hosting the vendor does not control.
7. **`ماده ۱۶` 15 % profit share and 15 % of future costs** is a commercial arrangement with no
   technical definition of "net profit" auditability in the system; the platform can produce the
   revenue/cost report, not adjudicate the share.

---

## 12. Traceability to contract clauses

| Contract pointer | Obligation | Requirement IDs |
|---|---|---|
| پیوست ۱ بند ۱ | Laravel + MySQL, 3-layer, modular | `REQ-CTR-201` → NFR-MAINT-001..008 |
| پیوست ۱ بند ۳ | Fully responsive | NFR-COMPAT-003 |
| پیوست ۱ بند ۴ | PWA output, icon, splash, smart cache | NFR-COMPAT-004 |
| پیوست ۱ بند ۵ | 12 gateways, two per transaction | `REQ-CTR-208` → NFR-AVAIL-004, §11.3 items 1–2 |
| پیوست ۱ بند ۶ | Browser compatibility | `REQ-CTR-201` → NFR-COMPAT-001..002 |
| پیوست ۱ بند ۷ | Separate admin panel + 2FA | `REQ-CTR-202` → NFR-SEC-002, NFR-SEC-017 |
| پیوست ۱ بند ۸ | Extensible user levels/product types | NFR-MAINT-004, NFR-SCALE-002 |
| پیوست ۱ بند ۹ | Daily DB backup + restore | `REQ-CTR-203` → NFR-DR-001..011 |
| پیوست ۱ بند ۱۰ | Full audit log of sensitive events | `REQ-CTR-204` → NFR-SEC-010, §8.1, NFR-OBS-006 |
| پیوست ۹ بند ۱ | Technical SEO, sitemap, clean URLs, structured data | `REQ-CTR-206` → NFR-SEO-001..009 |
| پیوست ۹ بند ۲ | GTmetrix B/A | `REQ-CTR-206` → NFR-SEO-001, NFR-PERF-015..016 |
| پیوست ۹ بند ۳ | SSL, brute force, captcha, RBAC, validation | `REQ-CTR-205` → NFR-SEC-001, 004, 006, 007 |
| پیوست ۹ بند ۴ | Initial data import | `REQ-CTR-207` → NFR-MAINT-008, acceptance AC-21 |
| پیوست ۹ بند ۶ | Training the client team | `REQ-CTR-216` | 
| پیوست ۱۰ بند ۲ | Defects logged within 3 working days | `REQ-CTR-215` |
| پیوست ۱۰ بند ۵ | 4 in-person + 25 online sessions | `REQ-CTR-216` |
| پیوست ۱۰ بند ۶ | Docs, videos, source bundle | `REQ-CTR-217` |
| پیوست ۱۳ بند ۱ | Filterable sales report for settlement | NFR-PERF-008 |
| پیوست ۱۳ بند ۳ | Excel export for bank batch payment | NFR-PERF-009 |
| پیوست ۱۳ بند ۶ | Failed/returned transactions, gateway reconciliation | NFR-SEC-010, NFR-OBS-005 |
| پیوست ۱۴ | Live marketer tracking + analysis | NFR-PERF-012, NFR-PRIV-005 |
| ماده ۴ بند ۳ | Unit, integration, acceptance tests before delivery | `REQ-CTR-218` → `../testing/test-strategy.md` |
| ماده ۴ بند ۸ | Data protection, API keys, backups, SQLi/brute force/upload hardening | `REQ-CTR-205` → §8 |
| ماده ۱۲ | Support and warranty SLA | `REQ-CTR-210..214` → §11 |
| ماده ۷ | IP ownership, no reuse, client owns data | `REQ-CTR-219` | 

---

## 13. Release gates derived from this document

| Gate | Condition | Blocks |
|---|---|---|
| RG-PERF | All NFR-PERF rows met at L-A; no > 20 % regression vs previous release | release |
| RG-SEC | NFR-SEC-001..018 verified; 0 high/critical findings | release |
| RG-DR | Restore drill executed successfully within the last 90 days | go-live, then quarterly |
| RG-A11Y | 0 critical/serious axe violations; keyboard checkout pass | release |
| RG-SEO | GTmetrix ≥ B; Lighthouse SEO ≥ 95; 0 structured-data errors | release |
| RG-SLA | Support rota, ticket channels, severity matrix and escalation contacts published | go-live |
| RG-DOCS | README, API reference, table dictionary, runbooks, training bundle delivered | final delivery |

These are distinct from the CI gates `G1`–`G14` in `../testing/quality-gates.md`: `RG-*` are release
conditions, `G*` are per-commit checks.

---

## 14. Out of scope for this version

- Native mobile binaries, store publishing, push infrastructure beyond Web Push (`RB-MOB-01`).
- Split payment across two gateways in one transaction (`RB-PAY-01`, §11.3 item 2).
- Reservation/appointment module (separate contract no. 2).
- Multi-region active/active, read replicas, CDN PoPs — the AD-01 baseline is single-region.
- Elasticsearch-class search; `NFR-PERF-003` is scoped to MySQL FULLTEXT (AD-14).

---

## 15. Changelog

| Version | Date | Change | Author |
|---|---|---|---|
| 1.0 | 2026-11-25 | Baseline created; 123 NFR rows across 13 quality areas, 18 contract-derived obligations, 16 recorded decisions, 7 delivery conflicts surfaced. | Agent F |
