# Story 6: Split Unified Flock Batch Manager Back to Individual Pages

## User Story

As a user,
I want the flock overview and per-batch management to live on their own dedicated pages,
So that `/flock` stays focused on fleet-level numbers and events, and `/batches` stays focused on individual batch management — each with the polish and features built in Stories 1–5.

---

## Story Context

### Course Correction

Stories 1–5 consolidated `/flock` and `/batches` into a single tabbed page at `/flock-batches`. This reverses Resolved Decision #2 of the epic: the canonical UI moves back to the two separate pages. Everything built in Stories 1–5 — the stats service, enums, modals, policies, HTMX partials, `flock:changed` event bus, SCSS animations, tests — is **preserved and carried over**. Only the unified page shell is retired.

### Existing System Integration

- Integrates with: `resources/views/flock/`, `resources/views/batches/`, `app/Http/Controllers/FlockProfileController.php`, `app/Http/Controllers/FlockBatchController.php`
- Technology: Laravel 13 Blade, HTMX, Alpine.js v3, SCSS keyframe animations
- Follows pattern: HTMX partial swaps + `flock:changed` event bus (already established in Stories 1–5)
- Touch points: `/flock` and `/batches` gain the Story 1–5 look and features; new dedicated `/batches/{batch}` detail route; `/flock-batches` routes, controller, views, and SCSS are deleted

### Change Scope

**Retained (page-agnostic, unchanged):**
- `App\Services\FlockBatchStatsService`
- Enums: `BatchAgeAtAcquisition`, `BatchEventType`, `DeathCause`
- `DeathRecord` fillable + `cause` enum cast
- `BatchEvent` `batch_id` fillable fix (C1)
- `FlockBatch` enum casts (C2)
- Policy `viewAny`/`view` additions on `BatchEventPolicy`, `DeathRecordPolicy` (H1, H2)
- Factories using enum instances (M1)
- `flock:changed` HX-Trigger event bus
- Reusable Blade components: `<x-ui.stat-card>`, toast region, modal scaffolding
- SCSS keyframe animations and `prefers-reduced-motion` handling
- All tests from Stories 1–5 (retargeted to new routes/controllers)

**Redistributed:**
- **`/flock`** inherits the **FlockOverview 5-card stats header** (Laying / Not Laying / Brooding / Roosters / Chicks) — derived from batches via `FlockBatchStatsService`. Continues to show the existing `FlockEvent` timeline.
- **`/batches`** inherits the **Batches List** (sortable paginated table, empty state, status badges, 📅 laying-date inline trigger) and the **Add Batch form** (as the existing create flow — keep whatever layout `/batches` already uses: inline, modal, or separate `/batches/create` page per existing convention; do not force the tab UX).
- **`/batches/{batch}`** — **NEW dedicated full-page route** — inherits the entire Batch Detail Drill-Down from Story 5: composition stats, batch age/cost summary, read-only details, batch events timeline + add event form, **Deaths section (log form + per-batch history table)**, Edit Composition modal, Laying Date modal.

**Retired:**
- Route `GET /app/flock-batches` and related sub-routes
- `FlockBatchManagerController`
- `FlockBatchDeathRecordController` (body-param deaths endpoint) — replaced by the existing nested `batches.deaths.*` routes
- `resources/views/flock-batches/` directory (all partials)
- `resources/scss/features/_flock-batches.scss` (if it exists as a separate file) — migrate any still-needed rules into `_flock.scss` / `_batches.scss`
- Unified-page-only navigation entries (sidebar/topbar links pointing to `/flock-batches`)

---

## Acceptance Criteria

### Functional Requirements

#### `/flock` — Flock Profile Page

1. **FlockOverview 5-card stats header** appears above the existing FlockEvent timeline.
   - Uses `FlockBatchStatsService::overview($user)` (batches-derived, per Resolved Decision #5).
   - Conditional Brooding card (shown only when `showBrooding === true`).
   - Responsive grid: 2-col mobile → 5-col desktop.
   - Dark mode classes preserved.
2. **Toast region** (reusable `<x-ui.toast>`) listens for `flock:changed` window events so profile-level mutations and batch mutations both trigger a stats refresh.
3. **FlockEvent timeline** continues to work unchanged.
4. `FlockProfile` aggregate count fields remain in the schema but are **not** used by the `/flock` view — `FlockBatchStatsService` is the single source of truth. Document this in a code comment on `FlockProfile`.

#### `/batches` — Batches List Page

5. **Sortable, paginated batches DataTable** matching Story 2's implementation.
   - Columns: Batch Name, Current Count, Status badge, Started With, Acquired, Source, Laying Since.
   - Row click navigates to `/batches/{batch}` (full page load, not hx-swap).
   - 📅 inline button triggers the Laying Date modal on the row.
   - Empty state: "📦 No Batches Yet" with CTA to Add Batch.
6. **Add Batch flow** matches Story 3's form (all fields, live composition preview, validation, enum-backed `age_at_acquisition`) using the existing `/batches` create convention — whether that is an inline panel, a modal, or `/batches/create`. Do **not** introduce a tab interface.
7. On submit success: `HX-Trigger: flock:changed` + success toast + redirect/refresh back to the batches list per existing convention.

#### `/batches/{batch}` — Batch Detail Page (NEW)

8. **New route:** `GET /app/batches/{batch}` → `FlockBatchController@show`, named `app.batches.show`.
   - Authorized via `FlockBatchPolicy::view`.
   - Full page load, not an HTMX partial swap.
9. Page contains **all of Story 5's drill-down content**:
   - Composition 4-stat grid (Hens / Roosters / Brooding / Chicks)
   - Batch Age + Batch Cost summary
   - Read-only Batch Details block
   - Batch Events timeline (11 event types) + add event form
   - **Deaths section**: log form (date, count, cause enum, description, notes) + per-batch death history table
   - Edit Composition modal
   - Laying Date modal
10. **Deaths for this batch use the existing nested route** `POST /app/batches/{batch}/deaths` → `DeathRecordController@store`. The body-param route from Story 4 is removed.
11. Back link returns to `/batches` (native browser nav or explicit "← Back to batches" link — match existing `/batches` convention).
12. All mutations (batch edit, event log, death log, laying date set/clear, composition edit) emit `HX-Trigger: flock:changed` so stats on `/flock` refresh on next visit and the batches list stays consistent.

#### Retirement of `/flock-batches`

13. Delete route registrations for `/app/flock-batches*` in `routes/web.php`.
14. Delete `app/Http/Controllers/FlockBatchManagerController.php`.
15. Delete `app/Http/Controllers/FlockBatchDeathRecordController.php`.
16. Delete `resources/views/flock-batches/` (entire directory and all partials).
17. Delete `resources/scss/features/_flock-batches.scss` if it exists; migrate any still-referenced rules into `_flock.scss` or `_batches.scss`.
18. Remove `/flock-batches` from navigation (sidebar, topbar, breadcrumbs, any hard-coded links in Blade).
19. Update `app/Policies/FlockBatchPolicy.php` — no changes expected, but verify `view` ability is defined for the new `/batches/{batch}` show route.

### Non-Functional Requirements

20. **Visual parity** with the Story 1–5 look is preserved on both `/flock` and `/batches` (light + dark mode, mobile + desktop).
21. **Dark mode** preserved across all migrated partials.
22. **Reduced motion** (`prefers-reduced-motion`) continues to be respected on all surviving animations.
23. **Accessibility** — ARIA attributes from Stories 1–5 (`aria-sort`, `aria-live`, `aria-modal`, focus trap on modals) carry over; no regressions.
24. **Performance** — page load overhead is unchanged or better (one fewer route, one fewer large Blade tree).

---

## Technical Notes

### Route Changes

```php
// Added
Route::get('/batches/{batch}', [FlockBatchController::class, 'show'])
    ->name('app.batches.show');

// Removed
Route::get('/flock-batches', ...);                                 // unified index
Route::get('/flock-batches/{batch}/detail', ...);                  // drill-down partial
Route::post('/flock-batches/deaths', ...);                         // body-param deaths
// ...and any other /flock-batches/* endpoints
```

### Controller Changes

- **`FlockProfileController@index`** — inject `FlockBatchStatsService`; pass `$overviewStats` to the view.
- **`FlockBatchController@index`** — adopt the Story 2 sortable/paginated list partial pattern.
- **`FlockBatchController@show`** — new method; returns the full batch detail view (eager-load events + deaths); authorizes via `FlockBatchPolicy::view`.
- **`FlockBatchManagerController`** — deleted.
- **`FlockBatchDeathRecordController`** — deleted; revert deaths creation to `DeathRecordController@store` under `batches.deaths`.

### Blade Migration Map

| From `resources/views/flock-batches/` | To |
|---|---|
| `_overview-stats.blade.php` | `resources/views/flock/partials/_overview-stats.blade.php` |
| `_batches-table.blade.php` | `resources/views/batches/partials/_batches-table.blade.php` |
| `_add-batch-form.blade.php` | `resources/views/batches/partials/_add-batch-form.blade.php` (or merge into existing create view) |
| `_batch-detail.blade.php` | `resources/views/batches/show.blade.php` (as full page, not partial) |
| `_deaths-*.blade.php` | `resources/views/batches/partials/_deaths-*.blade.php` (scoped to single batch) |
| `_edit-composition-modal.blade.php` | `resources/views/batches/partials/_edit-composition-modal.blade.php` |
| `_laying-date-modal.blade.php` | `resources/views/batches/partials/_laying-date-modal.blade.php` |
| `_toast.blade.php` | already `<x-ui.toast>` — keep |

Exact filenames may vary; use sibling files' conventions.

### Event Bus

- `flock:changed` is preserved. Listeners now live on `/flock` (stats refresh) and `/batches` (list refresh, via `hx-trigger="flock:changed from:body"` on the list partial).
- Tab-count badges are removed (no tabs).

### `FlockProfile` Paradigm

- Count fields on `FlockProfile` are deprecated but not dropped. Add a `@deprecated` PHPDoc tag to the relevant attributes documenting that `FlockBatchStatsService::overview()` is the source of truth.
- A future cleanup story can drop the columns after confirming no other code reads them.

---

## Testing

25. **Retarget existing tests** — tests that hit `/flock-batches*` routes update to hit `/flock`, `/batches`, or `/batches/{batch}` as appropriate. Assertions on HTMX headers (`flock:changed`), validation, authorization, decrement logic, enum labels, etc. should otherwise be unchanged.
26. **New test: `FlockBatchController@show`** — authorized user sees their own batch, unauthorized/other-user gets 403, missing batch gets 404.
27. **New test: batch detail page** — asserts composition stats, events timeline, deaths section, Edit Composition modal trigger, Laying Date modal trigger all render.
28. **Regression test: `/flock` overview stats** — stats match `FlockBatchStatsService::overview()`; conditional Brooding card hides when zero.
29. **Regression test: `/batches` list** — sort, pagination, row navigation to `/batches/{batch}`, 📅 modal trigger, empty state.
30. **Regression test: death logging via nested route** — `POST /app/batches/{batch}/deaths` creates the record, decrements `current_count` in a transaction, emits `flock:changed`.
31. **Delete orphaned tests** — any test exclusively asserting unified-page behavior (e.g. tab switching, tab badge counts, `?tab=` query param sync) is deleted.
32. Run `php artisan test --compact` at the end; all tests green.
33. Run `vendor/bin/pint --dirty --format agent`.

---

## Rollback Plan

- This story is itself a reversal of a prior decision, so the rollback is simply restoring the deleted files from git history.
- No database migrations — no data rollback needed.
- `FlockBatchStatsService` and enums survive either way.

---

## Open Questions

1. **Add Batch UX on `/batches`** — does the existing page use an inline create section, a modal, or a `/batches/create` page? (The story preserves whichever is canonical; confirm before implementation.)
2. **Sidebar/nav label** — "Flock" + "Batches" as two entries, matching the pre-epic structure?
3. **Single redirect** — add a temporary `Route::redirect('/flock-batches', '/flock')` for bookmarks, or hard-delete with no redirect? (Default: hard delete.)

---

## Dependencies

- Stories 1–5 must remain merged; this story builds on their artifacts.
- No external dependencies beyond what Stories 1–5 already required.

---

## Status

Ready for Review

## Dev Agent Record

### Agent Model Used

claude-opus-4-7 (1M context)

### Completion Notes

- Merged `FlockBatchManagerController` store/modal/composition/laying-date logic into `FlockBatchController`. Consolidated `StoreFlockBatchManagerRequest` → `StoreFlockBatchRequest` (the new contract derives `type`/`initial_count`/`current_count` from bird counts; all-zero rejected).
- `/batches/{batch}` now uses `FlockBatchController@show` as a full page (no HTMX tab UX). Nested deaths via `app.batches.deaths.store`; added `GET batches/{batch}/deaths` (named `app.batches.deaths.index`) for per-batch history HTMX refresh. Composition / laying-date modal + patch routes added.
- `FlockProfileController` injects `FlockBatchStatsService` and passes `overviewStats` to `flock.index`. Overview 5-card stats now render via `flock/partials/overview-stats.blade.php` (Brooding card conditional). Deprecation block on `FlockProfile` model aggregate attributes.
- `BatchEventController@store` now returns `batches.partials.timeline-event-row` + `HX-Trigger: flock:changed,flock:success` for HTMX. `DeathRecordController@store` returns `batches.partials.deaths-form` + `HX-Trigger: flock:changed`.
- `/app/flock-batches` routes, `FlockBatchManagerController`, `FlockBatchDeathRecordController`, `flock-batches` view dir, and `_flock-batches.scss` removed. Animations migrated into `_batches.scss` (shared `.flock-card-entrance` / `.flock-timeline-entry` classes retained).
- Test suite green: 936 passed / 2414 assertions. Pint clean. Visual QA subagent confirmed /flock, /batches, /batches/{batch}, /batches/create render correctly in light + dark + mobile with no tab UI and no console errors.

### File List

**Added**
- `resources/views/flock/partials/overview-stats.blade.php`
- `resources/views/batches/partials/batches-table.blade.php`
- `resources/views/batches/partials/timeline-event-row.blade.php`
- `resources/views/batches/partials/composition-modal.blade.php`
- `resources/views/batches/partials/laying-date-modal.blade.php`
- `resources/views/batches/partials/deaths-form.blade.php`
- `resources/views/batches/partials/deaths-history-table.blade.php`
- `resources/views/batches/partials/deaths-section.blade.php`
- `tests/Feature/FlockBatchIndexSortingTest.php`
- `tests/Feature/FlockBatchStoreTest.php`
- `tests/Feature/FlockOverviewStatsTest.php`

**Modified**
- `routes/web.php`
- `app/Http/Controllers/FlockBatchController.php`
- `app/Http/Controllers/FlockProfileController.php`
- `app/Http/Controllers/BatchEventController.php`
- `app/Http/Controllers/DeathRecordController.php`
- `app/Http/Requests/StoreFlockBatchRequest.php`
- `app/Models/FlockProfile.php`
- `resources/views/flock/index.blade.php`
- `resources/views/flock/partials/flock-overview.blade.php`
- `resources/views/batches/index.blade.php`
- `resources/views/batches/show.blade.php`
- `resources/views/batches/create.blade.php`
- `resources/scss/app.scss`
- `resources/scss/features/_batches.scss`
- `tests/Feature/FlockBatchControllerTest.php`
- `tests/Feature/FlockBatchDeathRecordControllerTest.php`
- `tests/Feature/BatchEventControllerTest.php`
- `tests/Feature/DeathRecordControllerTest.php`
- `tests/Feature/FlockBatches/BatchDetailTest.php`
- `tests/Feature/FlockBatches/BatchDetailModalsTest.php`

**Deleted**
- `app/Http/Controllers/FlockBatchManagerController.php`
- `app/Http/Controllers/FlockBatchDeathRecordController.php`
- `app/Http/Requests/StoreFlockBatchManagerRequest.php`
- `app/Http/Requests/StoreFlockBatchDeathRequest.php`
- `resources/views/flock-batches/` (entire directory)
- `resources/views/batches/partials/overview-tab.blade.php`
- `resources/views/batches/partials/events-tab.blade.php`
- `resources/views/batches/partials/deaths-tab.blade.php`
- `resources/views/batches/partials/batch-list.blade.php`
- `resources/scss/features/_flock-batches.scss`
- `tests/Feature/FlockBatchManagerTest.php`
- `tests/Feature/FlockBatchManagerBatchesTableTest.php`
- `tests/Feature/FlockBatchManagerStoreTest.php`

### Change Log

| Date       | Change                                                    |
|------------|-----------------------------------------------------------|
| 2026-04-18 | Story 6 implemented — split unified /flock-batches back into /flock, /batches, /batches/{batch}. Retired FlockBatchManager* controllers and views. |
