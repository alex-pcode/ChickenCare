# Progressive Web App (PWA)

## Overview

ChickenCare implements PWA capabilities to provide a native app-like experience on mobile devices. The PWA implementation is built in stages across Epic 8 stories.

## Install Experience (Story 8.1)

### Web App Manifest

The `public/manifest.webmanifest` file defines the install metadata for ChickenCare:

| Property | Value | Source |
|----------|-------|--------|
| `name` | `ChickenCare` | Brand name |
| `short_name` | `ChickenCare` | Brand name |
| `description` | ChickenCare helps poultry keepers track eggs, feed, flock health, and expenses from one dashboard. | App description |
| `start_url` | `/app` | Authenticated dashboard route |
| `scope` | `/` | Root scope for future PWA features |
| `display` | `standalone` | Fullscreen without browser chrome |
| `background_color` | `#fafafa` | `$color-neutral-50` from `resources/scss/_variables.scss:22` |
| `theme_color` | `#4a7c59` | `$color-primary` from `resources/scss/_variables.scss:2` |

### Color Token Contract (Critical)

When updating the visual theme, update these values in the manifest:

- **`theme_color`**: Must match `$color-primary` in `resources/scss/_variables.scss`
  - Current value: `#4a7c59` (Farm green)
  - This color appears in the browser address bar and as the splash screen background

- **`background_color`**: Must match `$color-neutral-50` in `resources/scss/_variables.scss`
  - Current value: `#fafafa` (Light neutral)
  - This color appears during app launch before the app shell loads

### Icon Set

PWA icons are served from `public/images/pwa/` and follow Epic 7's unhashed public file contract:

| Icon | Size | Purpose | Path |
|------|------|---------|------|
| Maskable icon | 192x192 | `maskable` | `/images/pwa/icon-192-maskable.png` |
| Maskable icon | 512x512 | `maskable` | `/images/pwa/icon-512-maskable.png` |
| Standard icon | 512x512 | `any` | `/images/pwa/icon-512.png` |
| Apple touch icon | 180x180 | iOS | `/images/pwa/apple-touch-icon.png` |

### Blade Integration

The authenticated app layout (`resources/views/layouts/app.blade.php`) emits the PWA metadata:

```blade
<link rel="manifest" href="/manifest.webmanifest">
<meta name="theme-color" content="#4a7c59">
<link rel="apple-touch-icon" href="/images/pwa/apple-touch-icon.png">
```

These tags are placed before the first-paint styles and theme-cookie bootstrap script to ensure no rendering disruption.

## Service Worker (Story 8.2+)

The service worker at `public/sw.js` provides:
- Caching for offline functionality
- HTMX boost-aware request handling
- Update detection and user prompting
- CSRF token refresh endpoint

## Cache Contracts (Epic 7)

| Asset Type | Path | Cache Policy |
|------------|------|--------------|
| Manifest | `/manifest.webmanifest` | Conservative root-public-file (not immutable) |
| PWA Icons | `/images/pwa/*` | Unhashed media (not immutable) |
| Service Worker | `/sw.js` | Conservative root-public-file (not immutable) |

## Future Stories

- **8.2**: Service worker registration and install/update prompt handling
- **8.3**: Precaching and offline read behavior
- **8.5**: Production MIME types, rewrite rules, and cache hardening (Apache/Plesk)
- **8.6**: Feature tests and Lighthouse installability verification
