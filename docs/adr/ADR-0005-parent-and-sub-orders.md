# ADR-0005: Parent order with per-seller sub-orders

- **Status:** Accepted (agent decision, pending owner review)
- **Date:** 2026-11-25
- **Deciders:** Architecture owner; product reviewer
- **Related:** REQ-ORD-*, REQ-INV-*, REQ-SHP-*, REQ-RTN-*; ADR-0004, ADR-0008; `../architecture/data-flow.md` §2

## Context

A consumer's cart can contain goods from several sellers: multiple stores and suppliers. Each seller independently accepts, prepares, ships and is paid. Each has its own shipping cost, delay penalty, cancellation decision, invoice and settlement. The contract also gives each seller the right to reject an order, to cancel with a refund, to flag an incomplete order, and to be penalised for delay — all of which are **per seller**, not per basket (پیوست ۱۱ بند ۱۲–۱۵).

There is no explicit clause describing the order data model. The requirement shape, however, is unambiguous: a single checkout produces several independently-fulfilled pieces of work. The question is whether that is one order with per-seller status columns, one order per seller, or a parent with children.

## Decision

Two levels:

1. **`ord_orders`** — the buyer's commercial act. One per checkout. Holds buyer id, address snapshot, totals, currency, payment status, the derived parent status, the public order number and the tracking code.
2. **`ord_sub_orders`** — one per seller per order. Holds seller id, its own status, its own totals (lines + shipping − seller-funded discounts), its own promised ship date, its own cancellation reason, its own settlement reference. `ord_order_items` belongs to a sub-order, not to the parent directly.

Rules:

- **Independent lifecycles.** Each sub-order runs the full state machine (`awaiting_payment → paid → preparing → shipped → delivered → completed`, with `cancelled`, `expired`, `returned`). Actions on one never change another's status.
- **Derived parent status.** `ord_orders.status` is a cached projection: all children cancelled → parent `cancelled`; all completed → `completed`; any shipped/delivered → `partially_shipped`/`partially_delivered`; otherwise the least-advanced child's status. Recomputed in the same transaction that changed a child.
- **One payment per parent order in v1.** The buyer pays once for the basket; the money is held per parent and allocated to sub-orders in the ledger (`platform.sales_holding:{order}` → `seller.payable:{merchant}` at completion). This avoids N payment redirects for one checkout — a UX requirement in practice, even though the contract does not spell it out.
- **Invoices are per sub-order** (one seller, one tax document), numbered from the same gapless sequence. The parent has a summary document ("pre-invoice") derived from children, not a separate legal invoice.
- **Shipping is per sub-order**: each seller ships its own goods, with its own waybill and its own delay calculation (which is what makes the penalty computable).
- **Returns, complaints, freezes and settlements are keyed on `sub_order_id`**, never on the parent. A dispute about one seller's goods must not freeze another seller's money.
- **Cancellation cascade:** cancelling a single sub-order refunds only that sub-order's share; the parent becomes `partially_cancelled` and the other children proceed.

## Consequences

**Positive**

- Seller-facing screens, permissions and notifications map one-to-one onto a sub-order, which is how sellers actually work.
- Money allocation is exact: each sub-order has a known share of the payment, so partial refunds, delay penalties and settlements do not need proportional arithmetic at settlement time.
- Disputes and freezes are naturally scoped; one seller cannot hold another's money hostage.
- Adding split payment later (`RB-PAY-01`) means multiple payment legs per parent, with allocation to sub-orders — the model already supports it.

**Negative**

- Two status fields to keep consistent (child truth + parent projection). A bug in the projection shows the buyer a stale parent status; mitigated by recomputing inside the child's transition transaction and a nightly consistency job that reports drift.
- More joins for reporting and for the buyer's order list; a parent summary needs aggregation over children.
- Every feature that touches orders must decide its level explicitly. "Cancel the order" is ambiguous in conversation and must be specified as parent-cancel or sub-order-cancel in every ticket.
- One payment across many sellers means the platform is holding other people's money, which is precisely the ambiguity in `AMB-FIN-001` — the model makes that exposure explicit rather than hiding it.

**Neutral**

- The parent is not an aggregate root that owns children's state transitions; it is a coordination record. Domain code lives on `SubOrder` for fulfilment and on `Order` for payment and totals.
- The contract's "order tracking by code" (پیوست ۲ بند ۱۱) tracks the **parent**, because that is what the buyer knows.

## Alternatives considered

| Alternative | Why it lost |
|---|---|
| One order per seller (no parent) | Checkout would create N unrelated orders and N payment redirects; the buyer loses a single total and a single tracking code; partial-payment failure leaves orphan orders |
| One order with `seller_id` and no children (one order per seller line) | Same as above, plus duplicated buyer/address/payment data per seller |
| One flat order with per-seller status columns (`seller_1_status`, …) | Does not survive more than two sellers; unqueryable; every new seller is a migration |
| Parent + children but one invoice for the whole basket | Violates the per-seller tax document reality; a seller cannot be shown an invoice containing another seller's goods |
| Parent + children with escrow released per line | Over-engineered for a manual settlement process (پیوست ۱۳) |

## References

- Contract page 5–6 / پیوست ۳ (store capabilities: orders, invoices, settlement), page 10–11 / پیوست ۱۱ clauses 12–15 (incomplete order, cancellation, delay penalty), page 9 / پیوست ۷ بند ۲ (admin manual invoice).
- `docs/canonical-context.md` §5 (parent orders + sub-orders; order state machine in `docs/data/order-state-machine.md`).
- `../architecture/data-flow.md` §2; `../architecture/domain-boundaries.md` §3.8–§3.9.
