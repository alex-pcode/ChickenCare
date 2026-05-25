# 12. Epic 7: Browser Cache Strategy

**Epic Goal:** Establish a repo-specific browser caching contract for ChickenCare so static assets load faster on repeat visits while dynamic HTML, HTMX fragments, JSON responses, and history restores avoid serving stale or misleading content.

**Integration Requirements:** The implementation must respect the current deployment model of Apache on Plesk, preserve the existing Laravel + HTMX request flow, keep static-file header ownership separate from Laravel response header ownership, and validate `/build/assets/*` behavior in a built environment rather than treating Vite dev-server responses as proof.

## Story 7.1: Static Asset Browser Caching

As a returning ChickenCare user,
I want static assets to use browser cache policies that match how the app ships files,
so that repeat visits are faster without serving stale public assets after deploys.

**Acceptance Criteria:**

1. Requests for hashed Vite assets under `/build/assets/*` return `Cache-Control: public, max-age=31536000, immutable`.
2. Unhashed media under `/images/*`, `/screenshots/*`, and duplicate `/fonts/*` return `Cache-Control: public, max-age=3600, must-revalidate`.
3. Root unhashed files such as `/favicon.ico` and `/robots.txt` return `Cache-Control: public, max-age=300, must-revalidate`.
4. No rule applies `immutable` or one-year caching to an unhashed path.
5. Static-file header ownership remains in Apache via `public/.htaccess`, not Laravel middleware.
6. Deployment notes explain why immutable caching is safe only for fingerprinted files and call out duplicate `public/fonts/` as a cleanup or validation follow-up.
7. Validation uses a built environment that serves real `/build/assets/*` files rather than only checking `pnpm run dev` responses from `:5173`.

**Integration Verification:**

- IV1: A built Vite JS or CSS asset under `/build/assets/*` returns the immutable policy.
- IV2: A direct public image or screenshot returns the conservative policy instead of immutable caching.
- IV3: `favicon.ico` and `robots.txt` return the short-TTL policy and deploy notes identify Apache/Plesk as the header owner.

## Story 7.2: Dynamic HTML, HTMX, and JSON Cache Headers

As an authenticated ChickenCare user,
I want dynamic responses to be explicitly non-storable,
so that HTMX navigation, middleware-generated fragments, and browser refreshes do not replay stale session-bound data.

**Acceptance Criteria:**

1. Authenticated full HTML responses, including boosted HTMX full-page responses, return `Cache-Control: private, no-store`.
2. Authenticated HTMX partials and middleware-generated fragments, including premium-gate and validation responses, return `Cache-Control: private, no-store`.
3. Authenticated JSON endpoints return `Cache-Control: private, no-store`.
4. Guest auth pages return `Cache-Control: private, no-store`.
5. Guest marketing pages are not made `public` or shared-cacheable while they still render through the current CSRF-bearing guest layout.
6. The policy is applied centrally through Laravel middleware rather than duplicated across controllers.
7. No `ETag` or `Last-Modified` behavior is introduced for the covered dynamic responses.
8. Focused feature tests verify representative guest HTML, authenticated HTML, boosted full-page HTML, HTMX partials, premium-gate fragments, validation errors, and JSON endpoints.

**Integration Verification:**

- IV1: A representative `/app` HTML page, a boosted HTMX full-page response, and an HTMX fragment all return `private, no-store`.
- IV2: A representative authenticated JSON endpoint and a guest auth page both return `private, no-store`.
- IV3: Middleware-generated premium-gate and HTMX validation responses inherit the same policy without controller-specific header code.

## Story 7.3: HTMX History Cache Review and Tuning Decision

As a ChickenCare user navigating with HTMX boost,
I want back and forward navigation to feel fast without restoring misleading stale views,
so that history behavior is understood and only tuned when real browser evidence supports it.

**Acceptance Criteria:**

1. The current baseline is documented: `resources/js/app.js` exposes HTMX but does not set explicit `historyCacheSize`, `refreshOnHistoryMiss`, or `getCacheBusterParam` overrides.
2. Browser review covers dashboard, expenses, account tab changes, CRM tab changes, at least one premium route, and one mutation followed by back navigation.
3. The review records for each scenario whether HTMX restored from history, issued a network request, showed a skeleton, and interacted with route warmup logic.
4. The final decision is one of exactly three paths: keep implicit defaults, codify current defaults, or increase `historyCacheSize`, with the reason and rollback note documented.
5. Any config change is gated on observed browser behavior after Stories 7.1 and 7.2 are complete.
6. The review explicitly records stale-content tolerance after create, update, or delete flows before recommending a larger history cache.

**Integration Verification:**

- IV1: Back and forward navigation behavior is recorded for dashboard, expenses, account, and CRM flows in a real authenticated browser session.
- IV2: At least one mutation-then-back scenario is reviewed to confirm whether history restore is acceptably fresh or confusingly stale.
- IV3: If a config change is made, the selected value is documented with the observed reason, and the same scenarios are rerun after the change.