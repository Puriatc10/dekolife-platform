# API Examples — Dekolife Platform

**Purpose:** Real request/response examples used by the frontend mocks, the contract tests and
the client's technical reviewer. Every example here is a shape the API actually returns.
**Audience:** Frontend/PWA engineers, QA, integration partners.
**Status:** Baseline (v1). Examples are copied into `tests/Contract/fixtures/` and validated
against the response schemas in CI.
**Source:** [`API-guidelines.md`](../API-guidelines.md) (envelope, pagination, money, dates);
[`endpoint-inventory.md`](../endpoint-inventory.md) (operation definitions);
[`order-state-machine.md`](../../data/order-state-machine.md) (status values).

---

## 1. Authenticate (OTP flow)

```http
POST /api/v1/auth/otp/request HTTP/1.1
Content-Type: application/json

{ "mobile": "09121234567", "purpose": "login" }
```

```json
HTTP/1.1 202 Accepted

{
  "challenge_id": "01J9Z5Q7K8N4M3P2R1S0T9V8W5",
  "expires_in": 120,
  "retry_after": 60
}
```

```http
POST /api/v1/auth/otp/verify HTTP/1.1
Content-Type: application/json

{ "challenge_id": "01J9Z5Q7K8N4M3P2R1S0T9V8W5", "code": "48213", "device_name": "Chrome / Windows" }
```

```json
HTTP/1.1 201 Created

{
  "token": "<sanctum-plain-text-token>",
  "token_type": "Bearer",
  "expires_at": "2027-02-23T09:30:00Z",
  "user": {
    "public_id": "01J9Z5Q7K8N4M3P2R1S0T9V8W4",
    "mobile": "09121234567",
    "display_name": "رضا محمدی",
    "status": "active",
    "mobile_verified_at": "2026-11-25T09:29:10Z"
  },
  "roles": [
    { "key": "consumer", "is_primary": true },
    { "key": "marketer", "is_primary": false }
  ]
}
```

## 2. Product detail with a role-dependent price

```http
GET /api/v1/catalog/products/parquet-oak-8mm?include=brand,category,media,variants HTTP/1.1
Authorization: Bearer <store-token>
X-Display-Calendar: jalali
```

```json
HTTP/1.1 200 OK

{
  "public_id": "01J9Z6A1B2C3D4E5F6G7H8J9K0",
  "slug": "parquet-oak-8mm",
  "title_fa": "پارکت بلوط ۸ میلی‌متر",
  "short_description": "پارکت سه‌لایه با روکش بلوط طبیعی",
  "product_type": "variant",
  "has_variants": true,
  "unit": { "code": "m2", "name_fa": "متر مربع", "precision": 3 },
  "brand": { "public_id": "01J9Z6...", "name_fa": "چوب‌سازان" },
  "category": { "public_id": "01J9Z6...", "name_fa": "پارکت و کفپوش", "slug": "parquet" },
  "seller": { "type": "supplier", "public_id": "01J9Z6...", "display_name": "صنایع چوب البرز" },
  "min_order_quantity": 1.0,
  "step_quantity": 0.5,
  "variants": [
    {
      "public_id": "01J9Z6...",
      "sku": "OAK-8-NAT-120",
      "title": "۸ میلی‌متر / بلوط طبیعی / ۱۲۰×۱۹",
      "is_default": true,
      "attributes": [
        { "code": "thickness", "name_fa": "ضخامت", "value": "۸", "unit_label": "mm" },
        { "code": "color", "name_fa": "رنگ", "value": "بلوط طبیعی" }
      ],
      "price": {
        "price_list_code": "store_b",
        "unit_amount": 1250000,
        "unit_amount_display": "۱٬۲۵۰٬۰۰۰ تومان",
        "compare_at_amount": 1390000,
        "currency": "IRT",
        "volume_tiers": [
          { "min_quantity": 50, "unit_amount": 1190000 },
          { "min_quantity": 200, "unit_amount": 1125000 }
        ]
      },
      "availability": { "available_quantity": 1860.5, "is_available": true }
    }
  ],
  "rating": { "average": 4.4, "count": 128 },
  "published_at": "2026-08-02T06:15:00Z",
  "published_at_jalali": "1405-05-11 09:45"
}
```

## 3. Cart with a coupon and shipping estimate

```json
HTTP/1.1 200 OK

{
  "cart": {
    "public_id": "01J9Z7C1D2E3F4G5H6J7K8M9N0",
    "status": "active",
    "price_list_id": 4,
    "price_list_code": "store_b",
    "items_count": 2,
    "expires_at": "2027-02-22T09:30:00Z"
  },
  "items": [
    {
      "id": 88121,
      "variant": { "public_id": "01J9Z6...", "title": "۸ میلی‌متر / بلوط طبیعی / ۱۲۰×۱۹" },
      "seller": { "type": "supplier", "public_id": "01J9Z6...", "display_name": "صنایع چوب البرز" },
      "quantity": 60.0,
      "unit_label": "m2",
      "unit_price_amount": 1190000,
      "line_total_amount": 71400000,
      "warnings": []
    },
    {
      "id": 88122,
      "variant": { "public_id": "01J9Z6...", "title": "قرنیز ام‌دی‌اف ۸ سانتی" },
      "seller": { "type": "supplier", "public_id": "01J9Z6...", "display_name": "صنایع چوب البرز" },
      "quantity": 40.0,
      "unit_label": "m",
      "unit_price_amount": 185000,
      "line_total_amount": 7400000,
      "warnings": []
    }
  ],
  "applied_coupon": {
    "code": "BON-1405-0042",
    "type": "bon",
    "amount": 2000000,
    "valid_to": "2026-12-20T20:30:00Z"
  },
  "totals": {
    "items_total_amount": 78800000,
    "discount_total_amount": 2000000,
    "coupon_discount_amount": 2000000,
    "loyalty_discount_amount": 0,
    "shipping_total_amount": 850000,
    "tax_total_amount": 0,
    "grand_total_amount": 77650000,
    "currency": "IRT"
  },
  "warnings": []
}
```

## 4. Checkout (idempotent)

```http
POST /api/v1/me/cart/checkout HTTP/1.1
Authorization: Bearer <store-token>
Idempotency-Key: 01J9Z8Q1W2E3R4T5Y6U7I8O9P0
Content-Type: application/json

{
  "address_id": 5512,
  "shipping_method_id": 12,
  "payment_method": "online",
  "coupon_code": "BON-1405-0042",
  "note": "تحویل در ساعات اداری"
}
```

```json
HTTP/1.1 201 Created
Location: /api/v1/me/orders/DK-1405-000418

{
  "order": {
    "public_id": "01J9Z8Q1W2E3R4T5Y6U7I8O9P1",
    "order_number": "DK-1405-000418",
    "status": "awaiting_payment",
    "payment_status": "unpaid",
    "fulfilment_status": "unfulfilled",
    "currency": "IRT",
    "items_total_amount": 78800000,
    "discount_total_amount": 2000000,
    "coupon_discount_amount": 2000000,
    "loyalty_discount_amount": 0,
    "shipping_total_amount": 850000,
    "tax_total_amount": 0,
    "grand_total_amount": 77650000,
    "paid_amount": 0,
    "wallet_applied_amount": 0,
    "balance_due_amount": 77650000,
    "placed_at": "2026-11-25T09:31:04Z",
    "expires_at": "2026-11-25T10:01:04Z",
    "sub_orders": [
      {
        "sub_order_number": "DK-1405-000418-01",
        "seller": { "type": "supplier", "display_name": "صنایع چوب البرز" },
        "status": "awaiting_confirmation",
        "total_amount": 77650000,
        "promised_ship_at": null
      }
    ],
    "lock_version": 0
  },
  "payment_intent": {
    "public_id": "01J9Z8Q1W2E3R4T5Y6U7I8O9P2",
    "amount": 77650000,
    "currency": "IRT",
    "status": "initiated",
    "gateway_code": "saman",
    "redirect_url": "https://api.dekolife.ir/api/v1/webhooks/payments/saman/start?authority=A00000000000000000000000000123456"
  }
}
```

Replaying the same request with the same `Idempotency-Key` returns the stored `201` body with:

```http
Idempotent-Replay: true
```

## 5. Order after delivery (money, points and commission already posted)

```json
HTTP/1.1 200 OK

{
  "public_id": "01J9Z8Q1W2E3R4T5Y6U7I8O9P1",
  "order_number": "DK-1405-000418",
  "status": "processing",
  "payment_status": "paid",
  "fulfilment_status": "partial",
  "grand_total_amount": 77650000,
  "paid_amount": 77650000,
  "refunded_amount": 0,
  "invoices": [
    {
      "invoice_number": "1405-000312",
      "type": "sale",
      "status": "paid",
      "total_amount": 77650000,
      "issued_at": "2026-11-25T09:33:11Z"
    }
  ],
  "sub_orders": [
    {
      "sub_order_number": "DK-1405-000418-01",
      "status": "delivered",
      "delivered_at": "2026-11-28T11:02:00Z",
      "return_window_ends_at": "2026-12-05T11:02:00Z",
      "payable_to_seller_amount": 74950000,
      "shipment": {
        "carrier": { "code": "tipax", "name_fa": "تیپاکس" },
        "tracking_code": "TIP-8841209931",
        "status": "delivered"
      }
    }
  ],
  "points": { "earned": 1120, "balance_after": 4380, "tier": "tier_3" },
  "timeline": [
    { "to_status": "awaiting_payment", "actor_type": "user", "occurred_at": "2026-11-25T09:31:04Z" },
    { "to_status": "paid", "actor_type": "gateway", "occurred_at": "2026-11-25T09:33:11Z" },
    { "to_status": "confirmed", "actor_type": "supplier", "occurred_at": "2026-11-25T11:20:00Z" },
    { "to_status": "shipped", "actor_type": "supplier", "occurred_at": "2026-11-26T08:05:00Z" },
    { "to_status": "delivered", "actor_type": "system", "occurred_at": "2026-11-28T11:02:00Z" }
  ],
  "lock_version": 7
}
```

## 6. Cursor pagination

```http
GET /api/v1/me/orders?limit=2&sort=-placed_at HTTP/1.1
Authorization: Bearer <token>
```

```json
HTTP/1.1 200 OK

{
  "data": [
    { "order_number": "DK-1405-000418", "status": "processing", "grand_total_amount": 77650000, "placed_at": "2026-11-25T09:31:04Z" },
    { "order_number": "DK-1405-000412", "status": "completed", "grand_total_amount": 12400000, "placed_at": "2026-11-19T14:02:44Z" }
  ],
  "meta": {
    "per_page": 2,
    "next_cursor": "eyJwbGFjZWRfYXQiOiIyMDI2LTExLTE5VDE0OjAyOjQ0WiIsImlkIjoxMjQwfQ",
    "prev_cursor": null,
    "has_more": true
  },
  "links": {
    "next": "https://api.dekolife.ir/api/v1/me/orders?limit=2&sort=-placed_at&cursor=eyJwbGFjZWRfYXQiOiIyMDI2LTExLTE5VDE0OjAyOjQ0WiIsImlkIjoxMjQwfQ",
    "prev": null
  }
}
```

## 7. Page pagination (admin table)

```json
HTTP/1.1 200 OK

{
  "data": [ { "order_number": "DK-1405-000418", "buyer": { "display_name": "فروشگاه کفپوش پارس" }, "status": "processing", "grand_total_amount": 77650000 } ],
  "meta": { "current_page": 2, "per_page": 25, "last_page": 37, "total": 913, "from": 26, "to": 50 },
  "links": { "first": "...", "prev": "...", "next": "...", "last": "..." }
}
```

## 8. Validation error

```http
POST /api/v1/me/cart/items HTTP/1.1
Content-Type: application/json

{ "variant_id": 9912, "quantity": 0 }
```

```json
HTTP/1.1 422 Unprocessable Content
X-Request-Id: 01J9Z9A1B2C3D4E5F6G7H8J9K1

{
  "error": {
    "code": "validation_failed",
    "message": "اطلاعات ارسالی معتبر نیست.",
    "request_id": "01J9Z9A1B2C3D4E5F6G7H8J9K1",
    "details": [
      { "field": "quantity", "code": "min_value", "message": "مقدار باید حداقل ۰٫۵ باشد.", "meta": { "min": 0.5 } }
    ]
  }
}
```

## 9. Business-rule error (stock)

```json
HTTP/1.1 422 Unprocessable Content

{
  "error": {
    "code": "insufficient_stock",
    "message": "موجودی کافی برای این محصول وجود ندارد.",
    "request_id": "01J9Z9A1B2C3D4E5F6G7H8J9K2",
    "details": [
      { "field": "items.0.quantity", "code": "max_available", "message": "حداکثر ۳ عدد موجود است.", "meta": { "available": 3 } }
    ]
  }
}
```

## 10. State conflict

```json
HTTP/1.1 409 Conflict

{
  "error": {
    "code": "state_conflict",
    "message": "این مورد هم‌زمان توسط کاربر دیگری تغییر کرده است؛ صفحه را تازه کنید.",
    "request_id": "01J9Z9A1B2C3D4E5F6G7H8J9K3",
    "details": [
      { "field": "lock_version", "code": "stale", "message": "نسخه ارسالی قدیمی است.", "meta": { "sent": 6, "current": 7 } }
    ]
  }
}
```

## 11. Internal error (shape only)

```json
HTTP/1.1 500 Internal Server Error
X-Request-Id: 01J9Z9A1B2C3D4E5F6G7H8J9K4

{
  "error": {
    "code": "internal_error",
    "message": "خطای غیرمنتظره‌ای رخ داد؛ لطفاً بعداً تلاش کنید.",
    "request_id": "01J9Z9A1B2C3D4E5F6G7H8J9K4",
    "details": []
  }
}
```

## 12. Gateway webhook

```http
POST /api/v1/webhooks/payments/saman HTTP/1.1
Content-Type: application/x-www-form-urlencoded
X-Signature: <hmac-sha256>

State=OK&ResNum=1405000418&RefNum=8841209931&TraceNo=3312&Amount=77650000
```

```json
HTTP/1.1 200 OK

{ "status": "ok" }
```

A duplicate delivery returns `{"status":"duplicate"}`; an invalid signature returns `401` with
`{"error":{"code":"gateway_signature_invalid",...}}` and the event is stored but not applied.

## 13. Settlement request (marketer)

```json
HTTP/1.1 201 Created

{
  "settlement_no": "ST-1405-000087",
  "payee_type": "marketer",
  "period_start": "2026-10-01",
  "period_end": "2026-10-31",
  "gross_amount": 184000000,
  "commission_amount": 9200000,
  "penalty_amount": 0,
  "adjustment_amount": 0,
  "net_amount": 9200000,
  "status": "requested",
  "bank_account": { "iban_last4": "4471", "bank_name": "بانک ملت", "status": "verified" },
  "created_at": "2026-11-25T10:12:00Z"
}
```

## 14. Money and date conventions in one object

```json
{
  "unit_price_amount": 1250000,
  "unit_price_display": "۱٬۲۵۰٬۰۰۰ تومان",
  "discount_percent": 4.5,
  "quantity": 12.5,
  "currency": "IRT",
  "created_at": "2026-11-25T09:31:04.123Z",
  "created_at_jalali": "1405-09-04 13:01",
  "created_at_display": "۴ آذر ۱۴۰۵، ۱۳:۰۱",
  "period_start": "2026-10-01"
}
```
