# API Deprecations — Dekolife Platform

**Purpose:** Register of every deprecated endpoint, field, header and enum value, with the date
it stops being honoured. A deprecation is a promise to clients, so it is recorded here before
it is announced.
**Audience:** API consumers (web app, PWA, admin), integration partners, release manager.
**Status:** Living register. **No deprecations in effect as of the v1 baseline.**
**Source:** [`API-guidelines.md`](./API-guidelines.md) §2 (versioning and compatibility).

---

## 1. Rules

1. A deprecation is announced at least **90 days** before removal, in the changelog and in the
   response headers of the affected operations:

   ```http
   Deprecation: true
   Sunset: Wed, 25 Feb 2027 00:00:00 GMT
   Link: <https://api.dekolife.ir/api/v2/orders>; rel="deprecation"
   ```

2. During the window the old behaviour keeps working exactly as documented; it is never
   "deprecated but broken".
3. Additive changes are **not** deprecations: a new field, a new optional query parameter, a new
   endpoint or a new error code needs no entry here.
4. An entry has: what, replacement, first deprecated date, sunset date, owner, and the migration
   note clients need.
5. Removal requires a `/api/v2` (or a documented `Sunset` that has passed) and a line in the
   changelog. The row stays in this file forever, marked `removed`.

## 2. Register

| # | Surface | Deprecated item | Replacement | Deprecated | Sunset | Status | Owner |
|---|---|---|---|---|---|---|---|
| — | — | *None* | — | — | — | — | — |

## 3. Not deprecations, but worth knowing

| Change | Why it is compatible |
|---|---|
| New enum value in a **response** | Clients must ignore unknown enum values (guidelines §2.4) |
| New optional request field | Existing clients omit it; server defaults apply |
| New error `code` for a new failure mode | Clients must handle unknown codes generically (§7 of the error model) |
| Stricter validation of a field that was already documented | Documented contract; announce in the changelog |
| Added `*_jalali` twin fields | Additive; the ISO-8601 field is unchanged |

## 4. Process

1. Engineer opens a PR that adds the row here **and** the `Deprecation`/`Sunset` headers.
2. Release manager confirms the 90-day window fits the release calendar.
3. Support is notified with the client-facing wording (Persian and English).
4. On the sunset date, the row moves to `removed`, the endpoint returns `410 Gone` for one
   release, then is deleted from the router.
