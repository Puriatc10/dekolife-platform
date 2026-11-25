# Definition of Ready (DoR)

**Purpose:** A binary checklist a task must satisfy **before** any code is written, so that parallel agents cannot start work on an undefined, unbounded or colliding item.
**Audience:** Every engineering agent, the Orchestrator (who assigns), the independent reviewer (who can bounce a task back to `Not Ready`).
**Status:** Baseline v1 (Phase 0 output). Applies from Phase 0 onward, to every task, including documentation and infrastructure tasks.
**Source:** Process derived from contract obligations — `ماده ۴` بند ۳ (unit/integration/acceptance tests before delivery), `ماده ۴` بند ۱ (no reduction from the appendices), `پیوست ۱` بند ۲ (documented code) — and the engineering conventions in `docs/canonical-context.md` §4, §5, §7.

> **Rule.** A task that fails any *blocking* item is `Not Ready` and must not be picked up, estimated as "in progress", or partly implemented "to save time". The Orchestrator either completes the missing input or splits the task. A task may not be marked ready by the same agent that will implement it unless that agent is the sole owner of the module and the reviewer has acknowledged the ticket.

---

## 1. Blocking checklist

Every row is mandatory. "Evidence" is what the reviewer looks at; a claim without evidence is a fail.

| # | Item | What "present" looks like | Evidence in the task/PR |
|---|---|---|---|
| 1 | **Requirement ids** | One or more `REQ-<AREA>-<NNN>` ids from the register; no task exists "because it seems needed" | ids listed in the task title/body |
| 2 | **Verbatim source + pointer** | The Persian quote and `page / پیوست / بند` for each id | quote block in the task |
| 3 | **Contract pointer for legal constraints** | If the task touches timing, payment, penalty, warranty, backup or audit, the specific article/clause is cited (`ماده ۴` بند ۲, `پیوست ۱` بند ۹, …) | citation line |
| 4 | **Acceptance criteria** | Testable statements: input → expected output, including the failure path and the authorization-denied path | bullet list, each with a measurable assertion |
| 5 | **Owned paths** | The exact files/directories the task will create or modify, already inside the agent's boundary from `dependency-map.md` §6 | path list; no path owned by another track |
| 6 | **Dependency check** | Every contract/table/event the task consumes is merged and tagged; if not, the task lists the blocking task id | dependency line, `blocked_by` |
| 7 | **Test plan** | Which Pest files, which fixtures, and whether a real MySQL feature test is required | test file paths |
| 8 | **Migration plan** | Yes/no. If yes: table prefix, columns with types, indexes, FK targets, backfill need, rollback path, and whether it is expand/contract | short migration note |
| 9 | **UI states** | If it renders: empty, loading, error, unauthorized/403, success, validation-error states defined, plus RTL and the 360/768/1280 px breakpoints | state list |
| 10 | **Reviewer assigned** | A named reviewer from the QA track, different from the author, with capacity in the current window | reviewer name in the task |
| 11 | **Ambiguity status** | Any `AMB-*` affecting the task is either resolved with a written decision or the task explicitly implements the conservative reading and says so | `AMB-` id + decision reference |
| 12 | **Estimate and window** | Effort in working days, and the phase/wave the task belongs to; must fit the current wave's contract surface | estimate + wave |
| 13 | **Definition of Done acknowledged** | The task body states that `definition-of-done.md` applies, with any module-specific addition | line reference |

---

## 2. Additional gates by task type

### 2.1 Money, points, settlement, commission

- Rounding rule stated (all arithmetic through `App\Shared\Money\Money`; integer Toman; no float anywhere).
- Idempotency key named (`reference`) and the duplicate-delivery behaviour described.
- Ledger impact described: which legs, and whether a reversal is needed for correction.
- A test that replays the same command twice and asserts a single effect.
- Reviewer with money authority signs; `progress.md` gets a decision row if a rule was interpreted.

### 2.2 Authorization / scoped resource

- The scope being enforced is named (`own`, `network`, `merchant:{id}`, `global`).
- A **negative** test is listed for every role that must be denied — web and API.
- The policy class and the route middleware are both named; hiding a button is not authorization.

### 2.3 Migrations and schema

- Table prefix matches the owning module; no DDL into another module's tables.
- Index list justified against a named query (no "add indexes later").
- Destructive change identified (drop/rename/type narrowing) and split into expand → backfill → contract with a release boundary.
- `down()` either restores safely or is documented as irreversible with a restore-based rollback.

### 2.4 Integrations (gateway, SMS, WhatsApp, maps, bank)

- Sandbox/simulator availability confirmed; a fake driver exists for tests.
- Timeout, retry, and circuit-breaker behaviour stated.
- Callback/receipt verification rule stated (signature, amount match, reference match).
- Never-log list respected: no tokens, card data or provider payloads in logs.

### 2.5 UI / Blade + Livewire

- Persian copy present (UI language is Persian) and no untranslated keys.
- The four states above plus the pagination boundary (page 1, empty page, last page).
- Accessibility baseline: focus order, labels, contrast at 4.5:1, keyboard-only path for the primary action.
- Blade components reused rather than one-off markup; no business logic in the view.

### 2.6 Documentation and operations

- Audience and status header, `Source:` line, cross-links verified to exist.
- Every command in a runbook is copy-pasteable and names its target (`docker compose exec app …`, `mysql …`).
- No placeholder text; unknown facts written as an explicit `AMB-` reference.

---

## 3. Ready-state handshake

```
Orchestrator: fills items 1–4, 6, 9, 11, 12
Module owner: fills items 5, 7, 8, 13
Reviewer:     accepts item 10 and countersigns the test plan (item 7)
```

If a task is bounced back twice for the same missing item, the Orchestrator records it in `progress.md` under "process defects" and the missing input becomes its own task with its own owner.

---

## 4. Anti-patterns explicitly rejected at the Ready gate

| Anti-pattern | Why it is rejected |
|---|---|
| "Implement the whole appendix heading" as one task | unbounded; fails items 1, 4, 5 and 12 |
| "Add tests later" | violates `ماده ۴` بند ۳ and the DoD; tests are part of the task |
| "Copy the price from the request to speed up checkout" | violates server-side price resolution; a security defect by construction |
| "We'll decide the role model while coding" | the `Actor`/`RoleSet` contract is frozen at `v0.2.0`; changing it mid-track breaks every other track |
| "Skip the migration note, it's a small column" | column type and index decisions on money columns are exactly where rework starts |
| A task touching two tracks' paths | guaranteed merge conflict and unclear ownership |
