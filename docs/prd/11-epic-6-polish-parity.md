# 11. Epic 6: Polish & Parity

**Epic Goal:** Deliver onboarding, landing page, account settings, and final UX polish to achieve full feature parity with the original app.

**Integration Requirements:** Spans all features. Must not break any existing functionality.

## Story 6.1: Onboarding Wizard

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

## Story 6.2: Landing Page & Public Pages

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

## Story 6.3: Account Settings

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

## Story 6.4: Final Parity Verification & Seed Data

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
