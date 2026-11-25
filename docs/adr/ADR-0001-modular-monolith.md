# ADR-0001: Modular monolith over microservices

- **Status:** Accepted (agent decision, pending owner review)
- **Date:** 2026-11-25
- **Deciders:** Architecture owner; delivery lead; client technical reviewer (informational)
- **Related:** REQ-CTR-001, REQ-CTR-008; ADR-0002, ADR-0006; `../architecture/modular-monolith.md`

## Context

The contract asks for a Laravel application with "MVC three-layer architecture and a modular, extensible structure" (پیوست ۱ بند ۱) and a schema that accepts a new user level or product type "without rewriting the core" (بند ۸). It does not ask for distributed systems.

The forces that are actually present:

- **One team, one deploy target.** The client provides a single server (ماده ۱, ماده ۵ بند ۳). There is no Kubernetes, no service mesh, no platform team.
- **Strong transactional needs.** Money, stock, points and orders change together. An order that is paid but not invoiced, or invoiced but not reserved, is a support ticket at best and a financial discrepancy at worst.
- **28 distinct requirement areas** with genuinely different vocabularies (ledger vs. loyalty rule sets vs. classifieds). Left in one `app/Models` folder they will interleave within months.
- **A future mobile client** (`RB-MOB-01`) that must reuse the same business rules, not reimplement them.
- **A three-month delivery window** (ماده ۳). Distributed infrastructure would consume a large share of it for no contractual benefit.

## Decision

Build a **modular monolith**: one Laravel application, one database, one deployable — divided into the 28 bounded contexts of `canonical-context.md` §3, each with `Domain/`, `Application/`, `Infrastructure/`, `Http/`, its own provider, routes, migrations and tests.

Enforce the boundaries mechanically, not by convention:

1. Layer stack (L0 Shared → L6 Reporting) with synchronous dependencies pointing only downward.
2. Cross-module communication through **application contracts** (interfaces with DTOs) or **domain events**.
3. **No cross-module table writes.** Reads only through query contracts, with an explicit and short read-join register for Reporting.
4. Architecture tests (`Pest arch()` plus custom prefix and cross-write tests) that fail CI, not a wiki page.
5. An **extraction playbook** (`modular-monolith.md` §8) so a module can leave later without a rewrite: contract freeze → outbox cutover → schema split → remote adapter behind a flag.

## Consequences

**Positive**

- One transaction can cover an order, its stock reservation and its ledger entry, which is the only way to keep money correct without sagas and compensating transactions.
- One deploy, one log stream, one backup, one place to debug. The operator's job stays tractable for a client-hosted system.
- Refactoring across a boundary is a code change, not a cross-team negotiation.
- The architecture tests make "modular" a property that is verified on every PR, which is what usually fails in monoliths.
- The mobile app and the API reuse application services; the future extraction path is designed, not hoped for.

**Negative**

- All modules share a process and a database connection pool; one pathological query can slow checkout. Mitigated by queue separation and reporting isolation, not eliminated.
- A single deployment means a bad release affects everything; expand-contract migrations are mandatory.
- Discipline is enforced by tests that must be maintained. When a legitimate exception appears, it must be added to the exception table — friction by design.
- Scaling is vertical until someone splits a module or the database (`RB-OPS-02`).

**Neutral**

- Microservices would eventually be needed only if the field-sales tracking or notification volume grew by orders of magnitude; nothing in the contract suggests that.
- Module boundaries are not free: 28 providers, 28 route files, 28 migration paths. This is a fixed cost paid once at scaffold.

## Alternatives considered

| Alternative | Why it lost |
|---|---|
| Microservices per domain (orders, payments, catalog, loyalty as separate services) | Requires distributed transactions or sagas for money and stock; 10× the operational surface for a single-host client deployment; impossible to deliver in three months with one team |
| Single Laravel app with no module boundaries (`app/Models`, `app/Http`) | Fastest to start, unmaintainable at 28 requirement areas; the contract's "extensible without rewriting the core" would fail on the first new user level |
| Laravel packages per module (composer path repositories) | Boundary enforcement is stronger but the tooling cost (autoload, versioning, test orchestration) is not justified at this size |
| Modular monolith with separate schemas per module | Extra migration and cross-schema grant complexity; MySQL schema separation buys little without separate services |

## References

- Contract page 5 / پیوست ۱ clauses 1, 2, 8; page 3 / ماده ۴ clause 4 (source delivery, documented structure).
- `docs/canonical-context.md` §3 (module list and prefixes), §5 (modular monolith baseline), §7 (conventions).
- `../architecture/overview.md` §3–§5; `../architecture/modular-monolith.md`; `../architecture/domain-boundaries.md` §2.
