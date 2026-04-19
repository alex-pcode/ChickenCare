# ChickenCare Brownfield Enhancement PRD

## 1. Intro Project Analysis and Context

### 1.1 Existing Project Overview

#### Analysis Source

Architecture documentation (v1.2, sharded at `docs/architecture/`) — comprehensive and current as of 2026-04-08.

#### Current Project State

ChickenCare is a **poultry farm management application** currently running as a React 19 SPA with Supabase (PostgreSQL + Auth + RLS) and Netlify hosting at `d:\Koke\Aplikacija`. It provides egg tracking, flock/batch management, CRM, expenses, feed inventory, dashboard analytics, and free/premium tier gating. The rebuild targets **full feature parity** using Laravel 12 + Blade + HTMX + MariaDB as a local server-rendered monolith.

### 1.2 Available Documentation Analysis

- [x] Tech Stack Documentation
- [x] Source Tree / Architecture
- [x] Coding Standards
- [x] API Documentation (routes + HTMX specs)
- [x] External API Documentation
- [ ] UX/UI Guidelines — design is embedded in component specs and SCSS architecture
- [ ] Technical Debt Documentation — N/A (greenfield rebuild)

### 1.3 Enhancement Scope Definition

#### Enhancement Type

- [x] Technology Stack Upgrade (React/Supabase -> Laravel/HTMX)
- [x] UI/UX Overhaul (SPA -> server-rendered + HTMX)

#### Enhancement Description

Complete rebuild of the Chicken Manager application from a React 19 SPA with Supabase backend to a Laravel 12 monolith with Blade + HTMX, MariaDB, and pure SCSS styling. Goal is full feature parity for local development/testing use.

#### Impact Assessment

- [x] Major Impact (architectural changes required) — this is a complete rewrite, not a modification.

### 1.4 Goals and Background Context

#### Goals

- Full feature parity with the existing production React + Supabase app
- Local-first architecture — no cloud dependencies, runs entirely on `php artisan serve` + Docker MariaDB
- Server-rendered simplicity — eliminate client-side state management complexity
- HTMX-driven interactivity — SPA-like UX without a JavaScript framework
- Maintain free/premium tier gating model
- Clean, maintainable codebase optimized for AI-assisted development

#### Background Context

The existing Chicken Manager is a production app that works but is over-engineered for its use case — React 19 with complex state management, Supabase with RLS policies, and Netlify serverless functions add unnecessary infrastructure complexity. The rebuild simplifies the stack to Laravel's traditional MVC pattern, making it easier to understand, modify, and extend. This is a learning/testing rebuild — not a migration of production users.

### 1.5 Change Log

| Change | Date | Version | Description | Author |
|--------|------|---------|-------------|--------|
| Initial | 2026-04-08 | 1.0 | Brownfield Enhancement PRD created for Laravel rebuild | John (PM) |

---

## 2. Requirements

### 2.1 Functional Requirements

- **FR1:** Users can register, log in, reset passwords, and manage their account using Laravel Breeze authentication (email + password).
- **FR2:** Users can create, read, update, and delete daily egg entries with date, count, size, color, and notes — with inline HTMX form submission and table updates.
- **FR3:** Users can manage a single flock profile per account with farm name, location, flock size, breed breakdown (hens/roosters/chicks/brooding), and notes. *(Premium)*
- **FR4:** Users can create and manage flock events (acquisition, laying start, broody, hatching, other) on their flock profile timeline. *(Premium)*
- **FR5:** Users can create, view, update, and archive flock batches with breed, acquisition date, bird counts by type, lifecycle dates, source, and cost. *(Premium)*
- **FR6:** Users can log batch events (health check, vaccination, relocation, breeding, laying start, etc.) and death records (with cause tracking) per batch. *(Premium)*
- **FR7:** Users can create, update, and delete expenses categorized by type (feed, medical, equipment, housing, utilities, other) with date and amount. *(Premium)*
- **FR8:** Users can track feed inventory with name, quantity, unit (kg/lbs), purchase date, expiry date, and cost. *(Premium)*
- **FR9:** Users can manage customers (name, phone, notes, active/inactive status) as a simple CRM. *(Premium)*
- **FR10:** Users can record egg sales with dozen/individual counts, total amount, payment status, and optional customer association. *(Premium)*
- **FR11:** Users can view sales reports with aggregated revenue data. *(Premium)*
- **FR12:** The dashboard displays aggregated stats (egg totals, expenses, revenue, flock counts, recent events) with Chart.js visualizations.
- **FR13:** Free-tier users have access to egg tracking and the dashboard only; premium features are gated by `EnsurePremiumTier` middleware with a clear upgrade prompt.
- **FR14:** All list views support pagination (15-25 items per page).
- **FR15:** HTMX-powered inline create/edit forms, tab switching, modal dialogs, and delete confirmations provide SPA-like interactivity without full page reloads.
- **FR16:** Savings calculator and viability analysis views are available to premium users.

### 2.2 Non-Functional Requirements

- **NFR1:** Full page loads must complete in < 200ms; HTMX partial swaps in < 100ms; CRUD operations in < 50ms (local environment).
- **NFR2:** CSS bundle must be < 50KB gzipped; JS bundle (HTMX + Alpine + Chart.js) < 100KB gzipped.
- **NFR3:** No more than 10 database queries per page load — enforce eager loading to prevent N+1.
- **NFR4:** All user data must be isolated via Policy-based authorization (`$user->id === $model->user_id`) — no user can access another user's records.
- **NFR5:** WCAG AA accessibility compliance — ARIA attributes on all interactive components, semantic HTML, keyboard navigability.
- **NFR6:** Application must run entirely locally with zero cloud dependencies — `php artisan serve` + Docker MariaDB only.
- **NFR7:** Pure SCSS styling with no CSS framework — neumorphic design system using variables, mixins, and BEM conventions.

### 2.3 Compatibility Requirements

- **CR1: Existing App Feature Parity:** Every feature in the React + Supabase app at `d:\Koke\Aplikacija` must have a functional equivalent in the Laravel rebuild.
- **CR2: Database Schema Parity:** All data models (User, FlockProfile, EggEntry, Expense, FeedInventory, Customer, Sale, FlockBatch, DeathRecord, BatchEvent, FlockEvent) must be represented with equivalent fields and relationships in MariaDB.
- **CR3: UI/UX Consistency:** The rebuild must replicate the same user workflows and navigation patterns (sidebar, tabs, inline forms, modals) even though the rendering technology changes.
- **CR4: Tier Gating Parity:** Free vs premium feature access must match the existing app's tier boundaries exactly.

---

## 3. User Interface Enhancement Goals

### 3.1 Integration with Existing UI

The UI is a complete rewrite from React components to Blade + HTMX, but it deliberately mirrors the original app's component structure. The architecture defines a 1:1 mapping of 17 React components to Blade equivalents (e.g., `StatCard` -> `<x-ui.stat-card>`, `DataTable` -> `<x-tables.data-table>`). The design system shifts from Tailwind CSS to pure SCSS with neumorphic styling using BEM naming, custom variables, and mixins. No CSS framework is used.

Key UI patterns preserved from the original:

- **Sidebar navigation** with tier-based feature visibility
- **Inline HTMX forms** for CRUD (no separate create/edit pages)
- **Modal dialogs** for complex operations (loaded via HTMX)
- **Tab switching** via HTMX partial swaps with URL push state
- **Flash messages** with auto-dismiss
- **Empty states** with friendly messaging

### 3.2 Modified/New Screens and Views

| Screen | Type | Tier | Notes |
|--------|------|------|-------|
| Landing page (`/`) | Rebuild | Public | Marketing page + costs page |
| Login / Register / Password Reset | Rebuild | Public | Laravel Breeze scaffolding |
| Dashboard (`/app`) | Rebuild | Auth | Stat cards + Chart.js charts |
| Egg Tracking (`/app/eggs`) | Rebuild | Free | Inline HTMX CRUD table |
| Flock Profile (`/app/flock`) | Rebuild | Premium | Single profile + events timeline |
| Batch Management (`/app/batches`) | Rebuild | Premium | List + detail view with tabs (overview, events, deaths) |
| Expenses (`/app/expenses`) | Rebuild | Premium | Inline HTMX CRUD table |
| Feed Inventory (`/app/feed`) | Rebuild | Premium | Inline HTMX CRUD table |
| Customers (`/app/customers`) | Rebuild | Premium | CRUD with search |
| Sales (`/app/sales`) | Rebuild | Premium | CRUD + reports sub-page |
| Savings (`/app/savings`) | Rebuild | Premium | Read-only financial analysis |
| Viability (`/app/viability`) | Rebuild | Premium | Calculator page |
| Account Settings (`/app/account`) | Rebuild | Auth | Edit profile |
| Onboarding Wizard | Rebuild | Auth | Multi-step first-login flow |
| Premium Gate | New pattern | Free | HTMX-aware upgrade prompt (partial or redirect) |

### 3.3 UI Consistency Requirements

- All interactive components must include ARIA attributes (WCAG AA) — `aria-invalid`, `aria-describedby`, `aria-live`, `role="alert"` on errors
- Form inputs must restore state on validation failure via Laravel's `old()` helper
- HTMX swap transitions must use consistent CSS animations (fade, slide) defined in `_animations.scss`
- Tables must use the shared `<x-tables.data-table>` component with standardized column formatting
- Modals load into a single `#modal-container` with `aria-live="polite"`
- Delete operations always use `hx-confirm` browser dialogs before executing
- Sidebar highlights active route; hides premium features for free-tier users

---

## 4. Technical Constraints and Integration Requirements

### 4.1 Existing Technology Stack

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

### 4.2 Integration Approach

**Database Integration Strategy:** Fresh MariaDB schema via Laravel migrations (14 migration files). No data migration from Supabase PostgreSQL — this is a clean rebuild. Schema is designed to match existing data models 1:1. Docker container `chickencare-db` runs MariaDB locally.

**API Integration Strategy:** No JSON API — controllers return full Blade views or HTML partials based on `HX-Request` header detection via `DetectHtmx` middleware. The `HandlesHtmx` trait provides dual-response helpers for all mutating actions.

**Frontend Integration Strategy:** Blade templates replace React components. HTMX handles dynamic interactivity (inline CRUD, tab switching, modals, delete confirmations). Alpine.js handles micro-state (dropdowns, toggles). Chart.js replaces Recharts for dashboard visualizations. All three libraries loaded via `resources/js/app.js` entry point compiled by Vite.

**Testing Integration Strategy:** PHPUnit with `RefreshDatabase` trait. Unit tests (~40%) cover models, services, policies. Feature tests (~50%) cover HTTP requests including both HTMX and standard paths, cross-user access denial. Optional Dusk browser tests (~10%). Target: 70% minimum coverage.

### 4.3 Code Organization and Standards

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

### 4.4 Deployment and Operations

**Build Process:** `pnpm build` compiles SCSS + JS via Vite. `php artisan optimize` caches config, routes, views (if production build ever needed).

**Deployment Strategy:** Local only — `php artisan serve` at `127.0.0.1:8000`. No CI/CD, no staging, no production pipeline. Docker Compose manages MariaDB container.

**Monitoring and Logging:** Laravel Telescope for local request inspection, query debugging, and mail preview. Standard file-based Laravel logging. No external monitoring services.

**Configuration Management:** `.env` file for database credentials and app settings. `docker-compose.yml` for MariaDB container configuration.

### 4.5 Risk Assessment and Mitigation

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

## 5. Epic and Story Structure

### Epic Approach

**Epic Structure Decision:** Multi-epic (6 epics) — the rebuild has natural architectural layers and domain boundaries that map to independent, sequentially buildable epics. A single epic with 24 stories would be unwieldy for tracking and AI agent execution.

| # | Epic | Stories | Dependencies |
|---|------|---------|--------------|
| 1 | Project Foundation & Auth | 5 | None — must be first |
| 2 | Egg Tracking (Free Tier) | 3 | Epic 1 |
| 3 | Flock & Batch Management | 4 | Epic 1 |
| 4 | Financial Management | 5 | Epic 1 |
| 5 | Dashboard & Analytics | 4 | Epics 2-4 |
| 6 | Polish & Parity | 4 | Epics 1-5 |

Epics 3 and 4 can be developed in parallel since they share no dependencies beyond the foundation.

---

## 6. Epic 1: Project Foundation & Authentication

**Epic Goal:** Establish the Laravel 12 project scaffold, Docker MariaDB, authentication system, shared UI components, and SCSS design system — the foundation everything else builds on.

**Integration Requirements:** Must produce a running app at `127.0.0.1:8000` with working registration, login, and an authenticated shell layout before any feature work begins.

### Story 1.1: Laravel Project Initialization & Docker MariaDB Setup

As a developer,
I want the Laravel 12 project scaffolded with Docker MariaDB connected,
so that I have a working development environment to build on.

**Acceptance Criteria:**

1. Laravel 12 project created with PHP 8.3
2. `docker-compose.yml` configured with MariaDB 10.6.22 container (`chickencare-db`)
3. `.env` configured with MariaDB connection credentials
4. `php artisan migrate` runs successfully against Docker MariaDB
5. `pnpm install` installs frontend dependencies (HTMX, Alpine.js, Chart.js)
6. `vite.config.js` configured for SCSS + JS compilation
7. `php artisan serve` starts the app at `127.0.0.1:8000`

**Integration Verification:**

- IV1: `php artisan db:show` confirms MariaDB 10.6.22 connection
- IV2: Vite dev server compiles assets without errors
- IV3: Browser loads the default Laravel welcome page

### Story 1.2: Authentication with Laravel Breeze

As a user,
I want to register, log in, log out, and reset my password,
so that my farm data is private and secure.

**Acceptance Criteria:**

1. Laravel Breeze (Blade stack) installed and configured
2. Registration creates user with `tier: 'free'` and `is_admin: false` defaults
3. Login, logout, forgot password, and reset password flows work
4. User migration includes `tier` enum and `is_admin` boolean columns
5. Tailwind stripped out — Breeze views restyled with SCSS (basic functional styling)
6. Authenticated users redirect to `/app`; unauthenticated users redirect to `/login`

**Integration Verification:**

- IV1: Register -> login -> logout cycle works end to end
- IV2: Password reset email sends via Laravel mail (log driver)
- IV3: Accessing `/app` without auth redirects to `/login`

### Story 1.3: App Layout Shell & SCSS Design System Foundation

As a user,
I want a consistent app layout with sidebar navigation,
so that I can navigate the application intuitively.

**Acceptance Criteria:**

1. `layouts/app.blade.php` — authenticated layout with sidebar, navbar, flash area, `#modal-container`
2. `layouts/guest.blade.php` — public layout for auth pages
3. `<x-layout.sidebar>` component with navigation links and tier-based visibility
4. `<x-layout.navbar>` component with user menu
5. SCSS foundation: `_variables.scss`, `_mixins.scss`, `_base.scss`, `_layout.scss`, `_animations.scss`, `app.scss` entry point
6. BEM naming convention applied throughout
7. HTMX included via `app.js` with CSRF token header and 422/419 handlers
8. Alpine.js initialized
9. Responsive sidebar collapse on smaller screens
10. ARIA: `role="navigation"`, `aria-current="page"` on active link, `aria-label` on nav

**Integration Verification:**

- IV1: Authenticated user sees sidebar with correct nav items
- IV2: Free-tier user sees premium items hidden or gated in sidebar
- IV3: HTMX requests include `X-CSRF-TOKEN` header

### Story 1.4: Shared Blade Components Library

As a developer,
I want reusable Blade components for forms, tables, modals, and UI elements,
so that all feature pages have consistent markup and accessibility.

**Acceptance Criteria:**

1. Form components: `<x-forms.input>`, `<x-forms.select>`, `<x-forms.textarea>`, `<x-forms.date-input>`, `<x-forms.form-card>`, `<x-forms.form-group>`, `<x-forms.form-row>`, `<x-forms.submit-button>`
2. Table components: `<x-tables.data-table>`, `<x-tables.pagination>`
3. Modal components: `<x-modals.modal>`, `<x-modals.confirm-delete>`
4. UI components: `<x-ui.stat-card>`, `<x-ui.progress-card>`, `<x-ui.empty-state>`, `<x-ui.flash>`, `<x-ui.timeline>`, `<x-ui.chart>`
5. `<x-layout.page-header>`, `<x-layout.section>`
6. `<x-premium-gate>` component for upgrade prompts
7. All form components support `old()` value restoration and `@error` display
8. All components include WCAG AA ARIA attributes per coding standards
9. SCSS component stylesheets: `_buttons.scss`, `_cards.scss`, `_forms.scss`, `_tables.scss`, `_modals.scss`, `_pagination.scss`, `_timeline.scss`, `_flash.scss`, `_badges.scss`

**Integration Verification:**

- IV1: Form component renders error state with `aria-invalid` and `aria-describedby`
- IV2: Modal loads into `#modal-container` with `role="dialog"` and `aria-modal="true"`
- IV3: Flash message auto-dismisses with `role="alert"`

### Story 1.5: Middleware & Policy Foundation

As a developer,
I want the `EnsurePremiumTier` and `DetectHtmx` middleware and the `HandlesHtmx` trait in place,
so that tier gating and HTMX detection work before feature controllers are built.

**Acceptance Criteria:**

1. `EnsurePremiumTier` middleware: blocks free-tier users from premium routes — returns partial for HTMX, redirect with flash for standard requests
2. `DetectHtmx` middleware: sets `$request->isHtmx()` helper based on `HX-Request` header
3. `HandlesHtmx` trait: provides dual-response helpers for controllers
4. Middleware registered in `bootstrap/app.php`
5. Premium middleware applied to route group in `routes/web.php`
6. Base Policy pattern established (ownership check `$user->id === $model->user_id`)

**Integration Verification:**

- IV1: Free-tier user accessing `/app/expenses` gets redirected with flash message
- IV2: HTMX request to premium route returns `<x-premium-gate>` partial
- IV3: `$request->isHtmx()` correctly detects HTMX vs standard requests

---

## 7. Epic 2: Egg Tracking (Free Tier)

**Epic Goal:** Deliver the egg entry CRUD feature — the only free-tier feature — with inline HTMX forms, pagination, and full test coverage.

**Integration Requirements:** Must work for both free and premium users. First feature to validate the full stack: controller -> form request -> model -> policy -> Blade view -> HTMX interaction.

### Story 2.1: Egg Entry Model, Migration, Factory & Seeder

As a developer,
I want the EggEntry data layer in place,
so that egg tracking has a working database foundation.

**Acceptance Criteria:**

1. `EggEntry` model with `$fillable`, `$casts`, `belongsTo(User)` relationship
2. Migration: `egg_entries` table with all columns per data model spec
3. `EggEntryFactory` with realistic fake data (sizes, colors, counts)
4. `EggEntrySeeder` that creates sample entries for test users
5. `EggEntryPolicy` with view/update/delete ownership checks
6. Policy registered in `AppServiceProvider`

**Integration Verification:**

- IV1: `php artisan migrate:fresh --seed` creates table and seed data
- IV2: Factory produces valid model instances
- IV3: Policy blocks cross-user access

### Story 2.2: Egg Entry Controller & Views (HTMX CRUD)

As a user,
I want to add, view, edit, and delete daily egg entries inline,
so that I can track my egg production quickly without page reloads.

**Acceptance Criteria:**

1. `EggEntryController` with `index`, `store`, `update`, `destroy` actions
2. `StoreEggEntryRequest` and `UpdateEggEntryRequest` form requests
3. `eggs/index.blade.php` — main page with data table and inline create form
4. `eggs/partials/entry-row.blade.php` — single row partial for HTMX swap
5. `eggs/partials/edit-form.blade.php` — inline edit form partial
6. Pagination via `<x-tables.pagination>` (15 items per page)
7. Dual response: HTMX requests get partials, standard requests get full page
8. `<x-ui.empty-state>` shown when no entries exist
9. Feature SCSS: `_egg-counter.scss`
10. All queries scoped to authenticated user

**Integration Verification:**

- IV1: Create egg entry via HTMX -> new row appears without page reload
- IV2: Edit inline -> row updates in place
- IV3: Delete with `hx-confirm` -> row fades out
- IV4: Free-tier user can access eggs page; premium user can too

### Story 2.3: Egg Entry Tests

As a developer,
I want comprehensive tests for egg tracking,
so that this first feature validates our testing patterns for all subsequent features.

**Acceptance Criteria:**

1. Unit tests: EggEntry model relationships, casts, factory
2. Feature tests: Full CRUD via HTTP (both HTMX and standard paths)
3. Policy tests: Ownership enforcement — user cannot access another user's entries
4. Validation tests: Required fields, enum values, min/max constraints
5. Tests use `RefreshDatabase` trait
6. Tests use `User::factory()->create()` and `User::factory()->premium()->create()`

**Integration Verification:**

- IV1: `php artisan test --filter=EggEntry` passes all tests
- IV2: Tests cover both HTMX (partial response) and standard (full page) paths
- IV3: Cross-user access test confirms 403 response

---

## 8. Epic 3: Flock & Batch Management

**Epic Goal:** Deliver flock profile, flock events, batch management, batch events, and death records — the core poultry management features behind the premium tier.

**Integration Requirements:** All routes behind `EnsurePremiumTier` middleware. Batch detail view uses HTMX tab switching pattern that will be reused in later features.

### Story 3.1: Flock Profile & Events

As a premium user,
I want to manage my farm's flock profile and track flock lifecycle events,
so that I have a central view of my farm configuration and history.

**Acceptance Criteria:**

1. `FlockProfile` model, migration, factory, seeder, policy
2. `FlockEvent` model, migration, factory, seeder, policy
3. `FlockProfileController` — `index` (show or create), `store`, `update`
4. `FlockEventController` — `store`, `update`, `destroy`
5. `StoreFlockProfileRequest`, `StoreFlockEventRequest` form requests
6. `flock/index.blade.php` with profile form + `<x-ui.timeline>` for events
7. One profile per user enforced at database level (`user_id` unique)
8. HTMX inline event add/edit/delete on timeline
9. Feature SCSS: `_flock.scss`

**Integration Verification:**

- IV1: Free-tier user is blocked from `/app/flock`
- IV2: Profile create/update persists correctly
- IV3: Timeline events appear in chronological order

### Story 3.2: Flock Batch CRUD

As a premium user,
I want to create and manage individual batches of birds,
so that I can track different groups of poultry separately.

**Acceptance Criteria:**

1. `FlockBatch` model, migration, factory, seeder, policy
2. `FlockBatchController` — full CRUD (`index`, `create`, `store`, `show`, `edit`, `update`, `destroy`)
3. `StoreFlockBatchRequest`, `UpdateFlockBatchRequest` form requests
4. `batches/index.blade.php` — batch list with key stats per batch
5. `batches/show.blade.php` — detail view with tab structure (overview tab default)
6. `batches/create.blade.php` and `batches/edit.blade.php` — full forms
7. Active/archived filtering (`is_active`)
8. Pagination on index
9. Feature SCSS: `_batches.scss`

**Integration Verification:**

- IV1: Create batch -> appears in list
- IV2: Batch detail shows correct bird counts
- IV3: Archiving a batch (is_active=false) removes it from default list view

### Story 3.3: Batch Events & Death Records

As a premium user,
I want to log events and mortality records for each batch,
so that I can track the health and lifecycle of my birds.

**Acceptance Criteria:**

1. `BatchEvent` model, migration, factory, seeder, policy
2. `DeathRecord` model, migration, factory, seeder, policy
3. `BatchEventController` — `store`, `update`, `destroy` (nested under batches)
4. `DeathRecordController` — `store`, `update`, `destroy` (nested under batches)
5. `StoreBatchEventRequest`, `StoreDeathRecordRequest` form requests
6. Batch detail tabs: Events tab and Deaths tab loaded via HTMX partial swap
7. `batches/partials/events-tab.blade.php`, `batches/partials/deaths-tab.blade.php`
8. Death records automatically decrement `FlockBatch.current_count`
9. Tab switching uses `hx-push-url` for URL state

**Integration Verification:**

- IV1: Adding death record decrements batch `current_count`
- IV2: Tab switching loads correct partial without full page reload
- IV3: Events display in reverse chronological order

### Story 3.4: Flock & Batch Tests

As a developer,
I want full test coverage for flock profile, batches, events, and death records,
so that the premium flock management features are reliable.

**Acceptance Criteria:**

1. Feature tests for all 4 controllers (HTMX + standard paths)
2. Policy tests for all 4 models (ownership enforcement)
3. Unit tests for FlockBatch `current_count` decrement logic
4. Tier enforcement tests — free user gets blocked
5. Nested resource tests — batch events/deaths scoped to correct batch

**Integration Verification:**

- IV1: `php artisan test --filter=Flock` passes
- IV2: `php artisan test --filter=Batch` passes
- IV3: Cross-user and cross-batch access tests confirm 403

---

## 9. Epic 4: Financial Management

**Epic Goal:** Deliver expenses, feed inventory, CRM (customers), and sales tracking — the financial features behind the premium tier.

**Integration Requirements:** All routes behind premium middleware. Sales link to customers (optional FK). These features follow the same HTMX CRUD pattern established in Epic 2.

### Story 4.1: Expense Tracking

As a premium user,
I want to record and categorize farm expenses,
so that I can understand my operating costs.

**Acceptance Criteria:**

1. `Expense` model, migration, factory, seeder, policy
2. `ExpenseController` — `index`, `store`, `update`, `destroy`
3. `StoreExpenseRequest` form request
4. `expenses/index.blade.php` with inline HTMX CRUD + pagination
5. Category filtering (feed, medical, equipment, housing, utilities, other)
6. Feature SCSS: `_expenses.scss`

**Integration Verification:**

- IV1: HTMX inline create/edit/delete works
- IV2: Free-tier user is blocked
- IV3: Expenses scoped to authenticated user only

### Story 4.2: Feed Inventory

As a premium user,
I want to track feed purchases and inventory,
so that I can monitor feed costs and avoid running out.

**Acceptance Criteria:**

1. `FeedInventory` model, migration, factory, seeder, policy
2. `FeedInventoryController` — `index`, `store`, `update`, `destroy`
3. `StoreFeedInventoryRequest` form request
4. `feed/index.blade.php` with inline HTMX CRUD
5. Expiry date visibility (highlight expired/near-expiry)
6. Feature SCSS: `_feed.scss`

**Integration Verification:**

- IV1: HTMX CRUD cycle works
- IV2: Expired feed is visually distinguished
- IV3: Quantities validate as non-negative

### Story 4.3: Customer CRM

As a premium user,
I want to manage my egg customers with contact info and status,
so that I can maintain buyer relationships.

**Acceptance Criteria:**

1. `Customer` model, migration, factory, seeder, policy
2. `CustomerController` — `index`, `create`, `store`, `edit`, `update`, `destroy`
3. `StoreCustomerRequest` form request
4. `customers/index.blade.php` with search and active/inactive filtering
5. Soft deactivation (`is_active` flag) instead of hard delete
6. Delete uses `hx-confirm` + fade-out animation
7. Feature SCSS: `_crm.scss`

**Integration Verification:**

- IV1: Search filters customers by name
- IV2: Deactivated customers hidden from default view
- IV3: Customer with sales cannot be hard-deleted (if applicable)

### Story 4.4: Sales Tracking

As a premium user,
I want to record egg sales with customer association and payment tracking,
so that I can monitor my revenue.

**Acceptance Criteria:**

1. `Sale` model, migration, factory, seeder, policy
2. `SaleController` — `index`, `store`, `update`, `destroy`
3. `StoreSaleRequest` form request
4. `sales/index.blade.php` with inline HTMX CRUD + pagination
5. Optional customer association via dropdown (populated from user's customers)
6. Payment status toggle (paid/unpaid)
7. Feature SCSS: `_sales.scss`

**Integration Verification:**

- IV1: Sale linked to customer displays customer name
- IV2: Sale without customer works (nullable FK)
- IV3: Payment status toggles via HTMX

### Story 4.5: Financial Features Tests

As a developer,
I want full test coverage for expenses, feed, customers, and sales,
so that financial data integrity is guaranteed.

**Acceptance Criteria:**

1. Feature tests for all 4 controllers
2. Policy tests for all 4 models
3. Validation tests for currency/quantity fields (decimal precision, non-negative)
4. Sale-customer relationship tests (nullable FK, customer deletion impact)
5. Tier enforcement tests

**Integration Verification:**

- IV1: `php artisan test --filter=Expense` passes
- IV2: `php artisan test --filter=Feed` passes
- IV3: `php artisan test --filter=Customer` passes
- IV4: `php artisan test --filter=Sale` passes

---

## 10. Epic 5: Dashboard & Analytics

**Epic Goal:** Deliver the dashboard with aggregated stats and charts, sales reports, savings analysis, and viability calculator.

**Integration Requirements:** Depends on all prior epics — aggregates data from egg entries, batches, expenses, sales, and feed. Requires service classes for complex calculations.

### Story 5.1: Dashboard

As a user,
I want a dashboard showing my farm's key metrics and recent activity,
so that I get an at-a-glance overview when I log in.

**Acceptance Criteria:**

1. `DashboardController` with `index` action
2. `DashboardService` — `getSummary(User): array` aggregating eggs, expenses, sales, flock counts, recent events
3. `dashboard/index.blade.php` with `<x-ui.stat-card>` components
4. Chart.js visualizations (egg production trend, expense breakdown)
5. `dashboard/partials/` for HTMX-refreshable sections (if applicable)
6. Dashboard accessible to both free and premium users (data scoped to available features)
7. Queries optimized: < 10 queries, < 500ms load, eager loading
8. Feature SCSS: `_dashboard.scss`

**Integration Verification:**

- IV1: Dashboard loads with real aggregated data from seeded entries
- IV2: Free-tier user sees egg stats only; premium sees all stats
- IV3: Chart.js renders without JavaScript errors

### Story 5.2: Sales Reports

As a premium user,
I want to view sales reports with revenue summaries by period,
so that I can analyze my sales performance.

**Acceptance Criteria:**

1. `SalesReportController` with `index` action
2. `ReportService` — `getSalesReport(User, ?from, ?to): array`
3. `sales/reports.blade.php` with date range filter and summary tables
4. Per-customer revenue breakdowns
5. Totals by period (weekly/monthly)

**Integration Verification:**

- IV1: Report data matches manual sum of sales records
- IV2: Date range filter updates results via HTMX
- IV3: Per-customer breakdown includes correct sale totals

### Story 5.3: Savings & Viability Calculators

As a premium user,
I want to see financial analysis and viability calculations,
so that I can understand my farm's profitability.

**Acceptance Criteria:**

1. `SavingsController` with `index` action
2. `SavingsService` — `getFinancialAnalysis(User): array` (income vs expenses, profit margins, cost per egg)
3. `ViabilityController` with `index` action
4. `ViabilityService` — `calculate(User, array): array` (cost/profit per bird, break-even)
5. `savings/index.blade.php` and `viability/index.blade.php`
6. Feature SCSS: `_savings.scss`, `_viability.scss`

**Integration Verification:**

- IV1: Savings calculations are consistent with expense + sales data
- IV2: Viability calculator produces sensible outputs with seed data
- IV3: Both pages blocked for free-tier users

### Story 5.4: Dashboard & Analytics Tests

As a developer,
I want tests for dashboard, reports, savings, and viability,
so that aggregation logic is verified.

**Acceptance Criteria:**

1. Unit tests for `DashboardService`, `ReportService`, `SavingsService`, `ViabilityService`
2. Feature tests for all 4 controllers
3. Edge case tests: empty data (new user), single entry, large datasets
4. Performance: dashboard query count assertion (< 10)

**Integration Verification:**

- IV1: `php artisan test --filter=Dashboard` passes
- IV2: `php artisan test --filter=Report` passes
- IV3: `php artisan test --filter=Savings` passes
- IV4: `php artisan test --filter=Viability` passes

---

## 11. Epic 6: Polish & Parity

**Epic Goal:** Deliver onboarding, landing page, account settings, and final UX polish to achieve full feature parity with the original app.

**Integration Requirements:** Spans all features. Must not break any existing functionality.

### Story 6.1: Onboarding Wizard

As a new user,
I want a guided onboarding experience after first registration,
so that I can set up my farm profile quickly.

**Acceptance Criteria:**

1. Multi-step onboarding flow (farm name, flock size, first batch or skip)
2. `onboarding/wizard.blade.php` with step partials
3. HTMX-powered step transitions
4. Skippable — user can go straight to dashboard
5. Only shown on first login (flag or empty profile detection)
6. Feature SCSS: `_onboarding.scss`

**Integration Verification:**

- IV1: New user sees onboarding after registration
- IV2: Completing onboarding creates flock profile
- IV3: Returning user does not see onboarding again

### Story 6.2: Landing Page & Public Pages

As a visitor,
I want an informative landing page explaining ChickenCare,
so that I understand the app before registering.

**Acceptance Criteria:**

1. `LandingController` — `index`, `costs`
2. `layouts/landing.blade.php` — public layout
3. `landing/index.blade.php` — marketing landing page
4. `landing/costs.blade.php` — pricing/costs information page
5. Feature SCSS: `_landing.scss`
6. Links to register/login

**Integration Verification:**

- IV1: `/` loads landing page without auth
- IV2: `/costs` loads costs page
- IV3: Navigation to register/login works

### Story 6.3: Account Settings

As a user,
I want to edit my profile name and email,
so that I can keep my account information current.

**Acceptance Criteria:**

1. `AccountController` — `edit`, `update`
2. `account/edit.blade.php` with profile form
3. Email change validation (unique)
4. Password change (optional, with current password confirmation)
5. Feature SCSS: `_auth.scss` (shared with auth pages)

**Integration Verification:**

- IV1: Name/email update persists
- IV2: Duplicate email rejected with validation error
- IV3: Password change requires current password

### Story 6.4: Final Parity Verification & Seed Data

As a developer,
I want comprehensive seed data and a full parity check against the original app,
so that the rebuild is complete and verified.

**Acceptance Criteria:**

1. `DatabaseSeeder` orchestrates all 12 seeders with realistic demo data
2. Two demo users: one free-tier, one premium with full data
3. Full walkthrough comparison against `d:\Koke\Aplikacija` for every feature
4. All tests pass: `php artisan test` with >= 70% coverage
5. Performance targets met (< 200ms page loads, < 10 queries per page)
6. WCAG AA spot-check on all key pages

**Integration Verification:**

- IV1: `php artisan migrate:fresh --seed` produces a fully populated app
- IV2: All feature tests pass
- IV3: Manual comparison confirms parity with original app
