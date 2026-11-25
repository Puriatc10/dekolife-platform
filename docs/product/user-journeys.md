# User Journeys — Dekolife

| | |
|---|---|
| **Purpose** | Trace each end-to-end path a real user takes, step by step, so no screen, notification, money movement or approval is left unowned. Journeys are the acceptance-test spine. |
| **Audience** | UX, engineering, QA (each step becomes a test), client operations for sign-off. |
| **Status** | Baseline v1 — 2026-11-25. Journey ids `J-01…J-14` are **local to this document**. Rule ids (`BR-*`) resolve in [business-rules.md](./business-rules.md); ambiguity ids (`AMB-*`) are raised there. |
| **Reading the tables** | "Module" names are the canonical bounded contexts from [`../canonical-context.md`](../canonical-context.md) §3. "Anchor" is the contract clause that authorises the step; steps with **no anchor** are marked `AGENT_DECISION` and carry the rule id that fixes them. |

> **Source:** Contract `DK-1405-01`, pages 5–11 + appendices 1–14.

---

## J-01 Consumer buys flooring end-to-end (مصرفکننده)

**Trigger:** سارا needs 90 m² of parquet plus skirting for a renovation. **Actors:** consumer, system, payment gateway, supplier, courier.
**Preconditions:** consumer registered and SMS-verified; at least one supplier has stock and a consumer price.

| # | Actor | Module | Step | Anchor |
|---|---|---|---|---|
| 1 | Consumer | Cms / Catalog | Lands on home: search bar, categories, banner slider, special offers, best sellers, newest | page 5 / پیوست ۲ / بند ۲ |
| 2 | Consumer | Catalog | Filters the category by price, brand, stock, rating, discounted-only; sorts as desired | page 5 / پیوست ۲ / بند ۳ |
| 3 | Consumer | Catalog | Opens product: gallery, video, specs, buyer ratings, related items; adds to compare (≤5) | page 5 / پیوست ۲ / بند ۴–۵ |
| 4 | System | Pricing | Resolves **consumer price** (colleague price × configured uplift, default +35%) server-side; no price is trusted from the client | page 11 / پیوست ۱۲ / بند ۱; `BR-PRI-02`, `BR-PRI-04`, `AMB-PRI-002` |
| 5 | Consumer | Cart | Adds to cart, enters discount code / applies voucher if held; shipping is computed and a shipping type chosen | page 5 / پیوست ۲ / بند ۶; page 11 / پیوست ۱۲ / بند ۱۲ |
| 6 | System | Promotions / Shipping | Validates coupon rules and free-shipping eligibility (city-wide or nationwide campaign) | page 11 / پیوست ۱۲ / بند ۱۲; `BR-CRT-02`, `BR-CRT-03` |
| 7 | Consumer | Invoices | Sees a **pre-invoice** stating role-based price; chooses deposit (بیعانه) or full payment | page 8 / پیوست ۵ / بند ۴۲; page 11 / پیوست ۱۲ / بند ۱۳ |
| 8 | Consumer | Payments | Picks one of the active gateways and pays | page 5 / پیوست ۱ / بند ۵; `BR-PAY-01` |
| 9 | System | Payments | Verifies the callback, records the transaction idempotently, emits `PaymentCaptured` | `BR-PAY-02` |
| 10 | System | Orders / Invoices | Creates parent order + one sub-order per seller; issues invoice(s); returns tracking code | page 5 / پیوست ۲ / بند ۱۱ |
| 11 | System | Notifications | Sends order-placed and payment-received messages on SMS/WhatsApp/email/push per preference | page 9 / پیوست ۷ / بند ۳; page 8 / پیوست ۵ / بند ۲۶, بند ۲۳ |
| 12 | Supplier | Orders | Accepts, packs, dispatches with waybill + courier; if the balance was a deposit, it is collected on delivery | page 11 / پیوست ۱۲ / بند ۱۳; page 10 / پیوست ۱۱ / بند ۱۵ |
| 13 | Consumer | Orders | Tracks status by code; chats in the order thread (text/image/audio/file) if something is wrong | page 5 / پیوست ۲ / بند ۱۱; page 8 / پیوست ۵ / بند ۲۸ |
| 14 | Consumer | Reviews / Loyalty | Rates product and supplier (1 week window, 5–10 parameters); points land; stars update | page 7 / پیوست ۴ / بند ۹, بند ۱۷; page 11 / پیوست ۱۲ / بند ۹ |
| 15 | Consumer | Patogh | If installation is needed, posts a Patogh request and receives local cost quotes | page 7 / پیوست ۵ / بند ۵ |

**Postconditions:** invoice issued; points credited (`BR-LOY-01`); consumer price never visible to the supplier's other buyers; delivery/return windows open.
**Exceptions:** gateway failure → order stays `pending_payment`, retry allowed (`BR-PAY-02`); out-of-stock after payment → supplier cancels with refund to SHEBA (`J-13`); price changed mid-session → cart re-prices and warns before payment (`BR-PRI-02`).

---

## J-02 Store reorders wholesale stock (فروشگاه / همکار)

**Trigger:** رضا needs to restock last month's pallet before the weekend. **Actors:** store, system, supplier.
**Preconditions:** store approved and `is_colleague = true`; colleague price exists for the SKUs.

| # | Actor | Module | Step | Anchor |
|---|---|---|---|---|
| 1 | Store | Identity | Logs into the store panel (username/password issued at registration; forced change at first login) | page 8 / پیوست ۵ / بند ۲; page 9 / پیوست ۶ / بند ۸; `BR-USR-05` |
| 2 | Store | Pricing | Sees **colleague price** and the **wholesale/volume price** for his role — not the consumer price | page 6 / پیوست ۳ / ۳-۱ / ۸; `BR-PRI-01` |
| 3 | Store | Catalog | Opens "reorder" from a past invoice; quantities prefilled | `AGENT_DECISION` — `BR-ORD-01` (reorder surface) |
| 4 | System | Pricing | Applies the volume band; if the basket crosses the threshold, the wholesale price and volume cash discount apply | page 7 / پیوست ۴ / بند ۱۱; `BR-PRI-03`, `BR-PRI-05` |
| 5 | System | Loyalty | Applies the store's tier discount on this invoice (4% / 5% / fixed n Toman) if earned on the previous invoice | page 7 / پیوست ۴ / بند ۲۰–۲۱; `BR-LOY-07` |
| 6 | System | Loyalty | Shows the reward available and how much will be deducted given the 4× rule | page 6 / پیوست ۴ / بند ۴; `BR-LOY-03` |
| 7 | Store | Cart / Invoices | Reviews pre-invoice: lines, discount, shipping, payable; confirms | page 8 / پیوست ۵ / بند ۴۲ |
| 8 | Store | Payments | Pays by gateway (or on credit terms if the supplier allows — `AMB-FIN-002`) | page 5 / پیوست ۱ / بند ۵ |
| 9 | System | Orders / Marketing | Creates sub-orders; attributes the sale to the marketer who owns the store | page 7 / پیوست ۵ / بند ۱; `BR-CMP-02` |
| 10 | Supplier | Orders | Fulfils; may record a supplementary cost with a **supplementary invoice** (e.g. crating) | page 11 / پیوست ۱۱ / بند ۱۷; `BR-ORD-07` |
| 11 | System | Loyalty | On issuance, credits points using the coefficient set for a volume-discounted invoice (0.2%/0.1%) if a volume discount was granted | page 7 / پیوست ۴ / بند ۱۲; `BR-LOY-01` |
| 12 | Store | Finance | Sees the new balance, points, and star count in the panel | page 5 / پیوست ۲ / بند ۷; page 7 / پیوست ۴ / بند ۹ |
| 13 | Store | Finance | Later requests settlement of any credit (e.g. compensation or overpayment) with statement evidence | page 6 / پیوست ۳ / ۳-۱ / ۴; `J-12` |

**Postconditions:** invoice + points + commission recorded; tier discount consumed; the store's next-invoice reward updated.
**Exceptions:** supplier marks the order **incomplete** (`سفارش ناقص`) → back to the buyer for correction, delay penalty suspended (`BR-ORD-04`); store disputes → complaint within 7 days freezes preparation and supplier payment (`J-06`).

---

## J-03 Supplier onboards and publishes a product (تأمینکننده / تولیدکننده)

**Trigger:** خانم اکبری signs the reseller agreement and wants her first 40 SKUs live. **Actors:** supplier, admin, system.

| # | Actor | Module | Step | Anchor |
|---|---|---|---|---|
| 1 | Supplier | Identity | Registers; submits company identity documents (private storage, admin-visible only) | page 9 / پیوست ۷ / بند ۱; `BR-ADM-01` |
| 2 | Admin | Admin | Reviews documents, approves the supplier and the role | page 6 / پیوست ۳ / ۳-۵; page 9 / پیوست ۷ / بند ۱ |
| 3 | Supplier | Merchants | Completes the brand page: description, services, map location, facilities, company history, images, video | page 10 / پیوست ۱۱ / بند ۳ |
| 4 | Supplier | Merchants | Lists nationwide agencies with location and contact so buyers can be referred for measurement/installation | page 11 / پیوست ۱۱ / بند ۱۹ |
| 5 | Supplier | Catalog | Creates categories/attributes as allowed, then the product: title, short and full description, gallery, attributes, filters, stock | page 9 / پیوست ۷ / بند ۴ |
| 6 | Supplier | Pricing | Sets the **price per role** in one matrix: colleague, wholesale (volume bands), consumer (or inherit the platform uplift) | page 9 / پیوست ۷ / بند ۴; page 11 / پیوست ۱۲ / بند ۱; `BR-PRI-01`, `BR-PRI-05` |
| 7 | Supplier | Pricing | Defines volume discount percentage in the product's introduction (shown to buyers) | page 11 / پیوست ۱۲ / بند ۶ |
| 8 | Supplier | Inventory | Sets stock; a low-stock alert is raised | page 10 / پیوست ۱۱ / بند ۱ |
| 9 | Admin | Admin | Optionally prices the item itself (admin may override per role) and publishes | page 9 / پیوست ۷ / بند ۴ |
| 10 | System | Catalog | Indexes the product; it appears in category filters, search and newest list | page 5 / پیوست ۲ / بند ۲–۳ |
| 11 | Supplier | Loyalty | Optionally sets a distinct **points rate** for this product/producer | page 11 / پیوست ۱۳ / بند ۴; page 8 / پیوست ۵ / بند ۲۲ |
| 12 | Supplier | Promotions | Optionally schedules a festival entry, seasonal slideshow, banner request or paid notification | page 10 / پیوست ۱۱ / بند ۴–۸ |
| 13 | Supplier | Samples | Receives sample/work-portfolio requests routed to its panel | page 10 / پیوست ۱۱ / بند ۹ |

**Postconditions:** product sellable at three price planes; stock and points config in place; the supplier appears in the agency directory.
**Exceptions:** admin rejects identity → role stays `pending`, product drafts remain hidden (`BR-ADM-01`); duplicate SKU → merge suggestion, never silent overwrite.

---

## J-04 Marketer registers a store door-to-door with QR (بازاریاب)

**Trigger:** مهدی is standing in a shop that has never heard of Dekolife. **Actors:** marketer, store owner, system, admin.
**Preconditions:** marketer approved and active; his personal QR/code exists.

| # | Actor | Module | Step | Anchor |
|---|---|---|---|---|
| 1 | Marketer | FieldOps | Opens the app at the shop; geolocation is captured for the visit | page 11 / پیوست ۱۴ / بند ۱ |
| 2 | Marketer | Marketing | Presents his **personal QR** (also on his card and shareable link) | page 7 / پیوست ۵ / بند ۱ |
| 3 | Store owner | Marketing | Scans the QR → registration is bound to that marketer | page 7 / پیوست ۵ / بند ۱; `BR-MKT-01` |
| 4 | Marketer | Marketing | Alternatively registers on the spot using the **separate on-site form** (not the public signup) | page 7 / پیوست ۵ / بند ۲; page 9 / پیوست ۶ / بند ۱ |
| 5 | Marketer | Marketing | Enters: address, store name, owner name, postal code, national ID, phones (with/without WhatsApp), store services, category/class, city, province | page 9 / پیوست ۶ / بند ۱ |
| 6 | Marketer | Marketing | Uploads photos: owner, storefront, business card; pins the map location | page 9 / پیوست ۶ / بند ۲, بند ۷ |
| 7 | System | Marketing | Checks national ID + postal code + mobile against existing stores to block duplicates | page 7 / پیوست ۵ / بند ۳; `BR-MKT-03` |
| 8 | System | Identity | Creates the account, sends username/password plus the app install link | page 9 / پیوست ۶ / بند ۸; page 7 / پیوست ۵ / بند ۲ |
| 9 | Marketer | Marketing | May correct the form only within the first **5 minutes**; afterwards edits/deletes need admin approval | page 9 / پیوست ۶ / بند ۹; `BR-MKT-04` |
| 10 | Admin | Admin | Sees the store in the approvals queue with photos, pin and the owning marketer | page 9 / پیوست ۷ / بند ۱; `J-05` |
| 11 | System | Marketing / Compensation | On approval, the store is attributed to the marketer for commission on future invoices | `BR-CMP-02`, `AMB-CMP-001` |
| 12 | Store owner | Identity | Logs in with the issued credentials and is forced to set a new password | page 9 / پیوست ۶ / بند ۸; `BR-USR-05` |

**Postconditions:** store exists, approved, owned by one marketer, priced at colleague level.
**Exceptions:** duplicate detected → marketer sees the existing owner and cannot claim it; claiming a store owned by another marketer requires admin transfer (`BR-MKT-05`, `AMB-MKT-002`).

---

## J-05 Admin approves a store (ادمین)

**Trigger:** the approvals queue has a new on-site registration. **Actors:** admin, store, marketer.

| # | Actor | Module | Step | Anchor |
|---|---|---|---|---|
| 1 | Admin | Admin | Opens the queue: store name, owner, city/province, photos, map pin, national ID, postal code, registering marketer | page 9 / پیوست ۷ / بند ۱ |
| 2 | System | Marketing | Shows duplicate-check result and any prior rejected application for the same national ID | page 7 / پیوست ۵ / بند ۳ |
| 3 | Admin | Admin | Verifies the identity photo against the national ID and the storefront photo against the address | `AGENT_DECISION` — `BR-ADM-01` |
| 4 | Admin | Admin | Sets the role to `store`, `is_colleague = true`, and the price plane | page 6 / پیوست ۳ / ۳-۱ / ۸ |
| 5 | Admin | Admin | Optionally enables a secondary level for this user | page 9 / پیوست ۷ / بند ۶; `BR-USR-03` |
| 6 | Admin | Admin | Approves → user activated, store visible in the directory | page 6 / پیوست ۳ / ۳-۵ |
| 7 | System | Notifications | Sends the store its welcome + credentials reminder (SMS/WhatsApp/email) | page 9 / پیوست ۷ / بند ۳ |
| 8 | System | Admin | Writes an audit entry (actor, before/after, reason) | page 5 / پیوست ۱ / بند ۱۰; `BR-ADM-03` |
| 9 | Admin | Admin | Rejection path: reason is recorded and returned to the marketer, who may fix within the edit window or re-present | page 9 / پیوست ۶ / بند ۹ |

**Postconditions:** the store can log in, sees colleague price, and belongs to exactly one marketer.
**Exceptions:** admin suspects fraud (same photo reused) → reject + flag; the marketer's plan gate may suspend commission until the store invoices (`BR-CMP-03`).

---

## J-06 Buyer files a complaint and freezes the order (شکایت)

**Trigger:** a pallet arrives with water-damaged cartons, 4 days after dispatch. **Actors:** store, system, supplier, admin.

| # | Actor | Module | Step | Anchor |
|---|---|---|---|---|
| 1 | Store | Orders | Opens the invoice/sub-order and taps **complaint** (available until **1 week after dispatch**) | page 7 / پیوست ۴ / بند ۱۵; `BR-ORD-02` |
| 2 | Store | Orders | Describes the issue, attaches photos/audio (2-minute audio supported) | page 8 / پیوست ۵ / بند ۴۱ |
| 3 | System | Orders | Moves the sub-order to `preparation_hold`; **supplier payment is suspended** until resolution | page 7 / پیوست ۴ / بند ۱۵; `BR-ORD-03` |
| 4 | System | Notifications | Notifies supplier and admin; SLA clock starts | page 9 / پیوست ۷ / بند ۳ |
| 5 | Supplier | Orders | Responds in the order thread: replace, discount, or reject with evidence | page 8 / پیوست ۵ / بند ۲۸ |
| 6 | Admin | Admin | Reviews evidence; may request inspection photos from both sides | page 9 / پیوست ۷ / بند ۲ |
| 7 | Admin | Admin | Decision A — **replace/compensate**: compensation amount approved and credited to the buyer's wallet | page 7 / پیوست ۴ / بند ۱۴; `BR-ORD-05` |
| 8 | Admin | Admin | Decision B — **cancel**: sub-order cancelled, money returned to the buyer (wallet or SHEBA) | page 10 / پیوست ۱۱ / بند ۱۴; `BR-ORD-06` |
| 9 | Admin | Admin | Decision C — **reject the complaint**: hold released, supplier paid, reason recorded | `BR-ORD-03` |
| 10 | System | Reviews | If the complaint is upheld, a violation may be recorded against the supplier and shown to peers | page 7 / پیوست ۴ / بند ۱۸–۱۹; `J-14` |
| 11 | System | Orders | Releases the hold, resumes fulfilment or closes the sub-order | `BR-ORD-01` |

**Postconditions:** money is either returned, compensated, or released; the audit trail holds every state change.
**Exceptions:** complaint filed after 7 days → not accepted by the button, but a ticket to admin is still allowed (goodwill path, `AMB-ORD-001`); supplier silent past SLA → admin auto-escalates and may release funds to the buyer.

---

## J-07 Delay compensation reaches the buyer's wallet (خسارت تأخیر)

**Trigger:** the order promised dispatch on the 3rd; on the 6th nothing has shipped. **Actors:** supplier, system, buyer, admin.

| # | Actor | Module | Step | Anchor |
|---|---|---|---|---|
| 1 | Supplier | Shipping | Records the delay with waybill number and courier dispatch details | page 10 / پیوست ۱۱ / بند ۱۵ |
| 2 | System | Orders | Computes compensation from the approved percentage × late line value × days late, capped | page 7 / پیوست ۴ / بند ۱۴; `BR-ORD-05` |
| 3 | System | Finance | Posts a debit to the supplier's account and a credit to the buyer's **wallet** | page 10 / پیوست ۱۱ / بند ۱۵; page 11 / پیوست ۱۲ / بند ۸ |
| 4 | Admin | Admin | Approves or adjusts the percentage/amount where the contract requires admin confirmation | page 7 / پیوست ۴ / بند ۱۴; `AMB-ORD-004` |
| 5 | System | Notifications | Tells both parties, with the arithmetic shown | page 9 / پیوست ۷ / بند ۳ |
| 6 | Buyer | Finance | Sees the credit in the wallet and spends it on the next invoice or requests settlement | page 6 / پیوست ۴ / بند ۷ |
| 7 | System | Loyalty | If the buyer never spends it, the credit expires per its configured validity (`AGENT_DECISION`) | `BR-FIN-03` |

**Postconditions:** supplier account debited, buyer credited, both notified, audit written.
**Exceptions:** supplier disputes the delay → ticket, admin decides; supplier uses the **incomplete-order** button instead, which suspends compensation (`BR-ORD-04`), and repeated misuse draws negative points.

---

## J-08 Points → tiered reward → next-invoice discount (امتیاز و پاداش)

**Trigger:** a store's invoice #104 pushes it from 92 to 128 points. **Actors:** store, system.

| # | Actor | Module | Step | Anchor |
|---|---|---|---|---|
| 1 | System | Loyalty | On invoice issuance, computes per-line points = line value × product coefficient × volume coefficient | page 6 / پیوست ۴ / بند ۱–۳; `BR-LOY-01`, `BR-LOY-08` |
| 2 | System | Loyalty | Sums lines into the invoice total; awards even if the buyer never claims in time | page 6 / پیوست ۴ / بند ۵ |
| 3 | System | Loyalty | Recomputes the tier: 92 → 128 crosses into **100–200** | page 7 / پیوست ۴ / بند ۲۰; `BR-LOY-07` |
| 4 | System | Loyalty | Awards the **5%** next-invoice discount (was 4% below 100) and records the entitlement with an expiry | page 7 / پیوست ۴ / بند ۲۰; `AMB-LOY-004` |
| 5 | System | Notifications | Sends an **SMS** announcing the new discount percentage | page 7 / پیوست ۴ / بند ۲۰; page 8 / پیوست ۵ / بند ۱۶ |
| 6 | System | Loyalty | Also checks the **star** rule: 128 points → 1 star (100 points = 1 star, unlimited) | page 7 / پیوست ۴ / بند ۹; `BR-LOY-06` |
| 7 | Store | Cart | On the next order, the 5% is applied automatically to the invoice | page 8 / پیوست ۵ / بند ۱۶ |
| 8 | System | Loyalty | If a cash **reward** also exists, deduct it only when the invoice ≥ 4× the reward; otherwise deduct half | page 6 / پیوست ۴ / بند ۴; `BR-LOY-03` |
| 9 | System | Loyalty | If a points threshold for a **cash reward** is met, credit it to the wallet (validity may be fixed-term or permanent) | page 6 / پیوست ۴ / بند ۷; `BR-LOY-04` |
| 10 | System | Loyalty | Deducts points if the buyer is inactive past the configured window (e.g. 4 points/month) | page 6 / پیوست ۴ / بند ۶; `BR-LOY-05`, `AMB-LOY-002` |
| 11 | System | Loyalty | Writes every movement to an append-only points ledger, replayable for audit | `BR-LOY-11` |

**Postconditions:** discount and reward both visible, source-traceable to the invoice that earned them.
**Exceptions:** invoice cancelled later → points and discount clawed back (`BR-LOY-11`); tier boundary exactly at 100 → resolved to the higher tier (`AMB-LOY-004`); reward larger than the invoice → half-deduction path only.

---

## J-09 Tender: buyer publishes, suppliers bid (مناقصه)

**Trigger:** a developer needs 4,000 m² of flooring for a tower. **Actors:** store/consumer (buyer), suppliers, admin.

| # | Actor | Module | Step | Anchor |
|---|---|---|---|---|
| 1 | Buyer | Tenders | Fills the tender form: scope, quantities, specs, delivery city, deadline | page 7 / پیوست ۵ / بند ۶; page 6 / پیوست ۳ / ۳-۳ / ۵ |
| 2 | System | Tenders | Publishes to eligible suppliers/producers (category, city, capacity) | page 7 / پیوست ۵ / بند ۶ |
| 3 | Buyer | Tenders | May also send the tender form to selected producers/suppliers directly (colleague-initiated) | page 8 / پیوست ۵ / بند ۳۷ |
| 4 | Supplier | Tenders | Submits a bid or asks an **inquiry/RFQ** question that is visible to all bidders | page 8 / پیوست ۵ / بند ۳۶ |
| 5 | System | Tenders | Enforces the deadline; late bids refused; bids sealed until opening | `AGENT_DECISION` — `BR-TND-01` |
| 6 | Buyer | Tenders | Compares bids side by side (price, lead time, supplier violation history) | page 7 / پیوست ۴ / بند ۱۹ |
| 7 | Buyer | Tenders | Awards; the winning bid converts into a normal order at the agreed prices | `BR-TND-02` |
| 8 | System | Orders / Pricing | Creates the order with **bid prices**, not catalogue prices, and snapshots the tender reference | `BR-PRI-02` |
| 9 | System | Notifications | Notifies winners and losers | page 9 / پیوست ۷ / بند ۳ |

**Postconditions:** one order from the award; tender history retained for reporting.
**Exceptions:** no bids by deadline → auto-expire; buyer withdraws → suppliers notified; winner refuses → next bid may be accepted (`BR-TND-02`).
**Related:** auctions (مزایده) run the same machinery in reverse, seller-initiated, open to suppliers, colleagues and consumers (page 10 / پیوست ۱۱ / بند ۱۰).

---

## J-10 Classified ad posted on دیوار (آگهی)

**Trigger:** a store wants to sell a used edge-banding machine. **Actors:** any authenticated user, admin.

| # | Actor | Module | Step | Anchor |
|---|---|---|---|---|
| 1 | System | Classifieds | Shows the ad form **only if admin has defined a taxonomy** for the relevant branch | page 7 / پیوست ۵ / بند ۴; `BR-CAT-03` |
| 2 | User | Classifieds | Enters title, body, images, price, city, and the classified category | page 7 / پیوست ۵ / بند ۴ |
| 3 | System | Classifieds | Validates required fields and image limits; saves as `pending_review` if the branch is moderated | `BR-CAT-03` |
| 4 | Admin | Admin | Approves or rejects with a reason (rejections are visible to the poster) | page 9 / پیوست ۷ / بند ۷ |
| 5 | System | Classifieds | Publishes with the poster's **public profile** card (name, level, phone/address per profile settings) | page 7 / پیوست ۵ / بند ۴, بند ۸ |
| 6 | Visitor | Classifieds | Searches and filters ads; opens the ad and contacts the poster | page 7 / پیوست ۵ / بند ۴ |
| 7 | User | Classifieds | Edits or closes the ad; edits re-enter review | `BR-CAT-03` |
| 8 | Anyone | Admin | Can **report** the ad or the user from the ad page | page 9 / پیوست ۷ / بند ۷ |

**Postconditions:** ad live with owner attribution; report path exists on every ad.
**Exceptions:** no taxonomy → form is closed with an explanatory message (this is the contract's explicit gate); reported ad → hidden pending admin review.

---

## J-11 Patogh service request: find an installer (پتوق)

**Trigger:** سارا needs a parquet installer in Tehran for next week. **Actors:** consumer, artisan/service provider, admin, optional local intermediary.

| # | Actor | Module | Step | Anchor |
|---|---|---|---|---|
| 1 | Consumer | Patogh | Browses the local-services directory: category (installer, repairer), city, capability description | page 7 / پیوست ۵ / بند ۵; page 8 / پیوست ۵ / بند ۱۳ |
| 2 | Consumer | Patogh | Opens a provider: profile, services, work photos, address/map, working hours, rating | page 8 / پیوست ۵ / بند ۳۵ |
| 3 | Consumer | Patogh | Submits a **request ticket**: area, material, timing, photos | page 8 / پیوست ۵ / بند ۱۳ |
| 4 | Provider | Patogh | Responds with a **local cost announcement** (price + availability) in the ticket | page 7 / پیوست ۵ / بند ۵ |
| 5 | Consumer | Patogh | Compares replies and accepts one; contact details are exchanged | `AGENT_DECISION` — `BR-PTG-01` |
| 6 | Consumer | Orders | Optionally orders the material through the platform and attaches the installation address/location | page 11 / پیوست ۱۲ / بند ۳ |
| 7 | Intermediary | Orders | Or a local shop (رابط) places the order on her behalf with address and location | page 11 / پیوست ۱۲ / بند ۳; `AMB-ORD-005` |
| 8 | Admin | Admin | Moderates provider profiles and categories; approves profile claims (hours, gallery, links) | page 8 / پیوست ۵ / بند ۷, بند ۳۵ |

**Postconditions:** a request thread with at least one priced reply; no platform payment for the service in v1.
**Exceptions:** provider unresponsive → request expires and is re-broadcast; profile claims unverified → shown as unverified; abuse → report to admin.

---

## J-12 Wallet settlement request → paid (تسویه)

**Trigger:** a marketer's month closes with 14,500,000 Toman of commission; a supplier is owed 62,000,000 for shipped goods. **Actors:** claimant, admin finance, bank.

| # | Actor | Module | Step | Anchor |
|---|---|---|---|---|
| 1 | Claimant | Finance | Opens statement: wallet balance, debit/credit, past settlements, transaction list | page 11 / پیوست ۱۳ / بند ۵; page 6 / پیوست ۳ / ۳-۱ / ۴ |
| 2 | Claimant | Finance | Submits a **settlement request** with amount and SHEBA | page 6 / پیوست ۳ / ۳-۲ / ۲ |
| 3 | System | Finance | Validates: amount ≤ available balance (not held), no open complaint on the underlying sub-orders, minimum threshold met | `BR-FIN-02`, `AMB-FIN-002` |
| 4 | System | Finance | Reserves the amount (a hold entry), so it cannot be double-spent | `BR-FIN-01` |
| 5 | Admin | Admin | Reviews the request against the period's sales report; may adjust with a written reason | page 11 / پیوست ۱۳ / بند ۱ |
| 6 | Admin | Admin | Approves → creates the payout batch | page 9 / پیوست ۷ / بند ۱۰ |
| 7 | Admin | Reporting | Exports the **Excel** bank file for group payment on the agreed date | page 11 / پیوست ۱۳ / بند ۳ |
| 8 | Admin | Finance | Uploads the bank reference; entries flip from `reserved` to `paid` | `BR-FIN-02` |
| 9 | System | Notifications | Notifies the claimant with the reference | page 9 / پیوست ۷ / بند ۳ |
| 10 | System | Admin | Audit entry: who approved, amount, bank reference, before/after balances | page 5 / پیوست ۱ / بند ۱۰ |

**Postconditions:** balance reduced by exactly the paid amount; a permanent, reconcilable trail.
**Exceptions:** rejected request → hold released with reason; partial payment → remainder stays reserved; gateway discrepancy → reconciliation case, not a manual edit (`BR-PAY-03`).

---

## J-13 Supplier cannot fulfil: incomplete order, then cancel + refund (سفارش ناقص)

**Trigger:** the ordered shade is discontinued after payment. **Actors:** supplier, buyer, system.

| # | Actor | Module | Step | Anchor |
|---|---|---|---|---|
| 1 | Supplier | Orders | Taps **incomplete order** (سفارش ناقص) with a reason and, if useful, a substitute proposal | page 10 / پیوست ۱۱ / بند ۱۲ |
| 2 | System | Orders | Moves the sub-order to `incomplete_pending_buyer`; **delay compensation is suspended** during this state | page 10 / پیوست ۱۱ / بند ۱۲; `BR-ORD-04` |
| 3 | System | Notifications | Notifies buyer with options: accept substitute, amend quantity, cancel | `BR-ORD-04` |
| 4 | Buyer | Orders | Accepts a substitute → new lines, price difference settled (charge or refund) | `BR-ORD-07` |
| 5 | Buyer | Orders | Or cancels → supplier cancels the sub-order and the amount is returned **to the buyer's SHEBA** | page 8 / پیوست ۵ / بند ۱۵; page 10 / پیوست ۱۱ / بند ۱۴; `BR-ORD-06` |
| 6 | System | Payments | Reverses the captured payment (or credits the wallet if the buyer prefers) | `BR-PAY-02`, `BR-FIN-03` |
| 7 | System | Loyalty | Claws back points and any tier discount granted by the cancelled invoice | `BR-LOY-11` |
| 8 | System | Compensation | Applies negative points when the button is used **repeatedly/incorrectly**, not when used once in good faith | page 10 / پیوست ۱۱ / بند ۱۳; `BR-ORD-04`, `AMB-ORD-003` |
| 9 | System | Reporting | Records the incomplete-order rate per supplier for the admin scorecard | `BR-ADM-05` |

**Postconditions:** no money lost, buyer refunded, supplier's misuse measurable.
**Exceptions:** buyer unreachable → auto-cancel after the configured window with refund; supplier uses the button to dodge a real delay → admin upholds complaint and records a violation.

---

## J-14 Violation report (وارن) → adjudication → peer visibility

**Trigger:** a distributor repeatedly ships a lower grade than invoiced. **Actors:** store, admin, supplier, other stores.

| # | Actor | Module | Step | Anchor |
|---|---|---|---|---|
| 1 | Store | Orders | On the invoice, taps **وارن** and states the violation (grade mismatch, short weight, no invoice) | page 7 / پیوست ۴ / بند ۱۸ |
| 2 | Store | Reviews | Attaches evidence: photos, the invoice line, chat history | page 8 / پیوست ۵ / بند ۴۱ |
| 3 | System | Reviews | Creates a violation case; the supplier's profile shows a pending marker to admin only | `BR-ORD-09` |
| 4 | Admin | Admin | Reviews; may request the supplier's response; decides upheld / dismissed | page 9 / پیوست ۷ / بند ۲ |
| 5 | Admin | Admin | If upheld: records a **violation point** (negative score) visible after approval | page 8 / پیوست ۵ / بند ۱۴; `BR-ORD-09` |
| 6 | System | Reviews | Shows the supplier's recent violations (e.g. last two months) **to peers at checkout** | page 7 / پیوست ۴ / بند ۱۹; page 8 / پیوست ۵ / بند ۳۴ |
| 7 | System | Notifications | Notifies the supplier and the reporter of the outcome with reasons | page 9 / پیوست ۷ / بند ۳ |
| 8 | Admin | Admin | Repeated upheld violations may trigger commission suspension or a level change for a marketer/store | page 7 / پیوست ۴ / بند ۲۲ |
| 9 | System | Admin | Everything above is audited | page 5 / پیوست ۱ / بند ۱۰ |

**Postconditions:** the peer-visible violation list is accurate and time-bounded; the supplier has a right of reply on record.
**Exceptions:** malicious reporting → dismissed with a note on the reporter; appeal within the configured window → admin re-opens (`AMB-ORD-006`).

---

## Journey → rule coverage

| Journey | Primary rules | Open ambiguities |
|---|---|---|
| J-01 Consumer purchase | `BR-PRI-02`, `BR-PRI-04`, `BR-CRT-01..04`, `BR-PAY-01..02`, `BR-LOY-01` | `AMB-PRI-002`, `AMB-ORD-001` |
| J-02 Store reorder | `BR-PRI-01`, `BR-PRI-03`, `BR-LOY-01`, `BR-LOY-07`, `BR-CMP-02` | `AMB-PRI-003`, `AMB-LOY-004` |
| J-03 Supplier onboarding | `BR-PRI-01`, `BR-PRI-05`, `BR-ADM-01`, `BR-CAT-01` | `AMB-CAT-001` |
| J-04 Marketer registration | `BR-MKT-01..04`, `BR-USR-05`, `BR-CMP-02` | `AMB-MKT-001`, `AMB-MKT-002` |
| J-05 Admin approves store | `BR-ADM-01`, `BR-ADM-03`, `BR-USR-03` | `AMB-USR-001` |
| J-06 Complaint / freeze | `BR-ORD-02`, `BR-ORD-03`, `BR-ORD-05` | `AMB-ORD-001` |
| J-07 Delay compensation | `BR-ORD-05`, `BR-FIN-03` | `AMB-ORD-004` |
| J-08 Points & reward | `BR-LOY-01..08`, `BR-LOY-11` | `AMB-LOY-001..005` |
| J-09 Tender | `BR-TND-01..02`, `BR-PRI-02` | `AMB-TND-001` |
| J-10 Classifieds | `BR-CAT-03` | `AMB-CLS-001` |
| J-11 Patogh | `BR-PTG-01` | `AMB-PTG-001` |
| J-12 Settlement | `BR-FIN-01..02`, `BR-PAY-03` | `AMB-FIN-001..002` |
| J-13 Incomplete + refund | `BR-ORD-04`, `BR-ORD-06`, `BR-LOY-11` | `AMB-ORD-003` |
| J-14 Violation report | `BR-ORD-09`, `BR-ADM-03` | `AMB-ORD-006` |
