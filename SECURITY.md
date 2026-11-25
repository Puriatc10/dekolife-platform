# Security policy

## Reporting a vulnerability

Do not open a public issue for a security problem. Report it privately to the repository
owner (Puriatc10) with:

- affected area (route, module, commit),
- reproduction steps or proof of concept,
- impact assessment (data exposed, action possible),
- suggested remediation, if known.

Acknowledgement target: 24 hours. The contractual support SLA for the delivered system is
24 hours for critical incidents, per `ماده ۱۲` of the contract.

## Scope

This repository is a private client project. The following are always in scope:

- authentication and session handling, including the admin 2FA flow,
- authorization bypasses and IDOR across role scopes (consumer / store / supplier /
  marketer / admin),
- payment callback forgery, replay or amount tampering,
- points, commission and settlement manipulation,
- upload handling and stored XSS,
- exposure of PII (national ID, postal code, mobile, address, geolocation, ID documents).

## Handling secrets

- Secrets live in the deployment environment or a secret manager, never in git.
- `.env.example` contains placeholders only.
- Gateway, SMS and map credentials are injected per environment; fakes are used locally
  and in CI.
- If a secret is committed by accident: rotate it first, then purge history. A rewrite
  without rotation is not a fix.

## Dependency and platform hygiene

- `composer audit` and `npm audit` run in CI; high/critical findings block a merge.
- PHP and Node base images are pinned by tag; upgrades go through a PR with the test suite.
- Database backups are encrypted at rest and restored in a drill (see
  `docs/operations/backup-and-restore.md`).
