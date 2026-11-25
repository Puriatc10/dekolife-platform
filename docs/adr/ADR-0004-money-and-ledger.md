# ADR-0004: Money as integer Toman with an append-only ledger

- **Status:** Accepted (agent decision, pending owner review)
- **Date:** 2026-11-25
- **Deciders:** Architecture owner; finance reviewer
- **Related:** REQ-FIN-*, REQ-LOY-*, REQ-PAY-*; AMB-FIN-001, AMB-FIN-002, AMB-FIN-003; ADR-0005, ADR-0008; `../architecture/data-flow.md` §1–§6

## Context

The platform moves money in five directions: buyers pay, sellers earn, marketers earn commission, buyers earn loyalty cash and delay compensation, and the platform takes commission and shipping margin. The contract also requires filtered sales reports for manual settlement, daily payment reports, a bank export for group payment, wallet/credit-debit tracking, and failed/returned transaction reconciliation (پیوست ۱۳ بند ۱–۷).

The contract states all prices in **Toman** (`۹۰٬۰۰۰٬۰۰۰ تومان`, product prices, commission thresholds) and never mentions sub-Toman amounts. Two decisions follow from that, and both are hard to reverse once data exists.

Ambiguity that shapes the model: the contract never says who holds buyer money, when a seller becomes entitled to it, or how the 15 % profit share in ماده ۱۶ is computed. The conservative reading — platform collects, holds per order, settles per period — is recorded in `AMB-FIN-001`; the profit share is explicitly unmodelled (`AMB-FIN-002`).

## Decision

### 1. Representation

- Money is an **integer number of Toman** in `BIGINT` columns named `*_amount` (and `*_amount` in DTOs). No `DECIMAL`, no `FLOAT`, no `DOUBLE`, ever.
- `App\Shared\Money\Money` is the only way to do arithmetic: `plus`, `minus`, `multiplyByBasisPoints(int $basisPoints)`, `allocate(array $weights)`, `isZero`, `isNegative`. It throws on overflow and on mixing currencies.
- Percentages are stored as **basis points** (`int`, 1 % = 100 bp) so a 0.75 % loyalty coefficient is `75`, not `0.0075`.
- Rounding: half-up at the Toman, applied **per line**; the residual from rounding a total from its lines is allocated to the largest line (deterministic, testable). Loyalty **points** are integers and round **down** (a customer never gets a point they did not earn), with the fractional remainder discarded.
- API and UI format for display only (`۱۲٬۵۰۰ تومان`, `Money::format()`); no formatted string ever enters the domain.

### 2. Ledger

- The ledger is **two append-only tables**: `fin_postings` (the business event: `id, reference (unique), occurred_at, description, actor_id, created_at`) and `fin_ledger_entries` (the lines: `id, posting_id, account_id, amount (signed BIGINT), currency, created_at`).
- Every posting is **double-entry**: the signed amounts of its lines sum to zero, and a posting whose lines do not balance is rejected before insert (validated in `Ledger::post()` and re-checked by a database trigger).
- **Balances are derived**, never stored: `SUM(amount)` over an account's lines (optionally within a date range). No `balance` column on wallets.
- **Corrections are reversal postings**, never updates or deletes. `UPDATE`/`DELETE` on `fin_postings` and `fin_ledger_entries` is blocked by a database trigger, and the application has no code path that attempts it.
- Every posting carries a **deterministic unique `reference`** (`pay:{payment_id}:capture`, `sub:{sub_order_id}:earn`, `comm:{commission_id}`, `delay:{shipment_id}`, `batch:{batch_id}`). A repeated posting with the same reference is a no-op returning the original posting id, which makes every financial command idempotent under retry, queue redelivery and double-clicked admin buttons — even when the posting has several lines (a payment that holds money for three sub-orders is one posting with four lines).
- Account types: asset, liability, income, expense. Accounts are per-entity where needed (`seller.payable:{merchant_id}`, `customer.wallet:{user_id}`) and are created lazily inside the posting transaction.
- Settlement availability = `SUM(payable) − SUM(active freezes) − SUM(already batched)`. A freeze withholds availability; it never mutates the ledger.

### 3. Wallets

`fin_wallets` stores **metadata only** (owner, currency, status, overdraft flag). The balance is a query over `fin_ledger_entries` filtered by the wallet's account. Spendable credit is a liability: the platform owes the customer.

### 4. What is explicitly out of scope

- The 15 % profit share (ماده ۱۶) is not modelled. It needs `AMB-FIN-002` resolved (revenue definition, cost allocation, quarterly reporting) before any table exists.
- Tax calculation is line-level metadata on invoices, not a ledger account, until the owner confirms VAT treatment (`AMB-FIN-005`).
- Multi-currency is not supported; the `currency` column exists for future-proofing and is fixed to `IRT`.

## Consequences

**Positive**

- No floating-point drift. `0.1 + 0.2` never produces a settlement discrepancy.
- Any historical balance can be recomputed and audited; a discrepancy is a query, not an investigation.
- Idempotency is structural: a retried job, a duplicated gateway callback or a double-clicked approve button cannot double-post money.
- Reversals preserve the audit trail, which is exactly what a financial dispute needs.
- Settlement, reporting and reconciliation all read the same source of truth.

**Negative**

- Every read of a balance is an aggregate query. At high volume this needs an index on `(account_id, occurred_at)` and, later, a snapshot table (`RB-FIN-01`); v1 accepts the cost.
- A ledger entry can be posted in the wrong account and only corrected by another entry — there is no "fix the row" escape hatch. This is intentional but unforgiving, so posting code needs review and tests.
- Integer division and rounding rules must be applied consistently; a developer who reaches for `round()` on a float reintroduces the bug. Enforced by a PHPStan rule banning float arithmetic on `*_amount` fields and by code review.
- No sub-Toman amounts means a 0.5 % fee on a 1 Toman item is 0 or 1, never 0.5. Acceptable given the contract's pricing, but a future micro-transaction feature would need a currency change.

**Neutral**

- Double-entry bookkeeping is more ceremony than a `balance` column, and it is the only model that survives five years of bug fixes and manual corrections.
- The ledger is not an event store: it records money, not business state. Orders keep their own history.

## Alternatives considered

| Alternative | Why it lost |
|---|---|
| `DECIMAL(18,2)` columns | Correct for currencies with subunits, but the contract's unit is whole Toman; decimals invite rounding disagreements between PHP, MySQL and Excel |
| `FLOAT`/`DOUBLE` | Unacceptable for money; silent precision loss at large values |
| Integer **Rial** (minor unit) | Would require dividing by 10 at every display and every contract comparison; the contract's numbers are Toman, so the mapping cost is constant and the confusion risk is real |
| Stored `balance` column updated in place | Fast reads, but every concurrent update is a lost-update risk and every correction loses history |
| Event-sourced wallet aggregate | Elegant, but a second event store alongside the domain events doubles the infrastructure for no contractual gain |
| Per-order escrow accounts with automatic release | Closer to a payment facilitator model, but the contract's settlement is manual/periodic (پیوست ۱۳ بند ۱–۳), so holding + period settlement matches reality |

## References

- Contract page 6–7 / پیوست ۴ (points, rewards, discounts), page 11 / پیوست ۱۳ clauses 1–7 (reports, bank export, wallet, reconciliation).
- `docs/canonical-context.md` §5 (money, ledger, idempotency, `AMB-FIN-001`).
- `../architecture/data-flow.md` §1, §5, §6; `../architecture/domain-boundaries.md` §3.11.
