# ADR-0002: Laravel 13 + PHP 8.4 + MySQL 8.4

- **Status:** Accepted (contract-mandated)
- **Date:** 2026-11-25
- **Deciders:** Contract (unilateral); delivery lead; architecture owner
- **Related:** REQ-CTR-001; ADR-0001, ADR-0006, ADR-0004; `../architecture/technology-stack.md`

## Context

پیوست ۱ بند ۱ of the contract states:

> «پیاده‌سازی با فریمورک لاراول (Laravel) و پایگاه داده MySQL، با معماری MVC سه‌لایه و ساختار ماژولار قابل توسعه»

This is not a technical preference to be evaluated — it is a delivery obligation attached to a fixed-price contract with penalty clauses (ماده ۴ بند ۲). Choosing PostgreSQL and a different framework would be a contract breach regardless of technical merit.

Two secondary forces matter:

- **The client's hosting** is a conventional Iranian host with nginx/Apache, PHP-FPM and MySQL. Nothing exotic is available, and the client's own IT may need to touch it after handover.
- **The 5-year free bug-fix warranty** (ماده ۱۲) means the stack must remain maintainable by an ordinary Laravel developer, not by whoever made an exotic choice.

Version selection within the mandate:

- `laravel/framework` **13.44.1** and `livewire/livewire` **4.4.10** were verified on 2026-11-25 from Packagist (`canonical-context.md` §2). Laravel 13 is the current line; starting on 12 would mean a major upgrade inside the warranty window.
- PHP **8.4**: the runtime Laravel 13 supports, with typed properties and `readonly` classes used throughout the domain layer.
- MySQL **8.4**: an LTS line, same major in local/CI/staging/production, with the JSON and generated-column features the blind-index design needs.

## Decision

1. Runtime is **PHP 8.4** (fpm + cli), pinned by container image digest; `composer.json` requires `"php": "^8.4"`.
2. Framework is **Laravel 13.x**, with `laravel/framework` pinned to an exact version (`13.44.1` at baseline) — not a floating constraint. Patch updates are deliberate PRs, not `composer update` surprises.
3. Database is **MySQL 8.4**, InnoDB, `utf8mb4_0900_ai_ci`, strict `sql_mode`, `time_zone='+00:00'`, binlog enabled.
4. No SQLite anywhere except a developer's throwaway experiment: tests run against MySQL 8.4 in CI and locally.
5. Architecture follows Laravel's own three layers as the contract's "MVC three-layer": HTTP adapters (`Http/`, Blade/Livewire) → application services (`Application/`) → domain + infrastructure (`Domain/`, `Infrastructure/`). The framework's default structure is a starting point, not the design.

## Consequences

**Positive**

- Contract compliance is unambiguous and auditable.
- The largest possible hiring and handover pool; the client's team can hire any Laravel developer.
- Eloquent, migrations, queues, scheduler, events, validation and Blade are all first-party — fewer bespoke mechanisms to document and maintain for five years.
- MySQL 8.4 LTS plus PHP 8.4 security support covers the warranty period with a planned upgrade path, not an emergency one.

**Negative**

- MySQL imposes real limits we must design around: no partial indexes (generated columns instead), `SELECT ... FOR UPDATE` serialisation on hot rows, JSON only queryable through generated columns, FULLTEXT rather than a real search engine.
- Laravel's active-record defaults actively encourage the architecture we are trying to prevent (logic in models/controllers). The layering rules and architecture tests exist because of this, and they cost ongoing effort.
- Major framework upgrades are a recurring cost that cannot be avoided, only scheduled.

**Neutral**

- The contract fixes the stack, so this ADR's real value is the *version pinning and upgrade policy*, not the choice itself.
- If the client ever asks for PostgreSQL, that is a contract addendum, not an engineering decision.

## Alternatives considered

| Alternative | Why it lost |
|---|---|
| Laravel 12 (previous major) | Would require a major upgrade inside the warranty window for no benefit |
| PostgreSQL | Direct contract violation (پیوست ۱ بند ۱) |
| Symfony / Django / Node stack | Same violation, plus a rewrite of the entire delivery plan |
| MariaDB | Not "MySQL" in the contract's sense; optimizer/JSON differences would surface in production, not staging |
| SQLite for tests | Explicitly rejected by canonical context: SQL semantics (`FOR UPDATE`, generated columns, collation) are part of what must be tested |
| Laravel with a floating `^13.0` constraint | Reproducibility loss; a patch could land mid-acceptance |

## References

- Contract page 5 / پیوست ۱ clause 1; page 3 / ماده ۴ بند ۲ (penalties), ماده ۱۲ (warranty).
- `docs/canonical-context.md` §2 (pinned stack), §0 (non-negotiables).
- `../architecture/technology-stack.md` §1–§2 (versions, verification dates, upgrade policy).
