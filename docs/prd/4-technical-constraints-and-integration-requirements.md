# 4. Technical Constraints and Integration Requirements

## 4.1 Existing Technology Stack

| Category | Technology | Version |
|----------|-----------|---------|
| Language | PHP | 8.3 (pinned) |
| Framework | Laravel | 12.x |
| Auth | Laravel Breeze (Blade) | latest |
| Frontend | HTMX + Alpine.js + Chart.js | 2.0.x / 3.x / 4.x |
| Styling | Pure SCSS (no framework) | latest |
| Build | Vite | 6.x |
| Database | MariaDB | 10.6.22 (Docker) |
| ORM | Eloquent | (Laravel built-in) |
| Package Mgr | pnpm (frontend), Composer (backend) | latest |
| Testing | PHPUnit 11.x + Laravel HTTP Tests + Dusk (optional) | latest |
| Dev Tools | Laravel Telescope, Laravel Boost MCP | latest |

## 4.2 Integration Approach

**Database Integration Strategy:** Fresh MariaDB schema via Laravel migrations (14 migration files). No data migration from Supabase PostgreSQL — this is a clean rebuild. Schema is designed to match existing data models 1:1. Docker container `chickencare-db` runs MariaDB locally.

**API Integration Strategy:** No JSON API — controllers return full Blade views or HTML partials based on `HX-Request` header detection via `DetectHtmx` middleware. The `HandlesHtmx` trait provides dual-response helpers for all mutating actions.

**Frontend Integration Strategy:** Blade templates replace React components. HTMX handles dynamic interactivity (inline CRUD, tab switching, modals, delete confirmations). Alpine.js handles micro-state (dropdowns, toggles). Chart.js replaces Recharts for dashboard visualizations. All three libraries loaded via `resources/js/app.js` entry point compiled by Vite.

**Testing Integration Strategy:** PHPUnit with `RefreshDatabase` trait. Unit tests (~40%) cover models, services, policies. Feature tests (~50%) cover HTTP requests including both HTMX and standard paths, cross-user access denial. Optional Dusk browser tests (~10%). Target: 70% minimum coverage.

## 4.3 Code Organization and Standards

**File Structure:** Standard Laravel conventions — `app/Http/Controllers/`, `app/Models/`, `app/Policies/`, `app/Services/`. ~60 Blade views organized by feature domain. ~25 SCSS files split between `components/` and `features/`.

**Naming Conventions:**

| Element | Convention | Example |
|---------|-----------|---------|
| Controllers | PascalCase, singular | `EggEntryController` |
| Models | PascalCase, singular | `FlockBatch` |
| DB tables | snake_case, plural | `flock_batches` |
| Form Requests | Store/Update + Model | `StoreFlockBatchRequest` |
| Policies | Model + Policy | `FlockBatchPolicy` |
| Services | Domain + Service | `DashboardService` |
| Blade views | kebab-case | `entry-row.blade.php` |
| Blade components | dot-namespaced | `<x-forms.date-input>` |
| Routes | kebab-case, RESTful | `/app/flock-batches/{batch}` |
| Route names | dot-separated | `app.batches.store` |
| SCSS files | `_` prefix, kebab-case | `_stat-card.scss` |
| SCSS classes | BEM | `.stat-card__value--positive` |
| Test methods | snake_case with test_ | `test_user_can_view_their_eggs()` |

**Coding Standards:**

1. Ownership scoping: every query starts from `$request->user()->relationship()`
2. Validation only in Form Requests — never `$request->validate()` in controllers
3. No raw HTML output — always `{{ }}`, never `{!! !!}` for user content
4. Blade components extracted if used more than once
5. Models are thin: `$fillable`, `$casts`, relationships, scopes only
6. Policies for authorization — never check ownership in controllers
7. No Eloquent in Blade — all data passed from controllers
8. HTMX dual responses: every mutating action handles both HTMX and standard requests

## 4.4 Deployment and Operations

**Build Process:** `pnpm build` compiles SCSS + JS via Vite. `php artisan optimize` caches config, routes, views (if production build ever needed).

**Deployment Strategy:** Local only — `php artisan serve` at `127.0.0.1:8000`. No CI/CD, no staging, no production pipeline. Docker Compose manages MariaDB container.

**Monitoring and Logging:** Laravel Telescope for local request inspection, query debugging, and mail preview. Standard file-based Laravel logging. No external monitoring services.

**Configuration Management:** `.env` file for database credentials and app settings. `docker-compose.yml` for MariaDB container configuration.

## 4.5 Risk Assessment and Mitigation

**Technical Risks:**

- MariaDB 10.6.22 vs PostgreSQL differences — some Supabase queries may use Postgres-specific features (JSON operators, array types) that need MariaDB equivalents
- HTMX learning curve — dual-response pattern (full page vs partial) adds complexity to every controller action
- SCSS from scratch — no utility framework means more CSS to write and maintain

**Integration Risks:**

- Feature parity gaps — original app may have implicit behaviors (Supabase triggers, RLS cascades, Netlify function side effects) not captured in architecture docs
- Chart.js vs Recharts — dashboard visualizations may look/behave differently, requiring manual alignment

**Deployment Risks:**

- Minimal — local-only deployment has near-zero deployment risk
- Docker dependency — requires Docker Desktop running for MariaDB

**Mitigation Strategies:**

- Build incrementally by epic/story — verify each feature against the original app before proceeding
- Use Laravel Boost MCP server for AI-assisted development to maintain consistency
- Run PHPUnit tests after each story to catch regressions early
- Cross-reference original React components when building Blade equivalents

---
