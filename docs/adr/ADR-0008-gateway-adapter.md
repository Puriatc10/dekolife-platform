# ADR-0008: Gateway adapter, one active gateway per payment, split payment deferred

- **Status:** Accepted (agent decision, pending owner review)
- **Date:** 2026-11-25
- **Deciders:** Architecture owner; delivery lead; client owner (required for the gap in §Contract position)
- **Related:** REQ-PAY-*, REQ-FIN-006; RB-PAY-01, AMB-PAY-002; ADR-0004; `../architecture/integrations.md` §2

## Context

Two contract clauses pull in different directions:

> پیوست ۱ بند ۵: «پشتیبانی هم‌زمان از ۱۲ درگاه پرداخت بانکی مجزا و امکان استفاده هم‌زمان از دو درگاه در یک تراکنش با انتخاب کاربر»

> پیوست ۱۳ بند ۷: «اتصال ۱۲ درگاه بانکی با امکان انتخاب دو درگاه هم‌زمان و ثبت لاگ کامل تراکنش‌ها»

So the contract requires (a) twelve separate gateways, and (b) the ability to use **two gateways simultaneously in one transaction, chosen by the user**. The contract never names the twelve gateways, and the platform's baseline scope (`canonical-context.md` §5) records split payment as deferred (`RB-PAY-01`) with one active gateway per payment in v1.

The technical realities behind the deferral:

- Iranian gateways are not composable: each has its own redirect, its own verification, its own refund semantics and its own settlement cycle. Two gateways in one transaction means two captures, two verifications, a partial-failure policy, and refunds that must be split proportionally.
- A partial capture (one gateway succeeds, the other fails) requires a compensating refund flow and customer communication the contract does not specify.
- The twelve gateways are unnamed, so even the driver list is an assumption (`AMB-PAY-002`).

## Decision

### 1. Adapter, not integration

Every gateway is a `GatewayDriver` implementation (`initiate`, `verify`, `refund`, `statement`, `capabilities`) behind `Payments\Domain\Contract\GatewayDriver`. No module outside Payments imports a gateway SDK or HTTP client. Drivers live in `app/Modules/Payments/Infrastructure/Gateway/`.

- Twelve driver classes ship at baseline (the candidate set in `integrations.md` §2.3), each with a key, capability flags and a config schema. A driver without credentials is registered but cannot be activated.
- Every driver passes the same contract test suite, parameterised from the fake driver's scenario matrix. "Done" means passing that suite, not "it worked once in the sandbox".

### 2. One active gateway per payment

- `pay_gateways` has exactly one `is_active = true` row (partial unique index). The operator activates a gateway in the admin panel with step-up 2FA; the action is audited.
- The buyer does not choose a gateway in v1 (the contract's "user choice" belongs to the split-payment feature). The active gateway is resolved server-side through `GatewayRegistry`, and the resolved key is **stored on the payment** so a mid-flight activation change cannot move money elsewhere.
- An inactive gateway cannot be initiated even by crafting the URL — the controller resolves through the registry, not through a route parameter.

### 3. Split payment is prepared, not built

- `pay_payments` has a `pay_payment_legs` child table, **exactly one leg in v1**.
- `GatewayCapabilities::multiLeg` exists; `GatewayDriver` has no split-specific methods, because splitting is an orchestration concern, not a driver concern.
- The allocation of legs to sub-orders is modelled (`leg.sub_order_ids`), because the ledger already allocates per sub-order.
- `RB-PAY-01` tracks the work: leg orchestration, partial-capture policy, proportional refunds, UI for choosing two gateways, and the reconciliation that compares two statements.

### 4. Contract gap is recorded, not hidden

The contract requires split payment; v1 does not deliver it. This is a **scope deviation that needs the owner's written acknowledgement** (an addendum or an explicit deferral in the acceptance minutes). The delivery notes must say so plainly; silently shipping single-gateway would be a contract breach under ماده ۴ بند ۱.

## Consequences

**Positive**

- Adding or replacing a gateway is config plus one class — no change to Orders, Finance or the checkout UI.
- Gateway failures are isolated: a broken driver opens a circuit, the operator switches the active gateway, buyers keep paying.
- The single-active model is operationally simple to reason about: one merchant account receives money, one reconciliation report, one settlement source.
- The leg table and capability flag mean split payment is a feature flag plus orchestration, not a schema migration across payments, orders and finance.

**Negative**

- **The contract's split-payment clause is unmet in v1.** This is the single largest scope gap in the delivery and must be negotiated, not assumed away.
- Twelve drivers is real maintenance: each provider changes endpoints, signatures and error codes; each needs sandbox credentials the client must obtain.
- One active gateway is a single point of failure at the business level: if the provider has an outage, every payment fails until an operator switches. Mitigated by a fast switch and an alert, not by redundancy.
- Per-gateway reconciliation reports differ in shape; normalising them costs ongoing effort.

**Neutral**

- The buyer does not choose a gateway, which is one less decision at checkout and one less support question; the contract's choice requirement returns with split payment.
- The driver contract is intentionally vendor-agnostic; if a gateway cannot support refunds, `capabilities()->refund = false` and the UI routes the case to manual settlement.

## Alternatives considered

| Alternative | Why it lost |
|---|---|
| One generic REST driver configured per gateway | Gateways differ in flow (redirect vs. token vs. signed form), verification and refunds; a generic driver becomes a pile of `if ($gateway === …)` |
| Integrate only one gateway in v1, add later | Violates the explicit 12-gateway clause; the adapter cost is small if designed up front |
| Build split payment in v1 | Partial-capture and proportional-refund policy is unspecified; delivering it badly is worse than delivering it deliberately later with owner sign-off |
| Multiple active gateways with buyer choice (no split) | Closer to the clause's spirit but doubles reconciliation and settlement sources without the contract's actual requirement (one transaction, two gateways) |
| Use a payment aggregator that fronts many banks | The contract requires 12 *separate* gateways and per-gateway logs; an aggregator hides the gateway identity and the settlement relationship |

## References

- Contract page 5 / پیوست ۱ بند ۵; page 11 / پیوست ۱۳ clauses 6–7; page 3 / ماده ۴ بند ۱ (complete delivery obligation).
- `docs/canonical-context.md` §5 (gateway adapter, one active gateway, split deferred as `RB-PAY-01`).
- `../architecture/integrations.md` §2 (driver contract, registry, states, retries, reconciliation, fakes); `../architecture/data-flow.md` §1.
