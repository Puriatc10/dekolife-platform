# Glossary — Persian ↔ English domain terms

| | |
|---|---|
| **Purpose** | One canonical translation per domain term so code, UI copy, API fields, tests and contracts never drift. Persian is the UI language; English is the engineering language. |
| **Audience** | Backend/frontend engineers, UX writers, QA, translators, the client's operators. |
| **Status** | Baseline v1 — 2026-11-25. Terms marked **(contract)** come verbatim from the contract; terms marked **(ops)** are trade jargon the client's users speak and the UI must use. |
| **Rule** | When a term has a canonical module, the module name is given so nobody invents a second concept for the same thing. Module names follow [`../canonical-context.md`](../canonical-context.md) §3. |

> **Source:** Contract `DK-1405-01` pages 1–11 and appendices 1–14 ([`../contract/contract-extracted-text.txt`](../contract/contract-extracted-text.txt)). Clause pointers below are the *first* place a term is defined, not every place it appears. Business semantics of each term are normative in [business-rules.md](./business-rules.md).

## A. Actors and levels

| Persian | English (canonical) | Meaning in this product | Module | First source |
|---|---|---|---|---|
| سامانه دکوالیف | Dekolife platform | The product; the whole multi-vendor system | — | page 1 / ماده ۱ |
| سطح کاربری | user level / role | One of the five independent levels; implemented as a role, not a column | Identity | page 5 / پیوست ۳ opening |
| سطح ثانویه | secondary level | An optional second role granted to a user by admin | Identity | page 9 / پیوست ۷ / بند ۶ |
| فروشگاه | store | Goods-providing shop on the platform; the wholesale buyer | Merchants | page 5 / پیوست ۳ / ۳-۱ |
| عرضهکننده کالا | goods provider / seller | Synonym for store when emphasising the selling side; also used for suppliers who list goods | Merchants | page 5 / پیوست ۳ / ۳-۱ |
| همکار | colleague (trade partner) | A registered store treated as a trade partner: sees colleague price, earns points and stars, can be visited by a marketer | Merchants | page 6 / پیوست ۳ / ۳-۱ / ۸; page 7 / پیوست ۴ / بند ۹ |
| همکار خریدار | buying colleague | A producer/supplier that also buys from other suppliers at a fixed discount | Merchants | page 10 / پیوست ۱۱ / بند ۲ |
| بازاریاب | marketer / field agent | Door-to-door salesperson who registers stores and owns them by QR/link | Marketing | page 6 / پیوست ۳ / ۳-۲ |
| مصرفکننده / مشتری | consumer / customer | End buyer at consumer price; may also be a marketer | Users | page 6 / پیوست ۳ / ۳-۳ |
| تأمینکننده | supplier | Supplier of goods, with its own panel | Merchants | page 6 / پیوست ۳ / ۳-۴ |
| تولیدکننده | producer / manufacturer | Factory-side supplier; same panel as supplier, different commercial weight | Merchants | page 6 / پیوست ۳ / ۳-۴; page 10 / پیوست ۱۱ |
| ادمین | admin | Platform operator with full console, 2FA, separate panel | Admin | page 6 / پیوست ۳ / ۳-۵; page 5 / پیوست ۱ / بند ۷ |
| توزیعکننده | distributor | Downstream seller receiving the order form; used interchangeably with store/supplier in the contract | Merchants | page 8 / پیوست ۵ / بند ۲۴, بند ۴۰ |
| رابط | intermediary (local referrer) | A local shop that takes a consumer's order on the platform's behalf, with address/location for installation | Patogh | page 11 / پیوست ۱۲ / بند ۳ |
| نمایندگی | agency / dealership | A producer's local branch, listed with location so buyers can be referred for measurement/installation | Merchants | page 11 / پیوست ۱۱ / بند ۱۹ |
| استادکار | artisan / tradesman | Installer, fitter, repairer listed in the local-services directory | Patogh | page 7 / پیوست ۵ / بند ۵ |
| نصاب | installer / fitter | Specifically a flooring/parquet installer | Patogh | page 8 / پیوست ۵ / بند ۱۳ |

## B. Commercial objects

| Persian | English (canonical) | Meaning in this product | Module | First source |
|---|---|---|---|---|
| محصول | product | A sellable item with per-role prices, attributes, gallery, stock | Catalog | page 5 / پیوست ۲ / بند ۴ |
| دستهبندی | category | Product taxonomy node; admin-controlled | Catalog | page 5 / پیوست ۲ / بند ۳ |
| طبقهبندی | classified taxonomy | The category tree that must exist before a classified ad can be posted | Classifieds | page 7 / پیوست ۵ / بند ۴ |
| ویژگی محصول | product attribute | Parameter used for specs, filters and comparison | Catalog | page 9 / پیوست ۷ / بند ۴ |
| موجودی / انبار | stock / inventory | Quantity available to promise | Inventory | page 9 / پیوست ۷ / بند ۴; page 10 / پیوست ۱۱ / بند ۱ |
| سبد خرید | shopping cart | Pre-order basket, role-priced | Cart | page 5 / پیوست ۲ / بند ۶ |
| پیشفاکتور | pre-invoice / proforma | Consumer-facing price preview before payment, showing that prices differ by role | Invoices | page 8 / پیوست ۵ / بند ۴۲ |
| فاکتور | invoice | The issued bill of sale; the unit that earns points and carries the وارن button | Invoices | page 6 / پیوست ۴ / بند ۱ |
| فاکتور مکمل | supplementary invoice | Later extra cost recorded by the supplier above the original invoice and sent to the buyer | Invoices | page 11 / پیوست ۱۱ / بند ۱۷ |
| فاکتور خرید دستی | manual purchase invoice | An invoice an admin creates by hand (phone/offline order) | Invoices / Admin | page 9 / پیوست ۷ / بند ۲ |
| سفارش | order | Purchase request; parent order with per-seller sub-orders | Orders | page 6 / پیوست ۳ / ۳-۱ / ۱ |
| سفارش ناقص | incomplete order | A supplier's flag that the order cannot be fulfilled as placed; refers it back to the buyer and suspends delay penalty | Orders | page 10 / پیوست ۱۱ / بند ۱۲–۱۳ |
| زیرسفارش | sub-order | The per-seller slice of a parent order; the unit of fulfilment, commission and settlement | Orders | [`../canonical-context.md`](../canonical-context.md) §5 |
| بارنامه | waybill | Carrier document number recorded when a shipment is dispatched or delayed | Shipping | page 10 / پیوست ۱۱ / بند ۱۵ |
| پیک | courier | Local same-city delivery agent | Shipping | page 10 / پیوست ۱۱ / بند ۱۵ |
| بیعانه | deposit / down payment | Partial payment at order time, balance settled on delivery or on site | Payments | page 11 / پیوست ۱۲ / بند ۱۳ |
| مرجوعی | return | Goods sent back within the accepted window for approved items | Returns | page 10 / پیوست ۱۱ / بند ۱۴; page 11 / پیوست ۱۲ / بند ۱۴ |
| سمپل | sample | Physical sample requested by a store/consumer from a supplier | Samples | page 6 / پیوست ۳ / ۳-۱ / ۹; page 10 / پیوست ۱۱ / بند ۹ |
| نمونه کار | work portfolio / reference job | Photos of completed installations, shown under a product | Samples / Catalog | page 11 / پیوست ۱۲ / بند ۱۶ |
| کاتالوگ | catalogue | Supplier's product catalogue, requestable by stores | Samples | page 6 / پیوست ۳ / ۳-۱ / ۹ |

## C. Money, pricing and settlement

| Persian | English (canonical) | Meaning in this product | Module | First source |
|---|---|---|---|---|
| قیمت همکار | colleague price | The trade price for registered stores | Pricing | page 11 / پیوست ۱۲ / بند ۱ |
| قیمت مصرفکننده | consumer price | Retail plane; default +35% over colleague price unless admin overrides | Pricing | page 11 / پیوست ۱۲ / بند ۱ |
| قیمت عمده | wholesale price | Volume price, lower than colleague price at qualifying quantity | Pricing | page 6 / پیوست ۳ / ۳-۱ / ۸ |
| خرید حجمی | volume purchase | A purchase crossing a quantity/amount band that unlocks wholesale price and/or cash discount | Pricing / Promotions | page 7 / پیوست ۴ / بند ۱۱–۱۲ |
| تخفیف عمده | volume discount | Cash discount granted for a volume purchase, independent of the buyer's points | Promotions | page 7 / پیوست ۴ / بند ۱۲ |
| بن | voucher (bon) | A granted purchase credit tied to a next invoice within a window | Promotions | page 9 / پیوست ۵ / بند ۴۳; page 11 / پیوست ۱۲ / بند ۴ |
| کوپن تخفیف / کد تخفیف | coupon / discount code | A code entered in the cart that reduces the payable amount | Promotions | page 5 / پیوست ۲ / بند ۶; page 9 / پیوست ۵ / بند ۴۳ |
| کیف پول | wallet | Per-user balance holding rewards, compensations and refunds; spendable on a new purchase, withdrawable only via settlement rules | Finance | page 6 / پیوست ۴ / بند ۷; page 11 / پیوست ۱۳ / بند ۵ |
| پاداش نقدی | cash reward | Points converted to money in the wallet once a threshold is met | Loyalty | page 6 / پیوست ۴ / بند ۷ |
| تسویه | settlement | Paying out a role's earned balance for a period | Finance | page 6 / پیوست ۳ / ۳-۱ / ۴ |
| درخواست تسویه | settlement request | A store's or marketer's formal request to be paid | Finance | page 6 / پیوست ۳ / ۳-۲ / ۲ |
| صورتحساب | statement | The itemised account view a role sees | Finance | page 6 / پیوست ۳ / ۳-۱ / ۴ |
| گردش مالی | financial ledger / transaction history | Time-ordered money movements for a role | Finance | page 6 / پیوست ۳ / ۳-۱ / ۴ |
| بدهکار / بستانکار | debit / credit | The two sides of a role's account balance | Finance | page 11 / پیوست ۱۳ / بند ۵ |
| پورسانت | commission | Marketer (or referrer) earning, computed from attributed invoices | Compensation | page 7 / پیوست ۴ / بند ۲۲; page 9 / پیوست ۶ / بند ۳ |
| پلن A/B/C/D | plan A/B/C/D | Four marketer compensation models: sales commission, per-visit fee, hybrid, fixed salary | Compensation | page 9 / پیوست ۶ / بند ۵–۶ |
| درگاه پرداخت | payment gateway | One of 12 bank gateways; selectable per payment | Payments | page 5 / پیوست ۱ / بند ۵; page 11 / پیوست ۱۳ / بند ۷ |
| شبا | SHEBA (IBAN) | Iranian bank account identifier used for refunds and payouts | Finance | page 8 / پیوست ۵ / بند ۱۵ |
| کارمزد درگاه | gateway fee | Acquirer fee deducted before net profit | Payments | page 4 / ماده ۱۶ |
| مغایرتگیری | reconciliation | Matching platform transactions against gateway reports | Payments / Reporting | page 11 / پیوست ۱۳ / بند ۶ |
| خروجی اکسل | Excel export | The bank-ready payout file and admin report export | Reporting | page 9 / پیوست ۷ / بند ۱۰; page 11 / پیوست ۱۳ / بند ۳ |

## D. Loyalty engine

| Persian | English (canonical) | Meaning in this product | Module | First source |
|---|---|---|---|---|
| امتیاز | point | Non-monetary score earned per invoice line, by product and volume coefficient | Loyalty | page 6 / پیوست ۴ / بند ۱–۳ |
| ضریب امتیاز | points coefficient | The percentage applied to invoice value to derive points (0.75/0.5/0.25/0.2, or 0.2/0.1 for volume-discounted invoices) | Loyalty | page 6 / پیوست ۴ / بند ۳; page 7 / پیوست ۴ / بند ۱۲ |
| ضریب کاهش امتیاز | diminishing coefficient | Rule that larger volumes earn fewer points per Toman, to reward continuity over one-off bulk | Loyalty | page 8 / پیوست ۵ / بند ۲۱ |
| پاداش پلکانی | tiered reward | Reward whose size depends on the points tier reached | Loyalty | page 1 / title; page 7 / پیوست ۴ / بند ۲۰ |
| تخفیف پلکانی | tiered discount | Automatic next-invoice discount by points tier: 4% (1–100), 5% (100–200), fixed n Toman (>200) | Loyalty | page 7 / پیوست ۴ / بند ۲۰–۲۱; page 8 / پیوست ۵ / بند ۱۶ |
| امتیاز تخفیف | discount credit | The redeemable value that a tier grants on the next invoice | Loyalty | page 7 / پیوست ۴ / بند ۲۰ |
| ستاره | star | Badge awarded per 100 purchase points, unlimited count, shown on the profile | Loyalty | page 7 / پیوست ۴ / بند ۹ |
| امتیاز تخلف | violation point (negative) | Negative score recorded against a supplier for confirmed violations, shown after admin approval | Reviews / Marketing | page 8 / پیوست ۵ / بند ۱۴ |
| عدم فعالیت | inactivity | No purchase and no invoice within a configured window; triggers point deduction and store downgrade | Loyalty / Merchants | page 6 / پیوست ۴ / بند ۶; page 7 / پیوست ۴ / بند ۲۳ |
| باشگاه مشتریان | customer club | The consumer-facing loyalty programme (points, vouchers, occasions, birthdays) | Loyalty | page 11 / پیوست ۱۲ heading |

## E. Field operations and channels

| Persian | English (canonical) | Meaning in this product | Module | First source |
|---|---|---|---|---|
| ویزیت | visit | A marketer's recorded physical call on a store, with geolocation | FieldOps | page 9 / پیوست ۶ / بند ۳ |
| فرم پرزن | presentation form | The separate form a marketer fills when presenting to an inactive store (owner photo, storefront, card) | Marketing | page 9 / پیوست ۶ / بند ۷ |
| لوکیشن | geolocation | Coordinates captured for a visit or a store, plus Google Maps link | FieldOps | page 9 / پیوست ۶ / بند ۲; page 11 / پیوست ۱۴ / بند ۱ |
| رهگیری | tracking | Live tracking of marketer movement and performance analysis | FieldOps | page 11 / پیوست ۱۴ / بند ۲ |
| کد اختصاصی | referral code | The marketer's unique code used on cards and links | Marketing | page 6 / پیوست ۳ / ۳-۲ / ۴ |
| QR اختصاصی | personal QR | Generated QR that binds a scanning store to that marketer | Marketing | page 7 / پیوست ۵ / بند ۱ |
| دیوار | Divar section (classifieds) | The classified-ads area, named after the Iranian classifieds site | Classifieds | page 7 / پیوست ۵ / بند ۴ |
| پتوق | Patogh (local services) | The local services/artisan directory area | Patogh | page 7 / پیوست ۵ / بند ۵; page 8 / پیوست ۵ / بند ۱۳ |
| مناقصه | tender | A buyer's published request for bids on a large project; suppliers bid | Tenders | page 7 / پیوست ۵ / بند ۶ |
| مزایده | auction | A seller-run auction where suppliers, colleagues and consumers bid | Tenders | page 10 / پیوست ۱۱ / بند ۱۰ |
| استعالم | price inquiry / RFQ | A request for quotation attached to a tender | Tenders | page 8 / پیوست ۵ / بند ۳۶ |
| تیکت | ticket | Structured support/communication thread between any two roles | Messaging | page 6 / پیوست ۳ / ۳-۱ / ۷ |
| پیامرسان داخلی | internal messenger | Persistent chat with text, image, audio, file, tied to the order | Messaging | page 8 / پیوست ۵ / بند ۲۸; page 9 / پیوست ۷ / بند ۵ |
| نوتیفیکیشن سفارشی | custom notification | Admin/supplier-composed push/SMS/WhatsApp/email blast with audience and schedule | Notifications | page 7 / پیوست ۵ / بند ۹ |
| نظرسنجی | poll / survey | Admin-created poll; also per-product rating by verified buyers | Reviews | page 7 / پیوست ۵ / بند ۱۰–۱۱ |
| عالقهمندیها | wishlist / favourites | Saved products, stores and producers | Catalog / Users | page 6 / پیوست ۳ / ۳-۳ / ۳; page 11 / پیوست ۱۲ / بند ۱۵ |

## F. Disputes, compliance and platform operations

| Persian | English (canonical) | Meaning in this product | Module | First source |
|---|---|---|---|---|
| شکایت | complaint | A buyer's formal dispute; freezes preparation and holds supplier payment while open | Orders / Admin | page 7 / پیوست ۴ / بند ۱۵ |
| توقف آمادهسازی سفارش | preparation hold | The freeze state applied to a sub-order after invoice and payment, or on complaint | Orders | page 7 / پیوست ۴ / بند ۱۶ |
| وارن | warning (violation report) | The button on every invoice that reports a distributor/producer violation to admin | Reviews / Admin | page 7 / پیوست ۴ / بند ۱۸ |
| تخلف | violation | A confirmed breach recorded against a supplier and shown to peers at checkout | Admin | page 7 / پیوست ۴ / بند ۱۹ |
| خسارت تأخیر | delay compensation | Automatic or admin-approved payment from supplier to buyer for late dispatch | Orders / Finance | page 7 / پیوست ۴ / بند ۱۴; page 10 / پیوست ۱۱ / بند ۱۵ |
| جریمه تأخیر | delay penalty (contractor) | A *different* concept: the 0.5%/day penalty the contractor owes the client under `ماده ۴ بند ۲`. Never confuse with delay compensation. | — | page 3 / ماده ۴ / بند ۲ |
| کد ملی | national ID | Iranian national identifier; one of three uniqueness keys for store registration | Identity | page 7 / پیوست ۵ / بند ۳ |
| کد پستی | postal code | Second uniqueness key for store registration | Identity | page 7 / پیوست ۵ / بند ۳ |
| حق عضویت | membership right | A user's entitlement to their role/level, transferable only with admin approval | Identity | page 8 / پیوست ۵ / بند ۳۹ |
| گزارش تخلفات عرضهکننده | supplier violation report | The peer-visible list of a supplier's recent violations shown at checkout | Reviews | page 7 / پیوست ۴ / بند ۱۹ |
| الگ رویداد | audit log | Immutable record of sensitive events (login, price change, role change, settlement) | Admin | page 5 / پیوست ۱ / بند ۱۰ |
| غیرفعالسازی | deactivation | Admin disabling a user or store; also automatic for inactive stores | Admin / Merchants | page 9 / پیوست ۷ / بند ۱; page 7 / پیوست ۴ / بند ۲۳ |
| جشنواره | festival / campaign | Seasonal promotion section surfaced on the homepage | Promotions / Cms | page 10 / پیوست ۱۱ / بند ۴ |
| بنر تبلیغاتی | advertising banner | Time-boxed homepage banner requested by a supplier and approved by admin | Cms | page 10 / پیوست ۱۱ / بند ۵ |
| اسلایدشو | slideshow | Supplier's own rotating showcase for a date range | Cms | page 10 / پیوست ۱۱ / بند ۸ |
| کد رهگیری | tracking code | Customer-facing order lookup code | Orders | page 5 / پیوست ۲ / بند ۱۱ |
| پنل کاربری | user panel | The role-specific dashboard | Users | page 5 / پیوست ۲ / بند ۷ |

## G. Naming rules

1. **One concept, one English word.** "Store" is `store` in code, never `vendor`, `seller` or `shop`. "Supplier" is `supplier`, never `producer` in identifiers — `producer` is a *kind* of supplier (`supplier.kind = producer|distributor`).
2. **Persian UI copy follows this table**, including the colloquial terms (`همکار`, `پتوق`, `دیوار`, `بن`, `وارن`, `بیعانه`) because the client's users speak them; do not "correct" them to formal Persian.
3. **`همکار` in UI means registered store**, not "colleague" in the HR sense; the English code term is `store`, and the trade-partner relationship is `store.is_colleague = true`.
4. **Never translate `وارن` to "warning" in UI** — the button label stays `وارن`; the English internal term is `violation_report`.
5. **Money terms carry a unit**: every amount is integer Toman (`*_amount`), and `قیمت` alone is ambiguous — always say colleague price, consumer price or wholesale price.
