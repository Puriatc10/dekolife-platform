# Technology Stack

| | |
|---|---|
| **Purpose** | Pin every runtime, library and tool the platform depends on, state why it is there, what it cannot do, and how it gets upgraded — so that "what version are we on" is never a conversation. |
| **Audience** | Backend, frontend, DevOps, the client's technical reviewer, and the maintainer who inherits this in year three. |
| **Status** | Baseline v1.0. Versions marked *unverified* are pinned at scaffold time and gated by lockfiles. |
| **Owner** | Agent C (architecture). |

**Source:** contract `DK-1405-01` page 5 / پیوست ۱ clauses 1–10 (Laravel + MySQL, MVC three-layer, modular, documented source, responsive, PWA, 12 gateways, browser support, admin panel on separate domain/path with 2FA, extensible schema, daily backup, audit log); page 10 / پیوست ۹ clauses 1–6 (SEO, GTmetrix B/A, SSL, brute-force, captcha, RBAC, input validation); `docs/canonical-context.md` §2.

---

## 1. Pinned versions

| Component | Pinned | Verification status |
|---|---|---|
| PHP | 8.4 (`php:8.4-fpm-bookworm`, digest-pinned) | Major mandated by contract; patch pinned by image digest |
| `laravel/framework` | **13.44.1** | Verified 2026-11-25 from Packagist |
| `livewire/livewire` | **4.4.10** | Verified 2026-11-25 from Packagist |
| MySQL | **8.4** (`mysql:8.4`, LTS line) | Major mandated by contract; patch pinned by image tag |
| Tailwind CSS | 4.x | Unverified — exact minor resolved at scaffold, recorded in `package-lock.json` |
| Alpine.js | 3.x | Unverified — resolved at scaffold with Livewire 4 |
| Vite | 7.x (Laravel 13 default) | Unverified — resolved at scaffold |
| Pest | 4.x | Unverified — resolved at scaffold |
| PHPStan + Larastan | PHPStan 2.x / Larastan 3.x, **level 6** | Unverified — resolved at scaffold; level is fixed at 6 |
| Laravel Pint | 1.x | Unverified — resolved at scaffold |
| nginx | 1.27 stable | Pinned by image tag |
| Node | 22 LTS (build only) | Pinned by image tag |
| Docker Engine / Compose | Compose v2 spec (`compose.yaml`) | Host-provided; `docker compose version` recorded in runbook |

**Rule:** no version is ever "assumed". A version is either (a) verified against the registry on the date in the table, or (b) quoted as "unverified — pinned at scaffold" until the lockfile exists, at which point this table is updated in the same PR that creates the lockfile. Re-verification is required for every dependency before a major upgrade and at each quarterly maintenance window.

## 2. Runtime and framework

### 2.1 PHP 8.4 (fpm + cli)

| | |
|---|---|
| **Responsibility** | Language runtime for the web app, queue workers, scheduler and console commands. |
| **Why** | Contract clause: the framework requirement implies a supported PHP; 8.4 gives typed properties everywhere, `readonly` classes for value objects, `json_validate()`, and the property hooks we use sparingly in DTOs. One runtime for web and workers means one image, one set of extensions, no drift. |
| **Alternatives considered** | PHP 8.3 (loses `json_validate`/property hooks, no benefit); PHP 8.2 (already old); HHVM (dead). Downgrading buys nothing and costs a rewrite of typed code. |
| **Limitations** | PHP 8.4 is at the end of its active-support window in this project's timeline; security fixes continue, feature work does not. Long-running workers still leak if a job holds large result sets — we cap batch sizes and restart workers nightly. No native async; concurrency comes from fpm workers and queue workers only. |
| **Upgrade policy** | Patch/minor: follow the base image digest automatically when CI is green. Major (8.5): planned in Q1 2027, one ADR, two weeks on staging, run the full Pest suite plus a load smoke test. |

Extensions required: `pdo_mysql`, `mbstring`, `intl`, `bcmath`, `gd` (image re-encode), `zip`, `exif`, `opcache`, `sodium`. `pcntl` for `queue:work` timeouts. Configured in `docker/php/php.ini` with `opcache.validate_timestamps=0` in production, `memory_limit=512M`, `upload_max_filesize=20M`, `post_max_size=24M`, `max_execution_time=60` (fpm) and `0` (cli).

### 2.2 Laravel 13.44.1

| | |
|---|---|
| **Responsibility** | HTTP kernel, routing, container, Eloquent ORM, migrations, queue, scheduler, events, validation, Blade, mail, notifications, filesystem. |
| **Why** | Contract appendix 1 clause 1 mandates Laravel. Version 13 gives the current LTS-adjacent line, first-class `ShouldDispatchAfterCommit`, typed config access, and native `Context` for request-scoped metadata (we attach `request_id`, `actor_id`, `module`). |
| **Alternatives considered** | Symfony full-stack (no contract fit, more assembly); Lumen (unmaintained for this use); Slim (no ORM/auth/queue ecosystem). Not a real choice — the contract fixes it. |
| **Limitations** | Eloquent's active-record style invites business logic in models; we counter it with the layering rules in `modular-monolith.md` and an architecture test. Framework upgrades are the single largest recurring maintenance cost. Blade + Livewire means no client-side router; heavy client interactivity is Alpine, not React. |
| **Upgrade policy** | Patch (`13.44.x`): merged by Dependabot when CI is green, weekly. Minor (`13.x`): same, after a staging deploy. Major (14): ADR required, dedicated branch, two-week staging soak, no major upgrade during an acceptance window. Security advisories: patched within 7 days regardless of cadence. |

### 2.3 MySQL 8.4

| | |
|---|---|
| **Responsibility** | System of record for all 28 modules, the queue, cache, sessions and the ledger. |
| **Why** | Contract appendix 1 clause 1. 8.4 is an LTS line with a long support horizon, native JSON, generated columns (used for blind indexes), `CHECK` constraints (used for state/amount invariants), and window functions for reporting without a warehouse. Same major version in local, CI, staging and production removes "works on my machine". |
| **Alternatives considered** | PostgreSQL (contract forbids it, and would invalidate the client's hosting); MariaDB (not the contract's MySQL, subtle JSON/optimizer differences); SQLite for tests (explicitly rejected — tests must exercise MySQL semantics, `canonical-context.md` §2). |
| **Limitations** | No native partial indexes (workaround: generated column + index). Row-level `SELECT ... FOR UPDATE` serialises hot rows — acceptable at expected volume, but festival-campaign stock contention is a known risk. JSON columns are not indexable directly; we only query them through generated columns. FULLTEXT is decent but not a search engine (`RB-CAT-01`). Single primary; no read replicas in v1 (`RB-OPS-02`). |
| **Upgrade policy** | Patch within 8.4: image tag bump, quarterly. Major (8.5/9.x): requires an ADR, a dump/restore rehearsal on staging, and a maintenance window — never in-place on the client's production host without a verified restore. |

InnoDB, `utf8mb4_0900_ai_ci` collation, `innodb_flush_log_at_trx_commit=1`, `binlog` enabled (point-in-time recovery), `sql_mode` strict, `time_zone='+00:00'`.

## 3. Presentation

### 3.1 Blade + Livewire 4.4.10 + Alpine.js

| | |
|---|---|
| **Responsibility** | Server-rendered UI for storefront, role panels and the admin panel; interactive islands (filters, cart, chat, wizard steps) without a SPA. |
| **Why** | Contract appendix 1 clause 1 asks for an MVC three-layer architecture; Blade/Livewire keeps the view layer a view layer, shares validation and policies with the server, and removes an entire API-contract-plus-client-state surface. Livewire 4 ships with Alpine and morphing, which is enough for the filter/cart/chat interactions in پیوست ۲ and پیوست ۵. The customer-facing design must be bespoke (پیوست ۲ بند ۱ forbids a ready-made template for the user side), and Blade components give us that control without adopting a JS framework. |
| **Alternatives considered** | Inertia + Vue/React (adds a client build, a second state model and an API contract for the web — the exact thing we avoid); Vue SPA + API (duplicate authorization/validation surfaces; slower first paint for an RTL commerce catalog); htmx (viable, but weaker form/validation integration than Livewire). |
| **Limitations** | Every interaction is a round trip: latency on slow Iranian mobile networks is real, mitigated with `wire:loading` skeletons, request bundling and 30 s polling instead of websockets. Livewire component state is server memory — large tables must be paginated, never fully hydrated. No offline write support; PWA caching is read-only (`RB-PWA-01`). |
| **Upgrade policy** | Minor upgrades monthly with the framework; major (5.x) requires an ADR because component lifecycle changes ripple through every screen. |

Livewire conventions: one component per interactive region, `#[Locked]` on ids, `#[Validate]` rules on public properties, no Eloquent models as public properties (DTOs or scalars only), `wire:key` on every loop.

### 3.2 Tailwind CSS 4

| | |
|---|---|
| **Responsibility** | The entire styling system: RTL-first layout, design tokens, responsive breakpoints, dark-mode-ready tokens (not shipped in v1). |
| **Why** | Contract requires full responsiveness (پیوست ۱ بند ۳) and browser support for Chrome/Edge/Firefox/Safari and their mobile versions (بند ۶). Tailwind 4's CSS-first config (`@theme`) fits a bespoke design system better than a utility framework with a JS config, and it produces a small RTL stylesheet with logical properties (`ms-*`, `me-*`, `ps-*`, `pe-*`) instead of direction-specific hacks. |
| **Alternatives considered** | Bootstrap 5 RTL (recognisable but generic, fights the bespoke design requirement); plain SASS/BEM (contract mentions SASS/CSS/HTML, but hand-written BEM for this surface area costs weeks and drifts); CSS-in-JS (no Node runtime in production, wrong fit). |
| **Limitations** | Class-heavy markup; needs component extraction discipline (`resources/views/components/ui/*`). Tailwind 4 requires a modern browser baseline — acceptable given the contract's browser list, and the Vite build targets the last two versions of each named browser. |
| **Upgrade policy** | Minor within 4.x monthly. Major requires an ADR plus a full visual regression pass on the 12 highest-traffic screens. |

### 3.3 Vite

| | |
|---|---|
| **Responsibility** | Asset bundling for JS/CSS, HMR in local dev, hashed production assets with a manifest. |
| **Why** | Laravel's default; fastest dev loop; integrates with Livewire's asset directives. |
| **Alternatives considered** | Webpack Encore (slower, more config), esbuild alone (no CSS pipeline/HMR story), no bundler (no cache busting). |
| **Limitations** | Node is a build-time dependency only; CI must build assets because the client's server will not have Node. Build output is an artifact, not a git artifact. |
| **Upgrade policy** | Minor monthly; major with the Node LTS bump, tested by a full `npm ci && npm run build` in CI. |

### 3.4 PWA layer (later phase)

| | |
|---|---|
| **Responsibility** | Installability on Android/iOS from the browser, app icon, splash screen, intelligent caching, push subscription (پیوست ۱ بند ۴; پیوست ۵ بند ۲۷; پیوست ۸ بند ۳). |
| **Why** | The contract treats PWA as the mobile web deliverable; it is never described as a native app (`canonical-context.md` §0.3). |
| **Alternatives considered** | Native shell (out of scope), Capacitor wrapper (deferred with the mobile apps). |
| **Limitations** | iOS PWA push requires iOS 16.4+ and home-screen installation; background sync is unreliable — no offline order placement. |
| **Upgrade policy** | Service worker versioned with the app build; cache strategy changes ship behind `adm_feature_flags`. |

## 4. Quality gates

### 4.1 Pest

| | |
|---|---|
| **Responsibility** | Unit, feature, architecture and (later) browser tests. |
| **Why** | Laravel's default test runner; expressive syntax; `arch()` presets give us enforceable layering tests, which is the only mechanism that stops a modular monolith from rotting. Runs against real MySQL (no SQLite) per `canonical-context.md` §2. |
| **Alternatives considered** | PHPUnit directly (Pest is a thin layer on it, no loss); Codeception (heavier, no arch tests). |
| **Limitations** | Feature tests are slow against MySQL; mitigated with `RefreshDatabase` + transaction wrapping and parallel test databases (`--parallel` with per-process schemas). No built-in mutation testing — deferred (`RB-QA-01`). |
| **Upgrade policy** | Minor monthly; major follows PHPUnit's major and requires rewriting only custom expectations. |

Coverage policy is behavioural, not percentage-based: every money, points, commission, settlement and authorization rule needs a test; every scoped resource needs a negative authorization test (`canonical-context.md` §7).

### 4.2 PHPStan + Larastan (level 6)

| | |
|---|---|
| **Responsibility** | Static analysis of `app/`, `tests/` and module code at level 6, with Larastan's Eloquent/container awareness. |
| **Why** | Level 6 catches missing type hints and unsafe array access, which is where most of the money-handling bugs hide. Larastan understands model properties from migrations, so `$order->total_amount` is typed as `int` rather than `mixed`. |
| **Alternatives considered** | Psalm (viable, weaker Laravel plugin ecosystem); level 8/9 (would require annotating every Eloquent relation and third-party generic — poor return for this codebase); no static analysis (not acceptable for a ledger). |
| **Limitations** | Cannot see through `DB::raw`, dynamic where-clauses, or array-shaped config; those spots get `@phpstan-ignore` with a reason comment, never a bare ignore. Baseline file is forbidden — new code must be clean. |
| **Upgrade policy** | Level is fixed at 6 for v1; raising it is a separate, dated task. Tool version follows PHPStan's minor releases monthly. |

### 4.3 Laravel Pint

| | |
|---|---|
| **Responsibility** | PSR-12 formatting, `declare(strict_types=1)` insertion, import ordering. |
| **Why** | Zero-argument formatting removes style debate from review. |
| **Alternatives considered** | PHP-CS-Fixer directly (Pint wraps it), no formatter (no). |
| **Limitations** | Opinionated; some rules fight long fluent chains — configured once in `pint.json`, not per-file. |
| **Upgrade policy** | Minor monthly; formatting-only PRs are merged with no review beyond CI. |

## 5. Build and delivery tooling

### 5.1 Docker Compose

| | |
|---|---|
| **Responsibility** | Reproducible local environment: `app` (php-fpm), `web` (nginx), `worker`, `scheduler`, `mysql`, `mailpit`, optional `node` for HMR. Also the image built for staging/production. |
| **Why** | The host has no PHP (`canonical-context.md` §2), and local/CI/staging parity is the only way to catch MySQL-version and PHP-extension differences before the client's server does. One image for web and workers removes "works in the queue but not in the request". |
| **Alternatives considered** | Laravel Herd/Valet (host PHP, no MySQL version parity, no worker containers); Vagrant (slow, another tool); plain host install (the thing we are avoiding). |
| **Limitations** | Docker Desktop on Windows/macOS has file-sync overhead on large vendor trees (mitigated with a named volume for `vendor`); Compose is not an orchestrator — no health-based restarts beyond `restart: unless-stopped`; production on the client's host is **not** containerised (nginx + fpm installed directly per `deployment-topology.md` §4). |
| **Upgrade policy** | Compose spec follows Docker Engine on the developer machines; base images refreshed quarterly and pinned by digest in the Dockerfile; the `mysql:8.4` and `php:8.4-fpm` tags move only with a deliberate PR. |

### 5.2 nginx

| | |
|---|---|
| **Responsibility** | TLS termination, static file serving, fastcgi to PHP-FPM, rate limiting, blocking PHP execution outside `public/`, two server blocks (storefront and admin host). |
| **Why** | Present on any Iranian host, well understood by the client's IT, and the config is small enough to review. The admin host separation required by پیوست ۱ بند ۷ is a server-block decision, which nginx expresses naturally. |
| **Alternatives considered** | Apache + mod_php (worse concurrency, `.htaccess` sprawl); Caddy (simpler TLS but less familiar to the client's operators); OpenLiteSpeed (licensing and familiarity). |
| **Limitations** | No WAF, no bot management, no HTTP/3 in v1. `limit_req` is per-IP, so a distributed attack is only partly mitigated (`RB-SEC-03`). Config drift between staging and production is a real risk — the file is versioned in `deploy/nginx/`. |
| **Upgrade policy** | Stable branch (1.27.x at baseline), quarterly image/package refresh, config tested with `nginx -t` in CI against a rendered template. |

### 5.3 GitHub Actions

| | |
|---|---|
| **Responsibility** | The only build path: Pint, PHPStan, Pest on MySQL, frontend build, dependency and secret scan, release artifact. |
| **Why** | Zero infrastructure to run, free minutes for a private repo of this size, native MySQL service containers, and the repository already lives on GitHub. The artifact is the deployable — no build on the production host. |
| **Alternatives considered** | GitLab CI (repo is on GitHub); Jenkins (a server to maintain); local build + rsync (no reproducible gate, no secret scanning). |
| **Limitations** | 6-hour job limit and 2 vCPU runners — the parallel Pest suite is the long pole (~6–8 min); no persistent cache beyond `actions/cache`; macOS/Windows runners are not used (not needed). GitHub availability is a single point of failure for merging, not for serving traffic. |
| **Upgrade policy** | Actions pinned by major tag (`actions/checkout@v4`) and reviewed quarterly; a major bump is a separate PR with a green run. Runner image (`ubuntu-24.04`) pinned explicitly, not `ubuntu-latest`. |

## 6. Platform conventions that are not libraries

| Convention | Decision | Why it matters |
|---|---|---|
| Money | integer Toman, `BIGINT *_amount`, `App\Shared\Money\Money` | Contract prices are Toman; floats lose cents and break ledger reconciliation (`ADR-0004`) |
| Time | store UTC, display `Asia/Tehran`, Jalali formatting at the edge | DST-free zone but Jalali month boundaries drive settlement and reporting periods |
| Jalali calendar | `morilog/jalali` (candidate, pinned at scaffold) | Needed for invoices, reports, settlement periods, birthday greetings (پیوست ۱۲ بند ۷) |
| IDs | `BIGINT UNSIGNED` PK + `public_id` ULID for anything in a URL | Never expose sequential ids; ULIDs sort by time and index well |
| Queue/cache | `database` driver | `ADR-0006` |
| Sessions | `database`, separate cookie names per guard | Admin and storefront sessions must not share a cookie |
| Files | `FileStorage` over `local` (private) and `public` disks | Identity documents never on the public disk |
| Excel | `maatwebsite/excel` (PhpSpreadsheet) | Contract asks for Excel exports (پیوست ۷ بند ۱۰; پیوست ۱۳ بند ۳) |
| Images | `intervention/image` | Re-encode uploads to strip EXIF and normalise sizes |
| QR | `bacon/bacon-qr-code` | Marketer QR codes (پیوست ۵ بند ۱) generated server-side, no third-party QR service |
| Push | `minishlink/web-push` (VAPID) | PWA push without a paid provider |
| Backups | `spatie/laravel-backup` | Wraps `mysqldump` with retention and off-site upload (پیوست ۱ بند ۹) |
| HTTP client | Laravel `Http` facade with per-gateway timeouts/retries | One place to enforce timeouts and log outbound calls |

## 7. Browser and device support

Contract: full compatibility with Chrome, Edge, Firefox, Safari and their mobile versions (پیوست ۱ بند ۶). Practical baseline from the Vite build target: last two versions of each, iOS Safari 16.4+ (PWA push), Android Chrome 120+. No Internet Explorer, no Opera Mini. Progressive enhancement: the catalog, cart and checkout must render and submit without JavaScript where the framework allows it; Livewire islands degrade to full page reloads.

## 8. Explicitly rejected dependencies

| Candidate | Why rejected |
|---|---|
| `spatie/laravel-permission` | Roles are contractual (5 levels + admin-enabled secondary level) and need scope semantics; a generic permission package adds a parallel authorization model next to policies (`ADR-0007`) |
| Redis / Horizon | Operationally unjustified for a single-node deployment (`ADR-0006`) |
| Laravel Octane | Requires a persistent server (Swoole/RoadRunner) the client host will not run; state leakage risk in a modular monolith |
| A ready-made admin template for the customer side | Forbidden by پیوست ۲ بند ۱; admin side may use one |
| `kreait/firebase-php` for push | Web push over VAPID is free and sufficient for the PWA |
| Elasticsearch / Meilisearch | Catalog size does not justify a second data store (`RB-CAT-01`) |
| GraphQL | No client needs it; adds an authorization surface to audit |

## 9. Maintenance calendar

| Cadence | Action |
|---|---|
| Weekly | Dependabot PRs for patch/minor, merged on green CI; `composer audit` / `npm audit` reviewed |
| Monthly | Dependency review meeting; Larastan/Pint/Pest minors; `RB-*` backlog triage |
| Quarterly | PHP/MySQL/nginx image digest refresh; restore drill from backup; SSL and domain expiry check |
| Per major | ADR, staging soak, rollback rehearsal, this table updated with the new verified date |
