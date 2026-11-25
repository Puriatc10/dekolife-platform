# Domain Model — Dekolife Platform

**Purpose:** The authoritative entity catalogue for the Dekolife modular monolith: one entry
per table, with purpose, key attributes, relationships, invariant rules and owning module.
**Audience:** Backend engineers, reviewers, test authors, agents writing migrations/models.
**Status:** Baseline (v1). Physical types, defaults and indexes are in
[`data-dictionary.md`](./data-dictionary.md); naming rules in
[`database-conventions.md`](./database-conventions.md); state transitions in
[`order-state-machine.md`](./order-state-machine.md).
**Source:** `docs/canonical-context.md` §3 (bounded contexts and prefixes) and §5 (baseline
decisions); contract appendices 1–14 as cited per module.

---

## 1. How to read this document

- **Table** is the physical table name and must match the module prefix in canonical-context
  §3. An entity without a prefix here does not exist.
- **P** is the delivery priority: `P0` = required for the first sellable release (browse →
  cart → order → invoice → payment → settlement, plus identity/roles), `P1` = required by
  contract but not by the first end-to-end flow, `P2` = contract feature that can ship after
  the core flows.
- **Key attributes** lists business-meaningful columns only; audit columns
  (`created_at`, `updated_at`, `deleted_at`, `created_by_id`, `updated_by_id`) are implied by
  [`database-conventions.md`](./database-conventions.md) §12 and are not repeated.
- Every entity inferred from the contract rather than named by it is marked with an
  **AMB** link (`AMB-<AREA>-<NNN>`). Those records are owned by the requirements agent's
  ambiguity register; this document raises them and states the modelling consequence.
- Money columns are integer Toman (`BIGINT`, `*_amount`). Timestamps are UTC.
- "Snapshot" means: written once at the moment of the event and never recalculated, because
  prices, addresses and titles change over time and history must stay explainable.

### 1.1 Module index

The catalogue below is complete: **194 entities across 28 modules, 90 of them P0**. Each module section carries the same structure — table catalogue, then module invariants.

| # | Module | Prefix | Entities | of which P0 | Contract area |
|---|---|---|---|---|---|
| 2 | Identity | `iam_` | 10 | 7 | IAM |
| 3 | Users | `usr_` | 7 | 4 | USR |
| 4 | Merchants | `mer_` | 10 | 4 | MER |
| 5 | Catalog | `cat_` | 13 | 9 | CAT |
| 6 | Pricing | `pri_` | 7 | 3 | PRI |
| 7 | Inventory | `stk_` | 7 | 4 | STK |
| 8 | Cart | `crt_` | 3 | 2 | CRT |
| 9 | Orders | `ord_` | 8 | 4 | ORD |
| 10 | Invoices | `inv_` | 4 | 3 | INV |
| 11 | Payments | `pay_` | 6 | 5 | PAY |
| 12 | Finance | `fin_` | 10 | 7 | FIN |
| 13 | Compensation | `cmp_` | 5 | 2 | CMP |
| 14 | Loyalty | `loy_` | 9 | 7 | LOY |
| 15 | Marketing | `mkt_` | 4 | 2 | MKT |
| 16 | Promotions | `prm_` | 8 | 4 | PRM |
| 17 | Shipping | `shp_` | 7 | 3 | SHP |
| 18 | Returns and complaints | `rtn_` | 7 | 3 | RTN |
| 19 | Tenders and auctions | `tnd_` | 5 | 0 | TND |
| 20 | Classifieds | `cls_` | 5 | 0 | CLS |
| 21 | Patogh local services | `ptg_` | 5 | 0 | PTG |
| 22 | Samples | `smp_` | 2 | 0 | SMP |
| 23 | Messaging | `msg_` | 7 | 4 | MSG |
| 24 | Notifications | `ntf_` | 7 | 4 | NTF |
| 25 | Cms | `cms_` | 14 | 0 | CMS |
| 26 | Reviews | `rvw_` | 6 | 1 | RVW |
| 27 | FieldOps | `geo_` | 7 | 2 | GEO |
| 28 | Admin | `adm_` | 6 | 2 | ADM |
| 29 | Shared kernel | `shr_` | 5 | 4 | SHR |
| — | **Total** | — | **194** | **90** | — |

Business tables total 189; the Shared kernel's five infrastructure tables are listed for completeness because every module references them.

### 1.2 Cross-module identity vocabulary

| Concept | Physical reference | Meaning |
|---|---|---|
| User | `iam_users.id` | Any authenticated actor (all five levels) |
| Role | `iam_roles.key` ∈ `consumer`, `store`, `supplier`, `marketer`, `admin` | Contract appendix 3 levels; a user may hold several (§1.3) |
| Store (همکار) | `mer_stores.id` | A trade buyer that may also sell/announce (appendix 3-1) |
| Supplier (تأمین‌کننده / تولیدکننده) | `mer_supplier_profiles.id` | Producer or distributor with its own panel (appendix 11) |
| Marketer (بازاریاب) | `mer_marketer_profiles.id` | Field agent with QR/referral code and commission plan (appendix 6) |
| Seller | `ord_sub_orders.seller_type` + `seller_id` | Whoever fulfils a sub-order: supplier or store |
| Buyer | `ord_orders.buyer_user_id` + `buyer_role` | Consumer or store account placing the order |

### 1.3 Roles are many-to-many, never a column

Canonical-context §1 and appendix 7 clause 6 require a **secondary level** per user, enabled
by the admin ("هر سطح کاربری می‌تواند یک سطح ثانویه داشته باشد"). Therefore:

- `iam_users` has no `role` column. Authorisation reads `iam_user_roles` joined to
  `iam_roles.key`.
- `iam_user_roles.is_primary` marks the level used for UI defaults and price-list resolution;
  exactly one active row per user may be primary (`ck_iam_user_roles_single_primary` enforced
  by a unique generated column on `(user_id)` filtered by `is_primary = 1`).
- A secondary role may be *forbidden* by configuration (e.g. store cannot be marketer),
  stored on `iam_roles.is_secondary_allowed` plus `adm_settings` overrides. The policy layer
  — not the schema — decides.

---

## 2. Identity (`iam_`) — module owner: Identity, area IAM

| Table | P | Purpose | Key attributes | Relationships | Invariants / rules |
|---|---|---|---|---|---|
| `iam_users` | P0 | The single account record for every actor, all five levels | `public_id`, `mobile` (unique, normalised to `09` + nine digits), `mobile_verified_at`, `email`, `password_hash`, `status` (`pending`,`active`,`suspended`,`deactivated`,`blocked`), `primary_role_id`, `invite_code`, `locale`, `timezone`, `last_login_at`, `failed_login_count`, `locked_until`, `two_factor_required`, `anonymized_at` | 1–N `iam_user_roles`; 1–1 `usr_profiles`; 1–N `usr_addresses`; 1–N `ord_orders` as buyer | Mobile is the login identifier and unique across all levels. `status='blocked'` blocks login but keeps history. A user with `anonymized_at` set has all PII nulled and cannot log in. Deleting a user is never a hard delete. |
| `iam_roles` | P0 | The five contract levels plus capability grouping | `key` (unique, code-owned), `name_fa`, `name_en`, `level` (1–5), `is_secondary_allowed`, `is_assignable_by_admin`, `is_system`, `sort_order` | N–M users via `iam_user_roles`; N–M permissions | Rows are seeded, not user-created. `key` never changes (code and policies depend on it). A role in use cannot be deleted, only deactivated. |
| `iam_user_roles` | P0 | Assignment of a level to a user, including secondary levels | `user_id`, `role_id`, `is_primary`, `granted_by_id`, `granted_at`, `revoked_at`, `revoke_reason` | → `iam_users`, → `iam_roles` | At most one active primary role per user; at most one active row per `(user_id, role_id)`; revoking is `revoked_at` + reason, never a delete; every grant/revoke writes `adm_audit_logs`. |
| `iam_permissions` | P0 | Atomic capability catalogue used by policies | `key` (`orders.cancel.any`), `module`, `name_fa`, `description` | N–M roles | Keys are code-owned constants; a permission not referenced by a policy is dead and fails the policy-coverage test. |
| `iam_role_permissions` | P0 | Grant of capability to a role | `role_id`, `permission_id` | → roles, → permissions | Admin role has all permissions by wildcard, not by 1 000 rows. |
| `iam_two_factor_secrets` | P0 | TOTP secret and recovery codes for admin 2FA (appendix 1 clause 7) | `user_id` (unique), `secret_encrypted`, `recovery_codes_encrypted`, `confirmed_at`, `last_used_at` | 1–1 `iam_users` | Secret is encrypted at rest and never returned by any API; `confirmed_at` is required before 2FA is enforced; regenerating recovery codes invalidates the old set. |
| `iam_login_events` | P1 | Audit trail of authentication attempts (brute-force detection, appendix 9 clause 3) | `user_id` (nullable), `mobile_attempted_hash`, `result` (`success`,`bad_password`,`unknown_user`,`locked`,`2fa_failed`,`blocked`), `ip_address`, `user_agent`, `occurred_at` | → `iam_users` | Append-only, partitioned monthly, kept 12 months. Never stores the attempted password. `mobile_attempted_hash` is a peppered hash, not plaintext. |
| `iam_password_reset_tokens` | P0 | SMS/email reset flow | `mobile`, `token_hash`, `expires_at`, `used_at`, `ip_address` | — | Single use, 15-minute expiry, token stored hashed; requesting a new token invalidates the previous. |
| `iam_invitations` | P1 | Onboarding invite issued by marketer/admin or by a colleague referral (appendix 4 clause 13, appendix 6 clause 8) | `code` (unique), `inviter_user_id`, `invited_mobile`, `role_key`, `channel` (`sms`,`link`,`qr`), `expires_at`, `accepted_at`, `accepted_user_id` | → `mkt_referral_attributions` on acceptance | An invitation is single-use; acceptance creates the user with `status='pending'` and a forced password change when issued by a marketer (appendix 5 clause 2). |
| `iam_sessions_security` | P2 | Device/session registry for revoking tokens | `user_id`, `token_id`, `device_name`, `ip_address`, `last_used_at`, `revoked_at` | → `personal_access_tokens` | Revoking a session deletes the Sanctum token; admin revocation is audited. AMB-IAM-003 (contract does not require device management; added for security parity with 2FA). |

**Module invariants**

1. One mobile = one account, across all five levels. A person who is both marketer and
   consumer holds one `iam_users` row with two `iam_user_roles` rows (appendix 12 clause 11).
2. Any change of level writes `iam_user_roles` history **and** `adm_audit_logs`
   (appendix 1 clause 10 requires logging level changes).
3. Admin authentication uses a separate guard and mandatory 2FA; the `admin` role cannot be
   self-granted — only an existing admin can grant it.

---

## 3. Users (`usr_`) — module owner: Users, area USR

| Table | P | Purpose | Key attributes | Relationships | Invariants / rules |
|---|---|---|---|---|---|
| `usr_profiles` | P0 | Shared human profile for every level | `user_id` (unique), `first_name`, `last_name`, `display_name`, `national_id`, `national_id_verified_at`, `birth_date`, `gender`, `avatar_media_id`, `bio`, `website_url`, `social_links_json`, `province_id`, `city_id`, `postal_code`, `address_line`, `latitude`, `longitude`, `is_public`, `public_approved_at`, `approved_by_id` | 1–1 `iam_users`; → `geo_regions`; → `shr_media` | `national_id` is unique when present and validated by the Iranian check-digit algorithm; `is_public = 1` requires `public_approved_at` (appendix 5 clause 7/8: public profile fields only after admin approval); `birth_date` powers the birthday greeting (appendix 12 clause 7) and is optional with explicit consent. |
| `usr_consumer_profiles` | P0 | Consumer-specific state: tier, stars, lifetime value (appendix 4 clause 9) | `user_id` (unique), `tier_id`, `stars_count`, `lifetime_points`, `lifetime_purchase_amount`, `orders_count`, `preferred_city_id`, `referrer_user_id`, `last_activity_at` | → `iam_users`, → `loy_tiers` | `stars_count = floor(lifetime_points / 100)` is a projection maintained by Loyalty, not an independent truth; `referrer_user_id` is set once at first order and never changes. |
| `usr_addresses` | P0 | Book of delivery addresses | `user_id`, `label`, `recipient_name`, `recipient_mobile`, `province_id`, `city_id`, `postal_code`, `address_line`, `latitude`, `longitude`, `is_default`, `deleted_at` | → `iam_users`, → `geo_regions` | Exactly one default per user (unique generated column on `(user_id)` where `is_default = 1`); addresses are snapshotted into `ord_orders` so later edits never rewrite history. |
| `usr_favorites` | P1 | Wishlist of products, stores and suppliers (appendix 3-1 clause 6, appendix 12 clause 15) | `user_id`, `product_id`, `store_id`, `supplier_id`, `created_at` | → `cat_products`, `mer_stores`, `mer_supplier_profiles` | Exactly one target per row (`CHECK`), unique per `(user_id, product_id)` etc.; a deleted target removes the row by cascade. |
| `usr_identity_documents` | P0 | KYC documents for store/supplier/marketer verification | `user_id`, `document_type` (`national_card`,`business_license`,`postal_receipt`,`contract`), `media_id`, `status` (`pending`,`approved`,`rejected`,`expired`), `reviewed_by_id`, `reviewed_at`, `reject_reason`, `expires_at` | → `shr_media` (private disk) | Files are private and only reachable through signed URLs; a rejected document keeps its reason for the applicant; approving a business licence is the gate for `mer_stores.status='active'`. |
| `usr_consents` | P1 | Versioned legal consents (terms, privacy, marketing) | `user_id`, `consent_key`, `document_version`, `accepted_at`, `ip_address`, `revoked_at` | → `iam_users` | Consent must exist for the version in force at registration; marketing consent is opt-in and revocable, and gates `ntf_preferences`. |
| `usr_settings` | P2 | Per-user UI preferences | `user_id` (unique), `locale`, `theme`, `default_role_key`, `marketing_opt_in`, `quiet_hours_json` | → `iam_users` | `default_role_key` must be one of the user's active roles. |

**Module invariants**

1. A profile is created lazily on first profile write, but every user has a display name
   resolved by fallback (`display_name` → `first_name + last_name` → masked mobile).
2. PII never leaves the module un-scoped: public endpoints expose only fields approved on
   `usr_profiles` (appendix 5 clause 8).
3. Address deletion is soft; orders and invoices keep the snapshot, so shipping labels remain
   reproducible.

---

## 4. Merchants (`mer_`) — module owner: Merchants, area MER

The contract has two distinct seller identities with different panels: **store/همکار**
(appendix 3-1) and **supplier/تولیدکننده–عرضه‌کننده** (appendix 11). Both are merchant
accounts owned by a user; they are modelled as two tables rather than one polymorphic table
because their panels, approvals and price lists differ. AMB-MER-001 records this split.

| Table | P | Purpose | Key attributes | Relationships | Invariants / rules |
|---|---|---|---|---|---|
| `mer_stores` | P0 | A trade store (همکار) onboarded publicly or by a marketer | `public_id`, `owner_user_id`, `store_code` (unique), `legal_name`, `display_name`, `slug`, `business_type`, `category_id`, `national_id`, `postal_code`, `mobile_primary`, `mobile_whatsapp`, `landline`, `province_id`, `city_id`, `address_line`, `latitude`, `longitude`, `google_maps_url`, `services_description`, `status` (`draft`,`pending_review`,`active`,`inactive`,`suspended`,`rejected`), `price_list_id`, `marketer_id`, `registration_source` (`public`,`marketer`,`admin`), `marketer_edit_locked_at`, `activated_at`, `deactivated_at`, `inactivity_reason`, `rating_avg`, `completed_orders_count`, `last_order_at`, `approved_by_id` | → `iam_users` (owner), → `pri_price_lists`, → `mer_marketer_profiles`, → `geo_regions`; 1–N `mer_store_locations`, 1–N `mer_store_media`, 1–N `mer_store_identifiers` | A store cannot be `active` without an approved business licence and an assigned price list. `marketer_id` is write-once after attribution confirmation (re-attribution needs admin). Editing by the marketer is allowed only while `marketer_edit_locked_at IS NULL` (5-minute window, appendix 6 clause 9). Auto-deactivation after inactivity is a scheduled command writing `inactivity_reason` (appendix 4 clause 23). |
| `mer_store_locations` | P1 | Physical branches of a store | `store_id`, `name`, `province_id`, `city_id`, `address_line`, `postal_code`, `latitude`, `longitude`, `phone`, `opening_hours_json`, `is_primary`, `status` | → `mer_stores`, → `geo_regions` | At most one `is_primary` per store; deletion is soft so historical shipments still resolve. |
| `mer_store_media` | P1 | Storefront, owner and business-card photos (appendix 6 clause 2) | `store_id`, `media_id`, `kind` (`storefront`,`owner`,`business_card`,`gallery`,`interior`), `caption`, `sort_order` | → `mer_stores`, → `shr_media` | `kind='owner'` is PII and never public; max 10 gallery items per store (enforced in validation, not schema). |
| `mer_store_identifiers` | P0 | PII-safe duplicate detection index (appendix 5 clause 3: search by national id, postal code, mobile) | `store_id`, `identifier_type` (`national_id`,`postal_code`,`mobile`), `identifier_hash` (SHA-256 with pepper), `last4` | → `mer_stores` | `UNIQUE (identifier_type, identifier_hash)` for `national_id` and `mobile`; **not** unique for `postal_code` (a mall shares one). Raw values stay in `mer_stores`; this index allows duplicate search without scanning PII columns. |
| `mer_store_status_history` | P1 | Why and when a store changed state (activation, auto-inactivation, suspension) | `store_id`, `from_status`, `to_status`, `reason_code`, `reason_text`, `actor_type` (`system`,`admin`,`marketer`), `actor_id`, `occurred_at` | → `mer_stores` | Append-only; required to justify exclusion of a store from the active list (appendix 4 clause 24). |
| `mer_supplier_profiles` | P0 | Producer/distributor account with its own panel (appendix 11 clause 1) | `public_id`, `owner_user_id`, `supplier_code` (unique), `legal_name`, `brand_name`, `slug`, `national_id`, `economic_code`, `registration_number`, `established_year`, `employee_count`, `postal_code`, `mobile`, `landline`, `province_id`, `city_id`, `address_line`, `latitude`, `longitude`, `website_url`, `social_links_json`, `description`, `status` (`draft`,`pending_review`,`active`,`inactive`,`suspended`,`rejected`), `price_list_id`, `is_buyer_partner`, `buyer_discount_percent`, `rating_avg`, `violation_score`, `approved_by_id`, `activated_at` | → `iam_users`, → `pri_price_lists`; 1–N `mer_supplier_branches`, 1–N `mer_supplier_media` | `economic_code` + `national_id` must both be present before activation. `is_buyer_partner` enables buying from other suppliers with `buyer_discount_percent` (appendix 11 clause 2); the percent is snapshotted on the order line. `violation_score` is negative points, displayed only after admin confirmation (appendix 5 clause 14). |
| `mer_supplier_branches` | P1 | Nationwide agencies/representatives with map location (appendix 11 clause 19) | `supplier_id`, `title`, `province_id`, `city_id`, `address_line`, `latitude`, `longitude`, `phone`, `contact_name`, `services_json`, `is_active` | → `mer_supplier_profiles` | Branch listing is public; coordinates are required because consumers are referred for measurement/installation. |
| `mer_supplier_media` | P2 | Supplier gallery, video and factory photos (appendix 11 clause 3) | `supplier_id`, `media_id`, `kind` (`logo`,`cover`,`gallery`,`video`,`catalog`), `caption`, `sort_order` | → `mer_supplier_profiles`, → `shr_media` | Catalogue PDFs are `visibility='public'`; factory videos capped at 100 MB by validation. |
| `mer_marketer_profiles` | P0 | Field agent identity, referral assets and plan (appendix 6) | `user_id` (unique), `marketer_code` (unique), `referral_slug` (unique), `qr_token` (unique), `qr_media_id`, `plan_id`, `parent_marketer_id`, `province_id`, `city_id`, `national_id`, `hired_at`, `status` (`active`,`suspended`,`inactive`,`terminated`), `commission_disabled_at`, `commission_disabled_by_id`, `visits_count`, `onboarded_stores_count`, `onboarded_orders_amount`, `last_activity_at` | → `iam_users`, → `cmp_commission_plans`, self-FK `parent_marketer_id`; 1–N `mkt_referral_codes`, 1–N `geo_visits` | `marketer_code`, `referral_slug` and `qr_token` are immutable after issuance (printed business cards). `commission_disabled_at` suppresses accrual without terminating the agent (appendix 4 clause 22). A marketer cannot be their own `parent_marketer_id`. |
| `mer_marketer_documents` | P2 | Contract, ID and guarantee documents of the agent | `marketer_id`, `document_type`, `media_id`, `signed_at`, `expires_at`, `status` | → `mer_marketer_profiles` | Private disk only; expiry blocks new attributions but not existing commissions. AMB-MER-004 (contract does not enumerate agent documents; inferred from onboarding practice). |

**Module invariants**

1. A merchant (store or supplier) is always backed by exactly one `iam_users` row and at
   least one active `store`/`supplier` role; the reverse is not true (a consumer may have no
   merchant record).
2. Approval is a two-step state (`pending_review` → `active`) performed by an admin, and the
   approving admin is recorded.
3. Deleting a merchant is forbidden while any `ord_sub_orders`, `inv_invoices`,
   `fin_settlements` or `pri_prices` reference it. Deactivate instead.
4. Every merchant's price list defines what it *pays* (buyer lists) — its own selling prices
   live in `pri_prices` keyed by the **audience** list, not by its own merchant id.

---

## 5. Catalog (`cat_`) — module owner: Catalog, area CAT

| Table | P | Purpose | Key attributes | Relationships | Invariants / rules |
|---|---|---|---|---|---|
| `cat_categories` | P0 | Admin-managed product taxonomy; also the classifieds gate (appendix 5 clause 4) | `parent_id`, `path`, `depth`, `name_fa`, `name_en`, `slug` (unique), `description`, `icon_media_id`, `sort_order`, `is_active`, `seo_title`, `seo_description`, `product_count` | self-FK, 1–N `cat_category_attributes`, N–M products via `cat_products.primary_category_id` | Max depth 4. `path` is a materialised path (`/1/7/22/`) maintained by the module; moving a node rewrites descendants in one transaction. A category with products cannot be deleted, only deactivated. |
| `cat_category_attributes` | P0 | Which attributes a category requires/filters on | `category_id`, `attribute_id`, `is_required`, `is_filterable`, `is_comparable`, `sort_order` | → `cat_categories`, → `cat_attributes` | A filterable attribute must have `cat_attributes.is_filterable = 1`; changing this set rebuilds the filtered-search index. |
| `cat_brands` | P0 | Manufacturer/brand master data | `name_fa`, `name_en`, `slug` (unique), `logo_media_id`, `supplier_id`, `description`, `is_active`, `sort_order`, `seo_title`, `seo_description` | → `mer_supplier_profiles` (optional owner), 1–N products | A brand may exist without a supplier (imported brands); `slug` is permanent once published because it is in URLs and SEO sitemaps. |
| `cat_units` | P0 | Unit of measure: m², linear metre, piece, roll, box, pack | `code` (unique: `m2`,`m`,`pcs`,`roll`,`box`), `name_fa`, `precision` (0–3), `is_active` | 1–N products | `precision` governs allowed quantity decimals; selling parquet by m² uses precision 3, by piece uses 0. |
| `cat_products` | P0 | Sellable product (SPU) | `public_id`, `sku` (unique), `slug` (unique among live rows), `title_fa`, `title_en`, `short_description`, `description`, `product_type` (`simple`,`variant`,`service`), `supplier_id`, `store_id`, `brand_id`, `primary_category_id`, `unit_id`, `status` (`draft`,`pending_review`,`active`,`inactive`,`archived`), `is_wholesale_only`, `min_order_quantity`, `max_order_quantity`, `step_quantity`, `weight_grams`, `warranty_months`, `country_of_origin`, `has_variants`, `rating_avg`, `reviews_count`, `sales_count`, `published_at`, `seo_title`, `seo_description`, `search_keywords` | → `mer_supplier_profiles` **or** `mer_stores` (exactly one, `CHECK`), → `cat_brands`, → `cat_categories`, → `cat_units`; 1–N variants, 1–N `cat_media`, 1–N `pri_prices` | Exactly one seller (`supplier_id` XOR `store_id`) — AMB-CAT-001. `has_variants = 0` means the product itself is the sellable unit and a single default variant exists. `step_quantity` and `min_order_quantity` are enforced at cart add. A product with order history cannot be deleted, only `archived`. |
| `cat_product_variants` | P0 | The actual SKU sold: size, colour, thickness, pattern | `product_id`, `sku` (unique), `barcode`, `title`, `attribute_signature` (unique per product, hash of attribute value ids), `is_default`, `status`, `weight_grams`, `sort_order` | → `cat_products`; N–M attribute values via `cat_variant_attribute_values`; 1–N `pri_prices`, → `stk_stock_items` | The combination of attribute values is unique per product (`uq_cat_product_variants_signature`); `attribute_signature` makes that enforceable without a 12-column index. Exactly one default variant per product. A variant with stock or order history cannot be deleted. |
| `cat_attributes` | P0 | Attribute definitions with data types and filtering behaviour (appendix 7 clause 4) | `code` (unique), `name_fa`, `data_type` (`string`,`number`,`boolean`,`select`,`multiselect`), `unit_label`, `is_filterable`, `is_variant_axis`, `is_comparable`, `sort_order` | 1–N `cat_attribute_values`, N–M categories | `is_variant_axis = 1` means the attribute distinguishes variants (colour, size) and therefore needs values; free-text attributes can never be a variant axis. `data_type` drives validation and comparison UI (appendix 2 clause 5). |
| `cat_attribute_values` | P0 | Enumerated values for `select`/`multiselect` attributes | `attribute_id`, `value_fa`, `value_en`, `slug`, `color_hex`, `sort_order` | → `cat_attributes`; N–M variants | `UNIQUE (attribute_id, slug)`; a value in use by a live variant cannot be deleted, only deactivated. |
| `cat_variant_attribute_values` | P0 | Which attribute values describe a variant | `variant_id`, `attribute_id`, `attribute_value_id`, `numeric_value` | → variants, attributes, values | `UNIQUE (variant_id, attribute_id)` — one value per axis. `numeric_value` is populated for numeric axes (e.g. thickness 8.0) so range filters work. |
| `cat_media` | P1 | Product/variant gallery, video and 360 assets (appendix 2 clause 4) | `product_id`, `variant_id`, `media_id`, `kind` (`image`,`video`,`360`,`catalog`), `alt_text`, `sort_order`, `is_primary` | → `cat_products`, `cat_product_variants`, → `shr_media` | At most one `is_primary` image per product; every image needs `alt_text` for SEO (appendix 9 clause 1). Files live in `shr_media`; this table only orders and describes them. |
| `cat_product_relations` | P2 | Related / alternative / accessory products | `product_id`, `related_product_id`, `relation_type` (`related`,`alternative`,`accessory`), `sort_order` | → products (twice) | No self-reference; symmetric relations are stored twice by the module. |
| `cat_search_synonyms` | P2 | Persian search synonyms (`پارکت` ↔ `کفپوش چوبی`) | `term`, `synonyms_json`, `is_active` | — | Used by the search query builder only; changing it never rewrites products. |
| `cat_product_import_batches` | P2 | Bulk import runs from owner spreadsheets (appendix 9 clause 4) | `batch_no`, `file_media_id`, `row_count`, `success_count`, `error_count`, `status`, `created_by_id`, `completed_at` | → `shr_media` | Import rows are validated before any product is created; the batch is the rollback unit. AMB-CAT-004 (import format not specified by contract). |

**Module invariants**

1. A product's category, brand and unit are mandatory before `status='active'`.
2. Price is **never** stored on `cat_products`; it lives in `pri_prices` keyed by audience.
   Any code that reads a price from the catalog table is a defect.
3. Stock is **never** stored on `cat_products`; it lives in `stk_stock_items`.
4. Persian text is normalised (yeh/kaf, ZWNJ preserved, digits) before storage so search and
   deduplication behave.
5. Deleting a category, brand or attribute value that is referenced by a live product is
   forbidden at the FK level (`RESTRICT`).

---

## 6. Pricing (`pri_`) — module owner: Pricing, area PRI

Prices are resolved **server-side only**, in the fixed order
base → role/tier list → volume → promotion → coupon → loyalty (canonical §5). Each step is
recorded so the invoice can explain itself.

| Table | P | Purpose | Key attributes | Relationships | Invariants / rules |
|---|---|---|---|---|---|
| `pri_price_lists` | P0 | An audience-specific price book (consumer, store tier A–D, wholesale, supplier-partner) | `code` (unique: `consumer`,`store_a`…`store_d`,`supplier_partner`,`wholesale_base`), `name_fa`, `audience` (`consumer`,`store`,`supplier`,`marketer`), `tier` (`A`,`B`,`C`,`D`, null), `currency` (`IRT`), `is_default`, `priority`, `effective_from`, `effective_to`, `status`, `created_by_id` | 1–N `pri_prices`; assigned to `mer_stores.price_list_id`, `mer_supplier_profiles.price_list_id`, `iam_roles` defaults | Exactly one default list per audience. A list in use by an active merchant cannot be deleted. Overlapping effective windows for the same audience+tier are rejected by validation. |
| `pri_prices` | P0 | A unit price for a variant in a list, optionally volume-banded and time-bounded | `price_list_id`, `product_id`, `variant_id`, `amount`, `compare_at_amount`, `min_quantity`, `max_quantity`, `includes_tax`, `source` (`manual`,`import`,`rule`,`promotion`), `effective_from`, `effective_to`, `status`, `approved_by_id` | → `pri_price_lists`, → products/variants | `CHECK (amount >= 0)`. Only one **open** active row per `(price_list_id, variant_id, min_quantity)` — enforced by the `active_key` generated column + unique index (conventions §4.7). `variant_id` NULL means "applies to the product's default variant". A price change writes `adm_audit_logs` (appendix 1 clause 10). |
| `pri_volume_tiers` | P1 | Volume discount bands that are not tied to a single SKU (per product, category or brand) | `price_list_id`, `scope_type` (`product`,`category`,`brand`,`supplier`), `scope_id`, `min_quantity`, `max_quantity`, `discount_percent`, `discount_amount`, `priority`, `effective_from`, `effective_to` | → `pri_price_lists` | Bands must not overlap for the same scope and list; the most specific scope wins; the applied band is snapshotted on the order line. |
| `pri_markup_rules` | P1 | Consumer-vs-partner spread, applied manually or as a percentage on all products (appendix 12 clause 1) | `price_list_id`, `scope_type` (`all`,`category`,`brand`,`product`), `scope_id`, `markup_percent`, `fixed_amount`, `rounding_mode` (`none`,`up_1000`,`up_5000`), `effective_from`, `effective_to`, `created_by_id` | → `pri_price_lists` | Only one active rule per scope and list; `all` is the fallback; markup is applied **after** the base list price and before volume discounts. |
| `pri_price_list_assignments` | P1 | Explicit assignment of a list to a merchant or user, overriding role defaults | `price_list_id`, `assignee_type` (`store`,`supplier`,`user`), `assignee_id`, `assigned_by_id`, `assigned_at`, `expires_at` | → `pri_price_lists` | The most recent non-expired assignment wins; assignment writes an audit entry; removing an assignment falls back to the role default. |
| `pri_price_snapshots` | P0 | The immutable explanation of a line's price (canonical §5: "order lines snapshot price and applied rules") | `order_item_id` (unique), `price_list_id`, `base_unit_amount`, `volume_discount_amount`, `promotion_discount_amount`, `coupon_discount_amount`, `loyalty_discount_amount`, `final_unit_amount`, `applied_rules_json`, `resolved_at`, `resolver_version` | 1–1 `ord_order_items` | Write-once. `final_unit_amount` must equal the arithmetic of the components (asserted by a DB `CHECK` on the sum and a feature test). `resolver_version` identifies the pricing engine build so old invoices remain explainable after a rule change. |
| `pri_price_change_requests` | P2 | Bulk price update by a supplier awaiting admin approval | `supplier_id`, `payload_json`, `item_count`, `status` (`draft`,`submitted`,`approved`,`rejected`,`applied`), `submitted_by_id`, `reviewed_by_id`, `applied_at` | → `mer_supplier_profiles` | Applying a request is a single transaction that writes `pri_prices` and the audit log; partial application is forbidden. AMB-PRI-002 (contract allows suppliers to change prices directly; approval workflow inferred from appendix 7 clause 2). |

**Module invariants**

1. Price resolution is deterministic for a given `(variant, quantity, user, at)` tuple; the
   resolver is pure and covered by golden tests.
2. No client-supplied price is ever trusted; the cart re-resolves on every read and the order
   re-resolves at checkout, snapshotting the result.
3. A price of `0` is legal only for explicitly free items (sample, gift) and must carry a
   `source` explaining it.
4. Percentage discounts never stack beyond `max_discount_amount` when the promotion defines
   one; the order of application is fixed and recorded.

---

## 7. Inventory (`stk_`) — module owner: Inventory, area STK

| Table | P | Purpose | Key attributes | Relationships | Invariants / rules |
|---|---|---|---|---|---|
| `stk_warehouses` | P0 | Physical stock location per supplier (and platform-owned buffers) | `code` (unique), `name`, `supplier_id`, `province_id`, `city_id`, `address_line`, `latitude`, `longitude`, `is_default`, `status` | → `mer_supplier_profiles`, → `geo_regions` | Exactly one default warehouse per supplier; a warehouse with stock cannot be deactivated. |
| `stk_stock_items` | P0 | On-hand, reserved and available quantity per variant per warehouse | `warehouse_id`, `variant_id`, `product_id`, `on_hand_quantity`, `reserved_quantity`, `available_quantity` (generated), `safety_stock`, `reorder_point`, `unit_cost_amount`, `last_counted_at`, `version` | → `stk_warehouses`, → `cat_product_variants` | `UNIQUE (warehouse_id, variant_id)`. `available_quantity` is a stored generated column `on_hand − reserved` so it cannot drift. `on_hand` and `reserved` are never negative (`CHECK`). Every mutation bumps `version` and writes a `stk_stock_movements` row. |
| `stk_stock_reservations` | P0 | Short-lived hold placed by a cart/order before payment | `stock_item_id`, `order_id`, `sub_order_id`, `order_item_id`, `quantity`, `status` (`held`,`committed`,`released`,`expired`), `expires_at`, `idempotency_key` (unique) | → stock item, → orders | A reservation is created with `FOR UPDATE` on the stock item; `held` expires after the configured window (default 30 min) and the expiry job releases it. `committed` is terminal and is written when payment captures. Sum of `held` per stock item must equal `reserved_quantity` (reconciliation query runs in tests). |
| `stk_stock_movements` | P0 | Append-only stock ledger | `stock_item_id`, `movement_type` (`receipt`,`issue`,`reservation`,`release`,`adjustment`,`return`,`damage`,`transfer`), `quantity_delta`, `balance_after`, `sub_order_id`, `return_id`, `reason`, `actor_id`, `occurred_at` | → stock items, → orders, → returns | Append-only, never updated. `balance_after` must equal the previous balance plus delta — verified by a nightly invariant job. Corrections are compensating movements, never edits. |
| `stk_stock_alerts` | P2 | Low-stock and out-of-stock notifications for suppliers | `stock_item_id`, `alert_type` (`low`,`out`,`overstock`), `threshold`, `status` (`open`,`acknowledged`,`resolved`), `notified_at`, `resolved_at` | → stock items | One open alert per `(stock_item_id, alert_type)`; auto-resolves when the quantity crosses back. |
| `stk_stock_counts` | P2 | Physical count sessions and their adjustments | `warehouse_id`, `count_no`, `status` (`draft`,`counting`,`review`,`posted`), `started_by_id`, `posted_at`, `variance_quantity`, `variance_amount` | 1–N `stk_stock_count_lines` | Posting a count writes `movement_type='adjustment'` rows and cannot be undone — a new count corrects it. |
| `stk_stock_count_lines` | P2 | Counted vs system quantity per variant | `count_id`, `variant_id`, `system_quantity`, `counted_quantity`, `variance_quantity`, `note` | → `stk_stock_counts` | `variance = counted − system`; zero-variance lines are kept for audit. |

**Module invariants**

1. Stock is authoritative only in `stk_stock_items`; `cat_products` shows availability by
   aggregating this table (cached, never stored on the product).
2. A sub-order cannot move to `confirmed` if any of its items lack a `committed` reservation.
3. Cancellation, rejection, return and expiry each release or restock exactly once, guarded
   by the `stk_stock_movements` idempotency of `(sub_order_id, movement_type)`.

---

## 8. Cart (`crt_`) — module owner: Cart, area CRT

| Table | P | Purpose | Key attributes | Relationships | Invariants / rules |
|---|---|---|---|---|---|
| `crt_carts` | P0 | A basket, owned by a guest (token) or a user, scoped to one price list | `public_id`, `session_token` (unique, guest), `user_id`, `currency`, `price_list_id`, `status` (`active`,`converted`,`abandoned`,`merged`,`expired`), `items_count`, `subtotal_amount`, `discount_amount`, `shipping_amount`, `total_amount`, `coupon_id`, `coupon_code`, `expires_at`, `converted_order_id`, `last_activity_at`, `lock_version` | → `iam_users`, → `pri_price_lists`, → `prm_coupons`, → `ord_orders`; 1–N items | One `active` cart per user (unique generated column on `user_id` where `status='active'`); a guest cart merges into the user cart on login and the guest row becomes `merged`. Amount columns are **cached projections** of the items; the authoritative computation is re-run on every read and at checkout. A converted cart is immutable. |
| `crt_cart_items` | P0 | One requested variant with quantity | `cart_id`, `product_id`, `variant_id`, `seller_type`, `seller_id`, `quantity`, `unit_price_amount`, `price_list_id`, `price_snapshot_json`, `notes`, `added_at` | → `crt_carts`, → `cat_products`, → `cat_product_variants` | `UNIQUE (cart_id, variant_id)` — adding the same variant increments quantity. Quantity respects `min_order_quantity`, `step_quantity` and `max_order_quantity`. `unit_price_amount` is display-only and re-resolved on read; the order re-prices at checkout. A cart may contain items from several sellers; the order splits into sub-orders by seller. |
| `crt_cart_events` | P2 | Funnel events for abandoned-cart analysis and reminders | `cart_id`, `event` (`created`,`item_added`,`item_removed`,`coupon_applied`,`checkout_started`,`converted`,`abandoned`), `payload_json`, `occurred_at` | → `crt_carts` | Append-only, kept 180 days; used to trigger reminder notifications, never to compute money. AMB-CRT-001 (reminder cadence not specified). |

**Module invariants**

1. A cart is never shared across price lists: changing the buyer's role or tier re-resolves
   prices and marks the cart `active` with a new `price_list_id`.
2. Deleting a product/variant that is in a cart does not delete the cart row; the item is
   flagged unavailable at read time and blocks checkout until removed.
3. Checkout is idempotent: the same cart produces at most one order (`converted_order_id`
   unique), enforced by `shr_idempotency_keys` scope `cart.checkout`.

---

## 9. Orders (`ord_`) — module owner: Orders, area ORD

Parent order = commercial intent of one buyer across sellers. Sub-order = fulfilment and
money unit per seller (canonical §5). The state machine is normative in
[`order-state-machine.md`](./order-state-machine.md).

| Table | P | Purpose | Key attributes | Relationships | Invariants / rules |
|---|---|---|---|---|---|
| `ord_orders` | P0 | Buyer-level order header and money totals | `public_id`, `order_number` (unique, human, `DK-1405-000123`), `buyer_user_id`, `buyer_role` (`consumer`,`store`), `channel` (`web`,`pwa`,`admin`,`marketer`), `status` (`awaiting_payment`,`paid`,`processing`,`completed`,`cancelled`,`expired`,`partially_refunded`,`refunded`), `payment_status` (`unpaid`,`partially_paid`,`paid`,`partially_refunded`,`refunded`), `fulfilment_status` (`unfulfilled`,`partial`,`fulfilled`), `currency`, `price_list_id`, `items_total_amount`, `discount_total_amount`, `coupon_discount_amount`, `loyalty_discount_amount`, `shipping_total_amount`, `tax_total_amount`, `grand_total_amount`, `paid_amount`, `refunded_amount`, `wallet_applied_amount`, `deposit_amount`, `balance_due_amount`, `coupon_id`, `coupon_code`, `shipping_recipient_name`, `shipping_mobile`, `shipping_province_id`, `shipping_city_id`, `shipping_postal_code`, `shipping_address_line`, `shipping_latitude`, `shipping_longitude`, `address_snapshot_json`, `buyer_note`, `placed_at`, `paid_at`, `completed_at`, `cancelled_at`, `cancelled_by_id`, `cancellation_reason_code`, `expires_at`, `idempotency_key` (unique), `created_by_id`, `lock_version` | → buyer, → `crt_carts` (via `converted_order_id` reverse), → `prm_coupons`; 1–N `ord_sub_orders`, 1–N `ord_order_items`, 1–N `pay_payments`, 1–N `inv_invoices` | `grand_total_amount = items_total − discount_total + shipping + tax` (DB `CHECK`). `paid_amount + wallet_applied_amount ≤ grand_total_amount`. Status is **derived from sub-orders** by the Orders service, never set directly by a controller. `expires_at` (default +30 min) closes unpaid orders. Cancelling a parent cancels every non-shipped sub-order in the same transaction. |
| `ord_sub_orders` | P0 | Per-seller fulfilment and money unit; carries its own state machine | `public_id`, `sub_order_number` (unique, `<order_number>-01`), `order_id`, `seller_type` (`supplier`,`store`), `seller_id`, `status` (`awaiting_confirmation`,`incomplete_reported`,`confirmed`,`preparing`,`ready_to_ship`,`shipped`,`delivered`,`completed`,`cancelled`,`return_in_progress`,`returned`,`refunded`), `subtotal_amount`, `discount_amount`, `shipping_amount`, `total_amount`, `commission_amount`, `payable_to_seller_amount`, `is_on_hold`, `hold_reason_code`, `hold_until`, `promised_ship_at`, `confirmed_at`, `shipped_at`, `delivered_at`, `completed_at`, `cancelled_at`, `cancel_actor` (`buyer`,`supplier`,`store`,`admin`,`system`), `cancel_reason_code`, `cancel_reason_text`, `return_window_ends_at`, `invoice_id`, `shipment_id`, `points_awarded_at`, `settlement_id`, `lock_version` | → `ord_orders`, → seller, → `inv_invoices`, → `shp_shipments`, → `fin_settlements`; 1–N items | `payable_to_seller_amount = subtotal − discount − commission + shipping_share`. Every transition writes `ord_order_status_history`. Status changes are rejected while `is_on_hold = 1` except `cancelled` by admin/dispute resolution. `settlement_id` is write-once. A sub-order with `status='completed'` cannot be cancelled. |
| `ord_order_items` | P0 | Priced line, with full snapshot of what was bought | `order_id`, `sub_order_id`, `product_id`, `variant_id`, `seller_type`, `seller_id`, `product_title_snapshot`, `variant_title_snapshot`, `sku_snapshot`, `attributes_snapshot_json`, `unit_label_snapshot`, `quantity`, `unit_price_amount`, `unit_discount_amount`, `line_discount_amount`, `line_total_amount`, `tax_amount`, `points_base_amount`, `points_awarded`, `returned_quantity`, `status` (`active`,`cancelled`,`returned`,`partially_returned`), `promotion_id`, `price_snapshot_id` | → `ord_orders`, `ord_sub_orders`, → catalog, 1–1 `pri_price_snapshots` | `line_total_amount = (unit_price − unit_discount) × quantity − line_discount` (rounded once, `CHECK`). Snapshots are required: a later rename or re-price never changes an existing order. `returned_quantity ≤ quantity`. Items are never deleted; the whole order is immutable except `status` and `returned_quantity`. |
| `ord_order_status_history` | P0 | Append-only transition log for both order and sub-order | `order_id`, `sub_order_id`, `from_status`, `to_status`, `actor_type` (`system`,`user`,`admin`,`supplier`,`store`,`gateway`), `actor_id`, `reason_code`, `reason_text`, `metadata_json`, `request_id`, `occurred_at` | → `ord_orders`, `ord_sub_orders` | Append-only. Every status change must have exactly one row (enforced by a feature test using a transition spy). `from_status` must equal the previous `to_status` — a broken chain fails the nightly consistency check. |
| `ord_order_holds` | P1 | Explicit freeze on preparation/payout: complaint, dispute, fraud review, admin stop (appendix 4 clauses 15–16, appendix 11 clause 12) | `order_id`, `sub_order_id`, `reason_code` (`complaint`,`dispute`,`incomplete`,`fraud_review`,`admin`,`payment_review`), `source_id`, `status` (`active`,`released`,`expired`), `held_at`, `hold_until`, `released_at`, `released_by_id`, `note` | → `ord_sub_orders`, → `rtn_complaints`, `rtn_disputes` | `ord_sub_orders.is_on_hold` is a cache of "exists active hold". A hold never changes the lifecycle status; it blocks transitions and blocks settlement release. `hold_until` defaults to +7 days for complaint holds (appendix 4 clause 15). Releasing a hold records the actor and reason. AMB-ORD-002 (contract states the behaviour, not the mechanism). |
| `ord_order_notes` | P1 | Internal and buyer-visible notes with attachments (appendix 11 clause 18) | `order_id`, `sub_order_id`, `author_id`, `visibility` (`internal`,`buyer`,`seller`), `body`, `media_id`, `kind` (`text`,`voice`,`image`,`document`), `created_at` | → orders, → `shr_media` | A buyer-visible note is immutable after the sub-order ships (evidence for disputes). Voice notes are capped at 2 minutes (appendix 5 clause 41) by validation. |
| `ord_incomplete_reports` | P1 | Supplier's "incomplete order" flag that returns the order to the buyer instead of incurring delay damages (appendix 11 clauses 12–13) | `sub_order_id`, `reported_by_id`, `missing_fields_json`, `note`, `status` (`open`,`buyer_responded`,`resolved`,`escalated`,`auto_closed`), `buyer_response_text`, `resolved_at`, `resolved_by_id`, `abuse_flag` | → `ord_sub_orders` | Reporting requires the item to be unshipped; it moves the sub-order to `incomplete_reported` and pauses `promised_ship_at`. Repeated abusive use within the configured window writes a negative loyalty transaction (appendix 11 clause 13). Cannot be raised twice for the same missing field set without buyer response. |
| `ord_order_cancellations` | P2 | Structured cancellation record with refund intent | `order_id`, `sub_order_id`, `actor_type`, `actor_id`, `reason_code`, `reason_text`, `refund_method` (`wallet`,`iban`,`gateway`), `refund_amount`, `status` (`requested`,`refunded`,`failed`), `payment_refund_id` | → orders, → `pay_refunds` | One cancellation per sub-order; refund amount cannot exceed `paid_amount − refunded_amount` of the sub-order's parent. |

**Module invariants**

1. Money on the parent equals the sum of sub-orders, and sub-order money equals the sum of its
   items plus its shipping share. Both are `CHECK`-constrained where possible and asserted by
   a daily reconciliation job.
2. An order is created only from a server-side cart re-pricing; the client cannot submit
   amounts.
3. `ord_orders.status` is never written by a request handler — only by the Orders state
   machine service, which also writes history and side effects (see state machine doc).
4. A sub-order belongs to exactly one seller, and all its items share that seller. Mixed
   seller items in one sub-order are rejected at creation.

---

## 10. Invoices (`inv_`) — module owner: Invoices, area INV

| Table | P | Purpose | Key attributes | Relationships | Invariants / rules |
|---|---|---|---|---|---|
| `inv_invoices` | P0 | Legally meaningful invoice per sub-order, plus supplementary, return and manual invoices | `invoice_number` (unique forever), `fiscal_year` (Jalali, `SMALLINT`), `type` (`sale`,`supplementary`,`return`,`manual`,`proforma`), `status` (`draft`,`issued`,`partially_paid`,`paid`,`void`), `order_id`, `sub_order_id`, `parent_invoice_id`, `supplier_id`, `buyer_user_id`, `buyer_name_snapshot`, `buyer_national_id_snapshot`, `buyer_address_snapshot_json`, `subtotal_amount`, `discount_amount`, `shipping_amount`, `tax_amount`, `total_amount`, `paid_amount`, `balance_amount`, `currency`, `issued_at`, `due_at`, `settled_at`, `notes`, `pdf_media_id`, `issued_by_id`, `voided_at`, `void_reason`, `lock_version` | → `ord_orders`, `ord_sub_orders`, → sellers/buyers, → `shr_media`; 1–N items | Issued invoices are **immutable**: no line, amount or party column may change after `status` leaves `draft`; corrections are `supplementary` or `return` invoices referencing `parent_invoice_id` (appendix 11 clause 17). `type='supplementary'` must have a parent. `balance_amount = total_amount − paid_amount`. Voiding requires a reason and is audited; a void invoice keeps its number. |
| `inv_invoice_items` | P0 | Invoice line, either from an order item or free-form (manual invoice) | `invoice_id`, `order_item_id`, `description`, `quantity`, `unit_price_amount`, `discount_amount`, `line_total_amount`, `unit_label`, `is_supplementary`, `source` (`order_item`,`manual`,`adjustment`) | → invoices, → `ord_order_items` | `SUM(line_total_amount) = subtotal_amount`. `order_item_id` is required for `type='sale'` and null for manual lines. Supplementary lines must not duplicate an existing `order_item_id` on the same parent invoice. |
| `inv_invoice_sequences` | P0 | Gapless numbering per fiscal year and scope | `fiscal_year`, `scope` (`invoice`,`supplementary`,`return`,`manual`), `prefix`, `next_number`, `last_issued_at` | — | `UNIQUE (fiscal_year, scope)`. Numbers are allocated inside the invoice transaction with `SELECT ... FOR UPDATE`; gaps are not allowed (legal requirement). Never reseeded backwards. |
| `inv_invoice_deliveries` | P2 | Delivery log of the invoice PDF (email, SMS link, buyer panel) | `invoice_id`, `channel` (`email`,`sms`,`in_app`,`whatsapp`), `recipient`, `status`, `sent_at`, `error_code` | → invoices | Retries are idempotent per `(invoice_id, channel, recipient)`; the PDF link is signed and expires. AMB-INV-003 (delivery channels per invoice not specified). |

**Module invariants**

1. One `sale` invoice per sub-order (`UNIQUE (sub_order_id)` where `type='sale'`), issued when
   the order is paid — before supplier confirmation, because the supplier's "stop preparation"
   right starts after invoice issuance (appendix 4 clause 16).
2. An invoice's seller is the sub-order seller; an invoice never mixes sellers.
3. Deleting an invoice is impossible: `ON DELETE RESTRICT` from all financial tables and no
   soft delete. A wrong invoice is voided and replaced.
4. Invoice totals must equal the sub-order totals at issue time; a mismatch blocks issuance
   and raises an operational alert.

---

## 11. Payments (`pay_`) — module owner: Payments, area PAY

| Table | P | Purpose | Key attributes | Relationships | Invariants / rules |
|---|---|---|---|---|---|
| `pay_gateways` | P0 | Configuration of the 12 bank gateways, one driver each (appendix 1 clause 5, appendix 13 clause 7) | `code` (unique: `saman`,`behpardakht`,`zarinpal`,…), `name_fa`, `driver` (class key), `mode` (`sandbox`,`live`), `is_active`, `priority`, `supports_refund`, `supports_partial_refund`, `credentials_json` (encrypted), `terminal_id`, `merchant_id_encrypted`, `fee_percent`, `fee_fixed_amount`, `activated_by_id`, `activated_at` | 1–N `pay_payments`, 1–N `pay_payment_attempts` | Credentials are encrypted and never returned by any endpoint (masked as `••••1234`). Deactivating a gateway does not affect in-flight payments. `code` is permanent. |
| `pay_payments` | P0 | A payable intent for an order, wallet top-up, paid notification, tender deposit or manual/admin charge | `public_id`, `reference` (unique, idempotency), `user_id`, `order_id`, `invoice_id`, `purpose` (`order`,`wallet_topup`,`notification_purchase`,`tender_deposit`,`sms_credit`,`manual`), `method` (`online`,`wallet`,`cod`,`bank_transfer`,`manual_admin`), `gateway_id`, `amount`, `currency`, `status` (`initiated`,`pending`,`authorized`,`captured`,`failed`,`cancelled`,`expired`,`partially_refunded`,`refunded`), `captured_amount`, `refunded_amount`, `authority`, `gateway_reference`, `paid_at`, `expires_at`, `failure_code`, `description`, `created_by_id`, `lock_version` | → orders, invoices, users, gateways; 1–N attempts, 1–N refunds | `captured_amount ≤ amount`; `refunded_amount ≤ captured_amount`. `status='captured'` is terminal for the payment intent and triggers the ledger. Only one **active** (`initiated`/`pending`/`authorized`) payment per order at a time (unique generated column). A gateway callback can only move `pending` to `captured` or `failed`. |
| `pay_payment_attempts` | P0 | Every call to a gateway, including failures (appendix 13 clause 6) | `payment_id`, `attempt_no`, `gateway_id`, `status` (`started`,`redirected`,`callback_received`,`verified`,`failed`,`timeout`), `request_payload_json` (redacted), `response_payload_json` (redacted), `http_status`, `error_code`, `error_message` (sanitised), `started_at`, `finished_at`, `ip_address`, `user_agent` | → `pay_payments` | `UNIQUE (payment_id, attempt_no)`. Payloads are redacted before write (conventions §16). Provider error text is stored but never surfaced to the client verbatim. Attempts are append-only. |
| `pay_refunds` | P0 | Refund to wallet, IBAN or gateway (appendix 5 clause 15, appendix 11 clause 14) | `public_id`, `payment_id`, `order_id`, `sub_order_id`, `amount`, `reason_code`, `destination` (`wallet`,`iban`,`gateway`), `bank_account_id`, `status` (`requested`,`approved`,`processing`,`completed`,`failed`,`cancelled`), `gateway_refund_reference`, `idempotency_key` (unique), `requested_by_id`, `approved_by_id`, `processed_at`, `failure_reason` | → `pay_payments`, → `fin_bank_accounts` | Requires an approved actor when `destination='iban'` (money leaves the platform). Sum of refunds per payment ≤ `captured_amount`. Refund to wallet is immediate and writes `fin_wallet_transactions`; refund to IBAN waits for the bank batch. |
| `pay_webhook_events` | P0 | Raw gateway callbacks for reconciliation and replay | `gateway_id`, `event_id`, `event_type`, `signature_valid`, `payload_json` (redacted), `received_at`, `processed_at`, `processing_result` (`applied`,`duplicate`,`ignored`,`failed`), `payment_id` | → gateways | `UNIQUE (gateway_id, event_id)`; processing is idempotent and ordered by `received_at`. An invalid signature is stored with `signature_valid = 0` and never applied. |
| `pay_reconciliations` | P1 | Daily statement matching per gateway (appendix 13 clause 6) | `gateway_id`, `business_date`, `gateway_total_amount`, `internal_total_amount`, `difference_amount`, `matched_count`, `unmatched_count`, `status` (`balanced`,`mismatch`,`resolved`), `resolved_by_id`, `notes`, `statement_media_id` | → gateways | `difference = gateway_total − internal_total`; a non-zero difference blocks gateway payout approval until resolved. One row per gateway per business date. |

**Module invariants**

1. Money is captured once: `pay_payments.reference` and the ledger `reference` make double
   capture impossible even under webhook replay.
2. The payment module never writes order status directly; it emits `PaymentCaptured` /
   `PaymentFailed` / `RefundCompleted`, and Orders reacts.
3. A payment's `amount` is fixed at creation from the server-side order total; no endpoint
   accepts an amount from the client.
4. Two gateways in one transaction (appendix 1 clause 5) is **deferred** for v1
   (`RB-PAY-01`); the schema supports it because `pay_payments` is per intent and an order
   may have several captured payments, but no endpoint creates split payments yet.

---

## 12. Finance (`fin_`) — module owner: Finance, area FIN

| Table | P | Purpose | Key attributes | Relationships | Invariants / rules |
|---|---|---|---|---|---|
| `fin_accounts` | P0 | Chart of accounts, including one receivable/payable account per party | `code` (unique), `name_fa`, `type` (`asset`,`liability`,`revenue`,`expense`,`equity`,`clearing`), `owner_type` (`platform`,`user`,`supplier`,`store`,`marketer`,`gateway`), `owner_id`, `currency`, `is_system`, `is_active` | 1–N `fin_ledger_entries` | System accounts (`platform_revenue`, `gateway_clearing`, `buyer_receivable`, `supplier_payable`) are seeded and cannot be deleted. A per-party account is created lazily and is unique per `(owner_type, owner_id)`. |
| `fin_ledger_batches` | P1 | Journal batch that groups the entries of one economic event | `batch_no` (unique), `source_type`, `source_id`, `occurred_at`, `entry_count`, `total_debit_amount`, `total_credit_amount`, `is_balanced`, `created_by_id` | 1–N entries | `total_debit = total_credit` for every batch (asserted before commit); an unbalanced batch is a hard failure. Batches are append-only. |
| `fin_ledger_entries` | P0 | Append-only double-entry ledger; the single source of financial truth (canonical §5) | `entry_no` (monotonic), `batch_id`, `account_id`, `direction` (`debit`,`credit`), `amount` (signed integer Toman), `currency`, `reference` (unique, idempotency), `source_type` (`order`,`payment`,`refund`,`settlement`,`commission`,`wallet_topup`,`wallet_withdrawal`,`penalty`,`adjustment`,`reversal`,`delay_damage`), `source_id`, `reverses_entry_id`, `description`, `occurred_at`, `created_by_id`, `metadata_json` | → accounts, → batches, self-FK `reverses_entry_id` | Append-only: no `updated_at`, no `deleted_at`, `UPDATE`/`DELETE` revoked in production. `reference` is unique — one economic event posts once. Corrections are reversal + new entry, never an edit. Balances are **derived** by summing entries; no balance column exists here. |
| `fin_wallets` | P0 | Wallet header per user with cached balances (appendix 3-1 clause 4, appendix 12 clause 13) | `user_id` (unique), `currency`, `balance_amount`, `available_amount`, `locked_amount`, `status` (`active`,`frozen`,`closed`), `last_entry_at`, `version` | → `iam_users`; 1–N `fin_wallet_transactions` | `available + locked = balance`. The cached balance must equal the sum of the wallet's ledger entries — a nightly job asserts it and reports drift. A frozen wallet cannot be debited. |
| `fin_wallet_transactions` | P0 | Read-optimised wallet statement (projection of the ledger) | `wallet_id`, `direction` (`credit`,`debit`), `amount`, `balance_after`, `reason` (`reward`,`refund`,`settlement`,`purchase`,`penalty`,`deposit`,`withdrawal`,`correction`), `ledger_entry_id` (unique), `reference` (unique), `description`, `expires_at`, `created_by_id`, `occurred_at` | → wallets, → ledger entries | Projection only: the ledger is authoritative, this table exists for fast statements and for expiry semantics. A reward credit with `expires_at` must be spent or expired exactly once (guarded by a compensation entry on expiry). |
| `fin_settlements` | P0 | Period settlement to a supplier, store or marketer (appendix 3-1 clause 4, appendix 13 clauses 1–3) | `settlement_no` (unique), `payee_type` (`supplier`,`store`,`marketer`), `payee_id`, `period_start`, `period_end`, `gross_amount`, `commission_amount`, `penalty_amount`, `adjustment_amount`, `net_amount`, `status` (`draft`,`requested`,`approved`,`rejected`,`processing`,`paid`,`cancelled`), `requested_by_id`, `approved_by_id`, `paid_at`, `bank_account_id`, `bank_reference`, `export_id`, `notes`, `idempotency_key` (unique), `lock_version` | → payee, → `fin_bank_accounts`, → `adm_exports`; 1–N items | `net = gross − commission − penalty + adjustment`. Approval requires a different user from the requester (four-eyes). A settlement cannot include items already settled (`fin_settlement_items.source` unique per payee). `status='paid'` writes one ledger batch and one bank transfer row. |
| `fin_settlement_items` | P0 | What a settlement pays for | `settlement_id`, `source_type` (`invoice`,`sub_order`,`commission`,`penalty`,`delay_damage`,`adjustment`), `source_id`, `amount`, `description` | → settlements | Each `(source_type, source_id)` can appear in at most one non-cancelled settlement (unique index) — prevents double payment. |
| `fin_bank_accounts` | P0 | Sheba/IBAN of a payee for refunds and settlements (appendix 5 clause 15) | `owner_type` (`user`,`supplier`,`store`,`marketer`), `owner_id`, `iban_encrypted`, `iban_last4`, `bank_name`, `account_holder_name`, `status` (`pending_verification`,`verified`,`rejected`,`disabled`), `verified_at`, `verified_by_id`, `is_default` | → payee | IBAN validated by the Iranian check-digit algorithm before storing; `iban_last4` is the only searchable/displayable part. Only verified accounts can receive payouts. One default per owner. Changes are audited and require SMS confirmation. |
| `fin_bank_transfers` | P1 | Group payment file sent to the bank (appendix 13 clause 3) | `batch_no` (unique), `business_date`, `total_amount`, `item_count`, `status` (`draft`,`exported`,`submitted`,`confirmed`,`failed`), `export_id`, `bank_reference`, `created_by_id`, `confirmed_at` | 1–N settlements | `total_amount = SUM(settlements.net_amount)`; a transfer cannot include a settlement in another open transfer. |
| `fin_adjustments` | P1 | Manual debit/credit applied by an admin to a party's account (appendix 11 clause 16) | `payee_type`, `payee_id`, `direction` (`credit`,`debit`), `amount`, `reason_code`, `note`, `status` (`draft`,`approved`,`posted`,`cancelled`), `approved_by_id`, `ledger_batch_id`, `created_by_id` | → ledger batches | Requires a reason and a second approver; posting creates a balanced ledger batch; never editable after posting. |

**Module invariants**

1. **Every money movement is a balanced ledger batch.** There is no other way to change a
   balance; `fin_wallets.balance_amount` and `mer_stores`-level statements are projections.
2. Platform-collected funds: the buyer pays the platform, the platform owes the seller until
   settlement (canonical §5, `AMB-FIN-001`). Accounts reflect this: `gateway_clearing` (asset)
   → `supplier_payable` (liability) → `platform_revenue`.
3. `fin_ledger_entries.reference` uniqueness is the idempotency backbone; retries reuse the
   reference and fail cleanly instead of double posting.
4. Settlements, refunds and adjustments require a second human approver above the configured
   threshold.

---

## 13. Compensation (`cmp_`) — module owner: Compensation, area CMP

Marketer pay is a contract negotiation point: commission per sale, fixed fee per visit,
salary, or a mix, and several plans (A/B/C/D) with conditions (appendix 6 clauses 3–6).

| Table | P | Purpose | Key attributes | Relationships | Invariants / rules |
|---|---|---|---|---|---|
| `cmp_commission_plans` | P0 | Pay plan definition with its basis and conditions | `code` (unique: `A`,`B`,`C`,`D` + custom), `name_fa`, `audience` (`marketer`,`store`,`consumer`), `basis` (`percent_of_sales`,`fixed_per_visit`,`fixed_salary`,`hybrid`,`percent_of_onboarded_sales`), `percent_rate` (`DECIMAL(6,3)`), `fixed_visit_amount`, `fixed_salary_amount`, `min_points_threshold`, `requires_plan_id`, `effective_from`, `effective_to`, `is_active`, `created_by_id` | → self (`requires_plan_id`); assigned to `mer_marketer_profiles.plan_id` | Only one active plan per `(audience, code)` at a time. `requires_plan_id` expresses "plan A only for marketer type B" (appendix 6 clause 6): the condition is evaluated at accrual, not at assignment. A plan in use cannot be edited — clone and deactivate. |
| `cmp_commission_rules` | P1 | Bands that refine a plan by scope and amount | `plan_id`, `scope_type` (`all`,`category`,`brand`,`product`,`store_tier`), `scope_id`, `min_amount`, `max_amount`, `rate_percent`, `fixed_amount`, `priority` | → plans | Bands must not overlap for the same scope; the highest `priority` match wins; the applied rule id is snapshotted on the entry. |
| `cmp_commission_entries` | P0 | Accrued commission per beneficiary per source document | `plan_id`, `rule_id`, `beneficiary_type` (`marketer`,`store`), `beneficiary_id`, `source_type` (`sub_order`,`invoice`,`visit`,`attribution`), `source_id`, `base_amount`, `rate_percent`, `amount`, `status` (`accrued`,`approved`,`reversed`,`settled`,`cancelled`), `accrual_date`, `settlement_id`, `reference` (unique), `reverses_entry_id`, `created_at` | → plans, → settlements | One entry per `(source_type, source_id, beneficiary)` (`reference` unique). Accrual happens when the sub-order is `delivered` (not at payment) so cancellations do not create payable commission; reversal entries correct errors. `status='settled'` only via a settlement item. |
| `cmp_commission_statements` | P2 | Periodic statement handed to the marketer (appendix 3-2 clause 2) | `beneficiary_id`, `period_start`, `period_end`, `total_amount`, `entry_count`, `status` (`draft`,`issued`,`disputed`,`accepted`), `pdf_media_id`, `issued_at` | 1–N entries | A statement is a report, not money: it never moves balances; dispute opens a `rtn_complaint`. AMB-CMP-001 (statement format not specified). |
| `cmp_plan_assignments` | P1 | History of plan changes per beneficiary | `beneficiary_type`, `beneficiary_id`, `plan_id`, `assigned_by_id`, `effective_from`, `effective_to`, `reason` | → plans | The newest open assignment wins; history is required to explain a commission calculation after a plan change. |

**Module invariants**

1. Commission is computed from **settled line amounts**, never from display prices or the
   order total including shipping.
2. No commission accrues while `mer_marketer_profiles.commission_disabled_at` is set
   (appendix 4 clause 22), and none accrues for a sub-order that was cancelled or returned.
3. Every entry has an explainable `base_amount × rate = amount` (rounded half-up) and stores
   its `rule_id`; a commission line that cannot be explained is a defect.
4. Commission is a cost of the platform, posted to the ledger at settlement time, not at
   accrual — accrual is a subledger.

---

## 14. Loyalty (`loy_`) — module owner: Loyalty, area LOY

Points, tiers, stars, cash rewards and penalties. Rules are **versioned and replayable**
(canonical §5): `loy_rule_sets` with `effective_from`, plus a replay command used in tests.

| Table | P | Purpose | Key attributes | Relationships | Invariants / rules |
|---|---|---|---|---|---|
| `loy_rule_sets` | P0 | Versioned container of the point/reward rules in force | `code` (unique), `version` (`SMALLINT`), `name`, `status` (`draft`,`active`,`retired`), `effective_from`, `effective_to`, `scope` (`global`,`supplier`,`category`,`product`), `scope_id`, `checksum` (SHA-256 of rules), `created_by_id`, `approved_by_id`, `activated_at`, `notes` | 1–N `loy_rules` | Only one `active` set per scope at any instant; activating a set retires the previous one in the same transaction. A rule set is immutable once active — changes create a new version. `checksum` lets a replay detect drift. |
| `loy_rules` | P0 | A single earning, tier, penalty or bonus rule | `rule_set_id`, `code`, `type` (`earn_per_amount`,`earn_per_product`,`volume_multiplier`,`tier_discount`,`inactivity_penalty`,`invite_bonus`,`star_threshold`,`cash_bonus_threshold`,`invoice_count_bonus`,`violation_penalty`), `scope_type`, `scope_id`, `params_json`, `points_value`, `percent_value`, `threshold_amount`, `threshold_points`, `window_days`, `priority`, `is_active` | → rule sets | `params_json` shape is validated per `type`. A rule whose `scope_id` points at a deleted product is deactivated, never silently ignored. Volume multipliers are the contract's 0.75 / 0.5 / 0.25 / 0.2 percent coefficients (appendix 4 clause 3). |
| `loy_point_balances` | P0 | Current point balance and tier per user (projection) | `user_id` (unique), `points_balance`, `lifetime_earned`, `lifetime_spent`, `stars_count`, `tier_id`, `tier_updated_at`, `last_activity_at`, `version` | → users, → tiers | `points_balance = lifetime_earned − lifetime_spent` (invariant asserted nightly). `stars_count = floor(lifetime_points_at_tier / 100)` per appendix 4 clause 9 and is monotonic (stars are never removed). Negative balance is forbidden. |
| `loy_point_transactions` | P0 | Append-only point ledger with expiry support | `user_id`, `rule_id`, `rule_set_id`, `direction` (`earn`,`redeem`,`expire`,`adjust`,`penalty`), `points`, `balance_after`, `source_type` (`order`,`invoice`,`return`,`invite`,`admin`,`penalty`,`expiry`,`survey`,`review`), `source_id`, `reference` (unique), `description`, `occurred_at`, `expires_at`, `created_by_id` | → users, → rules | Append-only. `points` is always positive; `direction` carries the sign. `balance_after` must chain correctly. A penalty can drive the balance to zero but never below (floor at 0, remainder recorded in `description`). |
| `loy_tiers` | P0 | Point bands with their automatic invoice discount (appendix 4 clauses 20–21) | `code` (unique), `name_fa`, `min_points`, `max_points`, `discount_percent` (4.000 / 5.000 / …), `fixed_discount_amount`, `star_multiplier`, `sort_order`, `is_active` | 1–N balances | Bands must be contiguous and non-overlapping; the top band has `max_points = NULL`. The discount is applied to the **next** invoice and is visible on the user's panel as a differing percentage per level (appendix 5 clause 16). |
| `loy_rewards` | P0 | Redeemable catalogue item: cash to wallet, coupon, product, free shipping | `code` (unique), `name_fa`, `type` (`cash_wallet`,`discount_coupon`,`product`,`free_shipping`), `points_cost`, `cash_amount`, `coupon_template_id`, `product_id`, `stock_quantity`, `per_user_limit`, `valid_from`, `valid_to`, `status`, `image_media_id`, `terms` | → `prm_promotions`/`prm_coupons`, → products | `points_cost > 0`. `stock_quantity` NULL = unlimited. A reward with `valid_to < now()` cannot be redeemed. Cash rewards credit the wallet and are spendable only on a new purchase (appendix 4 clause 7). |
| `loy_reward_redemptions` | P0 | Redemption record and its fulfilment | `reward_id`, `user_id`, `points_spent`, `status` (`pending`,`fulfilled`,`cancelled`,`expired`), `wallet_transaction_id`, `coupon_id`, `order_id`, `reference` (unique), `fulfilled_at` | → rewards, → `fin_wallet_transactions`, → coupons | One redemption reference per attempt; points are debited in the same transaction as the redemption row. `per_user_limit` enforced by count. Cancelling a redemption refunds points as a compensating `adjust` transaction, never by deleting the row. |
| `loy_rule_evaluations` | P1 | Why a specific invoice earned what it earned (reproducibility) | `rule_set_id`, `rule_id`, `source_type`, `source_id`, `user_id`, `base_amount`, `multiplier`, `points_awarded`, `explanation_json`, `evaluated_at` | → rules, → point transactions | One row per rule that fired; the replay command regenerates these and compares to stored values. Any difference fails CI. AMB-LOY-001 (contract does not require the audit table; it is required to keep versioned rules explainable). |
| `loy_penalty_rules` | P2 | Inactivity and violation penalties as data (appendix 4 clauses 6, 17; appendix 5 clause 14) | `code`, `type` (`inactivity`,`violation`,`incomplete_abuse`), `points_per_period`, `period_days`, `threshold_amount`, `max_points_per_period`, `is_active` | → rule sets | A penalty run is idempotent per `(user_id, period_start)`; the period key is stored in `loy_point_transactions.reference`. |

**Module invariants**

1. Points are earned on **invoices**, not carts or payments, and every invoice is scored even
   if the buyer never uses the reward (appendix 4 clause 5).
2. The reward on invoice *n* is deducted from invoice *n+1* only if that invoice is at least
   four times the reward; otherwise half the reward is deducted (appendix 4 clause 4). This
   rule lives in one place (`RewardApplier`) and is covered by a table-driven test.
3. Tier discount and reward deduction are different mechanisms and may both apply, in the
   fixed order: loyalty tier discount → reward deduction → coupon.
4. Replaying a rule set over historical invoices must produce byte-identical point
   transactions; that is the acceptance test for any rule change.

---

## 15. Marketing (`mkt_`) — module owner: Marketing, area MKT

| Table | P | Purpose | Key attributes | Relationships | Invariants / rules |
|---|---|---|---|---|---|
| `mkt_referral_codes` | P0 | Marketer QR / link / code used to attribute an onboarded store or user (appendix 5 clause 1) | `owner_user_id`, `code` (unique), `channel` (`qr`,`link`,`code`), `qr_media_id`, `landing_path`, `campaign_id`, `is_active`, `expires_at`, `usage_count` | → users, → `mer_marketer_profiles`, → `prm_campaigns` | A marketer has at most one active code per channel. Codes are immutable (printed on cards). Deactivating a code does not invalidate already-confirmed attributions. |
| `mkt_referral_attributions` | P0 | The binding of a registered store/user to the referrer, with confirmation state | `referral_code_id`, `referrer_user_id`, `referred_user_id`, `store_id`, `channel`, `status` (`pending`,`confirmed`,`rejected`,`expired`), `first_touch`, `attributed_at`, `confirmed_at`, `rejection_reason`, `source_ip`, `user_agent` | → codes, → users, → `mer_stores` | `UNIQUE (store_id)` — a store is attributed to exactly one marketer, forever (re-attribution requires admin action recorded in `mer_stores` history). Attribution is `pending` until the store is approved, then `confirmed`. Self-referral is rejected. First-touch wins when several codes are scanned (appendix 5 clause 1). |
| `mkt_segments` | P2 | Saved audience definition used by notification jobs and reports | `name`, `definition_json`, `member_count`, `last_computed_at`, `created_by_id` | → `ntf_notification_jobs` | Segment definitions are read-only queries over users/orders; membership is computed, never stored per user. AMB-MKT-001 (segments inferred; contract asks for filtered audiences without naming a mechanism). |
| `mkt_lead_sources` | P2 | Cost/source tracking for offline campaigns (exhibitions, printed QR) | `name`, `channel`, `cost_amount`, `starts_at`, `ends_at`, `utm_json` | 1–N attributions | Cost is reported only; it never touches the ledger. |

**Module invariants**

1. Attribution affects **commission and reporting only**; it never changes prices or
   permissions.
2. A confirmed attribution is the only accepted proof that a marketer onboarded a store;
   commission for onboarding reads `mkt_referral_attributions`, not the store's `marketer_id`
   alone (they are kept in sync by the Merchants service).
3. Referral links carry no PII: the code maps to the referrer server-side.

---

## 16. Promotions (`prm_`) — module owner: Promotions, area PRM

| Table | P | Purpose | Key attributes | Relationships | Invariants / rules |
|---|---|---|---|---|---|
| `prm_promotions` | P0 | A discount rule: percentage, fixed amount, free shipping, bundle, first-invoice (appendix 4 clauses 10–12, appendix 5 clause 43) | `code` (unique), `name_fa`, `type` (`percent`,`fixed_amount`,`free_shipping`,`bundle`,`first_invoice`,`volume_cash`), `scope` (`order`,`product`,`category`,`brand`,`supplier`), `discount_percent`, `discount_amount`, `max_discount_amount`, `min_order_amount`, `applies_to_role`, `price_list_id`, `is_stackable`, `priority`, `effective_from`, `effective_to`, `usage_limit_total`, `usage_limit_per_user`, `status` (`draft`,`active`,`paused`,`expired`), `created_by_id`, `approved_by_id` | 1–N targets, 1–N coupons, → `pri_price_lists` | Exactly one of `discount_percent` / `discount_amount` is set. `effective_to > effective_from`. A promotion used by an issued invoice cannot be deleted. Non-stackable promotions: the highest-priority eligible one wins, and the choice is snapshotted on the order. |
| `prm_promotion_targets` | P0 | What a scoped promotion applies to | `promotion_id`, `target_type` (`product`,`variant`,`category`,`brand`,`supplier`), `target_id` | → promotions | Unique per `(promotion_id, target_type, target_id)`; `scope='order'` promotions have no targets. |
| `prm_campaigns` | P1 | Seasonal festival grouping products and promotions for the home page (appendix 11 clause 4) | `code` (unique), `name_fa`, `slug`, `banner_media_id`, `starts_at`, `ends_at`, `status` (`draft`,`scheduled`,`active`,`ended`), `priority`, `created_by_id` | N–M products via `prm_campaign_products`; 1–N promotions | A campaign is a presentation grouping; it grants no discount on its own. Overlapping active campaigns are allowed but ordered by `priority`. |
| `prm_campaign_products` | P1 | Product participation in a campaign with an optional campaign price | `campaign_id`, `product_id`, `variant_id`, `campaign_price_amount`, `discount_percent`, `sort_order` | → campaigns, → products | `UNIQUE (campaign_id, product_id, variant_id)`; at most one active campaign price per variant per campaign. |
| `prm_coupons` | P0 | A single coupon or "bon" voucher, issued by admin or generated after an invoice (appendix 5 clause 43, appendix 12 clause 4) | `code` (unique), `promotion_id`, `type` (`single_use`,`multi_use`,`bon`,`compensation`), `amount`, `discount_percent`, `min_order_amount`, `issued_to_user_id`, `batch_id`, `status` (`active`,`used`,`expired`,`revoked`), `valid_from`, `valid_to`, `max_uses`, `used_count`, `used_at`, `used_order_id` | → promotions, → users, → orders | `used_count ≤ max_uses`; `single_use` has `max_uses = 1`. A coupon bound to a user cannot be redeemed by another (checked server-side). Revoking keeps the row for audit. |
| `prm_coupon_batches` | P1 | Bulk generation run (e.g. 1 000 bons for a campaign) | `batch_no`, `promotion_id`, `quantity`, `prefix`, `status` (`generating`,`ready`,`exported`,`cancelled`), `created_by_id`, `export_id` | 1–N coupons | Codes are unique across all batches; a batch is the rollback unit for a mistaken generation (revoke, never delete). |
| `prm_coupon_redemptions` | P0 | Redemption record per order | `coupon_id`, `user_id`, `order_id`, `invoice_id`, `amount`, `redeemed_at` | → coupons, → orders, → invoices | `UNIQUE (coupon_id, order_id)`. A cancelled order releases the redemption and decrements `used_count` in the same transaction. |
| `prm_discount_grants` | P1 | User-specific standing discount (fixed Toman or percent) granted by admin, applied on the next invoice (appendix 4 clause 21) | `user_id`, `type` (`fixed_amount`,`percent`), `amount`, `percent`, `reason`, `granted_by_id`, `valid_from`, `valid_to`, `max_invoices`, `invoices_used`, `status` | → users | At most one active grant per user per reason; consuming a grant is recorded on the order's price snapshot. |

**Module invariants**

1. Promotion math is applied server-side in one place; the applied promotion, its priority and
   the resulting amount are snapshotted (`pri_price_snapshots`).
2. A coupon is validated and **reserved** at checkout start and consumed only when the order
   is paid; an abandoned payment releases it.
3. Free-shipping promotions zero `shipping_total_amount` and are recorded as a promotion
   discount, not by editing the shipping row.

---

## 17. Shipping (`shp_`) — module owner: Shipping, area SHP

| Table | P | Purpose | Key attributes | Relationships | Invariants / rules |
|---|---|---|---|---|---|
| `shp_carriers` | P0 | Carrier master data (post, tipax, courier, freight, in-person pickup) | `code` (unique), `name_fa`, `type` (`post`,`tipax`,`peyk`,`freight`,`pickup`), `tracking_url_template`, `supports_cod`, `is_active` | 1–N methods, 1–N shipments | `code` permanent. Deactivating a carrier blocks new shipments only. |
| `shp_shipping_methods` | P0 | Selectable delivery option with pricing model (appendix 5 clause 40, appendix 12 clause 12) | `code` (unique), `name_fa`, `carrier_id`, `scope` (`city`,`province`,`national`,`pickup`), `pricing_type` (`flat`,`per_kg`,`weight_band`,`free_threshold`), `base_amount`, `per_kg_amount`, `free_threshold_amount`, `estimated_days_min`, `estimated_days_max`, `is_active`, `sort_order` | → carriers, 1–N rates | `estimated_days_min ≤ estimated_days_max`. A `free_threshold` method's free amount applies to the goods subtotal, not the grand total. |
| `shp_shipping_rates` | P1 | Region-specific price overrides and free-shipping zones | `method_id`, `province_id`, `city_id`, `amount`, `free_threshold_amount`, `is_free`, `effective_from`, `effective_to` | → methods, → `geo_regions` | Most specific match wins (city → province → national); overlapping windows for the same scope are rejected. |
| `shp_shipments` | P0 | One physical dispatch for a sub-order | `shipment_no` (unique), `sub_order_id`, `carrier_id`, `method_id`, `status` (`pending`,`label_issued`,`picked_up`,`in_transit`,`out_for_delivery`,`delivered`,`failed`,`returned`), `tracking_code`, `waybill_no`, `driver_name`, `vehicle_plate`, `shipped_at`, `delivered_at`, `recipient_name`, `recipient_mobile`, `shipping_cost_amount`, `cod_amount`, `notes`, `created_by_id` | → `ord_sub_orders`, → carriers | `UNIQUE (sub_order_id)` for v1 (one shipment per sub-order; multi-parcel deferred `RB-SHP-01`). `status` may only move forward; `delivered` requires `delivered_at`. A shipment cannot be created before the sub-order is `ready_to_ship`. |
| `shp_shipment_events` | P1 | Carrier/manual tracking history | `shipment_id`, `status`, `description`, `location_text`, `occurred_at`, `source` (`carrier`,`manual`,`system`), `raw_json` | → shipments | Append-only; `raw_json` redacted of recipient data. Events may arrive out of order — the shipment status takes the highest-progress event. |
| `shp_delivery_delays` | P1 | Delay assessment and damage deduction (appendix 11 clause 15, appendix 12 clause 8) | `sub_order_id`, `shipment_id`, `promised_at`, `actual_at`, `delay_days`, `penalty_percent`, `penalty_amount`, `status` (`assessed`,`approved`,`applied`,`waived`,`rejected`), `supplier_id`, `buyer_user_id`, `wallet_transaction_id`, `assessed_by_id`, `note` | → sub-orders, → `fin_wallet_transactions` | `penalty_amount = round(delay_days × penalty_percent% × sub_order.total_amount)` using the admin-configured percent; applying it debits the seller's payable and credits the buyer's wallet in one ledger batch. Once applied, immutable (correction is a new adjustment). AMB-SHP-002 (formula and cap are not fixed by the contract). |
| `shp_pickup_requests` | P2 | Courier pickup scheduling for supplier warehouses | `warehouse_id`, `carrier_id`, `requested_date`, `status`, `reference_no`, `created_by_id` | → `stk_warehouses` | Cancelling a pickup after the carrier confirms is a support action, not a status flip. AMB-SHP-003. |

**Module invariants**

1. Shipping cost is resolved at checkout from `shp_shipping_rates`/`methods` and snapshotted on
   the sub-order; later rate changes never alter an existing order.
2. Each sub-order ships separately (its own seller), so a parent order may produce several
   shipments with independent tracking.
3. A `delivered` shipment starts the return window and the delay assessment clock.

---

## 18. Returns and complaints (`rtn_`) — module owner: Returns, area RTN

| Table | P | Purpose | Key attributes | Relationships | Invariants / rules |
|---|---|---|---|---|---|
| `rtn_returns` | P0 | Return/exchange request against a delivered sub-order (appendix 12 clause 14) | `return_no` (unique), `order_id`, `sub_order_id`, `user_id`, `type` (`return`,`exchange`,`refund_only`), `reason_code`, `description`, `status` (`requested`,`approved`,`rejected`,`in_transit`,`received`,`inspected`,`refunded`,`closed`,`cancelled`), `window_ends_at`, `approved_at`, `received_at`, `inspected_at`, `refund_amount`, `restock` (bool), `refund_payment_id`, `wallet_credit_amount`, `decided_by_id`, `decision_note`, `media_id` | → sub-orders, → `pay_refunds`, → `shr_media` | A return is only possible while `now() ≤ window_ends_at` (configured days after delivery, per-item opt-in by admin). Requested quantity per item ≤ delivered − already returned. Approval moves the sub-order to `return_in_progress`; completion moves it to `returned` and triggers refund + restock. |
| `rtn_return_items` | P0 | Item-level return detail | `return_id`, `order_item_id`, `quantity`, `reason_code`, `condition` (`unopened`,`opened`,`damaged`,`wrong_item`,`missing_parts`), `refund_unit_amount`, `restock_quantity` | → returns, → `ord_order_items` | `SUM(refund_unit_amount × quantity) = rtn_returns.refund_amount`. `restock_quantity ≤ quantity`; damaged items are not restocked. |
| `rtn_complaints` | P0 | Complaint/ticket against an order, invoice, seller or delivery; freezes settlement (appendix 4 clauses 15, 18) | `complaint_no` (unique), `subject_type` (`order`,`invoice`,`supplier`,`store`,`user`,`product`,`delivery`), `subject_id`, `complainant_user_id`, `against_user_id`, `category`, `description`, `severity` (`low`,`normal`,`high`,`critical`), `status` (`open`,`in_review`,`awaiting_response`,`resolved`,`rejected`,`escalated`), `settlement_hold`, `hold_until`, `sla_due_at`, `resolved_by_id`, `resolution_note`, `closed_at`, `media_id` | → orders/invoices/sellers, → `ord_order_holds` | `settlement_hold = 1` creates an `ord_order_holds` row of type `complaint`; the complaint cannot be closed before the hold is released. A complaint filed later than the configured window after delivery is rejected with a business error. |
| `rtn_complaint_events` | P1 | Timeline of a complaint | `complaint_id`, `actor_id`, `action` (`created`,`assigned`,`message`,`status_changed`,`hold_applied`,`hold_released`,`escalated`), `from_status`, `to_status`, `note`, `occurred_at` | → complaints | Append-only. Every status change has exactly one event. |
| `rtn_disputes` | P1 | Escalated complaint with money at stake and an arbitration decision | `complaint_id`, `order_id`, `sub_order_id`, `amount_in_dispute`, `status` (`open`,`mediation`,`resolved_buyer`,`resolved_seller`,`split`,`closed`), `decision_note`, `decided_by_id`, `decided_at`, `ledger_batch_id` | → complaints, → ledger batches | A dispute blocks settlement release until decided. Any money movement on decision is one balanced ledger batch; the decision is immutable. |
| `rtn_violation_reports` | P1 | "Warning" reports against a supplier/store/product, visible to partners when confirmed (appendix 4 clauses 18–19, appendix 5 clause 14) | `reporter_user_id`, `target_type` (`supplier`,`store`,`product`,`user`,`content`), `target_id`, `category`, `description`, `media_id`, `status` (`new`,`reviewed`,`confirmed`,`rejected`), `confirmed_by_id`, `penalty_points`, `visible_to_partners`, `occurred_at`, `published_at` | → users, sellers, products | Only `confirmed` reports with `visible_to_partners = 1` are shown to other partners (appendix 4 clause 19). Confirmation may write a negative `loy_point_transactions` row and a `mer_supplier_profiles.violation_score` increment. |
| `rtn_return_reasons` | P2 | Admin-managed reason catalogue with whether restock is allowed | `code`, `name_fa`, `applies_to` (`return`,`complaint`,`violation`), `requires_photo`, `allows_restock`, `is_active`, `sort_order` | — | Reason codes on transactions reference `code`, not the id, so reports stay stable. |

**Module invariants**

1. Money returns only through `pay_refunds` or a wallet credit; no module edits an invoice to
   reflect a return — a `return` invoice is issued instead.
2. A return resets stock only for `allows_restock` reasons and never for damaged goods.
3. A complaint can freeze money (settlement hold) but never silently cancels an order; the
   cancellation is an explicit admin/supplier action with its own history row.

---

## 19. Tenders and auctions (`tnd_`) — module owner: Tenders, area TND

| Table | P | Purpose | Key attributes | Relationships | Invariants / rules |
|---|---|---|---|---|---|
| `tnd_tenders` | P1 | Tender, reverse auction or auction published by a store, consumer or admin (appendix 5 clause 6, appendix 11 clause 10) | `tender_no` (unique), `kind` (`tender`,`auction`,`reverse_auction`), `title`, `description`, `category_id`, `buyer_user_id`, `buyer_type` (`store`,`consumer`,`admin`), `province_id`, `city_id`, `quantity`, `unit_id`, `budget_amount`, `bid_start_amount`, `bid_step_amount`, `deposit_amount`, `opens_at`, `closes_at`, `status` (`draft`,`published`,`bidding`,`evaluating`,`awarded`,`cancelled`,`failed`), `visibility` (`public`,`invited`,`suppliers_only`), `awarded_bid_id`, `terms_media_id`, `created_by_id` | → buyers, → `cat_categories`, → `cat_units`; 1–N bids | `closes_at > opens_at`. Bids are rejected outside `[opens_at, closes_at)`. Awarding requires exactly one accepted bid and is irreversible; cancelling requires a reason shown to bidders. A tender with bids cannot be edited except `description` clarifications, which are versioned. |
| `tnd_tender_bids` | P1 | A supplier's offer | `tender_id`, `supplier_id`, `bidder_user_id`, `amount`, `delivery_days`, `validity_days`, `notes`, `attachment_media_id`, `status` (`submitted`,`withdrawn`,`accepted`,`rejected`,`expired`), `submitted_at` | → tenders, → suppliers | `UNIQUE (tender_id, supplier_id)` — one bid per supplier, replaceable until close (replacement keeps history in `tnd_bid_revisions`). `amount > 0`; for auctions a new bid must beat the current best by `bid_step_amount`. |
| `tnd_bid_revisions` | P2 | History of bid changes | `bid_id`, `amount`, `delivery_days`, `note`, `revised_at` | → bids | Append-only; the latest revision is mirrored on the bid row for query speed. |
| `tnd_tender_invitations` | P2 | Invitations sent to specific suppliers | `tender_id`, `supplier_id`, `invited_by_id`, `invited_at`, `responded_at`, `status` (`invited`,`viewed`,`declined`,`accepted`) | → tenders | `UNIQUE (tender_id, supplier_id)`; declining does not prevent a bid if `visibility='public'`. |
| `tnd_tender_questions` | P2 | Clarification questions and answers ("استعلام") | `tender_id`, `asker_user_id`, `question`, `answer`, `answered_by_id`, `answered_at`, `is_public` | → tenders | Answers are published to all bidders when `is_public`; they never change the deadline. |

**Module invariants**

1. Awarding a tender creates no order automatically in v1; the buyer converts it into an order
   through the normal checkout with a `tender`-sourced price snapshot (`RB-TND-01` documents
   the gap and the manual step).
2. Deposits taken for participation are `pay_payments` with `purpose='tender_deposit'` and are
   refunded by `pay_refunds` when the tender fails or the bid is not awarded.

---

## 20. Classifieds (`cls_`) — module owner: Classifieds, area CLS

| Table | P | Purpose | Key attributes | Relationships | Invariants / rules |
|---|---|---|---|---|---|
| `cls_categories` | P1 | Admin-defined classifieds taxonomy; posting is impossible until a category exists (appendix 5 clause 4) | `parent_id`, `name_fa`, `slug` (unique), `sort_order`, `requires_approval`, `max_images`, `is_active`, `created_by_id` | self-FK; 1–N listings | Only admins create/edit categories. A category with live listings cannot be deleted. |
| `cls_listings` | P1 | A classified ad with seller profile and location | `listing_no` (unique), `category_id`, `user_id`, `title`, `slug`, `description`, `price_amount`, `price_type` (`fixed`,`negotiable`,`free`,`on_request`), `province_id`, `city_id`, `contact_mobile`, `status` (`draft`,`pending_review`,`published`,`rejected`,`expired`,`sold`,`archived`), `published_at`, `expires_at`, `views_count`, `is_featured`, `rejection_reason`, `approved_by_id` | → categories, → users; 1–N media, 1–N views | Publishing requires an active category and, when `requires_approval`, an admin approval. `expires_at` defaults to +30 days. Contact mobile is PII and is masked on public list views. |
| `cls_listing_media` | P1 | Photos of the ad | `listing_id`, `media_id`, `sort_order` | → listings, → `shr_media` | `max_images` from the category is enforced in validation; the first image is the cover. |
| `cls_listing_views` | P2 | View counter detail and duplicate suppression | `listing_id`, `viewer_user_id`, `ip_hash`, `viewed_at` | → listings | One view per `(listing_id, ip_hash, day)` counted; raw rows kept 90 days, `views_count` is the durable counter. |
| `cls_listing_reports` | P2 | User reports of a listing | `listing_id`, `reporter_user_id`, `reason`, `status` (`new`,`reviewed`,`removed`,`rejected`), `reviewed_by_id` | → listings | Reporting hides nothing automatically; admin action changes the listing status and is audited. |

**Module invariants**

1. Classifieds are outside the order/ledger flow entirely: no stock, no invoice, no
   commission. Any ad that becomes a sale does so as a direct contact (contract appendix 5
   clause 4).
2. A user's contact details appear only on the detail page, never in list responses.

---

## 21. Patogh local services (`ptg_`) — module owner: Patogh, area PTG

| Table | P | Purpose | Key attributes | Relationships | Invariants / rules |
|---|---|---|---|---|---|
| `ptg_service_categories` | P1 | Trades and skills taxonomy (installer, repairer, …) | `parent_id`, `name_fa`, `slug` (unique), `icon_media_id`, `sort_order`, `is_active` | self-FK; 1–N services | Admin-managed; a category with active services cannot be deleted. |
| `ptg_services` | P1 | A provider's service offering with area and pricing model (appendix 5 clauses 5, 13) | `provider_user_id`, `category_id`, `title`, `slug`, `description`, `province_id`, `city_id`, `service_area_text`, `base_price_amount`, `price_unit` (`per_m2`,`per_hour`,`per_item`,`quote`), `is_price_negotiable`, `availability_json`, `rating_avg`, `reviews_count`, `status` (`draft`,`pending_review`,`active`,`suspended`,`rejected`), `approved_by_id`, `approved_at` | → users, → categories; 1–N media, 1–N requests | Listing requires admin approval (appendix 5 clause 7: profile fields only with approval). `price_unit='quote'` means `base_price_amount` is null and every job is quoted. Rating is derived from `rvw_reviews` with subject `service`. |
| `ptg_service_media` | P2 | Portfolio, before/after and video | `service_id`, `media_id`, `kind` (`portfolio`,`before_after`,`video`), `sort_order` | → services, → `shr_media` | Before/after pairs must reference the same `portfolio_group` key to render together. |
| `ptg_service_requests` | P1 | A customer's job request / ticket with location (appendix 5 clause 13) | `request_no` (unique), `service_id`, `requester_user_id`, `description`, `preferred_at`, `address_line`, `city_id`, `latitude`, `longitude`, `status` (`new`,`quoted`,`accepted`,`scheduled`,`completed`,`cancelled`,`disputed`), `quoted_amount`, `quote_note`, `quoted_at`, `accepted_quote_id`, `conversation_id`, `completed_at` | → services, → users, → `msg_conversations` | A request is visible to the selected provider only. Accepting a quote is final and opens a conversation. Completion requires both sides or admin confirmation after the configured window. |
| `ptg_service_quotes` | P2 | Provider quotes for a request | `request_id`, `provider_user_id`, `amount`, `note`, `valid_until`, `status` (`submitted`,`accepted`,`rejected`,`expired`) | → requests | At most one `accepted` quote per request; accepting one rejects the others in the same transaction. |

**Module invariants**

1. Patogh is a lead-generation and quoting flow; payment, if any, happens off-platform in v1
   (`RB-PTG-01`), so no ledger entry is created from a quote.
2. Location is mandatory for a request because the service is performed at the customer's
   address.

---

## 22. Samples (`smp_`) — module owner: Samples, area SMP

| Table | P | Purpose | Key attributes | Relationships | Invariants / rules |
|---|---|---|---|---|---|
| `smp_sample_requests` | P1 | Request for a sample, swatch, catalogue or work sample (appendix 5 clause 38, appendix 11 clause 9) | `request_no` (unique), `requester_user_id`, `requester_role`, `supplier_id`, `product_id`, `category_id`, `quantity`, `dimensions_text`, `description`, `delivery_address_id`, `status` (`submitted`,`accepted`,`rejected`,`shipped`,`delivered`,`cancelled`), `supplier_note`, `shipment_id`, `created_at`, `responded_at` | → users, → suppliers, → products, → `shp_shipments` | Exactly one of `product_id` / `category_id`. The supplier must respond within the configured SLA or the request auto-expires. Samples are not orders: no invoice, no payment, no points. |
| `smp_sample_request_items` | P2 | Multi-item sample requests | `request_id`, `product_id`, `variant_id`, `quantity` | → requests | Quantity capped by the admin-configured maximum per request. |

**Module invariants**

1. A sample request never reserves stock until the supplier accepts; acceptance may create a
   zero-amount shipment.
2. Requests are rate-limited per user to prevent sample farming (appendix 9 clause 3 spam
   control).

---

## 23. Messaging (`msg_`) — module owner: Messaging, area MSG

| Table | P | Purpose | Key attributes | Relationships | Invariants / rules |
|---|---|---|---|---|---|
| `msg_conversations` | P0 | A chat thread between two or more allowed participants, optionally attached to an order (appendix 5 clause 28) | `public_id`, `kind` (`direct`,`group`,`order`,`support`), `subject`, `order_id`, `created_by_id`, `status` (`open`,`archived`,`closed`), `last_message_at`, `last_message_preview`, `message_count`, `is_locked`, `participant_key` (unique for direct threads) | → users, → orders; 1–N participants, 1–N messages | A direct conversation is unique per unordered user pair (`participant_key = sha1(min:max)`). Messages are immutable except for soft delete by the author. `is_locked` blocks new messages but keeps history readable. |
| `msg_participants` | P0 | Membership and read state | `conversation_id`, `user_id`, `role` (`member`,`owner`,`agent`), `joined_at`, `left_at`, `last_read_message_id`, `last_read_at`, `muted_until` | → conversations, → users | `UNIQUE (conversation_id, user_id)`. Read state drives unread badges; `last_read_message_id` must belong to the conversation. |
| `msg_messages` | P0 | Message body and attachment reference | `conversation_id`, `sender_id`, `type` (`text`,`image`,`file`,`voice`,`system`), `body`, `reply_to_id`, `media_id`, `duration_seconds`, `client_message_id`, `is_deleted`, `edited_at` | → conversations, → users, → `shr_media`; 1–N attachments | `UNIQUE (conversation_id, client_message_id)` gives idempotent send on retry. A sender may only delete their own message, and only within the configured window. System messages have `sender_id = NULL`. |
| `msg_attachments` | P1 | Additional files per message | `message_id`, `media_id`, `kind`, `size_bytes`, `duration_seconds` | → messages | Max 5 attachments per message; voice capped at 2 minutes (appendix 5 clause 41). |
| `msg_tickets` | P0 | Support ticket metadata layered on a conversation (appendix 3-1 clause 7, appendix 7 clause 8) | `conversation_id` (unique), `ticket_no` (unique), `requester_id`, `assignee_id`, `department` (`support`,`finance`,`sales`,`technical`), `priority` (`low`,`normal`,`high`,`urgent`), `status` (`open`,`pending`,`answered`,`resolved`,`closed`), `sla_due_at`, `first_response_at`, `resolved_at`, `satisfaction_score`, `source` (`web`,`admin`,`api`), `order_id` | → conversations, → users, → orders | A ticket is always backed by a conversation (single message store). Closing requires a resolution note; reopening resets `sla_due_at` and is logged in `msg_ticket_events`. |
| `msg_ticket_events` | P1 | Assignment/status timeline | `ticket_id`, `actor_id`, `action` (`created`,`assigned`,`status_changed`,`escalated`,`reopened`,`rated`), `from_status`, `to_status`, `note`, `occurred_at` | → tickets | Append-only. |
| `msg_blocks` | P1 | User-level block list, enforcing "allowed users only" chat (appendix 3-2 clause 5) | `blocker_user_id`, `blocked_user_id`, `reason`, `created_at` | → users | `UNIQUE (blocker_user_id, blocked_user_id)`; a block prevents new direct conversations and suppresses notifications, but does not delete history. |

**Module invariants**

1. One conversation per user pair (direct) and per order (order threads are created by the
   Orders service on first message).
2. Attachments live in `shr_media` with `visibility='private'`; download links are signed and
   authorised per participant.
3. Chat history is permanent (appendix 5 clause 28: "حافظه ماندگار") and is the evidence trail
   for disputes; deletion is soft and audited.

---

## 24. Notifications (`ntf_`) — module owner: Notifications, area NTF

| Table | P | Purpose | Key attributes | Relationships | Invariants / rules |
|---|---|---|---|---|---|
| `ntf_templates` | P0 | Versioned message templates per channel and locale | `key` (unique with channel+locale), `channel` (`sms`,`email`,`whatsapp`,`push`,`in_app`), `locale`, `subject`, `body`, `variables_json`, `provider_template_id`, `is_active` | 1–N notifications/deliveries | Every variable used in `body` must exist in `variables_json` (validated at save). Persian copy lives here, not in code. |
| `ntf_notifications` | P0 | One notification addressed to one user (in-app record + delivery intent) | `public_id`, `user_id`, `channel`, `template_key`, `title`, `body`, `action_url`, `order_id`, `sub_order_id`, `payload_json`, `status` (`queued`,`sent`,`delivered`,`read`,`failed`,`suppressed`), `priority`, `dedupe_key` (unique), `read_at`, `sent_at`, `expires_at` | → users, → orders | `dedupe_key` (e.g. `order:123:shipped:user:9`) makes sends idempotent across retries and duplicate events. `expires_at` hides stale in-app items. |
| `ntf_notification_jobs` | P0 | Bulk/custom campaign: audience, content, schedule, price (appendix 5 clauses 9, 12; appendix 11 clauses 6–7) | `job_no` (unique), `created_by_id`, `audience_type` (`all`,`role`,`users`,`segment`,`filters`), `audience_filters_json`, `recipient_count`, `channel`, `title`, `body`, `media_id`, `action_url`, `scheduled_at`, `status` (`draft`,`pending_payment`,`pending_approval`,`queued`,`sending`,`sent`,`partially_failed`,`cancelled`), `price_amount`, `payment_id`, `approved_by_id`, `approved_at`, `sent_at`, `is_paid` | → users, → `pay_payments`, → `mkt_segments` | A paid job requires `payment_id` with `status='captured'` before `queued` (appendix 5 clause 12). A supplier job is capped by the admin-configured recipient limit. `recipient_count` is frozen at approval so the invoice and the send agree. |
| `ntf_deliveries` | P0 | Per-recipient, per-channel delivery attempt | `notification_id`, `notification_job_id`, `user_id`, `channel`, `provider`, `provider_message_id`, `status` (`queued`,`sent`,`delivered`,`failed`,`bounced`,`suppressed`), `error_code`, `cost_amount`, `sent_at`, `delivered_at`, `read_at` | → notifications, → jobs | Retries at most 3 times with backoff; a hard bounce suppresses the channel for that user. `cost_amount` feeds the SMS credit ledger. |
| `ntf_preferences` | P1 | Per-user channel/category opt-in and quiet hours | `user_id`, `channel`, `category` (`order`,`payment`,`promotion`,`social`,`security`), `is_enabled`, `quiet_hours_json` | → users | `security` category cannot be disabled. Transactional order messages are on by default; marketing requires `usr_consents` marketing consent. |
| `ntf_credit_ledger` | P1 | Prepaid SMS/notification credits for suppliers (appendix 11 clause 7) | `owner_user_id`, `delta`, `reason` (`purchase`,`usage`,`refund`,`adjustment`,`expiry`), `balance_after`, `payment_id`, `reference` (unique), `created_at` | → users, → `pay_payments` | Append-only; balance never negative; each send debits one credit and records the delivery id. AMB-NTF-001 (credit model inferred; contract only says "fee or free per admin setting"). |
| `ntf_provider_configs` | P2 | Per-channel provider credentials and sender ids | `channel`, `provider`, `sender_id`, `credentials_json` (encrypted), `is_active`, `priority`, `rate_limit_per_minute` | — | Credentials encrypted; only one active provider per channel; failover to the next priority on timeout. |

**Module invariants**

1. Notifications are **effects**, never a source of truth: a failed send never blocks an order
   transition (it is retried and surfaced in admin).
2. Every transactional message is deduplicated by `dedupe_key`, so retries and replayed events
   produce one message.
3. Marketing messages respect consent and quiet hours; security messages bypass both.

---

## 25. Cms (`cms_`) — module owner: Cms, area CMS

| Table | P | Purpose | Key attributes | Relationships | Invariants / rules |
|---|---|---|---|---|---|
| `cms_posts` | P1 | Blog articles with SEO metadata (appendix 2 clause 10) | `slug` (unique), `title`, `excerpt`, `body`, `category_id`, `author_user_id`, `cover_media_id`, `status` (`draft`,`scheduled`,`published`,`archived`), `published_at`, `seo_title`, `seo_description`, `canonical_url`, `reading_minutes`, `views_count` | → categories, → users; N–M tags | A published post requires `seo_title` and `seo_description` (appendix 9 clause 1). Slug is permanent after publish. Unpublishing keeps the URL returning 410, not 404. |
| `cms_post_categories` | P2 | Blog taxonomy | `parent_id`, `name_fa`, `slug` (unique), `sort_order`, `is_active` | self-FK | Max depth 2. |
| `cms_tags` | P2 | Free tags for posts | `name_fa`, `slug` (unique) | N–M posts | Tags are merged, not deleted, when duplicated. |
| `cms_post_tags` | P2 | Post–tag link | `post_id`, `tag_id` | → posts, → tags | `UNIQUE (post_id, tag_id)`. |
| `cms_pages` | P1 | Static pages: about, contact, terms, FAQ-adjacent legal pages (appendix 2 clauses 9, 11, 12) | `slug` (unique), `title`, `body`, `template`, `status` (`draft`,`published`,`archived`), `published_at`, `seo_title`, `seo_description`, `is_system`, `show_in_footer`, `show_in_menu`, `edited_by_id` | → users (editor) | `is_system = 1` pages (terms, privacy) cannot be deleted or unpublished while referenced by `usr_consents`. |
| `cms_banners` | P1 | Home/category/supplier banners and supplier slideshows with a request-approval flow (appendix 2 clause 12, appendix 11 clauses 5, 8) | `placement` (`home_hero`,`home_mid`,`home_side`,`category_top`,`supplier_slideshow`,`app_banner`), `title`, `image_media_id`, `mobile_media_id`, `target_url`, `supplier_id`, `starts_at`, `ends_at`, `sort_order`, `status` (`draft`,`pending_approval`,`approved`,`active`,`expired`,`rejected`), `requested_by_id`, `approved_by_id`, `impressions_count`, `clicks_count` | → `shr_media`, → suppliers | A supplier-requested banner is `pending_approval` until an admin approves (appendix 11 clause 5). Never more than the placement's configured slot count in `active` state. |
| `cms_menus` | P2 | Named navigation menus | `key` (unique), `name`, `location` (`header`,`footer`,`mobile`,`account`), `is_active` | 1–N items | One menu per location. |
| `cms_menu_items` | P2 | Menu entries with nesting | `menu_id`, `parent_id`, `label`, `url`, `target` (`_self`,`_blank`), `sort_order`, `is_active`, `permission_key` | → menus, self-FK | `permission_key` hides items for unauthorised roles — presentation only, never authorisation. |
| `cms_faqs` | P2 | Frequently asked questions before/after purchase (appendix 2 clause 9) | `category`, `question`, `answer`, `sort_order`, `is_active` | — | Published order follows `sort_order`; answers support a limited markdown subset. |
| `cms_surveys` | P1 | Survey definition (appendix 5 clause 10) | `title`, `description`, `type` (`general`,`product`,`order`,`satisfaction`), `status` (`draft`,`active`,`closed`), `starts_at`, `ends_at`, `is_anonymous`, `max_responses`, `response_count`, `created_by_id` | 1–N questions, 1–N responses | A closed survey accepts no responses. `AMB-CMS-001`: surveys live in CMS because the contract names no home module; they could move to a dedicated module without schema change. |
| `cms_survey_questions` | P1 | Question with type and options | `survey_id`, `question_text`, `type` (`single_choice`,`multi_choice`,`scale`,`nps`,`text`), `options_json`, `min_value`, `max_value`, `is_required`, `sort_order` | → surveys | `options_json` required for choice types; `min_value`/`max_value` required for scale. |
| `cms_survey_responses` | P1 | One submission | `survey_id`, `user_id`, `order_id`, `product_id`, `source` (`web`,`email`,`sms_link`,`app`), `submitted_at`, `ip_hash` | → surveys, → users | One response per `(survey_id, user_id)` unless `is_anonymous`. Anonymous responses store no user id and no IP. |
| `cms_survey_answers` | P1 | Answer per question | `response_id`, `question_id`, `option_value`, `numeric_value`, `text_value` | → responses, → questions | Exactly one value column populated, matching the question type (`CHECK`). |
| `cms_contact_messages` | P2 | Contact-form submissions (appendix 2 clause 11) | `name`, `mobile`, `email`, `subject`, `body`, `status` (`new`,`in_progress`,`answered`,`closed`,`spam`), `handled_by_id`, `created_at` | → users | Rate-limited and captcha-protected; auto-closed after 30 days without reply. |

**Module invariants**

1. All published content is versioned by `updated_at` + audit log; there is no draft/publish
   table duplication.
2. Content changes never alter prices, orders or invoices; CMS is presentation only.
3. Every public page has unique `seo_title`/`seo_description` and a canonical URL.

---

## 26. Reviews (`rvw_`) — module owner: Reviews, area RVW

| Table | P | Purpose | Key attributes | Relationships | Invariants / rules |
|---|---|---|---|---|---|
| `rvw_reviews` | P0 | Verified review of a product, supplier, store, service or delivery, with 5–10 scored parameters (appendix 4 clause 17) | `reviewer_user_id`, `subject_type` (`product`,`supplier`,`store`,`service`,`delivery`,`order`), `subject_id`, `order_item_id`, `invoice_id`, `rating` (`TINYINT` 1–5), `title`, `body`, `status` (`pending`,`published`,`rejected`,`hidden`), `is_verified_purchase`, `helpful_count`, `reported_count`, `published_at`, `moderated_by_id`, `rejection_reason` | → users, → products/sellers/services, → `ord_order_items` | Only buyers of the item may review it, and only within the review window after delivery (appendix 12 clause 9: one week). `UNIQUE (reviewer_user_id, subject_type, subject_id, order_item_id)`. `is_verified_purchase` is computed, never client-set. |
| `rvw_review_criteria` | P1 | The scored parameters (quality, packaging, delivery speed, accuracy, …) | `code` (unique), `name_fa`, `applies_to` (`product`,`supplier`,`delivery`,`service`), `min_score`, `max_score`, `weight`, `sort_order`, `is_active` | 1–N scores | Between 5 and 10 active criteria per `applies_to` — the contract requires 5–10 parameters (appendix 4 clause 17); a config test enforces the count. |
| `rvw_review_scores` | P1 | Score per criterion per review | `review_id`, `criterion_id`, `score` | → reviews, → criteria | `UNIQUE (review_id, criterion_id)`; `score` within the criterion's range (`CHECK`). |
| `rvw_review_media` | P1 | Photos/videos attached to a review | `review_id`, `media_id`, `sort_order` | → reviews, → `shr_media` | Max 5 items; media is public after the review is published, private before. |
| `rvw_review_replies` | P2 | Seller reply to a review | `review_id`, `user_id`, `body`, `status` (`pending`,`published`,`hidden`), `published_at` | → reviews | One reply per seller per review; a reply cannot change the rating. |
| `rvw_review_reports` | P2 | Abuse reports on a review | `review_id`, `reporter_user_id`, `reason`, `status` (`new`,`reviewed`,`removed`,`rejected`), `reviewed_by_id` | → reviews | Three confirmed reports auto-hide a review pending moderation. |

**Module invariants**

1. `rating_avg` and `reviews_count` on products/suppliers are **projections** updated by a
   listener; the truth is the set of published reviews.
2. A review cannot be edited after 24 hours; it can be deleted by its author (soft), which
   removes it from aggregates.
3. Review text passes profanity/PII screening before publication; a rejected review keeps its
   reason for the author.

---

## 27. FieldOps (`geo_`) — module owner: FieldOps, area GEO

| Table | P | Purpose | Key attributes | Relationships | Invariants / rules |
|---|---|---|---|---|---|
| `geo_regions` | P0 | Province/city/district master data used by every address form | `parent_id`, `level` (`country`,`province`,`city`,`district`), `name_fa`, `name_en`, `code` (unique per level), `latitude`, `longitude`, `is_active` | self-FK | Seeded from the official division list; renaming is allowed, deleting is not (addresses reference it). |
| `geo_visits` | P0 | A marketer's visit: lead, registration or follow-up, with check-in/out location (appendix 6 clauses 1–2, appendix 14 clause 1) | `marketer_id`, `store_id`, `lead_name`, `lead_mobile`, `visit_type` (`prospecting`,`registration`,`follow_up`,`reactivation`), `status` (`planned`,`in_progress`,`completed`,`cancelled`,`no_show`), `checkin_at`, `checkin_latitude`, `checkin_longitude`, `checkin_accuracy_m`, `checkout_at`, `checkout_latitude`, `checkout_longitude`, `duration_minutes`, `outcome` (`registered`,`interested`,`rejected`,`closed`), `notes`, `created_by_id` | → marketer, → stores; 1–N media | Check-in requires coordinates within the configured radius of the store, when the store exists. A visit cannot be completed without a check-out or an explicit `no_show`. `lead_mobile` is PII and visible only to the owning marketer and admin. |
| `geo_visit_media` | P1 | Storefront, owner, business-card and shelf photos captured during the visit (appendix 6 clause 2) | `visit_id`, `media_id`, `kind` (`storefront`,`owner`,`business_card`,`shelf`,`other`), `captured_at` | → visits, → `shr_media` | Photos are private until the store is approved; `kind='owner'` is PII. |
| `geo_tracking_pings` | P1 | Live location breadcrumbs for tracking and performance analysis (appendix 14 clause 2) | `marketer_id`, `latitude`, `longitude`, `accuracy_m`, `speed_mps`, `recorded_at`, `battery_percent`, `source` (`app`,`pwa`,`manual`), `batch_id` | → marketer | Partitioned monthly, 90-day retention. Recorded only while the agent is on shift and has consented; raw pings are never exposed through the API, only aggregated tracks. |
| `geo_territories` | P2 | Assignment of regions to marketers to avoid overlap | `marketer_id`, `region_id`, `assigned_by_id`, `assigned_at`, `revoked_at` | → marketers, → regions | `UNIQUE (region_id) WHERE revoked_at IS NULL` — one owner per region at a time. |
| `geo_route_plans` | P2 | Planned visit route for a day | `marketer_id`, `plan_date`, `status` (`draft`,`active`,`completed`), `total_distance_m`, `estimated_minutes`, `created_by_id` | 1–N `geo_route_stops` | One plan per marketer per day. |
| `geo_route_stops` | P2 | Ordered stops with target store and visit link | `plan_id`, `sequence`, `store_id`, `planned_at`, `visit_id`, `status` (`pending`,`visited`,`skipped`) | → plans, → visits | `UNIQUE (plan_id, sequence)`; completing a stop links the visit. |

**Module invariants**

1. Location data is collected only with `usr_consents` consent for `location_tracking`, and the
   consent can be withdrawn at any time (which stops pings, not history).
2. Visit and tracking data are visible to the owning marketer and admins only (appendix 5
   clause 29: "دسترسی محدود").
3. Region master data is referenced by FKs from addresses, stores, suppliers, tenders and
   classifieds — it is never duplicated as free text.

---

## 28. Admin (`adm_`) — module owner: Admin, area ADM

| Table | P | Purpose | Key attributes | Relationships | Invariants / rules |
|---|---|---|---|---|---|
| `adm_audit_logs` | P0 | Immutable record of sensitive actions (appendix 1 clause 10, canonical §5) | `actor_id`, `actor_role`, `action` (`login`,`logout`,`price_change`,`role_change`,`permission_change`,`settlement_approved`,`refund_approved`,`identity_change`,`settings_change`,`impersonation`,`data_export`,`invoice_void`), `auditable_type`, `auditable_id`, `before_json`, `after_json`, `ip_address`, `user_agent`, `request_id`, `occurred_at` | → users | Append-only, no soft delete, 5-year retention. `before_json`/`after_json` are PII-redacted. Writing an audit row is part of the command's transaction, not a queued side effect — if the audit write fails, the action fails. |
| `adm_settings` | P0 | Typed runtime configuration that admins change without a deploy (appendix 7 clause 9) | `group`, `key` (unique), `value_json`, `value_type` (`string`,`int`,`bool`,`json`,`money`,`percent`), `is_secret`, `description`, `updated_by_id`, `updated_at` | → users | Read through a typed settings service with a cache; changing a setting writes an audit row. `is_secret = 1` values are encrypted and never returned in full. Settings never hold credentials for gateways (those live in `pay_gateways`). |
| `adm_exports` | P1 | Background export jobs (Excel for banks, reports) (appendix 7 clause 10, appendix 13 clause 3) | `user_id`, `type`, `filters_json`, `status` (`queued`,`processing`,`ready`,`failed`,`expired`), `file_media_id`, `row_count`, `error_message`, `expires_at` | → users, → `shr_media` | Export files are generated on the private disk and expire after 7 days. Row count is capped by config; exceeding it fails with a business error suggesting filters. |
| `adm_impersonations` | P1 | Admin "view as user" sessions for support | `admin_id`, `target_user_id`, `reason`, `started_at`, `ended_at`, `request_id` | → users | Cannot impersonate another admin. Impersonated requests are read-only by policy, and every impersonation writes an audit row on start and end. |
| `adm_feature_flags` | P2 | Gradual rollout switches for deferred features (mobile, split payment) | `key` (unique), `is_enabled`, `rollout_json`, `description`, `updated_by_id` | — | Flags gate features only; they never replace authorisation. AMB-ADM-001 (flags inferred; needed to keep deferred scope dark). |
| `adm_report_subscriptions` | P2 | Scheduled emailed reports | `user_id`, `report_key`, `frequency` (`daily`,`weekly`,`monthly`), `filters_json`, `channel`, `is_active`, `last_sent_at` | → users | One subscription per `(user_id, report_key)`; unsubscribing keeps history. |

**Module invariants**

1. Admin actions are always attributable: `actor_id` is required for every admin write, and
   admin endpoints never operate without an audited actor.
2. `adm_settings` is the **only** place for admin-tunable values referenced by business rules
   (point coefficients, delay penalty percent, return window, inactivity period). Rules that
   need history live in their own versioned tables (`loy_rule_sets`, `cmp_commission_plans`)
   and read their baseline from settings only at creation.

---

## 29. Shared kernel (`shr_`) — owner: Shared, area CTR

No business rules live here (canonical §3). These tables are infrastructure that every module
references.

| Table | P | Purpose | Key attributes | Relationships | Invariants / rules |
|---|---|---|---|---|---|
| `shr_media` | P0 | Central file registry for every upload: product images, identity documents, chat attachments, invoice PDFs | `uuid` (unique), `disk` (`public`,`private`), `path`, `original_name`, `mime_type`, `extension`, `size_bytes`, `checksum_sha256`, `width`, `height`, `duration_seconds`, `visibility` (`public`,`private`), `owner_user_id`, `uploaded_by_id`, `virus_scanned_at` | referenced by every module's media table/column | A row is created only after the file is written and scanned. `path` is immutable; replacing an image creates a new row. Private files are served only through signed URLs. `checksum_sha256` deduplicates identical uploads. |
| `shr_idempotency_keys` | P0 | Replay protection for HTTP commands and jobs | `scope`, `key`, `request_hash`, `response_code`, `response_body_json`, `locked_at`, `completed_at`, `expires_at` | — | `UNIQUE (scope, key)`; same key + different request hash → `409`. TTL by scope (conventions §13). |
| `shr_outbox_events` | P0 | Transactional outbox for domain events, so side effects survive crashes | `event_id` (unique UUID), `event_name`, `aggregate_type`, `aggregate_id`, `payload_json`, `occurred_at`, `available_at`, `processed_at`, `attempts`, `last_error` | — | Written in the same transaction as the state change; a relay publishes and marks `processed_at`. Consumers must be idempotent (they receive `event_id`). Ordering is per aggregate, not global. |
| `shr_sequences` | P0 | Human-readable number allocation for orders, returns, tenders, listings | `name` (unique), `prefix`, `next_value`, `reset_period` (`never`,`yearly`,`monthly`), `last_reset_at` | — | Allocation uses `SELECT ... FOR UPDATE` inside the creating transaction. Invoice numbering is **not** here — it is legally gapless and lives in `inv_invoice_sequences`. |
| `shr_failed_jobs_log` | P2 | Long-term record of failed queue jobs after the framework's retries | `job_class`, `queue`, `payload_json`, `exception_class`, `attempts`, `failed_at`, `resolved_at`, `resolved_by_id` | — | Framework `failed_jobs` is operational; this table is the durable, queryable history. |

**Module invariants**

1. `shr_media` stores no business meaning: no module may branch on `disk`/`path` to infer
   purpose. Purpose is expressed by the referencing module's `kind` column.
2. Every event that has a cross-module side effect is written to `shr_outbox_events` in the
   same transaction; direct cross-module method calls from a command handler are forbidden.

---

## 30. Cross-cutting invariants

These hold across modules and are the acceptance criteria for the schema as a whole.

| # | Invariant | Enforced by |
|---|---|---|
| I-01 | `ord_orders.grand_total_amount = items_total − discount_total + shipping + tax` | DB `CHECK` + feature test |
| I-02 | `SUM(ord_sub_orders.total_amount) = ord_orders.grand_total_amount` | Domain service + nightly reconciliation |
| I-03 | `SUM(ord_order_items.line_total_amount) + shipping share = ord_sub_orders.total_amount` | Domain service + `CHECK` where possible |
| I-04 | `SUM(inv_invoice_items.line_total_amount) = inv_invoices.subtotal_amount` | DB `CHECK` on insert + test |
| I-05 | Every `fin_ledger_batches` row has `total_debit = total_credit` | Asserted before commit, verified nightly |
| I-06 | `fin_wallets.balance_amount = SUM(ledger entries for the wallet account)` | Nightly job, alert on drift |
| I-07 | `stk_stock_items.available_quantity = on_hand − reserved`, never negative | Generated column + `CHECK` |
| I-08 | `SUM(held reservations) = reserved_quantity` per stock item | Reconciliation test |
| I-09 | One `sale` invoice per sub-order; one settlement item per source document | Unique indexes |
| I-10 | A store is attributed to exactly one marketer | `UNIQUE (mkt_referral_attributions.store_id)` |
| I-11 | `loy_point_balances.points_balance = lifetime_earned − lifetime_spent ≥ 0` | Projection job + `CHECK` |
| I-12 | Replaying a rule set reproduces identical point transactions | CI replay test |
| I-13 | Status histories form an unbroken chain per order and sub-order | Nightly consistency query |
| I-14 | No money-bearing row is ever hard-deleted | `RESTRICT` FKs + revoked `DELETE` grants |
| I-15 | Every sensitive admin action has an audit row in the same transaction | `Auditable` trait + test |

---

## 31. Entities inferred from the contract (AMB register)

These tables are not named by the contract; they are the minimum structure needed to satisfy a
contract clause. Each carries an ambiguity record for the owner to confirm.

| Entity | Why it exists | Contract clause | AMB |
|---|---|---|---|
| `iam_sessions_security` | Device/token management beside 2FA | appendix 1 clause 7 (2FA only) | AMB-IAM-003 |
| `iam_invitations` | Marketer/colleague invitation lifecycle before a user exists | appendix 5 clause 2; appendix 4 clause 13 | AMB-IAM-004 |
| `mer_store_identifiers` | Duplicate prevention by national id / postal code / mobile without scanning PII | appendix 5 clause 3 | AMB-MER-002 |
| `mer_store_status_history` | Justify auto-inactivation and reactivation | appendix 4 clauses 23–24 | AMB-MER-003 |
| `mer_marketer_documents` | Agent contracting paperwork | appendix 6 (onboarding) | AMB-MER-004 |
| `mer_supplier_branches` | Nationwide agencies with location | appendix 11 clause 19 | AMB-MER-005 |
| `cat_product_import_batches` | Bulk catalogue load | appendix 9 clause 4 | AMB-CAT-004 |
| `cat_search_synonyms` | Persian search quality | appendix 2 clause 3 (search) | AMB-CAT-005 |
| `pri_price_change_requests` | Supplier-initiated price changes with approval | appendix 11 clause 1; appendix 7 clause 2 | AMB-PRI-002 |
| `pri_price_snapshots` | Explain an order line after rules change | canonical §5 (snapshot) | AMB-PRI-003 |
| `stk_stock_alerts` | Low/out-of-stock awareness for suppliers | appendix 11 clause 1 | AMB-STK-001 |
| `stk_stock_counts` / `stk_stock_count_lines` | Physical inventory correction | appendix 11 clause 1 (inventory management) | AMB-STK-002 |
| `crt_cart_events` | Abandoned-cart reminders | appendix 2 clause 6 (no reminder requirement) | AMB-CRT-001 |
| `ord_order_holds` | Mechanism for "stop preparation / stop payment" | appendix 4 clauses 15–16 | AMB-ORD-002 |
| `ord_order_cancellations` | Structured cancellation + refund intent | appendix 5 clause 15 | AMB-ORD-003 |
| `inv_invoice_deliveries` | Proof of invoice delivery | appendix 7 clause 2 (invoices) | AMB-INV-003 |
| `pay_reconciliations` | Gateway mismatch tracking | appendix 13 clause 6 | AMB-PAY-002 |
| `fin_ledger_batches` | Balanced journal per event | canonical §5 (append-only ledger) | AMB-FIN-002 |
| `fin_bank_transfers` | Group payment file to the bank | appendix 13 clause 3 | AMB-FIN-003 |
| `fin_adjustments` | Admin manual charge/payment on a supplier account | appendix 11 clause 16 | AMB-FIN-004 |
| `cmp_commission_statements` | Marketer statement document | appendix 3-2 clause 2 | AMB-CMP-001 |
| `cmp_plan_assignments` | Explain commission after a plan change | appendix 6 clauses 5–6 | AMB-CMP-002 |
| `loy_rule_evaluations` | Reproducibility of versioned rules | canonical §5 (replay) | AMB-LOY-001 |
| `loy_penalty_rules` | Inactivity/violation penalties as data | appendix 4 clauses 6, 17 | AMB-LOY-002 |
| `mkt_segments` | Filtered audiences for notifications | appendix 5 clause 9; appendix 11 clause 6 | AMB-MKT-001 |
| `prm_discount_grants` | Standing per-user discount | appendix 4 clause 21 | AMB-PRM-002 |
| `shp_pickup_requests` | Courier pickup scheduling | appendix 11 clause 15 | AMB-SHP-003 |
| `rtn_return_reasons` | Reason catalogue with restock policy | appendix 12 clause 14 | AMB-RTN-001 |
| `tnd_bid_revisions` | Auction bid history | appendix 11 clause 10 | AMB-TND-002 |
| `cls_listing_views` | Classifieds view analytics | appendix 5 clause 4 | AMB-CLS-001 |
| `ptg_service_quotes` | Local-service quoting | appendix 5 clause 13 | AMB-PTG-002 |
| `msg_blocks` | "Authorised users only" chat | appendix 3-2 clause 5 | AMB-MSG-001 |
| `ntf_credit_ledger` | Paid SMS/notification credits | appendix 11 clause 7 | AMB-NTF-001 |
| `cms_surveys` + questions/responses/answers | Site surveys | appendix 5 clause 10 | AMB-CMS-001 |
| `geo_route_plans` / `geo_route_stops` | Daily route planning | appendix 14 clause 2 (analysis) | AMB-GEO-002 |
| `adm_feature_flags` | Keep deferred scope dark | canonical §5 (deferred mobile) | AMB-ADM-001 |

Entities that are **named by the contract** and therefore carry no AMB: `iam_users`,
`iam_roles`, `iam_user_roles`, `mer_stores`, `mer_supplier_profiles`, `mer_marketer_profiles`,
`mkt_referral_attributions` (QR attribution), `cat_products`, `cat_product_variants`,
`cat_attributes`, `cat_attribute_values`, `cat_categories`, `cat_brands`, `pri_price_lists`,
`pri_prices`, `stk_stock_items`, `stk_stock_reservations`, `crt_carts`, `crt_cart_items`,
`ord_orders`, `ord_sub_orders`, `ord_order_items`, `ord_order_status_history`,
`inv_invoices`, `inv_invoice_items`, `pay_payments`, `pay_payment_attempts`,
`fin_ledger_entries`, `fin_wallets`, `fin_settlements`, `cmp_commission_plans`,
`cmp_commission_entries`, `loy_rule_sets`, `loy_rules`, `loy_point_transactions`,
`loy_rewards`, `prm_coupons`, `prm_promotions`, `shp_shipments`, `rtn_returns`,
`rtn_complaints`, `rtn_disputes`, `tnd_tenders`, `tnd_tender_bids`, `cls_listings`,
`ptg_services`, `smp_sample_requests`, `msg_conversations`, `msg_messages`, `msg_tickets`,
`ntf_notifications`, `ntf_notification_jobs`, `cms_posts`, `cms_pages`, `rvw_reviews`,
`adm_audit_logs`, `adm_settings`.

---

## 32. Decisions that need owner confirmation

1. **Store vs supplier as two tables** (`mer_stores` / `mer_supplier_profiles`) instead of one
   merchant table with a type column. Cost: duplicated address/approval columns. Benefit:
   panels, approvals and price lists differ and FKs stay honest. (AMB-MER-001)
2. **Consumer price is a price list, not a markup at read time.** The contract allows both a
   manual per-product consumer price and a global percentage (appendix 12 clause 1). Storing
   the resolved consumer price as its own list row makes invoices reproducible but requires a
   propagation job when the percentage changes. (AMB-PRI-001)
3. **Platform holds the money** (canonical `AMB-FIN-001`): supplier payable accrues at
   delivery, settlement is periodic and approved. If the owner wants direct buyer→supplier
   payment, `pay_payments` and `fin_settlements` change shape.
4. **Commission accrues at `delivered`, not at `paid`.** Protects the platform from paying
   commission on cancelled orders, but delays the agent's visible earnings. (AMB-CMP-003)
5. **Hard uniqueness on national id and mobile for stores, soft for postal code.** Two
   businesses in one mall share a postal code; the contract asks for duplicate *search*, not a
   unique constraint. (AMB-MER-002)

