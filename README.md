# Dekolife Platform (سامانه دکولایف)

Multi-vendor B2B/B2C marketplace for decoration and building-finish materials, with a
tiered-pricing engine, a points/reward/wallet system and a field-sales (بازاریاب) network.

This repository is the **web application** deliverable of contract `DK-1405-01` and its
appendices (`input/contract/`). Native mobile clients are out of scope for the current
mission (see `docs/contract/scope-boundaries.md`).

**Stack (contract-mandated):** Laravel 13 · PHP 8.4 · MySQL 8.4 · Blade + Livewire 4 +
Tailwind CSS 4 (Vite), container-first, MySQL-in-CI.

## Status

| Area | State |
| --- | --- |
| Phase 0 — contract analysis, requirements, architecture, ADRs, data model, UX, ambiguities | in progress (docs land here) |
| Phase 1 — repo, Docker, CI, health endpoint, shared kernel | foundation in place |
| Phase 2+ — identity, catalog, commerce, finance, marketing, loyalty, extensions | not started |

Progress is tracked in `docs/delivery/progress.md`. Nothing outside the "foundation"
column is implemented yet; do not read this table as a feature list.

## Quick start (no local PHP required)

```bash
cp .env.example .env
bash scripts/dev-up.sh          # builds images, starts MySQL, migrates, prints URLs
# storefront  http://localhost:8080
# mail catcher http://localhost:8025
```

Everyday commands:

```bash
docker compose exec app php artisan test
bash scripts/check.sh            # the exact gates CI runs, in CI order
docker compose down
```

Full local guide: `docs/operations/local-development.md`.

## Repository layout

```
app/
  Modules/<Module>/        # 28 bounded contexts (see docs/canonical-context.md)
  Shared/                  # Money, Jalali dates, audit trait — no business rules
docker/                    # php-fpm image, nginx vhost
docs/                      # the documentation baseline (start at docs/README.md)
input/contract/            # the signed contract PDF (private repository only)
scripts/                   # dev-up, check (lint + static analysis + tests + build)
tests/                     # Pest tests; feature tests require MySQL
```

## Documentation

- `docs/README.md` — index of every document
- `docs/canonical-context.md` — pinned names, paths, IDs, stack versions (read first)
- `docs/requirements/functional-requirements.md` — the requirement catalogue
- `docs/contract/contract-requirements-matrix.md` — contract clause → requirement traceability
- `docs/ambiguities/ambiguity-register.md` — every open question and the decision taken
- `docs/architecture/overview.md` — architecture and module boundaries
- `docs/engineering-rules.md` — the rules every contributor follows

## Contributing

`CONTRIBUTING.md`, `docs/engineering-rules.md`, and the PR template. `main` is protected:
branch → PR → independent review → squash merge.

## Security

`SECURITY.md`. Report vulnerabilities privately; never commit secrets, bank data or PII.

## License

Proprietary. All material and intellectual property transfers to the client on full
settlement (contract `ماده ۷`). No open-source license is granted by this repository.
