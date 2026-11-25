# Progress Log — Dekolife Web Platform

**Purpose:** The single living status record for the mission: current phase, requirement counts, open PRs, CI state, decisions and blockers. It is updated **after every cycle** (at minimum weekly, and at every phase gate) and is the source for the contractual weekly progress report (`ماده ۳`; `ماده ۴` بند ۱).
**Audience:** Orchestrator, all agents, reviewer, delivery lead.
**Status:** Live document — last updated **2026-11-25** (Phase 0, cycle 0).
**Source:** Phase plan and durations from `phases-and-milestones.md`; requirement baseline enumerated from contract appendices 1–13 and articles 1–16 (`docs/contract/contract-extracted-text.txt`).

> **Update discipline.** Every cycle appends a row to §7, refreshes §2–§5 in place, and never rewrites history: superseded numbers are struck through or moved into §7 with a reason. A cycle with no code merged but with review activity is still a cycle and must be logged.

---

## 1. Current state

| Field | Value |
|---|---|
| Current phase | **Phase 0 complete — documentation baseline published; Phase 1 foundation landed** |
| Phase start | 2026-11-25 |
| Phase elapsed | 1 of 4 planned working days |
| Overall status | **On plan for the phase; 17 calendar days over the contract window at completion (see §6)** |
| Next gate | M0 kickoff & baseline (planned day 7) — docs ready for review |
| Contract window | 90 calendar days from contract date + receipt of complete content (`ماده ۳`) |
| Projected completion | 107 calendar days after kickoff → **+17 days vs contract** |
| Extension request (M1 mitigation) | **not yet filed** — due by day 14, owner: delivery lead |
| Active engineering streams | 0 of 6 functional streams (platform + documentation only) |

---

## 2. Requirement counts

Baseline enumerated at Phase 0: **203 numbered clauses across appendices 1–13** (`پیوست ۱` 10, `پیوست ۲` 12, `پیوست ۳` 28 across the five role groups, `پیوست ۴` 24, `پیوست ۵` 44, `پیوست ۶` 12, `پیوست ۷` 10, `پیوست ۸` 8, `پیوست ۹` 6, `پیوست ۱۰` 6, `پیوست ۱۱` 20, `پیوست ۱۲` 16, `پیوست ۱۳` 7) plus **24 contract-level (CTR) obligations** from the 16 articles = **227 baseline items**.

| Status | Count | Notes |
|---|---|---|
| Recorded in the requirement register | 390 | `docs/requirements/functional-requirements.md` — 390 unique `REQ-*` ids across 29 areas, every one traced to a clause in `docs/contract/contract-requirements-matrix.md` |
| `Implemented` | 0 | no functional code yet |
| `Specified` (clean, no ambiguity) | 0 | classification starts with the register |
| `Ambiguous` (`AMB-*` open) | 0 | three are known in advance, see §6 B-02 |
| `Deferred` (`RB-*` with annex) | 1 | `RB-MOB-01` native Android/iOS apps, out of scope per `docs/canonical-context.md` §0 |
| **Remaining to implement** | **227** | all areas |

**By phase allocation** (target counts, reconciled when the register publishes):

| Phase | Areas | Baseline items allocated | Implemented | Remaining |
|---|---|---|---|---|
| 0 | CTR | 24 | 0 | 24 |
| 1 | IAM, USR | 34 | 0 | 34 |
| 2 | MER, CAT, PRI, STK | 52 | 0 | 52 |
| 3 | CRT, ORD, SHP, RTN | 32 | 0 | 32 |
| 4 | INV, PAY, FIN | 26 | 0 | 26 |
| 5 | MKT, GEO, CMP | 22 | 0 | 22 |
| 6 | LOY, PRM | 20 | 0 | 20 |
| 7 | MSG, NTF, CMS, RVW, SMP | 21 | 0 | 21 |
| 8 | TND, CLS, PTG | 12 | 0 | 12 |
| 9 | ADM, RPT, CTR | 14 | 0 | 14 |
| 10 | CTR | 4 | 0 | 4 |
| | **Total** | **261** | **0** | **261** |

The per-phase column sums to 261 — 34 more than the 227 clause count — because some obligations must be verified in more than one phase. 18 of the 24 CTR obligations recur (Phase 0 baselines them, Phase 9 hardens them operationally, Phase 10 collects the acceptance evidence), and 16 appendix clauses land in two areas at once (for example `پیوست ۷` بند ۲, which is both invoicing and finance reporting). **227 is the clause count; 261 is the work-item count.** Both are reported so that a later re-baseline is never mistaken for scope growth.

---

## 3. Open pull requests

Empty at Phase 0 start. Every merged PR is appended to §7 with its tag.

| PR | Title | Track | REQ ids | Reviewer | CI | State |
|---|---|---|---|---|---|---|
| — | *(none open)* | — | — | — | — | — |

---

## 4. CI status

| Workflow | Trigger | Last run | Result |
|---|---|---|---|
| `ci.yml` — Pint, PHPStan L6, `php artisan test` (MySQL 8.4), `composer audit`, `npm ci && npm run build`, gitleaks | push + PR to `main` | `610df85` (run 45633663465) | **green**: Pint 31 files, PHPStan no errors, Pest **14 passed (26 assertions)** on MySQL 8.4, frontend build ok, secret scan ok |

Two failures were found and fixed before the first green run, both worth remembering:

1. **The tests job failed because the gate ran on the bare runner** while `phpunit.xml` pinned `DB_HOST=mysql`. CI now runs every gate inside the built image with `compose.ci.yaml`, so the hostname, MySQL version and PHP extensions are the ones that ship.
2. **PHPUnit `<env>` entries do not reach Laravel's Dotenv repository** in this stack — only `<server>` does. With `<env>`, a developer `.env` won the `SESSION_DRIVER=database` setting and every HTTP test failed on a missing `sessions` table while the suite looked green in isolation.

The secret scan also flagged two documentation examples as `generic-api-key` (ULID idempotency keys); `.gitleaks.toml` allowlists that ULID shape only — no path-level exclusion, so a real credential in `docs/` still fails the build.

Phase 0 exit criterion: first green run on `main` for the skeleton. Until then no PR can satisfy DoD point 5.

---

## 5. Decisions

| ID | Decision | Rationale | Date | Affects |
|---|---|---|---|---|
| D-001 | Eleven phases (0–10), phases are hard gates | each phase maps to appendix headings and to a client-visible demo, which is how acceptance actually happens | 2026-11-25 | all |
| D-002 | Six parallel streams with file-ownership boundaries | the dependency chain resists compression; ownership boundaries are the only way to avoid merge collisions | 2026-11-25 | all |
| D-003 | Schedule gap handled by M1 (written extension) + M2 (overlap), not by silent scope cuts | `ماده ۳` allows extension only in writing; silent cuts trigger 5 % per heading (`ماده ۴` بند ۵) | 2026-11-25 | delivery |
| D-004 | v1 has one active gateway per payment; 12 drivers shipped behind admin activation | matches `docs/canonical-context.md` §5; split payment deferred as `RB-PAY-01` and raised as `AMB-PAY-001` for a written client decision | 2026-11-25 | Phase 4 |
| D-005 | `v0.8.0` is skipped; Phase 8 ships as `v0.7.1` | avoids implying a feature set that does not exist | 2026-11-25 | release |
| D-006 | Requirement baseline published as 227 clauses / 261 work items before any code | `ماده ۴` بند ۱ forbids reduction from the appendices; you cannot prove completeness without a count | 2026-11-25 | all |

---

## 6. Blockers and risks

| ID | Blocker / risk | Impact | Owner | Status | Next action |
|---|---|---|---|---|---|
| B-01 | Client server, domain, SSL and gateway credentials not yet provided | Phase 10 cannot run acceptance; staging preview impossible | delivery lead | open | request in the day-1 kickoff note, citing `ماده ۵` بند ۳ |
| B-02 | Three known ambiguities not yet ruled on: `AMB-PRI-001` (coefficient stacking), `AMB-LOY-001` (first-invoice discount vs loyalty reward), `AMB-FIN-001` (owner of funds / settlement model) | highest-cost rework if discovered late | architect | open | implement conservative reading, file written decision requests in week 1 |
| B-03 | `پیوست ۱` بند ۵ requires 12 gateways and two concurrent in one transaction; v1 plan is one active gateway | 5 % heading penalty (4,500,000 Toman) if unexecuted | architect | open | `AMB-PAY-001` written decision request by end of Phase 2 |
| B-04 | 17-day schedule gap vs the 90-day window | 8.5 % penalty (7,650,000) if unmitigated; termination right at 21 days (`ماده ۹`) | delivery lead | open | file extension request by day 14 |
| B-05 | Host has no PHP; all tooling must be container-first | onboarding friction for new agents | platform | mitigated | one-command wrapper documented in `docs/operations/local-development.md` |

---

## 7. Cycle log

### Cycle 0 — 2026-11-25 (Phase 0, day 0)

- **Completed:**
  - Phase plan with 11 phases, explicit working-day estimates, contract-fact table and gap analysis (`phases-and-milestones.md`).
  - Module dependency graph, build waves, shared contract freeze points, track ownership (`dependency-map.md`).
  - DoR (13 blocking items) and 12-point DoD plus product-level acceptance gates.
  - Release map `v0.1.0` → `v1.0.0`, branch/tag/migration/promotion/handover policy (`release-plan.md`).
  - Operations baseline: local development, backup and restore, monitoring and logging, 11 runbooks.
  - Requirement baseline: 227 clauses / 261 work items; 1 deferral (`RB-MOB-01`).
- **Merged PRs:** none (documentation baseline is the Phase 0 deliverable set; it enters review at M0).
- **CI:** not yet created.
- **Burn-down:** 0 of 334 person-days complete; 334 remaining. Elapsed 0 of 76 working days.
- **Variance:** none (day 0).
- **Escalations:** B-01, B-02, B-03, B-04 opened; extension request drafted but not filed.
- **Next cycle:** scaffold repo (`compose.yaml`, CI, module autoloading), publish ADR-0001…0007, publish the requirement register, first green CI run.

### Cycle 1 — 2026-11-25 (Phase 0 close-out, day 1)

- **Completed:**
  - Repository `puriatc10/dekolife-platform` (private) with `main`, PR template, CODEOWNERS, `SECURITY.md`, `CONTRIBUTING.md`, `docs/engineering-rules.md`.
  - Laravel 13.17 / PHP 8.4 skeleton installed container-first (the host has no PHP); Livewire 4.4, Sanctum 4.5, Larastan 3.15, Pest 4.7, Jalali 3.5 pinned in `composer.lock`.
  - Docker stack: `compose.yaml` (app, queue, nginx, mysql 8.4, mailpit), multi-stage `docker/php/Dockerfile`, nginx vhost; Docker Official Images resolved through the public ECR mirror because Docker Hub is blocked on the development host.
  - CI in three jobs, all green on `610df85` (see §4): backend gates **inside the built image**, frontend build, secret scan.
  - Shared kernel: `App\Shared\Money` (integer Toman, half-up percentages, no floats) with 10 unit tests; `/health` endpoint with a database probe and 2 feature tests. **14 tests / 26 assertions** green locally and in CI.
  - Documentation baseline published: 70 documents, ~30 000 lines — contract summary and source provenance, **requirement register with 390 `REQ-*` ids across 29 areas**, clause-level traceability matrix, ambiguity register (**67 topics**: 48 decided, 16 escalated, 3 open), 8 ADRs, architecture, data model/ERD/data dictionary (1 927 lines), API inventory, UX sitemap and 120+ screen inventory, security (threat model, PII, checklist), testing and delivery/operations runbooks. Zero broken internal links (checked programmatically).
- **Merged PRs:** the documentation baseline and the platform foundation entered `main` as two commits while branch protection was still being configured; the first PR through the full flow is #1 (`docs/phase-0-baseline-status`).
- **CI:** green on `610df85` — Pint 31 files, PHPStan L6 no errors, Pest 14 passed, frontend build ok, gitleaks ok.
- **Burn-down:** 0 of 334 person-days of functional work complete (documentation and platform do not burn the functional budget); elapsed 1 of 76 working days.
- **Variance:** none against the Phase 0 plan. The contract-window gap remains +17 days (`B-04`).
- **Escalations:** unchanged (B-01…B-04 open). New: `AMB-CTR-007` — the PDF is marked «پیشنویس نسخه ۱» and pages 1–4 carry **two stacked revisions** (payment plan and support duration each have two readings); the owner must confirm which revision is signed before any commercial claim is repeated.
- **Next cycle:** Phase 1 — module skeleton and autoloading for the six Phase-1 streams, identity/RBAC with negative authorization tests, and the first PR reviewed by an independent reviewer.

---

## 8. Reporting template for the next cycle

Copy this block at the top of §7, fill it, and update §1–§6 in place.

```
### Cycle N — YYYY-MM-DD (Phase P, day D)

- Completed (with REQ ids):
- Merged PRs (number, title, tag):
- CI: last green run on main @ <sha>
- Requirement counts: implemented X / remaining Y (of 261 work items)
- Burn-down: effort done E / 334 pd; elapsed W / 76 wd
- Variance vs plan: +/- N wd (reason)
- Blockers opened/closed:
- Client-facing evidence for the weekly report: <screenshots / demo link>
- Next cycle:
```
