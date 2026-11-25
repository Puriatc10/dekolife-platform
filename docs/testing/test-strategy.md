# Test Strategy — Dekolife Platform

**Purpose.** Define how the product is tested: which layer owns which risk, what must run on every pull
request, what runs nightly, what a human must do, and what "green" means.
**Audience.** Engineering, QA, reviewers, the Orchestrator.
**Status.** Baseline v1 — binding for all modules.

**Source:** contract `1405-01DK` article 4 clause 3 (unit, integration and acceptance testing before
delivery) and appendix 10 (acceptance, defect logging, training); stack, module layout and CI rules from
`../canonical-context.md` §2, §3, §7; measurable targets from `./non-functional-requirements.md`;
scenarios from `./acceptance-criteria.md`.

---

## 1. Quality objectives

The product fails expensively in exactly five places. Every rule in this document exists to protect one
of them.

| # | Risk | Failure mode | Primary defence |
|---|---|---|---|
| R1 | **Money** | a wrong total, a lost Toman, a double charge, a settlement that does not reconcile | unit tests on `App\Shared\Money` + feature tests on every money command + ledger reconciliation test |
| R2 | **Points / rewards** | a rule change silently rewrites history, or a replay produces different numbers | versioned rule sets + deterministic replay test + property tests |
| R3 | **Authorization** | one user reaches another's order, store, marketer network or export | negative test for every scoped route, mandatory, P0 |
| R4 | **State machines** | an order skips a state, a frozen order settles, a returned invoice keeps its points | transition table tests, one per legal and illegal edge |
| R5 | **Idempotency** | a retried gateway callback or a double-clicked submit duplicates a ledger entry | replay tests on every external-facing command |

Anything that does not touch R1–R5 is still tested, but it does not get to block a release on its own.

---

## 2. The pyramid, with numbers

| Layer | What it proves | Volume target (v1) | Runtime budget | Runs |
|---|---|---|---|---|
| **Unit** (Pest, no DB) | domain invariants, money and points arithmetic, Jalali conversion, state-transition tables, value objects, formatters | ≥ 700 tests | ≤ 90 s | every PR |
| **Feature** (Pest + MySQL) | HTTP contracts, validation, policies, transactions, ledger effects, queue dispatch, audit rows, migrations | ≥ 400 tests | ≤ 6 min (parallel, 4 workers) | every PR |
| **Browser** (Pest browser / Dusk-class, real HTTP) | the 8 journeys that must never break: register+verify, store onboarding, product publish, checkout+pay, order split tracking, complaint freeze, marketer commission, admin export | ≥ 30 tests | ≤ 12 min | nightly + pre-release |
| **Exploit suite** (feature tests written adversarially) | IDOR, CSRF, XSS, SQLi, SSRF, upload, rate limit, callback forgery | ≥ 60 tests | ≤ 3 min | every PR (P0) |
| **Performance** (k6) | NFR-PERF, NFR-SCALE | 6 scripts | 45 min | pre-release + quarterly |
| **Manual UAT** | business acceptance | 25 scenarios (`acceptance-criteria.md`) | 3 rounds × 3 days | UAT window |

Ratio guard: unit ≥ 55 % of all automated tests, feature ≤ 45 %, browser ≤ 5 %. A PR that adds a browser
test for logic that a unit test could cover is rejected in review — browser tests are the most expensive
and the flakiest thing in the suite.

---

## 3. Layer boundaries — what belongs where

### 3.1 Unit (no database, no HTTP, no container)

Belongs here:
- `App\Shared\Money`: add/subtract/multiply by basis points, rounding, comparison, formatting, zero and
  overflow behaviour (`BIGINT` limit, negative guard).
- Points arithmetic: coefficient application, floor rule, star derivation (`floor(points / 100)`), decay.
- Reward-application rule: full deduction when `invoice ≥ 4 × reward`, otherwise half (پیوست ۴ بند ۴).
- Tier discount selection: 1–100 ⇒ 4 %, 100–200 ⇒ 5 %, > 200 ⇒ fixed Toman (پیوست ۴ بند ۲۰–۲۱).
- Jalali conversion, period boundaries, Persian digit normalisation, national-ID check digit, mobile
  normalisation, IBAN checksum.
- Order/sub-order/shipment/return state-transition tables (as pure data + a guard function).
- Policy decision objects fed with plain DTOs (not Eloquent models) where the rule is not query-dependent.

Does **not** belong here: anything that needs `DB::`, `Storage::`, `Http::`, `Queue::`, config resolution
or a container binding. Those are feature tests, and pretending otherwise produces tests that pass
against mocks and fail in production.

### 3.2 Feature (MySQL, no browser)

Belongs here:
- Every route: status code, JSON/HTML envelope, validation errors, redirects, signed URL generation.
- Authorization: positive and negative for every scoped resource (R3).
- Transactions: order + sub-orders + invoice + ledger committed atomically; a forced exception rolls all
  of it back.
- Ledger: every command produces balanced entries; balances derive correctly; reversal never mutates.
- Idempotency: replay the same request/callback N times ⇒ one effect.
- Queue: `Queue::fake()` asserts the job is dispatched with the right payload; a separate test runs the
  job for real and asserts its effect.
- Audit: the expected `adm_audit_logs` row exists with the right actor/target/diff.
- Migrations: `migrate:fresh` + the module's seeder produce a usable schema.
- Time-dependent rules with a frozen clock.

Not here: visual layout, JS behaviour, real gateway redirects, real SMS delivery.

### 3.3 Browser (real HTTP, real session, real JS)

Only journeys where the interaction between Blade + Livewire + Alpine + session + redirects is the thing
under test. One test per journey, no combinatorics. The 8 journeys:

1. Register → SMS code → logged-in dashboard.
2. Marketer QR link → store form with photo upload → admin approval → first-login password change.
3. Supplier creates product with images and tier prices → product page shows the right price per role.
4. Consumer search → filter → product → cart → coupon → checkout → sandbox gateway → paid order page.
5. Multi-vendor order → per-seller status visible to consumer and supplier.
6. Complaint freeze → admin resolution → refund visible in the wallet.
7. Marketer commission statement → settlement request.
8. Admin login with 2FA → financial report → Excel export downloaded.

### 3.4 Manual / exploratory

- UAT scenarios (`acceptance-criteria.md`) — the client's contractual acceptance.
- Session-based exploratory charters, 60–90 min each, one charter per risky area per release:
  "break the price resolver", "abuse the marketer panel", "starve the checkout of stock",
  "make the Persian layout collapse at 360 px", "attack the upload endpoint".
- Accessibility walkthrough with a keyboard only and a screen reader on 3 templates.

---

## 4. MySQL in Docker — non-negotiable

**Rule:** every feature, browser and performance test runs against **MySQL 8.4**, the same engine and
version as production. SQLite is forbidden for anything above pure unit tests.

Why this is a rule and not a preference:

| Behaviour | MySQL 8.4 | SQLite | Consequence if we test on SQLite |
|---|---|---|---|
| `JSON` columns | native binary JSON, functions, indexes | text affinity | JSON queries pass in CI, fail in production |
| `FULLTEXT` (search, AD-14) | InnoDB full-text index | different FTS module | search tests are meaningless |
| Strict mode / `sql_mode` | `STRICT_TRANS_TABLES` rejects truncation | silent coercion | a 300-char value silently truncates in prod |
| Row locking (`SELECT … FOR UPDATE`) | real gap/next-key locks | database-level lock | the "last unit of stock" race test cannot be written |
| Generated columns, `CHECK` | supported, enforced | partial | constraint tests are lies |
| `BIGINT` arithmetic and overflow | exact | similar but not identical | money edge tests diverge |
| Collation / Persian sorting | `utf8mb4_0900_ai_ci` | different | search ordering and uniqueness differ |
| `ON DUPLICATE KEY`, `INSERT IGNORE` | MySQL semantics | different | idempotency tests prove the wrong thing |

### 4.1 Implementation

- `compose.yaml` at repo root declares `mysql:8.4` alongside `app`, `nginx`, `mailpit`.
- Local: `docker compose up -d mysql`, `DB_HOST=127.0.0.1 DB_PORT=3306`.
- CI: GitHub Actions service container `mysql:8.4` with `--health-cmd`, the job waits for healthy before
  running tests (canonical §7).
- `phpunit.xml` sets `DB_CONNECTION=mysql`, `DB_DATABASE=dekolife_test`, and never falls back to sqlite.
- **Guard test** (`tests/Feature/Infrastructure/DatabaseDriverTest.php`): asserts
  `DB::connection()->getDriverName() === 'mysql'` and that `@@version` starts with `8.4`. It fails the
  suite if someone runs it on sqlite or MariaDB. This single test is why the rule survives contact with
  a tired developer at 23:00.
- Parallel runs: `php artisan test --parallel --processes=4` — Laravel provisions `dekolife_test_1..4`;
  each worker migrates once via `--parallel` bootstrap. `RefreshDatabase` is used, which wraps each test
  in a transaction.
- Tests that genuinely need a fresh schema (migration tests, `FULLTEXT` index tests) use
  `DatabaseMigrations` and are isolated in their own group so they do not slow the main suite.

---

## 5. Factories and seeders policy

### 5.1 Factories

- **F1.** One factory per aggregate root, in the owning module (`app/Modules/<Module>/database/factories/`).
  No factory creates another module's model directly; use a nested factory call only through the owning
  module's factory.
- **F2.** Every factory has meaningful **states**, not booleans: `->pendingApproval()`, `->approved()`,
  `->frozen()`, `->withTierPrices([...])`, `->paid()`, `->shipped()`. A test never assembles a state by
  hand from five `create()` calls.
- **F3.** Money and points are **never random**. `fake()->numberBetween()` is banned for any `*_amount`,
  quantity, coefficient or percentage. Every monetary attribute has an explicit default that is a round,
  readable number (e.g. 1,000,000 T), and tests override it explicitly. Random money produces tests that
  fail once a month on a boundary and get retried instead of fixed.
- **F4.** Deterministic randomness where randomness is legitimate (names, text): `fake('fa_IR')` with a
  seed set in `TestCase::setUp()` (`$this->faker->seed(1404)`), so a failing run is reproducible.
- **F5.** No factory hits the network, the queue, storage or the clock. Fakes are injected in the test.
- **F6.** A factory may not create more than 10 rows by default; fan-out tests state their counts
  explicitly (`->count(50)`), so suite runtime stays predictable.
- **F7.** Every factory has at least one test that asserts the default state is valid against the domain
  invariants — a broken factory otherwise poisons hundreds of tests with a confusing failure.

### 5.2 Seeders

- **S1.** Seeders exist **only for reference data**: roles and permissions, admin menus, gateway
  definitions (12 rows, inactive by default), shipping carriers, units of measure, provinces/cities,
  loyalty rule-set v1, notification templates, CMS static pages, FAQ categories.
- **S2.** Seeders never create business data (no demo orders, no demo invoices). Demo data lives in a
  separate `DemoSeeder` that is only called explicitly by `--class=DemoSeeder` on staging.
- **S3.** Seeders are idempotent (`updateOrCreate` on a natural key) so `db:seed` can run twice.
- **S4.** A seeder's output is asserted by a test (`ReferenceDataSeederTest`) — reference data is a
  contract, and a missing gateway row breaks checkout silently.
- **S5.** No production data, no real national IDs, no real mobile numbers anywhere in the repo. Test
  data uses the reserved fictional ranges and clearly fake identifiers.

### 5.3 Volume datasets (for performance and pagination)

Built by a dedicated command, never by tests:

| Command | Produces | Used by |
|---|---|---|
| `php artisan demo:seed --profile=small` | 500 SKUs, 40 stores, 20 suppliers, 300 users | local dev, PR smoke |
| `php artisan demo:seed --profile=launch` | 25,000 SKUs, 1,200 stores, 150 suppliers, 60,000 users, 5,000 orders | staging, VT-04 at L-A |
| `php artisan demo:seed --profile=peak` | 60,000 SKUs, 4,000 stores, 250 suppliers, 250,000 users, 20,000 orders | quarterly load test at L-B |

---

## 6. Fakes: gateways, SMS, storage, clock

### 6.1 Payment gateways

A `FakeGatewayDriver` implements the same `GatewayDriver` contract as real drivers and is scripted per
test:

| Script | Behaviour |
|---|---|
| `->succeeds()` | callback with a valid signature and the exact amount |
| `->fails('insufficient_funds')` | callback with a failure status and a provider reason |
| `->timesOut()` | no callback within the window; the scheduled verifier resolves it |
| `->replays(n)` | the same callback delivered `n` times, optionally in parallel |
| `->forgesSignature()` | callback with a bad signature |
| `->sendsWrongAmount()` | amount differs by 1 Toman |

Rules:
- The fake is registered in the container only in the `testing` environment; a test asserts the real
  driver is bound in `production` config (`GatewayBindingTest`).
- Each real driver has a **contract test** (same test class, driven by the interface) that runs against
  its sandbox nightly, not in the PR suite. That is how "12 drivers" stays honest without 12 network
  dependencies in CI.
- Callback tests assert the raw HTTP path (`POST /payments/callback/{driver}`), not a service call, so
  middleware, CSRF exemption and signature verification are exercised.

### 6.2 SMS / WhatsApp / email

- `FakeSmsGateway` writes to an in-memory outbox; `$this->assertSmsSentTo('+98912…', 'کد تأیید')`.
- OTP codes are deterministic in tests (`123456` unless overridden) so browser tests can type them.
- A `MessageDeduplicationTest` asserts the same OTP is not sent twice for one request.
- Mail goes to `mailpit` locally and to `Mail::fake()` in feature tests.

### 6.3 Storage

- `Storage::fake('private')` and `Storage::fake('public')`; assertions on existence, disk and filename
  shape (randomised, no original name, no path traversal).
- One test asserts identity documents are **only** on the private disk and that a public URL for them
  returns 403/404.

### 6.4 Clock

- `travelTo()` / `Carbon::setTestNow()` with an explicit UTC instant derived from a Tehran wall-clock
  time: `Carbon::parse('2026-03-20 23:59:00', 'Asia/Tehran')->utc()`.
- Never `now()->addDays(3)` inline in a test body without freezing first — that is how a test passes at
  10:00 and fails at 23:59.

---

## 7. Time-travel testing for Jalali periods

Time is a first-class test dimension because the contract prices periods, discounts, freezes and
settlements in Jalali months and day windows.

### 7.1 Required boundary cases (one test each, `tests/Unit/Shared/JalaliCalendarTest.php`)

| Case | Input | Expectation |
|---|---|---|
| Month lengths | Farvardin–Shahrivar 31, Mehr–Bahman 30, Esfand 29 (common) / 30 (leap) | correct last day; no overflow into the next month |
| Leap year | 1403 (leap) vs 1404 (common) | Esfand has 30 vs 29 days |
| Year boundary | 1404-12-29 23:59:59 → 1405-01-01 00:00:00 | period closes and opens exactly once, no duplicate or missing day |
| Day cut-off in Tehran | order at 23:59 Tehran (= 20:29 UTC) | belongs to the Tehran day, not the UTC day |
| UTC storage | any created_at | stored UTC, rendered Tehran, round-trips without drift |
| No DST | every day of 1404–1410 | offset is constant +03:30; test fails if tzdata introduces a transition (AD-16) |
| Inclusive windows | 7-day complaint window, day 7 at 23:59 vs day 8 at 00:00 | allowed vs rejected, exactly on the boundary |
| Settlement period | 1st 00:00:00 to last day 23:59:59 Tehran | all and only the expected invoices included |
| Persian digits in dates | `۱۴۰۵/۰۱/۱۵` input | parsed to 2026-04-04, not rejected |

### 7.2 Rules

- **T1.** Every test that asserts a period, window or expiry freezes the clock. No exceptions; a reviewer
  rejects `now()`-dependent assertions.
- **T2.** Period fixtures are **Jalali-first**: the test names the period in Jalali (`مرداد ۱۴۰۴`) and
  converts once, so a reader can map the test to the contract.
- **T3.** A property-based test asserts that for 1,000 random UTC instants in 1400–1410,
  `toJalali(toGregorian(d)) === d` (round-trip) and the formatted date parses back to the same instant.
- **T4.** Scheduled commands are tested by invoking the command with a frozen clock, not by waiting:
  `$this->travelTo(...); $this->artisan('orders:expire-abandoned');`.
- **T5.** Time-travel must not break `created_at` semantics: a test asserts that `updated_at` is not
  rewritten when the clock is frozen mid-test.

---

## 8. Money and points determinism

### 8.1 Rules

- **M1.** All money is integer Toman in `BIGINT`; a static-analysis rule and a test forbid `float`/`double`
  casts on `*_amount` fields. A unit test asserts `Money::fromToman(0.1 + 0.2)` is rejected, not silently
  rounded.
- **M2.** One rounding rule, applied once per line, then summed: percentage results are computed in
  integer basis points and rounded **half-up** at the final step of each line; discounts round **down**
  in favour of the customer only where the contract says "حداقل" or "حداکثر". Each rule that rounds has
  a unit test with a `.5` boundary.
- **M3.** Order of operations is fixed and tested: base → role/tier price → volume break → promotion →
  coupon → loyalty discount → shipping → tax. A test asserts the sequence by recording intermediate
  values; swapping two steps must fail a test, not a customer.
- **M4.** Ledger is append-only. A test asserts that no code path issues `UPDATE`/`DELETE` on
  `fin_ledger_entries` (query-log assertion) and that a correction is a reversal pair.
- **M5.** Balances are always derived from entries, never cached as truth. A test recomputes every
  balance from scratch and compares it to the cached projection for a 1,000-order fixture.
- **M6.** Points replay: `php artisan loyalty:replay --rule-set=3` recomputed over 500 invoices produces
  the identical points total as the live path; any difference fails CI. This is the guard against rule
  changes silently rewriting history.
- **M7.** Idempotency: every money command accepts a key; a test issues the same command 5 times and
  asserts one effect and five identical responses.
- **M8.** Currency never mixes: a test asserts no code writes a non-Toman amount into a `*_amount` column
  (there is one currency in v1, and the type system should say so).
- **M9.** Commission uses the plan version recorded on the entry; a test changes the plan afterwards and
  asserts historical entries do not move.
- **M10.** Excel/report totals equal ledger totals for a 1,000-order fixture (`VT-12`), asserted by the
  same test that generates the export.

### 8.2 Property-based tests (the cheap insurance)

Using a data-provider over generated random order baskets (seeded), assert these invariants for 200
baskets:

1. `sum(line_totals) + shipping + tax − discounts == order_total`.
2. `order_total ≥ 0` for any legal basket.
3. `consumer_price ≥ store_price ≥ supplier_peer_price` for the same SKU and quantity.
4. Points awarded are monotonic in invoice value for a fixed coefficient.
5. A full refund of every line returns the wallet to its pre-order balance exactly.

These catch arithmetic drift that example-based tests miss.

---

## 9. Coverage targets

Measured with `php artisan test --coverage --min=75` (line) and the branch report in CI. **Line coverage
is a floor, not a goal** — the rule that matters is "every R1–R5 behaviour has a test", and coverage is
the cheap proxy for "someone forgot an entire class".

| Module | Line floor | Branch floor | Must-have behaviour tested |
|---|---|---|---|
| Shared (`Money`, Jalali, audit trait) | 95 % | 90 % | every arithmetic and calendar boundary |
| Pricing | 95 % | 90 % | resolution order, tier prices, volume, rounding |
| Loyalty | 95 % | 90 % | coefficients, reward rule, decay, replay determinism |
| Finance | 95 % | 90 % | ledger balance, reversal, settlement, reconciliation |
| Payments | 90 % | 85 % | callback idempotency, signature, amount mismatch, retries |
| Orders | 90 % | 85 % | state machine, split, cancellation, stock reservation |
| Compensation | 90 % | 85 % | commission plans, conditions, settlement linkage |
| Marketing | 90 % | 85 % | QR attribution, lead ownership, scope isolation |
| Invoices | 85 % | 80 % | numbering, credit notes, PDF content |
| Returns | 85 % | 80 % | return window, restock, points reversal |
| Inventory | 85 % | 80 % | reservation, release, concurrency |
| Merchants | 80 % | 75 % | onboarding, approval, duplicate detection |
| Identity / Users | 85 % | 80 % | OTP, lockout, 2FA, session invalidation |
| Catalog | 80 % | 75 % | publish rules, search index, SEO fields |
| Cart | 80 % | 75 % | recalculation, coupon, tamper resistance |
| Shipping | 80 % | 75 % | cost rules, tracking, compensation trigger |
| Notifications | 75 % | 70 % | fan-out, dedupe, failure isolation |
| Messaging | 75 % | 70 % | thread scope, attachment validation |
| Admin | 80 % | 75 % | 2FA gate, guard isolation, audit coverage |
| Reporting | 80 % | 75 % | totals reconcile, export content |
| Tenders / Classifieds / Patogh / Samples / Reviews / Promotions | 70 % | 65 % | state rules, visibility, moderation |
| **Repository overall** | **75 %** | **65 %** | |

Files excluded from coverage, with a written reason in `phpunit.xml`: generated migrations, service
providers that only bind, config files, Blade templates (covered by browser tests + axe), and the
`FakeGatewayDriver` (test support code).

---

## 10. Flaky tests

Flakiness destroys the value of the suite faster than missing tests, so it is treated as a defect with
an owner.

| Rule | Detail |
|---|---|
| **F-1** | A test that fails twice in 20 consecutive runs without a code change is **flaky** and must be quarantined the same day. |
| **F-2** | Quarantine = Pest group `quarantine`, excluded from the blocking gate, visible in the nightly report, with a tracking issue, owner and 14-day fix deadline. |
| **F-3** | Maximum 5 quarantined tests at any time. The 6th blocks new feature work until one is fixed. |
| **F-4** | A quarantined test that covers R1–R5 (money, points, authorization, state machine, idempotency) blocks **release**, not just merge. |
| **F-5** | No `--retry` in the PR pipeline. Retries hide flakiness; only the nightly browser suite may retry once, and every retry is reported as a flake count in the build summary. |
| **F-6** | Known flake sources and their fix: unfrozen clock → freeze; random money → explicit values; shared DB state → `RefreshDatabase`; order-dependent assertions → assert on sets, not arrays; network → fake; animation timing in browser tests → `waitFor` on a state, never `sleep()`; parallel workers sharing a storage path → per-worker disk root. |
| **F-7** | `sleep()` in any test is a review rejection. Wait for a condition. |
| **F-8** | Each quarantine removal requires a root-cause note in the tracking issue ("why it was flaky"), not just "fixed". |

---

## 11. Environment matrix

| Environment | Purpose | Data | Gateways / SMS |
|---|---|---|---|
| Local (Docker) | TDD loop | `demo:seed small` or factories | fakes |
| CI | merge gate | factories + reference seeders | fakes; MySQL 8.4 service |
| Staging | UAT, load, browser nightly | `demo:seed launch` (25 k SKUs) | sandbox gateways, SMS stub with readable outbox |
| Production | live | real | real |

Rules: staging is rebuilt from seeders weekly so it never drifts into an unreproducible state; no
production dump is ever copied down (PII, and the contract's confidentiality clause); feature flags gate
anything unfinished so `main` is always deployable.

---

## 12. Entry and exit criteria

| Phase | Entry | Exit |
|---|---|---|
| Unit/feature work | ticket has acceptance criteria + a DoR pass | tests written first, red → green, coverage floor met, no quarantine added |
| PR merge | CI green, review approved | gates in `../testing/quality-gates.md` pass |
| Release candidate | `main` green, changelog updated | nightly browser suite green, ZAP baseline 0 high, load test at L-A within NFR-PERF |
| UAT | RC deployed to staging with seeded launch data | 100 % P0 scenarios pass, ≤ 5 open P3/P4 (`acceptance-criteria.md` §0.3) |
| Final delivery | UAT sign-off, docs bundle ready | backup drill passed, training delivered, source + docs handed over |

---

## 13. Reporting

Every CI run publishes: pass/fail counts per layer, coverage per module vs floor, flake count, quarantine
list, slowest 10 tests, and the diff of coverage against `main`. Every release publishes the same plus
load-test p95 per NFR-PERF row and the ZAP summary. A release note without these numbers is not a release
note.

---

## 14. Changelog

| Version | Date | Change |
|---|---|---|
| 1.0 | 2026-11-25 | Pyramid with volumes and runtime budgets, MySQL-only rule with guard test, factory/seeder policy, fake-gateway contract, Jalali boundary matrix, money determinism rules, coverage floors, flaky policy. |
