# Agent Decision Log

| | |
|---|---|
| **Purpose** | The running log of decisions taken *by us* where the contract was silent or contradictory, each with its rationale and the exact procedure to reverse it. |
| **Audience** | Engineers implementing a rule (this is the "why"), reviewers, and the owner when reviewing what we assumed on their behalf. |
| **Status** | Baseline v1.0 — 35 decisions; 20 carry the `AGENT_DECISION` label, 15 restate `TECHNICAL_CONSTRAINT` or `OWNER_DECISION` from [`../canonical-context.md`](../canonical-context.md) §5. |
| **Source** | `docs/canonical-context.md` §5 (engineering baselines); contract pages 1–11; ambiguity rows in [`ambiguity-register.md`](ambiguity-register.md). |

## 0. What a decision here is — and is not

A decision in this log is **a defensible reading of an ambiguous clause, chosen so that work can proceed, with the reversal documented**. It is never:

- a replacement for a contract clause (if the contract says something, the contract wins);
- an owner decision (those are marked `OWNER_DECISION` and require the client's written answer);
- permanent. Every entry names the file/flag/config that would change, so a late answer costs a migration, not a rewrite.

Rules for the log:

1. **One decision per rule.** If two rules are entangled (money + settlement), they get two entries that reference each other.
2. **No decision without an ambiguity or a canonical baseline.** If there is neither, it is an implementation detail, not a decision.
3. **Append, never renumber.** A superseded decision keeps its number and gains a `Superseded by DEC-0NN` line.
4. **State the blast radius.** "How to change it later" is mandatory; "none" is not an acceptable answer.

## 1. Foundations

### DEC-001 — Modular monolith, module-per-bounded-context
**Decision.** One Laravel application, 28 modules under `app/Modules/<Module>/` with `Domain/Application/Infrastructure/Http`, cross-module calls only through application services or domain events. No microservices, no shared database tables across modules.
**Basis.** `TECHNICAL_CONSTRAINT` — canonical §3, §5.
**Rationale.** 203 contract clauses over 28 contexts is large but not distributed-system large; one deployable keeps the 90-day schedule survivable and makes transactions (money, stock, points) trivial instead of eventual.
**How to change.** Extract a module into a service when it has its own scaling or failure profile; the module boundary is already the seam, so extraction is mechanical.
**Ruled on.** none.

### DEC-002 — Money as integer Toman through one value object
**Decision.** All amounts are `BIGINT` columns named `*_amount`, in Toman, never float, never a decimal string; every operation goes through `App\Shared\Money`; percentages produce integer results with a documented rounding rule (floor in the customer's favour, remainder retained where a rule needs it).
**Basis.** `TECHNICAL_CONSTRAINT` — canonical §5; contract prices are Toman (page 2 / ماده ۲).
**Rationale.** The contract mixes percentages (0.5 %/day, 5 %, 15 %, 0.75 %, 4 %) with fixed Toman amounts; floats make penalty and reward arithmetic non-reproducible, and the audit log then proves nothing.
**How to change.** A currency column per row plus a rate table would allow multi-currency; `Money` is the only place that would change.
**Ruled on.** `AMB-CTR-002`.

### DEC-003 — Platform collects funds; ledger is the truth; settlement is periodic
**Decision.** The platform is the collector of customer money. Every movement is an append-only `fin_ledger_entries` row with a unique `reference`; balances are derived, never stored as editable numbers; corrections are reversal entries, never updates. Supplier payable is recognised when the sub-order is delivered and the return/complaint window closes, then paid out in a periodic settlement run. Refunds go back to the wallet by default, and to شبا where the contract names it (supplier rejection, page 8 / پیوست ۵ بند ۱۵).
**Basis.** `AGENT_DECISION` — the contract requires wallets, deposits, compensation credits, refunds and «تسویه دستی با تولیدکنندگان» (page 11 / پیوست ۱۳ بند ۱–۳, پیوست ۱۲ بند ۱۳) but never says who holds funds.
**Rationale.** It is the only reading under which every appendix clause is implementable at once, and it is auditable: any balance can be recomputed from rows. It also keeps `AMB-FIN-002` (15 % profit share) computable from the same revenue data.
**How to change.** If the owner is legally a mere introducer (`AMB-FIN-001` option b), the change is: stop recognising supplier payable on delivery, add a direct-payment instruction per sub-order, keep the ledger for commissions only. Schema survives; the settlement screens are removed.
**Ruled on.** `AMB-FIN-001`, `AMB-RTN-001`; costs noted in `AMB-FIN-002`.

### DEC-004 — Parent order + per-seller sub-orders; proxy orders keep the consumer as buyer
**Decision.** `ord_orders` is the customer's basket-level document; `ord_sub_orders` is the unit of fulfilment, invoicing, shipping, complaint, delay compensation and settlement — one per seller. When an intermediary (رابط) orders for a consumer, the consumer stays the buyer of record with an explicit proxy link: points, warranty and complaint rights attach to the consumer, the intermediary is a recorded actor.
**Basis.** `AGENT_DECISION` — the contract is multi-vendor and per-supplier throughout (page 10 / پیوست ۱۱, page 11 / پیوست ۱۳) but never defines order granularity; page 11 / پیوست ۱۲ بند ۳ introduces the intermediary without defining their standing.
**Rationale.** Sub-orders are the only structure in which five contract mechanisms (per-seller delay compensation, freeze, invoice, shipping, settlement) work without hacks. The proxy reading protects the consumer's statutory expectations while keeping the shop's operational role.
**How to change.** Splitting is a data migration across order, invoice, payment-allocation and settlement tables — do it before the first real order, not after (`AMB-ORD-005`, High reversibility cost).
**Ruled on.** `AMB-ORD-005`, `AMB-ORD-007`.

## 2. Pricing and loyalty

### DEC-005 — One server-side price pipeline, snapshotted per line
**Decision.** Every price is resolved on the server in a fixed sequence: base price → role/tier price → volume price → promotion/festival → coupon/bon → first-invoice discount → loyalty tier discount → reward deduction. Each step writes its rule id, version and delta onto the order line; the line stores the resulting unit price and total. Client-side prices are display only. Coupons: one per order. Total discount is capped per invoice at a configurable maximum. Supplier price changes above a configurable threshold require admin approval; below it they apply immediately, with audit.
**Basis.** `AGENT_DECISION` — eight reduction mechanisms exist across page 5–11 and no clause states their order, their exclusivity or their rounding (`AMB-PRI-001`).
**Rationale.** Without a fixed, recorded order, two developers implement "the same" invoice differently and neither can explain a receipt to a buyer. Recording the firing rule makes every dispute answerable with data.
**How to change.** The pipeline is an ordered list of rule classes in config; reordering or inserting a step changes results going forward while historical lines keep their snapshot (`AMB-PRI-003`). A different cap is a settings value.
**Ruled on.** `AMB-PRI-001`, `AMB-PRI-002`, `AMB-PRI-003`, `AMB-LOY-001`, `AMB-PRM-001`.

### DEC-006 — Versioned points/reward rule sets with a replay command
**Decision.** All points and reward parameters live in `loy_rule_sets` with `effective_from`; an invoice is evaluated against the rule set in force at issue time and the evaluation is stored (`loy_rule_evaluations`). Rules implemented: four volume coefficients (0.75/0.5/0.25/0.2 %) as amount bands with admin thresholds; next-invoice reward deducted in full only when the next invoice is ≥ 4× the reward, otherwise half is applied and **the other half stays available** until expiry; points accrue on every invoice regardless of claim; inactivity deduction with a floor at zero and a pre-deduction notice; 1 star per 100 points, unlimited; tier discount boundaries resolved **upward** (100 → 5 %, 200 → 5 % upper band), so exactly-100 is not in both tiers; referral points enter the same balance but are source-tagged.
**Basis.** `AGENT_DECISION` — page 6–7 / پیوست ۴ بند ۳–۹, بند ۲۰; page 8 / پیوست ۵ بند ۱۶–۱۷, بند ۲۱.
**Rationale.** The contract gives coefficients but no thresholds, no boundary rule and no statement on what happens to the unapplied half. A replay command turns "why did this invoice earn 37 points?" into a test rather than an investigation.
**How to change.** New bands, coefficients or thresholds are new rule-set rows with a later `effective_from` — no code change, no data migration. Reverting a band is a new rule set, never an edit of history.
**Ruled on.** `AMB-LOY-002`, `AMB-LOY-003`, `AMB-LOY-004`, `AMB-LOY-005`, `AMB-LOY-006`.

### DEC-028 — Consumer price = colleague price × configurable uplift (default 35 %)
**Decision.** Consumer price is derived per product from the colleague price with a global default multiplier of 35 %, overridable per product and per category; it is stored as a resolved price, never computed at render time from a hard-coded constant.
**Basis.** `AGENT_DECISION` — page 11 / پیوست ۱۲ بند ۱ gives 35 % as an example («مثلاً»), not as a rule.
**Rationale.** An example in a contract is a hint, not a setting; making it configurable keeps a promotion from silently inverting the consumer/colleague relationship.
**How to change.** Settings value or per-product override; no deploy.
**Ruled on.** `AMB-PRI-004`.

### DEC-025 — Compensation as data: plans, basis, statements, transfers; profit share out of code
**Decision.** Marketer plans are configuration rows (rate, basis, qualifying threshold, fixed-per-visit component, salary flag, payout mix), not enum cases. Commission accrues on invoice issue, **vests on collection net of returns and cancellations**, and is reversed by a compensating entry on refund. Each beneficiary gets a periodic statement with per-invoice lines and a dispute path. Store re-attribution on marketer change is effective from admin approval, applies to future invoices only, and keeps an attribution-history table. The 15 % net-profit share (ماده ۱۶) is **not modelled in code**; only the revenue/cost report that feeds it is.
**Basis.** `AGENT_DECISION` — page 9 / پیوست ۶ بند ۳–۶; page 7 / پیوست ۴ بند ۲۲–۲۴; page 4 / ماده ۱۶.
**Rationale.** The contract names four plan letters and never defines one, so any hard-coded plan is a guess with a sprint-sized correction cost. Vesting on collection prevents paying commission on money that never arrived — the failure mode that makes marketers and finance enemies.
**How to change.** Plan parameters are forms. Switching the vesting basis to "on invoice" is one policy class change plus a data decision about existing accruals.
**Ruled on.** `AMB-CMP-001`, `AMB-CMP-002`, `AMB-CMP-003`, `AMB-CMP-004`, `AMB-MER-003`, `AMB-FIN-002`, `AMB-FIN-004`.

### DEC-027 — Single-level referral: record depth, pay level one only
**Decision.** Every user may store one `referred_by` link (chain is preserved), points are awarded to the direct inviter only, and no commission or reward is paid at depth ≥ 2.
**Basis.** `AGENT_DECISION` — page 7 / پیوست ۴ بند ۱۳ pays «فرد دعوتکننده» only; the contract title says «بازاریابی چندمرحلهای» but no clause defines second-level payouts (`AMB-MKT-001`).
**Rationale.** Paying a downline from an undefined rate is both an unauditable liability and a regulatory exposure (pyramid-scheme rules). Storing the chain means a later multi-level policy needs no backfill.
**How to change.** Add a per-level rate table and a payout job; the graph already exists.
**Ruled on.** `AMB-MKT-001`.

## 3. Payments and finance operations

### DEC-007 — One `GatewayDriver` contract, 12 registry slots, one active gateway per payment
**Decision.** A single driver interface (`initiate`, `verify`, `refund`, `status`) with 12 registry slots; v1 ships one fully implemented production driver and the rest registered but inactive until credentials and behaviour are confirmed; admin activates per gateway and per currency; every transaction is logged with gateway reference and reconciled daily against a stored statement, mismatches entering an admin queue with a written-off/replayed decision. Two gateways in **one** transaction is deferred (`RB-PAY-01`), with the idempotent `reference` model already in place so it can be added.
**Basis.** `AGENT_DECISION` + `TECHNICAL_CONSTRAINT` — page 5 / پیوست ۱ بند ۵ and page 11 / پیوست ۱۳ بند ۷ require 12 gateways and two-at-once; canonical §5 defers split payment (`AMB-PAY-001`, `AMB-PAY-002`).
**Rationale.** Writing 12 drivers without merchant credentials produces 12 lies. An interface plus one real driver plus an honest registry is the only defensible middle; reconciliation is what actually protects money.
**How to change.** Each new gateway is one class + credentials + a test against its sandbox. Split payment adds a second leg and a compensating reversal — the ledger already supports it.
**Ruled on.** `AMB-PAY-001`, `AMB-PAY-002`, `AMB-PAY-003`.

### DEC-016 — No tax engine in v1; a configurable tax line defaulting to zero
**Decision.** Invoices carry a tax field that defaults to 0 and is configurable per issuer, but no VAT computation, no tax authority integration, no tax reporting. Four document types are distinct: proforma (پیشفاکتور), purchase invoice, manual invoice, supplementary invoice; numbering is gapless per fiscal year and per type.
**Basis.** `AGENT_DECISION` — the contract mentions «مالیات» only as a deduction in the profit-share formula (page 4 / ماده ۱۶) and never requires tax invoicing (`AMB-INV-001`).
**Rationale.** Inventing a VAT rule would create a compliance claim we cannot support; leaving a zero-default field keeps the schema ready.
**How to change.** A tax strategy per issuer plus a rate table; the document model already has the field.
**Ruled on.** `AMB-INV-001`.

### DEC-010 — Audit everything sensitive; 7-year retention; two-person rule for money overrides
**Decision.** A shared audit trait writes append-only `adm_audit_logs` rows for login, role/level change, price change, settlement, identity-data change and every manual money edit, with actor, before/after, reason and reference. Financial and audit records are retained 7 years (configurable). Manual money operations above a configurable threshold require a second admin approval.
**Basis.** `TECHNICAL_CONSTRAINT` (canonical §5) + `AGENT_DECISION` — page 5 / پیوست ۱ بند ۱۰ requires the log but no retention; page 11 / پیوست ۱۱ بند ۱۶ grants unlimited manual money edits (`AMB-FIN-003`, `AMB-ADM-001`).
**Rationale.** A ledger nobody can audit is worse than no ledger; the two-person rule is the cheapest control that survives an insider.
**How to change.** Retention is a config value; the approver threshold is a setting. Removing the second approver is a policy change with a written owner instruction.
**Ruled on.** `AMB-FIN-003`, `AMB-ADM-001`.

## 4. Identity, merchants, catalog

### DEC-011 — Many-to-many roles, allow-list pairs, policies as the only gate; merchants split; dedupe by triple match
**Decision.** Roles are many-to-many (`iam_user_roles`) with an admin-configurable allowed-pair table (`adm_role_pairs`), default deny; every authorization decision is a policy check on the server (UI hiding is not authorization); marketer scope = own network/leads, store scope = own catalog/orders. Stores and suppliers are separate merchant profiles with a capability matrix and XOR product ownership (a product belongs to exactly one of a store or a supplier). Duplicate-store detection: exact match on national ID + postal code + mobile blocks (with an "existing owner" hint), partial match warns, admin can merge or transfer. Admin 2FA is TOTP with recovery codes; a session registry allows revocation.
**Basis.** `AGENT_DECISION` — page 5–6 / پیوست ۳; page 9 / پیوست ۷ بند ۶ (self-contradictory example); page 11 / پیوست ۱۲ بند ۱۱; page 5 / پیوست ۱ بند ۷ (`AMB-IAM-001`, `AMB-MER-001`, `AMB-MER-002`, `AMB-CAT-001`).
**Rationale.** The contract's own role example is internally inconsistent, and پیوست ۱۲ requires a combination (marketer + consumer) that a single column cannot express. Policies are also the only way to satisfy «دسترسی محدود بازاریاب» without trusting a view.
**How to change.** Allowed pairs are admin data; adding a level is a row plus a policy, not a schema change (page 5 / پیوست ۱ بند ۸ requires exactly this). Splitting merchants back into one entity would be a data migration.
**Ruled on.** `AMB-IAM-001`, `AMB-IAM-002`, `AMB-IAM-003`, `AMB-MER-001`, `AMB-MER-002`, `AMB-CAT-001`.

### DEC-008 — Data minimisation with an explicit field inventory
**Decision.** National ID and postal code are stored encrypted with a hashed blind index for dedupe (searchable without exposing plaintext); documents and photos live on a private disk behind short-lived signed URLs; PII is masked in lists and logs and unmasked only for the owner/authorised admin with an audit row; no PII in application logs, error reports or queue payloads; retention per entity documented (financial 7 years, chat 24 months, location 12 months, marketing consents until withdrawn).
**Basis.** `AGENT_DECISION` — page 3 / ماده ۴ بند ۱۰, ماده ۷; page 8 / پیوست ۵ بند ۳۰ (`AMB-CTR-003`).
**Rationale.** The contract obliges us to protect user data and simultaneously requires collecting identity documents and photos in the field; minimisation plus masking satisfies both without a second system.
**How to change.** The inventory table is the single place to change a field's classification; retention jobs read from it.
**Ruled on.** `AMB-CTR-003`.

### DEC-009 — Store UTC, display Tehran, format Jalali, currency Toman, UI Persian
**Decision.** Timestamps stored in UTC; all display in `Asia/Tehran`; Jalali formatting only at the presentation layer via shared helpers; money integer Toman; UI copy Persian and RTL-first; no i18n framework in v1, but no user-facing string is concatenated from raw values outside the Money/date helpers.
**Basis.** `TECHNICAL_CONSTRAINT` (canonical §5) + `AGENT_DECISION` — the contract states none of this (`AMB-CTR-002`).
**Rationale.** Contract windows are expressed in days from events («یک هفته پس از ارسال»), so a timezone slip changes a legal window; Jalali months also drive the quarterly profit-share settlement.
**How to change.** Adding a locale is a config plus translation files; the storage layer never changes.
**Ruled on.** `AMB-CTR-002`.

## 5. Orders, stock, fulfilment

### DEC-012 — Complaints and freezes are first-class objects, not flags
**Decision.** A complaint creates a hold record with a reason, the transitions it blocks (preparation, payout, shipment), an expiry and a releaser. The complaint window is measured in days from the **shipment event** (configurable, default 7), and the button disappears after it; a ticket to admin remains available as a goodwill path. Releasing a hold is an audited action.
**Basis.** `AGENT_DECISION` — page 7 / پیوست ۴ بند ۱۵–۱۶; page 8 / پیوست ۵ بند ۳۲; page 11 / پیوست ۱۲ بند ۹ (`AMB-ORD-001`, `AMB-ORD-002`).
**Rationale.** The contract says "stop preparation and stop payment until settled" without saying what "stop" blocks; a boolean cannot express a partial freeze and cannot expire. Anchoring to shipment is the only event the platform reliably knows.
**How to change.** Window length and blocked transitions are configuration; changing the anchor to delivery confirmation is a policy change plus a backfill for in-flight orders.
**Ruled on.** `AMB-ORD-001`, `AMB-ORD-002`.

### DEC-013 — The incomplete-order button is allowed but scored; violation reports need a decision
**Decision.** A supplier may reject/declare an order incomplete with a mandatory reason; the order returns to the buyer for correction or cancellation with refund (wallet by default, شبا where required). Every use is audited; repeated use without a substantiated reason deducts points automatically, and the buyer's cancellation path is always available. Violation/warn reports enter an admin queue with optional evidence, publish a negative «امتیاز تخلف» only after admin approval, and carry an appeal path.
**Basis.** `AGENT_DECISION` — page 10 / پیوست ۱۱ بند ۱۲–۱۳; page 7 / پیوست ۴ بند ۱۸–۱۹; page 8 / پیوست ۵ بند ۱۴, بند ۳۵ (`AMB-ORD-003`, `AMB-ORD-006`).
**Rationale.** The contract grants a supplier veto *and* penalises improper use of it, which only makes sense as a scored, auditable action. Publishing accusations without review invites competitor abuse.
**How to change.** The scoring threshold and the required evidence level are settings; the workflow itself is stable.
**Ruled on.** `AMB-ORD-003`, `AMB-ORD-006`.

### DEC-017 — Soft stock hold at checkout, hard allocation at payment; rate-table shipping
**Decision.** Adding to cart reserves nothing; entering checkout takes a soft hold with a TTL (default 20 minutes) that is released by a scheduled job; stock is allocated at successful payment, and overselling is blocked at allocation. Shipping cost comes from an admin-maintained zone/weight rate table per supplier, with free-shipping rules expressed as promotions rather than hard-coded conditions; carriers are **recorded** (waybill number, method, dispatch time) with a pluggable tracking field, not integrated.
**Basis.** `AGENT_DECISION` — page 10 / پیوست ۱۱ بند ۱; page 5 / پیوست ۲ بند ۶; page 8 / پیوست ۵ بند ۴۰; page 10 / پیوست ۱۱ بند ۱۵ (`AMB-STK-001`, `AMB-ORD-009`, `AMB-SHP-001`).
**Rationale.** Hard-allocating at cart level blocks stock for abandoned carts in a B2B setting where carts are large and slow; allocating at payment is the latest safe moment. Carrier integrations are unpaid scope the contract never names.
**How to change.** TTL and allocation moment are configuration; a rate-provider interface allows live quotes later.
**Ruled on.** `AMB-STK-001`, `AMB-ORD-009`, `AMB-SHP-001`.

### DEC-029 — One documented order state machine
**Decision.** A single state vocabulary and transition table, enforced in one place: `draft → awaiting_payment → paid → preparing → ready_to_ship → shipped → delivered → completed`, with side branches `on_hold`, `incomplete`, `cancelled`, `returned`. Each transition records actor, timestamp and reason; illegal transitions raise rather than silently succeed.
**Basis.** `AGENT_DECISION` — no clause defines states, though the appendices name at least a dozen conditions (`AMB-ORD-008`).
**Rationale.** Every report, freeze, compensation and settlement rule is defined in terms of these states; letting each module invent its own vocabulary guarantees contradictory reporting.
**How to change.** The machine is a config file plus a test; adding a state is additive, renaming one is a migration.
**Ruled on.** `AMB-ORD-008`.

### DEC-030 — Delay compensation: configurable percentage, auto-posted with admin override
**Decision.** Compensation = configurable percentage of the sub-order total per day late, with a cap, posted automatically to the buyer's wallet when a delay is recorded against the schedule, minus any waiver an admin grants with a reason. The base is the sub-order total excluding shipping.
**Basis.** `AGENT_DECISION` — page 7 / پیوست ۴ بند ۱۴; page 10 / پیوست ۱۱ بند ۱۵; page 11 / پیوست ۱۲ بند ۸ (`AMB-ORD-004`).
**Rationale.** «کسر خودکار» (automatic deduction) and «تأیید ادمین» (admin approval) appear together; the only coherent reading is auto-apply with an override. A legally meaningful base must exclude shipping, which the supplier does not control.
**How to change.** Percentage, cap and base are settings; switching to manual approval is a policy flag.
**Ruled on.** `AMB-ORD-004`.

## 6. Communication, field, community

### DEC-020 — Order- or store-anchored one-to-one chat, 24-month retention
**Decision.** Chat threads are one-to-one and anchored to an order or a store relationship; attachments (voice, image, file) allowed; no group chat; history retained 24 months; admin can read a thread only through a report/complaint, and that read is audited; buyers and suppliers cannot initiate unsolicited contact outside an order or store relationship.
**Basis.** `AGENT_DECISION` — page 8 / پیوست ۵ بند ۲۸ requires persistent chat «بین خریداران با هر کاربر» with no moderation or retention limit (`AMB-MSG-001`).
**Rationale.** "Chat with anyone, forever" is a spam channel and an unbounded PII store. Anchoring to a commercial relationship keeps the contract's purpose (dispute reduction, order trace) and drops the abuse surface.
**How to change.** Anchors and retention are settings; opening user-to-user chat is a moderation-policy decision, not a schema change.
**Ruled on.** `AMB-MSG-001`.

### DEC-021 — Location capture at check-in, no continuous tracking in v1
**Decision.** The marketer app/web captures coordinates at visit check-in and check-out only; no background/continuous tracking; retention 12 months; the marketer sees their own trail, their manager sees visits and aggregates; route analysis is computed from check-ins. Consent is captured at onboarding and shown in the profile.
**Basis.** `AGENT_DECISION` — page 11 / پیوست ۱۴ بند ۱–۲ («رهگیری … بهصورت زنده») is two sentences for a live-tracking system of employees (`AMB-GEO-001`).
**Rationale.** Continuous tracking of staff devices carries labour-law and privacy exposure that the contract does not require in so many words; check-in data delivers the stated business purpose (coverage and route analysis).
**How to change.** If the owner insists on live dots, enable periodic pings behind a flag, add a consent flow and a retention cut — the event table already stores what is needed.
**Ruled on.** `AMB-GEO-001`, `AMB-CTR-003`.

### DEC-022 — Channel drivers, client-owned accounts, per-supplier credit ledger
**Decision.** One `SmsDriver` and one `WhatsAppDriver` interface, provider chosen by configuration; credentials belong to the client, never committed; suppliers get a prepaid credit ledger debited per send, with free quota set by admin; push and email are separate drivers. Consent flags per channel, quiet hours and a per-user daily cap are enforced centrally.
**Basis.** `AGENT_DECISION` — page 9 / پیوست ۷ بند ۳; page 10 / پیوست ۱۱ بند ۶–۷; page 8 / پیوست ۵ بند ۱۲, بند ۲۷ (`AMB-NTF-001`, `AMB-NTF-002`).
**Rationale.** The contract requires five channels and paid supplier messaging but names no provider and no price; a ledger is the only way «پرداختی» becomes auditable, and an interface keeps a provider switch to one class.
**How to change.** Provider swap is config + one adapter; free/paid split is a setting.
**Ruled on.** `AMB-NTF-001`, `AMB-NTF-002`.

### DEC-019 — Classifieds, Patogh and CMS as separate homes with shared primitives
**Decision.** دیوار = classifieds module (category-gated posting, admin pre-moderation, 30-day expiry). پتوق = services directory module (provider profile, skills, quote request via ticket), no calendar, no booking. CMS owns static pages, banners, menus, surveys and notices; anything that changes a price (festival, special discount) is owned by Promotions and merely *placed* by CMS.
**Basis.** `AGENT_DECISION` — page 7 / پیوست ۵ بند ۴, بند ۵, بند ۱۳; page 9 / پیوست ۷ بند ۹; page 10 / پیوست ۱۱ بند ۴ (`AMB-CLS-001`, `AMB-CLS-002`, `AMB-PTG-001`, `AMB-CMS-001`).
**Rationale.** The two community sections have near-identical mechanics but different moderation and different data (goods vs skills); sharing primitives avoids duplicate code without merging domains. Keeping discount-bearing objects out of CMS prevents a content editor from changing money.
**How to change.** A section can be merged or split without schema change if the shared posting primitive is used consistently.
**Ruled on.** `AMB-CLS-001`, `AMB-CLS-002`, `AMB-PTG-001`, `AMB-CMS-001`.

### DEC-018 — Tenders are one-round sealed forms; auctions are simple ascending
**Decision.** Tender: a form (spec, quantity, deadline) broadcast to suppliers/stores, bids submitted once, sealed until the deadline, awarded by the owner/admin with a recorded reason. Auction: ascending, admin-set window, minimum increment, optional reserve, winner pays on award, no auto-bidding or sniping extensions in v1.
**Basis.** `AGENT_DECISION` — page 6 / پیوست ۳-۳ بند ۵; page 7 / پیوست ۵ بند ۶; page 8 / پیوست ۵ بند ۳۶–۳۷; page 10 / پیوست ۱۱ بند ۱۰ (`AMB-TND-001`, `AMB-TND-002`).
**Rationale.** Both are one-sentence requirements; a sealed tender protects bidders' pricing, and a simple ascending auction avoids the dispute mechanics (sniping, shill bids) that the contract neither funds nor defines.
**How to change.** Add bid visibility, increments or extensions per auction type; the bid table already stores the audit trail.
**Ruled on.** `AMB-TND-001`, `AMB-TND-002`.

### DEC-024 — One report definition dictionary; server-generated protected exports
**Decision.** Every metric (gross sales, net sales, tax, returns, cancellations, active/inactive store, marketer activity, settlement status) is defined once in a dictionary; all screens, Excel exports and widgets read from it. Exports are generated server-side onto the private disk, delivered by short-lived signed links, keep the report's column contract, mask PII unless the exporter is authorised, and are logged.
**Basis.** `AGENT_DECISION` — page 9 / پیوست ۷ بند ۱۰; page 11 / پیوست ۱۳ بند ۱–۳; page 8 / پیوست ۵ بند ۳۰–۳۱ (`AMB-RPT-001`, `AMB-RPT-002`).
**Rationale.** Two reports that disagree by a few percent make the whole finance module untrustworthy, and a public export URL is the most likely leak path in a product that must export national IDs and settlements.
**How to change.** Definitions live in one document plus one query layer; adding a report is additive.
**Ruled on.** `AMB-RPT-001`, `AMB-RPT-002`.

## 7. Delivery, contract administration and naming

### DEC-023 — PWA is the mobile deliverable; native is deferred
**Decision.** The mobile deliverable in this mission is a PWA built from the same codebase: manifest, icons, splash, service worker with a caching strategy that never serves stale prices or balances, installable on Android and iOS, push-capable. Native Android (APK) and native iOS builds, plus store submission packages, are deferred under `RB-MOB-01`. Customer-facing capabilities stay reachable through the JSON API so a native shell remains additive.
**Basis.** `OWNER_DECISION` — canonical §0 non-negotiables 2–3; contract page 1 / ماده ۱, page 8 / پیوست ۵ بند ۲۵–۲۷, page 9–10 / پیوست ۸ (`AMB-CTR-001`).
**Rationale.** One codebase, one release pipeline, no store gatekeeping while the product is stabilising; the PWA satisfies «قابل نصب روی اندروید و iOS از مرورگر» literally (پیوست ۱ بند ۴), and «روی ویندوز» is a browser, not a binary.
**How to change.** A native client wraps the existing API; the only rework is any logic that leaked into Blade views. Store accounts and release approvals are the client's cost (پیوست ۸ بند ۷).
**Ruled on.** `AMB-CTR-001`.

### DEC-014 — Payment plan working assumption: 10 M on signature + 80 M one month after written approval
**Decision.** Invoices and acceptance milestones follow the two-instalment reading (10,000,000 at signature, 80,000,000 one month after final delivery and written approval). The split is data, not code; if the owner confirms the single-instalment reading, only the milestone rows change.
**Basis.** `AGENT_DECISION` — the rendered payment table shows one staged row; the text layer carries the two-instalment plan (`AMB-CTR-005`, High relationship impact).
**Rationale.** The two-instalment version is the only one that says *when* money moves, both parts sum exactly to 90,000,000, and «گشایش کار» appears in the phase table as a distinct stage. Choosing it does not create a claim until the owner confirms.
**How to change.** Replace milestone rows; nothing in the product depends on the split except the "final payment due" reminder.
**Ruled on.** `AMB-CTR-005`.

### DEC-015 — Support: plan on a 5-year horizon, model the SLA regardless
**Decision.** Free support is planned as 5 years from final delivery for costing and roadmap purposes; the SLA (24 working hours first response, 3 working days medium, 24 hours critical) is implemented as configuration and does not depend on the duration reading.
**Basis.** `AGENT_DECISION` — page 4 / ماده ۱۲ بند ۱ renders «تا پایان شرکت طرفین» while the text layer says «به مدت ۵ سال» (`AMB-CTR-006`).
**Rationale.** The duration changes a cost line; the SLA changes a product feature. Building the SLA is unavoidable under every reading, so it must not wait for the answer.
**How to change.** A single config value plus the annual-support contract trigger.
**Ruled on.** `AMB-CTR-006`.

### DEC-026 — Track two dates: the contract date and the plan date
**Decision.** The plan tracks the contractual 90-day clock (from signing + complete information) separately from the realistic phased plan; the delta is reported monthly to the owner with the driving causes (client content, gateway accounts, scope decisions). No deliverable is silently re-dated.
**Basis.** `AGENT_DECISION` + `OWNER_DECISION` — page 2 / ماده ۳ fixes 90 days; canonical §0 phases the work (`AMB-CTR-004`).
**Rationale.** Once the two dates blur, the first written notice arrives after 0.5 %/day (450,000 T/day, capped at 18,000,000) has accrued. Keeping them separate converts a legal risk into a management number.
**How to change.** If the owner signs an addendum for a new date, the contract date is replaced and the delta line disappears.
**Ruled on.** `AMB-CTR-004`.

### DEC-035 — Penalty exposure modelled at heading level, with a per-heading completion ledger
**Decision.** For risk management we count 14 appendix headings (not 203 clauses), and maintain a per-heading completion record — executed / partially / deferred-with-ID — so that exposure is visible before a client counts. Any heading that ends up partially executed is escalated to the owner with its 4,500,000 T price.
**Basis.** `AGENT_DECISION` — page 3 / ماده ۴ بند ۱۱ defines «بند» as a numbered heading but the appendices have headings, sub-sections and clauses (`AMB-CTR-008`).
**Rationale.** Counting clauses makes the contract impossible to satisfy and every gap fatal; counting headings matches the clause's own words and turns penalty into a managed number.
**How to change.** If the client argues sub-sections, the count rises to ~20 and the cap (9,000,000) is reached sooner — the ledger already holds the granularity.
**Ruled on.** `AMB-CTR-008`.

### DEC-031 — The reservation module is excluded, not deferred
**Decision.** No calendar, slot, availability or practitioner-schedule primitives are built. پتوق stays a directory with a quote request; installation/measurement referrals are recorded as leads. Any shared-login request is an addendum under ماده ۱۵.
**Basis.** `AGENT_DECISION` + `OWNER_DECISION` — page 9 / پیوست ۵ note excludes «ماژول نوبتدهی/رزرواسیون» under a separate 35,000,000 T / 1 month contract (canonical §0.4).
**Rationale.** It is another vendor's deliverable; building near-booking primitives invites a scope dispute and duplicates data ownership.
**How to change.** Only via a signed addendum; the identity module is kept capable of SSO so integration stays cheap.
**Ruled on.** `AMB-CTR-010`.

### DEC-032 — Samples: request workflow, not inventory
**Decision.** A sample/catalogue request is a lead with description, dimensions and attachments, routed to the supplier's panel with an accept/decline and a response note; no sample stock, deposit or return shipping is tracked.
**Basis.** `AGENT_DECISION` — page 6 / پیوست ۳-۱ بند ۹; page 8 / پیوست ۵ بند ۳۸; page 10 / پیوست ۱۱ بند ۹ (`AMB-CTR-009`).
**Rationale.** Nothing in the contract mentions sample inventory; adding it would create a physical-logistics subsystem with no requirement behind it.
**How to change.** If samples must ship with deposits, add a sample order type on top of the existing order model.
**Ruled on.** `AMB-CTR-009`.

### DEC-033 — Proceed on the draft text; never present it as executed
**Decision.** Work proceeds on the text as instructed by the owner, but no artefact — README, UI, proposal, invoice — describes the contract as signed or executed; commercial figures that exist in two versions are marked provisional. The document status line («پیشنویس نسخه ۱ — در انتظار توافق و امضای طرفین») is quoted when the question arises.
**Basis.** `AGENT_DECISION` — page 1 status field; page 11 signatures undated (`AMB-CTR-007`).
**Rationale.** Presenting an unsigned draft as binding is a credibility risk in the client relationship and a factual error in our own documentation; stopping all work is not a decision an agent may take.
**How to change.** When a signed copy exists, update the status line and drop the "provisional" markers (a documentation-only change).
**Ruled on.** `AMB-CTR-007`.

### DEC-034 — Standardise the product name as «دکولایف»
**Decision.** Use «دکولایف» (the contract's first spelling, page 1) in code, docs, UI copy and metadata; note the variants «دکولایف» and «دکالیف» as known spellings from the source. The English label is "Dekolife".
**Basis.** `AGENT_DECISION` — three spellings inside one document (`AMB-CTR-011`).
**Rationale.** A brand name that appears three ways in the same contract will appear three ways in a store listing, an invoice footer and a legal notice; picking the first use is defensible and cheap to change.
**How to change.** One config value plus a find/replace across locale files; owner confirmation closes it.
**Ruled on.** `AMB-CTR-011`.

## 8. Decision index

| DEC | Subject | Label | Ambiguities closed | Reversal cost |
|---|---|---|---|---|
| 001 | Modular monolith | TECHNICAL_CONSTRAINT | — | High |
| 002 | Integer Toman, `Money` | TECHNICAL_CONSTRAINT | `AMB-CTR-002` | High |
| 003 | Funds custody + ledger + settlement | AGENT_DECISION | `AMB-FIN-001`, `AMB-RTN-001` | High |
| 004 | Parent/sub orders, proxy buyer | AGENT_DECISION | `AMB-ORD-005`, `AMB-ORD-007` | High |
| 005 | Price pipeline + snapshots + approval | AGENT_DECISION | `AMB-PRI-001..003`, `AMB-LOY-001`, `AMB-PRM-001` | Medium |
| 006 | Versioned points rules + boundaries | AGENT_DECISION | `AMB-LOY-002..006` | Low |
| 007 | Gateway driver + reconciliation | AGENT_DECISION | `AMB-PAY-001..003` | Medium |
| 008 | PII minimisation + masking | AGENT_DECISION | `AMB-CTR-003` | Medium |
| 009 | UTC/Tehran/Jalali/Toman/Persian | TECHNICAL_CONSTRAINT | `AMB-CTR-002` | Low |
| 010 | Audit + 7-year retention + second approver | TECHNICAL_CONSTRAINT | `AMB-FIN-003`, `AMB-ADM-001` | Low |
| 011 | Roles, pairs, policies, merchants, dedupe | AGENT_DECISION | `AMB-IAM-001..003`, `AMB-MER-001..002`, `AMB-CAT-001` | Medium |
| 012 | Complaint holds + 7-day window | AGENT_DECISION | `AMB-ORD-001..002` | Medium |
| 013 | Incomplete order + violation reports | AGENT_DECISION | `AMB-ORD-003`, `AMB-ORD-006` | Low |
| 014 | Payment plan assumption | AGENT_DECISION | `AMB-CTR-005` | Low |
| 015 | 5-year support horizon | AGENT_DECISION | `AMB-CTR-006` | Low |
| 016 | No tax engine | AGENT_DECISION | `AMB-INV-001` | Medium |
| 017 | Stock hold, shipping rates, carrier data | AGENT_DECISION | `AMB-STK-001`, `AMB-ORD-009`, `AMB-SHP-001` | Medium |
| 018 | Tenders sealed, auctions ascending | AGENT_DECISION | `AMB-TND-001..002` | Medium |
| 019 | Classifieds/Patogh/CMS homes | AGENT_DECISION | `AMB-CLS-001..002`, `AMB-PTG-001`, `AMB-CMS-001` | Low |
| 020 | Anchored 1:1 chat, 24-month retention | AGENT_DECISION | `AMB-MSG-001` | Low |
| 021 | Check-in location, no continuous tracking | AGENT_DECISION | `AMB-GEO-001` | Medium |
| 022 | Channel drivers + supplier credits | AGENT_DECISION | `AMB-NTF-001..002` | Low |
| 023 | PWA now, native deferred | OWNER_DECISION | `AMB-CTR-001` | Medium |
| 024 | One metric dictionary, protected exports | AGENT_DECISION | `AMB-RPT-001..002` | Low |
| 025 | Comp plans as data, vest on collection | AGENT_DECISION | `AMB-CMP-001..004`, `AMB-FIN-002`, `AMB-FIN-004`, `AMB-MER-003` | Medium |
| 026 | Two dates tracked | OWNER_DECISION | `AMB-CTR-004` | Low |
| 027 | Single-level referral | AGENT_DECISION | `AMB-MKT-001` | Low |
| 028 | Consumer uplift configurable | AGENT_DECISION | `AMB-PRI-004` | Low |
| 029 | Order state machine | AGENT_DECISION | `AMB-ORD-008` | High |
| 030 | Delay compensation | AGENT_DECISION | `AMB-ORD-004` | Low |
| 031 | Reservation module excluded | OWNER_DECISION | `AMB-CTR-010` | Medium |
| 032 | Samples as request, not inventory | AGENT_DECISION | `AMB-CTR-009` | Low |
| 033 | Proceed on draft text | AGENT_DECISION | `AMB-CTR-007` | Low |
| 034 | Name standardisation | AGENT_DECISION | `AMB-CTR-011` | Low |
| 035 | Heading-level penalty model | AGENT_DECISION | `AMB-CTR-008` | Low |

## 9. How to change a decision

1. **Find the entry**, read `How to change`, and check the reversal cost. A `High` decision needs the Orchestrator and a written owner answer before any code moves.
2. **Write the replacement as a new entry** (`DEC-0NN`), never by editing the old text: append `**Superseded by DEC-0NN` to the original so the history stays readable.
3. **Update the ambiguity row's `Decision` and `Status`** in [`ambiguity-register.md`](ambiguity-register.md) (`Decided` → `Resolved` when the owner answers).
4. **Update the tests that encode the rule**, in the same change. A financial or points rule without a test is not decided, only hoped.
5. **Tell the Orchestrator** so parallel agents stop building against the old reading.
