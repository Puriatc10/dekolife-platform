# Release Plan — Dekolife Web Platform

**Purpose:** Define how the platform is versioned, branched, tagged, promoted from staging to production, rolled back, accepted by the client and handed over.
**Audience:** Orchestrator, engineering agents, reviewer, delivery lead, and (for §7) the client's technical representative.
**Status:** Baseline v1 (Phase 0 output). The version-to-phase mapping is fixed; dates are re-forecast at every gate in `progress.md`.
**Source:** Release scope from `phases-and-milestones.md`; contract obligations from `ماده ۱` (delivery items), `ماده ۲` (payment schedule), `ماده ۳` (90-day window, weekly reports), `ماده ۴` بند ۴ (source + install docs), `ماده ۵` بند ۳ (client supplies hosting/domain/SSL/gateway), `ماده ۹` (termination), `ماده ۱۲` (warranty and support SLAs), `پیوست ۱۰` (delivery, acceptance testing, training), `پیوست ۱` بند ۲ (documented code).

---

## 1. Versioning model

Semantic-style versioning with a fixed meaning for this project:

| Segment | Meaning here |
|---|---|
| MAJOR | contract-level delivery milestone: **1.x** = accepted delivery under `ماده ۱۰`; **2.x** would be a new annex-driven scope |
| MINOR | a phase boundary from `phases-and-milestones.md`; each minor is independently deployable and demonstrable |
| PATCH | fixes and hardening inside a phase, no new requirement area |

Pre-release suffixes: `-rc.N` for a release candidate frozen for client acceptance, `-beta.N` for a demonstrable but incomplete phase (used only for the client's staging preview). Build metadata is never part of the tag.

### 1.1 Release map

| Release | Content (phase) | Requirement areas | Deployable value | Contract headings touched |
|---|---|---|---|---|
| **v0.1.0 — foundation** | Phase 0 | CTR | Buildable, tested, CI-gated skeleton; shared contracts; ADRs; docs | `پیوست ۱` بند ۱–۳ |
| **v0.2.0 — identity** | Phase 1 | IAM, USR | All five roles authenticate; secondary roles; admin 2FA; audit trail | `پیوست ۱` بند ۷, ۱۰; `پیوست ۳`; `پیوست ۷` بند ۶ |
| **v0.3.0 — catalog** | Phase 2 | MER, CAT, PRI, STK | Suppliers list products, per-role prices resolve server-side, buyers search and compare | `پیوست ۲` بند ۲–۵; `پیوست ۷` بند ۴; `پیوست ۹` بند ۴; `پیوست ۱۱` بند ۱ |
| **v0.4.0 — commerce** | Phase 3 | CRT, ORD, SHP, RTN | Multi-seller cart, checkout, order state machine, shipping, returns, stall holds | `پیوست ۲` بند ۶, ۱۱; `پیوست ۱۱` بند ۱۲–۱۵; `پیوست ۱۲` بند ۱۲–۱۴ |
| **v0.5.0 — finance** | Phase 4 | INV, PAY, FIN | Invoices, gateway driver + verified callbacks, append-only ledger, settlement, reconciliation, bank export | `پیوست ۱` بند ۵; `پیوست ۷` بند ۲; `پیوست ۱۳` بند ۱–۳, ۶–۷ |
| **v0.6.0 — marketing** | Phase 5 | MKT, GEO, CMP | Marketer QR attribution, on-site store registration, plans A–D, commission runs | `پیوست ۵` بند ۱–۳; `پیوست ۶`; `پیوست ۴` بند ۲۳–۲۴ |
| **v0.6.1 — loyalty** | Phase 6 | LOY, PRM | Versioned points/tiered rewards, wallet bonuses, coupons, replay-proof rule sets | `پیوست ۴` بند ۱–۱۴, ۲۰–۲۲; `پیوست ۵` بند ۴۳ |
| **v0.7.0 — content** | Phase 7 | MSG, NTF, CMS, RVW, SMP | Messenger, tickets, throttled notifications, blog/CMS, reviews, samples | `پیوست ۲` بند ۱۰, ۱۲; `پیوست ۴` بند ۱۷; `پیوست ۵` بند ۹–۱۰, ۲۳, ۲۸ |
| **v0.7.1 — extensions** | Phase 8 | TND, CLS, PTG | Tenders/auctions, classifieds, Patogh services directory | `پیوست ۵` بند ۴–۶, ۱۳; `پیوست ۱۱` بند ۱۰ |
| **v0.9.0 — release candidate** | Phase 9 | ADM, RPT, CTR | Admin operations, reporting + Excel, PWA install, SEO/perf, security hardening | `پیوست ۱` بند ۴; `پیوست ۷` بند ۱, ۹–۱۰; `پیوست ۹` بند ۱–۳ |
| **v1.0.0 — UAT and delivery** | Phase 10 | CTR | Client acceptance on their server, training, handover, signed satisfaction | `پیوست ۱۰` بند ۱–۶ |

`v0.8.0` is intentionally unused: Phase 8 is a small extension increment shipped as `v0.7.1`, and reserving `0.8` avoids implying a wider feature set than exists.

**Native mobile apps are not in this plan.** The contract's appendix 8 covers Android/iOS apps; per `docs/canonical-context.md` §0 they are deferred as `RB-MOB-01`. PWA is delivered in `v0.9.0` as a web capability and must never be described as a native app in release notes or client correspondence.

---

## 2. Branching convention

Trunk-based with short-lived branches. `main` is protected at all times: no direct pushes, no force pushes, required review, required green CI, linear history via squash merge.

| Branch | Purpose | Lifetime | Naming |
|---|---|---|---|
| `main` | releasable at every tag; what staging deploys from | permanent | — |
| `feat/*` | one task from the DoR | ≤ 3 working days | `feat/<area>/<task>` e.g. `feat/loy/reward-consumption` |
| `fix/*` | a defect, referenced by `BUG-*` or an acceptance defect id | ≤ 2 working days | `fix/<area>/<bug>` |
| `docs/*` | documentation-only | ≤ 2 working days | `docs/<topic>` |
| `chore/*` | tooling, CI, deps, compose | ≤ 2 working days | `chore/<topic>` |
| `release/<minor>` | cut only for the UAT freeze and a client acceptance window | until `v1.0.0` ships | `release/1.0` |
| `hotfix/<minor>` | production fix during a freeze | until merged back | `hotfix/1.0.1` |

Rules: squash merge only; head branch deleted immediately after merge; a branch that lives longer than 3 working days is either split or escalated. Conventional Commits, one logical change per commit; `feat:`, `fix:`, `docs:`, `chore:`, `refactor:`, `test:`, `perf:`, `build:`, `ci:` with a scope naming the module (`feat(catalog): …`).

---

## 3. Tagging convention

- Annotated tags only: `git tag -a v0.5.0 -m "phase 4 — invoices, payments, finance"`.
- A tag is cut **only** from `main`, only after the release-level DoD in `definition-of-done.md` §2 passes, and only by the Orchestrator.
- Release candidates for client acceptance: `v0.9.0-rc.1`, `-rc.2`, … Each `rc` is immutable; a fix produces a new `rc`, never a moved tag.
- `CHANGELOG.md` follows Keep-a-Changelog structure (`Added`, `Changed`, `Fixed`, `Security`), grouped by requirement area, with the `REQ-*` ids and the appendix headings covered. It is the source of the weekly report's "what shipped" section.
- Every tag records the exact `composer.lock` and `package-lock.json` hashes in its release note plus the Docker image digest, so a rebuild can be proven to be the same artifact.

---

## 4. Migration and rollback policy

### 4.1 Migrations

1. **Forward-only in production.** `down()` must exist and be correct, but production rollback is by restore or by a compensating migration, not by `migrate:rollback` on a live database.
2. **Expand → backfill → contract** for any destructive change (rename, type narrowing, drop, non-null addition):
   - *expand*: add the new nullable column / new table, ship code that writes both;
   - *backfill*: a batched, resumable command with progress logging;
   - *contract*: drop the old shape in a later release, after a full backup and one stable release.
3. **One migration per module per PR**, prefix must match the owning module (see `dependency-map.md` §7). Cross-module DDL is rejected in review.
4. **Money columns** are `BIGINT` named `*_amount`; a migration that introduces a float, decimal-with-scale-drift or a nullable money column is rejected.
5. **Indexes** need a named query and an `EXPLAIN` note in the PR. Adding an index to a large table is scheduled as a maintenance window step.
6. **Irreversible migrations** must state it in the file header and be listed in the release note's rollback section.

### 4.2 Rollback decision matrix

| Situation | Action | Data consequence |
|---|---|---|
| Defect in code, schema compatible | redeploy previous image digest (container rollback) | none |
| Defect in code, new column only | redeploy previous image; the extra column stays unused | none |
| Bad data written by a bug | compensating command or reversal ledger entries — never `UPDATE` a ledger row | audited, traceable |
| Destructive migration already applied | restore the pre-release dump into a new database, replay binlogs to the release point, then cut over (see `runbooks.md` §2) | up to the RPO window |
| Gateway or provider outage | keep the release, disable the gateway in admin, use the secondary driver | none |
| Security defect | rollback first, fix forward second, notify per `ماده ۱۲` support SLA | none |

**Rollback time objective:** ≤ 15 minutes for an image rollback (previous digest is always retained), ≤ 90 minutes for a database restore-based rollback (RTO in `backup-and-restore.md`).

---

## 5. Staging → production promotion

The client supplies hosting, domain, SSL and the payment gateway accounts (`ماده ۲`, `ماده ۵` بند ۳). We promote the **same artifact** we accepted on staging.

```
feature PR → main (green CI) → tag vX.Y.Z → build image (digest D)
   → deploy D to staging → run smoke + acceptance suite → client preview (optional)
   → change request with the release note → deploy D to production → post-deploy verification
```

### 5.1 Promotion gates

| Gate | Check | Blocking |
|---|---|---|
| G1 | tag exists on `main`, CHANGELOG updated, release note lists headings covered | yes |
| G2 | image digest identical between staging and production | yes |
| G3 | staging smoke suite green: login (all roles), product list, cart→checkout→sandbox payment, invoice PDF, admin settlement screen, health endpoints | yes |
| G4 | production pre-flight: backup taken and verified (`backup-and-restore.md` §3), disk ≥ 30 % free, queue worker running, maintenance window announced | yes |
| G5 | migrations dry-run: `php artisan migrate --pretend` reviewed, expected duration recorded | yes |
| G6 | post-deploy: health `/health/ready` green, error rate normal for 15 minutes, one real low-value payment through the live gateway if the client permits | yes |

### 5.2 Production deployment sequence (summary; full steps in `runbooks.md` §1)

1. Announce window; put the app in read-only maintenance mode for the migration only.
2. Pull image digest D; run `php artisan migrate --force`; run seeders only if the release note says so.
3. Clear and warm caches; restart queue workers; reload php-fpm.
4. Exit maintenance mode; run post-deploy verification.
5. Record the deployment in `progress.md` (release row) and in the weekly report.

### 5.3 Environment parity rules

- Same PHP 8.4 / MySQL 8.4 / nginx versions and the same image in staging and production.
- Config differs only by environment values (`.env`); no code path is environment-conditional except debug/log level.
- Production never runs `APP_DEBUG=true`, never runs with the sandbox gateway enabled, and never has `mailpit` reachable.

---

## 6. Client acceptance milestones

Acceptance is incremental, per appendix heading, not one big-bang at the end. Each milestone produces a signed one-page record; the accumulated records are the evidence for the final satisfaction letter required by `پیوست ۱۰` بند ۴.

| Milestone | When | What the client sees | Sign-off artefact |
|---|---|---|---|
| A0 — baseline walkthrough | end of Phase 0 | CI, one-command environment, requirement baseline of 227 items, extension request (if needed) | meeting note + baseline PDF |
| A1 — identity & roles | end of Phase 1 | five roles, secondary role switch, audit log | heading sign-off `پیوست ۳`, `پیوست ۱` بند ۷/۱۰ |
| A2 — catalog & pricing | end of Phase 2 | one SKU, three role prices, filters, compare | `پیوست ۲` بند ۲–۵ |
| A3 — order flow | end of Phase 3 | two-supplier order end-to-end, stall hold | `پیوست ۲` بند ۶; `پیوست ۱۱` بند ۱۲–۱۵ |
| A4 — money | end of Phase 4 | payment, ledger, settlement statement, reconciliation report | `پیوست ۱۳` بند ۱–۳ |
| A5 — marketer network | end of Phase 5 | QR → attributed invoice → commission | `پیوست ۵` بند ۱–۳; `پیوست ۶` |
| A6 — loyalty | end of Phase 6 | replay-proof points, tiered discount, reward consumption | `پیوست ۴` |
| A7 — engagement & content | end of Phase 7 | throttled notification, messenger, blog, reviews | `پیوست ۵` بند ۹–۱۰, ۲۸ |
| A8 — extensions | end of Phase 8 | classified, tender, Patogh quote | `پیوست ۵` بند ۴–۶ |
| A9 — operational readiness | end of Phase 9 | PWA install, admin reports, Lighthouse, backup drill | `پیوست ۱` بند ۴; `پیوست ۷`; `پیوست ۹` |
| A10 — formal UAT | Phase 10 | staging on the client's server, acceptance scenarios, defect register | `پیوست ۱۰` بند ۱–۳ |
| A11 — final delivery | after A10 closes | handover package (§7), training complete, satisfaction letter | `پیوست ۱۰` بند ۴–۶; `ماده ۱` |

**Timing facts that shape this list.** Client feedback is due within 3 working days and silence for 7 working days deems our proposal approved (`ماده ۵` بند ۴) — so each milestone is sent with a dated cover note. Client delay in supplying content or the server extends the deadline day-for-day and must be recorded in the weekly report (`ماده ۳`). Final settlement (80,000,000 Toman) is due one month after final delivery **and** written confirmation (`ماده ۲`), so the satisfaction letter is a commercial dependency, not a formality.

---

## 7. Handover package

Delivered at `v1.0.0` as a single signed archive plus a repository transfer/collaborator grant. Contents are contractual (`ماده ۱`, `ماده ۴` بند ۴, `پیوست ۱` بند ۲, `پیوست ۱۰` بند ۶).

| # | Item | Detail | Contract basis |
|---|---|---|---|
| 1 | Source bundle | full repository at tag `v1.0.0`, with `.git` history, module structure, no secrets | `ماده ۴` بند ۴ |
| 2 | Install & operations guide | `README.md`, `docs/operations/local-development.md`, deployment steps, env-var reference | `پیوست ۱` بند ۲ |
| 3 | Architecture documentation | module map, ADRs, data model, order state machine, ledger model | `پیوست ۱` بند ۲ |
| 4 | API documentation | endpoint list, auth model, request/response envelope, error codes, pagination | `پیوست ۱` بند ۲ |
| 5 | Database documentation | table list by module with prefixes, key columns, and the ERD per module | `پیوست ۱` بند ۲ |
| 6 | Operations runbooks | deploy, rollback, restore, queue, gateway outage, certificate, disk, notification throttling | `ماده ۱۲` بند ۱ |
| 7 | Backup evidence | last 30 daily backup manifests, checksums, and the most recent restore-drill report | `پیوست ۱` بند ۹ |
| 8 | Test evidence | CI history, acceptance scenario results, defect register closed | `ماده ۴` بند ۳ |
| 9 | Training material | 4 in-person + 25 online session records, slide decks, training videos | `پیوست ۱۰` بند ۵–۶ |
| 10 | Admin credentials handover procedure | sealed, rotated at handover, 2FA re-enrolment performed by the client | `پیوست ۱` بند ۷ |
| 11 | Warranty statement | 5-year bug-fix warranty terms, SLA table (24 h first response, 24 h critical, 3 wd medium) | `ماده ۱۲` بند ۱–۲ |
| 12 | Acceptance dossier | per-heading sign-offs A0–A11 and the written satisfaction letter | `پیوست ۱۰` بند ۴ |

Handover is complete only when the client's technical representative can, from the package alone, rebuild the environment and run the smoke suite — that is the final test of `ماده ۴` بند ۴.

---

## 8. Post-delivery maintenance boundary

- **Warranty (5 years):** defects caused by programming error are fixed free of charge (`ماده ۱۲` بند ۲, `ماده ۱۶`). Response SLAs apply.
- **Change requests:** any new capability outside the appendices goes through the written annex mechanism (`ماده ۸`, annex table) with its own cost and delivery time. No verbal change is binding and no such change is implemented "while we are in there".
- **Annual support after warranty:** separate contract, negotiated fee (`ماده ۱۲` بند ۳).
- **Deferred items still owed:** `RB-MOB-01` (native Android/iOS) and any annex-listed split-payment work must appear in the roadmap the client receives at handover, with their penalty implications stated (`ماده ۴` بند ۵).
