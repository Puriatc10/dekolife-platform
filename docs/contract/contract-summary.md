# Contract Summary — Dekolife Platform (DK-1405-01)

| | |
|---|---|
| **Purpose** | One readable pass over the whole contract: parties, subject, money, time, obligations, penalties, governance, and what each of the 14 appendices actually asks for. |
| **Audience** | All agents and engineers; the Orchestrator for scope arbitration; anyone preparing a client conversation. |
| **Status** | Baseline v1.0. Read the full 11 pages; figures cross-checked against page renders (see [`contract-source.md`](contract-source.md) §6). |
| **Source** | `input/contract/1-قرارداد-دکولایف-۹۰م-ویرایش.pdf`, pages 1–11. Every statement below carries `page / ماده or پیوست / بند`. |

> **Read this first:** the document is titled «پیشنویس نسخه ۱ — در انتظار توافق و امضای طرفین» (page 1) and its signature dates are blank (page 11). It is an unsigned draft text. Two figures exist in two conflicting versions inside the same PDF file (`AMB-CTR-005`, `AMB-CTR-006`). Everything else in this summary is single-valued and verified.

## 1. Parties and instrument

| Field | Value | Pointer |
|---|---|---|
| Contract number | `DK-1405-01` | page 1 |
| Client (کارفرما) | آقای داوودی | page 1 |
| Contractor (مجری) | محسن فتحی | page 1 |
| Copies | Two identical signed copies + witnesses; signed electronic copy via the parties' Telegram chat has equal validity; every page must be annotated and numbered | page 4 / ماده ۱۴ |
| Governing structure | 16 مواد + detailed appendices that are «جزء لاینفک» (inseparable part) of the contract | page 4 / ماده ۱۴; page 5 preamble |
| Amendment mechanism | Written addendum only, two signed copies, appended to the original; **oral agreements have no legal effect**; new features only via the ماده ۱۵ table | page 3 / ماده ۸; page 4 / ماده ۱۵ |

## 2. Subject (ماده ۱)

> «موضوع این قرارداد عبارت است از: طراحی و پیادهسازی کامل وبسایت چندفروشندگی (B2B/B2C) با سیستم بازاریابی چندمرحلهای، امتیازدهی هوشمند و پاداش پلکانی + اپلیکیشن موبایل (اندروید و iOS و PWA). مجری متعهد است تمامی خدمات مندرج در پیوستهای این قرارداد (که جزء لاینفک آنها بهشمار میروند) را عیناً طبق مشخصات مصوب طراحی، پیادهسازی، آزمون و بهصورت کامل، پایدار و بدون نقص تحویل کارفرما نماید. کوتاهی در اجرای حتی یکی از بندهای پیوستها، تخلف از قرارداد محسوب میشود.»

Four named deliverables on page 1:

1. Full implementation of the Dekolife system (web + mobile app) with **every** capability, option and filter named in the appendices.
2. Complete, documented source code that the client can edit and extend.
3. Deployment on the client's server, domain + SSL configuration, performance test in the production environment.
4. Connection and configuration of the online payment gateway(s) per the appendices.

The scope sentence is the most load-bearing text in the contract: *any* unexecuted appendix clause is a breach, and ماده ۴ بند ۱۱ attaches a 5 % penalty to each missing **numbered appendix heading**. The entire compliance model of this project therefore rests on an explicit per-clause requirement inventory — see [`../requirements/`](../requirements/) (owned by the requirements agent) and the penalty exposure note in `AMB-CTR-008`.

## 3. Commercial terms

### 3.1 Price

> «مبلغ کل این قرارداد معادل ۹۰٬۰۰۰٬۰۰۰ تومان است.» — page 2 / ماده ۲

- **90,000,000 Toman, fixed**, «ثابت — بدون تعدیل یکطرفه» (page 1 summary table). No unilateral adjustment; indexation is not provided for.
- Payments go to the contractor's account/card; the deposit receipt is the proof of payment (account number and holder name are blank in the file — page 2).
- **Tax:** the contractor bears its own income tax and levies.
- **Infrastructure:** hosting, domain, SSL and payment-gateway costs are the **client's** (page 2 / ماده ۲; page 3 / ماده ۵ بند ۳).

### 3.2 Payment plan — two versions in the file (`AMB-CTR-005`)

| Version | Content | Where |
|---|---|---|
| **B — what page 2 prints** | One row: «پرداخت مرحلهای \| ۹۰٬۰۰۰٬۰۰۰ \| در طول اجرا و پس از تحویل نهایی و تأیید کتبی کارفرما» — a single staged payment during execution, finalised after final delivery and written approval | page 2 / ماده ۲ table; page 1 summary («۹۰٬۰۰۰٬۰۰۰ مرحلهای، در طول اجرا و پس از تحویل») |
| **A — text layer only, does not render** | «پیشپرداخت گشایش کار ۱۰٬۰۰۰٬۰۰۰ همزمان با امضای قرارداد و پیش از شروع کار» + «تسویه نهایی ۸۰٬۰۰۰٬۰۰۰ یکجا، پس از گذشت یک ماه از تحویل نهایی و تأیید کتبی کارفرما» | page 2 text layer (two occurrences) |

Both sum to 90,000,000. Version A is the only version that specifies *when* money moves, and it matches the phrase «گشایش کار» that also appears in the phase table; version B is the one a reader of the PDF sees. **Planning assumption: A (10,000,000 on signature + 80,000,000 one month after final delivery and written approval), because it is the only actionable breakdown — recorded as DEC-015 and reversible the day the owner confirms.** Consequence if B is what was signed: no advance is due, and the entire amount is conditioned on written approval, which makes the delivery gate (`پیوست ۱۰`) the single collection event.

Every payment must have its deposit receipt posted and confirmed in the parties' official Telegram chat (page 2 / ماده ۲ «یادآوری»).

### 3.3 Duration and phasing

> «مدت اجرای پروژه ۳ ماه (۹۰ روز تقویمی) از تاریخ انعقاد قرارداد و دریافت کامل اطلاعات و محتوا از کارفرما به شمار میرود.» — page 2 / ماده ۳

- **90 calendar days**, counted from contract date **and** complete receipt of client information/content — a dual trigger, so the clock starts at the later of the two.
- Extension only by written agreement (page 2 / ماده ۳). Force majeure extensions are likewise written (page 4 / ماده ۱۰).
- Contract date and delivery date are both **blank** (`.......... / ۱۴۰۵`), start status «در انتظار شروع».

| Phase | Content | Duration | Pointer |
|---|---|---|---|
| Month 1 | Delivery of the complete system version (web + app) | 1 month | page 2 / ماده ۳ table |
| Month 2 | Debugging and defect removal | 1 month | page 2 / ماده ۳ table |
| Month 3 | Heavy testing and final delivery | 1 month | page 2 / ماده ۳ table |

- Weekly progress report (text, image or video) is mandatory; the client must respond within **3 working days**, and **silence for 7 working days counts as approval** of the contractor's proposed plan at that stage (page 2 / ماده ۳; repeated as a client obligation in page 3 / ماده ۵ بند ۴).
- Client delay in supplying information or content extends the deadline **day for day**, recorded in the progress report (page 2 / ماده ۳).

### 3.4 Penalties — two independent, both capped

| Penalty | Rate | Cap | Trigger | Pointer |
|---|---|---|---|---|
| Delay | **0.5 % of contract value per day** of delay, deducted from the contractor's receivables | **20 %** of contract value (= 18,000,000 Toman) | Late final delivery | page 2 / ماده ۴ بند ۲ |
| Missing appendix heading | **5 % of contract value** per unexecuted heading (= 4,500,000 Toman each) | **10 %** of contract value (= 9,000,000 Toman) | «در صورت اجرا نکردن هر یک از سرفصلهای اصلی پیوستها» | page 3 / ماده ۴ بند ۱۱ |

> «… سقف مجموع این جریمه ۱۰ درصد مبلغ قرارداد است و «بند» در این ماده به هر یک از سرفصلهای شمارهدار پیوستها اطلاق میشود» — page 3 / ماده ۴ بند ۱۱

Notes that matter operationally:

- The 5 % penalty is counted **per numbered appendix heading** (سرفصل), not per clause. With 14 appendices there are 14 headings; three unexecuted headings exhaust the 10 % cap. What counts as a "heading" for this purpose is `AMB-CTR-008`.
- The delay penalty does **not** consume the client's right to terminate or claim further damages once delay exceeds 15 working days (page 4 / ماده ۱۲ تبصره).
- Maximum combined exposure if every indicator trips: 20 % + 10 % = 30 % of 90,000,000 = **27,000,000 Toman**.

### 3.5 Support and warranty

| Item | Value | Pointer |
|---|---|---|
| Free period | **Two versions:** «تا پایان شرکت طرفین» (page 4 render) vs «به مدت ۵ سال» (text layer) → `AMB-CTR-006` | page 4 / ماده ۱۲ بند ۱; page 1 summary («نامحدود (تا زمان شرکت)») |
| What is covered | Bugs and errors **caused by programming error**, plus free technical support and training; «بدون هیچ شرط یا هزینه افزوده» | page 4 / ماده ۱۲ بند ۱ |
| First response | **24 working hours** | page 4 / ماده ۱۲ بند ۲ |
| Medium bugs | Fixed within **3 working days** | page 4 / ماده ۱۲ بند ۲ |
| Critical bugs (system down, data loss) | Fixed within **24 hours** | page 4 / ماده ۱۲ بند ۲ |
| After the free period | Annual support/updating continues under a **separate contract at an agreed fee** | page 4 / ماده ۱۲ بند ۳ |
| New features | Priced per the ماده ۱۵ addendum mechanism | page 4 / ماده ۱۲ بند ۴ |

The client-facing consequence: any *defect* is free and SLA-bound; any *new capability* is billable. The dividing line is the appendix text, so ambiguous clauses are commercial risk in both directions.

### 3.6 Profit share (ماده ۱۶) — the outlier clause

> «مجری علاوه بر مبلغ مقرر در ماده ۲، مستحق پانزده درصد (٪۱۵) از سود خالص کسبوکار موضوع این قرارداد است و در مقابل، پانزده درصد (٪۱۵) از هزینههای آینده توسعه، نگهداری و زیرساخت سامانه (سرور، دامنه، تبلیغات و افزودن قابلیتهای جدید) را نیز متقبل میشود. مبنای سود خالص، کل درآمد فروش سامانه پس از کسر بهای تمامشده کالا، کارمزد درگاه، هزینه ارسال و مالیات است. کارفرما گزارش درآمد و هزینه هر ماه را مکتوب به مجری ارائه میکند و تسویه سهم مجری حداکثر پانزده روز پس از پایان هر فصل انجام میشود.» — page 4 / ماده ۱۶

- **15 % of net profit** to the contractor, in exchange for **15 % of future development, maintenance and infrastructure costs** (server, domain, advertising, new features).
- Net profit = total sales revenue − cost of goods − gateway fees − shipping − tax. Note what is **not** deducted: staff/salaries, marketing other than advertising, rent, overhead.
- Monthly written revenue/expense report by the client; quarterly settlement within 15 days of quarter end.
- This clause is outside the software scope but inside the product's finance design: it needs platform revenue/cost reporting that is defensible to a third party (`AMB-FIN-002`). It is **not** modelled in v1 code; it is an owner-level accounting obligation.

## 4. Obligations

### 4.1 Contractor (ماده ۴, twelve clauses)

| بند | Obligation | Page |
|---|---|---|
| ۱ | Execute exactly per appendices, without reducing features, options or quality | 2 |
| ۲ | Deliver on time; 0.5 %/day penalty, 20 % cap | 2 |
| ۳ | Full testing before declaring delivery: unit, integration, acceptance with real scenarios | 2 |
| ۴ | Deliver full source + installation docs + folder structure, editable by the client or their expert | 2 |
| ۵ | Free removal of all defects caused by programming error during support (per ماده ۱۲) | 1–2 |
| ۶ | Full confidentiality of information, data, graphics and know-how **even after termination** | 2 |
| ۷ | No reuse of this project's templates/proprietary code in any other project or for anyone else | 2 |
| ۸ | No assignment of the whole or part of the contract to a third party without written consent | 3 |
| ۹ | Weekly written progress report with screenshot or viewable demo | 3 |
| ۱۰ | Technical/security standards: protect user data and API keys, regular DB backup, prevent SQL injection / brute force / malicious uploads, keep components updated | 3 |
| ۱۱ | 5 % penalty per unexecuted appendix heading, 10 % cap; "بند" = each numbered appendix heading | 3 |
| ۱۲ | Safekeeping of all documents/files received from the client; no disclosure without written permission | 3 |

### 4.2 Client (ماده ۵, four clauses)

| بند | Obligation | Page |
|---|---|---|
| ۱ | Provide required information/content (logo, images, texts, product/service list, sales policies) within **5 working days** of request | 3 |
| ۲ | Pay per the ماده ۲ schedule | 3 |
| ۳ | Provide hosting, domain, SSL and payment gateway to the contractor's stated standards | 3 |
| ۴ | Review each stage and record feedback within **3 working days**; **silence for 7 working days = approval** | 3 |

### 4.3 Governance clauses

| ماده | Subject | Key content | Page |
|---|---|---|---|
| ۶ | Disputes | Direct negotiation up to **20 days**; then a mutually chosen arbitrator; failing agreement on the arbitrator, the competent authority / پونیشا (if contracted through it) / court. The award is binding; the rest of the contract keeps running during arbitration | 3 |
| ۷ | IP | All material and moral rights in source code, graphics, marks and project data transfer to the client **after full settlement**; the contractor may not transfer or hand the design/code to others; portfolio use only with written permission and anonymised; user data belongs to the client and may not be used competitively | 3 |
| ۸ | Amendments | Written, signed, two copies; oral agreements void; features only via ماده ۱۵ | 3 |
| ۹ | Termination | No unilateral termination without cause and without settling performed obligations; material breach (late delivery, confidentiality breach, no source handover, quality drop) → written notice + **7 working days** to cure → immediate right to terminate and claim damages; termination for >15 working days' delay → contractor refunds all payments within **15 days** net of ماده ۴ penalties; client's termination without cause → full settlement for work actually performed by mutual valuation | 3 |
| ۱۰ | Force majeure | Natural disasters, public unrest, sanctions, widespread infrastructure outages outside the parties' will → extension by written agreement; on termination neither party may claim damages; settlement based on work performed | 4 |
| ۱۱ | Notices | Addresses/email/phone at the head of the contract are the official channel; change of address within **3 days** in writing; the previous address stays valid until then; Telegram is a valid side channel if both parties agree | 4 |
| ۱۳ | Non-assignment | No assignment of the contract, in whole or in part, to a third party; execution limited to the contractor's own operators; breach → immediate termination + damages | 4 |
| ۱۴ | Copies | 16 articles + appendices; two identical signed copies with witnesses; pages numbered; signed electronic copy has equal validity | 4 |
| ۱۵ | Addendum register | Any capability or service added after signature enters via the addendum table with written agreement on **cost and delivery time**; a row without that agreement is not binding and carries no ماده ۴ rights | 4 |
| ۱۶ | Profit share | 15 % of net profit / 15 % of future costs (§3.6) | 4 |

Note the interaction between ماده ۷ and ماده ۹: IP transfers **on full settlement**, so an unpaid final invoice leaves the contractor holding the rights to code the client already runs. That is a contract-administration fact, not a code fact, but it determines when the repository can be described as "client-owned".

## 5. The fourteen appendices

Appendix counts below are the printed clause numbers. 203 numbered clauses in total.

### پیوست ۱ — Architecture, technology, infrastructure (page 5, 10 clauses)

Laravel + MySQL, three-layer (MVC) architecture, modular and extensible; fully documented code (README, API docs, table structure); fully responsive for mobile/tablet/desktop; **active PWA output installable on Android and iOS from the browser** with icon, splash screen and smart caching; **simultaneous support for 12 separate bank gateways and the ability to use two gateways in one transaction, chosen by the user**; compatibility with Chrome/Firefox/Safari/Edge and their mobile versions; **separate admin panel on a dedicated domain/path with two-factor authentication**; a database structure that allows a new user level or product type without rewriting the core; automatic daily DB backup with restore; full audit log of sensitive events (login, price change, user-level change, settlement).

> «پشتیبانی همزمان از ۱۲ درگاه پرداخت بانکی مجزا و امکان استفاده همزمان از دو درگاه در یک تراکنش با انتخاب کاربر» — page 5 / پیوست ۱ / بند ۵

This clause is the documented conflict with the engineering baseline (one active gateway per payment, split payment deferred): `AMB-PAY-001`, `AMB-PAY-002`.

### پیوست ۲ — Pages and UX/UI (page 5, 12 clauses)

Bespoke UX/UI from first sketch to HTML/CSS/SASS, **no ready-made template for the user-facing side**; home page in the style of «دیجیکالا» with search bar, categories, banner slider, special offers, best-sellers, newest products; category page with dynamic filters (price, brand, stock, rating, discounted) and free sorting; product page with gallery, video, specs, buyer reviews and ratings, related products, add-to-compare; **compare up to five products** side by side on the same parameters; cart, coupon, shipping cost calculation, integrated checkout; user panel with orders, addresses, favourites, wallet and points; login/registration with **SMS verification**, password recovery, profile editing; purchase rules and FAQ before/after purchase; blog with list, categories and single-article SEO metadata; about/contact (form + map) and order tracking by tracking code; admin-editable zones (footer, static pages, top-of-site notification, home widgets).

### پیوست ۳ — User levels and their capabilities (pages 5–6, 26 clauses in 5 sub-sections)

> «سامانه دارای پنج سطح کاربری مستقل با دسترسیهای تفکیکشده است» — page 5 / پیوست ۳

| Sub-section | Level | Clauses | Headline capabilities |
|---|---|---|---|
| ۳-۱ | فروشگاه (store / goods provider) | 10 | publish content and announcements; cart and own orders; earn points from sales, articles, comments and likes; message users/admins/other stores; request settlement, see statements and cash flow; register store data, post classifieds, be present in پتوق; **participate in tenders and auctions**; order, ticket, see issued invoices; enter stores to see special and wholesale prices; file violation reports and request samples/portfolio/catalogue; favourites and profile with SMS-verified changes |
| ۳-۲ | بازاریاب (marketer) | 10 | tickets to users/admin; settlement request and statements; store-registration form; **dedicated code, link and QR** for invitations; dedicated chat page; view stores and پتوق; file complaints to admin; client/project order forms; see stores admin marked active/inactive; see invoices of the stores they personally visited |
| ۳-۳ | مصرفکننده (consumer) | 6 | full user panel; cart, messaging, purchase history; favourites; profile and recent activity; **create a tender and order via a form**; purchase rules and order tracking |
| ۳-۴ | تأمینکننده / تولیدکننده (supplier) | 1 | independent full panel per پیوست ۵: products, price, stock, orders, sales reports |
| ۳-۵ | ادمین | 1 | full access per پیوست ۷, user approval, oversight of all levels |

### پیوست ۴ — Points, tiered rewards and discounts (pages 6–7, 24 clauses)

The densest and most ambiguous appendix. Core rules:

- Invoice points depend on invoice amount, product type inside it, and purchase volume; each product's points vary by type, producer/supplier and volume; invoice points = sum of item points (بند ۱–۲).
- **Volume-purchase coefficients: 0.75 %, 0.5 %, 0.25 % and 0.2 %**, by purchase volume, per admin setting (بند ۳).
- A product's reward is deducted on the **next** invoice only if that invoice is **at least 4× the reward amount**; otherwise **half** the reward is deducted as discount (بند ۴).
- Points accrue on every invoice and are preserved even if the buyer never claims the reward (بند ۵).
- **Inactivity deduction:** if no invoice/purchase is registered in a set window (e.g. one month), points are deducted (e.g. four points per month) (بند ۶).
- Reaching a threshold credits a cash reward to the wallet, usable **only** for a new purchase/invoice; the reward may be time-limited or permanent (بند ۷).
- Short/medium/long windows for "N invoices or points in a window → wallet credit" are admin-defined (بند ۸).
- **Every 100 points of purchase = one star** on the profile; number of stars unlimited (بند ۹).
- First-invoice discount (or any invoice the admin designates) can be given **in addition to** the next-invoice reward (بند ۱۰).
- High-volume invoices may get a higher cash discount independent of the buyer's points, and that invoice's coefficient for the next invoice is again 0.75/0.5/0.25/0.2 % (بند ۱۱).
- Volume invoices that already received a wholesale cash discount earn next-invoice reward at **0.2 % and 0.1 %** (or admin-set) (بند ۱۲).
- **Referral:** each new colleague entering via another colleague's link earns the inviter a set number of points (بند ۱۳).
- **Delay compensation** to the colleague is possible; percentage or amount approved by the admin (بند ۱۴).
- **Complaint window:** a complaint may be filed and order preparation frozen **up to one week after shipment**, and payment to the supplier is held until the matter is settled (بند ۱۵).
- A "stop preparation" button after invoice issuance and payment, for a set period; with supplier/producer approval the order is cancelled and the amount returned to the buyer (بند ۱۶).
- Rating/feedback on the received invoice **up to one week after shipment**, with **5 to 10 rating parameters** (بند ۱۷).
- A **Warn button** on every invoice to report distributor/producer violations to the admin (بند ۱۸).
- Supplier violation history shown at order time (e.g. violations in the last two months) (بند ۱۹).
- **Tiered next-invoice discount: 1–100 points → 4 %, 100–200 points → 5 %**, announced by SMS (بند ۲۰). The boundary value 100 is claimed by both tiers — `AMB-LOY-004`.
- Fixed system discount for users above 200 points, in Toman, set as `n` (بند ۲۱).
- Admin can disable a marketer's commission or change their level (بند ۲۲).
- Stores inactive for a set window (no purchase/invoice) are automatically moved to inactive and removed from listings (بند ۲۳).
- Account changes are shown in the admin panel so a new marketer can re-visit stores (بند ۲۴).

### پیوست ۵ — Client-requested specific capabilities (pages 7–9, 44 clauses)

The largest appendix: QR per marketer that attributes a scanned store registration; a separate marketer-only store registration form with generated credentials and forced password change at first login; store search by **national ID, postal code and mobile** to prevent duplicates; **دیوار-classifieds section** (title, text, image, search, user profile in the ad, posting only after admin defines the category); **پتوق local-services section** (installers, repairers, local cost requests); tender form sent to producers/suppliers; profile enrichment (site, socials, gallery, rating, working hours, field of work) only with admin approval; public display of phone, address, level, username and points with admin approval; custom notifications (text, image, link, video) with audience/count/time; site surveys; product star ratings by verified buyers; **paid notification purchase** (user buys count/text/image/link, admin reviews and sends); a second پتوق section (people and skills, e.g. "parquet installer in Tehran", with ticket form and cost quote); Warn section in producer profiles with a negative «امتیاز تخلف» shown after admin approval; **supplier "incomplete order" button** to avoid delay compensation, plus supplier rejection of an order with refund to the buyer's **شبا**; automatic tiered discount (1–100 → 4 %, 100–200 → 5 %) on the next invoice with SMS notice; point deductions for inactivity; activation of the consumer section with consumer-price visibility and the ability to pay the supplier above the purchase price in accounting, plus commission for marketers who issued invoices; points to marketers for consumer-section orders and commission cuts if conditions are not met; permanent discounts for suppliers who buy from other suppliers on the platform; a **decreasing points coefficient for larger volumes** (encouraging repeat rather than one-off bulk buying); different points per product and per store; order-status notifications including shipment; an approved-order form with a reverse order-number marker in two pages (distributor and buyer); **the whole software must run as a web-based application on Android, iOS and Windows**; WhatsApp and email as alternative notification channels; **PWA installation without a separate app**; persistent-memory online chat with any user including sellers, admin and marketers, with voice/image/text/files; visit reporting with limited marketer access to their own stores; active/inactive store reporting by invoice window; photo capture in the customer registration form by customer or marketer; complaint + preparation freeze up to a week after shipment; rating 5–10 parameters a week after shipment; supplier violation report button visible to colleagues; full professional profile; tender participation with form and enquiry; sending tender forms to producers; sample/portfolio requests with dimensions; **selling or transferring membership rights** with admin approval; shipping type and method captured at order time and forwarded to the distributor panel; voice recording and 2-minute audio attachment per invoice; pre-invoice view for consumers and level-based pricing.

The closing note is decisive for scope:

> «توجه: ماژول نوبتدهی/رزرواسیون موضوع این قرارداد نیست و مطابق قرارداد شماره ۲ (مبلغ ۳۵٬۰۰۰٬۰۰۰ تومان، مدت ۱ ماه) بهصورت مستقل طراحی و پیادهسازی میشود. اتصال فنی این دو سامانه (ورود مشترک) در صورت درخواست کارفرما با الحاقیه انجام میشود.» — page 9 / پیوست ۵

### پیوست ۶ — Professional marketer panel (page 9, 12 clauses)

In-person store/customer registration with a full field set (address, store name, owner, postal code, national ID, phones with/without WhatsApp, services, category, city, province); upload of owner/storefront/business-card galleries; **Google Maps location**; marketer pay as sales commission, fixed per visit, a mix, or a fixed salary; inactive-store lists per admin definition and re-visits; **multiple marketer models with different commission methods (plans A/B/C/D), also usable by buying colleagues**; making commission/salary contingent on activity and a points threshold in the plan («مثلاً پلن A برای بازاریاب نوع B»); re-presentation form and photos for inactive stores; link/credentials delivery after in-person registration with forced password change; **edit window of five minutes** after which changes need admin approval; visit reporting with limited access; active/inactive reporting by invoice window; photo capture by customer or marketer.

### پیوست ۷ — Admin panel (page 9, 10 clauses)

Member management (deactivate, delete, edit, verify); finance management (transactions, sold-product reports, manual purchase invoice, totals); sales reports and automatic messages after deposit/shipment/order via SMS, WhatsApp and push; product form with **price per user level**, attributes and filtering, short/long description, categories, gallery, warehouse; content and internal-messenger management; **each user level may hold one secondary level (e.g. a store level cannot be a marketer — per admin setting)**; report content/user buttons; admin-initiated tickets to all levels and replies; banners, static pages, menus, **gateways** and general settings; marketer/store/transaction/settlement reports **with Excel export**.

### پیوست ۸ — Mobile app (Android + PWA + iOS) (pages 9–10, 8 clauses)

Full Android app with all site features for the customer, **APK delivered for direct install**; iOS app either native or installable PWA for iPhone/iPad; PWA with push notifications on both platforms; full data sync with the web version (menu, orders, points, wallet); bespoke app design (splash, icon, login, home); full build package handed over (source, keys, publishing instructions); release preparation for Play Store and App Store (**developer-account costs and release approval are the client's**); updates via later versions per the support contract.

This appendix is the contractual root of `RB-MOB-01` (native mobile deferred) — see [`scope-boundaries.md`](scope-boundaries.md) §3.

### پیوست ۹ — SEO, optimisation, security, branding (page 10, 6 clauses)

Technical SEO (meta title/description, sitemap, clean URLs, structured data, load speed); speed target of an acceptable **GTmetrix score, aim B/A on core sections**; security (SSL, anti-brute-force, CAPTCHA anti-spam, role access control, input validation); initial **data import** of base items (categories, brands, sample products) from client-supplied data; branding services (UX/UI throughout, visual identity guidance, demo of all stages and capabilities); **training the client's team** on content, products, orders and settings.

### پیوست ۱۰ — Delivery, testing, training (page 10, 6 clauses)

Trial version on the client's server for acceptance testing; client runs acceptance scenarios and logs defects **within 3 working days**; contractor fixes and re-tests; final delivery after the client's **written satisfaction letter**; **4 in-person and 25 online training sessions** for admins and key users; handover of technical/user documentation, training videos and the source package.

### پیوست ۱۱ — Supplier and producer panel (pages 10–11, 20 clauses)

Independent panel per producer/supplier for stock, orders, pricing and price changes, comparable to the professional seller panel; conversion of any producer into a buying colleague with a fixed purchase discount from other suppliers (percentage and product type set by admin); dedicated producer page (services, products, map location, facilities, company history, images/video, allocated hosting space); product presentation in festivals and special discounts, shown on the home page; banner reservation requests for a period, sent to admin; broad or filtered notifications to customers with a count cap set by admin, paid or free; SMS panel with customer filtering and per-fee or free sending per admin setting; dedicated slideshow in set periods of the year; sample/portfolio request forms routed to the supplier panel; **auction section in the producer/supplier panel with suppliers, colleagues and consumers present**; panel access via the app anywhere; **incomplete-order button** to refer an incomplete order back to the buyer and avoid delay compensation; automatic negative points for repeated improper use of that button; order cancellation and refund instruction to the buyer's account from the supplier panel; recording shipment delay against the order schedule with waybill number and courier dispatch, **automatic deduction of the damage percentage from the invoice and deposit into the buyer's wallet**; admin manual charge/payment on the supplier account; supplier-registered extra cost beyond the invoice with a supplementary invoice to the buyer; audio/text/image attachments in order descriptions; nationwide active agencies per producer with location and details; product photography as training or a special service.

### پیوست ۱۲ — Consumer plan and customer club (page 11, 16 clauses)

Consumer price differing from colleague price, applied manually by admin or as a percentage on all products (**e.g. 35 % higher**); viewing distributors and agencies by city; order capture by an intermediary (e.g. a local shop) with address and location for installation and measurement; purchase voucher on invoice, applied to the next invoice within a window; full shop features (compare, search, postal shipping, ratings, surveys, standard pages); volume purchase with the discount set on the product; festival and birthday greetings from a stored birth date; **automatic delay compensation deducted from the supplier**; product and supplier review **up to one week after receipt**; first-invoice or admin-approved invoice discount on top of next-invoice rewards; **simultaneous marketer and consumer activity**; shipping type selection and free shipping in one city or nationwide; **deposit payment and settlement of the balance at shipment or at the customer's location**; returns within a set window for admin-approved items; favourites for products and producers; customer work photos under each product.

### پیوست ۱۳ — Financial reporting, settlement, bank connection (page 11, 7 clauses)

Filterable sales report for **manual settlement** with producers by period, amount, date and product type; daily payment report of purchased products to producer/supplier and admin; **Excel export for the bank** for group payment to suppliers on set dates; different points per product and producer; recording and tracking financial flow per user level (wallet, debtor/creditor, completed settlements); report of failed/returned transactions and **reconciliation with payment gateways**; **connection of 12 bank gateways with two simultaneously selectable and full transaction logging**.

### پیوست ۱۴ — Marketer field capabilities (page 11, 2 clauses)

1. Ability to record the **location** of the places a marketer visits.
2. **Live tracking** of marketers plus analysis of performance and **route** data.

Two clauses, no detail — the whole geolocation/privacy design (consent, retention, accuracy, battery, background tracking) is therefore an agent decision: `AMB-GEO-001`, `AMB-CTR-003`.

## 6. Numeric register — every figure in the contract

`V` = verified against the page render. `⚠` = exists in two conflicting versions.

| # | Figure | Meaning | Pointer | Check |
|---|---|---|---|---|
| 1 | 90,000,000 Toman | total fixed price | page 2 / ماده ۲ | V |
| 2 | 10,000,000 Toman | opening advance (text-layer revision only) | page 2 / ماده ۲ | ⚠ `AMB-CTR-005` |
| 3 | 80,000,000 Toman | final settlement one month after delivery + written approval (text-layer revision only) | page 2 / ماده ۲ | ⚠ `AMB-CTR-005` |
| 4 | 3 months / 90 calendar days | delivery period, from signing **and** full information receipt | page 2 / ماده ۳; page 1 | V |
| 5 | 1 month per phase | months 1/2/3 = build, debug, heavy test + final delivery | page 2 / ماده ۳ | V |
| 6 | 0.5 % per day | delay penalty rate | page 2 / ماده ۴ بند ۲ | V |
| 7 | 20 % | delay-penalty cap (= 18,000,000 T) | page 2 / ماده ۴ بند ۲ | V |
| 8 | 5 % per heading | penalty per unexecuted appendix heading (= 4,500,000 T) | page 3 / ماده ۴ بند ۱۱ | V |
| 9 | 10 % | cap of the heading penalty (= 9,000,000 T) | page 3 / ماده ۴ بند ۱۱ | V |
| 10 | 15 working days | delay beyond which the client may terminate and claim more | page 4 / ماده ۱۲ تبصره; page 3 / ماده ۹ بند ۳ | V |
| 11 | 15 days | window for the contractor to refund all payments after termination for delay | page 3 / ماده ۹ بند ۳ | V |
| 12 | 7 working days | cure period after a breach notice | page 3 / ماده ۹ بند ۲ | V |
| 13 | 20 days | direct-negotiation window before arbitration | page 3 / ماده ۶ | V |
| 14 | 5 working days | client's deadline to supply information/content | page 3 / ماده ۵ بند ۱ | V |
| 15 | 3 working days | client's feedback deadline per stage | page 3 / ماده ۵ بند ۴; page 2 / ماده ۳ | V |
| 16 | 7 working days | client silence = approval | page 3 / ماده ۵ بند ۴ | V |
| 17 | 3 days | notice of address change | page 4 / ماده ۱۱ | V |
| 18 | 24 working hours | first support response | page 4 / ماده ۱۲ بند ۲ | V |
| 19 | 3 working days | medium-bug fix | page 4 / ماده ۱۲ بند ۲ | V |
| 20 | 24 hours | critical-bug fix (outage, data loss) | page 4 / ماده ۱۲ بند ۲ | V |
| 21 | 5 years **or** until the end of the parties' company | free support period | page 4 / ماده ۱۲ بند ۱; page 1 | ⚠ `AMB-CTR-006` |
| 22 | 15 % | contractor's share of net profit | page 4 / ماده ۱۶ | V |
| 23 | 15 % | contractor's share of future development/maintenance/infrastructure cost | page 4 / ماده ۱۶ | V |
| 24 | 15 days | quarterly settlement deadline for the profit share | page 4 / ماده ۱۶ | V |
| 25 | 12 gateways | separate bank gateways supported simultaneously | page 5 / پیوست ۱ بند ۵; page 11 / پیوست ۱۳ بند ۷ | V |
| 26 | 2 gateways | two gateways selectable in one transaction | page 5 / پیوست ۱ بند ۵; page 11 / پیوست ۱۳ بند ۷ | V |
| 27 | 5 products | simultaneous comparison limit | page 5 / پیوست ۲ بند ۵ | V |
| 28 | 0.75 / 0.5 / 0.25 / 0.2 % | volume-purchase points coefficients | page 6 / پیوست ۴ بند ۳; بند ۱۱ | V |
| 29 | 0.2 / 0.1 % | next-invoice reward coefficients after a wholesale cash discount | page 7 / پیوست ۴ بند ۱۲ | V |
| 30 | 4× | next invoice must be at least four times the reward for full deduction; otherwise half | page 6 / پیوست ۴ بند ۴ | V |
| 31 | 4 points / month | example inactivity deduction | page 6 / پیوست ۴ بند ۶ | V |
| 32 | 100 points = 1 star | star per 100 purchase points, unlimited stars | page 7 / پیوست ۴ بند ۹ | V |
| 33 | 1–100 → 4 %; 100–200 → 5 % | tiered next-invoice discount (boundary 100 double-claimed) | page 7 / پیوست ۴ بند ۲۰; page 8 / پیوست ۵ بند ۱۶ | V, `AMB-LOY-004` |
| 34 | >200 points | threshold for an admin-set fixed Toman discount (`n`) | page 7 / پیوست ۴ بند ۲۱ | V |
| 35 | 5 to 10 parameters | invoice satisfaction rating parameters | page 7 / پیوست ۴ بند ۱۷; page 8 / پیوست ۵ بند ۳۴ | V |
| 36 | 1 week | complaint window after shipment; rating window after receipt | page 7 / پیوست ۴ بند ۱۵–۱۷; page 11 / پیوست ۱۲ بند ۹ | V |
| 37 | 2 months | example violation look-back shown at order time | page 7 / پیوست ۴ بند ۱۹ | V |
| 38 | 5 minutes | marketer's post-registration edit window | page 9 / پیوست ۶ بند ۹ | V |
| 39 | 4 + 25 sessions | in-person and online training sessions | page 10 / پیوست ۱۰ بند ۵ | V |
| 40 | 3 working days | client's acceptance-defect logging window | page 10 / پیوست ۱۰ بند ۲ | V |
| 41 | 35 % | example consumer-price uplift over colleague price | page 11 / پیوست ۱۲ بند ۱ | V |
| 42 | 35,000,000 Toman / 1 month | the **separate** reservation contract (not this one) | page 9 / پیوست ۵ note | V |
| 43 | 5 levels | user levels | page 5 / پیوست ۳ | V |
| 44 | 16 articles / 14 appendices / 203 clauses | document structure | page 4 / ماده ۱۴; pages 5–11 | V |

## 7. What the contract does not say

Recorded here so that nobody "fills the gap" in code by accident. Each item has an ambiguity row.

- **No gateway names.** "12 bank gateways" is never enumerated — `AMB-PAY-002`.
- **No settlement cycle, no commission rate, no escrow rule.** Who holds funds between payment and supplier payout is unstated — `AMB-FIN-001`.
- **No commission percentages for marketers**, only the plan letters A/B/C/D and the mechanisms — `AMB-CMP-001`.
- **No points formula**, only four coefficients and a set of conditions — `AMB-LOY-003`.
- **No precedence order** among role price, volume discount, promotion, coupon, loyalty discount, first-invoice discount — `AMB-PRI-001`.
- **No order-state model**, no cancellation windows except the complaint freeze — `AMB-ORD-008`.
- **No data-retention period** for personal data, location traces or chat history — `AMB-CTR-003`, `AMB-GEO-001`.
- **No SMS/WhatsApp/email provider**, no message templates, no cost split beyond "per admin setting" — `AMB-NTF-001`.
- **No language/currency/timezone statement**; no multi-currency, no English UI requirement — `AMB-CTR-002`.
- **No tax/VAT treatment** on invoices (only the statement that the contractor bears its own income tax) — `AMB-INV-001`.
- **No definition of "سرفصل"** for the 5 % penalty — `AMB-CTR-008`.
- **No acceptance criteria per clause**, and no definition of "done" beyond «نامه رضایت کتبی» — the delivery gate is a human letter, not a metric.
- **No conflict-of-laws clause**, no governing law named — disputes route through negotiation → arbitrator → «مرجع صالح ذیربط» → دادگستری (page 3 / ماده ۶).
- **No service levels for the hosted product** (uptime, RTO/RPO) — only daily backup (page 5 / پیوست ۱ بند ۹).

## 8. Reading the contract as an engineering brief

Three structural observations that shape the plan:

1. **The obligation is clause-complete, not feature-complete.** ماده ۱ makes every appendix clause a contractual promise, and ماده ۴ بند ۱۱ prices each missing numbered heading at 5 %. Silence about a detail is not permission to omit; it is a place where an explicit decision must be recorded (that is what [`../ambiguities/ambiguity-register.md`](../ambiguities/ambiguity-register.md) is for).
2. **The money clauses assume a platform-held wallet economy** (wallet rewards, deposits, delay compensation credited to the buyer's wallet, refunds to شبا, settlement with suppliers, Excel bank files) without ever saying who legally holds the funds. The engineering baseline resolves this conservatively (`AMB-FIN-001`, DEC-003) and the ledger design follows from it.
3. **The schedule is three months for 203 clauses** including 12 gateway integrations, a full B2B/B2C marketplace, classifieds, a services directory, tenders/auctions, chat, PWA, admin, reporting and four training days. The contractual date and the realistic date are different objects; the plan must be explicit about which one it tracks — `AMB-CTR-004`, and the phased plan in [`../delivery/phases-and-milestones.md`](../delivery/phases-and-milestones.md).
