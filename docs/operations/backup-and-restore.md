# Backup and Restore — Dekolife Platform

**Purpose:** Define the daily database backup required by the contract, its retention, its off-site copy, the exact restore procedure, the verification queries that prove a backup is usable, the RPO/RTO commitments, and how backups are tested in staging.
**Audience:** Platform agent, the on-call operator, the client's technical team at handover.
**Status:** Baseline v1 (Phase 0). Implemented as a scheduled command in Phase 0/1 and drilled before `v0.2.0`.
**Source:** `پیوست ۱` بند ۹ — «پشتیبانگیری خودکار روزانه از دیتابیس بههمراه امکان بازیابی» (automatic daily database backup **with restore capability**) is a contract requirement, not an optional good practice. Supporting obligations: `ماده ۴` بند ۱ (no reduction of appendix commitments), `ماده ۱۲` بند ۱ (critical incident = data loss, 24 h response), `پیوست ۹` بند ۳ (security standards). Ledger integrity rules come from `docs/canonical-context.md` §5 (append-only `fin_ledger_entries`, derived balances).

---

## 1. What is backed up, how often, and what it protects against

| Asset | Method | Frequency | Rationale |
|---|---|---|---|
| MySQL `dekolife` (all `*_` module tables incl. `fin_ledger_entries`, `adm_audit_logs`) | `mysqldump --single-transaction` + gzip | **daily at 02:30 Asia/Tehran** | contractual minimum (`پیوست ۱` بند ۹); the ledger is the money truth |
| Binary logs | continuous archiving | continuous (7-day retention) | point-in-time recovery between dumps; the only way to recover after a 09:00 incident without losing the day |
| Uploaded files (product images, identity documents, invoice PDFs, order attachments) on `storage/app` | incremental `rsync`/object-store sync + weekly full | daily incremental, weekly full | the DB references files; a DB-only restore yields broken documents |
| Configuration and secrets | **not** in backups | — | secrets live in the deployment environment; a backup must never contain `.env` |
| Redis | not used in v1 | — | `docs/canonical-context.md` §2 pins the database queue driver |

**Honest scope statement:** the contract mandates *database* backup (`پیوست ۱` بند ۹). File backup is an engineering addition we make because a restored database that points at missing invoice PDFs is not a working system. It is documented here so the client's team can keep both jobs alive after handover.

---

## 2. Daily backup job

Reference implementation as a Laravel command (`php artisan backup:database`) wrapped by cron; the same `mysqldump` invocation is used by `scripts/dev backup` so local and production cannot drift.

```bash
#!/usr/bin/env bash
# /usr/local/bin/dekolife-db-backup.sh — runs as the deploy user
set -Eeuo pipefail

STAMP="$(date -u +%Y%m%dT%H%M%SZ)"
DIR="/var/backups/dekolife/db"
FILE="$DIR/dekolife-$STAMP.sql.gz"
RETENTION_DAYS=35

mkdir -p "$DIR"

mysqldump \
  --host=mysql --user="$DB_USER" --password="$DB_PASSWORD" \
  --single-transaction --quick --skip-lock-tables \
  --routines --triggers --events \
  --set-gtid-purged=OFF --default-character-set=utf8mb4 \
  --hex-blob \
  dekolife | gzip -9 > "$FILE"

# integrity, size and manifest
gzip -t "$FILE"
SIZE=$(stat -c%s "$FILE")
[ "$SIZE" -gt 1048576 ] || { echo "FATAL: dump smaller than 1 MiB"; exit 1; }
sha256sum "$FILE" > "$FILE.sha256"

# row-count manifest used later by the restore drill
mysql --host=mysql --user="$DB_USER" --password="$DB_PASSWORD" -N -B dekolife <<'SQL' > "$FILE.manifest"
SELECT 'ord_orders', COUNT(*), IFNULL(MAX(id),0) FROM ord_orders
UNION ALL SELECT 'inv_invoices', COUNT(*), IFNULL(MAX(id),0) FROM inv_invoices
UNION ALL SELECT 'pay_payments', COUNT(*), IFNULL(MAX(id),0) FROM pay_payments
UNION ALL SELECT 'fin_ledger_entries', COUNT(*), IFNULL(SUM(amount),0) FROM fin_ledger_entries
UNION ALL SELECT 'iam_users', COUNT(*), IFNULL(MAX(id),0) FROM iam_users;
SQL

# off-site copy (encrypted, versioned bucket)
rclone copy "$FILE" "offsite:dekolife-backups/db/" --immutable
rclone copy "$FILE.sha256" "offsite:dekolife-backups/db/"
rclone copy "$FILE.manifest" "offsite:dekolife-backups/db/"

# retention: 7 daily, 4 weekly (Sundays kept separately), 12 monthly
find "$DIR" -name 'dekolife-*.sql.gz' -mtime +7 -not -name '*-weekly*' -delete
find "$DIR" -name 'dekolife-*.sql.gz.sha256' -mtime +7 -delete
```

**Flag rationale**

| Flag | Why |
|---|---|
| `--single-transaction` | consistent InnoDB snapshot without locking the storefront; the dump must not stop checkout |
| `--quick` | streams rows instead of buffering the whole table in memory |
| `--routines --triggers --events` | otherwise the restored schema silently loses logic |
| `--hex-blob` | binary columns (attachments metadata, hashes) survive a text pipe |
| `--set-gtid-purged=OFF` | keeps the dump restorable into a fresh instance |
| `gzip -9` + `gzip -t` | 6–10× compression on this schema; `-t` catches truncated writes |
| size floor | the cheapest possible detector of "dump produced an empty file at 02:30" |

**Scheduling.** Cron at `30 2 * * *` in the server's timezone set to `Asia/Tehran`; the timestamp inside the filename is UTC so files sort unambiguously. The job writes a one-line JSON result to `/var/log/dekolife/backup.log`; a non-zero exit triggers the alert in `monitoring-and-logging.md` §8 and the runbook `runbooks.md` §7.

**Warehouse-side prerequisites:** the MySQL user needs `SELECT, LOCK TABLES, SHOW VIEW, TRIGGER, EVENT, RELOAD`; `rclone` remote `offsite:` is an S3-compatible bucket with versioning and object lock enabled; the encryption key for the bucket is held outside the server (see §5).

---

## 3. Retention and off-site policy

| Tier | Kept | Where | Purpose |
|---|---|---|---|
| Daily | 7 | server disk + off-site bucket | fast recovery from the last week |
| Weekly | 4 | off-site bucket only | recover from a defect introduced days ago but noticed late |
| Monthly | 12 | off-site bucket only (cold) | accounting/audit reference and a year of history |
| Binary logs | 7 days | server + off-site | point-in-time recovery within the week |
| Restore-drill reports | 24 months | repository `docs/operations/drills/` | evidence for handover and audits |

Retention is enforced in two places: `find -mtime` on the server and a lifecycle rule on the bucket. Objects are **immutable** (`--immutable` on upload, bucket versioning on) so an accidental or malicious local deletion cannot propagate.

**Off-site guarantees**

- Geo-separate from the application server; the bucket is not reachable with the application's credentials.
- Encryption: bucket-side SSE plus an `age`/GPG envelope on the dump before upload when the provider's at-rest guarantee is not acceptable to the client. The private key is held by the client's IT and by the delivery lead in a sealed record — **never** in the repository or on the app server.
- Access: writes by a dedicated service account, reads by a separate break-glass account used only during a restore.

---

## 4. Restore drill — exact steps

Run this **monthly in staging** and **quarterly into an isolated instance**. A backup that has never been restored is an assumption, not a backup.

### 4.1 Pre-flight

```bash
# 1. pick the newest dump and confirm the checksum matches
ls -1t /var/backups/dekolife/db/dekolife-*.sql.gz | head -1
sha256sum -c /var/backups/dekolife/db/dekolife-<STAMP>.sql.gz.sha256

# 2. confirm the file decompresses cleanly
gzip -t /var/backups/dekolife/db/dekolife-<STAMP>.sql.gz
```

### 4.2 Restore into a scratch database (never over the live one)

```bash
# 3. create an empty target
mysql -uroot -p -e "DROP DATABASE IF EXISTS dekolife_restore; \
                    CREATE DATABASE dekolife_restore CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci;"

# 4. restore (order matters: schema+data come from the dump as one stream)
gunzip -c /var/backups/dekolife/db/dekolife-<STAMP>.sql.gz \
  | mysql -uroot -p --default-character-set=utf8mb4 dekolife_restore

# 5. sanity: table count and the five critical tables exist
mysql -uroot -p -N -B -e "
  SELECT COUNT(*) FROM information_schema.tables WHERE table_schema='dekolife_restore';"
mysql -uroot -p -N -B -e "
  SELECT table_name FROM information_schema.tables
  WHERE table_schema='dekolife_restore'
    AND table_name IN ('ord_orders','inv_invoices','pay_payments','fin_ledger_entries','iam_users');"
```

Expected: the count is within ±2 of production's table count (a variance means a migration is missing from the dump), and all five rows are returned.

### 4.3 Verification queries

Compare the restored database against the manifest written at backup time — **not** against live production, which has moved on.

```sql
-- 6. row counts and maxima vs the manifest
USE dekolife_restore;
SELECT 'ord_orders' AS t, COUNT(*) AS rows_now, MAX(id) AS max_id FROM ord_orders
UNION ALL SELECT 'inv_invoices',       COUNT(*), MAX(id) FROM inv_invoices
UNION ALL SELECT 'pay_payments',       COUNT(*), MAX(id) FROM pay_payments
UNION ALL SELECT 'fin_ledger_entries', COUNT(*), SUM(amount) FROM fin_ledger_entries
UNION ALL SELECT 'iam_users',          COUNT(*), MAX(id) FROM iam_users;

-- 7. ledger integrity: debit and credit legs must balance to zero per currency
SELECT SUM(CASE WHEN direction = 'debit'  THEN amount ELSE 0 END) AS total_debit,
       SUM(CASE WHEN direction = 'credit' THEN amount ELSE 0 END) AS total_credit,
       SUM(CASE WHEN direction = 'debit'  THEN amount ELSE -amount END) AS imbalance
FROM fin_ledger_entries;

-- 8. no orphaned money rows: every payment points at an existing invoice
SELECT COUNT(*) AS orphan_payments
FROM pay_payments p LEFT JOIN inv_invoices i ON i.id = p.invoice_id
WHERE i.id IS NULL;

-- 9. no orphaned sub-orders
SELECT COUNT(*) AS orphan_suborders
FROM ord_sub_orders s LEFT JOIN ord_orders o ON o.id = s.order_id
WHERE o.id IS NULL;

-- 10. audit trail reachable up to the backup moment
SELECT MIN(created_at) AS oldest, MAX(created_at) AS newest, COUNT(*) AS rows_total
FROM adm_audit_logs;
```

**Pass criteria**

| Check | Pass condition |
|---|---|
| Row counts (6) | equal to the manifest for every listed table |
| Ledger balance (7) | `imbalance = 0` |
| Orphans (8, 9) | both `0` |
| Audit window (10) | `newest` within 24 h of the dump timestamp |
| Charset | `SHOW CREATE TABLE cat_products` shows `utf8mb4` and the Persian sample below reads back intact |

```sql
-- 11. Persian text survived the round-trip (expect the seeded category name, not '?')
SELECT id, title FROM cat_categories ORDER BY id LIMIT 3;
```

### 4.4 Application-level validation

```bash
# 12. point a throwaway app instance at the restored schema and run the read-only smoke tests
APP_ENV=staging DB_DATABASE=dekolife_restore php artisan test --group=smoke-restore
```

The `smoke-restore` group opens the catalog, resolves a price for each role, renders an invoice PDF, and opens the admin settlement screen — read-only, so it is safe against a restored copy. If the group does not exist yet, the manual equivalent is: log in as each of the five roles, open a product, open an order, open one invoice.

### 4.5 Teardown and record

```bash
# 13. drop the scratch database and write the drill report
mysql -uroot -p -e "DROP DATABASE dekolife_restore;"
```

The report records: dump timestamp, restore duration (the measured RTO), verification query results, any mismatch and its explanation, and who ran it. It is committed to `docs/operations/drills/` and summarised in `progress.md`. A drill with a mismatch is a **failed drill**: the cause is fixed and the drill is repeated within the week.

---

## 5. Point-in-time recovery (when a daily dump is not enough)

Use when the incident happened after the last 02:30 dump and the lost hours matter (money, settlements, commissions).

```bash
# 1. restore the most recent dump into a scratch database (steps 3–5 above)
# 2. replay binary logs up to just before the damaging statement
mysqlbinlog --start-datetime="2026-11-25 02:30:00" \
            --stop-datetime="2026-11-25 09:12:00" \
            /var/lib/mysql-bin/binlog.000123 \
  | mysql -uroot -p dekolife_restore
# 3. verify (step 4.3), then promote: rename scratch over the live database during a window
```

The stop point is chosen from the audit log (`adm_audit_logs`) or the application log, never guessed: find the first bad event, stop one second before it. Replay is done **into the scratch database only**; replaying onto the live database risks compounding the damage.

---

## 6. RPO and RTO commitments

| Scenario | RPO (data loss) | RTO (time to service) | Mechanism |
|---|---|---|---|
| Single table drop / bad migration | 0 (recover from the same dump + binlog replay to the incident) | 90 min | restore to scratch + PITR |
| Full database corruption | up to **24 h** from the daily dump; **≤ 5 min** with binlog shipping | **120 min** | dump restore + binlog replay |
| Accidental mass delete of rows | ≤ 5 min | 60 min | PITR into scratch, then targeted re-insert |
| Server loss (hardware) | ≤ 24 h (dump) | **240 min** | new host, restore dump, attach file storage |
| Off-site region loss | ≤ 24 h | 480 min | restore from the secondary bucket region |

**Contract alignment.** `پیوست ۱` بند ۹ requires daily backup + restore capability; the 24 h RPO is exactly that promise. The ≤ 5 min figure is only true while binlog shipping is healthy, so binlog lag is monitored (`monitoring-and-logging.md` §8) and a binlog gap is treated as a backup failure, not a warning.

RTO of 120 minutes assumes: the dump is on local disk (not only off-site), the restore procedure is practised, and the MySQL instance is provisioned. The measured value from the last drill is recorded in `progress.md` — the promise is re-stated from measurements, not from hope.

---

## 7. How backups are tested in staging

| Cadence | Test | Evidence |
|---|---|---|
| Every deploy to staging | the migration in the release is followed by a dump+restore into `dekolife_restore_ci` in CI | CI job log, green |
| Monthly | the full drill in §4 against the newest production dump, on staging hardware | drill report in `docs/operations/drills/YYYY-MM.md` |
| Before every release tag | restore the release candidate's schema, run the acceptance smoke subset against it | release checklist item |
| Quarterly | restore into an isolated instance with **no** network route to production, to prove the procedure does not depend on production being alive | drill report + reviewer sign-off |
| Annually | full disaster simulation: new host from the handover package + off-site dump only | report shared with the client |

**Failure handling.** A failed drill is a P1: the runbook `runbooks.md` §7 (backup failure) is followed, the cause is fixed, and the drill is repeated within 7 days. If two consecutive drills fail, the release train stops until it is resolved — an unverifiable backup is a contractual exposure under `پیوست ۱` بند ۹, not a technical debt item.

---

## 8. Security and privacy of backups

- Dumps contain personal data (national IDs, phone numbers, addresses) and must be treated as production data: encrypted bucket, restricted IAM, no copies on laptops, no dumps attached to tickets or chat.
- Identity-document files are on a private disk with signed URLs; the file backup inherits the same access rules.
- Never back up `.env`, gateway credentials, or SMS provider keys. Restoring a dump must never restore a secret.
- Access to a dump is logged and requires the break-glass account; every restore is recorded with who, when, why.
- Deleting a backup before its retention expiry requires a written instruction from the delivery lead, because the deletion may itself be the incident.
