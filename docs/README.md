# Documentation index — Dekolife platform

Read in this order. Documents are the authority for design decisions; the code follows them,
not the reverse.

| # | Document | Why it exists |
| --- | --- | --- |
| 0 | [`canonical-context.md`](canonical-context.md) | Pinned names, module paths, requirement IDs, stack versions. **Read first.** |
| 1 | [`engineering-rules.md`](engineering-rules.md) | The short rule sheet (layering, security, tests, workflow, out-of-scope). |

## Contract

| Document | Content |
| --- | --- |
| [`contract/contract-source.md`](contract/contract-source.md) | Provenance of the PDF, how the text was extracted, checksums. |
| [`contract/contract-summary.md`](contract/contract-summary.md) | All 16 articles and 14 appendices, with every figure. |
| [`contract/scope-boundaries.md`](contract/scope-boundaries.md) | What this mission builds, what is deferred, what belongs to another contract. |
| [`contract/contract-requirements-matrix.md`](contract/contract-requirements-matrix.md) | Clause → requirement traceability, coverage counts. |

## Requirements

| Document | Content |
| --- | --- |
| [`requirements/functional-requirements.md`](requirements/functional-requirements.md) | The requirement catalogue (`REQ-<AREA>-NNN`). |
| [`requirements/non-functional-requirements.md`](requirements/non-functional-requirements.md) | Measurable quality targets and the contractual SLA. |
| [`requirements/permissions-matrix.md`](requirements/permissions-matrix.md) | Role × capability matrix and scoping rules. |
| [`requirements/acceptance-criteria.md`](requirements/acceptance-criteria.md) | End-to-end scenarios that must pass before UAT. |

## Product

| Document | Content |
| --- | --- |
| [`product/product-vision.md`](product/product-vision.md) | Problem, market, value per role, business model. |
| [`product/PRD.md`](product/PRD.md) | Product requirements, flows, non-goals, open questions. |
| [`product/personas-and-roles.md`](product/personas-and-roles.md) | The five roles plus admin and the secondary-role rule. |
| [`product/user-journeys.md`](product/user-journeys.md) | Twelve end-to-end journeys. |
| [`product/use-cases.md`](product/use-cases.md) | Use-case catalogue for the P0 areas. |
| [`product/business-rules.md`](product/business-rules.md) | The consolidated rulebook (pricing, points, rewards, commissions, penalties). |
| [`product/glossary.md`](product/glossary.md) | Persian ↔ English domain glossary. |

## Architecture

| Document | Content |
| --- | --- |
| [`architecture/overview.md`](architecture/overview.md) | System context, request lifecycle, layering. |
| [`architecture/technology-stack.md`](architecture/technology-stack.md) | Every dependency with reason, alternative, limitation, version. |
| [`architecture/modular-monolith.md`](architecture/modular-monolith.md) | Module anatomy and dependency rules. |
| [`architecture/domain-boundaries.md`](architecture/domain-boundaries.md) | The 28 contexts: tables, services, events, UI. |
| [`architecture/data-flow.md`](architecture/data-flow.md) | Sequence flows for checkout, orders, points, commissions, complaints, settlement. |
| [`architecture/security-architecture.md`](architecture/security-architecture.md) | AuthN/AuthZ, 2FA, PII, uploads, rate limits, audit. |
| [`architecture/integrations.md`](architecture/integrations.md) | Gateway adapters, SMS/WhatsApp/email/push, maps, bank files, fakes. |
| [`architecture/background-jobs.md`](architecture/background-jobs.md) | Queue and scheduler inventory. |
| [`architecture/deployment-topology.md`](architecture/deployment-topology.md) | Local, CI, staging, production, backup, rollback. |
| [`adr/README.md`](adr/README.md) | Decision log index (ADR-0001 … ADR-0008). |

## Data and API

| Document | Content |
| --- | --- |
| [`data/domain-model.md`](data/domain-model.md) | Entity catalogue per module. |
| [`data/ERD.md`](data/ERD.md) | ER diagrams per cluster. |
| [`data/data-dictionary.md`](data/data-dictionary.md) | Column-level dictionary for P0 tables. |
| [`data/database-conventions.md`](data/database-conventions.md) | Naming, types, migrations, money, time. |
| [`data/order-state-machine.md`](data/order-state-machine.md) | Order/sub-order states and transitions. |
| [`api/API-guidelines.md`](api/API-guidelines.md) | Versioning, pagination, errors, auth. |
| [`api/endpoint-inventory.md`](api/endpoint-inventory.md) | Endpoint list by module and role. |
| [`api/error-model.md`](api/error-model.md) | Error codes and HTTP mapping. |
| [`api/deprecations.md`](api/deprecations.md) | API deprecation policy and sunset headers. |

## UX

| Document | Content |
| --- | --- |
| [`UX/sitemap.md`](UX/sitemap.md) | Full sitemap for storefront and all panels. |
| [`UX/navigation-and-layout.md`](UX/navigation-and-layout.md) | Navigation, RTL layout, states, accessibility. |
| [`UX/design-system.md`](UX/design-system.md) | Tokens and component inventory. |
| [`UX/screen-inventory.md`](UX/screen-inventory.md) | The screen backlog with priorities. |

## Security, testing, delivery, operations

| Document | Content |
| --- | --- |
| [`security/threat-model.md`](security/threat-model.md) | Assets, trust boundaries, STRIDE, abuse cases. |
| [`security/privacy-and-PII.md`](security/privacy-and-PII.md) | Data classification, retention, masking. |
| [`security/security-checklist.md`](security/security-checklist.md) | Controls with the mechanism and the negative test. |
| [`testing/test-strategy.md`](testing/test-strategy.md) | Test pyramid, MySQL requirement, fakes. |
| [`testing/test-matrix.md`](testing/test-matrix.md) | Module × test type matrix. |
| [`testing/quality-gates.md`](testing/quality-gates.md) | Merge gates and PR checklist. |
| [`delivery/phases-and-milestones.md`](delivery/phases-and-milestones.md) | Phase 0–10 with exit criteria and estimates. |
| [`delivery/dependency-map.md`](delivery/dependency-map.md) | Build order, parallel tracks, path ownership. |
| [`delivery/definition-of-ready.md`](delivery/definition-of-ready.md) | Ready checklist. |
| [`delivery/definition-of-done.md`](delivery/definition-of-done.md) | Done checklist. |
| [`delivery/release-plan.md`](delivery/release-plan.md) | Releases, tags, rollback, handover. |
| [`delivery/progress.md`](delivery/progress.md) | Living progress log. |
| [`operations/local-development.md`](operations/local-development.md) | Docker workflow (no host PHP). |
| [`operations/backup-and-restore.md`](operations/backup-and-restore.md) | Daily backup, retention, restore drill. |
| [`operations/monitoring-and-logging.md`](operations/monitoring-and-logging.md) | Logs, health, audit review, what never to log. |
| [`operations/runbooks.md`](operations/runbooks.md) | Operational runbooks. |

## Ambiguities

| Document | Content |
| --- | --- |
| [`ambiguities/README.md`](ambiguities/README.md) | How the register and decision log are used. |
| [`ambiguities/ambiguity-register.md`](ambiguities/ambiguity-register.md) | Every open question, with the decision taken. |
| [`ambiguities/agent-decisions.md`](ambiguities/agent-decisions.md) | The decision log with rationale and reversal path. |

## Conventions

- Engineering documents are written in English; Persian appears verbatim where legal wording
  matters (with the source page and appendix cited).
- Every requirement-level statement cites `page N / پیوست X / بند Y`.
- Numbers that the contract does not fix are marked `AGENT_DECISION` and linked to an
  `AMB-*` entry; they are not presented as contractual obligations.
