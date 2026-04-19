# 6. Epic 1: Project Foundation & Authentication

**Epic Goal:** Establish the Laravel 12 project scaffold, Docker MariaDB, authentication system, shared UI components, and SCSS design system — the foundation everything else builds on.

**Integration Requirements:** Must produce a running app at `127.0.0.1:8000` with working registration, login, and an authenticated shell layout before any feature work begins.

## Story 1.1: Laravel Project Initialization & Docker MariaDB Setup

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

## Story 1.2: Authentication with Laravel Breeze

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

## Story 1.3: App Layout Shell & SCSS Design System Foundation

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

## Story 1.4: Shared Blade Components Library

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

## Story 1.5: Middleware & Policy Foundation

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
