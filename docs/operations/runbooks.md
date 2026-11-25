# Runbooks — Dekolife Platform

**Purpose:** Copy-pasteable procedures for the operational events that actually happen: deploy, rollback, database restore, stuck queues, payment callback storms, gateway outages, backup failures, certificate expiry, disk exhaustion, notification throttling, and the postmortem that closes an incident.
**Audience:** On-call operator, platform agent, the client's technical team after handover.
**Status:** Baseline v1 (Phase 0). Every procedure is exercised once in staging before the release that depends on it.
**Source:** Contract obligations that these runbooks protect: `ماده ۱۲` بند ۱ (first response ≤ 24 working hours; critical outage/data loss ≤ 24 h), `ماده ۴` بند ۳ (acceptance testing before delivery), `پیوست ۱` بند ۹ (backup + restore), `پیوست ۹` بند ۳ (security), `پیوست ۱۳` بند ۷ (12 gateways, full transaction logging). Alert thresholds referenced here are defined in `monitoring-and-logging.md` §8; backup mechanics in `backup-and-restore.md`.

---

## 0. How to use these runbooks

1. **Identify** the runbook from the alert (each alert names a section) or from the symptom.
2. **Announce** in the incident channel: `INCIDENT <id> — <runbook> — <one-line impact>`.
3. **Stabilise first, diagnose second, fix third.** A rollback that restores service in 10 minutes beats a clever fix in 90.
4. **Record timestamps as you go** — the postmortem (§11) needs them and memory is unreliable at 03:00.
5. **Never** run `migrate:fresh`, `migrate:rollback` on production, `DROP`/`TRUNCATE`, or a ledger `UPDATE` during an incident. Those turn a service incident into a data incident.

**Common environment**

```bash
APP_DIR=/var/www/dekolife
COMPOSE="docker compose -f /var/www/dekolife/compose.yaml"
cd "$APP_DIR"
```

---

## 1. Deploy

**Trigger:** a release tag from `release-plan.md` §1.1 is approved for promotion; or a hotfix merge.
**Impact if done badly:** 5xx during the window, half-applied migrations, stale caches serving old prices.
**Target duration:** 15 minutes of maintenance for a migration release, 3 minutes for a code-only release.

### Pre-flight (blocking)

```bash
# 1. tag exists on main and CI is green for that exact SHA
git fetch --tags && git describe --exact-match --tags HEAD
gh run list --branch main --limit 3        # all green

# 2. image digest matches what passed on staging
docker image inspect registry/dekolife/app:v0.5.0 --format '{{index .RepoDigests 0}}'

# 3. take and verify a backup (never deploy without a fresh dump)
/usr/local/bin/dekolife-db-backup.sh && \
  sha256sum -c /var/backups/dekolife/db/dekolife-$(date -u +%Y%m%d)*.sha256 | tail -1

# 4. migration dry-run
$COMPOSE exec app php artisan migrate --pretend

# 5. disk and queue headroom
df -h /var/lib/docker | tail -1        # ≥ 30 % free
$COMPOSE exec app php artisan queue:monitor default,payments,notifications --max=100
```

### Steps

```bash
# 6. announce the window
# 7. put the app in maintenance mode (keeps the DB writable, stops new requests)
$COMPOSE exec app php artisan down --render="errors::503" --retry=60

# 8. pull and start the new image
$COMPOSE pull app nginx
$COMPOSE up -d --no-deps app nginx

# 9. migrate — forward only, with a timeout guard
$COMPOSE exec app php artisan migrate --force

# 10. clear and warm caches, rebuild the config for the new env
$COMPOSE exec app php artisan optimize:clear
$COMPOSE exec app php artisan config:cache
$COMPOSE exec app php artisan route:cache
$COMPOSE exec app php artisan view:cache

# 11. restart workers so they load new code (a worker never reloads code by itself)
$COMPOSE exec app php artisan queue:restart

# 12. back online
$COMPOSE exec app php artisan up
```

### Verification (all must pass)

```bash
curl -fsS https://<host>/health/ready | jq
curl -fsS -o /dev/null -w '%{http_code}\n' https://<host>/
$COMPOSE exec app php artisan queue:monitor default,payments,notifications --max=100
$COMPOSE logs --tail=80 app | grep -Ei 'error|exception' || echo "no errors in last 80 lines"
```

Then click one product, one order, one invoice, and (if the release touched money) one sandbox payment. Watch the 5xx rate for 15 minutes.

### If it goes wrong

Stop and go to §2 (rollback). Do not attempt a second migration on top of a failed one.

---

## 2. Rollback

**Trigger:** post-deploy verification fails, or a defect appears within the release window and cannot be fixed forward in 30 minutes.
**Decision (per `release-plan.md` §4.2):** code-only defect → redeploy previous digest. Schema changed → previous digest still runs if the change was additive; otherwise restore-based rollback (§3) or a compensating migration.

```bash
# 1. redeploy the previous digest (known good)
docker pull registry/dekolife/app:<previous-tag>
$COMPOSE stop app nginx
$COMPOSE up -d --no-deps app nginx      # with the image pinned to <previous-tag> in .env or the compose override

# 2. clear caches built by the new code (config/view caches are version-specific)
$COMPOSE exec app php artisan optimize:clear
$COMPOSE exec app php artisan queue:restart

# 3. verify
curl -fsS https://<host>/health/ready | jq
```

**Additive schema is safe to leave behind.** If the new release only *added* a nullable column or a table, the previous code ignores it — do not drop it during an incident. Drop it in the next planned release after a backup.

**If the migration was destructive:** stop traffic, follow §3 with the pre-deploy dump, replay binlogs only if the dump is older than the incident, and expect the RTO in `backup-and-restore.md` §6. Announce the data-loss window to the delivery lead immediately — it is a contract-relevant event (`ماده ۱۲` بند ۱, critical = data loss).

**Rollback is not a failure.** Deploying a fix on top of an unverified rollback target is how a 15-minute incident becomes a 4-hour one.

---

## 3. Database restore

**Trigger:** corruption, an accidental destructive statement, a failed destructive migration, or A-02 readiness failure caused by schema damage.
**Target:** RTO 120 minutes (full), 90 minutes (single table + PITR).

```bash
# 1. stop writes: maintenance mode + stop the queue workers so no job writes mid-restore
$COMPOSE exec app php artisan down
$COMPOSE exec app supervisorctl stop "dekolife-worker:*"   # or: pkill -f 'queue:work'

# 2. identify the target dump (newest before the incident) and verify it
ls -1t /var/backups/dekolife/db/dekolife-*.sql.gz | head -3
sha256sum -c /var/backups/dekolife/db/dekolife-<STAMP>.sql.gz.sha256
gzip -t /var/backups/dekolife/db/dekolife-<STAMP>.sql.gz

# 3. restore into a NEW database first — never over the live one
mysql -uroot -p -e "DROP DATABASE IF EXISTS dekolife_restore; \
                    CREATE DATABASE dekolife_restore CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci;"
gunzip -c /var/backups/dekolife/db/dekolife-<STAMP>.sql.gz | mysql -uroot -p dekolife_restore

# 4. verify with the queries in backup-and-restore.md §4.3
#    (row counts vs manifest, ledger imbalance = 0, no orphans, Persian text intact)

# 5. optional PITR to the moment before the incident
mysqlbinlog --stop-datetime="<incident-ts minus 1s>" /var/lib/mysql-bin/binlog.0000NN | mysql -uroot -p dekolife_restore

# 6. promote: rename live aside (keep it), move the restored one into place
mysql -uroot -p -e "RENAME TABLE ... "   # or, simpler and safer: stop mysql, swap datadir, start
mysql -uroot -p -e "CREATE DATABASE dekolife_pre_restore_$(date -u +%Y%m%dT%H%M);"  # then copy the old data in

# 7. bring the app back
$COMPOSE exec app php artisan migrate --force     # only if the dump predates a required migration
$COMPOSE exec app php artisan optimize:clear
$COMPOSE restart app
$COMPOSE exec app supervisorctl start "dekolife-worker:*"
$COMPOSE exec app php artisan up
```

**Verification:** `/health/ready` green; log in as each of the five roles; open an order and its invoice; confirm the ledger imbalance is 0 in the restored live database. Record the actual RTO.

**After:** keep the pre-restore database for 7 days (it is evidence), write the incident report (§11), and if any transaction is known lost, reconcile payments against the gateway statement before telling anyone "everything is fine".

---

## 4. Queue stuck

**Trigger:** A-05 (backlog > 500 or oldest job > 600 s), A-06 (job failures), A-15 (scheduler missed), or a user reporting that notifications/emails stopped.

```bash
# 1. is a worker even running?
$COMPOSE exec app supervisorctl status
ps aux | grep -c '[q]ueue:work'

# 2. depth and age
$COMPOSE exec app php artisan tinker --execute="
  echo DB::table('jobs')->count().' jobs, oldest '.
       (int) DB::table('jobs')->min(DB::raw('TIMESTAMPDIFF(SECOND, available_at, NOW())')).' s'.PHP_EOL;"

# 3. what is failing, and why
$COMPOSE exec app php artisan queue:failed
$COMPOSE logs --tail=200 app | grep -i 'queue\|job' | tail -40

# 4. restart workers (clears a wedged process; jobs are re-queued, not lost)
$COMPOSE exec app php artisan queue:restart
$COMPOSE exec app supervisorctl restart "dekolife-worker:*"
```

**Diagnosis by shape**

| Pattern | Likely cause | Action |
|---|---|---|
| One job retried forever, blocking the queue | non-idempotent job throwing after a partial write | `queue:forget <id>` only after confirming the side effect; then fix idempotency |
| Many jobs failing with a DB timeout | DB saturated or a lock held | check `SHOW PROCESSLIST` for long transactions; kill the offender (never a settlement) |
| Backlog grows but nothing fails | worker dead or `--timeout` too low | restart; raise timeout only with a measured reason |
| Jobs for `notifications` only | provider rate limit or bad credentials | see §10 |
| Scheduler slot missed | cron entry removed or `schedule:run` failing | `php artisan schedule:list`; check the cron log; re-add one entry only |

**Never** delete `jobs` rows to "clear the backlog" — that silently drops notifications and, worse, could drop a payment side effect. If a job must be discarded, `queue:forget` it explicitly and record which id and why.

**Verification:** backlog < 50 and draining; `queue:failed` empty or each row explained; a sent test notification arrives.

---

## 5. Payment callback storm

**Trigger:** A-03 — a burst of callbacks (retries from the gateway, a user double-clicking, a replay attack), duplicated captures, or a payment success rate drop.
**Risk:** double-crediting a wallet, double-settling a supplier, or a ledger imbalance. The defence is idempotency, but the operator must confirm it held.

```bash
# 1. how many, from where, with what outcome
grep -c 'payment.callback' /var/www/dekolife/storage/logs/payments-$(date -u +%F).log
jq -r 'select(.event=="payment.callback.verified") | .reference' \
  /var/www/dekolife/storage/logs/payments-$(date -u +%F).log | sort | uniq -d | head

# 2. duplicate detection: same reference, more than one captured ledger entry
mysql -uroot -p dekolife -e "
  SELECT reference, COUNT(*) c FROM fin_ledger_entries
  WHERE reference LIKE 'PAY-%' GROUP BY reference HAVING c > 2 ORDER BY c DESC LIMIT 20;"

# 3. ledger must still balance
mysql -uroot -p dekolife -e "
  SELECT SUM(CASE WHEN direction='debit' THEN amount ELSE -amount END) AS imbalance
  FROM fin_ledger_entries;"
```

**Immediate actions**

1. **No manual fix of ledger rows, ever.** A duplicate is corrected by a reversal entry, produced by the application command, with its own reference.
2. If duplicates exist, run the reconciliation command to identify affected invoices:
   `php artisan payments:reconcile --since="<storm start>" --dry-run`, review the list, then run it for real.
3. If the storm is malicious (same IP, forged signatures), block the source at nginx and keep signature verification on — never "temporarily disable verification" to make the errors stop.
4. If the gateway is retrying because our endpoint is slow, raise the callback route's timeout budget and move side effects (notifications, reports) off the request path.

**Verification:** duplicate count from query 2 is zero after correction; ledger imbalance is 0; a canary payment captures exactly once; the affected customers' wallet balances match the ledger.

**Post-incident:** add the observed pattern to the reconciliation test fixtures, and record the money impact (if any) in the report.

---

## 6. Gateway outage

**Trigger:** A-04 — the active gateway fails its health probe or returns 5xx/timeouts for 3 consecutive checks; or the provider announces maintenance.

```bash
# 1. confirm it is the provider, not us
curl -sS -o /dev/null -w '%{http_code} %{time_total}s\n' https://<gateway-sandbox-endpoint>/health
$COMPOSE logs --tail=100 app | grep -i gateway

# 2. is our egress/DNS broken?
curl -sS -o /dev/null -w '%{http_code}\n' https://api.github.com
```

**Actions**

1. Announce on the storefront: a banner ("درگاه پرداخت موقتاً در دسترس نیست") via the admin settings — not a code deploy.
2. **Switch the active gateway** in admin → Settings → Gateways. This is a data change, takes effect immediately, and needs no deployment (drivers are already installed behind admin activation).
3. If no secondary gateway is configured, disable online payment and keep the order flow alive with "pay on delivery"/invoice-later only if the client has approved that mode in writing — otherwise stop checkout and say so plainly.
4. Do **not** mark pending payments as failed manually. Leave them `pending`; the reconciliation command closes them when the provider returns, using the provider's own status.

**Verification:** a sandbox payment succeeds on the new gateway; `pay_payments` has no rows stuck in an unknown state; the status page/banner is accurate.

**Follow-up:** record the outage window and the number of affected pending payments; confirm with the provider's settlement statement that no capture happened during the window.

---

## 7. Backup failure

**Trigger:** A-07 — backup job non-zero exit, dump < 1 MiB, checksum mismatch, or no new dump in 26 h. Also A-08 (binlog lag/gap).

```bash
# 1. read the job's own log first
tail -40 /var/log/dekolife/backup.log

# 2. rerun in the foreground to see the real error
/usr/local/bin/dekolife-db-backup.sh; echo "exit=$?"

# 3. common causes
df -h /var/backups            # no space (see §9)
mysql -uroot -p -e 'SHOW GRANTS FOR CURRENT_USER();'   # lost SELECT/LOCK TABLES/RELOAD
rclone lsd offsite:dekolife-backups/db/                 # credentials/network
```

**Actions**

| Cause | Fix |
|---|---|
| disk full | free space per §9, then rerun; never delete the newest dump to make room |
| credentials rotated | restore the backup user's grants; record who rotated them |
| `mysqldump` killed | check `SHOW PROCESSLIST` for a long transaction holding the snapshot; kill the *query*, not the dump, and retry |
| off-site upload failed | the local dump is still valid; re-upload with `rclone copy --immutable`; if the bucket is unreachable, keep local dumps and raise the risk |
| binlog gap | treat as P1: the PITR window is broken; take an immediate dump and investigate the purge setting |

**Verification:** a fresh dump exists, `gzip -t` passes, checksum verifies, the manifest is written, and the object is present off-site (`rclone ls | tail -1`).

**Escalation:** two consecutive failures = release train stops (`backup-and-restore.md` §7). A missing backup is an unmet contractual obligation under `پیوست ۱` بند ۹ and must be reported to the delivery lead the same day, not at the next cycle.

---

## 8. Certificate expiry

**Trigger:** A-10 — TLS certificate expires in < 14 days; or browsers warn; or `curl` fails with a certificate error.

```bash
# 1. what is actually served, and when does it expire
echo | openssl s_client -servername <host> -connect <host>:443 2>/dev/null \
  | openssl x509 -noout -subject -issuer -dates

# 2. is it the edge or the origin?
curl -sSI https://<host> | head -5
```

**Actions**

1. If certificates are managed by the client's hosting (the contract places SSL with the client — `ماده ۵` بند ۳), notify the client's technical contact immediately with the exact expiry timestamp and the command output, and record the notification. Keep the notice in writing; it is evidence.
2. If we manage it (Let's Encrypt or a purchased cert):

```bash
# renew with the ACME client in use, then reload — never restart nginx blindly mid-day
certbot renew --cert-name <host> --deploy-hook "nginx -s reload"
nginx -t && nginx -s reload
```

3. If renewal fails because of a DNS challenge or a rate limit, install the previously issued certificate (kept in the deployment archive) and extend the investigation window.
4. Never serve HTTP-only as a "temporary" fix for a payment site: the gateway callback and session cookies require TLS.

**Verification:** `openssl s_client` shows the new `notAfter`; `curl -sSI https://<host>` returns 200; the HSTS header is present; mixed-content warnings are absent on the checkout page.

**Follow-up:** add the renewal to the scheduled-command list with a 30-day lead and an alert at 14 days, so this runbook becomes a formality.

---

## 9. Disk full

**Trigger:** A-09 — free space < 15 %; writes fail; MySQL goes read-only; the app returns 500 on upload.

```bash
# 1. where did it go
df -h
du -xh --max-depth=2 /var/lib/docker /var/log /var/www/dekolife/storage /var/backups 2>/dev/null | sort -h | tail -20

# 2. usual suspects in this stack
du -sh /var/www/dekolife/storage/logs/*            # unrotated logs
du -sh /var/www/dekolife/storage/app/private/*     # identity docs / attachments
docker system df                                   # dangling images, build cache
ls -1t /var/backups/dekolife/db/*.sql.gz | wc -l    # retention not running
```

**Safe to delete now**

```bash
# old application logs (keep 3 days), dangling docker images, build cache
find /var/www/dekolife/storage/logs -name '*.log' -mtime +3 -delete
docker image prune -f
docker builder prune -f
```

**Never delete:** the newest 7 backups, `storage/app/private` files referenced by the database, `/var/lib/mysql` anything, or the current release's images.

**If MySQL went read-only**, it recovers on its own once space is freed; confirm with:

```bash
mysql -uroot -p -e "SELECT @@read_only, @@super_read_only;"
```

**Verification:** `df -h` shows ≥ 20 % free; an upload succeeds; a new order can be placed; the queue drains.

**Follow-up:** the real fix is a retention policy per directory and a disk alert at 20 %, not a monthly cleanup. If the growth is data (attachments), size the volume against the projected 12-month volume and raise it as a capacity decision in `progress.md`.

---

## 10. Mass-notification throttling

**Trigger:** A-14 — a campaign exceeds its recipient or per-minute cap, a provider starts returning 429, or cost alarms fire. Also the situation where an admin re-sends a campaign because "it looked stuck".

```bash
# 1. what is in flight
mysql -uroot -p dekolife -e "
  SELECT campaign_id, status, COUNT(*) c FROM ntf_messages
  WHERE created_at >= NOW() - INTERVAL 2 HOUR GROUP BY campaign_id, status;"

# 2. provider rate-limit evidence
grep -c '429' /var/www/dekolife/storage/logs/laravel-$(date -u +%F).log

# 3. is the queue the bottleneck or the provider?
$COMPOSE exec app php artisan queue:monitor notifications --max=100
```

**Actions**

1. **Pause the campaign, don't kill it:** set the campaign to `paused` in admin; in-flight jobs finish, nothing new is queued.
2. Lower the per-minute rate and the batch size in the campaign config, then resume. The notifier is resumable and idempotent per recipient, so a resume never double-sends.
3. If the provider is rate-limiting, back off exponentially and spread across the secondary channel where the consent allows it (`پیوست ۵` بند ۲۶ permits WhatsApp/email as alternatives).
4. If an operator already triggered a duplicate send, stop the job (`queue:forget` on the campaign batch ids), then measure exactly who received two messages from `ntf_messages` and report it — do not guess.
5. Hard caps are configuration, not advice: max recipients per campaign, max per minute, and a daily ceiling. Changing a cap requires the delivery lead's written approval because it is a cost and reputation decision.

**Verification:** `ntf_messages` shows no duplicate `(campaign_id, recipient_id)` pairs; the queue drains; the provider's error rate returns to baseline; the cost report matches the intended recipient count.

**Post-incident:** if the caps were wrong, change the default in config and add a test that asserts the cap is enforced before any provider call.

---

## 11. Incident postmortem template

Written within **3 working days** of any P1, and for any P2 that touched money, data or a contractual obligation. Blameless: the goal is a system that fails less, not a person to blame.

```markdown
# Incident <INC-YYYY-MM-DD-NN> — <short title>

**Severity:** P1 | P2 | P3
**Detected by:** alert <id> | customer report | internal review
**Start (UTC):**  · **Detected:**  · **Mitigated:**  · **Resolved:**
**Duration (customer impact):**  · **Duration (total):**
**Runbook used:** §<n>
**Owner:**  · **Responders:**

## 1. Customer and contract impact
- Users affected (count / roles):
- Money affected: Toman amount, invoices, ledger references — or "none"
- Contractual impact: SLA clock (ماده ۱۲), appendix heading at risk (ماده ۴ بند ۵), data loss window vs RPO (پیوست ۱ بند ۹)
- Client notified: yes/no, by whom, when

## 2. Timeline (UTC, 5-minute granularity)
| Time | Event | Actor |
|---|---|---|
| 09:12 | first 5xx in the payments channel | monitoring |
| 09:14 | on-call acknowledged A-03 | operator |
| … | … | … |

## 3. What happened (technical)
Short causal chain, no blame. Name the component and the failing assumption.

## 4. What made it worse / what made it better
- Detection gap:
- Diagnosis gap:
- Mitigation that worked (keep it):
- Mitigation that wasted time (drop it):

## 5. Root cause
One sentence a non-engineer can follow, then the technical detail.
Was it a missing test, a missing guard, a wrong assumption, or an operational gap?

## 6. Corrective actions
| # | Action | Type (code/test/ops/config/doc) | Owner | Due | Ticket |
|---|---|---|---|---|---|
| 1 | regression test that fails without the fix | test | | | |
| 2 | alert threshold adjusted | ops | | | |
| 3 | runbook section corrected | doc | | | |

## 7. Verification that the fix holds
Evidence: the failing test now passing, the alert firing in a staging drill, or the measurement after the change.

## 8. Sign-off
Reviewer · Orchestrator · Delivery lead (if contractual impact)
```

**Rules**

- Every P1 produces at least one **test** and one **monitoring or runbook** change; a postmortem with only code actions has not finished the job.
- Actions are filed as tasks with owners and dates, and tracked in `progress.md` until closed.
- If the incident exposed an ambiguity in the contract, add an `AMB-*` entry rather than resolving it silently in code.
- If the incident changed a client-facing promise (RTO, SLA, data window), the delivery lead updates the client in writing the same day — `ماده ۱۲` makes the response clock contractual.
