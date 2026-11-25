# Endpoint Inventory — Dekolife Platform

**Purpose:** The complete list of HTTP endpoints by surface and module, with method, path,
required role, purpose, request/response summary, the requirement it satisfies and its
delivery priority.
**Audience:** Backend engineers, frontend/PWA engineers, QA, the client's technical reviewer.
**Status:** Baseline (v1). This inventory is the source for the generated OpenAPI document;
every row must exist in the spec and vice versa (contract test, API guidelines §17).
**Source:** `docs/canonical-context.md` §2–3 (Sanctum, module boundaries), §5 (orders, prices,
ledger, points); contract appendices 1–14 as cited per row.

---

## 1. How to read this inventory

| Column | Meaning |
|---|---|
| **Auth** | `public` (anonymous allowed), `token` (any authenticated user), `store`, `supplier`, `marketer`, `admin`, `webhook` (signature-verified, no token) |
| **Request → Response** | Compact summary: key inputs on the left, the shape returned on the right. Money is integer Toman; timestamps ISO-8601 UTC |
| **REQ** | Requirement id, `REQ-<AREA>-<NNN>`; §10 maps each to its contract source |
| **P0** | `●` = required for the first sellable release (browse → cart → order → invoice → payment → settlement); `○` = contract-required but after the core flow |

Global rules that apply to every row and are not repeated:

- All paths are under `/api/v1`. Collections are wrapped in `data` + `meta` + `links`; single
  resources are unwrapped (API guidelines §14).
- Every mutating endpoint returns the affected resource, validates through a FormRequest, and
  rejects unknown body keys.
- `POST` endpoints that move money or create an order require `Idempotency-Key` (guidelines §6).
- Scoped resources are resolved through policies: another user's resource is `404`, not `403`,
  except on admin surfaces.
- Errors always use the `{error:{code,message,request_id,details}}` envelope
  ([`error-model.md`](./error-model.md)).
- Rate-limit classes are per API guidelines §8 and are not repeated per row.

Legend for the **Request → Response** column: `→` separates request from response; `?` marks
optional parameters; `[...]` marks a list.

---

## 2. Authentication and account (public surface)

| Method | Path | Auth | Purpose | Request → Response | REQ | P0 |
|---|---|---|---|---|---|---|
| POST | `/auth/register` | public | Register a consumer with mobile + password, captcha and terms consent | `{mobile, password, first_name?, last_name?, consent_version, captcha_token}` → `202 {user, otp_sent:true, challenge_id}` | REQ-IAM-001 | ● |
| POST | `/auth/otp/request` | public | Send or resend an SMS OTP (registration, login, mobile change) | `{mobile, purpose}` → `202 {challenge_id, expires_in, retry_after}` | REQ-IAM-001 | ● |
| POST | `/auth/otp/verify` | public | Verify OTP and receive a token (passwordless login / registration completion) | `{challenge_id, code, device_name?}` → `201 {token, token_type:"Bearer", expires_at, user, roles[]}` | REQ-IAM-001 | ● |
| POST | `/auth/login` | public | Password login for any role | `{mobile, password, device_name?}` → `200 {token, expires_at, user, roles[]}` | REQ-IAM-002 | ● |
| POST | `/auth/logout` | token | Revoke the current token | — → `204` | REQ-IAM-002 | ● |
| POST | `/auth/logout-all` | token | Revoke every token of the user | `{password}` → `204` | REQ-IAM-006 | ○ |
| POST | `/auth/password/forgot` | public | Start password reset by SMS | `{mobile, captcha_token}` → `202 {challenge_id, expires_in}` | REQ-IAM-003 | ● |
| POST | `/auth/password/reset` | public | Complete the reset | `{challenge_id, code, password}` → `204` | REQ-IAM-003 | ● |
| POST | `/auth/password/change` | token | Change password while authenticated | `{current_password, password}` → `204` | REQ-IAM-003 | ● |
| GET | `/auth/me` | token | Current actor, roles, active surface, permissions | — → `{user, roles[], primary_role, permissions[], must_change_password}` | REQ-IAM-002 | ● |
| GET | `/auth/sessions` | token | List active devices/tokens | — → `[{id, device_name, ip_masked, last_used_at, is_current}]` | REQ-IAM-006 | ○ |
| DELETE | `/auth/sessions/{session}` | token | Revoke one device | — → `204` | REQ-IAM-006 | ○ |
| GET | `/meta/version` | public | API/app version and minimum client version | — → `{api, app, min_client}` | REQ-CTR-004 | ● |
| GET | `/meta/regions` | public | Province/city tree for address forms | `?level=province\|city&parent_id=` → `[{id, parent_id, name_fa, level}]` | REQ-USR-003 | ● |
| POST | `/me/role-requests` | token | Request a secondary level (appendix 7 clause 6); admin approves | `{role_key, reason, documents[]?}` → `202 {request}` | REQ-IAM-004 | ○ |

---

## 3. Public storefront

### 3.1 Catalog

| Method | Path | Auth | Purpose | Request → Response | REQ | P0 |
|---|---|---|---|---|---|---|
| GET | `/catalog/categories` | public | Category tree for navigation and mega-menu | `?depth=2&parent_id=` → `[{id, slug, name_fa, children[]}]` | REQ-CAT-001 | ● |
| GET | `/catalog/categories/{slug}` | public | Category detail with filters and breadcrumb | — → `{category, breadcrumb[], filterable_attributes[], brands[]}` | REQ-CAT-001 | ● |
| GET | `/catalog/products` | public | Product listing with filters, sort and cursor paging | `?filter[category]=&filter[brand][in]=&filter[price][between]=&filter[attribute][thickness]=&filter[in_stock]=1&sort=-sales_count&limit=24&cursor=` → `{data:[product_card], meta, links, facets}` | REQ-CAT-001 | ● |
| GET | `/catalog/products/{slug}` | token/public | Product detail: gallery, specs, variants, price for the caller's level, reviews summary, related | `?include=brand,category,media,variants` → `{product, variants[], attributes[], price:{amount, compare_at_amount, price_list_code}, availability, supplier, related[]}` | REQ-CAT-002 | ● |
| GET | `/catalog/products/{slug}/availability` | public | Stock and lead time per variant (aggregated, no warehouse detail) | — → `[{variant_id, available_quantity, next_restock_at}]` | REQ-STK-002 | ● |
| GET | `/catalog/products/{slug}/related` | public | Related / alternative / accessory products | `?type=related` → `[product_card]` | REQ-CAT-002 | ○ |
| GET | `/catalog/products/compare` | public | Compare up to 5 products on shared attributes | `?ids=1,2,3` (max 5) → `{products[], attributes[], differences[]}` | REQ-CAT-003 | ○ |
| GET | `/catalog/search/suggest` | public | Type-ahead suggestions | `?q=پار` → `{terms[], products[product_card]}` (cache 60 s) | REQ-CAT-005 | ● |
| GET | `/catalog/brands` | public | Brand list with logos | `?filter[has_products]=1` → `[brand_card]` | REQ-CAT-001 | ● |
| GET | `/catalog/attributes` | public | Filterable attributes for a category | `?category_id=` → `[{code, name_fa, data_type, values[]}]` | REQ-CAT-001 | ● |
| GET | `/pricing/products/{product}` | token | Resolved price for the caller's level (consumer vs store tier) and quantity breaks | `?variant_id=&quantity=` → `{price_list_code, unit_amount, compare_at_amount, volume_tiers[], final_amount}` | REQ-PRI-001 | ● |
| GET | `/pricing/price-lists` | public | Public price-list catalogue (labels only, no amounts) | — → `[{code, name_fa, audience, tier}]` | REQ-PRI-001 | ○ |

### 3.2 Stores, suppliers and services

| Method | Path | Auth | Purpose | Request → Response | REQ | P0 |
|---|---|---|---|---|---|---|
| GET | `/stores` | public | Public store directory with location and category filters | `?filter[city]=&filter[category]=&filter[status]=active&q=` → `[store_card]` | REQ-MER-001 | ● |
| GET | `/stores/{slug}` | public | Store public profile, branches, services, approved contact fields | — → `{store, branches[], media[], rating, public_contacts}` | REQ-MER-006 | ● |
| GET | `/suppliers` | public | Supplier directory | `?filter[province]=&filter[brand]=` → `[supplier_card]` | REQ-MER-007 | ● |
| GET | `/suppliers/{slug}` | public | Supplier profile: description, gallery, video, branches with map | — → `{supplier, branches[], media[], rating, catalog_preview[]}` | REQ-MER-007 | ○ |
| GET | `/suppliers/{slug}/branches` | public | Nationwide agencies for referral (appendix 11 clause 19) | `?filter[city]=` → `[{title, city, address_line, latitude, longitude, phone}]` | REQ-MER-007 | ○ |
| GET | `/suppliers/{slug}/violations` | public | Confirmed, partner-visible violation reports (appendix 4 clause 19) | — → `[{category, occurred_at, description}]` | REQ-RTN-005 | ○ |
| GET | `/patogh/services` | public | Local services directory (installers, repairers) | `?filter[category]=&filter[city]=&q=` → `[service_card]` | REQ-PTG-001 | ○ |
| GET | `/patogh/services/{slug}` | public | Service detail with portfolio, pricing model, availability | — → `{service, provider, media[], rating, availability}` | REQ-PTG-001 | ○ |
| GET | `/patogh/categories` | public | Service taxonomy | — → `[{id, slug, name_fa, children[]}]` | REQ-PTG-001 | ○ |
| GET | `/classifieds` | public | Classified ads list (دیوار) | `?filter[category]=&filter[city]=&filter[price][between]=&q=` → `[listing_card]` | REQ-CLS-003 | ○ |
| GET | `/classifieds/{slug}` | public | Listing detail; contact details revealed per policy | — → `{listing, media[], seller{display_name, member_since}, contact_masked}` | REQ-CLS-003 | ○ |
| GET | `/classifieds/categories` | public | Admin-defined classifieds taxonomy | — → `[{id, slug, name_fa, requires_approval}]` | REQ-CLS-001 | ○ |
| GET | `/tenders` | public | Public tenders and auctions open for bids | `?filter[status]=bidding&filter[category]=` → `[tender_card]` | REQ-TND-001 | ○ |
| GET | `/tenders/{tender_no}` | token | Tender detail with terms and (for bidders) bid summary | — → `{tender, terms_media, my_bid?, bid_count}` | REQ-TND-001 | ○ |

### 3.3 Content, shipping and support

| Method | Path | Auth | Purpose | Request → Response | REQ | P0 |
|---|---|---|---|---|---|---|
| GET | `/cms/pages/{slug}` | public | Static page (about, terms, FAQ-legal, contact) | — → `{slug, title, body, seo, updated_at}` | REQ-CMS-002 | ● |
| GET | `/cms/posts` | public | Blog index | `?filter[category]=&q=&limit=` → `[post_card]` | REQ-CMS-001 | ○ |
| GET | `/cms/posts/{slug}` | public | Article with SEO metadata and related posts | — → `{post, author, related[]}` | REQ-CMS-001 | ○ |
| GET | `/cms/faqs` | public | FAQ list by category | `?category=` → `[{question, answer}]` | REQ-CMS-002 | ○ |
| GET | `/cms/banners` | public | Active banners for a placement (home hero, mid, supplier slideshow) | `?placement=home_hero` → `[{title, image_url, mobile_image_url, target_url, sort_order}]` | REQ-CMS-003 | ● |
| GET | `/cms/menus/{key}` | public | Navigation menu for a location | — → `{key, items[{label, url, children[]}]}` | REQ-CMS-004 | ○ |
| GET | `/cms/surveys/{id}` | public | Active survey with questions | — → `{survey, questions[]}` | REQ-CMS-005 | ○ |
| POST | `/cms/surveys/{id}/responses` | token/public | Submit a survey response | `{answers:[{question_id, option_value?, numeric_value?, text_value?}]}` → `201 {response_id}` | REQ-CMS-005 | ○ |
| POST | `/contact` | public | Contact form (captcha, rate-limited) | `{name, mobile, email?, subject, body, captcha_token}` → `202 {ticket_no}` | REQ-CMS-006 | ● |
| GET | `/shipping/methods` | public | Available shipping methods and prices for a destination | `?city_id=&weight_grams=&subtotal_amount=` → `[{id, name_fa, carrier, price_amount, is_free, estimated_days}]` | REQ-SHP-001 | ● |
| GET | `/orders/track/{tracking_code}` | public | Order tracking by public tracking code (no PII beyond status) | — → `{order_number_masked, status, sub_orders:[{status, shipment{carrier, tracking_code, events[]}}]}` | REQ-ORD-002 | ● |
| GET | `/reviews` | public | Published reviews for a product/supplier (paged) | `?filter[subject_type]=product&filter[subject_id]=&sort=-published_at` → `[review_card]` | REQ-RVW-001 | ● |
| GET | `/reviews/criteria` | public | Active review criteria for a subject type (5–10 parameters) | `?applies_to=product` → `[{code, name_fa, min_score, max_score}]` | REQ-RVW-001 | ○ |

---

## 4. Customer surface (`/me`)

### 4.1 Profile, addresses and preferences

| Method | Path | Auth | Purpose | Request → Response | REQ | P0 |
|---|---|---|---|---|---|---|
| GET | `/me/profile` | token | Full own profile | — → `{profile, consumer_profile, settings, consents[]}` | REQ-USR-001 | ● |
| PATCH | `/me/profile` | token | Update own profile (public fields need approval) | `{first_name?, last_name?, birth_date?, gender?, bio?, website_url?, social_links?}` → `{profile, pending_approval_fields[]}` | REQ-USR-001 | ● |
| POST | `/me/mobile/change` | token | Start mobile change with SMS confirmation to the new number | `{new_mobile}` → `202 {challenge_id}` | REQ-USR-002 | ● |
| POST | `/me/mobile/confirm` | token | Confirm the change with the OTP | `{challenge_id, code}` → `200 {mobile}` | REQ-USR-002 | ● |
| POST | `/me/email` | token | Add/verify an email address | `{email}` → `202 {verification_sent:true}` | REQ-USR-001 | ○ |
| GET | `/me/addresses` | token | Address book | — → `[address]` | REQ-USR-003 | ● |
| POST | `/me/addresses` | token | Add an address | `{label?, recipient_name, recipient_mobile, province_id, city_id, postal_code, address_line, latitude?, longitude?, is_default?}` → `201 {address}` | REQ-USR-003 | ● |
| PATCH | `/me/addresses/{address}` | token | Update an address | same fields, partial → `{address}` | REQ-USR-003 | ● |
| DELETE | `/me/addresses/{address}` | token | Soft-delete an address | — → `204` | REQ-USR-003 | ● |
| GET | `/me/favorites` | token | Wishlist of products, stores, suppliers | `?type=product&limit=` → `[{type, item}]` | REQ-USR-004 | ● |
| POST | `/me/favorites` | token | Add to wishlist | `{type:"product"\|"store"\|"supplier", id}` → `201` | REQ-USR-004 | ● |
| DELETE | `/me/favorites/{type}/{id}` | token | Remove from wishlist | — → `204` | REQ-USR-004 | ● |
| GET | `/me/documents` | token | Own identity documents and their review status | — → `[{document_type, status, reject_reason, expires_at}]` | REQ-USR-005 | ● |
| POST | `/me/documents` | token | Upload a KYC document | `{document_type, media_uuid}` → `201 {document}` | REQ-USR-005 | ● |
| PATCH | `/me/settings` | token | Locale, theme, default role, marketing opt-in, quiet hours | `{locale?, theme?, default_role_key?, marketing_opt_in?, quiet_hours?}` → `{settings}` | REQ-USR-001 | ○ |
| POST | `/me/consents` | token | Record a consent (terms version, marketing, location tracking) | `{consent_key, document_version, accepted}` → `201` | REQ-USR-001 | ● |
| GET | `/me/consents` | token | Consent history | — → `[{consent_key, document_version, accepted_at, revoked_at}]` | REQ-USR-001 | ○ |
| POST | `/me/data-export` | token | Request a copy of own data | — → `202 {job_id}` | REQ-USR-007 | ○ |
| POST | `/me/deletion-request` | token | Request account deletion (anonymisation workflow) | `{password, reason?}` → `202 {request_id, effective_at}` | REQ-USR-007 | ○ |
| GET | `/me/activity` | token | Recent activity feed (logins, orders, points) | `?limit=` → `[activity_item]` | REQ-USR-001 | ○ |

### 4.2 Cart and checkout

| Method | Path | Auth | Purpose | Request → Response | REQ | P0 |
|---|---|---|---|---|---|---|
| GET | `/me/cart` | token/public | Current cart with server-resolved prices and totals | `?city_id=` → `{cart, items[], totals, warnings[]}` | REQ-CRT-001 | ● |
| POST | `/me/cart/items` | token/public | Add a variant (or increment) | `{variant_id, quantity, notes?}` → `201 {cart, item}` | REQ-CRT-001 | ● |
| PATCH | `/me/cart/items/{item}` | token/public | Change quantity or note | `{quantity?, notes?}` → `{cart, item}` | REQ-CRT-001 | ● |
| DELETE | `/me/cart/items/{item}` | token/public | Remove a line | — → `{cart}` | REQ-CRT-001 | ● |
| POST | `/me/cart/merge` | token | Merge a guest cart into the user cart after login | `{guest_cart_token}` → `{cart}` | REQ-CRT-001 | ● |
| POST | `/me/cart/coupon` | token | Apply a coupon or bon | `{code}` → `{cart, applied_coupon, discount_amount}` | REQ-PRM-003 | ● |
| DELETE | `/me/cart/coupon` | token | Remove the coupon | — → `{cart}` | REQ-PRM-003 | ● |
| POST | `/me/cart/shipping-estimate` | token/public | Price and ETA per shipping method for the cart | `{city_id, postal_code?, address_id?}` → `{methods[], free_shipping_threshold}` | REQ-SHP-001 | ● |
| POST | `/me/cart/checkout` | token | Validate, reserve stock, create the order | `{address_id, shipping_method_id, payment_method, coupon_code?, wallet_amount?, note?, deposit_only?}` → `201 {order, payment_intent?}` | REQ-ORD-001 | ● |

### 4.3 Orders

| Method | Path | Auth | Purpose | Request → Response | REQ | P0 |
|---|---|---|---|---|---|---|
| GET | `/me/orders` | token | Own orders with status and totals | `?filter[status]=&filter[placed_at][gte]=&sort=-placed_at&limit=` → `[order_card]` | REQ-ORD-002 | ● |
| GET | `/me/orders/{order}` | token | Order detail (by `public_id` or `order_number`), sub-orders, invoices, tracking | — → `{order, sub_orders[], invoices[], payments[], shipments[], timeline[]}` | REQ-ORD-002 | ● |
| POST | `/me/orders/{order}/cancel` | token | Cancel before shipment | `{reason_code, reason_text?}` → `{order, refund?}` | REQ-ORD-003 | ● |
| POST | `/me/orders/{order}/confirm-delivery` | token | Buyer confirms receipt (starts completion clock) | — → `{sub_order}` | REQ-ORD-007 | ● |
| GET | `/me/orders/{order}/tracking` | token | Shipment tracking events | — → `{shipments:[{carrier, tracking_code, status, events[]}]}` | REQ-SHP-002 | ● |
| GET | `/me/orders/{order}/timeline` | token | Human-readable status history | — → `[{from_status, to_status, actor_type, occurred_at, note}]` | REQ-ORD-002 | ● |
| POST | `/me/orders/{order}/notes` | token | Add a note or voice attachment for the seller | `{body?, media_uuid?, kind}` → `201 {note}` | REQ-ORD-008 | ○ |
| POST | `/me/orders/{order}/reorder` | token | Rebuild a cart from a past order | — → `201 {cart, unavailable_items[]}` | REQ-ORD-002 | ○ |
| GET | `/me/orders/{order}/incomplete-report` | token | See what the seller says is missing | — → `{report, missing_fields[]}` | REQ-ORD-005 | ○ |
| POST | `/me/orders/{order}/incomplete-report/resolve` | token | Supply the missing information | `{response_text, address_id?}` → `{sub_order, report}` | REQ-ORD-005 | ○ |

### 4.4 Payments, invoices, wallet and finance

| Method | Path | Auth | Purpose | Request → Response | REQ | P0 |
|---|---|---|---|---|---|---|
| POST | `/me/orders/{order}/pay` | token | Start a payment for the order (or its balance) | `{gateway_code?, method:"online"\|"wallet", amount?}` → `201 {payment, redirect_url?}` | REQ-PAY-002 | ● |
| GET | `/me/payments/{payment}` | token | Payment status (polled by the client after redirect) | — → `{payment, attempts_summary, order_status}` | REQ-PAY-002 | ● |
| POST | `/me/payments/{payment}/cancel` | token | Abandon a pending payment | — → `{payment}` | REQ-PAY-002 | ○ |
| GET | `/me/invoices` | token | Own invoices | `?filter[type]=&filter[status]=` → `[invoice_card]` | REQ-INV-002 | ● |
| GET | `/me/invoices/{invoice}` | token | Invoice detail with lines | — → `{invoice, items[], payments[]}` | REQ-INV-002 | ● |
| GET | `/me/invoices/{invoice}/pdf` | token | Signed link to the invoice PDF | — → `{url, expires_at}` | REQ-INV-002 | ● |
| GET | `/me/wallet` | token | Wallet balance and summary | — → `{wallet, balance_amount, available_amount, locked_amount, expiring_soon}` | REQ-FIN-001 | ● |
| GET | `/me/wallet/transactions` | token | Wallet statement | `?filter[reason]=&limit=&cursor=` → `[wallet_transaction]` | REQ-FIN-001 | ● |
| POST | `/me/wallet/topup` | token | Top up the wallet through a gateway | `{amount, gateway_code}` → `201 {payment, redirect_url}` | REQ-PAY-003 | ○ |
| GET | `/me/points` | token | Points balance, tier, stars and next-tier gap | — → `{points_balance, lifetime_earned, tier, discount_percent, stars, next_tier_at}` | REQ-LOY-001 | ● |
| GET | `/me/points/transactions` | token | Points ledger | `?filter[direction]=&limit=` → `[point_transaction]` | REQ-LOY-001 | ● |
| GET | `/me/rewards` | token | Redeemable rewards with affordability | — → `[{reward, points_cost, affordable, stock_left}]` | REQ-LOY-003 | ○ |
| POST | `/me/rewards/{reward}/redeem` | token | Redeem points | `{quantity?}` → `201 {redemption, wallet_credit?/coupon?}` | REQ-LOY-003 | ○ |
| GET | `/me/coupons` | token | Own coupons and bons with validity | `?filter[status]=active` → `[coupon]` | REQ-PRM-002 | ● |
| GET | `/me/tier` | token | Tier detail and the automatic next-invoice discount | — → `{tier, discount_percent, fixed_discount_amount, valid_until}` | REQ-LOY-002 | ● |

### 4.5 Returns, complaints and reviews

| Method | Path | Auth | Purpose | Request → Response | REQ | P0 |
|---|---|---|---|---|---|---|
| POST | `/me/orders/{order}/returns` | token | Request a return/exchange inside the window | `{sub_order_id, items:[{order_item_id, quantity, reason_code, condition}], description?, media_uuids[]}` → `201 {return}` | REQ-RTN-001 | ● |
| GET | `/me/returns` | token | Own returns with status | `?filter[status]=` → `[return_card]` | REQ-RTN-001 | ● |
| GET | `/me/returns/{return}` | token | Return detail and refund status | — → `{return, items[], refund, timeline[]}` | REQ-RTN-001 | ● |
| POST | `/me/returns/{return}/ship` | token | Register the return shipment | `{carrier_code?, tracking_code?}` → `{return}` | REQ-RTN-001 | ○ |
| POST | `/me/orders/{order}/complaints` | token | File a complaint (optionally freezing settlement) | `{subject_type, category, description, severity, request_hold?, media_uuids[]}` → `201 {complaint}` | REQ-RTN-003 | ● |
| GET | `/me/complaints` | token | Own complaints | `?filter[status]=` → `[complaint_card]` | REQ-RTN-003 | ● |
| GET | `/me/complaints/{complaint}` | token | Complaint thread and events | — → `{complaint, events[], messages[]}` | REQ-RTN-003 | ● |
| POST | `/me/complaints/{complaint}/messages` | token | Add a message to a complaint | `{body, media_uuids[]?}` → `201 {message}` | REQ-RTN-003 | ○ |
| POST | `/me/violation-reports` | token | Report a supplier/store/product violation (وارن) | `{target_type, target_id, category, description, media_uuid?}` → `201 {report}` | REQ-RTN-005 | ● |
| POST | `/me/reviews` | token | Review a purchased item with 5–10 scored parameters | `{subject_type, subject_id, order_item_id, rating, title?, body?, scores:[{criterion_code, score}], media_uuids[]}` → `201 {review}` | REQ-RVW-001 | ● |
| PATCH | `/me/reviews/{review}` | token | Edit within 24 h | `{rating?, title?, body?, scores?}` → `{review}` | REQ-RVW-001 | ○ |
| DELETE | `/me/reviews/{review}` | token | Soft-delete own review | — → `204` | REQ-RVW-001 | ○ |
| GET | `/me/reviews` | token | Own reviews and moderation state | — → `[review_card]` | REQ-RVW-001 | ○ |

### 4.6 Messaging and notifications

| Method | Path | Auth | Purpose | Request → Response | REQ | P0 |
|---|---|---|---|---|---|---|
| GET | `/me/conversations` | token | Chat threads (direct, order, support) | `?filter[kind]=&filter[unread]=1` → `[conversation_card]` | REQ-MSG-001 | ● |
| POST | `/me/conversations` | token | Open a direct conversation with an allowed user | `{user_id, subject?, order_id?}` → `201 {conversation}` | REQ-MSG-001 | ● |
| GET | `/me/conversations/{conversation}/messages` | token | Message history (cursor) | `?limit=&cursor=` → `[message]` | REQ-MSG-001 | ● |
| POST | `/me/conversations/{conversation}/messages` | token | Send a message (text/image/file/voice) | `{type, body?, media_uuid?, client_message_id}` → `201 {message}` | REQ-MSG-001 | ● |
| POST | `/me/conversations/{conversation}/read` | token | Mark read up to a message | `{message_id}` → `204` | REQ-MSG-001 | ● |
| POST | `/me/conversations/{conversation}/archive` | token | Archive a thread | — → `{conversation}` | REQ-MSG-001 | ○ |
| POST | `/me/blocks` | token | Block a user | `{user_id, reason?}` → `201` | REQ-MSG-004 | ○ |
| DELETE | `/me/blocks/{user}` | token | Unblock | — → `204` | REQ-MSG-004 | ○ |
| GET | `/me/tickets` | token | Own support tickets | `?filter[status]=` → `[ticket_card]` | REQ-MSG-003 | ● |
| POST | `/me/tickets` | token | Open a ticket (wraps a conversation) | `{department, subject, priority?, body, order_id?, media_uuids[]?}` → `201 {ticket, conversation}` | REQ-MSG-003 | ● |
| GET | `/me/tickets/{ticket}` | token | Ticket detail with SLA and messages | — → `{ticket, conversation, messages[]}` | REQ-MSG-003 | ● |
| POST | `/me/tickets/{ticket}/messages` | token | Reply | `{body, media_uuids[]?}` → `201 {message}` | REQ-MSG-003 | ● |
| POST | `/me/tickets/{ticket}/rate` | token | Rate the resolution | `{score, comment?}` → `{ticket}` | REQ-MSG-003 | ○ |
| GET | `/me/notifications` | token | In-app notifications | `?filter[status]=unread&limit=&cursor=` → `[notification]` | REQ-NTF-001 | ● |
| POST | `/me/notifications/{notification}/read` | token | Mark one read | — → `204` | REQ-NTF-001 | ● |
| POST | `/me/notifications/read-all` | token | Mark all read | — → `204` | REQ-NTF-001 | ● |
| GET | `/me/notification-preferences` | token | Channel/category preferences | — → `[{channel, category, is_enabled}]` | REQ-NTF-004 | ○ |
| PATCH | `/me/notification-preferences` | token | Update preferences | `{preferences:[{channel, category, is_enabled}]}` → `{preferences}` | REQ-NTF-004 | ○ |
| POST | `/me/notifications/purchase` | token | Buy a paid notification broadcast (appendix 5 clause 12) | `{channel, title, body, media_uuid?, action_url?, audience:{type, filters}, recipient_count}` → `201 {job, payment}` | REQ-NTF-003 | ○ |

### 4.7 Customer-originated commerce extras

| Method | Path | Auth | Purpose | Request → Response | REQ | P0 |
|---|---|---|---|---|---|---|
| POST | `/me/sample-requests` | token | Request a sample/catalogue from a supplier | `{supplier_id, product_id?/category_id?, quantity, dimensions_text?, description, address_id}` → `201 {sample_request}` | REQ-SMP-001 | ○ |
| GET | `/me/sample-requests` | token | Own sample requests | `?filter[status]=` → `[sample_request_card]` | REQ-SMP-001 | ○ |
| POST | `/me/tenders` | token | Publish a tender/auction (consumer or store) | `{kind, title, description, category_id, province_id, city_id, quantity, unit_id, budget_amount?, opens_at, closes_at, visibility, terms_media_uuid?}` → `201 {tender}` | REQ-TND-001 | ○ |
| GET | `/me/tenders` | token | Own tenders and their state | — → `[tender_card]` | REQ-TND-001 | ○ |
| POST | `/me/tenders/{tender}/award` | token | Award to a bid | `{bid_id, note?}` → `{tender}` | REQ-TND-004 | ○ |
| POST | `/me/tenders/{tender}/questions` | token | Ask a clarification question | `{question}` → `201 {question}` | REQ-TND-001 | ○ |
| POST | `/me/classifieds` | token | Post a classified ad | `{category_id, title, description, price_amount?, price_type, province_id, city_id, contact_mobile, media_uuids[]}` → `201 {listing}` | REQ-CLS-002 | ○ |
| GET | `/me/classifieds` | token | Own listings with view counts | — → `[listing_card]` | REQ-CLS-002 | ○ |
| PATCH | `/me/classifieds/{listing}` | token | Edit or renew a listing | `{title?, description?, price_amount?, status?}` → `{listing}` | REQ-CLS-002 | ○ |
| DELETE | `/me/classifieds/{listing}` | token | Archive a listing | — → `204` | REQ-CLS-002 | ○ |
| POST | `/me/classifieds/{listing}/report` | token | Report a listing | `{reason}` → `201` | REQ-CLS-004 | ○ |
| POST | `/me/patogh/services` | token | Register a local service offering | `{category_id, title, description, province_id, city_id, service_area_text, base_price_amount?, price_unit, availability?, media_uuids[]}` → `201 {service}` (pending approval) | REQ-PTG-001 | ○ |
| GET | `/me/patogh/services` | token | Own service listings | — → `[service_card]` | REQ-PTG-001 | ○ |
| POST | `/me/patogh/requests` | token | Request a service (location required) | `{service_id, description, preferred_at?, address_line, city_id, latitude, longitude}` → `201 {request}` | REQ-PTG-002 | ○ |
| GET | `/me/patogh/requests` | token | Own service requests and quotes | — → `[request_card]` | REQ-PTG-002 | ○ |
| POST | `/me/patogh/requests/{request}/accept-quote` | token | Accept a provider quote | `{quote_id}` → `{request, conversation}` | REQ-PTG-002 | ○ |
| POST | `/me/media` | token | Upload a file and get a media reference | `multipart: file, purpose` → `201 {id, uuid, url, mime_type, size_bytes, expires_at}` | REQ-CTR-004 | ● |
| DELETE | `/me/media/{media}` | token | Delete an unreferenced upload | — → `204` | REQ-CTR-004 | ○ |
| POST | `/me/devices` | token | Register a push token for PWA notifications | `{token, platform, device_name}` → `201` | REQ-NTF-001 | ○ |

---

## 5. Store panel (`/store`)

### 5.1 Store profile

| Method | Path | Auth | Purpose | Request → Response | REQ | P0 |
|---|---|---|---|---|---|---|
| GET | `/store/profile` | store | Own store record, status, price list, attributed marketer | — → `{store, price_list, marketer, documents[], limits}` | REQ-MER-001 | ● |
| PATCH | `/store/profile` | store | Update store details (approval-sensitive fields flagged) | `{display_name?, services_description?, mobile_whatsapp?, landline?, address_line?, latitude?, longitude?, opening_hours?}` → `{store, pending_approval_fields[]}` | REQ-MER-001 | ● |
| POST | `/store/profile/media` | store | Add storefront/owner/business-card photos | `{media_uuid, kind, caption?}` → `201 {media}` | REQ-MER-002 | ● |
| DELETE | `/store/profile/media/{media}` | store | Remove a photo | — → `204` | REQ-MER-002 | ○ |
| GET | `/store/locations` | store | Branch list | — → `[location]` | REQ-MER-001 | ○ |
| POST | `/store/locations` | store | Add a branch | `{name, province_id, city_id, address_line, postal_code?, latitude?, longitude?, phone?, opening_hours?}` → `201 {location}` | REQ-MER-001 | ○ |
| PATCH | `/store/locations/{location}` | store | Update a branch | partial → `{location}` | REQ-MER-001 | ○ |
| GET | `/store/documents` | store | Licence/identity documents and review state | — → `[document]` | REQ-MER-005 | ● |
| POST | `/store/documents` | store | Upload a document for approval | `{document_type, media_uuid}` → `201 {document}` | REQ-MER-005 | ● |
| GET | `/store/bank-accounts` | store | Payout accounts (masked) | — → `[{id, iban_last4, bank_name, status, is_default}]` | REQ-FIN-007 | ● |
| POST | `/store/bank-accounts` | store | Add an IBAN for settlement/refund | `{iban, bank_name, account_holder_name}` → `201 {bank_account}` | REQ-FIN-007 | ● |

### 5.2 Catalog and pricing

| Method | Path | Auth | Purpose | Request → Response | REQ | P0 |
|---|---|---|---|---|---|---|
| GET | `/store/products` | store | Own products with status and stock summary | `?filter[status]=&q=&sort=` → `[product_row]` | REQ-CAT-004 | ● |
| POST | `/store/products` | store | Create a product | `{title_fa, category_id, brand_id, unit_id, sku, product_type, short_description?, description?, min_order_quantity, step_quantity, weight_grams?, warranty_months?, attributes:{code:value}, variants:[{sku, attribute_values:{code:value}, is_default}]}` → `201 {product}` | REQ-CAT-004 | ● |
| GET | `/store/products/{product}` | store | Product detail including drafts | — → `{product, variants[], media[], prices[], stock[]}` | REQ-CAT-004 | ● |
| PATCH | `/store/products/{product}` | store | Update a product or its variants | partial → `{product}` | REQ-CAT-004 | ● |
| DELETE | `/store/products/{product}` | store | Archive (never hard-delete with history) | — → `204` | REQ-CAT-004 | ● |
| POST | `/store/products/{product}/publish` | store | Submit for review / publish | `{status:"active"}` → `{product}` | REQ-CAT-004 | ● |
| POST | `/store/products/{product}/media` | store | Attach an uploaded image/video | `{media_uuid, kind, alt_text, sort_order?}` → `201 {media}` | REQ-CAT-002 | ● |
| DELETE | `/store/products/{product}/media/{media}` | store | Detach media | — → `204` | REQ-CAT-002 | ○ |
| GET | `/store/prices` | store | Own price rows per list and volume band | `?filter[price_list]=&filter[product_id]=` → `[price_row]` | REQ-PRI-001 | ● |
| PATCH | `/store/prices/{variant}` | store | Set the store's selling price for a variant | `{price_list_id, amount, min_quantity?, effective_from?}` → `{price}` | REQ-PRI-004 | ● |
| POST | `/store/prices/bulk` | store | Bulk price update from a CSV/Excel upload | `{file_media_uuid, dry_run?}` → `202 {batch, preview[]}` | REQ-PRI-004 | ○ |
| GET | `/store/inventory` | store | Stock per variant and warehouse | `?filter[low_stock]=1` → `[stock_row]` | REQ-STK-001 | ● |
| PATCH | `/store/inventory/{variant}` | store | Adjust on-hand quantity with a reason | `{warehouse_id, on_hand_quantity, reason}` → `{stock_item, movement}` | REQ-STK-001 | ● |

### 5.3 Orders and fulfilment

| Method | Path | Auth | Purpose | Request → Response | REQ | P0 |
|---|---|---|---|---|---|---|
| GET | `/store/sub-orders` | store | Work queue of sub-orders for this seller | `?filter[status]=&filter[placed_at][gte]=&sort=promised_ship_at` → `[sub_order_row]` | REQ-ORD-004 | ● |
| GET | `/store/sub-orders/{sub_order}` | store | Sub-order detail with items, buyer snapshot, notes, invoice | — → `{sub_order, items[], buyer, invoice, shipment, timeline[]}` | REQ-ORD-004 | ● |
| POST | `/store/sub-orders/{sub_order}/accept` | store | Accept and commit to a ship date | `{lead_time_days?}` → `{sub_order}` | REQ-ORD-004 | ● |
| POST | `/store/sub-orders/{sub_order}/reject` | store | Reject with a reason; triggers refund | `{reason_code, reason_text?}` → `{sub_order, refund}` | REQ-ORD-004 | ● |
| POST | `/store/sub-orders/{sub_order}/incomplete` | store | Report missing information | `{missing_fields[], note?}` → `{sub_order, report}` | REQ-ORD-005 | ○ |
| POST | `/store/sub-orders/{sub_order}/prepare` | store | Start preparing | — → `{sub_order}` | REQ-ORD-004 | ● |
| POST | `/store/sub-orders/{sub_order}/ready` | store | Mark ready to ship | — → `{sub_order}` | REQ-ORD-004 | ● |
| POST | `/store/sub-orders/{sub_order}/ship` | store | Register waybill and dispatch | `{carrier_id, tracking_code, waybill_no?, driver_name?, vehicle_plate?, shipping_cost_amount?}` → `{sub_order, shipment}` | REQ-ORD-006 | ● |
| POST | `/store/sub-orders/{sub_order}/cancel` | store | Cancel pre-shipment | `{reason_code, reason_text}` → `{sub_order, refund}` | REQ-ORD-003 | ● |
| POST | `/store/sub-orders/{sub_order}/notes` | store | Add an internal/buyer note with attachment | `{body?, media_uuid?, visibility}` → `201 {note}` | REQ-ORD-008 | ○ |
| POST | `/store/sub-orders/{sub_order}/hold` | store | Request a hold (e.g. payment review) | `{reason_code, note?}` → `{hold}` | REQ-ORD-009 | ○ |
| POST | `/store/sub-orders/{sub_order}/delay` | store | Register an expected delay with a new date | `{new_ship_at, reason}` → `{sub_order}` | REQ-ORD-007 | ○ |
| GET | `/store/shipping/carriers` | store | Carriers available to this seller | — → `[carrier]` | REQ-SHP-004 | ● |
| GET | `/store/shipping/methods` | store | Methods and rates for quoting buyers | — → `[method]` | REQ-SHP-001 | ○ |

### 5.4 Invoices, settlements and reports

| Method | Path | Auth | Purpose | Request → Response | REQ | P0 |
|---|---|---|---|---|---|---|
| GET | `/store/invoices` | store | Invoices issued to/for this store | `?filter[type]=&filter[status]=&filter[issued_at][gte]=` → `[invoice_row]` | REQ-INV-002 | ● |
| GET | `/store/invoices/{invoice}` | store | Invoice detail | — → `{invoice, items[]}` | REQ-INV-002 | ● |
| GET | `/store/invoices/{invoice}/pdf` | store | Signed PDF link | — → `{url, expires_at}` | REQ-INV-002 | ● |
| POST | `/store/invoices` | store | Create a manual invoice (store accounting, appendix 5 clause 44) | `{buyer:{user_id?/name, mobile?}, items:[{description, quantity, unit_price_amount}], due_at?, notes?}` → `201 {invoice}` | REQ-INV-004 | ○ |
| POST | `/store/invoices/{invoice}/void` | store | Void an unpaid manual invoice | `{reason}` → `{invoice}` | REQ-INV-005 | ○ |
| GET | `/store/wallet` | store | Store wallet/credit balance | — → `{wallet, balance_amount}` | REQ-FIN-001 | ● |
| GET | `/store/transactions` | store | Financial statement (sales, refunds, commissions, payouts) | `?filter[type]=&filter[occurred_at][between]=` → `[transaction_row]` | REQ-FIN-001 | ● |
| GET | `/store/settlements` | store | Settlement requests and their state | `?filter[status]=` → `[settlement_row]` | REQ-FIN-002 | ● |
| POST | `/store/settlements` | store | Request a settlement for a period | `{period_start, period_end, bank_account_id}` → `201 {settlement}` | REQ-FIN-002 | ● |
| GET | `/store/settlements/{settlement}` | store | Settlement detail with items | — → `{settlement, items[]}` | REQ-FIN-002 | ● |
| GET | `/store/reports/sales` | store | Sales by period, product, category | `?from=&to=&group_by=product\|category\|day` → `{totals, series[], rows[]}` | REQ-FIN-004 | ● |
| GET | `/store/reports/products` | store | Best sellers and slow movers | `?from=&to=&limit=` → `[product_performance]` | REQ-FIN-004 | ○ |
| POST | `/store/reports/export` | store | Excel export of a report | `{report, filters}` → `202 {export_id}` | REQ-ADM-003 | ○ |
| GET | `/store/reports/export/{export}` | store | Download a ready export | — → `{url, expires_at, row_count}` | REQ-ADM-003 | ○ |

### 5.5 Store interactions

| Method | Path | Auth | Purpose | Request → Response | REQ | P0 |
|---|---|---|---|---|---|---|
| GET | `/store/tenders` | store | Tenders open to this store as buyer or bidder | `?filter[status]=` → `[tender_card]` | REQ-TND-001 | ○ |
| POST | `/store/tenders` | store | Publish a tender for suppliers | `{title, description, category_id, quantity, unit_id, budget_amount, opens_at, closes_at, visibility}` → `201 {tender}` | REQ-TND-001 | ○ |
| POST | `/store/tenders/{tender}/invite` | store | Invite specific suppliers | `{supplier_ids[]}` → `201 {invitations[]}` | REQ-TND-003 | ○ |
| GET | `/store/sample-requests` | store | Sample requests addressed to this store | `?filter[status]=` → `[sample_request_card]` | REQ-SMP-002 | ○ |
| POST | `/store/sample-requests/{sample_request}/respond` | store | Accept or reject a sample request | `{status, note?}` → `{sample_request}` | REQ-SMP-002 | ○ |
| GET | `/store/notifications/broadcast` | store | Broadcast/paid notification catalogue and limits | — → `{limits, pricing, recent_jobs[]}` | REQ-NTF-002 | ○ |
| POST | `/store/notifications/broadcast` | store | Create and pay for a filtered broadcast | `{channel, title, body, media_uuid?, action_url?, audience:{type, filters}, recipient_count}` → `201 {job, payment}` | REQ-NTF-002 | ○ |
| GET | `/store/sms-credits` | store | SMS credit balance | — → `{balance, unit_price}` | REQ-NTF-005 | ○ |
| POST | `/store/sms-credits/purchase` | store | Buy SMS credits | `{quantity}` → `201 {payment, redirect_url}` | REQ-NTF-005 | ○ |
| GET | `/store/banner-requests` | store | Banner/slideshow requests and their approval state | — → `[banner_request]` | REQ-CMS-003 | ○ |
| POST | `/store/banner-requests` | store | Request a banner slot for a period | `{placement, image_media_uuid, mobile_media_uuid?, target_url, starts_at, ends_at}` → `201 {banner_request}` | REQ-CMS-003 | ○ |
| POST | `/store/tickets` | store | Open a support ticket | `{department, subject, priority?, body}` → `201 {ticket, conversation}` | REQ-MSG-003 | ● |
| GET | `/store/conversations` | store | Store chat threads with buyers | — → `[conversation_card]` | REQ-MSG-001 | ● |

---

## 6. Supplier panel (`/supplier`)

The supplier panel mirrors the store panel for fulfilment and adds inventory depth, branches,
buyer-partner pricing, supplementary invoices and delay handling (appendix 11).

| Method | Path | Auth | Purpose | Request → Response | REQ | P0 |
|---|---|---|---|---|---|---|
| GET | `/supplier/profile` | supplier | Own supplier record, status, price list, buyer-partner settings | — → `{supplier, price_list, buyer_partner, documents[], branches_count}` | REQ-MER-007 | ● |
| PATCH | `/supplier/profile` | supplier | Update description, site, socials, map location | partial → `{supplier}` | REQ-MER-007 | ● |
| POST | `/supplier/profile/media` | supplier | Logo, cover, gallery, video, catalogue | `{media_uuid, kind, caption?}` → `201 {media}` | REQ-MER-007 | ○ |
| GET | `/supplier/branches` | supplier | Nationwide agencies with coordinates | — → `[branch]` | REQ-MER-007 | ○ |
| POST | `/supplier/branches` | supplier | Add an agency/representative | `{title, province_id, city_id, address_line, latitude, longitude, phone?, contact_name?, services?}` → `201 {branch}` | REQ-MER-007 | ○ |
| PATCH | `/supplier/branches/{branch}` | supplier | Update a branch | partial → `{branch}` | REQ-MER-007 | ○ |
| DELETE | `/supplier/branches/{branch}` | supplier | Deactivate a branch | — → `204` | REQ-MER-007 | ○ |
| PATCH | `/supplier/buyer-partner` | supplier | Enable/disable buying from other suppliers at a fixed discount (appendix 11 clause 2) | `{is_buyer_partner, buyer_discount_percent?}` → `{supplier}` | REQ-MER-008 | ○ |
| GET | `/supplier/warehouses` | supplier | Own warehouses | — → `[warehouse]` | REQ-STK-001 | ● |
| POST | `/supplier/warehouses` | supplier | Add a warehouse | `{code, name, province_id, city_id, address_line?, latitude?, longitude?, is_default?}` → `201 {warehouse}` | REQ-STK-001 | ● |
| PATCH | `/supplier/warehouses/{warehouse}` | supplier | Update a warehouse | partial → `{warehouse}` | REQ-STK-001 | ○ |
| GET | `/supplier/inventory` | supplier | Stock levels, reserved, available, alerts | `?filter[warehouse]=&filter[low_stock]=1&sort=available_quantity` → `[stock_row]` | REQ-STK-001 | ● |
| PATCH | `/supplier/inventory/{variant}` | supplier | Set/adjust on-hand quantity with a reason | `{warehouse_id, on_hand_quantity, reason}` → `{stock_item, movement}` | REQ-STK-001 | ● |
| POST | `/supplier/inventory/bulk` | supplier | Bulk stock update from a file | `{file_media_uuid, dry_run?}` → `202 {batch, preview[]}` | REQ-STK-001 | ○ |
| GET | `/supplier/inventory/movements` | supplier | Stock movement ledger | `?filter[variant_id]=&filter[movement_type]=` → `[movement]` | REQ-STK-001 | ○ |
| POST | `/supplier/inventory/counts` | supplier | Start a physical count session | `{warehouse_id}` → `201 {count}` | REQ-STK-001 | ○ |
| POST | `/supplier/inventory/counts/{count}/lines` | supplier | Record counted quantities | `{lines:[{variant_id, counted_quantity, note?}]}` → `{count}` | REQ-STK-001 | ○ |
| POST | `/supplier/inventory/counts/{count}/post` | supplier | Post adjustments | — → `{count, adjustments[]}` | REQ-STK-001 | ○ |
| GET | `/supplier/alerts` | supplier | Low/out-of-stock alerts | `?filter[status]=open` → `[alert]` | REQ-STK-003 | ○ |
| POST | `/supplier/alerts/{alert}/acknowledge` | supplier | Acknowledge an alert | — → `{alert}` | REQ-STK-003 | ○ |
| GET | `/supplier/products` | supplier | Own catalogue | `?filter[status]=&q=` → `[product_row]` | REQ-CAT-004 | ● |
| POST | `/supplier/products` | supplier | Create a product with variants and per-level prices | `{title_fa, category_id, brand_id, unit_id, sku, product_type, attributes, variants[], prices:[{price_list_code, amount, min_quantity?}]}` → `201 {product}` | REQ-CAT-004 | ● |
| PATCH | `/supplier/products/{product}` | supplier | Update product, variants, attributes | partial → `{product}` | REQ-CAT-004 | ● |
| DELETE | `/supplier/products/{product}` | supplier | Archive a product | — → `204` | REQ-CAT-004 | ● |
| POST | `/supplier/products/{product}/media` | supplier | Attach gallery/video/360 media | `{media_uuid, kind, alt_text}` → `201 {media}` | REQ-CAT-002 | ● |
| POST | `/supplier/products/import` | supplier | Bulk catalogue import (appendix 9 clause 4) | `{file_media_uuid, dry_run?}` → `202 {batch}` | REQ-CAT-006 | ○ |
| GET | `/supplier/products/import/{batch}` | supplier | Import progress and row errors | — → `{batch, errors[]}` | REQ-CAT-006 | ○ |
| GET | `/supplier/prices` | supplier | Own price rows per list | `?filter[price_list]=` → `[price_row]` | REQ-PRI-001 | ● |
| PATCH | `/supplier/prices/{variant}` | supplier | Change a price for a list | `{price_list_id, amount, min_quantity?, effective_from?}` → `{price}` | REQ-PRI-004 | ● |
| POST | `/supplier/prices/change-requests` | supplier | Submit a bulk price change for approval | `{changes:[{variant_id, price_list_id, amount}], note?}` → `201 {price_change_request}` | REQ-PRI-004 | ○ |
| GET | `/supplier/prices/change-requests` | supplier | Status of submitted requests | — → `[price_change_request]` | REQ-PRI-004 | ○ |
| GET | `/supplier/sub-orders` | supplier | Fulfilment queue | `?filter[status]=&sort=promised_ship_at` → `[sub_order_row]` | REQ-ORD-004 | ● |
| GET | `/supplier/sub-orders/{sub_order}` | supplier | Sub-order detail | — → `{sub_order, items[], buyer, invoice, shipment, timeline[]}` | REQ-ORD-004 | ● |
| POST | `/supplier/sub-orders/{sub_order}/accept` | supplier | Accept with lead time | `{lead_time_days?}` → `{sub_order}` | REQ-ORD-004 | ● |
| POST | `/supplier/sub-orders/{sub_order}/reject` | supplier | Reject; refund to the buyer | `{reason_code, reason_text?}` → `{sub_order, refund}` | REQ-ORD-004 | ● |
| POST | `/supplier/sub-orders/{sub_order}/incomplete` | supplier | Flag incomplete order (prevents delay damages) | `{missing_fields[], note?}` → `{sub_order, report}` | REQ-ORD-005 | ● |
| POST | `/supplier/sub-orders/{sub_order}/prepare` | supplier | Start preparing | — → `{sub_order}` | REQ-ORD-004 | ● |
| POST | `/supplier/sub-orders/{sub_order}/ready` | supplier | Mark ready to ship | — → `{sub_order}` | REQ-ORD-004 | ● |
| POST | `/supplier/sub-orders/{sub_order}/ship` | supplier | Register waybill/pickup and dispatch | `{carrier_id, tracking_code, waybill_no?, driver_name?, vehicle_plate?}` → `{sub_order, shipment}` | REQ-ORD-006 | ● |
| POST | `/supplier/sub-orders/{sub_order}/delay` | supplier | Register a delay with a new promised date | `{new_ship_at, reason}` → `{sub_order}` | REQ-ORD-007 | ○ |
| POST | `/supplier/sub-orders/{sub_order}/cancel` | supplier | Cancel pre-shipment with refund | `{reason_code, reason_text}` → `{sub_order, refund}` | REQ-ORD-003 | ● |
| GET | `/supplier/sub-orders/{sub_order}/delay-assessment` | supplier | Delay damage computed for a shipment | — → `{promised_at, delivered_at, delay_days, penalty_percent, penalty_amount, status}` | REQ-ORD-007 | ○ |
| POST | `/supplier/sub-orders/{sub_order}/delay-assessment/dispute` | supplier | Contest a delay penalty | `{reason, evidence_media_uuids[]}` → `202 {dispute}` | REQ-ORD-007 | ○ |
| GET | `/supplier/invoices` | supplier | Issued invoices for own sub-orders | `?filter[status]=&filter[issued_at][gte]=` → `[invoice_row]` | REQ-INV-001 | ● |
| POST | `/supplier/invoices/{invoice}/supplementary` | supplier | Issue a supplementary invoice for extra costs (appendix 11 clause 17) | `{items:[{description, quantity, unit_price_amount}], note?}` → `201 {invoice}` | REQ-INV-003 | ○ |
| GET | `/supplier/invoices/{invoice}/pdf` | supplier | Signed PDF link | — → `{url, expires_at}` | REQ-INV-002 | ● |
| GET | `/supplier/settlements` | supplier | Settlement history | `?filter[status]=` → `[settlement_row]` | REQ-FIN-002 | ● |
| POST | `/supplier/settlements` | supplier | Request a settlement | `{period_start, period_end, bank_account_id}` → `201 {settlement}` | REQ-FIN-002 | ● |
| GET | `/supplier/ledger` | supplier | Own payable ledger (accruals, deductions, payouts) | `?from=&to=` → `{opening_balance, entries[], closing_balance}` | REQ-FIN-004 | ● |
| GET | `/supplier/reports/sales` | supplier | Sales, returns, top products, per store | `?from=&to=&group_by=` → `{totals, series[], rows[]}` | REQ-FIN-004 | ● |
| POST | `/supplier/reports/export` | supplier | Excel export | `{report, filters}` → `202 {export_id}` | REQ-ADM-003 | ○ |
| POST | `/supplier/festivals/{campaign}/join` | supplier | Offer products in a festival/campaign (appendix 11 clause 4) | `{products:[{product_id, variant_id, campaign_price_amount?, discount_percent?}]}` → `201 {participation}` | REQ-PRM-004 | ○ |
| GET | `/supplier/festivals` | supplier | Campaigns open for participation | — → `[campaign_card]` | REQ-PRM-004 | ○ |
| GET | `/supplier/banner-requests` | supplier | Slideshow/banner requests | — → `[banner_request]` | REQ-CMS-003 | ○ |
| POST | `/supplier/banner-requests` | supplier | Request a banner/slideshow slot | `{placement, image_media_uuid, target_url, starts_at, ends_at}` → `201 {banner_request}` | REQ-CMS-003 | ○ |
| GET | `/supplier/sample-requests` | supplier | Sample requests to fulfil | `?filter[status]=` → `[sample_request_card]` | REQ-SMP-002 | ○ |
| POST | `/supplier/sample-requests/{sample_request}/respond` | supplier | Accept/reject, optionally ship | `{status, note?, shipment:{carrier_id, tracking_code}?}` → `{sample_request}` | REQ-SMP-002 | ○ |
| GET | `/supplier/tenders` | supplier | Tenders and auctions to bid on | `?filter[status]=bidding&filter[category]=` → `[tender_card]` | REQ-TND-002 | ○ |
| GET | `/supplier/tenders/{tender}` | supplier | Tender detail and my bid | — → `{tender, my_bid, bid_count}` | REQ-TND-002 | ○ |
| POST | `/supplier/tenders/{tender}/bids` | supplier | Submit or revise a bid | `{amount, delivery_days, validity_days, notes?, attachment_media_uuid?}` → `201 {bid}` | REQ-TND-002 | ○ |
| DELETE | `/supplier/tenders/{tender}/bids/{bid}` | supplier | Withdraw before close | — → `204` | REQ-TND-002 | ○ |
| GET | `/supplier/violations` | supplier | Violation reports against this supplier | `?filter[status]=` → `[violation]` | REQ-RTN-005 | ○ |
| POST | `/supplier/violations/{violation}/respond` | supplier | Respond to a confirmed report | `{note, media_uuids[]?}` → `202 {violation}` | REQ-RTN-005 | ○ |
| GET | `/supplier/complaints` | supplier | Complaints and disputes against this supplier | `?filter[status]=` → `[complaint_card]` | REQ-RTN-003 | ● |
| POST | `/supplier/complaints/{complaint}/messages` | supplier | Respond to a complaint | `{body, media_uuids[]?}` → `201 {message}` | REQ-RTN-003 | ● |
| POST | `/supplier/returns/{return}/approve` | supplier | Approve a return | `{note?}` → `{return}` | REQ-RTN-002 | ○ |
| POST | `/supplier/returns/{return}/reject` | supplier | Reject a return with a reason | `{reason_code, note}` → `{return}` | REQ-RTN-002 | ○ |
| POST | `/supplier/returns/{return}/receive` | supplier | Record receipt and inspection | `{items:[{return_item_id, restock_quantity, condition}], note?}` → `{return}` | REQ-RTN-002 | ○ |
| POST | `/supplier/tickets` | supplier | Open a support ticket | `{department, subject, body}` → `201 {ticket, conversation}` | REQ-MSG-003 | ● |
| GET | `/supplier/conversations` | supplier | Buyer/store chat threads | — → `[conversation_card]` | REQ-MSG-001 | ● |
| GET | `/supplier/notifications/broadcast` | supplier | Broadcast limits and pricing | — → `{limits, pricing}` | REQ-NTF-002 | ○ |
| POST | `/supplier/notifications/broadcast` | supplier | Send a filtered broadcast to customers | `{channel, title, body, audience, recipient_count}` → `201 {job, payment}` | REQ-NTF-002 | ○ |
| GET | `/supplier/sms-credits` | supplier | SMS credit balance | — → `{balance, unit_price}` | REQ-NTF-005 | ○ |
| POST | `/supplier/sms-credits/purchase` | supplier | Buy SMS credits | `{quantity}` → `201 {payment, redirect_url}` | REQ-NTF-005 | ○ |

---

## 7. Marketer panel (`/marketer`)

| Method | Path | Auth | Purpose | Request → Response | REQ | P0 |
|---|---|---|---|---|---|---|
| GET | `/marketer/profile` | marketer | Code, QR, referral link, plan, status, counters | — → `{marketer, plan, qr_url, referral_url, stats}` | REQ-MKT-001 | ● |
| PATCH | `/marketer/profile` | marketer | Update contact details (code/QR immutable) | `{city_id?, phone?}` → `{marketer}` | REQ-MKT-001 | ○ |
| GET | `/marketer/qr` | marketer | Download the personal QR (PNG/SVG) and card data | `?format=png\|svg` → `{url, expires_at, payload}` | REQ-MKT-001 | ● |
| POST | `/marketer/referral-link` | marketer | Create/rotate a referral link for a channel | `{channel:"link"\|"qr", campaign_id?}` → `201 {code, url, qr_url}` | REQ-MKT-001 | ● |
| GET | `/marketer/stores` | marketer | Stores this marketer onboarded, with activity status | `?filter[status]=&filter[active]=1&q=` → `[store_row]` | REQ-MER-002 | ● |
| GET | `/marketer/stores/search` | marketer | Duplicate check by national id, postal code or mobile (appendix 5 clause 3) | `?national_id=\|postal_code=\|mobile=` (exactly one) → `{matches:[{store_id?, display_name, status, marketer_name, similarity}], can_register}` | REQ-MER-003 | ● |
| POST | `/marketer/stores` | marketer | Register a store on site (separate from public signup) | `{legal_name, display_name, owner:{first_name,last_name,mobile,national_id}, postal_code, province_id, city_id, address_line, latitude, longitude, google_maps_url?, mobile_primary, mobile_whatsapp?, landline?, category_id, business_type, services_description?, media_uuids[]}` → `201 {store, credentials_sent:true, edit_window_ends_at}` | REQ-MER-002 | ● |
| PATCH | `/marketer/stores/{store}` | marketer | Edit within the 5-minute window only (appendix 6 clause 9) | partial → `{store, edit_window_ends_at}` | REQ-MER-004 | ● |
| POST | `/marketer/stores/{store}/media` | marketer | Add owner/storefront/business-card photos | `{media_uuid, kind, caption?}` → `201 {media}` | REQ-MER-002 | ● |
| POST | `/marketer/stores/{store}/change-request` | marketer | Request a change after the edit window (admin approves) | `{changes:{...}, reason}` → `202 {change_request}` | REQ-MER-004 | ○ |
| GET | `/marketer/stores/{store}/invoices` | marketer | Invoices of stores this marketer visited | `?filter[issued_at][gte]=` → `[invoice_row]` | REQ-MER-002 | ○ |
| GET | `/marketer/visits` | marketer | Visit log with outcomes | `?filter[status]=&filter[from]=&filter[to]=` → `[visit]` | REQ-GEO-001 | ● |
| POST | `/marketer/visits` | marketer | Check in at a location (lead, registration or follow-up) | `{store_id?, lead_name?, lead_mobile?, visit_type, latitude, longitude, accuracy_m, notes?}` → `201 {visit}` | REQ-GEO-001 | ● |
| PATCH | `/marketer/visits/{visit}` | marketer | Update notes/photos while open | `{notes?, media_uuids[]?}` → `{visit}` | REQ-GEO-001 | ○ |
| POST | `/marketer/visits/{visit}/checkout` | marketer | Check out and record the outcome | `{latitude, longitude, outcome, notes?}` → `{visit}` | REQ-GEO-001 | ● |
| POST | `/marketer/visits/{visit}/cancel` | marketer | Cancel or mark a no-show | `{reason}` → `{visit}` | REQ-GEO-001 | ○ |
| GET | `/marketer/route-plans` | marketer | Daily route plans | `?from=&to=` → `[route_plan]` | REQ-GEO-004 | ○ |
| POST | `/marketer/route-plans` | marketer | Plan a day's stops | `{plan_date, stops:[{store_id?, lead_name?, planned_at}]}` → `201 {route_plan}` | REQ-GEO-004 | ○ |
| POST | `/marketer/tracking/pings` | marketer | Batch location pings while on shift (appendix 14 clause 2) | `{pings:[{latitude, longitude, accuracy_m, recorded_at, battery_percent?}]}` → `202 {accepted, rejected}` | REQ-GEO-002 | ○ |
| GET | `/marketer/tracking/summary` | marketer | Own distance/visit analysis for a period | `?from=&to=` → `{distance_km, visits, avg_minutes_per_visit, coverage}` | REQ-GEO-003 | ○ |
| GET | `/marketer/stores/{store}/activity` | marketer | Store activity: orders, last purchase, inactive flag | — → `{store, last_order_at, orders_count, is_inactive, reactivation_hint}` | REQ-MER-006 | ● |
| GET | `/marketer/commissions` | marketer | Accrued, approved and settled commission | `?filter[status]=&filter[period]=` → `{totals, entries[]}` | REQ-CMP-002 | ● |
| GET | `/marketer/statements` | marketer | Periodic statements (PDF) | `?from=&to=` → `[statement]` | REQ-CMP-003 | ○ |
| GET | `/marketer/settlements` | marketer | Settlement requests and status | — → `[settlement_row]` | REQ-FIN-002 | ● |
| POST | `/marketer/settlements` | marketer | Request a settlement | `{period_start, period_end, bank_account_id}` → `201 {settlement}` | REQ-FIN-002 | ● |
| GET | `/marketer/wallet` | marketer | Wallet/balance for commission payouts | — → `{wallet, balance_amount}` | REQ-FIN-001 | ● |
| GET | `/marketer/statement` | marketer | Full financial statement (appendix 3-2 clause 2) | `?from=&to=` → `{opening, entries[], closing, downloadable_pdf}` | REQ-FIN-001 | ● |
| GET | `/marketer/attributions` | marketer | Confirmed/pending store attributions | `?filter[status]=` → `[attribution]` | REQ-MKT-003 | ● |
| GET | `/marketer/performance` | marketer | Visits, registrations, activation rate, sales | `?from=&to=` → `{visits, registrations, activations, sales_amount, conversion_rate}` | REQ-GEO-003 | ● |
| GET | `/marketer/plan` | marketer | Current commission plan and conditions | — → `{plan, rules[], next_condition}` | REQ-CMP-001 | ● |
| POST | `/marketer/complaints` | marketer | File a complaint/report to admin | `{subject_type, subject_id, category, description}` → `201 {complaint}` | REQ-RTN-003 | ● |
| GET | `/marketer/territories` | marketer | Assigned regions | — → `[region]` | REQ-GEO-004 | ○ |
| POST | `/marketer/tickets` | marketer | Open a support ticket | `{department, subject, body}` → `201 {ticket, conversation}` | REQ-MSG-003 | ● |

---

## 8. Admin panel (`/admin`)

### 8.1 Admin authentication and access

| Method | Path | Auth | Purpose | Request → Response | REQ | P0 |
|---|---|---|---|---|---|---|
| POST | `/admin/auth/login` | public | Admin login step 1 (password) | `{mobile, password}` → `202 {challenge_token, two_factor_required:true, expires_in}` | REQ-IAM-005 | ● |
| POST | `/admin/auth/2fa/verify` | public | Step 2 (TOTP or recovery code) | `{challenge_token, code}` → `200 {token, expires_at, admin}` | REQ-IAM-005 | ● |
| POST | `/admin/auth/2fa/enrol` | admin | Enrol a TOTP secret and get recovery codes | — → `201 {secret, qr_svg, recovery_codes[]}` | REQ-IAM-005 | ● |
| POST | `/admin/auth/logout` | admin | Revoke the admin token | — → `204` | REQ-IAM-005 | ● |
| GET | `/admin/users` | admin | Search users across all levels | `?q=&filter[role]=&filter[status]=&sort=-created_at` → `[user_row]` | REQ-ADM-001 | ● |
| GET | `/admin/users/{user}` | admin | User 360°: roles, profiles, orders, wallet, points, documents | — → `{user, roles[], profile, merchant?, stats, recent_orders[]}` | REQ-ADM-001 | ● |
| PATCH | `/admin/users/{user}` | admin | Correct user data (audited) | `{first_name?, last_name?, email?, status?}` → `{user}` | REQ-ADM-001 | ● |
| POST | `/admin/users/{user}/suspend` | admin | Suspend/block with a reason | `{reason, until?}` → `{user}` | REQ-ADM-001 | ● |
| POST | `/admin/users/{user}/reactivate` | admin | Reactivate | `{note?}` → `{user}` | REQ-ADM-001 | ● |
| POST | `/admin/users/{user}/roles` | admin | Grant or revoke a level (incl. secondary) | `{role_key, action:"grant"\|"revoke", reason}` → `{user, roles[]}` | REQ-IAM-004 | ● |
| POST | `/admin/users/{user}/impersonate` | admin | Read-only "view as" session | `{reason}` → `201 {token, expires_at, read_only:true}` | REQ-ADM-006 | ○ |
| GET | `/admin/role-requests` | admin | Pending secondary-role requests | `?filter[status]=pending` → `[role_request]` | REQ-IAM-004 | ○ |
| POST | `/admin/role-requests/{request}/decide` | admin | Approve/reject a role request | `{decision, note?}` → `{role_request}` | REQ-IAM-004 | ○ |

### 8.2 Merchants

| Method | Path | Auth | Purpose | Request → Response | REQ | P0 |
|---|---|---|---|---|---|---|
| GET | `/admin/stores` | admin | Store list with status, marketer, activity | `?filter[status]=&filter[marketer]=&filter[city]=&q=` → `[store_row]` | REQ-MER-005 | ● |
| GET | `/admin/stores/{store}` | admin | Store detail with documents, history, orders | — → `{store, documents[], status_history[], orders_summary, marketer}` | REQ-MER-005 | ● |
| POST | `/admin/stores/{store}/approve` | admin | Approve and activate | `{price_list_id}` → `{store}` | REQ-MER-005 | ● |
| POST | `/admin/stores/{store}/reject` | admin | Reject with a reason | `{reason}` → `{store}` | REQ-MER-005 | ● |
| POST | `/admin/stores/{store}/deactivate` | admin | Deactivate (inactivity or violation) | `{reason_code, note?}` → `{store}` | REQ-MER-006 | ● |
| POST | `/admin/stores/{store}/reassign-marketer` | admin | Re-attribute a store (recorded) | `{marketer_id, reason}` → `{store, attribution}` | REQ-MER-003 | ○ |
| GET | `/admin/suppliers` | admin | Supplier list | `?filter[status]=&q=` → `[supplier_row]` | REQ-MER-005 | ● |
| POST | `/admin/suppliers/{supplier}/approve` | admin | Approve and activate | `{price_list_id}` → `{supplier}` | REQ-MER-005 | ● |
| POST | `/admin/suppliers/{supplier}/reject` | admin | Reject with a reason | `{reason}` → `{supplier}` | REQ-MER-005 | ● |
| PATCH | `/admin/suppliers/{supplier}` | admin | Edit supplier master data | partial → `{supplier}` | REQ-MER-005 | ○ |
| GET | `/admin/marketers` | admin | Marketer list with plan and performance | `?filter[status]=&filter[plan]=` → `[marketer_row]` | REQ-CMP-001 | ● |
| PATCH | `/admin/marketers/{marketer}` | admin | Change plan, territory, status | `{plan_id?, status?, city_id?}` → `{marketer}` | REQ-CMP-001 | ● |
| POST | `/admin/marketers/{marketer}/disable-commission` | admin | Disable/enable commission without termination | `{disabled, reason}` → `{marketer}` | REQ-CMP-004 | ● |
| GET | `/admin/marketers/{marketer}/performance` | admin | Visits, stores, sales, commissions | `?from=&to=` → `{stats, series[]}` | REQ-GEO-003 | ○ |
| POST | `/admin/marketers/{marketer}/regenerate-qr` | admin | Rotate QR (breaks printed cards) | `{reason}` → `{marketer, qr_url}` | REQ-MKT-001 | ○ |

### 8.3 Catalog, pricing and inventory

| Method | Path | Auth | Purpose | Request → Response | REQ | P0 |
|---|---|---|---|---|---|---|
| GET | `/admin/catalog/products` | admin | Product list incl. drafts and archived | `?filter[status]=&filter[supplier]=&q=` → `[product_row]` | REQ-CAT-004 | ● |
| POST | `/admin/catalog/products` | admin | Create a product on behalf of a seller, with per-level prices | `{seller:{type,id}, product fields, variants[], prices[]}` → `201 {product}` | REQ-CAT-004 | ● |
| PATCH | `/admin/catalog/products/{product}` | admin | Edit any product | partial → `{product}` | REQ-CAT-004 | ● |
| POST | `/admin/catalog/products/{product}/status` | admin | Change lifecycle status | `{status, reason?}` → `{product}` | REQ-CAT-004 | ● |
| GET | `/admin/catalog/categories` | admin | Category tree with counts | — → `[category]` | REQ-CAT-001 | ● |
| POST | `/admin/catalog/categories` | admin | Create a category | `{parent_id?, name_fa, slug, sort_order?, seo?}` → `201 {category}` | REQ-CAT-001 | ● |
| PATCH | `/admin/catalog/categories/{category}` | admin | Update/move a category | partial → `{category}` | REQ-CAT-001 | ● |
| DELETE | `/admin/catalog/categories/{category}` | admin | Deactivate a category | — → `204` | REQ-CAT-001 | ○ |
| GET | `/admin/catalog/brands` | admin | Brands | `?q=` → `[brand]` | REQ-CAT-004 | ● |
| POST | `/admin/catalog/brands` | admin | Create a brand | `{name_fa, name_en?, slug, logo_media_uuid?}` → `201 {brand}` | REQ-CAT-004 | ○ |
| GET | `/admin/catalog/attributes` | admin | Attribute definitions and values | — → `[attribute]` | REQ-CAT-004 | ● |
| POST | `/admin/catalog/attributes` | admin | Create an attribute | `{code, name_fa, data_type, is_filterable, is_variant_axis, unit_label?}` → `201 {attribute}` | REQ-CAT-004 | ● |
| POST | `/admin/catalog/attributes/{attribute}/values` | admin | Add an allowed value | `{value_fa, slug, color_hex?, sort_order?}` → `201 {value}` | REQ-CAT-004 | ○ |
| PUT | `/admin/catalog/categories/{category}/attributes` | admin | Set which attributes a category requires/filters | `{attributes:[{attribute_id, is_required, is_filterable, is_comparable}]}` → `{category}` | REQ-CAT-004 | ● |
| GET | `/admin/pricing/price-lists` | admin | Price lists and their audiences | — → `[price_list]` | REQ-PRI-001 | ● |
| POST | `/admin/pricing/price-lists` | admin | Create a price list | `{code, name_fa, audience, tier?, is_default?, priority?}` → `201 {price_list}` | REQ-PRI-001 | ● |
| GET | `/admin/pricing/prices` | admin | Price rows with filters | `?filter[price_list]=&filter[product]=&filter[status]=` → `[price_row]` | REQ-PRI-001 | ● |
| POST | `/admin/pricing/prices` | admin | Set or override a price | `{price_list_id, variant_id, amount, min_quantity?, effective_from?, effective_to?}` → `201 {price}` | REQ-PRI-001 | ● |
| POST | `/admin/pricing/markups` | admin | Create a consumer markup rule (e.g. +35 %) | `{price_list_id, scope_type, scope_id?, markup_percent, rounding_mode}` → `201 {markup_rule}` | REQ-PRI-002 | ● |
| POST | `/admin/pricing/volume-tiers` | admin | Define a volume discount band | `{price_list_id, scope_type, scope_id, min_quantity, max_quantity?, discount_percent?/discount_amount?}` → `201 {volume_tier}` | REQ-PRI-003 | ○ |
| POST | `/admin/pricing/price-change-requests/{request}/decide` | admin | Approve/reject a supplier bulk change | `{decision, note?}` → `{price_change_request}` | REQ-PRI-004 | ○ |
| POST | `/admin/pricing/publish` | admin | Apply staged prices atomically | `{batch_id}` → `202 {job_id}` | REQ-PRI-001 | ○ |
| GET | `/admin/inventory/stock` | admin | Stock across warehouses | `?filter[supplier]=&filter[low_stock]=` → `[stock_row]` | REQ-STK-001 | ○ |
| POST | `/admin/inventory/adjust` | admin | Manual stock adjustment with a reason | `{stock_item_id, quantity_delta, reason}` → `{stock_item, movement}` | REQ-STK-001 | ○ |

### 8.4 Orders, invoices and payments

| Method | Path | Auth | Purpose | Request → Response | REQ | P0 |
|---|---|---|---|---|---|---|
| GET | `/admin/orders` | admin | All orders with filters and totals | `?filter[status]=&filter[payment_status]=&filter[placed_at][between]=&filter[buyer]=&sort=-placed_at` → `{data, meta, links, totals}` | REQ-ADM-002 | ● |
| GET | `/admin/orders/{order}` | admin | Order detail with all sub-orders and money trail | — → `{order, sub_orders[], items[], invoices[], payments[], ledger_summary, timeline[]}` | REQ-ADM-002 | ● |
| POST | `/admin/orders/{order}/cancel` | admin | Cancel with refund | `{reason_code, reason_text, refund_destination}` → `{order, refund}` | REQ-ORD-003 | ● |
| POST | `/admin/orders/{order}/hold` | admin | Freeze fulfilment/payout | `{reason_code, note, hold_until?}` → `{hold}` | REQ-ORD-009 | ● |
| POST | `/admin/orders/{order}/release-hold` | admin | Release a hold | `{note}` → `{order}` | REQ-ORD-009 | ● |
| POST | `/admin/orders/{order}/force-status` | admin | Corrective status override (audited, reason required) | `{status, reason}` → `{order}` | REQ-ADM-002 | ○ |
| POST | `/admin/orders/{order}/resend-invoice` | admin | Re-deliver the invoice PDF | `{channel:"sms"\|"email"}` → `202` | REQ-INV-002 | ○ |
| GET | `/admin/invoices` | admin | All invoices | `?filter[type]=&filter[status]=&filter[supplier]=&filter[fiscal_year]=` → `[invoice_row]` | REQ-ADM-002 | ● |
| POST | `/admin/invoices` | admin | Create a manual invoice | `{seller:{type,id}, buyer, items[], due_at?}` → `201 {invoice}` | REQ-INV-004 | ○ |
| POST | `/admin/invoices/{invoice}/void` | admin | Void with a reason | `{reason}` → `{invoice}` | REQ-INV-005 | ● |
| GET | `/admin/payments` | admin | Payments with gateway, status and attempts | `?filter[status]=&filter[gateway]=&filter[paid_at][between]=` → `[payment_row]` | REQ-PAY-005 | ● |
| GET | `/admin/payments/{payment}` | admin | Payment detail with attempts and webhooks | — → `{payment, attempts[], webhooks[], refunds[]}` | REQ-PAY-005 | ● |
| POST | `/admin/payments/{payment}/refund` | admin | Refund fully or partially | `{amount, reason_code, destination, bank_account_id?, note?}` → `201 {refund}` | REQ-PAY-004 | ● |
| GET | `/admin/gateways` | admin | Gateway list with masked credentials | — → `[gateway{code, name_fa, is_active, mode, masked_terminal}]` | REQ-PAY-001 | ● |
| PATCH | `/admin/gateways/{gateway}` | admin | Activate/deactivate and set credentials | `{is_active?, mode?, credentials?, priority?, fee_percent?}` → `{gateway}` | REQ-PAY-001 | ● |
| GET | `/admin/reconciliations` | admin | Daily gateway reconciliation | `?filter[gateway]=&filter[status]=mismatch&from=&to=` → `[reconciliation]` | REQ-PAY-006 | ● |
| POST | `/admin/reconciliations` | admin | Upload a statement and match it | `{gateway_id, business_date, file_media_uuid}` → `202 {reconciliation}` | REQ-PAY-006 | ○ |
| GET | `/admin/payments/failed` | admin | Failed/returned transactions report (appendix 13 clause 6) | `?from=&to=&gateway=` → `{totals, rows[]}` | REQ-PAY-005 | ● |

### 8.5 Finance and settlements

| Method | Path | Auth | Purpose | Request → Response | REQ | P0 |
|---|---|---|---|---|---|---|
| GET | `/admin/ledger` | admin | Ledger explorer by account, source and date | `?filter[account]=&filter[source_type]=&filter[occurred_at][between]=` → `{entries[], totals}` | REQ-FIN-004 | ● |
| GET | `/admin/ledger/accounts` | admin | Chart of accounts with derived balances | — → `[{code, name_fa, type, balance_amount}]` | REQ-FIN-004 | ● |
| GET | `/admin/wallets` | admin | Wallets with balances and flags | `?filter[status]=&filter[min_balance]=` → `[wallet_row]` | REQ-FIN-001 | ○ |
| POST | `/admin/wallets/{user}/adjust` | admin | Manual wallet credit/debit with reason | `{direction, amount, reason_code, note}` → `201 {adjustment}` | REQ-FIN-006 | ○ |
| POST | `/admin/wallets/{user}/freeze` | admin | Freeze/unfreeze a wallet | `{frozen, reason}` → `{wallet}` | REQ-FIN-001 | ○ |
| GET | `/admin/settlements` | admin | Settlement queue | `?filter[status]=&filter[payee_type]=&filter[period]=` → `[settlement_row]` | REQ-FIN-003 | ● |
| GET | `/admin/settlements/{settlement}` | admin | Settlement detail and items | — → `{settlement, items[], payee, bank_account}` | REQ-FIN-003 | ● |
| POST | `/admin/settlements` | admin | Build a settlement for a payee and period | `{payee_type, payee_id, period_start, period_end}` → `201 {settlement}` | REQ-FIN-003 | ● |
| POST | `/admin/settlements/{settlement}/approve` | admin | Approve (four-eyes) | `{note?}` → `{settlement}` | REQ-FIN-003 | ● |
| POST | `/admin/settlements/{settlement}/reject` | admin | Reject with a reason | `{reason}` → `{settlement}` | REQ-FIN-003 | ● |
| POST | `/admin/settlements/{settlement}/pay` | admin | Mark paid with bank reference | `{bank_reference, paid_at}` → `{settlement}` | REQ-FIN-003 | ● |
| POST | `/admin/settlements/export` | admin | Excel batch file for the bank | `{settlement_ids[], business_date}` → `202 {export_id, transfer_no}` | REQ-FIN-005 | ● |
| GET | `/admin/bank-transfers` | admin | Bank transfer batches | `?filter[status]=` → `[transfer]` | REQ-FIN-005 | ○ |
| POST | `/admin/adjustments` | admin | Manual charge/credit on a payee account | `{payee_type, payee_id, direction, amount, reason_code, note}` → `201 {adjustment}` | REQ-FIN-006 | ● |
| GET | `/admin/bank-accounts` | admin | Payout accounts awaiting verification | `?filter[status]=pending_verification` → `[bank_account{iban_last4, owner, status}]` | REQ-FIN-007 | ● |
| POST | `/admin/bank-accounts/{bank_account}/verify` | admin | Verify an IBAN | `{decision, note?}` → `{bank_account}` | REQ-FIN-007 | ● |
| GET | `/admin/reports/sales` | admin | Sales report with filters and grouping | `?from=&to=&group_by=day\|product\|supplier\|store` → `{totals, series[], rows[]}` | REQ-FIN-004 | ● |
| GET | `/admin/reports/settlements` | admin | Payables by payee and period | `?from=&to=&payee_type=` → `{rows[], totals}` | REQ-FIN-004 | ● |

### 8.6 Loyalty, promotions and compensation

| Method | Path | Auth | Purpose | Request → Response | REQ | P0 |
|---|---|---|---|---|---|---|
| GET | `/admin/loyalty/rule-sets` | admin | Rule sets with versions and status | — → `[rule_set]` | REQ-LOY-007 | ● |
| POST | `/admin/loyalty/rule-sets` | admin | Create a new draft version | `{name, scope, scope_id?, effective_from, rules[]}` → `201 {rule_set}` | REQ-LOY-007 | ● |
| PATCH | `/admin/loyalty/rule-sets/{rule_set}` | admin | Edit a draft (active sets are immutable) | `{rules[]}` → `{rule_set}` | REQ-LOY-007 | ○ |
| POST | `/admin/loyalty/rule-sets/{rule_set}/activate` | admin | Activate; retires the previous version | `{note?}` → `{rule_set}` | REQ-LOY-007 | ● |
| POST | `/admin/loyalty/rule-sets/{rule_set}/replay` | admin | Recompute historical points to verify the ruleset | `{from, to, dry_run}` → `202 {job_id}` | REQ-LOY-007 | ○ |
| GET | `/admin/loyalty/tiers` | admin | Tier bands and their discounts | — → `[tier]` | REQ-LOY-002 | ● |
| PUT | `/admin/loyalty/tiers` | admin | Replace the tier table | `{tiers:[{code, min_points, max_points?, discount_percent, fixed_discount_amount}]}` → `{tiers[]}` | REQ-LOY-002 | ● |
| GET | `/admin/loyalty/points` | admin | Point balances and recent transactions | `?q=&filter[min_points]=&sort=-points_balance` → `[point_row]` | REQ-LOY-001 | ● |
| POST | `/admin/loyalty/points/adjust` | admin | Manual point credit/debit | `{user_id, points, direction, reason_code, note}` → `201 {point_transaction}` | REQ-LOY-005 | ● |
| GET | `/admin/loyalty/rewards` | admin | Reward catalogue | — → `[reward]` | REQ-LOY-003 | ○ |
| POST | `/admin/loyalty/rewards` | admin | Create a reward | `{code, name_fa, type, points_cost, cash_amount?, stock_quantity?, per_user_limit?, valid_from?, valid_to?}` → `201 {reward}` | REQ-LOY-003 | ○ |
| PATCH | `/admin/loyalty/rewards/{reward}` | admin | Update/deactivate a reward | partial → `{reward}` | REQ-LOY-003 | ○ |
| POST | `/admin/loyalty/penalties/run` | admin | Run inactivity/violation penalties for a period | `{period_start, period_end, dry_run}` → `202 {job_id, affected_count}` | REQ-LOY-005 | ○ |
| GET | `/admin/promotions` | admin | Promotion list with usage | `?filter[status]=&filter[type]=` → `[promotion]` | REQ-PRM-001 | ● |
| POST | `/admin/promotions` | admin | Create a promotion | `{code, name_fa, type, scope, discount_percent?/discount_amount?, min_order_amount?, max_discount_amount?, targets[], effective_from, effective_to, is_stackable, priority}` → `201 {promotion}` | REQ-PRM-001 | ● |
| PATCH | `/admin/promotions/{promotion}` | admin | Update/pause a promotion | partial → `{promotion}` | REQ-PRM-001 | ● |
| GET | `/admin/coupons` | admin | Coupons and bons with usage | `?filter[status]=&filter[batch]=&q=` → `[coupon]` | REQ-PRM-002 | ● |
| POST | `/admin/coupons` | admin | Create a single coupon/bon | `{code, type, amount?/discount_percent?, min_order_amount?, issued_to_user_id?, valid_from, valid_to, max_uses}` → `201 {coupon}` | REQ-PRM-002 | ● |
| POST | `/admin/coupons/batches` | admin | Generate a coupon batch | `{promotion_id, quantity, prefix, valid_to, amount?}` → `202 {batch}` | REQ-PRM-002 | ○ |
| POST | `/admin/coupons/{coupon}/revoke` | admin | Revoke a coupon | `{reason}` → `{coupon}` | REQ-PRM-002 | ○ |
| POST | `/admin/discount-grants` | admin | Grant a standing discount to a user | `{user_id, type, amount?/percent?, reason, valid_from, valid_to, max_invoices?}` → `201 {grant}` | REQ-PRM-005 | ○ |
| GET | `/admin/campaigns` | admin | Festivals/campaigns | `?filter[status]=` → `[campaign]` | REQ-PRM-004 | ○ |
| POST | `/admin/campaigns` | admin | Create a campaign | `{code, name_fa, starts_at, ends_at, banner_media_uuid?, priority}` → `201 {campaign}` | REQ-PRM-004 | ○ |
| POST | `/admin/campaigns/{campaign}/products` | admin | Attach products with campaign prices | `{products:[{product_id, variant_id?, campaign_price_amount?, discount_percent?}]}` → `201 {participation[]}` | REQ-PRM-004 | ○ |
| GET | `/admin/commission-plans` | admin | Marketer/store pay plans | — → `[plan]` | REQ-CMP-001 | ● |
| POST | `/admin/commission-plans` | admin | Create a plan (A–D or custom) | `{code, name_fa, audience, basis, percent_rate?, fixed_visit_amount?, fixed_salary_amount?, requires_plan_id?, effective_from}` → `201 {plan}` | REQ-CMP-001 | ● |
| PATCH | `/admin/commission-plans/{plan}` | admin | Clone/deactivate a plan | `{is_active?, name_fa?}` → `{plan}` | REQ-CMP-001 | ○ |
| GET | `/admin/commission-entries` | admin | Accrued commission with filters | `?filter[status]=&filter[beneficiary]=&from=&to=` → `{rows[], totals}` | REQ-CMP-002 | ● |
| POST | `/admin/commission-entries/{entry}/reverse` | admin | Reverse an accrual | `{reason}` → `{entry, reversal}` | REQ-CMP-002 | ○ |

### 8.7 Shipping, returns and disputes

| Method | Path | Auth | Purpose | Request → Response | REQ | P0 |
|---|---|---|---|---|---|---|
| GET | `/admin/shipping/carriers` | admin | Carrier master data | — → `[carrier]` | REQ-SHP-004 | ● |
| POST | `/admin/shipping/carriers` | admin | Create a carrier | `{code, name_fa, type, tracking_url_template?, supports_cod}` → `201 {carrier}` | REQ-SHP-004 | ○ |
| GET | `/admin/shipping/methods` | admin | Methods with pricing model | — → `[method]` | REQ-SHP-004 | ● |
| POST | `/admin/shipping/methods` | admin | Create a method | `{code, name_fa, carrier_id, scope, pricing_type, base_amount, per_kg_amount?, free_threshold_amount?, estimated_days_min, estimated_days_max}` → `201 {method}` | REQ-SHP-004 | ● |
| PUT | `/admin/shipping/methods/{method}/rates` | admin | Replace regional rates/free-shipping zones | `{rates:[{province_id?, city_id?, amount, free_threshold_amount?, is_free}]}` → `{method, rates[]}` | REQ-SHP-003 | ○ |
| GET | `/admin/shipments` | admin | Shipments with status and delays | `?filter[status]=&filter[carrier]=&filter[delayed]=1` → `[shipment_row]` | REQ-SHP-002 | ○ |
| POST | `/admin/shipments/{shipment}/status` | admin | Manual tracking update | `{status, description?, occurred_at?}` → `{shipment}` | REQ-SHP-002 | ○ |
| GET | `/admin/delivery-delays` | admin | Delay assessments and penalties | `?filter[status]=&from=&to=` → `[delay]` | REQ-ORD-007 | ○ |
| POST | `/admin/delivery-delays/{delay}/decide` | admin | Approve/waive a penalty | `{decision, note}` → `{delay}` | REQ-ORD-007 | ○ |
| GET | `/admin/returns` | admin | Return queue | `?filter[status]=&filter[supplier]=` → `[return_row]` | REQ-RTN-002 | ● |
| POST | `/admin/returns/{return}/decide` | admin | Approve/reject a return on the seller's behalf | `{decision, note}` → `{return}` | REQ-RTN-002 | ● |
| POST | `/admin/returns/{return}/refund` | admin | Execute the refund | `{destination, bank_account_id?}` → `201 {refund}` | REQ-RTN-002 | ● |
| GET | `/admin/complaints` | admin | Complaint queue with SLA | `?filter[status]=&filter[severity]=&filter[settlement_hold]=1` → `[complaint_row]` | REQ-RTN-003 | ● |
| POST | `/admin/complaints/{complaint}/assign` | admin | Assign to an agent | `{assignee_id}` → `{complaint}` | REQ-RTN-003 | ● |
| POST | `/admin/complaints/{complaint}/decide` | admin | Resolve/reject/escalate | `{decision, note, release_hold?}` → `{complaint}` | REQ-RTN-003 | ● |
| GET | `/admin/disputes` | admin | Disputes with money at stake | `?filter[status]=` → `[dispute_row]` | REQ-RTN-004 | ○ |
| POST | `/admin/disputes/{dispute}/decide` | admin | Arbitrate and post the money movement | `{decision, amounts:{buyer_amount, seller_amount}, note}` → `{dispute, ledger_batch}` | REQ-RTN-004 | ○ |
| GET | `/admin/violation-reports` | admin | Violation reports queue | `?filter[status]=&filter[target_type]=` → `[violation_row]` | REQ-RTN-005 | ● |
| POST | `/admin/violation-reports/{report}/decide` | admin | Confirm/reject, set penalty points and visibility | `{decision, penalty_points?, visible_to_partners?, note}` → `{report}` | REQ-RTN-005 | ● |

### 8.8 Content, messaging and notifications

| Method | Path | Auth | Purpose | Request → Response | REQ | P0 |
|---|---|---|---|---|---|---|
| GET | `/admin/cms/posts` | admin | Blog posts incl. drafts | `?filter[status]=&q=` → `[post_row]` | REQ-CMS-001 | ○ |
| POST | `/admin/cms/posts` | admin | Create a post | `{slug, title, excerpt?, body, category_id?, cover_media_uuid?, seo_title, seo_description, status, published_at?}` → `201 {post}` | REQ-CMS-001 | ○ |
| PATCH | `/admin/cms/posts/{post}` | admin | Update a post | partial → `{post}` | REQ-CMS-001 | ○ |
| DELETE | `/admin/cms/posts/{post}` | admin | Archive a post | — → `204` | REQ-CMS-001 | ○ |
| GET | `/admin/cms/pages` | admin | Static pages | — → `[page_row]` | REQ-CMS-002 | ● |
| PUT | `/admin/cms/pages/{slug}` | admin | Upsert a static page | `{title, body, seo_title, seo_description, status, show_in_footer}` → `{page}` | REQ-CMS-002 | ● |
| GET | `/admin/cms/banners` | admin | Banners and requests | `?filter[placement]=&filter[status]=` → `[banner_row]` | REQ-CMS-003 | ● |
| POST | `/admin/cms/banners` | admin | Create a banner | `{placement, title, image_media_uuid, mobile_media_uuid?, target_url, starts_at, ends_at, sort_order?}` → `201 {banner}` | REQ-CMS-003 | ● |
| POST | `/admin/cms/banners/{banner}/decide` | admin | Approve/reject a supplier banner request | `{decision, note?}` → `{banner}` | REQ-CMS-003 | ● |
| PUT | `/admin/cms/menus/{key}` | admin | Replace a menu | `{items:[{label, url, target?, children[]?}]}` → `{menu}` | REQ-CMS-004 | ○ |
| GET | `/admin/cms/surveys` | admin | Surveys with response counts | — → `[survey_row]` | REQ-CMS-005 | ○ |
| POST | `/admin/cms/surveys` | admin | Create a survey | `{title, description?, type, starts_at?, ends_at?, is_anonymous, questions[]}` → `201 {survey}` | REQ-CMS-005 | ○ |
| PATCH | `/admin/cms/surveys/{survey}` | admin | Update/close a survey | `{status?, ends_at?}` → `{survey}` | REQ-CMS-005 | ○ |
| GET | `/admin/cms/surveys/{survey}/responses` | admin | Results and aggregates | — → `{summary, responses[]}` | REQ-CMS-005 | ○ |
| GET | `/admin/notifications/jobs` | admin | Custom notification jobs | `?filter[status]=` → `[job_row]` | REQ-NTF-002 | ● |
| POST | `/admin/notifications/jobs` | admin | Create a custom notification (text, image, link, video) | `{channel, title, body, media_uuid?, action_url?, audience:{type, filters}, scheduled_at?, is_paid}` → `201 {job}` | REQ-NTF-002 | ● |
| POST | `/admin/notifications/jobs/{job}/approve` | admin | Approve and queue a paid job after payment | `{note?}` → `{job}` | REQ-NTF-002 | ● |
| POST | `/admin/notifications/jobs/{job}/cancel` | admin | Cancel before sending | `{reason}` → `{job}` | REQ-NTF-002 | ○ |
| GET | `/admin/notifications/jobs/{job}/deliveries` | admin | Per-recipient delivery results | `?filter[status]=failed` → `{summary, rows[]}` | REQ-NTF-002 | ○ |
| GET | `/admin/notification-templates` | admin | Templates per channel and locale | `?filter[channel]=` → `[template]` | REQ-NTF-001 | ● |
| PUT | `/admin/notification-templates/{key}` | admin | Upsert a template | `{channel, locale, subject?, body, variables[]}` → `{template}` | REQ-NTF-001 | ● |
| GET | `/admin/notification-orders` | admin | Paid notification purchases awaiting approval | `?filter[status]=pending_payment` → `[job_row]` | REQ-NTF-003 | ○ |
| GET | `/admin/tickets` | admin | Support ticket queue with SLA | `?filter[status]=&filter[department]=&filter[assignee]=` → `[ticket_row]` | REQ-MSG-003 | ● |
| POST | `/admin/tickets/{ticket}/assign` | admin | Assign to an agent | `{assignee_id}` → `{ticket}` | REQ-MSG-003 | ● |
| POST | `/admin/tickets/{ticket}/reply` | admin | Reply as support | `{body, media_uuids[]?, status?}` → `201 {message}` | REQ-MSG-003 | ● |
| POST | `/admin/tickets/{ticket}/status` | admin | Change status (resolve/close/reopen) | `{status, note?}` → `{ticket}` | REQ-MSG-003 | ○ |
| GET | `/admin/conversations/{conversation}` | admin | Inspect a conversation (audited) | — → `{conversation, participants[], messages[]}` | REQ-MSG-001 | ○ |
| POST | `/admin/notifications/broadcast-all` | admin | Message every level (appendix 7 clause 8) | `{channel, title, body, roles[]?, scheduled_at?}` → `201 {job}` | REQ-NTF-002 | ○ |

### 8.9 Reviews, community and admin operations

| Method | Path | Auth | Purpose | Request → Response | REQ | P0 |
|---|---|---|---|---|---|---|
| GET | `/admin/reviews` | admin | Review moderation queue | `?filter[status]=&filter[subject_type]=` → `[review_row]` | REQ-RVW-003 | ● |
| POST | `/admin/reviews/{review}/decide` | admin | Publish/reject/hide with a reason | `{decision, reason?}` → `{review}` | REQ-RVW-003 | ● |
| GET | `/admin/reviews/criteria` | admin | Review criteria (5–10 per subject) | `?applies_to=` → `[criterion]` | REQ-RVW-001 | ○ |
| PUT | `/admin/reviews/criteria` | admin | Replace criteria for a subject type | `{applies_to, criteria:[{code, name_fa, min_score, max_score, weight}]}` → `{criteria[]}` | REQ-RVW-001 | ○ |
| GET | `/admin/classifieds` | admin | Classifieds moderation | `?filter[status]=&filter[category]=` → `[listing_row]` | REQ-CLS-004 | ○ |
| POST | `/admin/classifieds/{listing}/decide` | admin | Approve/reject/remove | `{decision, reason?}` → `{listing}` | REQ-CLS-004 | ○ |
| POST | `/admin/classifieds/categories` | admin | Create the taxonomy required before posting | `{parent_id?, name_fa, slug, requires_approval, max_images}` → `201 {category}` | REQ-CLS-001 | ○ |
| GET | `/admin/patogh/services` | admin | Service listings awaiting approval | `?filter[status]=pending_review` → `[service_row]` | REQ-PTG-003 | ○ |
| POST | `/admin/patogh/services/{service}/decide` | admin | Approve/reject a service listing | `{decision, reason?}` → `{service}` | REQ-PTG-003 | ○ |
| GET | `/admin/tenders` | admin | All tenders and auctions | `?filter[status]=&filter[kind]=` → `[tender_row]` | REQ-TND-001 | ○ |
| POST | `/admin/tenders/{tender}/cancel` | admin | Cancel a tender with a reason | `{reason}` → `{tender}` | REQ-TND-001 | ○ |
| GET | `/admin/settings` | admin | Settings by group (secrets masked) | `?group=orders` → `[{key, value, value_type, is_secret, description}]` | REQ-ADM-004 | ● |
| PATCH | `/admin/settings` | admin | Update settings (audited) | `{settings:[{key, value}]}` → `{settings[]}` | REQ-ADM-004 | ● |
| GET | `/admin/audit-logs` | admin | Audit trail with filters | `?filter[action]=&filter[actor_id]=&filter[occurred_at][between]=` → `[audit_row]` | REQ-ADM-005 | ● |
| POST | `/admin/exports` | admin | Queue an export (Excel/CSV) | `{type, filters}` → `202 {export_id}` | REQ-ADM-003 | ● |
| GET | `/admin/exports/{export}` | admin | Export status and signed download link | — → `{export, url?, expires_at?}` | REQ-ADM-003 | ● |
| GET | `/admin/reports/marketers` | admin | Marketer activity report with Excel export | `?from=&to=` → `{rows[], totals}` | REQ-ADM-003 | ● |
| GET | `/admin/reports/stores` | admin | Active/inactive store report by period | `?from=&to=&filter[status]=` → `{rows[], totals}` | REQ-MER-006 | ● |
| GET | `/admin/reports/transactions` | admin | Transactions and settlement status report | `?from=&to=` → `{rows[], totals}` | REQ-ADM-002 | ● |
| GET | `/admin/feature-flags` | admin | Feature switches (deferred mobile, split payment) | — → `[flag]` | REQ-ADM-004 | ○ |
| PATCH | `/admin/feature-flags/{key}` | admin | Toggle a flag | `{is_enabled, rollout?}` → `{flag}` | REQ-ADM-004 | ○ |

---

## 9. Webhooks and provider callbacks

| Method | Path | Auth | Purpose | Request → Response | REQ | P0 |
|---|---|---|---|---|---|---|
| POST | `/webhooks/payments/{provider}` | webhook | Gateway payment callback (12 providers, one driver each) | raw provider body → `200 {status:"ok"\|"duplicate"\|"ignored"}` | REQ-PAY-002 | ● |
| POST | `/webhooks/shipping/{provider}` | webhook | Carrier tracking update | provider body → `200 {status:"ok"}` | REQ-SHP-002 | ● |
| POST | `/webhooks/sms/{provider}` | webhook | Delivery receipts and inbound SMS | provider body → `200 {status:"ok"}` | REQ-NTF-001 | ○ |
| POST | `/webhooks/whatsapp/{provider}` | webhook | WhatsApp message status (appendix 5 clause 26) | provider body → `200 {status:"ok"}` | REQ-NTF-001 | ○ |
| GET | `/webhooks/payments/{provider}/return` | public | Browser return URL after the gateway redirect (renders a result page) | query params → `302` to the SPA result route | REQ-PAY-002 | ● |

Rules: signature verification is mandatory; a `401` is returned for an invalid signature and
the event is stored with `signature_valid = 0`; responses are `200` even for events that are
ignored, so the provider stops retrying; all processing is queued.

---

## 10. Requirement index (REQ → contract source)

| REQ | Title | Source |
|---|---|---|
| REQ-IAM-001 | Registration with SMS verification | appendix 2 clause 8; appendix 3-3 clause 1 |
| REQ-IAM-002 | Login, token issue and revocation | appendix 1 clause 3; appendix 9 clause 3 |
| REQ-IAM-003 | Password recovery and change | appendix 3-1 clause 10 |
| REQ-IAM-004 | Secondary user level, admin-enabled | appendix 7 clause 6; appendix 12 clause 11 |
| REQ-IAM-005 | Admin panel on a separate path with 2FA | appendix 1 clause 7 |
| REQ-IAM-006 | Session/device revocation | appendix 9 clause 3 (access control) |
| REQ-USR-001 | Profile view and edit, SMS-confirmed changes | appendix 3-1 clause 10; appendix 5 clause 7 |
| REQ-USR-002 | Mobile change with SMS confirmation | appendix 3-1 clause 10 |
| REQ-USR-003 | Address book and default address | appendix 2 clause 7; appendix 5 clause 39 |
| REQ-USR-004 | Favourites (products, stores) | appendix 3-1 clause 6; appendix 12 clause 15 |
| REQ-USR-005 | Identity and business documents | appendix 6 clause 1; appendix 7 clause 1 |
| REQ-USR-007 | Data export and account deletion | appendix 9 clause 3 (data protection); CTR obligation |
| REQ-MER-001 | Store registration and profile (public form) | appendix 3-1 clause 5; appendix 6 clause 1 |
| REQ-MER-002 | Marketer on-site store registration with photos | appendix 5 clause 2; appendix 6 clauses 1–2, 7 |
| REQ-MER-003 | Duplicate prevention by national id, postal code, mobile | appendix 5 clause 3 |
| REQ-MER-004 | Five-minute marketer edit window, then admin approval | appendix 6 clause 9 |
| REQ-MER-005 | Admin approval/activation of merchants | appendix 7 clause 1 |
| REQ-MER-006 | Auto-inactivation of inactive stores and reactivation | appendix 4 clauses 23–24; appendix 5 clause 30 |
| REQ-MER-007 | Supplier panel, profile and nationwide branches | appendix 11 clauses 1, 3, 19 |
| REQ-MER-008 | Supplier as buyer-partner with a fixed discount | appendix 11 clause 2 |
| REQ-CAT-001 | Category browsing with dynamic filters and sorting | appendix 2 clause 3 |
| REQ-CAT-002 | Product page: gallery, video, specs, related, compare entry | appendix 2 clause 4 |
| REQ-CAT-003 | Compare up to five products | appendix 2 clause 5 |
| REQ-CAT-004 | Admin product form with per-level prices and attributes | appendix 7 clause 4 |
| REQ-CAT-005 | Search and suggestions | appendix 2 clause 2; appendix 9 clause 1 |
| REQ-CAT-006 | Bulk catalogue import | appendix 9 clause 4 |
| REQ-PRI-001 | Price resolution per user level and quantity | appendix 3-1 clause 8; appendix 7 clause 4; appendix 12 clause 1 |
| REQ-PRI-002 | Consumer price spread (manual or percentage) | appendix 12 clause 1 |
| REQ-PRI-003 | Volume bands and volume cash discounts | appendix 4 clauses 3, 11 |
| REQ-PRI-004 | Seller price editing and bulk change | appendix 11 clause 1 |
| REQ-STK-001 | Inventory management per warehouse | appendix 11 clause 1 |
| REQ-STK-002 | Stock availability and reservation | appendix 2 clause 3 (available filter); CTR integrity |
| REQ-STK-003 | Low-stock alerts | appendix 11 clause 1 |
| REQ-CRT-001 | Cart management, guest merge | appendix 2 clause 6 |
| REQ-ORD-001 | Order placement and split by seller | appendix 3-1 clause 7; appendix 5 clause 40 |
| REQ-ORD-002 | Order list, detail, tracking, timeline | appendix 2 clause 11; appendix 3-3 clause 6 |
| REQ-ORD-003 | Cancellation and refund | appendix 5 clause 15; appendix 11 clause 14 |
| REQ-ORD-004 | Seller accept/reject and fulfilment | appendix 5 clause 15; appendix 11 clauses 12, 14 |
| REQ-ORD-005 | Incomplete order report and resolution | appendix 11 clauses 12–13 |
| REQ-ORD-006 | Shipment with waybill and courier | appendix 11 clause 15 |
| REQ-ORD-007 | Delay registration, damage and delivery confirmation | appendix 11 clause 15; appendix 12 clause 8 |
| REQ-ORD-008 | Order notes with audio, image or file | appendix 11 clause 18; appendix 5 clause 41 |
| REQ-ORD-009 | Stop preparation / stop payment hold | appendix 4 clauses 15–16 |
| REQ-INV-001 | Invoice issued on payment | appendix 7 clause 2; appendix 4 clause 16 |
| REQ-INV-002 | Invoice list, detail and PDF | appendix 3-1 clause 7; appendix 3-2 clause 10 |
| REQ-INV-003 | Supplementary invoice for extra costs | appendix 11 clause 17 |
| REQ-INV-004 | Manual invoice creation | appendix 5 clause 44; appendix 7 clause 2 |
| REQ-INV-005 | Void an invoice with a reason | appendix 1 clause 10 (audit); CTR integrity |
| REQ-PAY-001 | Twelve gateways with activation | appendix 1 clause 5; appendix 13 clause 7 |
| REQ-PAY-002 | Online payment initiation and callback | appendix 2 clause 6; appendix 13 clause 7 |
| REQ-PAY-003 | Wallet payment and top-up | appendix 3-1 clause 4; appendix 12 clause 13 |
| REQ-PAY-004 | Refunds (wallet, IBAN, gateway) | appendix 5 clause 15; appendix 11 clause 14 |
| REQ-PAY-005 | Failed/returned transaction reporting | appendix 13 clause 6 |
| REQ-PAY-006 | Gateway reconciliation | appendix 13 clause 6 |
| REQ-FIN-001 | Wallet, statement and financial flow per level | appendix 3-1 clause 4; appendix 13 clause 5 |
| REQ-FIN-002 | Settlement request and statement | appendix 3-1 clause 4; appendix 3-2 clause 2 |
| REQ-FIN-003 | Settlement approval and payment | appendix 13 clauses 1–3 |
| REQ-FIN-004 | Filterable sales/finance reports | appendix 13 clauses 1–2 |
| REQ-FIN-005 | Excel export for bank group payment | appendix 13 clause 3 |
| REQ-FIN-006 | Manual charge/credit on an account | appendix 11 clause 16 |
| REQ-FIN-007 | IBAN capture and verification | appendix 5 clause 15 |
| REQ-CMP-001 | Commission plans A–D and conditions | appendix 6 clauses 3, 5–6 |
| REQ-CMP-002 | Commission accrual and entry listing | appendix 6 clause 3 |
| REQ-CMP-003 | Periodic commission statement | appendix 3-2 clause 2 |
| REQ-CMP-004 | Disable commission or change level | appendix 4 clause 22 |
| REQ-LOY-001 | Points per invoice and per product | appendix 4 clauses 1–2, 5 |
| REQ-LOY-002 | Tiered invoice discount (4 % / 5 % / fixed) | appendix 4 clauses 20–21; appendix 5 clause 16 |
| REQ-LOY-003 | Rewards catalogue and redemption | appendix 4 clause 7 |
| REQ-LOY-005 | Inactivity and violation penalties | appendix 4 clauses 6, 17; appendix 5 clause 14 |
| REQ-LOY-007 | Versioned rule sets and replay | appendix 4 clause 3; canonical §5 |
| REQ-MKT-001 | Personal QR, code and referral link | appendix 5 clause 1; appendix 6 clause 4 |
| REQ-MKT-003 | Attribution reporting | appendix 5 clause 1; appendix 6 clause 10 |
| REQ-PRM-001 | Promotion builder | appendix 4 clauses 10–12 |
| REQ-PRM-002 | Coupons and bons | appendix 5 clause 43; appendix 12 clause 4 |
| REQ-PRM-003 | Coupon application in the cart | appendix 2 clause 6 |
| REQ-PRM-004 | Festivals and special offers on the home page | appendix 11 clause 4 |
| REQ-PRM-005 | Standing per-user discount | appendix 4 clause 21 |
| REQ-SHP-001 | Shipping method selection and cost | appendix 5 clause 40; appendix 12 clause 12 |
| REQ-SHP-002 | Shipment creation and tracking | appendix 5 clause 23; appendix 11 clause 15 |
| REQ-SHP-003 | Free shipping zones | appendix 12 clause 12 |
| REQ-SHP-004 | Carrier and method administration | appendix 11 clause 15 |
| REQ-RTN-001 | Return request inside the window | appendix 12 clause 14 |
| REQ-RTN-002 | Return approval, receipt and refund | appendix 12 clause 14; appendix 11 clause 14 |
| REQ-RTN-003 | Complaints and settlement freeze | appendix 4 clauses 15, 18; appendix 5 clause 32 |
| REQ-RTN-004 | Dispute arbitration | appendix 4 clause 15 (تعیین تکلیف) |
| REQ-RTN-005 | Violation reports (وارن) and negative score | appendix 4 clauses 18–19; appendix 5 clause 14 |
| REQ-TND-001 | Tender/auction creation and listing | appendix 5 clause 6; appendix 3-3 clause 5; appendix 11 clause 10 |
| REQ-TND-002 | Bid submission and withdrawal | appendix 11 clause 10 |
| REQ-TND-003 | Supplier invitation to a tender | appendix 5 clause 37 |
| REQ-TND-004 | Award a tender | appendix 5 clause 6 |
| REQ-CLS-001 | Admin-defined classifieds categories | appendix 5 clause 4 |
| REQ-CLS-002 | Post and manage a classified | appendix 5 clause 4 |
| REQ-CLS-003 | Browse and search classifieds | appendix 5 clause 4 |
| REQ-CLS-004 | Classifieds moderation and reports | appendix 7 clause 7 |
| REQ-PTG-001 | Local service listing (پتوق) | appendix 5 clauses 5, 13 |
| REQ-PTG-002 | Service request and quote | appendix 5 clause 13 |
| REQ-PTG-003 | Admin approval of service listings | appendix 5 clause 7 |
| REQ-SMP-001 | Sample/catalogue request | appendix 5 clause 38; appendix 3-1 clause 9 |
| REQ-SMP-002 | Supplier response to a sample request | appendix 11 clause 9 |
| REQ-MSG-001 | Persistent chat with attachments | appendix 5 clause 28 |
| REQ-MSG-003 | Tickets to admin and back | appendix 3-1 clause 7; appendix 7 clause 8 |
| REQ-MSG-004 | Chat only with authorised users | appendix 3-2 clause 5 |
| REQ-NTF-001 | Transactional notifications (SMS, email, push, in-app, WhatsApp) | appendix 5 clause 26; appendix 7 clause 3 |
| REQ-NTF-002 | Custom/bulk notification with audience and schedule | appendix 5 clause 9; appendix 11 clause 6 |
| REQ-NTF-003 | Paid notification purchase | appendix 5 clause 12 |
| REQ-NTF-004 | Notification preferences | appendix 9 clause 3 (spam control) |
| REQ-NTF-005 | SMS credits with fee or free per admin setting | appendix 11 clause 7 |
| REQ-CMS-001 | Blog with categories and SEO | appendix 2 clause 10 |
| REQ-CMS-002 | Static pages, FAQ, contact | appendix 2 clauses 9, 11 |
| REQ-CMS-003 | Banners, slideshow and supplier banner requests | appendix 2 clause 12; appendix 11 clauses 5, 8 |
| REQ-CMS-004 | Admin-managed menus and footer | appendix 2 clause 12; appendix 7 clause 9 |
| REQ-CMS-005 | Surveys | appendix 5 clause 10 |
| REQ-CMS-006 | Contact form with map | appendix 2 clause 11 |
| REQ-RVW-001 | Product/supplier review with 5–10 parameters | appendix 4 clause 17; appendix 5 clause 11; appendix 12 clause 9 |
| REQ-RVW-003 | Review moderation | appendix 7 clause 7 |
| REQ-GEO-001 | Visit check-in with location and photos | appendix 6 clauses 1–2; appendix 14 clause 1 |
| REQ-GEO-002 | Live tracking of marketers | appendix 14 clause 2 |
| REQ-GEO-003 | Performance analysis and reports | appendix 14 clause 2; appendix 6 clause 11 |
| REQ-GEO-004 | Territory and route planning | appendix 6 clauses 4, 11 |
| REQ-ADM-001 | Member management (suspend, edit, approve) | appendix 7 clause 1 |
| REQ-ADM-002 | Financial management and reports | appendix 7 clause 2 |
| REQ-ADM-003 | Reports with Excel export | appendix 7 clause 10; appendix 13 clause 3 |
| REQ-ADM-004 | Settings, banners, gateways, general configuration | appendix 7 clause 9 |
| REQ-ADM-005 | Audit log of sensitive events | appendix 1 clause 10 |
| REQ-ADM-006 | Impersonation for support (read-only) | appendix 7 clause 1 (support) |
| REQ-CTR-004 | Technical documentation and API docs | appendix 1 clause 2 |

---

## 11. Coverage summary

| Surface | Endpoints | P0 |
|---|---|---|
| Public storefront (anonymous + token) | 39 | 18 |
| Authentication and account | 15 | 11 |
| Customer `/me` | 105 | 61 |
| Store panel | 65 | 38 |
| Supplier panel | 74 | 32 |
| Marketer panel | 33 | 22 |
| Admin panel | 185 | 114 |
| Webhooks / provider callbacks | 5 | 3 |
| **Total** | **521** | **299** |

- **299 P0 endpoints** cover the first sellable flow end to end: browse → cart → checkout →
  payment → invoice → seller fulfilment → shipment → delivery → settlement, plus identity,
  roles, store/supplier onboarding and the admin controls required to operate it.
- **222 P1/P2 endpoints** cover the contract features that ship after the core flow:
  tenders, classifieds, patogh, samples, paid notifications, supplier festivals, surveys,
  review moderation, route planning and secondary-role workflows.
- Every endpoint carries a REQ id that resolves in §10; the contract test in
  `tests/Contract/EndpointInventoryTest.php` fails if a route exists without a row here or a row
  exists without a route.

Notes on scope:

1. **Deferred surfaces are absent by design**: native app endpoints (appendix 8),
   split-payment selection (appendix 1 clause 5, `RB-PAY-01`), reservation/appointment module
   (separate contract), and multi-parcel shipments (`RB-SHP-01`). They are not stubs — they
   have no route.
2. **The web app uses the same endpoints** through its own session guard; Blade + Livewire
   call the same application services, so an endpoint is never "API-only" logic.
3. **Every admin endpoint is audited** where it mutates state; the audit is written in the same
   transaction (Data conventions §12).
4. **Idempotency** is required on the money paths listed in API guidelines §6; those rows are
   the ones with `POST` under `/pay`, `/refund`, `/settlements`, `/exports` (async, deduped by
   `type+filters`), and `/checkout`.

