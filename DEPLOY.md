# Deployment Guide

Production: **https://koke.svetpiva.rs** (Plesk shared host, PaukHost)

Server path: `/var/www/vhosts/budipobednik.rs/koke.svetpiva.rs/`
PHP CLI: `/opt/plesk/php/8.3/bin/php`

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
/opt/plesk/php/8.3/bin/php artisan config:cache
/opt/plesk/php/8.3/bin/php artisan route:cache
/opt/plesk/php/8.3/bin/php artisan view:cache
/opt/plesk/php/8.3/bin/php artisan migrate --force
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
    * * * * * /opt/plesk/php/8.3/bin/php /var/www/vhosts/budipobednik.rs/koke.svetpiva.rs/artisan schedule:run >> /dev/null 2>&1
    ```

---

## Troubleshooting

- **500 on any page** → check `storage/logs/laravel.log`
- **"Vite manifest not found"** → rebuild and upload `public/build/`
- **"php version 8.1 does not satisfy"** on composer → Toolkit is using wrong CLI version; select PHP 8.3 in the composer UI
- **"Nothing to migrate"** but app breaks → DB schema may be partial; check tables exist for sessions/cache/jobs (required by `.env` drivers)
- **"could not read Username" on Git pull** → repo is private or URL typo; use public HTTPS URL or SSH deploy key
- **Permissions errors writing logs/cache** → `storage/` and `bootstrap/cache/` need 755 or 775, owned by Plesk system user
