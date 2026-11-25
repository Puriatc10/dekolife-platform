## Summary

<!-- One paragraph: what changed and why. Link the issue/requirement. -->

## Requirement traceability

| Requirement IDs | Contract reference |
| --- | --- |
| REQ-XXX-000 | پیوست X / بند Y, page N |

## Type of change

- [ ] Feature
- [ ] Bug fix
- [ ] Refactor (no behaviour change)
- [ ] Documentation
- [ ] CI / tooling / infrastructure

## Screenshots / recordings (UI changes)

<!-- RTL screenshots on mobile + desktop, including empty/error states. -->

## Migrations

- [ ] No migration
- [ ] Migration added (describe: tables, indexes, backfill, rollback plan)
- [ ] Migration is backward compatible with the previous release

## How this was verified

<!-- Commands run and their real output, e.g. `php artisan test` → "48 passed". -->

```
$ scripts/check.sh
```

## Risk & rollback

<!-- User-facing risk, data risk, feature flag, rollback steps. -->

## Checklist

- [ ] Requirement IDs listed and traceable
- [ ] Server-side authorization implemented + negative test added
- [ ] Validation server-side (FormRequest), no client-only validation
- [ ] Money/points/commission changes covered by deterministic tests
- [ ] UI states: loading, empty, error, success, unauthorized, RTL/mobile checked
- [ ] Docs updated (`docs/`), no placeholder text
- [ ] No secrets, tokens, real PII or customer data in the diff
- [ ] `scripts/check.sh` green locally (same gates as CI)
