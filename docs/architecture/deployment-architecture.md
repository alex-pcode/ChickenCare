# Deployment Architecture

**Local only** — no CI/CD, no staging, no production pipeline.

| Environment | URL | Database | Purpose |
|-------------|-----|----------|---------|
| Local Dev | `http://127.0.0.1:8000` | `chickencare-db` Docker container | Development and testing |

## Production Build (If Ever Needed)

```bash
pnpm build                          # Minified CSS/JS
php artisan optimize                 # Cache config, routes, views
```

---
