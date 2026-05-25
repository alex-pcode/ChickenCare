# Epic: Serbian Translation Support

## Status
Draft

## Epic Goal

Add Serbian localization support to ChickenCare so Serbian-speaking users can use the existing Laravel + Blade + HTMX application in their language without regressing current English behavior, route structure, or premium/auth flows.

## Epic Description

### Existing System Context

- **Current implementation:** ChickenCare is a Laravel 13 application using Blade, HTMX, Alpine.js, and SCSS for a server-rendered product experience across auth, dashboard, account settings, CRM, flock, feed, savings, and other poultry-management features.
- **Localization surface already present:** `config/app.php` defines `locale` and `fallback_locale`, both app and guest layouts bind `<html lang>` to `app()->getLocale()`, and Breeze/auth flows plus a few controllers already call `__()` / `trans()` for some messages.
- **Current gap:** The repository has no application `lang/` directory, no locale-selection middleware or user preference persistence, and many product-facing strings remain hard-coded in Blade views, controllers, components, and enum-driven UI labels.
- **Integration points:** `config/app.php`, `bootstrap/app.php`, shared layouts, auth views, app feature views, flash/banner messaging, account preferences UX, and test coverage across feature flows that must continue to work in English and Serbian.
- **Technology stack:** PHP 8.3, Laravel 13, Blade, HTMX, Alpine.js, SCSS, Vite, PHPUnit.

### Enhancement Details

**What's being added/changed:**

1. A translation foundation for Serbian (`sr`) with clear fallback behavior to English.
2. A locale selection and persistence mechanism that works for full page requests and HTMX-driven navigation.
3. Serbian translation coverage for authentication, shared UI, dashboard/account/navigation copy, validation/status messaging, and high-frequency app surfaces.
4. Test coverage and regression checks proving existing English behavior remains intact while Serbian copy renders correctly.

**How it integrates:**

- Adds Laravel language resources under a new application `lang/` tree rather than introducing a third-party i18n layer.
- Introduces request-time locale resolution close to the application bootstrap/middleware boundary so existing controllers and Blade views can continue using Laravel translation helpers naturally.
- Extends an existing user-facing preferences surface, likely Account Settings, to expose language choice without altering route structure or auth rules.
- Converts the most visible hard-coded English copy to translation keys in a staged way, prioritizing globally shared layouts and high-traffic screens first.

**Success criteria:**

- Users can switch between English and Serbian and have that choice persist predictably.
- Shared layouts, auth flows, and primary in-app navigation render Serbian copy when Serbian is active.
- Validation, password-reset, and flash/status messages remain understandable in both locales.
- Existing English behavior remains the fallback when keys are missing.
- No route names, database schema expectations, or existing business logic regress because of localization work.

---

## Stories

### Story 1: Localization Foundation and Locale Persistence

Establish the Laravel localization infrastructure for Serbian, including application language files, locale resolution, fallback behavior, and a persistence mechanism that works across full-page and HTMX requests.

### Story 2: Serbian Translation Rollout for Shared UI and Priority Product Flows

Translate the highest-value user-facing surfaces, including auth, shared layouts/navigation, dashboard/account shell copy, banners, and common controller/status strings, by replacing hard-coded strings with translation keys and supplying Serbian resources.

### Story 3: Verification, Fallback Hardening, and Cross-Flow Consistency

Add focused tests and QA coverage to verify Serbian and English rendering, fallback behavior, HTMX interaction continuity, and consistency of translated terminology across connected app areas.

### Story 4: Serbian Translation Rollout for Deferred Authenticated Feature Modules

Translate the remaining authenticated feature module surfaces under `/app` that were explicitly deferred by Story 2 — expenses, feed, flock, batches, CRM, savings, viability, and eggs — so that Serbian-speaking users no longer encounter English copy when navigating beyond the shared shell and dashboard.

---

## Compatibility Requirements

- [ ] Existing route names, URLs, and controller contracts remain unchanged.
- [ ] Database changes, if any, are additive and backward compatible.
- [ ] UI updates follow existing Blade, HTMX, Alpine.js, and SCSS patterns.
- [ ] English remains the default/fallback experience when Serbian translations are unavailable.
- [ ] HTMX partial responses continue to behave the same regardless of active locale.

## Risk Mitigation

- **Primary Risk:** Localization work touches shared layouts and common messaging, so incomplete key migration could create mixed-language screens or broken copy in heavily reused flows.
- **Mitigation:** Sequence the work so locale infrastructure lands first, then migrate shared/global copy before feature-specific copy, and finish with regression tests across auth, dashboard, and account flows.
- **Rollback Plan:** Disable Serbian as a selectable locale and revert the translation-key substitutions and locale middleware/persistence changes while leaving English behavior unchanged.

## Definition of Done

- [ ] All four stories are completed with acceptance criteria met.
- [ ] Serbian can be selected and persists for subsequent requests.
- [ ] English fallback behavior is verified for missing or unmigrated keys.
- [ ] Existing auth, navigation, dashboard, and account flows are regression-tested.
- [ ] Documentation/story artifacts identify the translated surfaces and any intentionally deferred areas.

---

## Story Manager Handoff

Please develop detailed user stories for this brownfield epic. Key considerations:

- This enhancement targets an existing Laravel 13 + Blade + HTMX application.
- Integration points: `config/app.php`, `bootstrap/app.php`, shared layouts, auth views, shared Blade components, account settings/preferences UX, controller flash/status messages, and PHPUnit feature coverage.
- Existing patterns to follow: Laravel translation helpers (`__`, `trans`), Blade/HTMX dual-response behavior, BEM-style SCSS, and additive brownfield changes with strong fallback behavior.
- Critical compatibility requirements: English fallback must remain intact, routes/APIs must not change, and HTMX interactions must keep working under the active locale.
- Each story must include verification that existing English functionality remains intact while Serbian localization is introduced incrementally.

The epic should maintain system integrity while delivering a usable Serbian-language experience for the highest-value user journeys.

## Story Dependency Order

1. **Story 1: Localization Foundation and Locale Persistence**
2. **Story 2: Serbian Translation Rollout for Shared UI and Priority Product Flows**
3. **Story 3: Verification, Fallback Hardening, and Cross-Flow Consistency**
4. **Story 4: Serbian Translation Rollout for Deferred Authenticated Feature Modules**

Story 1 is a hard prerequisite for all subsequent stories. Story 2 depends on the locale contract introduced in Story 1. Story 3 depends on both the runtime contract from Story 1 and the translation coverage delivered by Story 2. Story 4 depends on Stories 1 and 2 (locale plumbing and shared topology); Story 3 is not a hard prerequisite but Story 4 benefits from Story 3's fallback hardening.

---

## Shared Contracts Between Stories

### Contract A: Locale Resolution and Persistence

Owned by Story 1.

Consumers:
- Story 2 relies on the active locale being set correctly before shared layouts, auth views, dashboard partials, and account partials render.
- Story 3 verifies that the contract holds for full-page requests, boosted requests, and HTMX partial requests.

Failure mode if Story 1 is incomplete:
- Story 2 will produce mixed-language output even if translation keys are correct.
- Story 3 will fail across multiple surfaces for reasons unrelated to translation coverage.

### Contract B: Translation Resource Topology

Owned jointly by Story 1 and Story 2.

Story 1 establishes the initial `lang/en` and `lang/sr` structure.
Story 2 expands that structure with real shell, auth, dashboard, account, and premium-surface copy.

Consumer:
- Story 3 uses this topology to validate fallback behavior, missing-key behavior, and terminology consistency.

Failure mode if key ownership is unclear:
- Duplicate keys emerge for the same concept.
- Serbian terminology drifts between shell, dashboard, account, and premium surfaces.

### Contract C: Account Preferences as the Locale-Selection Seam

Owned by Story 1.

Current reality in the codebase:
- Locale selection will most naturally attach to `PATCH /app/account/preferences`.
- `UpdatePreferencesRequest` currently validates `chicken_goal`, `yearly_egg_goal`, and `egg_price` together.
- `AccountController@updatePreferences` currently returns only the `tab-goals` partial for HX requests.

Consumers:
- Story 2 localizes the account shell and the surrounding preferences UI.
- Story 3 verifies locale switching and regression behavior on that exact request path.

Design pressure point:
- A language-only interaction does not cleanly fit the current bundled preferences contract.
- If the implementation keeps the bundled request shape, the language selector must submit compatible payloads.
- If the implementation loosens the request contract, it must remain backward compatible for existing goals/preferences behavior.

### Contract D: HTMX Refresh Strategy After Locale Change

Owned by Story 1, consumed by Stories 2 and 3.

Story 1 must define the behavior when locale changes over HX:
- locale change should trigger a full redirect or full-shell refresh
- non-locale preference updates may keep existing partial-swap behavior if that remains simple

Consumers:
- Story 2 assumes translated shell chrome and account tabs will render under the new locale only after a shell-wide refresh.
- Story 3 verifies that HTMX responses and the surrounding page stay in the same locale.

Failure mode if this is underspecified:
- account tab content changes language while sidebar, navbar, page title, and `html[lang]` remain stale

### Contract E: Terminology Glossary

Owned by Story 2.

Consumer:
- Story 3 turns the glossary into regression assertions and manual QA checks.

Failure mode if the glossary is deferred too long:
- Story 2 ships technically complete translation coverage with inconsistent Serbian wording.
- Story 3 becomes a content-triage story instead of a verification story.

---

## Interaction Risks

### Risk 1: Bundled Preferences Request Causes Locale UX Friction

Because `UpdatePreferencesRequest` currently requires the existing goals/pricing fields, Story 1 cannot treat locale as a completely isolated preference without either:
- submitting the full existing preference payload together with locale, or
- safely refactoring the request/controller contract to support partial preference updates.

This is the strongest cross-story interaction risk because Stories 2 and 3 both assume locale changes are easy to trigger from the account surface.

### Risk 2: Story 2 Localizes Surfaces Before Story 1 Stabilizes HX Behavior

If Story 2 starts replacing shell and account strings before Story 1 finalizes the locale-change HX response behavior, the team can end up debugging mixed-language rendering that is really a refresh problem, not a translation problem.

### Risk 3: Story 3 Becomes a Cleanup Story

If Story 2 does not define a shared glossary and an explicit deferred-scope list, Story 3 will absorb unresolved product-copy decisions instead of focusing on regression safety, fallback behavior, and consistency checks.

---

## Recommended Implementation Sequence

1. Deliver Story 1 through the account/preferences seam and prove locale persistence with one shared shell assertion.
2. Lock the Story 2 glossary before large-scale string migration.
3. Migrate shared shell and account/auth priority strings before deeper dashboard/partial strings.
4. Start Story 3 only after Story 2 has declared its deferred surfaces and key glossary decisions.

---

## Verification Focus by Story Boundary

### Story 1 -> Story 2 Boundary

Must be true before Story 2 begins in earnest:
- active locale is set correctly on full requests
- active locale is set correctly on HX requests
- locale selection persists predictably
- locale-change HX submission triggers a shell-safe refresh path

### Story 2 -> Story 3 Boundary

Must be true before Story 3 closes:
- priority surfaces use shared translation keys consistently
- deferred surfaces are documented
- shell, auth, dashboard, and account rendering are stable under English and Serbian
- missing Serbian keys fall back to English rather than exposing raw key names

### Story 3 -> Story 4 Boundary

Must be true before Story 4 closes:
- Deferred feature modules (expenses, feed, flock, batches, CRM, savings, viability, eggs) render Serbian copy for all in-scope user-facing text.
- Feature-domain translation files exist under `lang/en` and `lang/sr` for each deferred module.
- HTMX responses for covered deferred modules remain locale-consistent.
- English fallback behavior is preserved for all deferred modules.
- Focused PHPUnit coverage exists for each deferred module's translation slice.

---

## Overall Assessment

The three-story breakdown is coherent and low-risk for a brownfield enhancement.

The dependency chain is strict but clean:
- Story 1 establishes runtime behavior
- Story 2 fills the translated user-facing surfaces
- Story 3 hardens and proves the behavior across flows

The only meaningful interaction hotspot is the existing bundled account preferences contract. If that seam is handled carefully in Story 1, the rest of the story set should compose cleanly.