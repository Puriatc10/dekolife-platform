# Architecture Decision Records

| | |
|---|---|
| **Purpose** | Index and conventions for the decisions that shape the platform, in Nygard format, so that six months from now "why is it like this" has an answer with a date and an owner. |
| **Audience** | Engineers, reviewers, the client's technical reviewer, future maintainers. |
| **Status** | Living index; ADRs themselves are immutable once accepted. |
| **Owner** | Agent C (architecture); each ADR names its decision owner. |

**Source:** `docs/canonical-context.md` §5 (baseline engineering decisions that must be reflected in ADRs).

---

## Index

| ADR | Title | Status | Contract position |
|---|---|---|---|
| [0001](ADR-0001-modular-monolith.md) | Modular monolith over microservices | Accepted (agent decision, pending owner review) | Silent on architecture style; mandates modular, extensible structure |
| [0002](ADR-0002-laravel-mysql.md) | Laravel 13 + MySQL 8.4 | Accepted (contract-mandated) | پیوست ۱ بند ۱ |
| [0003](ADR-0003-blade-livewire.md) | Blade + Livewire over an SPA | Accepted (agent decision, pending owner review) | Silent; mandates MVC three-layer, responsive, PWA |
| [0004](ADR-0004-money-and-ledger.md) | Money as integer Toman with an append-only ledger | Accepted (agent decision, pending owner review) | Silent on representation; prices are in Toman; settlement/ledger implied |
| [0005](ADR-0005-parent-and-sub-orders.md) | Parent order + per-seller sub-orders | Accepted (agent decision, pending owner review) | Multi-vendor implied by پیوست ۳ and پیوست ۱۱; model not specified |
| [0006](ADR-0006-database-queue-and-cache.md) | Database queue/cache instead of Redis | Accepted (agent decision, pending owner review) | Silent; client provides the host |
| [0007](ADR-0007-roles-and-policies.md) | Many-to-many roles + policies, secondary level gated by admin | Accepted (agent decision, pending owner review) | پیوست ۳ (five levels), پیوست ۷ بند ۶ (secondary level) |
| [0008](ADR-0008-gateway-adapter.md) | Gateway adapter, one active gateway per payment, split payment deferred | Accepted (agent decision, pending owner review) | پیوست ۱ بند ۵ and پیوست ۱۳ بند ۷ require 12 gateways **and** simultaneous two-gateway payment — see the gap note |

## Status vocabulary

| Status | Meaning |
|---|---|
| `Proposed` | Written, not yet agreed |
| `Accepted (contract-mandated)` | The contract forces this; changing it is a contract change, not an engineering choice |
| `Accepted (agent decision, pending owner review)` | Engineering chose this where the contract is silent; the owner may override in writing, which produces a superseding ADR |
| `Superseded by ADR-NNNN` | Kept for history, no longer in force |
| `Deprecated` | Still true in code, scheduled to change |

Every ADR here starts at either `Accepted (contract-mandated)` or `Accepted (agent decision, pending owner review)`. There are no `Proposed` ADRs at baseline: a proposal that shapes the code must be decided before the code exists.

## Conventions

1. **One decision per ADR.** If the title has an "and" in it, it is probably two ADRs.
2. **Nygard sections**: Title, Status, Date, Context, Decision, Consequences, Alternatives considered, References. Consequences are split into positive / negative / neutral — the negative list is the useful one.
3. **Immutable once accepted.** A changed mind produces a new ADR with `Supersedes ADR-NNNN`; the old file is never edited except its status line.
4. **Numbers are never reused.** Gaps are acceptable; renumbering breaks every link.
5. **Every ADR cites its contract or canonical-context anchor.** A decision with no anchor is a preference, not an architecture decision.
6. **`RB-` ids** point to deferred work; **`AMB-` ids** point to contract ambiguities that block or shape the decision. Where an `AMB-` id in an ADR has no counterpart yet in the ambiguity register, the id is provisional and the register is the authority on numbering; the ADR keeps the citation so the decision remains traceable.

## Related documents

- `../architecture/overview.md` — the system these decisions describe.
- `../architecture/modular-monolith.md` — mechanics behind ADR-0001.
- `../architecture/security-architecture.md` — mechanics behind ADR-0007.
- `../architecture/data-flow.md` — mechanics behind ADR-0004, 0005, 0008.
- `../architecture/integrations.md` — mechanics behind ADR-0008.
- `../architecture/background-jobs.md` — mechanics behind ADR-0006.

## Template

```markdown
# ADR-NNNN: Title in the imperative

- **Status:** Accepted (agent decision, pending owner review)
- **Date:** YYYY-MM-DD
- **Deciders:** role(s)
- **Related:** REQ-XXX-NNN, AMB-XXX-NNN, RB-XXX-NN

## Context
What forces are at play: contract clauses, scale, team, hosting, deadlines.

## Decision
What we will do, concretely enough that a reviewer can check the code against it.

## Consequences
**Positive** / **Negative** / **Neutral** — including what becomes harder.

## Alternatives considered
Each with the reason it lost.

## References
Contract pages/appendices, canonical-context sections, related ADRs.
```
