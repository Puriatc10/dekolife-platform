# Threat Model — Dekolife Platform

**Purpose.** Name what is worth stealing or breaking in Dekolife, who would do it, where the trust
boundaries are, and which concrete Laravel mechanism stops it. This is a design document, not a checklist —
the actionable checklist is `./security-checklist.md`.
**Audience.** Engineering, security reviewer, the client's technical representative, the Orchestrator.
**Status.** Baseline v1 — reviewed at each architecture change and before go-live.

**Source:** contract `1405-01DK` article 4 clause 8 (data protection, API keys, backups, SQLi, brute force,
malicious uploads), appendix 1 clauses 7 and 10 (admin 2FA, audit log), appendix 9 clause 3 (SSL, brute
force, captcha, RBAC, input validation), appendix 6 and 5 clause 29 (marketer data scope), appendix 14
(geo tracking); stack from `../canonical-context.md` §2 and §5.

---

## 1. Scope and method

| Aspect | Value |
|---|---|
| In scope | the web application (Blade/Livewire), the JSON API, the admin panel, payment callbacks, file storage, the queue and scheduler, outbound SMS/WhatsApp/email/gateway calls, the MySQL database and its backups |
| Out of scope | native mobile apps (`RB-MOB-01`), the reservation system (separate contract), the client's hosting and network perimeter, the PSPs' own infrastructure, physical security |
| Method | STRIDE per component across trust boundaries, plus explicit abuse cases for the product-specific frauds; each threat gets a mitigation, a residual risk and a proving test |
| Re-review triggers | a new external integration, a new role or permission, a new file type, a change to how money moves, a new export path, an incident |

### 1.1 Assumptions

1. TLS terminates at nginx; the app is not exposed over plain HTTP (canonical §2, NFR-SEC-001).
2. The database is not reachable from the internet; only the app host can connect.
3. The hosting operator is trusted for infrastructure but **not** for application data — backups are
   encrypted with a key the host does not hold (NFR-DR-006).
4. The client's staff are trusted with admin rights but every admin action is audited (پیوست ۱ بند ۱۰).
5. Payment gateways are trusted to report their own transaction outcomes, but **not** to be the only
   source of truth: the amount and the order are always re-verified server-side.
6. Marketers are semi-trusted field staff with commercial incentives that conflict with the platform's
   (commission fraud is an insider-adjacent threat, not a purely external one).

---

## 2. Assets

| ID | Asset | Where it lives | Classification | Impact if compromised |
|---|---|---|---|---|
| A1 | Ledger and balances (`fin_*`) | MySQL | Confidential, integrity-critical | Direct financial loss; settlement disputes; unreconcilable books |
| A2 | Payment transactions and gateway credentials | MySQL, `.env`/encrypted config | Secret + financial | Money movement, refund fraud, PSP account suspension |
| A3 | Commission and settlement records (`cmp_*`) | MySQL | Confidential, integrity-critical | Overpayment to marketers; fraud hides here |
| A4 | Points, rewards, coupons (`loy_*`, `prm_*`) | MySQL | Integrity-critical | Free goods; margin erosion; customer trust |
| A5 | National ID, postal code, mobile, address | MySQL (encrypted fields) | **PII — high** | Identity theft, targeted fraud, legal exposure |
| A6 | Identity documents, storefront photos, business cards | private disk | **PII — high** (documents) | Forgery enablement, extortion |
| A7 | Geo positions of marketers and stores | MySQL, 90-day retention | **PII — location** | Stalking, competitor mapping, staff surveillance |
| A8 | Order and invoice data | MySQL | Commercial confidential | Competitor intelligence, customer targeting |
| A9 | Admin accounts and 2FA secrets | MySQL, sessions | Secret | Full platform takeover |
| A10 | Session cookies and API tokens | client + DB | Secret | Account takeover |
| A11 | Product content, media, SEO | disk + DB | Public (integrity-critical) | Defacement, SEO poisoning, malware distribution |
| A12 | Audit log | MySQL (append-only) | Integrity-critical | Loss of accountability; fraud becomes invisible |
| A13 | Backups | off-host storage | Confidential | Total data loss / total disclosure |
| A14 | Source code and deployment config | git | Confidential (ماده ۷) | IP loss, secret discovery |
| A15 | SMS/OTP channel | provider | Integrity-critical | Account takeover via OTP interception/fatigue |

---

## 3. Actors

| Actor | Trust | Capability | Primary motive |
|---|---|---|---|
| Anonymous visitor | untrusted | browse, search, register, view public profiles | scraping, spam, enumeration |
| Consumer | low | own orders, wallet, points, messages, reviews | free goods, refund abuse, points farming |
| Store (همکار) | low-medium | own catalog view, orders, invoices, settlements, tier prices | price arbitrage, fake invoices, competitor snooping |
| Supplier / producer | low-medium | own products, stock, prices, sub-orders, settlements | commission evasion, order manipulation, fake shipping |
| Marketer (بازاریاب) | medium (commercially motivated) | own network, leads, visits, commissions, geo | commission fraud, lead stealing, PII harvesting |
| Admin | high (audited) | everything | insider fraud, mistake, account takeover target |
| Gateway (PSP) | semi-trusted | sends callbacks | n/a — but a compromised or misconfigured PSP can send forged/duplicated callbacks |
| SMS/WhatsApp provider | semi-trusted | delivers messages, reports status | n/a — but OTP interception is a real attack |
| Hosting operator | infrastructure-trusted | filesystem, network, DB access | data theft, ransomware |
| Malicious insider (client staff) | high privilege | admin panel | fraud, data sale |
| Automated attacker / botnet | untrusted | credential stuffing, scraping, scanning | takeover, data, resale |

---

## 4. Trust boundaries

```
        Internet
           │
   ┌───────▼──────────────────────────────────────────────┐  B1  public edge (TLS, WAF-less nginx)
   │  nginx  ──►  PHP-FPM (web + API + admin)             │
   │                │            │            │           │
   │        B2 session   B3 token │   B4 admin │           │
   │                │            │            │           │
   │        ┌───────▼────────────▼────────────▼───────┐   │
   │        │        Application (Laravel)            │   │
   │        └───┬───────────┬──────────┬──────────┬───┘   │
   │      B5 DB │     B6 file│   B7 queue│   B8 outbound  │
   └────────────┼───────────┼──────────┼──────────┼───────┘
                │           │          │          │
           ┌────▼───┐  ┌────▼────┐ ┌───▼───┐ ┌────▼─────────────┐
           │ MySQL  │  │ storage │ │worker │ │ PSP / SMS / email │
           └────────┘  └─────────┘ └───────┘ └───────────────────┘
                │                                  ▲
                └──────── B9 backups ──────────────┘  (off-host)
```

| Boundary | What crosses it | Control at the boundary |
|---|---|---|
| **B1** public edge | all HTTP | TLS 1.2+, HSTS, security headers, rate limiting, request size caps, no `APP_DEBUG` |
| **B2** session (web) | cookie-authenticated user identity | session regeneration, `HttpOnly`/`Secure`/`SameSite`, CSRF token, idle timeout |
| **B3** API token | stateless client identity | Sanctum tokens with abilities, per-token rate limit, token scoping to the owning user |
| **B4** admin | privileged operator identity | separate guard, separate path, mandatory TOTP, 30-min idle timeout, IP allow-list option, full audit |
| **B5** database | SQL | least-privilege DB user, prepared statements, no `UPDATE/DELETE` grant on the audit table |
| **B6** file storage | uploaded and served files | MIME + magic-byte validation, re-encode, random names, private disk + signed URLs |
| **B7** queue | serialised jobs | only IDs in payloads, no raw PII, job signing not needed but payload validation is |
| **B8** outbound | PSP callbacks in, SMS/gateway calls out | signature verification, amount re-verification, allow-listed endpoints, timeouts |
| **B9** backups | full data snapshot | AES-256 encryption, key outside the host, restore tested quarterly |

---

## 5. STRIDE analysis

Severity is `H` (financial/legal/PII impact), `M` (operational or reputational), `L` (minor). "Residual"
is what remains after the mitigation — never `none`.

### 5.1 Public web surface (B1/B2)

| ID | S | Threat | Mitigation (Laravel mechanism) | Residual | Test |
|---|---|---|---|---|---|
| TH-01 | Spoofing | credential stuffing against consumer accounts | `RateLimiter::for('login')` 5/min/IP + 10/h/account; CAPTCHA after 3 failures; Argon2id; breach-list rejection; alert on ≥ 50 failures/min | M — a distributed botnet stays under per-IP limits | TM-IAM-005 |
| TH-02 | Spoofing | session hijack via stolen cookie | `session()->regenerate()` on login; `HttpOnly`, `Secure`, `SameSite=Lax`; 120-min idle; session invalidated on password change | L | TM-IAM-010 |
| TH-03 | Spoofing | OTP interception / SMS bombing | OTP 6 digits, 2-min TTL, single-use, 5 attempts, 3 requests/5 min per mobile, 10/day per IP, hashed at rest | M — SIM swap is outside app control | TM-IAM-003/004 |
| TH-04 | Tampering | price/quantity manipulation in the checkout request | server-side price resolution only; order line snapshots rules; client-submitted money fields ignored | L | TM-PRI-008, TM-CRT-006 |
| TH-05 | Tampering | mass assignment of `role`, `status`, `owner_id`, `referred_by` | explicit `$fillable`/`$guarded`, FormRequest `safe()` data, policy checks on transitions | L | TM-IAM-013, TM-MER-007 |
| TH-06 | Tampering | coupon/campaign parameter tampering (reuse beyond limit) | per-user usage rows, atomic counter inside the order transaction, unique key on `(coupon_id, user_id, order_id)` | L | TM-CRT-002 |
| TH-07 | Repudiation | user denies placing an order or a complaint | `adm_audit_logs` + order event trail + IP/UA + signed invoice snapshot | L | TM-ADM-004 |
| TH-08 | Information disclosure | IDOR on orders, addresses, invoices, tickets | policies on every model + scoped queries (`where owner_id = auth()->id()` at the query, not after fetch); 403/404 discipline | M — a forgotten route is the classic failure | TM-ORD-007, TM-<MOD>-A3 |
| TH-09 | Information disclosure | user enumeration via login/register/reset responses | identical response body and comparable timing for unknown vs wrong-password; reset always says "if the account exists…" | L | TM-IAM-012 |
| TH-10 | Information disclosure | scraping the catalog and public profiles | rate limit 120 req/min/IP, per-page result cap, no sitemap for out-of-stock, bot challenge on > 300 req/min | M — determined scrapers always win partially | load test + rate-limit test |
| TH-11 | Denial of service | expensive search / filter combinations | query cost cap (max 5 filters, max page 100), 600 ms budget, `FULLTEXT` index, cached category pages | M | TM-CAT-009/010 |
| TH-12 | Denial of service | large uploads / many images per product | 5 MB image / 20 MB document cap, max 12 images per product, nginx `client_max_body_size`, re-encode to bounded dimensions | L | TM-MER-011 |
| TH-13 | Elevation | horizontal: consumer → another consumer's data | policy + query scope; negative test per route; `RouteCoverageTest` manifest | M | TM-<MOD>-A3, G5 |
| TH-14 | Elevation | vertical: consumer/store → admin | separate `admin` guard, separate route group, 2FA-verified middleware, no role check by name in Blade | M | TM-IAM-008, TM-ADM-001 |

### 5.2 API surface (B3)

| ID | S | Threat | Mitigation | Residual | Test |
|---|---|---|---|---|---|
| TH-15 | Spoofing | stolen bearer token replayed | token hashed at rest (`hash('sha256')`), abilities scoped, revocation on password change, per-token rate limit | M | TM-IAM-007 |
| TH-16 | Tampering | token of a marketer used against another marketer's IDs | `tokenCan()` + policy on the resolved model; scope from `auth()->user()`, never from a request field | L | TM-MKT-005 |
| TH-17 | Information disclosure | API returns fields the UI hides (national ID, cost price) | API resources with explicit field lists; no `toArray()` of a model; a test asserts the JSON keys for each resource | L | TM-MKT-004, TM-PRI-006 |
| TH-18 | Elevation | IDOR through numeric IDs in `/api/v1/{resource}/{id}` | route-model binding + policy; 404 for out-of-scope when existence must not leak | M | TM-<MOD>-A3 |
| TH-19 | DoS | expensive API pagination / sorting by arbitrary column | allow-listed sort columns, max `per_page` 100, cursor pagination on large tables | L | API feature test |

### 5.3 Admin panel (B4)

| ID | S | Threat | Mitigation | Residual | Test |
|---|---|---|---|---|---|
| TH-20 | Spoofing | admin account takeover (phishing, reused password) | mandatory TOTP, 30-min idle, IP allow-list option, alert on new device/IP, 2FA reset needs a second admin | M | TM-IAM-006, TM-ADM-003 |
| TH-21 | Spoofing | admin session fixation | regenerate on login and on privilege change; separate cookie name and path | L | TM-IAM-010 |
| TH-22 | Tampering | admin edits a ledger entry or an invoice total | no admin route mutates the ledger; corrections are reversal commands that create new entries; invoice totals derive from lines | L | TM-FIN-003 |
| TH-23 | Repudiation | admin denies a price change, refund or settlement | every sensitive action audited with before/after diff, IP, UA, correlation ID; hash chain; DB-level no-UPDATE grant | L | TM-ADM-004/005 |
| TH-24 | Information disclosure | admin exports the full customer base | export is audited, capped (50,000 rows per job), requires a reason field, and PII columns are masked unless a second flag is set | M — an admin is authorised to export by design | TM-RPT-003 |
| TH-25 | Elevation | admin impersonation ("login as") used silently | impersonation requires a reason, is visibly indicated in the UI banner, audited, and cannot reach payment approvals | M | TM-ADM-007 |
| TH-26 | DoS | a long report query locks the DB during peak | report queries run read-only with a statement timeout, heavy exports queued, 30-day range guard | M | TM-RPT-007 |

### 5.4 Payment path (B8 inbound)

| ID | S | Threat | Mitigation | Residual | Test |
|---|---|---|---|---|---|
| TH-27 | Spoofing | forged callback marks an unpaid order paid | signature/HMAC verification per driver, source IP allow-list where the PSP publishes ranges, authority+amount re-verification with the PSP's verify API | L | TM-PAY-005 |
| TH-28 | Tampering | amount mismatch callback (pay 1 Toman for a 10 M order) | server compares callback amount to the stored payable amount; mismatch ⇒ `amount_mismatch`, no ledger, alert | L | TM-PAY-004 |
| TH-29 | Tampering | callback for order A used to mark order B paid | the callback resolves the payment by its own reference, and the order is read from that payment — never from a request field | L | TM-PAY-011 |
| TH-30 | Repudiation | "I paid but the system says I didn't" | every callback stored raw (body, headers, IP) in `pay_callback_logs`, retained 2 years, reconcilable against the PSP statement | M | TM-PAY-001, TM-FIN-008 |
| TH-31 | Information disclosure | gateway credentials leak via logs or error pages | credentials in `config/` from env, encrypted at rest for multi-gateway rows, never logged; log processor redacts `merchant_id`, `terminal_id`, `token` | L | TM-SHR-010 |
| TH-32 | DoS | callback flood exhausts workers | dedicated rate limit on the callback route (300/min/driver), queue the heavy work, respond fast (≤ 400 ms) | M | TM-PAY-012 |
| TH-33 | Elevation | replaying a valid callback to double-credit | unique `reference` on the ledger + idempotent handler + `lockForUpdate` on the payment row | L | TM-PAY-002/003 |

### 5.5 File upload and storage (B6)

| ID | S | Threat | Mitigation | Residual | Test |
|---|---|---|---|---|---|
| TH-34 | Tampering | polyglot/webshell upload (`.jpg` containing PHP) | magic-byte + `getimagesize` check, re-encode through GD/Imagick, random filename, extension forced from detected type, storage outside the webroot, `X-Content-Type-Options: nosniff` | L | TM-MER-010 |
| TH-35 | Information disclosure | identity documents served publicly | private disk + `URL::temporarySignedRoute` (15 min) + policy check on the route, not only on the link | L | TM-MER-005 |
| TH-36 | Information disclosure | image metadata leaks GPS of a store owner's home | EXIF stripped on re-encode | L | TM-MER-005 |
| TH-37 | DoS | decompression bomb / huge image dimensions | max pixels 40 MP, max dimensions 8000×8000, memory-bounded re-encode | L | upload feature test |
| TH-38 | Tampering | SVG with embedded script used as a logo | SVG rejected or sanitised through a whitelist; served with `Content-Disposition: attachment` when not sanitised | L | TM-MSG (attachment) |

### 5.6 Queue, scheduler, outbound integrations (B7/B8 outbound)

| ID | S | Threat | Mitigation | Residual | Test |
|---|---|---|---|---|---|
| TH-39 | Tampering | job payload altered in the DB | jobs carry IDs only; the worker re-loads and re-authorises state; payload validated before execution | L | job feature test |
| TH-40 | Information disclosure | PII in job payloads visible to anyone with DB read | payloads carry IDs; any exception is that the job encodes a mobile for an SMS — encrypted at rest and purged after send | M | TM-SHR-010 |
| TH-41 | DoS | notification fan-out saturates the queue | separate queue per channel, per-campaign cap, backpressure (pause at depth > 5,000), provider rate limits respected | M | TM-NTF (fan-out) |
| TH-42 | Spoofing | outbound webhook to a forged endpoint (SSRF via config) | gateway/provider endpoints from config only, never from user input; `Http::` with explicit host allow-list; no `file://`, no redirects to private ranges | L | SSRF test |
| TH-43 | Repudiation | a scheduled settlement ran but nobody knows who triggered it | scheduler runs are logged with the command, actor (`system`), and result counts | L | TM-FIN-005 |

### 5.7 Data at rest and backups (B5/B9)

| ID | S | Threat | Mitigation | Residual | Test |
|---|---|---|---|---|---|
| TH-44 | Information disclosure | DB dump leaks national IDs in clear | field-level encryption for national ID, IBAN, document numbers; disk encryption at rest; least-privilege DB user per service | M — an app-server compromise still decrypts with the app key | TM-PRV-001 |
| TH-45 | Tampering | attacker with DB access rewrites the audit log | append-only table, no `UPDATE/DELETE` grant, hash chain, `audit:verify` command, off-host copy of daily hashes | M | TM-ADM-005, TM-SHR-009 |
| TH-46 | Information disclosure | backup stolen from object storage | AES-256 client-side encryption, key held outside the storage account, access logs | L | NFR-DR-006 |
| TH-47 | DoS | ransomware on the app host destroys data and backups | off-host, versioned, immutable-retention bucket; restore drill quarterly | M | AC-24 |
| TH-48 | Tampering | malicious insider with DB access edits balances | ledger is append-only; balance mismatch triggers a reconciliation alert; monthly ledger-vs-report reconciliation | M | TM-FIN-002 |

### 5.8 Cross-cutting

| ID | S | Threat | Mitigation | Residual | Test |
|---|---|---|---|---|---|
| TH-49 | Tampering | stored XSS through product description, review, ticket or store profile | Blade escaping by default; rich text sanitised through an allow-list (no `<script>`, no `on*`, no `javascript:`); CSP with nonces blocks inline script | L | TM-CAT-008, TM-CMS |
| TH-50 | Tampering | CSRF on admin bulk actions | `VerifyCsrfToken` on all web routes; `SameSite=Lax`; no state change on GET | L | TM-ADM-008 |
| TH-51 | Tampering | SQL injection in filters, sorts or raw reports | Eloquent/query builder with bindings; `DB::raw` only with bindings; PHPStan rule; sqlmap-style test in the exploit suite | L | TM-SHR (SQLi) |
| TH-52 | Information disclosure | secrets committed to git | gitleaks gate, `.env.example` empty, rotation runbook | L | G8 |
| TH-53 | Tampering | supply-chain: a compromised composer/npm package | lockfiles committed, `composer audit`/`npm audit` gate, no `*` version constraints, review of new direct dependencies | M | G9 |

---

## 6. Abuse cases (product-specific fraud)

Each abuse case is written as an attack tree with the platform's detection and the residual exposure. These
are the frauds this business actually attracts; generic web threats are covered in §5.

### AB-01 — Commission fraud: self-dealing through fake referrals

**Goal.** A marketer earns commission on stores they own or control, or on stores that never trade.

```
Earn commission without a real sale
├── Register your own store under your QR           → self-referral
├── Register a relative's store                     → same address / same mobile prefix / same IP
├── Register 200 shell stores with borrowed IDs     → national ID reuse, no verifying document
├── Revisit an "inactive" store to claim the fee    → fake visit with a spoofed GPS point
└── Split a large store into 5 small ones            → same national ID, different trade name
```

| Control | Mechanism |
|---|---|
| Self-referral block | compare national ID, normalised mobile, postal code, and (IP + device fingerprint) of the marketer's registration session against the new store; any match ⇒ no attribution, `mkt.fraud_flag` row |
| Unique identity | one store per national ID, enforced by a DB unique index, not only by the duplicate-search screen (پیوست ۵ بند ۳) |
| Visit proof | a visit row requires a geo point inside the store's radius, a timestamp inside the marketer's shift, and a photo captured in-session (EXIF-stripped, but a server-side capture time is stored) |
| Fee condition | visit fee is payable only after the store's first paid invoice, or after admin approval of the visit — configurable, default = first paid invoice |
| Anomaly report | admin report: marketers with > 20 stores/month, > 30 % inactive stores, or a visit-to-invoice ratio below the configured floor |

**Residual:** a determined marketer with real, colluding store owners can still manufacture a small volume
of genuine-looking sales. Detected by the anomaly report, not prevented by code.

### AB-02 — Fake invoices to farm points, rewards and commissions

**Goal.** Create invoices that never correspond to goods, to mint points, star ratings, tier discounts and
commission.

```
Mint value from nothing
├── Store creates an invoice to itself              → same national ID / same address
├── Supplier creates a sub-order for a fake buyer   → buyer never confirms, no shipment
├── Invoice paid with a gateway that is later refunded in full → points kept
└── Circular trading between 3 colluding stores     → A→B→C→A, points accrue each loop
```

| Control | Mechanism |
|---|---|
| Points vest on delivery, not on payment | points are awarded on `delivered` + return-window expiry (configurable), so a refunded invoice never keeps points |
| Refund reverses points | compensating ledger entry on refund/return (TM-LOY-007) |
| Related-party detection | same national ID, same IBAN, same address, or bidirectional trading between two entities within 30 days ⇒ flag for review |
| Circular-trade report | graph query over `ord_sub_orders` for cycles within a window; surfaced to admin, not auto-blocked (false positives are real in wholesale) |
| Invoice requires a shipment or a pickup record | an invoice without a shipment after N days is flagged; settlement is held |
| Settlement hold | commission settles only after the return window closes |

**Residual:** slow, low-value circular trading below the report threshold. Handled by periodic manual
review, not by code.

### AB-03 — Points farming and reward laundering

**Goal.** Accumulate points without proportional purchases, then convert them into wallet credit.

```
Farm points
├── Many accounts, one person (multi-account)     → same device/IP, sequential mobiles
├── Buy → earn points → return goods → keep points → repeat
├── Exploit the reward rule (invoice just above 4× reward, then cancel)
└── Referral loops between two accounts
```

| Control | Mechanism |
|---|---|
| One reward per invoice, consumed atomically | reward redemption is a ledger entry created inside the order transaction; a cancelled order reverses it |
| Return reversal | points and reward reversed on return before the refund is released (TM-LOY-007) |
| Velocity limits | max 3 accounts per mobile-verified device fingerprint; referral points only after the referred store's first paid invoice |
| Rule replay | `loyalty:replay` detects drift between intended and actual awards monthly |
| Negative balance guard | decay/returns cannot push below zero; excess is carried and visible |

### AB-04 — Marketer lead stealing and store hijacking

**Goal.** Take over another marketer's store (and its commission stream) or read its commercial data.

```
Steal a lead
├── Claim an existing store via a new QR registration  → duplicate national ID must block this
├── Ask admin to reassign the store                     → social engineering, audited
├── Create a second account as the store owner          → mobile change without OTP
└── Read the store's invoices via the marketer panel     → scope leak
```

| Control | Mechanism |
|---|---|
| Duplicate identity blocks re-registration | unique index on national ID + mobile + postal code; a duplicate returns "already registered, contact support" without revealing the owning marketer |
| Reassignment is a privileged, audited command | requires a reason, writes before/after owner, notifies the previous marketer, appears in the activity report |
| Ownership change needs OTP | changing the store's mobile requires an OTP to both the old and the new number |
| Panel scope | every marketer query is scoped at the query builder level; `RouteCoverageTest` proves it per route; no export route exists for the role |

### AB-05 — Gateway callback forgery

**Goal.** Mark an order paid without paying, or trigger a refund to an attacker-controlled Sheba.

```
Forge a callback
├── POST a hand-written success body                 → signature check
├── Replay a real callback from another order        → payment resolved by its own reference
├── Pay 1 Toman for a 10M order                      → amount comparison
├── Refund request with an altered IBAN              → IBAN checksum + refund goes to the original payer only
└── Exploit a driver that skips verification in test mode → test mode refused outside `testing`/`staging`
```

| Control | Mechanism |
|---|---|
| Signature + verify call | HMAC over the raw body with the driver secret, then a server-to-server verify with the PSP (`Http::` with a 5 s timeout, retried twice) |
| Amount and reference binding | amount compared to the stored payable amount; refunds target only the original payment's payer identity, never a request-supplied IBAN |
| Test mode guard | sandbox drivers are refused when `app()->environment('production')` — asserted by a test |
| Alerting | > 2 % rejected callbacks in 10 min pages the operator (a forgery campaign or a broken integration) |

### AB-06 — PII harvesting from the marketer panel

**Goal.** Exfiltrate national IDs, mobiles and addresses of stores and customers for resale or spam.

```
Harvest PII
├── Browse the panel manually, 40 stores per hour      → per-role rate limit + access logging
├── Call the API list endpoint with a large page size  → max 100, cursor pagination
├── Export (if any export exists for the role)         → no export route exists for marketers
└── Screenshot the form (unavoidable)                  → masking reduces the value
```

| Control | Mechanism |
|---|---|
| Masking by default | national ID masked to last 4 digits for every role except the record owner and an audited admin reveal; full value revealed through an explicit action that is logged |
| Volume anomaly detection | a marketer viewing > 3× the median store count per day is flagged; > 500 PII reveals per day locks the account pending admin review |
| No bulk export | `route:list` assertion: the marketer role has no export/report route (TM-MKT-004) |
| Access log per record | `PII_ACCESS` audit row per reveal, with actor, target and reason |
| Watermarking | the panel shows the viewer's username and timestamp on the store detail header, so a screenshot is attributable |

### AB-07 — Geo-tracking misuse

**Goal.** (a) A marketer spoofs a location to fake visits; (b) the platform or a manager uses tracking to
surveil staff beyond working hours; (c) an attacker reads a store's exact coordinates.

```
Misuse of location
├── Spoofed GPS (mock location app)          → accuracy, speed, and route-plausibility checks
├── Track after shift / on holiday           → capture only inside an active visit session
├── Read coordinates of a store you don't own → scope + coordinate rounding for non-owners
└── Bulk-scrape the map                      → rate limit + server-side clustering
```

| Control | Mechanism |
|---|---|
| Session-scoped capture | the client can only submit a point inside an explicitly started visit session; the server checks `session.started_at <= now() <= session.expires_at` (30 min) and rejects otherwise |
| Plausibility checks | implausible speed (> 120 km/h between consecutive points), accuracy worse than 100 m, or a jump > 5 km in < 2 min ⇒ point stored but flagged, visit not auto-approved |
| Consent and visibility | explicit consent at first use, persistent "tracking on" indicator, one-tap off, consent withdrawal recorded (privacy doc §4) |
| Coordinate precision | non-owner roles see coordinates rounded to ~100 m; the exact point is available only to the owning marketer and to an audited admin action |
| Retention | raw points deleted after 90 days; only aggregated visit records (store, date, duration) are kept |

### AB-08 — Refund and return abuse by consumers

**Goal.** Keep goods and get money back; or refund to a different account.

| Control | Mechanism |
|---|---|
| Return window strictness | server-side window in Tehran time, inclusive boundary tested (TM-RTN-002) |
| Photo evidence | return requires ≥ 1 photo; supplier rejection escalates to admin, not to auto-refund |
| Refund destination | always the original payer; Sheba change requires OTP + admin review |
| Repeated-return scoring | a consumer with > 3 returns in 90 days is flagged; the next return requires admin approval |
| Restock verification | refund is released only after the supplier marks the goods received, or admin overrides with a reason |

### AB-09 — Fake supplier identity and invoice forgery

**Goal.** Sell as a brand you are not, or issue an invoice that misrepresents the seller.

| Control | Mechanism |
|---|---|
| Supplier verification | legal document upload + admin approval before any product is published; unapproved suppliers can only draft |
| Brand claim | claiming a brand requires an admin-approved authorisation letter; brand pages list only approved sellers |
| Invoice integrity | invoice PDFs are generated server-side from snapshots, include a verification code and a QR to `/verify/{code}`, and are signed (hash in the DB) so a doctored PDF fails verification |
| Impersonation report | "گزارش تخلف" on every supplier profile routes to the moderation queue with an SLA |

---

## 7. Risk register (top 10, post-mitigation)

| Rank | Risk | Likelihood | Impact | Residual | Owner |
|---|---|---|---|---|---|
| 1 | IDOR on a new route (a forgotten policy) | Medium | High | Medium | module owner + G5 |
| 2 | Commission/points fraud by a colluding network | High | Medium | Medium | Finance + Marketing |
| 3 | Gateway callback forgery / misconfiguration | Low | High | Low | Payments owner |
| 4 | Marketer PII harvesting | Medium | High | Medium | Marketing + Admin |
| 5 | Ledger corruption through a non-idempotent command | Medium | High | Low | Finance owner (2 approvals) |
| 6 | Admin account takeover | Low | Critical | Low | Admin owner |
| 7 | Backup loss / failed restore | Low | Critical | Low | Ops + quarterly drill |
| 8 | Scraping / competitive intelligence | High | Low | Medium | accepted |
| 9 | XSS through rich text (product, ticket, CMS) | Medium | Medium | Low | CMS owner |
| 10 | Supply-chain compromise of a PHP/npm dependency | Low | High | Medium | Ops + G9 |

---

## 8. What this model deliberately does not defend against

- A determined attacker with root on the app host (they hold the app key and the DB credentials).
- A colluding admin and supplier acting slowly and within policy — detected by reporting, not prevented.
- SIM-swap-based OTP interception — mitigated only by alerting on a mobile change, not prevented.
- Denial of service at the network layer (the client's hosting provider must absorb it).
- Scraping by a well-distributed residential proxy network.

---

## 9. Changelog

| Version | Date | Change |
|---|---|---|
| 1.0 | 2026-11-25 | 53 STRIDE threats across 9 trust boundaries, 9 product abuse cases, risk register, explicit non-defences. |
