# ADR-0006: Database queue and cache instead of Redis

- **Status:** Accepted (agent decision, pending owner review)
- **Date:** 2026-11-25
- **Deciders:** Architecture owner; operations
- **Related:** REQ-CTR-009 (daily backup), REQ-CTR-010 (audit); ADR-0001, ADR-0002; `../architecture/background-jobs.md`

## Context

The platform needs asynchronous work: payment verification and reconciliation, notification fan-out to four channels, point accrual, monthly inactivity deduction, settlement batches, Excel report generation, daily backups, and scheduled maintenance. It also needs a cache (settings, catalogue fragments, rate-limit counters, locks) and a session store.

The contract says nothing about infrastructure beyond "deploy on the client's server" (ماده ۱) and "daily automatic database backup with restore" (پیوست ۱ بند ۹). The client's host is a conventional single VM with nginx, PHP-FPM and MySQL; there is no evidence of a managed Redis, and adding one means another daemon to install, monitor, persist and back up on a machine we do not control.

Volume estimate: thousands of orders per month, tens of thousands of notifications per month, spikes during festivals (جشنواره) and month-end settlement. That is a few hundred jobs per hour at peak — comfortably within a MySQL-backed queue.

## Decision

Use the **`database` driver** for queue, cache and sessions, plus database-backed locks.

- `QUEUE_CONNECTION=database`, `CACHE_STORE=database`, `SESSION_DRIVER=database`.
- Jobs are dispatched with `after_commit=true`; `retry_after=180`; workers use `--sleep=1` and dedicated queues (`payments`, `default`, `notifications`, `reports`, `maintenance`) so a slow report cannot delay a payment confirmation.
- Scheduled work runs through `schedule:work` with `withoutOverlapping()` per entry; the scheduler is a single process by deployment.
- Locks (`Cache::lock`, `WithoutOverlapping`, `ShouldBeUnique`) use the database cache store, which supports atomic locks via `cache_locks`.
- **Queue tables live in MySQL next to the business data**, so a queue backlog is visible in the same backup and the same monitoring as everything else.
- The switch to Redis, if it ever happens, is configuration plus one infrastructure change — no application code changes, because the queue and cache are Laravel abstractions and no module touches the driver directly.

### Revisit triggers

Move the queue to Redis when **any** of these holds for two consecutive weeks:

1. More than 50,000 jobs/day, or a sustained queue depth above 1,000.
2. p95 end-to-end job latency above 30 s for `payments` (mostly polling delay).
3. `jobs` table write contention appearing in MySQL slow-query logs during checkout.
4. A festival campaign causes notification fan-out to degrade checkout response time.

Cache moves to Redis at the same time, for the same operational reason (one more daemon, not two).

## Consequences

**Positive**

- Zero additional infrastructure on a host we do not administer. One thing to install, back up, monitor and explain to the client's IT.
- Queue state is transactional with business state: a job enqueued in a rolled-back transaction cannot exist, and a backup restores jobs along with data.
- Failed jobs, retries and the admin operations screen are ordinary database rows — queryable, auditable, exportable.
- Rate limiting and locks work without another service, which matters for a system whose security controls depend on them.

**Negative**

- **Polling latency.** The database queue wakes on an interval (`--sleep=1`), so a job can wait up to ~1 s before starting. Acceptable for notifications and accruals; for payment verification it means a buyer may wait an extra second on the return page. Mitigated by verifying synchronously in the callback when possible and by the status-polling page.
- **Write amplification.** Every job is an `INSERT`, `UPDATE` (reserve) and `DELETE`; every cache entry is a row. At high volume this competes with business queries on the same buffer pool.
- **No native pub/sub or delayed-job precision.** Delays are `available_at` timestamps checked by the worker; a "run in 30 minutes" job may run at 30:01.
- **A queue backlog is a database load problem**, not an isolated one. A runaway job loop can slow checkout. Mitigated by queue separation, `max-jobs`, and monitoring, not eliminated.
- **Cache table growth** requires pruning; `cache:prune-stale-tags` and a scheduled cleanup are part of the job inventory.

**Neutral**

- Redis would be faster and would isolate queue load, but it introduces a second source of truth for locks and a persistence/backup question the contract does not ask us to answer.
- The architecture already treats the queue as an implementation detail; the decision is reversible in an afternoon.

## Alternatives considered

| Alternative | Why it lost |
|---|---|
| Redis (queue + cache + sessions + locks) | One more daemon on a client-owned single host, with its own persistence, memory limits, backup and monitoring; no contractual or volume justification at v1 |
| Amazon SQS / cloud queue | Requires reliable outbound access and cloud account ownership the client has not offered; adds latency for a single-region deployment |
| Beanstalkd | Extra daemon, no cache/session story, and unmaintained relative to Redis |
| `sync` driver (no queue) | Blocks the request on every notification, report and reconciliation; an SMS provider timeout would fail a checkout |
| File-based cache, database queue | Split-brain between two stores for locks; `WithoutOverlapping` becomes unreliable |
| Cron-only (no queue) | Cannot express per-event work with retries; the contract's notification and settlement requirements need real jobs |

## References

- `docs/canonical-context.md` §2 (database driver by default, Redis optional; ADR-0006 explains).
- Contract page 5 / پیوست ۱ clause 9 (daily backup), page 10 / پیوست ۱۱ clauses 6–7 (bulk notifications, SMS panel), page 11 / پیوست ۱۳ clause 2 (daily payment report).
- `../architecture/background-jobs.md` §1, §5, §9; `../architecture/deployment-topology.md` §4.2 (supervisor units).
