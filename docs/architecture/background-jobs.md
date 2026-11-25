# Background Jobs and Scheduler

| | |
|---|---|
| **Purpose** | Enumerate every asynchronous job and scheduled command, with queue, cadence, idempotency key, retry policy and failure behaviour — so that "what runs at 3 a.m. and what happens if it fails" is answerable without reading the code. |
| **Audience** | Backend engineers, the operator on call, QA writing time-travel tests. |
| **Status** | Baseline v1.0. Cadences marked *(config)* are admin settings with the shown default. |
| **Owner** | Agent C (architecture). |

**Source:** contract `DK-1405-01` page 5 / پیوست ۱ clause 9 (daily automatic database backup with restore); page 6–7 / پیوست ۴ clauses 6, 8, 20, 23; page 10 / پیوست ۱۱ clauses 6, 15; page 11 / پیوست ۱۳ clauses 2, 3; `docs/canonical-context.md` §2 (database queue), §5.

---

## 1. Queue topology

| Queue | Purpose | Workers | Priority |
|---|---|---|---|
| `payments` | capture confirmation, refunds, reconciliation | 2 | highest |
| `default` | domain-event listeners, accruals, order maintenance | 3 | normal |
| `notifications` | SMS/WhatsApp/email/push fan-out and delivery | 2 | normal |
| `reports` | Excel/CSV generation, large exports, statements | 1 | low |
| `maintenance` | backups, cleanup, verification jobs, sitemap | 1 | low |

Configuration (`config/queue.php`): `database` driver, `retry_after=180`, `after_commit=true` for all dispatched jobs. Workers:

```
php artisan queue:work --queue=payments,default,notifications,reports,maintenance \
  --tries=3 --timeout=120 --max-jobs=500 --max-time=3600
```

Two worker processes run `payments` + `default`, one runs `notifications`, one runs `reports` + `maintenance` (see `deployment-topology.md` §3 for the supervisor units). `--max-jobs`/`--max-time` cap memory growth in long-lived PHP processes; the supervisor restarts them.

The scheduler runs as a single container/process:

```
* * * * * php artisan schedule:run   # or: php artisan schedule:work as the container command
```

Business schedules are declared with `->timezone('Asia/Tehran')`; storage and logs stay UTC. `schedule:work` is single-instance by deployment (one scheduler container); every entry uses `withoutOverlapping()` so a slow run cannot stack. If the deployment ever grows to multiple app nodes, the scheduler must be pinned to one node or guarded by `onOneServer()` (`RB-OPS-01`).

## 2. Job conventions

Every queued job in `app/Modules/*/Application/Job/` (or `Infrastructure/Job/` when it is purely infrastructural):

1. **Carries ids, not models.** `public function __construct(public string $orderId)` — never a serialised Eloquent model.
2. **Has an idempotency key** — a business key (`invoice:{id}:points`, `batch:{id}:export`) enforced by a unique constraint, `insertOrIgnore`, or `shr_idempotency_keys`. Re-delivery must be a no-op, not a duplicate.
3. **Re-reads state inside the job.** A job is a hint, not a snapshot: it loads the aggregate and re-checks preconditions (`if ($order->status !== Paid) return;`).
4. **Is bounded.** `$timeout` set; large work is chunked (`chunkById(500)`) and requeued rather than run in one pass.
5. **Never swallows exceptions.** A failed money or notification job must be visible in `failed_jobs` with the payload and the exception.
6. **Declares its queue and tries**: `public int $tries = 5; public array $backoff = [10, 60, 300]; public string $queue = 'payments';`
7. **Uses `ShouldBeUnique` / `WithoutOverlapping`** where concurrent runs would corrupt or duplicate (`AccruePointsForInvoiceJob` is unique per invoice).

## 3. Scheduled inventory

| # | Command / job | Queue | Cadence (Asia/Tehran) | What it does | Idempotency key | Retry / failure |
|---|---|---|---|---|---|---|
| 1 | `outbox:relay` | `default` | every minute | Publishes `shr_outbox_messages` rows whose transaction committed; marks `dispatched` | row id + `attempts` | 3 tries, backoff 5/30/120 s; stuck rows alert after 15 min |
| 2 | `carts:expire` | `default` | hourly at :10 | Emits `CartAbandoned` for carts idle > 24 h *(config)* and prunes empty carts older than 30 days | `cart:{id}:abandoned` | 3 tries; per-cart failure logged, batch continues |
| 3 | `orders:expire-unpaid` | `payments` | every 5 min | Cancels `awaiting_payment` orders older than 30 min *(config)*; releases stock; voids intents | `order:{id}:expire` | 5 tries, 10/60/300 s; a failed expiry leaves the order pending, never half-cancelled |
| 4 | `inventory:release-expired` | `default` | every 5 min | Releases reservations past TTL (belt and braces with #3) | `reservation:{id}:release` | 3 tries |
| 5 | `orders:auto-complete` | `default` | daily 02:00 | `delivered` → `completed` after the return window *(config, default 7 days)* | `sub:{id}:complete` | 3 tries |
| 6 | `pay:reconcile` | `payments` | hourly at :20, plus nightly 03:20 | Re-verifies stale `pending` payments; pulls gateway statements; writes mismatches | `payment:{id}:reconcile:{date}` | 5 tries, 2/10/30/120/300 s; mismatch never auto-captured |
| 7 | `loyalty:accrue` (listener job) | `default` | event-driven on `InvoiceIssued` | Computes points per line with the rule set effective at invoice date; appends `loy_point_entries`; tier/star evaluation | `invoice:{id}:points` (unique) | 5 tries; replayable via `loyalty:replay` |
| 8 | `loyalty:deduct-inactive` | `default` | monthly, 1st 03:00 | Deducts the configured points (default 4) from customers with no invoice in the period *(config)* | `inactivity:{user}:{period}` (unique) | 3 tries; a partial run resumes by period marker |
| 9 | `loyalty:expire-rewards` | `default` | daily 04:00 | Marks rewards past `expires_at` as expired, emits `RewardExpired` | `reward:{id}:expire` | 3 tries |
| 10 | `loyalty:evaluate-tiers` | `default` | daily 04:30 | Recomputes tiers from rolling 12-month points; emits `TierChanged` | `tier:{user}:{date}` | 3 tries |
| 11 | `cmp:accrue-commissions` | `default` | event-driven on paid `InvoiceIssued` + nightly 05:00 sweep | Accrues commission for the attributed marketer per plan rules; posts to Finance | `comm:{invoice}:{marketer}:{rule}` (unique) | 5 tries; clawbacks are separate entries |
| 12 | `cmp:close-period` | `default` | monthly, last day 23:30 | Freezes a commission period, produces statements, marks payables settleable | `cmp-period:{yyyy-mm}` (unique) | 3 tries; re-runs are no-ops |
| 13 | `merchants:evaluate-activity` | `default` | daily 05:30 | Marks merchants inactive when no invoice in the admin window *(config, default 90 days)*; snapshot + `MerchantDeactivated` | `merchant:{id}:activity:{date}` | 3 tries |
| 14 | `finance:build-settlement-batch` | `default` | daily 06:00 and on demand | Builds the settlement batch for approved periods (see `data-flow.md` §6) | `batch:{period}:{sequence}` | 3 tries; immutable after approval |
| 15 | `finance:generate-bank-export` | `reports` | triggered by batch approval | Writes the `.xlsx` + `.sha256` + manifest to private storage | `export:{batch_id}` (unique) | 3 tries, 30/120/600 s; failure blocks "mark paid" |
| 16 | `finance:apply-delay-penalties` | `default` | event-driven on `ShipmentDelayed` + daily sweep | Credits the buyer's wallet, debits the seller payable for the admin-approved percentage | `delay:{shipment_id}` (unique) | 5 tries |
| 17 | `notifications:dispatch` | `notifications` | event-driven | Maps an event to templates, recipients and channels; writes `ntf_notifications` | `event:{name}:{aggregate_id}:{user_id}:{channel}` | 3 tries |
| 18 | `notifications:send` | `notifications` | queued per delivery | Calls the channel adapter; records cost and receipt | `delivery:{id}:send` | 3 tries, 10/60/300 s; permanent errors fail fast |
| 19 | `notifications:broadcast` | `notifications` | scheduled by the buyer's purchase or the admin | Sends a paid/free broadcast in chunks, respecting the purchased quantity and admin cap | `broadcast:{id}:chunk:{n}` | 3 tries; stops at cap, reports remainder |
| 20 | `reports:generate` | `reports` | on demand | Builds Excel/CSV reports; streams large ones to disk; notifies the requester | `report:{uuid}` | 1 try + alert; user sees a retry button |
| 21 | `reports:daily-payment-summary` | `reports` | daily 07:00 | Emails the day's paid products per supplier to supplier + admin (پیوست ۱۳ بند ۲) | `daily-summary:{date}` | 2 tries; missed run regenerated on next boot |
| 22 | `backup:run` | `maintenance` | daily 01:00 | `mysqldump --single-transaction` + gzip + encrypt + upload; prunes per retention | `backup:{date}` | 2 tries; failure raises a critical alert |
| 23 | `backup:verify` | `maintenance` | weekly, Sat 01:30 | Restores the latest dump into a scratch schema, runs row-count checksums, drops it | `backup-verify:{week}` | 1 try; failure alerts |
| 24 | `audit:verify` | `maintenance` | daily 01:15 | Recomputes the audit hash chain; alerts on a break | n/a (read-only) | 1 try |
| 25 | `cms:rebuild-sitemap` | `maintenance` | daily 02:30 and on publish | Regenerates `sitemap.xml` and pings nothing (no external ping in v1) | `sitemap:{date}` | 2 tries |
| 26 | `tenders:expire` | `default` | hourly at :40 | Closes tenders past their deadline; emits `TenderClosed` | `tender:{id}:close` | 3 tries |
| 27 | `classifieds:expire` | `default` | daily 03:40 | Expires listings past their lifetime | `listing:{id}:expire` | 3 tries |
| 28 | `promotions:expire` | `default` | every 5 min | Deactivates promotions past `ends_at`; clears cached eligibility | `promotion:{id}:expire` | 3 tries |
| 29 | `geo:purge-positions` | `maintenance` | daily 04:10 | Deletes location pings older than 90 days | `geo-purge:{date}` | 2 tries |
| 30 | `geo:retry-geocode` | `maintenance` | daily 04:20 | Retries addresses flagged `needs_geocode` | `geocode:{entity}:{id}` | 3 tries |
| 31 | `sessions:prune` | `maintenance` | daily 04:40 | Laravel `session:prune` + expired token cleanup | n/a | 1 try |
| 32 | `queue:prune-failed` | `maintenance` | weekly | Deletes failed jobs older than 30 days after review | n/a | 1 try |

Event-driven listeners that are **not** scheduled (they run when their event lands): `Invoices` issuing on `PaymentSucceeded`, `Orders` confirming on `PaymentSucceeded`, `Loyalty` accruing on `InvoiceIssued`, `Compensation` accruing on paid `InvoiceIssued`, `Finance` posting on completion, `Notifications` fan-out for every listed event, `Reporting` cache invalidation.

## 4. Outbox relay

The only reason a business event can be lost is a process death between commit and dispatch. The outbox closes that hole:

```
shr_outbox_messages(id, event_name, aggregate_type, aggregate_id, payload JSON,
                    available_at, attempts, dispatched_at, last_error, created_at)
```

- Producers insert the row **inside** the same transaction as the state change.
- `outbox:relay` selects `dispatched_at IS NULL AND available_at <= now()` ordered by id, `FOR UPDATE SKIP LOCKED`, dispatches, then marks `dispatched_at`.
- On listener failure the relay leaves the row pending with `attempts++` and exponential `available_at`; after 10 attempts it is parked (`dispatched_at` null, `attempts=10`) and raises an alert. `outbox:replay {id}` re-queues it after a fix.
- The relay is safe to run concurrently: `SKIP LOCKED` plus the `dispatched_at` guard makes double-dispatch harmless only because every consumer is idempotent — which is why §2 rule 2 is non-negotiable.

## 5. Retry and backoff policy

| Class | Tries | Backoff | Timeout | On exhaustion |
|---|---|---|---|---|
| Payment capture/verify | 5 | 2, 10, 30, 120, 300 s | 60 s | `failed_jobs` + critical alert + `pay_payments` stays `pending` |
| Refund | 3 | 30, 120, 600 s | 60 s | `pending_manual`, finance queue |
| Ledger posting | 5 | 5, 15, 60, 180, 600 s | 30 s | Critical alert; the command that triggered it is rolled back where possible |
| Notification send | 3 | 10, 60, 300 s | 30 s | `ntf_deliveries.status=failed`, visible in admin |
| Report generation | 1 | — | 900 s | User-visible retry, admin alert if repeated |
| Backup | 2 | 300, 900 s | 3600 s | Critical alert to operator + client |
| Cleanup/maintenance | 2 | 60, 300 s | 300 s | Alert at warning level; safe to skip a day |
| Outbox relay | n/a (row-level) | exponential to 10 attempts | 120 s | Parked row + alert |

`failed_jobs` is not a graveyard: the admin "Operations → Failed jobs" screen lists them with class, attempts, exception summary and a **retry** action (permission `admin.ops.jobs.retry`, audited). Retrying a job that is not idempotent is exactly the bug this policy is designed to prevent, so every job in the table above has a key.

## 6. Time-travel and testing

- Every job takes its clock from `App\Shared\Time\Clock` (bound to `SystemClock` in production, `FrozenClock` in tests). `Carbon::setTestNow()` is used only in adapter tests.
- Feature tests call the job directly; scheduling tests assert the schedule definition (`Schedule::events()` contains the command with the expected cron and timezone) rather than waiting for a minute to pass.
- The "monthly" jobs are tested by freezing the clock to the first of a month and running the command twice — the second run must be a no-op (idempotency assertion).
- Notification tests use the fake channel and assert one delivery per `(event, user, channel)`.

## 7. Monitoring and alerts

| Signal | Threshold | Severity |
|---|---|---|
| `failed_jobs` count (last hour) | > 5 | warning |
| Any failed job in `payments` queue | ≥ 1 | critical |
| Outbox oldest pending age | > 15 min | critical |
| Scheduler heartbeat (`schedule:run` marker) | missing 10 min | critical |
| Worker process count | below expected | critical |
| Backup age | > 26 h | critical |
| `audit:verify` failure | any | critical |
| Report queue depth | > 50 | warning |
| Notification failure rate | > 20 % in 15 min | warning |

Heartbeats are written to `shr_heartbeats` by the scheduler and each worker loop (`queue:work` `--max-jobs` restart writes one on boot); the admin dashboard and an external uptime check both read the latest row. This is the minimum viable monitoring for a single-host deployment without Prometheus.

## 8. Operational controls

| Need | Command |
|---|---|
| Pause all business jobs during a migration | `php artisan queue:pause` (sets `shr_runtime_flags.jobs_paused`, checked by every job's `middleware`) |
| Drain before deploy | `php artisan queue:restart` after the release symlink flips |
| Replay one event | `php artisan outbox:replay {id}` |
| Recompute loyalty for a period | `php artisan loyalty:replay --from=… --to=…` (writes a dry-run report first) |
| Rebuild a settlement batch | `php artisan finance:rebuild-batch {id} --reason="…"` (creates a correction batch, never edits) |
| Regenerate yesterday's summary | `php artisan reports:daily-payment-summary --date=…` |
| Emergency backup | `php artisan backup:run --only-db` |

All of these are also buttons in the admin operations screen, permission-gated and audited with the operator's identity and reason.

## 9. Capacity notes

At the expected scale (thousands of orders per month, tens of thousands of notifications), the database queue on a single MySQL instance is comfortable: a few hundred jobs per hour at peak. The pressure points are (a) festival campaigns, where notification fan-out can spike to tens of thousands of messages in an hour, and (b) month-end settlement and reporting, which run in the same window. Mitigations already in the design: separate `notifications` and `reports` queues with dedicated workers, chunked broadcasts, and report generation outside the settlement transaction. If volume outgrows this, the first change is Redis for queues only — the code does not change because the queue is already an abstraction (`ADR-0006`).
