# Security Checklist — Dekolife Platform

**Purpose.** One page per control: what it is, what implements it, and the exact negative test that proves
it works. If a control cannot name its test, it does not exist.
**Audience.** Engineering (per PR), the security reviewer (per release), the Orchestrator (per gate).
**Status.** Baseline v1 — every row must read `Implemented` with a green test before go-live.

**Source:** contract `1405-01DK` article 4 clause 8 (data protection, API keys, backups, SQLi, brute force,
malicious upload), appendix 1 clauses 7 and 10, appendix 9 clause 3 (SSL, brute force, captcha, RBAC, input
validation); mechanisms fixed by `../canonical-context.md` §2 and §5; threats by
`./threat-model.md`; NFR targets by `../requirements/non-functional-requirements.md` §8.

---

## 0. How to use this file

| Column | Meaning |
|---|---|
| **Control** | the security property that must hold |
| **Implementation** | the Laravel mechanism and the planned file that owns it. Paths follow `../canonical-context.md` §3 and are created during implementation. |
| **Negative test** | the test that fails if the control is removed — always an attack, never a happy path |
| **Gate** | which CI gate runs it (`G5` = exploit suite, `G8` = secret scan, `G10` = architecture, `G2` = PHPStan); see `../testing/quality-gates.md` |
| **Status** | `Planned` until the code and the test both exist; then `Implemented`. `Waived` needs a written waiver with an expiry. At go-live: 0 `Planned`, 0 `Waived` for any row marked **P0**. |

Rows marked **P0** are never waivable (see `quality-gates.md` §7). A control is only "done" when its
negative test is in the exploit suite and green on MySQL.

---

## 1. Authentication — `SC-AUTH`

| ID | Control | Implementation | Negative test | Gate | Status |
|---|---|---|---|---|---|
| SC-AUTH-01 **P0** | Passwords are never recoverable | `Hash::make` with Argon2id (`config/hashing.php`: memory 65536, time 3, threads 1); `App\Modules\Identity\Domain\PasswordPolicy` enforces ≥ 10 chars and a 10,000-entry common-password list | `TM-IAM-001` asserts the DB value starts with `$argon2id$` and is not equal to the plaintext; a test asserts a 6-char password is rejected | G5 | Planned |
| SC-AUTH-02 **P0** | Login is brute-force resistant | `RateLimiter::for('login')` in `app/Modules/Identity/Providers/IdentityServiceProvider.php` (5/min/IP, 10/h/account); lockout 15 min after 10 failures; CAPTCHA after 3 failures | `TM-IAM-005` fires 11 wrong logins and asserts 429/lockout, then asserts a correct password is still refused during the lockout | G5 | Planned |
| SC-AUTH-03 **P0** | OTP cannot be brute-forced or replayed | `iam_otp_tokens`: hashed code, 2-min TTL, 5 attempts, single use, 3 requests/5 min per mobile, 10/day/IP | `TM-IAM-003` asserts the 6th attempt invalidates the code; `TM-IAM-004` asserts the 4th request in 5 min is 429 | G5 | Planned |
| SC-AUTH-04 **P0** | Admin requires a second factor | `admin` guard + `RequireTwoFactor` middleware on the whole admin route group; TOTP 6 digits, ±1 step, replay rejected; enrolment forced before any admin page renders | `TM-IAM-006` logs in with a correct password and no TOTP and asserts no admin page renders; a reused TOTP is rejected | G5 | Planned |
| SC-AUTH-05 | Password reset is single-use and short-lived | Laravel password broker, 60-min expiry, token invalidated on use and on password change | `TM-IAM-009` reuses a reset token and asserts a validation error, not a second reset | G5 | Planned |
| SC-AUTH-06 | Account enumeration is not possible | identical response body/status for unknown vs wrong password; reset always answers "if the account exists…" | `TM-IAM-012` compares both responses and asserts byte equality of the message and the same status | G5 | Planned |
| SC-AUTH-07 **P0** | Role cannot be self-assigned | `$fillable` excludes `role`, `status`, `referred_by`; role changes only through `AssignRoleAction` behind `admin` + 2FA | `TM-IAM-013` POSTs `role=admin` to the profile form and asserts the stored role is unchanged and 403/422 is returned | G5 | Planned |
| SC-AUTH-08 | Sessions die on privilege change | `SessionGuard` listener invalidates other sessions on password change and role change | `TM-IAM-007` changes the password and asserts an old session cookie lands on the login page | G5 | Planned |
| SC-AUTH-09 | API tokens are scoped and revocable | Sanctum personal access tokens with abilities; hashed at rest; revoked on password change | `TM-MKT-005` uses marketer A's token against marketer B's resource and asserts 403 with 0 rows | G5 | Planned |

---

## 2. Session management — `SC-SESS`

| ID | Control | Implementation | Negative test | Gate | Status |
|---|---|---|---|---|---|
| SC-SESS-01 **P0** | No session fixation | `session()->regenerate()` on login and on privilege change | `TM-IAM-010` captures the pre-login ID and asserts it is rejected after login | G5 | Planned |
| SC-SESS-02 **P0** | Cookies are not readable by JS or sent cross-site | `config/session.php`: `http_only=true`, `secure=true`, `same_site=lax`, `domain=null`, name `dekolife_session`; admin uses a separate cookie name and path | header assertion test: `Set-Cookie` contains `HttpOnly`, `Secure`, `SameSite=Lax` | G5 | Planned |
| SC-SESS-03 | Idle and absolute timeouts | consumer 120 min idle / 12 h absolute; admin 30 min idle / 8 h absolute (`config/session.php` + admin guard config) | `TM-ADM-002` time-travels 31 min and asserts the admin session is gone while a consumer session survives | G5 | Planned |
| SC-SESS-04 | Session storage is server-side | database session driver; no data in the cookie beyond the ID | inspection test: cookie value is a 40-char ID, not a serialised payload | G2 | Planned |
| SC-SESS-05 | Logout destroys the session server-side | `Auth::logout()` + `invalidate()` + `regenerateToken()` | `TM-IAM-016` reuses a logged-out cookie and asserts redirect to login, no data | G5 | Planned |

---

## 3. CSRF — `SC-CSRF`

| ID | Control | Implementation | Negative test | Gate | Status |
|---|---|---|---|---|---|
| SC-CSRF-01 **P0** | All state-changing web routes require a token | `VerifyCsrfToken` (default, no route excluded except `payments/callback/*` which is signature-verified instead) | `TM-IAM-011` POSTs `/profile` with no token ⇒ 419 and no write | G5 | Planned |
| SC-CSRF-02 **P0** | No state change on GET | route table assertion: every `GET` route is a read; destructive verbs are POST/PATCH/DELETE | `RouteVerbTest` iterates `route:list --json` and fails on a GET route mapped to a write action | G10 | Planned |
| SC-CSRF-03 | Token rotation on login | `regenerateToken()` after authentication | stale-token POST after login ⇒ 419 | G5 | Planned |
| SC-CSRF-04 | Admin bulk actions are protected the same way | same middleware on the admin group; no `except` entry | `TM-ADM-008` cross-site POST to a bulk action ⇒ 419 | G5 | Planned |
| SC-CSRF-05 | API is not cookie-authenticated | `routes/api.php` uses Sanctum tokens only; `statefulApi()` not enabled for third-party clients | a cookie-only request to `/api/v1/*` ⇒ 401 | G5 | Planned |

---

## 4. XSS and output encoding — `SC-XSS`

| ID | Control | Implementation | Negative test | Gate | Status |
|---|---|---|---|---|---|
| SC-XSS-01 **P0** | User content is escaped by default | Blade `{{ }}` everywhere; `{!! !!}` is banned on user content by a custom Blade lint (`scripts/lint-blade.php`) | `TM-CAT-008` stores `<script>alert(1)</script>` in a product description and asserts the rendered HTML contains the escaped entity and no `<script>` | G5/G10 | Planned |
| SC-XSS-02 **P0** | Rich text is sanitised on write | `App\Shared\Support\HtmlSanitizer` with an allow-list (`p,br,strong,em,ul,ol,li,h2,h3,a[href],img[src]`), strips `on*`, `javascript:`, `data:` (except images), `<script>`, `<iframe>` | sanitizer unit test with 12 payloads (event handler, `javascript:`, encoded `<`, SVG onload, `srcdoc`) asserts all are neutralised | G5 | Planned |
| SC-XSS-03 **P0** | CSP blocks inline script | `Content-Security-Policy: default-src 'self'; script-src 'self' 'nonce-…'; style-src 'self' 'nonce-…'; img-src 'self' data: https:; frame-ancestors 'none'; object-src 'none'; base-uri 'self'` set in `app/Http/Middleware/SecurityHeaders.php` | header test asserts the CSP is present, has a nonce, and contains neither `unsafe-inline` nor `unsafe-eval` for scripts | G5 | Planned |
| SC-XSS-04 | JSON responses are not HTML-interpreted | `Content-Type: application/json` + `X-Content-Type-Options: nosniff` on all API responses | response header assertion on 3 API routes | G5 | Planned |
| SC-XSS-05 | Stored content in the admin panel is escaped too | same Blade rules; the admin list uses `{{ }}` for seller-supplied names — admin XSS is the highest-impact variant | `TM-CAT-008` also asserts the admin product list is escaped | G5 | Planned |
| SC-XSS-06 | Uploaded SVG cannot execute | SVG rejected or served with `Content-Disposition: attachment` and `X-Content-Type-Options: nosniff` | upload an SVG with `<script>` and assert it is rejected (or served as an attachment) | G5 | Planned |

---

## 5. SQL injection — `SC-SQLI`

| ID | Control | Implementation | Negative test | Gate | Status |
|---|---|---|---|---|---|
| SC-SQLI-01 **P0** | No string-concatenated SQL | Eloquent/query builder; `DB::raw`/`whereRaw` only with bindings; PHPStan rule `NoRawSqlConcatenationRule` | static: the rule fails the build on a concatenated `whereRaw` (fixture test asserts the rule fires) | G2 | Planned |
| SC-SQLI-02 **P0** | Search and filters are bound | `cat_search_index` query built with bindings; filter keys and sort columns allow-listed | `TM-CAT-012` sends `' OR 1=1 --`, `1;DROP TABLE`, `%'` in every filter and asserts 0 SQL errors, unchanged row counts, 200 with empty results | G5 | Planned |
| SC-SQLI-03 | Sort and pagination are allow-listed | `SortableRequest` maps a public key to a column; unknown key ⇒ 422 | request with `sort=password` ⇒ 422, no SQL error | G5 | Planned |
| SC-SQLI-04 | Reports use the same rule | report builder uses bindings; date-range parameters parsed as dates, not strings | report filter with a quoted payload ⇒ 422 | G5 | Planned |
| SC-SQLI-05 | DB user is least-privilege | app user: `SELECT, INSERT, UPDATE, DELETE` on `app_*`; **no** `UPDATE/DELETE` on `adm_audit_logs`; no `DROP`/`ALTER` at runtime | `TM-ADM-005` attempts an update on the audit table as the app user and asserts a permission error | G5 | Planned |

---

## 6. Authorization and IDOR — `SC-AUTHZ`

| ID | Control | Implementation | Negative test | Gate | Status |
|---|---|---|---|---|---|
| SC-AUTHZ-01 **P0** | Every scoped resource has a policy | `app/Modules/<Module>/Http/Policies/*` registered in the module provider; controllers call `$this->authorize()` or use `authorizeResource` | `TM-<MOD>-A2` (wrong role ⇒ 403) for every module | G5 | Planned |
| SC-AUTHZ-02 **P0** | Queries are scoped at the builder, not after fetch | `StoreScope`/`MarketerScope` global scopes on `mer_merchants`, `ord_sub_orders`, `cmp_*`, `msg_*` | `TM-MKT-003` asserts 0 rows of another marketer in every list endpoint | G5 | Planned |
| SC-AUTHZ-03 **P0** | Route coverage is enforced, not assumed | `tests/Feature/Security/RouteCoverageTest` diffs `route:list` against a manifest of negative tests | a deliberately added unprotected route fails the suite (self-test of the gate) | G5 | Planned |
| SC-AUTHZ-04 **P0** | Enumeration does not leak | 404 (not 403) where existence is sensitive; sequential scan over 100 IDs | `TM-ORD-007` asserts ≤ 2 response codes and 0 records over 100 IDs | G5 | Planned |
| SC-AUTHZ-05 **P0** | Marketers cannot export PII | no export route in the marketer group; `route:list` assertion | `TM-MKT-004` asserts the route does not exist and the admin export returns 403 for a marketer | G5 | Planned |
| SC-AUTHZ-06 | Secondary roles do not widen scope | policy resolves the **active context**, not the union of roles | `TM-<MOD>-A4`: a store+marketer user in store context cannot read marketer leads and vice versa | G5 | Planned |
| SC-AUTHZ-07 | Mass assignment is closed | explicit `$fillable`; `Model::preventSilentlyDiscardingAttributes()` in non-production to catch mistakes early | `TM-IAM-013`, `TM-MER-007` assert ignored fields | G5 | Planned |
| SC-AUTHZ-08 **P0** | Admin routes are unreachable from a consumer session | separate guard, separate path, 2FA middleware, distinct cookie | `TM-ADM-001` asserts 403/redirect for every admin route with a consumer session | G5 | Planned |

---

## 7. SSRF and outbound requests — `SC-SSRF`

| ID | Control | Implementation | Negative test | Gate | Status |
|---|---|---|---|---|---|
| SC-SSRF-01 **P0** | No user-controlled URL is fetched | gateway endpoints, SMS endpoints and webhook URLs come from `config/` only; no "test URL" feature | `TM-SSRF-001` posts a product image URL of `http://169.254.169.254/latest/meta-data/` and asserts it is rejected before any HTTP call (`Http::fake` asserts 0 requests) | G5 | Planned |
| SC-SSRF-02 | Outbound hosts are allow-listed | `Http::withOptions(['allow_redirects' => false])` + a `GuardedHttpClient` that rejects non-https, private ranges, and hosts outside the configured list | unit test with 15 URLs (localhost, 127.0.0.1, 10/8, 172.16/12, 192.168/16, `file://`, `gopher://`, decimal IP, redirect to internal) asserts all rejected | G5 | Planned |
| SC-SSRF-03 | Image import is fetched server-side but bounded | import downloads only from an allow-list of supplier domains, max 5 MB, 5 s timeout, no redirects | import with a redirect to an internal host asserts rejection | G5 | Planned |
| SC-SSRF-04 | Response size and time are bounded | `timeout(5)`, `connectTimeout(2)`, `max body 10 MB` | test asserts a slow endpoint is abandoned at 5 s | G5 | Planned |

---

## 8. File upload — `SC-UPLOAD`

| ID | Control | Implementation | Negative test | Gate | Status |
|---|---|---|---|---|---|
| SC-UPLOAD-01 **P0** | Type is detected, not trusted | `mimes:jpg,jpeg,png,webp` + `getimagesize()` + magic bytes; extension derived from the detected type | `TM-MER-010` uploads a `.php` renamed `.jpg` and a polyglot GIF/PHP and asserts both are rejected with nothing written to disk | G5 | Planned |
| SC-UPLOAD-02 **P0** | Identity documents are private | `Storage::disk('private')` + `URL::temporarySignedRoute` 15 min + policy check on the serving route | `TM-MER-005` requests the file without a signature and as another user ⇒ 403/404 | G5 | Planned |
| SC-UPLOAD-03 **P0** | Filenames cannot traverse or execute | random `Str::uuid()` filename, original name stored as metadata only, storage outside the webroot, no execute permission | upload named `../../.env` and `shell.php.jpg` asserts the stored path stays inside the disk root | G5 | Planned |
| SC-UPLOAD-04 | Images are re-encoded and stripped | GD/Imagick re-encode, EXIF removed, max 40 MP and 8000×8000, output WebP/JPEG | upload a 20,000×20,000 image ⇒ validation error; upload with GPS EXIF ⇒ stored file has no GPS tag | G5 | Planned |
| SC-UPLOAD-05 | Size limits are enforced in two places | FormRequest `max:5120` (image) / `max:20480` (document) **and** nginx `client_max_body_size 25m` | upload 6 MB image ⇒ 422; assert nginx returns 413 above 25 MB | G5 | Planned |
| SC-UPLOAD-06 | Uploaded archives are never extracted | zip/rar rejected outright (no use case) | upload `.zip` ⇒ 422 | G5 | Planned |

---

## 9. Rate limiting and abuse — `SC-RATE`

| ID | Control | Implementation | Negative test | Gate | Status |
|---|---|---|---|---|---|
| SC-RATE-01 **P0** | Login and OTP are limited | named limiters in the Identity provider (see SC-AUTH-02/03) | `TM-IAM-005`, `TM-IAM-004` | G5 | Planned |
| SC-RATE-02 | Search and catalog are limited | `throttle:catalog` 120/min/IP, 300/min burst with a bot challenge | 301 requests in a minute ⇒ 429 on the 121st | G5 | Planned |
| SC-RATE-03 | Checkout is limited per user | 10/min/user + 30/hour/user | `TM-CRT-007` asserts 429 and no order created on the 11th | G5 | Planned |
| SC-RATE-04 | Payment callbacks are limited per driver | 300/min/driver, plus a global 2,000/min | `TM-PAY-013` floods the endpoint and asserts 429 with no partial state | G5 | Planned |
| SC-RATE-05 | Messaging is limited | 20/min/user, 200/day/user | `TM-MSG-006` asserts 429 and no message row | G5 | Planned |
| SC-RATE-06 | API is limited per token | 120/min/token, 1,000/hour/token | API test with 121 requests ⇒ 429 with `Retry-After` | G5 | Planned |
| SC-RATE-07 | PII reveal is limited | 30/hour/user, hard lock at 500/day | `TM-ADM-009` asserts lock after the threshold and an alert | G5 | Planned |

---

## 10. Security headers — `SC-HDR`

Implemented once in `app/Http/Middleware/SecurityHeaders.php`, applied globally; verified by a header test
on 5 routes (home, product, login, admin, API).

| ID | Header | Value | Rationale |
|---|---|---|---|
| SC-HDR-01 | `Strict-Transport-Security` | `max-age=31536000; includeSubDomains` | prevents SSL-strip (پیوست ۹ بند ۳) |
| SC-HDR-02 | `Content-Security-Policy` | see SC-XSS-03 | blocks injected script |
| SC-HDR-03 | `X-Content-Type-Options` | `nosniff` | stops MIME confusion on uploads |
| SC-HDR-04 | `X-Frame-Options` | `DENY` | clickjacking; `frame-ancestors 'none'` in CSP is the primary |
| SC-HDR-05 | `Referrer-Policy` | `strict-origin-when-cross-origin` | no path/query leakage to PSPs |
| SC-HDR-06 | `Permissions-Policy` | `geolocation=(self), camera=(self), microphone=()` | only the panel uses geo/camera |
| SC-HDR-07 | `Cache-Control` on authenticated pages | `no-store, private` | prevents shared-cache leakage |
| SC-HDR-08 | Server banner | `server_tokens off` | no version disclosure |

Negative test: a single `SecurityHeadersTest` asserts all 8 on 5 routes and fails if any is missing or if
`unsafe-inline` appears in `script-src`.

---

## 11. Secrets and configuration — `SC-SECRET`

| ID | Control | Implementation | Negative test | Gate | Status |
|---|---|---|---|---|---|
| SC-SECRET-01 **P0** | No secret in git | gitleaks with the project ruleset; `.env.example` has empty values; `.gitignore` covers `.env*` except the example | G8 with a planted fake gateway key in a branch (verified once during setup, then removed) | G8 | Planned |
| SC-SECRET-02 **P0** | No `env()` outside config | PHPStan rule `NoEnvOutsideConfigRule` | fixture test asserts the rule fires on `env('X')` in a controller | G2 | Planned |
| SC-SECRET-03 **P0** | Debug is off in production | `APP_DEBUG=false` enforced by a boot check that throws if `app()->isProduction() && config('app.debug')` | `TM-OPS-001` boots with debug on in production env and asserts the exception | G5 | Planned |
| SC-SECRET-04 | Gateway credentials are encrypted at rest | `pay_gateway_configs.credentials` uses the `encrypted:array` cast; never returned by any admin endpoint (write-only field) | API/admin test asserts the response body contains no credential key | G5 | Planned |
| SC-SECRET-05 | Keys are rotatable | `php artisan key:rotate --re-encrypt` runbook; `APP_KEY_INDEX` for blind indexes can be rotated with a re-index command | dry-run test asserts re-encryption of 10 fixture rows produces identical plaintext after rotation | G5 | Planned |
| SC-SECRET-06 | Error pages leak nothing | custom 404/419/429/500 templates with a correlation ID; `APP_DEBUG=false`; no stack trace, SQL, or path | `TM-OPS-002` triggers a 500 and asserts the body contains no `Exception`, no file path, no SQL | G5 | Planned |

---

## 12. Logging and audit — `SC-LOG`

| ID | Control | Implementation | Negative test | Gate | Status |
|---|---|---|---|---|---|
| SC-LOG-01 **P0** | Sensitive events are audited | `App\Shared\Audit\Auditable` trait + `adm_audit_logs`; the event list is NFR §8.1 | `TM-ADM-004` executes all 20 event types and asserts exactly one row each | G5 | Planned |
| SC-LOG-02 **P0** | Audit rows are append-only and tamper-evident | no `UPDATE`/`DELETE` grant; `prev_hash` chain; `php artisan audit:verify` | `TM-ADM-005` asserts a DB permission error; `TM-SHR-009` tampers a row by fixture and asserts detection | G5 | Planned |
| SC-LOG-03 **P0** | No PII in logs | `RedactSensitive` Monolog processor with a field deny-list | `TM-SHR-010` logs a payload with national ID, mobile, IBAN, OTP, token and asserts none appears | G5 | Planned |
| SC-LOG-04 | Auth events are logged with IP and UA | `iam_login_attempts` + audit rows for success/failure/lockout | `TM-IAM-017` asserts the row contents after a failed login | G5 | Planned |
| SC-LOG-05 | Logs carry a correlation ID | middleware sets `request_id`, propagated to logs, jobs and error reports | log sample assertion: every line has `request_id` | G5 | Planned |
| SC-LOG-06 | Exports and bulk actions are logged | `rpt.export`, `ntf.bulk_send`, `privacy.export`, `pii.reveal` | `TM-RPT-003` asserts the export audit row with filters and row count | G5 | Planned |
| SC-LOG-07 | Failed jobs are visible and alertable | `failed_jobs` table + alert on > 20 failures in 10 min | a forced job failure asserts the alert hook fires | G5 | Planned |

---

## 13. Dependencies and supply chain — `SC-DEP`

| ID | Control | Implementation | Negative test | Gate | Status |
|---|---|---|---|---|---|
| SC-DEP-01 **P0** | No known high/critical vulnerability | `composer audit --no-dev`, `npm audit --omit=dev` | gate fails on a seeded vulnerable lock in a branch (verified during setup) | G9 | Planned |
| SC-DEP-02 | Versions are pinned | `composer.lock` and `package-lock.json` committed; no `*` or `dev-master` in direct deps | `scripts/check-lockfiles.php` asserts no wildcard constraint | G9 | Planned |
| SC-DEP-03 | New dependencies are reviewed | PR checklist requires a justification line for each new direct dependency; framework-provided alternatives preferred | review process, not a test | — | Planned |
| SC-DEP-04 | Patch SLAs are met | critical ≤ 72 h, high ≤ 14 days; tracked in the dependency board | monthly report | — | Planned |
| SC-DEP-05 | Frontend assets come from the build | no CDN `<script>` for JS; CSP `script-src 'self'` would block it anyway | `scripts/check-external-assets.php` greps Blade for external script tags | G7 | Planned |

---

## 14. Backup and recovery — `SC-BACKUP`

| ID | Control | Implementation | Negative test | Gate | Status |
|---|---|---|---|---|---|
| SC-BACKUP-01 **P0** | Daily automated backup with restore | `php artisan backup:run` on the scheduler at 02:30 Tehran; manifest with row counts + checksum | `AC-24` — full restore in ≤ 4 h, row counts match | G14 | Planned |
| SC-BACKUP-02 **P0** | Backups are encrypted | AES-256 client-side; key outside the storage account | a restore attempt without the key fails; `TM-BKP-001` asserts the dump is not readable as plain SQL | G14 | Planned |
| SC-BACKUP-03 **P0** | Backups are off-host and versioned | separate provider/region, immutable retention, 30 d / 12 m / 5 y | inventory review + a delete attempt inside the retention window is refused | G14 | Planned |
| SC-BACKUP-04 | Financial tables have ≤ 15 min RPO | binary-log shipping; point-in-time restore tested | `TM-BKP-002` restores to −15 min and asserts a known transaction is absent | G14 | Planned |
| SC-BACKUP-05 | Backup failure alerts | scheduler failure → alert within 5 min | forced failure asserts the alert hook | — | Planned |
| SC-BACKUP-06 | Restore is documented for a non-author | runbook in `docs/operations`, executed by the client's sysadmin during `AC-24` | the drill is only passed when a non-author completes it | G14 | Planned |

---

## 15. Cryptography and PII at rest — `SC-CRYPTO`

| ID | Control | Implementation | Negative test | Gate | Status |
|---|---|---|---|---|---|
| SC-CRYPTO-01 **P0** | National ID and IBAN are field-encrypted | `encrypted` cast + `national_id_hash` blind index (HMAC with `APP_KEY_INDEX`) | `TM-PRV-001` reads the raw column and asserts it is ciphertext, then asserts duplicate detection still works via the hash | G5 | Planned |
| SC-CRYPTO-02 | No weak randomness | `Str::random`, `random_int`, `uuid4`; `rand()`/`mt_rand()` banned by a PHPStan rule for tokens | fixture test asserts the rule fires on `mt_rand()` in a token generator | G2 | Planned |
| SC-CRYPTO-03 | Constant-time comparisons for secrets | `hash_equals` for signature and token comparison | unit test asserts no `===` on a signature (rule or review) | G2 | Planned |
| SC-CRYPTO-04 | TLS configuration is strong | TLS 1.2+, modern cipher list, TLS 1.0/1.1 disabled, HSTS | external scan + `curl --tlsv1.0` fails | G13 | Planned |
| SC-CRYPTO-05 | PII is masked by default in the UI | see `./privacy-and-PII.md` §7 | `TM-PRV-002` asserts the full national ID never appears in any list HTML | G5 | Planned |

---

## 16. Payment-specific controls — `SC-PAY`

| ID | Control | Implementation | Negative test | Gate | Status |
|---|---|---|---|---|---|
| SC-PAY-01 **P0** | Callback signature is verified | per-driver `verifySignature(rawBody, headers)` with `hash_equals` | `TM-PAY-005` forges a signature ⇒ 400, no state change | G5 | Planned |
| SC-PAY-02 **P0** | Amount is re-verified server-side | compare callback amount to `pay_transactions.amount` (integer) | `TM-PAY-004` sends +1 Toman ⇒ `amount_mismatch`, no ledger | G5 | Planned |
| SC-PAY-03 **P0** | Callback is idempotent | unique `reference` on the ledger + `lockForUpdate` on the payment row | `TM-PAY-002` (sequential ×5) and `TM-PAY-003` (parallel ×5) assert one effect | G5 | Planned |
| SC-PAY-04 **P0** | Payment resolves its own order | order read from `pay_transactions.order_id`, never from a request field | `TM-PAY-011` sends a valid body with another order's authority ⇒ no state change | G5 | Planned |
| SC-PAY-05 | Refunds go to the original payer | refund target derived from the original payment, not from a submitted IBAN | `TM-RTN-008` submits a different IBAN ⇒ 422 | G5 | Planned |
| SC-PAY-06 | Sandbox drivers cannot run in production | `abort_if(app()->isProduction() && $driver->isSandbox(), 500)` at binding time | `TM-PAY-014` boots production with a sandbox driver ⇒ exception | G5 | Planned |
| SC-PAY-07 | Callback traffic is retained for reconciliation | raw body, headers, IP stored in `pay_callback_logs` for 2 years, encrypted at rest | `TM-PAY-015` asserts the row exists and is not readable as plaintext | G5 | Planned |

---

## 17. Operational hygiene — `SC-OPS`

| ID | Control | Implementation | Negative test | Gate | Status |
|---|---|---|---|---|---|
| SC-OPS-01 | No debug tooling in production | `APP_DEBUG=false`, no `telescope`/`debugbar` in `composer.json` `require` (dev only), boot guard | `TM-OPS-001` | G5 | Planned |
| SC-OPS-02 | Admin path is not discoverable from the storefront | dedicated path/domain, no link, `robots.txt` disallow, 404 on the storefront prefix | grep test: no Blade file links to the admin path | G5 | Planned |
| SC-OPS-03 | Maintenance mode does not leak | `php artisan down --secret` returns 503 with a static page | test asserts no stack trace and the `Retry-After` header | G5 | Planned |
| SC-OPS-04 | Health endpoints expose no internals | `/health` returns `{status: ok}` only; readiness returns component booleans, no versions | response body assertion | G5 | Planned |
| SC-OPS-05 | Deploy artifact is immutable | built image tagged with the commit SHA; no `git pull` on the server | deployment review | — | Planned |
| SC-OPS-06 | Queue workers run as a non-root user | container user `app`, read-only filesystem except storage | container inspection | — | Planned |

---

## 18. Release security evidence pack

Attached to every release note. A release without it is not a release.

| # | Evidence | Produced by |
|---|---|---|
| 1 | G1–G10 green report with the commit SHA | CI |
| 2 | Exploit-suite summary (count, 0 failures) | CI |
| 3 | ZAP baseline report (auth + anon), triage of medium findings | G13 |
| 4 | `composer audit` / `npm audit` output | G9 |
| 5 | Coverage per module vs floor | G4 |
| 6 | New/changed audit event list | PR checklist |
| 7 | PII inventory diff (any new personal field) | PR checklist + `privacy-and-PII.md` §3 |
| 8 | Migration list with a destructive-change declaration | G6 |
| 9 | Backup drill date and result (must be ≤ 90 days old) | G14 |
| 10 | Open waivers with expiry | `waivers.json` |

---

## 19. Quick verification commands

```bash
# headers
curl -sSI https://staging.example.ir/ | grep -Ei 'strict-transport|content-security|x-frame|x-content-type|referrer|permissions'

# TLS floor
curl -sS --tlsv1.0 https://staging.example.ir/ -o /dev/null   # must fail
curl -sS --tlsv1.2 https://staging.example.ir/ -o /dev/null   # must succeed

# debug leak
curl -sS https://staging.example.ir/__nonexistent__ | grep -Ei 'exception|stack|/var/www' && echo LEAK || echo OK

# secrets
gitleaks detect --config=.gitleaks.toml --redact

# dependencies
composer audit --no-dev; npm audit --omit=dev

# audit chain
php artisan audit:verify
```

---

## 20. Changelog

| Version | Date | Change |
|---|---|---|
| 1.0 | 2026-11-25 | 17 control families, 105 controls (40 P0), each with mechanism, negative test and gate; release evidence pack; verification commands. |
