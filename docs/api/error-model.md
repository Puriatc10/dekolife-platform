# Error Model — Dekolife Platform

**Purpose:** The complete, stable catalogue of API error codes with their HTTP mapping, Persian
user-facing messages, logging rules and client handling guidance.
**Audience:** Backend engineers, frontend/PWA engineers, QA, support and operations.
**Status:** Baseline (v1). Codes are append-only: once published, a code never changes meaning
and is never removed.
**Source:** `docs/canonical-context.md` §5 (server-side authorisation, audited sensitive
events), §6 (Persian UI copy, explicit ambiguity instead of invented facts); contract appendix 9
clause 3 (access control, anti-spam, input validation), appendix 1 clause 10 (event logging);
[`API-guidelines.md`](./API-guidelines.md) §11 (envelope).

---

## 1. Envelope (normative)

Every non-2xx response body is exactly:

```json
{
  "error": {
    "code": "insufficient_stock",
    "message": "موجودی کافی برای این محصول وجود ندارد.",
    "request_id": "01J9Z5Q7K8N4M3P2R1S0T9V8W7",
    "details": [
      {
        "field": "items.0.quantity",
        "code": "max_available",
        "message": "حداکثر ۳ عدد موجود است.",
        "meta": { "available": 3 }
      }
    ]
  }
}
```

| Member | Type | Rules |
|---|---|---|
| `code` | string | Stable, `lower_snake_case`, ASCII, never localised, ≤ 48 chars. Identifies the **class** of failure |
| `message` | string | Persian (or `Accept-Language`), safe to display verbatim, no technical detail |
| `request_id` | string | ULID, equals the `X-Request-Id` response header, present even on `500` |
| `details` | array | Always present (`[]` when nothing field-specific). Each entry: `field?`, `code`, `message`, `meta?` |

Hard rules:

1. A client can branch on `error.code` alone. `error.message` may be reworded at any time.
2. `details[].code` uses the same namespace and stability guarantees, scoped to the parent code
   (e.g. `insufficient_stock` → `max_available`).
3. No stack trace, SQL fragment, file path, class name, provider text, token or PII ever appears
   in the body — including on `500`.
4. A `4xx` never returns `200`-shaped data. A partial success (batch import, bulk notification)
   is `200`/`202` **with** a `summary` object listing failures, not an error.
5. Gateway and carrier raw responses are stored (redacted) for support, never echoed.

---

## 2. Naming and ownership

`<domain>_<condition>` where domain is one of:

| Domain | Owner module | Examples |
|---|---|---|
| `validation` | Shared (FormRequests) | `validation_failed`, `unexpected_field` |
| `auth` | Identity | `invalid_credentials`, `otp_expired` |
| `forbidden` | Shared (policies) | `forbidden`, `store_not_active` |
| `not_found` | Shared (route model binding) | `order_not_found` |
| `conflict` | Per module | `state_conflict`, `idempotency_key_reused` |
| `business` | Per module | `insufficient_stock`, `return_window_closed` |
| `rate` | Shared | `rate_limited` |
| `upstream` | Payments / Notifications / Shipping | `gateway_timeout` |
| `server` | Shared | `internal_error` |

Rules:

1. A code is added by PR that also adds: the Persian string, the test, and a row in this file.
2. A code is **never** reused for a different condition and never deleted; it may be marked
   `deprecated` in this table and stop being emitted after the 90-day window.
3. Two modules do not define the same condition with two codes. If a condition is generic
   (not found, forbidden), the shared code is used and `details[].meta` carries the specifics.

---

## 3. HTTP mapping

| Family | HTTP | Retryable by client | Notes |
|---|---|---|---|
| `validation_*` / field problems | `422` | No (fix input) | At least one `details` entry |
| `auth_*` (missing/expired token) | `401` | No (re-authenticate) | `WWW-Authenticate: Bearer` |
| `auth_*` (locked / 2FA required) | `423` / `202` | No | 2FA challenge is `202`, not an error body |
| `forbidden_*` | `403` | No | Ownership failures on scoped resources return `404` instead |
| `not_found_*` | `404` | No | Also used to hide existence |
| `conflict_*` | `409` | Sometimes (re-read, then retry) | Concurrency and duplicate cases |
| `business_*` | `422` | No (business condition) | `422` not `400`: input parsed, rule failed |
| `rate_limited` | `429` | Yes, after `Retry-After` | Always includes `Retry-After` |
| `upstream_*` | `502` / `504` | Yes, with backoff (idempotent ops) | Never exposes provider text |
| `server_*` | `500` / `503` | Yes, with backoff | `503` carries `Retry-After` |

Why `422` for business rules: the request is syntactically valid and authorised, but cannot be
executed in the current state of the world. `409` is reserved for state/concurrency conflicts on
a specific resource, which is what distinguishes "try again later" from "not possible".

---

## 4. Catalogue

### 4.1 Validation

| Code | HTTP | Persian message (`errors.validation.*`) | Notes |
|---|---|---|---|
| `validation_failed` | 422 | اطلاعات ارسالی معتبر نیست. | Generic wrapper; `details` carries each field |
| `invalid_json` | 400 | ساختار درخواست قابل خواندن نیست. | Malformed body; never includes the parse error |
| `unexpected_field` | 422 | فیلد ناشناخته در درخواست وجود دارد. | Unknown body key (typo protection) |
| `invalid_datetime` | 422 | قالب تاریخ یا زمان معتبر نیست. | Jalali or offset-less rejected |
| `invalid_iban` | 422 | شماره شبا معتبر نیست. | Check-digit validation |
| `invalid_national_id` | 422 | کد ملی معتبر نیست. | Check-digit validation |
| `invalid_postal_code` | 422 | کد پستی باید ۱۰ رقم باشد. | — |
| `invalid_mobile` | 422 | شماره موبایل معتبر نیست. | Must match `09xxxxxxxxx` |
| `invalid_cursor` | 400 | صفحه‌بندی نامعتبر است؛ فهرست را از ابتدا بارگذاری کنید. | Cursor with changed filters |
| `unknown_filter` | 422 | فیلتر انتخابی پشتیبانی نمی‌شود. | Whitelist violation |
| `unknown_sort` | 422 | ترتیب انتخابی پشتیبانی نمی‌شود. | Whitelist violation |
| `unknown_include` | 422 | روابط درخواستی پشتیبانی نمی‌شود. | Whitelist violation |
| `query_too_short` | 422 | عبارت جستجو باید حداقل ۲ نویسه باشد. | Search `q` |
| `invalid_enum_value` | 422 | مقدار انتخابی مجاز نیست. | `details[].meta.allowed[]` |
| `captcha_failed` | 422 | تأیید امنیتی ناموفق بود؛ دوباره تلاش کنید. | appendix 9 clause 3 |
| `file_too_large` | 422 | حجم فایل بیش از حد مجاز است. | `meta.max_bytes` |
| `mime_not_allowed` | 422 | نوع فایل مجاز نیست. | `meta.allowed[]` |
| `image_corrupt` | 422 | تصویر قابل پردازش نیست. | — |
| `duration_too_long` | 422 | مدت فایل صوتی بیش از حد مجاز است. | Voice ≤ 120 s (appendix 5 clause 41) |
| `virus_detected` | 422 | فایل آلوده شناسایی شد و پذیرفته نشد. | Never retried with the same file |
| `too_many_items` | 422 | تعداد اقلام بیش از حد مجاز است. | Compare (5), attachments (5), selects |

### 4.2 Authentication

| Code | HTTP | Persian message (`errors.auth.*`) | Notes |
|---|---|---|---|
| `unauthenticated` | 401 | برای انجام این عملیات باید وارد شوید. | No/invalid header |
| `token_expired` | 401 | نشست شما منقضی شده است؛ دوباره وارد شوید. | Idle or absolute expiry |
| `token_revoked` | 401 | این نشست باطل شده است. | Logout-all or device revoke |
| `invalid_credentials` | 401 | شماره موبایل یا رمز عبور نادرست است. | Same message for unknown user (no enumeration) |
| `otp_invalid` | 422 | کد تأیید نادرست است. | — |
| `otp_expired` | 422 | کد تأیید منقضی شده است؛ کد جدید بگیرید. | — |
| `otp_too_many_attempts` | 429 | تعداد تلاش‌های نادرست زیاد است؛ کمی بعد دوباره تلاش کنید. | With `Retry-After` |
| `otp_challenge_not_found` | 404 | درخواست تأیید یافت نشد یا منقضی شده است. | — |
| `account_locked` | 423 | حساب شما موقتاً قفل شده است. | Brute force; `meta.locked_until` |
| `account_suspended` | 403 | حساب شما غیرفعال شده است؛ با پشتیبانی تماس بگیرید. | Admin suspension |
| `account_pending_verification` | 403 | حساب شما هنوز تأیید نشده است. | Mobile or documents |
| `two_factor_required` | 202 | ورود دو مرحله‌ای برای این حساب الزامی است. | Returns `challenge_token`, not an error |
| `two_factor_invalid` | 422 | کد ورود دو مرحله‌ای نادرست است. | Includes recovery-code attempt |
| `password_reset_expired` | 422 | کد بازیابی منقضی شده است. | — |
| `mobile_already_registered` | 409 | این شماره موبایل قبلاً ثبت شده است. | Registration/login disambiguation |
| `email_already_registered` | 409 | این ایمیل قبلاً ثبت شده است. | — |
| `weak_password` | 422 | رمز عبور انتخابی به‌اندازه کافی قوی نیست. | `meta.policy` |

### 4.3 Authorisation

| Code | HTTP | Persian message (`errors.auth.*` / `errors.forbidden.*`) | Notes |
|---|---|---|---|
| `forbidden` | 403 | دسترسی به این بخش مجاز نیست. | Policy denial |
| `insufficient_role` | 403 | سطح کاربری شما برای این عملیات کافی نیست. | Role missing |
| `forbidden_transition_actor` | 403 | شما اجازه انجام این تغییر وضعیت را ندارید. | Wrong actor for a state transition |
| `store_not_active` | 403 | فروشگاه شما فعال نیست. | Panel access before approval |
| `supplier_not_active` | 403 | حساب تأمین‌کننده فعال نیست. | — |
| `marketer_suspended` | 403 | حساب بازاریاب تعلیق شده است. | — |
| `read_only_impersonation` | 403 | در حالت مشاهده به‌جای کاربر، امکان تغییر وجود ندارد. | Admin impersonation |
| `ip_not_allowed` | 403 | دسترسی از این شبکه مجاز نیست. | Admin IP allow-list |
| `document_not_approved` | 403 | مدارک شما تأیید نشده است. | Gate for sensitive actions |

### 4.4 Not found

| Code | HTTP | Persian message (`errors.not_found.*`) | Notes |
|---|---|---|---|
| `not_found` | 404 | مورد درخواستی یافت نشد. | Generic |
| `order_not_found` | 404 | سفارش یافت نشد. | Also used for another user's order |
| `sub_order_not_found` | 404 | سفارش فروشنده یافت نشد. | — |
| `product_not_found` | 404 | محصول یافت نشد یا دیگر در دسترس نیست. | — |
| `variant_not_found` | 404 | تنوع انتخابی محصول یافت نشد. | — |
| `invoice_not_found` | 404 | فاکتور یافت نشد. | — |
| `payment_not_found` | 404 | تراکنش یافت نشد. | — |
| `store_not_found` | 404 | فروشگاه یافت نشد. | — |
| `supplier_not_found` | 404 | تأمین‌کننده یافت نشد. | — |
| `ticket_not_found` | 404 | تیکت یافت نشد. | — |
| `conversation_not_found` | 404 | گفتگو یافت نشد. | — |
| `listing_not_found` | 404 | آگهی یافت نشد یا منقضی شده است. | — |
| `return_not_found` | 404 | درخواست مرجوعی یافت نشد. | — |
| `settlement_not_found` | 404 | تسویه یافت نشد. | — |
| `media_not_found` | 404 | فایل یافت نشد یا منقضی شده است. | Expired signed URL |
| `export_not_found` | 404 | فایل خروجی یافت نشد یا منقضی شده است. | 7-day TTL |
| `page_not_found` | 404 | صفحه درخواستی وجود ندارد. | CMS slug |

### 4.5 Conflict

| Code | HTTP | Persian message (`errors.conflict.*`) | Retry guidance |
|---|---|---|---|
| `state_conflict` | 409 | این مورد هم‌زمان توسط کاربر دیگری تغییر کرده است؛ صفحه را تازه کنید. | Re-read, then retry with the new `lock_version` |
| `idempotency_key_reused` | 409 | این درخواست با کلید تکراری و محتوای متفاوت ارسال شده است. | Do not retry; generate a new key |
| `idempotency_in_progress` | 409 | درخواست قبلی در حال پردازش است؛ لحظه‌ای بعد دوباره تلاش کنید. | Retry after `Retry-After` |
| `duplicate_resource` | 409 | مورد مشابهی از قبل ثبت شده است. | No |
| `slug_taken` | 409 | این نشانی (slug) قبلاً استفاده شده است. | Choose another |
| `code_taken` | 409 | این کد قبلاً استفاده شده است. | — |
| `order_transition_not_allowed` | 409 | تغییر وضعیت درخواستی برای این سفارش مجاز نیست. | No |
| `order_on_hold` | 409 | این سفارش متوقف شده است و تا رفع توقف قابل تغییر نیست. | No |
| `order_expired` | 409 | مهلت پرداخت این سفارش پایان یافته است. | Place a new order |
| `order_already_shipped` | 409 | سفارش ارسال شده است؛ برای لغو، فرآیند مرجوعی را آغاز کنید. | No |
| `order_already_completed` | 409 | این سفارش تکمیل شده است. | No |
| `invoice_already_issued` | 409 | برای این سفارش قبلاً فاکتور صادر شده است. | No |
| `invoice_not_editable` | 409 | فاکتور صادرشده قابل ویرایش نیست. | Issue a supplementary or return invoice |
| `settlement_already_paid` | 409 | این تسویه قبلاً پرداخت شده است. | No |
| `settlement_in_progress` | 409 | این تسویه در حال پردازش است. | No |
| `return_already_requested` | 409 | برای این قلم قبلاً درخواست مرجوعی ثبت شده است. | No |
| `hold_active` | 409 | برای این سفارش توقف فعال وجود دارد. | Release the hold first |
| `incomplete_report_exists` | 409 | برای این سفارش قبلاً گزارش نقص ثبت شده است. | Respond to the existing report |
| `payment_already_captured` | 409 | این پرداخت قبلاً تکمیل شده است. | No |
| `review_already_exists` | 409 | برای این خرید قبلاً نظر ثبت کرده‌اید. | Edit the existing review |
| `cart_converted` | 409 | این سبد خرید قبلاً به سفارش تبدیل شده است. | No |
| `store_edit_window_closed` | 409 | مهلت ویرایش توسط بازاریاب پایان یافته است؛ درخواست تغییر ثبت کنید. | appendix 6 clause 9 |

### 4.6 Business rules

| Code | HTTP | Persian message (`errors.business.*`) | Notes |
|---|---|---|---|
| `business_rule_violation` | 422 | امکان انجام این عملیات وجود ندارد. | Generic; `details[].code` is specific |
| `insufficient_stock` | 422 | موجودی کافی برای این محصول وجود ندارد. | `meta.available` |
| `product_unavailable` | 422 | این محصول دیگر قابل خرید نیست. | Deactivated/archived |
| `price_changed` | 422 | قیمت این محصول تغییر کرده است؛ سبد خرید به‌روزرسانی شد. | `meta.new_amount` |
| `min_order_quantity` | 422 | حداقل مقدار سفارش رعایت نشده است. | `meta.min_quantity` |
| `step_quantity` | 422 | مقدار سفارش باید مضربی از گام فروش باشد. | `meta.step_quantity` |
| `max_order_quantity` | 422 | مقدار سفارش بیش از سقف مجاز است. | — |
| `coupon_expired` | 422 | کد تخفیف منقضی شده است. | — |
| `coupon_not_applicable` | 422 | کد تخفیف برای این سبد قابل استفاده نیست. | `meta.reason` |
| `coupon_already_used` | 422 | این کد تخفیف قبلاً استفاده شده است. | — |
| `coupon_user_mismatch` | 422 | این کد تخفیف مخصوص کاربر دیگری است. | — |
| `coupon_min_order` | 422 | مبلغ سبد برای این کد تخفیف کافی نیست. | `meta.min_order_amount` |
| `payment_amount_mismatch` | 422 | مبلغ پرداخت با مبلغ سفارش هم‌خوانی ندارد. | — |
| `payment_not_captured` | 422 | پرداخت این سفارش تکمیل نشده است. | — |
| `refund_exceeds_captured` | 422 | مبلغ مرجوعی بیش از مبلغ پرداخت‌شده است. | `meta.captured_amount` |
| `refund_destination_unavailable` | 422 | امکان بازگشت وجه به این مقصد وجود ندارد. | No verified IBAN |
| `settlement_nothing_to_pay` | 422 | برای این بازه مبلغی جهت تسویه وجود ندارد. | — |
| `settlement_hold_active` | 422 | به دلیل شکایت باز، تسویه این سفارش متوقف است. | appendix 4 clause 15 |
| `settlement_period_invalid` | 422 | بازه انتخابی برای تسویه معتبر نیست. | Overlap or future dates |
| `return_window_closed` | 422 | مهلت مرجوعی این سفارش پایان یافته است. | appendix 12 clause 14 |
| `return_not_eligible` | 422 | این قلم مشمول مرجوعی نیست. | Reason/condition |
| `return_quantity_exceeded` | 422 | تعداد مرجوعی بیش از تعداد قابل بازگشت است. | `meta.returnable_quantity` |
| `review_not_verified` | 422 | فقط خریداران این محصول می‌توانند نظر ثبت کنند. | — |
| `review_window_closed` | 422 | مهلت ثبت نظر برای این خرید پایان یافته است. | 7 days after delivery |
| `criteria_count_invalid` | 422 | تعداد پارامترهای امتیازدهی باید بین ۵ تا ۱۰ باشد. | appendix 4 clause 17 |
| `points_insufficient` | 422 | امتیاز کافی برای این جایزه وجود ندارد. | `meta.required`, `meta.balance` |
| `reward_out_of_stock` | 422 | موجودی این جایزه تمام شده است. | — |
| `reward_limit_reached` | 422 | سقف استفاده از این جایزه برای شما تکمیل شده است. | — |
| `tender_closed` | 422 | مهلت شرکت در این مناقصه پایان یافته است. | — |
| `bid_below_step` | 422 | مبلغ پیشنهاد باید حداقل به اندازه گام مزایده بهتر باشد. | `meta.min_amount` |
| `bid_already_exists` | 422 | برای این مناقصه قبلاً پیشنهاد ثبت کرده‌اید. | Revise instead |
| `listing_category_required` | 422 | ابتدا باید دسته‌بندی آگهی توسط مدیر تعریف شود. | appendix 5 clause 4 |
| `listing_expired` | 422 | مهلت این آگهی پایان یافته است. | Renew |
| `storage_quota_exceeded` | 422 | فضای مجاز بارگذاری شما تکمیل شده است. | `meta.remaining_bytes` |
| `delay_already_assessed` | 422 | خسارت تأخیر این مرسوله قبلاً محاسبه شده است. | — |
| `role_conflict` | 422 | این سطح کاربری با سطح فعلی شما قابل جمع نیست. | appendix 7 clause 6 |
| `role_not_assignable` | 422 | این سطح کاربری توسط مدیر قابل تخصیص نیست. | — |
| `four_eyes_required` | 422 | تأیید این عملیات باید توسط کاربر دیگری انجام شود. | Settlement/refund approval |
| `manual_adjustment_reason_required` | 422 | ثبت دلیل برای اصلاح دستی الزامی است. | appendix 11 clause 16 |
| `export_too_large` | 422 | حجم خروجی بیش از حد مجاز است؛ فیلترها را محدود کنید. | `meta.max_rows` |
| `notification_recipient_limit` | 422 | تعداد مخاطبان بیش از سقف مجاز است. | `meta.max_recipients` |
| `notification_not_paid` | 422 | پرداخت این ارسال تکمیل نشده است. | appendix 5 clause 12 |
| `settings_key_readonly` | 422 | این تنظیم قابل تغییر از پنل نیست. | — |

### 4.7 Rate limiting

| Code | HTTP | Persian message (`errors.rate.*`) | Headers |
|---|---|---|---|
| `rate_limited` | 429 | تعداد درخواست‌ها بیش از حد مجاز است؛ کمی بعد دوباره تلاش کنید. | `Retry-After`, `X-RateLimit-*` |
| `brute_force_ip_blocked` | 429 | دسترسی از این شبکه موقتاً محدود شده است. | `Retry-After` |
| `otp_request_limited` | 429 | تعداد درخواست کد تأیید زیاد است؛ بعداً تلاش کنید. | `Retry-After` |

### 4.8 Upstream failures

| Code | HTTP | Persian message (`errors.upstream.*`) | Notes |
|---|---|---|---|
| `upstream_failure` | 502 | سرویس بیرونی پاسخ مناسب نداد؛ لطفاً دوباره تلاش کنید. | Generic wrapper |
| `gateway_unavailable` | 502 | درگاه پرداخت در دسترس نیست؛ درگاه دیگری را انتخاب کنید. | `meta.gateway_code` |
| `gateway_timeout` | 504 | پاسخی از درگاه پرداخت دریافت نشد. | Payment stays `pending` |
| `gateway_rejected` | 502 | درگاه پرداخت تراکنش را نپذیرفت. | Provider detail logged, not returned |
| `gateway_signature_invalid` | 401 | اعتبار پاسخ درگاه تأیید نشد. | Webhook only; event stored unapplied |
| `payment_declined` | 422 | پرداخت توسط بانک تأیید نشد. | User-actionable (try another card/gateway) |
| `payment_cancelled_by_user` | 422 | پرداخت توسط شما لغو شد. | — |
| `payment_timeout` | 422 | زمان پرداخت به پایان رسید. | — |
| `sms_provider_failure` | 502 | ارسال پیامک با خطا مواجه شد. | Retried by the queue |
| `carrier_failure` | 502 | سرویس حمل و نقل در دسترس نیست. | Tracking degraded, order unaffected |
| `whatsapp_failure` | 502 | ارسال پیام واتساپ ناموفق بود. | Falls back to SMS |
| `storage_failure` | 502 | ذخیره‌سازی فایل با خطا مواجه شد. | Upload retryable |

### 4.9 Server

| Code | HTTP | Persian message (`errors.server.*`) | Notes |
|---|---|---|---|
| `internal_error` | 500 | خطای غیرمنتظره‌ای رخ داد؛ لطفاً بعداً تلاش کنید. | Full cause in logs with `request_id` |
| `service_unavailable` | 503 | سامانه موقتاً در دسترس نیست. | `Retry-After` |
| `maintenance_mode` | 503 | سامانه در حال به‌روزرسانی است. | `Retry-After` from settings |
| `database_unavailable` | 503 | ارتباط با پایگاه داده برقرار نشد. | No detail leaked |

---

## 5. i18n keys

1. Keys live in `lang/fa/errors.php` (and `lang/en/errors.php` for the English admin UI), keyed by
   family and code: `errors.<family>.<code>`.
2. The family in the key matches the code's domain prefix, with two deliberate aliases:
   `auth.*` covers `unauthenticated`/`token_*`, and `forbidden.*` covers policy denials.
3. `details[].message` uses field-scoped keys: `errors.validation.<field_code>`, e.g.
   `errors.validation.max_available`, with `:meta` placeholders substituted.
4. Persian text rules:
   - Sentence case, ends with a period, no exclamation marks.
   - Persian digits (`۰-۹`) for counts and amounts inside messages.
   - No Latin technical terms where a Persian equivalent exists (`درگاه`, `فاکتور`, `مرجوعی`).
   - No blame ("شما اشتباه کردید") and no internal identifiers.
   - Actionable where possible ("کد جدید بگیرید", "درگاه دیگری را انتخاب کنید").
5. A missing key is a build failure: `php artisan errors:verify` compares the catalogue in this
   document with both lang files in CI.
6. Messages are **never** built by concatenation; placeholders are named (`:min_quantity`) and
   formatted per locale.

---

## 6. Logging rules

### 6.1 What is logged per family

| Family | Level | Fields | Alert |
|---|---|---|---|
| `validation_*` | `info` (sampled 10 %) | route, field names only (no values), `request_id` | No |
| `auth_*` failures | `warning` | user id if known, hashed mobile, IP, user agent, `request_id` | Spike > 50/min per IP |
| `forbidden_*` | `warning` | actor id, route, target type+id, `request_id` | Spike > 20/min per actor |
| `not_found_*` | `info` (sampled) | route, path id shape, actor id | No |
| `conflict_*` | `info` | resource id, actor, `lock_version` seen vs stored | `state_conflict` > 1 % of writes |
| `business_*` | `info` | code, `details[].code`, resource id | No (product telemetry) |
| `rate_limited` | `warning` | limiter key (hashed), route, count | Sustained 429s |
| `upstream_*` | `error` | provider class, operation, latency, redacted payload digest, `request_id` | Any `gateway_signature_invalid`; > 5 gateway errors/5 min |
| `server_*` | `error` / `critical` | exception class, message, stack (log only), `request_id` | Any `internal_error`; page on `critical` |
| Payment state changes | `info` | payment id, from→to, gateway, amount | Reconciliation mismatch |
| Settlement/refund decisions | `info` + `adm_audit_logs` | actor, amounts, approver | Any approval by the requester (should be impossible) |

### 6.2 Absolute prohibitions in logs

Never logged, at any level, in any environment: passwords, OTP codes, TOTP secrets, recovery
codes, Sanctum tokens, `Authorization` headers, full IBAN, card PAN/CVV/expiry, national id,
raw mobile/email (hash or mask instead), full address, ID-document contents, gateway
credentials, raw provider payloads (store a hash + redacted copy), and request bodies of
`/auth/*`.

Masking rules: mobile `09•••••1234`, IBAN `IR••••••••••••••••1234`, email `a•••@example.com`,
national id `••••••1234`, token `<redacted:len=43>`.

### 6.3 Correlation and retention

1. Every log line for a request carries `request_id`, `route`, `actor_id` (when authenticated),
   `status`, `duration_ms`.
2. Client-supplied `X-Request-Id` is accepted only if it is a valid ULID; otherwise the server
   generates one and returns it.
3. Exceptions are reported with `request_id` attached, so a support ticket number maps to a log
   line without exposing internals to the user.
4. Application logs: 30 days hot, 12 months cold. Audit logs: 5 years (`adm_audit_logs`).
   Payment attempts and webhook events: 18 months.
5. Logs are write-only for the application; there is no API to read them.

### 6.4 Error responses must not correlate to internals

1. `500` responses carry no exception class, no file, no line.
2. `502` responses name the provider **class** (`gateway_unavailable`) but never the provider's
   code or message.
3. `404` for a forbidden resource is indistinguishable from a genuinely missing one (same body,
   same timing envelope).
4. `409 state_conflict` reports the version the client sent (it already knows it) and never the
   internal id sequence.

---

## 7. Client handling guidance

| Code | Client action |
|---|---|
| `token_expired`, `token_revoked`, `unauthenticated` | Clear the token, redirect to login, keep the intended destination |
| `account_suspended` | Show the support message; do not retry login |
| `two_factor_required` | Show the 2FA screen with the `challenge_token` |
| `state_conflict` | Re-fetch the resource, show "refreshed", let the user re-apply |
| `idempotency_in_progress` | Retry the identical request after `Retry-After` |
| `idempotency_key_reused` | Bug in the client: generate a new key, do not retry blindly |
| `order_transition_not_allowed`, `order_on_hold` | Re-fetch the order; hide the stale action |
| `price_changed`, `insufficient_stock` | Re-fetch the cart, show the delta, require confirmation |
| `coupon_*` | Keep the cart, clear the coupon, explain why |
| `payment_declined`, `payment_cancelled_by_user`, `payment_timeout` | Return to the payment step with the order intact |
| `gateway_unavailable` | Offer another gateway |
| `rate_limited` | Disable the action until `Retry-After`, show a countdown |
| `validation_failed` | Map `details[].field` to form fields |
| `upstream_failure`, `service_unavailable` | Retry with exponential backoff (max 3), then show a generic failure with `request_id` |
| `internal_error` | Show a generic message plus the `request_id`; never the body |

Client rules:

1. Never retry a `4xx` automatically except `429` and `idempotency_in_progress`.
2. Retries of money endpoints must reuse the same `Idempotency-Key`.
3. Always display `request_id` in the error UI (small, copyable) — it is the only support handle.
4. Never parse `message` to decide behaviour; branch on `code`.

---

## 8. Adding or changing a code

1. Add the code to this document with HTTP status, Persian message and notes.
2. Add `errors.<family>.<code>` to `lang/fa/errors.php` (and `en`).
3. Emit it from exactly one place (a domain exception mapped in the exception handler), not from
   controllers.
4. Add a feature test asserting the status and the `code` (not the message).
5. If a code is being replaced: keep emitting the old one behind a deprecation flag for 90 days,
   mark it `deprecated` here, then stop emitting — never delete the row.
6. Run `php artisan errors:verify` (catalogue ↔ lang files ↔ emitted codes) in CI.
