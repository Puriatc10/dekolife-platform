# Modular Monolith

| | |
|---|---|
| **Purpose** | Define how a module is built, what it may and may not touch, and how one module could be extracted into its own service later without rewriting the domain. |
| **Audience** | Every engineer writing code in `app/Modules/*`; reviewers rejecting PRs that break the rules. |
| **Status** | Baseline v1.0 — rules are enforceable by `tests/Architecture/LayeringTest.php`. |
| **Owner** | Agent C (architecture). |

**Source:** contract `DK-1405-01` page 5 / پیوست ۱ clauses 1, 2, 8 (Laravel + MySQL, MVC three-layer, modular and extensible structure, schema that accepts a new user level or product type without rewriting the core); `docs/canonical-context.md` §§3, 5, 7.

---

## 1. The shape, and why this shape

One deployable, 28 bounded contexts, hard walls between them in code. The contract asks for "معماری MVC سه‌لایه و ساختار ماژولار قابل توسعه" (three-layer MVC, modular, extensible) — not microservices. `ADR-0001` records the full argument.

What "modular" buys us here is concrete:

- A new user level (پیوست ۷ بند ۶) or a new product type (پیوست ۱ بند ۸) is a new role row, a new price list, and new attribute definitions — not a schema rewrite. That only holds if modules own their tables and expose services instead of letting callers reach into their data.
- A future mobile app (`RB-MOB-01`) is a new HTTP adapter over existing application services, not a second backend.
- If one context ever needs its own process (payments, notifications, reporting are the plausible candidates), it leaves with its domain and migrations intact.

## 2. Directory anatomy

Every module has the same skeleton. Example: Orders.

```
app/Modules/Orders/
├── Domain/
│   ├── Model/
│   │   ├── Order.php                    # aggregate root, no Eloquent
│   │   ├── SubOrder.php                 # entity inside the aggregate
│   │   ├── OrderItem.php
│   │   └── ValueObject/
│   │       ├── OrderId.php
│   │       ├── OrderNumber.php
│   │       └── OrderTotals.php
│   ├── Enum/
│   │   ├── OrderStatus.php
│   │   └── SubOrderStatus.php
│   ├── Event/
│   │   ├── OrderPlaced.php
│   │   ├── SubOrderAccepted.php
│   │   └── OrderCancelled.php
│   ├── Contract/
│   │   ├── OrderRepository.php          # persistence port
│   │   └── OrderNumberGenerator.php
│   ├── Exception/
│   │   ├── IllegalOrderTransition.php
│   │   └── OrderNotPayable.php
│   └── Service/
│       └── OrderStateMachine.php
├── Application/
│   ├── Command/
│   │   ├── PlaceOrder.php
│   │   ├── CancelOrder.php
│   │   └── MarkSubOrderShipped.php
│   ├── Handler/
│   │   ├── PlaceOrderHandler.php
│   │   ├── CancelOrderHandler.php
│   │   └── MarkSubOrderShippedHandler.php
│   ├── Query/
│   │   ├── CustomerOrderList.php
│   │   └── SellerOrderQueue.php
│   ├── Contract/                        # the module's public API
│   │   ├── OrderReader.php
│   │   └── OrderPayability.php
│   └── DTO/
│       ├── OrderResult.php
│       └── OrderSummary.php
├── Infrastructure/
│   ├── Persistence/
│   │   ├── Eloquent/OrderModel.php
│   │   ├── Eloquent/SubOrderModel.php
│   │   ├── Eloquent/OrderItemModel.php
│   │   ├── EloquentOrderRepository.php
│   │   └── SequenceOrderNumberGenerator.php
│   └── Listeners/                       # inbound event handlers
│       └── ReleaseOrderOnPaymentFailure.php
├── Http/
│   ├── Controller/Storefront/CheckoutController.php
│   ├── Controller/Store/SubOrderController.php
│   ├── Controller/Admin/OrderController.php
│   ├── Controller/Api/V1/OrderController.php
│   ├── Request/PlaceOrderRequest.php
│   ├── Resource/OrderResource.php
│   ├── Livewire/CheckoutWizard.php
│   ├── Livewire/SellerOrderQueue.php
│   └── Middleware/EnsureOrderOwner.php
├── Providers/
│   └── OrdersServiceProvider.php
├── routes/
│   ├── storefront.php
│   ├── store.php
│   ├── admin.php
│   ├── api.php
│   └── console.php
├── database/
│   ├── migrations/
│   │   └── 2026_12_01_000100_create_ord_orders_table.php
│   ├── factories/
│   └── seeders/
└── tests/
    ├── Unit/OrderStateMachineTest.php
    ├── Feature/PlaceOrderTest.php
    └── Feature/OrderAuthorizationTest.php
```

| Folder | May contain | May **not** contain |
|---|---|---|
| `Domain` | Entities, value objects, enums, domain events, repository/gateway interfaces, domain services, domain exceptions | Eloquent, facades, HTTP, config, `now()` without an injected clock, any other module's classes |
| `Application` | Commands/queries + handlers, transactions, DTOs, the module's public contracts, orchestration of other modules' contracts | SQL, Blade, request objects, business rules that belong in Domain |
| `Infrastructure` | Eloquent models and repositories, external clients, file storage, listeners that adapt events to the module | Business rules, HTTP responses |
| `Http` | Controllers, FormRequests, API Resources, Livewire components, route middleware | Queries, arithmetic, state transitions, `DB::` calls |
| `Providers` | Bindings, route/migration/view registration, event subscriptions, policy registration | Logic that runs on every request beyond registration |
| `routes` | Route definitions with names and middleware | Closures with logic |

`app/Shared/` follows the same discipline with one extra rule: **no business rules**. It holds `Money`, Jalali date helpers, the `Auditable` trait, base policy, event bus contracts, pagination/error envelope, `Idempotency`, `FileStorage`, the transactional outbox (`shr_outbox_messages`, `shr_idempotency_keys`), the external-call log (`shr_external_calls`), heartbeats (`shr_heartbeats`) and runtime flags (`shr_runtime_flags`). If a helper needs to know what an order is, it belongs in Orders.

## 3. Naming conventions

| Thing | Pattern | Example |
|---|---|---|
| Namespace root | `App\Modules\{Module}\` | `App\Modules\Loyalty\` |
| Aggregate / entity | singular noun | `Order`, `SubOrder`, `RewardVoucher` |
| Value object | noun + role | `OrderId`, `Money`, `Iban`, `PointBalance` |
| Command | imperative verb phrase | `PlaceOrder`, `RedeemReward` |
| Handler | command + `Handler` | `PlaceOrderHandler` |
| Query | noun phrase describing the read | `CustomerOrderList`, `SettlementBatchPreview` |
| Domain event | past tense | `OrderPlaced`, `CommissionAccrued` |
| Public contract | role noun | `OrderReader`, `WalletOperator`, `PriceResolver` |
| Eloquent model | entity + `Model` suffix, in `Infrastructure\Persistence\Eloquent` | `OrderModel` |
| Table | prefix + plural snake case | `ord_orders`, `fin_ledger_entries` |
| Migration | `{timestamp}_{action}_{prefix}{table}_table.php` | `2026_12_01_000100_create_ord_orders_table.php` |
| Route name | `{module}.{surface}.{action}` | `orders.storefront.checkout`, `orders.admin.index` |
| Policy | entity + `Policy` | `OrderPolicy`, `SettlementPolicy` |
| Job | verb phrase + `Job` | `AccruePointsForInvoiceJob` |
| Permission string | `{module}.{resource}.{action}` | `orders.suborder.ship`, `finance.settlement.approve` |

Table prefixes are fixed by `canonical-context.md` §3 and must appear in every migration the module owns. A migration that creates a table without its module's prefix fails review.

## 4. Dependency rules

### 4.1 Layer stack

Modules are assigned to a layer. **Synchronous calls only point downward; events may travel in any direction.**

| Layer | Modules |
|---|---|
| L0 Shared kernel | `Shared` |
| L1 Foundation | Identity, Users, Admin, Cms |
| L2 Supply | Merchants, Catalog, Pricing, Inventory |
| L3 Commerce | Cart, Orders, Shipping, Invoices, Payments, Finance, Returns |
| L4 Growth | Promotions, Loyalty, Marketing, Compensation, Reviews |
| L5 Services | Tenders, Classifieds, Patogh, Samples, Messaging, Notifications, FieldOps |
| L6 Insight | Reporting |

Why this ordering: a module may assume the modules below it exist and are stable, never the reverse. Loyalty reacting to `OrderCompleted` is an event (upward); Loyalty posting a cash reward calls `Finance\Contracts\WalletOperator` (downward). Promotions contributing a discount to price resolution is an inversion: Promotions implements `Pricing\Domain\Contract\DiscountContributor` and registers it in its own provider, so Pricing never imports Promotions.

### 4.2 The five hard rules

1. **No cross-module table writes.** `UPDATE ord_orders SET ...` from Loyalty is a defect even if the SQL works. Call a command or listen to an event.
2. **Cross-module reads go through a query contract**, not through another module's Eloquent model. Read-only joins are allowed only in `Reporting` and in a module's own reporting queries, and must be documented with the tables involved.
3. **No cross-module transactions that span an aggregate boundary** except through a service that explicitly joins the caller's transaction (`Finance\Contracts\Ledger::post()` is the only such service in v1 — see §7).
4. **Domain is framework-free.** No `Illuminate\*` imports in `Domain/` except `Illuminate\Contracts\Events\ShouldDispatchAfterCommit` on event classes and `Illuminate\Support\Collection` where a collection is genuinely a domain concept (discouraged).
5. **No service locator.** Dependencies arrive through constructor injection; `app()`/`resolve()` in module code fails review.

### 4.3 Documented exceptions

Every exception is listed here, justified, and excluded by name in the architecture test. Adding one requires a review note in the PR.

| # | Exception | Justification | Guard |
|---|---|---|---|
| E1 | `Cart` → `Promotions\Application\Contract\CouponValidator` | A shopper must learn immediately that a code is invalid, before checkout; an event round trip cannot answer "is this coupon valid for this cart". Read-only, no writes. | Contract exposes `validate(CouponCode, CartSnapshot): CouponVerdict` only. |
| E2 | `Orders` → `Shipping\Application\Contract\ShippingQuoter` | Shipping cost is part of the order total at placement time; can't be eventual. Same layer. | Quote is pure calculation; shipment creation stays event-driven. |
| E3 | `Reporting` → any module's tables (read-only) | Reports must not be 28 sequential N+1 service calls; reporting is explicitly read-only. | No writes; queries reviewed; no ORM models outside its own namespace. |
| E4 | `Admin` → any module's `Application\Contract` | Admin acts *on behalf of* other contexts (approve, freeze, adjust); it orchestrates their commands rather than reimplementing them. | Admin never writes another module's tables; every action is audited. |
| E5 | `Notifications` consumes every module's events | Notification is a fan-out sink by definition. | Consumes only; never calls back into a module synchronously. |

### 4.4 Enforcement

```php
// tests/Architecture/LayeringTest.php
arch('domain is framework-free')
    ->expect('App\Modules\*\Domain')
    ->not->toUse(['Illuminate\Http', 'Illuminate\Support\Facades', 'Illuminate\Database'])
    ->ignoring(['Illuminate\Contracts\Events\ShouldDispatchAfterCommit']);

arch('domain does not reach into other modules')
    ->expect('App\Modules\Orders\Domain')
    ->not->toUse(['App\Modules\Loyalty', 'App\Modules\Payments', 'App\Modules\Finance']);

arch('http layer holds no persistence')
    ->expect('App\Modules\*\Http')
    ->not->toUse(['App\Modules\*\Infrastructure', 'Illuminate\Support\Facades\DB']);

arch('modules do not touch foreign prefixes')
    ->expect('App\Modules\Loyalty')
    ->not->toUse(['App\Modules\Orders\Infrastructure']);
```

Two additional checks run in CI as custom tests:

- `ModuleTablePrefixTest` scans migrations and asserts every `Schema::create()` table starts with the owning module's prefix (or `shr_` for Shared).
- `NoCrossModuleWriteTest` greps module source for `DB::table('{other_prefix}` and `->update(`/`->insert(` on a foreign model class.

## 5. Service contracts

A module's public API is a set of small interfaces in `Application/Contract` (behavioural) or `Domain/Contract` (persistence/external ports). Nothing else in the module is public.

```php
namespace App\Modules\Pricing\Application\Contract;

interface PriceResolver
{
    /**
     * Resolve the final unit price for a product for a given actor and quantity.
     * Pipeline: base → role/tier price list → volume → promotion → coupon → loyalty.
     */
    public function resolve(PriceQuery $query): ResolvedPrice;

    /** @return list<ResolvedPrice> */
    public function resolveMany(PriceQueryBatch $batch): array;
}
```

```php
namespace App\Modules\Finance\Application\Contract;

interface Ledger
{
    /**
     * Post a balanced entry. Idempotent on $reference: a repeated call with the
     * same reference returns the original entry and writes nothing.
     */
    public function post(LedgerPosting $posting): LedgerEntryId;

    public function balance(AccountRef $account, ?DateRange $range = null): Money;
}
```

Rules for contracts:

- **Input/output are DTOs or scalars**, never Eloquent models, never arrays with undocumented keys.
- **No `Illuminate\Http` types**; no `Request`, no `Response`.
- **Exceptions are declared**: a contract documents which domain exceptions callers must handle (`OrderNotPayable`, `InsufficientStock`, `WalletFrozen`). Callers never catch `\Exception`.
- **Contracts are additive.** Removing or changing a signature is a breaking change and requires a deprecation cycle: add the new method, keep the old one delegating, mark `@deprecated since vX`, remove one minor later.
- **One implementation per contract in v1**, bound in the module's provider. Tests may swap it.

Interfaces live next to the module, but the binding is registered in `Providers/{Module}ServiceProvider.php`:

```php
public function register(): void
{
    $this->app->bind(PriceResolver::class, PipelinePriceResolver::class);
    $this->app->bind(OrderRepository::class, EloquentOrderRepository::class);
    $this->app->singleton(OrderStateMachine::class);
}
```

## 6. Domain events

Events are how modules talk without knowing each other, and how the system stays consistent when a step is slow or external.

### 6.1 Envelope

```php
namespace App\Modules\Orders\Domain\Event;

use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;

final readonly class OrderPlaced implements ShouldDispatchAfterCommit
{
    public function __construct(
        public string $orderId,
        public string $orderPublicId,
        public string $buyerId,
        public int $totalAmount,      // Toman, integer
        public array $subOrderIds,
        public string $occurredAt,    // ISO-8601 UTC
        public string $correlationId, // request_id of the originating command
    ) {}
}
```

Every event carries: aggregate id, public id (for logs), actor/buyer id where relevant, money as integers, `occurredAt` in UTC, `correlationId` equal to the originating request id. Events are immutable and serialisable to JSON by construction — no models, no `Carbon` instances in the payload (ISO strings instead), so an event can be queued or replayed.

### 6.2 Dispatch semantics

| Aspect | Decision |
|---|---|
| When | `ShouldDispatchAfterCommit` — listeners never see an event for a rolled-back transaction |
| Sync vs queued | Financial and inventory-critical listeners are **queued** (at-least-once, retried); UI-affecting listeners may be sync |
| Delivery | Transactional outbox for events that leave the module or the process (`shr_outbox_messages`, see `background-jobs.md` §4) |
| Ordering | Per-aggregate ordering is not guaranteed; handlers are idempotent and order-independent by design |
| Duplicates | Every listener has an idempotency key: `eventName + aggregateId + occurredAt` recorded in `shr_idempotency_keys` or enforced by a unique constraint |
| Failures | Retried by the queue with backoff; after max attempts the event lands in `failed_jobs` and raises an admin alert. The outbox row stays `pending` until the relay marks it `dispatched` |

### 6.3 Event catalogue conventions

- Emitted events are documented in `domain-boundaries.md` per module, with their consumers.
- A consumer that is added later must not require a change in the producer — if it does, the event payload is wrong.
- Events describe facts, never intentions (`PaymentSucceeded`, not `MarkOrderPaid`).
- Cross-module side effects that must not be lost (ledger posting, settlement freeze, commission accrual) are **also** reconciled by a nightly check that compares derived state to source tables; the check reports drift rather than silently fixing it.

## 7. Transactions

| Rule | Detail |
|---|---|
| One transaction per command | The handler opens it; repository methods never open their own |
| Nested calls join | `DB::transactionLevel() > 0` → join the ambient transaction; never `DB::beginTransaction()` inside a service |
| Ledger is the one cross-module participant | `Ledger::post()` deliberately participates in the caller's transaction so an order state change and its money movement commit together. It is the only such service. |
| External calls never inside a transaction | Gateway initiate/verify, SMS, email, file uploads happen after commit or in a queued job. A slow bank must never hold a row lock. |
| Lock ordering | Always lock in the order: inventory → order → payment → ledger. Documented because deadlocks at checkout are otherwise a guessing game. |
| Retry on deadlock | Laravel's `DB::transaction($cb, attempts: 3)` on commands that touch stock and order rows |
| Idempotency | Write commands accept `idempotencyKey`; `shr_idempotency_keys` has a unique index on `(scope, key)` and stores the response payload |

## 8. Extraction playbook

If a module must become its own service (most likely: Payments, Notifications, Reporting), the work is bounded because the contracts already exist.

1. **Stabilise the contract.** Freeze the module's `Application/Contract` set; add contract tests that both implementations must pass.
2. **Route all inbound traffic through the contract.** Find any caller touching the module's tables or internals; replace with a contract call. The architecture test already prevents most of this.
3. **Move side effects to events.** Replace synchronous calls *into* the module that only notify it (e.g. "order completed, accrue points") with events consumed from the outbox — the outbox is already the cutover point.
4. **Split the schema.** Give the module its own database user and schema; replace cross-module read joins (allowed only in reporting) with contract reads or replicated read models.
5. **Implement a remote adapter.** Write `HttpOrderReader implements OrderReader` in the caller-side infrastructure and bind it by config. Callers do not change.
6. **Dual-run.** Run the remote adapter against the extraction candidate in staging with shadow writes; compare responses and latencies.
7. **Cut over with a flag.** `adm_feature_flags` selects local vs remote binding per environment. Rollback is a flag flip, not a deploy.
8. **Delete the in-process implementation** only after one full release cycle of green metrics.

Two preconditions for any extraction: the module must have zero undocumented cross-module joins, and its jobs must be idempotent (they will be retried across a network boundary).

## 9. Anti-pattern checklist

Reject a PR that does any of these:

- A controller or Livewire component that calls `Model::query()` directly.
- A `Domain` class importing `Carbon`, `DB`, `Log`, `config()`, or `now()` without an injected clock.
- A migration with a foreign table prefix.
- An event carrying an Eloquent model or a `Carbon` instance.
- A listener that writes to two modules' tables in one transaction without a contract in between.
- A policy that returns `true` for `admin` by early return without also being logged in `adm_audit_logs`.
- A new module added without a row in `domain-boundaries.md` and a provider registration.
- A `catch (\Exception $e) {}` that swallows a payment or ledger failure.
- `env()` outside `config/`.
- A queue job without an idempotency key.
