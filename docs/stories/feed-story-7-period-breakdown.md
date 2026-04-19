# Story: Feed Period Breakdown & Flock-Aware Cost Allocation

## User Story

As a user,
I want to see a detailed breakdown of each feed period with flock-aware cost allocation,
So that I understand the true cost per bird even when my flock size changes mid-bag.

---

## Story Context

**Existing System Integration:**
- Integrates with: `resources/views/feed/index.blade.php`, `app/Http/Controllers/FeedInventoryController.php`, `app/Models/FeedInventory.php`
- Service: `App\Services\FeedStatsService` (created in Story 5) — provides `buildFlockTimeline()`, `getFlockSizeAtDate()`, and time-range-filtered metric methods
- Models: `FlockBatch` (batch_name, acquisition_date, initial_count, current_count, hens_count, roosters_count, chicks_count, brooding_count, is_active), `DeathRecord` (batch_id, date, count, cause, description)
- Technology: Laravel 13, HTMX 2, Alpine.js 3, Blade, SCSS, MariaDB 10.6.22
- Follows pattern: Existing neumorphic card styling from expenses/egg-counter, Alpine expand/collapse from flock batch detail drilldown (Story 5 of flock epic), `@usd` Blade directive for currency formatting via `App\Support\Money::usd()`
- Dependencies: Story 1 (schema/model), Story 5 (FeedStatsService core) — both must be complete before this story begins
- Touch points: Feed Cost Calculator section (below the trends chart from Story 6), new service method `feedPeriodBreakdown()`, new Blade partials

**Reference Implementation (read-only):**
- `d:\Koke\Aplikacija\src\components\features\feed\FeedCostCalculator.tsx` — `FeedPeriod` and `FlockChange` TypeScript interfaces, `FeedPeriodTreeItem` component, bird-days calculation algorithm, "How Calculations Work" info section

**Change Scope (Story 7 only):**
- Add `FeedStatsService@feedPeriodBreakdown()` method with full bird-days sub-period cost allocation algorithm
- Create `period-breakdown.blade.php` partial (section wrapper, heading, expandable list, "How Calculations Work" info)
- Create `period-card.blade.php` partial (collapsed/expanded card with flock changes and sub-period details)
- Alpine.js component for per-card expand/collapse state
- CSS animations for card expand transitions
- Include this section in the feed cost calculator area (below trends chart)

**Out of Scope (handled by other stories):**
- Schema migration, FeedType enum, model updates (Story 1)
- Hero, FormCard, banners (Story 2)
- Paginated table, mark depleted, delete (Story 3)
- Auto-expense creation (Story 4)
- Stat cards, time range selector, FeedStatsService core (Story 5)
- Feed cost trends line chart (Story 6)

---

## Acceptance Criteria

### Functional Requirements — Feed Period Breakdown Section

1. **Section placement:**
   - Rendered below the Feed Cost Trends chart (Story 6) inside the cost calculator area
   - Partial: `resources/views/feed/partials/period-breakdown.blade.php`
   - Root element: `<section id="feed-period-breakdown" class="feed__period-breakdown space-y-6">`

2. **Section heading:**
   - `<h2>` with text **"Feed Period Breakdown"**
   - Classes: `text-2xl font-bold text-gray-900 dark:text-white`
   - Subtitle `<p>`: **"Detailed analysis of each completed feed cycle"**
   - Classes: `text-sm text-gray-500 dark:text-gray-400 mt-1`

3. **Data source:**
   - Controller passes `$feedPeriods` from `FeedStatsService@feedPeriodBreakdown(User $user, ?string $range = '6months')`
   - Only depleted entries shown (where `depleted_date IS NOT NULL`)
   - Sorted by `opened_date DESC` (most recent first)

4. **Empty state:**
   - When no depleted feed periods exist: `<x-ui.empty-state>` with icon "📊", title "No Feed Periods Found", message "Complete feed cycles in Feed Tracker to see cost analysis here."

5. **Entry animation:**
   - Opacity 0 → 1, translateY 20px → 0
   - Delay 0.5s, duration 0.5s
   - Respect `prefers-reduced-motion`

### Functional Requirements — Period Card (Collapsed)

1. **Card wrapper:**
   - Partial: `resources/views/feed/partials/period-card.blade.php`
   - Each card: `<div class="neu-card cursor-pointer transition-all duration-200" @click="toggle({{ $period['id'] }})">`
   - Selected state: `:class="{ 'ring-2 ring-green-500': expanded === {{ $period['id'] }} }"`

2. **4-column grid (collapsed view):**
   - Classes: `grid grid-cols-2 md:grid-cols-4 gap-4 p-4`
   - Column 1 — **Brand:** `$period['brand']` with `$period['feed_type']` as subtitle (text-sm text-gray-500)
   - Column 2 — **Period:** `$period['start_date']` → `$period['end_date']` formatted as `M d, Y` with `$period['duration']` days subtitle
   - Column 3 — **Flock Size:** `$period['flock_size']['total']` birds at start, with breakdown subtitle (e.g., "12 hens, 2 roosters")
   - Column 4 — **Cost/Bird/Month:** `@usd($period['cost_per_bird_per_month'])` in bold green text (`text-green-600 dark:text-green-400 font-semibold`)

3. **Flock changes warning:**
   - When `$period['has_flock_changes']` is true: orange warning badge below the grid
   - Classes: `flex items-center gap-2 px-4 pb-3 text-sm text-orange-600 dark:text-orange-400`
   - Text: **"⚠️ Flock changes during this period"**

4. **Chevron indicator:**
   - Right-aligned chevron SVG that rotates 180° when expanded
   - `:class="{ 'rotate-180': expanded === {{ $period['id'] }} }"`
   - Transition: `transition-transform duration-200`

### Functional Requirements — Period Card (Expanded)

1. **Expand animation:**
   - Alpine `x-show="expanded === {{ $period['id'] }}"` with `x-transition:enter="transition ease-out duration-300"` `x-transition:enter-start="opacity-0 max-h-0"` `x-transition:enter-end="opacity-100 max-h-[2000px]"` `x-transition:leave="transition ease-in duration-200"` `x-transition:leave-start="opacity-100 max-h-[2000px]"` `x-transition:leave-end="opacity-0 max-h-0"`
   - Wrapper: `overflow-hidden`

2. **3-column detail grid:**
   - Classes: `grid grid-cols-1 md:grid-cols-3 gap-6 p-4 pt-0 border-t border-gray-200 dark:border-gray-700`

   **Column 1 — Feed Details:**
   | Label | Value |
   |-------|-------|
   | Brand | `$period['brand']` |
   | Type | `$period['feed_type']` |
   | Quantity | `$period['quantity']` `$period['unit']` |
   | Batch # | `$period['batch_number']` or "—" if null |

   **Column 2 — Consumption:**
   | Label | Value |
   |-------|-------|
   | Duration | `$period['duration']` days |
   | Opened | `$period['start_date']` formatted `M d, Y` |
   | Depleted | `$period['end_date']` formatted `M d, Y` |

   **Column 3 — Cost Analysis:**
   | Label | Value |
   |-------|-------|
   | Total Cost | `@usd($period['total_cost'])` |
   | Cost/Bird/Day | `@usd($period['cost_per_bird_per_day'])` |
   | Cost/Bird/Month | `@usd($period['cost_per_bird_per_month'])` |

3. **Detail label styling:**
   - Labels: `text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider`
   - Values: `text-sm font-semibold text-gray-900 dark:text-white mt-1`

### Functional Requirements — Flock Changes Sub-Section

1. **Visibility:**
   - Only rendered when `$period['has_flock_changes']` is true and `$period['flock_changes']` is non-empty
   - Appears below the 3-column detail grid inside the expanded area

2. **Section heading:**
   - `<h4>` with text **"Flock Changes During This Period"**
   - Classes: `text-sm font-semibold text-gray-700 dark:text-gray-300 mb-3`

3. **Chronological change list:**
   - Each change rendered as a bordered card in chronological order (earliest first)
   - **Acquisition events:** `bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 rounded-lg p-3`
   - **Death events:** `bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-lg p-3`
   - Spacing: `space-y-2`

4. **Change card content (4-column grid on md+):**
   - Column 1 — **Date:** `$change['date']` formatted `M d, Y`
   - Column 2 — **Change:** `+N` (green, font-semibold) for acquisitions, `-N` (red, font-semibold) for deaths
   - Column 3 — **Count:** `$change['previous_count']` → `$change['new_count']` with arrow separator
   - Column 4 — **Details:** `$change['description']` and batch name `$change['batch_name']` (italic, text-xs)

5. **Change type badge:**
   - Acquisition: `<span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-green-100 text-green-800 dark:bg-green-900/40 dark:text-green-300">Acquisition</span>`
   - Death: `<span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-red-100 text-red-800 dark:bg-red-900/40 dark:text-red-300">Death</span>`

### Functional Requirements — Sub-Period Cost Allocation

1. **Simple case (no flock changes during period):**
   - `costPerBirdPerDay = totalCost / duration / flockSize`
   - `costPerBirdPerMonth = costPerBirdPerDay * 30`
   - No sub-periods rendered

2. **Complex case (flock changes during period):**
   - Build timeline with all date boundaries (period start, each change date, period end)
   - For each sub-period between boundaries:
     - `birdDays = flockSizeInSubPeriod × daysInSubPeriod`
   - `totalBirdDays = Σ birdDays` across all sub-periods
   - For each sub-period:
     - `proportionalCost = totalCost × (subPeriodBirdDays / totalBirdDays)`
     - `costPerBirdPerDay = proportionalCost / daysInSubPeriod / flockSizeInSubPeriod`
     - `costPerBirdPerMonth = costPerBirdPerDay × 30`
   - The **overall** `costPerBirdPerDay` for the period header = `totalCost / totalBirdDays`
   - The **overall** `costPerBirdPerMonth` = `overallCostPerBirdPerDay * 30`

3. **Sub-period display (when flock changes exist):**
   - Rendered below the flock changes list, heading: **"Cost Allocation by Sub-Period"**
   - Table with columns: Sub-Period Dates | Days | Flock Size | Bird-Days | Proportional Cost | Cost/Bird/Day
   - Each row: one sub-period between flock change boundaries
   - Footer row: **Totals** showing total days, total bird-days, total cost
   - Table classes: `text-xs` for compact layout, `data-table data-table--compact`

4. **Edge cases:**
   - If `flockSize` is 0 at any point, `costPerBirdPerDay` = 0 (avoid division by zero)
   - If `duration` is 0 (opened and depleted same day), treat as 1 day
   - If `totalBirdDays` is 0, all costs display as $0.00

### Functional Requirements — "How Calculations Work" Info Section

1. **Placement:**
   - Below the feed period list, inside the period-breakdown partial
   - Collapsible via Alpine: collapsed by default

2. **Toggle button:**
   - `<button @click="showInfo = !showInfo" class="flex items-center gap-2 text-sm font-medium text-blue-600 dark:text-blue-400 hover:text-blue-700 dark:hover:text-blue-300">`
   - Text: **"ℹ️ How Calculations Work"** with chevron that rotates on expand

3. **Info cards (3 cards in responsive grid):**
   - Grid: `grid grid-cols-1 md:grid-cols-3 gap-4 mt-4`
   - Each card: `bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg p-4`

   **Card 1 — Basic Cost Allocation:**
   - Title: "Basic Cost Allocation"
   - Content: "When your flock size stays constant during a feed period, the calculation is straightforward: divide the total feed cost by the number of days and the flock size. This gives you a daily cost per bird, multiplied by 30 for the monthly rate."
   - Formula: `Cost/Bird/Day = Total Cost ÷ Days ÷ Flock Size`

   **Card 2 — Flock-Aware Allocation:**
   - Title: "Flock-Change-Aware Allocation"
   - Content: "When birds are added or lost during a feed period, the cost must be distributed proportionally. More birds consuming feed for more days should bear a larger share of the cost. We split the period at each flock change and allocate costs based on bird-days."

   **Card 3 — Bird-Days Concept:**
   - Title: "Understanding Bird-Days"
   - Content: "Bird-days measure total feed consumption capacity. If 10 birds eat for 5 days, that's 50 bird-days. If the flock grows to 15 birds for the next 3 days, that's 45 more bird-days — totaling 95 bird-days. Each sub-period's share of the total cost equals its share of total bird-days."
   - Example: `10 birds × 5 days = 50 bird-days` / `15 birds × 3 days = 45 bird-days` / `Total: 95 bird-days`

### Integration Requirements

1. **FeedStatsService dependency:**
   - `feedPeriodBreakdown()` calls `buildFlockTimeline()` and `getFlockSizeAtDate()` (both from Story 5)
   - If Story 5 methods are not yet implemented, this story is blocked

2. **Controller integration:**
   - `FeedInventoryController@stats` (created in Story 5) extended to include `feedPeriods` key in the JSON response
   - Alternatively, `feedPeriodBreakdown()` is called in `index()` and passed to the view directly (non-AJAX approach)
   - Decision: **Pass via view** (`$feedPeriods = $feedStatsService->feedPeriodBreakdown($user, $range)`) since the breakdown is rendered server-side in Blade, not fetched client-side

3. **Authorization:**
   - All queries scoped to `auth()->user()` — enforced inside `FeedStatsService`
   - FlockBatch and DeathRecord queries filter by `user_id` to prevent cross-user data leakage

4. **Time range filtering:**
   - Period breakdown respects the same time range selector from Story 5 (3m / 6m / 12m / All)
   - Range passed as query parameter `?range=6months`
   - Filters feed periods where `opened_date` falls within the selected range

5. **Story 5/6 handshake:**
   - This story adds to the cost calculator section; it does not replace anything from Stories 5 or 6
   - The time range selector triggers an HTMX request that refreshes stat cards (Story 5), chart (Story 6), AND period breakdown (this story) together

### Quality Requirements

1. **Tests:**
   - Unit tests for `FeedStatsService@feedPeriodBreakdown()` covering: simple case (no flock changes), complex case (multiple changes), edge cases (zero flock, zero duration, single day period)
   - Unit tests for bird-days calculation algorithm accuracy
   - Feature test: authenticated user sees period breakdown section with correct data
   - Feature test: empty state shown when no depleted feed entries
   - Feature test: flock changes warning renders when applicable
   - All existing feed tests continue to pass

2. **Performance:**
   - Period breakdown query count < 5 for a user with 10 feed periods and 3 flock batches
   - Eager-load flock batches and death records to prevent N+1
   - Use `buildFlockTimeline()` once per call, not per period

3. **Accessibility:**
   - Expand/collapse buttons have `aria-expanded` bound to Alpine state
   - `aria-controls` references the expandable region ID
   - Flock change badges use `aria-label` describing the change type

4. **Responsiveness:**
   - 4-column collapsed grid → 2-column on mobile (`grid-cols-2 md:grid-cols-4`)
   - 3-column detail grid → stacked on mobile (`grid-cols-1 md:grid-cols-3`)
   - Sub-period table horizontally scrollable on mobile (`overflow-x-auto`)

5. **Dark mode:**
   - All backgrounds, borders, and text colors have `dark:` variants as specified
   - Flock change cards use `/20` opacity dark variants for subtle backgrounds

---

## Technical Notes

### File Changes Summary

| File | Action | Description |
|------|--------|-------------|
| `app/Services/FeedStatsService.php` | MODIFY | Add `feedPeriodBreakdown()`, `buildSubPeriods()`, `calculateBirdDays()` methods |
| `app/Http/Controllers/FeedInventoryController.php` | MODIFY | Pass `$feedPeriods` to view in `index()` or `stats()` |
| `resources/views/feed/partials/period-breakdown.blade.php` | CREATE | Section wrapper, heading, period list loop, empty state, "How Calculations Work" info |
| `resources/views/feed/partials/period-card.blade.php` | CREATE | Collapsed/expanded card with flock changes and sub-period table |
| `resources/views/feed/partials/cost-calculator.blade.php` | MODIFY | Include `period-breakdown` partial below the trends chart |
| `resources/js/alpine/feed-period-breakdown.js` | CREATE | Alpine component for expand/collapse and info toggle |
| `resources/scss/features/_feed.scss` | MODIFY | Add period breakdown entry animation, card transitions |
| `tests/Unit/FeedPeriodBreakdownTest.php` | CREATE | Unit tests for bird-days algorithm and period breakdown service method |
| `tests/Feature/FeedPeriodBreakdownTest.php` | CREATE | Feature tests for section rendering, empty state, flock change display |

### FeedStatsService — `feedPeriodBreakdown()` Implementation Sketch

```php
<?php
// In app/Services/FeedStatsService.php

/**
 * @return array<int, array{
 *   id: int,
 *   brand: string,
 *   feed_type: string,
 *   quantity: string,
 *   unit: string,
 *   batch_number: ?string,
 *   total_cost: float,
 *   start_date: string,
 *   end_date: string,
 *   duration: int,
 *   flock_size: array{total: int, hens: int, roosters: int, chicks: int, brooding: int},
 *   cost_per_bird_per_day: float,
 *   cost_per_bird_per_month: float,
 *   has_flock_changes: bool,
 *   flock_changes: array<int, array{
 *     date: string,
 *     type: string,
 *     change_amount: int,
 *     previous_count: int,
 *     new_count: int,
 *     description: string,
 *     batch_name: ?string
 *   }>,
 *   sub_periods: array<int, array{
 *     start_date: string,
 *     end_date: string,
 *     days: int,
 *     flock_size: int,
 *     bird_days: int,
 *     proportional_cost: float,
 *     cost_per_bird_per_day: float,
 *     cost_per_bird_per_month: float
 *   }>
 * }>
 */
public function feedPeriodBreakdown(User $user, ?string $range = '6months'): array
{
    $rangeStart = $this->resolveRangeStart($range);

    // 1. Fetch all depleted feed entries in range, eager-load nothing (feed has no relations needed)
    $depletedEntries = FeedInventory::where('user_id', $user->id)
        ->whereNotNull('depleted_date')
        ->when($rangeStart, fn ($q) => $q->where('opened_date', '>=', $rangeStart))
        ->orderByDesc('opened_date')
        ->get();

    if ($depletedEntries->isEmpty()) {
        return [];
    }

    // 2. Build flock timeline once (reuse from Story 5)
    $flockTimeline = $this->buildFlockTimeline($user);

    // 3. Map each entry to a period with cost allocation
    return $depletedEntries->map(function (FeedInventory $entry) use ($flockTimeline) {
        $startDate = $entry->opened_date;
        $endDate = $entry->depleted_date;
        $duration = max(1, $startDate->diffInDays($endDate));
        $totalCost = (float) $entry->total_cost;

        // Get flock size at period start
        $flockSizeAtStart = $this->getFlockSizeAtDate($flockTimeline, $startDate);

        // Find flock changes during this period
        $flockChanges = $this->getFlockChangesDuringPeriod(
            $flockTimeline,
            $startDate,
            $endDate
        );

        $hasFlockChanges = count($flockChanges) > 0;

        if ($hasFlockChanges) {
            // Complex case: bird-days allocation
            $subPeriods = $this->buildSubPeriods(
                $flockTimeline,
                $startDate,
                $endDate,
                $totalCost
            );

            $totalBirdDays = array_sum(array_column($subPeriods, 'bird_days'));
            $costPerBirdPerDay = $totalBirdDays > 0
                ? $totalCost / $totalBirdDays
                : 0.0;
        } else {
            // Simple case: uniform flock
            $flockTotal = $flockSizeAtStart['total'];
            $costPerBirdPerDay = ($flockTotal > 0 && $duration > 0)
                ? $totalCost / $duration / $flockTotal
                : 0.0;
            $subPeriods = [];
        }

        $costPerBirdPerMonth = $costPerBirdPerDay * 30;

        return [
            'id'                     => $entry->id,
            'brand'                  => $entry->brand,
            'feed_type'              => $entry->feed_type?->value ?? $entry->feed_type,
            'quantity'               => $entry->quantity,
            'unit'                   => $entry->unit,
            'batch_number'           => $entry->batch_number,
            'total_cost'             => $totalCost,
            'start_date'             => $startDate->toDateString(),
            'end_date'               => $endDate->toDateString(),
            'duration'               => $duration,
            'flock_size'             => $flockSizeAtStart,
            'cost_per_bird_per_day'  => round($costPerBirdPerDay, 4),
            'cost_per_bird_per_month' => round($costPerBirdPerMonth, 2),
            'has_flock_changes'      => $hasFlockChanges,
            'flock_changes'          => $flockChanges,
            'sub_periods'            => $subPeriods,
        ];
    })->values()->toArray();
}

/**
 * Build sub-periods between flock change boundaries and allocate cost proportionally.
 *
 * @return array<int, array{start_date: string, end_date: string, days: int, flock_size: int, bird_days: int, proportional_cost: float, cost_per_bird_per_day: float, cost_per_bird_per_month: float}>
 */
private function buildSubPeriods(
    Collection $flockTimeline,
    Carbon $periodStart,
    Carbon $periodEnd,
    float $totalCost
): array {
    // Collect all boundary dates: period start, each change date, period end
    $boundaries = collect([$periodStart->copy()]);

    foreach ($flockTimeline as $event) {
        $eventDate = Carbon::parse($event['date']);
        if ($eventDate->gt($periodStart) && $eventDate->lt($periodEnd)) {
            $boundaries->push($eventDate->copy());
        }
    }

    $boundaries->push($periodEnd->copy());
    $boundaries = $boundaries->unique(fn ($d) => $d->toDateString())->sort()->values();

    // Build sub-periods
    $subPeriods = [];
    for ($i = 0; $i < $boundaries->count() - 1; $i++) {
        $subStart = $boundaries[$i];
        $subEnd = $boundaries[$i + 1];
        $days = max(1, $subStart->diffInDays($subEnd));
        $flockSize = $this->getFlockSizeAtDate($flockTimeline, $subStart)['total'];
        $birdDays = $flockSize * $days;

        $subPeriods[] = [
            'start_date' => $subStart->toDateString(),
            'end_date'   => $subEnd->toDateString(),
            'days'       => $days,
            'flock_size' => $flockSize,
            'bird_days'  => $birdDays,
        ];
    }

    // Calculate total bird-days and allocate cost proportionally
    $totalBirdDays = array_sum(array_column($subPeriods, 'bird_days'));

    foreach ($subPeriods as &$sub) {
        if ($totalBirdDays > 0 && $sub['bird_days'] > 0) {
            $sub['proportional_cost'] = round($totalCost * ($sub['bird_days'] / $totalBirdDays), 2);
            $sub['cost_per_bird_per_day'] = round(
                $sub['proportional_cost'] / $sub['days'] / $sub['flock_size'],
                4
            );
        } else {
            $sub['proportional_cost'] = 0.0;
            $sub['cost_per_bird_per_day'] = 0.0;
        }
        $sub['cost_per_bird_per_month'] = round($sub['cost_per_bird_per_day'] * 30, 2);
    }
    unset($sub);

    return $subPeriods;
}

/**
 * Find all flock changes (acquisitions + deaths) that occur strictly within a period.
 *
 * @return array<int, array{date: string, type: string, change_amount: int, previous_count: int, new_count: int, description: string, batch_name: ?string}>
 */
private function getFlockChangesDuringPeriod(
    Collection $flockTimeline,
    Carbon $periodStart,
    Carbon $periodEnd
): array {
    return $flockTimeline
        ->filter(function (array $event) use ($periodStart, $periodEnd) {
            $eventDate = Carbon::parse($event['date']);
            return $eventDate->gt($periodStart) && $eventDate->lt($periodEnd);
        })
        ->sortBy('date')
        ->map(fn (array $event) => [
            'date'           => $event['date'],
            'type'           => $event['type'],        // 'acquisition' or 'death'
            'change_amount'  => $event['change_amount'],
            'previous_count' => $event['previous_count'],
            'new_count'      => $event['new_count'],
            'description'    => $event['description'],
            'batch_name'     => $event['batch_name'] ?? null,
        ])
        ->values()
        ->toArray();
}
```

### Alpine.js Component — `feedPeriodBreakdown()`

```js
// resources/js/alpine/feed-period-breakdown.js

export function feedPeriodBreakdown() {
    return {
        /** @type {number|null} ID of the currently expanded period card */
        expanded: null,

        /** @type {boolean} Whether the "How Calculations Work" info section is visible */
        showInfo: false,

        /**
         * Toggle a period card's expanded state.
         * Clicking the same card again collapses it.
         * @param {number} periodId
         */
        toggle(periodId) {
            this.expanded = this.expanded === periodId ? null : periodId;
        },

        /**
         * Check if a specific period is currently expanded.
         * @param {number} periodId
         * @returns {boolean}
         */
        isExpanded(periodId) {
            return this.expanded === periodId;
        },

        /**
         * Toggle the "How Calculations Work" info section.
         */
        toggleInfo() {
            this.showInfo = !this.showInfo;
        },
    };
}
```

**Alpine x-data shape (inline equivalent):**

```js
x-data="{
    expanded: null,
    showInfo: false,
    toggle(id) { this.expanded = this.expanded === id ? null : id },
    isExpanded(id) { return this.expanded === id },
    toggleInfo() { this.showInfo = !this.showInfo }
}"
```

### Blade Partial Sketch — `period-breakdown.blade.php`

```blade
{{-- resources/views/feed/partials/period-breakdown.blade.php --}}
<section id="feed-period-breakdown"
         class="feed__period-breakdown space-y-6"
         x-data="feedPeriodBreakdown()">

    {{-- Heading --}}
    <div>
        <h2 class="text-2xl font-bold text-gray-900 dark:text-white">Feed Period Breakdown</h2>
        <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
            Detailed analysis of each completed feed cycle
        </p>
    </div>

    {{-- Period list or empty state --}}
    @if(count($feedPeriods) === 0)
        <x-ui.empty-state icon="📊"
                          title="No Feed Periods Found"
                          message="Complete feed cycles in Feed Tracker to see cost analysis here." />
    @else
        <div class="space-y-4">
            @foreach($feedPeriods as $period)
                @include('feed.partials.period-card', ['period' => $period])
            @endforeach
        </div>
    @endif

    {{-- "How Calculations Work" info section --}}
    <div class="mt-8">
        <button @click="toggleInfo()"
                class="flex items-center gap-2 text-sm font-medium text-blue-600 dark:text-blue-400 hover:text-blue-700 dark:hover:text-blue-300 transition-colors"
                :aria-expanded="showInfo">
            <span>ℹ️ How Calculations Work</span>
            <svg class="h-4 w-4 transition-transform duration-200"
                 :class="{ 'rotate-180': showInfo }"
                 fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
            </svg>
        </button>

        <div x-show="showInfo"
             x-transition:enter="transition ease-out duration-300"
             x-transition:enter-start="opacity-0 max-h-0"
             x-transition:enter-end="opacity-100 max-h-[1000px]"
             x-transition:leave="transition ease-in duration-200"
             x-transition:leave-start="opacity-100 max-h-[1000px]"
             x-transition:leave-end="opacity-0 max-h-0"
             class="overflow-hidden">

            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mt-4">
                {{-- Card 1: Basic Cost Allocation --}}
                <div class="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg p-4">
                    <h4 class="text-sm font-semibold text-blue-800 dark:text-blue-300 mb-2">Basic Cost Allocation</h4>
                    <p class="text-xs text-blue-700 dark:text-blue-400 mb-3">
                        When your flock size stays constant during a feed period, the calculation is straightforward:
                        divide the total feed cost by the number of days and the flock size. This gives you a daily
                        cost per bird, multiplied by 30 for the monthly rate.
                    </p>
                    <code class="block text-xs bg-blue-100 dark:bg-blue-900/40 rounded px-2 py-1 text-blue-800 dark:text-blue-300">
                        Cost/Bird/Day = Total Cost ÷ Days ÷ Flock Size
                    </code>
                </div>

                {{-- Card 2: Flock-Aware Allocation --}}
                <div class="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg p-4">
                    <h4 class="text-sm font-semibold text-blue-800 dark:text-blue-300 mb-2">Flock-Change-Aware Allocation</h4>
                    <p class="text-xs text-blue-700 dark:text-blue-400">
                        When birds are added or lost during a feed period, the cost must be distributed proportionally.
                        More birds consuming feed for more days should bear a larger share of the cost. We split the period
                        at each flock change and allocate costs based on bird-days.
                    </p>
                </div>

                {{-- Card 3: Bird-Days Concept --}}
                <div class="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg p-4">
                    <h4 class="text-sm font-semibold text-blue-800 dark:text-blue-300 mb-2">Understanding Bird-Days</h4>
                    <p class="text-xs text-blue-700 dark:text-blue-400 mb-3">
                        Bird-days measure total feed consumption capacity. If 10 birds eat for 5 days, that's 50 bird-days.
                        If the flock grows to 15 birds for the next 3 days, that's 45 more bird-days — totaling 95 bird-days.
                        Each sub-period's share of the total cost equals its share of total bird-days.
                    </p>
                    <div class="text-xs bg-blue-100 dark:bg-blue-900/40 rounded px-2 py-1 text-blue-800 dark:text-blue-300 space-y-0.5">
                        <div>10 birds × 5 days = 50 bird-days</div>
                        <div>15 birds × 3 days = 45 bird-days</div>
                        <div class="font-semibold border-t border-blue-200 dark:border-blue-700 pt-0.5 mt-0.5">Total: 95 bird-days</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
```

### Blade Partial Sketch — `period-card.blade.php`

```blade
{{-- resources/views/feed/partials/period-card.blade.php --}}
@php
    $startFormatted = \Carbon\Carbon::parse($period['start_date'])->format('M d, Y');
    $endFormatted = \Carbon\Carbon::parse($period['end_date'])->format('M d, Y');
@endphp

<div class="neu-card transition-all duration-200"
     :class="{ 'ring-2 ring-green-500': expanded === {{ $period['id'] }} }">

    {{-- Collapsed header (always visible) --}}
    <div class="cursor-pointer p-4" @click="toggle({{ $period['id'] }})">
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4 items-center">
            {{-- Brand --}}
            <div>
                <p class="text-sm font-semibold text-gray-900 dark:text-white">{{ $period['brand'] }}</p>
                <p class="text-xs text-gray-500 dark:text-gray-400">{{ $period['feed_type'] }}</p>
            </div>

            {{-- Period dates --}}
            <div>
                <p class="text-sm text-gray-900 dark:text-white">{{ $startFormatted }} → {{ $endFormatted }}</p>
                <p class="text-xs text-gray-500 dark:text-gray-400">{{ $period['duration'] }} days</p>
            </div>

            {{-- Flock size --}}
            <div>
                <p class="text-sm font-medium text-gray-900 dark:text-white">{{ $period['flock_size']['total'] }} birds</p>
                <p class="text-xs text-gray-500 dark:text-gray-400">
                    {{ $period['flock_size']['hens'] }} hens, {{ $period['flock_size']['roosters'] }} roosters
                </p>
            </div>

            {{-- Cost/bird/month + chevron --}}
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-semibold text-green-600 dark:text-green-400">
                        @usd($period['cost_per_bird_per_month'])
                    </p>
                    <p class="text-xs text-gray-500 dark:text-gray-400">per bird/month</p>
                </div>
                <svg class="h-5 w-5 text-gray-400 transition-transform duration-200 flex-shrink-0"
                     :class="{ 'rotate-180': expanded === {{ $period['id'] }} }"
                     fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                </svg>
            </div>
        </div>

        {{-- Flock changes warning --}}
        @if($period['has_flock_changes'])
            <div class="flex items-center gap-2 mt-3 text-sm text-orange-600 dark:text-orange-400">
                <span>⚠️ Flock changes during this period</span>
            </div>
        @endif
    </div>

    {{-- Expanded detail --}}
    <div x-show="expanded === {{ $period['id'] }}"
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0 max-h-0"
         x-transition:enter-end="opacity-100 max-h-[2000px]"
         x-transition:leave="transition ease-in duration-200"
         x-transition:leave-start="opacity-100 max-h-[2000px]"
         x-transition:leave-end="opacity-0 max-h-0"
         class="overflow-hidden"
         :aria-expanded="expanded === {{ $period['id'] }}"
         id="period-detail-{{ $period['id'] }}">

        {{-- 3-column detail grid --}}
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 p-4 pt-0 border-t border-gray-200 dark:border-gray-700">
            {{-- Feed Details --}}
            <div class="space-y-3">
                <h4 class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Feed Details</h4>
                <dl class="space-y-2">
                    <div>
                        <dt class="text-xs text-gray-500 dark:text-gray-400">Brand</dt>
                        <dd class="text-sm font-semibold text-gray-900 dark:text-white mt-0.5">{{ $period['brand'] }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs text-gray-500 dark:text-gray-400">Type</dt>
                        <dd class="text-sm font-semibold text-gray-900 dark:text-white mt-0.5">{{ $period['feed_type'] }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs text-gray-500 dark:text-gray-400">Quantity</dt>
                        <dd class="text-sm font-semibold text-gray-900 dark:text-white mt-0.5">{{ $period['quantity'] }} {{ $period['unit'] }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs text-gray-500 dark:text-gray-400">Batch #</dt>
                        <dd class="text-sm font-semibold text-gray-900 dark:text-white mt-0.5">{{ $period['batch_number'] ?? '—' }}</dd>
                    </div>
                </dl>
            </div>

            {{-- Consumption --}}
            <div class="space-y-3">
                <h4 class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Consumption</h4>
                <dl class="space-y-2">
                    <div>
                        <dt class="text-xs text-gray-500 dark:text-gray-400">Duration</dt>
                        <dd class="text-sm font-semibold text-gray-900 dark:text-white mt-0.5">{{ $period['duration'] }} days</dd>
                    </div>
                    <div>
                        <dt class="text-xs text-gray-500 dark:text-gray-400">Opened</dt>
                        <dd class="text-sm font-semibold text-gray-900 dark:text-white mt-0.5">{{ $startFormatted }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs text-gray-500 dark:text-gray-400">Depleted</dt>
                        <dd class="text-sm font-semibold text-gray-900 dark:text-white mt-0.5">{{ $endFormatted }}</dd>
                    </div>
                </dl>
            </div>

            {{-- Cost Analysis --}}
            <div class="space-y-3">
                <h4 class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Cost Analysis</h4>
                <dl class="space-y-2">
                    <div>
                        <dt class="text-xs text-gray-500 dark:text-gray-400">Total Cost</dt>
                        <dd class="text-sm font-semibold text-gray-900 dark:text-white mt-0.5">@usd($period['total_cost'])</dd>
                    </div>
                    <div>
                        <dt class="text-xs text-gray-500 dark:text-gray-400">Cost/Bird/Day</dt>
                        <dd class="text-sm font-semibold text-gray-900 dark:text-white mt-0.5">@usd($period['cost_per_bird_per_day'])</dd>
                    </div>
                    <div>
                        <dt class="text-xs text-gray-500 dark:text-gray-400">Cost/Bird/Month</dt>
                        <dd class="text-sm font-semibold text-green-600 dark:text-green-400 mt-0.5">@usd($period['cost_per_bird_per_month'])</dd>
                    </div>
                </dl>
            </div>
        </div>

        {{-- Flock changes sub-section --}}
        @if($period['has_flock_changes'] && count($period['flock_changes']) > 0)
            <div class="px-4 pb-4 space-y-4">
                <h4 class="text-sm font-semibold text-gray-700 dark:text-gray-300">Flock Changes During This Period</h4>

                <div class="space-y-2">
                    @foreach($period['flock_changes'] as $change)
                        <div class="rounded-lg p-3 {{ $change['type'] === 'acquisition'
                            ? 'bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800'
                            : 'bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800' }}">
                            <div class="grid grid-cols-2 md:grid-cols-4 gap-3 items-center">
                                {{-- Date --}}
                                <div>
                                    <p class="text-xs text-gray-500 dark:text-gray-400">Date</p>
                                    <p class="text-sm font-medium text-gray-900 dark:text-white">
                                        {{ \Carbon\Carbon::parse($change['date'])->format('M d, Y') }}
                                    </p>
                                </div>

                                {{-- Change amount with badge --}}
                                <div>
                                    @if($change['type'] === 'acquisition')
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-green-100 text-green-800 dark:bg-green-900/40 dark:text-green-300">
                                            Acquisition
                                        </span>
                                        <p class="text-sm font-semibold text-green-600 dark:text-green-400 mt-1">
                                            +{{ $change['change_amount'] }}
                                        </p>
                                    @else
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-red-100 text-red-800 dark:bg-red-900/40 dark:text-red-300">
                                            Death
                                        </span>
                                        <p class="text-sm font-semibold text-red-600 dark:text-red-400 mt-1">
                                            -{{ $change['change_amount'] }}
                                        </p>
                                    @endif
                                </div>

                                {{-- Previous → New count --}}
                                <div>
                                    <p class="text-xs text-gray-500 dark:text-gray-400">Flock Count</p>
                                    <p class="text-sm text-gray-900 dark:text-white">
                                        {{ $change['previous_count'] }} → {{ $change['new_count'] }}
                                    </p>
                                </div>

                                {{-- Description + batch name --}}
                                <div>
                                    <p class="text-sm text-gray-700 dark:text-gray-300">{{ $change['description'] }}</p>
                                    @if($change['batch_name'])
                                        <p class="text-xs italic text-gray-500 dark:text-gray-400">{{ $change['batch_name'] }}</p>
                                    @endif
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>

                {{-- Sub-period cost allocation table --}}
                @if(count($period['sub_periods']) > 0)
                    <div class="mt-4">
                        <h4 class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-3">Cost Allocation by Sub-Period</h4>
                        <div class="overflow-x-auto">
                            <table class="data-table data-table--compact text-xs w-full">
                                <thead class="data-table__head">
                                    <tr>
                                        <th class="data-table__header">Sub-Period</th>
                                        <th class="data-table__header text-right">Days</th>
                                        <th class="data-table__header text-right">Flock Size</th>
                                        <th class="data-table__header text-right">Bird-Days</th>
                                        <th class="data-table__header text-right">Cost Share</th>
                                        <th class="data-table__header text-right">Cost/Bird/Day</th>
                                    </tr>
                                </thead>
                                <tbody class="data-table__body">
                                    @foreach($period['sub_periods'] as $sub)
                                        <tr>
                                            <td class="data-table__cell">
                                                {{ \Carbon\Carbon::parse($sub['start_date'])->format('M d') }}
                                                → {{ \Carbon\Carbon::parse($sub['end_date'])->format('M d') }}
                                            </td>
                                            <td class="data-table__cell text-right">{{ $sub['days'] }}</td>
                                            <td class="data-table__cell text-right">{{ $sub['flock_size'] }}</td>
                                            <td class="data-table__cell text-right">{{ number_format($sub['bird_days']) }}</td>
                                            <td class="data-table__cell text-right">@usd($sub['proportional_cost'])</td>
                                            <td class="data-table__cell text-right">@usd($sub['cost_per_bird_per_day'])</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                                <tfoot>
                                    <tr class="font-semibold border-t border-gray-300 dark:border-gray-600">
                                        <td class="data-table__cell">Totals</td>
                                        <td class="data-table__cell text-right">{{ $period['duration'] }}</td>
                                        <td class="data-table__cell text-right">—</td>
                                        <td class="data-table__cell text-right">{{ number_format(array_sum(array_column($period['sub_periods'], 'bird_days'))) }}</td>
                                        <td class="data-table__cell text-right">@usd($period['total_cost'])</td>
                                        <td class="data-table__cell text-right">@usd($period['cost_per_bird_per_day'])</td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>
                @endif
            </div>
        @endif
    </div>
</div>
```

### SCSS Additions — `_feed.scss`

```scss
// Period breakdown entry animation
.feed__period-breakdown {
    opacity: 0;
    transform: translateY(20px);

    &--enter {
        animation: feed-period-enter 0.5s ease-out 0.5s forwards;
    }
}

@keyframes feed-period-enter {
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

@media (prefers-reduced-motion: reduce) {
    .feed__period-breakdown {
        opacity: 1;
        transform: none;
        animation: none;
    }
}
```

### Test Outlines

#### Unit Test — `tests/Unit/FeedPeriodBreakdownTest.php`

```php
<?php

namespace Tests\Unit;

use App\Models\DeathRecord;
use App\Models\FeedInventory;
use App\Models\FlockBatch;
use App\Models\User;
use App\Services\FeedStatsService;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class FeedPeriodBreakdownTest extends TestCase
{
    use LazilyRefreshDatabase;

    private User $user;
    private FeedStatsService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->premium()->create();
        $this->service = app(FeedStatsService::class);
    }

    /** Simple case: no flock changes during feed period */
    public function test_simple_period_cost_allocation_without_flock_changes(): void
    {
        // Given: a flock of 10 birds acquired before the feed period
        FlockBatch::factory()->for($this->user)->create([
            'acquisition_date' => '2026-01-01',
            'initial_count'    => 10,
            'current_count'    => 10,
            'hens_count'       => 8,
            'roosters_count'   => 2,
            'is_active'        => true,
        ]);

        // And: a depleted feed entry spanning 10 days, costing $50
        FeedInventory::factory()->for($this->user)->depleted()->create([
            'brand'         => 'Layer Pellets',
            'total_cost'    => 50.00,
            'opened_date'   => '2026-02-01',
            'depleted_date' => '2026-02-11',
        ]);

        $result = $this->service->feedPeriodBreakdown($this->user, 'all');

        $this->assertCount(1, $result);
        $period = $result[0];

        // costPerBirdPerDay = 50 / 10 days / 10 birds = 0.50
        $this->assertEquals(0.50, $period['cost_per_bird_per_day']);
        // costPerBirdPerMonth = 0.50 * 30 = 15.00
        $this->assertEquals(15.00, $period['cost_per_bird_per_month']);
        $this->assertFalse($period['has_flock_changes']);
        $this->assertEmpty($period['sub_periods']);
    }

    /** Complex case: death during feed period splits into sub-periods */
    public function test_complex_period_with_death_during_feed_period(): void
    {
        // Given: 20 birds acquired Jan 1
        FlockBatch::factory()->for($this->user)->create([
            'acquisition_date' => '2026-01-01',
            'initial_count'    => 20,
            'current_count'    => 17,
            'hens_count'       => 15,
            'roosters_count'   => 5,
            'is_active'        => true,
        ]);

        // And: 3 deaths on Feb 6 (mid-period)
        DeathRecord::factory()->for($this->user)->create([
            'date'  => '2026-02-06',
            'count' => 3,
        ]);

        // And: feed from Feb 1-11 (10 days), $100 total
        FeedInventory::factory()->for($this->user)->depleted()->create([
            'total_cost'    => 100.00,
            'opened_date'   => '2026-02-01',
            'depleted_date' => '2026-02-11',
        ]);

        $result = $this->service->feedPeriodBreakdown($this->user, 'all');

        $this->assertCount(1, $result);
        $period = $result[0];
        $this->assertTrue($period['has_flock_changes']);
        $this->assertCount(1, $period['flock_changes']);
        $this->assertCount(2, $period['sub_periods']);

        // Sub-period 1: Feb 1-6 = 5 days, 20 birds = 100 bird-days
        // Sub-period 2: Feb 6-11 = 5 days, 17 birds = 85 bird-days
        // Total: 185 bird-days
        $this->assertEquals(100, $period['sub_periods'][0]['bird_days']);
        $this->assertEquals(85, $period['sub_periods'][1]['bird_days']);

        // Proportional cost: sub1 = 100 * (100/185) ≈ 54.05
        // Proportional cost: sub2 = 100 * (85/185) ≈ 45.95
        $this->assertEqualsWithDelta(54.05, $period['sub_periods'][0]['proportional_cost'], 0.01);
        $this->assertEqualsWithDelta(45.95, $period['sub_periods'][1]['proportional_cost'], 0.01);
    }

    /** Complex case: acquisition during feed period */
    public function test_complex_period_with_acquisition_during_feed_period(): void
    {
        // Given: 10 birds acquired Jan 1
        FlockBatch::factory()->for($this->user)->create([
            'acquisition_date' => '2026-01-01',
            'initial_count'    => 10,
            'current_count'    => 10,
            'is_active'        => true,
        ]);

        // And: 5 more birds acquired Feb 4 (during feed period)
        FlockBatch::factory()->for($this->user)->create([
            'acquisition_date' => '2026-02-04',
            'initial_count'    => 5,
            'current_count'    => 5,
            'is_active'        => true,
        ]);

        // And: feed from Feb 1-9 (8 days), $80 total
        FeedInventory::factory()->for($this->user)->depleted()->create([
            'total_cost'    => 80.00,
            'opened_date'   => '2026-02-01',
            'depleted_date' => '2026-02-09',
        ]);

        $result = $this->service->feedPeriodBreakdown($this->user, 'all');

        $period = $result[0];
        $this->assertTrue($period['has_flock_changes']);

        // Sub-period 1: Feb 1-4 = 3 days, 10 birds = 30 bird-days
        // Sub-period 2: Feb 4-9 = 5 days, 15 birds = 75 bird-days
        // Total: 105 bird-days
        $this->assertEquals(30, $period['sub_periods'][0]['bird_days']);
        $this->assertEquals(75, $period['sub_periods'][1]['bird_days']);
    }

    /** Edge case: zero flock size at period start */
    public function test_zero_flock_returns_zero_cost_per_bird(): void
    {
        // No flock batches — flock size = 0
        FeedInventory::factory()->for($this->user)->depleted()->create([
            'total_cost'    => 50.00,
            'opened_date'   => '2026-03-01',
            'depleted_date' => '2026-03-11',
        ]);

        $result = $this->service->feedPeriodBreakdown($this->user, 'all');

        $this->assertCount(1, $result);
        $this->assertEquals(0.0, $result[0]['cost_per_bird_per_day']);
        $this->assertEquals(0.0, $result[0]['cost_per_bird_per_month']);
    }

    /** Edge case: same-day open/deplete treated as 1 day */
    public function test_same_day_period_treated_as_one_day(): void
    {
        FlockBatch::factory()->for($this->user)->create([
            'acquisition_date' => '2026-01-01',
            'current_count'    => 10,
            'is_active'        => true,
        ]);

        FeedInventory::factory()->for($this->user)->depleted()->create([
            'total_cost'    => 30.00,
            'opened_date'   => '2026-03-01',
            'depleted_date' => '2026-03-01',
        ]);

        $result = $this->service->feedPeriodBreakdown($this->user, 'all');

        // duration forced to 1, so 30 / 1 / 10 = 3.0
        $this->assertEquals(3.0, $result[0]['cost_per_bird_per_day']);
    }

    /** Only depleted entries are included */
    public function test_only_depleted_entries_returned(): void
    {
        FlockBatch::factory()->for($this->user)->create([
            'acquisition_date' => '2026-01-01',
            'current_count'    => 10,
            'is_active'        => true,
        ]);

        // Active entry (no depleted_date)
        FeedInventory::factory()->for($this->user)->active()->create([
            'opened_date' => '2026-03-01',
        ]);

        // Depleted entry
        FeedInventory::factory()->for($this->user)->depleted()->create([
            'opened_date'   => '2026-02-01',
            'depleted_date' => '2026-02-15',
        ]);

        $result = $this->service->feedPeriodBreakdown($this->user, 'all');

        $this->assertCount(1, $result);
    }

    /** Empty result when no depleted entries exist */
    public function test_empty_array_when_no_depleted_entries(): void
    {
        $result = $this->service->feedPeriodBreakdown($this->user, 'all');

        $this->assertIsArray($result);
        $this->assertEmpty($result);
    }

    /** Periods sorted by opened_date DESC */
    public function test_periods_sorted_by_opened_date_descending(): void
    {
        FlockBatch::factory()->for($this->user)->create([
            'acquisition_date' => '2026-01-01',
            'current_count'    => 10,
            'is_active'        => true,
        ]);

        FeedInventory::factory()->for($this->user)->depleted()->create([
            'brand'         => 'Older',
            'opened_date'   => '2026-01-01',
            'depleted_date' => '2026-01-15',
        ]);

        FeedInventory::factory()->for($this->user)->depleted()->create([
            'brand'         => 'Newer',
            'opened_date'   => '2026-03-01',
            'depleted_date' => '2026-03-15',
        ]);

        $result = $this->service->feedPeriodBreakdown($this->user, 'all');

        $this->assertCount(2, $result);
        $this->assertEquals('Newer', $result[0]['brand']);
        $this->assertEquals('Older', $result[1]['brand']);
    }

    /** Time range filtering */
    public function test_range_filter_excludes_old_periods(): void
    {
        FlockBatch::factory()->for($this->user)->create([
            'acquisition_date' => '2025-01-01',
            'current_count'    => 10,
            'is_active'        => true,
        ]);

        // Old entry: well outside 3-month range
        FeedInventory::factory()->for($this->user)->depleted()->create([
            'opened_date'   => '2025-06-01',
            'depleted_date' => '2025-06-15',
        ]);

        // Recent entry: within 3-month range
        FeedInventory::factory()->for($this->user)->depleted()->create([
            'opened_date'   => now()->subMonth()->toDateString(),
            'depleted_date' => now()->subWeek()->toDateString(),
        ]);

        $result = $this->service->feedPeriodBreakdown($this->user, '3months');

        $this->assertCount(1, $result);
    }

    /** Multiple flock changes during a single feed period */
    public function test_multiple_flock_changes_create_multiple_sub_periods(): void
    {
        $batch = FlockBatch::factory()->for($this->user)->create([
            'acquisition_date' => '2026-01-01',
            'initial_count'    => 20,
            'current_count'    => 16,
            'is_active'        => true,
        ]);

        // Death on Feb 4: -2
        DeathRecord::factory()->create([
            'batch_id' => $batch->id,
            'user_id'  => $this->user->id,
            'date'     => '2026-02-04',
            'count'    => 2,
        ]);

        // Death on Feb 7: -2
        DeathRecord::factory()->create([
            'batch_id' => $batch->id,
            'user_id'  => $this->user->id,
            'date'     => '2026-02-07',
            'count'    => 2,
        ]);

        FeedInventory::factory()->for($this->user)->depleted()->create([
            'total_cost'    => 120.00,
            'opened_date'   => '2026-02-01',
            'depleted_date' => '2026-02-11',
        ]);

        $result = $this->service->feedPeriodBreakdown($this->user, 'all');

        $period = $result[0];
        $this->assertTrue($period['has_flock_changes']);
        $this->assertCount(2, $period['flock_changes']);
        $this->assertCount(3, $period['sub_periods']); // 3 sub-periods from 2 change boundaries
    }

    /** Bird-days calculation accuracy with known values */
    public function test_bird_days_proportional_cost_sums_to_total_cost(): void
    {
        $batch = FlockBatch::factory()->for($this->user)->create([
            'acquisition_date' => '2026-01-01',
            'initial_count'    => 10,
            'current_count'    => 7,
            'is_active'        => true,
        ]);

        DeathRecord::factory()->create([
            'batch_id' => $batch->id,
            'user_id'  => $this->user->id,
            'date'     => '2026-02-06',
            'count'    => 3,
        ]);

        FeedInventory::factory()->for($this->user)->depleted()->create([
            'total_cost'    => 100.00,
            'opened_date'   => '2026-02-01',
            'depleted_date' => '2026-02-11',
        ]);

        $result = $this->service->feedPeriodBreakdown($this->user, 'all');
        $period = $result[0];

        // Sum of proportional costs should equal total cost (within rounding)
        $totalProportional = array_sum(array_column($period['sub_periods'], 'proportional_cost'));
        $this->assertEqualsWithDelta(100.00, $totalProportional, 0.02);
    }
}
```

#### Feature Test — `tests/Feature/FeedPeriodBreakdownTest.php`

```php
<?php

namespace Tests\Feature;

use App\Models\DeathRecord;
use App\Models\FeedInventory;
use App\Models\FlockBatch;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class FeedPeriodBreakdownFeatureTest extends TestCase
{
    use LazilyRefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->premium()->create();
    }

    public function test_period_breakdown_section_renders_for_authenticated_user(): void
    {
        FlockBatch::factory()->for($this->user)->create([
            'acquisition_date' => '2026-01-01',
            'current_count'    => 10,
            'is_active'        => true,
        ]);

        FeedInventory::factory()->for($this->user)->depleted()->create([
            'brand'         => 'Layer Pellets',
            'total_cost'    => 50.00,
            'opened_date'   => '2026-02-01',
            'depleted_date' => '2026-02-11',
        ]);

        $response = $this->actingAs($this->user)->get(route('app.feed.index'));

        $response->assertStatus(200);
        $response->assertSee('Feed Period Breakdown');
        $response->assertSee('Layer Pellets');
    }

    public function test_empty_state_shown_when_no_depleted_entries(): void
    {
        $response = $this->actingAs($this->user)->get(route('app.feed.index'));

        $response->assertStatus(200);
        $response->assertSee('No Feed Periods Found');
    }

    public function test_flock_changes_warning_displayed_when_applicable(): void
    {
        $batch = FlockBatch::factory()->for($this->user)->create([
            'acquisition_date' => '2026-01-01',
            'initial_count'    => 20,
            'current_count'    => 17,
            'is_active'        => true,
        ]);

        DeathRecord::factory()->create([
            'batch_id' => $batch->id,
            'user_id'  => $this->user->id,
            'date'     => '2026-02-06',
            'count'    => 3,
        ]);

        FeedInventory::factory()->for($this->user)->depleted()->create([
            'total_cost'    => 100.00,
            'opened_date'   => '2026-02-01',
            'depleted_date' => '2026-02-11',
        ]);

        $response = $this->actingAs($this->user)->get(route('app.feed.index'));

        $response->assertStatus(200);
        $response->assertSee('Flock changes during this period');
    }

    public function test_unauthenticated_user_cannot_access_feed_page(): void
    {
        $response = $this->get(route('app.feed.index'));

        $response->assertRedirect(route('login'));
    }

    public function test_user_only_sees_own_feed_periods(): void
    {
        $otherUser = User::factory()->premium()->create();

        FlockBatch::factory()->for($this->user)->create([
            'acquisition_date' => '2026-01-01',
            'current_count'    => 10,
            'is_active'        => true,
        ]);

        // This user's feed
        FeedInventory::factory()->for($this->user)->depleted()->create([
            'brand' => 'My Feed',
        ]);

        // Other user's feed
        FeedInventory::factory()->for($otherUser)->depleted()->create([
            'brand' => 'Other Feed',
        ]);

        $response = $this->actingAs($this->user)->get(route('app.feed.index'));

        $response->assertSee('My Feed');
        $response->assertDontSee('Other Feed');
    }

    public function test_how_calculations_work_section_present(): void
    {
        $response = $this->actingAs($this->user)->get(route('app.feed.index'));

        $response->assertStatus(200);
        $response->assertSee('How Calculations Work');
        $response->assertSee('Basic Cost Allocation');
        $response->assertSee('Bird-Days');
    }
}
```
