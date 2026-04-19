# Epic: Account Settings - Complete Feature Replication

## Implementation Status

| Story | Title | Status | Date |
|-------|-------|--------|------|
| 1 | Account Settings Shell — Route, Breadcrumbs, Tab Navigation, Banners | ✅ Complete | 2026-04-18 |
| 2 | Profile Tab — Personal Information & Appearance | ✅ Complete | 2026-04-18 |
| 3 | Security Tab — Status Card & Password Reset Confirm Dialog | ✅ Complete | 2026-04-18 |
| 4 | Billing Tab — Current Plan & Premium Features | ✅ Complete | 2026-04-18 |
| 5 | Goals & Preferences Tab — Chicken Goal, Production Goal, Pricing, Historical Data | ✅ Complete | 2026-04-18 |

### Implementation Summary

All five stories have been implemented and verified:

**Files Created/Modified:**
- `app/Http/Controllers/AccountController.php` — 4 actions: `index`, `updateProfile`, `updatePreferences`, `sendPasswordResetLink`
- `app/Http/Requests/UpdateProfileRequest.php` — validates `name` (required, string, max:255)
- `app/Http/Requests/UpdatePreferencesRequest.php` — validates `chicken_goal`, `yearly_egg_goal`, `egg_price`
- `resources/views/account/index.blade.php` — main layout with breadcrumbs, tabs (Alpine-driven), banners
- `resources/views/account/partials/tab-profile.blade.php` — personal info form + theme toggle
- `resources/views/account/partials/tab-security.blade.php` — security status card + password reset with confirm dialog
- `resources/views/account/partials/tab-billing.blade.php` — plan card + premium features list + upgrade CTA
- `resources/views/account/partials/tab-goals.blade.php` — chicken goals, production goals, pricing, historical data
- `resources/views/components/ui/confirm-dialog.blade.php` — reusable Alpine-driven modal
- `resources/views/components/ui/breadcrumbs.blade.php` — breadcrumb nav component
- `resources/views/components/ui/theme-toggle.blade.php` — three-option theme switcher (cookie-based)
- `resources/scss/features/_account.scss` — complete BEM-style SCSS with dark mode support
- `routes/web.php` — 4 new routes under `/app/account`
- `tests/Feature/AccountControllerTest.php` — 23 tests covering all stories

**Verification:**
- All 23 tests pass (57 assertions)
- Laravel Pint formatting passes
- SCSS compiles successfully via Vite
- Visual inspection in Chrome (1366x768) confirmed all four tabs render correctly in light mode
- Dark mode confirmed working
- HTMX partial swaps work correctly with `hx-push-url` for browser history
- Alpine.js tab state management works for active tab highlighting
- Sidebar "Account" link correctly appears in the free tier section

## Epic Goal

Replicate the React `ProfilePage` component exactly in Laravel + HTMX + Blade to achieve 100% feature parity with the original application at `d:\Koke\Aplikacija\src\components\features\profile\ProfilePage.tsx`, delivering a four-tab **Account Settings** experience (Profile / Security / Billing / Goals & Preferences). The sibling `Profile.tsx` component (Flock Profile + Events Timeline) is already covered by the existing `/app/flock` page — this epic is explicitly scoped to the account/settings surface.

## Epic Description

### Existing System Context

- **Current Implementation:** Laravel 13 + HTMX + Blade has NO dedicated account/settings page. Users have no in-app UI to edit their display name, reset their password from the app, view their subscription tier, configure the egg price, choose a chicken goal (hobby/business), set a yearly egg goal, or import historical egg data. The `/app/flock` page already replicates the React `Profile.tsx` (Flock Overview + Events Timeline + Batch Management promo) and is NOT in scope here.
- **Reference Implementation:**
  - `d:\Koke\Aplikacija\src\components\features\profile\ProfilePage.tsx` — four-tab account settings page
  - `d:\Koke\Aplikacija\src\components\ui\modals\ConfirmDialog.tsx` — password-reset confirmation dialog
  - `d:\Koke\Aplikacija\src\components\ui\modals\HistoricalEggTrackingModal.tsx` — historical egg backfill modal
  - `d:\Koke\Aplikacija\src\components\ui\Breadcrumbs.tsx` — breadcrumb nav
  - `d:\Koke\Aplikacija\src\components\ui\TabNavigation.tsx` — tab nav
  - `d:\Koke\Aplikacija\src\components\ui\forms\ThemeToggle.tsx` — theme toggle
- **Technology Stack:** Laravel 13, HTMX, Alpine.js, Blade, MariaDB 10.6.22, **pure CSS/SCSS (no Tailwind)**, Chart.js (unused by this epic)
- **Integration Points:** `User` model already has `name`, `email`, `tier`, `is_admin`, `yearly_egg_goal`, `egg_price`, `chicken_goal` (ChickenGoal enum) — **no new columns are required for this epic**. Existing auth scaffolding (`Auth\PasswordResetLinkController`, `Auth\NewPasswordController`) is reused for the password-reset flow. Existing `<x-ui.stat-card>`, `<x-forms.form-card>`, `<x-forms.input>`, `<x-forms.select>`, `<x-forms.submit-button>`, `<x-ui.empty-state>`, `<x-premium-gate>`, `<x-ui.progress-card>` Blade components. Existing `eggs/partials/backfill-modal.blade.php` is reused for the "Import Historical Data" flow.

### Enhancement Details

**What's Being Added/Changed:**

1. **New Account Settings Page** at `GET /app/account` → `AccountController@index`, name `app.account.index`, auth-required (not premium-gated — free users also manage their account)
2. **Breadcrumbs + Header** — "Dashboard › Account Settings" breadcrumbs; `<h1>` "Account Settings" with subtitle "Manage your personal information, security, and preferences"
3. **Tab Navigation** — four tabs: `👤 Profile`, `🔒 Security`, `💳 Billing`, `🎯 Goals & Preferences`; driven by `?tab=<id>` query string with `hx-push-url="true"` partial swaps
4. **Profile Tab** — Personal Information FormCard (Display Name editable, Email read-only with "Verified" pill, save button) + Appearance FormCard (theme toggle)
5. **Security Tab** — Security Status card (green, 100% progress bar, "Your account is protected…"), Password Reset card + button that opens a confirmation dialog and dispatches the existing password-reset-link flow
6. **Billing Tab** — Current Plan stat-card (reads `user.tier`, gradient variant), Premium Features list, disabled "Upgrade to Premium (Coming Soon)" button
7. **Goals & Preferences Tab** — 2-column grid on `≥1024px`:
   - **Your Chicken Goals** FormCard — select Hobby/Business with a contextual explanation panel that swaps based on the selection (green/purple themed)
   - **Production Goals** FormCard — yearly egg goal number input, progress bar + percentage when set, This Month / This Week stat-cards, "Keep Going!" encouragement panel when below target
   - **Pricing Configuration** FormCard — `egg_price` number input (step 0.01, min 0)
   - **Historical Data** FormCard (conditional: only when `user.eggEntries` is non-empty) — "Import Historical Data" button that opens the existing `eggs/partials/backfill-modal.blade.php` repurposed
8. **Confirm Dialog Component** — new `<x-ui.confirm-dialog>` Blade component (Alpine-driven) for password-reset confirmation; reusable for any future destructive actions
9. **Success & Error Banners** — slide-down banners (reusing the visual language established by the Expenses epic) render above the tab content; auto-dismiss success after 3s

**How It Integrates:**

- New `AccountController` with actions: `index` (render page with active tab), `updateProfile` (display name save), `updatePreferences` (chicken goal + yearly egg goal + egg price save), `sendPasswordResetLink` (delegates to the existing `PasswordResetLinkController@store`)
- New sidebar link "Account" pointing to `app.account.index` (or the existing user menu dropdown gains the link)
- Form requests: `UpdateProfileRequest`, `UpdatePreferencesRequest` validate each sub-form separately so partial saves are possible
- Theme toggle is wired to a new `ThemeToggleController@update` that persists `user.theme_preference` (new nullable `theme` column; see Story 1 Note) OR writes to a cookie — implementation decision flagged in Open Questions
- Historical data modal reuses `eggs/partials/backfill-modal.blade.php` and posts to the existing `app.eggs.backfill` endpoint

**Success Criteria:**

- Visual parity with the React component achieved (side-by-side screenshot diff per tab in light + dark mode)
- All four tabs render under `/app/account?tab=<id>` with HTMX partial swap + browser back/forward support
- Profile save persists `name` (display name equivalent) via form request validation
- Password reset confirm dialog opens, cancels safely, and on confirm dispatches the existing reset-link email
- Chicken goal contextual panel swaps green/purple based on selection without full page reload (Alpine)
- Yearly egg goal progress bar updates immediately after save
- This Month / This Week stats reflect the live `eggEntries` dataset
- Historical Data card is hidden when the user has no egg entries; visible when ≥ 1
- All Tailwind utility classes from the React source are translated to BEM-style SCSS in a new `_account.scss`
- Dark-mode parity for all four tabs, including the green/purple goal panels

---

## Stories

### Story 1: Account Settings Shell — Route, Breadcrumbs, Tab Navigation, Banners

**User Story:**

As a user,
I want a dedicated Account Settings page with clear tab navigation,
So that I can find and edit all account-level configuration in one place.

**Acceptance Criteria:**

**Route & Controller:**
1. New route `GET /app/account` → `AccountController@index`, name `app.account.index`, auth-required (no premium middleware)
2. `?tab=profile|security|billing|goals` query param selects the active tab (default `profile`)
3. HTMX tab clicks issue `hx-get` to `/app/account?tab=<id>` with `hx-push-url="true"` and swap `#account-tab-content`
4. Sidebar or top-right user menu gains an "Account" link pointing to `app.account.index`
5. Controller returns the full page layout on non-HTMX requests and returns only the tab partial on HTMX requests (matching `HX-Request` pattern already used across the codebase)

**Breadcrumbs:**
1. Above the heading: `Dashboard › Account Settings` with the first segment linking to `/app/` and the second segment rendered as the current page (not a link, `aria-current="page"`)
2. New `<x-ui.breadcrumbs>` Blade component accepting an `items` prop (array of `{label, href?, current?}`)

**Header:**
1. `<h1>` "Account Settings" (4xl, bold, Fraunces serif font-family — already loaded for headings in the project)
2. Subtitle (muted, lg): "Manage your personal information, security, and preferences"
3. Entry animation: opacity 0 → 1, y 20 → 0, 0.6s forwards

**Tab Navigation:**
1. Horizontal tab strip with four tabs: `👤 Profile`, `🔒 Security`, `💳 Billing`, `🎯 Goals & Preferences`
2. Active tab: indigo text, 2px indigo bottom border; inactive: muted text, hover darkens
3. Mobile (`<640px`): tab strip horizontally scrolls with `overflow-x: auto; white-space: nowrap`
4. Keyboard: Tab/Enter/Space switches tabs; `role="tablist"`, `role="tab"`, `aria-selected` wired

**Banners:**
1. Success banner (green, ✅ icon) slides down above the tab content on successful save; auto-dismiss after 3s
2. Error banner (red, ❌ icon) renders validation failure messages; dismissible by the user via close button
3. Banners mount inside a shared container `#account-banner` and are driven by Alpine `x-data="accountBanners()"` state
4. Entry transition: `x-transition:enter.opacity.duration.300ms` with slight downward translate

**Layout Width Cap:**
1. Tab content wrapper widths:
   - `profile` / `security` / `billing`: `max-width: 42rem` (≈ 672px)
   - `goals`: `max-width: 64rem` (≈ 1024px) — wider to fit the 2-column grid
2. Outer page container: `lg:mx-[10%]` equivalent → `.account-page { max-width: 80rem; margin-inline: auto; }`

**Technical Requirements:**

- New `app/Http/Controllers/AccountController.php` with `index(Request $request)` returning `resources/views/account/index.blade.php` or `account/partials/tab-{id}.blade.php` when `HX-Request` is present
- New partials skeleton: `account/partials/tab-profile.blade.php`, `tab-security.blade.php`, `tab-billing.blade.php`, `tab-goals.blade.php` (Stories 2–5 fill them in)
- New `<x-ui.breadcrumbs>` component
- New SCSS partial `resources/scss/features/_account.scss` registered in the main SCSS index; contains: `.account-page`, `.account-page__header`, `.account-page__title`, `.account-page__subtitle`, `.account-page__breadcrumbs`, `.account-page__tabs`, `.account-page__tab`, `.account-page__tab--active`, `.account-page__tab-content`, `.account-page__banner`, `.account-page__banner--success`, `.account-page__banner--error`
- Alpine component `accountBanners()`: `success`, `error`, `show(type, message)`, `dismiss()`
- Feature test: route loads with default tab, `?tab=goals` selects goals, HTMX request returns partial only, unauthenticated rejection
- Accessibility smoke test: `role="tablist"` present, tabs have `aria-selected`

---

### Story 2: Profile Tab — Personal Information & Appearance

**User Story:**

As a user,
I want to view and update my display name, see my email verification status, and toggle between light/dark themes,
So that the app feels personal and consistent with my preferences.

**Acceptance Criteria:**

**Personal Information FormCard:**
1. Title "Personal Information", subtitle "Update your profile details", icon 👤
2. Account Verification sub-section with an `<h4>` "Account Verification" and one row:
   - ✅ icon, "Email Address" label, sub-label "Your email is verified and secure", "Verified" pill on the right (green, pill-shaped)
   - Pill uses `.account-verification__pill--verified` SCSS class
3. **Display Name** field — text input, required, placeholder "Enter your display name", bound to `user.name` (the project uses `name` for this purpose)
4. **Email Address** field — text input, read-only/disabled, help text "Email changes require account verification and are currently disabled"
5. Primary "💾 Save Profile" button (full-width, large). On click → HTMX `PATCH /app/account/profile` with `name` payload; success banner "Profile updated successfully!" renders in `#account-banner`
6. Client-side validation disables submit when name is blank; server-side `UpdateProfileRequest` enforces `name` required and max 255

**Appearance FormCard:**
1. Title "Appearance", subtitle "Customize your visual experience", icon 🎨
2. `<h4>` "Theme" + muted description "Choose how ChickenCare looks to you. By default, it follows your device settings."
3. Theme toggle: three-option radio group or segmented control (Light / Dark / System)
4. Selecting a theme persists the choice (via cookie OR user column — see Open Questions) and applies immediately to `document.documentElement.classList`
5. No explicit save button; change persists on selection

**Technical Requirements:**

- New controller action `AccountController@updateProfile(UpdateProfileRequest $request)` validating `name` required + max 255 and updating the authenticated user; returns `HX-Trigger: account:profile-updated` on success
- New `UpdateProfileRequest` form request
- New `<x-ui.theme-toggle>` Blade component (Alpine-driven) with three buttons; persists to a cookie `theme` (`light|dark|system`) read by `resources/js/app.js` and applied on initial paint to prevent FOUC
- New partial: `account/partials/tab-profile.blade.php` composing the two FormCards
- SCSS additions: `.account-profile`, `.account-verification`, `.account-verification__row`, `.account-verification__pill--verified`, `.account-theme-toggle`, `.account-theme-toggle__btn`, `.account-theme-toggle__btn--active`
- Feature tests: profile update happy path, validation failure (blank name), theme cookie write, unauthenticated rejection

---

### Story 3: Security Tab — Status Card & Password Reset Confirm Dialog

**User Story:**

As a user,
I want to see my account security status and reset my password from within the app,
So that I can maintain secure access without leaving the settings page.

**Acceptance Criteria:**

**Security Status Card:**
1. Gradient green background (`linear-gradient(to right, #f0fdf4, #ecfdf5)` light / dark-mode equivalent), green border
2. 🛡️ icon on the left
3. `<h3>` "Security Status: Secure" + subtitle "Your account is protected with email verification and secure authentication"
4. Progress bar below the subtitle, 100% filled with a green gradient, rounded
5. Caption below the bar: "Your account security is fully configured"

**Password Reset Card:**
1. Blue gradient background, blue border, 🔐 icon
2. `<h3>` "Password Reset" + subtitle "Reset your password by receiving a secure link via email"
3. Below: "🔄 Reset Password" secondary button (full-width, large)

**Confirm Dialog:**
1. Clicking "Reset Password" opens a modal with:
   - Title: "Reset Password"
   - Message: `Are you sure you want to reset your password? A reset link will be sent to {user.email}.`
   - Variant: warning (amber iconography)
   - Cancel / Continue buttons
2. Cancel closes the modal; Continue dispatches an HTMX `POST /app/account/password-reset-link` which internally calls the existing `Auth\PasswordResetLinkController@store` logic against `user.email` and returns a success/error banner
3. Modal closes on success or error; the banner surfaces the result
4. Keyboard: Escape closes the modal, focus trap inside the modal while open, focus returns to the trigger button on close

**Error Display:**
1. If password reset fails, render the error message in a dedicated `.account-security__error` panel below the Password Reset card (matches React `getFieldError('password')` pattern)

**Technical Requirements:**

- New `<x-ui.confirm-dialog>` Blade component with props: `id`, `title`, `message`, `variant` (warning|danger|success), `confirmText`, `cancelText`, `hx-post` target for the Continue button. Alpine-driven; focus-trap via `@focusout` + `$refs`
- New controller action `AccountController@sendPasswordResetLink()` that delegates to `app(PasswordResetLinkController::class)->store(...)` with the authenticated user's email
- New route `POST /app/account/password-reset-link` → `AccountController@sendPasswordResetLink`
- New partial: `account/partials/tab-security.blade.php`
- SCSS: `.account-security`, `.account-security__status`, `.account-security__progress`, `.account-security__reset-card`, `.account-security__error`, `.confirm-dialog`, `.confirm-dialog__overlay`, `.confirm-dialog__panel`, `.confirm-dialog__actions`, variant modifiers for warning/danger/success
- Feature tests: password reset link dispatched on confirm, rate-limit respected (reuse Fortify/Breeze rate limiter), modal not dispatched on cancel, error surfacing when reset-link fails
- Accessibility: modal has `role="dialog"`, `aria-modal="true"`, labelled by the title; focus trap works

---

### Story 4: Billing Tab — Current Plan & Premium Features

**User Story:**

As a user,
I want to see my current subscription tier and what Premium unlocks,
So that I understand where I stand and what I'd gain by upgrading.

**Acceptance Criteria:**

**Current Plan Card:**
1. `<x-ui.stat-card variant="gradient">` (new gradient variant — or reuse an existing one if equivalent) showing:
   - Title "Current Plan"
   - Total: `user.tier` capitalized (`Free` or `Premium`)
   - Label: `Full access to all features` (premium) or `Basic features available` (free)
   - Icon: ⭐ (premium) or ✨ (free)
2. Premium users get a purple-indigo gradient background; free users get a green-emerald gradient

**Premium Features List:**
1. `<h4>` heading:
   - Premium user: "Premium Features:"
   - Free user: "Premium Features (Available after upgrade):"
2. Bulleted list with these exact items and icons (order preserved from React source):
   - 📊 Dashboard analytics and insights
   - 🐔 My Flock management
   - 💼 Customer relationship management
   - 💰 Expense tracking
   - 🌾 Feed management
   - 📈 Savings analysis
   - 🧮 Viability calculator
3. List uses `.account-billing__features` with `li` icons via a leading emoji + space

**Upgrade CTA:**
1. Secondary "🚀 Upgrade to Premium (Coming Soon)" button, full-width, large, `disabled`
2. Tooltip on hover: "Upgrade flow launching soon"

**Technical Requirements:**

- New partial: `account/partials/tab-billing.blade.php`
- If the `gradient` stat-card variant doesn't exist yet, add it: corner-gradient-like class with a stronger linear-gradient background; document the new variant in the component's `@props` block
- SCSS: `.account-billing`, `.account-billing__features`, `.account-billing__upgrade-btn`
- Feature tests: billing tab renders the correct tier label for premium and free users; upgrade button is disabled regardless of tier

---

### Story 5: Goals & Preferences Tab — Chicken Goal, Production Goal, Pricing, Historical Data

**User Story:**

As a user,
I want to configure my chicken goal (hobby vs business), set my yearly egg target, price per egg, and optionally backfill historical egg data,
So that the rest of the app can tailor itself to my farm's size and intent.

**Acceptance Criteria:**

**Layout:**
1. 2-column grid on `≥1024px`, 1 column on smaller; `gap: 2rem`
2. The **Historical Data** FormCard spans both columns when present
3. Section entry animation: opacity 0 → 1, y 20 → 0, 0.6s forwards

**Your Chicken Goals FormCard:**
1. Title "Your Chicken Goals", subtitle "Help us customize your experience based on your primary goal", icon 🐔
2. Select "What's your primary goal with raising chickens?" with two options: `Hobby/Family Use`, `Business/Profit`
3. Below the select, a contextual panel swaps based on the selection:
   - **Hobby**: green background, 🏠 icon, `<h4>` "Hobby/Family Focus", copy block with 5 bullet points exactly matching the React source, and a white sub-card: "📊 Your Savings tab will show: Money saved vs buying organic store eggs - perfect for tracking household cost benefits!"
   - **Business**: purple background, 💼 icon, `<h4>` "Business/Profit Focus", copy block with 4 bullet points exactly matching the React source, and a white sub-card: "📈 Your Savings tab will show: Actual revenue vs expenses - ideal for monitoring business profitability and growth!"
4. Panel swap happens without a full page reload (Alpine-driven); the `chicken_goal` value persists only on explicit save (see Save Button below)

**Production Goals FormCard:**
1. Title "Production Goals", subtitle "Track your annual egg production target", icon 🎯
2. Field "Yearly Egg Production Goal" — number input, min 0, placeholder "e.g. 1200", help text "Set your target number of eggs for the year"
3. When `yearly_egg_goal > 0`, render a progress panel:
   - Title "Annual Progress", 📊 icon on indigo-tinted background
   - Label: `{yearProgress} eggs collected ({percentage}% of goal)` with `percentage` to 1 decimal
   - Progress bar (indigo gradient), width = `min(100, percentage)%`
4. Below the progress panel, a 2-col grid of `<x-ui.stat-card variant="compact">`:
   - This Month — total `{thisMonthEggs}`, label "eggs collected", icon 📅
   - This Week — total `{thisWeekEggs}`, label "eggs collected", icon 📊
5. "Keep Going!" panel (blue background, 🎯 icon) — shown only when `yearProgress > 0 AND yearly_egg_goal > yearProgress`:
   - `<h3>` "Keep Going!"
   - Message: "You need {remaining} more eggs to reach your annual goal." (thousands-separator formatted)

**Pricing Configuration FormCard:**
1. Title "Pricing Configuration", subtitle "Set your egg pricing preferences", icon 💰
2. Field "Price per Egg ($)" — number input, step 0.01, min 0, default 0.30, placeholder "0.30"
3. No internal save button; saved via the shared Save Preferences button below

**Save Preferences Button:**
1. Single primary "💾 Save Preferences" button at the bottom of the tab (full-width, large)
2. Saves `chicken_goal`, `yearly_egg_goal`, `egg_price` in a single HTMX `PATCH /app/account/preferences` request
3. Success banner "Preferences updated successfully!" auto-dismisses after 3s
4. Validation errors render in the error banner; per-field errors render inline under each input

**Historical Data FormCard (conditional):**
1. Rendered only when `Auth::user()->eggEntries()->exists()`
2. Title "Historical Data", subtitle "Import historical egg tracking data", icon 📊
3. Blue info panel: "💡 Backfill Historical Data" + body "Add egg production data for dates before you started using ChickenCare. This helps create more accurate analytics and trends."
4. Secondary "📊 Import Historical Data" button (non-full-width) that opens the existing `eggs/partials/backfill-modal.blade.php` modal via `hx-get` to the existing `app.eggs.backfill-form` endpoint
5. On successful backfill, the modal closes, a success banner renders, and the This Month / This Week stats refresh via HTMX OOB swap

**Technical Requirements:**

- New controller action `AccountController@updatePreferences(UpdatePreferencesRequest $request)` validating:
  - `chicken_goal` in `['hobby','business']` (via `Rule::enum(ChickenGoal::class)`)
  - `yearly_egg_goal` integer, min 0, max 1_000_000
  - `egg_price` decimal, min 0, max 999.99
- New route `PATCH /app/account/preferences` → `AccountController@updatePreferences`
- New `UpdatePreferencesRequest` form request
- New partial: `account/partials/tab-goals.blade.php` composing all four FormCards
- New sub-partials: `account/partials/goal-context-hobby.blade.php` and `goal-context-business.blade.php` (the green/purple contextual panels)
- Alpine `x-data="{ goal: '{{ $user->chicken_goal?->value }}' }"` wraps the Chicken Goals FormCard to toggle the contextual panel on the fly
- `yearProgress` computed server-side in `AccountController@index` via `EggEntry::whereBelongsTo($user)->whereYear('date', now()->year)->sum('count')`
- `thisMonthEggs` / `thisWeekEggs` reuse the helpers already present in `DashboardService::getEggStats` (extract into a shared `EggStatsService` if convenient)
- Historical data modal: reuse `resources/views/eggs/partials/backfill-modal.blade.php` as-is; just wire its trigger on this tab
- SCSS: `.account-goals`, `.account-goals__grid`, `.account-goals__context`, `.account-goals__context--hobby`, `.account-goals__context--business`, `.account-goals__subcard`, `.account-production`, `.account-production__progress-panel`, `.account-production__progress-bar`, `.account-production__mini-stats`, `.account-production__keep-going`, `.account-pricing`, `.account-historical`
- Feature tests: preferences update happy path, validation failure (negative egg price, invalid goal), contextual panel toggles without form submission, Historical Data card hidden for users with no entries, modal opens and closes
- Unit test: `UpdatePreferencesRequest` validation rules

---

## Compatibility Requirements

- [x] No existing routes, controllers, or views are removed — `/app/flock` continues to host the Flock Profile + Events Timeline (React `Profile.tsx` equivalent)
- [x] Existing password-reset routes and logic are reused (`Auth\PasswordResetLinkController`); no duplicate implementations
- [x] Database schema: **no migrations required** — `User` already exposes `name`, `email`, `tier`, `is_admin`, `yearly_egg_goal`, `egg_price`, `chicken_goal`. Theme preference persists via a cookie in this epic's scope (see Open Questions)
- [x] UI changes are additive only (new `/app/account` page + sidebar link)
- [x] Performance impact: negligible — each tab renders ≤ 3 FormCards and a handful of queries scoped to the authenticated user
- [x] Dark mode support: first-class across all four tabs, including the green/purple goal panels and the security status card

---

## Risk Mitigation

### Primary Risk

**Tailwind → SCSS translation drift.** The React source is Tailwind-heavy (gradients, spacing utilities, color modifiers for light/dark). Every utility must map to a BEM rule in `_account.scss`. Gradients on the Billing plan card and Security status card are particularly prone to drift.

### Secondary Risk

**Modal/dialog accessibility.** The confirm dialog and historical-data modal must trap focus, close on Escape, and restore focus on close. Getting this wrong is a regression the React source shields us from.

### Tertiary Risk

**Theme persistence.** Writing `theme` to a cookie vs a user column has different trade-offs (per-device vs per-account). The React source uses `localStorage` (per-device). Proposed: cookie → server (so the theme applies on first paint without JS flash). Flagged in Open Questions.

### Mitigation

1. Build the Tailwind → SCSS class vocabulary upfront in Story 1; subsequent stories reuse the vocabulary
2. Implement the confirm dialog via a well-tested `<x-ui.confirm-dialog>` component with focus-trap primitives; snapshot-test the ARIA attributes
3. Persist theme via cookie on first implementation; revisit per-account sync in a follow-up if user feedback demands it
4. Reuse the banner visual language (success/error banners + slide-down transition) established by the Expenses epic — consistency ≠ drift
5. All animations are progressive enhancements — page is fully functional without JS

### Rollback Plan

- New route, controller, form requests, partials, and SCSS block are all additive — removing them cleanly rolls back
- Sidebar link can be removed without affecting existing pages
- No database migrations required (all columns already exist)
- No new JS library dependencies to remove

---

## Definition of Done

- [ ] All stories completed with acceptance criteria met
- [ ] Visual parity verified against the React component (light + dark mode, per tab, premium + free users, hobby + business goal, screenshots captured via Chrome DevTools CLI)
- [ ] `AccountController` + form requests have feature test coverage: profile update, preferences update, password reset link dispatch, validation failures, HTMX partial rendering
- [ ] `<x-ui.confirm-dialog>` has focus-trap and Escape-to-close verified
- [ ] Existing `/app/flock`, `/app/eggs`, and auth flows regression green (password reset rate limiting still enforced)
- [ ] Animations smooth across Chrome, Firefox, Safari
- [ ] Mobile responsiveness confirmed: tab strip horizontally scrolls, tab content stacks to 1 column, confirm dialog centers and respects viewport
- [ ] Accessibility verified: `role="tablist"` on tab nav, `aria-selected` on tabs, `aria-current="page"` on breadcrumb, modal `role="dialog"` + `aria-modal="true"` + focus trap, form labels on every input
- [ ] Code follows Laravel Boost guidelines (`laravel-best-practices` skill applied)
- [ ] Code formatted with `vendor/bin/pint --dirty --format agent`
- [ ] Per project rule: all changes have programmatic test coverage (unit or feature)

---

## Visual References

**Original Components:**
- `d:\Koke\Aplikacija\src\components\features\profile\ProfilePage.tsx` — main page
- `d:\Koke\Aplikacija\src\components\ui\Breadcrumbs.tsx`
- `d:\Koke\Aplikacija\src\components\ui\TabNavigation.tsx`
- `d:\Koke\Aplikacija\src\components\ui\modals\ConfirmDialog.tsx`
- `d:\Koke\Aplikacija\src\components\ui\modals\HistoricalEggTrackingModal.tsx`
- `d:\Koke\Aplikacija\src\components\ui\forms\ThemeToggle.tsx`

**Current Laravel State:**
- `E:\ChickenCare\app\Models\User.php` — already exposes all required columns (`name`, `email`, `tier`, `is_admin`, `yearly_egg_goal`, `egg_price`, `chicken_goal`)
- `E:\ChickenCare\app\Enums\ChickenGoal.php` — already exists, reused here
- `E:\ChickenCare\app\Http\Controllers\Auth\PasswordResetLinkController.php` — reused for password reset
- `E:\ChickenCare\resources\views\flock\index.blade.php` — already covers `Profile.tsx` (Flock Profile + Events Timeline) scope; NOT in this epic
- `E:\ChickenCare\resources\views\eggs\partials\backfill-modal.blade.php` — reused by the Historical Data card

---

## Technical Notes

### Tailwind → SCSS Mapping

All utilities in the React source must be translated to BEM rules in a new `_account.scss`. Common patterns:

| Tailwind | SCSS Equivalent |
|---|---|
| `max-w-2xl mx-auto` | `.account-page__tab-content--narrow { max-width: 42rem; margin-inline: auto; }` |
| `max-w-5xl` (goals tab) | `.account-page__tab-content--wide { max-width: 64rem; margin-inline: auto; }` |
| `grid grid-cols-1 lg:grid-cols-2 gap-8` | `.account-goals__grid { display: grid; grid-template-columns: 1fr; gap: 2rem; } @media (min-width: 1024px) { grid-template-columns: repeat(2, 1fr); }` |
| `bg-gradient-to-r from-purple-50 to-indigo-50` | `.account-billing__plan--premium { background: linear-gradient(to right, #faf5ff, #eef2ff); }` + dark-mode equivalents |
| `bg-green-50 ... border-green-200` (hobby panel) | `.account-goals__context--hobby { background: #f0fdf4; border: 1px solid #bbf7d0; }` |
| `bg-purple-50 ... border-purple-200` (business panel) | `.account-goals__context--business { background: #faf5ff; border: 1px solid #e9d5ff; }` |
| `rounded-full h-3 bg-gradient-to-r from-[#524AE6] to-[#4338CA]` | `.account-production__progress-fill { height: 0.75rem; border-radius: 9999px; background: linear-gradient(to right, #524AE6, #4338CA); }` |

### CSS Animation Equivalents

| Framer Motion | CSS Equivalent |
|---|---|
| Tab entry `{ opacity: 0, y: 20 } → { opacity: 1, y: 0 }` with delay 0.1s | `@keyframes accountTabEnter` with `animation: accountTabEnter 0.6s 0.1s backwards` on `#account-tab-content` |
| Success banner `{ opacity: 0, scale: 0.95 } → { opacity: 1, scale: 1 }` | `.account-page__banner--success { animation: bannerEnter 0.3s forwards; } @keyframes bannerEnter { from { opacity: 0; transform: scale(0.95); } to { opacity: 1; transform: scale(1); } }` |
| Header `{ opacity: 0, y: 20 }` | `.account-page__header { animation: slideDown 0.6s forwards; }` |
| `prefers-reduced-motion` | `@media (prefers-reduced-motion: reduce) { .account-page * { animation: none !important; transition: none !important; } }` |

### Alpine.js Integration

- `x-data="accountBanners()"` on `#account-banner` — success/error surfacing with auto-dismiss timers
- `x-data="{ goal: '{{ $user->chicken_goal?->value }}' }"` wraps the Chicken Goals FormCard — drives the contextual panel visibility
- `x-data="confirmDialog()"` inside `<x-ui.confirm-dialog>` — open/close, focus trap, Escape handler, Continue dispatches HTMX request

### Theme Toggle

Implemented as a cookie write. `resources/js/app.js` reads `document.cookie.match(/(?:^|; )theme=([^;]+)/)` on boot and applies `document.documentElement.classList.toggle('dark', theme === 'dark' || (theme === 'system' && prefersDark))`. The `ThemeToggleController` (already lightweight) sets a long-lived `theme` cookie server-side to avoid FOUC. If a per-account sync is needed later, a `users.theme` column can be added without changing the UI.

### Form Request Shapes

```php
// app/Http/Requests/UpdateProfileRequest.php
public function rules(): array {
    return ['name' => ['required', 'string', 'max:255']];
}

// app/Http/Requests/UpdatePreferencesRequest.php
public function rules(): array {
    return [
        'chicken_goal' => ['required', Rule::enum(ChickenGoal::class)],
        'yearly_egg_goal' => ['required', 'integer', 'min:0', 'max:1000000'],
        'egg_price' => ['required', 'numeric', 'min:0', 'max:999.99'],
    ];
}
```

---

## Dependencies

### External Dependencies
- None new

### Internal Dependencies
- `User` model (already exposes all required columns)
- `App\Enums\ChickenGoal` (already exists)
- `Auth\PasswordResetLinkController` (already exists; reused)
- `eggs/partials/backfill-modal.blade.php` + `app.eggs.backfill-form` route (already exist; reused)
- Existing `<x-ui.stat-card>`, `<x-forms.form-card>`, `<x-forms.input>`, `<x-forms.select>`, `<x-forms.submit-button>`, `<x-forms.date-input>`, `<x-premium-gate>`
- New `<x-ui.breadcrumbs>`, `<x-ui.confirm-dialog>`, `<x-ui.theme-toggle>` Blade components

### Story Dependencies
- Story 2 depends on Story 1 (needs the page shell + banner mechanism)
- Story 3 depends on Story 1
- Story 4 depends on Story 1
- Story 5 depends on Story 1 (wires all four sub-cards on the Goals tab)

---

## Resolved Decisions

1. **Scope — `ProfilePage.tsx` only.** The sibling `Profile.tsx` (Flock Profile + Events Timeline) is already covered by `/app/flock`. This epic does not touch the flock page.
2. **Styling — pure CSS/SCSS, no Tailwind.** All Tailwind utilities in the React source are translated to BEM rules in a new `resources/scss/features/_account.scss`.
3. **Route — `/app/account`.** A new dedicated route + sidebar link. Not nested under `/app/flock` or any other existing page.
4. **Tab state — query string + HTMX partial swap.** `?tab=<id>` drives the active tab, with `hx-push-url="true"` for back-button support. Each tab's partial is server-rendered fresh on switch.
5. **Schema — no new columns.** `User` already exposes `name`, `email`, `tier`, `is_admin`, `yearly_egg_goal`, `egg_price`, `chicken_goal`. The Savings epic's Story 1 is effectively already done.
6. **Display name → `user.name`.** The React source uses `display_name` in Supabase metadata; in ChickenCare, `users.name` (already present) serves the same purpose. No new column required.
7. **Password reset — reuses existing flow.** `AccountController@sendPasswordResetLink` delegates to `Auth\PasswordResetLinkController@store`. Rate limiting inherited.
8. **Historical Data modal — reuses `eggs/partials/backfill-modal.blade.php`.** Same endpoint, same validation; only the trigger surface is new.
9. **Theme persistence — cookie (per-device).** Matches the React source's `localStorage` semantics (per-device). Server-side cookie read avoids FOUC.
10. **Verification copy.** The React source shows a static "Your email is verified and secure" pill. In ChickenCare, this is gated on `user.email_verified_at !== null`; when null, render a different amber "Unverified — resend" pill that dispatches `EmailVerificationNotificationController@store`.

---

## Open Questions

1. **Theme storage.** Cookie (per-device, matches React localStorage) vs `users.theme` column (per-account, cross-device sync). Proposed: cookie now, column later if demand arises. Confirm before Story 2 implementation.
2. **Gradient stat-card variant.** Does the Billing tab's "Current Plan" card need a new `gradient` variant, or does the existing `corner-gradient` variant suffice? Inspect `<x-ui.stat-card>` in Story 4 and decide.
3. **Email-change gating.** The React source disables email editing entirely. Should ChickenCare eventually allow it (with re-verification), or permanently lock? Proposed: lock for this epic; revisit when an email-change flow is spec'd.
4. **"Coming Soon" upgrade button.** Is there a commitment date for the upgrade flow? If yes, consider linking to a waitlist form instead of disabling.
5. **Email verification pill for unverified users.** The React source assumes verified. Should the amber "Resend verification" flow (proposed in Resolved Decision 10) be in scope here or deferred to its own epic? Proposed: include as a small addendum in Story 2 since the markup already needs the branch.
