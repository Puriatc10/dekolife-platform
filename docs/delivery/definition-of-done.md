# Definition of Done (DoD)

**Purpose:** The 12-point gate every unit of work must pass before merge, plus the product-level acceptance criteria a release must pass before it is tagged or handed to the client.
**Audience:** Every engineering agent (self-check), the independent reviewer (the gate), the Orchestrator (release decisions).
**Status:** Baseline v1 (Phase 0 output). Binding. A PR that fails any point is not merged, regardless of schedule pressure.
**Source:** `ماده ۴` بند ۱ (no reduction in the appendices), `ماده ۴` بند ۳ (unit, integration and acceptance testing before announcing delivery), `ماده ۴` بند ۴ (documented, editable source), `ماده ۱۲` بند ۱ (support SLAs imply production-grade error handling), `پیوست ۱` بند ۲, ۹, ۱۰, `پیوست ۹` بند ۳, `پیوست ۱۰` بند ۱–۴; engineering conventions in `docs/canonical-context.md` §5 and §7.

---

## Part 1 — The 12-point DoD (every task, every PR)

| # | Point | Pass condition | How it is verified |
|---|---|---|---|
| 1 | **Requirement traceability** | Every `REQ-*` id in the task is either `Implemented` by this PR or explicitly still open with a reason; no requirement silently disappears | PR body maps id → file/test; reviewer checks the register row |
| 2 | **Acceptance criteria demonstrated** | Each criterion from the DoR has a test that fails without the change, or a documented manual script executed with screenshots | test names listed per criterion |
| 3 | **Test coverage of the change** | Unit tests for domain rules; feature tests against **real MySQL 8.4** for HTTP/DB paths; every money, points, settlement and commission rule has a test; every scoped resource has a negative authorization test for each denied role (web **and** API) | `php artisan test` output in the PR; reviewer inspects the diff for missing negatives |
| 4 | **Static quality gates** | Pint clean, PHPStan level 6 with zero errors, `declare(strict_types=1);` in every new PHP file, no `env()` outside `config/`, no business logic in controllers, validation in FormRequests, authorization in policies | CI job output |
| 5 | **CI green** | All CI jobs pass on the PR head: Pint, PHPStan, test suite on the MySQL service, `npm ci && npm run build`, secret scan. No skipped or `--filter`-narrowed tests to fake a pass | CI status check on the exact head SHA |
| 6 | **Migration safety** | Migration reviewed for prefix ownership, index justification, FK direction (only into `iam_`/`usr_`/`mer_` identity columns), and a rollback path; destructive changes split expand → backfill → contract | reviewer sign-off on the migration note; `migrate` + `migrate:rollback` executed in CI for new migrations |
| 7 | **Authorization and audit** | Server-side policy on every route; sensitive events (login, price change, role change, settlement, identity data change) write an audit row through `AuditLogger` with actor, old/new, request id | negative tests + a test asserting the audit row |
| 8 | **Observability contract** | Errors return the shared error envelope with `request_id`; the request id is propagated to logs; no PII, token, OTP, national ID, card data or provider payload is logged | reviewer greps the diff for `Log::` calls; envelope test |
| 9 | **UI completeness** (when the change renders) | Persian RTL copy, no untranslated keys, empty / loading / error / unauthorized / validation states implemented, responsive at 360/768/1280 px, accessible primary action (label, focus, contrast) | screenshots in the PR at the three widths |
| 10 | **Documentation updated** | Module README or ADR updated when a decision was made; API contract updated when a route shape changed; the "Decisions" or "Completed" row added to `progress.md`; ops docs updated if a new command, env var, queue or channel was introduced | diff includes the doc change |
| 11 | **Independent review approved** | A reviewer who did not author the change approved it; every comment resolved or converted to a tracked task; no self-merge, no "merge with comments pending" | PR review state |
| 12 | **Branch hygiene and release note** | Conventional-Commit messages, one logical change, squashed into `main`, **head branch deleted**, and a changelog entry written when the change is user-visible | branch list after merge; changelog diff |

### 1.1 Fast-fail rules

- A green CI run on an older commit does not count (point 5 is SHA-bound).
- A test that asserts on an implementation detail instead of the acceptance criterion does not satisfy point 2.
- "Existing behaviour, not my change" is only accepted with a linked issue; it is not a DoD waiver.
- Any waiver requires the Orchestrator to write it in `progress.md` with the reason and the expiry date; waivers may not be granted for points 3, 5, 7 or 8.

---

## Part 2 — Product-level DoD (every release, and mandatory for `v1.0.0`)

A release tag is only cut when all of the following hold for the phase in `phases-and-milestones.md`.

### 2.1 Functional completeness

- Every `REQ-*` allocated to the phase is `Implemented`, or carries a written `Deferred` decision with a `RB-*` roadmap id and a named annex item (`ماده ۴` بند ۵ makes an unexecuted appendix heading a 4,500,000 Toman penalty, so deferral is never silent).
- No P0 requirement is `Ambiguous`-only: each has a resolved decision or implements the conservative reading with an `AMB-` record.

### 2.2 End-to-end acceptance

- The golden path is executed on a **staging** environment with seeded realistic data: consumer registers with SMS verification → browses catalog with filters → compares 5 products → adds to cart with a coupon → checks out with a shipping method → pays via the sandbox gateway → supplier sees the sub-order → ships with carrier data → order completes → points are credited → reward is consumed on the next invoice.
- The second golden path: marketer scans a QR → registers a store on-site with galleries and location → store's first invoice places → commission is calculated → admin approves settlement → ledger reflects the payout.
- The third golden path: admin creates a manual invoice, applies a tiered discount, exports the settlement Excel, and reviews the audit log for the day.
- Each path has an automated `tests/Acceptance` test where feasible; where it is browser-only, it has a recorded script with screenshots stored with the release.

### 2.3 Non-functional gates

| Gate | Threshold | Evidence |
|---|---|---|
| Catalog list p95 | < 400 ms with 10k products seeded | request timing in staging |
| Checkout p95 | < 800 ms | request timing |
| N+1 | `Model::preventLazyLoading()` enabled in CI, zero violations on the top-10 pages | CI log |
| Slow queries | zero queries > 1 s in the slow-query log during the acceptance run | `mysql.slow_log` extract |
| PWA | installable on Android and iOS from the browser; Lighthouse PWA pass; performance ≥ 80 | Lighthouse report |
| Responsive | 360 / 768 / 1280 px with no horizontal scroll on the golden paths | screenshots |
| Security | SSL enforced, brute-force lockout, CAPTCHA on public forms, role checks on every route, CSRF on all state-changing forms, upload validation (`پیوست ۹` بند ۳) | checklist + tests |
| Backup | daily backup ran, restore drill passed this cycle | `backup-and-restore.md` drill log |
| Audit | sensitive-event rows present for the acceptance run | `adm_audit_logs` query |

### 2.4 Delivery readiness (release-level)

- `CHANGELOG.md` entry, annotated tag, release notes with the appendix headings covered.
- Migration plan for production: ordered list, expected duration, and the rollback decision for each (see `release-plan.md` §5).
- Staging and production run the **same image digest**; config differences limited to environment values.
- The restore drill has been executed on the release candidate's schema, not on an older dump.
- Operations docs updated: any new queue, scheduled command, env var, channel or endpoint appears in `monitoring-and-logging.md` and `runbooks.md`.
- Handover impact assessed: if the release changes a screen the client will be trained on, the training material task is opened the same day.

### 2.5 Sign-off record

| Role | Confirms |
|---|---|
| Author | points 1–12 for the PR |
| Reviewer | points 2, 3, 6, 7, 8, 11 and the product gates for the release |
| Orchestrator | scope vs `progress.md`, deferrals with `RB-*`, waiver log |
| Delivery lead | client-facing evidence pack (screenshots / demo link) for the weekly report and each milestone |

The sign-off is recorded in `progress.md` (release row) and, for milestones, in the weekly report required by `ماده ۳`.
