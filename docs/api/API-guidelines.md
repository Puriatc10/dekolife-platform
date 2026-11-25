# API Guidelines — Dekolife Platform

**Purpose:** One contract for every HTTP API consumer and producer: versioning, resource
naming, pagination, filtering, idempotency, authentication, rate limiting, the error envelope,
date/time and money representation, and file uploads.
**Audience:** Backend engineers implementing endpoints, frontend/PWA engineers, integration
partners, QA writing contract tests.
**Status:** Baseline (v1). Deviations require an ADR; the contract test suite enforces the
envelope and naming rules.
**Source:** `docs/canonical-context.md` §2 (Laravel Sanctum tokens, `routes/api.php`),
§5 (money in integer Toman, UTC storage, server-side authorisation); contract appendix 1
clauses 1–8 (Laravel/MySQL, responsive/PWA clients, API documentation deliverable, 2FA for
admin), appendix 9 clause 3 (rate limiting, captcha, role-based access control).

---

## 1. Fundamentals

| Property | Rule |
|---|---|
| Base path | `/api/v1` — the version is part of the path, never a header |
| Transport | HTTPS only; HSTS enabled; HTTP requests redirect with `308` |
| Format | JSON (`application/json; charset=utf-8`) for everything except file upload and download |
| Encoding | UTF-8; Persian text is sent as-is, not escaped |
| Methods | `GET` read, `POST` create/command, `PATCH` partial update, `PUT` full replace (rare), `DELETE` delete/deactivate |
| Idempotency | `POST` on money/order/refund/settlement endpoints requires `Idempotency-Key` (§6) |
| Concurrency | Mutable state changes accept `If-Match: "<lock_version>"`; a mismatch returns `409 state_conflict` |
| Traceability | Every response carries `X-Request-Id`; clients may send their own and it is echoed |
| Time | UTC ISO-8601 in every payload (§9) |
| Money | Integer Toman in `*_amount` fields (§10) |
| Auth | Laravel Sanctum bearer tokens (§7); admin uses a separate guard + mandatory 2FA |
| Authorisation | Policies and scopes on the server; a hidden button is never authorisation (canonical §5) |
| Documentation | OpenAPI 3.1 generated from route definitions and form requests; published by CI as a build artifact (never hand-edited) |

**Status codes used**

| Code | When |
|---|---|
| `200` | Successful read or command with a body |
| `201` | Resource created; `Location` header points at it |
| `202` | Accepted for async processing (export, bulk notification); `Location` points at the job |
| `204` | Successful command with no body (delete, mark-read) |
| `304` | `If-None-Match` matched on a cacheable read |
| `400` | Malformed JSON or unparsable query |
| `401` | Missing/expired token |
| `403` | Authenticated but not allowed (role, ownership, scope, state) |
| `404` | Resource absent **or** invisible to this actor (never leak existence) |
| `409` | State conflict, duplicate, idempotency reuse, concurrency |
| `422` | Validation or business-rule violation with field details |
| `423` | Account locked (brute force, 2FA required) |
| `429` | Rate limit; `Retry-After` and `X-RateLimit-*` headers |
| `500` | Unhandled server error (generic message + `request_id`) |
| `502`/`504` | Upstream gateway/provider failure |

---

## 2. Versioning and compatibility

1. The major version lives in the path: `/api/v1`. There is no `/api/v2` until a breaking change
   cannot be avoided.
2. **Additive changes are not breaking** and ship inside v1: new optional fields, new enum
   values **in responses only**, new endpoints, new optional query parameters, new error codes.
3. **Breaking changes** (removing/renaming a field, changing a type, making an optional field
   required, changing the meaning of a status, removing an enum value a client may send) require
   `/api/v2` or a deprecation window:
   - The old field/endpoint keeps working for **at least 90 days** after the announcement.
   - Deprecated responses carry `Deprecation: true` and `Sunset: <HTTP-date>` headers, plus a
     `Link: <...>; rel="deprecation"`.
   - Deprecations are recorded in `docs/api/deprecations.md` and announced in the changelog.
4. Clients must ignore unknown fields and unknown enum values in responses. The API never
   removes a field without the deprecation window.
5. Enum values sent by clients are validated strictly; unknown values return `422` with
   `details[].code = "enum"`. New enum values that a client may *send* are announced before
   they become mandatory.
6. `GET /api/v1/meta/version` returns `{ "api": "v1", "app": "1.4.2", "min_client": "1.2.0" }`
   so the PWA can force an update.

---

## 3. Resource naming and URL structure

1. Paths are **nouns, plural, kebab-free** (`snake_case` only where a compound is unavoidable;
   prefer `/sub-orders`? — no: paths use **kebab-case** for multiword resources,
   `/api/v1/sub-orders`, while JSON fields stay `snake_case`).
2. A resource path never contains a verb. State changes are sub-resources or explicit actions
   when the action is not a CRUD operation:
   - `POST /api/v1/me/orders/{order}/cancel` (command, documented as such)
   - `POST /api/v1/supplier/sub-orders/{sub_order}/accept`
   Verbs are limited to a closed list: `cancel`, `accept`, `reject`, `ship`, `deliver`,
   `confirm`, `approve`, `reject`, `hold`, `release`, `redeem`, `apply`, `remove`, `send`,
   `read`, `verify`. Anything else must be modelled as a resource.
3. Identifiers in paths are **public ids** (ULID `public_id`), never sequential database ids.
   Human document numbers (`order_number`, `invoice_number`) are accepted as an alternative
   lookup on detail endpoints, because that is what users have in hand.
4. Nesting depth is at most two levels. Deep relations get a top-level path with a filter:
   `/api/v1/orders/{order}/items` is acceptable; `/api/v1/orders/{o}/sub-orders/{s}/items/{i}`
   is not — use `/api/v1/sub-orders/{s}/items/{i}`.
5. Surface prefixes separate audiences and make authorisation obvious:

| Surface | Prefix | Who |
|---|---|---|
| Public storefront | `/api/v1/…` (no prefix) | Anonymous and any authenticated user |
| Customer | `/api/v1/me/…` | Authenticated user acting on their own data |
| Store panel | `/api/v1/store/…` | User with an active `store` role and an active store |
| Supplier panel | `/api/v1/supplier/…` | `supplier` role |
| Marketer panel | `/api/v1/marketer/…` | `marketer` role |
| Admin panel | `/api/v1/admin/…` | `admin` role, separate guard, 2FA |
| Auth | `/api/v1/auth/…` | Login, OTP, password reset, token management |
| Webhooks | `/api/v1/webhooks/…` | Gateway/carrier callbacks, signature-verified, never token-authenticated |

6. Casing: kebab-case paths, `snake_case` JSON keys, `lower_snake_case` enum values, ISO-8601
   dates. No exceptions.
7. Trailing slashes are not significant; the canonical form has none. A request with a trailing
   slash returns `308` to the canonical URL.

---

## 4. Requests

### 4.1 Headers

| Header | Required | Notes |
|---|---|---|
| `Authorization: Bearer <token>` | on protected routes | Sanctum personal access token |
| `Accept: application/json` | recommended | The API always answers JSON |
| `Accept-Language: fa` / `en` | optional | Localises `message` and validation text; default `fa` |
| `X-Display-Calendar: jalali` | optional | Adds `*_jalali` twin fields (§9) |
| `X-Request-Id` | optional | ULID; echoed back; stored on audit rows |
| `Idempotency-Key` | required on money/order commands | §6 |
| `If-Match` | optional on state changes | `"<lock_version>"` |
| `X-Client-Version` | optional | `web/1.4.2`, `pwa/1.4.2`; used for minimum-version enforcement |
| `X-Timezone` | optional | Display hint only; storage is UTC |

### 4.2 Body conventions

- `snake_case` keys; unknown keys are rejected with `422` (`details[].code = "unexpected"`) on
  write endpoints, so typos fail loudly.
- Booleans are JSON `true`/`false`, never `0`/`1`/`"true"`.
- Nullable fields: send `null` to clear, omit to leave unchanged on `PATCH`.
- Arrays are arrays, even with one element.
- Empty string is never a substitute for `null`.
- Enumerations are sent as their string code.
- Dates are ISO-8601 (§9); a Jalali date string is **rejected** with `422` and a hint.

### 4.3 Validation

- Validation lives in FormRequests; the response is always the error envelope with one
  `details[]` entry per failing field.
- Persian messages come from `lang/fa/validation.php`; the client shows `message` and may map
  `details[].code` for field-level display.
- Business rules that pass validation but fail policy (e.g. coupon expired between validation
  and commit) return `422` with `code = "business_rule_violation"` and a stable
  `details[].code` (`coupon_expired`, `insufficient_stock`, …).

---

## 5. Pagination

Two strategies, chosen by collection type. A response never mixes them.

### 5.1 Cursor pagination (default for feeds and large collections)

Used by: products, orders, sub-orders, notifications, conversations/messages, ledger rows,
classifieds, reviews, audit logs.

```
GET /api/v1/catalog/products?limit=24&cursor=eyJpZCI6MTIzNDUsInRzIjoiMjAyNi0xMS0yMFQxMDoxMjozM1oifQ
```

```json
{
  "data": [ ... ],
  "meta": {
    "per_page": 24,
    "next_cursor": "eyJpZCI6MTIzNDQsInRzIjoi..." ,
    "prev_cursor": null,
    "has_more": true
  },
  "links": {
    "next": "https://api.dekolife.ir/api/v1/catalog/products?limit=24&cursor=eyJ...",
    "prev": null
  }
}
```

- `limit` default `20`, max `100` (max `200` for admin exports of ids).
- The cursor encodes the last row's sort key + id, is opaque (base64url of JSON), and is
  **invalidated by changing filters** — a cursor with different filters returns `400
  invalid_cursor`.
- Cursor pagination never returns `total`.

### 5.2 Page pagination (admin tables and anything that shows a total)

```
GET /api/v1/admin/orders?page=2&per_page=50&sort=-placed_at
```

```json
{
  "data": [ ... ],
  "meta": {
    "current_page": 2,
    "per_page": 50,
    "last_page": 37,
    "total": 1832,
    "from": 51,
    "to": 100
  },
  "links": { "first": "...", "prev": "...", "next": "...", "last": "..." }
}
```

- `per_page` default `25`, max `100`.
- `page` beyond `last_page` returns `200` with an empty `data` array and correct `meta`.
- `total` is only present on page-paginated collections; computing it on a cursor feed is a
  performance bug.

### 5.3 Rules common to both

1. Collection responses are always wrapped in `data` (never a bare array) so metadata can be
   added without a breaking change.
2. Sort is **stable**: every ordering ends with `id` as a tiebreaker, so pages do not overlap
   or skip rows.
3. `meta` always includes `per_page`; `links` always includes `next` (nullable).

---

## 6. Idempotency

1. `Idempotency-Key` is **required** on:
   - `POST /api/v1/me/orders` (checkout)
   - `POST /api/v1/me/orders/{order}/pay`
   - `POST /api/v1/admin/payments/{payment}/refund`
   - `POST /api/v1/{store,supplier,marketer}/settlements`
   - `POST /api/v1/admin/settlements/{settlement}/approve|pay`
   - `POST /api/v1/me/wallet/topup`, `POST /api/v1/*/notifications/purchase`
   It is **accepted but optional** elsewhere on `POST` (harmless; stored for 24 h).
2. Format: 8–128 characters, `[A-Za-z0-9_-]`. A ULID or UUIDv4 is recommended.
3. Semantics:

   | Situation | Response |
   |---|---|
   | First request with this key | Executes; response stored with the key |
   | Repeat with same key **and** same body | Stored response replayed, `200`/`201` as recorded, `Idempotent-Replay: true` |
   | Repeat with same key, different body | `409 idempotency_key_reused` |
   | Repeat while the first is still in flight | `409 idempotency_in_progress` with `Retry-After: 2` |
   | Key older than the TTL (24 h; 30 days for payment capture) | Treated as a new request |

4. The key is scoped to the authenticated actor + route, so two users cannot collide.
5. Idempotency is not a substitute for unique business references: the ledger and payment
   references are unique regardless (§Data conventions 13).
6. Webhooks are deduplicated by provider event id, not by `Idempotency-Key`.

Example:

```http
POST /api/v1/me/orders HTTP/1.1
Authorization: Bearer <token>
Idempotency-Key: 01J9Z5Q7K8N4M3P2R1S0T9V8W7
Content-Type: application/json

{ "cart_id": "01J9Z5Q7K8N4M3P2R1S0T9V8W6", "shipping_method_id": 12, "payment_method": "online" }
```

---

## 7. Authentication and authorisation

### 7.1 Tokens

- Laravel **Sanctum** personal access tokens, `Authorization: Bearer <token>`.
- Issued by `POST /api/v1/auth/login` (mobile + password) or
  `POST /api/v1/auth/otp/verify` (SMS OTP, used for registration and passwordless login).
- One token per device; `POST /api/v1/auth/logout` revokes the current token,
  `POST /api/v1/auth/logout-all` revokes every token (requires password confirmation).
- Lifetime: idle expiry **30 days**, absolute expiry **90 days**; every authenticated response
  refreshes `last_used_at`. Expired tokens return `401 token_expired`.
- Token abilities mirror the surface, not the role: a token minted on the store panel carries
  `store:*` abilities. The role check is still performed server-side on every request — abilities
  are an additional gate, never the only one.
- Tokens are never placed in URLs or query strings.

### 7.2 Admin

- Separate guard (`admin`) and separate login endpoint (`POST /api/v1/admin/auth/login`).
- 2FA (TOTP) is mandatory: login returns `202` with `two_factor_required: true` and a short-lived
  challenge token; `POST /api/v1/admin/auth/2fa/verify` exchanges it for a session token.
- Admin tokens are shorter-lived: idle 8 hours, absolute 24 hours.
- Admin routes are additionally restricted by IP allow-list in production (config-driven) and
  every request writes an audit row for sensitive actions.

### 7.3 Roles and scopes

| Surface | Role required | Additional scope |
|---|---|---|
| `/me/*` | any authenticated | own resources only (`user_id = auth.id`) |
| `/store/*` | `store` | own store id; own catalog, sub-orders, invoices |
| `/supplier/*` | `supplier` | own supplier id; own catalog, stock, sub-orders |
| `/marketer/*` | `marketer` | own network: visited stores, own attributions, own commissions |
| `/admin/*` | `admin` | permission key per endpoint (`orders.view`, `settlements.approve`, …) |

Rules:

1. Every scoped resource is resolved through a policy that checks **ownership**, not just role.
   A store user requesting another store's sub-order gets `404` (not `403`) to avoid leaking
   existence, except for admin surfaces where `403` is correct.
2. Authorisation failures are logged at `warning` with actor, route and target id.
3. Impersonation (`admin` "view as user") is read-only by policy and marked in the audit log.
4. Secondary roles are honoured: a user holding `marketer` + `consumer` may use both surfaces
   with the same token.
5. Suspended/blocked accounts get `403 account_suspended` on every authenticated route, and
   their tokens are revoked on suspension.

---

## 8. Rate limiting

Limits are per authenticated user (or per IP for anonymous traffic), applied with Laravel's
`RateLimiter`, and returned in headers on every response:

```
X-RateLimit-Limit: 120
X-RateLimit-Remaining: 87
X-RateLimit-Reset: 1764153600
Retry-After: 12          # only on 429
```

| Class | Examples | Limit |
|---|---|---|
| Auth: login | `POST /auth/login` | 5 / minute / IP + 10 / hour / mobile |
| Auth: OTP request | `POST /auth/otp/request` | 3 / 5 minutes / mobile, 10 / hour / IP |
| Auth: OTP verify | `POST /auth/otp/verify` | 10 / 10 minutes / mobile |
| Auth: password reset | `POST /auth/password/forgot` | 3 / hour / mobile |
| Public read | product/category/store listing, search | 120 / minute / IP |
| Authenticated read | `/me/*` lists, panels | 300 / minute / user |
| Writes (non-money) | profile, addresses, favourites, cart | 60 / minute / user |
| Money/checkout | checkout, pay, refund, settlement | 20 / minute / user |
| File upload | `POST /…/media` | 30 / minute / user, 20 MB/min |
| Messaging | send message, ticket reply | 60 / minute / user |
| Admin | any `/admin/*` | 600 / minute / user |
| Webhooks | gateway/carrier callbacks | 600 / minute / provider (signature-verified) |
| Exports | `POST /admin/exports` | 5 / hour / user |

Rules:

1. `429` responses include `Retry-After` and the standard error envelope with
   `code = "rate_limited"`.
2. Captcha is required on anonymous registration, contact form and repeated OTP requests
   (appendix 9 clause 3); a failed captcha returns `422 captcha_failed`.
3. Repeated `401`/`403` from one IP feed the brute-force detector and can lock the IP for
   15 minutes.
4. Limits are configuration (`config/api.php` → `rate_limits`), not hardcoded, so the owner can
   raise them without a deploy; changes are audited.

---

## 9. Dates, times and calendars

1. **Every timestamp is ISO-8601 with an explicit UTC offset**: `2026-11-25T09:30:00Z` or
   `2026-11-25T09:30:00.123Z` for millisecond precision. Never a bare `Y-m-d H:i:s`, never a
   Jalali string, never a local time without an offset.
2. **Date-only fields** (`birth_date`, `period_start`, invoice `issue_date`) are `YYYY-MM-DD`
   in the **Gregorian** calendar on the wire. Jalali is a display concern.
3. On request (`X-Display-Calendar: jalali` or `?jalali=1`), every timestamp field gains a twin:

   ```json
   {
     "placed_at": "2026-11-25T09:30:00Z",
     "placed_at_jalali": "1405-09-04 13:00",
     "placed_at_display": "۴ آذر ۱۴۰۵، ۱۳:۰۰"
   }
   ```

   Twins are additive and never replace the ISO field. `*_jalali` is `YYYY-MM-DD HH:mm` in
   `Asia/Tehran`.
4. Timestamps in requests must be ISO-8601 with an offset; a Jalali or offset-less string is
   rejected with `422 invalid_datetime`.
5. Filter ranges use `filter[placed_at][gte]` / `[lte]` with ISO-8601 values, interpreted as
   inclusive.
6. Durations are integers in seconds with a `_seconds` suffix (`duration_seconds`,
   `hold_seconds`), never human strings.
7. Timezones are never accepted from the client for storage; `X-Timezone` only affects display
   helpers returned in `*_display` fields.
8. Business-day arithmetic (SLA, "3 working days") is computed in `Asia/Tehran` with the
   official holiday calendar from `adm_settings`; responses expose the resulting UTC instant.

---

## 10. Money and quantities

1. **Money is an integer number of Toman.** There are no decimals, no floats, no strings for
   arithmetic. `{"amount": 1250000}` means 1,250,000 Toman.
2. Money fields are named `*_amount`, always paired with a `currency` field at the object level
   (`"currency": "IRT"`). A single-currency deployment still sends it, so a future change is not
   breaking.
3. Display helpers are separate, read-only strings:

   ```json
   {
     "unit_price_amount": 1250000,
     "unit_price_display": "۱٬۲۵۰٬۰۰۰ تومان",
     "currency": "IRT"
   }
   ```

   Clients must never parse `*_display` for arithmetic.
4. Percentages are sent as decimal numbers with up to three decimals (`4.5` means 4.5 %), field
   suffix `_percent`. They are never applied client-side.
5. **The client never sends a price, a discount or a total.** Requests carry product/variant
   ids, quantities, coupon codes and shipping method ids; the server resolves every amount and
   returns the breakdown.
6. Every order/invoice response includes the full breakdown so the UI can display it without
   recomputing:

   ```json
   {
     "items_total_amount": 5000000,
     "discount_total_amount": 250000,
     "coupon_discount_amount": 100000,
     "loyalty_discount_amount": 150000,
     "shipping_total_amount": 120000,
     "tax_total_amount": 0,
     "grand_total_amount": 4870000,
     "paid_amount": 0,
     "wallet_applied_amount": 0,
     "balance_due_amount": 4870000,
     "currency": "IRT"
   }
   ```
7. Quantities are numbers with up to three decimals (`12.5` m²), field suffix `_quantity`.
   They respect the product's `step_quantity` and `min_order_quantity`; a violation is `422`.
8. Rounding is server-side, half-up at the line level, with the allocation residual assigned to
   the largest line (conventions §9). Clients that recompute will disagree by at most a few
   Toman — they must not.
9. Negative amounts appear only in ledger/adjustment contexts (`direction` + positive
   `amount`, or `adjustment_amount`), never in an order total.
10. Reporting endpoints may include `*_amount` aggregates larger than 32-bit; clients must use
    64-bit integers (JavaScript: `BigInt` or a string-typed field when above `2^53`; the API
    sends `total_amount` as a number and `total_amount_string` alongside aggregates that can
    exceed it).

---

## 11. Errors

Every non-2xx response uses exactly this envelope:

```json
{
  "error": {
    "code": "insufficient_stock",
    "message": "موجودی کافی برای این محصول وجود ندارد.",
    "request_id": "01J9Z5Q7K8N4M3P2R1S0T9V8W7",
    "details": [
      {
        "field": "items.0.quantity",
        "code": "max_available",
        "message": "حداکثر ۳ عدد موجود است."
      }
    ]
  }
}
```

| Member | Rule |
|---|---|
| `code` | Stable machine code, `lower_snake_case`, never localised, never changed once published |
| `message` | Human message in `Accept-Language` (Persian by default); safe to display |
| `request_id` | ULID matching `X-Request-Id`; the support handle for any incident |
| `details` | Always an array (empty `[]` when there is nothing field-specific); each entry may carry `field`, `code`, `message` and, where useful, `meta` (e.g. `{"available": 3}`) |

Rules:

1. `details` never contains stack traces, SQL, file paths, provider text or PII.
2. `422` always has at least one `details` entry.
3. `500` returns `{"code":"internal_error","message":"خطای غیرمنتظره…"}` — the real cause goes
   to the log with the same `request_id`.
4. Provider/gateway failures return `502 upstream_failure` with a generic message and a
   `details[].code` naming the provider class (never the provider's raw text).
5. The full catalogue with HTTP mapping and Persian keys is
   [`error-model.md`](./error-model.md).

---

## 12. File uploads

1. **Two-step upload for anything a user waits on** (product images, identity documents,
   portfolio):
   - `POST /api/v1/me/media` with `multipart/form-data` returns `201`
     `{ "id": 901, "uuid": "01J9…", "url": "https://…signed…", "mime_type": "image/jpeg",
        "size_bytes": 845123, "width": 1600, "height": 1200, "expires_at": "…" }`.
   - The returned `uuid` is then attached: `POST /api/v1/store/products/{id}/media`
     `{ "media_uuid": "01J9…", "kind": "gallery", "alt_text": "…" }`.
   This keeps large bodies out of business endpoints and lets the client show progress.
2. **Inline upload** is allowed only for small, single-purpose files (voice note on an order,
   chat attachment) via `multipart/form-data` on the owning endpoint.
3. Limits and types:

   | Kind | Max size | Allowed MIME | Notes |
   |---|---|---|---|
   | Product/gallery image | 8 MB | `image/jpeg`, `image/png`, `image/webp` | Re-encoded to WebP, max 2400 px long edge |
   | Identity document | 10 MB | `image/jpeg`, `image/png`, `application/pdf` | Private disk, signed URL, 5-minute TTL |
   | Invoice PDF | — | generated server-side | Never accepted from a client |
   | Voice note | 5 MB / 120 s | `audio/mpeg`, `audio/ogg`, `audio/wav` | Duration validated server-side (appendix 5 clause 41) |
   | Chat attachment | 15 MB | images, `application/pdf`, office docs, audio | Virus-scanned |
   | Video (product/supplier) | 100 MB | `video/mp4`, `video/webm` | Async transcode; `202` until ready |
   | Catalogue/price Excel | 20 MB | `application/vnd.openxmlformats-officedocument.spreadsheetml.sheet` | Import batch, validated row by row |
4. Security: extension is derived from the detected MIME (never trusted from the filename);
   files are stored under a random UUID path; SVG is rejected (XSS vector); images are
   re-encoded to strip EXIF; every upload is virus-scanned before `shr_media.virus_scanned_at`
   is set and before the file can be attached.
5. Downloads: public media are served from the CDN with long cache headers; private media are
   served **only** through signed, short-lived URLs (`5–15 min`) issued per request and
   authorised per viewer. A raw `/storage/...` path is never exposed for private files.
6. Replacing an image creates a new `shr_media` row; the old row is soft-deleted, so cached
   URLs keep working until their TTL.
7. Upload failures return `422` with `details[].field` pointing at the file part and a
   `details[].code` from the upload catalogue (`file_too_large`, `mime_not_allowed`,
   `virus_detected`, `image_corrupt`, `duration_too_long`).
8. Total storage per merchant is capped by plan/settings; exceeding it returns
   `422 storage_quota_exceeded` with the remaining allowance in `details[].meta`.

---

## 13. Filtering, sorting and search

### 13.1 Filtering

```
GET /api/v1/me/orders?filter[status]=paid&filter[placed_at][gte]=2026-09-01T00:00:00Z&filter[payment_status]=paid
```

- The `filter` object is a whitelist per endpoint; an unknown key returns `422
  unknown_filter`.
- Operators are expressed as a nested object: `[gte]`, `[lte]`, `[gt]`, `[lt]`, `[in]`
  (comma-separated), `[not]`, `[between]` (comma-separated pair), `[like]` (prefix search only).
- Multi-value: `filter[status][in]=paid,processing`.
- Boolean filters use `1`/`0`: `filter[has_variants]=1`.
- Range filters on money take integers (`filter[total_amount][gte]=1000000`).
- Every filter is indexed or explicitly documented as a scan (admin-only, capped).

### 13.2 Sorting

- `sort=field` ascending, `sort=-field` descending, multiple: `sort=-placed_at,id`.
- Whitelisted per endpoint (`sort=-placed_at`, `sort=price_amount`, `sort=-rating_avg`,
  `sort=title_fa`). Unknown field → `422 unknown_sort`.
- Ties are broken by `id` automatically; clients need not send it.

### 13.3 Search

- Free text: `q=` on catalog, classifieds, patogh, admin search. Backed by a `FULLTEXT` index
  with the ngram parser for Persian; `q` shorter than 2 characters returns `422
  query_too_short`.
- Faceted search uses explicit filters (`filter[attribute][color]=...`,
  `filter[price][between]=...`), never a query-string DSL.
- Suggestions: `GET /api/v1/catalog/search/suggest?q=پار` returns max 10 terms, 60 s cache.

### 13.4 Sparse fields and includes

- `fields[products]=id,title_fa,slug,price` limits payload fields (whitelisted; `id` always
  returned).
- `include=brand,category,media` expands documented relations, max depth 1, max 3 includes per
  request. Unknown include → `422 unknown_include`.
- Both are optimisations; omitting them returns the documented default representation.

---

## 14. Response shapes

1. A single resource is returned **unwrapped**: `{ "id": …, "title_fa": … }`.
2. A collection is wrapped: `{ "data": [...], "meta": {...}, "links": {...} }`.
3. Commands return the affected resource (or a purpose-built summary), never just
   `{"success": true}`. Example: `POST /me/orders/{order}/cancel` returns the order with its new
   status and `cancellation` object.
4. A `202` returns `{ "data": { "job_id": "...", "status": "queued" } }` plus `Location`.
5. `204` returns no body at all.
6. Field names are `snake_case`; nested objects keep the same convention.
7. ID fields are named `<entity>_id` and carry the **internal** id only where the client needs
   it (filters, relations); the public identifier is `public_id` and is what appears in URLs.
8. Every object that can be edited carries `lock_version` so the client can send `If-Match`.
9. Every list item carries the minimum needed to render itself; detail endpoints carry the rest.
   Avoid returning a 200-field product in a list of 24.

---

## 15. Caching and conditional requests

- Public catalog reads send `ETag` and `Cache-Control: public, max-age=60, stale-while-revalidate=300`.
- Authenticated reads send `Cache-Control: private, no-store` (order, wallet, personal data).
- `GET` with `If-None-Match` returns `304` when unchanged.
- Mutations invalidate the affected collection tags (`catalog:products`, `orders:{user}`).
- Never cache a private resource on a shared CDN.

---

## 16. Webhooks and callbacks

| Rule | Detail |
|---|---|
| Path | `/api/v1/webhooks/{provider}` (`zarinpal`, `saman`, `behpardakht`, `post`, `tipax`, …) |
| Auth | Signature verification (HMAC or provider-specific); no bearer token |
| Idempotency | Deduplicated on `(provider, event_id)`; replay returns `200` with `{"status":"duplicate"}` |
| Response | Always `200` quickly; processing happens in a queued job; a `5xx` makes the provider retry |
| Body storage | Raw body stored redacted in `pay_webhook_events` for reconciliation |
| Ordering | Out-of-order events are tolerated; the payment/shipment state takes the highest-progress event |
| Security | Invalid signature → `401`, logged with the source IP, never applied |

Carrier tracking webhooks follow the same rules and write `shp_shipment_events`.

---

## 17. Documentation and contract testing

1. The OpenAPI 3.1 document is generated in CI from routes, FormRequests and API Resources;
   a hand-written spec that drifts is not accepted.
2. Every endpoint in [`endpoint-inventory.md`](./endpoint-inventory.md) must exist in the
   generated spec, with its auth class and a response schema.
3. Contract tests (`tests/Contract/`) assert: envelope shape, status codes, pagination meta,
   error `code` stability, and that no response leaks a field marked internal.
4. A snapshot of each response schema is committed; a diff fails the build unless the PR
   declares the change as additive or deprecating.
5. Persian example payloads live in `docs/api/examples/` and are used by the frontend mocks.

---

## 18. Checklist for a new endpoint

- [ ] Path follows §3 and lives on the correct surface prefix
- [ ] Method matches semantics; no verb unless in the closed list
- [ ] Policy exists and has a negative test (other user's resource → `404`/`403`)
- [ ] FormRequest validates every input; unknown keys rejected
- [ ] `Idempotency-Key` required if it moves money or creates an order
- [ ] Response uses the documented shape; money as integer Toman; timestamps ISO-8601 UTC
- [ ] Errors use the envelope and a code from the catalogue
- [ ] Rate limit class assigned
- [ ] Pagination strategy chosen and documented
- [ ] Audit row written if the action is sensitive
- [ ] OpenAPI regenerated; contract test updated
- [ ] Persian `message` keys added to `lang/fa/errors.php`
