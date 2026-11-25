# Acceptance Criteria — Dekolife Platform

**Purpose.** Define the end-to-end scenarios that must pass before the client's acceptance test (UAT)
and before final delivery, in a form the client can execute without engineering help.
**Audience.** QA, engineering, the client's acceptance team (کارفرما), and the Orchestrator.
**Status.** Baseline v1 — the executable definition of "done" for the web product.

**Source:** contract `1405-01DK` (`../contract/contract-extracted-text.txt`) appendices 2–13, articles 4
and 12, appendix 10 (delivery/acceptance/training); NFR targets from
`./non-functional-requirements.md`; stack and module names from `../canonical-context.md`.

---

## 0. Conventions

### 0.1 Scenario IDs and traceability

- `AC-NN` — acceptance scenario. Stable; never renumbered. A withdrawn scenario is marked `Withdrawn`
  with the replacement ID.
- **Requirement references.** Functional requirements are cited as `REQ-<AREA>` **plus the exact
  contract clause**. The three-digit suffix is owned by the functional requirements register
  (`docs/requirements/`), so this document deliberately cites the area and clause instead of
  guessing a number and creating a dangling link. Non-functional references use the exact IDs owned by
  `./non-functional-requirements.md` (`NFR-*`, `REQ-CTR-2NN`).
- **Test type.** `Feature` (Pest feature test, MySQL), `E2E` (browser-driven, full stack, real HTTP),
  `Manual UAT` (executed by the client with a QA observer), `Ops` (operational drill). A scenario may
  carry more than one type; the strictest one gates.
- **Priority.** `P0` = blocks go-live; `P1` = blocks final delivery, may ship with a documented
  workaround; `P2` = cosmetic/edge, next release.

### 0.2 Execution environment

| Aspect | Requirement |
|---|---|
| Environment | Staging on the client's server, `APP_ENV=staging`, `APP_DEBUG=false`, production-like data volume (≥ 25,000 SKUs, ≥ 1,200 stores) |
| Database | MySQL 8.4, same version as production; seeded via factories/seeders, never a production dump |
| Gateways | Sandbox credentials only; no real money moves during UAT |
| SMS | Sandbox/stub provider with a readable outbox; OTP visible to the tester only |
| Time | Clock can be moved forward with a documented test-only command (`time travel`); every scenario states whether it needs it |
| Evidence | Screenshot per step-group, network log for payment scenarios, SQL assertion output for money/points scenarios, exported Excel file attached to the scenario sheet |

### 0.3 Exit criteria for UAT

1. 100 % of **P0** scenarios pass on the delivered build.
2. ≥ 90 % of **P1** scenarios pass; the remainder have a written workaround accepted by the client and
   a defect ID.
3. 0 open **P1** defects (severity definitions in `./non-functional-requirements.md` §11.2).
4. ≤ 5 open **P3/P4** defects, each with a scheduled release.
5. Defect log closed within **3 working days** of each test round (پیوست ۱۰ بند ۲, `REQ-CTR-215`).
6. Signed acceptance sheet per scenario, plus the aggregate sign-off letter required by پیوست ۱۰ بند ۴.

---

## AC-01 — Consumer registration with SMS verification

| Field | Value |
|---|---|
| Priority | P0 |
| Test type | E2E + Manual UAT |
| Requirements | `REQ-USR` (پیوست ۲ بند ۸ — ثبت‌نام با تأیید پیامک), `REQ-IAM` (پیوست ۳-۳ بند ۱); NFR-SEC-004, NFR-L10N-006 |
| Preconditions | Staging reachable; SMS stub outbox readable; mobile `09121234567` not registered |

**Steps**
1. Open `/register`, choose "مصرف‌کننده", enter name, mobile `۰۹۱۲۱۲۳۴۵۶۷` (Persian digits) and password `Dekolife!1404`.
2. Submit. Read the 6-digit code from the SMS outbox.
3. Enter the code on the verification screen.
4. Log out, log in again with mobile + password.
5. Repeat registration with the same mobile.

**Expected result**
- Persian digits are normalised to Latin before validation; the stored mobile is `+989121234567`.
- No user row exists with an active session before the code is verified; the account is `pending_verification`.
- Wrong code 3 times ⇒ inline Persian error and the code is invalidated after 5 attempts within 10 minutes.
- Successful verification creates the user, marks the mobile verified, logs the user in, and writes an audit row `auth.register`.
- Step 5 returns a validation error "این شماره قبلاً ثبت شده است" and sends nothing: no duplicate row, no second SMS.
- Password is stored as Argon2id (assert the hash prefix in the DB), never in logs.

---

## AC-02 — Marketer onboards a store by QR and admin approves it

| Field | Value |
|---|---|
| Priority | P0 |
| Test type | E2E + Manual UAT |
| Requirements | `REQ-MKT` (پیوست ۵ بند ۱–۲, پیوست ۶ بند ۱–۲ و ۸–۹), `REQ-MER` (پیوست ۳-۱ بند ۵); NFR-SEC-006, NFR-PRIV-005 |
| Preconditions | Marketer `m1` approved and active with QR token; admin logged in on the admin path with 2FA; national ID `0013542419` unused |

**Steps**
1. As `m1`, open the panel, copy the QR image and the referral link `/join/{token}`.
2. Open the referral link in a fresh browser (no session). Confirm the landing page shows the marketer's display name.
3. Submit the dedicated store form: store name, owner name, national ID, postal code, two phone numbers (one WhatsApp-enabled), services, category, province, city, storefront photo, owner photo, business-card photo, map location.
4. Attempt to submit the same national ID again in a second registration.
5. As `m1`, try to edit the submitted form after 6 minutes.
6. As admin, open "تأیید کاربران", review the store, approve it.
7. Check the credentials SMS and first login.

**Expected result**
- The store is created with `referred_by = m1`, `attribution_source = qr`, `status = pending_approval`; the QR token is single-purpose and cannot be reused to attribute a different store.
- Duplicate national ID, postal code or mobile is rejected **before** insert with a Persian message naming the conflicting field (پیوست ۵ بند ۳); no partial record is written.
- Step 5 is rejected server-side (5-minute window enforced in the policy, not only in the UI) and shows the "ویرایش فقط با تأیید ادمین" path.
- Photos land on the **private** disk; their URLs are signed and expire in 15 minutes; the storefront photo is re-encoded and stripped of EXIF GPS.
- Approval generates a username and a one-time password, sends them by SMS, and forces a password change at first login (پیوست ۶ بند ۸).
- Approval writes audit rows `merchant.approve` and `iam.password.reset` with the admin's ID.
- The store appears in `m1`'s network only; a second marketer `m2` cannot see it (see AC-14).

---

## AC-03 — Supplier creates a product with variants and role-based tier prices

| Field | Value |
|---|---|
| Priority | P0 |
| Test type | E2E + Feature |
| Requirements | `REQ-CAT` (پیوست ۷ بند ۴, پیوست ۱۱ بند ۱), `REQ-PRI` (پیوست ۳-۱ بند ۸, پیوست ۱۲ بند ۱); NFR-PERF-002, NFR-SEO-004 |
| Preconditions | Supplier `s1` approved; categories "پارکت" → "لمینت" exist; consumer price multiplier 35 % configured |

**Steps**
1. As `s1`, create product "پارکت لمینت ۸ میلی‌متر", SKU `LMN-8-001`, with 3 images, 1 video, 6 attributes, short and long description, and stock 500 in warehouse `W1`.
2. Add tier prices: consumer 1,350,000 T, store 1,000,000 T, supplier-peer 950,000 T; volume break at 100 m² ⇒ −3 %.
3. Leave the meta description empty and save.
4. As a consumer, open the product page; as a store, open the same page.
5. Change the base price to 1,400,000 T as `s1`; check the price history and audit trail.

**Expected result**
- Product saved with status `draft`; publishing requires all mandatory attributes and at least one image (server-side rule).
- Price resolution is server-side: consumer sees 1,350,000 T, store sees 1,000,000 T, and neither price appears in the other's HTML payload.
- Quantity 100 m² for a store yields 970,000 T per unit (1,000,000 × 0.97) computed in integer Toman with the documented rounding rule, and the applied rules are snapshotted on the order line at checkout.
- A generated meta description of 120–160 characters is produced; JSON-LD `Product` + `Offer` with price, currency `IRT`, availability and `AggregateRating` is present and valid.
- The price change writes `pri.price.changed` with before/after, and existing order lines are unchanged (snapshot integrity).
- Product appears in search within 60 s (NFR-PERF-018) and in the sitemap after the next daily generation.

---

## AC-04 — Consumer checkout at consumer price with a pre-invoice

| Field | Value |
|---|---|
| Priority | P0 |
| Test type | E2E + Manual UAT |
| Requirements | `REQ-CRT` (پیوست ۲ بند ۶), `REQ-ORD` (پیوست ۱۲ بند ۵ و ۱۲), `REQ-INV` (پیوست ۵ بند ۴۲); NFR-PERF-006 |
| Preconditions | AC-03 product published, stock 500; consumer `c1` logged in with one saved address in Tehran |

**Steps**
1. Add 12 m² to the cart; open the cart; apply coupon `WELCOME10` (10 %, max 200,000 T).
2. Choose shipping "ارسال با پست" and then "تحویل در محل" to compare costs.
3. Open the pre-invoice (پیش‌فاکتور) screen.
4. Submit the order and choose the sandbox gateway.
5. Inspect the created order, sub-order, invoice and ledger rows.

**Expected result**
- Cart totals are recomputed server-side on every render; a tampered client-side price is ignored.
- Coupon applies once per user, is capped at 200,000 T, and its usage counter increments inside the same transaction as the order.
- Shipping cost changes the total and is shown separately; free-shipping rule for Tehran applies only when configured.
- Pre-invoice shows line prices, discount, shipping, tax (if configured), payable total, seller name and delivery estimate; it is printable and marked "پیش‌فاکتور — غیرقابل استناد برای تسویه".
- Order is created with `status = awaiting_payment`, one invoice with a sequential number, and **no** ledger debit before payment.
- Prices shown to the consumer never equal the store tier price; the order line stores `price_source = consumer_list`.
- Double-clicking submit produces exactly one order (idempotency key on the checkout POST).

---

## AC-05 — Sandbox payment success and duplicate callback

| Field | Value |
|---|---|
| Priority | P0 |
| Test type | Feature + E2E |
| Requirements | `REQ-PAY` (پیوست ۱ بند ۵, پیوست ۱۳ بند ۶–۷), `REQ-FIN` (پیوست ۱۳ بند ۵); NFR-PERF-007, NFR-SCALE-006 |
| Preconditions | AC-04 order in `awaiting_payment`; sandbox gateway `GW-TEST` active |

**Steps**
1. Redirect to the sandbox gateway, complete payment with the test card.
2. Let the gateway call `POST /payments/callback/{driver}`.
3. Replay the identical callback 4 more times (same authority/reference), in parallel.
4. Replay with a wrong amount, then with a forged signature.
5. Check the order, invoice, ledger and audit rows.

**Expected result**
- Exactly **one** `pay_transactions` row reaches `verified`, exactly one ledger credit and one order transition to `paid`; replays return the original result with HTTP 200 and change nothing (unique `reference` on the ledger).
- Parallel replays produce no deadlock and no duplicate row; the response is identical across calls (byte-comparable body except timestamps).
- Wrong amount ⇒ payment marked `amount_mismatch`, order stays `awaiting_payment`, alert raised, no ledger entry.
- Forged signature ⇒ HTTP 400 with a generic error, `pay.callback.rejected` audited with IP and driver, no state change.
- The callback handler responds in ≤ 400 ms p95 (NFR-PERF-007) and never renders HTML (JSON only).
- Audit trail contains `pay.verified` with gateway reference, amount and actor=`gateway`.

---

## AC-06 — Failed payment, retry, and abandoned-order cleanup

| Field | Value |
|---|---|
| Priority | P1 |
| Test type | Feature |
| Requirements | `REQ-PAY` (پیوست ۱۳ بند ۶), `REQ-ORD`; NFR-AVAIL-004 |
| Preconditions | Order in `awaiting_payment`; sandbox can force `failed` and `timeout` |

**Steps**
1. Pay with a card the sandbox rejects.
2. Retry the same order with a successful payment.
3. Create a second order, abandon it at the gateway for 48 h (time-travel).
4. Inspect reserved stock and the order status.

**Expected result**
- Failed attempt records a `failed` transaction, keeps the order payable, shows a Persian retry message, and does not decrement stock permanently.
- The second attempt succeeds and produces exactly one paid transaction; the failed attempt remains as history.
- After 48 h the abandoned order moves to `expired` by a scheduled job, releases reserved stock, and appears in the "سفارش‌های رهاشده" admin report.
- No ledger entry exists for any failed or expired order.

---

## AC-07 — Multi-vendor order split into sub-orders

| Field | Value |
|---|---|
| Priority | P0 |
| Test type | Feature + E2E |
| Requirements | `REQ-ORD` (پیوست ۱۱ بند ۱), `REQ-INV`, `REQ-SHP`; NFR-SCALE-004 |
| Preconditions | Cart with 3 lines from 3 different suppliers, one of them with 2 shipping options |

**Steps**
1. Checkout the mixed cart and pay successfully.
2. Open the consumer order page, then each supplier's panel.
3. Attempt to open supplier B's sub-order while logged in as supplier A.

**Expected result**
- One parent order + 3 sub-orders, each with its own status, shipment and invoice; parent total = sum of sub-order totals with no rounding drift (assert integer equality).
- Each supplier sees only its own sub-order and its own line items; the consumer sees all three with per-seller status.
- Step 3 returns 403 (policy), not 404-by-accident, and writes no data.
- Cancelling one sub-order does not change the other two; the parent order becomes `partially_cancelled` and the refund is scoped to that sub-order.
- Ledger entries are per sub-order, and the platform commission is computed per sub-order, not per parent.

---

## AC-08 — Shipment, tracking code, delivery, and delay compensation

| Field | Value |
|---|---|
| Priority | P0 |
| Test type | E2E + Manual UAT |
| Requirements | `REQ-SHP` (پیوست ۱۱ بند ۱۵, پیوست ۱۲ بند ۸), `REQ-CMP` (پیوست ۴ بند ۱۴); NFR-PRIV-003 |
| Preconditions | Paid sub-order with a promised ship date; delay compensation 2 % configured by admin |

**Steps**
1. As the supplier, register the shipment with carrier "پیک" and waybill `TRK-99881`.
2. As the consumer, open order tracking with the code `TRK-99881` (also without logging in, using order code + mobile).
3. Time-travel 3 days past the promised date without delivery; open the order.
4. Confirm delivery as the consumer.

**Expected result**
- Shipment row stores carrier, waybill, shipped_at (UTC) and renders in Jalali; consumer receives an SMS and an in-app notification.
- Public tracking requires order code **and** the last 4 digits of the mobile; a wrong pair returns a generic "یافت نشد" and never leaks the address.
- Delay compensation is computed automatically at the configured 2 % of the sub-order goods value, deducted from the supplier's payable and credited to the consumer's wallet with a ledger pair (debit supplier, credit wallet) — never a negative wallet balance.
- Compensation is idempotent: refreshing or re-running the job does not double-credit (assert one ledger pair).
- Delivery confirmation sets `delivered_at`, unlocks review for 7 days, and triggers the points calculation event.

---

## AC-09 — Complaint freezes order preparation and payment to the supplier

| Field | Value |
|---|---|
| Priority | P0 |
| Test type | Feature + E2E |
| Requirements | `REQ-RTN` (پیوست ۴ بند ۱۵–۱۶ و ۱۸), `REQ-MSG` (پیوست ۳-۱ بند ۹); NFR-SEC-010 |
| Preconditions | Paid sub-order, not yet settled; consumer within the 7-day post-shipment window |

**Steps**
1. As the consumer, press "توقف آماده‌سازی" and submit a complaint with text + photo.
2. Check the supplier's panel and the settlement queue.
3. As admin, resolve the complaint in the consumer's favour.
4. Repeat step 1 on a sub-order shipped 8 days ago.

**Expected result**
- Sub-order moves to `frozen`, settlement for that sub-order is blocked (assert it disappears from the settlement batch and `fin` shows a hold), supplier receives a notification, and the admin sees the case in a queue with SLA age.
- The freeze does not block other sub-orders of the same supplier.
- Admin resolution in the consumer's favour cancels the sub-order, creates a full refund to the wallet/Sheba, releases the hold and writes `rtn.complaint.resolved` with the admin ID and rationale.
- Step 4 is rejected: the window is enforced by policy with a Persian reason, and the button is hidden for out-of-window orders as a UX nicety only.
- Every state change is audited; the consumer cannot freeze twice the same sub-order (idempotent).

---

## AC-10 — Return request and refund

| Field | Value |
|---|---|
| Priority | P0 |
| Test type | Feature + E2E |
| Requirements | `REQ-RTN` (پیوست ۱۲ بند ۱۴), `REQ-PAY` (پیوست ۱۱ بند ۱۴); NFR-SEC-010 |
| Preconditions | Delivered sub-order within the configured return window; item marked returnable by admin |

**Steps**
1. Consumer submits a return request with reason and photos.
2. Supplier rejects it; consumer escalates to admin; admin approves.
3. Refund is issued to the wallet and, for a second order, to Sheba.
4. Check stock, ledger, invoice and points.

**Expected result**
- Return request has its own state machine (`requested → rejected → escalated → approved → refunded`) with an audit row per transition.
- Refund to wallet credits immediately with a ledger pair; refund to Sheba creates a `refund_payout` record in `pending` and requires admin approval to become `paid`.
- Restocked quantity returns to the original warehouse; a non-restockable flag keeps stock unchanged and records a write-off reason.
- Points awarded for the returned invoice are reversed by a compensating ledger entry (never by editing history); the points balance may not go below zero — the excess is recorded as a negative carry.
- Invoice gets a credit note referencing the original invoice number; the original is never mutated.

---

## AC-11 — Marketer commission accrual and settlement request

| Field | Value |
|---|---|
| Priority | P0 |
| Test type | Feature + E2E |
| Requirements | `REQ-CMP` (پیوست ۶ بند ۳ و ۵–۶), `REQ-FIN` (پیوست ۳-۲ بند ۲); NFR-PERF-011 |
| Preconditions | Marketer `m1` on plan `B` (fixed visit fee 150,000 T + 1.5 % of first-invoice value); two approved stores attributed to `m1`; one invoice paid |

**Steps**
1. Verify the commission accrual after the first paid invoice of a referred store.
2. Attempt a second accrual on the same invoice (replay the domain event).
3. Submit a settlement request from the marketer panel.
4. As admin, approve the settlement and export the payment batch.

**Expected result**
- Accrual = 150,000 T visit fee (once per approved store) + 1.5 % of the invoice goods value, computed in integer Toman, stored as a `cmp_commission_entries` row with the plan version used.
- Replaying the event creates no second entry (unique key on invoice + plan + type).
- A plan with the "requires points/activity" condition (پیوست ۶ بند ۶) blocks accrual when the condition is unmet, and the block reason is visible to the marketer.
- Settlement request validates a minimum payout (1,000,000 T) and an open-dispute check; the request appears in the admin queue.
- Admin approval moves entries to `settled`, writes a ledger debit against the platform payable account and produces a bank batch row; re-approval is impossible.
- Marketer sees only own entries; the panel exposes no other marketer's data (assert in AC-14 too).

---

## AC-12 — Points accrual, tier discount, and reward redemption on the next invoice

| Field | Value |
|---|---|
| Priority | P0 |
| Test type | Feature |
| Requirements | `REQ-LOY` (پیوست ۴ بند ۱–۴, ۷, ۹, ۲۰), `REQ-PRI`; NFR-PERF-011, NFR-L10N-004 |
| Preconditions | Consumer with 0 points; product coefficient 0.5 %; admin sets 4 points/month inactivity deduction; reward rule active |

**Steps**
1. Pay an invoice of 10,000,000 T with the 0.5 % coefficient.
2. Verify the points balance and the star count.
3. Place a second invoice of 30,000,000 T and apply the accrued reward.
4. Place a third invoice of 2,000,000 T with a reward larger than one quarter of the invoice.
5. Time-travel 31 days without a purchase.

**Expected result**
- Step 1 awards exactly 50,000 points (10,000,000 × 0.5 %, floored to integer) and the rule-set version is recorded on the entry; the replay command reproduces the same number.
- 100 points = 1 star, unlimited stars (پیوست ۴ بند ۹): 50,000 points ⇒ 500 stars, displayed on the profile.
- Tier discount is 4 % for 1–100 points and 5 % for 100–200 points on the next invoice; above 200 points the admin-configured fixed Toman discount applies; the applied discount is shown on the invoice and sent by SMS.
- Step 3: the reward is deducted **only** because the invoice (30,000,000) is ≥ 4 × the reward; otherwise exactly half is deducted (پیوست ۴ بند ۴). Assert both branches with fixture data.
- Step 4 exercises the half-deduction branch and the leftover is retained, not lost.
- Step 5 deducts 4 points per inactive month once — not 4 per day — and never drives the balance negative.
- All amounts are integer; assert `points_balance` equals the sum of `loy_point_entries` (no cached drift).

---

## AC-13 — Referral points, volume coefficients, and inactivity downgrade

| Field | Value |
|---|---|
| Priority | P1 |
| Test type | Feature |
| Requirements | `REQ-LOY` (پیوست ۴ بند ۳, ۱۲–۱۳, ۲۳), `REQ-MER` |
| Preconditions | Store `st1` with 120 points; referrer `st0`; volume bands configured |

**Steps**
1. Register a new store through `st0`'s referral link and approve it.
2. Create a volume invoice that already received a wholesale cash discount.
3. Time-travel 90 days with no invoice for `st1`.

**Expected result**
- Referral awards the configured points to `st0` once per verified store; a self-referral (same national ID, same mobile, same IP + same device fingerprint) awards nothing and raises a fraud flag.
- A volume invoice with wholesale discount uses the 0.2 / 0.1 % coefficients instead of 0.75 / 0.5 / 0.25 / 0.2 (پیوست ۴ بند ۱۲), and the coefficient set is snapshotted.
- After the inactivity window `st1` becomes `inactive`, is removed from active-store listings, and appears in the "فروشگاه‌های غیرفعال" report for revisits (پیوست ۴ بند ۲۳).
- Reactivation on a new paid invoice restores `active` and keeps the historical points.

---

## AC-14 — Scope and IDOR negative tests

| Field | Value |
|---|---|
| Priority | P0 |
| Test type | Feature (dedicated exploit suite) |
| Requirements | `REQ-ADM` (پیوست ۷ بند ۱), `REQ-MKT` (پیوست ۵ بند ۲۹, پیوست ۶ بند ۱۰), canonical §5 authorization; NFR-SEC-006, NFR-PRIV-006 |
| Preconditions | Two stores, two suppliers, two marketers, two consumers, one admin — all with known IDs |

**Steps**
1. As store A, request store B's order (`GET /panel/orders/{id}`), invoice PDF, and settlement statement by ID.
2. As marketer `m1`, request `m2`'s store profile, visit list and commission statement; also try the admin export route.
3. As consumer C1, request C2's address, wallet and ticket.
4. As a store, POST to an admin-only route with a valid CSRF token.
5. As a marketer, call the API with a token of another marketer (`Authorization: Bearer …`).
6. Enumerate IDs with a sequential scan over 100 order IDs.

**Expected result**
- Every cross-tenant request returns **403** (own-scope resources) or **404** (resources whose existence must not leak), never the record; responses are identical in shape to a non-existent ID.
- No route in the marketer panel returns another marketer's rows: assert empty result sets and 0 rows in the JSON for each endpoint.
- The marketer has no export route at all (`route:list` assertion) and the admin export requires the admin guard + 2FA session.
- API tokens are scoped: a token minted for `m1` returns 403 for `m2`'s resources; token abilities are checked server-side.
- 100-ID enumeration yields ≤ 2 distinct response codes and 0 records; rate limiting triggers at the configured threshold.
- The suite is a **P0 gate**: a single failure blocks merge (see `../testing/quality-gates.md`).

---

## AC-15 — Messaging and support ticket lifecycle

| Field | Value |
|---|---|
| Priority | P1 |
| Test type | E2E + Manual UAT |
| Requirements | `REQ-MSG` (پیوست ۵ بند ۲۸, پیوست ۳-۲ بند ۱, پیوست ۷ بند ۸), `REQ-NTF`; NFR-SEC-009, NFR-PERF-017 |
| Preconditions | Consumer and store both active; ticket categories seeded |

**Steps**
1. Consumer starts a chat with the store, sends text, an image and a 20 MB PDF.
2. Store replies; consumer sends a 25 MB file.
3. Consumer opens a support ticket to admin; admin replies; consumer replies.
4. Consumer tries to open another consumer's conversation by URL.

**Expected result**
- Messages are persisted with sender, timestamp (UTC) and read state; the thread survives logout and is searchable.
- The 25 MB upload is rejected with a Persian size message; the 20 MB PDF is accepted and stored on the private disk with a signed, expiring URL.
- Blocked extensions (`.php`, `.phtml`, `.svg` with script, `.exe`) are rejected by magic-byte check, not by extension alone.
- Ticket has states `open → answered → closed`, an SLA age counter, and notifies both sides; closing requires a resolution note.
- Step 4 returns 403/404 with no message content.
- Chat with a store the consumer has no order or inquiry with is either blocked by policy or explicitly allowed by config — the behaviour is asserted, not incidental.

---

## AC-16 — Financial report and Excel export reconcile with the ledger

| Field | Value |
|---|---|
| Priority | P0 |
| Test type | Feature + Manual UAT |
| Requirements | `REQ-RPT` (پیوست ۷ بند ۲ و ۱۰, پیوست ۱۳ بند ۱–۳ و ۵–۶); NFR-PERF-008, NFR-PERF-009, NFR-SEC-010 |
| Preconditions | 30 days of seeded transactions across 5 suppliers, including 3 failed and 2 refunded payments |

**Steps**
1. As admin, open the sales report for a Jalali date range (1–30 Mehr), filter by supplier and product type.
2. Export to Excel; open the file and compare totals with the on-screen figures and with a direct ledger query.
3. Filter failed/returned transactions and run the gateway reconciliation view.
4. Export the bank batch file for supplier settlement.

**Expected result**
- On-screen totals = ledger sums = Excel totals, to the Toman, with 0 drift (`VT-12`); the report shows gross, commission, shipping, refunds and net payable as separate columns.
- Excel export is generated asynchronously, delivered as a download link (and email), contains a header row with Jalali dates and integer amounts, and has no formula injection (`=`, `+`, `-`, `@` prefixed cells are escaped).
- The export writes an audit row `rpt.export` with the filter set, row count and admin ID.
- Reconciliation view lists failed, reversed and mismatched gateway transactions with their references and a one-click drill-down.
- Bank batch export matches the settlement screen row-for-row (IBAN, amount, beneficiary name), and the file is refused if any IBAN fails checksum.
- Export of 20,000 rows completes ≤ 90 s; the request itself returns ≤ 300 ms.

---

## AC-17 — Audit log completeness for sensitive events

| Field | Value |
|---|---|
| Priority | P0 |
| Test type | Feature |
| Requirements | `REQ-ADM` (پیوست ۱ بند ۱۰); `REQ-CTR-204`, NFR-SEC-010, NFR-OBS-006 |
| Preconditions | Fresh staging database |

**Steps**
1. Execute one instance of each event in `non-functional-requirements.md` §8.1: login, failed login, 2FA enrol, role change, price change, coupon creation, settlement approval, refund, complaint resolution, identity-data change, export, bulk notification, data import, impersonation.
2. Query the audit table by actor, target, date and IP.
3. Attempt to update and delete an audit row as the application DB user.

**Expected result**
- Exactly one audit row per event, with actor, actor role, target type/ID, before/after diff (JSON), IP, user agent, correlation ID and UTC timestamp.
- No sensitive value is stored in clear text inside the diff: national ID, IBAN and mobile appear masked (`******2419`).
- The DB user used by the app cannot `UPDATE` or `DELETE` on the audit table (permission error asserted); the hash chain validates with `php artisan audit:verify` and reports 0 broken links.
- Search by any of the four dimensions returns the row in ≤ 3 s for a 30-day window.
- A tampered row (simulated by a privileged fixture) is detected by `audit:verify` with the exact row ID.

---

## AC-18 — Admin isolation and mandatory 2FA

| Field | Value |
|---|---|
| Priority | P0 |
| Test type | Feature + E2E + Manual UAT |
| Requirements | `REQ-ADM` (پیوست ۱ بند ۷); `REQ-CTR-202`, NFR-SEC-002, NFR-SEC-017 |
| Preconditions | Admin account without 2FA enrolled; consumer account with an active session |

**Steps**
1. Open the admin path as an admin who has not enrolled 2FA.
2. Enrol TOTP with an authenticator app; log out; log in again.
3. Reuse the previous TOTP code for a second login within the same 30-second step.
4. As a consumer, request the admin path directly.
5. Attempt 6 failed admin logins from one IP.

**Expected result**
- Step 1 forces enrolment before any admin page renders; no admin screen is reachable without a verified second factor.
- Login requires password **and** TOTP; the code window is ±1 step; a reused code is rejected (`otp.replay` audited).
- Consumer session on the admin path gets 403 and, if the path is on a separate domain, no admin cookie is even set (cookie domain scoping asserted).
- 6 failed attempts trigger the lockout/CAPTCHA path (NFR-SEC-004) with a Persian message and an alert.
- The admin panel sets a 30-minute idle timeout, distinct from the 120-minute consumer timeout.
- All admin logins appear in the audit log with IP and user agent; 2FA reset requires a second admin's approval.

---

## AC-19 — Catalog search, filters, and 5-product comparison

| Field | Value |
|---|---|
| Priority | P1 |
| Test type | E2E + Manual UAT |
| Requirements | `REQ-CAT` (پیوست ۲ بند ۲–۵), `REQ-CTR-206` (پیوست ۹ بند ۱); NFR-PERF-003, NFR-SEO-007 |
| Preconditions | 25,000 seeded SKUs across 40 categories, 60 brands |

**Steps**
1. Search "پارکت لمینت" and apply filters price range, brand, in-stock, rating, has-discount; change sort.
2. Add 5 products to comparison; try a 6th.
3. Open page 2 of results, then reload the filtered URL.

**Expected result**
- Search returns ≤ 600 ms p95 with 20 results per page; filters are combinable and reflected in the URL (shareable, reload-stable).
- Result count updates correctly; zero-result state shows suggestions, not an empty page.
- Comparison accepts exactly 5 products and rejects the 6th with a Persian message, comparing on identical parameter rows.
- Filtered and paginated URLs carry `noindex` and canonicalise to the clean category URL (NFR-SEO-007).
- Search input is escaped in the results header (XSS attempt `<script>alert(1)</script>` renders as text).

---

## AC-20 — Classifieds (دیوار) posting gated by admin-defined category

| Field | Value |
|---|---|
| Priority | P2 |
| Test type | E2E |
| Requirements | `REQ-CLS` (پیوست ۵ بند ۴); NFR-SEO-003, NFR-SEC-009 |
| Preconditions | No classified category defined yet; store user active |

**Steps**
1. As a store, attempt to post an ad.
2. As admin, create category "ابزار و یراق" with one required attribute.
3. Post an ad with title, text, 3 images, price and the required attribute.
4. Search the ad by keyword; open it from a logged-out browser.

**Expected result**
- Step 1 blocks posting with "دسته‌بندی توسط مدیر تعریف نشده است" — no ad row is created, and the create form is not reachable.
- Ad requires the mandatory attribute; images are validated and re-encoded; the ad URL is `/ads/{slug}-{id}` and canonical.
- The ad is visible publicly with the poster's public profile (no mobile number unless the user enabled it), and the poster's identity fields are not exposed.
- Ad appears in search within 60 s; a reported ad (گزارش تخلف) creates a moderation queue item and hides it pending review.

---

## AC-21 — Base data import (Import Data) and idempotent re-run

| Field | Value |
|---|---|
| Priority | P1 |
| Test type | Feature + Ops |
| Requirements | `REQ-CAT` (پیوست ۹ بند ۴); `REQ-CTR-207`, NFR-MAINT-008 |
| Preconditions | Client-supplied sample file: 40 categories, 60 brands, 500 products, 3 images per product |

**Steps**
1. Run the import command in dry-run mode.
2. Run the real import.
3. Run the same import again unchanged.
4. Run with 5 intentionally broken rows (missing price, invalid category, duplicated SKU).
5. Verify catalog, images and search index.

**Expected result**
- Dry run reports counts per entity and 0 writes (assert row counts before/after).
- Real import creates 40/60/500 entities, queues image downloads, and finishes without blocking the request path.
- Re-run creates 0 duplicates and updates only changed rows (report shows `updated` counts, not `created`).
- Bad rows are rejected individually with a row-numbered error report; good rows still import (partial success, exit code non-zero when any row fails).
- Search finds the imported products within 60 s; every imported image has alt text (`"{brand} {name}"`).

---

## AC-22 — Paid bulk notification and targeted custom notification

| Field | Value |
|---|---|
| Priority | P2 |
| Test type | E2E + Feature |
| Requirements | `REQ-NTF` (پیوست ۵ بند ۹ و ۱۲), `REQ-PRM`; NFR-PERF-010 |
| Preconditions | Consumer with wallet balance; 2,000 seeded recipients; SMS stub |

**Steps**
1. As a consumer, buy a notification: 1,000 recipients, text, image, link; pay from wallet.
2. As admin, approve and send it.
3. As admin, send a custom notification with an audience filter (city = تهران, role = store).
4. Inspect delivery counts and failures.

**Expected result**
- Price = configured unit price × recipients, debited from the wallet only after successful payment; insufficient balance blocks submission with a Persian message.
- The purchase creates a `pending_review` campaign; sending is impossible before admin approval.
- Send is queued: 2,000 recipients accepted ≤ 2 s, all dispatched ≤ 15 min, per-recipient failure isolated, and a delivery report (sent/failed/skipped with reasons) is produced.
- Audience filter is applied server-side and the resolved recipient count is frozen at approval time (no drift between quote and send).
- Rate limits and provider caps are respected; a provider outage keeps the campaign retryable for ≤ 24 h without duplicate sends (dedupe on recipient + campaign).

---

## AC-23 — SEO, PWA, and browser/device matrix

| Field | Value |
|---|---|
| Priority | P1 |
| Test type | Manual UAT + automated (Lighthouse/GTmetrix) |
| Requirements | `REQ-CTR-201`, `REQ-CTR-206`; NFR-COMPAT-001..004, NFR-SEO-001..009, NFR-A11Y-001 |
| Preconditions | Staging published with a real domain and valid TLS certificate |

**Steps**
1. Run GTmetrix and Lighthouse (mobile) on home, one category, one product.
2. Validate structured data with a rich-results validator; fetch `/sitemap.xml` and `/robots.txt`.
3. Install the PWA on Android Chrome and on iOS Safari; go offline and reopen.
4. Execute the smoke script (search → product → cart → checkout) on Chrome, Edge, Firefox, Safari and one Android + one iOS device.
5. Run axe-core on 12 templates.

**Expected result**
- GTmetrix ≥ B on all three pages; Lighthouse SEO ≥ 95, Performance ≥ 80, Accessibility ≥ 90.
- Structured data: 0 errors; `Product`, `Offer`, `AggregateRating`, `BreadcrumbList`, `Organization` present.
- Sitemap index resolves, lists ≤ 50,000 URLs per file, and excludes draft/out-of-stock items; `robots.txt` blocks `/admin`, `/panel`, `/api`, `/checkout`.
- PWA installs, shows icon + splash, and serves the offline fallback; the app is not installable on the admin path.
- Smoke script passes with 0 blocking defects on all browsers in NFR-COMPAT-001/002; no horizontal scroll at 360 px.
- axe-core: 0 critical, 0 serious violations.
- TLS: certificate valid, HTTP redirects to HTTPS, HSTS header present, TLS 1.0/1.1 rejected.

---

## AC-24 — Backup and restore drill (operational acceptance)

| Field | Value |
|---|---|
| Priority | P0 |
| Test type | Ops (executed with the client's sysadmin watching) |
| Requirements | `REQ-CTR-203`; NFR-DR-001..011 |
| Preconditions | Nightly backup from the previous night; a clean host or scratch database |

**Steps**
1. Show the backup job log and the manifest (row counts + checksum) for last night.
2. Restore the full dump into a clean MySQL instance.
3. Compare row counts of 12 critical tables and the checksum manifest.
4. Perform a point-in-time restore of `fin_ledger_entries` to 15 minutes before a known test transaction.
5. Time both operations.

**Expected result**
- Backup exists for the last 24 h, is encrypted (restore fails without the key), and the manifest matches.
- Full restore completes ≤ 4 h (RTO) and matches row counts exactly for all 12 tables; the app boots against the restored DB and serves a product page.
- Point-in-time restore to −15 min excludes the known transaction and completes ≤ 1 h (RPO ≤ 15 min proven).
- The runbook is followed by the client's sysadmin without engineering help; deviations are written into the runbook before sign-off.

---

## AC-25 — Brute force, captcha, and input-validation negatives

| Field | Value |
|---|---|
| Priority | P0 |
| Test type | Feature (exploit suite) + Manual UAT |
| Requirements | `REQ-CTR-205`; NFR-SEC-004, NFR-SEC-007, NFR-SEC-008, NFR-SEC-009 |
| Preconditions | Fresh IP; CAPTCHA provider in test mode |

**Steps**
1. Attempt 10 logins with wrong passwords for one account from one IP.
2. Request OTP 4 times in 5 minutes for one mobile.
3. Submit the store registration form with `' OR 1=1 --` in the store-name field and a `<script>` payload in the description.
4. Upload a `.php` file renamed to `.jpg` as a product image.
5. Submit an order with a negative quantity and with `amount=-1`.

**Expected result**
- Lockout after 10 failures (15 min), CAPTCHA challenge after 3 failures, and an alert entry; the account is not permanently locked and can recover via password reset.
- OTP request 4 in 5 min is rejected with a Persian message and the 24 h per-mobile cap is enforced.
- SQL payload is stored as literal text and rendered escaped; 0 SQL errors in logs; the product page shows the text, not a script execution (assert no alert dialog in the browser test).
- The renamed file is rejected by magic-byte inspection, nothing is written to disk, and the error does not reveal the internal path.
- Negative quantity and negative amount are rejected by validation with field-level Persian errors; no order, invoice or ledger row is created.
- All rejections are logged at `warning` without echoing the payload back to the client.

---

## 1. Coverage map

| Contract area | Scenarios |
|---|---|
| Identity / users (پیوست ۲ بند ۸, پیوست ۳) | AC-01, AC-02, AC-14, AC-18 |
| Merchant onboarding (پیوست ۵, پیوست ۶) | AC-02, AC-03, AC-11, AC-13 |
| Catalog / pricing (پیوست ۲, پیوست ۷ بند ۴, پیوست ۱۲) | AC-03, AC-19, AC-21 |
| Cart / orders / invoices (پیوست ۲ بند ۶, پیوست ۵ بند ۴۲) | AC-04, AC-06, AC-07 |
| Payments (پیوست ۱ بند ۵, پیوست ۱۳) | AC-05, AC-06, AC-16 |
| Shipping and compensation (پیوست ۱۱ بند ۱۵, پیوست ۴ بند ۱۴) | AC-08 |
| Complaints and returns (پیوست ۴ بند ۱۵–۱۶, پیوست ۱۲ بند ۱۴) | AC-09, AC-10 |
| Loyalty / points / rewards (پیوست ۴) | AC-12, AC-13 |
| Marketing / commission (پیوست ۶) | AC-02, AC-11, AC-13 |
| Messaging / tickets (پیوست ۵ بند ۲۸, پیوست ۷ بند ۸) | AC-15 |
| Reporting and settlement (پیوست ۷, پیوست ۱۳) | AC-16 |
| Admin, audit, security (پیوست ۱ بند ۷ و ۱۰, پیوست ۹ بند ۳) | AC-17, AC-18, AC-25 |
| Classifieds / Patogh (پیوست ۵ بند ۴, ۵, ۱۳) | AC-20 |
| Notifications (پیوست ۵ بند ۹ و ۱۲) | AC-22 |
| SEO / PWA / compatibility (پیوست ۱ بند ۳–۶, پیوست ۹) | AC-23 |
| Backup and DR (پیوست ۱ بند ۹) | AC-24 |
| Import and training (پیوست ۹ بند ۴ و ۶, پیوست ۱۰) | AC-21 + training attendance sheets |

## 2. Scenario counts

| Priority | Count | Scenarios | Gate |
|---|---|---|---|
| P0 | 17 | AC-01, 02, 03, 04, 05, 07, 08, 09, 10, 11, 12, 14, 16, 17, 18, 24, 25 | 100 % pass before go-live |
| P1 | 6 | AC-06, 13, 15, 19, 21, 23 | ≥ 90 % pass, remainder with accepted workaround |
| P2 | 2 | AC-20, 22 | best effort, tracked as backlog |
| **Total** | **25** | all scenarios, §AC-01 … AC-25 | |

## 3. UAT execution protocol

1. **Round kickoff** — build SHA, environment URL, seeded dataset and the scenario sheet are handed over
   in writing. Clock: defect logging window opens (3 working days, `REQ-CTR-215`).
2. **Execution** — the client's team runs scenarios; QA observes and records evidence per §0.2. Any
   scenario the client cannot execute is executed by QA in front of them.
3. **Defect capture** — one record per defect: scenario ID, step, expected vs actual, severity
   (P1–P4 per `non-functional-requirements.md` §11.2), evidence attachment.
4. **Triage** — within 1 working day the vendor classifies each defect as bug / change request /
   not-a-defect; change requests go to the addendum mechanism (ماده ۸ / ماده ۱۵), not the defect log.
5. **Fix and retest** — P1 fixed within 24 h (calendar), P2 within 3 working days; the affected scenario
   is re-executed in full, not partially.
6. **Round close** — signed sheet per scenario; open-defect register attached; the next round starts
   only when no P1 is open.

## 4. Definition of acceptance complete

A scenario counts as accepted only when: (a) every expected-result bullet is observed, (b) evidence is
attached, (c) the client's executor signed the line, and (d) the scenario ran on the build that will be
delivered. A pass on a different build does not transfer.

## 5. Changelog

| Version | Date | Change |
|---|---|---|
| 1.0 | 2026-11-25 | 25 scenarios, coverage map, UAT protocol, exit criteria. |
