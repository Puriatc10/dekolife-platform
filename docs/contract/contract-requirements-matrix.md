# Contract Requirements Traceability Matrix — Dekolife Platform

**Purpose:** prove that every numbered clause of the contract and its fourteen appendices is answered by at least one requirement, and expose the clauses that are ambiguous or internally contradictory.
**Audience:** delivery manager, QA lead, product owner — the artefact used at clause-level acceptance.
**Status:** baseline v1, generated from the same dataset as `docs/requirements/functional-requirements.md`.

**Source:** `docs/contract/contract-extracted-text.txt` (pages 1–11 of the signed PDF) and `docs/canonical-context.md` (§4 requirement IDs).

---

## 1. Coverage summary

| Appendix / article | Subject | Clauses | Requirements | Specified | Ambiguous | Conflicting | Deferred |
|---|---|---:|---:|---:|---:|---:|---:|
| پیوست ۱ | معماری، فناوری و زیرساخت | ۱۰ | 17 | 16 | 1 | 0 | 0 |
| پیوست ۲ | صفحات و تجربه کاربری (UX/UI) | ۱۲ | 37 | 37 | 0 | 0 | 0 |
| پیوست ۳ | سطوح کاربری و امکانات هر سطح | ۳۳ | 37 | 37 | 0 | 0 | 0 |
| پیوست ۴ | سیستم امتیازدهی، پاداش پلکانی و تخفیف | ۲۴ | 32 | 28 | 3 | 1 | 0 |
| پیوست ۵ | قابلیت‌های اختصاصی درخواستی کارفرما | ۴۵ | 75 | 75 | 0 | 0 | 0 |
| پیوست ۶ | پنل حرفه‌ای بازاریاب | ۱۲ | 14 | 12 | 2 | 0 | 0 |
| پیوست ۷ | پنل مدیریت سایت (ادمین) | ۱۰ | 42 | 41 | 1 | 0 | 0 |
| پیوست ۸ | اپلیکیشن موبایل (اندروید + PWA + iOS) | ۸ | 7 | 2 | 0 | 0 | 5 |
| پیوست ۹ | سئو، بهینه‌سازی، امنیت و برندسازی | ۶ | 6 | 6 | 0 | 0 | 0 |
| پیوست ۱۰ | تحویل، آزمون و آموزش | ۶ | 6 | 6 | 0 | 0 | 0 |
| پیوست ۱۱ | پنل تأمین‌کنندگان و عرضه‌کنندگان کالا | ۲۰ | 29 | 29 | 0 | 0 | 0 |
| پیوست ۱۲ | پلن مصرف‌کننده و باشگاه مشتریان | ۱۶ | 33 | 32 | 0 | 1 | 0 |
| پیوست ۱۳ | گزارش‌گیری مالی، تسویه و اتصال بانکی | ۷ | 20 | 20 | 0 | 0 | 0 |
| پیوست ۱۴ | قابلیت‌های مدیریت بازاریابان | ۲ | 5 | 4 | 1 | 0 | 0 |
| ماده ۱–۱۶ | تعهدات قراردادی (CTR) | ۱۶ | 30 | 29 | 0 | 0 | 1 |
| **Total** | | **۲۲۷** | **390** | **374** | **8** | **2** | **6** |

The status columns count requirements by their primary clause; a requirement that satisfies a second, repeated clause is counted once, in the appendix of its primary clause. `Deferred` is always the mobile-native work of پیوست ۸ (RB-MOB-01) plus post-warranty annual support.

Clause count reconciliation: پیوست ۱–۱۴ hold 182 numbered clauses; پیوست ۳ is five user levels carrying 28 sub-items, and پیوست ۵ ends with a scope note — the matrix therefore has 211 rows. One clause can be satisfied by several requirements (a compound clause is split) and one requirement can satisfy a second clause that repeats the same obligation (marked in *Notes*).

**Appendix count:** the extracted contract numbers fourteen appendices; `canonical-context.md` says thirteen. پیوست ۱۴ (`قابلیت‌های مدیریت بازاریابان`, 2 clauses) sits at the tail of the extraction in a reversed block and is included here — see notes on pages 11 / rows ۱۴-۱ and ۱۴-۲.

---

## 2. Matrix

### پیوست ۱ — معماری، فناوری و زیرساخت

| Clause | Clause title (English) | Requirement IDs | Status | Notes / ambiguity |
|---|---|---|---|---|
| پیوست ۱ / بند ۱ | Laravel + MySQL three-layer MVC with modular structure | `REQ-CTR-005` | Specified | — |
| پیوست ۱ / بند ۲ | Code documentation set: README, API reference, schema description | `REQ-CTR-006` | Specified | — |
| پیوست ۱ / بند ۳ | Responsive layout for mobile, tablet and desktop | `REQ-CTR-007` | Specified | — |
| پیوست ۱ / بند ۴ | Installable PWA with icon, splash screen and smart caching | `REQ-CTR-008` | Specified | — |
| پیوست ۱ / بند ۵ | Twelve gateways, dual gateway | `REQ-CTR-009`, `REQ-PAY-001`, `REQ-PAY-002` | Ambiguous | «استفاده هم‌زمان از دو درگاه در یک تراکنش» با مفهوم تراکنش واحد بانکی سازگار نیست — AMB-PAY-001; split into 3 requirements; statuses: Ambiguous, Specified |
| پیوست ۱ / بند ۶ | Compatibility with standard desktop and mobile browsers | `REQ-CTR-010` | Specified | — |
| پیوست ۱ / بند ۷ | Admin panel 2FA | `REQ-CTR-011`, `REQ-IAM-012` | Specified | split into 2 requirements |
| پیوست ۱ / بند ۸ | Extensible model | `REQ-CTR-012`, `REQ-CAT-006`, `REQ-ADM-011` | Specified | split into 3 requirements |
| پیوست ۱ / بند ۹ | Daily automatic database backup with restore capability | `REQ-CTR-013` | Specified | — |
| پیوست ۱ / بند ۱۰ | Price audit | `REQ-CTR-014`, `REQ-PRI-005`, `REQ-ADM-009` | Specified | split into 3 requirements |

### پیوست ۲ — صفحات و تجربه کاربری (UX/UI)

| Clause | Clause title (English) | Requirement IDs | Status | Notes / ambiguity |
|---|---|---|---|---|
| پیوست ۲ / بند ۱ | Bespoke UX/UI | `REQ-CTR-021` | Specified | — |
| پیوست ۲ / بند ۲ | Homepage | `REQ-CAT-001` | Specified | — |
| پیوست ۲ / بند ۳ | Category filters | `REQ-CAT-002`, `REQ-CAT-014`, `REQ-PRI-008`, `REQ-STK-004`, `REQ-STK-006` | Specified | split into 5 requirements |
| پیوست ۲ / بند ۴ | Product page | `REQ-CAT-004`, `REQ-CAT-013`, `REQ-RVW-001`, `REQ-RVW-006`, `REQ-RVW-007`, `REQ-RVW-009` | Specified | split into 6 requirements |
| پیوست ۲ / بند ۵ | Comparison | `REQ-CAT-007` | Specified | — |
| پیوست ۲ / بند ۶ | Cart and checkout | `REQ-CRT-001`, `REQ-CRT-002`, `REQ-CRT-003`, `REQ-CRT-004`, `REQ-CRT-006`, `REQ-CRT-007`, `REQ-PAY-015`, `REQ-SHP-002` | Specified | split into 8 requirements |
| پیوست ۲ / بند ۷ | User panel | `REQ-USR-001`, `REQ-USR-002` | Specified | split into 2 requirements |
| پیوست ۲ / بند ۸ | Auth pages | `REQ-IAM-001`, `REQ-IAM-002`, `REQ-IAM-003`, `REQ-NTF-004` | Specified | split into 4 requirements |
| پیوست ۲ / بند ۹ | Rules and FAQ | `REQ-CMS-003` | Specified | — |
| پیوست ۲ / بند ۱۰ | Blog | `REQ-CMS-002`, `REQ-CMS-011` | Specified | split into 2 requirements |
| پیوست ۲ / بند ۱۱ | About, contact, tracking | `REQ-ORD-003`, `REQ-CMS-004`, `REQ-SHP-006` | Specified | split into 3 requirements |
| پیوست ۲ / بند ۱۲ | Configurable sections | `REQ-CMS-005`, `REQ-CMS-006`, `REQ-NTF-007` | Specified | split into 3 requirements |

### پیوست ۳ — سطوح کاربری و امکانات هر سطح

| Clause | Clause title (English) | Requirement IDs | Status | Notes / ambiguity |
|---|---|---|---|---|
| پیوست ۳-۱ | Level: فروشگاه (ارائه‌دهنده کالا) — Store level | `REQ-IAM-006` | Specified | — |
| پیوست ۳-۱ / بند ۱ | Store publishing and basket | `REQ-MER-014`, `REQ-CRT-005`, `REQ-CRT-008` | Specified | split into 3 requirements |
| پیوست ۳-۱ / بند ۲ | Article points | `REQ-CMS-008`, `REQ-RVW-004`, `REQ-LOY-017` | Specified | split into 3 requirements |
| پیوست ۳-۱ / بند ۳ | Store messaging | `REQ-MSG-004` | Specified | — |
| پیوست ۳-۱ / بند ۴ | Settlement request | `REQ-FIN-002`, `REQ-FIN-003` | Specified | split into 2 requirements |
| پیوست ۳-۱ / بند ۵ | Store info and ads | `REQ-CLS-006`, `REQ-PTG-003` | Specified | split into 2 requirements |
| پیوست ۳-۱ / بند ۶ | Store participation | `REQ-TND-009` | Specified | — |
| پیوست ۳-۱ / بند ۷ | Invoice list | `REQ-INV-003` | Specified | — |
| پیوست ۳-۱ / بند ۸ | Wholesale price view | `REQ-MER-015`, `REQ-PRI-001` | Specified | split into 2 requirements |
| پیوست ۳-۱ / بند ۹ | Sample and catalogue | `REQ-SMP-003` | Specified | — |
| پیوست ۳-۱ / بند ۱۰ | SMS-confirmed profile change | `REQ-IAM-014` | Specified | — |
| پیوست ۳-۲ | Level: بازاریاب — Marketer level | `REQ-IAM-007` | Specified | — |
| پیوست ۳-۲ / بند ۱ | Marketer ticket | `REQ-MSG-005` | Specified | — |
| پیوست ۳-۲ / بند ۲ | Settlement request by store, supplier or marketer | `REQ-FIN-002` | Specified | shared with `3/1/4` |
| پیوست ۳-۲ / بند ۳ | Store registration by a marketer through a dedicated form | `REQ-MER-001` | Specified | shared with `5/2` |
| پیوست ۳-۲ / بند ۴ | Referral code and link | `REQ-MER-007` | Specified | — |
| پیوست ۳-۲ / بند ۵ | Dedicated chat page | `REQ-MSG-003` | Specified | — |
| پیوست ۳-۲ / بند ۶ | Marketer-level capabilities enforced as a permission set | `REQ-IAM-007` | Specified | shared with `3/2` |
| پیوست ۳-۲ / بند ۷ | Complaint filing | `REQ-MKT-012` | Specified | — |
| پیوست ۳-۲ / بند ۸ | Marketer order form | `REQ-ORD-005`, `REQ-PAY-010` | Specified | split into 2 requirements |
| پیوست ۳-۲ / بند ۹ | Activation visibility | `REQ-MER-010` | Specified | — |
| پیوست ۳-۲ / بند ۱۰ | Marketer invoice view | `REQ-INV-009` | Specified | — |
| پیوست ۳-۳ | Level: مصرف‌کننده / مشتری — Consumer level | `REQ-IAM-008` | Specified | — |
| پیوست ۳-۳ / بند ۱ | Consumer-level capabilities enforced as a permission set | `REQ-IAM-008` | Specified | shared with `3/3` |
| پیوست ۳-۳ / بند ۲ | Cart, messages, purchases | `REQ-USR-006` | Specified | — |
| پیوست ۳-۳ / بند ۳ | Favourites | `REQ-USR-003` | Specified | — |
| پیوست ۳-۳ / بند ۴ | Profile and activity | `REQ-USR-004`, `REQ-USR-005` | Specified | split into 2 requirements |
| پیوست ۳-۳ / بند ۵ | Consumer tender/order form | `REQ-ORD-016` | Specified | — |
| پیوست ۳-۳ / بند ۶ | Order tracking by tracking code without login | `REQ-ORD-003` | Specified | shared with `2/11` |
| پیوست ۳-۴ | Level: تأمین‌کننده / تولیدکننده — Supplier level | `REQ-IAM-009`, `REQ-MER-019` | Specified | ارجاع به پیوست ۵ نادرست است؛ پنل تأمین‌کننده در پیوست ۱۱ آمده — AMB-MER-001; shared with `11/1` |
| پیوست ۳-۴ / بند ۱ | Independent supplier panel for products, prices, stock, orders and reports | `REQ-MER-019` | Specified | shared with `11/1` |
| پیوست ۳-۵ | Level: ادمین — Admin level | `REQ-IAM-010`, `REQ-ADM-002` | Specified | split into 2 requirements |
| پیوست ۳-۵ / بند ۱ | Admin level has full access, user approval and supervision | `REQ-IAM-010` | Specified | shared with `3/5` |

### پیوست ۴ — سیستم امتیازدهی، پاداش پلکانی و تخفیف

| Clause | Clause title (English) | Requirement IDs | Status | Notes / ambiguity |
|---|---|---|---|---|
| پیوست ۴ / بند ۱ | Points basis | `REQ-RPT-010`, `REQ-LOY-001`, `REQ-LOY-018` | Specified | split into 3 requirements |
| پیوست ۴ / بند ۲ | Per-product points | `REQ-LOY-002` | Specified | — |
| پیوست ۴ / بند ۳ | Volume coefficient set of 0.75, 0.5, 0.25 and 0.2 percent | `REQ-LOY-003` | Specified | — |
| پیوست ۴ / بند ۴ | 4x reward rule | `REQ-LOY-004` | Specified | — |
| پیوست ۴ / بند ۵ | Points always computed and retained even when the reward is not claimed | `REQ-LOY-005` | Specified | — |
| پیوست ۴ / بند ۶ | Points deducted for inactivity over a defined period | `REQ-LOY-006` | Specified | — |
| پیوست ۴ / بند ۷ | Wallet credit granted by admin with expiry support | `REQ-FIN-013`, `REQ-LOY-007` | Specified | split into 2 requirements |
| پیوست ۴ / بند ۸ | Window-based reward for a number of invoices or points within a short period | `REQ-LOY-008` | Specified | — |
| پیوست ۴ / بند ۹ | Star rating displayed on the buyer's profile | `REQ-USR-011` | Specified | — |
| پیوست ۴ / بند ۱۰ | First-invoice discount | `REQ-LOY-011` | Specified | — |
| پیوست ۴ / بند ۱۱ | Higher cash discount on volume invoices with independent coefficients | `REQ-LOY-012`, `REQ-PRM-008`, `REQ-PRM-011` | Ambiguous | split into 3 requirements; statuses: Ambiguous, Specified |
| پیوست ۴ / بند ۱۲ | Reduced coefficients | `REQ-LOY-013` | Ambiguous | با بند ۳ هم‌پوشانی دارد — AMB-LOY-002 |
| پیوست ۴ / بند ۱۳ | Invitation bonus | `REQ-MKT-010` | Specified | — |
| پیوست ۴ / بند ۱۴ | Delay compensation | `REQ-CMP-001`, `REQ-CMP-006`, `REQ-CMP-009` | Specified | split into 3 requirements |
| پیوست ۴ / بند ۱۵ | Complaint freeze | `REQ-ORD-010` | Specified | با پیوست ۱۱ بند ۱۵ و ۱۲ بند ۸ (کسر خودکار خسارت) در تنش است — AMB-ORD-002 |
| پیوست ۴ / بند ۱۶ | Stop icon | `REQ-ORD-011` | Specified | — |
| پیوست ۴ / بند ۱۷ | Satisfaction parameters | `REQ-RVW-002` | Ambiguous | پارامترها مشخص نشده و «روتکافٔ» در متن استخراج‌شده ناخوانا است — AMB-RVW-001 |
| پیوست ۴ / بند ۱۸ | Warning button | `REQ-CMP-003` | Specified | — |
| پیوست ۴ / بند ۱۹ | Violation history | `REQ-ORD-017`, `REQ-CMP-008` | Specified | split into 2 requirements |
| پیوست ۴ / بند ۲۰ | Tiered discount | `REQ-LOY-009` | Conflicting | مرز ۱۰۰ در دو بازه مشترک است — AMB-LOY-001 |
| پیوست ۴ / بند ۲۱ | Fixed discount over 200 | `REQ-LOY-010` | Specified | — |
| پیوست ۴ / بند ۲۲ | Disable commission | `REQ-MKT-006` | Specified | — |
| پیوست ۴ / بند ۲۳ | Auto-deactivation | `REQ-MER-011` | Specified | بازه «معین» عدد ندارد — AMB-MER-002 |
| پیوست ۴ / بند ۲۴ | Account change review | `REQ-MER-013` | Specified | — |

### پیوست ۵ — قابلیت‌های اختصاصی درخواستی کارفرما

| Clause | Clause title (English) | Requirement IDs | Status | Notes / ambiguity |
|---|---|---|---|---|
| پیوست ۵ / بند ۱ | Marketer QR code | `REQ-MER-006` | Specified | — |
| پیوست ۵ / بند ۲ | Marketer-provisioned store | `REQ-IAM-011`, `REQ-MER-001` | Specified | split into 2 requirements |
| پیوست ۵ / بند ۳ | Duplicate store prevention | `REQ-MER-005` | Specified | — |
| پیوست ۵ / بند ۴ | Classifieds section | `REQ-CLS-001`, `REQ-CLS-002`, `REQ-CLS-003`, `REQ-CLS-004`, `REQ-CLS-005`, `REQ-CLS-007` | Specified | split into 6 requirements |
| پیوست ۵ / بند ۵ | Patogh services | `REQ-PTG-001`, `REQ-PTG-006` | Specified | split into 2 requirements |
| پیوست ۵ / بند ۶ | Tender form | `REQ-TND-001` | Specified | — |
| پیوست ۵ / بند ۷ | Professional profile | `REQ-USR-007` | Specified | — |
| پیوست ۵ / بند ۸ | Public profile fields | `REQ-USR-008` | Specified | — |
| پیوست ۵ / بند ۹ | Custom notification | `REQ-ADM-018`, `REQ-NTF-002`, `REQ-NTF-009` | Specified | split into 3 requirements |
| پیوست ۵ / بند ۱۰ | Poll | `REQ-CMS-007`, `REQ-PRM-010` | Specified | split into 2 requirements |
| پیوست ۵ / بند ۱۱ | Buyer-only rating | `REQ-CAT-008` | Specified | — |
| پیوست ۵ / بند ۱۲ | Paid notification | `REQ-PAY-005`, `REQ-NTF-003` | Specified | split into 2 requirements |
| پیوست ۵ / بند ۱۳ | Patogh individuals | `REQ-PTG-002`, `REQ-PTG-004` | Specified | split into 2 requirements |
| پیوست ۵ / بند ۱۴ | Violation section | `REQ-ADM-014`, `REQ-CMP-004` | Specified | split into 2 requirements |
| پیوست ۵ / بند ۱۵ | Order rejection and refund | `REQ-ORD-008`, `REQ-PAY-007`, `REQ-CMP-007` | Specified | همین موضوع در پیوست ۱۱ بند ۱۴ تکرار شده — AMB-ORD-001; split into 3 requirements |
| پیوست ۵ / بند ۱۶ | Points-tier discount percentage displayed per tier and announced by SMS | `REQ-LOY-019` | Specified | — |
| پیوست ۵ / بند ۱۷ | Admin point deduction | `REQ-LOY-014` | Specified | — |
| پیوست ۵ / بند ۱۸ | Consumer price visibility | `REQ-PRI-007`, `REQ-FIN-011`, `REQ-MKT-009` | Specified | split into 3 requirements |
| پیوست ۵ / بند ۱۹ | Marketer points and cut | `REQ-MKT-007`, `REQ-MKT-008` | Specified | split into 2 requirements |
| پیوست ۵ / بند ۲۰ | Permanent supplier discount | `REQ-PRM-004` | Specified | — |
| پیوست ۵ / بند ۲۱ | Decreasing coefficient | `REQ-LOY-015` | Specified | — |
| پیوست ۵ / بند ۲۲ | Store-specific rates | `REQ-LOY-016` | Specified | — |
| پیوست ۵ / بند ۲۳ | Order status push | `REQ-NTF-006` | Specified | — |
| پیوست ۵ / بند ۲۴ | Reverse-number order form | `REQ-ORD-006` | Specified | — |
| پیوست ۵ / بند ۲۵ | Web-based app everywhere | `REQ-CTR-060` | Specified | — |
| پیوست ۵ / بند ۲۶ | WhatsApp and email | `REQ-NTF-001` | Specified | shared with `7/3` |
| پیوست ۵ / بند ۲۷ | Installable PWA with icon, splash screen and smart caching | `REQ-CTR-008` | Specified | shared with `1/4` |
| پیوست ۵ / بند ۲۸ | Persistent chat | `REQ-MSG-001`, `REQ-MSG-002`, `REQ-MSG-008`, `REQ-MSG-009`, `REQ-MSG-010` | Specified | split into 5 requirements |
| پیوست ۵ / بند ۲۹ | Scoped access | `REQ-IAM-015`, `REQ-MKT-011`, `REQ-GEO-006`, `REQ-RPT-009` | Specified | split into 4 requirements |
| پیوست ۵ / بند ۳۰ | Active stores report | `REQ-MKT-014` | Specified | — |
| پیوست ۵ / بند ۳۱ | Photo capture | `REQ-USR-009` | Specified | — |
| پیوست ۵ / بند ۳۲ | Complaint suspends order preparation and supplier payout for up to one week after shipment | `REQ-ORD-010` | Specified | shared with `4/15` |
| پیوست ۵ / بند ۳۳ | Satisfaction rating for received goods on five to ten parameters | `REQ-RVW-002` | Ambiguous | shared with `4/17` |
| پیوست ۵ / بند ۳۴ | Supplier violation history shown at the moment of ordering | `REQ-ORD-017` | Specified | shared with `4/19` |
| پیوست ۵ / بند ۳۵ | Professional profile | `REQ-PTG-005` | Specified | — |
| پیوست ۵ / بند ۳۶ | Tender participation | `REQ-TND-003`, `REQ-TND-004`, `REQ-TND-005`, `REQ-TND-008`, `REQ-TND-010` | Specified | split into 5 requirements |
| پیوست ۵ / بند ۳۷ | Tender distribution | `REQ-TND-002` | Specified | — |
| پیوست ۵ / بند ۳۸ | Sample request | `REQ-SMP-001`, `REQ-SMP-004` | Specified | split into 2 requirements |
| پیوست ۵ / بند ۳۹ | Membership transfer | `REQ-IAM-013` | Specified | — |
| پیوست ۵ / بند ۴۰ | Carrier and shipping method | `REQ-ORD-001`, `REQ-SHP-001` | Specified | split into 2 requirements |
| پیوست ۵ / بند ۴۱ | Order attachments | `REQ-ORD-012`, `REQ-INV-005` | Specified | split into 2 requirements |
| پیوست ۵ / بند ۴۲ | Pre-invoice | `REQ-PRI-010` | Specified | — |
| پیوست ۵ / بند ۴۳ | Bulk deactivation | `REQ-ADM-010`, `REQ-PRM-001`, `REQ-PRM-002`, `REQ-PRM-009` | Specified | split into 4 requirements |
| پیوست ۵ / بند ۴۴ | Store accounting | `REQ-MER-020` | Specified | — |
| پیوست ۵ — تبصره نوبت‌دهی | Reservation out of scope | `REQ-CTR-059` | Specified | — |

### پیوست ۶ — پنل حرفه‌ای بازاریاب

| Clause | Clause title (English) | Requirement IDs | Status | Notes / ambiguity |
|---|---|---|---|---|
| پیوست ۶ / بند ۱ | In-person registration | `REQ-MER-002` | Specified | — |
| پیوست ۶ / بند ۲ | Gallery and location | `REQ-MER-003`, `REQ-MER-004`, `REQ-GEO-004` | Specified | split into 3 requirements |
| پیوست ۶ / بند ۳ | Commission models | `REQ-MKT-002` | Specified | — |
| پیوست ۶ / بند ۴ | Automatic deactivation of stores with no purchase or invoice in a period | `REQ-MER-011` | Specified | shared with `4/23` |
| پیوست ۶ / بند ۵ | Plan tiers | `REQ-MKT-003`, `REQ-MKT-004` | Ambiguous | مشخصات پلن‌ها در قرارداد تعریف نشده — AMB-MKT-001; split into 2 requirements |
| پیوست ۶ / بند ۶ | Commission conditions | `REQ-MKT-005` | Specified | — |
| پیوست ۶ / بند ۷ | Second presentation form | `REQ-MER-012` | Specified | — |
| پیوست ۶ / بند ۸ | Credential handover | `REQ-MER-008` | Specified | — |
| پیوست ۶ / بند ۹ | Five-minute edit window | `REQ-MER-009` | Specified | — |
| پیوست ۶ / بند ۱۰ | Approved visits | `REQ-MKT-001` | Specified | — |
| پیوست ۶ / بند ۱۱ | Active/inactive report | `REQ-MKT-015` | Specified | — |
| پیوست ۶ / بند ۱۲ | Customer registration with photo | `REQ-MKT-016` | Specified | — |

### پیوست ۷ — پنل مدیریت سایت (ادمین)

| Clause | Clause title (English) | Requirement IDs | Status | Notes / ambiguity |
|---|---|---|---|---|
| پیوست ۷ / بند ۱ | Member management | `REQ-ADM-001`, `REQ-ADM-012`, `REQ-ADM-017`, `REQ-ADM-019` | Specified | split into 4 requirements |
| پیوست ۷ / بند ۲ | Financial management and invoices | `REQ-INV-001`, `REQ-INV-002`, `REQ-INV-006`, `REQ-INV-007`, `REQ-INV-010`, `REQ-INV-011`, `REQ-ADM-003`, `REQ-ADM-016`, `REQ-RPT-003` | Specified | split into 9 requirements |
| پیوست ۷ / بند ۳ | Sales report and messages | `REQ-ADM-004`, `REQ-NTF-001`, `REQ-NTF-005`, `REQ-NTF-011`, `REQ-NTF-012` | Specified | split into 5 requirements |
| پیوست ۷ / بند ۴ | Admin product form | `REQ-CAT-003`, `REQ-CAT-005`, `REQ-PRI-002`, `REQ-STK-001` | Specified | split into 4 requirements |
| پیوست ۷ / بند ۵ | Content management | `REQ-CMS-012`, `REQ-CMS-013`, `REQ-ADM-005`, `REQ-MSG-007` | Specified | split into 4 requirements |
| پیوست ۷ / بند ۶ | Secondary level | `REQ-IAM-005` | Ambiguous | مثال بند، ترکیب فروشگاه+بازاریاب را منع می‌کند در حالی که پیوست ۱۲ بند ۱۱ ترکیب بازاریاب+مصرف‌کننده را الزام می‌کند — AMB-IAM-001 |
| پیوست ۷ / بند ۷ | Report content | `REQ-USR-012`, `REQ-ADM-006` | Specified | split into 2 requirements |
| پیوست ۷ / بند ۸ | Admin tickets | `REQ-ADM-007`, `REQ-MSG-006` | Specified | split into 2 requirements |
| پیوست ۷ / بند ۹ | Site settings | `REQ-CMS-001`, `REQ-CMS-009`, `REQ-CMS-010`, `REQ-PRM-005` | Specified | split into 4 requirements |
| پیوست ۷ / بند ۱۰ | Reports with Excel | `REQ-ADM-008`, `REQ-ADM-015`, `REQ-RPT-004`, `REQ-RPT-005`, `REQ-RPT-006`, `REQ-RPT-007`, `REQ-RPT-008` | Specified | split into 7 requirements |

### پیوست ۸ — اپلیکیشن موبایل (اندروید + PWA + iOS)

| Clause | Clause title (English) | Requirement IDs | Status | Notes / ambiguity |
|---|---|---|---|---|
| پیوست ۸ / بند ۱ | Android APK | `REQ-CTR-052` | Deferred | با پیوست ۵ بند ۲۵ و پیوست ۱ بند ۴ ناسازگار — AMB-CTR-003 |
| پیوست ۸ / بند ۲ | iOS app or PWA | `REQ-CTR-053` | Deferred | — |
| پیوست ۸ / بند ۳ | PWA push | `REQ-CTR-054` | Specified | — |
| پیوست ۸ / بند ۴ | Data sync | `REQ-CTR-055` | Specified | — |
| پیوست ۸ / بند ۵ | App design | `REQ-CTR-056` | Deferred | — |
| پیوست ۸ / بند ۶ | App identity: splash, icon, login and home screens; build and store package | `REQ-CTR-056` | Deferred | shared with `8/5` |
| پیوست ۸ / بند ۷ | Store release | `REQ-CTR-057` | Deferred | — |
| پیوست ۸ / بند ۸ | App updates | `REQ-CTR-058` | Deferred | — |

### پیوست ۹ — سئو، بهینه‌سازی، امنیت و برندسازی

| Clause | Clause title (English) | Requirement IDs | Status | Notes / ambiguity |
|---|---|---|---|---|
| پیوست ۹ / بند ۱ | Technical SEO | `REQ-CTR-040` | Specified | — |
| پیوست ۹ / بند ۲ | Performance | `REQ-CTR-041` | Specified | — |
| پیوست ۹ / بند ۳ | Security controls | `REQ-CTR-042` | Specified | — |
| پیوست ۹ / بند ۴ | Initial data import | `REQ-CTR-043` | Specified | — |
| پیوست ۹ / بند ۵ | Branding | `REQ-CTR-044` | Specified | — |
| پیوست ۹ / بند ۶ | Team training | `REQ-CTR-045` | Specified | — |

### پیوست ۱۰ — تحویل، آزمون و آموزش

| Clause | Clause title (English) | Requirement IDs | Status | Notes / ambiguity |
|---|---|---|---|---|
| پیوست ۱۰ / بند ۱ | Pilot on client server | `REQ-CTR-046` | Specified | — |
| پیوست ۱۰ / بند ۲ | Acceptance run | `REQ-CTR-047` | Specified | — |
| پیوست ۱۰ / بند ۳ | Remediation and retest | `REQ-CTR-048` | Specified | — |
| پیوست ۱۰ / بند ۴ | Final delivery | `REQ-CTR-049` | Specified | — |
| پیوست ۱۰ / بند ۵ | 29 training sessions | `REQ-CTR-050` | Specified | — |
| پیوست ۱۰ / بند ۶ | Handover package | `REQ-CTR-051` | Specified | — |

### پیوست ۱۱ — پنل تأمین‌کنندگان و عرضه‌کنندگان کالا

| Clause | Clause title (English) | Requirement IDs | Status | Notes / ambiguity |
|---|---|---|---|---|
| پیوست ۱۱ / بند ۱ | Supplier panel | `REQ-MER-019`, `REQ-PRI-009`, `REQ-STK-002` | Specified | split into 3 requirements |
| پیوست ۱۱ / بند ۲ | Producer as colleague buyer | `REQ-MER-017` | Specified | — |
| پیوست ۱۱ / بند ۳ | Producer page | `REQ-MER-016` | Specified | — |
| پیوست ۱۱ / بند ۴ | Festival showcase | `REQ-CAT-009`, `REQ-PRM-006` | Specified | split into 2 requirements |
| پیوست ۱۱ / بند ۵ | Banner reservation | `REQ-PAY-009` | Specified | — |
| پیوست ۱۱ / بند ۶ | Filtered notification | `REQ-NTF-008` | Specified | — |
| پیوست ۱۱ / بند ۷ | SMS panel tariff | `REQ-PAY-008` | Specified | — |
| پیوست ۱۱ / بند ۸ | Seasonal slideshow | `REQ-PRM-007` | Specified | — |
| پیوست ۱۱ / بند ۹ | Sample to supplier panel | `REQ-SMP-002` | Specified | — |
| پیوست ۱۱ / بند ۱۰ | Auction | `REQ-TND-006`, `REQ-TND-007` | Specified | split into 2 requirements |
| پیوست ۱۱ / بند ۱۱ | Independent supplier panel for products, prices, stock, orders and reports | `REQ-MER-019` | Specified | shared with `11/1` |
| پیوست ۱۱ / بند ۱۲ | Incomplete order | `REQ-ORD-007` | Specified | — |
| پیوست ۱۱ / بند ۱۳ | Automatic negative points | `REQ-CMP-005` | Specified | — |
| پیوست ۱۱ / بند ۱۴ | Supplier cancellation | `REQ-ORD-009` | Specified | — |
| پیوست ۱۱ / بند ۱۵ | Shipment delay and compensation | `REQ-STK-003`, `REQ-ORD-019`, `REQ-FIN-009`, `REQ-SHP-007` | Specified | split into 4 requirements |
| پیوست ۱۱ / بند ۱۶ | Manual admin adjustment | `REQ-STK-005`, `REQ-FIN-005`, `REQ-FIN-016`, `REQ-ADM-013` | Specified | split into 4 requirements |
| پیوست ۱۱ / بند ۱۷ | Supplementary invoice | `REQ-INV-004` | Specified | — |
| پیوست ۱۱ / بند ۱۸ | Order note attachments | `REQ-ORD-013` | Specified | — |
| پیوست ۱۱ / بند ۱۹ | Dealership directory | `REQ-MER-018` | Specified | — |
| پیوست ۱۱ / بند ۲۰ | Product photography | `REQ-CAT-016` | Specified | — |

### پیوست ۱۲ — پلن مصرف‌کننده و باشگاه مشتریان

| Clause | Clause title (English) | Requirement IDs | Status | Notes / ambiguity |
|---|---|---|---|---|
| پیوست ۱۲ / بند ۱ | Consumer price spread | `REQ-PRI-003`, `REQ-PRI-004`, `REQ-INV-008` | Specified | «مثلاً ۳۵ درصد بالاتر» به‌عنوان نمونه ذکر شده، نه مقدار ثابت — AMB-PRI-001; split into 3 requirements |
| پیوست ۱۲ / بند ۲ | City distributors | `REQ-PTG-008` | Specified | — |
| پیوست ۱۲ / بند ۳ | Intermediary order | `REQ-ORD-014` | Specified | — |
| پیوست ۱۲ / بند ۴ | Buy-bon | `REQ-PRM-003` | Specified | — |
| پیوست ۱۲ / بند ۵ | Full storefront features | `REQ-CAT-012`, `REQ-CAT-015`, `REQ-SHP-005` | Specified | split into 3 requirements |
| پیوست ۱۲ / بند ۶ | Volume purchase | `REQ-CAT-011`, `REQ-PRI-006` | Specified | split into 2 requirements |
| پیوست ۱۲ / بند ۷ | Birthday greetings | `REQ-USR-010`, `REQ-NTF-010` | Specified | split into 2 requirements |
| پیوست ۱۲ / بند ۸ | Automatic compensation | `REQ-CMP-002` | Specified | — |
| پیوست ۱۲ / بند ۹ | Delivery confirmation | `REQ-ORD-018`, `REQ-RVW-003`, `REQ-RVW-005`, `REQ-RVW-008`, `REQ-PTG-007` | Specified | split into 5 requirements |
| پیوست ۱۲ / بند ۱۰ | Cash discount on the first invoice or any admin-selected invoice | `REQ-LOY-011` | Specified | shared with `4/10` |
| پیوست ۱۲ / بند ۱۱ | Dual role | `REQ-MKT-013` | Conflicting | با مثال پیوست ۷ بند ۶ ناسازگار — AMB-IAM-001 |
| پیوست ۱۲ / بند ۱۲ | Carrier and free shipping | `REQ-SHP-003`, `REQ-SHP-004` | Specified | split into 2 requirements |
| پیوست ۱۲ / بند ۱۳ | Deposit and balance | `REQ-ORD-015`, `REQ-PAY-006`, `REQ-PAY-013` | Specified | split into 3 requirements |
| پیوست ۱۲ / بند ۱۴ | Return acceptance | `REQ-RTN-001`, `REQ-RTN-002`, `REQ-RTN-003`, `REQ-RTN-004`, `REQ-RTN-005`, `REQ-RTN-006`, `REQ-RTN-007` | Specified | بازه مرجوعی عدد مشخصی ندارد — AMB-RTN-001; split into 7 requirements |
| پیوست ۱۲ / بند ۱۵ | Favourites box for products, stores and producers | `REQ-USR-003` | Specified | shared with `3/3/3` |
| پیوست ۱۲ / بند ۱۶ | Customer work photos | `REQ-CAT-010` | Specified | — |

### پیوست ۱۳ — گزارش‌گیری مالی، تسویه و اتصال بانکی

| Clause | Clause title (English) | Requirement IDs | Status | Notes / ambiguity |
|---|---|---|---|---|
| پیوست ۱۳ / بند ۱ | Sales report for settlement | `REQ-ORD-002`, `REQ-ORD-020`, `REQ-FIN-006`, `REQ-FIN-010`, `REQ-FIN-012`, `REQ-RPT-001`, `REQ-SHP-008` | Specified | split into 7 requirements |
| پیوست ۱۳ / بند ۲ | Daily payment report | `REQ-FIN-007`, `REQ-RPT-011` | Specified | split into 2 requirements |
| پیوست ۱۳ / بند ۳ | Bank Excel export | `REQ-FIN-008` | Specified | — |
| پیوست ۱۳ / بند ۴ | Per-product point rate varying by producer and volume, summed per invoice | `REQ-LOY-002` | Specified | shared with `4/2` |
| پیوست ۱۳ / بند ۵ | Financial flow per level | `REQ-ORD-004`, `REQ-FIN-001`, `REQ-FIN-004`, `REQ-FIN-014`, `REQ-FIN-015` | Specified | split into 5 requirements |
| پیوست ۱۳ / بند ۶ | Failed transactions and reconciliation | `REQ-PAY-004`, `REQ-PAY-011`, `REQ-PAY-014` | Specified | split into 3 requirements |
| پیوست ۱۳ / بند ۷ | Gateway integration | `REQ-PAY-003`, `REQ-PAY-012` | Specified | split into 2 requirements |

### پیوست ۱۴ — قابلیت‌های مدیریت بازاریابان

| Clause | Clause title (English) | Requirement IDs | Status | Notes / ambiguity |
|---|---|---|---|---|
| پیوست ۱۴ / بند ۱ | Location capture | `REQ-GEO-001`, `REQ-GEO-007` | Specified | متن استخراج‌شده این پیوست وارونه/ناخوانا است؛ محتوا بازسازی شده — AMB-GEO-001; split into 2 requirements |
| پیوست ۱۴ / بند ۲ | Live tracking and analysis | `REQ-GEO-002`, `REQ-GEO-003`, `REQ-GEO-005` | Ambiguous | «ریسم» در متن اصلی ناخوانا (احتمالاً «مسیر») — AMB-GEO-002; split into 3 requirements; statuses: Ambiguous, Specified |

## 3. Contract articles (ماده ۱–۱۶)

Articles do not carry feature behaviour except where noted; they are traced to the CTR area, which binds the delivery.

| Article | Subject | Requirement IDs | Status |
|---|---|---|---|
| ماده ۱ | موضوع قرارداد و تحویل‌دادنی‌ها | `REQ-CTR-001`, `REQ-CTR-002`, `REQ-CTR-003`, `REQ-CTR-004` | Specified |
| ماده ۲ | مبلغ و شرایط پرداخت | `REQ-PRI-011` | Specified |
| ماده ۳ | مدت اجرا و فازبندی | `REQ-CTR-015`, `REQ-CTR-016`, `REQ-CTR-017` | Specified |
| ماده ۴ | تعهدات مجری | `REQ-CTR-018`, `REQ-CTR-020`, `REQ-CTR-022`, `REQ-CTR-023`, `REQ-CTR-024`, `REQ-CTR-025`, `REQ-CTR-026`, `REQ-CTR-027`, `REQ-CTR-028` | Specified |
| ماده ۵ | تعهدات کارفرما | `REQ-CTR-029`, `REQ-CTR-030` | Specified |
| ماده ۶ | حل اختلاف | — (no software requirement; governance only) | n/a |
| ماده ۷ | مالکیت مادی و معنوی | `REQ-CTR-031`, `REQ-CTR-032` | Specified |
| ماده ۸ | اصلاحیه و الحاقیه | `REQ-CTR-033` | Specified |
| ماده ۹ | فسخ قرارداد | `REQ-CTR-019`, `REQ-CTR-035` | Specified |
| ماده ۱۰ | شرایط قهری | — (no software requirement; governance only) | n/a |
| ماده ۱۱ | ابلاغ و نشانی | — (no software requirement; governance only) | n/a |
| ماده ۱۲ | گارانتی، پشتیبانی و SLA | `REQ-CTR-036`, `REQ-CTR-037`, `REQ-CTR-038` | Specified (+1 deferred) |
| ماده ۱۳ | واگذاری به غیر | — (no software requirement; governance only) | n/a |
| ماده ۱۴ | نسخ قرارداد | — (no software requirement; governance only) | n/a |
| ماده ۱۵ | جدول الحاقیه فیچرها | `REQ-CTR-034` | Specified |
| ماده ۱۶ | مشارکت مجری در سود | `REQ-CTR-039`, `REQ-RPT-002` | Specified |

---

## 4. Unmapped clauses

**None.** All 211 numbered clauses (182 appendix clauses + 28 sub-items of پیوست ۳ + the appendix-5 scope note) resolve to at least one requirement; the check is mechanical (a script parses every requirement's source pointer into `appendix/clause` and diffs it against the canonical clause list).

Two edge cases are mapped deliberately, not accidentally:

1. **Clauses repeated across appendices** (for example پیوست ۴ بند ۱۵ and پیوست ۵ بند ۳۲ both describe the one-week post-shipment complaint freeze). They are satisfied by a single requirement and the matrix row names the requirement plus the shared clause, so no behaviour is implemented twice with divergent rules.
2. **Appendix 3 sub-items** describe role capabilities rather than new features. Each row points at the requirement that implements the capability (order, invoice, settlement, chat, tender …), and the five level headings point at `REQ-IAM-006` … `REQ-IAM-010`, which own the permission set.

---

## 5. Ambiguity and conflict register (clause level)

| ID | Clauses | Conflict | Resolution taken in the requirements |
|---|---|---|---|
| AMB-CTR-001 | ماده ۲ / ماده ۳ | Settlement of 80,000,000 Toman is due «پس از گذشت یک ماه» in the payment table, «پس از گذشت سه ماه» in the summary block, and the duration is stated as «۳ ماه (۹۰ روز)». | Payment terms are treated as commercial, not software; CTR-015…CTR-019 track schedule and penalties against the 90-day duration. |
| AMB-CTR-002 | ماده ۱۶ | The article text is reversed/corrupted in the extraction («حقتسم», «رب»); 15% profit share and 15% of future costs are still legible. | REQ-RPT-002 computes the statement from gross revenue minus COGS, gateway fees, shipping and tax, as the article states. |
| AMB-CTR-003 | پیوست ۱ بند ۴ / پیوست ۵ بند ۲۵ / پیوست ۸ بند ۱ | PWA-only delivery («اپلیکیشن برپایه وب», «بدون نیاز به اپلیکیشن جداگانه») contradicts «تحویل فایل APK قابل نصب مستقیم». | Web/PWA is delivered now (REQ-CTR-008, REQ-CTR-060); native shells are Deferred under RB-MOB-01 (REQ-CTR-052…058). |
| AMB-IAM-001 | پیوست ۷ بند ۶ / پیوست ۱۲ بند ۱۱ | بند ۶ grants every level an optional secondary level yet its example forbids store+marketer; بند ۱۱ requires simultaneous marketer+consumer. | Represented as many-to-many roles with an admin-controlled allow-list (REQ-IAM-005, REQ-MKT-013); combinations are configuration, default deny. |
| AMB-IAM-002 | پیوست ۳ | «پنج سطح کاربری مستقل» counts admin as one of the five, although admin is not a marketplace participant. | Five levels implemented as stated (REQ-IAM-004); admin is a staff level with a separate guard (REQ-CTR-011). |
| AMB-LOY-001 | پیوست ۴ بند ۲۰ / پیوست ۵ بند ۱۶ | The bands «۱ تا ۱۰۰» and «۱۰۰ تا ۲۰۰» both contain 100, so a 100-point user qualifies for 4% and 5%. | REQ-LOY-009 resolves the boundary as inclusive-lower/exclusive-upper (100 → 5%); owner confirmation requested. |
| AMB-LOY-002 | پیوست ۴ بند ۳ / بند ۱۲ | بند ۳ defines coefficients 0.75/0.5/0.25/0.2; بند ۱۲ uses 0.2 and 0.1 for wholesale-discounted invoices, reusing 0.2 in two different meanings. | REQ-LOY-013 keeps a separate band set for wholesale-discounted invoices; the overlap is documented, not merged. |
| AMB-LOY-003 | پیوست ۴ بند ۴ / بند ۵ / پیوست ۱۲ بند ۴ | A reward is deducted from the next invoice only if that invoice is ≥ 4× the reward, otherwise half — while بند ۵ says points are always retained and بند ۴ issues a buy-bon on the next invoice. | Reward offset (REQ-LOY-004) and buy-bon (REQ-PRM-003) are modelled as two separate instruments with their own wallets/validity. |
| AMB-ORD-001 | پیوست ۵ بند ۱۵ / پیوست ۱۱ بند ۱۴ | Both clauses give the seller a rejection right with a refund to the buyer's SHEBA; one calls it «عدم تکمیل سفارش», the other «کنسل کردن سفارش». | Treated as one rejection flow (REQ-ORD-008, REQ-ORD-009) with a rule: rejection allowed before shipment, return flow after. |
| AMB-ORD-002 | پیوست ۴ بند ۱۵ / پیوست ۱۱ بند ۱۵ / پیوست ۱۲ بند ۸ | A complaint «پرداخت به عرضهکننده را متوقف میکند» while delay compensation is «بهصورت خودکار با کسر از عرضهکننده» — a hold and an automatic debit on the same balance. | Freeze wins over automatic deduction (REQ-FIN-006): compensation is accrued but not posted while a freeze is active. |
| AMB-PAY-001 | پیوست ۱ بند ۵ / پیوست ۱۳ بند ۷ | «استفاده همزمان از دو درگاه در یک تراکنش» is not a concept any Iranian PSP exposes; a single bank transaction cannot settle through two gateways. | Implemented as two linked payments that together settle one order (REQ-PAY-002), flagged Ambiguous pending owner confirmation. |
| AMB-MER-001 | پیوست ۳-۴ | The supplier level's panel is referenced as «مطابق پیوست ۵», but the supplier panel is specified in پیوست ۱۱. | Requirements follow پیوست ۱۱ (REQ-MER-019); the mis-reference is recorded here so acceptance does not test against the wrong appendix. |
| AMB-MER-002 | پیوست ۴ بند ۲۳ / پیوست ۶ بند ۴ / پیوست ۵ بند ۳۰ | Stores become inactive «در بازه معین» / «بازه مشخص» without a number. | Window is an admin setting (REQ-MER-011), default 90 days, recorded as an owner decision point. |
| AMB-PRI-001 | پیوست ۱۲ بند ۱ | «مثلاً ۳۵ درصد بالاتر» is an example, not a fixed value; manual override is also allowed on top. | Consumer spread is a configurable factor with per-product override (REQ-PRI-003); 35% seeded as the default. |
| AMB-PRI-002 | پیوست ۱۲ بند ۶ | «درصد تخفیف تعیینشده در معرفی محصول» implies a per-product discount but gives no ladder structure. | Ladders are per product with quantity/amount thresholds (REQ-CAT-011, REQ-PRI-006); single step allowed. |
| AMB-PRM-001 | پیوست ۴ بند ۱۱ / پیوست ۵ بند ۴۳ / پیوست ۱۲ بند ۱۰ | Several discounts may apply to one invoice (volume, campaign, coupon, bon, reward) but no stacking order or cap is stated. | One explicit policy with a fixed order and a subtotal cap (REQ-PRM-008), plus a supplier price floor (REQ-PRM-011). |
| AMB-RTN-001 | پیوست ۱۲ بند ۱۴ | Returns are accepted «در بازه مشخص» with no number and «برای اقالم تأییدشده ادمین» with no list. | Window per category (default 7 days) and an explicit returnable flag (REQ-RTN-001, REQ-RTN-007). |
| AMB-RVW-001 | پیوست ۴ بند ۱۷ / پیوست ۵ بند ۳۳ | «پنج تا ده پارامتر امتیازدهی» — the parameters are never named, and «روتکافٔ» is unreadable in the extraction. | Parameters are admin-defined per product type (REQ-RVW-002); a default five-parameter set is seeded. |
| AMB-GEO-001 | پیوست ۱۴ بند ۱ | The clause is reversed in the extraction («تبث تیعقوم یناکم»); only the intent (record the marketer's location for visited places) is certain. | REQ-GEO-001 implements location capture with consent (REQ-GEO-007); wording to be confirmed with the owner. |
| AMB-GEO-002 | پیوست ۱۴ بند ۲ | «تحلیل دادههای عملکرد و ریسم» — «ریسم» is not a word; most likely «مسیر» (route). | Read as performance + route analysis (REQ-GEO-003, REQ-GEO-005). |
| AMB-MKT-001 | پیوست ۶ بند ۵ | Plans A/B/C/D exist but their parameters (rate, target, fee) are not defined anywhere in the contract. | Plans are data-driven with admin-editable parameters (REQ-MKT-003); initial values are an owner decision. |
| AMB-FIN-001 | پیوست ۱۳ / پیوست ۱۱ بند ۱۶ | The contract never states who holds funds between payment and settlement (platform or supplier). | Most conservative model: platform collects, ledger records, periodic settlement (REQ-FIN-002, REQ-FIN-006, REQ-FIN-012). |

---

## 6. Clauses that are internally contradictory

Five clauses contradict another clause or themselves in a way that changes what gets built (the full list of softer ambiguities is in §5):

1. **پیوست ۷ بند ۶** — grants every level «یک سطح ثانویه» and then, in the same sentence, gives an example that removes the most useful combination («سطح فروشگاه نتواند بازاریاب باشد»); پیوست ۱۲ بند ۱۱ then *requires* simultaneous marketer+consumer activity. → `AMB-IAM-001`
2. **پیوست ۴ بند ۲۰ (and its twin پیوست ۵ بند ۱۶)** — the bands «۱ تا ۱۰۰» and «۱۰۰ تا ۲۰۰» both include 100, so the same user is entitled to 4% and 5% at once. → `AMB-LOY-001`
3. **پیوست ۴ بند ۳ versus بند ۱۲** — the coefficient set is 0.75/0.5/0.25/0.2, but بند ۱۲ computes wholesale-discounted invoices at «۰٫۲ و ۰٫۱ درصد», reusing 0.2 with a different meaning and introducing 0.1 that بند ۳ never defines. → `AMB-LOY-002`
4. **پیوست ۵ بند ۲۵ versus پیوست ۸ بند ۱** — the product must run «بهصورت یک اپلیکیشن برپایه وب … بدون نیاز به اپلیکیشن جداگانه» while the same contract demands «تحویل فایل APK قابل نصب مستقیم» and an App Store build. → `AMB-CTR-003`
5. **پیوست ۴ بند ۱۵ versus پیوست ۱۱ بند ۱۵ / پیوست ۱۲ بند ۸** — a complaint must *freeze* the supplier's payout «تا تعیین تکلیف», while delay compensation must be deducted from the supplier and paid to the buyer's wallet *automatically*; both act on the same payable with no precedence rule. → `AMB-ORD-002`

A sixth, structural contradiction is worth flagging to the owner although it does not change a feature: **ماده ۳** states a 90-day duration while **ماده ۲** schedules final settlement «پس از گذشت یک ماه» in one place and «سه ماه» in another (`AMB-CTR-001`), and **پیوست ۳-۴** points the supplier panel at the wrong appendix (`AMB-MER-001`).

---

## 7. Cross-document note (not a clause mapping)

`docs/requirements/acceptance-criteria.md` (owned by another agent) cites a parallel coarse numbering for the global acceptance gates — `REQ-CTR-201` … `REQ-CTR-215`. Those IDs are not feature requirements and do not exist in this catalogue; each of them already names its appendix clause, so the equivalents are:

| Sibling ID | Clause it names | Requirement(s) in this catalogue |
|---|---|---|
| `REQ-CTR-201` | پیوست ۱ بند ۶ | `REQ-CTR-010` |
| `REQ-CTR-202` | پیوست ۱ بند ۷ | `REQ-CTR-011`, `REQ-IAM-012` |
| `REQ-CTR-203` | پیوست ۱ بند ۹ | `REQ-CTR-013` |
| `REQ-CTR-204` | پیوست ۱ بند ۱۰ | `REQ-CTR-014`, `REQ-ADM-009` |
| `REQ-CTR-205` | پیوست ۹ بند ۳ | `REQ-CTR-027`, `REQ-CTR-042` |
| `REQ-CTR-206` | پیوست ۹ بند ۱ | `REQ-CTR-040` |
| `REQ-CTR-207` | پیوست ۹ بند ۴ | `REQ-CTR-043` |
| `REQ-CTR-215` | پیوست ۱۰ بند ۲ | `REQ-CTR-047` |

The Orchestrator should either re-point those references here or keep the coarse IDs as acceptance-gate identifiers and link them to the requirement IDs above; no behaviour changes either way.

---

