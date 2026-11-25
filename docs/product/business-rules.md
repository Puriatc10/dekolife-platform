# Business Rules Rulebook — Dekolife

| | |
|---|---|
| **Purpose** | The single normative rule set behind every price, point, reward, commission, hold, refund and state transition. If two documents disagree about a number or an ordering, this one wins — except where the contract itself states the number, in which case the contract wins and the conflict is reported. |
| **Audience** | Backend engineers (this is the implementation spec), QA (these are the assertions), the client's operations team (this is the policy they own), finance. |
| **Status** | Baseline v1 — 2026-11-25. 71 rules in total; **31 of them fix a numeric value or boundary the contract leaves open** and are labelled `AGENT_DECISION` with an `AMB-*` id for client sign-off (§12). |
| **Change control** | Rules marked `CONTRACT` may only change by contract addendum. Rules marked `AGENT_DECISION` are configured as **versioned rule sets** (`effective_from`) and changed by admin without a release; the version is stamped on every computed row. |

> **Source:** Contract `DK-1405-01`, pages 1–11 and appendices 1–14 ([`../contract/contract-extracted-text.txt`](../contract/contract-extracted-text.txt)); constraints from [`../canonical-context.md`](../canonical-context.md) §5. Every rule carries a `Source` cell with page/appendix/clause. `AMB-*` ids raised here must be registered in the project ambiguity register by the requirements owner (Agent A); if the register later assigns a different number, the register wins and this file is re-pointed — the *rule* does not change.

---

## 0. Labels, units and how to read a rule

| Label | Meaning | Who can change it |
|---|---|---|
| `CONTRACT` | Stated in the contract, with a clause pointer. | Only by written addendum (`ماده ۸`). |
| `AGENT_DECISION` | The contract implies the mechanism but does not state the number, the boundary or the tie-break. This document fixes a default so the system can be built; client sign-off is required (`AMB-*`). | Admin, at runtime, as a rule-set version. |
| `TECHNICAL_CONSTRAINT` | Derived from [`../canonical-context.md`](../canonical-context.md) §5 (money as integer Toman, append-only ledger, server-side pricing, UTC storage, idempotent financial commands). | Architecture decision (ADR). |

**Units, fixed once and never re-litigated**

| Quantity | Representation | Note |
|---|---|---|
| Money | integer Toman in `BIGINT` columns named `*_amount` | never float; all arithmetic through `App\Shared\Money` (`TECHNICAL_CONSTRAINT`) |
| Points | integer; **1 point = 1,000 Toman of reward value** | `AGENT_DECISION`, `AMB-LOY-001` — see the divisor rationale in §6 (`BR-LOY-01`) |
| Percentages | stored as basis points (`*_bp`, 10,000 = 100%) | avoids float drift in rule sets |
| Time | stored UTC, displayed `Asia/Tehran`, Jalali at the presentation layer | `TECHNICAL_CONSTRAINT` |
| Window "یک هفته" | 7 × 24 hours from the anchoring event | `AGENT_DECISION` where the anchor is unclear (`AMB-ORD-001`) |

**Rule id scheme:** `BR-<AREA>-<NN>`; areas mirror the canonical requirement areas (`PRI`, `CRT`, `ORD`, `INV`, `PAY`, `FIN`, `LOY`, `CMP`, `MKT`, `CAT`, `TND`, `PTG`, `USR`, `ADM`, `NTF`).

---

## 1. Rule index

| Area | Rules | What they settle |
|---|---|---|
| Pricing | `BR-PRI-01…06` | price planes, resolution order, volume bands, consumer uplift, matrix ownership, cost-floor guard |
| Cart | `BR-CRT-01…04` | server-side pricing, instrument stacking, shipping, deposit |
| Orders | `BR-ORD-01…09` | lifecycle + states, complaint window, holds, incomplete orders, delay compensation, cancellation, supplementary invoice, review window, violation reports |
| Invoices | `BR-INV-01…02` | issuance and numbering, manual invoices |
| Payments | `BR-PAY-01…04` | gateway selection, idempotency, reconciliation, dual-gateway conflict |
| Finance | `BR-FIN-01…05` | ledger, settlement, wallet, commission vesting, platform take-rate |
| Loyalty | `BR-LOY-01…11` | points formula, eligibility, 4× redemption, reward credit, inactivity, stars, tier discount, diminishing coefficient, first-invoice discount, campaign windows, ledger/clawback |
| Compensation | `BR-CMP-01…05` | plans A–D, attribution, commission base, clawback, self-dealing |
| Marketing | `BR-MKT-01…07` | QR/link attribution, on-site registration, duplicates, edit window, referral points, ownership transfer, scoped access |
| Catalog / CMS | `BR-CAT-01…03` | publishing rights, attributes, classifieds taxonomy gate |
| Tenders | `BR-TND-01…02` | sealed bids, award→order |
| Patogh | `BR-PTG-01` | request/quote loop, no platform payment |
| Users | `BR-USR-01…05` | five levels, secondary level, forced password change, membership transfer |
| Admin | `BR-ADM-01…05` | approvals, rule sets, audit, exports, inactive-store downgrade |
| Notifications | `BR-NTF-01…02` | channel policy, paid notifications |

---

## 2. Pricing

### BR-PRI-01 — Three price planes exist; the role selects the plane `CONTRACT`
Every sellable item carries three commercial planes:

| Plane | Who pays it | Contract basis |
|---|---|---|
| **Colleague price** (قیمت همکار) | registered stores (`is_colleague = true`) | page 6 / پیوست ۳ / ۳-۱ / ۸; page 11 / پیوست ۱۲ / بند ۱ |
| **Wholesale price** (قیمت عمده) | stores crossing a volume band | page 6 / پیوست ۳ / ۳-۱ / ۸; page 7 / پیوست ۴ / بند ۱۱ |
| **Consumer price** (قیمت مصرفکننده) | consumers | page 11 / پیوست ۱۲ / بند ۱ |

A user with a secondary role gets the plane of the **role chosen for that order**, never a blend (`AMB-PRI-001`). An item with no price for the caller's plane is **hidden in catalogue** and **blocked in cart** — never rendered as "price on request" (that would let a store infer a plane it must not see).

### BR-PRI-02 — Resolution order is fixed and server-side; order lines snapshot it `TECHNICAL_CONSTRAINT` + `AGENT_DECISION`
Resolution runs in exactly this order, and no step may be reordered:

```
base list price
  → role price (colleague | wholesale | consumer)        [BR-PRI-01]
  → volume band adjustment                               [BR-PRI-03]
  → active promotion / festival price                    [BR-CRT-02]
  → coupon or voucher (بن)                               [BR-CRT-02]
  → loyalty: tier discount, then reward deduction        [BR-LOY-07, BR-LOY-03]
  = net payable
```

1. Never trust a price, discount or total from the client; recompute at cart view, at checkout and at payment intent.
2. Each order line stores `unit_price_amount`, `applied_rules` (a JSON trail of rule ids + versions) and `price_snapshot_at`.
3. If the resolution result changes between cart and payment, the cart re-prices and **requires explicit re-confirmation**; the order is never silently charged a different amount.
4. Admin manual overrides sit above step 1, are audited, and expire at a set date. `AGENT_DECISION` (`AMB-PRI-001`).

### BR-PRI-03 — Volume bands and the volume cash discount `AGENT_DECISION`
The contract fixes four coefficients for volume purchases (0.75 / 0.5 / 0.25 / 0.2 %) and states the mechanism is "based on purchase volume, per admin settings" (page 6 / پیوست ۴ / بند ۳), but names no thresholds. Defaults:

| Band | Invoice net (Toman) | Points coefficient | Wholesale price applies | Volume cash discount |
|---|---|---|---|---|
| V1 | < 10,000,000 | 0.75% | no | per product definition, 0% default |
| V2 | 10,000,000 – 29,999,999 | 0.50% | no | 0–2%, per product |
| V3 | 30,000,000 – 79,999,999 | 0.25% | yes | 2–4%, per product |
| V4 | ≥ 80,000,000 | 0.20% | yes | 3.5–6%, per product |

Rules: the band is computed **per invoice, not per line** (`AMB-PRI-003`); the band is decided **after** coupon/voucher so that a discount cannot push a buyer into a better band; the product's own volume discount percentage is authoritative for the cash discount and is shown to buyers in the product introduction (page 11 / پیوست ۱۲ / بند ۶). Coefficients descend as volume rises on purpose — the contract's "ضریب کاهش امتیاز" (page 8 / پیوست ۵ / بند ۲۱) rewards continuity over one-off bulk, so a single huge invoice must not out-earn steady buying.

### BR-PRI-04 — Consumer price defaults to colleague price × 1.35 `AGENT_DECISION`
The contract gives "مثلاً ۳۵ درصد بالاتر" as an example and requires both a manual admin option and a percentage multiplier across all products (page 11 / پیوست ۱۲ / بند ۱). Default: **consumer = colleague × 1.35**, overridable per product and per category; a manual consumer price always wins over the multiplier. Guard: consumer price may never be **below** colleague price for the same role combination — such a save is rejected with a clear error (`AMB-PRI-002`).

### BR-PRI-05 — The published matrix is the only source of truth `CONTRACT` + `AGENT_DECISION`
Suppliers set prices for their own products through the per-role matrix; admin may set or override any product (page 9 / پیوست ۷ / بند ۴). Every price row carries `effective_from`, `effective_to`, `set_by`, `source` (`supplier|admin|import`). Overlapping rows resolve to the latest `effective_from`, the conflict is reported to admin, and a supplier may not silently overwrite an admin override (`AMB-PRI-001`).

### BR-PRI-06 — Cost-floor guard on discounts `AGENT_DECISION`
Because free-shipping campaigns, coupons, tier discounts and rewards all reduce the payable amount (page 11 / پیوست ۱۲ / بند ۱۲), a resolved price can fall below the sub-order's cost. Rule: the pricing engine computes `landed_cost` per line (supplier price + shipping share + gateway fee) and raises a **warning to admin at configuration time** and a **non-blocking flag on the invoice** when the net falls below cost by more than the configured tolerance (default 0%). Nothing is silently blocked, because the contract requires the discount to be honoured; the flag exists so the client can see the leakage per campaign (`AMB-PRI-004`).

---

## 3. Cart and checkout

### BR-CRT-01 — Cart is a projection, not a ledger `TECHNICAL_CONSTRAINT`
Cart lines store product, quantity, chosen variant and the *last resolved* price. Nothing is reserved by adding to cart; stock is checked at checkout and again at payment capture. A stale cart never blocks the catalogue.

### BR-CRT-02 — Instrument stacking and cap `AGENT_DECISION`
Order of application is `promotion → coupon → voucher (بن) → tier discount → reward`. Defaults:

| Rule | Default | Ambiguity |
|---|---|---|
| Coupons per order | 1 | `AMB-LOY-008` |
| Voucher (بن) per order | 1, must be within its validity window | page 11 / پیوست ۱۲ / بند ۴ |
| Coupon + voucher together | allowed unless the promotion says otherwise | `AMB-LOY-008` |
| Maximum total discount | **30%** of the pre-discount net; beyond that the highest-value instrument applies alone and the buyer is told | `AMB-LOY-004` |
| Discount never applies to | shipping fee, gateway fee, tax | `AGENT_DECISION` |

### BR-CRT-03 — Shipping choice and free-shipping campaigns `CONTRACT`
The buyer picks the shipping type at checkout and it is carried into the supplier's order form (page 8 / پیوست ۵ / بند ۴۰). A campaign may make shipping free **within one city** or **nationwide** (page 11 / پیوست ۱۲ / بند ۱۲); eligibility is evaluated against the delivery address city at checkout and re-validated at capture. The fee is part of the order but **never** part of the points base (`BR-LOY-01`).

### BR-CRT-04 — Deposit (بیعانه) and balance on delivery `CONTRACT`
A consumer may pay a deposit and settle the remainder on dispatch or on site (page 11 / پیوست ۱۲ / بند ۱۳). Rules: the deposit is a captured payment against the sub-order; the sub-order sits in `partially_paid` until the balance is captured; the supplier sees an explicit "collect on delivery" flag; an unpaid balance beyond the configured window moves the sub-order to `unpaid_balance_expired` and releases stock (`AGENT_DECISION`, `AMB-FIN-002`). Points are awarded on the **paid** amount only, and the remainder when captured.

---

## 4. Orders

### BR-ORD-01 — Order lifecycle and state machine `CONTRACT` + `AGENT_DECISION`

**Aggregate shape:** one parent `ord_orders` per checkout, one `ord_sub_orders` per seller (the unit of fulfilment, invoicing, commission, complaint, hold and settlement). Parent state = derived function of its sub-orders.

**States**

| State | Meaning | Money | Points |
|---|---|---|---|
| `draft` | cart converted, not yet payable | none | none |
| `pending_payment` | awaiting first capture | intent created | none |
| `partially_paid` | deposit captured, balance outstanding | partial | on paid part |
| `paid` | fully captured | captured | **awarded** |
| `accepted` | supplier acknowledged | captured | awarded |
| `preparing` | being picked/packed | captured | awarded |
| `preparation_hold` | frozen by complaint or by the stop button | captured, **supplier payable held** | awarded (clawback only on cancel) |
| `incomplete_pending_buyer` | supplier flagged `سفارش ناقص` | captured, **supplier payable held**, **delay compensation suspended** | awarded |
| `dispatched` | waybill recorded | captured | awarded |
| `delivered` | receipt confirmed (or auto-confirmed, `BR-ORD-07`) | captured | awarded |
| `completed` | review window closed, no open case | captured → settled | awarded |
| `cancelled` | cancelled before dispatch | refunded | **clawed back** |
| `refunded` | cancelled or returned after payment, money returned | refunded/credited | clawed back |
| `returned` | goods accepted back within window | refunded/credited | clawed back |

**Transitions** (guard in brackets; every transition writes an audit row)

| # | From → To | Trigger | Guard | Side effects |
|---|---|---|---|---|
| T1 | `draft → pending_payment` | checkout confirmed | cart valid, stock available | payment intent, idempotency key |
| T2 | `pending_payment → paid` | capture verified | amount matches, signature valid | invoice issued, points awarded, commission accrued, notifications |
| T3 | `pending_payment → partially_paid` | deposit captured | deposit ≥ configured minimum | invoice issued for paid part |
| T4 | `partially_paid → paid` | balance captured | — | remainder points, invoice completed |
| T5 | `paid → accepted` | supplier accepts | sub-order assigned to supplier | SLA clock starts |
| T6 | `accepted → preparing` | supplier starts picking | stock reserved | — |
| T7 | `preparing → dispatched` | waybill + courier recorded | quantity shipped > 0 | dispatch notification, delivery clock starts |
| T8 | any of `paid…preparing` → `preparation_hold` | complaint filed **or** stop button pressed | within complaint window (T8a) or stop window (T8b) | supplier payable held, admin case opened |
| T9 | `preparation_hold → preparing` | complaint rejected / stop released | admin decision recorded | hold released |
| T10 | `preparation_hold → cancelled` | complaint upheld as cancel | admin decision | refund + points clawback |
| T11 | any of `paid…preparing` → `incomplete_pending_buyer` | supplier flags incomplete | reason recorded | compensation suspended, buyer notified |
| T12 | `incomplete_pending_buyer → preparing` | buyer accepts substitute/amendment | price delta settled | new lines, delta charged/refunded |
| T13 | `incomplete_pending_buyer → cancelled` | buyer cancels or window expires | — | refund to SHEBA/wallet, points clawback |
| T14 | `dispatched → delivered` | buyer confirms, or auto-confirm window elapses | — | review window opens |
| T15 | `delivered → completed` | review window closed | no open complaint/violation | settles in the next settlement run |
| T16 | `delivered → returned` | return accepted within window | item eligible per admin list | refund per `BR-ORD-06` |
| T17 | `dispatched → cancelled` | supplier/admin cancels after dispatch | goods returned or refused | refund, possible delay compensation |
| T18 | `paid → cancelled` | supplier cannot fulfil without using the incomplete flag | admin visible | refund, commission reversed, incomplete misuse recorded |

**Cross-cutting rules:** a parent order is `completed` only when all sub-orders are terminal; a hold on one sub-order never blocks the others; an order placed by a local intermediary (رابط) on a consumer's behalf is tagged `placed_on_behalf_of = consumer` and keeps the consumer's price plane and review rights (`AMB-ORD-005`); `order_state_history` is append-only with actor, reason and timestamp; no state may be skipped (a `paid` sub-order cannot jump to `dispatched` without `accepted`) — `AGENT_DECISION` on the skip rule, contract only implies sequence via the stop button existing "after invoice and payment" (page 7 / پیوست ۴ / بند ۱۶).

### BR-ORD-02 — Complaint window is one week from dispatch `CONTRACT`
The contract allows a complaint and a preparation stop "up to one week after the load is dispatched" (page 7 / پیوست ۴ / بند ۱۵; repeated at page 8 / پیوست ۵ / بند ۳۳). Rule: the complaint button is available for **7 × 24 h from the dispatch timestamp**; after that the button is disabled and the only path is a support ticket to admin (goodwill, no automatic hold). `AMB-ORD-001` records the open question of whether the client means dispatch or delivery — the anchor used is dispatch because the clause says "ارسال".

### BR-ORD-03 — A hold freezes supplier payment, not the buyer's obligation `CONTRACT`
While a complaint or stop is open: the supplier's payable is **held** (not paid, not cancelled); the buyer's capture is retained; fulfilment stops. Release requires an admin decision with a written reason (uphold → compensate/cancel, reject → release). Default escalation: if no decision within **14 days**, the case auto-escalates and admin must choose, so a hold cannot become a permanent interest-free loan (`AGENT_DECISION`, `AMB-ORD-002`). The stop button is only offered after invoice **and** payment, for a configured period (page 7 / پیوست ۴ / بند ۱۶).

### BR-ORD-04 — Incomplete order (`سفارش ناقص`) suspends delay compensation `CONTRACT` + `AGENT_DECISION`
A supplier may flag an order incomplete to refer it back to the buyer, explicitly to avoid a delay penalty (page 10 / پیوست ۱۱ / بند ۱۲). Effects: state `incomplete_pending_buyer`; delay clock paused; buyer chooses substitute / amendment / cancel within the configured window (default **72 h**); on expiry the sub-order auto-cancels with refund.

**Anti-abuse** — the contract requires automatic negative points for incorrect or repeated use (page 10 / پیوست ۱۱ / بند ۱۳) but states no values. Defaults (`AMB-ORD-003`): a flag dismissed by admin as delay evasion costs **10 points**; **3 upheld misuse flags in 90 days** reinstates the delay compensation for the affected sub-orders and triggers an admin review; the supplier's incomplete-order rate is visible on the admin scorecard (page 9 / پیوست ۷ / بند ۱۰).

### BR-ORD-05 — Delay compensation formula `AGENT_DECISION`
The contract requires the capability and says the percentage or amount is **approved by admin** (page 7 / پیوست ۴ / بند ۱۴), that the supplier records the delay with a waybill number (page 10 / پیوست ۱۱ / بند ۱۵) and that the deduction is automatic into the buyer's wallet (page 11 / پیوست ۱۲ / بند ۸). Default formula:

```
compensation = late_line_value × 0.5% × days_late, capped at 20% of sub-order value
days_late    = dispatch_date − promised_dispatch_date   (whole days, min 1)
```

Worked example: 8,000,000 Toman of late lines, 3 days late → 8,000,000 × 0.005 × 3 = **120,000 Toman**, cap 1,600,000, so 120,000 is credited. The 0.5%/day and 20% cap deliberately mirror the contract's own delay-penalty construction in `ماده ۴ بند ۲` (page 3) so the platform uses one arithmetic language; it is **not** the same obligation and must never be reported as such (`AMB-ORD-004`). Admin may set 0 for a documented force-majeure waiver.

### BR-ORD-06 — Cancellation and refund routing `CONTRACT`
Cancellation is allowed from the supplier panel and by admin (page 10 / پیوست ۱۱ / بند ۱۴); the buyer's money returns to **their SHEBA** (page 8 / پیوست ۵ / بند ۱۵) or, at the buyer's choice, to the wallet for reuse. Order of preference: original payment instrument via gateway refund where the gateway supports it, otherwise bank transfer to the stored SHEBA, otherwise wallet credit. Partial cancellation refunds only the affected lines plus their share of shipping when nothing else ships. Points, tier discount and reward used on the invoice are reversed (`BR-LOY-11`); commission is reversed (`BR-CMP-04`).

### BR-ORD-07 — Receipt, auto-confirm and review windows `AGENT_DECISION`
Delivery is confirmed by the buyer, or automatically after **7 days** from dispatch with no complaint (`AMB-ORD-007`). The review/rating window is **1 week** after receipt with 5–10 parameters (page 7 / پیوست ۴ / بند ۱۷; page 11 / پیوست ۱۲ / بند ۹). Reviews after the window are refused with an explanation, not silently dropped.

### BR-ORD-08 — Returns are item-eligible and window-bounded `CONTRACT`
Returns are accepted within a configured window **only for items admin has whitelisted** (page 11 / پیوست ۱۲ / بند ۱۴). Return acceptance does not automatically void the original invoice — it creates a credit note and a linked refund, so the audit trail keeps both documents.

### BR-ORD-09 — Violation reports (`وارن`) are time-bounded and peer-visible `CONTRACT` + `AGENT_DECISION`
Every invoice carries the `وارن` button (page 7 / پیوست ۴ / بند ۱۸) and the supplier's recent violations are shown to peers at checkout — the contract's example is "the last two months" (page 7 / پیوست ۴ / بند ۱۹). Rule: a violation case is `open → upheld | dismissed`; an upheld case records a negative **violation point** visible only after admin approval (page 8 / پیوست ۵ / بند ۱۴); peer visibility defaults to **60 days** (`AMB-ORD-006`); appeal window **14 days** after the decision; the supplier's right of reply is stored on the case. `AMB-ORD-006` also covers whether upheld violations affect search ranking (default: no).

---

## 5. Invoices

### BR-INV-01 — One immutable invoice per sub-order, sequenced and snapshotting `CONTRACT` + `TECHNICAL_CONSTRAINT`
An invoice is issued when the sub-order reaches `paid` or `partially_paid`, and is the unit that earns points and carries the `وارن` button (page 6 / پیوست ۴ / بند ۱; page 7 / پیوست ۴ / بند ۱۸). Rules: numbering is gap-free and sequential per fiscal year (`INV-1405-000123`); a issued invoice is immutable — corrections are credit notes or supplementary invoices, never edits; every invoice stores a snapshot of lines, prices, the applied rule trail and the buyer/seller identity as at issue; a pre-invoice (`پیشفاکتور`) is a separate, non-numbered, expiring document (page 8 / پیوست ۵ / بند ۴۲); voiding posts a reversal rather than deleting. Tax and shipping are separate lines so the points base (`BR-LOY-01`) is unambiguous.

### BR-INV-02 — Manual invoices are first-class `CONTRACT`
Admin may create a purchase invoice by hand (page 9 / پیوست ۷ / بند ۲), because a large share of trade still arrives by phone. A manual invoice follows the same rules: numbering, snapshot, points (unless admin marks it points-exempt with a reason), commission attribution if a marketer owns the buyer, and an audit entry naming the admin. It may not be backdated beyond the configured window (default 30 days) without a second approval.

---

## 6. Loyalty — points, tiers, rewards

### BR-LOY-01 — Points formula `CONTRACT (coefficients) + AGENT_DECISION (unit)`

```
line_points    = floor( line_net_amount × product_coefficient × volume_coefficient / 1,000 )
invoice_points = sum(line_points)
```

- `line_net_amount` excludes shipping, gateway fee and tax.
- `product_coefficient` defaults to the band coefficient; a product/producer may carry its own rate (page 11 / پیوست ۱۳ / بند ۴; page 8 / پیوست ۵ / بند ۲۲).
- `volume_coefficient` is the band value: **0.75% / 0.5% / 0.25% / 0.2%** (page 6 / پیوست ۴ / بند ۳).
- **If the invoice received a volume cash discount**, the coefficient set becomes **0.2% / 0.1%** instead (page 7 / پیوست ۴ / بند ۱۲): V3 → 0.2%, V4 → 0.1% (`AGENT_DECISION` mapping, `AMB-LOY-001`).
- **Why divide by 1,000:** the contract fixes "100 points = 1 star" (بند ۹) and "4% discount at 1–100 points" (بند ۲۰). Taken as raw Toman, a single 10,000,000 invoice at 0.75% would yield 75,000 points — 750 stars and a permanently maxed tier, which is absurd. Making **1 point = 1,000 Toman of reward value** keeps the contract's own thresholds meaningful: the same invoice yields 75 points, so a store reaches 100 points (one star, 4% tier) in roughly one to two months of normal buying. This is the single most consequential interpretation in this document and is explicitly flagged in `AMB-LOY-001`; if the client intended raw Toman, only the divisor and the thresholds change, not the formula shape.
- Points are integer; the fractional remainder per line is dropped (rounded down) so the sum is reproducible by replay.

### BR-LOY-02 — Every invoice earns points, even if never redeemed `CONTRACT`
"امتیازدهی همه فاکتورها انجام میشود؛ حتی اگر خریدار پاداش خود را در مهلت تعیینشده دریافت نکند، امتیازها محاسبه و حفظ میشوند" (page 6 / پیوست ۴ / بند ۵). Rule: points are awarded on the **paid** state (money captured), for every invoice including manual admin invoices, and are retained indefinitely unless clawed back (`AMB-LOY-009` fixes "paid" as the trigger; the contract says "invoice" without timing).

### BR-LOY-03 — The 4× redemption rule `CONTRACT`
"A reward defined on a product is deducted from the next invoice only when the next invoice is at least four times the reward; otherwise exactly half the reward is deducted" (page 6 / پیوست ۴ / بند ۴). Implementation:

```
if next_invoice_net ≥ 4 × available_reward  → deduct full reward
else                                        → deduct floor(reward / 2), keep the rest available
```

Worked example: reward 500,000 Toman, next invoice 1,800,000 < 2,000,000 → deduct **250,000**, 250,000 stays. `AGENT_DECISION` details (`AMB-LOY-003`): "half" is floor-rounded to whole Toman; the undeducted remainder **carries forward** (it is not forfeited, because بند ۵ says points are preserved); the deduction is capped so it never exceeds the invoice net; a reward with an expiry date lapses per its own terms (بند ۷ allows fixed-term or permanent).

### BR-LOY-04 — Points threshold converts to a cash reward in the wallet `AGENT_DECISION`
"The buyer reaching a defined number of points gets the surplus cash reward registered in the wallet, usable only for a new purchase and invoice; the reward may be time-limited or permanent" (page 6 / پیوست ۴ / بند ۷). Defaults (`AMB-LOY-004`): threshold **500 points** (= 500,000 Toman of reward value); surplus above the threshold converts in whole 100-point blocks; default validity **180 days** unless admin marks it permanent; spendable only against a new invoice, never withdrawable as cash.

Campaign variant (`CONTRACT`, بند ۸): reaching a defined number of invoices or points **within a short window** credits a cash reward, with short/medium/long windows admin-definable. Defaults (`AMB-LOY-007`): short = 30 days, medium = 90, long = 365.

### BR-LOY-05 — Inactivity deduction `CONTRACT (mechanism) + AGENT_DECISION (value)`
"In case of no invoice/purchase in a defined period (e.g. one month), an amount of points (e.g. four points per month) is deducted" (page 6 / پیوست ۴ / بند ۶; page 8 / پیوست ۵ / بند ۱۷). Defaults (`AMB-LOY-002`): grace **60 days** with no paid invoice, then **−4 points per 30-day period**, applied by a nightly job, never below zero; **paused** while the buyer has an open complaint or an unresolved violation case; one notification before the first deduction; every deduction is a ledger entry with a reason code. Inactivity also drives store downgrade (`BR-ADM-05`).

### BR-LOY-06 — Stars: 1 star per 100 purchase points, unlimited `CONTRACT`
"Colleague buyers receive one star per 100 points of purchase, displayed on the profile; the number of stars is unlimited" (page 7 / پیوست ۴ / بند ۹). Rule: `stars = floor(lifetime_purchase_points / 100)`. `AGENT_DECISION` (`AMB-LOY-006`): the basis is **purchase points only** (referral/campaign bonus points do not buy stars, otherwise invites would inflate status); stars **never decay** even if points are deducted, because the contract calls the count unlimited and gives no decay rule; stars are not transferable and are recomputed from the ledger, never stored as a mutable counter.

### BR-LOY-07 — Tiered next-invoice discount `CONTRACT`
"The tiered discount on the next invoice: users with 1–100 points get 4%, users with 100–200 points get 5%; the discount is announced by SMS" (page 7 / پیوست ۴ / بند ۲۰; repeated at page 8 / پیوست ۵ / بند ۱۶). The contract's bands overlap at exactly 100; resolution (`AGENT_DECISION`, `AMB-LOY-004`):

| Points balance | Next-invoice discount |
|---|---|
| 0 | none |
| 1 – 99 | **4%** |
| 100 – 200 | **5%** |
| ≥ 201 | **fixed n Toman**, default **250,000**, admin-configurable (page 7 / پیوست ۴ / بند ۲۱) |

Rules: the fixed n replaces the percentage (it does not stack); the entitlement is created when the tier is reached, applies to **one** next invoice, expires after the configured window (default 90 days), and is announced by SMS; the discount is computed on the invoice net **before** reward deduction; total discount is capped per `BR-CRT-02`. A separate first-invoice (or admin-nominated invoice) discount may exist **in addition** to the next-invoice reward (page 7 / پیوست ۴ / بند ۱۰; page 11 / پیوست ۱۲ / بند ۱۰) — `BR-LOY-09`.

### BR-LOY-08 — Diminishing coefficient for volume, and per-product variance `CONTRACT`
"Higher-volume invoices earn fewer points relative to the amount paid — a mechanism to encourage continuous buying rather than one large purchase" (page 8 / پیوست ۵ / بند ۲۱), and "a million Toman spent on different products/stores can earn a different number of points" (بند ۲۲). This is why `BR-PRI-03`'s coefficients **descend** with volume and why the product-level coefficient exists. A volume cash discount additionally halves the coefficient set to 0.2/0.1 (`BR-LOY-01`). The two mechanisms must never double-penalise: if a band already dropped the coefficient, the volume-discount set applies **instead of**, not on top of, the band value.

### BR-LOY-09 — First-invoice / admin-nominated discount `CONTRACT`
A discount may be granted on the first invoice or on any invoice admin nominates, **in addition to** the next-invoice reward (page 7 / پیوست ۴ / بند ۱۰; page 11 / پیوست ۱۲ / بند ۱۰). Rule: it is a promotion with source `admin`, is capped by `BR-CRT-02`, is audited, and must never be used to bypass the 4× rule (it is a discount, not a reward).

### BR-LOY-10 — Campaign windows are admin-defined `CONTRACT`
Short/medium/long windows for "reach N invoices/points in a window → cash reward" are configurable (page 6 / پیوست ۴ / بند ۸). Defaults per `BR-LOY-04`. A campaign is a versioned rule set with a start and end; invoices are evaluated against the campaign active **at capture time**.

### BR-LOY-11 — Points live in an append-only ledger; every change is reproducible `TECHNICAL_CONSTRAINT`
`loy_point_entries` is append-only: `award`, `deduct_inactivity`, `convert_reward`, `redeem`, `reverse`, `manual_adjust`, each with `rule_set_version`, `source_type`, `source_id`, `reference` (unique for idempotency). Balances are derived. Clawback (`reverse`) fires on cancel, refund, return, or an upheld fraud case, and reverses **the points that invoice produced plus any tier entitlement still unused**. A replay command must recompute any period's points from the ledger and the versioned rules and produce byte-identical totals — this is a test gate, not an optional tool.

---

## 7. Compensation — marketer plans and commissions

### BR-CMP-01 — Four plan shapes, all configurable `CONTRACT (mechanism) + AGENT_DECISION (values)`
The contract requires several marketer models with different commission methods — "plans A/B/C/D", also usable by buying colleagues — and requires commission/pay to be **conditioned on activity and reaching a defined score in the plan** (page 9 / پیوست ۶ / بند ۳, بند ۵–۶). Default definitions (`AMB-CMP-001`):

| Plan | Shape | Default values | Qualifying condition |
|---|---|---|---|
| **A** — commission-only | % of invoiced net of attributed stores | **2.0%**, paid on `paid`, vested 30 days after delivery | store must be active (≥1 invoice/90 days) |
| **B** — per-visit | fixed fee per verified first visit | **150,000 Toman**, cap **40 visits/month** | geolocated visit + store approved |
| **C** — hybrid | monthly retainer + commission | **5,000,000/month + 1.0%** | retainer paid only if ≥5 new stores/month |
| **D** — salary | fixed monthly salary, no per-invoice commission | **12,000,000/month** | KPI gate: ≥20 activated stores/quarter |

Rules: a plan is assigned to a user, is versioned, and changes take effect on `effective_from` without recomputing history; the qualifying condition is evaluated per period and a failed gate **suspends** the payout rather than deleting it (the contract requires the ability to cut commission if conditions are not met — page 8 / پیوست ۵ / بند ۱۹; page 7 / پیوست ۴ / بند ۲۲); commission may be disabled per marketer and the marketer's level changed by admin (بند ۲۲).

### BR-CMP-02 — Attribution is by store ownership, established once at registration `CONTRACT`
A store belongs to the marketer whose QR/link/code registered it (page 7 / پیوست ۵ / بند ۱). The marketer sees invoices of stores **he visited** (page 6 / پیوست ۳ / ۳-۲ / ۱۰). Rules: exactly one owning marketer per store at a time; every sub-order inherits the owner at creation and stores `attributed_marketer_id` on the sub-order (never re-derived later); a transfer changes future sub-orders only; a sub-order with no owner accrues no commission.

### BR-CMP-03 — Commission base and vesting `AGENT_DECISION`
Base = sub-order **net** (after discounts, excluding shipping, gateway fee and tax) — consistent with the contract's own definition of net profit (page 4 / ماده ۱۶). Commission accrues on `paid`, becomes **payable** 30 days after `delivered` with no open complaint, and is **reversed** if the sub-order later cancels, refunds or returns (`AMB-CMP-002`). A negative balance from a reversal is carried forward against future commission, never silently zeroed.

### BR-CMP-04 — Reversal and clawback `AGENT_DECISION`
On cancel/refund/return: reverse the commission entry with the same reference chain; if it was already paid, post a debit to the marketer's account so the next settlement nets it. Never edit a past payout (`TECHNICAL_CONSTRAINT` — append-only ledger).

### BR-CMP-05 — No self-dealing `AGENT_DECISION`
A marketer earns nothing on his own purchases, on stores he owns an interest in, or on orders where he acted as the buyer (`AMB-CMP-002`). This is what makes the marketer+consumer combination in `BR-USR-03` safe. Self-dealing attempts are blocked at accrual and recorded for admin review.

---

## 8. Marketing — field operations and referral

### BR-MKT-01 — QR/code attribution is binding at scan or link-open `CONTRACT`
Each marketer gets a system-generated QR, a personal code and a personal link (page 7 / پیوست ۵ / بند ۱; page 6 / پیوست ۳ / ۳-۲ / ۴). A registration that arrives through any of them is bound to that marketer, with the evidence (code, timestamp, IP, geo where available) stored on the store record. Attribution survives even if the marketer later leaves, subject to `BR-MKT-06`.

### BR-MKT-02 — On-site registration is a separate form with issued credentials `CONTRACT`
Store registration by a marketer uses a form **distinct** from the public signup (page 7 / پیوست ۵ / بند ۲), collecting address, store name, owner name, postal code, national ID, phones with/without WhatsApp, services, category, city, province, photos (owner, storefront, card), map location (page 9 / پیوست ۶ / بند ۱–۲). After submission the system issues a username and password plus the app install link, and the store **must** change the password at first login.

### BR-MKT-03 — Duplicate prevention on three keys `CONTRACT`
Stores are searchable by national ID, postal code and mobile to prevent duplicate registration (page 7 / پیوست ۵ / بند ۳). Rule: a new registration matching any of the three keys on an existing store is **blocked** with the existing record's owner shown (not its financial data); admin may merge or override with a written reason. Normalisation: mobile to `09xxxxxxxxx`, postal code to 10 digits, national ID checksum-validated.

### BR-MKT-04 — Five-minute edit window `CONTRACT`
A marketer may edit his own registration for **5 minutes** after submission; afterwards edits, deletions and approvals require admin (page 9 / پیوست ۶ / بند ۹). Implemented as an immutable `submitted_at` plus a permission check; every post-window edit is a change request with before/after.

### BR-MKT-05 — Referral points `CONTRACT (mechanism) + AGENT_DECISION (value, timing)`
"When a new colleague enters through another colleague's link, a defined number of points is added to the inviter" (page 7 / پیوست ۴ / بند ۱۳). Default (`AMB-MKT-001`): **50 points** to the inviter, credited when the invitee's **first invoice is paid** — a deliberate tightening of the contract's "on entry" wording to prevent registration farming. Flagged as a deviation for client approval; if the client insists on entry, the value drops to 20 points to keep the abuse cost tolerable. One referral credit per invitee, lifetime; no self-referral; the inviter must still be active.

### BR-MKT-06 — Store ownership transfer and re-visit `CONTRACT`
Admin sees account changes and can assign a store to a new marketer for a re-visit (page 7 / پیوست ۴ / بند ۲۴; page 9 / پیوست ۶ / بند ۴). Rules: transfer requires admin action with a reason; the previous owner keeps commission on sub-orders created before the transfer; a store inactive per `BR-ADM-05` is surfaced in the "inactive" list for re-visit and a **presentation form** (second form) is used, capturing owner/storefront/card photos (page 9 / پیوست ۶ / بند ۷). `AMB-MKT-002` covers whether a transfer needs the previous marketer's consent (default: no, admin decides).

### BR-MKT-07 — Marketer data scope `CONTRACT`
Access is limited to stores he visited/owns and their invoices; visit reporting is available only with admin approval and configuration (page 8 / پیوست ۵ / بند ۲۹; page 9 / پیوست ۶ / بند ۱۰). Live tracking and performance analysis exist for admin (page 11 / پیوست ۱۴ / بند ۲); the marketer sees his own route and scorecard. Location permission denied → the visit is recorded as `unverified` and is not commissionable under plan B.

---

## 9. Catalog, classifieds, tenders, Patogh

### BR-CAT-01 — Publishing rights and visibility `CONTRACT` + `AGENT_DECISION`
Admin may create and price products (page 9 / پیوست ۷ / بند ۴); suppliers manage their own catalogue (page 10 / پیوست ۱۱ / بند ۱); stores may "send content and announcements" and publish their store info (page 6 / پیوست ۳ / ۳-۱ / ۱, بند ۵). `AMB-CAT-001` settles the boundary: **stores do not create catalogue SKUs in v1**; they publish store-profile content, classifieds and Patogh entries, and may request a SKU be listed by a supplier. A product is visible only if `published`, in stock for the caller's city if city-scoped, and priced for the caller's plane.

### BR-CAT-02 — Attributes drive filters and comparison `CONTRACT`
Product attributes are defined per category with filterable flags; comparison aligns on identical parameters (page 5 / پیوست ۲ / بند ۴–۵; page 9 / پیوست ۷ / بند ۴). A missing value renders as "not specified", never as zero or blank-cell arithmetic.

### BR-CAT-03 — Classifieds require an admin-defined taxonomy `CONTRACT`
"Posting an ad is possible only after admin defines the classification" (page 7 / پیوست ۵ / بند ۴). Rule: no taxonomy node → the ad form is closed with an explanatory message; ads carry the poster's public profile card and a report action; moderation applies when the branch is moderated (`AMB-CLS-001`: default moderated, 3 active ads per user, images ≤5, no price required).

### BR-TND-01 — Tenders are sealed and deadline-bound `AGENT_DECISION`
Tender form goes to producers/suppliers (page 7 / پیوست ۵ / بند ۶); colleagues may forward it (page 8 / پیوست ۵ / بند ۳۷); bids may include an inquiry/RFQ (بند ۳۶); an auction section exists for the reverse direction (page 10 / پیوست ۱۱ / بند ۱۰). Defaults (`AMB-TND-001`): bids sealed until the deadline, no bid edits after submission, late bids refused, minimum 3 bidders before award is allowed (admin may waive), no participation fee in v1.

### BR-TND-02 — Award converts to an order at bid prices `AGENT_DECISION`
The winning bid creates a normal order whose lines carry **bid prices** with `source = tender`, bypassing the catalogue matrix but still snapshotting the rule trail (`BR-PRI-02`). If the winner declines within the acceptance window (default 48 h), the buyer may award the next bid. A tender never creates a price list.

### BR-PTG-01 — Patogh is a request/quote loop without platform payment `CONTRACT`
Patogh introduces urban services and artisans (installer, repairer) with a request form and a local cost announcement (page 7 / پیوست ۵ / بند ۵; page 8 / پیوست ۵ / بند ۱۳), and professional profiles are admin-approved (بند ۳۵). Rules (`AMB-PTG-001`): no platform payment, no commission, no order state machine — the loop is `request → quoted → accepted → closed`, contact details are exchanged only after the consumer accepts a quote, and profiles must be admin-approved before appearing.

---

## 10. Payments and finance

### BR-PAY-01 — One active gateway per payment, chosen by the user `CONTRACT (12 gateways) + TECHNICAL_CONSTRAINT`
Twelve bank gateways must be supported (page 5 / پیوست ۱ / بند ۵; page 11 / پیوست ۱۳ / بند ۷). Implementation: a `GatewayDriver` per gateway, admin-activated, and **one gateway per payment attempt** in v1 (canonical §5). The contract also asks for "two gateways simultaneously in one transaction with user selection" (پیوست ۱ / بند ۵) — this conflicts with the v1 constraint and is deferred as `RB-PAY-01`; the conflict is reported, not silently dropped.

### BR-PAY-02 — Every capture is idempotent and verified `TECHNICAL_CONSTRAINT`
A payment intent gets a unique idempotency key; the callback is verified for signature, amount and reference; a duplicate callback is a no-op returning the same result; a mismatch never advances the order and opens a reconciliation case. Refunds are their own intents with their own keys.

### BR-PAY-03 — Daily reconciliation is mandatory `CONTRACT`
Failed, returned and mismatched transactions must be reported and reconciled against gateway reports (page 11 / پیوست ۱۳ / بند ۶). Rule: a nightly import produces match / mismatch / missing buckets; mismatches become cases with an owner and an SLA (5 days); adjustments post as ledger entries with a reason; no balance is ever hand-edited.

### BR-PAY-04 — Split payment is deferred `TECHNICAL_CONSTRAINT`
Combining two gateways in a single transaction, and splitting one payment across instruments, is `RB-PAY-01`. Any UI that implies it must not ship in v1.

### BR-FIN-01 — Append-only ledger; balances are derived `TECHNICAL_CONSTRAINT`
`fin_ledger_entries` records every movement (capture, refund, compensation, commission, reward credit, settlement hold, settlement paid, manual adjustment) with `reference` unique, `account_type` (user/role/platform), `direction`, `amount`, `source_type/id`, `rule_set_version`. Balances are sums, never columns. Corrections are reversal entries. `AMB-FIN-001` records that the contract is silent on **who holds funds**; the conservative model is adopted: platform collects, ledger records, period settlement pays out, and the platform take-rate defaults to **0%** until the client sets it.

### BR-FIN-02 — Settlement lifecycle `AGENT_DECISION`
`requested → validated → reserved → approved → batched → paid | rejected`. Rules (`AMB-FIN-002`): minimum request **1,000,000 Toman**; cadence **weekly** (admin-configurable); the amount is **reserved** at validation so it cannot be double-spent; a request is refused if any underlying sub-order is under a hold or has an open complaint; approval requires a second admin action when above a threshold (default 100,000,000 Toman) — separation of duties, even with a single admin role today; the bank file export must reconcile to the ledger to the Toman, and a batch is immutable once exported.

### BR-FIN-03 — Wallet rules `CONTRACT`
Wallet holds rewards, compensations and refunds; rewards are spendable only on a new purchase/invoice (page 6 / پیوست ۴ / بند ۷). Rules: wallet is **not** a bank account; withdrawal happens only through settlement (`BR-FIN-02`) and only for earned commercial balances (commission, settlement-eligible credits), never for unspent reward credit; every entry has an expiry policy (`permanent` or `expires_at`); negative balances are allowed only for reversal carry-forward (`BR-CMP-04`), never for spending.

### BR-FIN-04 — Commission vesting interacts with holds `AGENT_DECISION`
A sub-order under a hold does not vest commission; if the hold resolves in the supplier's favour, vesting resumes from the resolution date. This prevents a marketer being paid on money that may be refunded.

### BR-FIN-05 — Platform take-rate `AGENT_DECISION`
A per-sub-order platform commission may be configured (default **0%** in seed). It posts at capture, is held with the seller's payable, and nets out at settlement. Its existence is required for the contractor's 15% profit share in `ماده ۱۶` to be computable; its value is a client decision (`AMB-FIN-001`).

---

## 11. Users, admin, notifications

### BR-USR-01 — Five levels, many-to-many, policy-enforced `CONTRACT`
`store | marketer | consumer | supplier | admin`, implemented as roles with capabilities and a policy layer (canonical §1). Hiding a button is never authorization; every scoped resource has a negative test (`TECHNICAL_CONSTRAINT`).

### BR-USR-02 — Admin sub-scopes `AGENT_DECISION`
The contract has one admin level (page 6 / پیوست ۳ / ۳-۵) but requires separation-sensitive actions (settlement, deletion, approvals). Proposal (`AMB-USR-002`): admin capabilities are grouped into `admin.ops`, `admin.finance`, `admin.support`, `admin.auditor` (read-only); the seeded `super_admin` holds all. 2FA and audit are mandatory for every admin scope (page 5 / پیوست ۱ / بند ۷, بند ۱۰).

### BR-USR-03 — Secondary level `CONTRACT + AGENT_DECISION`
A user may hold a secondary level where admin enables it (page 9 / پیوست ۷ / بند ۶). The contract's only explicit combination rule is that a store **cannot** be a marketer; the consumer+marketer combination is explicitly allowed elsewhere (page 11 / پیوست ۱۲ / بند ۱۱). Full matrix and guards: [personas-and-roles.md §4](./personas-and-roles.md#4-secondary-level-matrix) (`AMB-USR-001`). Enabling a secondary level is audited and reversible.

### BR-USR-04 — Membership transfer `CONTRACT`
Selling or transferring a membership right is possible with edit and admin approval (page 8 / پیوست ۵ / بند ۳۹). Rule: the transfer moves the role and its obligations, never the history; points, stars and wallet stay with the original person unless admin explicitly moves them with a written reason; the transferee must pass identity verification; the transfer is audited.

### BR-USR-05 — Forced password change `CONTRACT`
Credentials issued by the system (marketer registration, admin reset) must be changed at first login (page 8 / پیوست ۵ / بند ۲; page 9 / پیوست ۶ / بند ۸). Until changed, the session can only reach the change-password screen.

### BR-ADM-01 — Approvals are evidence-based and reasoned `CONTRACT + AGENT_DECISION`
Admin approves users, store registrations, content, profile claims and banners (page 9 / پیوست ۷ / بند ۱, بند ۷, بند ۹). Rule: every decision stores actor, timestamp, before/after and a reason; rejections are visible to the submitter; default SLA **2 business days** (`AMB-ADM-001`); approvals of identity documents happen on private storage with signed URLs (canonical §5).

### BR-ADM-02 — Rules are versioned, never edited in place `TECHNICAL_CONSTRAINT`
Points coefficients, tier boundaries, reward thresholds, commission plans, consumer uplift, compensation rates and discount caps all live in `*_rule_sets` with `effective_from`/`effective_to` and a change reason. A computation always carries the version it used. Admin previews a rule change against a sample invoice before publishing (`UC-ADM-01`).

### BR-ADM-03 — Audit log on sensitive events `CONTRACT`
Login, price change, role change and settlement must be logged (page 5 / پیوست ۱ / بند ۱۰). Rule: `adm_audit_logs` via a shared trait; append-only; includes actor, IP, entity, before/after, reason, and correlation id; readable by `admin.auditor`.

### BR-ADM-04 — Excel export is a product feature, not an afterthought `CONTRACT`
Admin must export marketer/store/transaction/settlement reports (page 9 / پیوست ۷ / بند ۱۰) and a bank-ready group-payment file (page 11 / پیوست ۱۳ / بند ۳). Rule: exports are generated from the same query as the on-screen report, are stamped with the rule-set and data-as-of time, and are logged (who exported what, when).

### BR-ADM-05 — Inactive store downgrade `CONTRACT + AGENT_DECISION`
Stores inactive (no purchase, no invoice) in a defined window are automatically moved to inactive level and removed from listings (page 7 / پیوست ۴ / بند ۲۳), and the change is surfaced to admin for re-visit (بند ۲۴; page 9 / پیوست ۶ / بند ۴). Defaults (`AMB-ADM-002`): window **90 days**; downgrade after a 14-day warning; **never** while an open complaint or unpaid settlement exists; reversal on the first new paid invoice, restoring the previous level (but not the star count history, which is preserved anyway).

### BR-NTF-01 — Channel policy `CONTRACT`
Order/shipment/payment events go out on SMS, WhatsApp, email and push, and every channel must be switchable per user (page 8 / پیوست ۵ / بند ۲۳, بند ۲۶; page 9 / پیوست ۷ / بند ۳). Rule: transactional messages are never suppressed by marketing preferences; marketing messages require opt-in; every send is logged with provider, template, status.

### BR-NTF-02 — Paid notifications `CONTRACT + AGENT_DECISION`
A user or supplier may buy a notification: they enter count, text, image, link, pay the computed amount, and admin sends it after review (page 8 / پیوست ۵ / بند ۱۲; page 10 / پیوست ۱۱ / بند ۶–۷). Defaults (`AMB-NTF-001`): price per 1,000 recipients set by admin with a minimum purchase; audience caps per role; content review before send; failed sends refunded pro rata; a supplier cannot target a competitor's customer list — audience filters are limited to platform-wide, category, city and own-customer segments.

---

## 12. AGENT_DECISION register — numbers this document had to choose

Every row is a value the contract implies but does not state. Sign-off turns it into `OWNER_DECISION`; rejection changes only the value, not the mechanism.

| Rule | Value fixed here | Why this value | Ambiguity |
|---|---|---|---|
| `BR-LOY-01` | **1 point = 1,000 Toman**; points = floor(net × coef / 1,000) | makes "100 points = 1 star" and "4% at 1–100" reachable in 1–2 months of normal buying | `AMB-LOY-001` |
| `BR-PRI-03` | Volume bands **10M / 30M / 80M Toman**; coefficients 0.75 / 0.5 / 0.25 / 0.2% by ascending band | contract fixes the four coefficients and the descending direction, not the cut points | `AMB-PRI-003` |
| `BR-LOY-01` | Volume-discounted invoice → **0.2% (V3) / 0.1% (V4)** | contract gives the 0.2/0.1 pair without bands; mapping to the top two bands matches "the bigger the volume, the smaller the coefficient" | `AMB-LOY-001` |
| `BR-LOY-07` | Tiers **1–99 → 4%**, **100–200 → 5%**, **≥201 → fixed n** | resolves the contract's overlap at exactly 100 and "above 200" boundary | `AMB-LOY-004` |
| `BR-LOY-07` | Fixed **n = 250,000 Toman** | roughly the 5% tier on a mid-size store invoice; admin-editable | `AMB-LOY-005` |
| `BR-LOY-04` | Reward credit at **500 points**; validity **180 days** | 500 points ≈ 500,000 Toman, a meaningful but affordable reward; the 4× rule then needs a 2M invoice | `AMB-LOY-004` |
| `BR-LOY-03` | Half = **floor(reward/2)**, remainder **carries forward** | contract says points/rewards are preserved; forfeiture would contradict بند ۵ | `AMB-LOY-003` |
| `BR-LOY-05` | Inactivity: **−4 points / 30 days after 60 days** without a paid invoice; paused on open complaint | contract's own example is "four points per month" | `AMB-LOY-002` |
| `BR-LOY-06` | Stars from **purchase points only**, **no decay** | prevents referral farming from buying status; contract gives no decay rule | `AMB-LOY-006` |
| `BR-LOY-10` | Campaign windows **30 / 90 / 365 days** | contract names short/medium/long but no lengths | `AMB-LOY-007` |
| `BR-CRT-02` | **Total discount ≤ 30%** of net; 1 coupon + 1 voucher | without a cap, stacked 5% + reward + coupon can exceed margin | `AMB-LOY-008` |
| `BR-PRI-04` | Consumer **+35%** default | the contract's own example | `AMB-PRI-002` |
| `BR-ORD-05` | Delay compensation **0.5%/day, cap 20%**, min 1 day | mirrors the contract's own delay-penalty arithmetic; admin-approved per case | `AMB-ORD-004` |
| `BR-ORD-07` | Auto-confirm delivery **7 days**; review window **7 days** | contract fixes the 7-day review; confirmation needs a default | `AMB-ORD-007` |
| `BR-ORD-04` | Incomplete-order buyer window **72 h**; misuse **−10 points**, **3 upheld/90 days** triggers review | contract demands automatic negative points for repeated misuse, no values | `AMB-ORD-003` |
| `BR-ORD-03` | Hold auto-escalates at **14 days** | prevents indefinite payment freeze | `AMB-ORD-002` |
| `BR-ORD-09` | Violation visibility **60 days**; appeal **14 days** | contract says "e.g. last two months" | `AMB-ORD-006` |
| `BR-CMP-01` | Plan A **2%**, B **150,000/visit (cap 40/mo)**, C **5,000,000 + 1%**, D **12,000,000/mo** | contract names the four shapes and requires activity gating, no values | `AMB-CMP-001` |
| `BR-CMP-03` | Vesting **30 days after delivery** | ties commission to a return-free sale | `AMB-CMP-002` |
| `BR-MKT-05` | Referral **50 points**, credited on invitee's **first paid invoice** | contract says "on entry"; paying on entry invites farming — flagged as deviation | `AMB-MKT-001` |
| `BR-FIN-02` | Settlement minimum **1,000,000**; weekly; second approval **>100,000,000** | protects against churn and single-admin error | `AMB-FIN-002` |
| `BR-FIN-05` | Platform take-rate **0%** in seed | value is a commercial decision | `AMB-FIN-001` |
| `BR-ADM-05` | Inactive store window **90 days**, warning **14 days** | contract says "a defined period" only | `AMB-ADM-002` |
| `BR-ADM-01` | Approval SLA **2 business days** | contract gives no approval SLA | `AMB-ADM-001` |
| `BR-TND-01` | Sealed bids, **3 bidders** minimum, no fee | contract describes the form only | `AMB-TND-001` |
| `BR-CAT-03` | Classifieds **moderated**, **3 active ads**, **5 images** | contract only requires a taxonomy gate | `AMB-CLS-001` |
| `BR-PTG-01` | **No payment, no commission** in Patogh | contract says cost is "announced locally" | `AMB-PTG-001` |
| `BR-CRT-04` | Unpaid-balance window **7 days**, then release stock | contract requires deposit + settle, no expiry | `AMB-FIN-002` |
| `BR-PRI-02` | Re-price before payment **requires confirmation** | honest UX; silently charging a different amount is worse | `AMB-PRI-001` |
| `BR-NTF-02` | Paid notification minimum purchase + audience caps | contract requires a computed amount only | `AMB-NTF-001` |
| `BR-PRI-06` | Cost-floor tolerance **0%**, flag never blocks | honours mandatory discounts while surfacing margin leakage | `AMB-PRI-004` |

**Count:** 31 numeric or boundary values fixed by this document across **28 ambiguity ids** (`AMB-LOY-001…008`, `AMB-PRI-001…004`, `AMB-ORD-002…004`, `AMB-ORD-006…007`, `AMB-CMP-001…002`, `AMB-MKT-001`, `AMB-FIN-001…002`, `AMB-ADM-001…002`, `AMB-TND-001`, `AMB-CLS-001`, `AMB-PTG-001`, `AMB-NTF-001`); the remaining 40 rules are direct contract statements or technical constraints.

**Non-numeric ambiguities raised by this rulebook** (mechanism unclear, no number to fix — the rule states the default and the id is for client confirmation):

| Rule | Open point | Default applied | Ambiguity |
|---|---|---|---|
| `BR-ORD-02`, `BR-ORD-03` | Is "one week after dispatch" measured from dispatch or from delivery? | Dispatch timestamp (clause wording "پس از ارسال") | `AMB-ORD-001` |
| `BR-LOY-02` | Do points vest on invoice issuance or on payment? | On `paid` (money captured); manual invoices included | `AMB-LOY-009` |
| `BR-CAT-01` | May stores create catalogue SKUs, or only supplier/admin? | Stores publish profile content, classifieds, Patogh only | `AMB-CAT-001` |
| `BR-MKT-06` | Does transferring a store need the previous marketer's consent? | No; admin decides with a written reason | `AMB-MKT-002` |
| `BR-USR-03` | Which secondary-role combinations are legal? | Matrix in [personas §4](./personas-and-roles.md#4-secondary-level-matrix); store↔marketer blocked | `AMB-USR-001` |
| `BR-USR-02` | May admin be split into sub-scopes? | Yes: ops / finance / support / auditor, all with 2FA | `AMB-USR-002` |
| `BR-ORD-01` | Who owns an order an intermediary places for a consumer? | The consumer (price plane, points, reviews) | `AMB-ORD-005` |

---

## 13. Rule coverage of the contract's numeric claims

| Contract claim (verbatim gist) | Clause | Encoded in |
|---|---|---|
| Points coefficients 0.75 / 0.5 / 0.25 / 0.2 % | page 6 / پیوست ۴ / بند ۳ | `BR-LOY-01`, `BR-PRI-03` |
| Reward deducted next invoice only if invoice ≥ 4× reward, else half | page 6 / پیوست ۴ / بند ۴ | `BR-LOY-03` |
| All invoices earn points even if reward unclaimed | page 6 / پیوست ۴ / بند ۵ | `BR-LOY-02` |
| Inactivity: e.g. 4 points/month | page 6 / پیوست ۴ / بند ۶ | `BR-LOY-05` |
| Reward credited to wallet, for new purchase only, fixed-term or permanent | page 6 / پیوست ۴ / بند ۷ | `BR-LOY-04`, `BR-FIN-03` |
| Short/medium/long bonus windows admin-defined | page 6 / پیوست ۴ / بند ۸ | `BR-LOY-10` |
| 100 points = 1 star, unlimited stars | page 7 / پیوست ۴ / بند ۹ | `BR-LOY-06` |
| First/admin-chosen invoice discount in addition to reward | page 7 / پیوست ۴ / بند ۱۰ | `BR-LOY-09` |
| Volume invoices: higher cash discount, coefficient 0.75/0.5/0.25/0.2 | page 7 / پیوست ۴ / بند ۱۱ | `BR-PRI-03`, `BR-LOY-01` |
| Volume-discounted invoices: 0.2 / 0.1 % | page 7 / پیوست ۴ / بند ۱۲ | `BR-LOY-01` |
| Referral: new colleague via link → points to inviter | page 7 / پیوست ۴ / بند ۱۳ | `BR-MKT-05` |
| Delay compensation to colleague, %/amount approved by admin | page 7 / پیوست ۴ / بند ۱۴ | `BR-ORD-05` |
| Complaint + stop up to 1 week after dispatch, supplier payment held | page 7 / پیوست ۴ / بند ۱۵ | `BR-ORD-02`, `BR-ORD-03` |
| Stop button after invoice+payment for a set period; cancel+refund on supplier approval | page 7 / پیوست ۴ / بند ۱۶ | `BR-ORD-03` |
| Review window 1 week, 5–10 parameters | page 7 / پیوست ۴ / بند ۱۷ | `BR-ORD-07` |
| `وارن` button on every invoice | page 7 / پیوست ۴ / بند ۱۸ | `BR-ORD-09` |
| Violation report visible at checkout (e.g. last two months) | page 7 / پیوست ۴ / بند ۱۹ | `BR-ORD-09` |
| Tiered discount 1–100 → 4%, 100–200 → 5%, SMS notice | page 7 / پیوست ۴ / بند ۲۰ | `BR-LOY-07` |
| Fixed n Toman for >200 points | page 7 / پیوست ۴ / بند ۲۱ | `BR-LOY-07` |
| Disable marketer commission / change level | page 7 / پیوست ۴ / بند ۲۲ | `BR-CMP-01` |
| Inactive stores auto-downgraded and de-listed | page 7 / پیوست ۴ / بند ۲۳ | `BR-ADM-05` |
| Account changes surfaced for re-visit | page 7 / پیوست ۴ / بند ۲۴ | `BR-MKT-06` |
| QR/code/link binds a store to a marketer | page 7 / پیوست ۵ / بند ۱ | `BR-MKT-01` |
| On-site registration form, issued credentials, forced password change | page 7 / پیوست ۵ / بند ۲ | `BR-MKT-02`, `BR-USR-05` |
| Duplicate check on national ID / postal / mobile | page 7 / پیوست ۵ / بند ۳ | `BR-MKT-03` |
| Consumer price = colleague + %, e.g. 35% | page 11 / پیوست ۱۲ / بند ۱ | `BR-PRI-04` |
| Deposit + settle remainder on delivery | page 11 / پیوست ۱۲ / بند ۱۳ | `BR-CRT-04` |
| 12 gateways, two in one transaction | page 5 / پیوست ۱ / بند ۵ | `BR-PAY-01` (+ `RB-PAY-01`) |
| Excel export for group bank payment | page 11 / پیوست ۱۳ / بند ۳ | `BR-ADM-04`, `BR-FIN-02` |

---

## 14. Known conflicts and open questions

| # | Issue | Resolution taken | Owner |
|---|---|---|---|
| 1 | پیوست ۱ / بند ۵ requires **two gateways in one transaction**; canonical §5 defers split payment (`RB-PAY-01`). | Follow canonical; ship single-gateway v1, keep the UI copy free of dual-payment promises, log the deferral. | Orchestrator + client |
| 2 | پیوست ۷ / بند ۶ reads "a store **cannot** be a marketer" in one place while its own parenthesis says "per admin setting", i.e. it may be configurable. | Treat store↔marketer as **blocked**, everything else per the matrix; admin setting can only tighten, not loosen, a blocked pair. | Client (`AMB-USR-001`) |
| 3 | "امتیاز" is used both as a score and as a discount entitlement ("امتیاز تخفیف"). | Score = points; entitlement = discount credit with its own table; UI labels them differently (امتیاز vs تخفیف earned). | Product |
| 4 | Referral points are due "on entry" but the same annex penalises inactivity/fraud. | Credit on first paid invoice; flagged as deviation. | Client (`AMB-MKT-001`) |
| 5 | The contract names no **platform commission** yet `ماده ۱۶` splits "net profit". | 0% default, configurable; ledger ready. | Client (`AMB-FIN-001`) |
| 6 | Contract is silent on **who holds funds** before settlement. | Platform-collected, ledger-backed, period settlement. | Client (`AMB-FIN-001`) |
| 7 | Free-shipping campaigns and city-scoped pricing can make an order cheaper than its cost. | Admin must configure a margin floor; the pricing engine warns when a resolved price is below cost. | Client (`AMB-PRI-004`) |
| 8 | The consumer's **intermediary (رابط)** places orders on someone else's behalf, but the contract never says who owns the order, the points or any commission (page 11 / پیوست ۱۲ / بند ۳). | Order, points and price plane belong to the consumer; the intermediary earns nothing in v1. | Client (`AMB-ORD-005`) |
| 9 | The contract has one admin level but requires settlement execution, user deletion and audit reading — three capabilities that should not sit with one person (page 9 / پیوست ۷ / بند ۱–۲, بند ۱۰). | Admin sub-scopes seeded (`ops`, `finance`, `support`, `auditor`) with 2FA and audit on all of them. | Client (`AMB-USR-002`) |
