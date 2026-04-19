# 7. Epic 2: Egg Tracking (Free Tier)

**Epic Goal:** Deliver the egg entry CRUD feature — the only free-tier feature — with inline HTMX forms, pagination, inline analytics, and full test coverage. Achieve parity with the original app's egg tracking experience.

**Integration Requirements:** Must work for both free and premium users. First feature to validate the full stack: controller -> form request -> model -> policy -> Blade view -> HTMX interaction. Stats cards depend on existing UI components from Epic 1. Lay Rate placeholder awaits Epic 3 (Flock & Batch Management).

## Story 2.1: Egg Entry Model, Migration, Factory & Seeder

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

## Story 2.2: Egg Entry Controller & Views (HTMX CRUD)

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

## Story 2.4: Egg Tracking Analytics & Stats Cards

As a user,
I want to see statistics, trends, and goal progress on my egg tracking page,
so that I gain actionable insights from my egg data without navigating away.

**Acceptance Criteria:**

1. `EggStatsService` computes totals, averages, weekly/monthly comparisons
2. Stats section on egg page: 4 stat cards (Total Eggs, Average Daily, Lay Rate placeholder, Protein Generated)
3. Two comparison cards (7-day and monthly, with trend indicators)
4. Monthly goal progress card (based on new `yearly_egg_goal` user column)
5. "Set Your Annual Goal" CTA when no goal is set (links to account settings — placeholder route until Epic 6)
6. Lay Rate card shows `--` placeholder until Epic 3 delivers flock batch data
7. Stats section only visible when entries exist

**Integration Verification:**

- IV1: Stats cards display correct computed values from seeded data
- IV2: Comparison cards show accurate week-over-week and month-over-month deltas
- IV3: Lay Rate placeholder renders without errors
- IV4: Goal progress card reflects yearly_egg_goal / 12

## Story 2.5: Egg Entry UX Enhancements

As a user,
I want a streamlined form with duplicate protection and historical backfill,
so that I can enter data quickly, avoid mistakes, and import past records.

**Acceptance Criteria:**

1. Advanced logging toggle: size/color fields hidden behind a "Detailed tracking" checkbox
2. Duplicate detection: same date + size + color triggers confirmation before creating
3. Confirmation handled via HTMX partial with "Update" and "Cancel" options
4. Historical backfill modal: available when no entries exist, allows bulk date/count entry (up to 90 days back)
5. All interactions follow existing HTMX dual-response patterns

**Integration Verification:**

- IV1: Toggle hides/shows size and color fields
- IV2: Duplicate submission shows confirmation, confirm updates existing entry
- IV3: Backfill creates multiple entries for new users
- IV4: Existing CRUD unchanged (regression check)

## Story 2.3: Egg Entry Tests

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
