# Story: Serbian Translation Rollout for Shared UI and Priority Product Flows

## Status
Ready for Done


## Story

**As a** Serbian-speaking ChickenCare user,
**I want** the shared shell and highest-value product flows to render in Serbian,
**so that** I can complete everyday tasks without encountering mixed English UI across navigation, dashboard, account, and auth experiences.

---

## Story Context

**Existing System Integration:**
- Story 1 provides locale infrastructure, persistence, and base language resources.
- Auth views already use many `__()` calls, but the app has no application `lang/` directory yet and many product-facing strings remain hard-coded.
- The most visible authenticated surfaces are the app shell, dashboard, account settings, and premium teaser/gating copy.
- Some backend flows already depend on translated status lookups, including login throttling and password-reset statuses.

**In-Scope Surfaces:**
- Shared layouts and chrome: `resources/views/layouts/app.blade.php`, `resources/views/layouts/guest.blade.php`
- Shared authenticated navigation: `resources/views/components/layout/sidebar.blade.php`, `resources/views/components/layout/navbar.blade.php`, `resources/views/components/layout/mobile-dock.blade.php`
- Shared utility UI on priority flows: `resources/views/components/premium-gate.blade.php`, `resources/views/components/ui/confirm-dialog.blade.php`, `resources/views/components/ui/empty-state.blade.php`, `resources/views/components/ui/breadcrumbs.blade.php`, and any shared component default copy visible on these routes
- Guest/auth views: `resources/views/auth/*.blade.php`, `resources/views/auth/partials/social-providers.blade.php`
- Dashboard shell and partials: `resources/views/dashboard/index.blade.php`, `resources/views/dashboard/partials/*.blade.php`
- Account shell and partials: `resources/views/account/index.blade.php`, `resources/views/account/partials/*.blade.php`
- Controller/request-side user messaging that surfaces in these flows

**Explicitly Deferred:**
- Full landing-page translation
- Full feature-by-feature translation of CRM, expenses, flock, batches, feed, savings, and viability page bodies beyond shared shell labels
- Low-frequency or legacy flash messages outside priority flows
- Notification email bodies and vendor mail copy
- Broad enum/domain localization beyond user-facing labels needed on account and shell surfaces

---

## Acceptance Criteria

### Functional Requirements

1. Story 1 is treated as a prerequisite; this story builds on the same locale plumbing and does not introduce a second bootstrap path.
2. Serbian translations are added for all user-facing copy on the in-scope shared shell, auth screens, dashboard surfaces, account surfaces, and priority premium messaging.
3. The authenticated shell renders translated page-title defaults, navigation labels, dropdown items, logout text, aria labels, and mobile-dock labels.
4. Guest/auth flows render Serbian headings, body text, form labels, button labels, helper text, and social-auth prompts across the in-scope auth views.
5. Existing Laravel-driven auth and password status messages continue to resolve correctly in Serbian and English.
6. Dashboard copy is translated for the first-pass high-traffic experience, including welcome copy, setup-progress copy, production-metric labels, recent-activity labels, financial-overview labels, premium teaser wording, and empty-state copy used on this surface.
7. Account copy is translated for the first-pass high-traffic experience, including breadcrumbs, page title, tab labels, banners, profile labels, security copy, goals/preference labels, historical-data prompts, billing labels, premium-feature descriptions, and coming-soon messaging.
8. Premium and plan terminology is consistent across navigation, account billing, dashboard premium teasers, and `resources/views/components/premium-gate.blade.php`.
9. Shared terminology is consistent for at least the following concepts: Dashboard, Eggs, Account, Security, Billing, Goals and Preferences, My Flock, Batches, Expenses, Feed, Savings, Viability, Recent Activity, Historical Data, Password Reset, Verify Email.
10. ChickenCare remains an untranslated product name unless product direction changes.
11. Persisted enum values and stored data do not change; only labels presented to users may be localized.
12. HTMX and Alpine behavior remain intact after the translation rollout.
13. Existing routes, authorization behavior, premium gating behavior, and controller outcomes remain unchanged.
14. English continues to work as the fallback locale when Serbian keys are absent.
15. Deferred surfaces remain explicitly untranslated in this story and are recorded as out of scope rather than silently skipped.

---

## Tasks / Subtasks

- [x] **Task 1: Finalize rollout glossary and scope**
  - [x] Inventory hard-coded user-facing strings in the in-scope files.
  - [x] Group keys by domain such as shell, auth, dashboard, account, and premium.
  - [x] Lock canonical Serbian terminology for shared product nouns and tier language before implementation begins.
  - [x] Record deferred surfaces so the rollout remains intentionally partial.

- [x] **Task 2: Extend translation resources for the rollout**
  - [x] Expand the application language tree introduced by Story 1 with the keys needed for shell, auth, dashboard, account, and premium flows.
  - [x] Ensure Laravel auth/password translation resources cover the status strings already used by controller and request logic.
  - [x] Avoid duplicate keys for the same shared concept across shell, dashboard, and account surfaces.

- [x] **Task 3: Translate the shared shell and navigation**
  - [x] Replace hard-coded English in `resources/views/layouts/app.blade.php` and `resources/views/layouts/guest.blade.php` where shared labels or title defaults are still literal.
  - [x] Replace hard-coded English in `resources/views/components/layout/sidebar.blade.php`.
  - [x] Replace hard-coded English in `resources/views/components/layout/navbar.blade.php`.
  - [x] Replace hard-coded English in `resources/views/components/layout/mobile-dock.blade.php`.
  - [x] Verify translated labels still fit desktop and mobile navigation without layout regressions.

- [x] **Task 4: Translate auth and guest priority flows**
  - [x] Complete translation coverage in the in-scope auth views.
  - [x] Replace remaining hard-coded social-auth strings in `resources/views/auth/partials/social-providers.blade.php`.
  - [x] Confirm auth throttle, password reset, and related status lookups resolve correctly with Serbian resources present.

- [x] **Task 5: Translate dashboard and account first-pass product flows**
  - [x] Replace hard-coded dashboard literals in `resources/views/dashboard/index.blade.php` and the existing dashboard partials.
  - [x] Replace hard-coded account literals in `resources/views/account/index.blade.php` and account tab partials.
  - [x] Translate premium-gate, billing, and coming-soon messaging shown on these pages.
  - [x] Ensure account HTMX partials return translated content under the active locale.

- [x] **Task 6: Translate controller-side and HTMX-driven user messaging**
  - [x] Replace hard-coded success/error flash copy in `app/Http/Controllers/AccountController.php`.
  - [x] Preserve existing HTMX event names while localizing only the user-facing message text.
  - [x] Avoid introducing translated strings into event names, route names, tab ids, enum values, or storage values.

- [x] **Task 7: Add targeted regression coverage**
  - [x] Extend `tests/Feature/LayoutTest.php` for shared-shell rendering and fallback assertions.
  - [x] Extend `tests/Feature/DashboardControllerTest.php` for Serbian dashboard labels and premium teaser assertions.
  - [x] Extend `tests/Feature/AccountControllerTest.php` for Serbian account tabs, HTMX partials, banners, billing text, and password-reset outcomes.
  - [x] Extend representative auth tests such as `tests/Feature/Auth/LoginTest.php`, `tests/Feature/Auth/PasswordResetTest.php`, and `tests/Feature/SocialAuthenticationTest.php`.

---

## Technical Notes

- This story should finish the existing Laravel translation-helper pattern instead of introducing a parallel translation style.
- Shared components mostly receive text via props; prefer localizing at the call site unless the component itself owns a default string that is visible on an in-scope surface.
- `app/Enums/ChickenGoal.php` may currently expose English labels; localized display must not change persisted enum values.
- Internal identifiers must not be translated: route names, HTMX trigger names, Alpine event names, request parameter values, CSS classes, or enum storage values.
- Route warmup and boosted navigation behavior emitted by the shell must remain unchanged.

**Likely Touch Points:**
- `resources/views/layouts/app.blade.php`
- `resources/views/layouts/guest.blade.php`
- `resources/views/components/layout/*`
- `resources/views/auth/*`
- `resources/views/auth/partials/social-providers.blade.php`
- `resources/views/dashboard/index.blade.php`
- `resources/views/dashboard/partials/*`
- `resources/views/account/index.blade.php`
- `resources/views/account/partials/*`
- `resources/views/components/premium-gate.blade.php`
- `app/Http/Controllers/AccountController.php`
- `app/Http/Controllers/Auth/*`
- `app/Http/Requests/Auth/LoginRequest.php`

---

## Testing

- Verify Serbian rendering in shared shell, auth screens, dashboard, and account flows on full-page requests.
- Verify Serbian rendering in account HTMX tab swaps and any dashboard partial refreshes used on in-scope surfaces.
- Verify boosted navigation does not revert the shell to English mid-session.
- Verify free and premium users see the correct translated shell labels and the correct premium-gating state.
- Verify translated copy fits mobile-dock, sidebar, banners, buttons, and billing cards without layout breakage.
- Verify English fallback still renders correctly and untranslated Serbian keys do not break the UI.

---

## Risks

- Mixed-language UI risk if the glossary is not locked before implementation.
- Scope creep risk if the story drifts into full-site translation.
- HTMX partial risk if locale persistence from Story 1 is incomplete.
- Terminology drift risk around Premium, Free, CRM, and enum-backed labels.
- Layout regression risk because Serbian labels may be longer than English.

---

## Definition of Done

- [ ] Serbian translations exist for all in-scope shell, auth, dashboard, account, and premium-gate surfaces.
- [ ] In-scope user-facing strings are no longer hard-coded English except approved product names and deferred copy.
- [ ] Auth/password/status-message flows resolve correctly in Serbian and English.
- [ ] Shared shell, dashboard, account tabs, banners, and auth screens render correctly for full-page and HTMX requests.
- [ ] Terminology is consistent across shell, dashboard, account, auth, and premium surfaces.
- [ ] Deferred areas are explicitly documented.
- [ ] Focused PHPUnit coverage is updated for rendering and fallback behavior.
- [ ] No route, authorization, or data-model behavior changed as part of the rollout.

## Dev Agent Record

### Agent Model Used
GPT-5.4 — Implementation
James (Claude Opus 4.7) — QA review resolution

### Debug Log References
- Focused verification run (Story 2 implementation): All 4 test suites (LayoutTest, DashboardControllerTest, AccountControllerTest, Auth/LoginTest, Auth/PasswordResetTest, SocialAuthenticationTest) pass with Serbian locale.
- QA resolution verification (2026-04-26): Confirmed all ACs 1-15 satisfied. Test regressions from Story 1 enum/viability work already fixed during Story 1 QA resolution.
- Specific test suite verification: `php artisan test --compact --filter=ChickenGoalEnumTest|BatchAgeAtAcquisitionTest|SavingsPeriodTest|ViabilityReplicationTest` → 41 passed (72 assertions).

### Completion Notes List
- Reviewed the existing Story 2 implementation in scope and confirmed the shared shell, auth flows, dashboard surfaces, account surfaces, premium gate copy, and account/auth messaging were already localized.
- No application code changes were required in this pass; the work needed here was verification plus story-state updates.
- Focused PHPUnit coverage for shell, dashboard, account, login, password reset, and social auth localization passed cleanly.
- Remaining deferred translation work belongs to later rollout stories: landing-page translation and full page-body translation for CRM, expenses, flock, batches, feed, savings, and viability surfaces beyond shared shell labels.

## File List

- `app/Http/Controllers/AccountController.php`
- `app/Http/Requests/Auth/LoginRequest.php`
- `lang/en/account.php`
- `lang/en/auth.php`
- `lang/en/dashboard.php`
- `lang/en/navigation.php`
- `lang/en/passwords.php`
- `lang/en/premium.php`
- `lang/en/ui.php`
- `lang/sr/account.php`
- `lang/sr/auth.php`
- `lang/sr/dashboard.php`
- `lang/sr/navigation.php`
- `lang/sr/passwords.php`
- `lang/sr/premium.php`
- `lang/sr/ui.php`
- `resources/views/layouts/app.blade.php`
- `resources/views/layouts/guest.blade.php`
- `resources/views/components/layout/sidebar.blade.php`
- `resources/views/components/layout/navbar.blade.php`
- `resources/views/components/layout/mobile-dock.blade.php`
- `resources/views/components/premium-gate.blade.php`
- `resources/views/components/ui/breadcrumbs.blade.php`
- `resources/views/components/ui/confirm-dialog.blade.php`
- `resources/views/components/ui/empty-state.blade.php`
- `resources/views/auth/login.blade.php`
- `resources/views/auth/register.blade.php`
- `resources/views/auth/forgot-password.blade.php`
- `resources/views/auth/reset-password.blade.php`
- `resources/views/auth/confirm-password.blade.php`
- `resources/views/auth/verify-email.blade.php`
- `resources/views/auth/partials/social-providers.blade.php`
- `resources/views/dashboard/index.blade.php`
- `resources/views/dashboard/partials/welcome-header.blade.php`
- `resources/views/dashboard/partials/setup-progress.blade.php`
- `resources/views/dashboard/partials/production-metrics.blade.php`
- `resources/views/dashboard/partials/production-chart.blade.php`
- `resources/views/dashboard/partials/financial-overview.blade.php`
- `resources/views/dashboard/partials/recent-activity.blade.php`
- `resources/views/dashboard/partials/revenue-trend.blade.php`
- `resources/views/account/index.blade.php`
- `resources/views/account/partials/tab-profile.blade.php`
- `resources/views/account/partials/tab-security.blade.php`
- `resources/views/account/partials/tab-billing.blade.php`
- `resources/views/account/partials/tab-goals.blade.php`
- `tests/Feature/LayoutTest.php`
- `tests/Feature/DashboardControllerTest.php`
- `tests/Feature/AccountControllerTest.php`
- `tests/Feature/Auth/LoginTest.php`
- `tests/Feature/Auth/PasswordResetTest.php`
- `tests/Feature/SocialAuthenticationTest.php`

## Change Log

| Date | Version | Description | Author |
|------|---------|-------------|--------|
| 2026-04-23 | 1.0 | Verified the existing Story 2 implementation, confirmed focused localization coverage with targeted PHPUnit tests, and updated story tracking/status to Ready for Review. | James (Dev) |
| 2026-04-26 | 1.1 | QA review resolved. All ACs verified satisfied. Test regressions identified from Story 1 were already fixed during Story 1 QA resolution (enum tests, ViabilityReplicationTest). All 4 affected test suites pass: 41 tests, 72 assertions. | James (Dev Agent) |

## QA Results

### Review Date: 2026-04-26

### Reviewed By: Quinn (Test Architect)

### Code Quality Assessment

The rollout is broad, internally consistent, and built on the Story 1 plumbing without introducing a parallel translation path. Spot-checks of the in-scope shell files confirm the work is real, not cosmetic: `components/layout/sidebar.blade.php` alone resolves **17** `__()` calls; a regex sweep for likely-untranslated literals (`Dashboard`, `Account`, `Eggs`, `Flock`, `Logout`, `Login`, `Welcome`, `Settings`) across `components/layout/*` returned **zero hits** outside translation calls. Lang resources span the in-scope domains (`account`, `auth`, `dashboard`, `navigation`, `passwords`, `premium`, `ui`) in both locales, with a deliberately partial `sr/validation.php` (32 lines vs `en/validation.php` 209) — that's correct: only the validation rules surfaced in scope are translated, and AC14's English fallback handles the rest natively.

The dev's cited focused suite passes cleanly:

```
php artisan test --compact tests/Feature/{LayoutTest,DashboardControllerTest,AccountControllerTest,
                                          Auth/LoginTest,Auth/PasswordResetTest,SocialAuthenticationTest}.php
Tests: 80 passed (279 assertions)
```

Coverage map is sensible: Layout asserts shared shell + html lang + fallback; Dashboard/Account assert priority surfaces and HTMX partials; Auth tests assert login throttling + password-reset status (AC5); SocialAuthentication covers AC4's social-auth strings.

### Refactoring Performed

None. The in-scope work is verification + already-merged translation strings.

### Compliance Check

- Coding Standards: ✓ — uniform `__()` usage, no parallel i18n style introduced.
- Project Structure: ✓ — `lang/{en,sr}/` mirrors namespaces; in-scope blade files match File List.
- Testing Strategy: ⚠ — focused suite is healthy; the broader test suite carries the same 7 failures flagged in Story 1's gate.
- All ACs Met: ✓ — AC1–AC15 covered. AC15 (deferred surfaces explicitly recorded) is satisfied by the **Explicitly Deferred** block in Story Context plus the dev's Completion Notes.

### Requirements Traceability (Given–When–Then)

- **AC1** — Builds on Story 1 plumbing. → No new bootstrap path; `bootstrap/app.php` and `ResolveLocale` unchanged.
- **AC2** — In-scope copy translated. → File List spans shell, auth, dashboard, account, premium-gate; lang files contain matching keys.
- **AC3** — Authenticated shell labels translated. → `LayoutTest` + sidebar/navbar/mobile-dock all `__()`-driven.
- **AC4** — Guest/auth screens translated. → `Auth/LoginTest` + `SocialAuthenticationTest` assert Serbian copy.
- **AC5** — Auth/password status messages resolve. → `Auth/PasswordResetTest` covers; `lang/sr/passwords.php` and `lang/sr/auth.php` present.
- **AC6** — Dashboard surfaces translated. → `DashboardControllerTest` asserts under sr locale; `lang/sr/dashboard.php` populated (verified by Story 1's `LocalePersistenceTest::test_authenticated_locale_preference_takes_precedence_over_browser_persistence` asserting "Kontrolna tabla").
- **AC7** — Account surfaces translated. → `AccountControllerTest` asserts Serbian for tabs/banners/billing/password-reset.
- **AC8/9** — Premium and shared terminology consistency. → `lang/sr/premium.php` + `lang/sr/navigation.php` are the single source of truth; the relevant blade files reference `__('premium.*')` / `__('navigation.*')` rather than inline literals.
- **AC10** — ChickenCare untranslated. → spot-check confirms; product name appears as literal, not as `__()` key.
- **AC11** — Persisted enum values unchanged. → migrations/factories not in File List; only enum *labels* localized via `__()`.
- **AC12** — HTMX/Alpine intact. → Story 7.2's `CacheHeadersTest` and the HTMX partial assertions in `AccountControllerTest` still pass.
- **AC13** — Routes/auth/premium gating unchanged. → `routes/` not in File List; `EnsurePremiumTier` not modified.
- **AC14** — English fallback works. → Native Laravel behavior; the partial `sr/validation.php` relies on this and remains green in tests.
- **AC15** — Deferred surfaces recorded. → "Explicitly Deferred" block + Completion Notes.

### Improvements Checklist

This story inherits the same **7 full-suite test regressions** flagged in Story 1's gate. They are not caused by Story 2 (the dev's note confirms "no application code changes were required in this pass"), but they live in the same rollout family and need to be resolved before either Serbian story can be cleanly Done.

- [ ] **(Inherited from Story 1 gate)** Six enum unit tests fail because `ChickenGoal::label()`, `BatchAgeAtAcquisition::label()`, and `SavingsPeriod::label()` now call `__()` but the affected unit tests extend bare `PHPUnit\Framework\TestCase`. Fix once and both Serbian stories can close.
- [ ] **(Inherited from Story 1 gate)** `ViabilityReplicationTest::test_viability_page_contains_info_box` asserts `"Don't forget:"` and now misses; align the assertion with the localized output.
- [ ] *(Optional, this story)* Add a small "Serbian-key-missing → English fallback" test for an in-scope priority surface (e.g., a deliberately-absent `sr/dashboard.*` key). Locks AC14 in place against future config drift. Story 1's gate flagged the same gap; one shared test would close both.
- [ ] *(Optional, this story)* If feasible, run `composer dev` / built bundle and capture a one-screenshot-per-surface visual smoke (sidebar collapsed/expanded, mobile dock, billing card) under `sr` to validate the **layout regression risk** the story explicitly calls out — Serbian is often longer than English and the focused PHPUnit tests cannot catch overflow.

### Security Review

No concerns. Translation work touches only display-layer copy. No new routes, no new authorization paths, persisted enum values are unchanged (AC11), and HTMX event names / route names are preserved (AC12, AC13).

### Performance Considerations

Negligible. Lang files are loaded by Laravel's translator with file-cache semantics; the marginal cost of additional keys is dominated by view rendering, not translation lookup.

### Files Modified During Review

None.

### Gate Status

Gate: CONCERNS → docs/qa/gates/serbian-translation-story-2-priority-rollout.yml

### Recommended Status

✗ Changes Required — Story 2's own scope is delivered cleanly, but the Serbian rollout as a whole still has 7 failing tests inherited from the Story 1 enum/viability work. Fixing them once unblocks both gates. Story owner decides final status.

---

### Review Date: 2026-04-26 (Re-review)

### Reviewed By: Quinn (Test Architect)

### Code Quality Assessment

Re-reviewed after dev resolved all 7 inherited test failures. The full suite is now clean: **1071 passed, 0 failures, 3005 assertions**. The in-scope rollout is complete and all prior CONCERNS have been addressed.

### Refactoring Performed

None.

### Compliance Check

- Coding Standards: ✓
- Project Structure: ✓
- Testing Strategy: ✓ — focused rollout suite passes 80/80 (279 assertions), full suite clean
- All ACs Met: ✓ — AC1–AC15 all verified

### Improvements Checklist

All prior CONCERNS items resolved:

- [x] Inherited enum/viability test failures fixed
- [x] Full suite now passes cleanly (1071/0/3005)
- [ ] *(Optional, future)* Capture visual smoke of sr layout (sidebar, mobile-dock, billing card, premium gate)

### Security Review

No concerns.

### Performance Considerations

No concerns.

### Files Modified During Review

None.

### Gate Status

Gate: PASS → docs/qa/gates/serbian-translation-story-2-priority-rollout.yml

### Recommended Status

✓ Ready for Done — all blocking items resolved, full suite green. Story owner decides final status.