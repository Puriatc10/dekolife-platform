# Product Vision — Dekolife (سامانه دکوالیف)

| | |
|---|---|
| **Purpose** | Explain why Dekolife exists, for whom, what each role gets, how the platform earns money, and what "success" means numerically. Anchors every later product decision. |
| **Audience** | Client (کارفرما), engineering leads, UX, marketing/sales operations, QA (acceptance criteria origin). |
| **Status** | Baseline v1 — 2026-11-25. Companion documents: [PRD](./PRD.md), [personas and roles](./personas-and-roles.md), [user journeys](./user-journeys.md), [use cases](./use-cases.md), [business rules](./business-rules.md), [glossary](./glossary.md). |
| **Scope of this mission** | Web application only. Native Android/iOS are deferred (`RB-MOB-01`); PWA is in scope as a web deliverable. Reservation/appointment module is out of scope (page 9 note under پیوست ۵). |

> **Source:** Contract `DK-1405-01` (pages 1–11) + appendices 1–14 as extracted to [`../contract/contract-extracted-text.txt`](../contract/contract-extracted-text.txt); naming and module boundaries from [`../canonical-context.md`](../canonical-context.md). Section markers: `page N` = PDF page, `پیوست N / بند M` = appendix and clause. Anything not traceable to a clause is labelled `AGENT_DECISION` and carries an `AMB-*` id resolved in the [rulebook](./business-rules.md).

---

## 1. The problem

Iran's decoration and building-finish trade — parquet and flooring first, then the adjacent goods and the installation labour that goes with them — runs on **relationships and phone calls**, not on systems.

1. **Price is a private conversation.** A producer sells the same carton of parquet at a different price to every store, and at a third price to a walk-in consumer. There is no mechanism that publishes a tier price per role, so the store cannot verify it got the "real" wholesale price, and the producer cannot enforce a floor (page 7 / پیوست ۴ / بند ۲۰–۲۱ imply three price planes: colleague, consumer, volume).
2. **The wholesale relationship is discovered door-to-door.** New stores are signed up by a travelling salesperson with a paper catalogue. Nobody knows which stores were visited, which went quiet, or which salesperson actually owns the relationship — and when the salesperson leaves, the network leaves with them (page 8 / پیوست ۵ / بند ۱, بند ۲۹; page 9 / پیوست ۶ / بند ۱–۲, بند ۱۰).
3. **Loyalty is informal and forgettable.** A producer gives "a bit more off next time" verbally. There is no ledger of who is owed what, so the discount is either forgotten or abused (page 6 / پیوست ۴ / بند ۳–۵).
4. **Money settles late and opaquely.** Stores ask for settlement and wait; producers cannot see a clean per-period sales report to pay against; unpaid or disputed invoices sit in WhatsApp threads (page 6 / پیوست ۳ / بند ۳-۱ / ۴; page 11 / پیوست ۱۳ / بند ۱–۳).
5. **Information asymmetry hides bad actors.** A store that received damaged or late goods has no structured way to warn peers, and the producer's payment is never held while a complaint is examined (page 7 / پیوست ۴ / بند ۱۵, بند ۱۸–۱۹).
6. **The consumer is priced out of the trade channel.** A homeowner buying 80 m² of parquet pays retail with no visibility of the local distributor, installer, or the fact that the material has a colleague price at all (page 11 / پیوست ۱۲ / بند ۱–۳).

The cost of all six is the same: **the trade runs on trust that cannot be audited**, so everyone prices in a risk premium.

## 2. Target market

| Dimension | Definition | Source |
|---|---|---|
| Category anchor | Parquet/flooring and decoration/building-finish materials; installation and measurement services attached to them | page 5 / پیوست ۲ / بند ۲–۴ (product/category pages); page 11 / پیوست ۱۲ / بند ۳ (installation & measurement), بند ۱۶ (customer work photos) |
| Supply side | Producers/manufacturers (تأمینکننده / تولیدکننده) and their nationwide agencies; goods providers/stores (فروشگاه / عرضهکننده کالا, addressed as "همکار") | page 6 / پیوست ۳ / ۳-۱, ۳-۴; page 11 / پیوست ۱۱ / بند ۱۹ |
| Demand side | Trade buyers (stores reordering stock) **and** consumers buying for a home/renovation project | page 6 / پیوست ۳ / ۳-۱ (store buys at colleague + wholesale price), ۳-۳ (consumer) |
| Geography | Iran; city/province on every store record, nationwide agency list, city-scoped shipping and free-shipping rules | page 9 / پیوست ۶ / بند ۱; page 10 / پیوست ۱۱ / بند ۱۹; page 11 / پیوست ۱۲ / بند ۱۲ |
| Go-to-market motion | Field sales: a marketer physically visits a store, registers it on the spot (with photos, map pin, national ID, postal code), and the store is bound to that marketer by a QR/link | page 8 / پیوست ۵ / بند ۱–۳; page 9 / پیوست ۶ / بند ۱–۲, بند ۷ |
| Adjacent surfaces shipped with the core | Classifieds ("دیوار"), local services directory ("پتوق"), tenders, auctions, blog/CMS, internal chat | page 7 / پیوست ۵ / بند ۴–۶, ۱۳; page 10 / پیوست ۱۱ / بند ۱۰ |

**Why this market tolerates a platform:** the unit economics are large (a single store order is a pallet-scale purchase, not a consumer basket), the buyer set is small and addressable in person, and the incumbent "system" is a paper catalogue plus a phone — so a modest digital layer captures the whole relationship rather than a sliver of it.

## 3. What Dekolife is

One multi-vendor marketplace with **five independent user levels** (page 5 / پیوست ۳ opening clause) plus an **optional secondary level** enabled per user by admin (page 9 / پیوست ۷ / بند ۶):

- **فروشگاه / عرضهکننده کالا — store.** Sells goods, buys at colleague and wholesale prices, sees its own invoices, requests settlement.
- **بازاریاب — marketer.** Field agent who registers stores door-to-door, owns them via QR/link, earns commission, is tracked geographically.
- **مصرفکننده — consumer.** Buys at consumer price, uses the loyalty club, can also act as a marketer (page 11 / پیوست ۱۲ / بند ۱۱).
- **تأمینکننده / تولیدکننده — supplier.** Independent panel for products, prices, stock, orders, reports, festivals, banner reservations, auctions.
- **ادمین — admin.** Approves users and content, owns pricing/points/commission configuration, accounting and Excel export, with 2FA and a separate panel.
- **Secondary level.** A user may hold a second level where admin enables it; the canonical model is many-to-many roles plus a policy layer, with the specific allowed combinations left open (`AMB-USR-001`, see [business rules §11](./business-rules.md#11-users-admin-notifications)).

Around that core sit the engines that make the trade relationship worth digitising: **role-scoped pricing**, **points → tiered discount → wallet reward**, **commissions**, **payments through 12 gateways**, **messaging/notifications**, and **accounting with settlement**.

## 4. Value proposition, per role

| Role | The one thing it gets | Supporting capabilities | Source |
|---|---|---|---|
| **Store (همکار)** | Buys at a verifiable colleague/wholesale price with a documented discount ladder, and stops hunting for the price by phone | role + volume price visibility, per-invoice points, tiered next-invoice discount (4%/5%/fixed n Toman), star badge, complaint/freeze with payment hold, delay compensation into wallet, settlement request + statement, wishlist, samples/catalogues, tender participation | page 6 / پیوست ۳ / ۳-۱ / ۱–۱۰; page 7 / پیوست ۴ / بند ۹, ۱۴–۲۱ |
| **Marketer (بازاریاب)** | A portable, provable book of business: every store he signs is attributed to him, and his earnings are computed from invoices he can see | dedicated QR/link code, separate on-site registration form, 5-minute edit window then admin approval, per-visit/commission/hybrid/salary plans A–D, live geo tracking and performance analysis, visibility of invoices from stores he visited, settlement request | page 6 / پیوست ۳ / ۳-۲ / ۱–۱۰; page 7 / پیوست ۵ / بند ۱–۲; page 9 / پیوست ۶ / بند ۳–۱۰; page 11 / پیوست ۱۴ / بند ۱–۲ |
| **Consumer (مصرفکننده)** | Trade-grade product information and a price that is explicitly *not* the colleague price, plus a loyalty club that pays back | consumer price plane (default +35% over colleague — `AMB-PRI-002`), compare up to 5 products, wishlist, deposit + settle on delivery, free shipping rules, birthday/occasion messages, returns window, customer work photos, can also act as marketer | page 5 / پیوست ۲ / بند ۵–۶; page 11 / پیوست ۱۲ / بند ۱–۱۶ |
| **Supplier (تأمینکننده/تولیدکننده)** | A storefront plus a demand engine it controls: price per role, stock, festivals, paid notifications, banners, auctions | independent panel for product/price/stock/orders/sales reports, per-role price setting, festival section on homepage, banner reservation, filtered paid notifications and SMS, slideshow, sample requests, auction house, agency map, supplementary invoice for extra costs, cancel/refund, shipment-delay recording | page 9 / پیوست ۷ / بند ۴; page 10–11 / پیوست ۱۱ / بند ۱–۲۰ |
| **Admin (ادمین)** | One console where pricing, points, commissions, approvals, disputes and money all reconcile, exportable to Excel | member management + approvals, per-role product pricing form, points/commission rule configuration, complaint and violation adjudication, transaction and settlement reports, manual invoices and manual account adjustments, banners/pages/menus/gateways, audit log, 2FA | page 9 / پیوست ۷ / بند ۱–۱۰; page 5 / پیوست ۱ / بند ۷, بند ۱۰ |

**The compounding effect.** Each role's value grows with the others: more stores → more volume bands hit → better tier discounts; more marketers → more stores; more suppliers → more price competition → more consumer traffic; more consumer traffic → more value in supplier paid promotion. That loop, not any single feature, is the product.

## 5. Business model

The contract does not state a tariff schedule; it states the **revenue surfaces** and one explicit profit-sharing clause. Each surface below is therefore a mechanism the product must support, with the commercial value left to the client (`AGENT_DECISION` where a default is needed).

| # | Revenue surface | Mechanism the platform must implement | Source | Status |
|---|---|---|---|---|
| 1 | **Platform commission on trade** | Take-rate on each seller sub-order, recorded in the ledger at capture, netted at settlement | Implied by `ماده ۱۶` profit-share (the client's "net profit" is defined as sales revenue minus cost of goods, gateway fee, shipping and tax — a platform margin must exist to be shared) + settlement/report requirements (page 11 / پیوست ۱۳ / بند ۱–۵) | Rate is configurable, **default 0% in seed** — `AMB-FIN-001` |
| 2 | **Paid notifications** | Buyer of the notification enters count, text, image, link, pays the computed amount, admin reviews and sends | page 8 / پیوست ۵ / بند ۱۲; page 10 / پیوست ۱۱ / بند ۶–۷ | Specified; price table is admin data (`AMB-NTF-001`) |
| 3 | **Advertising: banners** | Supplier requests a banner slot for a date range; admin approves; impression/click reporting | page 10 / پیوست ۱۱ / بند ۵; page 9 / پیوست ۷ / بند ۹ | Specified |
| 4 | **Supplier plans / visibility** | Festival participation, dedicated slideshow, priority placement, product photography service | page 10 / پیوست ۱۱ / بند ۴, بند ۸, بند ۲۰ | Partly specified (no price list) |
| 5 | **Marketer commission as a cost centre, not revenue** | Commission plans A–D paid to field agents; the platform is the payer of record | page 9 / پیوست ۶ / بند ۳, بند ۵–۶ | Specified mechanism, values `AMB-CMP-001` |
| 6 | **Optional paid surfaces** (classifieds, tenders, auctions) | Posting/participation gated by admin-defined taxonomy and eligibility | page 7 / پیوست ۵ / بند ۴–۶ | Free in v1; monetisation is a later `AGENT_DECISION` |

**Contract economics for context** (not revenue, but the frame the client bought): fixed price **90,000,000 Toman** with **10,000,000** on signature and **80,000,000** one month after final delivery and written approval; **3 months (90 days)** execution; **5-year** free bug-fix warranty; support first response ≤24h, critical fixes ≤24h, medium ≤3 days (page 1; page 2 / ماده ۲, ماده ۳; page 4 / ماده ۱۲). Under `ماده ۱۶` the contractor additionally holds **15% of net operating profit** and bears **15% of future development/maintenance costs** — which is why the product must expose *clean, exportable* revenue and cost reporting (page 11 / پیوست ۱۳ / بند ۳, بند ۵).

> **Why the platform must own the money, not just the order.** Commission, delay compensation, wallet rewards and settlement all require a single authoritative balance per user. The contract is silent on who holds funds; the conservative model (platform-collected, ledger-backed, period settlement) is adopted and recorded as `AMB-FIN-001`.

## 6. Success metrics

Targets are `AGENT_DECISION` planning values for the first 12 months after go-live; the *definitions* are binding because they are what acceptance testing and reporting will compute. Every metric below is computable from data the platform already captures — no survey instruments.

| # | Metric | Definition (computable) | 12-month target | Source of the underlying data |
|---|---|---|---|---|
| M1 | **Activated stores** | Stores with ≥1 issued invoice in the last 90 days ÷ stores registered | ≥55% | page 7 / پیوست ۴ / بند ۲۳ (active/inactive definition by invoice window) |
| M2 | **Marketer-sourced share** | Invoices from stores whose `mer_` ownership came from a QR/link registration ÷ all invoices | ≥70% | page 7 / پیوست ۵ / بند ۱; page 9 / پیوست ۶ / بند ۱ |
| M3 | **Repeat purchase rate (stores)** | Stores with ≥2 invoices in a trailing 90-day window ÷ stores with ≥1 | ≥45% | page 7 / پیوست ۴ / بند ۲۱ (design intent: reward continuity) |
| M4 | **Points redemption depth** | Reward value redeemed on next invoice ÷ reward value credited | ≥40% | page 6 / پیوست ۴ / بند ۴, بند ۷ |
| M5 | **Discount ladder reach** | Invoices receiving 4%/5%/fixed-n discount ÷ all invoices | ≥30% | page 7 / پیوست ۴ / بند ۲۰–۲۱ |
| M6 | **Settlement cycle time** | Median days from settlement request to paid, per role | ≤7 days | page 6 / پیوست ۳ / ۳-۱ / ۴; page 11 / پیوست ۱۳ / بند ۱ |
| M7 | **Dispute rate & resolution** | Complaints per 100 invoices; median hours to adjudication | ≤3 per 100; ≤48h | page 7 / پیوست ۴ / بند ۱۵–۱۶, بند ۱۸ |
| M8 | **On-time dispatch** | Sub-orders dispatched within promised window ÷ dispatched | ≥90% | page 10 / پیوست ۱۱ / بند ۱۵ |
| M9 | **Consumer channel mix** | Consumer-role invoices ÷ all invoices | ≥20% by month 12 | page 8 / پیوست ۵ / بند ۱۸; page 11 / پیوست ۱۲ |
| M10 | **Gate failure rate** | Failed/returned gateway transactions ÷ attempts, with daily reconciliation exceptions | ≤2%; zero unreconciled >5 days | page 11 / پیوست ۱۳ / بند ۶ |
| M11 | **Field coverage** | Distinct geocoded visits per marketer per working day | ≥6 | page 11 / پیوست ۱۴ / بند ۱–۲ |
| M12 | **Paid-promotion uptake** | Suppliers with ≥1 paid notification or banner in the quarter ÷ active suppliers | ≥25% | page 8 / پیوست ۵ / بند ۱۲; page 10 / پیوست ۱۱ / بند ۵–۶ |

**Anti-metrics** (guardrails that must not regress while chasing the above): complaint rate must not be reduced by suppressing the freeze button; discount depth must not exceed a configured ceiling (`AMB-LOY-004`); and inactive-store auto-downgrade must never fire on a store with an open complaint.

## 7. Three-year direction

Direction is `AGENT_DECISION` planning, not contract scope; it exists so the v1 data model and module boundaries do not block it. Each horizon names the decision it protects.

| Horizon | What ships | Why the v1 architecture must already allow it |
|---|---|---|
| **Year 1 — Trade core, web-first** | Web app + PWA; the five roles; role pricing; orders/invoices; payments through the first live gateways; points/rewards/wallet; marketer field ops with QR attribution and geo; complaints, delay compensation, violations; admin accounting with Excel export; classifieds, Patogh, tenders at v1 depth | Module boundaries (Catalog/Pricing/Orders/Payments/Finance/Loyalty/Marketing/FieldOps) and an append-only ledger mean new revenue surfaces are new modules, not schema surgery |
| **Year 2 — Native apps + supplier monetisation depth** | Android/iOS clients (`RB-MOB-01` lift), push-first notification strategy, richer supplier promotion products (sponsored placement auctions, product photography as a service), multi-gateway orchestration and split payment (`RB-PAY-01`) | API-first (`routes/api.php`, Sanctum) and a `GatewayDriver` contract already exist; notification templating is provider-agnostic (SMS/WhatsApp/email/push) |
| **Year 3 — Network services** | Installation/measurement marketplace integrated with the reservation contract (separate contract no. 2), financing/BNPL for stores, cross-border sourcing, data products for producers (category demand, price index) | Pricing and loyalty are already rule-set driven and versioned; geo/visit data and invoice history are the raw material for a price index; the reservation module is deliberately **not** built here so its later integration is an addendum, not a rewrite |

**What we deliberately do not do** (so the vision stays honest): we are not a logistics company (shipping is a choice recorded on the order, page 8 / پیوست ۵ / بند ۴۰); we are not a lender in v1; we do not build native apps in this mission (`RB-MOB-01`); and we do not build appointment/reservation (separate contract).
