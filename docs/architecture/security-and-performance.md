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
- **No client-side state hydration** — pages interactive immediately

### Navigation (HTMX)

- **`hx-boost="true"`** on `<body>` in `layouts/app.blade.php` — sidebar/navbar links and forms AJAX-swap the body instead of full-reloading. Eliminates re-parse of CSS/JS/fonts and Alpine re-init on every nav. See `frontend-architecture.md` for details.
- **No `hx-boost` on** logout and external links if they must full-reload (opt out with `hx-boost="false"`).

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

### Session and cache drivers (local)

Local `.env`:

```env
SESSION_DRIVER=file
CACHE_STORE=file
```

Database-backed session/cache add two DB round trips per request. On Windows + MariaDB over loopback that is measurable (50–150 ms per nav).

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
