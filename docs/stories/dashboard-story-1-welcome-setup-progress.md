# Story: Welcome Header & Setup Progress Panel

## Status
Not Started

## Story

**As a** user,
**I want** a personalized welcome header and a progressive-guidance panel that celebrates my setup progress,
**so that** the dashboard feels tailored to my journey and nudges me toward the next useful action.

---

## Story Context

**Existing System Integration:**
- Integrates with: `resources/views/dashboard/index.blade.php`, `app/Http/Controllers/DashboardController.php`, `app/Services/DashboardService.php`
- Technology: Laravel 13 Blade, HTMX, Alpine.js v3, SCSS keyframe animations
- Touch points: New `SetupProgressService`, new Blade partials under `resources/views/dashboard/partials/`, extended `_dashboard.scss`
- Follows pattern: CSS keyframes + Alpine transitions as Framer Motion equivalents (established in `egg-counter-story-1-hero-animation.md` and `expenses-story-1-hero-form-banners.md`)
- Reuses: `.gradient-text` utility (exists in `resources/scss/components/_neumorphic.scss`), `<x-ui.progress-card>` component (exists, accepts `title`, `value`, `max`, `label`, `variant`, `loading`)
- Models: `User`, `FlockProfile`, `FlockBatch`, `EggEntry`, `Expense`, `FeedInventory` (all exist)

**Change Scope:**
- New `App\Services\SetupProgressService` — computes onboarding progress from existing data signals
- New partial `resources/views/dashboard/partials/welcome-header.blade.php`
- New partial `resources/views/dashboard/partials/setup-progress.blade.php`
- Modified `DashboardController@index` — injects `SetupProgressService`, passes `$displayName` and `$setupProgress` to view
- Modified `resources/views/dashboard/index.blade.php` — includes the two new partials above the existing Egg Production section
- Extended `resources/scss/features/_dashboard.scss` — new `.dashboard__welcome`, `.dashboard__welcome-title`, `.dashboard__setup` BEM blocks
- No database migrations required — all required relationships and columns already exist
- No route changes — reuses existing `GET /app/` → `DashboardController@index`

---

## Acceptance Criteria

### Functional Requirements

#### Welcome Header

1. **`h1` heading with `.gradient-text` utility:** text reads `Welcome {displayName}` where `{displayName}` is resolved dynamically
2. **`displayName` resolution order:**
   - First: `user.name` (the `name` column, equivalent to `display_name` — the User model does NOT have a separate `display_name` column)
   - Second: localpart of `user.email` (everything before the `@`)
   - Third: literal string `"User"`
   - Resolution logic: if `name` is non-null and non-empty after trimming, use it; else if `email` is non-null, extract localpart; else fall back to `"User"`
3. **Responsive typography:** `1.5rem` on mobile, `2.25rem` on `≥1024px` viewports
4. **Classes:** `.dashboard__welcome-title` and `.gradient-text` on the `<h1>` element
5. **Entry animation:** CSS keyframe `@keyframes dashboardWelcomeEnter` — opacity 0 → 1, translateY(20px) → translateY(0), duration 0.6s, ease-out, forwards, `animation-delay: 0s`
6. **Accessibility:** `role="heading"` `aria-level="1"` (implicit via `<h1>` tag — no extra ARIA needed on native heading element)

#### Setup Progress Panel

7. **Conditional rendering:** Panel rendered ONLY when `$setupProgress['percentage'] < 100`. When `percentage === 100`, the entire panel `<section>` is absent from the DOM (not hidden via CSS)
8. **Section heading by bracket:**
   - `0 ≤ pct ≤ 40` → `"🚀 Getting Started"`
   - `41 ≤ pct ≤ 70` → `"📈 Building Your Farm"`
   - `71 ≤ pct ≤ 90` → `"⚡ Advanced Features"`
   - `91 ≤ pct ≤ 99` → `"🎯 Final Steps"`
9. **Phase pill:** displayed inside the panel body, one of:
   - `percentage ≤ 40` → label `"New User"`, CSS modifier `--new`
   - `41 ≤ percentage ≤ 70` → label `"Getting Started"`, CSS modifier `--started`
   - `71 ≤ percentage ≤ 90` → label `"Active User"`, CSS modifier `--active`
   - `91 ≤ percentage` → label `"Power User"`, CSS modifier `--power`
10. **Phase message:** below the pill, one of:
    - `≤ 40` → `"Get started with basic setup"`
    - `≤ 70` → `"Expand to core features"`
    - `≤ 90` → `"Unlock advanced features"`
    - `> 90` → `"You're using all features!"`
11. **Progress bar:** uses `<x-ui.progress-card>` component with `title="Setup Progress"`, `:value="$setupProgress['points']"`, `:max="120"`, `variant="detailed"`
12. **Checklist of 4 items:**

    | # | Key | Label | Points | Icon | Predicate |
    |---|-----|-------|--------|------|-----------|
    | 1 | `flock_profile` | Flock Profile Created | 50 | 🐔 | `hasFlockProfile` |
    | 2 | `first_egg` | First Egg Entry | 30 | 🥚 | `hasRecordedProduction` |
    | 3 | `first_expense` | First Expense Logged | 20 | 💰 | `hasRecordedExpense` |
    | 4 | `feed_tracking` | Feed Tracking Started | 20 | 🌾 | `hasFeedTracking` |

    - Completed items: show a `✓` checkmark, label is styled with strikethrough or muted color, points badge shown as `"50 pts"` in a success-colored pill
    - Incomplete items: show the icon, label, points badge in a neutral pill, and an action button (e.g., "Set up now →")

13. **Action button routing (incomplete items only):**
    - `flock_profile` → `/app/flock` (route: `app.flock.index`)
    - `first_egg` → `/app/eggs` (route: `app.eggs.index`)
    - `first_expense` → `/app/expenses` (route: `app.expenses.index`)
    - `feed_tracking` → `/app/feed` (route: `app.feed.index`)
14. **Panel entry animation:** CSS keyframe `@keyframes dashboardSectionEnter` — opacity 0 → 1, translateY(20px) → translateY(0), duration 0.6s, ease-out, forwards, `animation-delay: 0.1s`
15. **HTMX OOB dismissal:** when the user completes the final checklist item (triggering `eggs:changed`, `expenses:changed`, `feed:changed`, or `flock:changed` events), the panel can be hidden via `hx-swap-oob="delete"` on a server response targeting `#setup-progress-panel`. The panel `<section>` has `id="setup-progress-panel"` to support this.

### Non-Functional Requirements

1. **Dark mode:** `.gradient-text` already handles dark mode via existing `_neumorphic.scss`. Phase pill, progress bar, checklist items must all work in dark mode using existing CSS custom properties (`--color-surface`, `--color-text-muted`, etc.)
2. **Responsive:** welcome header and setup panel render cleanly on mobile (single column) through desktop
3. **Accessibility:**
   - Progress bar has `role="progressbar"`, `aria-valuenow`, `aria-valuemin="0"`, `aria-valuemax="120"` (handled by `<x-ui.progress-card>`)
   - Checklist items use semantic list markup (`<ul>` + `<li>`)
   - Action buttons have descriptive `aria-label` (e.g., `"Set up flock profile"`)
4. **`prefers-reduced-motion`:** all CSS animations wrapped in `@media (prefers-reduced-motion: no-preference)` — elements render statically for reduced-motion users
5. **No new external dependencies:** Alpine.js and HTMX already loaded via `layouts.app`
6. **Performance:** `SetupProgressService::compute()` uses existence checks (`->exists()`) rather than loading full model collections — 4 lightweight queries maximum

---

## Tasks / Subtasks

- [ ] **Task 1: Create `SetupProgressService`** (AC: 7, 8, 9, 10, 11, 12)

  - [ ] 1.1 — Generate the service class:
    ```bash
    C:\php83\php.exe artisan make:class App/Services/SetupProgressService --no-interaction
    ```
    File: `app/Services/SetupProgressService.php`

  - [ ] 1.2 — Implement `compute(User $user): array` with this exact signature and return shape:
    ```php
    /**
     * Compute the onboarding setup progress for a user.
     *
     * @return array{
     *   percentage: int,
     *   points: int,
     *   bracket: string,
     *   phase: array{key: string, label: string, message: string},
     *   items: list<array{key: string, label: string, points: int, icon: string, completed: bool, action_label: string, action_href: string}>,
     * }
     */
    public function compute(User $user): array
    ```

  - [ ] 1.3 — Implement the four predicate methods (private):
    ```php
    private function hasFlockProfile(User $user): bool
    {
        return $user->flockProfile()->exists() || $user->flockBatches()->exists();
    }

    private function hasRecordedProduction(User $user): bool
    {
        return $user->eggEntries()->exists();
    }

    private function hasRecordedExpense(User $user): bool
    {
        return $user->expenses()->exists();
    }

    private function hasFeedTracking(User $user): bool
    {
        return $user->feedInventory()->exists();
    }
    ```

  - [ ] 1.4 — Build the items array inside `compute()`:
    ```php
    $items = [
        [
            'key' => 'flock_profile',
            'label' => 'Flock Profile Created',
            'points' => 50,
            'icon' => '🐔',
            'completed' => $this->hasFlockProfile($user),
            'action_label' => 'Set up now',
            'action_href' => route('app.flock.index'),
        ],
        [
            'key' => 'first_egg',
            'label' => 'First Egg Entry',
            'points' => 30,
            'icon' => '🥚',
            'completed' => $this->hasRecordedProduction($user),
            'action_label' => 'Record eggs',
            'action_href' => route('app.eggs.index'),
        ],
        [
            'key' => 'first_expense',
            'label' => 'First Expense Logged',
            'points' => 20,
            'icon' => '💰',
            'completed' => $this->hasRecordedExpense($user),
            'action_label' => 'Add expense',
            'action_href' => route('app.expenses.index'),
        ],
        [
            'key' => 'feed_tracking',
            'label' => 'Feed Tracking Started',
            'points' => 20,
            'icon' => '🌾',
            'completed' => $this->hasFeedTracking($user),
            'action_label' => 'Track feed',
            'action_href' => route('app.feed.index'),
        ],
    ];
    ```

  - [ ] 1.5 — Calculate points, percentage, bracket, and phase:
    ```php
    $points = collect($items)->where('completed', true)->sum('points');
    $percentage = (int) round(($points / 120) * 100);

    $bracket = match (true) {
        $percentage <= 40 => '🚀 Getting Started',
        $percentage <= 70 => '📈 Building Your Farm',
        $percentage <= 90 => '⚡ Advanced Features',
        default            => '🎯 Final Steps',
    };

    $phase = match (true) {
        $percentage <= 40 => ['key' => 'new',     'label' => 'New User',        'message' => 'Get started with basic setup'],
        $percentage <= 70 => ['key' => 'started',  'label' => 'Getting Started', 'message' => 'Expand to core features'],
        $percentage <= 90 => ['key' => 'active',   'label' => 'Active User',     'message' => 'Unlock advanced features'],
        default            => ['key' => 'power',    'label' => 'Power User',      'message' => "You're using all features!"],
    };
    ```

  - [ ] 1.6 — Return the assembled array:
    ```php
    return [
        'percentage' => $percentage,
        'points' => $points,
        'bracket' => $bracket,
        'phase' => $phase,
        'items' => $items,
    ];
    ```

---

- [ ] **Task 2: Create `SetupProgressServiceTest` (unit test)** (AC: 7, 8, 9, 10, 12)

  - [ ] 2.1 — Generate the test file:
    ```bash
    C:\php83\php.exe artisan make:test --phpunit --unit Services/SetupProgressServiceTest --no-interaction
    ```
    File: `tests/Unit/Services/SetupProgressServiceTest.php`

  - [ ] 2.2 — Test class setup:
    ```php
    use Illuminate\Foundation\Testing\RefreshDatabase;
    use App\Services\SetupProgressService;
    use App\Models\User;
    use App\Models\FlockProfile;
    use App\Models\FlockBatch;
    use App\Models\EggEntry;
    use App\Models\Expense;
    use App\Models\FeedInventory;

    class SetupProgressServiceTest extends TestCase
    {
        use RefreshDatabase;

        private User $user;
        private SetupProgressService $service;

        protected function setUp(): void
        {
            parent::setUp();
            $this->user = User::factory()->create();
            $this->service = new SetupProgressService();
        }
    }
    ```

  - [ ] 2.3 — `test_brand_new_user_has_zero_percentage_and_getting_started_bracket`:
    - No related records created
    - Assert: `percentage === 0`, `points === 0`, `bracket === '🚀 Getting Started'`
    - Assert: `phase['key'] === 'new'`, `phase['label'] === 'New User'`
    - Assert: all 4 items have `completed === false`

  - [ ] 2.4 — `test_has_flock_profile_predicate_true_when_flock_profile_exists`:
    - Create `FlockProfile::factory()->for($this->user)->create()`
    - Assert: item with key `flock_profile` has `completed === true`
    - Assert: `points === 50`, `percentage === 42` (round(50/120 * 100))

  - [ ] 2.5 — `test_has_flock_profile_predicate_true_when_flock_batch_exists_but_no_profile`:
    - Create `FlockBatch::factory()->for($this->user)->create()` (no FlockProfile)
    - Assert: item with key `flock_profile` has `completed === true`
    - Assert: `points === 50`

  - [ ] 2.6 — `test_has_recorded_production_predicate_true_when_egg_entry_exists`:
    - Create `EggEntry::factory()->for($this->user)->create()`
    - Assert: item with key `first_egg` has `completed === true`
    - Assert: `points === 30`, `percentage === 25` (round(30/120 * 100))

  - [ ] 2.7 — `test_has_recorded_expense_predicate_true_when_expense_exists`:
    - Create `Expense::factory()->for($this->user)->create()`
    - Assert: item with key `first_expense` has `completed === true`
    - Assert: `points === 20`, `percentage === 17` (round(20/120 * 100))

  - [ ] 2.8 — `test_has_feed_tracking_predicate_true_when_feed_inventory_exists`:
    - Create `FeedInventory::factory()->for($this->user)->create()`
    - Assert: item with key `feed_tracking` has `completed === true`
    - Assert: `points === 20`, `percentage === 17` (round(20/120 * 100))

  - [ ] 2.9 — `test_all_items_complete_yields_100_percentage`:
    - Create all four: FlockProfile, EggEntry, Expense, FeedInventory for user
    - Assert: `percentage === 100`, `points === 120`
    - Assert: all 4 items have `completed === true`

  - [ ] 2.10 — `test_bracket_is_building_your_farm_at_42_percent`:
    - Complete only flock profile (50 pts → 42%)
    - Assert: `bracket === '📈 Building Your Farm'` (42 falls in 41–70 range)

  - [ ] 2.11 — `test_bracket_is_advanced_features_at_67_percent`:
    - Complete flock profile (50) + first egg (30) = 80 pts → 67%
    - Assert: `bracket === '📈 Building Your Farm'` (67 falls in 41–70 range)

  - [ ] 2.12 — `test_bracket_is_advanced_features_at_83_percent`:
    - Complete flock profile (50) + first egg (30) + first expense (20) = 100 pts → 83%
    - Assert: `bracket === '⚡ Advanced Features'` (83 falls in 71–90 range)

  - [ ] 2.13 — `test_phase_mapping_power_user_at_100_percent`:
    - Complete all four items → 120 pts → 100%
    - Assert: `phase['key'] === 'power'`, `phase['label'] === 'Power User'`, `phase['message'] === "You're using all features!"`

  - [ ] 2.14 — `test_items_contain_correct_action_hrefs`:
    - Assert: `items[0]['action_href']` matches `route('app.flock.index')`
    - Assert: `items[1]['action_href']` matches `route('app.eggs.index')`
    - Assert: `items[2]['action_href']` matches `route('app.expenses.index')`
    - Assert: `items[3]['action_href']` matches `route('app.feed.index')`

  - [ ] 2.15 — `test_other_users_data_does_not_affect_progress`:
    - Create EggEntry for a different user
    - Assert: `$this->user` still has `percentage === 0` and `first_egg` item `completed === false`

  - [ ] 2.16 — Run tests:
    ```bash
    C:\php83\php.exe artisan test --compact --filter=SetupProgressServiceTest
    ```

---

- [ ] **Task 3: Modify `DashboardController` to inject progress data** (AC: 1, 2, 7)

  - [ ] 3.1 — Edit `app/Http/Controllers/DashboardController.php`:
    - Add `use App\Services\SetupProgressService;` import
    - Update the `index` method signature to accept `SetupProgressService $setupProgressService` via dependency injection (alongside existing `DashboardService $dashboardService`)
    - Compute display name:
      ```php
      $user = $request->user();
      $displayName = filled($user->name) ? $user->name : (
          $user->email ? str($user->email)->before('@')->toString() : 'User'
      );
      ```
    - Compute setup progress:
      ```php
      $setupProgress = $setupProgressService->compute($user);
      ```
    - Pass both to the view by adding `$displayName` and `$setupProgress` to the `compact()` or array:
      ```php
      return view('dashboard.index', compact('summary', 'eggChartData', 'expenseChartData', 'displayName', 'setupProgress'));
      ```
    - Ensure the existing HTMX partial branch (HX-Target === 'dashboard-activity') is unchanged

---

- [ ] **Task 4: Create `welcome-header.blade.php` partial** (AC: 1, 2, 3, 4, 5, 6)

  - [ ] 4.1 — Create file: `resources/views/dashboard/partials/welcome-header.blade.php`
  - [ ] 4.2 — Content:
    ```blade
    <section class="dashboard__welcome" style="animation-delay: 0s;">
        <h1 class="dashboard__welcome-title gradient-text">
            Welcome {{ $displayName }}
        </h1>
    </section>
    ```
  - [ ] 4.3 — The `<section>` gets the `.dashboard__welcome` class which applies the entry animation via CSS (Task 6)

---

- [ ] **Task 5: Create `setup-progress.blade.php` partial** (AC: 7, 8, 9, 10, 11, 12, 13, 14, 15)

  - [ ] 5.1 — Create file: `resources/views/dashboard/partials/setup-progress.blade.php`
  - [ ] 5.2 — Wrap entire partial in `@if($setupProgress['percentage'] < 100)` ... `@endif`
  - [ ] 5.3 — Outer `<section>` element:
    ```blade
    <section id="setup-progress-panel" class="dashboard__setup dashboard__section" style="animation-delay: 0.1s;">
    ```
  - [ ] 5.4 — Section heading using bracket:
    ```blade
    <h2 class="dashboard__section-title">{{ $setupProgress['bracket'] }}</h2>
    ```
  - [ ] 5.5 — Phase pill and message:
    ```blade
    <div class="dashboard__setup-header">
        <span class="dashboard__setup-pill dashboard__setup-pill--{{ $setupProgress['phase']['key'] }}">
            {{ $setupProgress['phase']['label'] }}
        </span>
        <p class="dashboard__setup-message">{{ $setupProgress['phase']['message'] }}</p>
    </div>
    ```
  - [ ] 5.6 — Progress bar using existing component:
    ```blade
    <x-ui.progress-card
        title="Setup Progress"
        :value="$setupProgress['points']"
        :max="120"
        label="{{ $setupProgress['percentage'] }}% complete"
        variant="detailed"
    />
    ```
  - [ ] 5.7 — Checklist as a `<ul>`:
    ```blade
    <ul class="dashboard__setup-checklist">
        @foreach($setupProgress['items'] as $item)
            <li class="dashboard__setup-item {{ $item['completed'] ? 'dashboard__setup-item--done' : '' }}">
                <span class="dashboard__setup-item-icon" aria-hidden="true">
                    {{ $item['completed'] ? '✓' : $item['icon'] }}
                </span>
                <span class="dashboard__setup-item-label">{{ $item['label'] }}</span>
                <span class="dashboard__setup-item-points dashboard__setup-item-points--{{ $item['completed'] ? 'earned' : 'pending' }}">
                    {{ $item['points'] }} pts
                </span>
                @unless($item['completed'])
                    <a href="{{ $item['action_href'] }}"
                       class="btn btn--sm btn--secondary dashboard__setup-item-action"
                       aria-label="{{ $item['action_label'] }} — {{ $item['label'] }}">
                        {{ $item['action_label'] }} →
                    </a>
                @endunless
            </li>
        @endforeach
    </ul>
    ```

---

- [ ] **Task 6: Update `dashboard/index.blade.php`** (AC: 1, 7)

  - [ ] 6.1 — Insert the two new partials at the top of the `<div class="dashboard">` block, immediately after `<x-layout.page-header title="Dashboard" />`:
    ```blade
    @include('dashboard.partials.welcome-header')
    @include('dashboard.partials.setup-progress')
    ```
  - [ ] 6.2 — Verify the existing egg stats, chart, financial, flock, and activity sections remain intact below the new partials

---

- [ ] **Task 7: Add SCSS styles to `_dashboard.scss`** (AC: 3, 5, 6, 9, 10, 12, 14; Non-Functional: 1, 2, 4)

  - [ ] 7.1 — Edit `resources/scss/features/_dashboard.scss` — append the following BEM blocks at the end of the `.dashboard { }` rule:

  - [ ] 7.2 — Welcome block:
    ```scss
    &__welcome {
        margin-bottom: 0.5rem;
    }

    &__welcome-title {
        font-size: 1.5rem;
        font-weight: 700;
        margin: 0;

        @media (min-width: 1024px) {
            font-size: 2.25rem;
        }
    }
    ```

  - [ ] 7.3 — Setup progress block:
    ```scss
    &__setup {
        display: flex;
        flex-direction: column;
        gap: 1rem;
    }

    &__setup-header {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        gap: 0.75rem;
    }

    &__setup-pill {
        display: inline-block;
        padding: 0.25rem 0.75rem;
        border-radius: 9999px;
        font-size: 0.75rem;
        font-weight: 600;
        text-transform: uppercase;

        &--new {
            background: var(--color-indigo-100, #e0e7ff);
            color: var(--color-indigo-700, #4338ca);
        }

        &--started {
            background: var(--color-purple-100, #f3e8ff);
            color: var(--color-purple-700, #7e22ce);
        }

        &--active {
            background: var(--color-violet-100, #ede9fe);
            color: var(--color-violet-700, #6d28d9);
        }

        &--power {
            background: var(--color-success-bg, #d1fae5);
            color: var(--color-success-text, #065f46);
        }
    }

    &__setup-message {
        margin: 0;
        font-size: 0.875rem;
        color: var(--color-text-muted, #6b7280);
    }

    &__setup-checklist {
        list-style: none;
        margin: 0;
        padding: 0;
        display: flex;
        flex-direction: column;
        gap: 0.5rem;
    }

    &__setup-item {
        display: flex;
        align-items: center;
        gap: 0.75rem;
        padding: 0.75rem 1rem;
        border-radius: 8px;
        background: var(--color-surface, #f9fafb);
        border: 1px solid var(--color-border, #e5e7eb);

        &--done {
            opacity: 0.7;

            .dashboard__setup-item-label {
                text-decoration: line-through;
                color: var(--color-text-muted, #6b7280);
            }
        }
    }

    &__setup-item-icon {
        flex-shrink: 0;
        font-size: 1.25rem;
        width: 2rem;
        text-align: center;
    }

    &__setup-item-label {
        flex: 1;
        font-size: 0.875rem;
        font-weight: 500;
    }

    &__setup-item-points {
        flex-shrink: 0;
        font-size: 0.75rem;
        font-weight: 600;
        padding: 0.125rem 0.5rem;
        border-radius: 9999px;

        &--earned {
            background: var(--color-success-bg, #d1fae5);
            color: var(--color-success-text, #065f46);
        }

        &--pending {
            background: var(--color-neutral-100, #f3f4f6);
            color: var(--color-text-muted, #6b7280);
        }
    }

    &__setup-item-action {
        flex-shrink: 0;
    }
    ```

  - [ ] 7.4 — Animation keyframes (OUTSIDE the `.dashboard` block, at the bottom of the file):
    ```scss
    @media (prefers-reduced-motion: no-preference) {
        @keyframes dashboardWelcomeEnter {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @keyframes dashboardSectionEnter {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .dashboard__welcome {
            animation: dashboardWelcomeEnter 0.6s ease-out forwards;
        }

        .dashboard__setup {
            opacity: 0;
            animation: dashboardSectionEnter 0.6s ease-out forwards;
        }
    }
    ```

  - [ ] 7.5 — Dark mode overrides (inside a `.dark { }` block or using existing dark mode pattern):
    ```scss
    .dark {
        .dashboard__setup-item {
            background: var(--color-surface-dark, #1f2937);
            border-color: var(--color-border-dark, #374151);
        }

        .dashboard__setup-pill {
            &--new {
                background: rgba(99, 102, 241, 0.2);
                color: #a5b4fc;
            }

            &--started {
                background: rgba(168, 85, 247, 0.2);
                color: #c084fc;
            }

            &--active {
                background: rgba(139, 92, 246, 0.2);
                color: #a78bfa;
            }

            &--power {
                background: rgba(34, 197, 94, 0.2);
                color: #86efac;
            }
        }

        .dashboard__setup-item-points {
            &--earned {
                background: rgba(34, 197, 94, 0.2);
                color: #86efac;
            }

            &--pending {
                background: rgba(107, 114, 128, 0.2);
                color: #9ca3af;
            }
        }
    }
    ```

---

- [ ] **Task 8: Create feature test `DashboardWelcomeSetupTest`** (AC: 1, 2, 7, 8, 13, 15)

  - [ ] 8.1 — Generate the test file:
    ```bash
    C:\php83\php.exe artisan make:test --phpunit DashboardWelcomeSetupTest --no-interaction
    ```
    File: `tests/Feature/DashboardWelcomeSetupTest.php`

  - [ ] 8.2 — Test class setup:
    ```php
    use Illuminate\Foundation\Testing\RefreshDatabase;
    use App\Models\User;
    use App\Models\FlockProfile;
    use App\Models\FlockBatch;
    use App\Models\EggEntry;
    use App\Models\Expense;
    use App\Models\FeedInventory;

    class DashboardWelcomeSetupTest extends TestCase
    {
        use RefreshDatabase;
    }
    ```

  - [ ] 8.3 — `test_welcome_header_shows_user_name_when_present`:
    - Create user with `name => 'Marija'`
    - GET `/app/` → response contains `Welcome Marija`

  - [ ] 8.4 — `test_welcome_header_shows_email_localpart_when_name_is_null`:
    - Create user with `name => null, email => 'farmer@example.com'`
    - GET `/app/` → response contains `Welcome farmer`

  - [ ] 8.5 — `test_welcome_header_shows_email_localpart_when_name_is_empty_string`:
    - Create user with `name => '', email => 'hen.keeper@example.com'`
    - GET `/app/` → response contains `Welcome hen.keeper`

  - [ ] 8.6 — `test_setup_progress_panel_visible_for_new_user`:
    - Create user with no related records
    - GET `/app/` → response contains `id="setup-progress-panel"` and `🚀 Getting Started`

  - [ ] 8.7 — `test_setup_progress_panel_hidden_when_all_items_complete`:
    - Create user with FlockProfile, EggEntry, Expense, FeedInventory
    - GET `/app/` → response does NOT contain `id="setup-progress-panel"`

  - [ ] 8.8 — `test_setup_progress_shows_building_your_farm_bracket_when_flock_profile_exists`:
    - Create user + FlockProfile (50 pts → 42%)
    - GET `/app/` → response contains `📈 Building Your Farm`

  - [ ] 8.9 — `test_setup_progress_shows_advanced_features_bracket`:
    - Create user + FlockProfile + EggEntry + Expense (100 pts → 83%)
    - GET `/app/` → response contains `⚡ Advanced Features`

  - [ ] 8.10 — `test_incomplete_checklist_item_shows_action_button`:
    - Create user with no related records
    - GET `/app/` → response contains link to `route('app.flock.index')` with text `Set up now`

  - [ ] 8.11 — `test_completed_checklist_item_shows_checkmark`:
    - Create user + EggEntry
    - GET `/app/` → response contains `✓` within the first egg item area

  - [ ] 8.12 — `test_dashboard_still_shows_egg_stats_section`:
    - Create user + some egg entries
    - GET `/app/` → response contains `Egg Production` (regression check)

  - [ ] 8.13 — `test_view_receives_display_name_and_setup_progress`:
    - Create user
    - GET `/app/` → `assertViewHas('displayName')`, `assertViewHas('setupProgress')`
    - Assert `$response->viewData('setupProgress')` has keys: `percentage`, `points`, `bracket`, `phase`, `items`

  - [ ] 8.14 — Run tests:
    ```bash
    C:\php83\php.exe artisan test --compact --filter=DashboardWelcomeSetupTest
    ```

---

- [ ] **Task 9: Run Pint and verify no regressions** (All ACs)

  - [ ] 9.1 — Run Pint on modified files:
    ```bash
    vendor/bin/pint --dirty --format agent
    ```
  - [ ] 9.2 — Run the full unit test:
    ```bash
    C:\php83\php.exe artisan test --compact --filter=SetupProgressServiceTest
    ```
  - [ ] 9.3 — Run the full feature test:
    ```bash
    C:\php83\php.exe artisan test --compact --filter=DashboardWelcomeSetupTest
    ```
  - [ ] 9.4 — Run existing dashboard tests to verify no regressions:
    ```bash
    C:\php83\php.exe artisan test --compact --filter=DashboardControllerTest
    ```
  - [ ] 9.5 — Optionally run `pnpm run build` to verify frontend compiles with the new SCSS

---

## Dev Notes

### New File Structure

```
app/
  Services/
    SetupProgressService.php                     (NEW)

resources/
  views/
    dashboard/
      index.blade.php                            (MODIFY — add 2 @include lines)
      partials/
        welcome-header.blade.php                 (NEW)
        setup-progress.blade.php                 (NEW)

  scss/
    features/
      _dashboard.scss                            (MODIFY — append new BEM blocks + keyframes)

app/
  Http/
    Controllers/
      DashboardController.php                    (MODIFY — inject SetupProgressService, compute displayName)

tests/
  Unit/
    Services/
      SetupProgressServiceTest.php               (NEW)
  Feature/
    DashboardWelcomeSetupTest.php                (NEW)
```

### Artisan Commands for File Creation

```bash
C:\php83\php.exe artisan make:class App/Services/SetupProgressService --no-interaction
C:\php83\php.exe artisan make:test --phpunit --unit Services/SetupProgressServiceTest --no-interaction
C:\php83\php.exe artisan make:test --phpunit DashboardWelcomeSetupTest --no-interaction
```

### displayName Resolution Logic

The User model has a `name` column (not `display_name`). The resolution chain is:

```php
$displayName = filled($user->name) ? $user->name : (
    $user->email ? str($user->email)->before('@')->toString() : 'User'
);
```

`filled()` returns `false` for `null`, `''`, and whitespace-only strings — exactly the check needed.

### Progress Points Math

| Completed Items | Points | Percentage | Bracket |
|---|---|---|---|
| None | 0 | 0% | 🚀 Getting Started |
| Flock Profile only | 50 | 42% | 📈 Building Your Farm |
| Flock + Egg | 80 | 67% | 📈 Building Your Farm |
| Flock + Egg + Expense | 100 | 83% | ⚡ Advanced Features |
| All four | 120 | 100% | (panel hidden) |
| Egg only | 30 | 25% | 🚀 Getting Started |
| Expense only | 20 | 17% | 🚀 Getting Started |
| Feed only | 20 | 17% | 🚀 Getting Started |
| Egg + Expense | 50 | 42% | 📈 Building Your Farm |
| Egg + Expense + Feed | 70 | 58% | 📈 Building Your Farm |

Note: It's impossible to reach 91–99% range with the current item set (possible totals are 0, 17, 17, 25, 33, 33, 42, 42, 50, 58, 58, 67, 67, 75, 83, 83, 100). The "🎯 Final Steps" bracket and "Power User" phase at < 100% cannot occur with the defined items, but the code handles the range correctly in case future items are added.

### Flock Profile Predicate — OR Logic

The `hasFlockProfile` predicate is `true` when EITHER a `FlockProfile` record OR any `FlockBatch` record exists. This is intentional: some users jump straight to batch management without creating a legacy flock profile. Both paths count as "has set up their flock."

### HTMX OOB Dismissal (Future Integration)

The panel has `id="setup-progress-panel"` to support OOB swap deletion. When another controller's response triggers the completion of the last checklist item, it can include:

```html
<div id="setup-progress-panel" hx-swap-oob="delete"></div>
```

This story only sets up the `id` attribute. The actual OOB response is wired in when the egg, expense, feed, and flock controllers emit their respective HTMX triggers. This can be handled in a follow-up or as part of each feature controller's existing HTMX response logic.

### Existing Dashboard Content Preserved

The welcome header and setup progress panel are purely additive — they are inserted above the existing Egg Production section. No existing sections are removed or modified. The `recent-activity` HTMX partial continues to work unchanged.

### Cross-Epic Dependencies

- **No upstream dependencies** — this is Story 1 of the Dashboard Replication Epic, and all required models and relationships already exist
- **Downstream:** Stories 2–5 of this epic build on the same `dashboard/index.blade.php` view — no conflicts expected since each story adds new sections below the ones from Story 1
- **`@usd` directive** — NOT needed in this story (no currency display); needed in Story 4 (Financial Overview)

### Cross-Story Integration Map (Epic-Wide)

This section documents how all 5 stories connect, to be read from any story:

**Shared touchpoints:**

| File | Story 1 | Story 2 | Story 3 | Story 4 | Story 5 |
|---|---|---|---|---|---|
| `DashboardController` | Inject `SetupProgressService`, add `$displayName` + `$setupProgress` | Add `$productionMetrics` | Add `$productionChartData` + `$productionChartOptions`; create `data()` method + route | Add `$financialOverview`; extend `data()` with `financial` case | Add `$revenueTrendDesktop` + `$revenueTrendMobile` + `$revenueTrendOptions`; extend `data()` with `analytics` case |
| `DashboardService` | — | `getProductionMetrics()` | `getThirtyDayProductionChart()` | `getFinancialOverview()` | `getWeeklyRevenueTrend()` |
| `dashboard/index.blade.php` | Add welcome-header + setup-progress includes | Add production-metrics include; **replace** old Egg Production section | Replace old egg trend chart with production-chart include | Replace old 5-card financial section with financial-overview include | Add revenue-trend include before Recent Activity |
| `_dashboard.scss` | `__welcome`, `__setup` blocks + keyframes | `__metrics-grid`, `__stat-card--tight`, `__comparison-pill` | `__chart--production`, `__chart-title`, `__section--chart-entry` | `__financial-grid` | `__analytics`, `__chart-subtitle`, `__revenue-trend--desktop/--mobile` |

**Dependency chain:** Story 1 → Story 2 → Story 3 → Story 4 → Story 5

**Shared keyframe:** `dashboardSectionEnter` (defined in Story 1) is reused by Stories 3–5 with different `animation-delay` values.

**JSON data endpoint:** `GET /app/dashboard/data?section=` introduced in Story 3, extended in Story 4 (`financial`) and Story 5 (`analytics`). The `match` expression in `data()` accumulates cases across stories.

### Rollback Plan

- Delete `app/Services/SetupProgressService.php`
- Delete `resources/views/dashboard/partials/welcome-header.blade.php`
- Delete `resources/views/dashboard/partials/setup-progress.blade.php`
- Revert the 2-line `@include` additions in `dashboard/index.blade.php`
- Revert the `DashboardController` changes (remove `SetupProgressService` injection and `$displayName`/`$setupProgress` variables)
- Revert the SCSS additions in `_dashboard.scss`
- Delete test files
- No migrations to reverse
