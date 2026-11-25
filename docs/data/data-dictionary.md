# Data Dictionary — Core Commerce Tables

**Purpose:** Column-level definition of the tables that carry the sellable flow — identity,
users, merchants, catalog, pricing, inventory, cart, orders, invoices, payments, finance and
ledger, loyalty and promotions — plus the shared media registry they all reference. Type,
nullability, default, index, business notes and PII classification for each column.
**Audience:** Backend engineers, DB reviewers, test authors, integration partners reading the
schema contract.
**Status:** Baseline (v1). **85 tables** are specified here. The remaining modules
(messaging, notifications, CMS, reviews, shipping, returns, tenders, classifieds, patogh,
samples, field ops, admin, marketing, compensation) are documented at attribute level in
[`domain-model.md`](./domain-model.md) and follow the identical conventions; their column
lists are written when each module's first migration lands (see §14 for the exact gap list).
**Source:** `docs/canonical-context.md` §5 (money, ledger, orders, prices, points, payment,
files, time, audit); contract appendix 1 clause 2 (documented schema), appendix 13 clause 5
(per-user financial ledger); types ruled by [`database-conventions.md`](./database-conventions.md).

---

## 0. Reading the dictionary

| Column | Meaning |
|---|---|
| **Type** | MySQL 8.4 type as written in the migration. `BIGINT UNSIGNED` = ids, `BIGINT` = money/quantities that can be signed, `DECIMAL` = percentages and measured quantities |
| **Null** | `NO` = `NOT NULL`. A nullable column always has a documented reason in **Notes** |
| **Default** | Database default. `—` means the column is supplied by the application on every insert |
| **Index** | `PK` primary, `UQ` unique, `IX` non-unique, `FK` foreign key (always indexed), `GEN` generated column |
| **PII** | `—` none; `Yes` personal data; `Yes*` personal data encrypted at rest; `Yes†` personal data stored only as a hash |

Global rules applied to every table below (not repeated per row):

- Charset `utf8mb4`, collation `utf8mb4_0900_ai_ci`; `ascii_bin` for `public_id`, `code`,
  `token_hash`, `checksum`.
- `created_at DATETIME(3) NOT NULL DEFAULT CURRENT_TIMESTAMP(3)` and
  `updated_at DATETIME(3) NULL` on every mutable table; `deleted_at DATETIME(3) NULL` only
  where soft delete is allowed.
- Tables that are append-only (`iam_login_events`, `ord_order_status_history`,
  `stk_stock_movements`, `fin_ledger_entries`, `loy_point_transactions`,
  `adm_audit_logs`) have no `updated_at`/`deleted_at`.
- Money is integer Toman in `BIGINT` columns named `*_amount`, never nullable, `CHECK (… >= 0)`
  unless the column is a ledger amount.
- All foreign keys are `ON DELETE RESTRICT` unless the table is a pure child (noted).
- Index names follow `uq_<table>_<cols>` / `ix_<table>_<cols>` / `fk_<table>_<col>`
  (conventions §4); short forms are shown in the **Index** column.

---

## 1. Identity

### `iam_users`

The single account record for all five contract levels (canonical §1; appendix 3).

| Column | Type | Null | Default | Index | Notes | PII |
|---|---|---|---|---|---|---|
| `id` | `BIGINT UNSIGNED` | NO | auto | PK | Never exposed to clients | — |
| `public_id` | `CHAR(26)` | NO | — | UQ | ULID used in API paths, QR payloads and receipts | — |
| `mobile` | `VARCHAR(15)` | NO | — | UQ | Normalised `09xxxxxxxxx`; login identifier | Yes |
| `mobile_verified_at` | `DATETIME(3)` | YES | NULL | — | Set by SMS OTP verification; `NULL` blocks ordering | — |
| `email` | `VARCHAR(255)` | YES | NULL | UQ | Optional; multiple `NULL`s allowed by MySQL | Yes |
| `email_verified_at` | `DATETIME(3)` | YES | NULL | — | — | — |
| `password_hash` | `VARCHAR(255)` | NO | — | — | Argon2id; a marketer-created account gets a generated password and `must_change_password` in `usr_settings` | — |
| `status` | `VARCHAR(32)` | NO | `'pending'` | IX | `pending`,`active`,`suspended`,`deactivated`,`blocked`; `CHECK` constraint mirrors the PHP enum | — |
| `primary_role_id` | `BIGINT UNSIGNED` | YES | NULL | FK | Display default only; authorisation reads `iam_user_roles`. Nullable for legacy rows before first role assignment | — |
| `invite_code` | `VARCHAR(32)` | YES | NULL | UQ | The user's own referral code (appendix 4 clause 13) | — |
| `locale` | `VARCHAR(10)` | NO | `'fa'` | — | Presentation only | — |
| `timezone` | `VARCHAR(64)` | NO | `'Asia/Tehran'` | — | Display timezone; storage is always UTC | — |
| `last_login_at` | `DATETIME(3)` | YES | NULL | IX | Updated on successful login | — |
| `last_login_ip` | `VARBINARY(16)` | YES | NULL | — | `inet_pton` value, supports IPv6 | Yes |
| `failed_login_count` | `SMALLINT UNSIGNED` | NO | `0` | — | Reset on success; drives brute-force lockout | — |
| `locked_until` | `DATETIME(3)` | YES | NULL | — | Lockout expiry; `NULL` = not locked | — |
| `two_factor_required` | `TINYINT(1)` | NO | `0` | — | Forced for the `admin` role | — |
| `anonymized_at` | `DATETIME(3)` | YES | NULL | — | Set by the erasure job; PII columns nulled, id retained for FK integrity | — |
| `created_at` | `DATETIME(3)` | NO | `CURRENT_TIMESTAMP(3)` | IX | — | — |
| `updated_at` | `DATETIME(3)` | YES | NULL | — | — | — |
| `deleted_at` | `DATETIME(3)` | YES | NULL | — | Soft delete only; anonymisation is separate | — |

Indexes: `uq_iam_users_mobile (mobile)`, `uq_iam_users_public_id (public_id)`,
`uq_iam_users_email (email, deleted_at)`, `ix_iam_users_status_created (status, created_at)`,
`ix_iam_users_primary_role (primary_role_id)`.
Invariants: `mobile_verified_at` must be non-null before the first order; `status='blocked'`
and `locked_until` are independent guards; `deleted_at` requires `anonymized_at`.

### `iam_roles`

| Column | Type | Null | Default | Index | Notes | PII |
|---|---|---|---|---|---|---|
| `id` | `BIGINT UNSIGNED` | NO | auto | PK | — | — |
| `key` | `VARCHAR(32)` | NO | — | UQ | `consumer`,`store`,`supplier`,`marketer`,`admin`; permanent, code-referenced | — |
| `name_fa` | `VARCHAR(80)` | NO | — | — | Persian label for admin UI | — |
| `name_en` | `VARCHAR(80)` | YES | NULL | — | — | — |
| `level` | `TINYINT UNSIGNED` | NO | — | UQ | 1–5 matching appendix 3 ordering; used for sorting only, never for authorisation | — |
| `is_secondary_allowed` | `TINYINT(1)` | NO | `1` | — | Whether the level may be held alongside another (appendix 7 clause 6) | — |
| `is_assignable_by_admin` | `TINYINT(1)` | NO | `1` | — | `0` for roles reserved to system flows | — |
| `is_system` | `TINYINT(1)` | NO | `1` | — | Seeded row; cannot be deleted from the admin UI | — |
| `sort_order` | `SMALLINT UNSIGNED` | NO | `0` | — | — | — |
| `created_at` | `DATETIME(3)` | NO | `CURRENT_TIMESTAMP(3)` | — | — | — |
| `updated_at` | `DATETIME(3)` | YES | NULL | — | — | — |

Invariants: `key` and `level` are unique and never change; rows are seeded by migration.

### `iam_user_roles`

| Column | Type | Null | Default | Index | Notes | PII |
|---|---|---|---|---|---|---|
| `id` | `BIGINT UNSIGNED` | NO | auto | PK | — | — |
| `user_id` | `BIGINT UNSIGNED` | NO | — | FK, UQ¹ | → `iam_users.id` | — |
| `role_id` | `BIGINT UNSIGNED` | NO | — | FK, UQ¹ | → `iam_roles.id` | — |
| `is_primary` | `TINYINT(1)` | NO | `0` | GEN | One active primary per user via `primary_user_key` | — |
| `granted_by_id` | `BIGINT UNSIGNED` | YES | NULL | FK | `NULL` = system/registration | — |
| `granted_at` | `DATETIME(3)` | NO | `CURRENT_TIMESTAMP(3)` | — | — | — |
| `revoked_at` | `DATETIME(3)` | YES | NULL | — | Revocation is a timestamp, never a delete | — |
| `revoke_reason` | `VARCHAR(255)` | YES | NULL | — | Required when `revoked_at` is set (application rule) | — |
| `created_at` | `DATETIME(3)` | NO | `CURRENT_TIMESTAMP(3)` | — | — | — |
| `updated_at` | `DATETIME(3)` | YES | NULL | — | — | — |

¹ `uq_iam_user_roles_user_role (user_id, role_id)`.
`primary_user_key BIGINT UNSIGNED GENERATED ALWAYS AS (IF(is_primary = 1 AND revoked_at IS NULL, user_id, NULL)) STORED` with `UNIQUE`.
Invariants: re-granting a revoked role clears `revoked_at` on the existing row (no duplicate
rows); every grant/revoke writes `adm_audit_logs`.

### `iam_permissions`

| Column | Type | Null | Default | Index | Notes | PII |
|---|---|---|---|---|---|---|
| `id` | `BIGINT UNSIGNED` | NO | auto | PK | — | — |
| `key` | `VARCHAR(96)` | NO | — | UQ | `<module>.<resource>.<action>`, e.g. `orders.cancel.any` | — |
| `module` | `VARCHAR(32)` | NO | — | IX | Module name from canonical §3 | — |
| `name_fa` | `VARCHAR(120)` | NO | — | — | Admin UI label | — |
| `description` | `VARCHAR(255)` | YES | NULL | — | — | — |
| `created_at` | `DATETIME(3)` | NO | `CURRENT_TIMESTAMP(3)` | — | — | — |
| `updated_at` | `DATETIME(3)` | YES | NULL | — | — | — |

### `iam_role_permissions`

| Column | Type | Null | Default | Index | Notes | PII |
|---|---|---|---|---|---|---|
| `id` | `BIGINT UNSIGNED` | NO | auto | PK | — | — |
| `role_id` | `BIGINT UNSIGNED` | NO | — | FK, UQ¹ | → `iam_roles.id` | — |
| `permission_id` | `BIGINT UNSIGNED` | NO | — | FK, UQ¹ | → `iam_permissions.id` | — |
| `created_at` | `DATETIME(3)` | NO | `CURRENT_TIMESTAMP(3)` | — | — | — |

¹ `uq_iam_role_permissions_role_permission (role_id, permission_id)`.

### `iam_two_factor_secrets`

| Column | Type | Null | Default | Index | Notes | PII |
|---|---|---|---|---|---|---|
| `id` | `BIGINT UNSIGNED` | NO | auto | PK | — | — |
| `user_id` | `BIGINT UNSIGNED` | NO | — | FK, UQ | One enrolment per user | — |
| `secret_encrypted` | `VARBINARY(512)` | NO | — | — | Laravel `encrypted` cast (AES-256-GCM); never returned by any API | Yes* |
| `recovery_codes_encrypted` | `TEXT` | NO | — | — | JSON array of hashed codes, encrypted | Yes* |
| `confirmed_at` | `DATETIME(3)` | YES | NULL | — | Set after the first valid TOTP; enforcement starts here | — |
| `last_used_at` | `DATETIME(3)` | YES | NULL | — | Replay window guard | — |
| `created_at` | `DATETIME(3)` | NO | `CURRENT_TIMESTAMP(3)` | — | — | — |
| `updated_at` | `DATETIME(3)` | YES | NULL | — | — | — |

### `iam_login_events`

Append-only; partitioned monthly on `occurred_at`; retained 12 months.

| Column | Type | Null | Default | Index | Notes | PII |
|---|---|---|---|---|---|---|
| `id` | `BIGINT UNSIGNED` | NO | auto | PK (id, occurred_at) | Partition key must be in the PK | — |
| `user_id` | `BIGINT UNSIGNED` | YES | NULL | IX | `NULL` for unknown-user attempts | — |
| `mobile_attempted_hash` | `CHAR(64)` | YES | NULL | IX | Peppered SHA-256; never the raw mobile | Yes† |
| `result` | `VARCHAR(32)` | NO | — | IX | `success`,`bad_password`,`unknown_user`,`locked`,`2fa_failed`,`blocked` | — |
| `ip_address` | `VARBINARY(16)` | YES | NULL | — | — | Yes |
| `user_agent` | `VARCHAR(255)` | YES | NULL | — | Truncated | Yes |
| `occurred_at` | `DATETIME(3)` | NO | `CURRENT_TIMESTAMP(3)` | IX | — | — |

### `iam_password_reset_tokens`

| Column | Type | Null | Default | Index | Notes | PII |
|---|---|---|---|---|---|---|
| `id` | `BIGINT UNSIGNED` | NO | auto | PK | — | — |
| `mobile` | `VARCHAR(15)` | NO | — | IX | — | Yes |
| `token_hash` | `CHAR(64)` | NO | — | UQ | SHA-256 of the SMS token; single use | — |
| `expires_at` | `DATETIME(3)` | NO | — | IX | 15 minutes | — |
| `used_at` | `DATETIME(3)` | YES | NULL | — | Set on redemption | — |
| `ip_address` | `VARBINARY(16)` | YES | NULL | — | — | Yes |
| `created_at` | `DATETIME(3)` | NO | `CURRENT_TIMESTAMP(3)` | — | — | — |

Invariants: issuing a new token for a mobile invalidates unused rows for that mobile.

### `iam_invitations`

| Column | Type | Null | Default | Index | Notes | PII |
|---|---|---|---|---|---|---|
| `id` | `BIGINT UNSIGNED` | NO | auto | PK | — | — |
| `code` | `VARCHAR(32)` | NO | — | UQ | Single-use invitation code | — |
| `inviter_user_id` | `BIGINT UNSIGNED` | NO | — | FK | Marketer, colleague or admin | — |
| `invited_mobile` | `VARCHAR(15)` | YES | NULL | IX | `NULL` for open link invites | Yes |
| `role_key` | `VARCHAR(32)` | NO | — | IX | Role to grant on acceptance | — |
| `channel` | `VARCHAR(16)` | NO | `'sms'` | — | `sms`,`link`,`qr` | — |
| `expires_at` | `DATETIME(3)` | NO | — | IX | Default +7 days | — |
| `accepted_at` | `DATETIME(3)` | YES | NULL | — | — | — |
| `accepted_user_id` | `BIGINT UNSIGNED` | YES | NULL | FK | Set on acceptance | — |
| `created_at` | `DATETIME(3)` | NO | `CURRENT_TIMESTAMP(3)` | — | — | — |
| `updated_at` | `DATETIME(3)` | YES | NULL | — | — | — |

### `iam_sessions_security`

| Column | Type | Null | Default | Index | Notes | PII |
|---|---|---|---|---|---|---|
| `id` | `BIGINT UNSIGNED` | NO | auto | PK | — | — |
| `user_id` | `BIGINT UNSIGNED` | NO | — | FK | — | — |
| `token_id` | `BIGINT UNSIGNED` | NO | — | UQ | → `personal_access_tokens.id` | — |
| `device_name` | `VARCHAR(120)` | YES | NULL | — | Derived from user agent | — |
| `ip_address` | `VARBINARY(16)` | YES | NULL | — | — | Yes |
| `last_used_at` | `DATETIME(3)` | YES | NULL | IX | — | — |
| `revoked_at` | `DATETIME(3)` | YES | NULL | — | Revocation deletes the Sanctum token | — |
| `created_at` | `DATETIME(3)` | NO | `CURRENT_TIMESTAMP(3)` | — | — | — |

---

## 2. Users

### `usr_profiles`

| Column | Type | Null | Default | Index | Notes | PII |
|---|---|---|---|---|---|---|
| `id` | `BIGINT UNSIGNED` | NO | auto | PK | — | — |
| `user_id` | `BIGINT UNSIGNED` | NO | — | FK, UQ | One profile per user | — |
| `first_name` | `VARCHAR(80)` | YES | NULL | — | Required before first order | Yes |
| `last_name` | `VARCHAR(80)` | YES | NULL | IX¹ | — | Yes |
| `display_name` | `VARCHAR(160)` | YES | NULL | — | Falls back to first+last, then masked mobile | Yes |
| `national_id` | `CHAR(10)` | YES | NULL | UQ | Iranian check-digit validated; unique when present | Yes* |
| `national_id_verified_at` | `DATETIME(3)` | YES | NULL | — | Set after document review | — |
| `birth_date` | `DATE` | YES | NULL | — | Consent-gated; powers birthday greeting (appendix 12 clause 7) | Yes |
| `gender` | `VARCHAR(16)` | YES | NULL | — | `male`,`female`,`unspecified` | Yes |
| `avatar_media_id` | `BIGINT UNSIGNED` | YES | NULL | FK | → `shr_media.id`, public disk | — |
| `bio` | `TEXT` | YES | NULL | — | Public only after approval | Yes |
| `website_url` | `VARCHAR(255)` | YES | NULL | — | Public only with approval (appendix 5 clause 7) | — |
| `social_links_json` | `JSON` | YES | NULL | — | `{instagram, telegram, whatsapp, linkedin}` | Yes |
| `province_id` | `BIGINT UNSIGNED` | YES | NULL | FK | → `geo_regions.id` (level=province) | — |
| `city_id` | `BIGINT UNSIGNED` | YES | NULL | FK | → `geo_regions.id` (level=city) | — |
| `postal_code` | `VARCHAR(10)` | YES | NULL | IX | — | Yes |
| `address_line` | `VARCHAR(512)` | YES | NULL | — | Text address, public only with approval | Yes |
| `latitude` | `DECIMAL(10,7)` | YES | NULL | — | Range −90..90 | Yes |
| `longitude` | `DECIMAL(10,7)` | YES | NULL | — | Range −180..180 | Yes |
| `is_public` | `TINYINT(1)` | NO | `0` | IX | Public profile visibility (appendix 5 clause 8) | — |
| `public_approved_at` | `DATETIME(3)` | YES | NULL | — | Must be set when `is_public = 1` | — |
| `approved_by_id` | `BIGINT UNSIGNED` | YES | NULL | FK | Admin approver | — |
| `created_at` | `DATETIME(3)` | NO | `CURRENT_TIMESTAMP(3)` | — | — | — |
| `updated_at` | `DATETIME(3)` | YES | NULL | — | — | — |

¹ `ix_usr_profiles_name (last_name, first_name)` supports admin search.
`national_id` is encrypted at the application layer but kept searchable via `mer_store_identifiers`
for merchant duplicate checks; the unique index applies to the deterministic ciphertext, so
equality lookups still work.

### `usr_consumer_profiles`

| Column | Type | Null | Default | Index | Notes | PII |
|---|---|---|---|---|---|---|
| `id` | `BIGINT UNSIGNED` | NO | auto | PK | — | — |
| `user_id` | `BIGINT UNSIGNED` | NO | — | FK, UQ | — | — |
| `tier_id` | `BIGINT UNSIGNED` | YES | NULL | FK | → `loy_tiers.id`; denormalised for the panel | — |
| `stars_count` | `SMALLINT UNSIGNED` | NO | `0` | IX | `floor(lifetime_points / 100)` (appendix 4 clause 9), monotonic | — |
| `lifetime_points` | `BIGINT` | NO | `0` | — | Never decreases (penalties reduce balance, not lifetime) | — |
| `lifetime_purchase_amount` | `BIGINT` | NO | `0` | — | Sum of paid invoices, Toman | — |
| `orders_count` | `INT UNSIGNED` | NO | `0` | — | — | — |
| `preferred_city_id` | `BIGINT UNSIGNED` | YES | NULL | FK | Drives shipping estimates | — |
| `referrer_user_id` | `BIGINT UNSIGNED` | YES | NULL | FK | Write-once at first order (appendix 4 clause 13) | — |
| `last_activity_at` | `DATETIME(3)` | YES | NULL | IX | Inactivity penalty input | — |
| `created_at` | `DATETIME(3)` | NO | `CURRENT_TIMESTAMP(3)` | — | — | — |
| `updated_at` | `DATETIME(3)` | YES | NULL | — | — | — |

### `usr_addresses`

| Column | Type | Null | Default | Index | Notes | PII |
|---|---|---|---|---|---|---|
| `id` | `BIGINT UNSIGNED` | NO | auto | PK | — | — |
| `user_id` | `BIGINT UNSIGNED` | NO | — | FK, IX | — | — |
| `label` | `VARCHAR(40)` | YES | NULL | — | `home`,`work`, free text | — |
| `recipient_name` | `VARCHAR(160)` | NO | — | — | — | Yes |
| `recipient_mobile` | `VARCHAR(15)` | NO | — | IX | — | Yes |
| `province_id` | `BIGINT UNSIGNED` | NO | — | FK | — | — |
| `city_id` | `BIGINT UNSIGNED` | NO | — | FK | — | — |
| `postal_code` | `VARCHAR(10)` | NO | — | IX | 10 digits | Yes |
| `address_line` | `VARCHAR(512)` | NO | — | — | — | Yes |
| `latitude` | `DECIMAL(10,7)` | YES | NULL | — | Optional map pin | Yes |
| `longitude` | `DECIMAL(10,7)` | YES | NULL | — | — | Yes |
| `is_default` | `TINYINT(1)` | NO | `0` | GEN | `default_user_key` unique per user | — |
| `created_at` | `DATETIME(3)` | NO | `CURRENT_TIMESTAMP(3)` | — | — | — |
| `updated_at` | `DATETIME(3)` | YES | NULL | — | — | — |
| `deleted_at` | `DATETIME(3)` | YES | NULL | IX | Soft delete; orders keep a snapshot | — |

### `usr_favorites`

| Column | Type | Null | Default | Index | Notes | PII |
|---|---|---|---|---|---|---|
| `id` | `BIGINT UNSIGNED` | NO | auto | PK | — | — |
| `user_id` | `BIGINT UNSIGNED` | NO | — | FK, UQ¹ | — | — |
| `product_id` | `BIGINT UNSIGNED` | YES | NULL | FK, UQ¹ | Exactly one target column set | — |
| `store_id` | `BIGINT UNSIGNED` | YES | NULL | FK, UQ² | — | — |
| `supplier_id` | `BIGINT UNSIGNED` | YES | NULL | FK, UQ³ | — | — |
| `created_at` | `DATETIME(3)` | NO | `CURRENT_TIMESTAMP(3)` | IX | — | — |

¹ `uq_usr_favorites_product (user_id, product_id)`; ² `uq_usr_favorites_store`;
³ `uq_usr_favorites_supplier`. `CHECK ((product_id IS NOT NULL) + (store_id IS NOT NULL) + (supplier_id IS NOT NULL) = 1)`.

### `usr_identity_documents`

| Column | Type | Null | Default | Index | Notes | PII |
|---|---|---|---|---|---|---|
| `id` | `BIGINT UNSIGNED` | NO | auto | PK | — | — |
| `user_id` | `BIGINT UNSIGNED` | NO | — | FK | — | — |
| `document_type` | `VARCHAR(32)` | NO | — | IX | `national_card`,`business_license`,`postal_receipt`,`contract` | — |
| `media_id` | `BIGINT UNSIGNED` | NO | — | FK | → `shr_media` on the private disk | Yes |
| `status` | `VARCHAR(16)` | NO | `'pending'` | IX | `pending`,`approved`,`rejected`,`expired` | — |
| `reviewed_by_id` | `BIGINT UNSIGNED` | YES | NULL | FK | — | — |
| `reviewed_at` | `DATETIME(3)` | YES | NULL | — | — | — |
| `reject_reason` | `VARCHAR(255)` | YES | NULL | — | Shown to the applicant | — |
| `expires_at` | `DATE` | YES | NULL | — | Licences expire | — |
| `created_at` | `DATETIME(3)` | NO | `CURRENT_TIMESTAMP(3)` | — | — | — |
| `updated_at` | `DATETIME(3)` | YES | NULL | — | — | — |

Invariant: approving a `business_license` is the gate for `mer_stores.status='active'`.

### `usr_consents`

| Column | Type | Null | Default | Index | Notes | PII |
|---|---|---|---|---|---|---|
| `id` | `BIGINT UNSIGNED` | NO | auto | PK | — | — |
| `user_id` | `BIGINT UNSIGNED` | NO | — | FK, UQ¹ | — | — |
| `consent_key` | `VARCHAR(40)` | NO | — | UQ¹ | `terms`,`privacy`,`marketing`,`location_tracking` | — |
| `document_version` | `VARCHAR(20)` | NO | — | UQ¹ | Matches `cms_pages` version or a settings value | — |
| `accepted_at` | `DATETIME(3)` | NO | `CURRENT_TIMESTAMP(3)` | — | — | — |
| `ip_address` | `VARBINARY(16)` | YES | NULL | — | — | Yes |
| `revoked_at` | `DATETIME(3)` | YES | NULL | — | Marketing and location consent are revocable | — |

¹ `uq_usr_consents_user_key_version (user_id, consent_key, document_version)`.

### `usr_settings`

| Column | Type | Null | Default | Index | Notes | PII |
|---|---|---|---|---|---|---|
| `id` | `BIGINT UNSIGNED` | NO | auto | PK | — | — |
| `user_id` | `BIGINT UNSIGNED` | NO | — | FK, UQ | — | — |
| `locale` | `VARCHAR(10)` | NO | `'fa'` | — | — | — |
| `theme` | `VARCHAR(16)` | NO | `'system'` | — | `light`,`dark`,`system` | — |
| `default_role_key` | `VARCHAR(32)` | YES | NULL | — | Must be one of the user's active roles | — |
| `marketing_opt_in` | `TINYINT(1)` | NO | `0` | — | Mirrors `usr_consents.marketing` | — |
| `must_change_password` | `TINYINT(1)` | NO | `0` | — | Forced on marketer-created accounts (appendix 6 clause 8) | — |
| `quiet_hours_json` | `JSON` | YES | NULL | — | `{from:"22:00",to:"08:00"}` in `Asia/Tehran` | — |
| `created_at` | `DATETIME(3)` | NO | `CURRENT_TIMESTAMP(3)` | — | — | — |
| `updated_at` | `DATETIME(3)` | YES | NULL | — | — | — |

---

## 3. Merchants

### `mer_stores`

A trade store (همکار) onboarded publicly or by a marketer (appendix 3-1, appendix 6 clause 1).

| Column | Type | Null | Default | Index | Notes | PII |
|---|---|---|---|---|---|---|
| `id` | `BIGINT UNSIGNED` | NO | auto | PK | — | — |
| `public_id` | `CHAR(26)` | NO | — | UQ | API/URL identifier | — |
| `owner_user_id` | `BIGINT UNSIGNED` | NO | — | FK, UQ | One store account per user | — |
| `store_code` | `VARCHAR(24)` | NO | — | UQ | Human code used on invoices and by the marketer | — |
| `legal_name` | `VARCHAR(160)` | NO | — | — | Registered name | Yes |
| `display_name` | `VARCHAR(160)` | NO | — | IX | Storefront name | — |
| `slug` | `VARCHAR(160)` | NO | — | UQ | Public URL segment, permanent after publish | — |
| `business_type` | `VARCHAR(32)` | YES | NULL | — | `retail`,`wholesale`,`installer`,`contractor` | — |
| `category_id` | `BIGINT UNSIGNED` | YES | NULL | FK | → `cat_categories.id`; the store's trade category | — |
| `national_id` | `CHAR(10)` | YES | NULL | UQ | Encrypted at rest, deterministic for uniqueness | Yes* |
| `postal_code` | `VARCHAR(10)` | YES | NULL | IX | Not unique: a mall shares one | Yes |
| `mobile_primary` | `VARCHAR(15)` | YES | NULL | IX | May differ from the owner's login mobile | Yes |
| `mobile_whatsapp` | `VARCHAR(15)` | YES | NULL | — | Whether the number has WhatsApp matters to marketers (appendix 6 clause 1) | Yes |
| `landline` | `VARCHAR(15)` | YES | NULL | — | — | Yes |
| `province_id` | `BIGINT UNSIGNED` | NO | — | FK | — | — |
| `city_id` | `BIGINT UNSIGNED` | NO | — | FK | — | — |
| `address_line` | `VARCHAR(512)` | NO | — | — | — | Yes |
| `latitude` | `DECIMAL(10,7)` | YES | NULL | — | Google Maps pin (appendix 6 clause 2) | Yes |
| `longitude` | `DECIMAL(10,7)` | YES | NULL | — | — | Yes |
| `google_maps_url` | `VARCHAR(255)` | YES | NULL | — | — | — |
| `services_description` | `TEXT` | YES | NULL | — | Services offered (appendix 6 clause 1) | — |
| `status` | `VARCHAR(20)` | NO | `'draft'` | IX | `draft`,`pending_review`,`active`,`inactive`,`suspended`,`rejected` | — |
| `price_list_id` | `BIGINT UNSIGNED` | YES | NULL | FK | What this store pays; mandatory before `active` | — |
| `marketer_id` | `BIGINT UNSIGNED` | YES | NULL | FK | Attributed agent; write-once after confirmation | — |
| `registration_source` | `VARCHAR(16)` | NO | `'public'` | IX | `public`,`marketer`,`admin` | — |
| `marketer_edit_locked_at` | `DATETIME(3)` | YES | NULL | — | Set 5 minutes after marketer registration (appendix 6 clause 9) | — |
| `activated_at` | `DATETIME(3)` | YES | NULL | — | — | — |
| `deactivated_at` | `DATETIME(3)` | YES | NULL | — | — | — |
| `inactivity_reason` | `VARCHAR(255)` | YES | NULL | — | Justifies auto-inactivation (appendix 4 clause 23) | — |
| `rating_avg` | `DECIMAL(3,2)` | NO | `0.00` | IX | Projection of published reviews | — |
| `completed_orders_count` | `INT UNSIGNED` | NO | `0` | — | — | — |
| `last_order_at` | `DATETIME(3)` | YES | NULL | IX | Inactivity detection | — |
| `approved_by_id` | `BIGINT UNSIGNED` | YES | NULL | FK | — | — |
| `created_at` | `DATETIME(3)` | NO | `CURRENT_TIMESTAMP(3)` | IX | — | — |
| `updated_at` | `DATETIME(3)` | YES | NULL | — | — | — |
| `deleted_at` | `DATETIME(3)` | YES | NULL | — | Soft delete; `RESTRICT` from orders/invoices | — |

Indexes: `uq_mer_stores_store_code (store_code)`, `uq_mer_stores_slug (slug, deleted_at)`,
`uq_mer_stores_owner (owner_user_id)`, `ix_mer_stores_status_city (status, city_id)`,
`ix_mer_stores_marketer (marketer_id, status)`, `ix_mer_stores_last_order (last_order_at)`.
Invariants: `status='active'` requires `price_list_id`, `national_id`, an approved
`business_license` and `approved_by_id`; `marketer_edit_locked_at` blocks marketer edits.

### `mer_store_locations`

| Column | Type | Null | Default | Index | Notes | PII |
|---|---|---|---|---|---|---|
| `id` | `BIGINT UNSIGNED` | NO | auto | PK | — | — |
| `store_id` | `BIGINT UNSIGNED` | NO | — | FK, IX | — | — |
| `name` | `VARCHAR(160)` | NO | — | — | Branch label | — |
| `province_id` | `BIGINT UNSIGNED` | NO | — | FK | — | — |
| `city_id` | `BIGINT UNSIGNED` | NO | — | FK | — | — |
| `address_line` | `VARCHAR(512)` | NO | — | — | — | Yes |
| `postal_code` | `VARCHAR(10)` | YES | NULL | — | — | Yes |
| `latitude` | `DECIMAL(10,7)` | YES | NULL | — | — | Yes |
| `longitude` | `DECIMAL(10,7)` | YES | NULL | — | — | Yes |
| `phone` | `VARCHAR(15)` | YES | NULL | — | — | Yes |
| `opening_hours_json` | `JSON` | YES | NULL | — | `{"sat":{"from":"09:00","to":"19:00"},…}` | — |
| `is_primary` | `TINYINT(1)` | NO | `0` | GEN | Unique per store where true | — |
| `status` | `VARCHAR(16)` | NO | `'active'` | IX | `active`,`inactive` | — |
| `created_at` | `DATETIME(3)` | NO | `CURRENT_TIMESTAMP(3)` | — | — | — |
| `updated_at` | `DATETIME(3)` | YES | NULL | — | — | — |
| `deleted_at` | `DATETIME(3)` | YES | NULL | — | — | — |

### `mer_store_media`

| Column | Type | Null | Default | Index | Notes | PII |
|---|---|---|---|---|---|---|
| `id` | `BIGINT UNSIGNED` | NO | auto | PK | — | — |
| `store_id` | `BIGINT UNSIGNED` | NO | — | FK, IX | — | — |
| `media_id` | `BIGINT UNSIGNED` | NO | — | FK | → `shr_media.id` | — |
| `kind` | `VARCHAR(24)` | NO | — | IX | `storefront`,`owner`,`business_card`,`gallery`,`interior` | — |
| `caption` | `VARCHAR(160)` | YES | NULL | — | — | — |
| `sort_order` | `SMALLINT UNSIGNED` | NO | `0` | — | — | — |
| `created_at` | `DATETIME(3)` | NO | `CURRENT_TIMESTAMP(3)` | — | — | — |

Invariant: `kind='owner'` rows are served only to the owner and admins.

### `mer_store_identifiers`

Duplicate-detection index (appendix 5 clause 3) that keeps PII out of hot queries.

| Column | Type | Null | Default | Index | Notes | PII |
|---|---|---|---|---|---|---|
| `id` | `BIGINT UNSIGNED` | NO | auto | PK | — | — |
| `store_id` | `BIGINT UNSIGNED` | NO | — | FK | `CASCADE` on store delete | — |
| `identifier_type` | `VARCHAR(16)` | NO | — | UQ¹ | `national_id`,`postal_code`,`mobile` | — |
| `identifier_hash` | `CHAR(64)` | NO | — | UQ¹ | SHA-256 of the normalised value + pepper | Yes† |
| `last4` | `VARCHAR(4)` | YES | NULL | — | Shown to the marketer as a duplicate hint | Yes |
| `created_at` | `DATETIME(3)` | NO | `CURRENT_TIMESTAMP(3)` | — | — | — |

¹ Unique for `national_id` and `mobile`; a second non-unique index
`ix_mer_store_identifiers_type_hash (identifier_type, identifier_hash)` carries
`postal_code`, where duplicates are flagged not blocked.
Check constraint: `identifier_type <> 'postal_code'` on the unique index is enforced through a
generated `unique_guard` column (`IF(identifier_type='postal_code', NULL, identifier_hash)`).

### `mer_store_status_history`

| Column | Type | Null | Default | Index | Notes | PII |
|---|---|---|---|---|---|---|
| `id` | `BIGINT UNSIGNED` | NO | auto | PK | — | — |
| `store_id` | `BIGINT UNSIGNED` | NO | — | FK, IX | — | — |
| `from_status` | `VARCHAR(20)` | YES | NULL | — | `NULL` on creation | — |
| `to_status` | `VARCHAR(20)` | NO | — | IX | — | — |
| `reason_code` | `VARCHAR(32)` | NO | — | — | `inactivity`,`admin_action`,`reapproval`,`duplicate` | — |
| `reason_text` | `VARCHAR(255)` | YES | NULL | — | — | — |
| `actor_type` | `VARCHAR(16)` | NO | — | — | `system`,`admin`,`marketer` | — |
| `actor_id` | `BIGINT UNSIGNED` | YES | NULL | FK | `NULL` for system | — |
| `occurred_at` | `DATETIME(3)` | NO | `CURRENT_TIMESTAMP(3)` | IX | — | — |

### `mer_supplier_profiles`

Producer/distributor with its own panel (appendix 11 clause 1).

| Column | Type | Null | Default | Index | Notes | PII |
|---|---|---|---|---|---|---|
| `id` | `BIGINT UNSIGNED` | NO | auto | PK | — | — |
| `public_id` | `CHAR(26)` | NO | — | UQ | — | — |
| `owner_user_id` | `BIGINT UNSIGNED` | NO | — | FK, UQ | — | — |
| `supplier_code` | `VARCHAR(24)` | NO | — | UQ | — | — |
| `legal_name` | `VARCHAR(160)` | NO | — | — | — | Yes |
| `brand_name` | `VARCHAR(160)` | NO | — | IX | Public brand | — |
| `slug` | `VARCHAR(160)` | NO | — | UQ | — | — |
| `national_id` | `CHAR(10)` | NO | — | UQ | Required before activation | Yes* |
| `economic_code` | `VARCHAR(20)` | YES | NULL | IX | Required before activation | Yes |
| `registration_number` | `VARCHAR(20)` | YES | NULL | — | Company registration | Yes |
| `established_year` | `SMALLINT UNSIGNED` | YES | NULL | — | Jalali year | — |
| `employee_count` | `INT UNSIGNED` | YES | NULL | — | — | — |
| `postal_code` | `VARCHAR(10)` | YES | NULL | — | — | Yes |
| `mobile` | `VARCHAR(15)` | YES | NULL | — | — | Yes |
| `landline` | `VARCHAR(15)` | YES | NULL | — | — | Yes |
| `province_id` | `BIGINT UNSIGNED` | NO | — | FK | — | — |
| `city_id` | `BIGINT UNSIGNED` | NO | — | FK | — | — |
| `address_line` | `VARCHAR(512)` | NO | — | — | — | Yes |
| `latitude` | `DECIMAL(10,7)` | YES | NULL | — | Map location (appendix 11 clause 3) | Yes |
| `longitude` | `DECIMAL(10,7)` | YES | NULL | — | — | Yes |
| `website_url` | `VARCHAR(255)` | YES | NULL | — | — | — |
| `social_links_json` | `JSON` | YES | NULL | — | — | — |
| `description` | `TEXT` | YES | NULL | — | Company history, capabilities | — |
| `status` | `VARCHAR(20)` | NO | `'draft'` | IX | Same lifecycle as stores | — |
| `price_list_id` | `BIGINT UNSIGNED` | YES | NULL | FK | List applied when buying from others | — |
| `is_buyer_partner` | `TINYINT(1)` | NO | `0` | — | May buy from other suppliers at a fixed discount (appendix 11 clause 2) | — |
| `buyer_discount_percent` | `DECIMAL(6,3)` | YES | NULL | — | Required when `is_buyer_partner = 1`; snapshotted on the line | — |
| `rating_avg` | `DECIMAL(3,2)` | NO | `0.00` | IX | — | — |
| `violation_score` | `INT` | NO | `0` | — | Negative points; shown after admin confirmation (appendix 5 clause 14) | — |
| `approved_by_id` | `BIGINT UNSIGNED` | YES | NULL | FK | — | — |
| `activated_at` | `DATETIME(3)` | YES | NULL | — | — | — |
| `created_at` | `DATETIME(3)` | NO | `CURRENT_TIMESTAMP(3)` | IX | — | — |
| `updated_at` | `DATETIME(3)` | YES | NULL | — | — | — |
| `deleted_at` | `DATETIME(3)` | YES | NULL | — | — | — |

Invariants: `CHECK (is_buyer_partner = 0 OR buyer_discount_percent IS NOT NULL)`;
activation requires `national_id`, `economic_code` and an approved licence.

### `mer_supplier_branches`

| Column | Type | Null | Default | Index | Notes | PII |
|---|---|---|---|---|---|---|
| `id` | `BIGINT UNSIGNED` | NO | auto | PK | — | — |
| `supplier_id` | `BIGINT UNSIGNED` | NO | — | FK, IX | — | — |
| `title` | `VARCHAR(160)` | NO | — | — | Agency name | — |
| `province_id` | `BIGINT UNSIGNED` | NO | — | FK | — | — |
| `city_id` | `BIGINT UNSIGNED` | NO | — | FK | — | — |
| `address_line` | `VARCHAR(512)` | NO | — | — | — | Yes |
| `latitude` | `DECIMAL(10,7)` | NO | — | — | Required: customers are referred here for measurement | Yes |
| `longitude` | `DECIMAL(10,7)` | NO | — | — | — | Yes |
| `phone` | `VARCHAR(15)` | YES | NULL | — | — | Yes |
| `contact_name` | `VARCHAR(120)` | YES | NULL | — | — | Yes |
| `services_json` | `JSON` | YES | NULL | — | `["installation","measurement","after_sales"]` | — |
| `is_active` | `TINYINT(1)` | NO | `1` | IX | — | — |
| `created_at` | `DATETIME(3)` | NO | `CURRENT_TIMESTAMP(3)` | — | — | — |
| `updated_at` | `DATETIME(3)` | YES | NULL | — | — | — |

### `mer_supplier_media`

| Column | Type | Null | Default | Index | Notes | PII |
|---|---|---|---|---|---|---|
| `id` | `BIGINT UNSIGNED` | NO | auto | PK | — | — |
| `supplier_id` | `BIGINT UNSIGNED` | NO | — | FK, IX | — | — |
| `media_id` | `BIGINT UNSIGNED` | NO | — | FK | — | — |
| `kind` | `VARCHAR(16)` | NO | — | IX | `logo`,`cover`,`gallery`,`video`,`catalog` | — |
| `caption` | `VARCHAR(160)` | YES | NULL | — | — | — |
| `sort_order` | `SMALLINT UNSIGNED` | NO | `0` | — | — | — |
| `created_at` | `DATETIME(3)` | NO | `CURRENT_TIMESTAMP(3)` | — | — | — |

### `mer_marketer_profiles`

Field agent identity, referral assets and plan (appendix 6).

| Column | Type | Null | Default | Index | Notes | PII |
|---|---|---|---|---|---|---|
| `id` | `BIGINT UNSIGNED` | NO | auto | PK | — | — |
| `user_id` | `BIGINT UNSIGNED` | NO | — | FK, UQ | — | — |
| `marketer_code` | `VARCHAR(24)` | NO | — | UQ | Printed on the business card; immutable | — |
| `referral_slug` | `VARCHAR(60)` | NO | — | UQ | `/r/<slug>` landing | — |
| `qr_token` | `CHAR(32)` | NO | — | UQ | Payload of the QR image (appendix 5 clause 1) | — |
| `qr_media_id` | `BIGINT UNSIGNED` | YES | NULL | FK | Rendered PNG/SVG in `shr_media` | — |
| `plan_id` | `BIGINT UNSIGNED` | NO | — | FK | → `cmp_commission_plans.id` | — |
| `parent_marketer_id` | `BIGINT UNSIGNED` | YES | NULL | FK | Team hierarchy; cannot reference self | — |
| `province_id` | `BIGINT UNSIGNED` | YES | NULL | FK | — | — |
| `city_id` | `BIGINT UNSIGNED` | YES | NULL | FK | — | — |
| `national_id` | `CHAR(10)` | YES | NULL | UQ | Required for payout | Yes* |
| `hired_at` | `DATE` | YES | NULL | — | — | — |
| `status` | `VARCHAR(16)` | NO | `'active'` | IX | `active`,`suspended`,`inactive`,`terminated` | — |
| `commission_disabled_at` | `DATETIME(3)` | YES | NULL | — | Suppresses accrual without termination (appendix 4 clause 22) | — |
| `commission_disabled_by_id` | `BIGINT UNSIGNED` | YES | NULL | FK | — | — |
| `visits_count` | `INT UNSIGNED` | NO | `0` | — | Projection of `geo_visits` | — |
| `onboarded_stores_count` | `INT UNSIGNED` | NO | `0` | — | Projection of confirmed attributions | — |
| `onboarded_orders_amount` | `BIGINT` | NO | `0` | — | Lifetime attributed sales, Toman | — |
| `last_activity_at` | `DATETIME(3)` | YES | NULL | IX | — | — |
| `created_at` | `DATETIME(3)` | NO | `CURRENT_TIMESTAMP(3)` | — | — | — |
| `updated_at` | `DATETIME(3)` | YES | NULL | — | — | — |
| `deleted_at` | `DATETIME(3)` | YES | NULL | — | — | — |

### `mer_marketer_documents`

| Column | Type | Null | Default | Index | Notes | PII |
|---|---|---|---|---|---|---|
| `id` | `BIGINT UNSIGNED` | NO | auto | PK | — | — |
| `marketer_id` | `BIGINT UNSIGNED` | NO | — | FK, IX | — | — |
| `document_type` | `VARCHAR(32)` | NO | — | IX | `contract`,`national_card`,`guarantee_cheque` | — |
| `media_id` | `BIGINT UNSIGNED` | NO | — | FK | Private disk | Yes |
| `signed_at` | `DATE` | YES | NULL | — | — | — |
| `expires_at` | `DATE` | YES | NULL | IX | Expiry blocks new attributions | — |
| `status` | `VARCHAR(16)` | NO | `'pending'` | IX | `pending`,`approved`,`rejected` | — |
| `created_at` | `DATETIME(3)` | NO | `CURRENT_TIMESTAMP(3)` | — | — | — |
| `updated_at` | `DATETIME(3)` | YES | NULL | — | — | — |

---

## 4. Catalog

### `cat_categories`

| Column | Type | Null | Default | Index | Notes | PII |
|---|---|---|---|---|---|---|
| `id` | `BIGINT UNSIGNED` | NO | auto | PK | — | — |
| `parent_id` | `BIGINT UNSIGNED` | YES | NULL | FK, IX | `NULL` = root | — |
| `path` | `VARCHAR(255)` | NO | `'/'` | IX | Materialised path `/1/7/22/` | — |
| `depth` | `TINYINT UNSIGNED` | NO | `0` | — | 0–3 (max depth 4) | — |
| `name_fa` | `VARCHAR(160)` | NO | — | — | — | — |
| `name_en` | `VARCHAR(160)` | YES | NULL | — | — | — |
| `slug` | `VARCHAR(160)` | NO | — | UQ | Unique among live rows | — |
| `description` | `TEXT` | YES | NULL | — | SEO body copy | — |
| `icon_media_id` | `BIGINT UNSIGNED` | YES | NULL | FK | — | — |
| `sort_order` | `SMALLINT UNSIGNED` | NO | `0` | IX | — | — |
| `is_active` | `TINYINT(1)` | NO | `1` | IX | — | — |
| `seo_title` | `VARCHAR(160)` | YES | NULL | — | — | — |
| `seo_description` | `VARCHAR(320)` | YES | NULL | — | — | — |
| `product_count` | `INT UNSIGNED` | NO | `0` | — | Cached projection | — |
| `created_by_id` | `BIGINT UNSIGNED` | YES | NULL | FK | — | — |
| `updated_by_id` | `BIGINT UNSIGNED` | YES | NULL | FK | — | — |
| `created_at` | `DATETIME(3)` | NO | `CURRENT_TIMESTAMP(3)` | — | — | — |
| `updated_at` | `DATETIME(3)` | YES | NULL | — | — | — |
| `deleted_at` | `DATETIME(3)` | YES | NULL | IX | — | — |

Check: `CHECK (depth BETWEEN 0 AND 3)`; `CHECK (parent_id <> id)`.

### `cat_category_attributes`

| Column | Type | Null | Default | Index | Notes | PII |
|---|---|---|---|---|---|---|
| `id` | `BIGINT UNSIGNED` | NO | auto | PK | — | — |
| `category_id` | `BIGINT UNSIGNED` | NO | — | FK, UQ¹ | — | — |
| `attribute_id` | `BIGINT UNSIGNED` | NO | — | FK, UQ¹ | — | — |
| `is_required` | `TINYINT(1)` | NO | `0` | — | Blocks publish when missing | — |
| `is_filterable` | `TINYINT(1)` | NO | `0` | — | Only if the attribute is filterable | — |
| `is_comparable` | `TINYINT(1)` | NO | `0` | — | Comparison table (appendix 2 clause 5) | — |
| `sort_order` | `SMALLINT UNSIGNED` | NO | `0` | — | — | — |

¹ `uq_cat_category_attributes (category_id, attribute_id)`.

### `cat_brands`

| Column | Type | Null | Default | Index | Notes | PII |
|---|---|---|---|---|---|---|
| `id` | `BIGINT UNSIGNED` | NO | auto | PK | — | — |
| `name_fa` | `VARCHAR(160)` | NO | — | IX | — | — |
| `name_en` | `VARCHAR(160)` | YES | NULL | — | — | — |
| `slug` | `VARCHAR(160)` | NO | — | UQ | Permanent once published | — |
| `logo_media_id` | `BIGINT UNSIGNED` | YES | NULL | FK | — | — |
| `supplier_id` | `BIGINT UNSIGNED` | YES | NULL | FK | Optional owner of an imported brand | — |
| `description` | `TEXT` | YES | NULL | — | — | — |
| `is_active` | `TINYINT(1)` | NO | `1` | IX | — | — |
| `sort_order` | `SMALLINT UNSIGNED` | NO | `0` | — | — | — |
| `seo_title` | `VARCHAR(160)` | YES | NULL | — | — | — |
| `seo_description` | `VARCHAR(320)` | YES | NULL | — | — | — |
| `created_at` | `DATETIME(3)` | NO | `CURRENT_TIMESTAMP(3)` | — | — | — |
| `updated_at` | `DATETIME(3)` | YES | NULL | — | — | — |
| `deleted_at` | `DATETIME(3)` | YES | NULL | — | — | — |

### `cat_units`

| Column | Type | Null | Default | Index | Notes | PII |
|---|---|---|---|---|---|---|
| `id` | `BIGINT UNSIGNED` | NO | auto | PK | — | — |
| `code` | `VARCHAR(16)` | NO | — | UQ | `m2`,`m`,`pcs`,`roll`,`box`,`pack` | — |
| `name_fa` | `VARCHAR(40)` | NO | — | — | متر مربع، متر، عدد، رول، جعبه | — |
| `precision` | `TINYINT UNSIGNED` | NO | `0` | — | Allowed decimal places for quantity | — |
| `is_active` | `TINYINT(1)` | NO | `1` | — | — | — |
| `created_at` | `DATETIME(3)` | NO | `CURRENT_TIMESTAMP(3)` | — | — | — |
| `updated_at` | `DATETIME(3)` | YES | NULL | — | — | — |

### `cat_products`

| Column | Type | Null | Default | Index | Notes | PII |
|---|---|---|---|---|---|---|
| `id` | `BIGINT UNSIGNED` | NO | auto | PK | — | — |
| `public_id` | `CHAR(26)` | NO | — | UQ | — | — |
| `sku` | `VARCHAR(64)` | NO | — | UQ | Seller-defined; unique platform-wide | — |
| `slug` | `VARCHAR(160)` | NO | — | UQ¹ | — | — |
| `title_fa` | `VARCHAR(200)` | NO | — | FT | Full-text with ngram parser | — |
| `title_en` | `VARCHAR(200)` | YES | NULL | — | — | — |
| `short_description` | `VARCHAR(500)` | YES | NULL | — | Listing card copy | — |
| `description` | `MEDIUMTEXT` | YES | NULL | FT | Long copy | — |
| `product_type` | `VARCHAR(16)` | NO | `'variant'` | IX | `simple`,`variant`,`service` | — |
| `supplier_id` | `BIGINT UNSIGNED` | YES | NULL | FK, IX | XOR with `store_id` | — |
| `store_id` | `BIGINT UNSIGNED` | YES | NULL | FK, IX | — | — |
| `brand_id` | `BIGINT UNSIGNED` | YES | NULL | FK | Mandatory before `active` | — |
| `primary_category_id` | `BIGINT UNSIGNED` | NO | — | FK, IX | — | — |
| `unit_id` | `BIGINT UNSIGNED` | NO | — | FK | — | — |
| `status` | `VARCHAR(16)` | NO | `'draft'` | IX | `draft`,`pending_review`,`active`,`inactive`,`archived` | — |
| `is_wholesale_only` | `TINYINT(1)` | NO | `0` | IX | Hidden from consumer list prices | — |
| `min_order_quantity` | `DECIMAL(12,3)` | NO | `1.000` | — | Enforced at cart add | — |
| `max_order_quantity` | `DECIMAL(12,3)` | YES | NULL | — | `NULL` = no cap | — |
| `step_quantity` | `DECIMAL(12,3)` | NO | `1.000` | — | Quantity must be a multiple | — |
| `weight_grams` | `INT UNSIGNED` | YES | NULL | — | Shipping price basis | — |
| `warranty_months` | `SMALLINT UNSIGNED` | YES | NULL | — | — | — |
| `country_of_origin` | `CHAR(2)` | YES | NULL | — | ISO-3166 alpha-2 | — |
| `has_variants` | `TINYINT(1)` | NO | `1` | — | `0` ⇒ single default variant | — |
| `rating_avg` | `DECIMAL(3,2)` | NO | `0.00` | IX | Projection | — |
| `reviews_count` | `INT UNSIGNED` | NO | `0` | — | Projection | — |
| `sales_count` | `INT UNSIGNED` | NO | `0` | IX | Projection | — |
| `published_at` | `DATETIME(3)` | YES | NULL | IX | Set on first activation | — |
| `seo_title` | `VARCHAR(160)` | YES | NULL | — | — | — |
| `seo_description` | `VARCHAR(320)` | YES | NULL | — | — | — |
| `search_keywords` | `VARCHAR(500)` | YES | NULL | — | — | — |
| `created_by_id` | `BIGINT UNSIGNED` | YES | NULL | FK | Supplier or admin | — |
| `updated_by_id` | `BIGINT UNSIGNED` | YES | NULL | FK | — | — |
| `created_at` | `DATETIME(3)` | NO | `CURRENT_TIMESTAMP(3)` | IX | — | — |
| `updated_at` | `DATETIME(3)` | YES | NULL | — | — | — |
| `deleted_at` | `DATETIME(3)` | YES | NULL | IX | Only for products with no order history | — |

¹ `uq_cat_products_slug (slug, deleted_at)`.
Check: `CHECK ((supplier_id IS NOT NULL) + (store_id IS NOT NULL) = 1)`;
`CHECK (min_order_quantity > 0 AND step_quantity > 0)`;
`CHECK (max_order_quantity IS NULL OR max_order_quantity >= min_order_quantity)`.
Full-text: `ft_cat_products_title_desc (title_fa, short_description, description) WITH PARSER ngram`.

### `cat_product_variants`

| Column | Type | Null | Default | Index | Notes | PII |
|---|---|---|---|---|---|---|
| `id` | `BIGINT UNSIGNED` | NO | auto | PK | — | — |
| `product_id` | `BIGINT UNSIGNED` | NO | — | FK, IX | — | — |
| `sku` | `VARCHAR(64)` | NO | — | UQ | — | — |
| `barcode` | `VARCHAR(32)` | YES | NULL | IX | EAN/UPC | — |
| `title` | `VARCHAR(200)` | NO | — | — | Generated label: "8mm / oak / 1.2×0.19" | — |
| `attribute_signature` | `CHAR(64)` | NO | — | UQ¹ | SHA-256 of sorted attribute value ids | — |
| `is_default` | `TINYINT(1)` | NO | `0` | GEN² | One default per product | — |
| `status` | `VARCHAR(16)` | NO | `'active'` | IX | `active`,`inactive`,`discontinued` | — |
| `weight_grams` | `INT UNSIGNED` | YES | NULL | — | Overrides the product weight | — |
| `sort_order` | `SMALLINT UNSIGNED` | NO | `0` | IX | — | — |
| `created_at` | `DATETIME(3)` | NO | `CURRENT_TIMESTAMP(3)` | — | — | — |
| `updated_at` | `DATETIME(3)` | YES | NULL | — | — | — |
| `deleted_at` | `DATETIME(3)` | YES | NULL | — | Only without stock/order history | — |

¹ `uq_cat_product_variants_signature (product_id, attribute_signature)`.
² `default_product_key BIGINT UNSIGNED GENERATED ALWAYS AS (IF(is_default = 1, product_id, NULL)) STORED` with `UNIQUE`.

### `cat_attributes`

| Column | Type | Null | Default | Index | Notes | PII |
|---|---|---|---|---|---|---|
| `id` | `BIGINT UNSIGNED` | NO | auto | PK | — | — |
| `code` | `VARCHAR(48)` | NO | — | UQ | `thickness`,`color`,`pattern`,`width` | — |
| `name_fa` | `VARCHAR(120)` | NO | — | — | — | — |
| `data_type` | `VARCHAR(16)` | NO | — | — | `string`,`number`,`boolean`,`select`,`multiselect` | — |
| `unit_label` | `VARCHAR(24)` | YES | NULL | — | `mm`,`cm`,`m²` | — |
| `is_filterable` | `TINYINT(1)` | NO | `0` | — | Drives the filter UI (appendix 2 clause 3) | — |
| `is_variant_axis` | `TINYINT(1)` | NO | `0` | — | Distinguishes variants | — |
| `is_comparable` | `TINYINT(1)` | NO | `0` | — | Comparison table | — |
| `sort_order` | `SMALLINT UNSIGNED` | NO | `0` | — | — | — |
| `created_at` | `DATETIME(3)` | NO | `CURRENT_TIMESTAMP(3)` | — | — | — |
| `updated_at` | `DATETIME(3)` | YES | NULL | — | — | — |

Check: `CHECK (is_variant_axis = 0 OR data_type IN ('select','multiselect','number','string'))`.

### `cat_attribute_values`

| Column | Type | Null | Default | Index | Notes | PII |
|---|---|---|---|---|---|---|
| `id` | `BIGINT UNSIGNED` | NO | auto | PK | — | — |
| `attribute_id` | `BIGINT UNSIGNED` | NO | — | FK, UQ¹ | — | — |
| `value_fa` | `VARCHAR(120)` | NO | — | — | — | — |
| `value_en` | `VARCHAR(120)` | YES | NULL | — | — | — |
| `slug` | `VARCHAR(120)` | NO | — | UQ¹ | ASCII slug for URLs/filters | — |
| `color_hex` | `CHAR(7)` | YES | NULL | — | `#RRGGBB` for colour swatches | — |
| `sort_order` | `SMALLINT UNSIGNED` | NO | `0` | IX | — | — |
| `created_at` | `DATETIME(3)` | NO | `CURRENT_TIMESTAMP(3)` | — | — | — |
| `updated_at` | `DATETIME(3)` | YES | NULL | — | — | — |

¹ `uq_cat_attribute_values_attr_slug (attribute_id, slug)`.

### `cat_variant_attribute_values`

| Column | Type | Null | Default | Index | Notes | PII |
|---|---|---|---|---|---|---|
| `id` | `BIGINT UNSIGNED` | NO | auto | PK | — | — |
| `variant_id` | `BIGINT UNSIGNED` | NO | — | FK, UQ¹ | `CASCADE` on variant delete | — |
| `attribute_id` | `BIGINT UNSIGNED` | NO | — | FK, UQ¹ | — | — |
| `attribute_value_id` | `BIGINT UNSIGNED` | NO | — | FK | Must belong to `attribute_id` | — |
| `numeric_value` | `DECIMAL(16,4)` | YES | NULL | IX | Populated for numeric axes so ranges filter | — |

¹ `uq_cat_variant_attribute_values (variant_id, attribute_id)`.

### `cat_media`

| Column | Type | Null | Default | Index | Notes | PII |
|---|---|---|---|---|---|---|
| `id` | `BIGINT UNSIGNED` | NO | auto | PK | — | — |
| `product_id` | `BIGINT UNSIGNED` | NO | — | FK, IX | — | — |
| `variant_id` | `BIGINT UNSIGNED` | YES | NULL | FK | Set when the image is variant-specific | — |
| `media_id` | `BIGINT UNSIGNED` | NO | — | FK | → `shr_media.id` | — |
| `kind` | `VARCHAR(12)` | NO | `'image'` | IX | `image`,`video`,`360`,`catalog` | — |
| `alt_text` | `VARCHAR(200)` | NO | — | — | Required for SEO | — |
| `sort_order` | `SMALLINT UNSIGNED` | NO | `0` | IX | — | — |
| `is_primary` | `TINYINT(1)` | NO | `0` | GEN | Unique per product | — |
| `created_at` | `DATETIME(3)` | NO | `CURRENT_TIMESTAMP(3)` | — | — | — |

### `cat_product_relations`

| Column | Type | Null | Default | Index | Notes | PII |
|---|---|---|---|---|---|---|
| `id` | `BIGINT UNSIGNED` | NO | auto | PK | — | — |
| `product_id` | `BIGINT UNSIGNED` | NO | — | FK, UQ¹ | — | — |
| `related_product_id` | `BIGINT UNSIGNED` | NO | — | FK, UQ¹ | — | — |
| `relation_type` | `VARCHAR(16)` | NO | `'related'` | IX | `related`,`alternative`,`accessory` | — |
| `sort_order` | `SMALLINT UNSIGNED` | NO | `0` | — | — | — |

¹ `uq_cat_product_relations (product_id, related_product_id, relation_type)`;
`CHECK (product_id <> related_product_id)`.

### `shr_media`

The single file registry every module references (product images, identity documents, chat
attachments, invoice PDFs, exports). Owned by the Shared kernel; it stores no business meaning.

| Column | Type | Null | Default | Index | Notes | PII |
|---|---|---|---|---|---|---|
| `id` | `BIGINT UNSIGNED` | NO | auto | PK | — | — |
| `uuid` | `CHAR(26)` | NO | — | UQ | ULID; the handle clients use before attaching | — |
| `disk` | `VARCHAR(16)` | NO | `'private'` | IX | `public`,`private` | — |
| `path` | `VARCHAR(512)` | NO | — | UQ¹ | Immutable object path; never a URL | — |
| `original_name` | `VARCHAR(255)` | YES | NULL | — | As uploaded, sanitised | Yes |
| `mime_type` | `VARCHAR(128)` | NO | — | — | Detected, never trusted from the filename | — |
| `extension` | `VARCHAR(16)` | NO | — | — | Derived from the detected MIME | — |
| `size_bytes` | `BIGINT UNSIGNED` | NO | — | — | — | — |
| `checksum_sha256` | `CHAR(64)` | NO | — | IX | Deduplicates identical uploads | — |
| `width` | `INT UNSIGNED` | YES | NULL | — | Images/video only | — |
| `height` | `INT UNSIGNED` | YES | NULL | — | — | — |
| `duration_seconds` | `INT UNSIGNED` | YES | NULL | — | Audio/video only | — |
| `visibility` | `VARCHAR(8)` | NO | `'private'` | IX | `public` (CDN) or `private` (signed URL only) | — |
| `owner_user_id` | `BIGINT UNSIGNED` | YES | NULL | FK | Uploader/owner; `NULL` for system-generated files | — |
| `uploaded_by_id` | `BIGINT UNSIGNED` | YES | NULL | FK | Differs from the owner on admin uploads | — |
| `virus_scanned_at` | `DATETIME(3)` | YES | NULL | IX | A file is attachable only when set | — |
| `created_at` | `DATETIME(3)` | NO | `CURRENT_TIMESTAMP(3)` | IX | — | — |
| `updated_at` | `DATETIME(3)` | YES | NULL | — | Metadata only | — |
| `deleted_at` | `DATETIME(3)` | YES | NULL | IX | Replaced files are soft-deleted so cached URLs keep working | — |

¹ `uq_shr_media_path (disk, path)`.
Check: `CHECK (visibility IN ('public','private'))`.
Invariants: a row is created only after the bytes are written and scanned; `path` never changes;
a private file is served only through a signed URL with a 5–15 minute TTL; `kind`-style purpose is
expressed by the referencing table (`cat_media.kind`, `mer_store_media.kind`), never here.

---

## 5. Pricing

### `pri_price_lists`

One audience-specific price book (consumer, store tiers A–D, wholesale, supplier partner).

| Column | Type | Null | Default | Index | Notes | PII |
|---|---|---|---|---|---|---|
| `id` | `BIGINT UNSIGNED` | NO | auto | PK | — | — |
| `code` | `VARCHAR(32)` | NO | — | UQ | `consumer`,`store_a`…`store_d`,`supplier_partner`,`wholesale_base` | — |
| `name_fa` | `VARCHAR(80)` | NO | — | — | — | — |
| `audience` | `VARCHAR(16)` | NO | — | IX | `consumer`,`store`,`supplier`,`marketer` | — |
| `tier` | `CHAR(1)` | YES | NULL | IX | `A`,`B`,`C`,`D`; `NULL` for non-tiered lists | — |
| `currency` | `CHAR(3)` | NO | `'IRT'` | — | — | — |
| `is_default` | `TINYINT(1)` | NO | `0` | GEN¹ | One default per audience | — |
| `priority` | `SMALLINT UNSIGNED` | NO | `100` | IX | Lower wins when several could apply | — |
| `effective_from` | `DATETIME(3)` | NO | `CURRENT_TIMESTAMP(3)` | IX | — | — |
| `effective_to` | `DATETIME(3)` | YES | NULL | — | `NULL` = open-ended | — |
| `status` | `VARCHAR(12)` | NO | `'active'` | IX | `active`,`inactive` | — |
| `created_by_id` | `BIGINT UNSIGNED` | YES | NULL | FK | — | — |
| `created_at` | `DATETIME(3)` | NO | `CURRENT_TIMESTAMP(3)` | — | — | — |
| `updated_at` | `DATETIME(3)` | YES | NULL | — | — | — |

¹ `default_audience_key VARCHAR(16) GENERATED ALWAYS AS (IF(is_default = 1 AND status = 'active' AND effective_to IS NULL, audience, NULL)) STORED` with `UNIQUE`.
Check: `CHECK (effective_to IS NULL OR effective_to > effective_from)`;
`CHECK (tier IS NULL OR audience = 'store')`.

### `pri_prices`

| Column | Type | Null | Default | Index | Notes | PII |
|---|---|---|---|---|---|---|
| `id` | `BIGINT UNSIGNED` | NO | auto | PK | — | — |
| `price_list_id` | `BIGINT UNSIGNED` | NO | — | FK, UQ¹ | — | — |
| `product_id` | `BIGINT UNSIGNED` | NO | — | FK, IX | — | — |
| `variant_id` | `BIGINT UNSIGNED` | YES | NULL | FK, UQ¹ | `NULL` = the product's default variant | — |
| `amount` | `BIGINT` | NO | — | IX | Unit price in Toman | — |
| `compare_at_amount` | `BIGINT` | YES | NULL | — | Strike-through "was" price | — |
| `min_quantity` | `DECIMAL(12,3)` | NO | `1.000` | UQ¹ | Lower bound of the volume band | — |
| `max_quantity` | `DECIMAL(12,3)` | YES | NULL | — | `NULL` = open-ended band | — |
| `includes_tax` | `TINYINT(1)` | NO | `1` | — | v1 prices are tax-inclusive | — |
| `source` | `VARCHAR(12)` | NO | `'manual'` | IX | `manual`,`import`,`rule`,`promotion` | — |
| `effective_from` | `DATETIME(3)` | NO | `CURRENT_TIMESTAMP(3)` | IX | — | — |
| `effective_to` | `DATETIME(3)` | YES | NULL | — | — | — |
| `status` | `VARCHAR(12)` | NO | `'active'` | IX | `active`,`inactive` | — |
| `approved_by_id` | `BIGINT UNSIGNED` | YES | NULL | FK | Set when a price change request is approved | — |
| `created_by_id` | `BIGINT UNSIGNED` | YES | NULL | FK | — | — |
| `updated_by_id` | `BIGINT UNSIGNED` | YES | NULL | FK | — | — |
| `created_at` | `DATETIME(3)` | NO | `CURRENT_TIMESTAMP(3)` | IX | — | — |
| `updated_at` | `DATETIME(3)` | YES | NULL | — | — | — |

¹ `uq_pri_prices_list_variant_qty (price_list_id, variant_id, min_quantity)` plus
`active_key VARCHAR(96) GENERATED ALWAYS AS (IF(status='active' AND effective_to IS NULL, CONCAT(price_list_id,':',IFNULL(variant_id,0),':',min_quantity), NULL)) STORED` with `UNIQUE` — this is what enforces "one open active price" (conventions §4.7).
Check: `CHECK (amount >= 0)`; `CHECK (max_quantity IS NULL OR max_quantity > min_quantity)`.
Invariant: every change writes `adm_audit_logs` with the before/after amount.

### `pri_volume_tiers`

| Column | Type | Null | Default | Index | Notes | PII |
|---|---|---|---|---|---|---|
| `id` | `BIGINT UNSIGNED` | NO | auto | PK | — | — |
| `price_list_id` | `BIGINT UNSIGNED` | NO | — | FK, IX | — | — |
| `scope_type` | `VARCHAR(12)` | NO | — | IX | `product`,`category`,`brand`,`supplier` | — |
| `scope_id` | `BIGINT UNSIGNED` | NO | — | IX | Target id of the scope | — |
| `min_quantity` | `DECIMAL(12,3)` | NO | — | IX | — | — |
| `max_quantity` | `DECIMAL(12,3)` | YES | NULL | — | — | — |
| `discount_percent` | `DECIMAL(6,3)` | YES | NULL | — | Percentage off the list price | — |
| `discount_amount` | `BIGINT` | YES | NULL | — | Fixed Toman off per unit | — |
| `priority` | `SMALLINT UNSIGNED` | NO | `100` | — | Higher wins inside the same scope | — |
| `effective_from` | `DATETIME(3)` | NO | `CURRENT_TIMESTAMP(3)` | IX | — | — |
| `effective_to` | `DATETIME(3)` | YES | NULL | — | — | — |
| `created_at` | `DATETIME(3)` | NO | `CURRENT_TIMESTAMP(3)` | — | — | — |
| `updated_at` | `DATETIME(3)` | YES | NULL | — | — | — |

Check: `CHECK ((discount_percent IS NOT NULL) + (discount_amount IS NOT NULL) = 1)`.

### `pri_markup_rules`

Consumer-vs-partner spread, applied manually or as a blanket percentage (appendix 12 clause 1).

| Column | Type | Null | Default | Index | Notes | PII |
|---|---|---|---|---|---|---|
| `id` | `BIGINT UNSIGNED` | NO | auto | PK | — | — |
| `price_list_id` | `BIGINT UNSIGNED` | NO | — | FK, IX | The list being marked up (usually `consumer`) | — |
| `scope_type` | `VARCHAR(12)` | NO | `'all'` | IX | `all`,`category`,`brand`,`product` | — |
| `scope_id` | `BIGINT UNSIGNED` | YES | NULL | IX | `NULL` when `scope_type='all'` | — |
| `markup_percent` | `DECIMAL(6,3)` | YES | NULL | — | e.g. `35.000` | — |
| `fixed_amount` | `BIGINT` | YES | NULL | — | Alternative to the percentage | — |
| `rounding_mode` | `VARCHAR(12)` | NO | `'none'` | — | `none`,`up_1000`,`up_5000` | — |
| `effective_from` | `DATETIME(3)` | NO | `CURRENT_TIMESTAMP(3)` | IX | — | — |
| `effective_to` | `DATETIME(3)` | YES | NULL | — | — | — |
| `created_by_id` | `BIGINT UNSIGNED` | YES | NULL | FK | — | — |
| `created_at` | `DATETIME(3)` | NO | `CURRENT_TIMESTAMP(3)` | — | — | — |
| `updated_at` | `DATETIME(3)` | YES | NULL | — | — | — |

Check: `CHECK ((markup_percent IS NOT NULL) + (fixed_amount IS NOT NULL) = 1)`.

### `pri_price_list_assignments`

| Column | Type | Null | Default | Index | Notes | PII |
|---|---|---|---|---|---|---|
| `id` | `BIGINT UNSIGNED` | NO | auto | PK | — | — |
| `price_list_id` | `BIGINT UNSIGNED` | NO | — | FK | — | — |
| `assignee_type` | `VARCHAR(12)` | NO | — | UQ¹ | `store`,`supplier`,`user` | — |
| `assignee_id` | `BIGINT UNSIGNED` | NO | — | UQ¹ | — | — |
| `assigned_by_id` | `BIGINT UNSIGNED` | YES | NULL | FK | — | — |
| `assigned_at` | `DATETIME(3)` | NO | `CURRENT_TIMESTAMP(3)` | — | — | — |
| `expires_at` | `DATETIME(3)` | YES | NULL | IX | `NULL` = permanent | — |

¹ `uq_pri_price_list_assignments (assignee_type, assignee_id, price_list_id)`.

### `pri_price_snapshots`

Write-once explanation of every order line's price (canonical §5).

| Column | Type | Null | Default | Index | Notes | PII |
|---|---|---|---|---|---|---|
| `id` | `BIGINT UNSIGNED` | NO | auto | PK | — | — |
| `order_item_id` | `BIGINT UNSIGNED` | NO | — | FK, UQ | One snapshot per line | — |
| `price_list_id` | `BIGINT UNSIGNED` | YES | NULL | FK | `NULL` for manual/admin lines | — |
| `base_unit_amount` | `BIGINT` | NO | — | — | List price before any discount | — |
| `volume_discount_amount` | `BIGINT` | NO | `0` | — | Per unit | — |
| `promotion_discount_amount` | `BIGINT` | NO | `0` | — | Per unit | — |
| `coupon_discount_amount` | `BIGINT` | NO | `0` | — | Allocated per unit | — |
| `loyalty_discount_amount` | `BIGINT` | NO | `0` | — | Tier discount + reward deduction, per unit | — |
| `final_unit_amount` | `BIGINT` | NO | — | — | What the buyer pays per unit | — |
| `applied_rules_json` | `JSON` | NO | — | — | Ordered list of `{type,id,percent,amount}` | — |
| `resolver_version` | `VARCHAR(20)` | NO | — | — | Pricing engine build | — |
| `resolved_at` | `DATETIME(3)` | NO | `CURRENT_TIMESTAMP(3)` | — | — | — |

Check: `CHECK (final_unit_amount = base_unit_amount - volume_discount_amount - promotion_discount_amount - coupon_discount_amount - loyalty_discount_amount)` — the arithmetic is enforced, not just documented.
Invariant: no `UPDATE` after insert (trigger-free: the application repository exposes only
`create`).

---

## 6. Inventory

### `stk_warehouses`

| Column | Type | Null | Default | Index | Notes | PII |
|---|---|---|---|---|---|---|
| `id` | `BIGINT UNSIGNED` | NO | auto | PK | — | — |
| `code` | `VARCHAR(24)` | NO | — | UQ | — | — |
| `name` | `VARCHAR(120)` | NO | — | — | — | — |
| `supplier_id` | `BIGINT UNSIGNED` | YES | NULL | FK, IX | `NULL` for a platform-owned buffer | — |
| `province_id` | `BIGINT UNSIGNED` | NO | — | FK | — | — |
| `city_id` | `BIGINT UNSIGNED` | NO | — | FK | — | — |
| `address_line` | `VARCHAR(512)` | YES | NULL | — | — | Yes |
| `latitude` | `DECIMAL(10,7)` | YES | NULL | — | — | Yes |
| `longitude` | `DECIMAL(10,7)` | YES | NULL | — | — | Yes |
| `is_default` | `TINYINT(1)` | NO | `0` | GEN | One default per supplier | — |
| `status` | `VARCHAR(12)` | NO | `'active'` | IX | `active`,`inactive` | — |
| `created_at` | `DATETIME(3)` | NO | `CURRENT_TIMESTAMP(3)` | — | — | — |
| `updated_at` | `DATETIME(3)` | YES | NULL | — | — | — |

### `stk_stock_items`

| Column | Type | Null | Default | Index | Notes | PII |
|---|---|---|---|---|---|---|
| `id` | `BIGINT UNSIGNED` | NO | AUTO_INCREMENT | PK | — | — |
| `warehouse_id` | `BIGINT UNSIGNED` | NO | — | FK, UQ¹ | — | — |
| `variant_id` | `BIGINT UNSIGNED` | NO | — | FK, UQ¹ | — | — |
| `product_id` | `BIGINT UNSIGNED` | NO | — | FK, IX | Denormalised for catalog joins | — |
| `on_hand_quantity` | `DECIMAL(12,3)` | NO | `0.000` | — | Physical quantity | — |
| `reserved_quantity` | `DECIMAL(12,3)` | NO | `0.000` | — | Sum of `held` + `committed` | — |
| `available_quantity` | `DECIMAL(12,3)` | NO | GEN² | IX | Generated, never written | — |
| `safety_stock` | `DECIMAL(12,3)` | NO | `0.000` | — | Never sold below | — |
| `reorder_point` | `DECIMAL(12,3)` | NO | `0.000` | — | Triggers a low-stock alert | — |
| `unit_cost_amount` | `BIGINT` | YES | NULL | — | Landed cost, for margin reports | — |
| `last_counted_at` | `DATETIME(3)` | YES | NULL | — | — | — |
| `version` | `INT UNSIGNED` | NO | `0` | — | Optimistic lock | — |
| `created_at` | `DATETIME(3)` | NO | `CURRENT_TIMESTAMP(3)` | — | — | — |
| `updated_at` | `DATETIME(3)` | YES | NULL | — | — | — |

¹ `uq_stk_stock_items_warehouse_variant (warehouse_id, variant_id)`.
² `available_quantity DECIMAL(12,3) GENERATED ALWAYS AS (on_hand_quantity - reserved_quantity) STORED`.
Check: `CHECK (on_hand_quantity >= 0)`, `CHECK (reserved_quantity >= 0)`,
`CHECK (reserved_quantity <= on_hand_quantity)`.

### `stk_stock_reservations`

| Column | Type | Null | Default | Index | Notes | PII |
|---|---|---|---|---|---|---|
| `id` | `BIGINT UNSIGNED` | NO | auto | PK | — | — |
| `stock_item_id` | `BIGINT UNSIGNED` | NO | — | FK, IX | — | — |
| `order_id` | `BIGINT UNSIGNED` | NO | — | FK, IX | — | — |
| `sub_order_id` | `BIGINT UNSIGNED` | NO | — | FK, IX | — | — |
| `order_item_id` | `BIGINT UNSIGNED` | NO | — | FK, IX | — | — |
| `quantity` | `DECIMAL(12,3)` | NO | — | — | `CHECK (quantity > 0)` | — |
| `status` | `VARCHAR(12)` | NO | `'held'` | IX | `held`,`committed`,`released`,`expired` | — |
| `expires_at` | `DATETIME(3)` | NO | — | IX | Default +30 min from order placement | — |
| `idempotency_key` | `VARCHAR(96)` | NO | — | UQ | `order:{id}:item:{id}` | — |
| `created_at` | `DATETIME(3)` | NO | `CURRENT_TIMESTAMP(3)` | — | — | — |
| `updated_at` | `DATETIME(3)` | YES | NULL | — | Status transitions only | — |

Indexes: `ix_stk_stock_reservations_status_expiry (status, expires_at)` drives the expiry job.

### `stk_stock_movements`

Append-only stock ledger.

| Column | Type | Null | Default | Index | Notes | PII |
|---|---|---|---|---|---|---|
| `id` | `BIGINT UNSIGNED` | NO | auto | PK | — | — |
| `stock_item_id` | `BIGINT UNSIGNED` | NO | — | FK, IX | — | — |
| `movement_type` | `VARCHAR(16)` | NO | — | IX | `receipt`,`issue`,`reservation`,`release`,`adjustment`,`return`,`damage`,`transfer` | — |
| `quantity_delta` | `DECIMAL(12,3)` | NO | — | — | Signed; `CHECK (quantity_delta <> 0)` | — |
| `balance_after` | `DECIMAL(12,3)` | NO | — | — | `on_hand` after the movement | — |
| `sub_order_id` | `BIGINT UNSIGNED` | YES | NULL | IX | Set for order-driven movements | — |
| `return_id` | `BIGINT UNSIGNED` | YES | NULL | IX | Set for restocks | — |
| `reason` | `VARCHAR(255)` | YES | NULL | — | Required for `adjustment` and `damage` | — |
| `actor_id` | `BIGINT UNSIGNED` | YES | NULL | FK | `NULL` for system | — |
| `occurred_at` | `DATETIME(3)` | NO | `CURRENT_TIMESTAMP(3)` | IX | — | — |

Invariant: `balance_after` must equal the previous balance for the stock item plus
`quantity_delta`; a nightly job verifies the chain.

---

## 7. Cart

### `crt_carts`

| Column | Type | Null | Default | Index | Notes | PII |
|---|---|---|---|---|---|---|
| `id` | `BIGINT UNSIGNED` | NO | auto | PK | — | — |
| `public_id` | `CHAR(26)` | NO | — | UQ | Opaque cart token for guests | — |
| `session_token` | `CHAR(40)` | YES | NULL | UQ | Guest cookie value; `NULL` once merged | — |
| `user_id` | `BIGINT UNSIGNED` | YES | NULL | FK, GEN¹ | `NULL` for guests | — |
| `currency` | `CHAR(3)` | NO | `'IRT'` | — | — | — |
| `price_list_id` | `BIGINT UNSIGNED` | YES | NULL | FK | Resolved from the buyer's roles | — |
| `status` | `VARCHAR(12)` | NO | `'active'` | IX | `active`,`converted`,`abandoned`,`merged`,`expired` | — |
| `items_count` | `INT UNSIGNED` | NO | `0` | — | Cached projection | — |
| `subtotal_amount` | `BIGINT` | NO | `0` | — | Cached projection | — |
| `discount_amount` | `BIGINT` | NO | `0` | — | Cached projection | — |
| `shipping_amount` | `BIGINT` | NO | `0` | — | Estimate until checkout | — |
| `total_amount` | `BIGINT` | NO | `0` | — | Cached projection | — |
| `coupon_id` | `BIGINT UNSIGNED` | YES | NULL | FK | Reserved coupon | — |
| `coupon_code` | `VARCHAR(40)` | YES | NULL | — | Denormalised for display | — |
| `expires_at` | `DATETIME(3)` | YES | NULL | IX | Abandonment horizon (90 days) | — |
| `converted_order_id` | `BIGINT UNSIGNED` | YES | NULL | FK, UQ | One order per cart | — |
| `last_activity_at` | `DATETIME(3)` | NO | `CURRENT_TIMESTAMP(3)` | IX | — | — |
| `lock_version` | `INT UNSIGNED` | NO | `0` | — | Optimistic lock | — |
| `created_at` | `DATETIME(3)` | NO | `CURRENT_TIMESTAMP(3)` | — | — | — |
| `updated_at` | `DATETIME(3)` | YES | NULL | — | — | — |

¹ `active_user_key BIGINT UNSIGNED GENERATED ALWAYS AS (IF(status = 'active', user_id, NULL)) STORED` with `UNIQUE` — one active cart per user.

### `crt_cart_items`

| Column | Type | Null | Default | Index | Notes | PII |
|---|---|---|---|---|---|---|
| `id` | `BIGINT UNSIGNED` | NO | auto | PK | — | — |
| `cart_id` | `BIGINT UNSIGNED` | NO | — | FK, UQ¹ | `CASCADE` on cart delete | — |
| `product_id` | `BIGINT UNSIGNED` | NO | — | FK | — | — |
| `variant_id` | `BIGINT UNSIGNED` | NO | — | FK, UQ¹ | — | — |
| `seller_type` | `VARCHAR(12)` | NO | — | IX | `supplier`,`store`; copied from the product | — |
| `seller_id` | `BIGINT UNSIGNED` | NO | — | IX | — | — |
| `quantity` | `DECIMAL(12,3)` | NO | — | — | `CHECK (quantity > 0)` | — |
| `unit_price_amount` | `BIGINT` | NO | — | — | Display snapshot; re-resolved on read | — |
| `price_list_id` | `BIGINT UNSIGNED` | YES | NULL | FK | List used for the snapshot | — |
| `price_snapshot_json` | `JSON` | YES | NULL | — | Last resolution result, for the UI breakdown | — |
| `notes` | `VARCHAR(500)` | YES | NULL | — | Buyer note for the seller | — |
| `added_at` | `DATETIME(3)` | NO | `CURRENT_TIMESTAMP(3)` | IX | — | — |
| `updated_at` | `DATETIME(3)` | YES | NULL | — | — | — |

¹ `uq_crt_cart_items_cart_variant (cart_id, variant_id)`.

### `crt_cart_events`

| Column | Type | Null | Default | Index | Notes | PII |
|---|---|---|---|---|---|---|
| `id` | `BIGINT UNSIGNED` | NO | auto | PK | — | — |
| `cart_id` | `BIGINT UNSIGNED` | NO | — | FK, IX | — | — |
| `event` | `VARCHAR(24)` | NO | — | IX | `created`,`item_added`,`item_removed`,`coupon_applied`,`checkout_started`,`converted`,`abandoned` | — |
| `payload_json` | `JSON` | YES | NULL | — | Small, PII-free payload | — |
| `occurred_at` | `DATETIME(3)` | NO | `CURRENT_TIMESTAMP(3)` | IX | Kept 180 days | — |

---

## 8. Orders

### `ord_orders`

| Column | Type | Null | Default | Index | Notes | PII |
|---|---|---|---|---|---|---|
| `id` | `BIGINT UNSIGNED` | NO | auto | PK | — | — |
| `public_id` | `CHAR(26)` | NO | — | UQ | — | — |
| `order_number` | `VARCHAR(24)` | NO | — | UQ | `DK-1405-000123`, allocated from `shr_sequences` | — |
| `buyer_user_id` | `BIGINT UNSIGNED` | NO | — | FK, IX | — | — |
| `buyer_role` | `VARCHAR(12)` | NO | — | IX | `consumer`,`store`; drives price list | — |
| `channel` | `VARCHAR(12)` | NO | `'web'` | IX | `web`,`pwa`,`admin`,`marketer` | — |
| `status` | `VARCHAR(24)` | NO | `'awaiting_payment'` | IX | See [`order-state-machine.md`](./order-state-machine.md) | — |
| `payment_status` | `VARCHAR(24)` | NO | `'unpaid'` | IX | `unpaid`,`partially_paid`,`paid`,`partially_refunded`,`refunded` | — |
| `fulfilment_status` | `VARCHAR(16)` | NO | `'unfulfilled'` | IX | `unfulfilled`,`partial`,`fulfilled` | — |
| `currency` | `CHAR(3)` | NO | `'IRT'` | — | — | — |
| `price_list_id` | `BIGINT UNSIGNED` | YES | NULL | FK | List used at checkout | — |
| `items_total_amount` | `BIGINT` | NO | `0` | — | Sum of line totals | — |
| `discount_total_amount` | `BIGINT` | NO | `0` | — | Sum of line + order discounts | — |
| `coupon_discount_amount` | `BIGINT` | NO | `0` | — | Part of the total discount | — |
| `loyalty_discount_amount` | `BIGINT` | NO | `0` | — | Tier discount + reward deduction | — |
| `shipping_total_amount` | `BIGINT` | NO | `0` | — | Sum of sub-order shipping | — |
| `tax_total_amount` | `BIGINT` | NO | `0` | — | Zero in v1 (prices are tax-inclusive) | — |
| `grand_total_amount` | `BIGINT` | NO | `0` | IX | What the buyer owes | — |
| `paid_amount` | `BIGINT` | NO | `0` | — | Captured payments | — |
| `refunded_amount` | `BIGINT` | NO | `0` | — | Completed refunds | — |
| `wallet_applied_amount` | `BIGINT` | NO | `0` | — | Wallet debited at checkout | — |
| `deposit_amount` | `BIGINT` | NO | `0` | — | Prepayment (بیعانه) when enabled | — |
| `balance_due_amount` | `BIGINT` | NO | `0` | — | Remaining for COD/delivery settlement | — |
| `coupon_id` | `BIGINT UNSIGNED` | YES | NULL | FK | — | — |
| `coupon_code` | `VARCHAR(40)` | YES | NULL | — | Snapshot of the applied code | — |
| `shipping_recipient_name` | `VARCHAR(160)` | NO | — | — | Snapshot | Yes |
| `shipping_mobile` | `VARCHAR(15)` | NO | — | IX | Snapshot | Yes |
| `shipping_province_id` | `BIGINT UNSIGNED` | NO | — | FK | Snapshot | — |
| `shipping_city_id` | `BIGINT UNSIGNED` | NO | — | FK | Snapshot | — |
| `shipping_postal_code` | `VARCHAR(10)` | YES | NULL | — | Snapshot | Yes |
| `shipping_address_line` | `VARCHAR(512)` | NO | — | — | Snapshot | Yes |
| `shipping_latitude` | `DECIMAL(10,7)` | YES | NULL | — | Snapshot | Yes |
| `shipping_longitude` | `DECIMAL(10,7)` | YES | NULL | — | Snapshot | Yes |
| `address_snapshot_json` | `JSON` | YES | NULL | — | Full address at placement, for reprints | Yes |
| `buyer_note` | `VARCHAR(1000)` | YES | NULL | — | — | — |
| `placed_at` | `DATETIME(3)` | NO | `CURRENT_TIMESTAMP(3)` | IX | — | — |
| `paid_at` | `DATETIME(3)` | YES | NULL | IX | — | — |
| `completed_at` | `DATETIME(3)` | YES | NULL | IX | — | — |
| `cancelled_at` | `DATETIME(3)` | YES | NULL | — | — | — |
| `cancelled_by_id` | `BIGINT UNSIGNED` | YES | NULL | FK | — | — |
| `cancellation_reason_code` | `VARCHAR(32)` | YES | NULL | — | — | — |
| `expires_at` | `DATETIME(3)` | YES | NULL | IX | Payment deadline, default +30 min | — |
| `idempotency_key` | `VARCHAR(96)` | NO | — | UQ | `cart:{public_id}` from checkout | — |
| `created_by_id` | `BIGINT UNSIGNED` | YES | NULL | FK | Set for admin/marketer-created orders | — |
| `lock_version` | `INT UNSIGNED` | NO | `0` | — | Optimistic lock | — |
| `created_at` | `DATETIME(3)` | NO | `CURRENT_TIMESTAMP(3)` | — | — | — |
| `updated_at` | `DATETIME(3)` | YES | NULL | — | — | — |

Indexes: `ix_ord_orders_buyer_status_placed (buyer_user_id, status, placed_at, id)` serves
"my orders"; `ix_ord_orders_status_expiry (status, expires_at)` serves the expiry job;
`ix_ord_orders_paid_at (paid_at)` serves daily financial reports.
Check: `CHECK (grand_total_amount = items_total_amount - discount_total_amount + shipping_total_amount + tax_total_amount)`;
`CHECK (paid_amount >= 0 AND refunded_amount >= 0 AND paid_amount + wallet_applied_amount <= grand_total_amount)`.

### `ord_sub_orders`

| Column | Type | Null | Default | Index | Notes | PII |
|---|---|---|---|---|---|---|
| `id` | `BIGINT UNSIGNED` | NO | auto | PK | — | — |
| `public_id` | `CHAR(26)` | NO | — | UQ | — | — |
| `sub_order_number` | `VARCHAR(32)` | NO | — | UQ | `DK-1405-000123-01` | — |
| `order_id` | `BIGINT UNSIGNED` | NO | — | FK, IX | — | — |
| `seller_type` | `VARCHAR(12)` | NO | — | IX | `supplier`,`store` | — |
| `seller_id` | `BIGINT UNSIGNED` | NO | — | IX | Logical FK to the chosen seller table | — |
| `status` | `VARCHAR(24)` | NO | `'awaiting_confirmation'` | IX | See state machine | — |
| `subtotal_amount` | `BIGINT` | NO | `0` | — | Sum of its items | — |
| `discount_amount` | `BIGINT` | NO | `0` | — | Allocated discounts | — |
| `shipping_amount` | `BIGINT` | NO | `0` | — | Shipping share for this seller | — |
| `total_amount` | `BIGINT` | NO | `0` | IX | `subtotal − discount + shipping` | — |
| `commission_amount` | `BIGINT` | NO | `0` | — | Platform/marketer commission attributed here | — |
| `payable_to_seller_amount` | `BIGINT` | NO | `0` | IX | What the seller is owed | — |
| `is_on_hold` | `TINYINT(1)` | NO | `0` | IX | Cache of active `ord_order_holds` | — |
| `hold_reason_code` | `VARCHAR(24)` | YES | NULL | — | Mirrors the active hold | — |
| `hold_until` | `DATETIME(3)` | YES | NULL | IX | — | — |
| `promised_ship_at` | `DATETIME(3)` | YES | NULL | IX | Drives delay damage | — |
| `confirmed_at` | `DATETIME(3)` | YES | NULL | — | — | — |
| `shipped_at` | `DATETIME(3)` | YES | NULL | IX | — | — |
| `delivered_at` | `DATETIME(3)` | YES | NULL | IX | Starts return window | — |
| `completed_at` | `DATETIME(3)` | YES | NULL | — | — | — |
| `cancelled_at` | `DATETIME(3)` | YES | NULL | IX | — | — |
| `cancel_actor` | `VARCHAR(12)` | YES | NULL | — | `buyer`,`supplier`,`store`,`admin`,`system` | — |
| `cancel_reason_code` | `VARCHAR(32)` | YES | NULL | — | — | — |
| `cancel_reason_text` | `VARCHAR(500)` | YES | NULL | — | — | — |
| `return_window_ends_at` | `DATETIME(3)` | YES | NULL | IX | Set on delivery | — |
| `invoice_id` | `BIGINT UNSIGNED` | YES | NULL | FK, UQ | The `sale` invoice | — |
| `shipment_id` | `BIGINT UNSIGNED` | YES | NULL | FK, UQ | The single v1 shipment | — |
| `settlement_id` | `BIGINT UNSIGNED` | YES | NULL | FK, IX | Write-once | — |
| `points_awarded_at` | `DATETIME(3)` | YES | NULL | — | Guards double accrual | — |
| `lock_version` | `INT UNSIGNED` | NO | `0` | — | Optimistic lock | — |
| `created_at` | `DATETIME(3)` | NO | `CURRENT_TIMESTAMP(3)` | — | — | — |
| `updated_at` | `DATETIME(3)` | YES | NULL | — | — | — |

Indexes: `ix_ord_sub_orders_seller_status (seller_type, seller_id, status, created_at)` is the
seller's work queue; `ix_ord_sub_orders_order (order_id)`.
Check: `CHECK (total_amount = subtotal_amount - discount_amount + shipping_amount)`;
`CHECK (payable_to_seller_amount >= 0)`.

### `ord_order_items`

| Column | Type | Null | Default | Index | Notes | PII |
|---|---|---|---|---|---|---|
| `id` | `BIGINT UNSIGNED` | NO | auto | PK | — | — |
| `order_id` | `BIGINT UNSIGNED` | NO | — | FK, IX | — | — |
| `sub_order_id` | `BIGINT UNSIGNED` | NO | — | FK, IX | — | — |
| `product_id` | `BIGINT UNSIGNED` | NO | — | FK, IX | — | — |
| `variant_id` | `BIGINT UNSIGNED` | NO | — | FK | — | — |
| `seller_type` | `VARCHAR(12)` | NO | — | — | Copied from the sub-order | — |
| `seller_id` | `BIGINT UNSIGNED` | NO | — | IX | Copied for reporting | — |
| `product_title_snapshot` | `VARCHAR(200)` | NO | — | — | Name at purchase | — |
| `variant_title_snapshot` | `VARCHAR(200)` | NO | — | — | — | — |
| `sku_snapshot` | `VARCHAR(64)` | NO | — | — | — | — |
| `attributes_snapshot_json` | `JSON` | YES | NULL | — | `[{attribute,value}]` at purchase | — |
| `unit_label_snapshot` | `VARCHAR(24)` | NO | — | — | `m2`, `pcs`, … | — |
| `quantity` | `DECIMAL(12,3)` | NO | — | — | `CHECK (quantity > 0)` | — |
| `unit_price_amount` | `BIGINT` | NO | — | — | Final unit price paid | — |
| `unit_discount_amount` | `BIGINT` | NO | `0` | — | Per-unit discount | — |
| `line_discount_amount` | `BIGINT` | NO | `0` | — | Whole-line discount | — |
| `line_total_amount` | `BIGINT` | NO | — | IX | `(unit_price − unit_discount) × qty − line_discount` | — |
| `tax_amount` | `BIGINT` | NO | `0` | — | Zero in v1 | — |
| `points_base_amount` | `BIGINT` | NO | `0` | — | Amount used for point accrual | — |
| `points_awarded` | `INT` | NO | `0` | — | Written once at accrual | — |
| `returned_quantity` | `DECIMAL(12,3)` | NO | `0.000` | — | `CHECK (returned_quantity <= quantity)` | — |
| `status` | `VARCHAR(16)` | NO | `'active'` | IX | `active`,`cancelled`,`returned`,`partially_returned` | — |
| `promotion_id` | `BIGINT UNSIGNED` | YES | NULL | FK | Promotion that produced the discount | — |
| `price_snapshot_id` | `BIGINT UNSIGNED` | YES | NULL | FK, UQ | → `pri_price_snapshots` | — |
| `created_at` | `DATETIME(3)` | NO | `CURRENT_TIMESTAMP(3)` | — | — | — |
| `updated_at` | `DATETIME(3)` | YES | NULL | — | Only `status`/`returned_quantity` change | — |

Check: `CHECK (line_total_amount = (unit_price_amount - unit_discount_amount) * quantity - line_discount_amount)`
— note that the multiplication is on a `DECIMAL` quantity; the application computes it at
scale 0 (Toman) and the constraint validates the stored result.

### `ord_order_status_history`

| Column | Type | Null | Default | Index | Notes | PII |
|---|---|---|---|---|---|---|
| `id` | `BIGINT UNSIGNED` | NO | auto | PK | — | — |
| `order_id` | `BIGINT UNSIGNED` | NO | — | FK, IX | — | — |
| `sub_order_id` | `BIGINT UNSIGNED` | YES | NULL | FK, IX | `NULL` for parent-order transitions | — |
| `from_status` | `VARCHAR(24)` | YES | NULL | — | `NULL` on creation | — |
| `to_status` | `VARCHAR(24)` | NO | — | IX | — | — |
| `actor_type` | `VARCHAR(12)` | NO | — | — | `system`,`user`,`admin`,`supplier`,`store`,`gateway` | — |
| `actor_id` | `BIGINT UNSIGNED` | YES | NULL | FK | `NULL` for system/gateway | — |
| `reason_code` | `VARCHAR(32)` | YES | NULL | — | — | — |
| `reason_text` | `VARCHAR(500)` | YES | NULL | — | — | — |
| `metadata_json` | `JSON` | YES | NULL | — | Small, PII-free context (`payment_id`, `refund_id`) | — |
| `request_id` | `CHAR(26)` | YES | NULL | IX | Trace id from the HTTP layer | — |
| `occurred_at` | `DATETIME(3)` | NO | `CURRENT_TIMESTAMP(3)` | IX | — | — |

Index: `ix_ord_order_status_history_order_time (order_id, occurred_at, id)`.

### `ord_order_holds`

| Column | Type | Null | Default | Index | Notes | PII |
|---|---|---|---|---|---|---|
| `id` | `BIGINT UNSIGNED` | NO | auto | PK | — | — |
| `order_id` | `BIGINT UNSIGNED` | NO | — | FK, IX | — | — |
| `sub_order_id` | `BIGINT UNSIGNED` | NO | — | FK, IX | — | — |
| `reason_code` | `VARCHAR(24)` | NO | — | IX | `complaint`,`dispute`,`incomplete`,`fraud_review`,`admin`,`payment_review` | — |
| `source_id` | `BIGINT UNSIGNED` | YES | NULL | IX | Complaint/dispute id | — |
| `status` | `VARCHAR(12)` | NO | `'active'` | IX | `active`,`released`,`expired` | — |
| `held_at` | `DATETIME(3)` | NO | `CURRENT_TIMESTAMP(3)` | — | — | — |
| `hold_until` | `DATETIME(3)` | YES | NULL | IX | Default +7 days for complaints | — |
| `released_at` | `DATETIME(3)` | YES | NULL | — | — | — |
| `released_by_id` | `BIGINT UNSIGNED` | YES | NULL | FK | — | — |
| `note` | `VARCHAR(500)` | YES | NULL | — | — | — |

Invariant: at most one `active` hold per `(sub_order_id, reason_code)`, enforced by a generated
unique key on `(sub_order_id, reason_code, status)` where `status='active'`.

### `ord_incomplete_reports`

| Column | Type | Null | Default | Index | Notes | PII |
|---|---|---|---|---|---|---|
| `id` | `BIGINT UNSIGNED` | NO | auto | PK | — | — |
| `sub_order_id` | `BIGINT UNSIGNED` | NO | — | FK, IX | — | — |
| `reported_by_id` | `BIGINT UNSIGNED` | NO | — | FK | Supplier/user who flagged it | — |
| `missing_fields_json` | `JSON` | NO | — | — | `["address","postal_code","unit"]` | — |
| `note` | `VARCHAR(1000)` | YES | NULL | — | — | — |
| `status` | `VARCHAR(16)` | NO | `'open'` | IX | `open`,`buyer_responded`,`resolved`,`escalated`,`auto_closed` | — |
| `buyer_response_text` | `VARCHAR(1000)` | YES | NULL | — | — | — |
| `abuse_flag` | `TINYINT(1)` | NO | `0` | IX | Set when used repeatedly (appendix 11 clause 13) | — |
| `resolved_at` | `DATETIME(3)` | YES | NULL | — | — | — |
| `resolved_by_id` | `BIGINT UNSIGNED` | YES | NULL | FK | — | — |
| `created_at` | `DATETIME(3)` | NO | `CURRENT_TIMESTAMP(3)` | IX | — | — |
| `updated_at` | `DATETIME(3)` | YES | NULL | — | — | — |

### `ord_order_notes`

| Column | Type | Null | Default | Index | Notes | PII |
|---|---|---|---|---|---|---|
| `id` | `BIGINT UNSIGNED` | NO | auto | PK | — | — |
| `order_id` | `BIGINT UNSIGNED` | NO | — | FK, IX | — | — |
| `sub_order_id` | `BIGINT UNSIGNED` | YES | NULL | FK | — | — |
| `author_id` | `BIGINT UNSIGNED` | YES | NULL | FK | `NULL` for system notes | — |
| `visibility` | `VARCHAR(12)` | NO | `'internal'` | IX | `internal`,`buyer`,`seller` | — |
| `kind` | `VARCHAR(12)` | NO | `'text'` | — | `text`,`voice`,`image`,`document` | — |
| `body` | `TEXT` | YES | NULL | — | — | — |
| `media_id` | `BIGINT UNSIGNED` | YES | NULL | FK | Voice/image/document attachment | — |
| `created_at` | `DATETIME(3)` | NO | `CURRENT_TIMESTAMP(3)` | IX | — | — |

Check: `CHECK (body IS NOT NULL OR media_id IS NOT NULL)`.

---

## 9. Invoices

### `inv_invoices`

| Column | Type | Null | Default | Index | Notes | PII |
|---|---|---|---|---|---|---|
| `id` | `BIGINT UNSIGNED` | NO | auto | PK | — | — |
| `invoice_number` | `VARCHAR(24)` | NO | — | UQ | Gapless, per fiscal year; never reused | — |
| `fiscal_year` | `SMALLINT UNSIGNED` | NO | — | IX | Jalali year, e.g. `1405` | — |
| `type` | `VARCHAR(16)` | NO | `'sale'` | IX | `sale`,`supplementary`,`return`,`manual`,`proforma` | — |
| `status` | `VARCHAR(16)` | NO | `'draft'` | IX | `draft`,`issued`,`partially_paid`,`paid`,`void` | — |
| `order_id` | `BIGINT UNSIGNED` | YES | NULL | FK, IX | `NULL` for a pure manual invoice | — |
| `sub_order_id` | `BIGINT UNSIGNED` | YES | NULL | FK, GEN¹ | — | — |
| `parent_invoice_id` | `BIGINT UNSIGNED` | YES | NULL | FK, IX | Set for supplementary/return invoices | — |
| `supplier_id` | `BIGINT UNSIGNED` | YES | NULL | FK, IX | Seller on the invoice | — |
| `buyer_user_id` | `BIGINT UNSIGNED` | NO | — | FK, IX | — | — |
| `buyer_name_snapshot` | `VARCHAR(160)` | NO | — | — | — | Yes |
| `buyer_national_id_snapshot` | `CHAR(10)` | YES | NULL | — | Encrypted | Yes* |
| `buyer_address_snapshot_json` | `JSON` | YES | NULL | — | — | Yes |
| `subtotal_amount` | `BIGINT` | NO | `0` | — | Sum of lines | — |
| `discount_amount` | `BIGINT` | NO | `0` | — | — | — |
| `shipping_amount` | `BIGINT` | NO | `0` | — | — | — |
| `tax_amount` | `BIGINT` | NO | `0` | — | Zero in v1 | — |
| `total_amount` | `BIGINT` | NO | `0` | IX | — | — |
| `paid_amount` | `BIGINT` | NO | `0` | — | — | — |
| `balance_amount` | `BIGINT` | NO | `0` | — | `total − paid` | — |
| `currency` | `CHAR(3)` | NO | `'IRT'` | — | — | — |
| `issued_at` | `DATETIME(3)` | YES | NULL | IX | Set when leaving `draft` (immutability point) | — |
| `due_at` | `DATETIME(3)` | YES | NULL | — | — | — |
| `settled_at` | `DATETIME(3)` | YES | NULL | IX | Payment to the seller completed | — |
| `notes` | `VARCHAR(1000)` | YES | NULL | — | — | — |
| `pdf_media_id` | `BIGINT UNSIGNED` | YES | NULL | FK | Rendered PDF on the private disk | — |
| `issued_by_id` | `BIGINT UNSIGNED` | YES | NULL | FK | `NULL` when system-issued | — |
| `voided_at` | `DATETIME(3)` | YES | NULL | — | — | — |
| `void_reason` | `VARCHAR(500)` | YES | NULL | — | Required when voided | — |
| `lock_version` | `INT UNSIGNED` | NO | `0` | — | — | — |
| `created_at` | `DATETIME(3)` | NO | `CURRENT_TIMESTAMP(3)` | IX | — | — |
| `updated_at` | `DATETIME(3)` | YES | NULL | — | Only while `draft` | — |

¹ `sale_sub_order_key BIGINT UNSIGNED GENERATED ALWAYS AS (IF(type = 'sale', sub_order_id, NULL)) STORED` with `UNIQUE` — exactly one sale invoice per sub-order.
Check: `CHECK (total_amount = subtotal_amount - discount_amount + shipping_amount + tax_amount)`;
`CHECK (balance_amount = total_amount - paid_amount)`;
`CHECK (type <> 'supplementary' OR parent_invoice_id IS NOT NULL)`;
`CHECK (status = 'draft' OR issued_at IS NOT NULL)`.

### `inv_invoice_items`

| Column | Type | Null | Default | Index | Notes | PII |
|---|---|---|---|---|---|---|
| `id` | `BIGINT UNSIGNED` | NO | auto | PK | — | — |
| `invoice_id` | `BIGINT UNSIGNED` | NO | — | FK, IX | — | — |
| `order_item_id` | `BIGINT UNSIGNED` | YES | NULL | FK, IX | Required for `type='sale'`, null for manual | — |
| `description` | `VARCHAR(500)` | NO | — | — | Snapshot of the product title | — |
| `quantity` | `DECIMAL(12,3)` | NO | — | — | — | — |
| `unit_price_amount` | `BIGINT` | NO | — | — | — | — |
| `unit_label` | `VARCHAR(24)` | YES | NULL | — | — | — |
| `discount_amount` | `BIGINT` | NO | `0` | — | — | — |
| `line_total_amount` | `BIGINT` | NO | — | — | — | — |
| `is_supplementary` | `TINYINT(1)` | NO | `0` | — | Marks lines added by a supplementary invoice | — |
| `source` | `VARCHAR(16)` | NO | `'order_item'` | IX | `order_item`,`manual`,`adjustment` | — |
| `created_at` | `DATETIME(3)` | NO | `CURRENT_TIMESTAMP(3)` | — | — | — |

Check: `CHECK (line_total_amount = unit_price_amount * quantity - discount_amount)`.

### `inv_invoice_sequences`

| Column | Type | Null | Default | Index | Notes | PII |
|---|---|---|---|---|---|---|
| `id` | `BIGINT UNSIGNED` | NO | auto | PK | — | — |
| `fiscal_year` | `SMALLINT UNSIGNED` | NO | — | UQ¹ | Jalali year | — |
| `scope` | `VARCHAR(16)` | NO | — | UQ¹ | `invoice`,`supplementary`,`return`,`manual` | — |
| `prefix` | `VARCHAR(12)` | NO | — | — | e.g. `DK` | — |
| `next_number` | `INT UNSIGNED` | NO | `1` | — | Allocated under `FOR UPDATE` | — |
| `last_issued_at` | `DATETIME(3)` | YES | NULL | — | — | — |

¹ `uq_inv_invoice_sequences_year_scope (fiscal_year, scope)`.
Invariant: numbers are never decremented or reused, even after a void.

---

## 10. Payments

### `pay_gateways`

Twelve bank gateways, one driver each (appendix 1 clause 5, appendix 13 clause 7).

| Column | Type | Null | Default | Index | Notes | PII |
|---|---|---|---|---|---|---|
| `id` | `BIGINT UNSIGNED` | NO | auto | PK | — | — |
| `code` | `VARCHAR(24)` | NO | — | UQ | `saman`,`behpardakht`,`zarinpal`,… permanent | — |
| `name_fa` | `VARCHAR(80)` | NO | — | — | — | — |
| `driver` | `VARCHAR(64)` | NO | — | — | `App\Modules\Payments\Infrastructure\Drivers\…` key | — |
| `mode` | `VARCHAR(8)` | NO | `'sandbox'` | IX | `sandbox`,`live` | — |
| `is_active` | `TINYINT(1)` | NO | `0` | IX | Only active gateways are offered | — |
| `priority` | `SMALLINT UNSIGNED` | NO | `100` | IX | Display order / failover order | — |
| `supports_refund` | `TINYINT(1)` | NO | `0` | — | Drives the admin refund button | — |
| `supports_partial_refund` | `TINYINT(1)` | NO | `0` | — | — | — |
| `credentials_json` | `JSON` | YES | NULL | — | Encrypted; redacted as `••••` in every response | Yes* |
| `terminal_id` | `VARCHAR(64)` | YES | NULL | — | — | — |
| `merchant_id_encrypted` | `VARBINARY(512)` | YES | NULL | — | — | Yes* |
| `fee_percent` | `DECIMAL(6,3)` | NO | `0.000` | — | Gateway cost, used in reconciliation | — |
| `fee_fixed_amount` | `BIGINT` | NO | `0` | — | — | — |
| `activated_by_id` | `BIGINT UNSIGNED` | YES | NULL | FK | — | — |
| `activated_at` | `DATETIME(3)` | YES | NULL | — | — | — |
| `created_at` | `DATETIME(3)` | NO | `CURRENT_TIMESTAMP(3)` | — | — | — |
| `updated_at` | `DATETIME(3)` | YES | NULL | — | — | — |

Invariant: activating a gateway writes `adm_audit_logs` without the credentials;
`credentials_json` is never logged, never serialised to an API resource.

### `pay_payments`

| Column | Type | Null | Default | Index | Notes | PII |
|---|---|---|---|---|---|---|
| `id` | `BIGINT UNSIGNED` | NO | auto | PK | — | — |
| `public_id` | `CHAR(26)` | NO | — | UQ | — | — |
| `reference` | `VARCHAR(64)` | NO | — | UQ | Idempotency backbone | — |
| `user_id` | `BIGINT UNSIGNED` | NO | — | FK, IX | Payer | — |
| `order_id` | `BIGINT UNSIGNED` | YES | NULL | FK, IX | `NULL` for wallet top-up / notification purchase | — |
| `invoice_id` | `BIGINT UNSIGNED` | YES | NULL | FK, IX | Set when paying a specific invoice | — |
| `purpose` | `VARCHAR(24)` | NO | `'order'` | IX | `order`,`wallet_topup`,`notification_purchase`,`tender_deposit`,`sms_credit`,`manual` | — |
| `method` | `VARCHAR(16)` | NO | `'online'` | IX | `online`,`wallet`,`cod`,`bank_transfer`,`manual_admin` | — |
| `gateway_id` | `BIGINT UNSIGNED` | YES | NULL | FK | `NULL` for wallet/COD | — |
| `amount` | `BIGINT` | NO | — | IX | `CHECK (amount > 0)` | — |
| `currency` | `CHAR(3)` | NO | `'IRT'` | — | — | — |
| `status` | `VARCHAR(20)` | NO | `'initiated'` | IX | `initiated`,`pending`,`authorized`,`captured`,`failed`,`cancelled`,`expired`,`partially_refunded`,`refunded` | — |
| `captured_amount` | `BIGINT` | NO | `0` | — | — | — |
| `refunded_amount` | `BIGINT` | NO | `0` | — | — | — |
| `authority` | `VARCHAR(128)` | YES | NULL | IX | Gateway-side token for the redirect | — |
| `gateway_reference` | `VARCHAR(128)` | YES | NULL | IX | Gateway transaction id | — |
| `paid_at` | `DATETIME(3)` | YES | NULL | IX | — | — |
| `expires_at` | `DATETIME(3)` | YES | NULL | IX | Intent expiry | — |
| `failure_code` | `VARCHAR(48)` | YES | NULL | — | Internal code from the error catalogue | — |
| `description` | `VARCHAR(255)` | YES | NULL | — | — | — |
| `created_by_id` | `BIGINT UNSIGNED` | YES | NULL | FK | Admin for manual payments | — |
| `lock_version` | `INT UNSIGNED` | NO | `0` | — | — | — |
| `created_at` | `DATETIME(3)` | NO | `CURRENT_TIMESTAMP(3)` | IX | — | — |
| `updated_at` | `DATETIME(3)` | YES | NULL | — | — | — |

Indexes: `ix_pay_payments_order_status (order_id, status)`, `ix_pay_payments_user_paid (user_id, paid_at)`.
Generated: `active_order_key BIGINT UNSIGNED GENERATED ALWAYS AS (IF(status IN ('initiated','pending','authorized'), order_id, NULL)) STORED` with `UNIQUE` — one live payment per order.
Check: `CHECK (captured_amount <= amount)`, `CHECK (refunded_amount <= captured_amount)`.

### `pay_payment_attempts`

| Column | Type | Null | Default | Index | Notes | PII |
|---|---|---|---|---|---|---|
| `id` | `BIGINT UNSIGNED` | NO | auto | PK | — | — |
| `payment_id` | `BIGINT UNSIGNED` | NO | — | FK, UQ¹ | — | — |
| `attempt_no` | `SMALLINT UNSIGNED` | NO | — | UQ¹ | Starts at 1 | — |
| `gateway_id` | `BIGINT UNSIGNED` | NO | — | FK | — | — |
| `status` | `VARCHAR(20)` | NO | `'started'` | IX | `started`,`redirected`,`callback_received`,`verified`,`failed`,`timeout` | — |
| `request_payload_json` | `JSON` | YES | NULL | — | Redacted before write | — |
| `response_payload_json` | `JSON` | YES | NULL | — | Redacted before write | — |
| `http_status` | `SMALLINT UNSIGNED` | YES | NULL | — | — | — |
| `error_code` | `VARCHAR(48)` | YES | NULL | IX | Internal catalogue code | — |
| `error_message` | `VARCHAR(255)` | YES | NULL | — | Sanitised; never the raw provider text | — |
| `started_at` | `DATETIME(3)` | NO | `CURRENT_TIMESTAMP(3)` | IX | — | — |
| `finished_at` | `DATETIME(3)` | YES | NULL | — | — | — |
| `ip_address` | `VARBINARY(16)` | YES | NULL | — | — | Yes |
| `user_agent` | `VARCHAR(255)` | YES | NULL | — | — | Yes |

¹ `uq_pay_payment_attempts_payment_no (payment_id, attempt_no)`.

### `pay_refunds`

| Column | Type | Null | Default | Index | Notes | PII |
|---|---|---|---|---|---|---|
| `id` | `BIGINT UNSIGNED` | NO | auto | PK | — | — |
| `public_id` | `CHAR(26)` | NO | — | UQ | — | — |
| `payment_id` | `BIGINT UNSIGNED` | NO | — | FK, IX | — | — |
| `order_id` | `BIGINT UNSIGNED` | YES | NULL | FK, IX | — | — |
| `sub_order_id` | `BIGINT UNSIGNED` | YES | NULL | FK, IX | — | — |
| `amount` | `BIGINT` | NO | — | — | `CHECK (amount > 0)` | — |
| `reason_code` | `VARCHAR(32)` | NO | — | — | `supplier_rejection`,`admin_cancel`,`return`,`delay_damage`,`duplicate`,`goodwill` | — |
| `destination` | `VARCHAR(12)` | NO | — | IX | `wallet`,`iban`,`gateway` | — |
| `bank_account_id` | `BIGINT UNSIGNED` | YES | NULL | FK | Required for `iban` | — |
| `status` | `VARCHAR(16)` | NO | `'requested'` | IX | `requested`,`approved`,`processing`,`completed`,`failed`,`cancelled` | — |
| `gateway_refund_reference` | `VARCHAR(128)` | YES | NULL | IX | — | — |
| `idempotency_key` | `VARCHAR(96)` | NO | — | UQ | `refund:{payment_id}:{n}` | — |
| `requested_by_id` | `BIGINT UNSIGNED` | NO | — | FK | — | — |
| `approved_by_id` | `BIGINT UNSIGNED` | YES | NULL | FK | Four-eyes for IBAN refunds | — |
| `processed_at` | `DATETIME(3)` | YES | NULL | IX | — | — |
| `failure_reason` | `VARCHAR(255)` | YES | NULL | — | — | — |
| `created_at` | `DATETIME(3)` | NO | `CURRENT_TIMESTAMP(3)` | — | — | — |
| `updated_at` | `DATETIME(3)` | YES | NULL | — | — | — |

Invariant: `SUM(pay_refunds.amount WHERE status IN ('approved','processing','completed')) <= pay_payments.captured_amount`,
asserted inside the refund transaction (`SELECT … FOR UPDATE` on the payment).

### `pay_webhook_events`

| Column | Type | Null | Default | Index | Notes | PII |
|---|---|---|---|---|---|---|
| `id` | `BIGINT UNSIGNED` | NO | auto | PK | — | — |
| `gateway_id` | `BIGINT UNSIGNED` | NO | — | FK, UQ¹ | — | — |
| `event_id` | `VARCHAR(128)` | NO | — | UQ¹ | Provider event id | — |
| `event_type` | `VARCHAR(64)` | YES | NULL | — | — | — |
| `signature_valid` | `TINYINT(1)` | NO | `0` | IX | Invalid signatures are stored, never applied | — |
| `payload_json` | `JSON` | YES | NULL | — | Redacted | Yes |
| `payment_id` | `BIGINT UNSIGNED` | YES | NULL | FK | Resolved payment, when identifiable | — |
| `received_at` | `DATETIME(3)` | NO | `CURRENT_TIMESTAMP(3)` | IX | — | — |
| `processed_at` | `DATETIME(3)` | YES | NULL | — | — | — |
| `processing_result` | `VARCHAR(12)` | YES | NULL | IX | `applied`,`duplicate`,`ignored`,`failed` | — |

¹ `uq_pay_webhook_events_gateway_event (gateway_id, event_id)`.

---

## 11. Finance

### `fin_accounts`

| Column | Type | Null | Default | Index | Notes | PII |
|---|---|---|---|---|---|---|
| `id` | `BIGINT UNSIGNED` | NO | auto | PK | — | — |
| `code` | `VARCHAR(40)` | NO | — | UQ | `gateway_clearing`,`supplier_payable`,`platform_revenue`,`wallet_liability` | — |
| `name_fa` | `VARCHAR(120)` | NO | — | — | — | — |
| `type` | `VARCHAR(12)` | NO | — | IX | `asset`,`liability`,`revenue`,`expense`,`equity`,`clearing` | — |
| `owner_type` | `VARCHAR(12)` | NO | `'platform'` | UQ¹ | `platform`,`user`,`supplier`,`store`,`marketer`,`gateway` | — |
| `owner_id` | `BIGINT UNSIGNED` | YES | NULL | UQ¹ | `NULL` for platform/system accounts | — |
| `currency` | `CHAR(3)` | NO | `'IRT'` | — | — | — |
| `is_system` | `TINYINT(1)` | NO | `0` | IX | Seeded accounts cannot be deleted | — |
| `is_active` | `TINYINT(1)` | NO | `1` | — | — | — |
| `created_at` | `DATETIME(3)` | NO | `CURRENT_TIMESTAMP(3)` | — | — | — |
| `updated_at` | `DATETIME(3)` | YES | NULL | — | — | — |

¹ `uq_fin_accounts_owner (owner_type, owner_id, currency)` — note MySQL treats `NULL`
owner ids as distinct, so platform accounts are protected by `is_system` and the unique `code`
instead; per-party accounts rely on this index.

### `fin_ledger_batches`

| Column | Type | Null | Default | Index | Notes | PII |
|---|---|---|---|---|---|---|
| `id` | `BIGINT UNSIGNED` | NO | auto | PK | — | — |
| `batch_no` | `VARCHAR(32)` | NO | — | UQ | `LB-1405-000012` | — |
| `source_type` | `VARCHAR(24)` | NO | — | IX | Same vocabulary as `fin_ledger_entries.source_type` | — |
| `source_id` | `BIGINT UNSIGNED` | YES | NULL | IX | — | — |
| `occurred_at` | `DATETIME(3)` | NO | `CURRENT_TIMESTAMP(3)` | IX | — | — |
| `entry_count` | `SMALLINT UNSIGNED` | NO | `0` | — | `CHECK (entry_count >= 2)` | — |
| `total_debit_amount` | `BIGINT` | NO | `0` | — | — | — |
| `total_credit_amount` | `BIGINT` | NO | `0` | — | — | — |
| `is_balanced` | `TINYINT(1)` | NO | `0` | IX | Set only when debit = credit | — |
| `created_by_id` | `BIGINT UNSIGNED` | YES | NULL | FK | `NULL` for system commands | — |

Check: `CHECK (total_debit_amount = total_credit_amount)` — an unbalanced batch cannot be
inserted at all.

### `fin_ledger_entries`

The single source of financial truth. Append-only, no `updated_at`, no `deleted_at`
(canonical §5).

| Column | Type | Null | Default | Index | Notes | PII |
|---|---|---|---|---|---|---|
| `id` | `BIGINT UNSIGNED` | NO | auto | PK | — | — |
| `entry_no` | `BIGINT UNSIGNED` | NO | — | UQ | Monotonic sequence for statements | — |
| `batch_id` | `BIGINT UNSIGNED` | NO | — | FK, IX | — | — |
| `account_id` | `BIGINT UNSIGNED` | NO | — | FK, IX | — | — |
| `direction` | `VARCHAR(6)` | NO | — | — | `debit`,`credit` | — |
| `amount` | `BIGINT` | NO | — | — | Signed integer Toman; positive for debit-normal accounts, reversal rows carry the opposite sign | — |
| `currency` | `CHAR(3)` | NO | `'IRT'` | — | — | — |
| `reference` | `VARCHAR(96)` | NO | — | UQ | `order:{id}:capture`, `settlement:{id}`, … — idempotency | — |
| `source_type` | `VARCHAR(24)` | NO | — | IX | `order`,`payment`,`refund`,`settlement`,`commission`,`wallet_topup`,`wallet_withdrawal`,`penalty`,`adjustment`,`reversal`,`delay_damage` | — |
| `source_id` | `BIGINT UNSIGNED` | YES | NULL | IX | — | — |
| `reverses_entry_id` | `BIGINT UNSIGNED` | YES | NULL | FK, UQ | One reversal per entry | — |
| `description` | `VARCHAR(255)` | NO | — | — | Persian, shown on statements | — |
| `occurred_at` | `DATETIME(3)` | NO | `CURRENT_TIMESTAMP(3)` | IX | — | — |
| `created_by_id` | `BIGINT UNSIGNED` | YES | NULL | FK | `NULL` for system postings | — |
| `metadata_json` | `JSON` | YES | NULL | — | PII-free context | — |

Indexes: `ix_fin_ledger_entries_account_time (account_id, occurred_at, id)` is the balance
query; `ix_fin_ledger_entries_source (source_type, source_id)`.
Check: `CHECK (amount <> 0)`; `CHECK ((direction = 'debit' AND amount > 0) OR (direction = 'credit' AND amount > 0))`
— direction carries the sign, so `amount` is always a positive magnitude and reversal rows
swap the direction rather than storing negatives. This is the ONE deviation from "signed
amount" above: `amount` is a magnitude, `direction` is authoritative. Balances are computed
as `SUM(debit) − SUM(credit)` per account type.
Invariant: no update/delete; the application DB user has `INSERT, SELECT` only on this table.

### `fin_wallets`

| Column | Type | Null | Default | Index | Notes | PII |
|---|---|---|---|---|---|---|
| `id` | `BIGINT UNSIGNED` | NO | auto | PK | — | — |
| `user_id` | `BIGINT UNSIGNED` | NO | — | FK, UQ | One wallet per user | — |
| `currency` | `CHAR(3)` | NO | `'IRT'` | — | — | — |
| `balance_amount` | `BIGINT` | NO | `0` | — | Cached; equals ledger sum | — |
| `available_amount` | `BIGINT` | NO | `0` | — | Spendable now | — |
| `locked_amount` | `BIGINT` | NO | `0` | — | Held by an in-flight order | — |
| `status` | `VARCHAR(12)` | NO | `'active'` | IX | `active`,`frozen`,`closed` | — |
| `last_entry_at` | `DATETIME(3)` | YES | NULL | IX | — | — |
| `version` | `INT UNSIGNED` | NO | `0` | — | Optimistic lock | — |
| `created_at` | `DATETIME(3)` | NO | `CURRENT_TIMESTAMP(3)` | — | — | — |
| `updated_at` | `DATETIME(3)` | YES | NULL | — | — | — |

Check: `CHECK (balance_amount >= 0)`, `CHECK (available_amount + locked_amount = balance_amount)`.

### `fin_wallet_transactions`

| Column | Type | Null | Default | Index | Notes | PII |
|---|---|---|---|---|---|---|
| `id` | `BIGINT UNSIGNED` | NO | auto | PK | — | — |
| `wallet_id` | `BIGINT UNSIGNED` | NO | — | FK, IX | — | — |
| `direction` | `VARCHAR(6)` | NO | — | — | `credit`,`debit` | — |
| `amount` | `BIGINT` | NO | — | — | Positive magnitude | — |
| `balance_after` | `BIGINT` | NO | — | — | Chains per wallet | — |
| `reason` | `VARCHAR(16)` | NO | — | IX | `reward`,`refund`,`settlement`,`purchase`,`penalty`,`deposit`,`withdrawal`,`correction` | — |
| `ledger_entry_id` | `BIGINT UNSIGNED` | NO | — | FK, UQ | One projection row per ledger entry | — |
| `reference` | `VARCHAR(96)` | NO | — | UQ | — | — |
| `description` | `VARCHAR(255)` | NO | — | — | Persian statement line | — |
| `expires_at` | `DATETIME(3)` | YES | NULL | IX | Reward credits can expire (appendix 4 clause 7) | — |
| `created_by_id` | `BIGINT UNSIGNED` | YES | NULL | FK | — | — |
| `occurred_at` | `DATETIME(3)` | NO | `CURRENT_TIMESTAMP(3)` | IX | — | — |

### `fin_settlements`

| Column | Type | Null | Default | Index | Notes | PII |
|---|---|---|---|---|---|---|
| `id` | `BIGINT UNSIGNED` | NO | auto | PK | — | — |
| `settlement_no` | `VARCHAR(32)` | NO | — | UQ | — | — |
| `payee_type` | `VARCHAR(12)` | NO | — | IX | `supplier`,`store`,`marketer` | — |
| `payee_id` | `BIGINT UNSIGNED` | NO | — | IX | Logical FK | — |
| `period_start` | `DATE` | NO | — | IX | — | — |
| `period_end` | `DATE` | NO | — | IX | — | — |
| `gross_amount` | `BIGINT` | NO | `0` | — | Sum of settled documents | — |
| `commission_amount` | `BIGINT` | NO | `0` | — | Platform/marketer commission deducted | — |
| `penalty_amount` | `BIGINT` | NO | `0` | — | Delay damages, violations | — |
| `adjustment_amount` | `BIGINT` | NO | `0` | — | Signed manual adjustment | — |
| `net_amount` | `BIGINT` | NO | `0` | IX | Amount to transfer | — |
| `status` | `VARCHAR(16)` | NO | `'draft'` | IX | `draft`,`requested`,`approved`,`rejected`,`processing`,`paid`,`cancelled` | — |
| `requested_by_id` | `BIGINT UNSIGNED` | YES | NULL | FK | — | — |
| `approved_by_id` | `BIGINT UNSIGNED` | YES | NULL | FK | Must differ from `requested_by_id` | — |
| `paid_at` | `DATETIME(3)` | YES | NULL | IX | — | — |
| `bank_account_id` | `BIGINT UNSIGNED` | YES | NULL | FK | Required before `processing` | — |
| `bank_reference` | `VARCHAR(64)` | YES | NULL | IX | — | — |
| `export_id` | `BIGINT UNSIGNED` | YES | NULL | FK | Excel batch sent to the bank | — |
| `notes` | `VARCHAR(1000)` | YES | NULL | — | — | — |
| `idempotency_key` | `VARCHAR(96)` | NO | — | UQ | `settlement:{payee_type}:{payee_id}:{period_start}` | — |
| `lock_version` | `INT UNSIGNED` | NO | `0` | — | — | — |
| `created_at` | `DATETIME(3)` | NO | `CURRENT_TIMESTAMP(3)` | — | — | — |
| `updated_at` | `DATETIME(3)` | YES | NULL | — | — | — |

Check: `CHECK (net_amount = gross_amount - commission_amount - penalty_amount + adjustment_amount)`;
`CHECK (period_end >= period_start)`; `CHECK (approved_by_id IS NULL OR approved_by_id <> requested_by_id)`.

### `fin_settlement_items`

| Column | Type | Null | Default | Index | Notes | PII |
|---|---|---|---|---|---|---|
| `id` | `BIGINT UNSIGNED` | NO | auto | PK | — | — |
| `settlement_id` | `BIGINT UNSIGNED` | NO | — | FK, IX | — | — |
| `source_type` | `VARCHAR(20)` | NO | — | UQ¹ | `invoice`,`sub_order`,`commission`,`penalty`,`delay_damage`,`adjustment` | — |
| `source_id` | `BIGINT UNSIGNED` | NO | — | UQ¹ | — | — |
| `amount` | `BIGINT` | NO | — | — | Positive for earnings, negative for deductions | — |
| `description` | `VARCHAR(255)` | YES | NULL | — | — | — |

¹ `uq_fin_settlement_items_source (source_type, source_id)` — a document is paid once,
ever. Cancelled settlements keep the row, so re-settling requires an explicit admin action
that reverses the index by archiving the row.

### `fin_bank_accounts`

| Column | Type | Null | Default | Index | Notes | PII |
|---|---|---|---|---|---|---|
| `id` | `BIGINT UNSIGNED` | NO | auto | PK | — | — |
| `owner_type` | `VARCHAR(12)` | NO | — | UQ¹ | `user`,`supplier`,`store`,`marketer` | — |
| `owner_id` | `BIGINT UNSIGNED` | NO | — | UQ¹ | — | — |
| `iban_encrypted` | `VARBINARY(512)` | NO | — | — | Iran IBAN (`IR` + 24 digits), check-digit validated | Yes* |
| `iban_last4` | `CHAR(4)` | NO | — | IX | Only the displayable fragment | Yes |
| `bank_name` | `VARCHAR(80)` | YES | NULL | — | — | — |
| `account_holder_name` | `VARCHAR(160)` | NO | — | — | Must match the national id holder | Yes |
| `status` | `VARCHAR(20)` | NO | `'pending_verification'` | IX | `pending_verification`,`verified`,`rejected`,`disabled` | — |
| `verified_at` | `DATETIME(3)` | YES | NULL | — | — | — |
| `verified_by_id` | `BIGINT UNSIGNED` | YES | NULL | FK | — | — |
| `is_default` | `TINYINT(1)` | NO | `0` | GEN | One default per owner | — |
| `created_at` | `DATETIME(3)` | NO | `CURRENT_TIMESTAMP(3)` | — | — | — |
| `updated_at` | `DATETIME(3)` | YES | NULL | — | — | — |
| `deleted_at` | `DATETIME(3)` | YES | NULL | — | Retained so paid settlements remain explainable | — |

¹ `uq_fin_bank_accounts_owner_iban (owner_type, owner_id, iban_last4)` is the practical
uniqueness (the encrypted value is not comparable); the application prevents duplicate full
IBANs by decrypting on write.

---

## 12. Loyalty

### `loy_rule_sets`

| Column | Type | Null | Default | Index | Notes | PII |
|---|---|---|---|---|---|---|
| `id` | `BIGINT UNSIGNED` | NO | auto | PK | — | — |
| `code` | `VARCHAR(32)` | NO | — | UQ¹ | `global`,`supplier_<id>`,`category_<id>` | — |
| `version` | `SMALLINT UNSIGNED` | NO | `1` | UQ¹ | Increments per scope | — |
| `name` | `VARCHAR(120)` | NO | — | — | — | — |
| `status` | `VARCHAR(12)` | NO | `'draft'` | IX | `draft`,`active`,`retired` | — |
| `effective_from` | `DATETIME(3)` | NO | — | IX | — | — |
| `effective_to` | `DATETIME(3)` | YES | NULL | — | — | — |
| `scope` | `VARCHAR(12)` | NO | `'global'` | IX | `global`,`supplier`,`category`,`product` | — |
| `scope_id` | `BIGINT UNSIGNED` | YES | NULL | IX | `NULL` for global | — |
| `checksum` | `CHAR(64)` | NO | — | — | SHA-256 over the rule payload | — |
| `created_by_id` | `BIGINT UNSIGNED` | YES | NULL | FK | — | — |
| `approved_by_id` | `BIGINT UNSIGNED` | YES | NULL | FK | — | — |
| `activated_at` | `DATETIME(3)` | YES | NULL | — | — | — |
| `notes` | `VARCHAR(1000)` | YES | NULL | — | Why the version exists | — |
| `created_at` | `DATETIME(3)` | NO | `CURRENT_TIMESTAMP(3)` | — | — | — |
| `updated_at` | `DATETIME(3)` | YES | NULL | — | Only while `draft` | — |

¹ `uq_loy_rule_sets_code_version (code, version)`; one `active` set per scope enforced by a
generated unique key on `(scope, scope_id)` where `status='active'`.

### `loy_rules`

| Column | Type | Null | Default | Index | Notes | PII |
|---|---|---|---|---|---|---|
| `id` | `BIGINT UNSIGNED` | NO | auto | PK | — | — |
| `rule_set_id` | `BIGINT UNSIGNED` | NO | — | FK, IX | — | — |
| `code` | `VARCHAR(48)` | NO | — | UQ¹ | `earn_base`,`volume_075`,`tier_gold_5pct` | — |
| `type` | `VARCHAR(28)` | NO | — | IX | `earn_per_amount`,`earn_per_product`,`volume_multiplier`,`tier_discount`,`inactivity_penalty`,`invite_bonus`,`star_threshold`,`cash_bonus_threshold`,`invoice_count_bonus`,`violation_penalty` | — |
| `scope_type` | `VARCHAR(12)` | NO | `'all'` | IX | `all`,`product`,`category`,`brand`,`supplier` | — |
| `scope_id` | `BIGINT UNSIGNED` | YES | NULL | IX | — | — |
| `params_json` | `JSON` | YES | NULL | — | Type-specific; validated by JSON Schema | — |
| `points_value` | `INT` | YES | NULL | — | Points awarded or deducted | — |
| `percent_value` | `DECIMAL(6,3)` | YES | NULL | — | Point rate or discount percent | — |
| `threshold_amount` | `BIGINT` | YES | NULL | — | Minimum invoice amount | — |
| `threshold_points` | `INT` | YES | NULL | — | Tier/bonus threshold | — |
| `window_days` | `SMALLINT UNSIGNED` | YES | NULL | — | Period for count-based rules | — |
| `priority` | `SMALLINT UNSIGNED` | NO | `100` | — | Higher wins when rules overlap | — |
| `is_active` | `TINYINT(1)` | NO | `1` | IX | — | — |
| `created_at` | `DATETIME(3)` | NO | `CURRENT_TIMESTAMP(3)` | — | — | — |
| `updated_at` | `DATETIME(3)` | YES | NULL | — | Only while the set is `draft` | — |

¹ `uq_loy_rules_set_code (rule_set_id, code)`.

### `loy_point_balances`

| Column | Type | Null | Default | Index | Notes | PII |
|---|---|---|---|---|---|---|
| `id` | `BIGINT UNSIGNED` | NO | auto | PK | — | — |
| `user_id` | `BIGINT UNSIGNED` | NO | — | FK, UQ | — | — |
| `points_balance` | `INT UNSIGNED` | NO | `0` | IX | Never negative | — |
| `lifetime_earned` | `BIGINT UNSIGNED` | NO | `0` | — | Monotonic | — |
| `lifetime_spent` | `BIGINT UNSIGNED` | NO | `0` | — | Monotonic | — |
| `stars_count` | `SMALLINT UNSIGNED` | NO | `0` | IX | `floor(lifetime_earned / 100)` | — |
| `tier_id` | `BIGINT UNSIGNED` | YES | NULL | FK | — | — |
| `tier_updated_at` | `DATETIME(3)` | YES | NULL | — | — | — |
| `last_activity_at` | `DATETIME(3)` | YES | NULL | IX | Inactivity penalty input | — |
| `version` | `INT UNSIGNED` | NO | `0` | — | Optimistic lock | — |
| `created_at` | `DATETIME(3)` | NO | `CURRENT_TIMESTAMP(3)` | — | — | — |
| `updated_at` | `DATETIME(3)` | YES | NULL | — | — | — |

Check: `CHECK (points_balance = lifetime_earned - lifetime_spent)` (signed arithmetic on
`BIGINT UNSIGNED` is guarded by the application; the constraint is written with `CAST` to
`SIGNED` to avoid MySQL's unsigned underflow error).

### `loy_point_transactions`

| Column | Type | Null | Default | Index | Notes | PII |
|---|---|---|---|---|---|---|
| `id` | `BIGINT UNSIGNED` | NO | auto | PK | — | — |
| `user_id` | `BIGINT UNSIGNED` | NO | — | FK, IX | — | — |
| `rule_id` | `BIGINT UNSIGNED` | YES | NULL | FK | `NULL` for manual adjustments | — |
| `rule_set_id` | `BIGINT UNSIGNED` | YES | NULL | FK | Which version awarded | — |
| `direction` | `VARCHAR(8)` | NO | — | IX | `earn`,`redeem`,`expire`,`adjust`,`penalty` | — |
| `points` | `INT UNSIGNED` | NO | — | — | Always positive; direction carries the sign | — |
| `balance_after` | `INT UNSIGNED` | NO | — | — | Chains per user | — |
| `source_type` | `VARCHAR(16)` | NO | — | IX | `order`,`invoice`,`return`,`invite`,`admin`,`penalty`,`expiry`,`survey`,`review` | — |
| `source_id` | `BIGINT UNSIGNED` | YES | NULL | IX | — | — |
| `reference` | `VARCHAR(96)` | NO | — | UQ | `invoice:{id}:points`, `penalty:{user}:{period}` | — |
| `description` | `VARCHAR(255)` | NO | — | — | Persian statement line | — |
| `occurred_at` | `DATETIME(3)` | NO | `CURRENT_TIMESTAMP(3)` | IX | — | — |
| `expires_at` | `DATETIME(3)` | YES | NULL | IX | Earned points can expire | — |
| `created_by_id` | `BIGINT UNSIGNED` | YES | NULL | FK | — | — |

Index: `ix_loy_point_transactions_user_time (user_id, occurred_at, id)`.

### `loy_tiers`

| Column | Type | Null | Default | Index | Notes | PII |
|---|---|---|---|---|---|---|
| `id` | `BIGINT UNSIGNED` | NO | auto | PK | — | — |
| `code` | `VARCHAR(16)` | NO | — | UQ | `tier_1`,`tier_2`,`tier_3`,`tier_4` | — |
| `name_fa` | `VARCHAR(80)` | NO | — | — | — | — |
| `min_points` | `INT UNSIGNED` | NO | — | UQ | Inclusive | — |
| `max_points` | `INT UNSIGNED` | YES | NULL | — | Exclusive; `NULL` = top tier | — |
| `discount_percent` | `DECIMAL(6,3)` | NO | `0.000` | — | 4.000 for 1–100, 5.000 for 100–200 (appendix 4 clause 20) | — |
| `fixed_discount_amount` | `BIGINT` | NO | `0` | — | For tiers above 200 points (appendix 4 clause 21) | — |
| `star_multiplier` | `DECIMAL(4,2)` | NO | `1.00` | — | — | — |
| `sort_order` | `TINYINT UNSIGNED` | NO | `0` | IX | — | — |
| `is_active` | `TINYINT(1)` | NO | `1` | — | — | — |
| `created_at` | `DATETIME(3)` | NO | `CURRENT_TIMESTAMP(3)` | — | — | — |
| `updated_at` | `DATETIME(3)` | YES | NULL | — | — | — |

Check: `CHECK (max_points IS NULL OR max_points > min_points)`.

### `loy_rewards`

| Column | Type | Null | Default | Index | Notes | PII |
|---|---|---|---|---|---|---|
| `id` | `BIGINT UNSIGNED` | NO | auto | PK | — | — |
| `code` | `VARCHAR(32)` | NO | — | UQ | — | — |
| `name_fa` | `VARCHAR(160)` | NO | — | — | — | — |
| `type` | `VARCHAR(20)` | NO | — | IX | `cash_wallet`,`discount_coupon`,`product`,`free_shipping` | — |
| `points_cost` | `INT UNSIGNED` | NO | — | IX | `CHECK (points_cost > 0)` | — |
| `cash_amount` | `BIGINT` | YES | NULL | — | Required for `cash_wallet` | — |
| `coupon_template_id` | `BIGINT UNSIGNED` | YES | NULL | FK | → `prm_promotions.id` for coupon rewards | — |
| `product_id` | `BIGINT UNSIGNED` | YES | NULL | FK | For physical rewards | — |
| `stock_quantity` | `INT UNSIGNED` | YES | NULL | — | `NULL` = unlimited | — |
| `per_user_limit` | `SMALLINT UNSIGNED` | YES | NULL | — | — | — |
| `valid_from` | `DATETIME(3)` | NO | `CURRENT_TIMESTAMP(3)` | IX | — | — |
| `valid_to` | `DATETIME(3)` | YES | NULL | IX | — | — |
| `status` | `VARCHAR(12)` | NO | `'active'` | IX | `active`,`inactive` | — |
| `image_media_id` | `BIGINT UNSIGNED` | YES | NULL | FK | — | — |
| `terms` | `TEXT` | YES | NULL | — | Persian terms shown before redemption | — |
| `created_at` | `DATETIME(3)` | NO | `CURRENT_TIMESTAMP(3)` | — | — | — |
| `updated_at` | `DATETIME(3)` | YES | NULL | — | — | — |

Check: `CHECK (type <> 'cash_wallet' OR cash_amount IS NOT NULL)`.

### `loy_reward_redemptions`

| Column | Type | Null | Default | Index | Notes | PII |
|---|---|---|---|---|---|---|
| `id` | `BIGINT UNSIGNED` | NO | auto | PK | — | — |
| `reward_id` | `BIGINT UNSIGNED` | NO | — | FK, IX | — | — |
| `user_id` | `BIGINT UNSIGNED` | NO | — | FK, IX | — | — |
| `points_spent` | `INT UNSIGNED` | NO | — | — | Snapshot of the cost | — |
| `status` | `VARCHAR(12)` | NO | `'pending'` | IX | `pending`,`fulfilled`,`cancelled`,`expired` | — |
| `wallet_transaction_id` | `BIGINT UNSIGNED` | YES | NULL | FK, UQ | Set for cash rewards | — |
| `coupon_id` | `BIGINT UNSIGNED` | YES | NULL | FK | Set for coupon rewards | — |
| `order_id` | `BIGINT UNSIGNED` | YES | NULL | FK | Set when the reward is applied to an order | — |
| `reference` | `VARCHAR(96)` | NO | — | UQ | Idempotent redemption | — |
| `fulfilled_at` | `DATETIME(3)` | YES | NULL | — | — | — |
| `created_at` | `DATETIME(3)` | NO | `CURRENT_TIMESTAMP(3)` | IX | — | — |
| `updated_at` | `DATETIME(3)` | YES | NULL | — | — | — |

---

## 13. Promotions

### `prm_promotions`

| Column | Type | Null | Default | Index | Notes | PII |
|---|---|---|---|---|---|---|
| `id` | `BIGINT UNSIGNED` | NO | auto | PK | — | — |
| `code` | `VARCHAR(32)` | NO | — | UQ | — | — |
| `name_fa` | `VARCHAR(160)` | NO | — | — | — | — |
| `type` | `VARCHAR(20)` | NO | — | IX | `percent`,`fixed_amount`,`free_shipping`,`bundle`,`first_invoice`,`volume_cash` | — |
| `scope` | `VARCHAR(12)` | NO | `'order'` | IX | `order`,`product`,`category`,`brand`,`supplier` | — |
| `discount_percent` | `DECIMAL(6,3)` | YES | NULL | — | Exactly one of percent/amount | — |
| `discount_amount` | `BIGINT` | YES | NULL | — | — | — |
| `max_discount_amount` | `BIGINT` | YES | NULL | — | Cap for percentage promotions | — |
| `min_order_amount` | `BIGINT` | NO | `0` | — | — | — |
| `applies_to_role` | `VARCHAR(16)` | YES | NULL | IX | `consumer`,`store`,`supplier`; `NULL` = all | — |
| `price_list_id` | `BIGINT UNSIGNED` | YES | NULL | FK | Restrict to one price book | — |
| `is_stackable` | `TINYINT(1)` | NO | `0` | — | — | — |
| `priority` | `SMALLINT UNSIGNED` | NO | `100` | IX | Winner selection for non-stackable | — |
| `effective_from` | `DATETIME(3)` | NO | — | IX | — | — |
| `effective_to` | `DATETIME(3)` | NO | — | IX | — | — |
| `usage_limit_total` | `INT UNSIGNED` | YES | NULL | — | — | — |
| `usage_limit_per_user` | `SMALLINT UNSIGNED` | YES | NULL | — | — | — |
| `status` | `VARCHAR(12)` | NO | `'draft'` | IX | `draft`,`active`,`paused`,`expired` | — |
| `created_by_id` | `BIGINT UNSIGNED` | YES | NULL | FK | — | — |
| `approved_by_id` | `BIGINT UNSIGNED` | YES | NULL | FK | — | — |
| `created_at` | `DATETIME(3)` | NO | `CURRENT_TIMESTAMP(3)` | — | — | — |
| `updated_at` | `DATETIME(3)` | YES | NULL | — | — | — |

Check: `CHECK ((discount_percent IS NOT NULL) + (discount_amount IS NOT NULL) = 1)`;
`CHECK (effective_to > effective_from)`.

### `prm_coupons`

| Column | Type | Null | Default | Index | Notes | PII |
|---|---|---|---|---|---|---|
| `id` | `BIGINT UNSIGNED` | NO | auto | PK | — | — |
| `code` | `VARCHAR(40)` | NO | — | UQ | Uppercase, case-insensitive lookup via `ascii_bin` on normalised input | — |
| `promotion_id` | `BIGINT UNSIGNED` | YES | NULL | FK, IX | `NULL` for a standalone bon | — |
| `type` | `VARCHAR(16)` | NO | `'single_use'` | IX | `single_use`,`multi_use`,`bon`,`compensation` | — |
| `amount` | `BIGINT` | YES | NULL | — | Fixed Toman value | — |
| `discount_percent` | `DECIMAL(6,3)` | YES | NULL | — | Percentage value | — |
| `min_order_amount` | `BIGINT` | NO | `0` | — | — | — |
| `issued_to_user_id` | `BIGINT UNSIGNED` | YES | NULL | FK, IX | `NULL` = public code | — |
| `batch_id` | `BIGINT UNSIGNED` | YES | NULL | FK | → `prm_coupon_batches` | — |
| `status` | `VARCHAR(12)` | NO | `'active'` | IX | `active`,`used`,`expired`,`revoked` | — |
| `valid_from` | `DATETIME(3)` | NO | `CURRENT_TIMESTAMP(3)` | IX | — | — |
| `valid_to` | `DATETIME(3)` | YES | NULL | IX | — | — |
| `max_uses` | `SMALLINT UNSIGNED` | NO | `1` | — | — | — |
| `used_count` | `SMALLINT UNSIGNED` | NO | `0` | — | `CHECK (used_count <= max_uses)` | — |
| `used_at` | `DATETIME(3)` | YES | NULL | — | — | — |
| `used_order_id` | `BIGINT UNSIGNED` | YES | NULL | FK | — | — |
| `created_at` | `DATETIME(3)` | NO | `CURRENT_TIMESTAMP(3)` | — | — | — |
| `updated_at` | `DATETIME(3)` | YES | NULL | — | — | — |

Check: `CHECK ((amount IS NOT NULL) + (discount_percent IS NOT NULL) = 1)`;
`CHECK (type <> 'single_use' OR max_uses = 1)`.

### `prm_coupon_redemptions`

| Column | Type | Null | Default | Index | Notes | PII |
|---|---|---|---|---|---|---|
| `id` | `BIGINT UNSIGNED` | NO | auto | PK | — | — |
| `coupon_id` | `BIGINT UNSIGNED` | NO | — | FK, UQ¹ | — | — |
| `user_id` | `BIGINT UNSIGNED` | NO | — | FK, IX | — | — |
| `order_id` | `BIGINT UNSIGNED` | NO | — | FK, UQ¹ | — | — |
| `invoice_id` | `BIGINT UNSIGNED` | YES | NULL | FK | — | — |
| `amount` | `BIGINT` | NO | — | — | Discount actually applied | — |
| `redeemed_at` | `DATETIME(3)` | NO | `CURRENT_TIMESTAMP(3)` | IX | — | — |

¹ `uq_prm_coupon_redemptions_coupon_order (coupon_id, order_id)`.

---

## 14. Coverage and verification

| Check | How it is verified |
|---|---|
| Every P0 table above exists in `domain-model.md` with the same name | Cross-reference test in `tests/Schema/DomainModelParityTest.php` |
| Every `*_amount` column is `BIGINT` | Schema test (conventions §18) |
| No `FLOAT`/`DOUBLE`, no `ENUM` type | Schema test |
| Every FK has an index and a named constraint | Schema test |
| Every table has `utf8mb4` | `information_schema` assertion in CI |
| Money invariants (order, invoice, ledger, stock) | `CHECK` constraints + reconciliation jobs (§Domain model 30) |
| PII inventory matches the `PII` column | `tests/Security/PiiColumnInventoryTest.php` compares the dictionary list with the model casts |

### 14.1 Tables specified here

**85 tables**: identity (10), users (7), merchants (10), catalog (11) + `shr_media` (1),
pricing (6), inventory (4), cart (3), orders (7), invoices (3), payments (5), finance (8),
loyalty (7), promotions (3).

### 14.2 Tables specified at attribute level only (not yet column-documented)

These are fully described in [`domain-model.md`](./domain-model.md) — purpose, attributes,
relationships and invariants — and must follow the same conventions. Their column lists are
added when the module's first migration lands, with the same review checklist.

| Cluster | Tables still to be column-documented | Why it is safe to defer |
|---|---|---|
| Marketing | `mkt_referral_codes`, `mkt_referral_attributions`, `mkt_segments`, `mkt_lead_sources` | Onboarding path is P0 but its schema is small and fully enumerated in the catalogue; no money depends on it directly |
| Compensation | `cmp_commission_plans`, `cmp_commission_rules`, `cmp_commission_entries`, `cmp_commission_statements`, `cmp_plan_assignments` | Accrual consumes `fin_settlements` and `ord_sub_orders`, both specified here |
| Promotions (campaigns) | `prm_promotions` targets, `prm_campaigns`, `prm_campaign_products`, `prm_coupon_batches`, `prm_discount_grants` | Coupons and redemptions (the money path) are specified here |
| Shipping | `shp_carriers`, `shp_shipping_methods`, `shp_shipping_rates`, `shp_shipments`, `shp_shipment_events`, `shp_delivery_delays`, `shp_pickup_requests` | Cost is snapshotted on `ord_sub_orders.shipping_amount` (specified) |
| Returns | `rtn_returns`, `rtn_return_items`, `rtn_complaints`, `rtn_complaint_events`, `rtn_disputes`, `rtn_violation_reports`, `rtn_return_reasons` | Refunds flow through `pay_refunds` and `fin_settlements` (specified) |
| Messaging | `msg_conversations`, `msg_participants`, `msg_messages`, `msg_attachments`, `msg_tickets`, `msg_ticket_events`, `msg_blocks` | No ledger or order-state dependency |
| Notifications | `ntf_templates`, `ntf_notifications`, `ntf_notification_jobs`, `ntf_deliveries`, `ntf_preferences`, `ntf_credit_ledger`, `ntf_provider_configs` | Paid jobs settle through `pay_payments` (specified) |
| CMS | all 14 `cms_*` tables | Presentation only; no money, no order state |
| Reviews | all 6 `rvw_*` tables | Aggregates are projections onto specified catalog columns |
| Tenders | all 5 `tnd_*` tables | Award does not create orders in v1 |
| Classifieds | all 5 `cls_*` tables | Outside the order/ledger flow by design |
| Patogh | all 5 `ptg_*` tables | No on-platform payment in v1 |
| Samples | `smp_sample_requests`, `smp_sample_request_items` | Zero-amount flow, no invoice |
| Field ops | all 7 `geo_*` tables | Location only; `geo_regions` is referenced by specified address tables |
| Admin | `adm_audit_logs`, `adm_settings`, `adm_exports`, `adm_impersonations`, `adm_feature_flags`, `adm_report_subscriptions` | Cross-cutting; shapes are simple and fully enumerated |
| Shared | `shr_idempotency_keys`, `shr_outbox_events`, `shr_sequences`, `shr_failed_jobs_log` | Infrastructure; referenced by specified tables |

This split is deliberate and recorded: the columns of the commerce spine are fixed before code,
while peripheral modules are allowed to settle their physical shape with their first migration
without blocking the P0 build.


