# Use-Case Catalogue — Dekolife

| | |
|---|---|
| **Purpose** | One entry per system-supported interaction, with actors, pre/postconditions, main and alternate flows — the unit QA turns into feature tests and UX turns into screens. |
| **Audience** | Engineering (service boundaries), QA (test design), UX (flow design), client (scope confirmation). |
| **Status** | Baseline v1 — 2026-11-25. Use-case ids `UC-<AREA>-<NN>` are **local to this document**; areas follow the canonical requirement areas. |
| **Coverage** | P0 areas in full (catalog, pricing, cart/checkout, order lifecycle, payments, loyalty, marketing/field ops, admin). P1 surfaces (tenders, classifieds, Patogh, samples, reviews) at flow depth sufficient to build. |

> **Source:** Contract `DK-1405-01` pages 5–11 and appendices 1–14. Every use case cites the clause that authorises it; steps that exist only to make the clause operable are marked `AGENT_DECISION` with the rule id that fixes them ([business-rules.md](./business-rules.md)). Journeys that chain these use cases are in [user-journeys.md](./user-journeys.md).

---

## Index

| Area | Use cases | Priority |
|---|---|---|
| Catalog | UC-CAT-01…05 | P0 (01–03), P1 (04–05) |
| Pricing | UC-PRI-01…03 | P0 |
| Cart & checkout | UC-CRT-01…03 | P0 |
| Orders | UC-ORD-01…08 | P0 |
| Payments | UC-PAY-01…02 | P0 |
| Loyalty | UC-LOY-01…04 | P0 |
| Marketing & field ops | UC-MKT-01…06 | P0 (01–03), P1 (04–06) |
| Admin | UC-ADM-01…03 | P0 |

---

## A. Catalog

### UC-CAT-01 — Browse and filter the catalogue
**Actors:** store, consumer, supplier, admin (view), visitor.
**Preconditions:** category tree exists; at least one published product with stock and a resolvable price.
**Postconditions:** filtered result set; no price is computed client-side.
**Main flow:** (1) user opens a category; (2) system lists published products visible to the user's role; (3) user filters by price, brand, stock, rating, discounted-only and sorts; (4) system returns paginated results with role-appropriate price; (5) user opens a product (`UC-CAT-02`).
**Alternates:** no results → suggestion of adjacent categories/brands; product unpublished mid-session → 404 with graceful return; role has no price plane for an item → item is hidden, not shown "price on request" (`BR-PRI-01`).
**Rules:** `BR-CAT-01`, `BR-PRI-01`. **Anchor:** page 5 / پیوست ۲ / بند ۳.

### UC-CAT-02 — Open a product and compare
**Actors:** store, consumer.
**Pre / Post:** catalogue is browsable / comparison set holds ≤5 products.
**Main flow:** (1) product page shows gallery, video, specs, buyer ratings, related items; (2) user adds to compare; (3) system enforces the 5-product cap; (4) comparison table aligns identical parameters; (5) user proceeds to cart or wishlist.
**Alternates:** 6th add → prompt to remove one; a parameter missing on one product → shown as "not specified", never as zero.
**Rules:** `BR-CAT-02`. **Anchor:** page 5 / پیوست ۲ / بند ۴–۵.

### UC-CAT-03 — Supplier publishes a product
**Actors:** supplier (primary), admin (override/approval).
**Pre:** supplier approved; category and attributes exist.
**Post:** product published with per-role prices, stock, optional product-level points rate.
**Main flow:** (1) supplier opens the product form; (2) enters title, short/full description, gallery, attributes, filters; (3) sets per-role price matrix and volume discount percentage; (4) sets stock and reorder point; (5) optionally sets a distinct points rate; (6) saves draft → publish; (7) system indexes for search/filters.
**Alternates:** missing required attribute → blocked with field-level errors; admin price override → supplier notified and cannot silently re-override (`BR-PRI-05`); duplicate SKU detected → merge suggestion.
**Rules:** `BR-CAT-01`, `BR-PRI-01`, `BR-PRI-05`. **Anchor:** page 9 / پیوست ۷ / بند ۴; page 11 / پیوست ۱۲ / بند ۶; page 11 / پیوست ۱۳ / بند ۴.

### UC-CAT-04 — Request a sample, work portfolio or catalogue *(P1)*
**Actors:** store, consumer, supplier.
**Main flow:** (1) buyer submits a request with description and dimensions; (2) request lands in the supplier's panel; (3) supplier responds with availability/dispatch; (4) buyer sees status.
**Alternates:** supplier silent → request expires after the configured window; sample shipped → recorded against the sub-order for traceability.
**Anchor:** page 6 / پیوست ۳ / ۳-۱ / ۹; page 8 / پیوست ۵ / بند ۳۸; page 10 / پیوست ۱۱ / بند ۹.

### UC-CAT-05 — Manage wishlist *(P1)*
**Actors:** store, consumer.
**Main flow:** (1) user saves a product, store or producer; (2) wishlist page lists saved items with current price; (3) price drop or restock triggers a notification (if enabled); (4) user removes or moves to cart.
**Anchor:** page 6 / پیوست ۳ / ۳-۳ / ۳; page 11 / پیوست ۱۲ / بند ۱۵.

---

## B. Pricing

### UC-PRI-01 — Resolve the price for a user and context
**Actors:** system (invoked by catalog, cart, invoice, API).
**Pre:** product has at least one price plane; user's roles are known.
**Post:** a price + an explanation trail (which plane, which band, which rules) is returned and, at order time, snapshotted.
**Main flow:** (1) system reads the user's active role for the context; (2) selects the price plane (colleague / consumer / wholesale); (3) applies the volume band if the quantity qualifies; (4) applies active promotions and coupons (`UC-CRT-01`); (5) applies loyalty entitlement (`UC-LOY-02`, `UC-LOY-03`); (6) returns the net price with a rule trail.
**Alternates:** no price for the role → item hidden (catalogue) or line blocked (cart); price changed between cart and payment → re-price and require confirmation (`BR-PRI-02`); admin manual override → takes precedence and is logged.
**Rules:** `BR-PRI-01`, `BR-PRI-02`, `BR-PRI-03`. **Anchor:** page 6 / پیوست ۳ / ۳-۱ / ۸; page 11 / پیوست ۱۲ / بند ۱.

### UC-PRI-02 — Configure a per-role price matrix
**Actors:** supplier (own products), admin (any product).
**Main flow:** (1) open the matrix; (2) enter colleague, wholesale bands, consumer price or inherit the platform uplift; (3) set validity dates; (4) preview the effect on 3 sample baskets; (5) publish → audit entry with before/after.
**Alternates:** consumer price below colleague → blocked (guard); overlapping validity → latest `effective_from` wins and the conflict is reported.
**Rules:** `BR-PRI-04`, `BR-PRI-05`. **Anchor:** page 9 / پیوست ۷ / بند ۴; page 11 / پیوست ۱۲ / بند ۱.

### UC-PRI-03 — Apply a volume band and cash discount
**Actors:** system, store, supplier (definition).
**Main flow:** (1) cart total/quantity crosses a band; (2) system applies the wholesale price for qualifying lines; (3) applies the volume cash discount defined on the product; (4) marks the invoice as volume-discounted; (5) downstream, the points coefficient switches to the volume set (`BR-LOY-01`).
**Alternates:** band reached only via a line that is later removed → discount withdrawn and the user is told; stacking with a coupon → allowed unless the promotion forbids it (`BR-CRT-02`).
**Rules:** `BR-PRI-03`, `BR-LOY-08`. **Anchor:** page 7 / پیوست ۴ / بند ۱۱–۱۲; page 11 / پیوست ۱۲ / بند ۶.

---

## C. Cart and checkout

### UC-CRT-01 — Build a cart and apply a discount instrument
**Actors:** store, consumer.
**Main flow:** (1) add lines (quantity, variant); (2) system re-resolves prices server-side; (3) user applies a coupon code or a granted voucher (بن); (4) system validates scope, window, minimum and stacking; (5) cart shows subtotal, discount, shipping, payable.
**Alternates:** expired/ineligible code → explicit reason, cart unchanged; two instruments that may not stack → the higher-value one is proposed, not silently chosen; stock shortfall → line flagged (`UC-CRT-03`).
**Rules:** `BR-CRT-01`, `BR-CRT-02`. **Anchor:** page 5 / پیوست ۲ / بند ۶; page 9 / پیوست ۵ / بند ۴۳; page 11 / پیوست ۱۲ / بند ۴.

### UC-CRT-02 — Checkout with shipping choice and deposit
**Actors:** consumer, store.
**Main flow:** (1) choose address; (2) choose shipping type (post/courier/pickup) and see the fee, or free shipping if a city/nationwide campaign applies; (3) choose deposit (بیعانه) or full payment; (4) review pre-invoice; (5) select gateway (`UC-PAY-01`); (6) confirm.
**Alternates:** address outside a free-shipping zone → fee recomputed; deposit below the configured minimum → blocked; seller cannot ship to the city → checkout warns before payment.
**Rules:** `BR-CRT-03`, `BR-CRT-04`. **Anchor:** page 11 / پیوست ۱۲ / بند ۱۲–۱۳; page 8 / پیوست ۵ / بند ۴۰, بند ۴۲.

### UC-CRT-03 — Handle an unavailable line
**Actors:** system, buyer, supplier.
**Main flow:** (1) at payment time a line is out of stock or withdrawn; (2) system splits the cart: payable lines proceed, the rest is parked; (3) buyer is told and may remove, substitute or wait; (4) parked lines never block the rest of the order.
**Alternates:** stock disappears after payment → supplier uses incomplete-order (`UC-ORD-04`) or cancels with refund (`UC-ORD-05`).
**Rules:** `BR-CRT-01`, `BR-ORD-04`. **Anchor:** page 10 / پیوست ۱۱ / بند ۱۲.

---

## D. Order lifecycle

### UC-ORD-01 — Place an order (multi-seller split)
**Actors:** buyer (store/consumer), system.
**Pre:** cart valid; payment captured or deposit captured.
**Post:** one parent order, N sub-orders (one per seller), N invoices, tracking code issued.
**Main flow:** (1) system validates cart snapshot; (2) creates the parent order; (3) creates one sub-order per seller with its own lines, prices and shipping; (4) issues invoices; (5) starts the fulfilment clock per sub-order; (6) notifies buyer and sellers; (7) attributes each sub-order to the owning marketer for commission.
**Alternates:** partial payment failure → order stays `pending_payment` with a retry window; a seller becomes inactive between cart and order → that sub-order is not created, buyer informed.
**Rules:** `BR-ORD-01`, `BR-INV-01`, `BR-CMP-02`. **Anchor:** page 6 / پیوست ۳ / ۳-۱ / ۱, بند ۷.

### UC-ORD-02 — Supplier accepts, packs and dispatches
**Actors:** supplier.
**Main flow:** (1) sub-order appears in the queue with SLA; (2) supplier accepts; (3) picks/packs, sets quantities actually shipped; (4) records waybill + courier; (5) marks dispatched → status notifications fire; (6) buyer confirms receipt (or auto-confirm after the configured window).
**Alternates:** supplier cannot fulfil → `UC-ORD-04`; dispatch late → `UC-ORD-07`; partial dispatch → sub-order splits into shipped + backorder lines.
**Rules:** `BR-ORD-01`, `BR-ORD-05`. **Anchor:** page 10 / پیوست ۱۱ / بند ۱۵; page 8 / پیوست ۵ / بند ۲۴, بند ۴۰.

### UC-ORD-03 — File a complaint and freeze the order
**Actors:** store/consumer, supplier, admin.
**Pre:** sub-order dispatched and within the complaint window (1 week).
**Post:** sub-order held; supplier payment suspended until adjudication.
**Main flow:** see [J-06](./user-journeys.md#j-06-buyer-files-a-complaint-and-freezes-the-order-شکایت). Alternates: window expired → ticket-only path; supplier non-responsive → admin decides on evidence.
**Rules:** `BR-ORD-02`, `BR-ORD-03`. **Anchor:** page 7 / پیوست ۴ / بند ۱۵–۱۶.

### UC-ORD-04 — Supplier marks the order incomplete
**Actors:** supplier, buyer, admin (oversight).
**Main flow:** (1) supplier selects a reason (discontinued, damaged stock, quantity short, price error); (2) system moves the sub-order to `incomplete_pending_buyer` and **suspends delay compensation**; (3) buyer chooses substitute / amendment / cancel; (4) resolution recorded; (5) repeat misuse accrues negative points.
**Alternates:** buyer silent past the window → auto-cancel + refund; admin may reject an incomplete claim that looks like delay evasion and reinstate compensation.
**Rules:** `BR-ORD-04`. **Anchor:** page 10 / پیوست ۱۱ / بند ۱۲–۱۳; page 8 / پیوست ۵ / بند ۱۵.

### UC-ORD-05 — Cancel a sub-order and refund
**Actors:** supplier or admin (initiate), buyer (recipient), Payments/Finance.
**Main flow:** (1) cancellation requested with reason; (2) system checks whether goods shipped; (3) refund computed (full or partial, minus non-refundable shipping if applicable); (4) refund to SHEBA or wallet per buyer choice; (5) invoice voided/credited; (6) points and tier discount clawed back.
**Alternates:** already-shipped goods → must go through returns (`UC-ORD-08`); gateway refund fails → reconciliation case (`UC-PAY-02`).
**Rules:** `BR-ORD-06`, `BR-LOY-11`, `BR-PAY-02`. **Anchor:** page 8 / پیوست ۵ / بند ۱۵; page 10 / پیوست ۱۱ / بند ۱۴.

### UC-ORD-06 — Issue a supplementary invoice
**Actors:** supplier, buyer, admin.
**Main flow:** (1) supplier records an extra cost above the original invoice (crating, extra transport, re-measure); (2) attaches evidence; (3) system issues a supplementary invoice linked to the parent invoice; (4) buyer pays or disputes; (5) admin sees both linked.
**Alternates:** buyer rejects → dispute path with the original invoice held; supplementary invoice after settlement → carried into the next period.
**Rules:** `BR-ORD-07`. **Anchor:** page 11 / پیوست ۱۱ / بند ۱۷.

### UC-ORD-07 — Record a delay and pay compensation
**Actors:** supplier, system, admin, buyer.
**Main flow:** see [J-07](./user-journeys.md#j-07-delay-compensation-reaches-the-buyers-wallet-خسارت-تأخیر). Alternates: supplier disputes → admin decides; force-majeure style justification → admin may waive.
**Rules:** `BR-ORD-05`, `BR-FIN-03`. **Anchor:** page 7 / پیوست ۴ / بند ۱۴; page 10 / پیوست ۱۱ / بند ۱۵; page 11 / پیوست ۱۲ / بند ۸.

### UC-ORD-08 — Review, rate, and report a violation *(P1 for returns)*
**Actors:** buyer, admin, supplier.
**Main flow:** (1) within 1 week of receipt the buyer rates product and supplier on 5–10 parameters; (2) optional violation report (`وارن`) with evidence; (3) admin adjudicates; (4) upheld violations become visible to peers for the configured period; (5) returns requested for eligible items follow the return window.
**Alternates:** review after the window → blocked with explanation; duplicate review per invoice line → rejected.
**Rules:** `BR-ORD-08`, `BR-ORD-09`. **Anchor:** page 7 / پیوست ۴ / بند ۱۷–۱۹; page 11 / پیوست ۱۲ / بند ۹, بند ۱۴.

---

## E. Payments

### UC-PAY-01 — Pay through a gateway
**Actors:** buyer, Payments, gateway, Finance.
**Pre:** payable amount > 0; at least one gateway active.
**Post:** captured transaction with unique reference; ledger entry; order advanced.
**Main flow:** (1) buyer selects an active gateway (or the admin-default one); (2) system creates a payment intent with an idempotency key; (3) redirects to the gateway; (4) verifies the callback signature and amount; (5) records capture, emits `PaymentCaptured`; (6) shows receipt.
**Alternates:** user abandons → intent expires, order stays payable; callback amount mismatch → payment flagged, order not advanced; duplicate callback → idempotent no-op; gateway down → other active gateways offered.
**Rules:** `BR-PAY-01`, `BR-PAY-02`. **Anchor:** page 5 / پیوست ۱ / بند ۵; page 11 / پیوست ۱۳ / بند ۷.

### UC-PAY-02 — Reconcile gateway transactions
**Actors:** admin (finance), Payments, Reporting.
**Main flow:** (1) nightly import of gateway settlement files; (2) match by reference and amount; (3) unmatched items become reconciliation cases (failed, returned, amount mismatch); (4) admin resolves with a written reason; (5) adjustments are posted as ledger entries, never edits.
**Alternates:** gateway file late → case stays open and is reported; systematic mismatch → gateway disabled pending fix.
**Rules:** `BR-PAY-03`, `BR-FIN-01`. **Anchor:** page 11 / پیوست ۱۳ / بند ۶.

---

## F. Loyalty

### UC-LOY-01 — Award points on an invoice
**Actors:** system, store, consumer.
**Main flow:** (1) invoice issued; (2) per line, points = line net value × product coefficient × volume coefficient; (3) sum to invoice points; (4) post to the points ledger with the rule-set version; (5) recompute stars and tier.
**Alternates:** invoice voided → reversal entry (`BR-LOY-11`); product excluded from points → zero coefficient with a reason; rule set changed later → historical entries keep their version.
**Rules:** `BR-LOY-01`, `BR-LOY-02`, `BR-LOY-06`, `BR-LOY-08`. **Anchor:** page 6 / پیوست ۴ / بند ۱–۳, بند ۵; page 7 / پیوست ۴ / بند ۹.

### UC-LOY-02 — Redeem a reward on the next invoice
**Actors:** system, buyer.
**Main flow:** (1) system reads available reward balance; (2) compares next invoice net value to 4× the reward; (3) if ≥4× → deduct the full reward; (4) else deduct exactly half; (5) record the remainder per policy (carry or lapse); (6) show the arithmetic on the pre-invoice.
**Alternates:** reward expired → not offered; reward > invoice value → half-deduction only; buyer removes items until the invoice falls below the threshold → recompute to the half path.
**Rules:** `BR-LOY-03`, `BR-LOY-04`. **Anchor:** page 6 / پیوست ۴ / بند ۴, بند ۷.

### UC-LOY-03 — Apply the tiered discount
**Actors:** system, buyer.
**Main flow:** (1) read the buyer's current tier from points; (2) 1–100 → 4%, 100–200 → 5%, >200 → fixed n Toman; (3) apply to the next invoice automatically; (4) send an SMS announcing the percentage; (5) record the entitlement and its expiry.
**Alternates:** tier drops after inactivity deduction → next invoice uses the lower tier and the buyer is told; two entitlements overlap → the higher applies once, no stacking.
**Rules:** `BR-LOY-05`, `BR-LOY-07`. **Anchor:** page 7 / پیوست ۴ / بند ۲۰–۲۱; page 8 / پیوست ۵ / بند ۱۶.

### UC-LOY-04 — Maintain stars and inactivity
**Actors:** system, admin (configuration), buyer.
**Main flow:** (1) nightly job computes stars = floor(points/100), unlimited; (2) applies inactivity deduction after the configured window without invoice (e.g. 4 points/month); (3) recomputes tier; (4) notifies the buyer before the first deduction.
**Alternates:** buyer has an open complaint → inactivity clock pauses (`BR-LOY-05`); admin manual adjustment → ledger entry with reason, audited.
**Rules:** `BR-LOY-05`, `BR-LOY-06`. **Anchor:** page 6 / پیوست ۴ / بند ۶; page 7 / پیوست ۴ / بند ۹, بند ۱۷; page 8 / پیوست ۵ / بند ۱۷.

---

## G. Marketing and field operations

### UC-MKT-01 — Register a store on-site with QR attribution
**Actors:** marketer, store owner, system.
**Flow:** see [J-04](./user-journeys.md#j-04-marketer-registers-a-store-door-to-door-with-qr-بازاریاب). Key rules: unique attribution by code/QR, duplicate blocking on national ID/postal/mobile, 5-minute edit window.
**Rules:** `BR-MKT-01`, `BR-MKT-02`, `BR-MKT-03`, `BR-MKT-04`. **Anchor:** page 7 / پیوست ۵ / بند ۱–۳; page 9 / پیوست ۶ / بند ۱–۲, بند ۹.

### UC-MKT-02 — Approve or reject a store
**Actors:** admin, marketer.
**Flow:** see [J-05](./user-journeys.md#j-05-admin-approves-a-store-ادمین).
**Rules:** `BR-ADM-01`, `BR-ADM-03`. **Anchor:** page 9 / پیوست ۷ / بند ۱.

### UC-MKT-03 — Track visits and performance
**Actors:** marketer, admin.
**Main flow:** (1) marketer logs a visit with geolocation and photos; (2) system stores the point and the visit type (first visit / re-visit / presentation); (3) dashboard shows coverage, conversion to first invoice, commission accrual; (4) admin sees live map and per-marketer analysis, scoped.
**Alternates:** location permission denied → visit recorded as unverified and flagged; marketer edits a past visit → only within the window, else admin approval.
**Rules:** `BR-MKT-07`. **Anchor:** page 11 / پیوست ۱۴ / بند ۱–۲; page 9 / پیوست ۶ / بند ۴, بند ۱۰–۱۱.

### UC-MKT-04 — Post a classified ad *(P1)*
**Actors:** any authenticated user, admin.
**Flow:** see [J-10](./user-journeys.md#j-10-classified-ad-posted-on-دیوار-آگهی).
**Rules:** `BR-CAT-03`, `BR-ADM-01`. **Anchor:** page 7 / پیوست ۵ / بند ۴.

### UC-MKT-05 — Request a local service in Patogh *(P1)*
**Actors:** consumer, provider, admin.
**Flow:** see [J-11](./user-journeys.md#j-11-patogh-service-request-find-an-installer-پتوق).
**Rules:** `BR-PTG-01`. **Anchor:** page 7 / پیوست ۵ / بند ۵; page 8 / پیوست ۵ / بند ۱۳, بند ۳۵.

### UC-MKT-06 — Bid on a tender *(P1)*
**Actors:** supplier, buyer, admin.
**Flow:** see [J-09](./user-journeys.md#j-09-tender-buyer-publishes-suppliers-bid-مناقصه).
**Rules:** `BR-TND-01`, `BR-TND-02`. **Anchor:** page 7 / پیوست ۵ / بند ۶; page 8 / پیوست ۵ / بند ۳۶–۳۷; page 10 / پیوست ۱۱ / بند ۱۰.

---

## H. Admin

### UC-ADM-01 — Configure a rule set (points, tiers, commission, consumer uplift)
**Actors:** admin.
**Pre:** admin authenticated with 2FA.
**Post:** a new **versioned** rule set with `effective_from`; historical computations unaffected.
**Main flow:** (1) open the rule-set editor; (2) change coefficients/bands/thresholds (e.g. 0.75/0.5/0.25/0.2%, tier boundaries, 4× rule, n Toman, plan A–D values, consumer uplift); (3) preview against a sample invoice; (4) save as a new version with a reason; (5) audit entry; (6) replay command verifies reproducibility.
**Alternates:** invalid combination (e.g. tier overlap, negative coefficient) → blocked; urgent change mid-period → allowed with `effective_from = now` and a note that open invoices keep their snapshot.
**Rules:** `BR-ADM-02`, `BR-ADM-04`, `BR-LOY-01`, `BR-CMP-01`. **Anchor:** page 7 / پیوست ۴ / بند ۳, بند ۲۰–۲۱; page 9 / پیوست ۶ / بند ۵–۶.

### UC-ADM-02 — Run settlement and export the bank file
**Actors:** admin (finance).
**Flow:** see [J-12](./user-journeys.md#j-12-wallet-settlement-request--paid-تسویه). Export must match the ledger to the Toman.
**Rules:** `BR-FIN-01`, `BR-FIN-02`, `BR-ADM-05`. **Anchor:** page 9 / پیوست ۷ / بند ۲, بند ۱۰; page 11 / پیوست ۱۳ / بند ۱–۳, بند ۵.

### UC-ADM-03 — Compose and send a custom or paid notification
**Actors:** admin, supplier (buyer of the notification), system.
**Main flow:** (1) requester enters audience, count, text, image, link, schedule; (2) system computes price for a paid notification; (3) requester pays (or the send is free per admin config); (4) admin reviews; (5) send is executed across the chosen channels; (6) delivery report recorded.
**Alternates:** audience exceeds the admin cap → capped with a warning; requester is a supplier without payment → held; content rejected → refunded or returned for edit.
**Rules:** `BR-NTF-01`, `BR-NTF-02`. **Anchor:** page 7 / پیوست ۵ / بند ۹; page 8 / پیوست ۵ / بند ۱۲; page 10 / پیوست ۱۱ / بند ۶–۷.

---

## Traceability summary

| Area | Use cases | Rules | Contract anchors |
|---|---|---|---|
| Catalog | 01–05 | `BR-CAT-01..03` | پیوست ۲ / ۳–۵; پیوست ۷ / ۴; پیوست ۱۲ / ۶ |
| Pricing | 01–03 | `BR-PRI-01..05` | پیوست ۳ / ۳-۱ / ۸; پیوست ۷ / ۴; پیوست ۱۲ / ۱ |
| Cart | 01–03 | `BR-CRT-01..04` | پیوست ۲ / ۶; پیوست ۵ / ۴۰, ۴۲, ۴۳; پیوست ۱۲ / ۱۲–۱۳ |
| Orders | 01–08 | `BR-ORD-01..09`, `BR-INV-01` | پیوست ۴ / ۱۴–۱۹; پیوست ۵ / ۱۵, ۲۴; پیوست ۱۱ / ۱۲–۱۵, ۱۷ |
| Payments | 01–02 | `BR-PAY-01..03` | پیوست ۱ / ۵; پیوست ۱۳ / ۶–۷ |
| Loyalty | 01–04 | `BR-LOY-01..11` | پیوست ۴ / ۱–۲۱; پیوست ۵ / ۱۶–۱۷; پیوست ۱۲ / ۱۰ |
| Marketing | 01–06 | `BR-MKT-01..07`, `BR-CMP-01..05` | پیوست ۳ / ۳-۲; پیوست ۵ / ۱–۶; پیوست ۶; پیوست ۱۴ |
| Admin | 01–03 | `BR-ADM-01..05`, `BR-NTF-01..02` | پیوست ۷ / ۱–۱۰; پیوست ۱۳ |
