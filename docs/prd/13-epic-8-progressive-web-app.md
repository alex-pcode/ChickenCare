# 13. Epic 8: Progressive Web App

## Status

In Progress — implementation complete locally; pending production deploy + live-host re-verification.

## Goal

Ship ChickenCare as an installable PWA: add to home screen, launch fullscreen, read recently-seen pages offline, and queue create-form submissions while offline with automatic sync on reconnect.

## Integration Constraints

- Additive layer on top of the existing Laravel + Blade + HTMX + Alpine stack — no SPA rewrite, no new create-API surface.
- `public/sw.js` served from site root with scope `/`; `public/manifest.webmanifest` and `public/images/pwa/*` served as first-party static files via Apache/Plesk.
- Honors the Epic 7 cache contract: immutable for `/build/assets/*`, `must-revalidate` for unhashed public media, `private, no-store` for dynamic HTML/HTMX/JSON.
- Offline writes use Background Sync against the same CSRF-protected Laravel routes the online flow already uses.

## Stories and Status

| # | Title | Status | Gate |
|---|-------|--------|------|
| 8.1 | Web App Manifest and Install Experience | Ready for Done | PASS |
| 8.2 | Service Worker Registration and Install Prompt Handling | Ready for Done | PASS |
| 8.3 | Offline Read Support via Service Worker Caching | Ready for Done | PASS |
| 8.4 | Offline Write Queue with Background Sync | Ready for Done | PASS |
| 8.5 | Plesk/Apache Deployment Configuration for PWA | Ready for Done | PASS (operator deploy pending) |
| 8.6 | PWA Tests and Lighthouse Verification | Ready for Done | PASS (live-host Lighthouse pending) |

## Story Dependencies

```
8.1 → 8.2 → 8.3 → 8.4
              ↘    ↘
               8.5 → 8.6
```

- **8.1** is a hard prerequisite for 8.2, 8.5, and 8.6.
- **8.2** is a hard prerequisite for 8.3, 8.4, 8.5, and 8.6.
- **8.3** is a hard prerequisite for 8.6 and a recommended predecessor for 8.4 (it defines the HTMX-safe offline fallback pattern 8.4 reuses).
- **8.4** and **8.5** are hard prerequisites for 8.6.

## Shared Contracts

**A. PWA identity (8.1).** `manifest.webmanifest`, `start_url=/app`, `scope=/`, icons under `/images/pwa/`, colors sourced from documented SCSS tokens (`docs/architecture/pwa.md`).

**B. Service worker lifecycle (8.2).** `/sw.js` at root, explicit `SW_VERSION`, `chickencare-sw-v<version>` cache namespace, wait-for-reload update model with user-visible banner, deferred install prompt.

**C. Offline read matrix (8.3).**
- Full-page authenticated HTML → network-first (2.5s timeout) → per-URL last-seen → `/offline`.
- HTMX fragments (`HX-Request: true`, not boosted) → network-first → minimal inline offline fragment, never full HTML.
- Authenticated JSON → always online-only.
- `/build/assets/*` → cache-first.

**D. Offline write boundary (8.4).** Allowlist limited to four create flows: eggs, batch deaths, feed, expenses. IndexedDB-backed queue, replay via Background Sync (with `online`/`visibilitychange` fallback), CSRF refresh via `/csrf-token`, lock-based dedupe, single 419 retry, failed bucket surfaced in Account → Offline sync review. Edits, deletes, sales, CRM, settings, auth, JSON endpoints, and uploads stay online-only.

**E. Production hosting (8.5).** Apache owns static delivery via `public/.htaccess`: rewrite carve-outs for `/sw.js` and `/manifest.webmanifest` before the front-controller fallback, scoped `<Files "sw.js">` no-cache, MIME types for `.webmanifest` and `.js`, immutable policy preserved exclusively for `/build/assets/*`. Root hosting required; subdirectory deployments out of scope. `Service-Worker-Allowed` documented as a future-only escape hatch.

**F. Verification (8.6).** `php artisan test --filter=Pwa` (PHPUnit) and `node --test resources/js/offline-queue.test.js` are the narrow regression entry points. Lighthouse evidence saved under `public/screenshots/pwa-lighthouse/`. Release checklist and rollback playbook live in `DEPLOY.md`.

## Acceptance Criteria — Epic Level

1. Installable from a supported mobile browser, launches `/app` in standalone mode.
2. Service worker registers only in secure contexts, updates via wait-for-reload banner, never disrupts HTMX or CSRF.
3. Previously visited authenticated pages remain readable offline; never-visited routes render `/offline`; HTMX fragment offline returns inline notice.
4. Allowlisted create submissions queue offline and replay safely with deduplication and 419 retry; failed items surface in Account.
5. Plesk/Apache serves `/sw.js`, `/manifest.webmanifest`, and PWA icons with the right MIME types and cache headers; root scope verified.
6. Automated tests, Lighthouse evidence, and rollback note exist for the PWA layer.

## Risk Mitigation

- **Worker too broad too early** → sequenced 8.2 lifecycle before any caching or sync logic.
- **Offline write duplication / silent loss** → narrow allowlist, original-route replay, queue locking, explicit 419 retry, visible failed bucket.
- **Local pass / production fail** → `.htaccess` rules and DEPLOY.md `curl -I` checklist make production behavior explicit, not assumed.
- **Rollback** → unregister SW client-side and remove manifest links from layouts; documented in `DEPLOY.md` under `## PWA rollback`. Base Laravel app behavior remains intact.

## Outstanding Operator Tasks

The local implementation is complete and verified. The remaining work is deployment-side:

1. Deploy `public/sw.js`, `public/manifest.webmanifest`, `public/images/pwa/*`, and any updated public fonts to the Plesk host.
2. Run the `curl -I` checklist in `DEPLOY.md → ## PWA verification checklist` and replace the recorded pre-deploy 404 snapshot with live-origin headers.
3. Re-run Lighthouse against `https://koke.svetpiva.rs` post-deploy and update saved artifacts under `public/screenshots/pwa-lighthouse/`.
4. Verify Add to Home Screen on Android Chrome and iOS Safari from the live origin (closes manual ACs in 8.1 and the live-origin gates in 8.5/8.6).

## Story Detail

The full acceptance criteria for each story live in their own files under `docs/stories/8.*.story.md`. The summaries above and the shared contracts in this document are the canonical epic-level reference; consult the story files for AC text, tasks, and QA history.
