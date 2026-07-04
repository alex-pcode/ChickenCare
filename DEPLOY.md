# Deployment Guide

Production: **https://koke.svetpiva.rs** (Plesk shared host, PaukHost)

Server path: `/var/www/vhosts/budipobednik.rs/koke.svetpiva.rs/`
PHP CLI: `php` (Plesk resolves to 8.3; `/opt/plesk/php/8.3/bin/php` does not exist on PaukHost)

---

## Standard deploy (most changes)

```
git add .
git commit -m "..."
git push
```

Plesk auto-pulls from `main` (or click **Websites & Domains → koke.svetpiva.rs → Git → Pull Updates**).

Plesk's Additional Deploy Actions auto-run:

```
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan migrate --force
```

Done.

---

## Frontend changes (JS / Vue / CSS / Tailwind)

`public/build/` is gitignored, so rebuild locally and upload.

```
pnpm run build
```

Upload `public/build/` via Plesk File Manager to:
```
koke.svetpiva.rs/public/build/
```
Overwrite existing files.

---

## Combined changes (PHP + frontend in one feature)

Many features touch **both** PHP and the JS/SCSS bundle (e.g. a middleware change plus an `app.js` change). These have two independent deploy halves and the feature is **not live until both land**:

1. **PHP + committed assets** → reach the server only via the `main` git pull. This includes `app/`, `routes/`, `lang/`, Blade, and committed `public/` files such as `public/images/*`. **If your commits are on a feature branch, merge to `main` first** — Plesk pulls `main`, not the branch (this is the usual reason a "deployed" change doesn't show up).
2. **`public/build/`** (gitignored) → `pnpm run build` + manual File Manager upload.

After the `main` pull, confirm state with `php artisan about` (Environment, cached config/routes/views, drivers).

---

## Redis (production sessions + cache)

Production runs **sessions and cache on Redis**, not the database. The DB is remote (`DB_HOST=db2.paukhost.com`), so database-backed sessions cost a network round trip per request; Redis (`127.0.0.1:6379`, local) replaces those with in-memory hits.

Production `.env`:

```env
REDIS_CLIENT=phpredis
REDIS_HOST=127.0.0.1
REDIS_PORT=6379
REDIS_PASSWORD=null
SESSION_DRIVER=redis
CACHE_STORE=redis
```

- After editing `.env`, run `php artisan config:cache` (config is cached, so `.env` edits are otherwise ignored).
- `phpredis` is the PHP extension (enabled in Plesk PHP settings); no `predis` Composer package needed.
- Switching the session driver logs out existing sessions once (everyone re-logs in).
- If a shared Redis instance evicts keys (`allkeys-lru`) and causes random logouts, fall back to `SESSION_DRIVER=file` (still avoids the remote-DB hop) and keep Redis only for `CACHE_STORE`.

---

## Diagnostics

Run via the Laravel Toolkit (PHP 8.3):

- **`php artisan redis:check`** (`--host`/`--port` to override `127.0.0.1:6379`) — reports whether the `phpredis` extension and `predis` package are present and whether it can connect + PING Redis. On success it prints the `.env` lines to enable Redis.
- **`Server-Timing` header** — every web response carries `Server-Timing: app;desc="PHP wall time";dur=<ms>` (from the `ServerTiming` middleware). In the browser, `TTFB − app;dur` ≈ network latency, so you can tell server-processing time apart from network. Baseline after the Redis switch: PHP wall time ~120–140ms, dominated by the remote-MySQL page queries.

---

## Static asset cache headers

Static-file browser cache headers are owned by Apache via `public/.htaccess`, not by Laravel middleware.

- Hashed Vite assets under `/build/assets/*` use `Cache-Control: public, max-age=31536000, immutable`.
- Unhashed public media under `/images/*`, `/screenshots/*`, and `/fonts/*` use `Cache-Control: public, max-age=3600, must-revalidate`.
- Root unhashed files `favicon.ico` and `robots.txt` use `Cache-Control: public, max-age=300, must-revalidate`.

The immutable policy is only safe for content-hashed files. When Vite output changes, the filename changes too, so a one-year TTL does not risk serving stale assets after deploys.

The repo still contains duplicate font files under `public/fonts/` in addition to Vite-emitted hashed fonts under `/build/assets/*`. Keep the conservative `/fonts/*` rule in place until that duplicate public path is confirmed unused or removed.

**Dynamic page responses** are owned by `SetDynamicResponseCacheHeaders`, not Apache. They are `private, no-store` — **except boosted GET navigations** (htmx `HX-Boosted: true`), which get `private, max-age=5` + `Vary: HX-Request` so the intent-based link prefetch (`window.ChickenCare.prefetch` in `app.js`) can serve the next navigation from the browser cache. The 5s window only spans hover→click; direct (non-boosted) loads and non-boosted partials stay `no-store`. See `docs/architecture/security-and-performance.md` for the full prefetch design.

Validate `/build/assets/*` cache headers in a built environment that serves the real files through Apache/Plesk. Responses from the Vite dev server on `:5173` do not prove the production cache policy.

---

## PWA deployment constraints

ChickenCare's PWA layer assumes root hosting.

- Host the app at a domain or subdomain root such as `https://koke.svetpiva.rs/`.
- Do not deploy ChickenCare under a subdirectory path such as `/chickencare/`.
- `manifest.webmanifest` uses `start_url=/app` and `scope=/`, and `sw.js` is served from `/sw.js` with the default root scope.

Production is currently visible through an nginx front end on Plesk.

- `curl -I` on 2026-04-26 returned `Server: nginx` for the production host.
- Treat the deployment mode as `nginx -> Apache/PHP` unless Plesk is reconfigured.
- If nginx rules are customized in Plesk, they must preserve the `Cache-Control` header on `/sw.js` and must not strip a future `Service-Worker-Allowed` header.

`Service-Worker-Allowed` is a future-only escape hatch.

- Do not enable it by default.
- Only add it if a future deployment deliberately moves the worker away from `/sw.js` and still needs scope `/`.

Reference Apache snippet for that future-only case:

```apache
<Files "sw.js">
    Header set Service-Worker-Allowed "/"
</Files>
```

---

## PWA release checklist

Run these steps for every PWA-affecting release.

1. Update `public/sw.js` and bump `SW_VERSION` whenever service-worker behavior changes.
2. Run `C:\php83\php.exe artisan test --compact --filter=Pwa`.
3. Run `node --test resources/js/offline-queue.test.js`.
4. Run `pnpm run build` and upload the refreshed `public/build/` directory.
5. Deploy `public/sw.js`, `public/manifest.webmanifest`, `public/images/pwa/*`, and any updated public fonts.
6. Verify `/offline` responds on the deployed host before closing the release.

---

## PWA verification checklist

Run these checks after each deploy.

### Static delivery checks

```powershell
curl -I https://koke.svetpiva.rs/sw.js
curl -I https://koke.svetpiva.rs/manifest.webmanifest
curl -I https://koke.svetpiva.rs/images/pwa/apple-touch-icon.png
curl -I https://koke.svetpiva.rs/offline
```

Expected results:

- `/sw.js` returns `200`, `Content-Type: application/javascript`, and `Cache-Control: no-cache, no-store, must-revalidate`.
- `/manifest.webmanifest` returns `200` and `Content-Type: application/manifest+json`.
- `/images/pwa/apple-touch-icon.png` returns `200` and stays on the unhashed public-media cache policy.
- `/offline` returns `200` from Laravel and renders the controlled offline fallback page.

### Browser checks

Use Chrome DevTools or the Chrome DevTools CLI on a built environment served from the real app origin.

- Confirm `navigator.serviceWorker.getRegistration()` resolves with scope `/` and an activated worker.
- Confirm the manifest link resolves to `/manifest.webmanifest` with no scope or MIME warnings.
- Visit a previously loaded `/app/*` page offline and confirm the last-seen HTML renders instead of the browser's default offline error.
- Visit a never-before-loaded `/app/*` route offline and confirm the controlled offline page renders.
- Submit an allowlisted create form offline, confirm the UI resets immediately, then bring the browser back online and confirm the queue drains.
- Force a non-retryable replay failure and confirm the item appears under `Account Settings -> Offline sync review`.

### Current evidence

Local verification completed on 2026-04-26 against `http://127.0.0.1:8000`:

- Service worker state: controlled page, activated worker, scope `http://127.0.0.1:8000/`, manifest href `/manifest.webmanifest`.
- Offline read: a previously visited `/app/eggs` page reloaded offline from cache, and a never-visited `/app/import` route fell back to the controlled offline page.
- Offline write: an egg entry queued offline, replayed successfully on reconnect, and a duplicate egg entry moved into the account review bucket instead of disappearing.

Current production observation on 2026-04-26 before deployment:

- `https://koke.svetpiva.rs/sw.js` returned `404 Not Found` with `Server: nginx`.
- `https://koke.svetpiva.rs/manifest.webmanifest` returned `404 Not Found` with `Server: nginx`.
- `https://koke.svetpiva.rs/images/pwa/apple-touch-icon.png` returned `404 Not Found` with `Server: nginx`.

Treat those 404s as a deployment-gap signal and rerun the checklist immediately after the next production deploy.

---

## Lighthouse evidence

Saved local Lighthouse artifacts:

- `public/screenshots/pwa-lighthouse/report.html`
- `public/screenshots/pwa-lighthouse/report.json`

Captured on 2026-04-26 against `http://127.0.0.1:8000/app` in an authenticated Chrome DevTools CLI session after a production build.

- Accessibility: `96`
- Best Practices: `100`
- SEO: `91`
- Audit summary: `45 passed`, `3 failed`

The current Chrome DevTools CLI Lighthouse schema did not emit a dedicated PWA category in this run, so pair the saved report with the browser verification checklist above when reviewing installability, offline fallback, and service-worker scope.

---

## PWA rollback

If a production incident requires disabling the PWA layer quickly, use this rollback order:

1. Remove or comment the manifest and `apple-touch-icon` tags from `resources/views/layouts/app.blade.php` and `resources/views/layouts/guest.blade.php`.
2. Disable `window.ChickenCare.pwa.initialize()` in `resources/js/app.js` so new sessions stop registering the worker.
3. Deploy the updated app bundle and Blade templates.
4. In an affected browser session, run:

```js
navigator.serviceWorker.getRegistrations().then((registrations) => Promise.all(registrations.map((registration) => registration.unregister())));
```

5. Reload the page once to return to the non-PWA baseline.

This rollback leaves the core Laravel application intact while removing manifest wiring and service-worker behavior.

---

## New Composer package

After `git pull` on server, run via Laravel Toolkit UI (PHP 8.3):

```
composer install --no-dev --optimize-autoloader
```

---

## First-time setup reference

Already done — kept here for disaster recovery.

1. Create subdomain in Plesk, PHP 8.3 (FPM served by Apache)
2. Connect Git repo: `https://github.com/alex-pcode/ChickenCare.git`, deployment path = subdomain root (not `httpdocs/`)
3. Set Document Root to `koke.svetpiva.rs/public`
4. Install Let's Encrypt SSL covering `koke.svetpiva.rs`
5. Create `.env` manually (gitignored) — see `.env.example` as base, set `APP_URL=https://koke.svetpiva.rs`
6. Create DB `budipobednik_chickencare`, import schema from `chickencare.sql` if bootstrapping
7. In Laravel Toolkit, run `composer install --no-dev --optimize-autoloader` with PHP 8.3 selected (not server default 8.1)
8. Run `php artisan key:generate`, `storage:link`, `migrate --force`, `config:cache`, `route:cache`, `view:cache`
9. Build frontend locally: `pnpm run build`, upload `public/build/`
10. Add Plesk scheduler cron for Laravel scheduler:
    ```
    * * * * * php /var/www/vhosts/budipobednik.rs/koke.svetpiva.rs/artisan schedule:run >> /dev/null 2>&1
    ```

---

## Troubleshooting

- **500 on any page** → check `storage/logs/laravel.log`
- **"Vite manifest not found"** → rebuild and upload `public/build/`
- **"php version 8.1 does not satisfy"** on composer → Toolkit is using wrong CLI version; select PHP 8.3 in the composer UI
- **"Nothing to migrate"** but app breaks → DB schema may be partial; check tables exist for any DB-backed drivers in `.env`. Note production sessions/cache are on **Redis** (not the `sessions`/`cache` tables); only `QUEUE_CONNECTION=database` still needs the `jobs` table.
- **Random logouts after a deploy** → likely Redis key eviction on a shared instance; set `SESSION_DRIVER=file` and keep Redis for `CACHE_STORE` only (see Redis section).
- **A change "deployed" but isn't live** → commits are probably on a feature branch; Plesk pulls `main`. Merge to `main`. If it's a frontend change, also rebuild and upload `public/build/`.
- **"could not read Username" on Git pull** → repo is private or URL typo; use public HTTPS URL or SSH deploy key
- **Permissions errors writing logs/cache** → `storage/` and `bootstrap/cache/` need 755 or 775, owned by Plesk system user
