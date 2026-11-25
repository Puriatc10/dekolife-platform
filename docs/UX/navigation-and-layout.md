# Navigation & Layout — Dekolife Web Platform

**Purpose:** pin the page shell, navigation model, RTL behaviour and state patterns once, so six
panels feel like one product and no screen invents its own chrome.
**Audience:** frontend implementers (Blade + Livewire 4 + Alpine + Tailwind 4), designers, QA.
**Status:** baseline; every rule below is testable.

Source: appendix 2 clauses 1–12 (pages & UX), appendix 1 clauses 3–7 (responsive, PWA, browser
support, separate admin panel), appendix 7 clauses 9–10 (admin-managed menus/footer), appendix 3
§3-1…§3-5 (panel scope), appendix 6 clauses 1–12 (marketer navigation needs), canonical-context
§2 (stack, RTL-first) and §5 (authorization lives on the server).

---

## 1. Shell anatomy

Every screen is composed of the same five regions. Regions are optional per panel, never per
page:

```
┌──────────────────────────────────────────────────────────────┐
│ R1  utility bar    (desktop only, 32px)                      │
├──────────────────────────────────────────────────────────────┤
│ R2  primary header (logo · search · actions)   sticky, 72px  │
├──────────────────────────────────────────────────────────────┤
│ R3  navigation     (mega menu  |  panel sidebar)              │
├──────────────────────────────────────────────────────────────┤
│ R4  content        (breadcrumbs, page header, body)           │
├──────────────────────────────────────────────────────────────┤
│ R5  footer         (storefront)  |  panel footer (legal only)  │
└──────────────────────────────────────────────────────────────┘
```

| Region | Storefront | Customer | Store / Supplier / Marketer | Admin |
|---|---|---|---|---|
| R1 utility bar | yes | no | no | no |
| R2 header | full (search, cart, account) | compact (back to shop, notifications) | compact + role chip | admin header (env badge, search, 2FA state) |
| R3 nav | mega menu | panel sidebar (collapsed to icons at `lg`) | panel sidebar | admin sidebar (grouped, 2 levels) |
| R5 footer | full, admin-editable | legal strip | legal strip | status strip (version, queue health) |

**Breakpoints** (Tailwind defaults, mobile-first): `sm 640`, `md 768`, `lg 1024`, `xl 1280`,
`2xl 1536`. Content max width `1440px`, gutters `16 / 24 / 32px`. Panels with data tables switch
from cards (below `lg`) to tables (`lg` and up) — never a horizontally scrolling table on a
phone.

**Sticky rules.** R2 sticks at `top: 0` with `z-index: 40` and becomes 56px tall on scroll (logo
+ search + cart only). The mega menu is not sticky. Panel sidebars stick below R2 and scroll
independently (`max-height: calc(100vh - 72px)`).

---

## 2. Global header (storefront)

Top-to-bottom, right-to-left reading order:

1. **Utility bar** (R1, `--surface-sunken`): delivery promise («ارسال به سراسر کشور»), order
   tracking link («پیگیری سفارش»), support phone, and the login/register link for anonymous
   users or «حساب کاربری» for signed-in users. Admin-editable strings (REQ-CMS-002). Hidden
   below `md`.
2. **Logo** — right edge in RTL, links to `/`. Accessible name «دکولایف، صفحه اصلی».
3. **Search** — see §7. Occupies the flexible middle column, minimum 320px, maximum 720px.
4. **Action cluster** (left edge): notifications bell with unread count, favorites heart,
   cart with item count and subtotal tooltip, account menu. On `md` and below the cluster
   collapses to cart + hamburger.
5. **Role chip.** A signed-in user with more than one role sees a chip with the active panel
   (e.g. «پنل بازاریاب») and a menu listing the other panels. Switching panels is a navigation,
   not a role change (REQ-IAM-005, REQ-USR-008).
6. **Notification bar** — optional full-width strip under R2 for admin broadcasts
   (REQ-NTF-005). Dismissible per session, `role="status"`, never blocks the header.

Cart badge updates arrive over Livewire without a page reload; the badge is an `aria-live="polite"`
region announcing «سبد خرید: ۳ کالا».

---

## 3. Mega menu

Desktop only (`lg+`). Three-level maximum: category → sub-category → leaf group.

- **Trigger:** the «دسته‌بندی کالاها» button in R3. Opens on click and on `ArrowDown`; hovering a
  sibling top-level item switches panels. Escape closes and returns focus to the trigger.
- **Panel layout:** RTL grid, 4 columns of links + 1 promotion column (image, headline, link) on
  the far left. Promotion slot is admin-managed (REQ-CAT-008).
- **Content:** top 8 sub-categories per column, then «همهٔ محصولات {دسته}» linking to
  `/c/{category}`. Category icons are 20px, aligned to the right of the label.
- **Keyboard contract:** `Tab` moves through links in visual order; `ArrowRight`/`ArrowLeft`
  move between columns RTL-aware (`ArrowLeft` advances visually left = next column); `Home`/`End`
  jump to first/last; the panel is a `role="menu"` with `aria-orientation="vertical"` per column
  and `aria-haspopup="true"` on the trigger.
- **Performance:** the panel is server-rendered into the layout for the top 12 categories and
  cached for 10 minutes; it must paint within 100ms of the click (no network round-trip).
- **Mobile substitute:** the drawer's first accordion («دسته‌بندی‌ها»), same data, one column.

Menu items and ordering come from `cms_menus` (REQ-CMS-002); the tree is capped at three levels
by validation, because a fourth level cannot be rendered legibly in a mega menu.

---

## 4. Mobile navigation

Below `lg`, the storefront uses three mechanisms and nothing else:

1. **Sticky header** (56px): hamburger (right), logo (center), cart (left).
2. **Search** is a full-screen overlay opened by the search icon or by tapping the logo area's
   search field; it contains the input, recent searches, and trending terms.
3. **Bottom navigation** (fixed, 56px + safe-area inset, `md` and below):

| Slot | Label | Icon | Route | Badge |
|---|---|---|---|---|
| 1 | خانه | home | `/` | — |
| 2 | دسته‌بندی | grid | `/categories` (drawer state) | — |
| 3 | جستجو | search | opens overlay | — |
| 4 | سبد خرید | cart | `/cart` | item count |
| 5 | حساب من | user | `/account` or `/auth/login` | unread messages |

Rules: exactly five slots, never six; labels always visible (icon-only navigation fails the
recognition criterion in §10); the active slot uses brand color plus a 2px top indicator; the bar
hides on scroll-down past 200px and returns on scroll-up; `padding-bottom: env(safe-area-inset-bottom)`.
Panels do **not** use bottom navigation — they use a drawer with the panel sidebar (§5).

`/categories` resolves to a full-screen category list when opened from slot 2 and is the same
component the drawer shows.

---

## 5. Panel navigation

### 5.1 Shared sidebar rules

- Fixed order, grouped by task frequency, not by module name. Maximum two levels; anything
  deeper is a tab inside a screen.
- **Permission-driven visibility:** the sidebar is rendered from the actor's permission set
  (`can('viewAny', Order::class)` etc.). A hidden item is *not* authorization — the route still
  enforces the policy (canonical-context §5). Hiding prevents dead ends, not access.
- **Disabled state** is used only when a feature exists but is gated by a plan or unapproved
  profile: the item renders muted with a lock icon and a tooltip carrying the reason
  («پس از تأیید مدارک فعال می‌شود»). It never renders as a 403 on click.
- Badge counters (pending orders, unread tickets, approvals) render as a Persian-digit pill; the
  poll interval is 60s and pauses when the tab is hidden.
- Active item: 3px brand bar on the **right** edge (RTL), `--surface-selected` background.
- Collapse at `lg` to a 72px icon rail with a flyout on hover/focus; the collapsed state persists
  per user in `localStorage`.
- Below `lg` the sidebar becomes a right-side drawer with a scrim; focus is trapped, Escape
  closes, and the drawer state resets on navigation.

### 5.2 Customer panel — `/account`

Order: خلاصه (`/account`) · سفارش‌ها · پیگیری مرجوعی · کیف پول · امتیاز و ستاره‌ها · علاقه‌مندی‌ها ·
آدرس‌ها · نظرات من · پیام‌ها · تیکت‌ها · اعلان‌ها · اطلاعات کاربری · امنیت حساب.

Footer of the sidebar: a «ورود به پنل‌های دیگر» block listing secondary panels.

### 5.3 Store panel — `/store`

Order: داشبورد · محصولات · قیمت‌ها · موجودی انبار · سفارش‌ها · فاکتورها · تسویه و گردش مالی ·
مناقصه‌ها · درخواست سمپل · گزارش فروش · نظرات · پیام‌ها · تیکت‌ها · پروفایل فروشگاه.

Grouping: two visible groups — «فروش» (products → orders → invoices) and «مالی» (prices,
settlements, reports). A store that is also a supplier (secondary role) sees a footer link
«پنل تأمین‌کننده».

### 5.4 Supplier panel — `/supplier`

Order: داشبورد · محصولات · قیمت‌گذاری · موجودی · سفارش‌ها · ارسال و بارنامه · فاکتورها · فاکتور
تکمیلی · تسویه و صورت‌حساب · خسارت تأخیر · نمایندگی‌ها · جشنواره‌ها · بنر تبلیغاتی · اسلایدشو ·
نوتیفیکیشن · پنل پیامک · سمپل و کاتالوگ · مناقصه و مزایده · گزارش فروش · پیام‌ها · تیکت‌ها ·
صفحه عمومی برند.

Grouping: «کاتالوگ» · «سفارش و ارسال» · «مالی» · «بازاریابی» · «ارتباطات».

### 5.5 Marketer panel — `/marketer`

Order: داشبورد · شبکه فروشگاه‌ها · ثبت فروشگاه جدید · مشتریان · ویزیت‌ها · سفارش مشتریان ·
فاکتورهای شبکه · پورسانت · تسویه · کد و QR اختصاصی · عملکرد · نقشه · گزارش تخلف · تیکت‌ها ·
پیام‌ها · پروفایل.

Persistent element: a primary CTA «ثبت فروشگاه جدید» at the top of the sidebar (the field task
that generates revenue) plus a «ویزیت امروز» shortcut. On mobile the two actions appear as a
fixed bottom action bar above the safe area.

### 5.6 Admin panel — `/admin`

Grouped, 2-level accordion, order:

1. **میزکار** — داشبورد · صف تأییدها · گزارش‌ها
2. **کاربران** — کاربران · نقش‌ها و دسترسی‌ها · ادمین‌ها · فروشگاه/تأمین‌کننده در انتظار تأیید
3. **کاتالوگ** — دسته‌بندی‌ها · برندها · ویژگی‌ها · محصولات · انبار · قیمت‌گذاری
4. **فروش** — سفارش‌ها · فاکتورها · مرجوعی‌ها · حمل و ارسال
5. **مالی** — تراکنش‌ها · درگاه‌ها · مغایرت‌گیری · تسویه‌ها · دفتر کل · پورسانت · خسارت‌ها
6. **بازاریابی** — بازاریابان · پلن‌ها · پورسانت‌ها · عملیات میدانی · وفاداری · کمپین‌ها · کدهای تخفیف
7. **محتوا** — صفحات · منوها · فوتر · ویجت‌های صفحه اصلی · بلاگ · نظرات · نوتیفیکیشن · نظرسنجی
8. **خدمات** — دیوار · پتوق · مناقصه · مزایده · سمپل · تیکت‌ها · پیام‌ها · گزارش تخلف
9. **تنظیمات** — عمومی · امنیت · نقش‌های ثانویه · پرداخت · اعلان‌ها · ورود داده · پشتیبان‌گیری

The admin sidebar shows a persistent **environment badge** (`محیط آزمایشی` / `عملیاتی`) — the
single most effective guard against acting on production data during acceptance.

### 5.7 Cross-panel switcher

A user with roles `store` + `supplier` + `consumer` gets three panels. The switcher lists only
granted panels, ordered consumer → store → supplier → marketer, with the current one marked
`aria-current="true"`. The admin panel never appears in this list: it is a different guard and a
different login (REQ-IAM-004).

---

## 6. Breadcrumbs and page headers

- Breadcrumbs appear on every storefront page below level 1 and on every panel detail screen.
  Not on dashboards (the sidebar already says where you are).
- Separator: `‹` rendered as an inline SVG chevron pointing **left** in RTL (`‹` visually), i.e.
  the reading direction of «parent → child». Implemented as `content` on a `::before` with
  `dir` awareness, never as a literal character that mirrors inconsistently.
- Format: `خانه › دسته‌بندی › پارکت › پارکت سه‌لایه بلوط`. Last item is plain text with
  `aria-current="page"`; earlier items are links. Maximum 4 visible levels, then collapse the
  middle into «…» with a menu.
- Structured data: `BreadcrumbList` JSON-LD on public pages (REQ-CAT-009).
- **Page header** pattern (panels): title (H1) + optional subtitle with count («۲۳ سفارش») +
  primary action on the left + secondary actions in an overflow menu. On mobile the primary
  action becomes a full-width button under the title when it is the page's main task.

---

## 7. Search behaviour

Search is the primary navigation on this catalog, so its contract is explicit.

| Aspect | Rule |
|---|---|
| Entry points | Header field, mobile overlay, `/search?q=`, category page «جستجو در این دسته» checkbox |
| Debounce | 250ms after keystroke; requests cancelled on new input (`AbortController`) |
| Minimum length | 2 characters for suggestions, 1 accepted on submit |
| Normalisation | Persian/Arabic unification (ي→ی, ك→ک), ZWNJ handling, digit folding (۰-۹ and 0-9 equivalent), diacritic removal, trailing-space trim |
| Suggestions | Max 8: 5 products (image, title, price), 2 categories, 1 brand; plus «همهٔ نتایج برای …» |
| Recent searches | Last 5, stored locally, clearable; never synced across devices without consent |
| Keyboard | `ArrowDown/Up` moves the active option, `Enter` opens it, `Escape` closes; the listbox is `role="listbox"` with `aria-activedescendant` |
| Zero results | Show the query, spell-check hint, top 6 categories, and a «درخواست افزودن کالا» ticket link (REQ-MSG-004) |
| Scope chip | When searching inside a category the chip «در دستهٔ پارکت» is visible and removable |
| Result page | Facets identical to the category page (§ sitemap `/c/{category}`), query preserved across pagination and sort |
| Empty query | `/search` without `q` renders categories + popular searches, never a blank page |
| Analytics | Query, result count and clicked position are logged for merchandising (REQ-RPT-001); no personal data |
| Accessibility | Input has `aria-label="جستجوی محصول"`; the suggestion list announces result count changes politely |

Search must return in under 400ms p95 on the seeded catalog (50k products) for acceptance
performance (appendix 9 clause 2).

---

## 8. RTL layout rules

The UI is RTL Persian, built with **CSS logical properties only**.

| Concern | Rule |
|---|---|
| Direction | `<html lang="fa" dir="rtl">`. Never `direction` on individual blocks except for LTR islands. |
| Spacing | `margin-inline-start/end`, `padding-inline`, `inset-inline-start`. No `margin-left` unless deliberately physical (e.g. scrollbar gutter). |
| Sizing | `inline-size` / `block-size`; `text-align: start`. |
| Flex/grid | rely on `dir`; use `flex-direction: row` and let the browser mirror. Never `row-reverse` to fake RTL. |
| Borders | `border-inline-start` for the 3px active bar (lands on the right in RTL). |
| Icons | Mirror **directional** icons only: back/next chevrons, arrows, send, indent, undo/redo, list-order, progress arrows. Do **not** mirror: clock, search, cart, heart, user, trash, star, play, plus, close, camera, QR, map pin. Mirroring is a build-time rule: `[data-icon].is-directional { transform: scaleX(-1) }` under `[dir="rtl"]`. |
| Numerals | Persian digits (۰۱۲۳۴۵۶۷۸۹) in prose, prices, counts, dates, ratings. Latin digits only inside technical identifiers: IBAN/شبا, postal code, national ID input, tracking code, OTP, gateway reference, SKU. Those are wrapped in `<span dir="ltr" class="numeric-ltr">` with `unicode-bidi: isolate`. |
| Money | `۱٬۲۵۰٬۰۰۰ تومان` — Persian digits, U+066C thousands separator, unit after the number, non-breaking space between number and unit. |
| Mixed content | Model names, brand names and URLs inside Persian sentences get `dir="auto"` on the element or `<bdi>`; never rely on punctuation to fix order. |
| Forms | Labels above fields, `text-align: start`; numeric inputs `inputmode="numeric"` with Latin digits accepted and normalised on blur; currency affix «تومان» on the inline-end side. |
| Tables | Column order follows RTL: first column (identifier) on the right. Numeric columns align `text-align: end` but the digits themselves stay Persian. Sort indicators sit inline-end of the header label. |
| Sliders/carousels | First item on the right; navigation arrows point in reading order (‹ for next). Swipe direction follows RTL (swipe left = next). |
| Progress/steps | Stepper fills right → left; step numbers on the right of their labels. |
| Pagination | Page 1 on the right, «قبلی» arrow pointing right. |
| Toasts | Bottom-inline-start (bottom-left in RTL) so they never cover the primary action on the inline-end side. |
| Scrollbars | Native; `scrollbar-gutter: stable` on scroll containers to avoid layout shift. |

**Bidi isolation checklist for QA:** order tracking code page, IBAN display in settlements, SKU
in invoice lines, brand name inside a Persian title, OTP entry, URL shown in the supplier page
editor.

---

## 9. State patterns

Every data screen must implement all six states below. A screen that only implements "happy
path" fails review.

### 9.1 Empty

- **First-use empty** (no data yet): illustration, one sentence explaining the value
  («هنوز سفارشی ثبت نکرده‌اید»), and the primary action that creates the first record.
- **Filtered empty** (data exists, filters exclude it): «کالایی با این فیلترها پیدا نشد» +
  «حذف فیلترها» button + the count of removed filters. Never reuse the first-use illustration.
- **Permission-empty**: not used — that case is an unauthorized state, not empty.
- Empty states are `role="status"` and are announced once.

### 9.2 Loading

- Server-rendered pages: no spinner. Livewire updates use **skeletons** that match final layout
  dimensions (card: 3 lines + image block; table: 8 rows × 5 columns) to keep CLS < 0.1.
- Long operations (Excel export, points replay, bulk price update) use a job pattern: a toast
  «در حال آماده‌سازی فایل…» plus a notification when ready (REQ-NTF-004). Buttons enter a
  disabled + inline spinner state and show «در حال ارسال…».
- Skeletons use `aria-busy="true"` on the container and a visually hidden «در حال بارگذاری».

### 9.3 Error

- **Field errors:** inline under the field, `aria-describedby`, red border plus icon (never color
  alone), first invalid field receives focus on submit.
- **Form-level error:** a summary alert at the top of the form, `role="alert"`, listing errors as
  links to the fields.
- **Request failure:** inline retry block inside the affected region — the rest of the page stays
  usable. Copy: «اطلاعات دریافت نشد. دوباره تلاش کنید.» + «تلاش مجدد».
- **Payment failure:** dedicated result screen with the gateway reference, «پرداخت ناموفق»,
  the reason if the gateway returned one, «تلاش مجدد» and «انتخاب درگاه دیگر» (REQ-PAY-003).
- Never a raw exception, stack trace, SQL or gateway payload in the UI; the correlation ID is
  shown in small text for support.

### 9.4 Unauthorized

- `403` shell inside the panel chrome: title «دسترسی مجاز نیست», one sentence naming the required
  role in plain Persian («این بخش مخصوص سطح «فروشگاه» است»), actions: «بازگشت به داشبورد» and,
  when the user holds another role, «ورود به پنل …».
- **Never** reveal the existence of another tenant's record. A request for a foreign ID returns
  the same 403 body as a permission failure — no «not found» vs «forbidden» oracle
  (see `docs/requirements/permissions-matrix.md` §5.4).
- A gated feature that the user could unlock (unverified documents, plan upgrade) uses a
  different screen: «برای فعال‌سازی…» with the exact prerequisite and a support link.

### 9.5 Offline / degraded

- PWA shell serves `/offline` with cached categories and a «اتصال اینترنت قطع است» banner.
- Marketer visit capture stores drafts locally and syncs on reconnect; the visit list marks
  «در انتظار ارسال» with a retry action (REQ-GEO-001).
- Queue-backed actions submitted offline are rejected with a clear message, never silently lost.

### 9.6 Partial / stale data

- Any number older than 60s in a dashboard carries a «به‌روزرسانی» link and a relative timestamp
  («۲ دقیقه پیش»).
- Tables paginate at 20 rows default (`10/20/50/100`), sort and filter state lives in the query
  string so a link is shareable and the back button restores it.

---

## 10. Accessibility targets (WCAG 2.1 AA)

| Criterion area | Target | Implementation notes |
|---|---|---|
| Contrast | 4.5:1 body text, 3:1 large text and UI boundaries | Token pairs in `docs/UX/design-system.md` §2 are pre-checked; a CI script re-checks pairs |
| Focus | Visible 2px focus ring, 2px offset, never `outline: none` | `:focus-visible` on all interactive elements; skip link «رفتن به محتوا» first in DOM |
| Keyboard | Every action reachable; no traps except modals/drawers (Escape exits) | Mega menu, drawer, modal, combobox, date picker all keyboard-tested |
| Target size | 44×44px minimum touch target (24×24 CSS px absolute floor) | Icon buttons get padding not larger icons |
| Text scaling | Usable at 200% zoom, no horizontal scroll at 1280px | Rem-based type scale; no fixed-height text containers |
| Forms | Programmatic label, `autocomplete`, `inputmode`, error association, no placeholder-as-label | OTP field: 6 separate boxes with one accessible group label |
| Images | `alt` in Persian; decorative images `alt=""`; product images describe the product and finish | Supplier uploads require alt text at approval (REQ-MER-003) |
| Media | Video has captions or a text transcript link; no autoplay with sound | Product video plays muted on demand (REQ-CAT-004) |
| Motion | Respect `prefers-reduced-motion`; no flashing > 3/s; carousels pause on hover/focus | Rail animation disabled under reduced motion |
| Timed content | OTP and payment timers warn before expiry and allow extension | 2-minute OTP, resend after 60s |
| Tables | `<caption>`, `<th scope>`, sortable headers with `aria-sort` | Data tables in admin and panels |
| Live regions | Cart count, toasts, async validation, upload progress | `aria-live="polite"`; errors `assertive` |
| Language | `lang="fa"` on html; LTR islands get `lang`/`dir` | Prevents screen readers spelling Persian with Latin rules |
| Charts | Text alternative table for every chart (marketer performance, admin dashboards) | Toggle «نمایش جدول داده» |
| Errors | Error text states the fix, not just the fault | «شماره موبایل باید ۱۱ رقم و با ۰۹ شروع شود» |

Acceptance: axe-core automated pass (0 serious/critical) on the 20 highest-traffic screens plus a
manual keyboard-only walkthrough of checkout, store onboarding and admin approval recorded in
`docs/testing/`.

---

## 11. Layout performance rules

- Reserve space for every image and embed (`width`/`height` or `aspect-ratio`), so CLS < 0.1.
- Product card images: `aspect-ratio: 1/1`, `loading="lazy"` below the fold, `fetchpriority="high"`
  on the LCP image only.
- Fonts: Vazirmatn self-hosted, `woff2`, `font-display: swap`, preload the 400 and 700 weights
  only; `unicode-range` split for Arabic/Persian subset.
- No layout shift from the notification bar: it pushes content, it does not overlay it.
- The mega menu payload is inlined in the layout (≈8KB gzipped) rather than fetched on hover.
