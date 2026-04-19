# Development Workflow

## Docker MariaDB

MariaDB 10.6.22 is run as a single-service container defined in `docker-compose.yml` at the repo root:

```yaml
services:
  db:
    image: mariadb:10.6.22
    container_name: chickencare-db
    environment:
      MYSQL_DATABASE: chickencare
      MYSQL_USER: chickencare
      MYSQL_PASSWORD: secret
      MYSQL_ROOT_PASSWORD: rootsecret
    ports:
      - "3306:3306"
    volumes:
      - chickencare_db_data:/var/lib/mysql

volumes:
  chickencare_db_data:
```

PHP and Node run on the host (not in Docker). The database port is exposed on `127.0.0.1:3306` so the host's PHP connects to it directly.

Lifecycle:

```bash
docker compose up -d     # start the DB
docker compose down      # stop the DB (data preserved in the named volume)
docker compose down -v   # stop and wipe the volume (destructive)
```

## Environment Configuration

Copy `.env.example` to `.env` on first setup. The DB credentials match the `docker-compose.yml` defaults:

```env
APP_URL=http://127.0.0.1:8000

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=chickencare
DB_USERNAME=chickencare
DB_PASSWORD=secret
```

For local dev, use file-backed session and cache (database-backed adds ~50–150 ms per request — see `security-and-performance.md`):

```env
SESSION_DRIVER=file
CACHE_STORE=file
```

`QUEUE_CONNECTION=database` is fine locally because `composer dev` runs `queue:listen` in a worker process.

## Prerequisites

- **PHP 8.3** (with `opcache` enabled — see `security-and-performance.md`)
- **Composer 2.x**
- **Node 20+** and **pnpm**
- **Docker Desktop** (for the MariaDB container)
- **Git Bash or equivalent** on Windows (the `composer dev` script uses `npx concurrently`)

## Initial Setup

```bash
git clone <repo> ChickenCare
cd ChickenCare
docker compose up -d
composer run setup   # runs composer install, copies .env, key:generate, migrate, pnpm install, pnpm run build
```

`composer run setup` is defined in `composer.json` and performs, in order:

1. `composer install`
2. copies `.env.example` → `.env` if missing
3. `php artisan key:generate`
4. `php artisan migrate --force`
5. `pnpm install`
6. `pnpm run build` (Vite production build — writes to `public/build/`)

After setup, seed with fixture data if needed:

```bash
php artisan migrate:fresh --seed
```

## Daily Commands

The canonical way to start all dev processes together:

```bash
composer run dev
```

This runs four processes concurrently via `npx concurrently`:

| Process | Command | Purpose |
|---|---|---|
| `server` | `php artisan serve` | PHP dev server on `http://127.0.0.1:8000` |
| `queue` | `php artisan queue:listen --tries=1 --timeout=0` | Queue worker for background jobs |
| `logs` | `php artisan pail --timeout=0` | Tails `storage/logs/laravel.log` |
| `vite` | `pnpm run dev` | Vite dev server on `[::1]:5173` for HMR |

Stop with `Ctrl+C` — `--kill-others` shuts all four down together.

Individually:

```bash
php artisan serve                 # PHP only
pnpm run dev                      # Vite only (for asset HMR)
pnpm run build                    # Production asset build
php artisan pail                  # Follow the log
php artisan tinker                # REPL
php artisan test --compact        # Run the test suite
vendor/bin/pint --dirty --format agent   # Format PHP changes
php artisan app:warmup-routes     # Pre-warm opcache + views for the hot routes
```

### After code changes

| Changed | Command |
|---|---|
| Config files (`config/*.php`) | `php artisan config:clear` (only if `config:cache` was ever run) |
| Routes | `php artisan route:clear` (same caveat) |
| Blade views | No action — opcache re-validates within 2s (see `security-and-performance.md`) |
| Frontend assets | Vite HMR auto-reloads if `pnpm run dev` is running; otherwise `pnpm run build` |
| Migrations | `php artisan migrate` (or `migrate:fresh --seed` to reset + reseed) |

### Troubleshooting

- **`Illuminate\Foundation\ViteException: Unable to locate file in Vite manifest`** — run `pnpm run build` (or keep `pnpm run dev` running).
- **Feature flag / DB change not picked up** — you likely ran `config:cache` in the past; run `php artisan config:clear`.
- **Stale Blade output** — opcache is revalidating every 2s, so rare; `php artisan view:clear` forces a recompile.
- **Port 8000 or 3306 in use** — kill the previous `php artisan serve` / stop the conflicting Docker service.
