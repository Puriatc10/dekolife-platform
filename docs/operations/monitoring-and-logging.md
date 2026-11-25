# Monitoring and Logging — Dekolife Platform

**Purpose:** Define the log channels, the request-id propagation rule, the error envelope, the health endpoints, queue and worker monitoring, slow-query and N+1 detection, the audit-log review routine, the alert list, and the data that must never be logged.
**Audience:** Platform agent, module owners (their logging decisions), the on-call operator, the reviewer (point 8 of the DoD).
**Status:** Baseline v1 (Phase 0). Channels and health endpoints land in Phase 0; queue, audit and alerting wiring lands with the modules they observe.
**Source:** `پیوست ۱` بند ۱۰ (audit of sensitive events: login, price change, role change, settlement), `پیوست ۹` بند ۳ (security: brute-force prevention, input validation, access control), `ماده ۱۲` بند ۱ (support SLAs require fast diagnosis), `ماده ۴` بند ۵ (a silent failure that drops an appendix heading is a penalty, so failure must be visible). Engineering baseline: `docs/canonical-context.md` §5 (audit trait, UTC storage, no Redis in v1).

---

## 1. Principles

1. **Every log line is correlated.** A request id is generated or accepted at the edge and travels through the web request, the queue job, the scheduled command and the outbound message record. Without it, an incident cannot be reconstructed across processes.
2. **Logs are for diagnosis, the audit log is for accountability.** Application logs are ephemeral and rotated; `adm_audit_logs` is durable, structured, and never deleted. They are different systems with different retention and different access rules.
3. **Money paths are observable by default.** Every payment, ledger command and settlement run emits a structured event with the reference, the amount and the outcome — never the card data or the gateway payload.
4. **No PII in logs.** §9 is a hard list, enforced in review and by a log-scrubbing processor.
5. **Alerts must be actionable.** An alert that does not map to a runbook section in `runbooks.md` is noise and gets deleted, not muted.

---

## 2. Log channels

Laravel `config/logging.php`, one channel per concern. All channels use the JSON formatter in staging and production and the single-line formatter locally.

| Channel | Driver | Path / target | Level | Retention | Used for |
|---|---|---|---|---|---|
| `stack` | stack of `daily` + `stderr` | `storage/logs/laravel-YYYY-MM-DD.log`, plus container stderr | `debug` local, `info` prod | 14 days (`LOG_DAILY_DAYS`) | application default |
| `stderr` | `errorlog`/`stream` | container stderr → Docker log driver | `info` | driver-defined | container-native collection; what the platform's log shipper reads |
| `security` | `daily` | `storage/logs/security-YYYY-MM-DD.log` | `info` | 90 days | auth failures, lockouts, 2FA events, permission denials, suspicious uploads |
| `payments` | `daily` | `storage/logs/payments-YYYY-MM-DD.log` | `info` | 90 days | payment intents, callback verification outcomes, refund commands, reconciliation results (amounts and references, never card data) |
| `queue` | `daily` | `storage/logs/queue-YYYY-MM-DD.log` | `warning` | 30 days | job failures, retries exhausted, worker restarts |
| `audit` | database | `adm_audit_logs` | — | permanent | sensitive business events via `App\Shared\Audit\AuditLogger` |
| `slow` | `daily` | `storage/logs/slow-YYYY-MM-DD.log` | `warning` | 30 days | queries over the threshold, with the route name and the request id |

Rules: no `Log::info` of a whole model or request payload; log identifiers and decisions, not objects. `LOG_LEVEL` is `debug` only in local. Each channel that is written by more than one module is declared in `config/logging.php` (owned by the platform track) so a module never invents its own file.

### 2.1 Structured fields (every production line)

```json
{
  "ts": "2026-11-25T13:30:12.481Z",
  "level": "info",
  "channel": "payments",
  "env": "production",
  "request_id": "01JD8Z3Q7K9V2M4T6R8X0B1C2D",
  "actor_id": 4821,
  "actor_role": "store",
  "route": "payments.callback",
  "event": "payment.callback.verified",
  "reference": "PAY-2026-000123",
  "amount": 4500000,
  "gateway": "sandbox",
  "duration_ms": 143,
  "message": "callback verified and payment captured"
}
```

Field rules: `ts` in UTC ISO-8601 with milliseconds; `request_id` always present; `actor_id` null for anonymous (never an email or phone); `amount` integer Toman with no separators; `event` is a stable dotted name (`domain.subject.verb`) that dashboards can group on; `message` is a short human sentence in English.

---

## 3. Request-id propagation

```
Browser/API client
   │  X-Request-Id: <optional, must match ^[A-Za-z0-9_-]{8,64}$>
   ▼
nginx  ── adds $request_id if the header is absent or malformed ──►  php-fpm
   ▼
App\Shared\Http\RequestId middleware
   • stores the id in the container and in Log::withContext(['request_id' => …])
   • replaces any client-supplied id that fails the pattern (never trust the client)
   • returns the id in the response header X-Request-Id
   • puts it in the error envelope
   ▼
Queue job payload: {"request_id": "…"}  →  job restores the context on start
Scheduled command: generates a run id and logs it as request_id with actor_id=null
Outbound message (SMS/email/notification): msg_notifications.request_id column
```

**nginx snippet**

```nginx
map $http_x_request_id $req_id {
    default    $http_x_request_id;
    ""         $request_id;
    "~*[^A-Za-z0-9_-]" $request_id;   # malformed → replace
}
add_header X-Request-Id $req_id always;
fastcgi_param HTTP_X_REQUEST_ID $req_id;
```

**Verification.** `GET /health/ready` returns the id; a feature test asserts that a request without the header still produces one, that a malformed header is replaced, and that the same id appears in the response header and in the error envelope.

---

## 4. Error envelope

One shape for every JSON response, web and API (`App\Shared\Http\ApiResponse`):

```json
{
  "data": null,
  "meta": { "request_id": "01JD8Z3Q7K9V2M4T6R8X0B1C2D", "pagination": null },
  "errors": [
    { "code": "payment_gateway_timeout", "field": null, "message": "درگاه پرداخت پاسخ نداد. دوباره تلاش کنید." }
  ]
}
```

| Rule | Detail |
|---|---|
| `code` | stable machine-readable snake_case; documented in the API reference; the client handles codes, not messages |
| `message` | Persian, user-safe, no stack trace, no SQL, no class names |
| `field` | present for validation errors, null otherwise |
| `meta.request_id` | always present; it is what support asks the customer for |
| HTTP status | validation 422, unauthenticated 401, unauthorized 403, not found 404, conflict/state 409, throttled 429, server 500 |
| 500 bodies | never include the exception message in production; the exception is logged with the `request_id` and the client sees a generic code |
| Blade errors | the HTML error page shows the `request_id` in a footer; the JSON envelope is used for all Livewire/AJAX calls |

A test asserts the envelope for 401/403/404/422/429/500 on a representative route per module, so a module cannot invent its own error shape.

---

## 5. Health endpoints

Three endpoints, no authentication, no side effects, excluded from request logging noise (they are logged at `debug`).

| Endpoint | Purpose | Checks | Failure means |
|---|---|---|---|
| `GET /health/live` | liveness — is the process up | PHP responds; returns `{"status":"ok"}` | the container is dead; restart it |
| `GET /health/ready` | readiness — can it serve traffic | DB `SELECT 1`; migrations table reachable and no pending migration; cache write/read; storage writable; queue table reachable; `APP_KEY` present | do not route traffic; a deploy is incomplete or a dependency is down |
| `GET /health/deep` | diagnostics (internal only, restricted by IP or basic auth) | ready checks plus: queue backlog age, failed-jobs count, last successful backup timestamp, binlog lag, disk free %, gateway sandbox reachability | used by the operator and by the alert rules |

`/health/ready` returns 503 with a JSON body listing which check failed:

```json
{"status":"fail","checks":{"db":"ok","migrations":"pending:3","cache":"ok","storage":"fail:not writable","queue":"ok"},"request_id":"…"}
```

**Never** expose `/health/deep` publicly: it reveals infrastructure shape. **Never** put a health check behind auth: the load balancer cannot log in.

---

## 6. Queue and worker monitoring

The database queue driver (`docs/canonical-context.md` §2) means the queue is visible in SQL, which is convenient and also the reason it must be watched — a stopped worker looks exactly like a quiet system.

| Signal | Query / command | Healthy | Alert at |
|---|---|---|---|
| Backlog depth | `SELECT COUNT(*) FROM jobs` | < 50 | > 500 for 5 min |
| Oldest pending job age | `SELECT TIMESTAMPDIFF(SECOND, MIN(available_at), NOW()) FROM jobs` | < 60 s | > 600 s |
| Failed jobs | `php artisan queue:failed` / `SELECT COUNT(*) FROM failed_jobs` | 0 new per hour | any new failure in `payments` or `notifications` |
| Worker heartbeat | a `queue:work --once` style heartbeat row written by a scheduled command every minute | fresh within 90 s | missing two beats |
| Long-running job | job runtime over `--timeout` | none | any timeout, twice in an hour |
| Scheduled command heartbeat | `schedule:run` writes `shr_heartbeats` rows | fresh within 5 min of the expected slot | missing a slot |
| Notification fan-out | `notifications` queue depth and per-batch completion | draining | batch stalled > 10 min (runbook §10) |

**Worker topology.** `queue:work --queue=payments,default,notifications --tries=3 --timeout=90 --max-time=3600` under supervisor, at least two workers in production so a stuck job cannot block payments. Payment callbacks that need immediate handling are processed **synchronously in the request** after verification, and the queue only carries side effects (notifications, reports) — this keeps a queue stall from freezing money.

**Cron supervision.** `php artisan schedule:work` is development-only; production uses a single cron entry `* * * * * cd /var/www && php artisan schedule:run >> /dev/null 2>&1` plus `schedule:list` reviewed at each release, because a duplicate cron entry double-runs settlements.

---

## 7. Slow-query and N+1 detection

### 7.1 Slow queries

MySQL slow log is enabled in staging and production:

```ini
slow_query_log = 1
slow_query_log_file = /var/log/mysql/slow.log
long_query_time = 1
log_queries_not_using_indexes = 0
min_examined_row_limit = 1000
```

The application additionally listens to `QueryExecuted` and writes to the `slow` channel when a query exceeds **300 ms**, including the route name, the `request_id` and the SQL with bindings replaced by types (not values, to avoid leaking PII).

Weekly review: `pt-query-digest /var/log/mysql/slow.log` (or `mysqldumpslow -s t`) for the top 10 by total time; each item gets either an index, a query change, or a written decision that it is acceptable. Money-path queries (ledger, settlement, commission) are reviewed on every release, not weekly.

### 7.2 N+1 detection

- `Model::preventLazyLoading(! app()->isProduction())` in `AppServiceProvider` — staging and test throw on a lazy load, which turns an N+1 into a failing test instead of a slow page.
- CI runs the feature suite with that flag on, so an N+1 cannot merge (DoD point 4 and the release gate in `definition-of-done.md` §2.3).
- A Blade/Livewire view that queries inside a loop is caught by the same flag when the view is rendered in a feature test; the release checklist requires at least one render test per list screen.
- Debugbar/Telescope are **local-only** and never enabled in staging or production (they record payloads, which conflicts with §9).

### 7.3 Performance budgets

| Page | p95 budget | Measured by |
|---|---|---|
| Catalog list with filters | 400 ms | request timing in the `slow` channel + the acceptance run |
| Product detail | 300 ms | same |
| Checkout submit | 800 ms | same |
| Admin settlement report | 1500 ms (paginated, aggregated) | same |
| Invoice PDF render | 1200 ms | job timing in the `payments` log |

A budget breach in staging blocks the release (`definition-of-done.md` §2.3); a breach in production opens an alert candidate below.

---

## 8. Alert candidates

Each alert names its runbook so the responder is never improvising.

| # | Alert | Condition | Severity | Runbook |
|---|---|---|---|---|
| A-01 | Site down | `/health/live` fails twice in 60 s, or 5xx rate > 2 % for 5 min | P1 | `runbooks.md` §1, §11 |
| A-02 | Readiness failing | `/health/ready` 503 (DB, storage, pending migration) | P1 | §1 |
| A-03 | Payment callback failures | > 3 verified-callback failures in 10 min, or payment success rate < 90 % for 15 min | P1 | §5, §6 |
| A-04 | Gateway outage | sandbox/live gateway health probe fails 3× consecutively | P1 | §6 |
| A-05 | Queue backlog | `jobs` > 500 or oldest job > 600 s | P2 | §4 |
| A-06 | Job failures | any `failed_jobs` row in `payments`/`notifications`, or > 10 rows total in an hour | P2 | §4 |
| A-07 | Backup failure | backup job exit ≠ 0, dump < 1 MiB, checksum mismatch, or no new dump in 26 h | P1 | §7 |
| A-08 | Binlog lag / gap | shipping lag > 15 min, or a sequence gap | P1 | §7 |
| A-09 | Disk full | free < 15 % on the data volume, < 5 % = page | P1 | §9 |
| A-10 | Certificate expiry | TLS certificate expires in < 14 days | P2 | §8 |
| A-11 | Slow query spike | > 20 queries over 1 s in 5 min | P3 | §4, §6 |
| A-12 | Auth abuse | > 50 failed logins per IP in 5 min, or > 200 across the site | P2 | §11 |
| A-13 | Settlement exception | a settlement run posts an unbalanced ledger sum, or a payout exceeds its eligibility | P1 | §4, §11 |
| A-14 | Notification storm | a campaign exceeds its configured recipient or per-minute cap | P2 | §10 |
| A-15 | Scheduler missed | a scheduled command slot missed by > 5 min | P2 | §4 |

Delivery: to the operator's channel (email + chat webhook). P1 pages; P2 notifies during working hours and is re-checked at the next cycle; P3 is a weekly digest. Alert thresholds are versioned here so they are reviewable, not buried in a dashboard.

---

## 9. What must never be logged

Hard list. A PR that logs any of these is rejected at DoD point 8, regardless of level.

| Never log | Why | Instead |
|---|---|---|
| Passwords, password hashes, password-reset tokens | credential disclosure | log the user id and the event |
| Sanctum/API tokens, session ids, CSRF tokens, 2FA secrets and OTP codes | replay/2FA bypass | log `token_id` prefix only if needed, or just the actor id |
| Card numbers, CVV, expiry, full bank account / SHEBA with account holder name | PCI/PII exposure | log the gateway reference and the last 4 digits at most |
| Full national ID (کد ملی), full postal code with address, date of birth | personal data (`پیوست ۹` بند ۳) | log a masked form (`***4567`) only when a support case requires it |
| Identity-document file contents or signed URLs | durable credential in a text file | log the file id and the storage disk |
| Raw gateway request/response payloads | contain card and customer data | log `gateway`, `reference`, `status`, `amount`, `error_code` |
| SMS/WhatsApp provider payloads with the recipient number | PII + provider secrets | log `message_id`, `template`, `channel`, `status` |
| Full request bodies or `$request->all()` | mixes every category above | log validated field names, not values |
| `.env` values, `APP_KEY`, DB passwords, API keys | catastrophic | log the key name and whether it is set |
| Stack traces containing SQL with bound values | leaks PII and schema | log the exception class + a scrubbed message; bindings replaced by types |
| Customer addresses, phone numbers, emails in free-text messages | PII | reference the entity id |

**Enforcement.** A `ScrubSensitiveData` monolog processor masks anything matching the national-ID, SHEBA, phone and 16-digit-card patterns as a second line of defence; review is the first. The processor is tested with a fixture containing each category, and the test asserts the masked output — because a scrubber nobody tests is a false sense of safety.

---

## 10. Audit log review

`adm_audit_logs` is written only through `App\Shared\Audit\AuditLogger` (never by hand) and is append-only.

| Field | Content |
|---|---|
| `actor_id`, `actor_role` | who; null for system jobs with a named `system` pseudo-actor |
| `event` | `auth.login`, `auth.login_failed`, `role.changed`, `price.changed`, `settlement.approved`, `identity.data_changed`, `order.state_changed`, `payout.executed` |
| `subject_type`, `subject_id` | the affected record |
| `old_values`, `new_values` | JSON diff, money as integers, no secrets |
| `ip`, `user_agent`, `request_id` | correlation |
| `created_at` | UTC |

**Routine**

| Cadence | Review | Look for |
|---|---|---|
| Daily (automatic digest) | count by event; list of `role.changed`, `price.changed`, `settlement.approved` | unexpected role grants, bulk price edits, settlements outside the period |
| Weekly | the digest plus `auth.login_failed` spikes by IP | credential stuffing (feeds A-12) |
| Per settlement run | every `payout.executed` row against its `PayoutEligibility` decision | payouts on held or returned sub-orders |
| Per release | confirm the new sensitive events are covered by a test | a feature that ships without audit is a compliance gap |

The audit screen in the admin panel (Phase 9) is read-only with filters by actor, event and date; **no** delete or edit affordance exists, by design.

---

## 11. Dashboards (minimum set)

| Dashboard | Panels |
|---|---|
| Service health | live/ready status, 5xx rate, p95 by route group, request volume |
| Money | payments initiated/captured/failed, callback verification outcomes, ledger imbalance (must be 0), settlements per period, refunds |
| Queue | backlog depth, oldest job age, failures by queue, worker heartbeats |
| Data safety | last backup timestamp and size, last restore-drill date and duration, binlog lag |
| Growth | marketer QR scans, store registrations, commission runs, points issued vs consumed |
| Security | failed logins by IP, lockouts, 2FA challenges, permission denials |

Dashboards read from logs and from SQL views; they are not a source of truth and must never be used to compute money owed — that is the ledger's job.

---

## 12. Verification checklist for this document

Before a module's PR is merged, the reviewer confirms:

- [ ] every log line in the diff has a `request_id` in context (via `Log::withContext` or the job payload);
- [ ] no line logs a field from §9;
- [ ] new failure paths return the error envelope, not a bare `abort()` or a custom JSON shape;
- [ ] new queue jobs carry the request id and are idempotent;
- [ ] new scheduled commands write a heartbeat row;
- [ ] new external integration has a health probe and an alert candidate, or a written reason why not;
- [ ] if a sensitive business event was added, it is written through `AuditLogger` and has a test.
