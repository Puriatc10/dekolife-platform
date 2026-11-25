# Canonical Context — Dekolife Platform (single source of truth for all agents)

> Every agent working in this repository MUST read this file first. It pins names, paths,
> identifiers, stack versions and conventions. If a document or task description contradicts
> this file, this file wins — and report the contradiction to the Orchestrator.
> Contract text is the ultimate authority for **requirements**; this file only fixes
> **how we name, structure and record** them.

## 0. Mission (this mission's scope)

Build the **web application** of the Dekolife platform (سامانه دکولایف) as specified by the
signed contract and its 13 appendices (`input/contract/`), plus 14 appendices of feature
detail. Deliverable now: documentation baseline → repository + CI → phased implementation of
the web product.

### Non-negotiables

1. Backend **Laravel 13 (PHP 8.4)**; database **MySQL 8.4**. No Django, no PostgreSQL.
   (Contract appendix 1, clause 1.)
2. **Web only** in this mission. Native Android/iOS apps, APK, store publishing are
   **Deferred** (`RB-MOB-01`), documented as out of scope for now but kept in the roadmap.
3. PWA is in scope as a *web* deliverable in a later phase; it is never called a native app.
4. The reservation/appointment module (page 9 of the contract) is **out of scope**
   (separate contract no. 2); any integration is a future addendum.
5. No requirement may be silently dropped. Anything postponed gets an ID, a reason and a
   roadmap entry.
6. No real secrets, credentials, bank details or personal data in git.

## 1. Product in three sentences

Dekolife is a **multi-vendor B2B/B2C marketplace** for decoration and building-finish
materials (flooring/parquet, related goods) plus installation services. Producers/suppliers
sell to stores ("همکار") at tiered wholesale prices while consumers buy at consumer prices;
a **field-sales network (بازاریاب)** onboards offline stores door-to-door with QR-based
attribution, commissions and live tracking. A **points / tiered-reward / wallet** engine,
multi-gateway payments, messaging, classifieds ("دیوار"), a local-services directory
("پتوق"), tenders/auctions and full admin accounting sit around that core.

Roles (contract appendix 3): `consumer`, `store` (goods provider/همکار), `supplier`
(producer/manufacturer), `marketer` (field agent), `admin`. A user may hold a **secondary
role** where admin enables it (appendix 7, clause 6): implement as many-to-many roles with a
policy layer, not a single `role` column.

## 2. Stack (pinned)

| Layer | Choice | Notes |
|---|---|---|
| Runtime | PHP 8.4 (fpm + cli) | container-first; host has no PHP |
| Framework | Laravel 13.x (>=13.44) | `composer.json` pins exact `laravel/framework` |
| Database | MySQL 8.4 | local = CI = staging version; no SQLite for feature/integration tests |
| Cache/Queue | `database` driver by default; Redis optional | ADR-0006 explains why Redis is not required for v1 |
| Admin/UI | Blade + Livewire 4 + Alpine.js + Tailwind CSS 4, Vite | server-rendered, no SPA build; RTL-first |
| API | `routes/api.php` + Laravel Sanctum (token) | same application services as web; client-agnostic |
| AuthN (web) | Laravel session guards | separate `admin` guard + 2FA for admin |
| Tests | Pest (Laravel default) + MySQL | `php artisan test` must run against MySQL |
| Static analysis | Larastan/PHPStan level 6 + Pint | both gates run in CI |
| Containers | Docker Compose (app, nginx, mysql, mailpit) | single `compose.yaml` at repo root |
| CI | GitHub Actions | PHP matrix-free, MySQL 8.4 service container |

Version numbers are verified on 2026-11-25 from Packagist (`laravel/framework` 13.44.1,
`livewire/livewire` 4.4.10) and MUST be re-verified, not assumed, before changing.

## 3. Canonical bounded contexts (modules)

Path pattern: `app/Modules/<Module>/` with `Domain/`, `Application/`, `Infrastructure/`,
`Http/` sub-folders, and the module's own `Providers/`, `routes/`, `database/migrations/`.
Table prefix per module is listed; **never** write business logic in another module's tables
— call its application service or listen to its domain event.

| # | Module | Path | Table prefix | Requirement area |
|---|---|---|---|---|
| 1 | Identity | `app/Modules/Identity` | `iam_` | IAM |
| 2 | Users | `app/Modules/Users` | `usr_` | USR |
| 3 | Merchants | `app/Modules/Merchants` | `mer_` | MER |
| 4 | Catalog | `app/Modules/Catalog` | `cat_` | CAT |
| 5 | Pricing | `app/Modules/Pricing` | `pri_` | PRI |
| 6 | Inventory | `app/Modules/Inventory` | `stk_` | STK |
| 7 | Cart | `app/Modules/Cart` | `crt_` | CRT |
| 8 | Orders | `app/Modules/Orders` | `ord_` | ORD |
| 9 | Invoices | `app/Modules/Invoices` | `inv_` | INV |
| 10 | Payments | `app/Modules/Payments` | `pay_` | PAY |
| 11 | Finance | `app/Modules/Finance` | `fin_` | FIN |
| 12 | Marketing | `app/Modules/Marketing` | `mkt_` | MKT |
| 13 | Compensation | `app/Modules/Compensation` | `cmp_` | CMP |
| 14 | Loyalty | `app/Modules/Loyalty` | `loy_` | LOY |
| 15 | Promotions | `app/Modules/Promotions` | `prm_` | PRM |
| 16 | Shipping | `app/Modules/Shipping` | `shp_` | SHP |
| 17 | Returns | `app/Modules/Returns` | `rtn_` | RTN |
| 18 | Tenders | `app/Modules/Tenders` | `tnd_` | TND |
| 19 | Classifieds | `app/Modules/Classifieds` | `cls_` | CLS |
| 20 | Patogh | `app/Modules/Patogh` | `ptg_` | PTG |
| 21 | Samples | `app/Modules/Samples` | `smp_` | SMP |
| 22 | Messaging | `app/Modules/Messaging` | `msg_` | MSG |
| 23 | Notifications | `app/Modules/Notifications` | `ntf_` | NTF |
| 24 | Cms | `app/Modules/Cms` | `cms_` | CMS |
| 25 | Reviews | `app/Modules/Reviews` | `rvw_` | RVW |
| 26 | FieldOps | `app/Modules/FieldOps` | `geo_` | GEO |
| 27 | Admin | `app/Modules/Admin` | `adm_` | ADM |
| 28 | Reporting | `app/Modules/Reporting` | (read-only) | RPT |
| — | Shared kernel | `app/Shared/` | `shr_` | CTR |

`app/Shared/` holds: money value object, Jalali date helpers, audit traits, base policies,
event bus contracts, pagination/error envelope, file-storage abstraction. **No business
rules** in Shared.

## 4. Requirement IDs

Format: `REQ-<AREA>-<NNN>` (three digits, stable, never renumbered). AREA ∈ the 27 above plus
`CTR` for contract-level/legal requirements. Example: `REQ-LOY-004`.

Every requirement record has: id, title (English), **verbatim Persian source quote**, source
pointer (`page 6 / پیوست ۴ / بند ۳`), actors, preconditions, expected outcome, business
rules, priority (P0/P1/P2), dependencies, testable acceptance criteria, source label, status
(`Specified | Ambiguous | Conflicting | Deferred | Implemented`).

Source labels: `CONTRACT`, `OWNER_DECISION`, `AGENT_DECISION`, `TECHNICAL_CONSTRAINT`.

Ambiguity IDs: `AMB-<AREA>-<NNN>`; architectural decision IDs: `ADR-<NNNN>-<slug>`;
roadmap/deferred items: `RB-<AREA>-<NN>`.

## 5. Baseline engineering decisions (must be reflected in ADRs)

- **Modular monolith**, no microservices in v1.
- **Money**: integer **Toman** in `BIGINT` columns named `*_amount` (contract prices are in
  Toman). Never float. All arithmetic through `App\Shared\Money`.
- **Ledger**: append-only `fin_ledger_entries`; balances are derived; corrections are
  reversal entries; every financial command is idempotent (unique `reference`).
- **Orders**: parent `ord_orders` + per-seller `ord_sub_orders`; explicit state machine
  documented in `docs/data/order-state-machine.md`.
- **Prices**: server-side resolution only (base → role/tier price list → volume → promotion →
  coupon → loyalty); order lines snapshot price and applied rules.
- **Points/rewards**: rule engine tables owned by Loyalty, versioned (`loy_rule_sets` with
  `effective_from`), reproducible via a replay command in tests.
- **Payments**: `Payments\Contracts\GatewayDriver` adapter; one active gateway per payment in
  v1; 12 gateways = 12 drivers + admin activation; split payment deferred (`RB-PAY-01`).
- **Files**: `Storage` abstraction, private disk for identity documents, signed URLs.
- **Time**: store UTC; display `Asia/Tehran`; Jalali formatting at the presentation layer.
- **Audit**: `adm_audit_logs` via a shared trait for sensitive events (login, price change,
  role change, settlement, identity data change).
- **Authorization**: policies + scopes on the server; hiding a button is never authorization.
  Marketer scope = own network/leads; store scope = own catalog/orders.
- **Money movement** (owner of funds, settlement, commission) is a **contract ambiguity**;
  implement the most conservative model (platform-collected funds, ledger, period
  settlement) and record it as `AMB-FIN-001`.

## 6. Documentation set and ownership

```
docs/
├── README.md                     # Orchestrator
├── canonical-context.md          # Orchestrator (this file)
├── contract/                     # Agent A
│   ├── contract-source.md
│   ├── contract-summary.md
│   ├── scope-boundaries.md
│   └── contract-requirements-matrix.md
├── requirements/                 # Agent A (functional), Agent F (NFR, acceptance), Agent E (permissions)
├── product/                      # Agent B
├── architecture/  adr/           # Agent C
├── data/  api/                   # Agent D
├── UX/                           # Agent E
├── testing/ delivery/ operations/ security/   # Agent F
└── ambiguities/                  # Agent A (register), all agents may append via Orchestrator
```

Rules for every document:

- Start with a short "Purpose / Audience / Status" header and a `Source:` line.
- Cite the contract by page + appendix + clause for every requirement-level statement.
- **No placeholders.** If a fact is unknown, write it as an explicit ambiguity reference.
- English prose for engineering docs; Persian verbatim quotes in blockquotes where the exact
  wording matters (legal traceability). UI copy is Persian.
- Cross-link with relative paths; every path you mention must exist when you finish.

## 7. Repository conventions

- Branches: `feat/<domain>/<task>`, `fix/...`, `docs/...`, `chore/...`; `main` is protected;
  all changes via PR; squash merge; delete head branch after merge.
- Commits: Conventional Commits, one logical change.
- Code: `declare(strict_types=1);` in PHP files; PSR-12 via Pint; no `env()` outside config;
  no business logic in controllers; validation in FormRequests; authorization in policies.
- Tests: feature tests hit real MySQL; every money/points/settlement rule needs a test;
  negative authorization tests are mandatory for every scoped resource.
- CI (`.github/workflows/ci.yml`): Pint check, PHPStan, `php artisan test` on MySQL service,
  frontend `npm ci && npm run build`, secret scan.
