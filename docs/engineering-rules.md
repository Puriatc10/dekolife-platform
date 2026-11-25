# Engineering rules — Dekolife platform

This is the short rule sheet every contributor (human or agent) reads before touching the
code. The long form lives in `docs/`; `docs/canonical-context.md` pins names, paths, IDs and
stack versions.

## Non-negotiables

- Stack is **Laravel 13 / PHP 8.4 / MySQL 8.4** (contract appendix 1, clause 1). No Django,
  no PostgreSQL, no second framework.
- **Web only** in this mission. Native apps, APK and store publishing are deferred
  (`RB-MOB-01`). PWA is a web feature, never called a native app.
- Requirements come from `input/contract/` and `docs/requirements/functional-requirements.md`.
  Never invent a requirement; never silently drop one — defer it with an ID and a reason.
- No secrets, real credentials, bank data or customer PII in git. `.env` stays untracked.
- `main` is protected: branch → PR → independent review → squash merge → delete branch.

## Layering

- `app/Modules/<Module>/{Domain,Application,Infrastructure,Http}` — business rules live in
  `Domain`/`Application`; framework code sits at the edges.
- Thin controllers: validation in FormRequests, authorization in Policies, execution in
  application services.
- A module never writes another module's tables. Call its service or listen to its events.
- `app/Shared/` holds only cross-cutting primitives (money, Jalali dates, audit trait, base
  policies). No business rules.
- Money is an integer number of **Toman** (`BIGINT`) handled through `App\Shared\Money`.
  Never float; never a currency string inside a calculation.
- Financial state is append-only: `fin_ledger_entries` plus reversal entries, balances
  derived. Never update a posted entry.

## Security

- Authorization is enforced server-side in policies and scopes; hiding a button is not
  authorization. Every scoped resource needs a negative test proving a foreign record 403s.
- Marketer scope = own network; store scope = own catalog/orders; supplier scope = own
  invoices.
- Admin panel under `/admin`, separate guard, 2FA required.
- Validate uploads (MIME, size, extension) and store them outside the public web root.
- Rate-limit login, OTP, search and every cost-bearing endpoint.
- Sensitive events go through the audit trait; mask PII; never log tokens or provider bodies.

## Testing

- `php artisan test` against real MySQL. SQLite is not acceptable for feature tests.
- `scripts/check.sh` runs exactly the gates CI runs; run it before pushing.
- Every money/points/commission/settlement rule needs a deterministic test; every order
  state transition needs a refusal test.
- A green run with zero tests is a failure, not a pass.

## Workflow

1. `git switch main && git pull`, then `git switch -c feat/<domain>/<task>`.
2. Code + tests + docs; run `scripts/check.sh`.
3. Conventional Commits; open a PR with requirement IDs in the template.
4. Independent reviewer approves, CI is green, squash-merge, delete the branch.
5. Update `docs/delivery/progress.md`.

## Out of scope here

- Native Android/iOS clients, APK, store publishing (`RB-MOB-01`).
- Appointment/reservation module (separate contract no. 2).
- Split payment across two gateways in one transaction (`RB-PAY-01`).
- Real gateway credentials or live bank settlement files (adapters and fakes only).
