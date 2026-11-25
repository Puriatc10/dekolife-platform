# Personas and Roles — Dekolife

| | |
|---|---|
| **Purpose** | Define who uses the platform, what each one is trying to get done, where the product hurts them today, what they are allowed to do, and how we will know we served them. |
| **Audience** | UX, engineering (authorization policies), QA (role-based test matrix), client operations. |
| **Status** | Baseline v1 — 2026-11-25. |
| **Normative sibling** | Permissions here are summaries; the authoritative rule set is [business-rules.md](./business-rules.md). Journeys are in [user-journeys.md](./user-journeys.md). |

> **Source:** Roles and per-level capabilities: page 5–6 / پیوست ۳ (`۳-۱` store, `۳-۲` marketer, `۳-۳` consumer, `۳-۴` supplier, `۳-۵` admin); secondary level: page 9 / پیوست ۷ / بند ۶; admin console: page 9 / پیوست ۷ / بند ۱–۱۰; marketer console: page 9 / پیوست ۶; supplier console: page 10–11 / پیوست ۱۱; consumer plan: page 11 / پیوست ۱۲; field ops: page 11 / پیوست ۱۴. Role model (many-to-many + policy layer) from [`../canonical-context.md`](../canonical-context.md) §1.

---

## 1. The role model

The contract calls these **five independent user levels** with separated access (page 5 / پیوست ۳ opening). Two consequences drive the implementation:

1. **Admin is a level in the contract but a separate guard in the code.** `admin` gets its own panel on a dedicated path/domain with 2FA (page 5 / پیوست ۱ / بند ۷). It is not a "super store".
2. **A user may hold a second level** where admin enables it (page 9 / پیوست ۷ / بند ۶). So roles are many-to-many with a policy layer, and the *capability* checks are per (user, capability) — never `if user.role == 'store'`.

```
users ──< user_roles >── roles(store|marketer|consumer|supplier|admin)
                              │
                              └──< role_capabilities (seeded from پیوست ۳)
```

Three scoping rules are non-negotiable because a leak is a commercial breach:

- **Marketer scope** = stores he registered, invoices of those stores, leads in his own network, his own commission and visits. Nothing else (page 8 / پیوست ۵ / بند ۲۹; page 9 / پیوست ۶ / بند ۱۰).
- **Store scope** = own catalog entries he is allowed to publish, own orders and invoices, own wallet/statement. A store never sees another store's prices or order volume.
- **Supplier scope** = own products, prices, stock, sub-orders addressed to it, its own reports. A supplier never sees a competitor's price list or a store's buying history with others.

## 2. Persona cards

### P1 — رضا، owner of a flooring store (فروشگاه / همکار)

| | |
|---|---|
| **Context** | 15-year-old parquet shop in a provincial capital, 2 employees, buys 3–8 pallets a month from 4–5 producers, orders by phone and WhatsApp. |
| **Goals** | (a) know the real wholesale price before he commits; (b) not lose the "next-order discount" he was promised; (c) get paid on time by consumers and pay suppliers without a fight; (d) not be blamed for a supplier's late shipment. |
| **Pains** | Price is quoted per phone call and changes by caller; the discount he was promised verbally vanished; a damaged pallet took three weeks of arguing with no leverage; he has no single statement of what he owes and is owed. |
| **Permissions (summary)** | Publish content/announcements; manage cart and see own orders; earn points from sales, articles, comments and likes; message users, admin and other stores; request settlement and view statement/ledger; register store info, post classifieds, appear in Patogh; join tenders and auctions; place orders, open tickets, view issued invoices; view colleague and wholesale prices; report violations, request samples/work portfolio/catalogue; wishlist; edit profile with SMS verification (page 5–6 / پیوست ۳ / ۳-۱ / ۱–۱۰). |
| **Primary screens** | Store dashboard · Product/category browse with role price · Cart & checkout · My orders & sub-orders · Invoice detail (with `وارن` and freeze buttons) · Wallet, points & stars · Statement & settlement request · Wishlist · Samples & catalogue requests · Tenders · Classifieds/Patogh entry. |
| **KPI** | Repeat orders per quarter; average discount realised vs tier; settlement cycle days; complaint rate he files vs resolves. |
| **Success sentence** | "I open the app, see my price, reorder last month's pallet in under two minutes, and my discount is already applied." |

### P2 — مهدی، field marketer (بازاریاب)

| | |
|---|---|
| **Context** | Commission-only field agent covering 3 cities, 6–8 store visits a day, works from a phone, paid per invoice his stores place. |
| **Goals** | (a) prove a store is his so the commission is not stolen; (b) register a store in under 5 minutes standing in the shop; (c) see what his stores actually bought; (d) get settled without chasing accounting. |
| **Pains** | Registration needs head-office paperwork, so he writes it on paper and loses deals; a colleague re-registers "his" store and takes the commission; he cannot see whether a store ordered; his commission is disputed monthly. |
| **Permissions (summary)** | Message users and admin by ticket; request settlement, view statement/ledger; register colleague stores via the dedicated on-site form; personal code, link and QR; dedicated chat with permitted users; view store pages and Patogh; file complaints to admin; register customer/project orders; see which stores admin marked active/inactive; see invoices of stores he visited (page 6 / پیوست ۳ / ۳-۲ / ۱–۱۰; page 9 / پیوست ۶ / ۱–۱۲). |
| **Primary screens** | Marketer dashboard (today's route, targets) · On-site registration form (photos, map pin, national ID, postal code) · My stores (active/inactive) · My invoices · Commission & plan status · Settlement request · QR/card share sheet · Visit log with geolocation · Leads/customer orders. |
| **KPI** | Activated stores per month; share of his stores with ≥1 invoice in 90 days; commission per visit; registration-to-first-invoice days. |
| **Success sentence** | "I scan my own QR, the shop is mine, and next month I can show exactly which of my 40 shops ordered." |

### P3 — سارا، renovating a 90 m² apartment (مصرفکننده)

| | |
|---|---|
| **Context** | 34, buying parquet + skirting + installation for one project; researches on her phone at night; does not know trade prices exist. |
| **Goals** | (a) compare products on real specs, not marketing copy; (b) know the delivered total including shipping before paying; (c) find a trusted installer for her area; (d) get something back for a large purchase. |
| **Pains** | Showroom prices are opaque; she cannot tell if the "sale" price is real; installation is a separate phone hunt; returns are refused verbally. |
| **Permissions (summary)** | Full registration and user panel; cart, messaging, purchase history; wishlist of stores and products; profile edits and last-activity view; submit a tender and place orders via form; view purchase rules and track order status (page 6 / پیوست ۳ / ۳-۳ / ۱–۶). Plus consumer-plan features: consumer price visibility, distributor/agency lookup by city, intermediary order with address/location, vouchers, compare up to 5, postal shipping, deposit + settle on delivery, returns window, birthday/occasion messages, free-shipping rules (page 11 / پیوست ۱۲ / ۱–۱۶). |
| **Primary screens** | Home (search, categories, banner slider, special offers, best sellers, newest) · Category with filters · Product page (gallery, video, specs, reviews, related, compare) · Compare · Cart/checkout (gateway, shipping, deposit) · My orders + tracking code · Wishlist · Points & vouchers · Patogh (find installer). |
| **KPI** | Cart→paid conversion; compare usage before purchase; return rate within window; repeat purchase within 12 months. |
| **Success sentence** | "I saw the price the shops pay, so I know my price is fair, and I booked the installer in the same session." |

### P4 — خانم اکبری، sales manager at a parquet factory (تأمینکننده / تولیدکننده)

| | |
|---|---|
| **Context** | Runs 300 SKUs, 40 agencies, 200 active stores; today price lists go out as PDFs and orders arrive by phone. |
| **Goals** | (a) publish a price per role and change it without reprinting anything; (b) keep stock accurate so she stops promising what she cannot ship; (c) promote a new collection without paying an agency; (d) see a per-period sales report she can pay settlement against. |
| **Pains** | Stores compare her price with a competitor's rumour; her promotions never reach the right buyer; late dispatch penalties are argued case by case; no clean settlement file for the bank. |
| **Permissions (summary)** | Independent full panel: manage products, prices, stock, orders and sales reports (page 6 / پیوست ۳ / ۳-۴); plus per-role pricing form, product attributes/filters, categories, gallery, inventory (page 9 / پیوست ۷ / بند ۴); become a buying colleague at a fixed discount (page 10 / پیوست ۱۱ / بند ۲); dedicated brand page with map, history, images/video (بند ۳); festival participation and homepage placement (بند ۴); banner reservation (بند ۵); filtered notifications with admin cap, paid or free (بند ۶); SMS panel (بند ۷); seasonal slideshow (بند ۸); sample requests (بند ۹); auctions (بند ۱۰); incomplete-order button (بند ۱۲–۱۳); cancel + refund (بند ۱۴); record delay with waybill (بند ۱۵); supplementary invoice for extra cost (بند ۱۷); nationwide agency list (بند ۱۹). |
| **Primary screens** | Supplier dashboard (orders to ship, stock alerts) · Product editor with role price matrix · Inventory · Sub-order queue (accept / incomplete / cancel / dispatch + waybill) · Supplementary invoice · Promotions (festival, slideshow, banner requests) · Notifications & SMS · Sales & settlement reports (Excel) · Agency map · Auctions. |
| **KPI** | On-time dispatch; incomplete-order rate (and repeat misuse); stock accuracy; promotion-attributed revenue; settlement disputes. |
| **Success sentence** | "I set three prices once, my stock is right, and at month end the bank file is already exported." |

### P5 — نازنین، platform operations admin (ادمین)

| | |
|---|---|
| **Context** | Runs the marketplace day to day: approves stores, adjudicates complaints, configures points and commissions, reconciles money. Non-technical; lives in spreadsheets today. |
| **Goals** | (a) approve a store in one screen with evidence; (b) resolve a complaint before it becomes a chargeback; (c) tune the points/commission rules without a developer; (d) hand the bank a payout file that matches the ledger. |
| **Pains** | Approvals arrive as photos in chat; a complaint needs three systems to check; a discount rule change needs a release; month-end reconciliation is manual. |
| **Permissions (summary)** | Full access, user approval and oversight of all levels (page 6 / پیوست ۳ / ۳-۵): member management incl. disable/delete/edit/approve (page 9 / پیوست ۷ / بند ۱); finance — transactions, sold-product report, manual purchase invoice, per-user totals and invoices (بند ۲); sales reports and automatic messages on payment/dispatch/order via SMS, WhatsApp, notification (بند ۳); product form with per-role price, attributes, filters, descriptions, categories, gallery, inventory (بند ۴); content, messages and internal messenger (بند ۵); secondary-level configuration per user (بند ۶); report content/user (بند ۷); two-way tickets with all levels (بند ۸); banners, static pages, menus, gateways, general settings (بند ۹); marketer/store/transaction/settlement reports with Excel export (بند ۱۰). Plus 2FA and audit log (page 5 / پیوست ۱ / بند ۷, بند ۱۰). |
| **Primary screens** | Admin console: Approvals queue · Users & roles (incl. secondary) · Products & per-role pricing · Points/commission rule sets (versioned) · Orders & disputes (`وارن` queue, freeze/unfreeze, compensation approval) · Payments & reconciliation · Settlement runs + Excel export · Notifications composer · CMS (banners, pages, menus, festivals) · Audit log. |
| **KPI** | Approval turnaround; dispute resolution time; rule changes shipped without a release; reconciliation exceptions open >5 days. |
| **Success sentence** | "I can trace any Toman from gateway to wallet to bank file, and change a discount tier myself." |

### P6 — the secondary-level user (cross-role persona)

A real person often needs two hats, and the contract allows it: a consumer who also recruits stores (page 11 / پیوست ۱۲ / بند ۱۱), a producer who also buys from other suppliers (page 10 / پیوست ۱۱ / بند ۲). This persona is not a seventh level — it is the **union of two levels' capabilities with the stricter rule applied to any shared resource**.

| Need | Behaviour |
|---|---|
| One login, one wallet? | **One login, one wallet per user** (money must be traceable to a person, not a hat). Points and stars are per user as well; commission is per marketer role assignment. |
| Which price applies? | The price plane of the **role used at order time** (`AMB-PRI-001`); an order never mixes planes. |
| Which panel? | A role switcher in the header; each panel keeps its own scope. |
| Attribution conflict | A consumer who is also a marketer owns stores he registered; his own consumer purchases never earn him commission (self-dealing block, `AMB-CMP-002`). |

## 3. Capability matrix (normative summary)

Legend: **●** full · **◐** scoped to own records · **○** request/approval only · **—** none.

| Capability | Store | Marketer | Consumer | Supplier | Admin |
|---|---|---|---|---|---|
| Browse catalog & category filters | ● | ● | ● | ● | ● |
| See colleague price | ● | ◐ (via own stores' invoices) | — | ● | ● |
| See wholesale/volume price | ● | — | — | ● | ● |
| See consumer price | ● | ● | ● | ● | ● |
| Place order / checkout | ● | ○ (on behalf of a lead) | ● | ◐ (as buying colleague) | ○ (manual invoice) |
| Create/edit products, prices, stock | ◐ (own listings, admin-approvable) | — | — | ◐ (own catalogue) | ● |
| Publish content/announcement | ● | — | — | ● | ● |
| Post classified ad (دیوار) | ● | ● | ● | ● | ● |
| Appear in Patogh directory | ● | ◐ (own profile) | — | ● | ● |
| Enter tenders | ● | ○ (on behalf of lead) | ● | ● | ● |
| Run an auction | — | — | — | ● | ● |
| Register a store on site | — | ● (own network) | — | — | ● |
| View invoices | ◐ (own) | ◐ (stores he visited) | ◐ (own) | ◐ (addressed to it) | ● |
| Request settlement | ● | ● | ○ (wallet refund) | ● | ● (execute) |
| See statement / ledger | ◐ | ◐ | ◐ | ◐ | ● |
| File complaint / freeze order | ● | ◐ (on behalf of store) | ● | — | ● (adjudicate) |
| File `وارن` violation report | ● | ● | ● | — | ● (adjudicate) |
| Approve users, content, banners, secondary roles | — | — | — | ○ (request banner) | ● |
| Configure points/commission/price rules | — | — | — | — | ● |
| Excel export / bank payout file | ◐ (own statement) | ◐ (own commission) | — | ◐ (own sales) | ● |
| 2FA required | — | — | — | — | ● |

## 4. Secondary-level matrix

Cells: **Allowed** (admin may enable, no structural conflict) · **Conditional** (allowed with a specific guard) · **Blocked** (structurally conflicting). Sources: page 9 / پیوست ۷ / بند ۶; page 11 / پیوست ۱۲ / بند ۱۱; page 10 / پیوست ۱۱ / بند ۲.

| Primary ↓ / Secondary → | Store | Marketer | Consumer | Supplier |
|---|---|---|---|---|
| **Store** | — | **Blocked** — the contract's only explicit example: "سطح فروشگاه نتواند بازاریاب باشد — مطابق تنظیم ادمین" (a store cannot be a marketer, per admin setting) | Allowed (store staff also buys for home) | Conditional — allowed only as "buying colleague" (page 10 / پیوست ۱۱ / بند ۲), never as a competing seller in the same category (`AMB-USR-001`) |
| **Marketer** | Blocked (mirror of the rule above; a marketer who owns a store would self-attribute commission) | — | **Allowed, explicitly** (page 11 / پیوست ۱۲ / بند ۱۱) | Blocked (supplier + marketer collapses supplier and channel) |
| **Consumer** | Allowed (this is the normal path: consumer upgrades to a store) | **Allowed, explicitly** (بند ۱۱) | — | Conditional — allowed with admin approval (`AMB-USR-001`) |
| **Supplier** | Conditional — as buying colleague only | Blocked | Conditional — allowed (supplier staff buying retail) | — |

**Guards that apply to every allowed combination** (`AMB-USR-001`):

1. Pricing plane is chosen per order, never per user (`BR-PRI-02`).
2. Commission is never paid on a user's own purchase (self-dealing block, `BR-CMP-05`).
3. A marketer's scope over a store he does not own is always read-only.
4. Enabling/disabling a secondary level is an audited admin action (`adm_audit_logs`, page 5 / پیوست ۱ / بند ۱۰), and it is reversible without deleting history.

## 5. What no persona covers

Recorded here because it changes who we build for, and one of them is a real gap:

| Observation | Consequence |
|---|---|
| **The contract has no "warehouse/logistics operator" role.** Shipping is a *choice on the order* (`shipping type`, waybill number) and a courier is a free-text/carrier reference, not a platform user (page 8 / پیوست ۵ / بند ۴۰; page 10 / پیوست ۱۱ / بند ۱۵). | No carrier accounts, no proof-of-delivery flow, no carrier settlement. Dispatch confirmation is the supplier's statement — which is exactly why delay compensation is adjudicated, not measured. Flag to client if real logistics tracking is expected. |
| **No "customer support agent" role distinct from admin.** Tickets are two-way between admin and every level (page 9 / پیوست ۷ / بند ۸). | Support staff will need an admin sub-role; propose `admin.support` with ticket+dispute scope only (`AMB-USR-002`). |
| **No "finance officer" role distinct from admin.** Settlement execution sits inside the admin console (page 9 / پیوست ۷ / بند ۲, بند ۱۰). | Propose `admin.finance` (settlement + reconciliation, no user deletion) so payout rights are separable from content rights (`AMB-USR-002`). |
| **The installer/artisan is a directory entry, not a transacting user.** Patogh lists capability and takes a request + local cost announcement (page 7 / پیوست ۵ / بند ۵, بند ۱۳). | No artisan onboarding, no job scheduling, no artisan payout. This is adjacent to the reservation contract (out of scope, page 9 note). |
| **The consumer's "intermediary" (رابط) is loosely defined** — a local shop taking an order on the platform's behalf with address/location (page 11 / پیوست ۱۲ / بند ۳). | Implemented as a store-role user placing an order with `placed_on_behalf_of = consumer`; flag `AMB-ORD-005` for whether the intermediary earns commission. |
| **No auditor/read-only role** despite an explicit audit-log requirement (page 5 / پیوست ۱ / بند ۱۰). | Propose `admin.auditor` read-only over logs and finance (`AMB-USR-002`). |
