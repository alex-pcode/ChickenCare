# 8. Epic 3: Flock & Batch Management

**Epic Goal:** Deliver flock profile, flock events, batch management, batch events, and death records — the core poultry management features behind the premium tier.

**Integration Requirements:** All routes behind `EnsurePremiumTier` middleware. Batch detail view uses HTMX tab switching pattern that will be reused in later features.

## Story 3.1: Flock Profile & Events

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

## Story 3.2: Flock Batch CRUD

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

## Story 3.3: Batch Events & Death Records

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

## Story 3.4: Flock & Batch Tests

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
