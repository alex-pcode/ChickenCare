# AC7 Verification: Static Asset Browser Caching

**Story:** 7.1 - Static Asset Browser Caching
**Verification Date:** 2026-04-26
**Status:** Pending - Must be run against Apache server (not `php artisan serve`)

---

## Purpose

Validate that the `.htaccess` cache rules emit correct `Cache-Control` headers for all asset types in a built environment.

## Prerequisites

- Server must be Apache (not `php artisan serve` Vite dev server)
- Assets must be built (`pnpm run build` completed)
- Server must have `mod_headers` and `mod_rewrite` enabled

---

## Verification Matrix

Run each curl command on the Apache server and verify the `Cache-Control` header matches the expected value.

### 1. Hashed Build Assets (Immutable - 1 year)

| Asset Type | Path | Command | Expected Cache-Control | Status |
|-----------|------|---------|------------------------|--------|
| JS | `/build/assets/app-B-tKz2zZ.js` | `curl -I https://yourdomain.com/build/assets/app-B-tKz2zZ.js 2>&1 \| grep -i cache-control` | `public, max-age=31536000, immutable` | ⬜ |
| CSS | `/build/assets/app-CDqOjx9x.css` | `curl -I https://yourdomain.com/build/assets/app-CDqOjx9x.css 2>&1 \| grep -i cache-control` | `public, max-age=31536000, immutable` | ⬜ |
| Font | `/build/assets/fraunces-400-MH2cmx7r.woff2` | `curl -I https://yourdomain.com/build/assets/fraunces-400-MH2cmx7r.woff2 2>&1 \| grep -i cache-control` | `public, max-age=31536000, immutable` | ⬜ |

### 2. Unhashed Public Media (Conservative - 1 hour, must-revalidate)

| Asset Type | Path | Command | Expected Cache-Control | Status |
|-----------|------|---------|------------------------|--------|
| Image | `/images/chicken-coin.webp` | `curl -I https://yourdomain.com/images/chicken-coin.webp 2>&1 \| grep -i cache-control` | `public, max-age=3600, must-revalidate` | ⬜ |
| Screenshot | `/screenshots/crm desktop.webp` | `curl -I https://yourdomain.com/screenshots/crm%20desktop.webp 2>&1 \| grep -i cache-control` | `public, max-age=3600, must-revalidate` | ⬜ |
| Font | `/fonts/` (any file) | `curl -I https://yourdomain.com/fonts/[filename].woff2 2>&1 \| grep -i cache-control` | `public, max-age=3600, must-revalidate` | ⬜ |

### 3. Root Public Files (Very Conservative - 5 minutes, must-revalidate)

| Asset Type | Path | Command | Expected Cache-Control | Status |
|-----------|------|---------|------------------------|--------|
| Favicon | `/favicon.ico` | `curl -I https://yourdomain.com/favicon.ico 2>&1 \| grep -i cache-control` | `public, max-age=300, must-revalidate` | ⬜ |
| Robots | `/robots.txt` | `curl -I https://yourdomain.com/robots.txt 2>&1 \| grep -i cache-control` | `public, max-age=300, must-revalidate` | ⬜ |

### 4. Negative Control: NO Immutable on Unhashed Files (AC4)

| Path | Command | Must NOT Contain | Status |
|------|---------|-----------------|--------|
| `/build/manifest.json` | `curl -I https://yourdomain.com/build/manifest.json 2>&1 \| grep -i cache-control` | `immutable` | ⬜ |
| `/sw.js` | `curl -I https://yourdomain.com/sw.js 2>&1 \| grep -i cache-control` | `immutable` | ⬜ |

---

## Quick One-Liner Test (All Assets)

```bash
# Run all tests in one go (replace YOUR_DOMAIN)
curl -I https://YOUR_DOMAIN/build/assets/app-B-tKz2zZ.js 2>&1 | grep -i cache-control
curl -I https://YOUR_DOMAIN/build/assets/app-CDqOjx9x.css 2>&1 | grep -i cache-control
curl -I https://YOUR_DOMAIN/build/assets/fraunces-400-MH2cmx7r.woff2 2>&1 | grep -i cache-control
curl -I https://YOUR_DOMAIN/images/chicken-coin.webp 2>&1 | grep -i cache-control
curl -I https://YOUR_DOMAIN/screenshots/crm%20desktop.webp 2>&1 | grep -i cache-control
curl -I https://YOUR_DOMAIN/favicon.ico 2>&1 | grep -i cache-control
curl -I https://YOUR_DOMAIN/robots.txt 2>&1 | grep -i cache-control
curl -I https://YOUR_DOMAIN/build/manifest.json 2>&1 | grep -i cache-control
```

---

## Acceptance Criteria Checklist (AC7)

- [ ] At least one hashed JS file returns `Cache-Control: public, max-age=31536000, immutable`
- [ ] At least one hashed CSS file returns `Cache-Control: public, max-age=31536000, immutable`
- [ ] At least one Vite-emitted font returns `Cache-Control: public, max-age=31536000, immutable`
- [ ] At least one `/images/*` file returns `Cache-Control: public, max-age=3600, must-revalidate`
- [ ] At least one `/screenshots/*` file returns `Cache-Control: public, max-age=3600, must-revalidate`
- [ ] `/favicon.ico` returns `Cache-Control: public, max-age=300, must-revalidate`
- [ ] `/robots.txt` returns `Cache-Control: public, max-age=300, must-revalidate`
- [ ] At least one unhashed file (e.g., `/build/manifest.json`) does NOT return `immutable`

---

## Notes

- **This verification CANNOT be run against `php artisan serve`** - the Vite dev server does not honor `.htaccess` rules.
- Run on staging or production Apache server with `.htaccess` enabled.
- Update the Status column with ✅ (pass) or ❌ (fail) after running each test.
- Once all checks pass, copy results to story file Completion Notes.
