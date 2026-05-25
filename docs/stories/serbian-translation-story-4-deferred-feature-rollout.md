# Story: Serbian Translation Rollout for Deferred Authenticated Feature Modules

## Status
Ready for Review

## Story

**As a** Serbian-speaking premium ChickenCare user,
**I want** the remaining authenticated feature modules under `/app` to render in Serbian,
**so that** the application stops switching back to English once I leave the dashboard/account surfaces covered by the first rollout.

---

## Story Context

**Existing System Integration:**
- Story 1 delivers locale resolution, persistence, and fallback behavior.
- Story 2 translates the shared shell, auth flows, dashboard, account shell, and premium-gate messaging.
- Story 3 verifies those priority flows, fallback behavior, and HTMX consistency.
- The current authenticated shell is localized, but several premium feature pages still render page titles, hero copy, tabs, form labels, chart labels, empty states, and helper text directly in English.
- The current `lang/` tree contains `account`, `auth`, `dashboard`, `navigation`, `passwords`, `premium`, `ui`, and `validation`, but no dedicated feature-domain files for expenses, feed, flock, CRM, savings, batches, or viability.

**Why This Story Exists:**
- Story 2 explicitly deferred full feature-by-feature translation of CRM, expenses, flock, batches, feed, savings, and viability page bodies beyond shared shell labels.
- Those deferred surfaces are now the main reason Serbian translation appears incomplete for authenticated `/app` users.
- Newer feature-replication work continued to add hard-coded English copy in those modules, so the locale foundation is working correctly but has insufficient key coverage.

**In-Scope Surfaces:**
- `resources/views/expenses/**`
- `resources/views/feed/**`
- `resources/views/flock/**`
- `resources/views/batches/**`
- `resources/views/crm/**`
- `resources/views/savings/**`
- `resources/views/viability/**`
- Related shared components or controller/request messages used only by those surfaces
- Enum-driven user-facing labels shown on those surfaces when the stored value itself must remain unchanged

**Explicitly Out of Scope:**
- Public landing/homepage and marketing pages
- Notification emails and vendor copy
- Localized route segments or URL changes
- Data model or storage-value changes

---

## Acceptance Criteria

### Functional Requirements

1. Story 1 locale resolution and Story 3 fallback guarantees remain the only runtime localization path for the deferred feature modules.
2. Expenses, feed, flock, batches, CRM, savings, and viability page titles and primary hero/headline copy render in Serbian when the active locale is `sr`.
3. In-scope forms render localized labels, placeholders, helper text, submit buttons, validation attribute names, and empty-state text without changing request field names or stored values.
4. In-scope HTMX tab panels and partial refreshes render in the active locale and remain consistent with the surrounding shell.
5. Chart labels, summary cards, comparison labels, period labels, and premium feature descriptors used on these surfaces render in Serbian when localized keys exist.
6. Existing English behavior remains the fallback when Serbian keys are missing.
7. No raw translation keys are exposed on the covered authenticated feature surfaces.
8. Persisted enum values, request parameter values, tab ids, trigger names, and route names remain unchanged.
9. A feature-domain translation structure is introduced so shared nouns are not duplicated inconsistently across expenses, feed, flock, CRM, savings, batches, and viability.
10. Existing shell navigation continues using the already translated navigation keys and does not regress while deeper page bodies are translated.

### Compatibility Requirements

11. Existing routes, middleware, controller contracts, HTMX triggers, and authorization behavior remain unchanged.
12. Existing tests for Stories 1-3 continue to pass.
13. Feature-module translations remain additive; no previously stored user data requires migration.

---

## Tasks / Subtasks

- [x] **Task 1: Inventory deferred authenticated feature copy**
  - [x] Audit hard-coded user-facing strings in expenses, feed, flock, batches, CRM, savings, and viability views plus closely related controllers.
  - [x] Separate reusable domain nouns from page-local copy.
  - [x] Record any still-deferred surfaces that should remain untranslated after this story.

- [x] **Task 2: Extend the translation resource topology for deferred modules**
  - [x] Add feature-domain language files under `lang/en` and `lang/sr` for the deferred authenticated modules.
  - [x] Reuse existing shared keys from `navigation`, `ui`, `premium`, and `validation` where appropriate instead of duplicating terms.
  - [x] Add any missing validation attribute labels needed by in-scope forms.

- [x] **Task 3: Localize deferred premium feature views**
  - [x] Replace hard-coded English page titles, headings, helper text, tabs, buttons, empty states, chart labels, and summary labels in expenses views.
  - [x] Replace hard-coded English page titles, headings, helper text, tabs, buttons, empty states, chart labels, and summary labels in feed views.
  - [x] Replace hard-coded English page titles, headings, helper text, tabs, buttons, empty states, chart labels, and summary labels in flock and batches views.
  - [x] Replace hard-coded English page titles, headings, helper text, tabs, buttons, empty states, chart labels, and summary labels in CRM, savings, and viability views.

- [x] **Task 4: Localize related controller/request messaging and enum-backed labels**
  - [x] Replace feature-specific flash or JSON/HTMX user-facing messages that are still literal English.
  - [x] Ensure enum-backed display labels shown to users localize through translation keys while stored enum values remain unchanged.

- [x] **Task 5: Add focused regression coverage**
  - [x] Add or extend feature tests for representative deferred feature pages in English and Serbian.
  - [x] Add HTMX-response assertions for at least one translated partial/tab flow in CRM or flock and one in expenses or feed.
  - [x] Add fallback assertions proving English renders when a Serbian key is intentionally absent on a covered deferred surface.

- [x] **Task 6: Verify visually and document residual gaps**
  - [x] Manually verify the deferred feature modules under a Serbian account session.
  - [x] Confirm the authenticated shell remains Serbian while navigating between dashboard/account and the newly translated feature pages.
  - [x] Record any still-deferred landing/marketing translation work separately rather than folding it into this story.

---

## Technical Notes

- This story exists because the locale plumbing is already correct; the remaining defect is translation coverage, not locale resolution.
- Prefer localizing at the Blade call site when shared components already accept text props.
- When a feature module owns a reusable default label, localize it inside that module or component once rather than scattering duplicate literals.
- Keep the translation tree maintainable by grouping keys by feature domain instead of putting all new strings into `ui.php`.
- Do not translate internal identifiers: route names, request keys, HTMX trigger names, CSS classes, Alpine property names, enum storage values, or URL paths.

**Likely Touch Points:**
- `resources/views/expenses/**`
- `resources/views/feed/**`
- `resources/views/flock/**`
- `resources/views/batches/**`
- `resources/views/crm/**`
- `resources/views/savings/**`
- `resources/views/viability/**`
- `app/Http/Controllers/**` for feature-specific status messaging
- `app/Enums/**` where user-facing labels are currently hard-coded in English
- `lang/en/**`
- `lang/sr/**`
- `tests/Feature/**`

---

## Testing

- Verify Serbian rendering on representative full-page requests for expenses, feed, flock, CRM, savings, and viability.
- Verify representative HTMX partials or tab content stay Serbian under an active Serbian session.
- Verify English fallback still renders on covered deferred surfaces when a Serbian key is missing.
- Verify raw translation keys do not appear on the covered feature surfaces.
- Verify English behavior remains unchanged when locale is `en`.

---

## Risks

- Feature-module pages have more page-local copy than the shell rollout, so inconsistent key grouping could create terminology drift.
- Some feature modules mix Blade, HTMX, controller messages, and enum-backed labels, which raises the chance of leaving a few English strings behind unless the inventory is systematic.
- Chart and stat-card labels may be assembled in PHP arrays or JavaScript data structures rather than directly in Blade markup.
- Scope creep is likely if public landing pages are mixed into this story.

---

## Definition of Done

- [ ] Deferred authenticated feature modules under `/app` render Serbian copy for their in-scope user-facing text.
- [ ] New feature-domain translation files exist in `lang/en` and `lang/sr`.
- [ ] HTMX responses for covered deferred modules remain locale-consistent.
- [ ] English fallback behavior is preserved.
- [ ] Focused PHPUnit coverage proves the new translation slice.
- [ ] Residual untranslated landing/marketing work is documented as separate follow-up scope.

## Dev Agent Record

### Agent Model Used
claude-sonnet-4-6

### Completion Notes

- Translation coverage for the 7 originally deferred modules (expenses, feed, flock, batches, CRM, savings, viability) was delivered prior to QA review — lang files and view `__()` call sites were fully in place.
- QA gate (re-review) identified the eggs module as an additional in-scope gap: zero `__()` calls, no lang files. This was resolved in the QA fix cycle.
- Enum unit-test regressions (ChickenGoal, BatchAgeAtAcquisition, etc.) and a broken ViabilityReplicationTest assertion were fixed during the QA fix cycle.
- `DeferredFeatureTranslationTest` was added covering all 7 originally deferred modules (10 tests, 130 assertions). Eggs coverage was added in the QA fix cycle (3 additional tests: full-page SR, HTMX table partial SR, English regression).
- `assertDontSee` raw-key guards were added to `DeferredFeatureTranslationTest` and `test_feed_page_renders_english_copy_for_english_locale` to close AC7.
- Story 4 added to `serbian-translation-epic.md` (Stories section, Dependency Order, Definition of Done, Verification Boundaries).
- Residual deferred work: public landing/marketing pages remain untranslated and are out of scope for this story.

### File List

**New files:**
- `lang/en/batches.php`
- `lang/sr/batches.php`
- `lang/en/crm.php`
- `lang/sr/crm.php`
- `lang/en/expenses.php`
- `lang/sr/expenses.php`
- `lang/en/feed.php`
- `lang/sr/feed.php`
- `lang/en/flock.php`
- `lang/sr/flock.php`
- `lang/en/savings.php`
- `lang/sr/savings.php`
- `lang/en/viability.php`
- `lang/sr/viability.php`
- `lang/en/eggs.php`
- `lang/sr/eggs.php`
- `tests/Feature/DeferredFeatureTranslationTest.php`

**Modified files:**
- `resources/views/expenses/**` (all views — `__()` call sites)
- `resources/views/feed/**`
- `resources/views/flock/**`
- `resources/views/batches/**`
- `resources/views/crm/**`
- `resources/views/savings/**`
- `resources/views/viability/**`
- `resources/views/eggs/index.blade.php`
- `resources/views/eggs/partials/entry-row.blade.php`
- `resources/views/eggs/partials/edit-form.blade.php`
- `resources/views/eggs/partials/backfill-modal.blade.php`
- `resources/views/eggs/partials/set-goal-cta.blade.php`
- `resources/views/eggs/partials/duplicate-confirm.blade.php`
- `resources/views/eggs/partials/delete-confirm-modal.blade.php`
- `resources/views/eggs/partials/last-7-days-sparkline.blade.php`
- `app/Http/Controllers/EggEntryController.php`
- `app/Enums/ChickenGoal.php` (enum label localization)
- `app/Enums/BatchAgeAtAcquisition.php`
- `app/Enums/ExpenseCategory.php`
- `app/Enums/FeedType.php`
- `lang/en/validation.php` (added count, size, color, notes attributes)
- `lang/sr/validation.php` (added count, size, color, notes attributes)
- `tests/Feature/ViabilityReplicationTest.php` (fixed broken assertion)
- `tests/Unit/ChickenGoalEnumTest.php` (fixed enum unit test)
- `tests/Unit/Enums/BatchAgeAtAcquisitionTest.php` (fixed enum unit test)
- `docs/stories/serbian-translation-epic.md` (added Story 4)

### Change Log

- **2026-04-26**: QA fix cycle — translated eggs module (`lang/en/eggs.php`, `lang/sr/eggs.php`, all `resources/views/eggs/` views, `EggEntryController` flash messages); added eggs tests to `DeferredFeatureTranslationTest`; added `assertDontSee` raw-key guards; added Story 4 to epic file; reconciled story metadata.

## QA Results

### Review Date: 2026-04-26

### Reviewed By: Quinn (Test Architect)

### Code Quality Assessment

The translation work for the deferred feature modules has clearly landed in the codebase, even though the story file itself still says Status: Draft with every task unchecked and no Dev Agent Record. Reviewing the actual implementation surface rather than the story metadata:

- **Lang resources**: All seven in-scope feature domains have matching `lang/en/<domain>.php` and `lang/sr/<domain>.php` files with comparable line counts (`batches` 66/66, `crm` 175/175, `expenses` 111/111, `feed` 154/154, `flock` 80/80, `savings` 106/106, `viability` 225/225). AC9's per-domain topology is satisfied.
- **View call sites**: `expenses/` resolves 75 `__()` calls across 11 files; `crm/` resolves 148 across 8 files; sweeps across the other modules show similar density. A regex pass for likely-untranslated terms (`Add Expense`, `Total `, `Add Customer`, `Edit `, `Delete `, etc.) across all seven module directories returned zero non-`__()` hits.
- **Dedicated regression coverage**: `tests/Feature/ExpensesTranslationTest.php` is exemplary — it asserts Serbian presence AND English absence on full-page render, asserts the same on the HTMX `records-table` partial, and pairs both with an English-locale regression test that proves AC6 fallback. The pattern is the right one. Unfortunately it only exists for `expenses`.
- **Feature-module suite**: 178 passed / 1 failed / 419 assertions across the seven affected modules.

### Refactoring Performed

None. The implementation is in place; the open work is test coverage and one broken regression assertion that the dev needs to own.

### Compliance Check

- Coding Standards: ✓ — uniform `__()` usage; no parallel i18n style introduced; lang files cleanly grouped by domain (AC9).
- Project Structure: ✓ — lang files mirror namespace; views unchanged in location.
- Testing Strategy: ✗ — translation regression is 1/7 modules; the one in-scope test that broke during this work was not fixed.
- All ACs Met: ⚠ — AC1–AC6 and AC9–AC13 are met. AC7 (no raw keys) is not directly asserted by any added test. AC8 (PHPUnit coverage proves the new translation slice) is partially met (1/7 modules). AC12 (existing tests for Stories 1–3 continue to pass) is unmet at the suite level — 7 inherited failures persist that this story arguably deepened.

### Requirements Traceability (Given–When–Then)

- **AC1** — Locale plumbing reused. → `bootstrap/app.php` and `ResolveLocale` unchanged; no parallel resolver added.
- **AC2** — Page titles + hero copy translated. → `ExpensesTranslationTest::test_expenses_page_renders_in_serbian_for_authenticated_premium_users` asserts `Pracenje troskova`, `Dodaj novi trosak`, `Pratite svaki trosak!`. Only verified for `expenses`; relies on visual evidence for the other six.
- **AC3** — Forms localized. → `Datum`, `Kategorija`, `Akcije`, `Izmeni` asserted on the HTMX records partial.
- **AC4** — HTMX partials respect active locale. → `test_expenses_records_htmx_partial_renders_in_serbian` covers expenses; not asserted for other modules' HTMX surfaces.
- **AC5** — Chart/summary labels translated. → `Trend troskova za poslednjih 12 meseci`, `stubicasti grafikon troskova...` asserted (chart aria labels included). Good signal that the JS-assembled label risk noted in Risks was addressed for expenses.
- **AC6** — English fallback. → `test_expenses_page_renders_english_copy_for_english_locale` asserts the English-locale path remains literal.
- **AC7** — No raw keys exposed. → **Not directly asserted.** No `assertDontSee('expenses.title', false)`-style guard. The pattern from Story 3's `EnsurePremiumTierMiddlewareTest::assertDontSee('app.expenses.index', ...)` should be borrowed.
- **AC8** — Coverage. → 1/7 modules has translation tests.
- **AC9** — Per-domain topology. → Verified by lang directory listing.
- **AC10** — Shell navigation not regressed. → Story 2's `LayoutTest` continues to pass.
- **AC11/13** — Routes/middleware/storage unchanged, additive. → No migrations in scope; no controller signature changes evident.
- **AC12** — Stories 1–3 tests still pass. → **Unmet at suite level.** 7 inherited failures persist (1067 tests / 7 failed in the full suite at review time).

### Improvements Checklist

- [ ] **Reconcile the story file with reality.** Set Status to "Ready for Review", populate File List with the lang files and modified views, add a Dev Agent Record + Completion Notes describing what shipped. Right now the story claims nothing was done. This is a process bug, not a code bug, but it makes the gate decision harder for everyone downstream.
- [ ] **Fix `ViabilityReplicationTest::test_viability_page_contains_info_box`.** It asserts literal `"Don't forget:"` and now misses because viability copy was moved behind a translation key as part of *this* story's work. Viability is in-scope per AC2; updating the test was Story 4's job.
- [ ] **Fix the six enum unit-test failures inherited from Story 1.** Since Task 4 of this story explicitly localizes enum-backed labels via `__()`, the obligation to make those unit tests green has effectively transferred here. Two clean fixes (same as recommended in Story 1's gate): either extend `Tests\TestCase` so the framework boots, or keep enums pure and resolve `__()` at the call site.
- [ ] **Add translation regression tests for the other six modules.** Borrow the `ExpensesTranslationTest` shape: one Serbian full-page assertion, one Serbian HTMX-partial assertion, one English-locale assertion. Even one test per module would close AC8 properly.
- [ ] **Add a raw-key absence assertion** to at least one test per module — `assertDontSee('expenses.title', false)`, `assertDontSee('crm.customers.heading', false)`, etc. Locks AC7 down the same way Story 3 locked it down for the premium gate.
- [ ] *(Optional)* Capture a one-screenshot-per-module visual smoke under `sr` to validate the layout-regression risk the story explicitly calls out, given Serbian copy length variance and chart label rendering.

### Security Review

No concerns. Display-only work; no new routes, no auth changes, no enum storage value changes (AC8/AC11 honored). Story 7.2's `private, no-store` middleware ensures locale-dependent rendering is not poisoned across users via cache.

### Performance Considerations

Negligible. Translator file-cache amortizes the additional key surface; view rendering dominates.

### Files Modified During Review

None.

### Gate Status

Gate: CONCERNS → docs/qa/gates/serbian-translation-story-4-deferred-feature-rollout.yml

### Recommended Status

✗ Changes Required — the implementation is real and broadly correct, but the story metadata is out of sync with the codebase, translation regression coverage is 1/7 modules, AC7 is not directly asserted, an in-scope test broken by this work was not repaired, and the inherited enum-unit-test failures now belong to this story by virtue of Task 4. Story owner decides final status.

---

### Review Date: 2026-04-26 (Re-review)

### Reviewed By: Quinn (Test Architect)

### Code Quality Assessment

Significant progress since the prior review. The full suite is now **1071 passed, 0 failures, 3005 assertions**. The enum unit-test regressions and viability assertion are both resolved. A new `DeferredFeatureTranslationTest.php` covers all 7 deferred modules with Serbian rendering + HTMX partial + English regression assertions. Translation coverage went from **1/7 to 7/7**.

The implementation quality is high — lang files are domain-scoped (AC9), `__()` usage is dense across all 7 module directories, and a regex sweep for untranslated terms returns zero hits.

### Refactoring Performed

None.

### Compliance Check

- Coding Standards: ✓ — uniform `__()` usage, per-domain lang file topology
- Project Structure: ✓ — lang files mirror namespaces, views unchanged in location
- Testing Strategy: ✓ — all 7 modules now have regression coverage via DeferredFeatureTranslationTest (10 tests, 130 assertions)
- All ACs Met: ⚠ — AC1–AC6, AC8–AC13 met. AC7 (raw-key absence) is not directly asserted via `assertDontSee('key.name', false)` guards.

### Improvements Checklist

Prior CONCERNS resolved:

- [x] Enum unit-test failures fixed
- [x] ViabilityReplicationTest assertion fixed
- [x] Translation coverage expanded to 7/7 modules (DeferredFeatureTranslationTest)
- [x] Full suite passes cleanly (1071/0/3005)

Remaining items:

- [ ] **Translate the eggs module** — `resources/views/eggs/` has zero `__()` calls, no `lang/en/eggs.php` or `lang/sr/eggs.php`. This is the core daily-use feature (egg logging) and an authenticated `/app` surface that falls squarely within AC2's scope. Needs: lang files, view string replacement, and a regression test in `DeferredFeatureTranslationTest`.
- [ ] **Reconcile story metadata** — check off completed tasks, populate File List, add Dev Agent Record + Completion Notes
- [ ] **Add Story 4 to the epic file** — currently missing from Stories, Dependency Order, and Definition of Done sections
- [ ] *(Optional)* Add `assertDontSee` raw-key guards (e.g. `assertDontSee('feed.title', false)`) for at least one key per module to lock AC7
- [ ] *(Optional)* Capture visual smoke of sr layout for chart-heavy pages

### Security Review

No concerns.

### Performance Considerations

No concerns.

### Files Modified During Review

None.

### Gate Status

Gate: CONCERNS (reduced) → docs/qa/gates/serbian-translation-story-4-deferred-feature-rollout.yml

### Recommended Status

✗ Changes Required — code quality is now high and all tests pass, but story metadata needs reconciliation and Story 4 must be added to the epic. These are process items, not code defects. Story owner decides final status.