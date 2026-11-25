# Database Conventions — Dekolife Platform

**Purpose:** Pin every rule that governs schema shape, naming, types, migrations and data
integrity, so 28 modules written by different agents produce one coherent MySQL 8.4 schema.
**Audience:** Backend engineers, reviewers, agents generating migrations or models.
**Status:** Baseline (v1). Changes require an ADR; deviations require reviewer sign-off.
**Source:** `docs/canonical-context.md` §3 (modules and table prefixes), §5 (money, ledger,
orders, prices, points, payments, files, time, audit); contract appendix 1 clauses 1, 2, 8
(Laravel + MySQL, documented schema, extensible user levels / product types); appendix 13
clause 5 (per-user financial ledger).

---

## 1. Scope and framework exceptions

1. All business tables live in **MySQL 8.4**, `InnoDB`, `ROW_FORMAT=DYNAMIC`. No SQLite for
   feature or integration tests.
2. Tables owned by Laravel itself keep their framework names and have **no module prefix**:
   `migrations`, `sessions`, `cache`, `cache_locks`, `jobs`, `job_batches`, `failed_jobs`,
   `personal_access_tokens`. Everything else is prefixed (§2).
3. A table belongs to exactly one module (the prefix owner). Other modules read it only
   through that module's application service, query object, or domain event — never by
   joining across prefixes in their own repositories. Reporting (`RPT`) is the single
   exception: read-only views and aggregate queries may join across prefixes.
4. Cross-module foreign keys are allowed (they are a database-level integrity guarantee) but
   cross-module **writes** are not. If module A must change module B's row, it dispatches a
   command/event to B.
5. Views are named `v_<module>_<purpose>` and are read-only; no view may be written to.

## 2. Naming

### 2.1 Table prefix per module

| Module | Prefix | Module | Prefix |
|---|---|---|---|
| Identity | `iam_` | Promotions | `prm_` |
| Users | `usr_` | Shipping | `shp_` |
| Merchants | `mer_` | Returns | `rtn_` |
| Catalog | `cat_` | Tenders | `tnd_` |
| Pricing | `pri_` | Classifieds | `cls_` |
| Inventory | `stk_` | Patogh | `ptg_` |
| Cart | `crt_` | Samples | `smp_` |
| Orders | `ord_` | Messaging | `msg_` |
| Invoices | `inv_` | Notifications | `ntf_` |
| Payments | `pay_` | Cms | `cms_` |
| Finance | `fin_` | Reviews | `rvw_` |
| Marketing | `mkt_` | FieldOps | `geo_` |
| Compensation | `cmp_` | Admin | `adm_` |
| Loyalty | `loy_` | Shared kernel | `shr_` |

### 2.2 Object names

| Object | Rule | Example |
|---|---|---|
| Table | `<prefix><plural_snake_case>` | `ord_sub_orders`, `loy_point_transactions` |
| Pivot / link table | `<prefix><singular_a>_<singular_b>` (alphabetical) | `iam_role_permissions`, `cat_variant_attribute_values` |
| Column | `snake_case`, singular | `unit_price_amount`, `national_id` |
| Boolean column | `is_` / `has_` / `allow_` prefix | `is_active`, `has_variants` |
| Timestamp column | `<verb>_at`, `datetime` UTC | `paid_at`, `deleted_at` |
| Date-only column | `<noun>_date`, `date` | `birth_date` |
| Money column | `<noun>_amount`, `BIGINT` | `grand_total_amount` |
| Percentage | `<noun>_percent`, `DECIMAL(6,3)` | `commission_percent` |
| Quantity | `<noun>_quantity`, `DECIMAL(12,3)` | `on_hand_quantity` |
| Enum column | `<noun>` with `_status` for lifecycle | `status`, `payment_status` |
| Snapshot column | `<noun>_snapshot_json` | `address_snapshot_json` |
| Foreign key | `<referenced_entity_singular>_id` | `order_id`, `variant_id` |
| Self reference | `parent_id` (+ `path` when tree) | `cat_categories.parent_id` |
| Actor column | `<role>_by_id` | `approved_by_id`, `cancelled_by_id` |

Rules:

- Table names are **plural**; column names are **singular**. `cat_products.brand_id`, not
  `cat_product.brands_id`.
- Identifiers are `lower_snake_case`, ASCII only, ≤ 64 characters including prefix.
- Reserved words are never used bare (`order`, `group`, `rank`, `key`, `system`). Use the
  business noun (`ord_orders`), or suffix (`sort_order`, `setting_key`).
- Abbreviations are limited to this closed list: `id`, `sku`, `iban`, `vat`, `qr`, `sla`,
  `pdf`, `url`, `uuid`, `ip`, `lat`, `lng`, `min`, `max`, `no` (number), `avg`, `fa`, `en`.
  No other abbreviation without an ADR — spell out `quantity`, `amount`, `description`.
- Two columns must never mean the same thing with different names. Canonical vocabulary:
  `amount` (money), `quantity` (countable/measurable), `percent`, `status`, `code`
  (machine-readable, unique per scope), `title` (human label), `slug` (URL segment).
- Persian text lives in `*_fa` columns; English (optional) in `*_en`. Never store both in
  one column with a separator.

## 3. Keys and relationships

1. **Primary key:** `id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT` on every table. No natural
   primary keys, no UUID primary keys (index locality and InnoDB clustering).
2. **Public identifier:** any entity exposed in a URL, QR code, receipt or API path carries
   `public_id CHAR(26)` (ULID) with `UNIQUE`. Sequential ids are never exposed to clients;
   they leak volume and enable enumeration.
3. **UUID columns** (`BINARY(16)` or `CHAR(36)`) are used only for external correlation:
   `shr_outbox_events.event_id`, `shr_media.uuid`.
4. **Foreign keys:** `[CONSTRAINT fk_<table>_<column>] FOREIGN KEY (<col>) REFERENCES
   <table>(id)`. Every FK column is indexed (MySQL creates one, but name it explicitly so
   the index name is stable).
5. **Referential actions:**
   - `ON DELETE RESTRICT` — default for all financial and order data (`ord_*`, `inv_*`,
     `pay_*`, `fin_*`, `loy_*`). Deleting a parent is never allowed while children exist.
   - `ON DELETE CASCADE` — only for pure child rows with no independent meaning:
     `crt_cart_items`, `msg_attachments`, `cat_variant_attribute_values`,
     `rvw_review_scores`, `cms_survey_answers`, `geo_visit_media`.
   - `ON DELETE SET NULL` — only for optional attribution: `mer_stores.marketer_id`,
     `mkt_referral_attributions.referred_user_id`.
   - `ON UPDATE` is always `RESTRICT` (ids never change).
6. **Soft-deleted parents:** a FK to a soft-deletable table is always `RESTRICT`; the child
   keeps pointing at the soft-deleted row. Deletion of business data is a status change, not
   a row removal (§7).
7. **Composite uniqueness** is expressed as a unique index, not as application logic:
   `uq_stk_stock_items_warehouse_variant (warehouse_id, variant_id)`.
8. **Nullable FK = optional relationship**, and it must be documented in the data dictionary
   with the business reason. A nullable FK that is "always set in practice" is a defect.
9. **No polymorphic `*_type` + `*_id` pairs** in business tables. Replace with explicit
   nullable FKs plus a CHECK constraint that exactly one is set, or with a dedicated link
   table. Permitted exceptions (documented, read-only projections): `adm_audit_logs`,
   `shr_outbox_events`, `shp_shipment_events`.

## 4. Index and constraint naming

| Kind | Pattern | Example | Max length handling |
|---|---|---|---|
| Primary | `PRIMARY` | — | — |
| Unique | `uq_<table>_<cols_short>` | `uq_iam_users_mobile` | truncate cols to fit 64 |
| Plain index | `ix_<table>_<cols_short>` | `ix_ord_orders_buyer_status` | same |
| Foreign key | `fk_<table>_<column>` | `fk_ord_sub_orders_order_id` | same |
| Check | `ck_<table>_<rule>` | `ck_ord_order_items_qty_positive` | same |
| Fulltext | `ft_<table>_<cols>` | `ft_cat_products_title_desc` | — |

Index design rules:

1. Every FK column has an index whose leftmost column is that FK.
2. Composite index order = equality columns first, then range, then sort. List endpoints
   must be served by an index ending in the tiebreaker sort column (`id`) to keep cursor
   pagination stable: `ix_ord_orders_buyer_status_placed (buyer_user_id, status, placed_at, id)`.
3. Do not index a column with < 10 distinct values on its own (`status`, `is_active`).
   Combine it with the scoping column.
4. Index prefix length is unnecessary: `utf8mb4` + `DYNAMIC` gives 3072-byte keys, so
   `VARCHAR(191)` and below index fully. Slugs and codes are capped at `VARCHAR(160)`.
   Free-text columns (`description`) are never indexed except via `FULLTEXT`.
5. `FULLTEXT` indexes use `WITH PARSER ngram` for Persian product search; they are an
   optimization, never the source of truth for filtering.
6. A unique index on a soft-deletable table must include the lifecycle discriminator, e.g.
   `uq_cat_products_slug (slug, deleted_at)` — MySQL treats `NULL` as distinct, so live rows
   (one `NULL`) stay unique while deleted rows accumulate. If the table uses
   `status = 'archived'` instead of soft delete, uniqueness is enforced only over live rows
   through a generated column (§4.7).
7. Conditional uniqueness uses a stored generated column:
   `active_key VARCHAR(80) GENERATED ALWAYS AS (IF(status='active', CONCAT(price_list_id,':',variant_id), NULL)) STORED`
   with `UNIQUE (active_key)`. This is the standard way to express "one active price row".

## 5. Column type standards

| Purpose | Type | Notes |
|---|---|---|
| Internal id | `BIGINT UNSIGNED` | auto-increment, never reused |
| Public id | `CHAR(26)` | ULID, `ascii_bin` collation |
| Money | `BIGINT` (signed) | integer **Toman**; signed because ledger reversals and adjustments are negative |
| Percentage | `DECIMAL(6,3)` | `0.000`–`100.000`; never float |
| Quantity | `DECIMAL(12,3)` | m², m, rolls; 3 decimals covers millimetres |
| Count | `INT UNSIGNED` | views, impressions, counters |
| Short label | `VARCHAR(160)` | titles, names, slugs |
| Code | `VARCHAR(64)` | `ascii_bin` when case-sensitive |
| Description | `TEXT` / `MEDIUMTEXT` | long copy, product description |
| Boolean | `TINYINT(1)` | default `0`, never nullable |
| Enum | `VARCHAR(32)` | PHP backed enum + `CHECK`; never MySQL `ENUM` |
| Datetime | `DATETIME(3)` or `TIMESTAMP` | UTC; `DATETIME` when the value may exceed 2038 or must not shift with session TZ |
| Date | `DATE` | calendar-only facts (birth date, invoice date) |
| Time | `TIME` | opening hours only |
| JSON | `JSON` | §8 |
| IP | `VARBINARY(16)` | supports IPv6; store via `inet_pton` |
| Hash | `CHAR(64)` | SHA-256 hex, `ascii_bin` |
| Encrypted value | `VARBINARY(1024)` or `TEXT` | Laravel `encrypted` cast, key from KMS/env |
| File path | `VARCHAR(512)` | never a full URL; the disk + path pair is authoritative |

Additional rules:

- **No `FLOAT`/`DOUBLE`** anywhere. No `DECIMAL` for money.
- **No `ENUM`** column type: altering it rewrites the table and couples migrations to code.
- `VARCHAR` lengths are truthful: 160 for names/titles, 255 for emails/URLs, 512 for paths.
  A `VARCHAR(255)` used for a 20-character field is a review defect.
- Every column is `NOT NULL` with a default unless "unknown" is a real business state.
  Nullable is a deliberate decision, not laziness.
- `utf8mb4` everywhere (§11); text columns that hold Persian get no `_bin` collation, because
  users expect Arabic/Persian letter variants to compare equal.

## 6. Enum vs lookup table

| Use an enum (`VARCHAR(32)` + PHP enum + CHECK) when | Use a lookup table when |
|---|---|
| The set is closed, code-owned and changes with a release | Admins create/edit values at runtime |
| Values drive a state machine or branch in code | Values are taxonomy: categories, brands, units, regions, service categories |
| Renaming a value requires a deploy anyway | Each value needs metadata (sort, parent, icon, commission rate) |
| Examples: `status`, `payment_status`, `type`, `channel`, `direction` | Examples: `cat_categories`, `cat_brands`, `cat_units`, `geo_regions`, `prm_promotions` |

Rules:

1. Enum values are `lower_snake_case`, stable forever, and **never** translated in the
   database. Persian labels live in `lang/fa/*.php`, keyed `enum.<table>.<column>.<value>`.
2. Enum-backed columns carry a `CHECK (col IN (...))` constraint generated from the PHP
   enum, so an out-of-band write fails loudly. Adding a value = new migration that drops and
   re-adds the CHECK (metadata-only in MySQL 8.4).
3. Enum columns store the code, never the Persian label, never an integer ordinal.
4. Lookup tables are small, `is_active`-flagged, `sort_order`-ordered, soft-deletable, and
   referenced by FK. Deleting a lookup value in use is forbidden; deactivate instead.

## 7. Soft deletes policy

| Table class | Strategy | Rationale |
|---|---|---|
| Financial, order, invoice, payment, ledger, settlement, points, commission | **Never delete.** Status/`void` + append-only history | Audit and legal traceability (appendix 1 clause 10) |
| Append-only logs (`ord_order_status_history`, `adm_audit_logs`, `fin_ledger_entries`, `stk_stock_movements`) | No `updated_at`, no `deleted_at` | Immutability is the point |
| Master data (`cat_products`, `cat_categories`, `cat_brands`, `mer_stores`, `cms_posts`, `cls_listings`) | `deleted_at` soft delete + `status` | Referenced by history; must stay resolvable |
| User content (`rvw_reviews`, `msg_messages`) | `deleted_at` for author delete; moderation uses `status` | Two different intents must not share one column |
| Pure children (`crt_cart_items`, survey answers) | Hard delete | No independent meaning |
| Identity (`iam_users`) | `deleted_at` + `anonymized_at`; PII columns nulled on anonymization | Right-to-erasure without breaking FKs |

Rules:

1. `deleted_at DATETIME(3) NULL` is the only soft-delete column name. Laravel's
   `SoftDeletes` trait is used unchanged.
2. Global scopes are **not** relied on for correctness in reporting or admin queries;
   explicit `withTrashed()` is required where history matters.
3. A soft-deleted row must remain readable by FK joins forever. Therefore columns that are
   needed to render history (`title`, `name`, `code`) are never nulled on soft delete;
   instead `status = 'archived'` marks it inactive.
4. Unique indexes include `deleted_at` (§4.6) so a deleted slug can be reused, except for
   codes that are legally meaningful (`inv_invoices.invoice_number`) which stay globally
   unique forever.
5. Anonymization: `iam_users.deleted_at` + `usr_profiles` PII columns set to `NULL` or a
   stable pseudonym, `mobile` replaced with `deleted+<id>@invalid`. Orders keep the id.

## 8. JSON column policy

JSON is allowed only for these four purposes:

1. **Snapshots** of a decision at a point in time: `price_snapshot_json`,
   `address_snapshot_json`, `applied_rules_json`. Write-once, read-whole.
2. **Rule parameters** evaluated in PHP, never filtered in SQL: `loy_rules.params_json`,
   `pri_volume_tiers` conditions.
3. **Audit payloads**: `adm_audit_logs.before_json` / `after_json`,
   `shp_shipment_events.raw_json`.
4. **Provider payloads** kept for dispute resolution: `pay_webhook_events.payload_json`,
   `pay_payment_attempts.response_payload_json` — always redacted (§16).

Forbidden: putting anything in JSON that is queried, joined, sorted, or aggregated; storing
money amounts only inside JSON; storing the authoritative copy of a field that also exists
as a column.

Rules:

1. Column suffix is `_json`, type `JSON`, `NOT NULL` only when an empty object is meaningful.
2. Every JSON column has a documented shape. Shapes that gate money or state are validated
   with a JSON Schema in the module's `Application` layer before write, and the schema lives
   next to the code (`app/Modules/<M>/Application/Schemas/*.json`).
3. Keys inside JSON are `snake_case`; money keys end in `_amount` and hold integers.
4. Max size: 64 KB per JSON document; larger artifacts go to `shr_media`.
5. A JSON column is never a substitute for a link table when cardinality can grow
   (`options_json` is fine for a survey question's fixed options, not for order items).
6. Reading code must tolerate unknown keys and missing keys (forward compatibility).

## 9. Money, quantities and rounding

1. **Money is `BIGINT` integer Toman.** Contract prices are in Toman; there are no sub-Toman
   units in v1. `App\Shared\Money` is the only arithmetic entry point.
2. Signed integers: negative amounts appear only in `fin_ledger_entries.amount` (by
   direction) and adjustment rows. Order/invoice amounts are non-negative and enforced by
   `CHECK (amount >= 0)`.
3. Percentages are `DECIMAL(6,3)` and multiplied only through `Money::percentage()`, which
   rounds **half-up** to the whole Toman at the point the amount is materialised.
4. Rounding order for an order total: per-line unit price × quantity → line discount →
   line total (rounded). Order-level discount is allocated pro-rata across lines; the
   residual (≤ n−1 Toman) is added to the largest line so that `SUM(lines) = order total`
   exactly. This invariant is asserted in tests.
5. Commission, penalty and loyalty amounts are computed from the **settled** line amounts,
   not from display prices.
6. Quantity is `DECIMAL(12,3)`; `Money` never multiplies by a float — quantity is converted
   to a scaled integer (`×1000`) first.
7. Currency is stored as `CHAR(3)` = `'IRT'` on every money-bearing header row, so a future
   currency does not require a silent reinterpretation.

## 10. Time and timezone

1. All datetimes are stored in **UTC**, type `DATETIME(3)` (millisecond precision) for
   business events and `TIMESTAMP` only where MySQL TZ conversion is explicitly wanted.
2. The application timezone is `UTC` (`config/app.php`); the display timezone is
   `Asia/Tehran` (`config/app.display_timezone`). Conversion happens at the presentation
   layer only.
3. Jalali dates are **never** stored as strings. Persian calendar formatting happens in the
   API resource / Blade component using the shared Jalali helper; the wire format is
   ISO-8601 UTC plus an optional Jalali display field (§API guidelines).
4. `DATE` columns (`usr_profiles.birth_date`, `inv_invoices.invoice_date`) are calendar facts
   with no timezone; they are stored as the Persian-calendar date converted to Gregorian at
   write time, and rendered back to Jalali for display.
5. Deadline columns (`expires_at`, `hold_until`, `sla_due_at`) are UTC instants. Business-day
   arithmetic (e.g. "3 working days") is computed in `Asia/Tehran` and stored as the
   resulting UTC instant.
6. Fiscal year for invoice numbering is the Jalali year, stored as `SMALLINT`
   (`inv_invoice_sequences.fiscal_year = 1405`), never derived at read time.

## 11. Character set and collation

1. Database, tables and text columns: `CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci`.
2. Case-sensitive machine identifiers (`code`, `slug`, `public_id`, `checksum`) use
   `utf8mb4_0900_as_cs` (or `ascii_bin` for pure-ASCII fields) so `ABC` ≠ `abc`.
3. Persian text uses the default `ai_ci` collation: users type `ي`/`ی` and `ك`/`ک`
   interchangeably, and search must match. Normalization (Arabic yeh/kaf → Persian) is done
   in the application before write, not by a `_bin` collation.
4. Connection charset is `utf8mb4`; `SET NAMES` is never issued by hand.
5. `sql_mode` includes `STRICT_TRANS_TABLES,NO_ZERO_DATE,NO_ZERO_IN_DATE,ERROR_FOR_DIVISION_BY_ZERO`.
   Zero dates are forbidden; "no date" is `NULL`.

## 12. Audit columns

Every mutable table carries:

| Column | Type | Rule |
|---|---|---|
| `created_at` | `DATETIME(3) NOT NULL DEFAULT CURRENT_TIMESTAMP(3)` | set by DB default and by Eloquent |
| `updated_at` | `DATETIME(3) NULL` | `NULL` on insert, touched on update |
| `deleted_at` | `DATETIME(3) NULL` | only where §7 allows |
| `created_by_id` | `BIGINT UNSIGNED NULL` | FK `iam_users.id`; `NULL` = system/guest |
| `updated_by_id` | `BIGINT UNSIGNED NULL` | FK `iam_users.id` |

Rules:

1. `created_by_id` / `updated_by_id` are mandatory on admin-editable configuration and
   master data (`pri_prices`, `adm_settings`, `loy_rules`, `cmp_commission_plans`). They are
   optional on high-volume transactional rows where `adm_audit_logs` already records the
   actor.
2. Append-only tables (`fin_ledger_entries`, `ord_order_status_history`,
   `stk_stock_movements`, `adm_audit_logs`, `pay_webhook_events`, `loy_point_transactions`)
   have `created_at`/`occurred_at` only — no `updated_at`, no `deleted_at`, and a DB-level
   `REVOKE UPDATE, DELETE` for the application user in production.
3. Sensitive business events also write `adm_audit_logs` through the shared
   `Auditable` trait: login, price change, role change, settlement approval, identity data
   change, manual ledger adjustment, settings change (canonical §5; appendix 1 clause 10).
4. `request_id` (`CHAR(26)`) is propagated from the HTTP layer into audit and event rows so
   a support ticket can be traced end-to-end.

## 13. Idempotency

1. **Every financial command is idempotent.** The idempotency key is stored, not inferred.
2. Table `shr_idempotency_keys`:
   `id`, `scope VARCHAR(64)`, `key VARCHAR(128)`, `request_hash CHAR(64)`,
   `response_code SMALLINT`, `response_body_json JSON`, `locked_at`, `completed_at`,
   `expires_at`, `UNIQUE (scope, key)`.
   - `scope` = `<module>.<command>` (e.g. `payments.capture`, `orders.place`).
   - Same key + same `request_hash` → replay the stored response (`200`/`201` as recorded).
   - Same key + different `request_hash` → `409 idempotency_key_reused`.
   - TTL 24 h for HTTP commands, 30 days for payment captures.
3. Independent of the generic key, these natural references are `UNIQUE` and are the real
   guard against double posting:
   - `fin_ledger_entries.reference` — one economic event, one ledger entry.
   - `pay_payments.reference` — one payable, one payment intent.
   - `pay_refunds.idempotency_key`, `fin_settlements.idempotency_key`,
     `loy_point_transactions.reference`, `cmp_commission_entries.reference`.
4. `Idempotency-Key` HTTP header maps 1:1 to `shr_idempotency_keys.key` with
   `scope = <route name>`. The header is **required** on all `POST` endpoints that move
   money or create an order, and ignored (but logged) elsewhere.
5. Gateway callbacks are deduplicated on `pay_webhook_events (gateway_id, event_id)` before
   any state change.

## 14. Migrations

1. One migration = one concern, named `YYYY_MM_DD_HHMMSS_<verb>_<object>.php` in the owning
   module's `database/migrations/` directory. Verbs: `create_`, `add_<cols>_to_`,
   `alter_`, `backfill_`, `drop_`.
2. **Forward-only in production.** `down()` exists and must work on a clean local database,
   but is never the recovery plan for a released migration; recovery is a new forward
   migration.
3. **Expand → migrate → contract.** A destructive or renaming change ships in three releases:
   (a) add new nullable column/index, dual-write; (b) backfill in batches and switch reads;
   (c) drop the old column. A `DROP COLUMN`, `RENAME`, narrowing type change, or adding
   `NOT NULL` without a default to a non-empty table is never combined with feature work.
4. Adding a `NOT NULL` column to a large table requires a default or a two-step
   add-nullable → backfill → alter.
5. Backfills are separate, chunked (`chunkById`, 5 000 rows), resumable, and idempotent.
   They never run inside the schema migration.
6. Index creation on large tables specifies `ALGORITHM=INPLACE, LOCK=NONE`; if MySQL refuses,
   the migration fails and the plan changes — no silent table lock in production.
7. Every migration must run on **MySQL 8.4** (local = CI = staging). No `DB::statement` with
   syntax that only works on MariaDB. No `enum()` in `Schema::create`.
8. Foreign keys are added after the table is populated in the same migration only when the
   table is new; for existing data, FK addition is its own migration with a pre-check query.
9. Migrations never reference model classes (schema drift), only `Schema`/`DB` and raw
   column names. No `Model::` inside a migration.
10. Data migration that changes money or status must be reviewed by the owner and paired
    with a reconciliation query documented in the PR.
11. Migration review checklist (enforced in PR template):
    - runs forward on a fresh DB and on a staging clone;
    - zero-downtime compatible;
    - `down()` present and honest;
    - index names follow §4;
    - no data loss without an ADR;
    - rollback plan written in the PR description.

## 15. Transactions, locking and concurrency

1. Any command that writes more than one row runs in a single `DB::transaction`, with the
   aggregate root locked first to impose a consistent lock order and prevent deadlocks.
2. Stock is never mutated by `UPDATE stock = stock - n` alone. The sequence is:
   `SELECT ... FOR UPDATE` on `stk_stock_items` → verify `available_quantity` → insert
   `stk_stock_reservations` → update counters. `stk_stock_items.version` is an optimistic
   lock bumped on every write.
3. `ord_orders`, `ord_sub_orders`, `inv_invoices`, `fin_settlements` carry
   `lock_version INT UNSIGNED NOT NULL DEFAULT 0`; a conflicting concurrent transition raises
   `409 state_conflict` instead of silently overwriting.
4. Deadlock (SQLSTATE 40001) is retried up to 3 times with jitter at the command bus level,
   never inside the domain layer.
5. Ledger writes happen **after** the business row is written, in the same transaction, and
   the ledger is the commit point: if the ledger insert fails on the unique `reference`, the
   whole transaction rolls back.

## 16. Security and PII at rest

1. PII columns are flagged in the data dictionary (§`PII` column). The flagged set is:
   mobile, email, national id, postal code, address, IBAN, birth date, IP, user agent,
   geolocation, ID-document media.
2. High-risk secrets are encrypted with Laravel `encrypted` casts (AES-256-GCM, key from
   env/KMS): `iam_two_factor_secrets.secret_encrypted`, `fin_bank_accounts.iban`,
   `pay_gateways.credentials_json`, gateway merchant keys.
3. Provider payloads stored in JSON are **redacted before write**: PAN, CVC, full IBAN,
   national id, OTP and any `token`/`password`/`secret` key is replaced with `"[redacted]"`.
   The redaction allow-list lives in `App\Shared\Support\PayloadRedactor`.
4. Identity documents and contracts live on the private disk; access is only through signed,
   short-lived URLs; `shr_media.visibility = 'private'`.
5. Query logging is disabled in production; slow-query log is enabled with bindings redacted.
6. Read replicas are used for reporting only; a replica read must never feed a write decision.

## 17. Retention and partitioning

| Data | Retention | Mechanism |
|---|---|---|
| `geo_tracking_pings` | 90 days raw | monthly `RANGE` partitioning on `recorded_at`, drop old partitions |
| `pay_webhook_events` | 18 months | archive to cold storage, keep checksum |
| `iam_login_events` | 12 months | monthly partition, drop |
| `adm_audit_logs` | 5 years (contract guarantee period) | archive job, never delete in place |
| `ntf_deliveries` | 12 months | archive |
| `ord_orders` / `inv_invoices` / `fin_ledger_entries` | permanent | never deleted |
| `crt_carts` (abandoned) | 90 days | purge job |

Partitioned tables keep the primary key as `(id, <partition_column>)` because MySQL requires
the partition key in every unique key.

## 18. Enforcement

1. A schema test (`tests/Schema/NamingConventionTest.php`) asserts, on a freshly migrated
   database: every table has a known prefix or is a framework table; every table has `id`
   `BIGINT UNSIGNED`; every FK has a named constraint matching `fk_*`; every money column
   ends in `_amount` and is `bigint`; every charset is `utf8mb4`.
2. CI runs that test plus `php artisan migrate:fresh` on MySQL 8.4 before the feature suite.
3. Reviewers reject: MySQL `ENUM`, floats, missing FK indexes, unprefixed business tables,
   `*_type`/`*_id` polymorphism outside the allow-list, and any `DROP` without an ADR
   reference in the PR body.
