# Epic: Flock Batch Manager - Complete Feature Replication

## Epic Goal

Replicate the React Flock Batch Manager in Laravel + HTMX to achieve feature parity with the original at `d:\Koke\Aplikacija\src\components\features\flock\FlockBatchManager.tsx` (+ `FlockOverview`, `BatchDetailView`, `BatchTimeline`, `EventTimeline`, `EventForm`, `FlockSummaryDisplay`).

`ChickenViability` is **out of scope** for this epic — it's a separate route (`/viability`) and warrants its own epic.

## Epic Description

### Existing System Context

- **Current Implementation:** Laravel 13 + HTMX + Blade with `/flock` (profile overview, legacy) and `/batches` (resource CRUD) as two separate pages
- **Reference Implementation:** React 19 unified SPA at `/flock-batches` combining overview + management + drill-down + modals
- **Technology Stack:** Laravel 13, HTMX, Alpine.js, Blade, MariaDB 10.6.22, Chart.js (already installed)
- **Integration Points:** `FlockBatch`, `BatchEvent`, `DeathRecord` (exists — fillable fix needed), `FlockProfile`, `User`

### Enhancement Details

**What's Being Added/Changed:**

1. **Unified Flock Batch Manager Page** — single tabbed page consolidating the overview, batch list, deaths, and add-batch flows (React's `FlockBatchManager`)
2. **FlockOverview Stats Header** — 5-card grid (Laying / Not Laying / Brooding / Roosters / Chicks) with conditional Brooding card
3. **Tab Navigation with Counts** — three tabs: Batches, Deaths, Add Batch, each with live badge count + animated tab switching
4. **Batches List Table** — sortable DataTable with batch name, current count, laying status badge, started-with, acquired, source, laying-since; click row to drill down
5. **Add Batch Form** — full form with batch name, breed, 4-field composition grid (hens/brooding/roosters/chicks), age-at-acquisition enum, acquisition date, optional laying start, source, cost, notes; live composition preview; auto-calculated batch type
6. **Deaths Tab** — FormCard with batch select (filtered to batches with birds), date, count, cause enum (7 values), description, notes; history DataTable with date/batch/count/cause/description columns
7. **Batch Detail Drill-Down** — composition stats, batch age, batch cost, read-only batch details block, timeline events (11 event types), batch timeline display, Edit Composition modal
8. **Laying Date Modal** — HTMX modal for setting/clearing `actual_laying_start_date` (with today-as-max constraint)
9. **Animated Toasts** — slide-down success/error banners with auto-dismiss (AnimatePresence height-based animations)
10. **Fleet-Level Paradigm Shift** — `FlockProfile` aggregate counts become derived from batches; legacy `/flock` profile-level events remain but UI consolidates to `/flock-batches`

**How It Integrates:**

- Builds on existing `FlockBatch` model + migration (32 columns, acquisition/laying dates, counts, cost, type enum)
- Builds on existing `BatchEvent` model/migration (11 event types)
- Extends existing `DeathRecord` model + `DeathRecordController` + `DeathRecordPolicy` (all present); introduces a new body-param route `POST /app/flock-batches/deaths` serviced by a new `FlockBatchDeathRecordController`
- Extends `FlockBatchController`, `BatchEventController`
- New `FlockBatchStatsService` for the 5 FlockOverview stat calculations
- Uses existing `<x-ui.chart>` Blade component (no chart on this page currently, but bird-count visualization may be added in a future epic)
- Continues HTMX partial swap patterns established in the egg-counter and expenses epics

**Success Criteria:**

- Visual parity with `FlockBatchManager.tsx` achieved (side-by-side screenshot diff, light + dark mode)
- All 3 tabs work with animated transitions
- Drill-down into a batch shows composition, details, events timeline, and edit modal
- Laying date modal sets/clears the field correctly with today-as-max constraint
- FlockOverview stat counts match React's exact formulas
- Responsive: 2-col grid on mobile, 5-col on desktop
- Dark mode preserved (including badge and toast colors)

---

## Stories

### ✅ Story 1: Page Shell, Tabs, and FlockOverview Stats Header — COMPLETE

**Scope:** Create the unified `/flock-batches` page shell with header, 5-stat FlockOverview grid (Laying / Not Laying / Brooding / Roosters / Chicks), and animated 3-tab navigation with live badge counts. Tab content is stubbed; sub-stories fill in each tab.

[text](flock-story-1-page-shell-tabs-overview.md)

**Key deliverables:**
- Route `GET /flock-batches` → `FlockBatchManagerController@index`
- `FlockBatchStatsService` with the 5 FlockOverview formulas
- Blade partial for stats cards (reuse existing `<x-ui.stat-card>` if present)
- Alpine-driven tab switching with URL sync (`?tab=batches|deaths|add-batch`) and `expenses:changed`-style event pattern (`flock:changed`)
- Animated toast region (reusable `<x-ui.toast>`)

---

### ✅ Story 2: Batches Tab — Sortable List Table — COMPLETE

**Scope:** Implement the "Batches" tab with a DataTable-equivalent showing all active batches. Each row is clickable to drill down (Story 5 handles the drill-down view). Columns: Batch Name, Current Count, Status badge, Started With, Acquired, Source, Laying Since (with 📅 inline edit).

[text](flock-story-2-batches-list-table.md)

**Key deliverables:**
- Server-side HTMX pagination + sort (pattern from expenses Story 3)
- Status badge ("🥚 Laying" green / "⏳ Not Laying" amber) — derived, not stored
- Empty state: "📦 No Batches Yet" with CTA to Add Batch tab
- 📅 button inline-triggers the Laying Date modal (Story 5 defines the modal, but the trigger lives here)

---

### ✅ Story 3: Add Batch Tab — New Batch Form — COMPLETE

**Scope:** The "Add Batch" tab form with all fields, live composition preview, and validation. Creates a new `FlockBatch` record.

[text](flock-story-3-add-batch-form.md)

**Key deliverables:**
- FormCard with batch name, breed, 4-field counts grid (hens/brooding/roosters/chicks), age-at-acquisition enum (chick 0-8w / juvenile 8-18w / adult 18+w), acquisition date, optional laying start (min=acquisitionDate), source, cost (decimal 0.01 step), notes
- Live composition preview card showing total + auto-calculated `type` (hens / roosters / chicks / mixed)
- Validation: at least 1 bird required, all counts ≥ 0, acquisition date ≤ today, laying start ≥ acquisition date
- `App\Enums\BatchAgeAtAcquisition` backed enum
- On success: `HX-Trigger: flock:changed`, success toast, form reset, auto-switch to Batches tab
- Currency column uses `@usd` directive (from expenses Story 1)

---

### ✅ Story 4: Deaths Tab — Death Logging & History — COMPLETE

**Scope:** "Deaths" tab with logging form and history table. Extends existing `DeathRecord` scaffolding; no new model needed.

[text](flock-story-4-deaths-tab.md)

**Key deliverables:**
- Existing `App\Models\DeathRecord` (add `batch_id` to `$fillable`; add `cause` enum cast)
- New `App\Enums\DeathCause` backed enum (unknown / predator / disease / age / injury / culled / other) with `label()` + `badgeColor()` methods
- New `FlockBatchDeathRecordController@store` for the body-param route `/app/flock-batches/deaths` (distinct from legacy nested `batches.deaths.*`)
- FormCard: batch select (filtered to `current_count > 0`), date, number lost (≥1), cause enum, description (required), notes
- History DataTable: Date (sortable), Batch (with 😢 icon lookup), Birds Lost (bold red, sortable), Cause (capitalized red badge), Description (truncated + tooltip), empty state "📝 No Losses Recorded"
- On submit: decrement `current_count` on the affected batch by the lost count; emit `flock:changed`
- Unit tests for `DeathRecord` model + decrement logic

---

### ✅ Story 5: Batch Detail View Drill-Down + Modals — COMPLETE

**Scope:** When a batch row is clicked, swap the tab content to show the detail view: composition stats, batch age + cost summary, read-only details block, timeline events section (11 event types), Edit Composition modal, and Laying Date modal.

[text](flock-story-5-batch-detail-drilldown.md)

**Key deliverables:**
- HTMX swap pattern: `hx-get="/flock-batches/{batch}/detail"` → replaces tab pane with detail partial
- Back button to return to Batches table
- Composition 4-stat grid (Hens / Roosters / Brooding / Chicks)
- Secondary stats: Batch Age (weeks since acquisition), Batch Cost (total + per-bird)
- Read-only Batch Details section (acquired, age at acquisition, source, cost, started with, laying status, notes)
- Add Timeline Event FormCard: date, event type (11-enum), description, affected count (optional), notes
- `App\Enums\BatchEventType` backed enum with 11 values: health_check, vaccination, relocation, breeding, laying_start, brooding_start, brooding_stop, production_note, flock_added, flock_loss, other
- Timeline display (reuse or port an alternating left/right timeline layout from React's `EventTimeline`)
- Edit Composition Modal (updates hens/brooding/roosters/chicks counts; recalculates `type`; amber-warning tip icon)
- Laying Date Modal (set/clear `actual_laying_start_date`; max=today; conditional info text by batch type)
- All detail-view actions emit `flock:changed` via HTMX header to keep Stories 1 & 2 stats/table in sync

---

### 📝 Story 6: Split Unified Page Back to Individual `/flock` + `/batches` — PLANNED

**Scope:** Course-correct Resolved Decision #2. Migrate all Story 1–5 features (stats header, sortable list, add-batch form, batch detail with deaths section, modals, event bus) back onto `/flock` and `/batches`, introduce a dedicated `/batches/{batch}` detail page, and retire `/flock-batches` entirely.

[text](flock-story-6-split-back-to-individual-pages.md)

**Key deliverables:**
- `/flock` gains the 5-card FlockOverview header (batches-derived via `FlockBatchStatsService`) above the existing FlockEvent timeline
- `/batches` gains the Story 2 sortable list + Story 3 add-batch form (matching existing create convention, no tabs)
- New `/batches/{batch}` full-page route holding the Story 5 drill-down **including per-batch Deaths section**
- Deaths revert to nested `POST /batches/{batch}/deaths` (existing `DeathRecordController`)
- Delete `/flock-batches` routes, `FlockBatchManagerController`, `FlockBatchDeathRecordController`, `resources/views/flock-batches/`, `_flock-batches.scss`
- Retain all page-agnostic artifacts: `FlockBatchStatsService`, 3 enums, policy fixes, factory updates, `flock:changed` event bus, tests (retargeted)

---

## Compatibility Requirements

- [x] Existing `FlockBatchController` CRUD routes remain functional; new unified page is additive
- [x] Existing `/flock` and `/batches` routes continue to work during migration; the new `/flock-batches` is the canonical UI
- [x] Database schema: `flock_batches`, `batch_events`, and `death_records` tables require no changes. All Eloquent models, controllers, and policies for these tables already exist (fillable fix required on `DeathRecord`)
- [x] UI changes are additive; legacy partials remain until Story 5 lands, then a follow-up cleanup removes them
- [x] Performance impact: one additional stats service call on page load; negligible
- [x] Dark mode support: preserved and enhanced

---

## Risk Mitigation

### Primary Risk

**Data model paradigm shift** — React treats batches as the source of truth for flock composition; Laravel's `FlockProfile` currently stores aggregate counts as independent fields. If both coexist, counts can drift.

### Secondary Risk

**BatchDetailView is the largest single component** (686 lines) — Story 5 is the highest-complexity story. Underestimation risk.

### Mitigation

1. Story 1 establishes `FlockBatchStatsService` as the single source for aggregate counts; `FlockProfile`'s count fields are deprecated (kept but unused on this page) — documented in a follow-up cleanup task
2. Story 5 starts with a spike to confirm the detail partial can be cleanly hx-swapped into the tab pane without losing the tab's Alpine state
3. Story 4 begins with a quick audit of the existing `DeathRecord`/controller/policy/request scaffolding and applies the minor fixes (fillable, enum cast, new body-param route) before UI work

### Rollback Plan

- All new routes are under `/flock-batches`; the legacy `/flock` and `/batches` routes are unchanged
- `FlockBatchStatsService` is additive (new file)
- New Blade partials are isolated under `resources/views/flock-batches/`
- `DeathRecord` scaffolding changes are backward-compatible (fillable additions, enum cast)
- No database migrations required (tables already exist)
- SCSS additions isolated to a new `_flock-batches.scss` (or extend `_flock.scss` / `_batches.scss`)

---

## Definition of Done

- [x] All 5 stories completed with acceptance criteria met
- [ ] Visual parity verified against `FlockBatchManager.tsx` (side-by-side screenshots, light + dark mode, mobile + desktop)
- [ ] `FlockBatchStatsService` has unit tests covering all 5 stat formulas (including the conditional Brooding card) — **REVIEW: roosters & chicks formulas missing tests**
- [x] `DeathRecord` model has a factory and feature tests for store + count decrement
- [x] `BatchEvent` and enum tests cover all 11 event types
- [ ] Feature tests: add batch happy path, validation errors, death logging, laying date set/clear, composition edit, tab switching preserves state, drill-down/back — **REVIEW: modal/drill-down tests missing**
- [ ] Existing CRUD behavior regression tested
- [ ] Animations smooth across Chrome, Firefox, Safari
- [ ] Dark mode verified — **REVIEW: 95%+ coverage confirmed**
- [ ] Mobile responsiveness: grid collapses, tabs remain usable, modals fit viewport — **REVIEW: responsive grid confirmed, table has overflow-x-auto**
- [ ] Accessibility: ARIA tab roles, aria-sort on columns, aria-label on delete & modal triggers, modals trap focus, reduced-motion respected — **REVIEW: strong ARIA coverage; `aria-describedby` on form error fields missing**
- [x] Code follows Laravel Boost guidelines; `vendor/bin/pint --dirty --format agent` passes
- [x] Per project rule: every change has programmatic test coverage (unit or feature)
- [x] **Code review findings resolved** — all C1-C4, H1-H4, M1-M3, M8 fixes applied (2026-04-17)

---

## Visual References

**Original Components:**
- Top-level page: `d:\Koke\Aplikacija\src\components\features\flock\FlockBatchManager.tsx`
- Stats header: `d:\Koke\Aplikacija\src\components\features\flock\FlockOverview.tsx`
- Drill-down: `d:\Koke\Aplikacija\src\components\features\flock\BatchDetailView.tsx`
- Timeline: `d:\Koke\Aplikacija\src\components\features\flock\BatchTimeline.tsx`, `EventTimeline.tsx`
- Event form: `d:\Koke\Aplikacija\src\components\features\flock\EventForm.tsx`
- Summary: `d:\Koke\Aplikacija\src\components\features\flock\FlockSummaryDisplay.tsx`

**Current Laravel Implementation:**
- Routes: `E:\ChickenCare\routes\web.php` (flock, batches, batches.events, batches.deaths)
- Controllers: `FlockBatchController`, `BatchEventController`, `FlockEventController`, `FlockProfileController`
- Models: `FlockBatch`, `BatchEvent`, `FlockEvent`, `FlockProfile`, `DeathRecord` (all exist)
- Views: `resources/views/batches/`, `resources/views/flock/`
- Styles: `resources/scss/features/_flock.scss`, `_batches.scss`

---

## Technical Notes

### New Enums (PHP 8.3 backed)

- `App\Enums\BatchAgeAtAcquisition` — cases: Chick ('chick'), Juvenile ('juvenile'), Adult ('adult'); `label()` returns e.g. "Chick (0–8 weeks)"
- `App\Enums\BatchEventType` — 11 cases (health_check, vaccination, relocation, breeding, laying_start, brooding_start, brooding_stop, production_note, flock_added, flock_loss, other); `label()` + `icon()` methods
- `App\Enums\DeathCause` — 7 cases (unknown, predator, disease, age, injury, culled, other); `label()` + `badgeColor()` methods

### Framer Motion → CSS / Alpine Equivalents

| Framer Motion | Laravel Equivalent |
|---|---|
| `AnimatePresence mode="wait"` on tabs | Alpine `x-show` + `x-transition:enter` + `x-transition:leave` on tab panes |
| Toast `height: 0 → auto` | Alpine `x-collapse` plugin, or Tailwind `transition-all` + `max-height` keyframes |
| Timeline staggered entries | CSS `animation-delay` per row, or Alpine `x-intersect` with incrementing delay |
| Modal slide-in | HTMX modal swap target + CSS keyframe on modal backdrop |
| Tab badge pop-in | CSS `transform: scale()` keyframe |

### Event Bus

- Continue the `expenses:changed` pattern: `flock:changed` emitted via `HX-Trigger` header on all mutations (add batch, delete batch, log death, log event, edit composition, set laying date)
- Listeners: FlockOverview stats card (`@flock:changed.window="refetchStats()"`), Batches table (`hx-trigger="flock:changed from:body"` → re-fetch current page), tab badge counts

### Stats Service Contract

```php
class FlockBatchStatsService {
    public function overview(User $user): array; // returns ['laying' => [...], 'notLaying' => [...], 'brooding' => [...], 'roosters' => [...], 'chicks' => [...], 'showBrooding' => bool]
    public function tabCounts(User $user): array; // returns ['batches' => int, 'deaths' => int, 'addBatch' => null]
    public function batchComposition(FlockBatch $batch): array; // for detail view
}
```

---

## Dependencies

### External Dependencies
- Chart.js (already installed) — only if future visualization added; not used by this epic's first pass

### Internal Dependencies
- `FlockBatch` model + existing migration
- `BatchEvent` model + existing migration
- `DeathRecord` model — exists (minor fillable + enum cast fixes in Story 4)
- `App\Support\Money::usd()` helper from expenses Story 1 — depends on that epic landing first OR the helper being extracted independently
- Policies: `FlockBatchPolicy` — **verify exists**; enforces user ownership on all batch operations

### Story Dependencies
- Story 1 → foundation; all others depend on it
- Story 2 depends on Story 1 (tab shell)
- Story 3 depends on Story 1 (tab shell)
- Story 4 depends on Story 1 (tab shell); extends existing `DeathRecord` scaffolding (no model creation needed)
- Story 5 depends on Stories 1 and 2 (drill-down triggered from the batches list)

### Epic Dependencies
- **Expenses epic (Story 1)** — if `App\Support\Money` + `@usd` directive land there first, this epic reuses them. If this epic ships first, extract them here and let expenses consume.

---

## Resolved Decisions (locked at epic start)

1. **Scope** — `ChickenViability` excluded; separate epic
2. **Routing** — ~~canonical URL is `/flock-batches`; legacy `/flock` and `/batches` remain functional until post-epic cleanup~~ **REVERSED 2026-04-18:** `/flock` and `/batches` are the canonical URLs; Story 6 migrates Story 1–5 features to them and retires `/flock-batches`. New dedicated `/batches/{batch}` page for batch detail.
3. **Event name** — `flock:changed` (follows `expenses:changed` convention)
4. **Enums** — `BatchAgeAtAcquisition`, `BatchEventType`, `DeathCause` all PHP 8.3 backed enums with `label()` methods
5. **Stats** — `FlockBatchStatsService` is the single source of truth; `FlockProfile` count fields deprecated (not removed) on this page

---

## Post-Implementation Code Review (2026-04-17)

### Overall Score: 85% — Solid implementation with targeted fixes needed

**Methodology:** Automated subagent review of controllers, routes, models, enums, policies, factories, Blade views, SCSS, and tests — cross-referenced against Laravel best practices and epic requirements.

### Critical Issues (Must Fix)

| # | Component | Issue | Impact |
|---|-----------|-------|--------|
| C1 | `BatchEvent` model | `batch_id` missing from `$fillable` — `batchEvents()->create()` will silently drop `batch_id`, producing NULL FK | **Data integrity** |
| C2 | `FlockBatch` model | Missing enum casts for `type` and `age_at_acquisition` — values stored/returned as raw strings instead of enum instances | **Data integrity** |
| C3 | `FlockBatchDeathRecordController@index` | Authorizes against `FlockBatch::class` instead of `DeathRecord::class` — wrong policy applied | **Security** |
| C4 | `FlockBatchManagerController` | Missing `HandlesHtmx` trait — inconsistent with all other flock controllers that use it | **Consistency** |

### High-Priority Issues

| # | Component | Issue | Impact |
|---|-----------|-------|--------|
| H1 | `BatchEventPolicy` | Missing `viewAny()` and `view()` methods — listing/viewing events will fail authorization | **Functionality** |
| H2 | `DeathRecordPolicy` | Missing `viewAny()` and `view()` methods — same as H1 | **Functionality** |
| H3 | `FlockBatchDeathRecordController@store` | No explicit `$this->authorize()` call — authorization delegated solely to Form Request, inconsistent with other controllers | **Security consistency** |
| H4 | `FlockBatchManagerController` | Duplicate type-resolution logic — `deriveFlockType()` private method duplicates `FlockBatch::resolveType()` static method | **Code quality** |

### Medium-Priority Issues

| # | Component | Issue | Impact |
|---|-----------|-------|--------|
| M1 | All factories | Using string values instead of enum instances (`'chick'` instead of `BatchAgeAtAcquisition::Chick`) | **Best practice** |
| M2 | `BatchEventFactory` | Only 3 of 11 event types have dedicated factory states | **Test coverage** |
| M3 | `DeathRecordFactory` | Only 3 of 7 death causes have dedicated factory states | **Test coverage** |
| M4 | Blade views | `batches-table.blade.php` row click uses `hx-swap="innerHTML"` — may cause focus loss; should be `outerHTML` | **UX** |
| M5 | Blade views | Form fields lack `aria-describedby` pointing to error messages | **Accessibility** |
| M6 | Blade views | Status badges and tab badge counts lack explicit `aria-label` | **Accessibility** |
| M7 | Blade views | Batch detail timeline event form submit button missing `:disabled="submitting"` | **UX** |
| M8 | Test suite | 10+ test files use `RefreshDatabase` instead of `LazilyRefreshDatabase` | **Test speed** |

### Test Coverage Gap Analysis

**Overall test coverage: ~65% of epic requirements**

| Story | Status | Missing Tests |
|-------|--------|---------------|
| Story 1: Page Shell & Stats | ⚠️ Partial | Roosters/chicks stat formula unit tests; conditional brooding card edge cases |
| Story 2: Batches Table | ⚠️ Partial | Pagination, sort direction toggle, drill-down row click trigger, laying date modal button, status badge derivation |
| Story 3: Add Batch Form | ⚠️ Partial | `age_at_acquisition` enum validation, composition preview, tab auto-switch on success, laying start ≥ acquisition date constraint |
| Story 4: Deaths Tab | ✅ Mostly complete | All 7 cause enum values individually, history table sorting/display |
| Story 5: Drill-Down & Modals | ⚠️ Partial | Row click → detail partial, back button, composition modal render + submit, laying date modal set/clear, timeline with all 11 event types, max-date constraint |
| Authorization/Policies | ✅ Complete | — |
| HTMX Headers | ✅ Complete | — |

### Blade & Frontend Assessment

**Strengths (8.1/10 overall):**
- 15 Blade partials, ~1,400 lines of well-structured view logic
- Excellent `<x-ui.stat-card>` reuse (15 instances)
- Strong ARIA coverage: `role="tablist"`, `aria-sort`, `aria-selected`, `aria-live`, `aria-modal`, focus trap in modals
- 95%+ dark mode coverage with proper `dark:` Tailwind classes
- All 6 CSS animations respect `@media (prefers-reduced-motion: reduce)`
- `@csrf` on all POST/PATCH forms; `{{ }}` escaping throughout (zero `{!! !!}`)
- Responsive grids: 2-col mobile → 5-col desktop; table has `overflow-x-auto`

**Component extraction opportunities (non-blocking):**
- `<x-ui.toast>` — inline Alpine toast logic could be extracted for app-wide reuse
- `<x-ui.modal>` — focus trap + backdrop duplicated across 2 modals
- `<x-ui.status-badge>` — laying/not-laying badges hard-coded in 4 places

### Positive Findings

- **Routes:** All required routes present under `auth` + `premium` middleware with proper naming (`app.flock-batches.*`)
- **Validation:** All controllers use Form Request classes; `$request->validated()` only; no `$request->all()`
- **HTMX:** `HX-Trigger: flock:changed` + `modal:close` emitted on all mutations per spec
- **Enums:** All 3 enums complete with correct cases, `label()`, `icon()`, `badgeColor()` methods as specified
- **FlockBatchStatsService:** Implements `overview()`, `tabCounts()`, `batchComposition()`, `metricDisplayStats()` with proper eager loading
- **FlockBatchPolicy:** Complete with all 5 CRUD methods + user ownership enforcement
- **DeathRecord model:** `batch_id` in `$fillable`, `cause` cast to `DeathCause::class` — original epic concern resolved
- **Transactions:** `DB::transaction()` wrapping death record creation + count decrement
- **Legacy compatibility:** `/flock` and `/batches` routes untouched; new `/flock-batches` is additive

### Recommended Fix Order

1. ~~**C1** — Add `batch_id` to `BatchEvent::$fillable` (1 line)~~ ✅ **FIXED 2026-04-17**
2. ~~**C2** — Add enum casts to `FlockBatch::casts()` (2 lines)~~ ✅ **FIXED 2026-04-17** (age_at_acquisition cast added; type remains string — auto-calculated field)
3. ~~**C3** — Fix policy class in `FlockBatchDeathRecordController@index` (1 line)~~ ✅ **FIXED 2026-04-17** (added `authorize('viewAny', DeathRecord::class)`)
4. ~~**C4** — Add `use HandlesHtmx` to `FlockBatchManagerController` (1 line)~~ ✅ **FIXED 2026-04-17**
5. ~~**H1/H2** — Add `viewAny()` + `view()` to `BatchEventPolicy` and `DeathRecordPolicy` (8 lines each)~~ ✅ **FIXED 2026-04-17**
6. ~~**H4** — Remove `deriveFlockType()` from controller, use `FlockBatch::resolveType()` (delete + 1 line change)~~ ✅ **FIXED 2026-04-17**
7. ~~**M8** — Bulk replace `RefreshDatabase` → `LazilyRefreshDatabase` in 10+ test files~~ ✅ **FIXED 2026-04-17** (11 flock-related test files updated)
8. ~~**M1** — Update factories to use enum instances~~ ✅ **FIXED 2026-04-17** (all 3 factories)
9. Write missing tests per gap analysis above — **TODO: remaining work**

---

## Open Questions (Product / Design — non-blocking for Story 1)

1. ~~**FlockProfile cleanup** — after the epic ships, do we drop the legacy `/flock` route entirely, or keep it as an archive view?~~ **RESOLVED 2026-04-18:** `/flock` and `/batches` stay as the canonical separate pages; `/flock-batches` is retired. See Story 6.
2. **Event types extension** — React has `brooding_start` / `brooding_stop` as explicit events; does logging these actually adjust `brooding_count` on the batch, or are they purely informational?
3. **Soft deletes** — batches and death records: soft or hard? (Current: hard.)
4. **Batch archival** — existing `is_active` flag gives an archived state. Should the Batches tab filter show active only, with a secondary toggle?
5. **Cost tracking** — batch cost shown on detail. Should it feed into the Expenses page automatically (auto-create an Expense row on batch creation with category "Birds")?
