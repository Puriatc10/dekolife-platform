# Design System — Dekolife Web Platform

**Purpose:** the single source of visual and interaction truth: tokens, components, form and
data-display rules, so 170+ screens are built from one vocabulary instead of per-page decisions.
**Audience:** designers, Blade/Livewire implementers, QA doing visual and a11y checks.
**Status:** baseline v1; token values are final for build. Brand-level refinements arrive as a
theme override, never as per-screen edits.

Source: appendix 2 clause 1 (custom UX/UI from concept through SASS/CSS/HTML, **no ready-made
template for the user-facing area**), appendix 9 clauses 1–5 (SEO, speed, security, branding),
appendix 1 clauses 3–6 (responsive, PWA, browser support), appendix 7 clause 4 (product form with
per-level prices and attributes), appendix 11 clauses 1–20 (supplier admin density), appendix 13
clauses 1–7 (financial reports, Excel output), canonical-context §2 (Blade + Livewire 4 + Alpine
+ Tailwind 4, RTL-first) and §5 (integer Toman money, Jalali display, Asia/Tehran).

---

## 1. Two non-negotiable rules

### 1.1 No ready-made marketplace template

Appendix 2 clause 1 obliges a bespoke design: «طراحی اختصاصی UX/UI … بدون استفاده از قالب آماده
برای بخش کاربری». Concretely:

- **Forbidden:** purchasing or vendoring a marketplace/e-commerce theme (RTL shop templates,
  Bootstrap admin themes, "multi-vendor store" HTML kits), copying a competitor's layout
  wholesale, or shipping a page whose structure is a theme demo with new colors.
- **Allowed:** Tailwind CSS 4 as a utility engine, Alpine.js for behaviour, Livewire 4 for
  server-driven interactivity, headless/behaviour-only libraries (focus trap, chart primitives,
  date conversion), and open-source **icons**. These provide primitives, not product design.
- **Evidence for acceptance:** `resources/css/app.css` contains our token layer and component
  classes only; `package.json` lists no theme package; every public screen traces to a component
  in §5. A reviewer can diff any screen against this document and find its parts.
- **Consequence of violation:** contract article 4 clause 7 forbids reusing this project's code
  elsewhere, and appendix 2 clause 1 makes a template a deliverable defect — treat a template
  import as a P0 bug, not a style preference.

The visual identity is deliberately *not* a clone of any Iranian marketplace: layout patterns
(search-first header, category rail, offer strip) are conventional commerce patterns required by
appendix 2 clause 2, while the palette, type scale, card geometry and panel chrome are ours and
defined here.

### 1.2 RTL is the default, not a variant

There is no LTR build. Tokens use logical properties, components are authored RTL-first, and the
LTR islands (IBAN, tracking code, SKU) are explicit and few (see
`docs/UX/navigation-and-layout.md` §8).

---

## 2. Color

### 2.1 Ramps

`brand` — Dekolife green-teal, primary actions, links, active nav, focus ring.

| Token | Hex | Typical use |
|---|---|---|
| `--color-brand-50` | `#EFF8F5` | selected row tint, subtle badge bg |
| `--color-brand-100` | `#D7EEE7` | hover tint on light surfaces |
| `--color-brand-200` | `#AFDDCF` | chart series 2, decorative |
| `--color-brand-300` | `#7FC4B1` | chart series 3 |
| `--color-brand-400` | `#4FA691` | icon accents |
| `--color-brand-500` | `#2A8A74` | hover state of primary button |
| `--color-brand-600` | `#1B715E` | **primary button**, link text, active nav |
| `--color-brand-700` | `#175A4C` | pressed state, headings on tinted bg |
| `--color-brand-800` | `#14483E` | dark surfaces (panel header accent) |
| `--color-brand-900` | `#0F332C` | text on brand-50, print headings |

`accent` — warm wood/amber, reserved for promotional and points contexts (offers, stars, wallet
rewards). Never used for primary actions, so "on sale" never looks like "submit".

| Token | Hex | Use |
|---|---|---|
| `--color-accent-50` | `#FDF6EC` | offer card background |
| `--color-accent-100` | `#F9E7C9` | points pill background |
| `--color-accent-200` | `#F1CE95` | progress track for points |
| `--color-accent-300` | `#E5AE5B` | star fill alt |
| `--color-accent-400` | `#D4922C` | star fill |
| `--color-accent-500` | `#B87A17` | offer price text on white |
| `--color-accent-600` | `#96600F` | pressed |
| `--color-accent-700` | `#774C0D` | text on accent-50 |
| `--color-accent-800` | `#5B3A0C` | print |
| `--color-accent-900` | `#3E2808` | print |

`neutral` — surfaces and text.

| Token | Hex | Use |
|---|---|---|
| `--color-neutral-0` | `#FFFFFF` | canvas, cards |
| `--color-neutral-25` | `#FCFCFD` | table zebra (very light) |
| `--color-neutral-50` | `#F7F8FA` | sunken canvas, filter rail bg |
| `--color-neutral-100` | `#F1F2F5` | hover on sunken, disabled bg |
| `--color-neutral-200` | `#E4E6EB` | dividers (decorative, exempt from 1.4.11) |
| `--color-neutral-300` | `#D0D3DA` | table borders, skeleton base |
| `--color-neutral-400` | `#A8ADB8` | disabled text/icon only (non-interactive, exempt) |
| `--color-neutral-500` | `#7C8391` | non-text only: chart gridlines, icon strokes, dividers |
| `--color-neutral-600` | `#5A616E` | **muted body text** (meta, timestamps) |
| `--color-neutral-700` | `#3F4550` | default body text |
| `--color-neutral-800` | `#2A2F38` | headings, table headers |
| `--color-neutral-900` | `#171B21` | max-contrast text, inverse surfaces |

`--color-border-control` = `#888E99` (3.29:1 on white) — the border of every interactive control
(input, select, checkbox, secondary button). `--color-border-subtle` = `#E4E6EB` for non-essential
dividers.

### 2.2 Semantic roles

| Role | Text token | Background token | Contrast (text on bg) |
|---|---|---|---|
| Primary action | `#FFFFFF` on `brand-600` | `brand-600` | 5.88:1 |
| Link | `brand-600` `#1B715E` on white | — | 5.88:1 |
| Body | `neutral-700` `#3F4550` | `neutral-0` | 9.64:1 |
| Muted | `neutral-600` `#5A616E` | `neutral-0` / `neutral-50` | 6.23 / 5.86 |
| Success | `#146B45` | `#E8F6EE` | 6.14:1 |
| Warning | `#8C6510` | `#FFF4E0` | 5.27:1 |
| Danger | `#B02A18` | `#FDECEA` | 6.19:1 |
| Info | `#1F6FB2` | `#EAF3FB` | 5.28:1 |
| Disabled | `neutral-400` | `neutral-100` | exempt (non-interactive) |

Rules:

- `neutral-500` is **not** valid for text below 18px/700 — it measures 3.81:1. Use
  `neutral-600`.
- `brand-500` (4.21:1) is not valid for text; it is a hover-surface color only.
- Status is never colour-only: every status badge ships an icon or a Persian word
  («در انتظار تأیید», «ارسال شده»), and a pattern in print views.
- Charts use the brand/accent ramps in fixed order (brand-600, accent-500, brand-300, info,
  neutral-500, brand-800); series are labelled, not legend-colour-only.
- A dedicated dark theme is **not** in v1 scope. Because components consume semantic tokens only,
  a `[data-theme="dark"]` override can be added without touching components; no component may
  reference a raw hex value.

---

## 3. Typography

**Family:** **Vazirmatn** (SIL OFL), self-hosted `woff2`, weights 400 / 500 / 700 / 900, variable
font subset to Arabic + Latin.

```css
--font-sans: "Vazirmatn", "IRANSansX", "Segoe UI", system-ui, sans-serif;
--font-numeric: "Vazirmatn", ui-monospace, monospace; /* tabular figures for tables */
```

Why Vazirmatn: complete Persian glyph set (including ی/ک Persian forms, ژ, گ, چ, پ), good
legibility at 13–14px in dense admin tables, open licence, and variable-axis support for weight
without extra requests. Fallbacks exist only for a failed font load.

**Scale** (rem, 1rem = 16px). Persian text needs more leading than Latin: body line-height is
1.75.

| Token | Size / line-height | Weight | Use |
|---|---|---|---|
| `text-2xs` | 11 / 16 | 500 | badges, table meta, chart axes |
| `text-xs` | 12 / 18 | 400–500 | captions, helper text, timestamps |
| `text-sm` | 13 / 22 | 400 | dense tables, panel secondary text |
| `text-base` | 14 / 24 | 400 | **default UI text**, forms, cards |
| `text-md` | 16 / 28 | 400–500 | product titles in cards, article body |
| `text-lg` | 18 / 30 | 500 | section subtitles, modal titles |
| `text-xl` | 20 / 32 | 700 | card group headings |
| `text-2xl` | 24 / 36 | 700 | page titles (H1 in panels) |
| `text-3xl` | 30 / 42 | 700 | product page title, hero headline |
| `text-4xl` | 36 / 48 | 900 | marketing hero only |

Rules: letter-spacing is always `0` (negative tracking breaks Persian joins); no italics (they
render as synthetic oblique and damage Persian letterforms — use weight or colour for emphasis);
`text-wrap: balance` on headings, `pretty` on article body; maximum measure 72 characters
(`--measure-prose: 68ch`).

**Numerals in type.** Display text uses Persian digits via `Intl.NumberFormat('fa-IR')`; technical
identifiers stay Latin in `.numeric-ltr` (`dir="ltr"`, `unicode-bidi: isolate`, tabular figures).
Tables use tabular figures so columns of prices align.

---

## 4. Spacing, grid, radii, elevation

**Spacing** — 4px base, only these steps:

`2, 4, 8, 12, 16, 20, 24, 32, 40, 48, 64, 80` → `--space-1 … --space-20`.

Component padding is fixed by role: button `12/16`, input `10/12`, card `16`, panel content
`24` (desktop) / `16` (mobile), table cell `12/16`, section gap `32` (mobile `24`).

**Grid:** 12 columns, gutter 24 (`16` below `md`), max content width 1440, page padding
`16/24/32`. Product grids: 2 columns mobile, 3 `sm`, 4 `lg`, 5 `xl` for listing pages; home rails
are horizontal scroll-snap with 2.2 visible cards on mobile.

**Radii:** `--radius-xs 4` (badges, inputs inside tables), `--radius-sm 8` (inputs, buttons),
`--radius-md 12` (cards, panels), `--radius-lg 16` (modals, hero blocks), `--radius-full 999`
(pills, avatars, FAB).

**Elevation** (soft, low-contrast; RTL-neutral):

| Token | Value | Use |
|---|---|---|
| `--shadow-0` | none + `1px solid neutral-200` | cards on white |
| `--shadow-1` | `0 1px 2px rgba(23,27,33,.06), 0 1px 3px rgba(23,27,33,.04)` | sticky header on scroll |
| `--shadow-2` | `0 4px 12px rgba(23,27,33,.08)` | dropdown, popover |
| `--shadow-3` | `0 8px 24px rgba(23,27,33,.12)` | drawer, mega menu |
| `--shadow-4` | `0 16px 40px rgba(23,27,33,.16)` | modal |

**Layers (z-index):** `base 0`, `sticky 10`, `dropdown 20`, `overlay-scrim 30`, `header 40`,
`drawer 45`, `modal 50`, `toast 60`, `dev 90`. No component invents a value outside this list.

**Motion:** `--duration-fast 120ms`, `--duration-base 180ms`, `--duration-slow 240ms`,
`--ease-standard cubic-bezier(.2,0,0,1)`, `--ease-exit cubic-bezier(.4,0,1,1)`. Animate only
`transform` and `opacity`. All motion collapses to 0ms under `prefers-reduced-motion: reduce`.

**Iconography:** 24px grid, 1.5px stroke, `currentColor`, 20px in dense tables, 16px inline with
text. One family (Lucide) — no hand-drawn mixes. Directional mirroring rules in
`docs/UX/navigation-and-layout.md` §8.

---

## 5. Component inventory

`resources/views/components/ui/<name>.blade.php`; each component documents variants, states and
its a11y contract. The list below is the complete allow-list for v1 — a new visual element needs
an entry here before it ships.

### 5.1 Actions

| Component | Variants / sizes | States | Notes |
|---|---|---|---|
| `button` | primary, secondary, ghost, danger, link; `sm 32 / md 40 / lg 48` | default, hover, active, focus-visible, disabled, loading | Loading keeps width, shows spinner + «در حال ارسال…», `aria-busy` |
| `icon-button` | 32/40/44 | as above | Requires `aria-label`; tooltip on hover/focus |
| `button-group` | segmented, split | — | Split button for «ثبت سفارش» + «ذخیره پیش‌نویس» |
| `fab` | mobile only, one per screen | — | Marketer «ثبت فروشگاه» |
| `link` | inline, standalone | visited not styled differently | Persian underline offset 3px |

### 5.2 Inputs

| Component | Notes |
|---|---|
| `input` | text, tel, email, number, password (with reveal), search; prefix/suffix slots; `inputmode` set per type |
| `money-input` | Latin digit entry, live grouping to Persian digits on blur, «تومان» suffix, min/max, step 1000 for prices; server re-validates integer Toman |
| `number-stepper` | quantity 1–999 with held-repeat, `aria-live` on value |
| `textarea` | auto-grow to 8 rows, counter at 80% of limit |
| `select` | native `<select>` on mobile; searchable combobox (`role="combobox"`) when > 8 options |
| `multiselect` | chips + search, max-select support (e.g. 5 compare slots) |
| `checkbox`, `radio`, `switch` | 20px control, 44px hit area; switch used only for immediate-effect settings |
| `otp-input` | 6 boxes, one group label, paste-fills all, numeric keypad, auto-submit on last digit, 60s resend |
| `date-picker` | **Jalali** calendar, Persian digits, week starts Saturday, quick picks (امروز، دیروز، ۷ روز گذشته، این ماه), range mode for reports |
| `time-picker` | 24h, 15-minute steps |
| `file-uploader` | drag-drop + click, per-file progress, 10MB images / 20MB PDF, MIME allow-list, image cropper (1:1 product, 16:9 banner), EXIF strip, virus scan job, presigned direct upload |
| `audio-recorder` | max **2 minutes** per appendix 5 clause 41, waveform, re-record, ≤ 5MB, stored privately with signed playback |
| `rich-text` | article/page body, sanitised allow-list (no scripts, no inline styles), image insert via uploader |
| `map-picker` | OpenStreetMap tiles, pin drag, reverse-geocode to text address, required for store registration (appendix 6 clause 2) |
| `tag-input` | keywords, postal-code lists |
| `range-slider` | dual-thumb price filter, numeric inputs mirrored, RTL-correct track fill |

### 5.3 Display

| Component | Notes |
|---|---|
| `card` | `product`, `store`, `supplier`, `article`, `order-summary`, `kpi`, `offer` |
| `product-card` | image 1:1, title 2 lines, price block (see §7), discount badge, rating, compare + favourite icon buttons, out-of-stock overlay |
| `price-block` | consumer price, colleague price (only to eligible roles), strikethrough original, discount %, «از … تومان» for ranges, installment/deposit note |
| `badge` | status, level (`مصرف‌کننده`, `فروشگاه`, `تأمین‌کننده`, `بازاریاب`), points, star count, «جدید», «تخفیف‌دار» |
| `rating` | 5 stars, half-star support, count, `aria-label="امتیاز ۴٫۲ از ۵ از ۱۸ نظر"` |
| `avatar` | image, initials fallback, size 24/32/40/56, store logo variant |
| `table` | see 5.4 |
| `description-list` | specs, invoice header, order meta (two-column, wraps to stacked on mobile) |
| `timeline` | order status history with Jalali timestamps and actor |
| `tabs` | underline style, `role="tablist"`, URL-synced where it changes data |
| `accordion` | FAQ, filters on mobile, admin settings groups |
| `carousel` | home rails, RTL swipe, snap, arrows, no autoplay except hero (8s, pauses on hover/focus) |
| `lightbox` | product gallery, keyboard arrows, zoom, ESC |
| `video-player` | product video, no autoplay, poster required |
| `chart` | line, bar, stacked bar, donut; always with a «نمایش جدول» data alternative |
| `stat` | KPI with label, value, delta, period, and a link to the underlying report |
| `empty-state` | illustration + copy + action (see navigation §9.1) |
| `skeleton` | card, table-row, chart, text-line shapes |
| `tooltip` | 200ms delay, keyboard accessible, never sole carrier of required info |
| `popover` | filters, user menu, date range |
| `pagination` | RTL order, page-size selector, «۱ تا ۲۰ از ۲۳۴» summary |
| `breadcrumb` | §6 of navigation doc |
| `progress` | linear and circular, determinate only (indeterminate = skeleton) |
| `stepper` | checkout, store registration, product wizard, tender submission |
| `qr-block` | marketer QR with download PNG/SVG and print card layout (appendix 5 clause 1) |
| `map-view` | store pins, marketer live board, agency locations |
| `invoice-document` | printable A5/A4 invoice with print stylesheet, reverse order number (appendix 5 clause 24) |
| `compare-table` | up to 5 columns, sticky first (parameter) column, diff highlighting |
| `chat-thread` / `chat-composer` | persistent history, text/image/file/voice, read receipts, attachment previews (appendix 5 clause 28) |
| `notification-composer` | text + image + link + video, audience picker, count, schedule, cost preview |
| `permission-matrix` | capability rows × role columns, tri-state with inherited locks |
| `filter-rail` | facet groups, active-filter chips, counts, «حذف همه» |
| `ledger-row` | debit/credit columns, running balance, reference, reversal marker |
| `audit-row` | actor, action, target, before/after diff, IP, Jalali timestamp |
| `upload-dropzone` | inside file-uploader, empty and dragging states |
| `copy-field` | referral link, tracking code, IBAN — copy button with «کپی شد» toast |

### 5.4 Data tables (used by every panel)

A single `data-table` component provides: sticky header, column sorting (`aria-sort`), server-side
pagination, per-column filters, saved views (name + shareable query string), row selection with a
bulk-action bar, row actions in an overflow menu, density toggle (comfortable/compact), column
visibility, and CSV/Excel export where the contract requires it (appendix 13 clause 3, appendix 7
clause 10).

- Below `lg`, each row becomes a **stacked card**: title line, 3 key/value pairs, actions.
- Money columns right-aligned visually with `text-align: end` and tabular Persian digits.
- Status columns use `badge` with text, never a bare colour dot.
- Bulk actions that move money or change roles require a confirmation modal with a typed count
  («۳ مورد انتخاب شده») and an audit reason field.
- Empty/filtered-empty/no-permission states are the table's own responsibility, not the page's.

### 5.5 Feedback

| Component | Notes |
|---|---|
| `toast` | bottom-inline-start, 4s default, max 3 stacked, `role="status"`, action slot («مشاهده») |
| `alert` | inline, 4 tones, dismissible when informational only |
| `modal` | sizes `sm 400 / md 560 / lg 720 / xl 960`, focus trap, ESC, scroll lock, `aria-modal` |
| `drawer` | inline-end side on desktop (filters, details), bottom sheet on mobile |
| `confirm-dialog` | destructive actions; type-to-confirm for role changes, refunds, settlements, user deletion |
| `banner` | site notification bar, offer strip, maintenance notice |
| `inline-validation` | blur + submit timing, never per-keystroke |

---

## 6. Form patterns

1. **Structure.** One column, labels above fields, groups of ≤ 7 fields separated by a section
   heading and a 32px gap. Two-column only for short paired fields (کد پستی / شهر) at `md+`.
2. **Labels.** Always visible; placeholder is an example, never the label. Required fields marked
   with a right-side asterisk **and** explained once at the top («فیلدهای ستاره‌دار الزامی‌اند»).
   Optional fields are labelled «(اختیاری)» in the store/supplier forms where most fields are
   required.
3. **Help text.** Below the field, `text-xs`, `neutral-600`, tied by `aria-describedby`.
4. **Validation timing.** Validate on blur, re-validate on change after the first error, full
   validation on submit. Server errors map back to fields by name; unmatched errors surface in the
   summary alert.
5. **Persian input hygiene.** Convert Arabic ي/ك to Persian, trim ZWNJ noise, normalise digits,
   reject strings that are only whitespace. Phone numbers accept `۰۹…`, `+98…`, `0098…` and store
   E.164.
6. **Wizards.** Store registration, product creation, tender submission and checkout use a
   stepper: 3–5 steps, each independently validated, back always allowed, draft autosaved every
   20s (server-side draft row, not `localStorage`, because a field visit can span days).
7. **Destructive actions.** Placed inline-end, styled `danger`, never the default focus. Money,
   role and deletion actions require a reason string that lands in `adm_audit_logs`.
8. **Permission-aware fields.** A field the actor cannot edit renders read-only with a lock icon
   and a reason («فقط ادمین می‌تواند تغییر دهد»), and the submitted payload simply excludes it.
   The 5-minute marketer edit window (appendix 6 clause 9) is implemented as a countdown chip
   next to the save button; after it expires the form switches to «درخواست ویرایش» mode.
9. **Autofill.** `autocomplete` on name, tel, postal-code, address-line1, email; never on OTP.
10. **Error copy formula.** What is wrong + what to do: «کد پستی باید ۱۰ رقم باشد.» Not
    «ورودی نامعتبر است.»
11. **Submit affordance.** One primary button per form, label states the outcome («ثبت فروشگاه»,
    «ذخیره قیمت‌ها»), sticky footer on long forms with the same button repeated — never two
    different primary actions.

---

## 7. Money, numbers and Jalali dates

### 7.1 Money

- Stored and computed as **integer Toman** (`BIGINT`, `*_amount`) per canonical-context §5; the UI
  never sees a float and never does arithmetic on formatted strings.
- Display: Persian digits, U+066C thousands separator, unit after the number, non-breaking space:
  `۱٬۲۵۰٬۰۰۰ تومان`. Rial is never shown (the contract prices are Toman).
- Formatting helper: `App\Shared\Money::format($amount)` → string; Blade component `<x-ui.money
  :amount="…" />` handles digits, separator and unit. One helper, no local `number_format` calls.
- Negative amounts (debt, reversal) render with a leading `−` and `danger` text in ledgers; a
  credit is plain with a `+` only in the delta column.
- Percentages: `۴٪` with Persian percent sign, one decimal only when the source has one
  (coefficients ۰٫۷۵٪ / ۰٫۵٪ / ۰٫۲۵٪ / ۰٫۲٪ are shown exactly as defined).
- Ranges: «از ۱٬۲۰۰٬۰۰۰ تومان». Rounded marketing ranges: «از ۱٫۲ میلیون تومان» is **not** used —
  exactness matters in B2B.
- Deposit/remainder (appendix 12 clause 13) shows both lines plus the total, never only the
  deposit.
- Table cells never wrap a price across lines (`white-space: nowrap`).

### 7.2 Numbers and units

- Counts below 1000: Persian digits (`۲۳ سفارش`). Above: grouped with U+066C (`۱۲٬۴۵۰`).
- Dimensions follow the catalog convention square metre / metre / piece, stored in
  `cat_attributes` with a unit; display `۲٫۵ متر مربع` with the Persian decimal separator (U+066B).
- Weight `کیلوگرم`, volume `متر مکعب`, thickness `میلی‌متر` — unit strings come from the attribute
  definition, not from templates.
- Points: `۱٬۸۴۰ امتیاز`; stars: numeral + star icon + `aria-label` («۴ ستاره»).
- Ratings: `۴٫۲ از ۵` with one decimal.
- Percentages in charts and progress: integer, no decimals.

### 7.3 Jalali dates and time

- All timestamps stored UTC, displayed `Asia/Tehran`, converted at the presentation layer
  (canonical-context §5). Backend never stores a Jalali string.
- Formats: date `۱۴۰۵/۰۹/۰۴`; long date `۴ آذر ۱۴۰۵`; date+time `۴ آذر ۱۴۰۵، ۱۴:۳۰`;
  relative `< 24h` («۲ ساعت پیش»), absolute beyond that; `< 60s` «همین حالا».
- Week starts **Saturday**; weekend is Thursday+Friday in date pickers and report presets.
- Month names: فروردین … اسفند; no Latin month names anywhere in the UI.
- Date ranges in reports: presets (امروز، دیروز، ۷ روز، ۳۰ روز، این ماه، ماه گذشته، امسال) plus
  custom Jalali range; the resolved Gregorian range is shown in the export file metadata so
  accounting can reconcile (appendix 13 clause 1).
- Countdowns (offer expiry, OTP, payment) show `mm:ss` with Persian digits and an
  `aria-live="off"` region updated once per minute to avoid screen-reader chatter.
- Timezone is displayed once, in the footer of reports: «همهٔ زمان‌ها به وقت تهران است».

---

## 8. Content and copy rules (Persian)

| Rule | Example |
|---|---|
| Buttons are verbs in imperative, 1–3 words | «افزودن به سبد», not «ثبت» alone |
| No English words where a settled Persian term exists | «سبد خرید», «تسویه حساب», «فاکتور» |
| Industry terms stay as the trade uses them | «همکار», «پارس», «تخته», «فوق سبک» are not translated |
| Numbers and units Persian | «۲٬۵ متر مربع» |
| Errors state the fix | «شماره شبا باید با IR شروع شود و ۲۴ رقم باشد.» |
| Empty states offer the next step | «هنوز فروشگاهی ثبت نکرده‌اید. اولین فروشگاه را ثبت کنید.» |
| No blame, no exclamation marks, no «خطا!» | «ذخیره نشد. اتصال را بررسی و دوباره تلاش کنید.» |
| Status vocabulary is fixed | در انتظار پرداخت · پرداخت شده · در حال آماده‌سازی · ارسال شده · تحویل شده · لغو شده · مرجوع شده |
| Role vocabulary is fixed | مصرف‌کننده · فروشگاه (همکار) · تأمین‌کننده · بازاریاب · مدیر سامانه |

The fixed vocabularies above are used in badges, filters, exports and emails — one word per state,
so a support agent and a customer discuss the same thing.

---

## 9. Implementation mapping

- **Tokens** live in `resources/css/tokens.css` as CSS custom properties (`--color-*`,
  `--space-*`, `--radius-*`, `--shadow-*`, `--text-*`, `--duration-*`). Tailwind 4 `@theme` maps
  them to utilities (`bg-brand-600`, `text-neutral-700`, `rounded-md`, `shadow-2`). No arbitrary
  values (`bg-[#123456]`, `p-[13px]`) in Blade — a lint rule fails the build.
- **Components** are anonymous Blade components under `resources/views/components/ui/` with a
  sibling `*.blade.php` docblock listing variants, and Livewire components under
  `app/Modules/<Module>/Http/Livewire/` for stateful pieces (filters, cart, chat, data tables).
- **Do not** put design decisions in module CSS; module folders may add layout-only classes.
- **Print**: a `print.css` layer reduces chrome, forces black text, and renders invoices and
  settlement statements on A4 with the company header from settings.
- **Icon set**: Lucide, imported per-icon (no full sprite), mirrored icons flagged
  `data-directional`.
- **Governance**: token or component changes go through a PR touching this document plus a visual
  regression run on the 20 reference screens (home, category, product, cart, checkout, order
  detail, wallet, store dashboard, supplier order detail, admin dashboard, admin approvals,
  marketer network). A screen that needs a new pattern gets a §5 entry first.

### 9.1 Reference screens for visual QA

Home · Category listing · Product detail · Cart · Checkout · Payment result · Customer orders ·
Order detail · Wallet · Store dashboard · Product form · Supplier order detail · Marketer network
· Marketer registration form · Admin dashboard · Admin approvals · Admin product form · Admin
settlements · Login/OTP · 403.
