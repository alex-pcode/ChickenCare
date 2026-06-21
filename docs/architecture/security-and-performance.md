# Security and Performance

## Security

- **XSS:** Blade `{{ }}` auto-escapes all output
- **CSRF:** Token in every form (`@csrf`) and HTMX request (`hx-headers`)
- **SQL Injection:** Eloquent parameterized queries
- **Mass Assignment:** `$fillable` whitelist on every model
- **Authentication:** Laravel Breeze (bcrypt, session cookies, login throttling)
- **Authorization:** Policies check `$user->id === $model->user_id` on every record access
- **Query Scoping:** All queries start from `$request->user()->relationship()`
- **Tier Enforcement:** `EnsurePremiumTier` middleware on route groups

## Performance Targets (Local)

| Metric | Target |
|--------|--------|
| Full page load | < 200ms |
| HTMX partial swap | < 100ms |
| CRUD operation | < 50ms |
| Dashboard load | < 500ms |
| CSS bundle | < 50KB gzipped |
| JS bundle | < 100KB gzipped |
| DB queries per page | < 10 |

## Optimization Techniques

### Application-level

- **Eager loading** with `with()` to prevent N+1
- **Pagination** on all list views (15-25 items)
- **Composite indexes** on `(user_id, date DESC)`
- **Range-based date filters** with `whereBetween()` for month/year reports instead of `whereMonth()` / `whereYear()` so `date` and `sale_date` indexes stay usable
- **Shared request-scoped datasets** for repeated dashboard chart reads to avoid re-querying the same 30-day egg window within one request
- **Aggregate-first reporting** in dashboard, CRM, flock, and feed services so totals are computed in SQL instead of hydrating full model collections
- **No client-side state hydration** — pages interactive immediately

### Implemented hot-path fixes

- **Dashboard HTMX partials** now early-return recent activity before building the full dashboard summary, which keeps the `dashboard-activity` swap on a narrow query path.
- **Dashboard production charts** share one 30-day egg-entry dataset per request instead of running duplicate reads for the same window.
- **CRM overview reports** use grouped SQL aggregates for revenue, customer rankings, purchase cadence, production pipeline, and revenue trend data.
- **CRM per-customer reports** now use aggregate summaries, grouped monthly trends, and a limited recent-transactions query instead of loading full customer history into memory.
- **CRM cache invalidation** uses per-user versioned cache keys instead of tag-based invalidation so it works cleanly with the local `file` cache store.
- **CRM reports tab** only loads the customer selector list for per-customer views; overview requests skip that extra query entirely.
- **Shared HTMX skeleton loaders** now render immediately for boosted page navigation plus Account and CRM tab targets, masking first-hit cold latency while the server finishes the swap.
- **Flock overview cards** now derive totals and counts from active-batch SQL aggregates rather than collection filtering with eager-loaded death records.
- **Feed monthly trends** are grouped by month in SQL before flock-size enrichment, which reduces the amount of depleted feed history loaded into PHP.

### Navigation (HTMX)

- **`hx-boost="true"`** on `<body>` in `layouts/app.blade.php` — sidebar/navbar links and forms AJAX-swap the body instead of full-reloading. Eliminates re-parse of CSS/JS/fonts and Alpine re-init on every nav. See `frontend-architecture.md` for details.
- **No `hx-boost` on** logout and external links if they must full-reload (opt out with `hx-boost="false"`).

#### View transitions

`htmx.config.globalViewTransitions = true` (`resources/js/app.js`) wraps each boosted swap in the browser's View Transitions API. Because `hx-boost` swaps the whole `<body>`, the default `root` transition would crossfade the *entire* page — including the persistent chrome (`.sidebar`, `.navbar`, `.mobile-dock`), making them flicker on every navigation.

`resources/scss/_layout.scss` lifts the chrome into its own named snapshots (`view-transition-name: app-sidebar | app-navbar | app-dock`) and disables their old/new animations, so the chrome holds steady (active-link state flips instantly, no fade) while only the content area crossfades under `root` — tuned to a 200ms eased fade with no size-morph slide. The whole block is gated behind `@media (prefers-reduced-motion: no-preference)`.

#### Link prefetching (intent-based)

`window.ChickenCare.prefetch` in `resources/js/app.js` makes navigation feel near-instant by removing the server round-trip from the click→render path. On `pointerover` (hover), `touchstart` (tap-start), or `focusin` (keyboard) over a boosted `/app` link, it fetches that page in the background with `HX-Request`/`HX-Boosted` headers so the response lands in the browser HTTP cache; htmx's actual click request is then served from cache with no network hop.

This requires the boosted navigation response to be cacheable, so `SetDynamicResponseCacheHeaders` gives **boosted GET navigations** `Cache-Control: private, max-age=5` + `Vary: HX-Request`. Direct (non-boosted) page loads and non-boosted htmx partials stay `no-store`. The 5s window only needs to span hover→click (a few hundred ms); it's deliberately short to keep the stale-revisit window tiny. The JS dedup TTL (`TTL_MS = 4000`) sits just under it so an expired cache entry re-warms on the next intent.

Prefetch is connection-aware (skips on `save-data` and 2G via `navigator.connection`), deduped per path, and delegated on `document` so links added by boosted swaps are covered automatically. It pairs with the route warmup (`window.ChickenCare.routeWarmup`), which warms the *server* (opcache, compiled views, query plans) but not the browser cache. Covered by `tests/Feature/BoostedNavigationCacheTest.php`.

### Skeleton loading

Three coordinated layers, all sharing the `shimmer` keyframe (`transform: translateX(...)` only — never `left/top` — so the animation stays compositor-only and does not trigger layout/paint on every loading card).

#### 1. First-paint inline shell (cold-cache, per-route)

Lives in `resources/views/partials/fp-skeleton/{dashboard,eggs,account,flock,batches,crm,expenses,feed,savings,viability,default}.blade.php`, all wrapping a shared sidebar-and-navbar frame at `resources/views/components/fp-skeleton/frame.blade.php`. The layout (`layouts/app.blade.php:43-58`) picks the right variant for the current route via a `match` on `request()->route()?->getName()` and falls back to `default`. Inline `<style>` lives in `partials/first-paint-styles.blade.php` and ships with every page response (~4KB).

Each variant mirrors the *destination page's* structural shape — eggs gets a hero row + form + 4-card grid + table panel, dashboard gets a stat row + chart + financial row + activity panel, batches gets a single table panel, etc. So a cold-cache first paint on `/app/eggs` paints an eggs-shaped placeholder *before any JS or component CSS arrives*, not a generic shell.

The bundled stylesheet hides the FP markup via `.fp-skeleton { display: none; }` once parsed (CSS-only transition, no JS race). After `app.js` boots, the FP element is removed from the DOM so it doesn't linger as dead nodes (`resources/js/app.js`). Inline FP CSS includes shape variants for `__hero-row`, `__strip`, `__chip`, `__grid--three`, and panel sizes (`--chart`, `--hero-media`, `--hero-status`, `--form`, `--form-tall`, `--table`).

To add a new route variant: drop a new file at `partials/fp-skeleton/<name>.blade.php` using `<x-fp-skeleton.frame>`, then add a `str_starts_with($fpRoute, 'app.<name>') => '<name>'` arm to the layout's `match`.

#### 2. Component-level loading state (post-boot, structural fidelity)

Visual components accept a `loading` prop and, when `true`, render the same wrapper (padding, radius, grid placement) with content slots replaced by `<x-ui.skel block="…" />` shimmer blocks (`resources/views/components/ui/skel.blade.php`). Components currently wired: `<x-ui.stat-card>`, `<x-ui.comparison-card>`, `<x-ui.progress-card>`, `<x-forms.input>`, `<x-forms.submit-button>`. Each page's `index.blade.php` threads a `$skel` flag through partials and passes `:loading="$skel"` to its components — the page Blade itself stays the only source of truth for layout. Edit the real card → its loading branch updates with it.

#### 3. Runtime skeleton on hx-boost navigations

Wired through HTMX events in `resources/js/app.js` (`window.ChickenCare.skeletons`). On `htmx:beforeRequest`:

1. If the destination path is in `SKELETON_ROUTES` (`/app`, `/app/eggs`, `/app/account`), look up the matching skeleton URL and check `sessionStorage` for cached HTML.
2. If cached, mount it into the target's `.skeleton-loader-host` overlay immediately.
3. If not cached, mount the generic `<template id="skeleton-template-page-shell">` (inlined in `layouts/app.blade.php`) as a fallback, and kick off a fetch in the background to warm `sessionStorage` for the next nav.
4. After page boot, `requestIdleCallback` prewarms all skeleton URLs so the first nav already hits the cache.

##### `SHOW_DELAY_MS` deferral (boosted navigations only)

Boosted page navigations don't render the skeleton immediately — they defer it past `SHOW_DELAY_MS` (`resources/js/app.js`). If the swap resolves first (warm/fast page), the timer is cancelled and the page swaps directly via the view transition with no placeholder flash. Only genuinely slow navigations reveal a skeleton. Inline partial requests (forms, pagination) skip the deferral and show their skeleton at once.

Tuning history and the trade-off:

- **180ms → 400ms**: raised so typical production navigations (PHP ~130–160ms + ~40ms network ≈ 170–210ms total) swap cleanly without a skeleton flash on desktop.
- **400ms → 200ms** (current): lowered after profiling on a mid-range phone (Slow 4G + 4× CPU), where a boosted nav takes **~380ms** (≈240ms fetch + ≈135ms swap/settle). At 400ms the skeleton never fired, leaving the old page frozen with **no feedback** for the full ~380ms — the perceived "click-to-render delay" on mobile. 200ms is the balance point: fast desktop navs (~170ms) finish before the timer fires so there's no skeleton flash, while slow mobile navs still reveal the skeleton for feedback.

The skeleton URLs (`/app/__skeleton`, `/app/eggs/__skeleton`, `/app/account/__skeleton`) are real Laravel routes that render the page Blade with `$skel = true` and zeroed data — no DB queries, no `Cache::rememberForever` (Blade view caching in `storage/framework/views/` already makes each render <10ms). `Cache-Control: private, max-age=300` lets the browser cache the response for 5 min within a session. To add a new page: drop a `skeleton()` controller method, add the route inside the `auth` middleware group, and add the path → URL pair to `SKELETON_ROUTES` in `app.js`.

#### Cross-cutting

- **Layout consistency:** the runtime `page-shell` grid promotes `--stats` to 4 columns at `$breakpoint-tablet` (1024px) so it matches the real dashboard layout the skeleton is masking — no row-count flip on swap. Per-element `::after` shimmers on `__card`, `__panel`, `__block`, and `__line` mirror the FP skeleton's tile-level animation.
- **Flow positioning:** `.skeleton-loader-host` is `position: relative` and `.is-loading-skeleton > :not([data-skeleton-host])` uses `display: none`, so the skeleton's own variant `min-height` (36rem page-shell, 28rem tab variants) drives the loading area instead of inheriting the previous page's height.
- **Accessibility:** every skeleton element is `aria-hidden="true"`; containers get `aria-busy="true"` for the request lifetime; both clear on swap. `prefers-reduced-motion: reduce` neutralizes every shimmer (mixin + skel helper + FP rules).
- **Shared mixin:** `card-loading` in `resources/scss/_mixins.scss` is the canonical shimmer source, also reused by `*--loading` card variants in `_cards.scss`. Keep the keyframe transform-based to avoid regressing the dozens of in-app loading cards that depend on it.

### PHP / Opcache (local + production)

Opcache must be enabled for acceptable TTFB. Without it, every request recompiles every PHP file from source.

In `C:\php83\php.ini` (local Windows) or equivalent:

```ini
zend_extension=opcache
opcache.enable=1
opcache.enable_cli=1
opcache.memory_consumption=256
opcache.interned_strings_buffer=16
opcache.max_accelerated_files=20000
opcache.validate_timestamps=1
opcache.revalidate_freq=2
```

- **`validate_timestamps=1` + `revalidate_freq=2`** is the right tradeoff for local dev: PHP still picks up code edits within 2 seconds, but skips the filesystem stat on ~99% of requests.
- **Production:** set `opcache.validate_timestamps=0` and `opcache.revalidate_freq=0`; run `php artisan opcache:clear` (or restart PHP-FPM) on deploy.
- **`opcache.max_accelerated_files=20000`** — Laravel has ~5–8k PHP files; the default 10k is tight once vendor autoloading warms up.

### Session and cache drivers

Database-backed session/cache add two DB round trips per request (a session SELECT + UPDATE on every request, plus any cache reads). This is especially costly in production, where the database is **remote** (`DB_HOST=db2.paukhost.com`), so each of those round trips is a network hop to a separate host rather than a loopback call.

**Local `.env`:**

```env
SESSION_DRIVER=file
CACHE_STORE=file
```

File-backed stores avoid the DB round trips entirely. On Windows + MariaDB over loopback the database driver costs a measurable 50–150 ms per nav.

**Production `.env`** (Plesk, phpredis extension available, local Redis on `127.0.0.1:6379`):

```env
REDIS_CLIENT=phpredis
SESSION_DRIVER=redis
CACHE_STORE=redis
```

Redis replaces the remote-MySQL session round trips with local in-memory hits. Measured `Server-Timing` PHP wall time before/after the switch:

| Page | DB sessions | Redis | Δ |
|------|-------------|-------|---|
| `/app` | 163 ms | 137 ms | −16% |
| `/app/eggs` | 136 ms | 123 ms | −10% |
| `/app/crm` | 129 ms | 136 ms | noise |

The remaining ~130 ms is dominated by each page's own queries against the remote MySQL host — Redis only removed the session/cache round trips. The next lever (only worthwhile now that Redis is local) is `Cache::remember()`-wrapping the expensive dashboard aggregates per user with a short TTL to collapse ~15 remote queries into one local Redis hit.

> Switching the session driver invalidates existing sessions once (everyone re-logs in). If a shared Redis instance evicts keys (`allkeys-lru`) and causes random logouts, keep `SESSION_DRIVER=file` (still avoids the DB hop) and use Redis only for `CACHE_STORE`.

Avoid cache-tag-based invalidation for CRM/report payloads. Versioned cache keys are the portable invalidation strategy that works across `file`, `database`, and `redis` stores.

### Measuring server time

- **`ServerTiming` middleware** (`app/Http/Middleware/ServerTiming.php`, prepended to the `web` group in `bootstrap/app.php`) stamps every web response with `Server-Timing: app;desc="PHP wall time";dur=<ms>`, measured from `LARAVEL_START`. Read it in the browser (or via `fetch().headers.get('server-timing')`) to separate server processing from network latency — TTFB minus `app;dur` is the network component.
- **`php artisan redis:check`** (`app/Console/Commands/RedisCheck.php`) probes Redis availability: whether the `phpredis` extension is loaded, whether the `predis` package is installed, and whether it can connect + PING a server (`--host`/`--port` override the `127.0.0.1:6379` default). On success it prints the `.env` lines to enable Redis.

### Route warmup

`app/Console/Commands/WarmupRoutes.php` (`php artisan app:warmup-routes`) hits the hot routes over HTTP so opcache, compiled views, and MariaDB query plans stay primed. Scheduled in `routes/console.php` every 5 minutes with `withoutOverlapping()` + `runInBackground()`. In local dev, trigger the scheduler via `php artisan schedule:work` in a separate terminal, or Windows Task Scheduler running `php artisan schedule:run` every minute.

### Config / route / view cache

Run once when stable (and after deploy):

```bash
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

During active development, prefer leaving config/route cache off (they mask `.env` and route changes) but keep `view:cache` on.

---
