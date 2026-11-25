# Permissions Matrix — Dekolife Web Platform

**Purpose:** define who may do what, on which records, and prove it with negative tests. This is
the reference for every policy, scope query and 403 page in the product.
**Audience:** backend implementers (policies, queries, exports), QA (authorization suite), security
review.
**Status:** v1 baseline. Every capability row names its requirement; every negative test is
expected to fail closed.

Source: contract appendix 3 §3-1…§3-5 (five user levels and their capabilities), appendix 5
clauses 1–3, 9, 15, 29–32, 39–40 (scoped marketer access, registration by marketer, membership
transfer), appendix 6 clauses 3–12 (marketer panel scope, 5-minute edit window, scoped reports),
appendix 7 clauses 1–10 (admin powers, secondary level), appendix 11 clauses 1–20 (supplier
panel), appendix 12 clauses 1–16 (consumer plan), appendix 13 clauses 1–7 (financial reporting),
appendix 1 clause 7 (separate admin panel + 2FA), canonical-context §5 (policies and scopes on
the server; hiding a button is never authorization).

---

## 1. Role model

Five primary roles, stored as a many-to-many relation (`iam_roles` ↔ `iam_role_user`), never a
single `role` column (canonical-context §1):

| Role key | Persian label | Panel | Contract basis |
|---|---|---|---|
| `consumer` | مصرف‌کننده | `/account` | appendix 3 §3-3 |
| `store` | فروشگاه (همکار) | `/store` | appendix 3 §3-1 |
| `supplier` | تأمین‌کننده | `/supplier` | appendix 3 §3-4, appendix 11 |
| `marketer` | بازاریاب | `/marketer` | appendix 3 §3-2, appendix 6 |
| `admin` | مدیر سامانه | `/admin` | appendix 3 §3-5, appendix 7 |

**Secondary roles.** Appendix 7 clause 6 allows one additional level per user, subject to admin
configuration («هر سطح کاربری می‌تواند یک سطح ثانویه داشته باشد … مطابق تنظیم ادمین»). Appendix 12
clause 11 requires marketer + consumer to work simultaneously. Implementation:

- `iam_role_user` holds `is_primary` and `granted_by`; a user has exactly one primary role and at
  most one secondary role, both enforced by a unique partial constraint plus a policy.
- `adm_role_combinations` (admin-configured allow-list) decides which pairs exist. Default
  configuration shipped: `consumer+store` ✔, `consumer+marketer` ✔ (required by appendix 12
  cl.11), `store+supplier` ✔, `marketer+store` ✖, `supplier+marketer` ✔, any pair containing
  `admin` ✖.
- Capabilities are the **union** of the two roles, but each capability resolves a *scope*: holding
  `store` and `consumer` does not let the store read another store's orders, and does not let the
  consumer price apply to store orders. A request is authorised against the panel/role that owns
  the route (`/store/*` evaluates the store grant only).
- `admin` is never combined. Admin actions are not "superuser on the storefront": an admin has no
  storefront session and no cart (separate guard, separate cookie).

**Two guards.** `web` for consumer/store/supplier/marketer, `admin` for the admin panel with
mandatory TOTP 2FA (appendix 1 cl.7). A `web` session can never satisfy an `admin` route and vice
versa; both are asserted by tests (`NEG-GUARD-01/02`).

**Ability naming.** `<resource>.<action>[.<scope>]` — e.g. `orders.view.own`, `orders.view.any`,
`settlements.approve`, `products.update.own`. Each matrix row below corresponds to one ability and
one policy method; the permission catalogue in §8 maps abilities to routes.

---

## 2. Legend

| Symbol | Meaning |
|---|---|
| `✔` | allowed without record restriction (still subject to policy preconditions) |
| `◐` | allowed **only inside the actor's scope** (own store / own supplier / own network / own records) |
| `◑` | read-only, inside the actor's scope |
| `✖` | denied — must return 403 and must not leak existence |
| `—` | not applicable to this role |
| `A*` | admin-only, additionally requires 2FA session and produces an audit entry |

Requirement IDs follow canonical-context §4. Every capability row and every negative test names
the appendix clause through its REQ id; the appendix pointers in the document header are the join
key if the functional register renumbers a clause.

Role columns: **C** consumer · **S** store · **Su** supplier · **M** marketer · **A** admin.

---

## 3. Capability matrix

### 3.1 Identity and account

| Capability | REQ | C | S | Su | M | A |
|---|---|---|---|---|---|---|
| Register / log in with mobile + SMS OTP | IAM-001 | ✔ | ✔ | ✔ | ✔ | — |
| Reset own password | IAM-002 | ✔ | ✔ | ✔ | ✔ | ✔ |
| Edit own profile (SMS-confirmed) | IAM-003 | ✔ | ✔ | ✔ | ✔ | ✔ |
| Upload own avatar / logo | USR-003 | ✔ | ✔ | ✔ | ✔ | ✔ |
| Read own activity log | USR-004 | ✔ | ✔ | ✔ | ✔ | ✔ |
| Change own secondary role (self-service) | IAM-005 | ✖ | ✖ | ✖ | ✖ | — |
| Grant / revoke any user's roles | IAM-005 | ✖ | ✖ | ✖ | ✖ | A* |
| View another user's profile PII | USR-003 | ✖ | ◑ | ◑ | ◑ | A* |
| View identity documents (national ID, business licence) | MER-006 | ✖ | ◑ | ◑ | ✖ | A* |
| Deactivate / delete a user | ADM-001 | ✖ | ✖ | ✖ | ✖ | A* |
| Transfer or sell own membership to another person | USR-006 | ✔ | ✔ | ✔ | ✔ | — |
| Approve a membership transfer | USR-006 | ✖ | ✖ | ✖ | ✖ | A* |
| See own stars, points and tier | LOY-001, LOY-008 | ✔ | ✔ | ✔ | ✔ | — |
| Force 2FA enrolment / reset another admin's 2FA | ADM-010 | ✖ | ✖ | ✖ | ✖ | A* |
| Impersonate another user | — | ✖ | ✖ | ✖ | ✖ | ✖ |

Impersonation is deliberately absent: no clause authorises it, and it would destroy the audit
trail required by appendix 1 clause 10. Support works through tickets instead.

### 3.2 Catalog and pricing

| Capability | REQ | C | S | Su | M | A |
|---|---|---|---|---|---|---|
| Browse public catalog, search, compare | CAT-003, CAT-005, CAT-006 | ✔ | ✔ | ✔ | ✔ | — |
| View consumer price | PRI-003 | ✔ | ✔ | ✔ | ✔ | ✔ |
| View colleague (wholesale) price | PRI-001 | ✖ | ✔ | ✔ | ◑ | ✔ |
| View volume-tier prices | PRI-002 | ✖ | ✔ | ✔ | ◑ | ✔ |
| Create a product | CAT-002 | ✖ | ✔ | ✔ | ✖ | A* |
| Edit a product | CAT-002 | ✖ | ◐ | ◐ | ✖ | A* |
| Delete / unpublish a product | CAT-002 | ✖ | ◐ | ◐ | ✖ | A* |
| Set prices for own products | PRI-005 | ✖ | ◐ | ◐ | ✖ | A* |
| Set another seller's prices | PRI-005 | ✖ | ✖ | ✖ | ✖ | A* |
| Manage categories, brands, attributes | CAT-001, CAT-002 | ✖ | ✖ | ✖ | ✖ | A* |
| Set consumer markup (percent / per product) | PRI-003 | ✖ | ✖ | ✖ | ✖ | A* |
| Manage media library of own products | CAT-004, CAT-010 | ✖ | ◐ | ◐ | ✖ | A* |

### 3.3 Inventory

| Capability | REQ | C | S | Su | M | A |
|---|---|---|---|---|---|---|
| View own stock | STK-001 | — | ◐ | ◐ | ✖ | ✔ |
| Adjust own stock | STK-001, STK-003 | — | ◐ | ◐ | ✖ | A* |
| Adjust any seller's stock with audit reason | STK-003 | — | ✖ | ✖ | ✖ | A* |
| See stock movements of another seller | STK-003 | — | ✖ | ✖ | ✖ | A* |

### 3.4 Cart, checkout and orders

| Capability | REQ | C | S | Su | M | A |
|---|---|---|---|---|---|---|
| Use a cart and place an order | CRT-001, CRT-004 | ✔ | ✔ | ✔ | ✔ | — |
| Apply a coupon | CRT-002 | ✔ | ✔ | ✔ | ✔ | — |
| See own orders | ORD-001 | ◐ | ◐ | ◐ | ◑ | ✔ |
| See orders of another buyer | ORD-001 | ✖ | ✖ | ✖ | ✖ | A* |
| See sub-orders addressed to own store/supplier | ORD-002 | — | ◐ | ◐ | ✖ | ✔ |
| Confirm / prepare a sub-order | ORD-002 | — | ◐ | ◐ | ✖ | A* |
| Register shipment with waybill | ORD-009 | — | ◐ | ◐ | ✖ | A* |
| Reject an order and refund to buyer | ORD-004 | — | ✖ | ◐ | ✖ | A* |
| Mark an order "incomplete" | ORD-005 | — | ✖ | ◐ | ✖ | A* |
| Cancel an order | ORD-004 | ◐ | ✖ | ◐ | ✖ | A* |
| Freeze preparation via complaint | ORD-007 | ◐ | — | — | ✖ | A* |
| Attach voice / image / file to an order | ORD-008 | ◐ | ◐ | ◐ | ◑ | ✔ |
| Register a customer order for a visited store | ORD-011 | ✖ | ✖ | ✖ | ◐ | ✔ |
| Change order status outside the state machine | ORD-002 | ✖ | ✖ | ✖ | ✖ | ✖ |
| Read orders of the whole platform | ORD-001 | ✖ | ✖ | ✖ | ✖ | A* |

### 3.5 Invoices, payments and finance

| Capability | REQ | C | S | Su | M | A |
|---|---|---|---|---|---|---|
| View own invoice | INV-001 | ◐ | ◐ | ◐ | ◑ | ✔ |
| View invoice of a store the marketer visited | INV-004 | ✖ | ✖ | ✖ | ◐ | ✔ |
| View invoice of an unvisited store | INV-004 | ✖ | ✖ | ✖ | ✖ | ✔ |
| Issue a manual invoice | INV-003 | ✖ | ✖ | ✖ | ✖ | A* |
| Issue a supplementary invoice on own invoice | INV-002 | ✖ | ✖ | ◐ | ✖ | A* |
| Void / reverse an invoice | INV-003 | ✖ | ✖ | ✖ | ✖ | A* |
| Pay by gateway / wallet | PAY-001, PAY-005 | ✔ | ✔ | ✔ | ✔ | — |
| Choose a second gateway in one payment | PAY-002 | ✔ | ✔ | ✔ | ✔ | — |
| Manage gateways (activate, order, credentials) | PAY-001 | ✖ | ✖ | ✖ | ✖ | A* |
| View all transactions | PAY-003 | ✖ | ◐ | ◐ | ✖ | A* |
| Run reconciliation | PAY-004 | ✖ | ✖ | ✖ | ✖ | A* |
| View own wallet and ledger | FIN-001, FIN-003 | ◐ | ◐ | ◐ | ◐ | ✔ |
| Adjust any wallet / ledger balance | FIN-005 | ✖ | ✖ | ✖ | ✖ | A* |
| Request a settlement | FIN-002 | ✖ | ◐ | ◐ | ◐ | — |
| Approve / hold / reject a settlement | FIN-002 | ✖ | ✖ | ✖ | ✖ | A* |
| Generate bank batch file | FIN-006 | ✖ | ✖ | ✖ | ✖ | A* |
| View settlement report of another seller | FIN-008 | ✖ | ✖ | ✖ | ✖ | A* |
| Approve a delay compensation | CMP-001 | ✖ | ✖ | ✖ | ✖ | A* |
| Dispute an applied compensation | CMP-001 | — | — | ◐ | — | — |
| Edit commission rates of a marketer | MKT-005 | ✖ | ✖ | ✖ | ✖ | A* |
| Approve a return and refund route | RTN-001, RTN-002 | ✖ | ✖ | ✖ | ✖ | A* |
| Request a return | RTN-001 | ◐ | — | — | — | — |
| View platform-wide revenue | RPT-001 | ✖ | ✖ | ✖ | ✖ | A* |

### 3.6 Loyalty and promotions

| Capability | REQ | C | S | Su | M | A |
|---|---|---|---|---|---|---|
| Read own points history and tier | LOY-001, LOY-004 | ✔ | ✔ | ✔ | ✔ | — |
| Spend a reward / voucher on an invoice | LOY-006, LOY-018 | ✔ | ✔ | ✔ | ✔ | — |
| Grant or deduct points manually | LOY-005 | ✖ | ✖ | ✖ | ✖ | A* |
| Edit loyalty rule sets | LOY-001, LOY-002 | ✖ | ✖ | ✖ | ✖ | A* |
| Edit discount tiers (4 % / 5 % / fixed) | LOY-009, LOY-010 | ✖ | ✖ | ✖ | ✖ | A* |
| Create coupon codes | PRM-001 | ✖ | ✖ | ✖ | ✖ | A* |
| Create a festival campaign | PRM-002 | ✖ | ✖ | ◐ | ✖ | A* |
| Request a banner slot | MER-009 | ✖ | ✖ | ◐ | ✖ | — |
| Approve a banner slot | MER-009 | ✖ | ✖ | ✖ | ✖ | A* |
| Buy a notification campaign | NTF-002 | ✖ | ✖ | ◐ | ✖ | — |
| Send a platform broadcast | NTF-001 | ✖ | ✖ | ✖ | ✖ | A* |
| Send SMS to a customer segment | NTF-003 | ✖ | ✖ | ◐ | ✖ | A* |
| See the customer list behind a segment | NTF-003 | ✖ | ✖ | ✖ | ✖ | A* |
| Run a poll / survey | NTF-006 | ✖ | ✖ | ✖ | ✖ | A* |

### 3.7 Marketing and field operations

| Capability | REQ | C | S | Su | M | A |
|---|---|---|---|---|---|---|
| Own referral code, link and QR | MKT-001 | ✖ | ✖ | ✖ | ◐ | ✔ |
| Register a store on behalf of the platform | MER-001 | ✖ | ✖ | ✖ | ✔ | A* |
| Check store duplication by national ID / postal / mobile | MER-002 | ✖ | ✖ | ✖ | ✔ | ✔ |
| Edit a store created by self within 5 minutes | MER-005 | ✖ | ✖ | ✖ | ◐ | A* |
| Edit a store after the 5-minute window | MER-005 | ✖ | ✖ | ✖ | ✖ | A* |
| Request an edit to a store record | MER-005 | ✖ | ◐ | ✖ | ◐ | — |
| View the store list of own network | MKT-003 | ✖ | ✖ | ✖ | ◐ | ✔ |
| View a store outside own network | MKT-003 | ✖ | ✖ | ✖ | ✖ | ✔ |
| Capture a visit with GPS and photos | GEO-001, GEO-004 | ✖ | ✖ | ✖ | ◐ | ✔ |
| See live marketer tracking | GEO-002 | ✖ | ✖ | ✖ | ✖ | A* |
| See own performance analysis | GEO-002 | ✖ | ✖ | ✖ | ◐ | ✔ |
| See another marketer's performance | GEO-002 | ✖ | ✖ | ✖ | ✖ | A* |
| Reassign a store to another marketer | MKT-003 | ✖ | ✖ | ✖ | ✖ | A* |
| Disable a marketer's commission | MKT-005 | ✖ | ✖ | ✖ | ✖ | A* |
| Disable a marketer overall (suspend) | MKT-005 | ✖ | ✖ | ✖ | ✖ | A* |
| Approve or reject a visit report | GEO-003 | ✖ | ✖ | ✖ | ✖ | A* |

### 3.8 Content, community and support

| Capability | REQ | C | S | Su | M | A |
|---|---|---|---|---|---|---|
| Write / reply to reviews for purchased items | RVW-001, RVW-002 | ◐ | ◐ | ◐ | ✖ | — |
| Moderate or delete a review | RVW-004 | ✖ | ✖ | ✖ | ✖ | A* |
| Post a classified ad | CLS-001 | ✔ | ✔ | ✔ | ✔ | — |
| Edit / delete own ad | CLS-001 | ◐ | ◐ | ◐ | ◐ | A* |
| Approve or remove an ad | CLS-001 | ✖ | ✖ | ✖ | ✖ | A* |
| Edit own Patogh provider profile | PTG-001 | ◐ | ◐ | ◐ | ✖ | A* |
| Publish a Patogh profile (requires approval) | PTG-001 | ✖ | ✖ | ✖ | ✖ | A* |
| Submit a tender | TND-001 | ✔ | ✔ | ✔ | ◐ | — |
| Bid on a tender | TND-003 | ✖ | ◐ | ◐ | ✖ | — |
| Open / close an auction | TND-002 | ✖ | ✖ | ◐ | ✖ | A* |
| Request a sample / catalogue | SMP-001 | ✔ | ✔ | ✖ | ✔ | — |
| Fulfil a sample request | SMP-001 | ✖ | ◐ | ◐ | ✖ | — |
| Chat with users of allowed roles | MSG-001, MSG-003 | ✔ | ✔ | ✔ | ✔ | A* |
| Read a chat thread one is not a participant of | MSG-003 | ✖ | ✖ | ✖ | ✖ | A* |
| Open and answer tickets | MSG-004 | ✔ | ✔ | ✔ | ✔ | A* |
| Report a user or a content item | USR-005 | ✔ | ✔ | ✔ | ✔ | — |
| Edit static pages, menus, footer, blog | CMS-002, CMS-001 | ✖ | ✖ | ✖ | ✖ | A* |
| Configure home widgets and the notification bar | CMS-005, NTF-005 | ✖ | ✖ | ✖ | ✖ | A* |

### 3.9 Platform administration

| Capability | REQ | C | S | Su | M | A |
|---|---|---|---|---|---|---|
| Access the admin panel | ADM-010 | ✖ | ✖ | ✖ | ✖ | A* |
| Pass 2FA challenge before any admin render | IAM-004 | ✖ | ✖ | ✖ | ✖ | A* |
| Manage role catalogue and role permissions | ADM-011 | ✖ | ✖ | ✖ | ✖ | A* |
| Configure allowed secondary-role combinations | ADM-006 | ✖ | ✖ | ✖ | ✖ | A* |
| Read the audit log | ADM-009 | ✖ | ✖ | ✖ | ✖ | A* |
| Delete audit entries | ADM-009 | ✖ | ✖ | ✖ | ✖ | ✖ |
| Export any report to Excel | RPT-003, ADM-008 | ✖ | ◐ | ◐ | ◐ | A* |
| Trigger and restore a backup | CTR-007 | ✖ | ✖ | ✖ | ✖ | A* |
| Configure general site settings | ADM-007 | ✖ | ✖ | ✖ | ✖ | A* |
| Turn off an optional module (classifieds, Patogh) | ADM-007 | ✖ | ✖ | ✖ | ✖ | A* |
| Clear the approval queue in bulk | ADM-012 | ✖ | ✖ | ✖ | ✖ | A* |

---

## 4. Scoping rules

Authorization has two layers: a **capability** decision (does this role hold the ability?) and a
**scope** constraint (which records?). Only the second prevents IDOR, so it lives in query
builders, not in views.

### 4.1 Marketer scope — own network only

Basis: appendix 3 §3-2 cl.10 («فاکتورهای … همکارانی که خودش ویزیت کرده است»), appendix 5 cl.29
(«دسترسی محدود بازاریاب به اطلاعات فروشگاه‌های مربوط به خود»), appendix 6 cl.10–11.

- Scope key: `mer_stores.owner_marketer_id = actor.id` **or** an active
  `mkt_store_assignments` row (current holder), never a historical one.
- Applies to: store list/detail, store invoices (read-only), visits, customer records the marketer
  created, commission rows.
- Explicitly excluded: store wallet balance, settlement figures, other marketers' names and
  performance, platform-wide counts beyond their own network.
- A store reassigned by an admin moves out of the previous marketer's scope immediately; the
  previous marketer keeps read access to **invoices already attributed** to him (append-only
  attribution table), but not to the store profile. This is the only historical carve-out, and it
  is tested (`NEG-MKT-07`).
- Reports (`/marketer/performance`) aggregate only the actor's rows; aggregates are computed in
  SQL with the scope predicate, not by fetching everything and filtering in PHP.

### 4.2 Store scope — own catalog, own orders

- Scope keys: `cat_products.seller_id`, `ord_sub_orders.seller_id`, `inv_invoices.seller_id`,
  `stk_*` per seller, `fin_*` accounts per seller.
- A store sees a parent order **only** through its own sub-order summary (buyer name and delivery
  city as needed to ship), never the other sellers' lines or totals.
- Store cannot read its own `buyer` price tier of another store; price lists are per seller.
- Store staff accounts are not in contract scope; one login per store.

### 4.3 Supplier scope — own invoices and own catalog

- Same shape as store scope, plus: agencies list, festival participation, banner requests,
  notification campaigns, auctions it created.
- Supplier sees the buyer's identity for fulfilment but not the buyer's wallet, points or other
  orders.
- Compensation rows are visible to the supplier they were deducted from, read-only, with the
  calculation shown (`qty × unit price × delay percent`).

### 4.4 Consumer scope

- Own orders, own invoices, own wallet/points, own addresses, own reviews, own ads, own tickets.
- Consumer may never see another buyer's order even within the same sub-order conversation.
- A consumer who is also a store keeps the two scopes separate by panel; `/account` never renders
  wholesale prices.

### 4.5 Admin scope and separation of duties

- Admin is platform-wide, but three constraints remain:
  1. **Separate authentication** (admin guard + 2FA) — an admin cannot act on the storefront and a
     storefront session cannot reach `/admin` (appendix 1 cl.7).
  2. **Audited mutation** — every admin write to money, role, price, settlement, refund, document
     approval or user status appends to `adm_audit_logs` with actor, target, before/after and
     reason (appendix 1 cl.10).
  3. **No deletion of audit rows** — the table is append-only; corrections are new entries.
- Admin permission granularity is capability-based (matrix §3.9). Two admin sub-roles ship in v1:
  `admin` (all) and `admin.support` (read-only on users/orders, no money, no role changes). This is
  an internal split, not a contract role, and exists so support staff cannot move money.

### 4.6 Secondary roles

- The route's panel decides which grant is evaluated: `/store/*` never accepts a `marketer` grant,
  `/marketer/*` never accepts a `store` grant.
- Capabilities are **not** unioned across panels. A `consumer+marketer` user browsing `/` gets
  consumer prices; inside `/marketer` they can register stores. A `store+supplier` user gets two
  panels with two separate catalogs and two separate ledgers.
- A user cannot grant themselves a role, and cannot hold two roles where the combination is
  disabled (`adm_role_combinations`). Attempting it returns 403 and writes an audit entry
  (`NEG-ROLE-03`).

### 4.7 Derived-data leaks

Counts, totals, suggestions and exports leak as effectively as records. Rules:

| Surface | Rule |
|---|---|
| Dashboard KPI | computed with the same scope predicate as the list it links to |
| Pagination totals | `count()` on the scoped query only |
| Search suggestions | never include records the actor cannot open |
| Autocomplete of stores/customers | scoped; marketer autocomplete only own network |
| Exports (Excel/CSV) | built from the scoped query, not from the current page; export jobs store the actor and re-check the scope at generation time |
| Error messages | never include another tenant's identifier, name or amount |
| 403 vs 404 | one body for "exists but forbidden" and "does not exist"; no oracle |

### 4.8 Data classification and masking

| Class | Examples | Handling |
|---|---|---|
| Public | product data, store name, city, public rating | open |
| Internal | order volumes, stock levels | role-scoped |
| Confidential | phone numbers, addresses, IBAN, national ID, business licence, store photos with GPS | masked in lists (`۰۹۱۲***۴۵۶۷`), full value only on a record the actor owns or an admin with `A*`; every reveal is audited |
| Secret | gateway credentials, API keys, SMS provider keys | never rendered; admin sees only "configured / missing" (appendix 1 cl.7, canonical-context §0.6) |

Documents (national ID, licence, cheque) live on the private disk and are served through
short-lived signed URLs (5 minutes, single use) with the policy checked before signing.

### 4.9 Time-based authority

- Marketer edit window: 5 minutes from store creation (appendix 6 cl.9), enforced server-side on
  the record (`created_at + 5m > now()`), not by hiding the button. After expiry the same form
  submits an `mer_edit_requests` row for admin approval.
- Complaint freeze: an open complaint blocks supplier payout until resolved or 7 days pass
  (appendix 5 cl.32); the payout service checks the freeze, the UI merely explains it.
- Reward/coupon validity is checked at apply time and again at payment capture.

### 4.10 Money-movement authority

Money writes are the highest-risk operations. Required actor and evidence:

| Operation | Actor | Extra requirement |
|---|---|---|
| Refund to buyer IBAN on order rejection | supplier (own sub-order) | order in a refundable state, IBAN belongs to the buyer on record, reason, audit |
| Cancel + refund | supplier / admin | same as above; admin override requires reason |
| Wallet adjustment | admin only | reason code + reference, audit, appears in the user's ledger |
| Settlement approval | admin only | amount ≤ derived balance, statement snapshot stored |
| Commission override | admin only | reason, before/after, audit |
| Manual invoice | admin only | buyer exists, lines priced from a price list, audit |
| Coupon creation | admin only | usage cap required |

No supplier or store may move money to an account other than the one stored on the buyer/seller
record. There is no free-text IBAN field in any refund flow.

---

## 5. Negative tests that must fail

Every row is a Pest feature test in `tests/Feature/Authorization/`. Expected response is `403`
with the shared forbidden body (no resource data, no name, no amount). "No leak" means the
response body contains neither the record identifier nor any of its attributes.

| ID | Actor | Request | Expected | Why it matters | REQ |
|---|---|---|---|---|---|
| NEG-GUARD-01 | store session | `GET /admin` | 403, admin login redirect, no admin chrome | guard separation | IAM-004 |
| NEG-GUARD-02 | admin session | `GET /store/orders` | 403 / redirect to storefront login | guard separation | IAM-004 |
| NEG-GUARD-03 | admin without 2FA | `GET /admin/users` | redirect to `/auth/two-factor` | 2FA mandatory | ADM-010 |
| NEG-IDOR-01 | store A | `GET /store/orders/{subOfB}` | 403, no leak | sub-order ownership | ORD-002 |
| NEG-IDOR-02 | store A | `POST /store/orders/{subOfB}/ship` | 403, no state change | write on foreign record | ORD-009 |
| NEG-IDOR-03 | supplier A | `GET /supplier/invoices/{invOfB}` | 403, no leak | invoice ownership | INV-004 |
| NEG-IDOR-04 | supplier A | `POST /supplier/invoices/{invOfB}/supplementary` | 403, no invoice created | financial write | INV-002 |
| NEG-IDOR-05 | supplier A | `POST /supplier/orders/{subOfB}/reject` | 403, no refund | money movement | ORD-004 |
| NEG-IDOR-06 | consumer A | `GET /account/orders/{orderOfB}` | 403, no leak | order privacy | ORD-001 |
| NEG-IDOR-07 | consumer A | `GET /account/wallet` with `user_id=B` in query | own wallet only | parameter tampering | FIN-001 |
| NEG-IDOR-08 | marketer A | `GET /marketer/network/{storeOfB}` | 403, no leak | network scope | MKT-003 |
| NEG-IDOR-09 | marketer A | `GET /marketer/invoices?store={storeOfB}` | empty result, no rows | scoped query, not view filter | INV-004 |
| NEG-IDOR-10 | marketer A | `GET /marketer/network/{storeOfB}/edit` | 403 | edit scope | MER-005 |
| NEG-IDOR-11 | marketer A | `POST /marketer/register-store` with `marketer_id=B` | stored under actor A, 422/403 if forced | attribute injection | MER-001 |
| NEG-IDOR-12 | store A | `POST /store/products` with `seller_id=B` | product stored under A (payload field ignored) | mass assignment | CAT-002 |
| NEG-IDOR-13 | store A | `PUT /store/products/{productOfB}` | 403, unchanged | cross-tenant write | CAT-002 |
| NEG-IDOR-14 | supplier A | `GET /supplier/orders/{subOfB}/attachments/{file}` | 403, file not streamed | signed URL without policy | ORD-008 |
| NEG-IDOR-15 | consumer A | `GET /storage/private/ids/{docOfB}` | 403, no signed URL issued | document privacy | MER-006 |
| NEG-IDOR-16 | store | `GET /admin/users/{any}` | 403 | admin-only read | ADM-001 |
| NEG-IDOR-17 | marketer | `GET /admin/marketers/{other}` | 403 | admin-only read | ADM-008 |
| NEG-IDOR-18 | store A | `GET /account/messages/{threadOfB}` | 403 | chat isolation | MSG-003 |
| NEG-IDOR-19 | consumer A | `POST /account/messages/{threadOfB}/reply` | 403 | chat write | MSG-003 |
| NEG-IDOR-20 | any non-admin | `GET /admin/reports/sales` | 403 | revenue exposure | RPT-001 |
| NEG-FIN-01 | store | `POST /store/settlements/request` with amount > derived balance | 422, no settlement | balance integrity | FIN-002 |
| NEG-FIN-02 | store | `POST /store/settlements/{id}/approve` | 403 | approval separation | FIN-002 |
| NEG-FIN-03 | supplier | `POST /supplier/orders/{sub}/reject` with `iban=attacker` | 422, refund uses buyer IBAN on record | refund redirection | PAY-006 |
| NEG-FIN-04 | admin.support | `POST /admin/users/{u}/wallet/adjust` | 403 | money outside support role | FIN-005 |
| NEG-FIN-05 | admin | `POST /admin/settlements/{s}/approve` without reason | 422 | audit evidence required | FIN-003 |
| NEG-FIN-06 | store | `GET /store/settlements/statements?store={B}` | own statement only | statement scope | FIN-008 |
| NEG-FIN-07 | admin | `POST /admin/ledger/{entry}/delete` | 405/403 | append-only ledger | FIN-003 |
| NEG-FIN-08 | `store+supplier` user | place an order whose seller is their own supplier entity | 422, order rejected | self-dealing | PRI-001, FIN-003 |
| NEG-PRICE-01 | consumer | `POST /checkout` with manipulated `unit_price` | server price wins, 422 on mismatch | price tampering | PRI-001 |
| NEG-PRICE-02 | consumer | `GET /p/{product}` with `tier=colleague` | consumer price rendered | tier escalation | PRI-003 |
| NEG-PRICE-03 | marketer | `GET /p/{product}` inside `/marketer` expecting colleague price | colleague price only if role grants, otherwise consumer | price by role | PRI-001 |
| NEG-COUP-01 | consumer | apply same coupon twice beyond cap | 422 on second use | coupon abuse | PRM-001 |
| NEG-COUP-02 | consumer | apply coupon of another audience segment | 422 | audience scoping | PRM-001 |
| NEG-LOY-01 | consumer | `POST /account/points/redeem` with amount > balance | 422 | points integrity | LOY-006 |
| NEG-LOY-02 | store | `POST /admin/loyalty/adjustments` | 403 | admin-only points | LOY-005 |
| NEG-ROLE-01 | user | `POST /account/profile` with `roles[]=admin` | field ignored, no role change | mass assignment | IAM-005 |
| NEG-ROLE-02 | store | add `marketer` role where the combination is disabled | 403 + audit entry | privilege escalation | ADM-006 |
| NEG-ROLE-03 | admin | grant `admin` as a secondary role | 422 | admin is never combined | ADM-006 |
| NEG-ROLE-04 | marketer | `GET /store` after gaining `store` role without approval | 403 until the grant is effective | delayed grant | IAM-005 |
| NEG-TIME-01 | marketer A | `PUT /marketer/network/{ownStore}` after 5 minutes | 403 on direct save; only edit-request accepted | time-boxed authority | MER-005 |
| NEG-TIME-02 | store | confirm shipment on a cancelled sub-order | 409, no state change | state machine | ORD-002 |
| NEG-TIME-03 | supplier | payout attempted while a complaint is open | blocked, hold reason shown | freeze rule | ORD-007 |
| NEG-ADM-01 | admin | `POST /admin/roles/{role}/permissions` removing own last permission | 422 | lockout prevention | ADM-011 |
| NEG-ADM-02 | admin | `GET /admin/audit-log?delete=1` | 405/403 | audit immutability | ADM-009 |
| NEG-EXP-01 | marketer | export invoices (job) | file contains only own-network rows | export scope | RPT-003 |
| NEG-EXP-02 | store | export orders | only own sub-orders, no buyer wallet data | export scope | RPT-003 |
| NEG-NTF-01 | supplier | create notification with an audience not permitted by quota | 422 | quota abuse | NTF-002 |
| NEG-NTF-02 | supplier | `GET /supplier/sms/segments/{id}/export` | 403 | customer list protection | NTF-003 |
| NEG-CMS-01 | store | `PUT /admin/cms/pages/{page}` | 403 | content authority | CMS-002 |
| NEG-API-01 | any token | `GET /api/v1/orders` | only own orders | API parity with web | ORD-001 |
| NEG-API-02 | revoked token | any `/api/v1/*` | 401 | Sanctum revocation | IAM-008 |
| NEG-XSS-01 | supplier | product description containing `<script>` | sanitized on save and render | stored XSS | CTR-006 |
| NEG-UPL-01 | store | upload `.php` as product image | 422, file rejected | malicious upload | CTR-006 |

Suite rule: every scoped resource (anything with a `seller_id`, `store_id`, `supplier_id`,
`marketer_id`, `user_id` or `thread_id`) must ship at least one `NEG-IDOR-*` test in the same PR
that adds the route. A PR that adds a scoped route without a negative test fails review
(canonical-context §7).

---

## 6. Secondary-role combinations

| Combination | Allowed by default | What the user gets | Guard rails |
|---|---|---|---|
| consumer + store | ✔ | both panels; wholesale prices only in `/store` | store catalog scope, consumer cart cannot use wholesale tier |
| consumer + marketer | ✔ (appendix 12 cl.11) | both panels; consumer prices in shop, own network in panel | marketer cannot see own consumer orders as network data |
| store + supplier | ✔ | two catalogs, two ledgers, two settlements | separate scopes; a supplier buying from itself is blocked (`NEG-FIN-08` below) |
| supplier + marketer | ✔ | producer panel + field network | commission computed only on attributed stores, never on own orders |
| consumer + supplier | ✔ | buys at consumer price, sells as producer | price tiers resolved per panel |
| marketer + store | ✖ (appendix 7 cl.6 example) | — | 403 on grant; admin may enable per deployment, the default is closed |
| store + store (second shop) | ✖ | not modelled in v1 | one store per account; a second shop needs a second account |
| admin + anything | ✖ | — | admin is a separate guard with no storefront session |

Additional test implied by this table: `NEG-FIN-08` — a `store+supplier` user cannot place an
order whose seller is their own supplier entity (self-dealing), and cannot approve their own
settlement.

Contract note: appendix 7 clause 6 gives the *example* that a store may not be a marketer, but
leaves the final list to admin configuration. The default table above implements the example and
keeps the door open through `adm_role_combinations` (screen SCR-ADM-079). This is recorded as a
configurable policy, not a hard-coded rule, to avoid contradicting a later owner decision.

---

## 7. Enforcement architecture

1. **Route middleware**: `auth:web` / `auth:admin`, then `role:<panel>`, then `verified.phone`,
   then `2fa` on admin. Order matters: authentication → 2FA → role → policy.
2. **FormRequest::authorize()** performs the capability check; the controller never decides.
3. **Policy methods** receive the actor and the model; for collections a `Scope` class
   (`StoreScope`, `SupplierScope`, `MarketerScope`, `CustomerScope`) is applied to the query
   builder at the repository boundary. A controller that calls `Model::find($id)` on a scoped
   resource fails code review; the pattern is `$this->scope->subOrders($actor)->findOrFail($id)`.
4. **Route model binding** is combined with the scope, so `findOrFail` on a foreign ID yields 404
   from the scoped query while an existing-but-forbidden record yields 403 from the policy. Both
   render the same body (§4.7).
5. **Livewire actions** re-run `authorize()` server-side on every call — a hidden button is never
   the check.
6. **Exports and jobs** carry the actor id and re-apply the scope at generation time; a stale job
   cannot widen scope.
7. **API parity**: `routes/api.php` uses Sanctum tokens with the same policies; token abilities
   are a subset of the role's capabilities, never a superset.
8. **Audit**: `adm_audit_logs` writes for login, price change, role change, settlement, refund,
   document approval, settings change (appendix 1 cl.10). The audit writer is a shared trait, not
   optional per controller.
9. **Response shape**: `403` = authenticated but not permitted (except where §4.7 requires one
   body); `401` = unauthenticated; `404` = not found *or* out of scope for a scoped query;
   `409` = state conflict; `422` = validation. The forbidden page names the required role in
   Persian, never the record.

---

## 8. Permission catalogue → routes and screens

| Ability prefix | Guards | Representative routes | Screens |
|---|---|---|---|
| `profile.*` | web | `/account/profile`, `/store/profile`, `/supplier/profile`, `/marketer/profile` | SCR-CUS-024, STR-025, SUP-032, MKT-021 |
| `catalog.view` | public | `/c/*`, `/p/*`, `/search` | SCR-PUB-002…010 |
| `products.*.own` | web+role | `/store/products*`, `/supplier/products*` | SCR-STR-002…005, SUP-002…005 |
| `products.*.any` | admin | `/admin/products*` | SCR-ADM-016…018 |
| `pricing.*` | web+role | `/store/prices`, `/supplier/prices` | SCR-STR-006, SUP-006 |
| `inventory.*.own` | web+role | `/store/inventory`, `/supplier/inventory` | SCR-STR-007, SUP-007 |
| `orders.view.own` | web | `/account/orders*`, `/store/orders*`, `/supplier/orders*` | SCR-CUS-002/003, STR-008/009, SUP-008/009 |
| `orders.view.any` | admin | `/admin/orders*` | SCR-ADM-022/023 |
| `invoices.view.own` | web | `/account/orders/{o}/invoice`, `/store/invoices`, `/supplier/invoices` | SCR-CUS-004, STR-011, SUP-014 |
| `invoices.view.network` | web+marketer | `/marketer/invoices` | SCR-MKT-012 |
| `finance.settle.own` | web | `/store/settlements*`, `/supplier/settlements*`, `/marketer/settlements` | SCR-STR-013/014, SUP-016/017, MKT-014 |
| `finance.settle.any` | admin | `/admin/settlements*` | SCR-ADM-030…032 |
| `loyalty.view.own` | web | `/account/points*` | SCR-CUS-014…017 |
| `loyalty.manage` | admin | `/admin/loyalty/*` | SCR-ADM-045…048 |
| `marketing.network.own` | web+marketer | `/marketer/network*`, `/marketer/visits*` | SCR-MKT-002…010 |
| `marketing.manage` | admin | `/admin/marketers/*`, `/admin/fieldops/*` | SCR-ADM-037…044 |
| `content.manage` | admin | `/admin/cms/*`, `/admin/blog*` | SCR-ADM-057…063 |
| `support.tickets` | web / admin | `/account/tickets`, `/store/tickets`, `/admin/tickets` | SCR-CUS-022, SCR-ADM-072 |
| `platform.settings` | admin | `/admin/settings/*`, `/admin/system/*` | SCR-ADM-077…084 |
| `audit.view` | admin | `/admin/audit-log` | SCR-ADM-003 |

---

## 9. Permission risks carried by the contract

These are the five places where the contract's wording creates a real authorization hazard. Each
one has a decision above and tests in §5; each is also an entry for the ambiguity register.

1. **Marketer reach into store financials (appendix 3 §3-2 cl.10 + appendix 5 cl.29).** The
   contract grants a marketer "invoices of colleagues he visited" and "limited access to the
   information of his own stores" without defining "information". A marketer who can read
   settlement figures or wallet balances learns the commercial terms of a shop that is also a
   competitor's customer. Decision: invoices read-only, no wallet/settlement, no other marketers,
   invoice attribution is append-only so a reassigned store does not silently widen anyone's
   history. Residual risk: invoice line prices still reveal wholesale terms; accepted because the
   clause explicitly requires the invoice view.
2. **Secondary-role escalation (appendix 7 cl.6, appendix 12 cl.11).** Allowing one extra level per
   user, configured by the admin, means the role pair defines the blast radius. A `store+marketer`
   user could self-attribute the stores he registers and earn commission on his own shop; a
   `supplier+consumer` user could buy at consumer price from a shop that buys from him. Decision:
   capability is evaluated per panel, the pair list is closed by default, self-dealing is blocked
   (`NEG-FIN-08`), and grant changes are audited. Residual risk: the admin can open a dangerous
   pair; the configuration screen warns with the concrete consequence before saving.
3. **Refund and rejection authority at the supplier (appendix 5 cl.15, appendix 11 cl.14).** A
   supplier may reject an order and trigger a refund to the buyer's IBAN. If the refund target were
   request-supplied, a supplier could redirect money. Decision: the IBAN is read from the buyer
   record, never from the request; IBAN changes on a buyer record require SMS confirmation and are
   audited; refunds are idempotent with a unique reference. Residual risk: a compromised supplier
   account can still deny service at scale — rate-limited and surfaced in the admin abuse queue.
4. **Admin concentration of power (appendix 7 cl.1–4, appendix 1 cl.7).** One role can edit any
   user, issue manual invoices, adjust wallets, approve settlements and change roles. Compromise of
   one admin account is a full-platform event. Decision: separate guard + mandatory 2FA,
   append-only audit, no impersonation, `admin.support` sub-role without money/role powers, and
   type-to-confirm + reason on every money or role mutation. Residual risk: no dual-control
   approval for settlements in v1 (single approver); recorded as a hardening item.
5. **Personal data captured in the field (appendix 5 cl.31, appendix 6 cl.2 and cl.7–9,
   appendix 14 cl.1).** Marketers upload owner photos, shopfront photos, business cards and GPS
   coordinates, and can edit the record for five minutes after creation. Photos of people plus
   location are personal data; a shared marketer login or a leaked link exposes them. Decision:
   private disk with 5-minute signed URLs, per-record policy, masking in lists, audit on reveal,
   the 5-minute window enforced server-side, and all later edits routed through admin approval.
   Residual risk: marketer devices are outside our control — the contract requires field capture,
   so the mitigation is short-lived URLs plus access logging, not prevention.

Additional risks worth tracking, beyond the top five: notification/SMS audience selection can be
turned into a customer-list export if the segment preview is not scoped (`NEG-NTF-02`); bulk Excel
exports are the easiest way to exfiltrate a scoped dataset (`NEG-EXP-01/02`); and the "membership
transfer" clause (appendix 5 cl.39) is an account-takeover vector if approval is not strictly
admin-only with re-verification of the receiving person's mobile.

Ambiguities raised here (to be registered in the project ambiguity register described in
canonical-context §6): the definition of a marketer's
"information" scope; the final allowed secondary-role list; whether a supplier may refund to a
wallet instead of a bank account; whether settlement needs dual approval. Money ownership and
settlement timing remain governed by `AMB-FIN-001` (canonical-context §5).
