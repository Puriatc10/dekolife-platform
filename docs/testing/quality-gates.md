# Quality Gates — Dekolife Platform

**Purpose.** Make "the code is good enough to merge" a machine decision, not an opinion. Every gate below
is a command with a pass condition, a runtime budget and a named blocker.
**Audience.** Engineering, reviewers, the Orchestrator, CI maintainers.
**Status.** Baseline v1 — enforced by branch protection once the repository has code.

**Source:** `../canonical-context.md` §7 (CI definition, branch rules, test rules); `./test-strategy.md`
(layers, coverage floors, flaky policy); `../requirements/non-functional-requirements.md` (NFR-MAINT,
NFR-SEC, NFR-PERF); contract article 4 clause 3 and appendix 1 clauses 2 and 10.

---

## 1. Gate inventory

| Gate | Name | Command | Pass condition | Budget | Blocks |
|---|---|---|---|---|---|
| G1 | Formatting | `vendor/bin/pint --test` | exit 0, 0 files would change | 20 s | merge |
| G2 | Static analysis | `vendor/bin/phpstan analyse -c phpstan.neon --memory-limit=1G --no-progress` | exit 0, **level 6**, 0 errors, baseline not extended | 90 s | merge |
| G3 | Test suite on MySQL | `php artisan test --parallel --processes=4` | exit 0, 0 failures, 0 errors, 0 skipped in P0 modules | 8 min | merge |
| G4 | Coverage floors | `php artisan test --coverage --min=75` + per-module script | overall ≥ 75 % line / 65 % branch; every module ≥ its floor (`./test-strategy.md` §9) | +3 min | merge |
| G5 | Exploit suite | `php artisan test --group=security` | exit 0, 0 failures | 3 min | merge |
| G6 | Migration check | `php artisan migrate:fresh --seed` + rollback probe | fresh schema builds ≤ 60 s; each new migration rolls back cleanly; no destructive change on a populated table | 2 min | merge |
| G7 | Frontend build | `npm ci && npm run build` | exit 0, 0 errors, bundle budgets met | 3 min | merge |
| G8 | Secret scan | `gitleaks detect --config=.gitleaks.toml --redact` | 0 findings | 15 s | merge |
| G9 | Dependency audit | `composer audit --no-dev` + `npm audit --omit=dev` | 0 high, 0 critical | 40 s | merge |
| G10 | Architecture | `php artisan test --group=architecture` | 0 cross-module model imports, 0 `env()` outside `config/`, 0 raw SQL string concatenation | 40 s | merge |
| G11 | Browser journeys | `php artisan test --group=browser` | 8 journeys green, flake count 0 | 12 min | release |
| G12 | Performance | `k6 run tests/load/<script>.js` on staging | every NFR-PERF threshold met at L-A | 45 min | release |
| G13 | Security scan | OWASP ZAP baseline (auth + anon) | 0 high, 0 critical | 20 min | release |
| G14 | Backup drill | `AC-24` runbook | restore ≤ RTO, row counts match | 4 h | go-live, then quarterly |

G1–G10 run on **every pull request**, in parallel where possible, total wall clock ≤ 12 min. G11–G13 run
on `main` nightly and on every release candidate. G14 is operational and scheduled.

---

## 2. Gate detail

### G1 — Pint (formatting)

```bash
vendor/bin/pint --test
```

- Pass: exit code 0. A single file that *would* change fails the gate.
- Developer fixes with `vendor/bin/pint` before pushing; CI never auto-commits formatting.
- Preset: Laravel, with one documented override — `declare(strict_types=1);` is enforced by a custom
  fixer (`strict_param` + a project ruleset file `.pintfixer.php`), because canonical §7 requires it in
  every PHP file and Pint does not do it by default.
- Blade files are formatted by the same run (Pint handles `.blade.php` since v1.x via the Laravel preset);
  a Blade file that fails is fixed, not excluded.

### G2 — PHPStan level 6 (Larastan)

```bash
vendor/bin/phpstan analyse -c phpstan.neon --memory-limit=1G --no-progress
```

- **Level 6 is the floor, not a target.** Level 7+ per module is welcome; lowering is a reviewed decision.
- `phpstan-baseline.neon` exists and is **frozen**: a PR may remove entries, never add them. CI fails if
  the baseline line count grows (`scripts/check-baseline.sh` compares against `main`).
- Custom rules shipped in `phpstan.neon`:
  - `NoEnvOutsideConfigRule` — `env()` is only allowed under `config/`.
  - `NoRawSqlConcatenationRule` — `DB::raw()` and `whereRaw()` must use bindings.
  - `NoFloatMoneyRule` — no `float`/`double` cast on a property or column ending in `_amount`.
  - `ModuleBoundaryRule` — a class in `app/Modules/A` may not reference a model or repository class of
    `app/Modules/B`; only `Contracts`, `Application` services and events are allowed.
  - `PolicyRequiredRule` — every controller action on a route with a `{model}` binding must call
    `authorize()` or a policy-backed FormRequest.
- Analysis includes `app/`, `routes/`, `database/`, `tests/` (tests at level 5 to keep noise down; the
  exclusion is written in `phpstan.neon`, not implied).

### G3 — Test suite on MySQL

```bash
php artisan test --parallel --processes=4
```

- **MySQL 8.4 only.** A guard test fails the run if the driver is sqlite
  (`./test-strategy.md` §4.1). Local `sqlite` runs are not accepted as evidence.
- Pass: 0 failures, 0 errors. **Skipped tests are failures** in P0 modules; a `markTestSkipped()` in
  `app/Modules/{Payments,Finance,Loyalty,Orders,Pricing,Marketing,Compensation}/**` blocks merge.
- No retries. A flaky test that fails twice is quarantined per `./test-strategy.md` §10, and its
  quarantine issue is linked in the PR.
- Timeout: the job fails at 12 min; the suite must stay under 8 min at 4 workers. A PR that pushes the
  suite over budget must split or speed up tests, not raise the timeout.

### G4 — Coverage floors

```bash
php artisan test --coverage --min=75
php scripts/coverage-by-module.php --report=coverage/clover.xml --config=coverage-floors.json
```

- Overall floors: **75 % line, 65 % branch**.
- Per-module floors from `./test-strategy.md` §9 live in `coverage-floors.json`; the script exits non-zero
  if any module is below its floor, and prints the delta versus `main`.
- A PR that adds code without tests lowers the ratio and fails; the fix is tests, not lowering the floor.
  Lowering a floor requires an ADR and the Orchestrator's approval.

### G5 — Exploit suite

```bash
php artisan test --group=security
```

- Contains every `TM-<MOD>-A*`, the upload/CSRF/XSS/SQLi/SSRF cases, the route-coverage manifest test and
  the IDOR enumeration tests.
- `RouteCoverageTest` builds the manifest from `php artisan route:list --json`, subtracts an explicit
  allow-list (health, login, public catalog), and fails if any authenticated route lacks a negative test.
  **This is the gate that makes "hiding a button is not authorization" mechanical.**
- A new route without a negative test fails the build, not the review.

### G6 — Migration check

```bash
php artisan migrate:fresh --seed --env=testing   # must build an empty schema in ≤ 60 s
php artisan migrate:status
# for each migration added in the PR:
php artisan migrate:rollback --step=1 --env=testing && php artisan migrate --env=testing
```

Checks enforced by `scripts/check-migrations.php`:

| Check | Rule |
|---|---|
| Naming | `YYYY_MM_DD_HHMMSS_<verb>_<table>.php`, snake_case |
| Forward-only safety | no `dropColumn`, `renameColumn`, `change()` on a table listed in `protected-tables.json` without an accompanying backfill migration |
| Destructive change on populated table | blocked unless the PR description carries a `data-migration:` line naming the backfill |
| Charset | every table `utf8mb4` / `utf8mb4_0900_ai_ci` (Persian text) |
| Money columns | `*_amount` ⇒ `bigint unsigned` (or `bigint` where negative is legal) — float and decimal rejected |
| Foreign keys | every FK has an index and an explicit `onDelete` policy |
| Module prefix | table name starts with the module prefix from `../canonical-context.md` §3 |
| Timestamps | `created_at`/`updated_at` nullable=false with a default, no `timestamp` without timezone semantics (store UTC) |

### G7 — Frontend build

```bash
npm ci
npm run build
node scripts/check-bundle-size.js
```

- Pass: Vite build succeeds with 0 errors and 0 unresolved imports.
- Bundle budgets (gzip): consumer JS ≤ 300 KB, CSS ≤ 120 KB, admin JS ≤ 450 KB. Exceeding a budget fails
  the gate; the fix is code-splitting, not raising the budget without an ADR.
- Tailwind 4 must produce a CSS file with no `@apply` on undefined classes (build error), and the RTL
  plugin output must be present (`dir="rtl"` utilities generated).
- `npm run build` also runs the manifest check: every Blade `@vite` entry exists in the build output.

### G8 — Secret scan

```bash
gitleaks detect --config=.gitleaks.toml --redact
```

- 0 findings. The scan covers the full history on release branches and the working diff on PRs.
- `.gitleaks.toml` extends the default ruleset with:
  - Iranian gateway merchant IDs and terminal IDs (`^[0-9]{8,}$` near `gateway|terminal|merchant`),
  - `APP_KEY=base64:…`,
  - SMS provider tokens, `Authorization: Bearer …` in fixtures,
  - the contract's bank-account pattern (16-digit card, `IR` IBAN).
- `.env.example` is the only env file in git and contains **empty** values with a comment, never a default
  secret. A committed `.env` fails the gate regardless of content.
- Allow-list entries require a comment with the reason and an expiry date; the scanner fails on an expired
  allow-list entry, so temporary exceptions cannot become permanent.

### G9 — Dependency audit

```bash
composer audit --no-dev --format=plain
npm audit --omit=dev
```

- 0 high and 0 critical. Medium findings are recorded in the PR with a remediation date.
- Patch SLA (NFR-SEC-012): critical ≤ 72 h, high ≤ 14 days. A Dependabot/Renovate PR that only bumps a
  patch version still runs the full gate set — no fast lane that skips tests.

### G10 — Architecture

```bash
php artisan test --group=architecture
```

- 0 cross-module model/repository imports (mirrors the PHPStan rule, but as an executable spec so the
  rule itself is tested).
- 0 `env()` outside `config/`.
- 0 business logic in controllers: a controller action over 40 lines or containing a DB write fails.
- Every module has `Providers/`, `routes/`, `database/migrations/` and at least one test file — an empty
  module is a scaffold mistake, not a plan.
- No `dd(`, `dump(`, `var_dump(`, `ray(` outside tests (grep-based assertion with an allow-list for
  debugging helpers in `tests/`).

### G11 — Browser journeys

Nightly and pre-release. Pass: 8 journeys, 0 failures, flake count 0 after one permitted retry per test
(reported, not hidden). Screenshots on failure are uploaded as CI artifacts and linked in the run summary.

### G12 — Performance

`k6` scripts under `tests/load/`: `catalog.js`, `search.js`, `checkout.js`, `payment-callback.js`,
`admin-report.js`, `marketer-map.js`. Thresholds are the NFR-PERF rows, expressed in the script
(`http_req_duration: ['p(95)<800']` for catalog). A failed threshold fails the release, with the p95 and
the offending route in the summary.

### G13 — ZAP baseline

Authenticated and unauthenticated scans on staging. Pass: 0 high, 0 critical; medium findings triaged in a
written report attached to the release note. A new medium finding without a triage line blocks the release.

---

## 3. Branch protection and merge rules

| Rule | Setting |
|---|---|
| Protected branches | `main` (and `release/*` once cut) |
| Required checks | G1–G10 must be green; **stale approvals dismissed** on new commits |
| Reviews | ≥ 1 approval; ≥ 2 for anything touching `app/Modules/{Payments,Finance,Loyalty}` or `app/Shared/Money` |
| CODEOWNERS | money, ledger, loyalty rules and security middleware require the named owner's review |
| Merge strategy | squash only; the PR title becomes the commit subject and must be a Conventional Commit |
| Head branch | deleted after merge |
| Force push | blocked on protected branches |
| Linear history | required |
| Commit hygiene | one logical change per PR; a PR over 600 changed lines (excluding tests, migrations, docs) needs a written justification |

---

## 4. Pull-request checklist

The author fills this in the PR body; the reviewer verifies rather than trusts.

```markdown
### Scope
- [ ] PR does one thing; title is a Conventional Commit (`feat(orders): …`)
- [ ] Linked issue / requirement ID (`REQ-<AREA>-NNN`) and contract clause
- [ ] No unrelated refactor, no reformatting of untouched files

### Correctness
- [ ] Tests written first (red → green) or the PR states why TDD did not apply
- [ ] Money/points paths use `App\Shared\Money`; no float, no `number_format` before arithmetic
- [ ] Every new scoped route has a negative authorization test
- [ ] State transitions added to the transition table **and** tested both ways
- [ ] Any new external call is idempotent and has a replay test

### Data
- [ ] Migration follows the naming rule, has an index for every FK, `utf8mb4`
- [ ] Destructive change has a `data-migration:` line and a backfill plan
- [ ] No production data, real national ID, real mobile or real IBAN in fixtures

### Security
- [ ] Input validated in a FormRequest; output escaped; no `{!! !!}` on user content
- [ ] File uploads validated by magic bytes, stored on the right disk
- [ ] New admin action is audited and behind the admin guard + 2FA
- [ ] No secret, token or credential added (G8 will catch it, but do not rely on it)

### Operations
- [ ] New config key added to `config/` with a default and to `.env.example` (empty value)
- [ ] New queue job has retry/backoff and a `failed()` handler
- [ ] Log lines contain no PII; correlation ID propagated
- [ ] Feature flag added if the behaviour is not ready for production

### Verification
- [ ] G1–G10 green on the latest commit
- [ ] Screenshot or short recording for any UI change (RTL and 360 px included)
- [ ] Coverage did not drop below a module floor
- [ ] No new quarantine entry; existing entries unchanged
```

---

## 5. Definition of Ready (before work starts)

A ticket may not enter a sprint until all of these are true:

1. Requirement ID assigned and traceable to a contract clause.
2. Acceptance criteria written as observable outcomes (not "works correctly").
3. Actors, preconditions and the state machine edge(s) affected are named.
4. Data touched is listed, with a PII classification from `../security/privacy-and-PII.md`.
5. Non-functional impact stated: latency budget, new query, new queue job, new index.
6. Design decision recorded: reuse an existing module service, or an ADR if a new pattern is introduced.
7. Test approach named: unit / feature / browser / exploit, with the specific cases from
   `./test-matrix.md`.
8. Dependencies and blockers listed; if it depends on an undelivered contract item, the ambiguity ID is
   referenced.
9. **Open questions resolved or converted into an ambiguity ID** — a ticket with an unresolved question is
   not ready.
10. Size fits one PR (≤ 600 changed lines excluding tests/migrations/docs); otherwise split.

## 6. Definition of Done (before a ticket closes)

1. All acceptance criteria demonstrably met, with a test or a recorded manual check per criterion.
2. G1–G10 green; new tests cover the new behaviour and the negative path.
3. Coverage floors met; no quarantine added.
4. Migration (if any) applied on staging and rolled back successfully in the check.
5. Audit rows written for every new sensitive action.
6. Authorization: positive + negative tests, and the route-coverage manifest still passes.
7. Idempotency: every new command that can be retried has a replay test.
8. Observability: logs/metrics added for the new path; no PII in them.
9. Docs updated in the same PR: README/API/table dictionary as applicable, plus a changelog line.
10. Reviewer approval recorded; PR merged by squash; branch deleted.
11. For a client-visible change: a line in the release note, in Persian where the client reads it.

---

## 7. Waivers and exceptions

- A gate may be waived **only** by the Orchestrator, in writing, in the PR body, with: gate ID, reason,
  risk, compensating control, expiry date (≤ 14 days) and the follow-up ticket.
- Waivers are listed in the release note. A release with more than 2 open waivers requires an explicit
  client-facing risk note.
- Some gates are never waivable: **G5** (exploit suite), **G8** (secret scan), the P0 subset of **G3**, and
  the audit-immutability case. A security gate waiver is not a business decision the vendor can make.
- An expired waiver is a failing gate; the CI job reads `waivers.json` and fails on expiry.

---

## 8. When a gate fails

| Failure | First action | Never do |
|---|---|---|
| G1 Pint | run `vendor/bin/pint`, commit | add the file to the Pint exclude list |
| G2 PHPStan | fix the type, add a narrow `@phpstan-var` if the framework is genuinely untyped | extend the baseline |
| G3 test | reproduce locally on MySQL, read the assertion, fix the cause | retry until green, or `markTestSkipped` |
| G4 coverage | write the missing test for the untested branch | lower `--min` |
| G5 exploit | treat as a real vulnerability; if it is a genuine false positive, fix the assertion with a comment | delete the test |
| G6 migration | rewrite the migration (it has not shipped) or add a backfill migration | edit a migration that already ran in production |
| G7 build | fix the import/size | raise the budget |
| G8 secret | rotate the secret **first**, then rewrite history if it reached a branch | just delete the file in a new commit |
| G12 performance | profile the query (`EXPLAIN`), add the index, cache the block | raise the threshold |

---

## 9. Changelog

| Version | Date | Change |
|---|---|---|
| 1.0 | 2026-11-25 | 14 gates with commands and budgets, branch protection, PR checklist, DoR/DoD, waiver rules, failure playbook. |
