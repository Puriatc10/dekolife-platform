# ADR-0003: Blade + Livewire over an SPA

- **Status:** Accepted (agent decision, pending owner review)
- **Date:** 2026-11-25
- **Deciders:** Architecture owner; frontend lead
- **Related:** REQ-CTR-002, REQ-CTR-003 (responsive, PWA), REQ-CTR-SEO-001; ADR-0002; `../architecture/technology-stack.md` §3

## Context

The contract requires:

- a bespoke UX/UI design implemented in HTML/CSS/SASS with **no ready-made template for the customer-facing part** (پیوست ۲ بند ۱);
- a DiGiKala-style home page with search bar, categories, banner slider, special offers, best sellers and newest products (بند ۲);
- dynamic filters on category pages and product comparison of up to five items (بند ۳–۵);
- full responsiveness and PWA installability (پیوست ۱ بند ۳–۴);
- **technical SEO**: meta tags, sitemap, clean URLs, structured data, fast loading (پیوست ۹ بند ۱–۲), with a GTmetrix B/A target;
- browser compatibility across Chrome/Edge/Firefox/Safari and their mobile versions (پیوست ۱ بند ۶).

Two constraints dominate the choice. First, **SEO is a contractual deliverable** — an SPA that renders product content only in the browser starts from a handicap in exactly the channel this marketplace depends on. Second, the interactive surface is *forms and filters*, not a canvas application: add-to-cart, filter, compare, checkout steps, chat, visit forms.

## Decision

Server-render everything with **Blade + Livewire 4** (with Alpine.js for local UI state) and style it with **Tailwind CSS 4** through Vite.

- One route per page; the page is HTML on first byte, hydrating into Livewire islands for interactive regions.
- Livewire components hold **presentation state only** (filter values, wizard step, quantity input) and call application services through actions/controllers — never Eloquent directly.
- Interactive regions are deliberately small: filter panel, mini-cart, compare tray, checkout wizard, chat thread, visit form, admin tables.
- Long lists paginate server-side; no component ever hydrates a full table.
- The existing API surface (`routes/api.php`, Sanctum) remains for the future mobile apps and for any genuinely client-heavy widget that appears later.
- The admin panel uses the same stack; a purchased admin template is permitted there (the no-template rule applies to the customer-facing design only).

## Consequences

**Positive**

- SEO is native: every product, category, article and merchant page is crawlable HTML with server-rendered meta, canonical and structured data — no prerender service, no hydration mismatch, no double maintenance of routes.
- One language for validation, authorization and rendering. A form rule exists once (`FormRequest` + policy) and is enforced on the same request that renders the error.
- No client-side state synchronization, no API contract drift between web and mobile, no duplicate DTOs.
- Smaller JavaScript payload than a React/Vue storefront: Tailwind CSS plus a modest Alpine/Livewire bundle, which matters for the GTmetrix target on Iranian mobile networks.
- The team can move between storefront, panels and admin without context-switching frameworks.

**Negative**

- Every interaction is a network round trip. On a 3G connection a filter change is 300–800 ms; mitigated with skeletons, debounced inputs, `wire:loading` states and pagination — but a highly interactive comparison table will always feel slower than a client-rendered one.
- Livewire keeps component state in server memory: a careless component that loads 5,000 rows into a public property will exhaust PHP memory before it exhausts the browser.
- Offline behaviour is limited to read caching in the PWA; no offline cart mutations, no background sync.
- Rich client interactions (drag-to-compare, chat scroll, map) need Alpine glue, which is another small thing to learn and test.
- Real-time features need polling rather than websockets (`RB-GEO-01`), which is a deliberate trade for operational simplicity.

**Neutral**

- The API is not an afterthought: application services are the shared layer, so adding the mobile client later does not require rewriting the web.
- If a single screen ever needs a SPA (a live operations map, for example), it can be a small isolated Vue/Alpine island without changing the architecture.

## Alternatives considered

| Alternative | Why it lost |
|---|---|
| Vue/React SPA + JSON API | Duplicate validation and authorization on both sides, SSR still required for SEO, slower first paint, and a second state model to keep in sync; the interactive surface does not justify it |
| Inertia + Vue/React | Better than a pure SPA (server-side routing, no API contract), but still ships a client framework, still needs SSR for meta tags, and splits every feature across two languages |
| htmx + Alpine | Genuinely close: smaller and simpler than Livewire. Lost on form/validation ergonomics, file uploads with progress, and the ecosystem of ready Laravel integration |
| Blade only, full page reloads | Meets SEO and simplicity but fails the "dynamic filter" and cart UX expectations of a DiGiKala-style storefront; every filter click would be a full navigation |
| Filament for the admin panel | Attractive for CRUD, but the admin's hard parts (settlement approval, gateway activation, rule-set editors, audit viewer) are custom; the framework's conventions would fight the domain. Reconsiderable for simple CRUD screens later |
| Next.js frontend + Laravel API | Two deployables, two auth models, two caches, and the contract's single-server constraint; the SEO gain over server-rendered Blade is marginal |

## References

- Contract page 5 / پیوست ۱ clauses 3, 4, 6; page 5 / پیوست ۲ clauses 1–5; page 10 / پیوست ۹ clauses 1–2.
- `docs/canonical-context.md` §2 (Blade + Livewire 4 + Alpine + Tailwind 4 + Vite), §0.3 (PWA is web, never a native app).
- `../architecture/technology-stack.md` §3; `../architecture/overview.md` §4–§5.
