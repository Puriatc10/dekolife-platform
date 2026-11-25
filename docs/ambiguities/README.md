# Ambiguities — How This Folder Works

| | |
|---|---|
| **Purpose** | Explain the register: what belongs in it, who may resolve what, how statuses behave, and how a decision becomes code or a client question. |
| **Audience** | Every agent (before implementing any rule the contract does not spell out), the Orchestrator, reviewers. |
| **Status** | Baseline v1.0. |
| **Source** | `docs/canonical-context.md` §4–§6 (ID scheme, source labels, ownership); contract pages 1–11. |

## 1. Why this folder exists

The contract is 16 articles and 203 appendix clauses, and it is silent or contradictory on the things that decide the architecture: who holds money, when stock is reserved, in what order discounts apply, what a "plan A" marketer earns, how long chat survives. Every one of those gaps is a place where an engineer will otherwise invent a rule.

This folder makes the invention **visible**: an ID, the clause it comes from, the options, the chosen reading, the blast radius, and how to undo it. Three outcomes are possible for any gap:

1. **We decide** → the row gets `Decided` and a `DEC-*` entry. Work proceeds.
2. **The owner must decide** → the row gets `Escalated` and the question goes into the next owner review with the cost of each option.
3. **Nothing depends on it yet** → the row gets `Open` and is picked up by the phase that needs it. No guessing ahead of need.

What is *not* allowed: implementing an unspecified rule without a row, and closing a row by "it seemed obvious".

## 2. Files

| File | What it holds | Who writes |
|---|---|---|
| [`ambiguity-register.md`](ambiguity-register.md) | The register: 81 rows (`AMB-<AREA>-<NNN>`) with pointer, options, decision, impact, reversibility, status; seven aliases; and the ID-collision table for sibling documents. | Contract/ambiguity agent (this folder's owner); others append via the Orchestrator |
| [`agent-decisions.md`](agent-decisions.md) | 35 `DEC-*` entries: the decision, its basis and label, the rationale, how to change it, the ambiguities it closes. | Contract/ambiguity agent; any agent may propose a new entry |
| [`../contract/contract-source.md`](../contract/contract-source.md) | Provenance, extraction defects, verification log. Read before quoting a figure | Contract agent |
| [`../contract/contract-summary.md`](../contract/contract-summary.md) | The contract in one pass, with the numeric register | Contract agent |
| [`../contract/scope-boundaries.md`](../contract/scope-boundaries.md) | In scope / deferred / out of scope for this mission | Contract agent |

## 3. ID scheme and statuses

**IDs.** `AMB-<AREA>-<NNN>` where `AREA` is one of the 27 module areas from [`../canonical-context.md`](../canonical-context.md) §3 plus `CTR` for contract-level/legal topics. Three digits, zero-padded, **stable forever** — never renumber, never reuse a retired number, because tests, ADRs and requirement records reference these strings.

Related identifiers you will see in the same sentences:

- `DEC-<NNN>` — a decision in [`agent-decisions.md`](agent-decisions.md).
- `REQ-<AREA>-<NNN>` — a requirement record (owned by the requirements agent), which cites the ambiguity it resolves.
- `ADR-<NNNN>-<slug>` — an architectural decision that implements a `DEC-*` at the design level.
- `RB-<AREA>-<NN>` — a deferred roadmap item (e.g. `RB-MOB-01`, `RB-PAY-01`).

**Statuses.**

| Status | Meaning | Who may set | What it permits |
|---|---|---|---|
| `Decided` | An agent baseline is in force: the `DEC-*` is written and the code may rely on it. Reversible without renegotiation. | Contract/ambiguity agent | Build against it; cover it with a test |
| `Escalated` | A written owner answer is required; the current reading is a placeholder, not a commitment | Contract/ambiguity agent, on the Orchestrator's instruction | Build against the placeholder **only** behind a config/flag and with the row referenced in the code comment |
| `Open` | No decision needed yet — the phase that needs it will decide | Any agent, with a one-line reason | Do not build ahead of it |
| `Resolved` | The owner answered; the reading is now frozen and traceable to a written instruction | Orchestrator after receiving the answer | Build normally; quote the answer in the `Decision` cell |
| `Superseded` | A later row replaces this one (merged, split or corrected) | Contract/ambiguity agent | Follow the successor row |

A row marked `Escalated` is **not** blocked work. It means: the conservative reading is in force, it is written down, and a change is one config value or one migration away — never a surprise.

## 4. Who resolves what

| Kind of ambiguity | Resolver | Example |
|---|---|---|
| Engineering reading of an under-specified clause (thresholds, window lengths, retention, ordering) | Agent baseline → `Decided` | `AMB-LOY-004` — which tier owns 100 points: resolved upward |
| Anything that changes what the client pays, what they own, or what they are legally exposed to | **Owner, in writing** → `Escalated` | `AMB-FIN-001` custody of funds; `AMB-CTR-005` payment plan; `AMB-CMP-001` plan definitions |
| Anything the contract contradicts itself on | Owner, with the conflict quoted | `AMB-CTR-006` support duration; `AMB-IAM-001` secondary role |
| Anything that collides with a canonical engineering baseline | Orchestrator, then owner if it has commercial impact | `AMB-PAY-001` two gateways in one transaction vs `RB-PAY-01` |
| Missing facts about the client's business (gateway list, SMS provider, bank format) | Owner / client operations | `AMB-PAY-002`, `AMB-NTF-001`, `AMB-FIN-004` |

Rule of thumb: if two engineers could implement the two options and a reasonable client would accept either, decide it here. If one option costs the client money or creates a legal posture, escalate.

## 5. The lifecycle of a row

```
found → registered (Open) → baseline written (Decided) → owner review
                                      ↘ Escalated → answer → Resolved
                                      ↘ phase needs it → Decided | Superseded
```

1. **Found.** Quote the clause, note the page/پیوست/بند, and describe the defect in one sentence a non-lawyer can follow.
2. **Registered.** Take the next free number in the area. Never reuse. Add the row even if the decision is obvious — the point is the trace.
3. **Baseline.** Write a `DEC-*` entry, set the row to `Decided`, and reference the `DEC` from any code that depends on it (one comment line with the ID is enough).
4. **Owner review.** Rows marked `Escalated` go to the owner as a numbered question with options and cost. The answer is copied into the `Decision` cell, the status becomes `Resolved`, and the `DEC` entry gains a line quoting the answer.
5. **Superseded.** If a later answer contradicts an earlier decision, do not edit history: append `Superseded by AMB-...-NNN` and write the new row.

## 6. How to add a row

Copy this template and fill every cell — an empty cell is a rejection, not a shortcut:

```markdown
| `AMB-<AREA>-<NNN>` | <short title> | page N / پیوست M بند K | <the defect, in one sentence> | (a) …; (b) …; (c) … | (x) <reading> (DEC-0NN) | <what breaks if wrong> | Low \| Medium \| High | Decided \| Escalated \| Open |
```

Then:

1. Add the `DEC-*` entry to [`agent-decisions.md`](agent-decisions.md) (§1–§7) **and** a line to its §8 index.
2. Add any deferral to the roadmap as `RB-*` so it does not vanish.
3. Tell the Orchestrator — other agents work in parallel and will otherwise duplicate the row under a different number.
4. If the row changes a money, points or settlement rule, write the test in the same change. An undecided financial rule that is already in code is a defect.

## 7. Reading the register in a review

- **Implementing something not in the appendices?** Check §Part A/B for a row first; if none, register one before writing code.
- **Reviewing a money change?** Every such change must cite a `DEC-*`; a `High` reversibility decision needs an ADR and an owner answer.
- **Client asks "why is it like this?"** Quote the row: clause, options, chosen reading, and what it would cost to change. That is the whole purpose of the folder.
- **Suspect the register is stale?** Compare it against the contract's numeric register in [`../contract/contract-summary.md`](../contract/contract-summary.md) §6 — every figure there should be traceable to a `Decided` or `Resolved` row.

## 8. Known hygiene issues (and what to do)

| Issue | Effect | Action |
|---|---|---|
| The PDF carries two revisions of pages 1–4, so the payment plan and support duration exist in two versions | Two plausible readings of two commercial terms | `AMB-CTR-005`, `AMB-CTR-006`; owner answer closes both |
| Sibling documents attached different meanings to `AMB-FIN-002/003/004`, `AMB-PAY-002`, `AMB-PRI-002`, `AMB-STK-001`, `AMB-ORD-005`, `AMB-CMP-001`, `AMB-LOY-002`, `AMB-MKT-001` | A reference can point at the wrong topic | See the collision table at the end of [`ambiguity-register.md`](ambiguity-register.md); the register wins — re-point the sibling |
| The contract is an unsigned draft (page 1, page 11) | No clause can be enforced yet; commercial figures are provisional | `AMB-CTR-007`; never describe it as executed |
| Persian text extraction drops lam-alef ligatures and reverses some blocks | Quotes can look corrupted or mean the opposite | Apply the normalisation rules in [`../contract/contract-source.md`](../contract/contract-source.md) §3.2–§3.3, verify figures against page renders |
