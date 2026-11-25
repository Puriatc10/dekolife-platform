# Privacy and PII — Dekolife Platform

**Purpose.** Define what personal data Dekolife holds, why it is allowed to hold it, how long it keeps it,
who can see it, how it is masked and encrypted, and how a person's request to see or delete their data is
executed.
**Audience.** Engineering, the client (data controller), admin staff, the Orchestrator.
**Status.** Baseline v1 — must be reviewed by the client's legal counsel before go-live.

**Source:** contract `1405-01DK` article 4 clause 8 (protecting user data), article 7 (user data belongs to
the client), appendix 1 clause 10 (audit of sensitive events), appendix 6 clauses 1–2 and 10 (marketer
captures national ID, postal code, address, photos, location), appendix 5 clause 3 (search by national ID,
postal code, mobile), appendix 14 (location capture and tracking); decisions AD-06, NFR-PRIV-* from
`../requirements/non-functional-requirements.md`; threat analysis in `./threat-model.md`.

> **Legal caveat.** This is an engineering document, not legal advice. It applies the data-minimisation and
> purpose-limitation principles that Iranian e-commerce and consumer-protection law require in practice
> (قانون تجارت الکترونیک، قانون حمایت از حقوق مصرف‌کننده) and uses GDPR concepts only as an engineering
> benchmark, not as an applicable regime. The client, as data controller, must confirm the lawful basis and
> the retention periods with counsel; where it disagrees, the change is an addendum to this file.

---

## 1. Principles applied

| Principle | How it is enforced in the codebase |
|---|---|
| **Lawfulness, fairness** | every field in §3 has a purpose and a lawful basis; there is no field collected "for later" |
| **Purpose limitation** | data collected for onboarding a store is not reused for marketing; a new purpose needs a new consent and a new row in §3 |
| **Data minimisation** | national ID is collected only for store/supplier/marketer onboarding and invoicing, never for consumers browsing; the consumer signup form has 3 fields |
| **Accuracy** | profile edit with OTP confirmation; admin correction is audited; the person can correct their own data |
| **Storage limitation** | retention schedule in §6 with an enforcing scheduled job and a deletion counter in the audit log |
| **Integrity and confidentiality** | field-level encryption (§8), masking (§7), least-privilege access (§9), audit of every sensitive read |
| **Accountability** | every access to national ID / IBAN / identity documents writes an audit row; the retention job reports counts |

**Hard rules**

1. No field enters the database without a row in §3.
2. No PII in logs, queue payloads, error reports, URLs or analytics events.
3. No production data in a non-production environment (§12).
4. Masking is the default; an unmasked view is an explicit, audited action.

---

## 2. Classification levels

| Level | Label | Definition | Handling |
|---|---|---|---|
| **L1** | Public | intended for public display | no restriction; still integrity-controlled |
| **L2** | Internal | business data, not personal | role-based; export audited |
| **L3** | Confidential (personal) | identifies a person or a business owner | masked in UI by default, encrypted in transit, access logged for bulk reads |
| **L4** | Restricted (sensitive) | national ID, identity documents, IBAN, precise location, credentials | encrypted at rest, masked everywhere except an audited reveal, never exported in bulk |

---

## 3. Data inventory

Every stored personal field. `L` is the classification, `Ret.` the retention (see §6).

| # | Data element | Table / location | Purpose | Lawful basis | L | Encryption | Ret. |
|---|---|---|---|---|---|---|---|
| 1 | Full name | `usr_users`, `mer_merchants` | account, invoice, delivery | contract performance | L3 | disk | R1/R2 |
| 2 | Mobile number | `usr_users.mobile`, `mer_*` | login, OTP, order notifications | contract performance | L3 | disk | R1 |
| 3 | **National ID (کد ملی)** | `mer_merchants.national_id`, `iam_identity_verifications` | legal identity of a store/supplier/marketer, invoicing, duplicate prevention | legal obligation + contract | **L4** | **field-level** | R3 |
| 4 | Legal document (ثبت شرکت / جواز کسب) | `mer_documents` (file) | supplier/store verification | legal obligation | **L4** | file-encrypted, private disk | R4 |
| 5 | Postal code (کد پستی) | `mer_merchants`, `usr_addresses` | delivery, duplicate detection | contract performance | L3 | disk | R1/R2 |
| 6 | Address (text) | `usr_addresses`, `mer_merchants` | delivery, field visits | contract performance | L3 | disk | R2 |
| 7 | Store coordinates (lat/lng) | `mer_merchants.location` | map, visit routing, delivery | contract performance | L3 | disk | R2 |
| 8 | **Marketer live position** | `geo_positions` | visit proof, live tracking (پیوست ۱۴) | **consent** | **L4** | disk | **R5 (90 d)** |
| 9 | IBAN / Sheba (شبا) | `fin_payout_accounts` | settlement, refund | contract + legal obligation | **L4** | **field-level** | R1 |
| 10 | Card / terminal identifiers | `pay_gateway_configs` | payments | contract | **L4** | **field-level** | R1 |
| 11 | Identity photos (owner, storefront, business card) | private disk | onboarding evidence | contract | **L4** | file-encrypted | R4 |
| 12 | Order history | `ord_*`, `inv_*` | contract, warranty, accounting | contract + legal obligation | L3 | disk | R1 |
| 13 | Invoice / financial records | `fin_*`, `inv_*` | accounting, tax | **legal obligation** | L3 | disk | R1 (10 y) |
| 14 | Points / wallet balance | `loy_*`, `fin_*` | loyalty programme | contract | L3 | disk | R1 |
| 15 | Messages and tickets | `msg_*` | support, dispute evidence | contract | L3 | disk | R2 |
| 16 | Reviews and ratings | `rvw_*` | public product feedback | consent (on submit) | L2 | disk | R6 |
| 17 | IP address + user agent | `adm_audit_logs`, `iam_login_attempts` | security, audit | legitimate interest (security) | L3 | disk | R7 |
| 18 | Device fingerprint (hashed) | `iam_devices` | fraud detection (self-referral) | legitimate interest (fraud) | L3 | hash only | R2 |
| 19 | Birth date | `usr_profiles.birth_date` | birthday greeting (پیوست ۱۲ بند ۷) | consent | L3 | disk | R2 |
| 20 | Geo consent record | `iam_consents` | proof of consent | legal obligation | L3 | disk | R8 |
| 21 | OTP codes | `iam_otp_tokens` | authentication | contract | L4 | hashed | **10 min** |
| 22 | Session records | `sessions` | authentication | contract | L4 | disk | 12 h |
| 23 | Support call recordings / attachments | private disk | dispute evidence | consent (recorded notice) | L4 | file-encrypted | R2 |

**Not collected, on purpose:** father's name, marital status, education, religion, income, bank statement,
card CVV2, passport number, photo of the consumer's ID, browsing history beyond anonymised analytics.
Any request to add one goes through the §1 hard rule 1 and needs a lawful basis in writing.

---

## 4. Consent

### 4.1 Where consent is the basis

| Purpose | Consent needed | Withdrawable |
|---|---|---|
| Marketer location capture and live tracking (پیوست ۱۴) | yes, explicit, at first use of the field app/panel | yes, in the panel and by SMS |
| Marketing SMS / WhatsApp / push beyond transactional messages | yes, opt-in, separate from registration | yes, in the profile and via a STOP keyword |
| Birthday greeting | yes, tied to entering a birth date | yes, by clearing the birth date |
| Review publication with the reviewer's display name | yes, on submit | yes, by deleting the review |
| Non-essential analytics / heatmaps | yes | yes |

### 4.2 Geo-tracking consent — the wording (binding)

Persian, shown in a modal before the first location capture, with two buttons: «موافقم» / «فعلاً نه». The
"no" path keeps the panel usable; visits can be registered manually and approved by an admin.

> **متن رضایت‌نامه رهگیری موقعیت مکانی**
>
> برای ثبت بازدید فروشگاه‌ها و تأیید فعالیت شما، سامانه در طول «جلسه بازدید» موقعیت مکانی شما را ثبت
> می‌کند. این ثبت فقط وقتی انجام می‌شود که خودتان جلسه بازدید را شروع کرده باشید و در همان لحظه نشانگر
> «رهگیری فعال» در بالای صفحه نمایش داده می‌شود. موقعیت شما فقط برای اثبات بازدید، مسیریابی و گزارش
> عملکرد استفاده می‌شود و در اختیار فروشگاه‌ها یا سایر بازاریابان قرار نمی‌گیرد. موقعیت‌های خام پس از
> ۹۰ روز حذف می‌شوند و فقط خلاصه بازدید (فروشگاه، تاریخ، مدت) باقی می‌ماند. شما می‌توانید هر زمان با
> زدن کلید «توقف رهگیری» یا از بخش تنظیمات حساب، این اجازه را لغو کنید؛ لغو اجازه، ثبت بازدیدهای بعدی
> را به حالت دستی و تأیید مدیر منتقل می‌کند و حقوق و پورسانت گذشته شما را از بین نمی‌برد.

English working translation (not shown to users):

> To record store visits and confirm your activity, the system records your location **during a visit
> session only**. Recording happens only after you start a visit session yourself, and a "tracking active"
> indicator is shown at the top of the screen while it is on. Your location is used solely to prove visits,
> route you, and produce performance reports; it is never shared with stores or other marketers. Raw
> positions are deleted after 90 days, leaving only a visit summary (store, date, duration). You may
> withdraw this permission at any time via "stop tracking" or account settings; withdrawal moves future
> visit recording to a manual, admin-approved mode and does not remove commission already earned.

### 4.3 Consent record

`iam_consents` stores: `user_id`, `purpose` (`geo_tracking`, `marketing_sms`, `birthday`, `analytics`),
`version` (the wording version, e.g. `geo-v1`), `granted_at`, `withdrawn_at`, `ip`, `user_agent`, `source`
(`panel_modal`, `sms_stop`, `profile_toggle`). A wording change increments the version and requires
re-consent for the geo purpose; existing grants remain valid for the old version and are reported.

### 4.4 Rules

- Consent is never a condition of registration, employment, or commission payment.
- Pre-ticked boxes are forbidden; the default is off.
- Withdrawal must be as easy as granting: one action, no support ticket, no retention of the old permission
  beyond the audit record.
- Transactional messages (order status, OTP, invoice) are not marketing and do not need marketing consent —
  but they must not carry promotional content.

---

## 5. Purpose limitation map

| Data | Allowed uses | Forbidden uses without new consent |
|---|---|---|
| Mobile | login, OTP, order status, invoice | marketing campaigns, sharing with other sellers |
| National ID | identity verification, invoicing, duplicate prevention | marketing segmentation, credit scoring, sharing |
| Address | delivery, installation visit | mapping a store's customer base, resale |
| Marketer location | visit proof, routing, performance report | surveillance outside a session, ranking by dwell time without disclosure |
| Order history | fulfilment, warranty, accounting, personalised recommendations (opt-out available) | selling to a third party, publishing per-customer spend |
| Wallet / points | programme operation | transfer to another person except the contract's membership-transfer rule (پیوست ۵ بند ۳۹, admin-approved and audited) |

---

## 6. Retention schedule

| ID | Data | Retention | Trigger | Enforcement |
|---|---|---|---|---|
| R1 | Financial records: invoices, ledger, payments, settlements, commission | **10 years** | creation | never deleted; archive partition after 2 years |
| R2 | Account and operational PII: profile, addresses, orders (non-financial part), messages, devices | **5 years after last activity**, then pseudonymised | last login or last order | `privacy:anonymise-inactive` |
| R3 | National ID | kept while the merchant is active + **5 years**; on account closure, pseudonymised but the hash is kept for duplicate prevention | closure + 5 y | `privacy:anonymise-inactive` |
| R4 | Identity documents and photos | **12 months after verification**, then deleted (the verification decision and its audit row remain) | verification date | `privacy:purge-documents` |
| R5 | Raw geo positions | **90 days** | capture | `geo:purge-positions` (daily) |
| R6 | Reviews | life of the product + 2 years | product retirement | `privacy:purge-reviews` |
| R7 | Audit log, login attempts, IP logs | **5 years** (audit), 90 days (login attempts) | event | `audit:archive` |
| R8 | Consent records | life of the account + **5 years** (proof) | withdrawal or closure | retained with the audit archive |
| R9 | Application logs | **30 days** hot, 12 months cold, no PII | write | log rotation |
| R10 | Backups | 30 d / 12 m / 5 y per NFR-DR-007; deleted copies age out with the backup, not individually | backup date | storage lifecycle policy |
| R11 | OTP | 10 minutes | issue | `iam_otp_tokens` cleanup |
| R12 | Cart / abandoned order | 30 days | last change | `carts:prune` |

**Rules**

1. A retention job never deletes a row that a legal hold (open dispute, tax audit, court order) covers; the
   hold is an explicit flag with a reason and an owner.
2. Deletion is **pseudonymisation** where a financial record must survive: the person's identifying fields
   are replaced with a stable pseudonym, the ledger link stays intact, and the invoice number never changes.
3. Every run writes one audit row: job name, table, rows affected, cutoff date.

---

## 7. Masking rules

### 7.1 UI masking by role

| Data | Owner sees | Store / supplier | Marketer | Admin (support) | Admin (finance) | Export |
|---|---|---|---|---|---|---|
| Mobile | full | full for own customers; masked for others | **masked** `0912***4567`, reveal button (audited) | masked + reveal (audited) | masked | masked |
| National ID | full (own record) | own only | **masked** `******2419` | masked + reveal (audited, reason required) | masked | **never** |
| Postal code | full | full for own orders | masked `1234******` | masked + reveal | masked | masked |
| Address | full | full for own orders | street masked, city visible | full (support need) | city only | masked |
| IBAN | own, masked `IR**…1234` with full on demand | own | own, masked | masked | full (payout) | masked except bank batch |
| Store coordinates | full | own | full for own network | full | city only | no |
| Marketer position | own | **never** | own only | aggregated only | no | no |
| ID documents | own | own | own uploads | thumbnail + audited full view | no | **never** |
| Order amounts | own | own | commission of own network | own-scope | full | full |

### 7.2 Rules

1. **Masking is server-side.** The full value is never rendered into HTML and hidden with CSS. A masked view
   returns only the masked string from the controller/resource.
2. A reveal is a POST to a dedicated endpoint, writes a `pii.reveal` audit row (actor, target, field,
   reason, IP) and returns the value once. It is rate-limited (30/hour/user) and locked after 500/day.
3. Admin lists never show a full national ID, even for a super-admin. Bulk reveal does not exist.
4. PDFs and invoices show the last 4 digits of the national ID, never the full value, unless the document is
   a legally required tax invoice, in which case the full value is printed and the PDF is watermarked with
   the requester's identity and the generation timestamp.

### 7.3 Logs and telemetry

| Never logged | Replacement |
|---|---|
| national ID, IBAN, card/terminal id | `sha256(value)` prefix or `******2419` |
| mobile | `+98912****567` |
| OTP, password, token, session id | `<redacted>` |
| address | city + province only |
| geo coordinates | rounded to 2 decimals in operational logs, or omitted |
| full request body on auth routes | route + status only |

Enforcement: a `RedactSensitive` Monolog processor with a field-name deny-list
(`national_id`, `iban`, `sheba`, `mobile`, `phone`, `otp`, `password`, `token`, `authorization`, `card`,
`cvv2`, `lat`, `lng`), plus a unit test that feeds a payload containing all of them and asserts none appears
in the output (TM-SHR-010). Exception messages are filtered too: a `ValidationException` may echo an invalid
national ID back to the user, but the log processor strips it.

### 7.4 Exports and error reports

- Excel/CSV exports mask L4 columns; a full-value export requires a separate permission, a reason field and
  a second admin approval, and is watermarked.
- Error tracker payloads (`beforeSend`) strip request bodies on auth/payment routes, cookies and headers
  except `user-agent`.
- Screenshots in bug reports are reviewed before attaching; the support template tells staff to use the
  masked view.

---

## 8. Encryption

| Layer | Mechanism | Detail |
|---|---|---|
| In transit | TLS 1.2+ (1.3 preferred), HSTS 1 year | nginx termination, no plain-HTTP route, internal service calls over the private network |
| Database at rest | host/volume encryption | provided by the client's infrastructure; documented in the ops runbook |
| **Field-level** | Laravel `encrypted` cast (AES-256-GCM via `APP_KEY`) | `mer_merchants.national_id`, `mer_merchants.national_id_hash` (blind index for duplicate search), `fin_payout_accounts.iban`, `pay_gateway_configs.credentials`, `mer_documents.document_number` |
| Blind index | `hash_hmac('sha256', normalised_value, APP_KEY_INDEX)` | allows duplicate detection (پیوست ۵ بند ۳) without decrypting rows; the index is not reversible and is keyed separately from the cipher |
| Files | encrypted at rest on the private disk | identity documents, business licences, support attachments |
| Secrets | env/config, never in git | gateway keys, SMS tokens; rotation runbook with a 90-day cadence for gateway credentials |
| Backups | AES-256 client-side, key outside the storage account | restores require the key; the hosting operator cannot read them |
| Key management | `APP_KEY`, `APP_KEY_INDEX`, backup key held by the client | documented custody, rotation procedure with re-encryption command, and a tested restore after rotation |

**Why a blind index and not a plain unique index:** duplicate detection needs an equality search on the
national ID, but a plain column would store the value in clear. The HMAC index gives equality without
disclosure; a full table scan of hashes cannot be reversed without the key.

---

## 9. Access matrix

`V` = view masked · `F` = view full · `A` = view full only via audited reveal · `E` = export · `—` = no
access. "Admin" rows assume the admin guard **and** a 2FA-verified session (NFR-SEC-002).

| Data category | Consumer | Store | Supplier | Marketer | Support admin | Finance admin | Content admin | System job |
|---|---|---|---|---|---|---|---|---|
| Own profile | F | F | F | F | A | A | A | F |
| Own orders | F | F | F | F (own network) | A | F | — | F |
| Other customers' contact | — | V (own orders) | V (own orders) | V | A | V | — | — |
| National ID | — | F (own) / — | F (own) / — | V + A | A | V | — | F |
| Identity documents | — | F (own) | F (own) | F (own upload) | A | — | — | F |
| IBAN / payouts | F (own) | F (own) | F (own) | F (own) | V | F | — | F |
| Wallet and points | F (own) | F (own) | — | F (own) | A | F | — | F |
| Ledger | — | own statement | own statement | own statement | — | F | — | F |
| Audit log | — | — | — | — | A (read-only) | A (read-only) | — | F (write) |
| Geo positions | — | — | — | own only | aggregated | — | — | F |
| Reports | own | own | own | own | V | F + E | V | F |
| CMS content | — | — | — | — | — | — | F | F |

Rules: the matrix is enforced by policies, not by menu visibility (canonical §5). A role with no cell gets
403. Adding a role means adding a column here **and** a policy test — `RouteCoverageTest` fails otherwise.

---

## 10. Data-subject requests (access, correction, export, deletion)

### 10.1 Channels and timelines

| Request | Channel | Acknowledge | Complete | Fee |
|---|---|---|---|---|
| Access / copy of data | panel → "حریم خصوصی" → «دریافت اطلاعات من», or support ticket | 3 working days | **≤ 30 days** | free |
| Correction | panel profile (self-service where possible), else ticket with evidence | 3 working days | ≤ 10 working days | free |
| Export (machine-readable) | same as access | 3 working days | ≤ 30 days | free |
| Deletion | ticket with identity verification (OTP to the registered mobile) | 3 working days | ≤ 30 days | free |
| Withdraw consent | panel toggle or SMS STOP | immediate | immediate | free |

### 10.2 Procedure — access / export

1. Verify identity: OTP to the registered mobile **and** a logged-in session. A third party needs a written
   authorisation plus ID — handled by support, not by the panel.
2. Run `php artisan privacy:export --user=<id> --format=json` (and `--format=csv` for non-technical readers).
   The package contains: profile, addresses, orders, invoices, payments, wallet and points ledger, messages
   (own side), reviews, consents, and a list of audit entries about the person.
3. Exclude: other people's data (a message's other participant, a supplier's internal notes), fraud flags,
   and any legal-hold reference.
4. Deliver through a signed URL valid 72 h, or upload to the panel's private area. Never by plain email
   attachment.
5. Write an audit row `privacy.export` with requester, target, format, row counts.
6. Log the request in the register (`documents/requests` table) with the completion date — the count is
   reported monthly.

### 10.3 Procedure — deletion

1. Verify identity as above; confirm the consequence in writing (order history lost from the panel, warranty
   still honoured through the invoice number).
2. Check legal holds: open dispute, unsettled commission, tax audit → deletion is **deferred** with a written
   reason and a review date.
3. Run `php artisan privacy:erase --user=<id> --dry-run` first; the dry run lists every table and row count
   that will be affected.
4. Execute: pseudonymise L3/L4 fields (name → `حساب حذف‌شده`, mobile → random unused, address deleted,
   national ID → null with the HMAC hash retained for duplicate prevention), delete files, revoke sessions
   and tokens, cancel marketing consent.
5. **Retain**: invoice and ledger rows (10 years, legal obligation), the audit trail of the deletion itself,
   and the aggregate order totals needed for accounting. These keep the pseudonym, not the person.
6. Write `privacy.erase` with the counts per table; notify the person in writing within 30 days with what was
   deleted and what was retained and why.

### 10.4 Refusals

A request may be refused only for: an unverifiable identity, an active legal hold, a request that would
expose another person's data, or a manifestly abusive repetition (more than 2 access requests in 30 days).
A refusal is written, reasoned, and recorded; it is never silent.

---

## 11. Third parties and processors

| Processor | Data shared | Purpose | Requirement |
|---|---|---|---|
| Payment gateways (12) | order amount, payer identity as the PSP requires, callback metadata | payment | merchant agreement + the PSP's own privacy terms; only the minimum fields sent |
| SMS provider | mobile, message body (no national ID, no IBAN) | OTP and notifications | DPA-equivalent clause; sender ID registered |
| WhatsApp Business API | mobile, template text | notifications | opt-in only, per §4 |
| Email provider | email, message body | notifications, exports link (never the file) | TLS, no PII in subject |
| Hosting / infrastructure | everything at rest | operation | confidentiality clause (ماده ۴ بند ۶), encrypted backups, no data use for their own purposes |
| Error tracking | stack traces, route, user id hash | reliability | PII scrubbing before send |

A new processor requires: a row here, a purpose, the data list, and the client's written approval — because
the client is the controller (ماده ۷).

---

## 12. Non-production environments

1. Production dumps are never copied to staging or local (contract confidentiality, ماده ۴ بند ۶).
2. Staging is built from `demo:seed` with clearly fictional data: mobiles in the `091200000xx` range, national
   IDs that fail the checksum by design, addresses marked "خیابان تست".
3. If a production-shaped dataset is ever needed for a performance test, it is generated through the
   anonymising exporter (`privacy:export --anonymise`) that replaces every L3/L4 value with a deterministic
   pseudonym before it leaves production.
4. Screenshots used in tickets/UAT evidence must come from the masked view or from staging.

---

## 13. Incident and breach handling

| Step | Timing | Action |
|---|---|---|
| Detect | — | alert, report, or audit anomaly (`privacy.export` spike, bulk reveal spike, unknown admin login) |
| Contain | ≤ 1 h | revoke sessions/tokens, disable the account, rotate the exposed key, block the vector |
| Assess | ≤ 24 h | what data, how many people, which classification, is it reversible |
| Notify the client | **≤ 72 h** | written report: facts, scope, containment, remediation; the client decides on notifying data subjects and any authority, as controller |
| Notify data subjects | client's decision | template prepared in advance in Persian |
| Record | — | incident log with timeline, root cause, corrective actions, and a regression test |
| Learn | ≤ 14 days | post-mortem; a new test or control in `./security-checklist.md` |

---

## 14. Privacy checklist for every PR

- [ ] No new personal field without a §3 row (purpose + lawful basis + retention + classification).
- [ ] New field is masked in every list view and never rendered in full into HTML.
- [ ] No PII in logs, job payloads, URLs, analytics or error reports.
- [ ] New export masks L4 columns and writes an audit row.
- [ ] New third-party call sends the minimum fields; no PII in a query string.
- [ ] Retention job updated if the new data has a different life.
- [ ] Policy updated in §9 and a negative test added.
- [ ] Consent copy reviewed if a new purpose is introduced.

---

## 15. Changelog

| Version | Date | Change |
|---|---|---|
| 1.0 | 2026-11-25 | 23-element data inventory, 4 classification levels, geo-consent wording (fa/en), 12 retention rules, role masking matrix, encryption and blind-index design, DSAR procedures, processor list, breach runbook. |
