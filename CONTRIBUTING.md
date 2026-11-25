# Contributing

## Prerequisites

- Docker Desktop (with Compose v2). **No local PHP, Composer or MySQL is required** — every
  PHP command runs inside the `app` container.
- Node is only needed if you prefer running Vite on the host; the container can build assets.

## First run

```bash
cp .env.example .env
docker compose up -d --build
docker compose exec app php artisan key:generate
docker compose exec app php artisan migrate --seed
docker compose exec app php artisan storage:link
open http://localhost:8080
```

## Daily loop

```bash
docker compose up -d                 # start services
docker compose exec app php artisan test
docker compose exec app ./vendor/bin/pint
docker compose exec app ./vendor/bin/phpstan analyse
bash scripts/check.sh               # everything CI runs, in CI order
```

## Branches and commits

- `feat/<domain>/<task>`, `fix/<domain>/<task>`, `docs/<area>/<task>`, `chore/<task>`
- Conventional Commits (`feat(catalog): add dynamic attribute filters`)
- One logical change per PR; fill the PR template, including requirement IDs.

## Adding a module

1. Read `docs/canonical-context.md` for the module's path and table prefix.
2. Create `app/Modules/<Module>` with `Domain`, `Application`, `Infrastructure`, `Http`,
   `Providers`, `routes`, `database/migrations` and `tests`.
3. Register the module provider in `bootstrap/providers.php`.
4. Add the module to `docs/architecture/domain-boundaries.md` (owned tables, services,
   events).
5. Write the first feature test before the first controller.

## Definition of done

See `docs/delivery/definition-of-done.md`. In short: requirement IDs, tests green on MySQL,
authorization negative tests, updated docs, CI green, independent review, merged branch.
