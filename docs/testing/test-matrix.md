# Test Matrix — Dekolife Platform

**Purpose.** Turn `./test-strategy.md` into an assignment sheet: which module needs which test type, which
specific cases are mandatory, and which of them are P0 gates that block merge or release.
**Audience.** Engineering, QA, reviewers, the Orchestrator.
**Status.** Baseline v1 — the must-have column is binding.

**Source:** module list from `../canonical-context.md` §3; risk model R1–R5 and coverage floors from
`./test-strategy.md`; scenarios from `../requirements/acceptance-criteria.md`; contract appendix clauses
as cited per row.

---

## 1. Legend

### 1.1 Test-type columns

| Column | Meaning | Runs | Blocks merge |
|---|---|---|---|
| `U` | Unit (no DB) | every PR | yes |
| `F` | Feature (MySQL 8.4) | every PR | yes |
| `B` | Browser (real HTTP/JS) | nightly + pre-release | nightly only |
| `E` | Exploit / negative-security suite | every PR | yes |
| `L` | Load / performance (k6) | pre-release, quarterly | release |
| `A` | Manual UAT (acceptance scenario) | UAT window | go-live |

Counts are **minimums**, not estimates. A module below its floor is an incomplete module.

### 1.2 Concern tags used in the must-have tables

| Tag | Concern | Applies when |
|---|---|---|
| `$` | **Money** — integer Toman, rounding, ledger balance, no double effect | the module writes or computes an amount |
| `Pt` | **Points** — accrual, coefficient, reward rule, decay, replay determinism | the module awards, deducts or redeems points |
| `A` | **Authorization** — positive + negative for every scoped resource | the module exposes a per-user or per-tenant resource |
| `S` | **State machine** — every legal transition passes, every illegal one is rejected | the module owns a lifecycle |
| `I` | **Idempotency** — the same external input applied twice has one effect | the module handles callbacks, retries, queues or user double-submits |

### 1.3 Case IDs

`TM-<MODULE>-<NNN>` — stable. `P0` in the last column means the case is in a blocking gate (§4).

---

## 2. Master matrix

| # | Module | Prefix | U | F | B | E | L | A | P0 module |
|---|---|---|---|---|---|---|---|---|---|
| — | Shared kernel (Money, Jalali, audit, policies) | `shr_` | 85 | 10 | 0 | 2 | 0 | — | **yes** |
| 1 | Identity | `iam_` | 60 | 45 | 6 | 20 | 0 | AC-01, AC-18 | **yes** |
| 2 | Users | `usr_` | 25 | 20 | 2 | 4 | 0 | AC-01 | no |
| 3 | Merchants | `mer_` | 20 | 35 | 3 | 8 | 0 | AC-02, AC-13 | **yes** |
| 4 | Catalog | `cat_` | 30 | 40 | 3 | 3 | 2 | AC-19, AC-21 | **yes** |
| 5 | Pricing | `pri_` | 55 | 30 | 2 | 2 | 1 | AC-03 | **yes** |
| 6 | Inventory | `stk_` | 20 | 18 | 0 | 1 | 1 | AC-06 | **yes** |
| 7 | Cart | `crt_` | 25 | 22 | 2 | 2 | 0 | AC-04 | no |
| 8 | Orders | `ord_` | 45 | 45 | 4 | 6 | 1 | AC-07, AC-08 | **yes** |
| 9 | Invoices | `inv_` | 20 | 22 | 1 | 3 | 0 | AC-04, AC-16 | no |
| 10 | Payments | `pay_` | 30 | 40 | 3 | 10 | 1 | AC-05, AC-06 | **yes** |
| 11 | Finance | `fin_` | 50 | 30 | 1 | 3 | 1 | AC-16 | **yes** |
| 12 | Marketing | `mkt_` | 25 | 30 | 3 | 8 | 1 | AC-02, AC-14 | **yes** |
| 13 | Compensation | `cmp_` | 30 | 25 | 2 | 4 | 1 | AC-11 | **yes** |
| 14 | Loyalty | `loy_` | 55 | 30 | 2 | 2 | 1 | AC-12, AC-13 | **yes** |
| 15 | Promotions | `prm_` | 15 | 15 | 1 | 1 | 0 | AC-04 | no |
| 16 | Shipping | `shp_` | 15 | 18 | 1 | 1 | 0 | AC-08 | no |
| 17 | Returns | `rtn_` | 20 | 22 | 2 | 3 | 0 | AC-09, AC-10 | **yes** |
| 18 | Tenders | `tnd_` | 10 | 12 | 0 | 1 | 0 | — | no |
| 19 | Classifieds | `cls_` | 8 | 12 | 1 | 2 | 0 | AC-20 | no |
| 20 | Patogh | `ptg_` | 6 | 10 | 0 | 1 | 0 | — | no |
| 21 | Samples | `smp_` | 6 | 8 | 0 | 1 | 0 | — | no |
| 22 | Messaging | `msg_` | 10 | 15 | 1 | 4 | 0 | AC-15 | no |
| 23 | Notifications | `ntf_` | 10 | 15 | 1 | 1 | 1 | AC-22 | no |
| 24 | Cms | `cms_` | 8 | 10 | 1 | 2 | 0 | — | no |
| 25 | Reviews | `rvw_` | 8 | 12 | 0 | 2 | 0 | AC-08 | no |
| 26 | FieldOps | `geo_` | 10 | 12 | 1 | 4 | 0 | — | no |
| 27 | Admin | `adm_` | 12 | 30 | 3 | 8 | 0 | AC-17, AC-18 | **yes** |
| 28 | Reporting | `rpt_` | 15 | 20 | 1 | 2 | 0 | AC-16 | **yes** |
| | **Total** | | **728** | **653** | **47** | **111** | **11** | 25 scenarios | 14 P0 modules + shared |

Browser tests are deliberately concentrated on the 8 journeys in `./test-strategy.md` §3.3; modules with
`B = 0` are covered end-to-end through the journey of the module that owns the screen (e.g. Inventory has
no screen of its own).

---

## 3. Cross-cutting must-have families

These cases are **duplicated per module**, because a shared test in `Shared` cannot prove that *this*
module's route is scoped correctly.

### 3.1 Money (`$`) — mandatory in every module that writes an amount

| ID pattern | Case | Assertion |
|---|---|---|
| `TM-<MOD>-$1` | amount persisted as integer | column type is `BIGINT`, value has no decimal part, no float cast in the model |
| `TM-<MOD>-$2` | rounding boundary | a `.5` basis-point case rounds per M2 of the strategy, exactly once |
| `TM-<MOD>-$3` | zero and negative guard | zero is legal where the contract allows it; negative is rejected with a validation error |
| `TM-<MOD>-$4` | ledger balance | sum of entries for the aggregate equals the projected balance |
| `TM-<MOD>-$5` | reversal, not mutation | a correction adds a reversal pair; the original row is byte-identical afterwards |
| `TM-<MOD>-$6` | concurrency | two parallel writers produce a consistent total, no lost update (`lockForUpdate` proven) |

### 3.2 Points (`Pt`)

| ID pattern | Case | Assertion |
|---|---|---|
| `TM-<MOD>-Pt1` | coefficient application | points = floor(amount × coefficient), coefficient and rule-set version recorded |
| `TM-<MOD>-Pt2` | replay determinism | replay over the same invoices yields identical totals |
| `TM-<MOD>-Pt3` | non-negative balance | decay/return cannot push the balance below zero; excess is carried, not lost |
| `TM-<MOD>-Pt4` | policy change isolation | editing the rule set afterwards does not change historical entries |

### 3.3 Authorization (`A`) — mandatory in every module with a scoped resource

| ID pattern | Case | Assertion |
|---|---|---|
| `TM-<MOD>-A1` | anonymous | 401/redirect to login, no data in the body |
| `TM-<MOD>-A2` | wrong role | 403, no data, no state change |
| `TM-<MOD>-A3` | same role, other tenant | 403 (own-scope) or 404 (existence must not leak) — the chosen code is asserted and documented |
| `TM-<MOD>-A4` | secondary role | a user holding store + marketer sees only what each active context allows |
| `TM-<MOD>-A5` | mass assignment | posting an `owner_id`/`status` field that is not allowed is ignored, not applied |
| `TM-<MOD>-A6` | route-list audit | every route in the module appears in the authorization coverage manifest (`tests/Feature/Security/RouteCoverageTest`) |

### 3.4 State machine (`S`)

| ID pattern | Case | Assertion |
|---|---|---|
| `TM-<MOD>-S1` | every legal transition | table-driven: one test per edge, from-state → to-state, actor, side effects |
| `TM-<MOD>-S2` | every illegal transition | table-driven rejection for all non-edges; state unchanged; 422/409 |
| `TM-<MOD>-S3` | terminal states | a terminal state rejects all commands |
| `TM-<MOD>-S4` | side-effect atomicity | transition + stock + ledger + audit commit together or not at all |
| `TM-<MOD>-S5` | event emission | exactly one domain event per transition, with the right payload |

### 3.5 Idempotency (`I`)

| ID pattern | Case | Assertion |
|---|---|---|
| `TM-<MOD>-I1` | replay ×5 | one effect, five identical responses |
| `TM-<MOD>-I2` | parallel replay | no deadlock, no duplicate row, all responses 2xx |
| `TM-<MOD>-I3` | different payload, same key | rejected with 409, first result preserved |
| `TM-<MOD>-I4` | key expiry | after 24 h the key is reusable and the old response is gone |

---

## 4. P0 gates

A release is blocked if any of these is red. Merge is blocked by the `P0-merge` subset.

| Gate | Contents | Blocks |
|---|---|---|
| **P0-merge** | all `U` in P0 modules; all `A1–A6`; `$1`,`$4`,`$5`; `Pt1`,`Pt2`; `S1`,`S2`; `I1`,`I2`; the whole exploit suite; the route-coverage manifest | merge |
| **P0-release** | P0-merge + all `B` journeys + all `L` scripts within NFR-PERF + 0 high ZAP findings | release |
| **P0-go-live** | P0-release + 100 % of P0 UAT scenarios + backup restore drill (`AC-24`) | go-live |

The 16 cases below are the ones most likely to be skipped and most expensive when wrong; they are called
out individually:

| Case | What it protects |
|---|---|
| `TM-PAY-I1` / `TM-PAY-I2` | duplicate gateway callback ⇒ one ledger entry (AC-05) |
| `TM-PAY-$6` | two parallel callbacks ⇒ no lost update |
| `TM-ORD-S2` | an order cannot jump from `awaiting_payment` to `delivered` |
| `TM-ORD-A3` | supplier B cannot read supplier A's sub-order |
| `TM-STK-$6` | the last unit of stock is sold once (NFR-SCALE-005) |
| `TM-PRI-$2` | tier price resolution rounds identically in cart, invoice and report |
| `TM-LOY-Pt2` | rule change does not rewrite history |
| `TM-LOY-$5` | a return reverses points by compensation, never by editing |
| `TM-FIN-$4` | derived balance equals cached projection across 1,000 orders |
| `TM-FIN-A3` | a marketer cannot reach the settlement approval route |
| `TM-MKT-A3` | marketer cannot read another marketer's leads |
| `TM-CMP-I1` | commission accrual is not duplicated by an event replay |
| `TM-ADM-A2` | admin routes require the admin guard **and** a 2FA-verified session |
| `TM-ADM-$1` | audit rows are append-only (DB permission asserted) |
| `TM-RPT-$1` | Excel export totals equal ledger totals |
| `TM-IAM-A5` | a user cannot post `role=admin` on the profile form |

---

## 5. Per-module must-have cases

### 5.1 Shared kernel — `shr_`

| ID | Type | Case | P0 |
|---|---|---|---|
| TM-SHR-001 | U | `Money` add/sub/mul-by-basis-points, half-up rounding at `.5`, overflow at `BIGINT` limit | yes |
| TM-SHR-002 | U | `Money` rejects float input and non-Toman units | yes |
| TM-SHR-003 | U | Jalali conversion round-trip over 1,000 random instants in 1400–1410 | yes |
| TM-SHR-004 | U | Month lengths incl. Esfand 29/30; year boundary 1404→1405 | yes |
| TM-SHR-005 | U | Tehran day cut-off at 23:59 local = 20:29 UTC | yes |
| TM-SHR-006 | U | No DST transition in 1404–1410 (AD-16 guard) | yes |
| TM-SHR-007 | U | Persian digit normalisation, national-ID check digit, mobile E.164, IBAN checksum | yes |
| TM-SHR-008 | F | Audit trait writes actor/target/diff/IP/UA and masks PII in the diff | yes |
| TM-SHR-009 | F | `audit:verify` detects a tampered row by ID | yes |
| TM-SHR-010 | E | log processor redacts national ID, mobile, IBAN, OTP, bearer tokens | yes |

### 5.2 Identity — `iam_`

| ID | Type | Case | P0 |
|---|---|---|---|
| TM-IAM-001 | F | register with Persian digits normalises mobile, stores Argon2id hash | yes |
| TM-IAM-002 | F | no session before OTP verification; account `pending_verification` | yes |
| TM-IAM-003 | F | OTP wrong 5× invalidates the code; resend resets the attempt counter | yes |
| TM-IAM-004 | F | OTP request 4 in 5 min rejected; 24 h per-mobile cap enforced | yes |
| TM-IAM-005 | F | login lockout after 10 failures, 15 min, CAPTCHA after 3 | yes |
| TM-IAM-006 | F | 2FA enrol/verify/replay-reject; ±1 step window | yes |
| TM-IAM-007 | F | session invalidated on password change and on role change | yes |
| TM-IAM-008 | F | admin guard isolated from web guard; cross-guard access 403 | yes |
| TM-IAM-009 | F | password reset token single-use, 60 min expiry, invalidated on use | yes |
| TM-IAM-010 | E | session fixation: ID regenerates at login; old ID rejected | yes |
| TM-IAM-011 | E | CSRF token missing on POST /profile ⇒ 419 | yes |
| TM-IAM-012 | E | enumeration: login with unknown mobile returns the same body/timing class as wrong password | yes |
| TM-IAM-013 | E | mass assignment of `role`, `status`, `referred_by` ignored | yes |
| TM-IAM-014 | B | register → OTP → dashboard (journey 1) | release |
| TM-IAM-015 | B | admin 2FA enrolment and forced gate (journey 8, first half) | release |

### 5.3 Merchants — `mer_`

| ID | Type | Case | P0 |
|---|---|---|---|
| TM-MER-001 | F | marketer-registered store carries `referred_by`, `attribution_source=qr` | yes |
| TM-MER-002 | F | duplicate national ID / postal code / mobile rejected before insert, no partial row | yes |
| TM-MER-003 | F | edit window: allowed at 4:59, rejected at 5:01, admin path required afterwards | yes |
| TM-MER-004 | F | approval generates credentials, forces first-login password change | yes |
| TM-MER-005 | F | identity documents land on the private disk only; public URL 404 | yes |
| TM-MER-006 | A | marketer A cannot read/approve marketer B's store (403/404 asserted) | yes |
| TM-MER-007 | A | store cannot change its own `status` or `commission_plan_id` | yes |
| TM-MER-008 | S | state machine `draft → pending_approval → approved` or `rejected → suspended`; illegal edges rejected | yes |
| TM-MER-009 | F | inactivity downgrade after the configured window; reactivation keeps history | no |
| TM-MER-010 | E | upload of `.php` renamed `.jpg` rejected by magic bytes, nothing written | yes |
| TM-MER-011 | E | 6 MB identity document rejected (limit 5 MB image / 20 MB document) | yes |

### 5.4 Catalog — `cat_`

| ID | Type | Case | P0 |
|---|---|---|---|
| TM-CAT-001 | F | publish requires mandatory attributes + ≥ 1 image; draft otherwise | yes |
| TM-CAT-002 | F | slug generation, uniqueness, 301 on slug change, 410 after 90 days | no |
| TM-CAT-003 | F | JSON-LD `Product`/`Offer`/`AggregateRating` valid; meta description generated 120–160 chars | yes |
| TM-CAT-004 | F | search index updated ≤ 60 s after create/update/price change | yes |
| TM-CAT-005 | F | sitemap excludes draft/out-of-stock, ≤ 50,000 URLs per file | no |
| TM-CAT-006 | F | comparison accepts 5, rejects 6, compares on identical parameter rows | no |
| TM-CAT-007 | A | supplier cannot edit another supplier's product (403) | yes |
| TM-CAT-008 | E | stored XSS in product description renders escaped on PDP and in admin list | yes |
| TM-CAT-009 | L | category page p95 ≤ 800 ms at 60 k SKUs, 20 results (NFR-PERF-001) | release |
| TM-CAT-010 | L | search p95 ≤ 600 ms / type-ahead ≤ 250 ms (NFR-PERF-003) | release |
| TM-CAT-011 | F | import dry-run writes 0 rows; real import creates 40/60/500; re-run creates 0 duplicates | release |

### 5.5 Pricing — `pri_`

| ID | Type | Case | P0 |
|---|---|---|---|
| TM-PRI-001 | U | resolution order base → role/tier → volume → promotion → coupon → loyalty, proven by recorded steps | yes |
| TM-PRI-002 | U | tier price selection per role, incl. secondary role context | yes |
| TM-PRI-003 | U | volume break applied at exactly the boundary quantity (99 vs 100) | yes |
| TM-PRI-004 | U | rounding half-up once per line, then sum (M2) | yes |
| TM-PRI-005 | F | price snapshot on the order line survives a later price change | yes |
| TM-PRI-006 | F | consumer price never leaks the store tier price in HTML/JSON | yes |
| TM-PRI-007 | A | store cannot request another role's price list via a query parameter | yes |
| TM-PRI-008 | E | tampered client-side price/quantity ignored; server recomputes | yes |
| TM-PRI-009 | F | price change writes audit row with before/after | yes |

### 5.6 Inventory — `stk_`

| ID | Type | Case | P0 |
|---|---|---|---|
| TM-STK-001 | F | reservation on checkout, release on expire/cancel | yes |
| TM-STK-002 | F | 50 concurrent checkouts of the last unit ⇒ 1 success, 49 clean failures, 0 negative stock | yes |
| TM-STK-003 | F | `lockForUpdate` proven by a parallel transaction test | yes |
| TM-STK-004 | F | oversell prevented across warehouses; stock per warehouse summed correctly | yes |
| TM-STK-005 | F | restock on return only for returnable items; write-off path recorded | no |
| TM-STK-006 | L | checkout with 20-line carts keeps p95 ≤ 900 ms (NFR-PERF-006) | release |

### 5.7 Cart — `crt_`

| ID | Type | Case | P0 |
|---|---|---|---|
| TM-CRT-001 | U | totals recomputed from lines, never trusted from the client | yes |
| TM-CRT-002 | F | coupon once per user, cap enforced, usage counter increments in the same transaction | yes |
| TM-CRT-003 | F | cart merged on login without duplicating lines | no |
| TM-CRT-004 | F | shipping option change recalculates the total | no |
| TM-CRT-005 | A | cart of user A not readable by user B (session + DB scoped) | yes |
| TM-CRT-006 | E | negative quantity / negative amount rejected, no order created | yes |

### 5.8 Orders — `ord_`

| ID | Type | Case | P0 |
|---|---|---|---|
| TM-ORD-001 | F | parent + N sub-orders, parent total = sum, integer equality | yes |
| TM-ORD-002 | F | one invoice per sub-order, sequential numbering, no gaps under concurrency | yes |
| TM-ORD-003 | S | every legal transition, one test per edge (table-driven) | yes |
| TM-ORD-004 | S | every illegal transition rejected, state unchanged (table-driven) | yes |
| TM-ORD-005 | S | transition + stock + ledger + audit atomic; forced exception rolls all back | yes |
| TM-ORD-006 | A | supplier sees only own sub-orders; consumer sees all of own | yes |
| TM-ORD-007 | A | store enumeration of order IDs ⇒ ≤ 2 response codes, 0 records | yes |
| TM-ORD-008 | I | double-click checkout ⇒ 1 order | yes |
| TM-ORD-009 | F | abandoned order expires after 48 h, stock released, appears in admin report | no |
| TM-ORD-010 | F | cancel one sub-order ⇒ parent `partially_cancelled`, others unchanged | yes |
| TM-ORD-011 | B | multi-vendor order per-seller status (journey 5) | release |

### 5.9 Payments — `pay_`

| ID | Type | Case | P0 |
|---|---|---|---|
| TM-PAY-001 | F | success callback ⇒ one verified transaction, one ledger entry, order `paid` | yes |
| TM-PAY-002 | I | 5 sequential replays ⇒ one effect, identical responses | yes |
| TM-PAY-003 | I | 5 parallel replays ⇒ one effect, no deadlock | yes |
| TM-PAY-004 | F | wrong amount ⇒ `amount_mismatch`, order unchanged, alert raised, no ledger | yes |
| TM-PAY-005 | F | forged signature ⇒ 400 generic, audited, no state change | yes |
| TM-PAY-006 | F | failed attempt is retryable; a later success yields exactly one paid transaction | yes |
| TM-PAY-007 | F | gateway down ⇒ remaining active gateways offered, no raw exception | yes |
| TM-PAY-008 | F | exactly one active gateway per payment in v1; two active is a configuration error surfaced in admin | yes |
| TM-PAY-009 | F | driver contract test class reusable across all 12 drivers (fake in CI, sandbox nightly) | yes |
| TM-PAY-010 | E | callback endpoint rejects unsigned/unknown `driver`, no 500 leak | yes |
| TM-PAY-011 | E | callback with a valid body but a foreign `authority` cannot mark another order paid | yes |
| TM-PAY-012 | L | callback p95 ≤ 400 ms (NFR-PERF-007) | release |

### 5.10 Finance — `fin_`

| ID | Type | Case | P0 |
|---|---|---|---|
| TM-FIN-001 | U | ledger entry invariants: balanced pair, immutable, one per reference | yes |
| TM-FIN-002 | U | derived balance == cached projection over 1,000 orders | yes |
| TM-FIN-003 | F | no code path issues UPDATE/DELETE on the ledger (query-log assertion) | yes |
| TM-FIN-004 | F | settlement batch excludes frozen/disputed sub-orders | yes |
| TM-FIN-005 | F | settlement approval moves entries to `settled` once; re-approval impossible | yes |
| TM-FIN-006 | A | marketer cannot reach settlement approval; admin requires 2FA session | yes |
| TM-FIN-007 | F | period boundaries in Jalali: 1st 00:00:00 to last day 23:59:59 Tehran | yes |
| TM-FIN-008 | F | reconciliation view lists failed/reversed/mismatched gateway transactions | release |
| TM-FIN-009 | F | negative wallet impossible; carry-over recorded | yes |

### 5.11 Marketing — `mkt_`

| ID | Type | Case | P0 |
|---|---|---|---|
| TM-MKT-001 | F | QR token attributes exactly one store; reuse for a second store rejected | yes |
| TM-MKT-002 | F | self-referral (same national ID / mobile / IP + fingerprint) awards nothing, flags fraud | yes |
| TM-MKT-003 | A | marketer sees own network only — 0 rows of another marketer in every list endpoint | yes |
| TM-MKT-004 | A | marketer has no export route (`route:list` assertion) | yes |
| TM-MKT-005 | A | API token of marketer A rejected (403) for marketer B's resources | yes |
| TM-MKT-006 | E | sequential ID scan over 100 store IDs leaks 0 records | yes |
| TM-MKT-007 | F | lead reassignment by admin is audited; original marketer loses access immediately | yes |
| TM-MKT-008 | F | revisit list shows stores inactive per admin window | no |
| TM-MKT-009 | E | geo endpoint rejects a spoofed timestamp/position outside an active visit session | yes |

### 5.12 Compensation — `cmp_`

| ID | Type | Case | P0 |
|---|---|---|---|
| TM-CMP-001 | U | plan A/B/C/D: fixed fee, percentage, hybrid, salary — each computed per its rule | yes |
| TM-CMP-002 | U | percentage base = first-invoice goods value, excluding shipping and tax | yes |
| TM-CMP-003 | F | visit fee accrues once per approved store, never twice | yes |
| TM-CMP-004 | F | plan condition (points/activity) blocks accrual with a visible reason | yes |
| TM-CMP-005 | I | replaying the `InvoicePaid` event creates no second entry | yes |
| TM-CMP-006 | F | settlement requires minimum payout and no open dispute | yes |
| TM-CMP-007 | F | plan version recorded on the entry; later plan edit does not move history | yes |
| TM-CMP-008 | A | marketer cannot read or trigger another marketer's settlement | yes |

### 5.13 Loyalty — `loy_`

| ID | Type | Case | P0 |
|---|---|---|---|
| TM-LOY-001 | U | coefficient 0.75/0.5/0.25/0.2 % and the wholesale set 0.2/0.1 % applied per line, floored | yes |
| TM-LOY-002 | U | star derivation `floor(points/100)`, unlimited stars | yes |
| TM-LOY-003 | U | reward: full deduction iff invoice ≥ 4 × reward, else exactly half | yes |
| TM-LOY-004 | U | tier discount 1–100 ⇒ 4 %, 100–200 ⇒ 5 %, > 200 ⇒ fixed Toman | yes |
| TM-LOY-005 | F | replay command over 500 invoices reproduces the live total exactly | yes |
| TM-LOY-006 | F | inactivity decay once per month, not per day; never negative | yes |
| TM-LOY-007 | F | return reverses points by compensating entry; original entry untouched | yes |
| TM-LOY-008 | F | rule-set versioning: `effective_from`, historical entries pinned | yes |
| TM-LOY-009 | A | a consumer cannot read another's points balance/history | yes |
| TM-LOY-010 | F | referral points once per verified store; self-referral blocked | yes |

### 5.14 Returns — `rtn_`

| ID | Type | Case | P0 |
|---|---|---|---|
| TM-RTN-001 | S | `requested → rejected → escalated → approved → refunded` with audit per transition | yes |
| TM-RTN-002 | F | window enforcement: day 7 23:59 allowed, day 8 00:00 rejected | yes |
| TM-RTN-003 | F | refund to wallet immediate ledger pair; refund to Sheba `pending → paid` by admin | yes |
| TM-RTN-004 | F | credit note references the original invoice; original never mutated | yes |
| TM-RTN-005 | F | restock only for returnable items; write-off reason required otherwise | yes |
| TM-RTN-006 | I | double approval ⇒ one refund | yes |
| TM-RTN-007 | A | supplier cannot refund an order it does not own | yes |

### 5.15 Admin — `adm_`

| ID | Type | Case | P0 |
|---|---|---|---|
| TM-ADM-001 | F | admin path unreachable without 2FA-verified session | yes |
| TM-ADM-002 | F | 30-minute admin idle timeout vs 120-minute consumer timeout | yes |
| TM-ADM-003 | F | 2FA reset requires second-admin approval and is audited | yes |
| TM-ADM-004 | F | every sensitive event in NFR §8.1 produces exactly one audit row with masked PII | yes |
| TM-ADM-005 | E | app DB user cannot UPDATE/DELETE audit rows (permission error asserted) | yes |
| TM-ADM-006 | F | role change and secondary-role enablement audited, effective immediately | yes |
| TM-ADM-007 | F | impersonation ("login as") audited and visibly indicated in the UI | no |
| TM-ADM-008 | E | CSRF on admin bulk actions; cross-site POST rejected | yes |

### 5.16 Reporting — `rpt_`

| ID | Type | Case | P0 |
|---|---|---|---|
| TM-RPT-001 | F | on-screen total == ledger sum == Excel total for 1,000 orders (VT-12) | yes |
| TM-RPT-002 | F | Excel export escapes `=`, `+`, `-`, `@` (formula injection) | yes |
| TM-RPT-003 | F | export writes an audit row with filters, row count, actor | yes |
| TM-RPT-004 | F | 20,000-row export queued, link + email delivered, request ≤ 300 ms | release |
| TM-RPT-005 | F | bank batch refuses an invalid IBAN and matches the settlement screen row-for-row | release |
| TM-RPT-006 | A | report routes require admin guard + 2FA; marketer/store 403 | yes |
| TM-RPT-007 | L | 30-day report p95 ≤ 5 s (NFR-PERF-008) | release |

### 5.17 Remaining modules — compact must-haves

| Module | Must-have cases |
|---|---|
| **Users** `usr_` | profile edit with SMS confirmation (`A5` against `role`), address CRUD scoped to owner, favourites scoped, activity list scoped, deactivation blocks login |
| **Invoices** `inv_` | numbering under concurrency, PDF content matches snapshot, credit note chain, invoice visible only to buyer/seller/admin |
| **Promotions** `prm_` | coupon/banner validity window in Jalali, per-user limit, stacking rules, admin deactivation is immediate |
| **Shipping** `shp_` | carrier/cost rules, waybill uniqueness, public tracking requires order code + mobile last-4, delay compensation trigger fires once |
| **Tenders** `tnd_` | open/close window, bid visibility (no bids before close), participant scoping |
| **Classifieds** `cls_` | posting blocked without an admin category, moderation queue, report hides ad, owner-only edit |
| **Patogh** `ptg_` | service profile approval gate, contact visibility flag, cost-request form validation |
| **Samples** `smp_` | request per store/supplier pair, status transitions, attachment limits |
| **Messaging** `msg_` | thread scope (`A3`), attachment magic-byte validation, read state, ticket state machine, 25 MB rejected |
| **Notifications** `ntf_` | fan-out 10,000 recipients ≤ 15 min, dedupe on recipient+campaign, failure isolation, audience frozen at approval |
| **Cms** `cms_` | page/banner/menu CRUD, XSS in rich text sanitised, noindex honoured on static pages |
| **Reviews** `rvw_` | review window 7 days after delivery, one review per order line, 5–10 parameters, moderation, aggregate rating recompute |
| **FieldOps** `geo_` | consent required before capture, capture only in an active visit session, position delta poll, retention job deletes > 90 days, spoofed timestamp rejected |
| **Admin** | see 5.15; plus gateway activation, maintenance mode, bulk notification send |
| **Reporting** | see 5.16 |

---

### 5.18 Security, privacy and operations cases

These are the proving tests named by `../security/security-checklist.md`. They live in
`tests/Feature/Security/` and run in the exploit group (G5); the backup cases run in the operations drill
(G14).

| ID | Type | Case | P0 |
|---|---|---|---|
| TM-IAM-016 | E | logged-out session cookie replayed ⇒ redirect to login, 0 data | yes |
| TM-IAM-017 | F | login success/failure/lockout rows carry IP, UA, timestamp, outcome | yes |
| TM-CAT-012 | E | SQLi payloads in every catalog filter/sort ⇒ 0 SQL errors, 200 with empty results, row counts unchanged | yes |
| TM-CRT-007 | E | 11th checkout attempt in a minute ⇒ 429, no order row | yes |
| TM-MSG-006 | E | 21st message in a minute ⇒ 429, no message row | yes |
| TM-PAY-013 | E | callback flood above 300/min/driver ⇒ 429, no partial state | yes |
| TM-PAY-014 | F | sandbox driver bound in `production` ⇒ boot-time exception | yes |
| TM-PAY-015 | F | callback raw body/headers/IP retained encrypted for reconciliation | yes |
| TM-RTN-008 | E | refund to an IBAN other than the original payer ⇒ 422 | yes |
| TM-ADM-009 | E | PII reveal above 30/h ⇒ 429; above 500/day ⇒ account lock + alert | yes |
| TM-SSRF-001 | E | image-by-URL pointing at `169.254.169.254` ⇒ rejected before any HTTP call (0 requests faked) | yes |
| TM-SSRF-002 | E | 15 hostile URLs (localhost, 127.0.0.1, 10/8, 172.16/12, 192.168/16, `file://`, `gopher://`, decimal IP, private-range redirect) ⇒ all rejected | yes |
| TM-SSRF-003 | E | import redirect to an internal host ⇒ rejected | yes |
| TM-SSRF-004 | E | outbound call to a 10 s endpoint aborts at the 5 s timeout | no |
| TM-PRV-001 | F | national ID / IBAN columns are ciphertext at rest; duplicate detection still works via the HMAC blind index | yes |
| TM-PRV-002 | E | full national ID never appears in any list HTML/JSON for any role | yes |
| TM-OPS-001 | F | boot with `APP_DEBUG=true` in production ⇒ guard exception | yes |
| TM-OPS-002 | E | forced 500 ⇒ no `Exception`, file path or SQL in the body; correlation ID present | yes |
| TM-BKP-001 | Ops | backup dump is not readable as plain SQL without the key | go-live |
| TM-BKP-002 | Ops | point-in-time restore to −15 min excludes the known transaction, ≤ 1 h (NFR-DR-003) | go-live |

---

## 6. Traceability: acceptance scenario → matrix rows

| Scenario | Primary matrix rows |
|---|---|
| AC-01 register+SMS | TM-IAM-001..003, TM-IAM-014 |
| AC-02 store approval | TM-MER-001..006, TM-MKT-001, TM-IAM-006 |
| AC-03 product+price | TM-CAT-001, TM-CAT-003, TM-PRI-002..005 |
| AC-04 checkout | TM-CRT-001..002, TM-PRI-001, TM-ORD-001, TM-ORD-008 |
| AC-05 payment+duplicate | TM-PAY-001..003, TM-PAY-009..011, TM-FIN-001 |
| AC-06 failed payment | TM-PAY-006, TM-ORD-009, TM-STK-001 |
| AC-07 order split | TM-ORD-001..005, TM-ORD-011 |
| AC-08 shipment | TM-SHP (shipping row), TM-CMP-002, TM-LOY-001 |
| AC-09 complaint freeze | TM-RTN-001, TM-FIN-004, TM-ORD-003 |
| AC-10 return+refund | TM-RTN-002..007, TM-LOY-007 |
| AC-11 commission | TM-CMP-001..007 |
| AC-12 points+reward | TM-LOY-001..008 |
| AC-13 referral+decay | TM-LOY-006, TM-LOY-010, TM-MER-009 |
| AC-14 IDOR | TM-<MOD>-A1..A6 (all), TM-MKT-003..006, TM-ADM-001 |
| AC-15 messaging+ticket | TM-MSG rows, TM-IAM-011 |
| AC-16 report+export | TM-RPT-001..007, TM-FIN-008 |
| AC-17 audit coverage | TM-ADM-004..005, TM-SHR-008..009 |
| AC-18 admin 2FA | TM-IAM-006, TM-IAM-008, TM-ADM-001..003 |
| AC-19 search+filters | TM-CAT-004, TM-CAT-006, TM-CAT-009..010 |
| AC-20 classifieds | TM-CLS rows |
| AC-21 import | TM-CAT-011 |
| AC-22 notifications | TM-NTF rows |
| AC-23 SEO/PWA/browsers | TM-CAT-003, TM-CAT-005 + NFR-COMPAT rows |
| AC-24 backup drill | `AC-24` (ops), NFR-DR rows |
| AC-25 brute force/validation | TM-IAM-004..005, TM-PRI-008, TM-CRT-006, TM-MER-010 |

---

## 7. Changelog

| Version | Date | Change |
|---|---|---|
| 1.0 | 2026-11-25 | Master matrix (28 modules + shared), 5 cross-cutting families, 16 named P0 cases, per-module must-haves, scenario traceability. |
