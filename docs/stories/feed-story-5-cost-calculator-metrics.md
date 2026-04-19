# Story: Feed Cost Calculator — Key Metrics & Time Range

## Status

Ready for Implementation

## Story

**As a** user,
**I want** to see key feed cost metrics with time range filtering,
**so that** I understand my feed spending efficiency at a glance.

## Story Context

This is Story 5 of the Feed Inventory Replication Epic. It depends on Story 1 (schema migration with `brand`, `feed_type`, `opened_date`, `depleted_date`, `batch_number` columns) and introduces `FeedStatsService` — the foundation for Stories 6 (Trends Chart) and 7 (Period Breakdown).

### Reference Implementation

- **React source:** `d:\Koke\Aplikacija\src\components\features\feed\FeedCostCalculator.tsx`
- **State:** `timeRange: '3months' | '6months' | '12months' | 'all'` (default `'6months'`)
- **Metrics:** 4 stat cards in a responsive grid — Monthly Cost (per bird), Total Feed Purchased, Depleted Feed Cost, Feed Cycles
- **Flock size logic:** FlockBatch acquisitions + DeathRecord deaths → cumulative flock timeline → weighted average flock size over the filtered time range
- **Empty state:** "📊 No Feed Periods Found" + guidance message

### Existing System Context

- `FeedInventory` model (post-Story 1): `brand`, `feed_type`, `quantity`, `unit`, `opened_date`, `depleted_date`, `batch_number`, `total_cost`
- `FlockBatch` model: `acquisition_date`, `initial_count`, `hens_count`, `roosters_count`, `chicks_count`, `is_active`, `user_id`
- `DeathRecord` model: `batch_id`, `user_id`, `date`, `count`, `cause`
- `FlockProfile` model: `flock_size`, `hens`, `roosters`, `chicks`, `brooding`
- `ExpenseStatsService` — pattern to follow (fluent `for($user)` builder, returns arrays)
- `DashboardService` — pattern to follow (single service class, private helpers, typed return arrays)
- `App\Support\Money::usd()` + `@usd` Blade directive for currency formatting
- `HandlesHtmx` trait for dual HTMX/standard responses
- `<x-ui.stat-card>` Blade component exists for stat card rendering
- Chart.js 4.5.1 already installed, `window.Chart` globally registered, `<x-ui.chart>` Blade component available
- Routes nested under `Route::prefix('app')->middleware(['auth', 'premium'])` group

---

## Acceptance Criteria

### Functional Requirements

#### Section Layout

1. Feed Cost Calculator section renders below the feed table on the feed index page
2. Section wrapped in a gradient header card with title **"Feed Cost Analysis"** and subtitle **"Analyze your feed spending efficiency"**
3. Gradient header uses `bg-gradient-to-r from-green-500/10 to-emerald-500/10` (light) and `dark:from-green-900/20 dark:to-emerald-900/20` (dark), with `border border-green-200 dark:border-green-800 rounded-xl p-6`
4. Time range selector renders as 4 pill toggle buttons: **3m** | **6m** | **12m** | **All**
5. Default active range is **6m** (6 months)
6. Active button styled: `bg-green-500 text-white shadow-sm`; inactive: `bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700`
7. All four pill buttons are the same width (`min-w-[3rem]`), grouped in a `flex gap-1 bg-gray-100 dark:bg-gray-800 rounded-lg p-1` container
8. Section entry animation: opacity 0 → 1, translateY 20px → 0, delay 0.5s, spring easing

#### Stat Cards

9. Four stat cards in a responsive grid: `grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4`
10. **Card 1 — Monthly Cost (per bird):** calculated as `grandTotal / totalMonths / avgFlockSize * 30`, formatted as `$X.XX/bird`. Label: "Monthly Cost". Subtitle: "Per bird average"
11. **Card 2 — Total Feed Purchased:** sum of all `total_cost` in the filtered time range, formatted as `$X,XXX.XX`. Label: "Total Purchased". Subtitle: "Feed spending"
12. **Card 3 — Depleted Feed Cost:** sum of `total_cost` where `depleted_date IS NOT NULL` within the filtered range, formatted as `$X,XXX.XX`. Label: "Depleted Cost". Subtitle: "Completed cycles"
13. **Card 4 — Feed Cycles:** count of feed entries where `depleted_date IS NOT NULL` within the filtered range, displayed as integer. Label: "Feed Cycles". Subtitle: "Completed bags"
14. Each card has neumorphic/glass styling: `bg-white/80 dark:bg-gray-800/80 backdrop-blur-sm rounded-xl border border-gray-200 dark:border-gray-700 p-5 shadow-sm`
15. Each card has a corner gradient accent: 4×4 rounded-full `bg-gradient-to-br` dot (green shades), positioned `absolute top-3 right-3`
16. Card accent colors: Card 1 `from-green-400 to-emerald-500`, Card 2 `from-blue-400 to-cyan-500`, Card 3 `from-amber-400 to-yellow-500`, Card 4 `from-purple-400 to-violet-500`
17. Stat value text: `text-2xl font-bold text-gray-900 dark:text-white`
18. Stat label text: `text-sm font-medium text-gray-600 dark:text-gray-400`
19. Stat subtitle text: `text-xs text-gray-500 dark:text-gray-500`

#### Time Range Filtering

20. Clicking a time range button filters all four stat card values
21. Time range `3months`: filter `opened_date >= now - 3 months`
22. Time range `6months`: filter `opened_date >= now - 6 months`
23. Time range `12months`: filter `opened_date >= now - 12 months`
24. Time range `all`: no date filter applied
25. On range change, stat cards update via `fetch()` to `GET /app/feed/stats?range={range}` and re-render
26. Loading state: stat card values show a pulsing placeholder (`animate-pulse bg-gray-200 dark:bg-gray-700 h-8 w-24 rounded`) while fetching

#### Data Endpoint

27. `GET /app/feed/stats?range=6months` returns JSON: `{ "monthlyCostPerBird": float, "totalPurchased": float, "depletedCost": float, "feedCycles": int, "breakdown": array }`
28. `range` parameter validated: must be one of `3months`, `6months`, `12months`, `all`; defaults to `6months` if missing or invalid
29. Response scoped to authenticated user only (`$request->user()`)
30. `breakdown` array contains per-feed-period data: `{ "brand": string, "feedType": string, "totalCost": float, "openedDate": string, "depletedDate": string|null, "durationDays": int|null }`

#### Monthly Cost Per Bird Calculation

31. **Flock timeline construction:** Collect all `FlockBatch` rows for the user (each contributing `+initial_count` on `acquisition_date`) and all `DeathRecord` rows (each contributing `-count` on `date`). Sort chronologically
32. **Cumulative flock size:** Walk the timeline to build `[date => cumulativeFlockSize]` pairs; flock size at any given date is the running total of acquisitions minus deaths up to that date
33. **Average flock size:** For the filtered time range, calculate a weighted average flock size based on how many days each flock size was in effect. If no flock data exists, fall back to `FlockProfile.flock_size` for the user, or `1` to avoid division by zero
34. **Total months:** Number of months spanned by the filtered feed data (minimum 1 to avoid division by zero). Calculated as `max(1, monthsBetween(earliestOpenedDate, latestDepletedDateOrNow))`
35. **Formula:** `monthlyCostPerBird = (grandTotal / totalMonths) / avgFlockSize * 30` — this gives a normalized 30-day cost per bird

#### Empty State

36. If no feed entries exist with `depleted_date` in the filtered range: show empty state
37. Empty state displays: "📊 No Feed Periods Found" heading (text-lg, font-semibold, text-gray-600)
38. Empty state body: "Complete feed cycles in Feed Tracker to see cost analysis here." (text-sm, text-gray-500)
39. Empty state centered in a `py-12 text-center` container with a subtle dashed border: `border-2 border-dashed border-gray-300 dark:border-gray-600 rounded-xl`

### Integration Requirements

40. Section included in `resources/views/feed/index.blade.php` below the records table via `@include('feed.partials.cost-calculator')`
41. Stats endpoint registered in `routes/web.php` inside the existing premium middleware group, before the feed resource route (to avoid `{feed}` param capture)
42. `FeedStatsService` follows the same fluent pattern as `ExpenseStatsService` (`for($user)` → method calls)
43. Stat cards re-fetch when `feed:changed` HTMX trigger event fires (emitted by store/update/destroy/markDepleted actions in future stories)

### Quality Requirements

44. All stat calculations covered by unit tests in `FeedStatsServiceTest`
45. Controller stats endpoint covered by feature tests
46. Edge cases tested: no feed entries, no flock data, single feed entry, zero total cost, flock size of zero (fallback to 1)
47. All PHP files pass `vendor/bin/pint --dirty --format agent`
48. Dark mode verified for all card styles and gradient header
49. Responsive grid collapses correctly: 4-col → 2-col → 1-col

---

## Technical Notes

### File Changes Summary

| File | Action | Description |
|------|--------|-------------|
| `app/Services/FeedStatsService.php` | **Create** | New service with `getMetrics()`, `buildFlockTimeline()`, `getFlockSizeAtDate()`, `getAverageFlockSize()` |
| `app/Http/Controllers/FeedInventoryController.php` | **Update** | Add `stats()` method returning JSON |
| `routes/web.php` | **Update** | Add `GET /app/feed/stats` route before feed resource |
| `resources/views/feed/index.blade.php` | **Update** | Include `cost-calculator` partial below table |
| `resources/views/feed/partials/cost-calculator.blade.php` | **Create** | Main cost calculator section with gradient header + Alpine.js |
| `resources/views/feed/partials/stat-cards.blade.php` | **Create** | 4-card responsive grid partial |
| `resources/scss/features/_feed.scss` | **Update** | Add `.feed-calculator` BEM block styles |
| `tests/Unit/FeedStatsServiceTest.php` | **Create** | Unit tests for all service methods |
| `tests/Feature/FeedStatsControllerTest.php` | **Create** | Feature tests for stats endpoint |

### FeedStatsService — Implementation Sketch

```php
<?php

namespace App\Services;

use App\Models\DeathRecord;
use App\Models\FlockBatch;
use App\Models\FlockProfile;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Collection;

final class FeedStatsService
{
    private ?int $userId = null;

    public function for(int|User $user): self
    {
        $this->userId = is_int($user) ? $user : $user->id;

        return $this;
    }

    /**
     * @return array{
     *     monthlyCostPerBird: float,
     *     totalPurchased: float,
     *     depletedCost: float,
     *     feedCycles: int,
     *     breakdown: array
     * }
     */
    public function getMetrics(string $range = '6months'): array
    {
        $feeds = $this->getFeedEntries($range);

        $totalPurchased = $feeds->sum('total_cost');
        $depletedFeeds = $feeds->whereNotNull('depleted_date');
        $depletedCost = $depletedFeeds->sum('total_cost');
        $feedCycles = $depletedFeeds->count();

        $monthlyCostPerBird = $this->calculateMonthlyCostPerBird(
            $feeds,
            $totalPurchased
        );

        $breakdown = $depletedFeeds->map(fn ($feed) => [
            'brand' => $feed->brand,
            'feedType' => $feed->feed_type->value ?? $feed->feed_type,
            'totalCost' => (float) $feed->total_cost,
            'openedDate' => $feed->opened_date?->toDateString(),
            'depletedDate' => $feed->depleted_date?->toDateString(),
            'durationDays' => $feed->durationInDays(),
        ])->values()->toArray();

        return [
            'monthlyCostPerBird' => round($monthlyCostPerBird, 2),
            'totalPurchased' => round((float) $totalPurchased, 2),
            'depletedCost' => round((float) $depletedCost, 2),
            'feedCycles' => $feedCycles,
            'breakdown' => $breakdown,
        ];
    }

    /**
     * Build a chronological timeline of flock size changes.
     *
     * Returns a Collection of [date => Carbon, delta => int, cumulative => int]
     * sorted by date ascending.
     *
     * @return Collection<int, array{date: Carbon, delta: int, cumulative: int}>
     */
    public function buildFlockTimeline(): Collection
    {
        $events = collect();

        // Acquisitions: each batch adds initial_count on acquisition_date
        FlockBatch::where('user_id', $this->userId)
            ->whereNotNull('acquisition_date')
            ->select('acquisition_date', 'initial_count')
            ->each(function ($batch) use ($events) {
                $events->push([
                    'date' => $batch->acquisition_date,
                    'delta' => (int) $batch->initial_count,
                ]);
            });

        // Deaths: each record subtracts count on date
        DeathRecord::where('user_id', $this->userId)
            ->select('date', 'count')
            ->each(function ($death) use ($events) {
                $events->push([
                    'date' => $death->date,
                    'delta' => -1 * (int) $death->count,
                ]);
            });

        // Sort chronologically, then build cumulative
        $sorted = $events->sortBy('date')->values();
        $cumulative = 0;

        return $sorted->map(function ($event) use (&$cumulative) {
            $cumulative += $event['delta'];

            return [
                'date' => $event['date'],
                'delta' => $event['delta'],
                'cumulative' => max(0, $cumulative),
            ];
        });
    }

    /**
     * Get the flock size at a specific date by walking the timeline.
     */
    public function getFlockSizeAtDate(Carbon $targetDate): int
    {
        $timeline = $this->buildFlockTimeline();

        $size = 0;
        foreach ($timeline as $event) {
            if ($event['date']->gt($targetDate)) {
                break;
            }
            $size = $event['cumulative'];
        }

        // Fallback: if no timeline events before target date, use FlockProfile
        if ($size === 0) {
            $size = $this->getFallbackFlockSize();
        }

        return max(1, $size); // Never return 0 to avoid division by zero
    }

    /**
     * Calculate the weighted average flock size over a date range.
     *
     * Weights each flock size by the number of days it was in effect.
     */
    public function getAverageFlockSize(Carbon $from, Carbon $to): float
    {
        $timeline = $this->buildFlockTimeline();

        if ($timeline->isEmpty()) {
            return max(1.0, (float) $this->getFallbackFlockSize());
        }

        // Filter to events relevant to the range and build weighted average
        $totalBirdDays = 0;
        $totalDays = 0;
        $currentSize = 0;
        $periodStart = $from->copy();

        foreach ($timeline as $event) {
            $eventDate = $event['date'];

            // Skip events before our range
            if ($eventDate->lt($from)) {
                $currentSize = $event['cumulative'];
                continue;
            }

            // If event is after our range, stop
            if ($eventDate->gt($to)) {
                break;
            }

            // Calculate bird-days for the period from periodStart to this event
            $days = $periodStart->diffInDays($eventDate);
            if ($days > 0) {
                $totalBirdDays += max(0, $currentSize) * $days;
                $totalDays += $days;
            }

            $currentSize = $event['cumulative'];
            $periodStart = $eventDate->copy();
        }

        // Add the remaining period from last event to range end
        $remainingDays = $periodStart->diffInDays($to);
        if ($remainingDays > 0) {
            $totalBirdDays += max(0, $currentSize) * $remainingDays;
            $totalDays += $remainingDays;
        }

        if ($totalDays === 0) {
            return max(1.0, (float) $this->getFallbackFlockSize());
        }

        return max(1.0, $totalBirdDays / $totalDays);
    }

    /**
     * Filter feed entries by time range.
     */
    private function getFeedEntries(string $range): Collection
    {
        $query = \App\Models\FeedInventory::where('user_id', $this->userId);

        $cutoff = $this->getRangeCutoff($range);
        if ($cutoff !== null) {
            $query->where('opened_date', '>=', $cutoff);
        }

        return $query->orderBy('opened_date', 'desc')->get();
    }

    /**
     * Calculate monthly cost per bird using flock-weighted averages.
     */
    private function calculateMonthlyCostPerBird(Collection $feeds, float $totalPurchased): float
    {
        if ($totalPurchased <= 0 || $feeds->isEmpty()) {
            return 0.0;
        }

        $earliest = $feeds->min('opened_date');
        $latest = $feeds->max(fn ($f) => $f->depleted_date ?? now());

        if ($earliest === null) {
            return 0.0;
        }

        $earliest = Carbon::parse($earliest);
        $latest = Carbon::parse($latest);

        // Total months spanned, minimum 1
        $totalMonths = max(1, $earliest->diffInMonths($latest) ?: 1);

        // Average flock size over that period
        $avgFlockSize = $this->getAverageFlockSize($earliest, $latest);

        // Monthly cost per bird normalized to 30 days
        return ($totalPurchased / $totalMonths / $avgFlockSize) * 30;
    }

    /**
     * Convert range string to a Carbon cutoff date, or null for 'all'.
     */
    private function getRangeCutoff(string $range): ?Carbon
    {
        return match ($range) {
            '3months' => now()->subMonths(3),
            '6months' => now()->subMonths(6),
            '12months' => now()->subYear(),
            'all' => null,
            default => now()->subMonths(6),
        };
    }

    /**
     * Fallback flock size from FlockProfile when no timeline events exist.
     */
    private function getFallbackFlockSize(): int
    {
        $profile = FlockProfile::where('user_id', $this->userId)->first();

        return $profile?->flock_size ?? 1;
    }
}
```

### Controller Method — `stats()`

```php
// In FeedInventoryController.php

use App\Services\FeedStatsService;

public function stats(Request $request, FeedStatsService $service)
{
    $range = $request->query('range', '6months');

    // Validate range parameter
    if (! in_array($range, ['3months', '6months', '12months', 'all'], true)) {
        $range = '6months';
    }

    $metrics = $service->for($request->user())->getMetrics($range);

    return response()->json($metrics);
}
```

### Route Definition

```php
// In routes/web.php, inside the premium middleware group,
// BEFORE the feed resource route (to avoid {feed} param capture):

Route::get('feed/stats', [FeedInventoryController::class, 'stats'])->name('feed.stats');
Route::resource('feed', FeedInventoryController::class)->except(['create', 'edit', 'show']);
```

### Alpine.js `x-data` Shape — Cost Calculator

```js
// resources/views/feed/partials/cost-calculator.blade.php
{
    range: '6months',
    loading: false,
    metrics: {
        monthlyCostPerBird: 0,
        totalPurchased: 0,
        depletedCost: 0,
        feedCycles: 0,
        breakdown: []
    },
    isEmpty: true,

    async init() {
        await this.fetchStats();

        // Listen for feed changes (store/update/destroy/markDepleted)
        this.$el.addEventListener('feed:changed', () => this.fetchStats());
    },

    async setRange(newRange) {
        this.range = newRange;
        await this.fetchStats();
    },

    async fetchStats() {
        this.loading = true;
        try {
            const response = await fetch(`/app/feed/stats?range=${this.range}`, {
                headers: { 'Accept': 'application/json' }
            });
            if (response.ok) {
                this.metrics = await response.json();
                this.isEmpty = this.metrics.feedCycles === 0;
            }
        } catch (e) {
            console.error('Failed to fetch feed stats:', e);
        } finally {
            this.loading = false;
        }
    },

    formatCurrency(value) {
        return '$' + Number(value).toLocaleString('en-US', {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2
        });
    },

    formatCostPerBird(value) {
        return '$' + Number(value).toFixed(2) + '/bird';
    }
}
```

### Blade Partial — `cost-calculator.blade.php` (Structure)

```blade
{{-- resources/views/feed/partials/cost-calculator.blade.php --}}
<section
    class="feed-calculator mt-8"
    x-data="feedCostCalculator()"
    @feed:changed.window="fetchStats()"
>
    {{-- Gradient Header --}}
    <div class="feed-calculator__header bg-gradient-to-r from-green-500/10 to-emerald-500/10 dark:from-green-900/20 dark:to-emerald-900/20 border border-green-200 dark:border-green-800 rounded-xl p-6 mb-6">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <h2 class="text-2xl font-bold text-gray-900 dark:text-white">Feed Cost Analysis</h2>
                <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">Analyze your feed spending efficiency</p>
            </div>

            {{-- Time Range Selector --}}
            <div class="flex gap-1 bg-gray-100 dark:bg-gray-800 rounded-lg p-1">
                <template x-for="opt in [{key:'3months',label:'3m'},{key:'6months',label:'6m'},{key:'12months',label:'12m'},{key:'all',label:'All'}]">
                    <button
                        type="button"
                        class="min-w-[3rem] px-3 py-1.5 text-sm font-medium rounded-md transition-colors"
                        :class="range === opt.key
                            ? 'bg-green-500 text-white shadow-sm'
                            : 'text-gray-700 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-700'"
                        @click="setRange(opt.key)"
                        x-text="opt.label"
                    ></button>
                </template>
            </div>
        </div>
    </div>

    {{-- Stat Cards or Empty State --}}
    <template x-if="!isEmpty || loading">
        @include('feed.partials.stat-cards')
    </template>

    <template x-if="isEmpty && !loading">
        <div class="py-12 text-center border-2 border-dashed border-gray-300 dark:border-gray-600 rounded-xl">
            <p class="text-lg font-semibold text-gray-600 dark:text-gray-400">📊 No Feed Periods Found</p>
            <p class="text-sm text-gray-500 dark:text-gray-500 mt-2">Complete feed cycles in Feed Tracker to see cost analysis here.</p>
        </div>
    </template>
</section>
```

### Blade Partial — `stat-cards.blade.php` (Structure)

```blade
{{-- resources/views/feed/partials/stat-cards.blade.php --}}
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
    {{-- Card 1: Monthly Cost Per Bird --}}
    <div class="feed-calculator__card relative bg-white/80 dark:bg-gray-800/80 backdrop-blur-sm rounded-xl border border-gray-200 dark:border-gray-700 p-5 shadow-sm">
        <div class="absolute top-3 right-3 w-4 h-4 rounded-full bg-gradient-to-br from-green-400 to-emerald-500"></div>
        <template x-if="loading">
            <div class="animate-pulse bg-gray-200 dark:bg-gray-700 h-8 w-24 rounded"></div>
        </template>
        <template x-if="!loading">
            <p class="text-2xl font-bold text-gray-900 dark:text-white" x-text="formatCostPerBird(metrics.monthlyCostPerBird)"></p>
        </template>
        <p class="text-sm font-medium text-gray-600 dark:text-gray-400 mt-1">Monthly Cost</p>
        <p class="text-xs text-gray-500">Per bird average</p>
    </div>

    {{-- Card 2: Total Feed Purchased --}}
    <div class="feed-calculator__card relative bg-white/80 dark:bg-gray-800/80 backdrop-blur-sm rounded-xl border border-gray-200 dark:border-gray-700 p-5 shadow-sm">
        <div class="absolute top-3 right-3 w-4 h-4 rounded-full bg-gradient-to-br from-blue-400 to-cyan-500"></div>
        <template x-if="loading">
            <div class="animate-pulse bg-gray-200 dark:bg-gray-700 h-8 w-24 rounded"></div>
        </template>
        <template x-if="!loading">
            <p class="text-2xl font-bold text-gray-900 dark:text-white" x-text="formatCurrency(metrics.totalPurchased)"></p>
        </template>
        <p class="text-sm font-medium text-gray-600 dark:text-gray-400 mt-1">Total Purchased</p>
        <p class="text-xs text-gray-500">Feed spending</p>
    </div>

    {{-- Card 3: Depleted Feed Cost --}}
    <div class="feed-calculator__card relative bg-white/80 dark:bg-gray-800/80 backdrop-blur-sm rounded-xl border border-gray-200 dark:border-gray-700 p-5 shadow-sm">
        <div class="absolute top-3 right-3 w-4 h-4 rounded-full bg-gradient-to-br from-amber-400 to-yellow-500"></div>
        <template x-if="loading">
            <div class="animate-pulse bg-gray-200 dark:bg-gray-700 h-8 w-24 rounded"></div>
        </template>
        <template x-if="!loading">
            <p class="text-2xl font-bold text-gray-900 dark:text-white" x-text="formatCurrency(metrics.depletedCost)"></p>
        </template>
        <p class="text-sm font-medium text-gray-600 dark:text-gray-400 mt-1">Depleted Cost</p>
        <p class="text-xs text-gray-500">Completed cycles</p>
    </div>

    {{-- Card 4: Feed Cycles --}}
    <div class="feed-calculator__card relative bg-white/80 dark:bg-gray-800/80 backdrop-blur-sm rounded-xl border border-gray-200 dark:border-gray-700 p-5 shadow-sm">
        <div class="absolute top-3 right-3 w-4 h-4 rounded-full bg-gradient-to-br from-purple-400 to-violet-500"></div>
        <template x-if="loading">
            <div class="animate-pulse bg-gray-200 dark:bg-gray-700 h-8 w-24 rounded"></div>
        </template>
        <template x-if="!loading">
            <p class="text-2xl font-bold text-gray-900 dark:text-white" x-text="metrics.feedCycles"></p>
        </template>
        <p class="text-sm font-medium text-gray-600 dark:text-gray-400 mt-1">Feed Cycles</p>
        <p class="text-xs text-gray-500">Completed bags</p>
    </div>
</div>
```

### SCSS Additions — `_feed.scss`

```scss
// Append to resources/scss/features/_feed.scss

.feed-calculator {
    animation: feedCalcEntry 0.6s cubic-bezier(0.34, 1.56, 0.64, 1) 0.5s both;

    &__header {
        // Gradient + border defined in Tailwind utilities
    }

    &__card {
        transition: transform 0.2s ease, box-shadow 0.2s ease;

        &:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
        }
    }
}

@keyframes feedCalcEntry {
    from {
        opacity: 0;
        transform: translateY(20px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

@media (prefers-reduced-motion: reduce) {
    .feed-calculator {
        animation: none;
    }

    .feed-calculator__card {
        transition: none;
    }
}
```

---

## Tasks / Subtasks

- [ ] Task 1: Create `FeedStatsService` (AC: 10–13, 27–35)
  - [ ] Create `app/Services/FeedStatsService.php`
  - [ ] Implement fluent `for(int|User $user): self` pattern (matching `ExpenseStatsService`)
  - [ ] `getMetrics(string $range = '6months'): array` — returns `monthlyCostPerBird`, `totalPurchased`, `depletedCost`, `feedCycles`, `breakdown`
  - [ ] `buildFlockTimeline(): Collection` — collects FlockBatch acquisitions (+initial_count) and DeathRecord deaths (-count), sorted by date, with cumulative size
  - [ ] `getFlockSizeAtDate(Carbon $targetDate): int` — walks timeline, returns flock size at the given date (min 1)
  - [ ] `getAverageFlockSize(Carbon $from, Carbon $to): float` — weighted average using bird-days calculation (min 1.0)
  - [ ] Private `getFeedEntries(string $range): Collection` — filters FeedInventory by user + time range
  - [ ] Private `calculateMonthlyCostPerBird(Collection $feeds, float $totalPurchased): float`
  - [ ] Private `getRangeCutoff(string $range): ?Carbon`
  - [ ] Private `getFallbackFlockSize(): int` — FlockProfile fallback, default 1
  - [ ] Run Pint: `vendor/bin/pint --dirty --format agent`

- [ ] Task 2: Add `stats()` method to `FeedInventoryController` (AC: 27–30)
  - [ ] Add `use App\Services\FeedStatsService;` import
  - [ ] Add `stats(Request $request, FeedStatsService $service)` method
  - [ ] Validate `range` query parameter (allow-list)
  - [ ] Return `response()->json($metrics)`
  - [ ] Run Pint: `vendor/bin/pint --dirty --format agent`

- [ ] Task 3: Register stats route in `routes/web.php` (AC: 41)
  - [ ] Add `Route::get('feed/stats', [FeedInventoryController::class, 'stats'])->name('feed.stats');` **before** the `Route::resource('feed', ...)` line
  - [ ] Verify with `php artisan route:list --name=feed.stats`

- [ ] Task 4: Create `cost-calculator.blade.php` partial (AC: 1–8, 20–26, 36–39)
  - [ ] Create `resources/views/feed/partials/cost-calculator.blade.php`
  - [ ] Alpine.js `x-data="feedCostCalculator()"` with `range`, `loading`, `metrics`, `isEmpty` state
  - [ ] Gradient header with title, subtitle, time range pill buttons
  - [ ] `@feed:changed.window` listener to re-fetch on mutations
  - [ ] `fetch()` call to `/app/feed/stats?range={range}` with loading state
  - [ ] Empty state with dashed border, icon, and guidance text
  - [ ] Conditional rendering via `x-if` for empty vs. populated states

- [ ] Task 5: Create `stat-cards.blade.php` partial (AC: 9–19)
  - [ ] Create `resources/views/feed/partials/stat-cards.blade.php`
  - [ ] 4-card responsive grid: `grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4`
  - [ ] Each card: glass styling, corner gradient accent, loading pulse placeholder
  - [ ] Card 1: Monthly Cost (per bird), green accent
  - [ ] Card 2: Total Purchased, blue accent
  - [ ] Card 3: Depleted Cost, amber accent
  - [ ] Card 4: Feed Cycles, purple accent
  - [ ] Currency formatting via Alpine.js `formatCurrency()` / `formatCostPerBird()`

- [ ] Task 6: Include cost calculator in feed index page (AC: 40)
  - [ ] Add `@include('feed.partials.cost-calculator')` to `resources/views/feed/index.blade.php` below the records table section

- [ ] Task 7: Add SCSS for cost calculator section (AC: 8, 48)
  - [ ] Append `.feed-calculator` BEM block to `resources/scss/features/_feed.scss`
  - [ ] Entry animation keyframe (`feedCalcEntry`)
  - [ ] Card hover effect (translateY + shadow)
  - [ ] `prefers-reduced-motion` media query disabling animations
  - [ ] Run `pnpm run build` to verify compilation

- [ ] Task 8: Write unit tests for `FeedStatsService` (AC: 44, 46)
  - [ ] Create `tests/Unit/FeedStatsServiceTest.php`
  - [ ] Tests listed below in Test Outline section

- [ ] Task 9: Write feature tests for stats endpoint (AC: 45, 46)
  - [ ] Create `tests/Feature/FeedStatsControllerTest.php`
  - [ ] Tests listed below in Test Outline section

- [ ] Task 10: Run full test suite and verify (AC: 47)
  - [ ] `php artisan test --compact --filter=FeedStats` — all new tests pass
  - [ ] `vendor/bin/pint --dirty --format agent` — clean
  - [ ] Verify no regressions: `php artisan test --compact --filter=Feed`

---

## Test Outline

### Unit Tests — `tests/Unit/FeedStatsServiceTest.php`

| # | Test Method | Description |
|---|-------------|-------------|
| 1 | `test_get_metrics_returns_correct_structure` | Verify return array has all 5 keys with correct types |
| 2 | `test_total_purchased_sums_all_feed_costs_in_range` | 3 feeds in range, 1 outside → sum only in-range |
| 3 | `test_depleted_cost_sums_only_depleted_feeds` | Mix of active + depleted → sum only depleted `total_cost` |
| 4 | `test_feed_cycles_counts_only_depleted_entries` | 5 feeds, 3 depleted → feedCycles = 3 |
| 5 | `test_monthly_cost_per_bird_basic_calculation` | Known values: $300 total, 3 months, 10 birds → $300/3/10*30 = $300 |
| 6 | `test_monthly_cost_per_bird_with_flock_changes` | Flock size changes mid-range → weighted average used |
| 7 | `test_monthly_cost_per_bird_zero_when_no_feeds` | No feeds → monthlyCostPerBird = 0 |
| 8 | `test_build_flock_timeline_combines_acquisitions_and_deaths` | 2 batches + 1 death → 3 events sorted chronologically |
| 9 | `test_build_flock_timeline_empty_when_no_flock_data` | No batches, no deaths → empty collection |
| 10 | `test_get_flock_size_at_date_walks_timeline_correctly` | Timeline with 3 events → correct cumulative at each date |
| 11 | `test_get_flock_size_at_date_falls_back_to_profile` | No timeline → uses FlockProfile.flock_size |
| 12 | `test_get_flock_size_at_date_returns_one_when_no_data` | No timeline, no profile → returns 1 |
| 13 | `test_get_average_flock_size_weighted_calculation` | 10 birds for 20 days, 15 birds for 10 days → (10×20 + 15×10) / 30 = 11.67 |
| 14 | `test_get_average_flock_size_returns_one_when_empty` | No flock data, no profile → returns 1.0 |
| 15 | `test_time_range_3months_filters_correctly` | Feeds at 1mo, 2mo, 4mo ago → 3months returns only first two |
| 16 | `test_time_range_all_returns_everything` | Feeds across 2 years → all returned |
| 17 | `test_metrics_scoped_to_user` | User A and User B feeds → service returns only User A's data |
| 18 | `test_breakdown_contains_correct_feed_details` | Depleted feed → breakdown includes brand, feedType, totalCost, dates, duration |

### Feature Tests — `tests/Feature/FeedStatsControllerTest.php`

| # | Test Method | Description |
|---|-------------|-------------|
| 1 | `test_stats_endpoint_returns_json` | GET /app/feed/stats → 200 + JSON content type |
| 2 | `test_stats_endpoint_returns_correct_structure` | Response has monthlyCostPerBird, totalPurchased, depletedCost, feedCycles, breakdown keys |
| 3 | `test_stats_endpoint_default_range_is_6months` | No `?range` param → same result as `?range=6months` |
| 4 | `test_stats_endpoint_accepts_valid_ranges` | Each of 3months, 6months, 12months, all returns 200 |
| 5 | `test_stats_endpoint_invalid_range_defaults_to_6months` | `?range=invalid` → treated as 6months |
| 6 | `test_stats_endpoint_requires_authentication` | Unauthenticated → redirect to login |
| 7 | `test_stats_endpoint_requires_premium` | Free-tier user → 403 or redirect |
| 8 | `test_stats_endpoint_scoped_to_user` | User A's feeds not visible in User B's stats |
| 9 | `test_stats_endpoint_empty_for_new_user` | No feeds → all zeros, empty breakdown |
| 10 | `test_stats_endpoint_with_depleted_feeds` | 3 depleted feeds → correct totals and feedCycles = 3 |

### Test Count Summary

| Category | Tests |
|----------|-------|
| FeedStatsService unit tests | 18 |
| Stats endpoint feature tests | 10 |
| **Total** | **28** |

---

## Dev Notes

### Route Order Matters

The `GET /app/feed/stats` route **must** be registered before `Route::resource('feed', ...)` because the resource route defines `GET /app/feed/{feed}` which would capture `stats` as a `{feed}` parameter. This is the same pattern used for `feed/{feed}/edit-form` and `feed/{feed}/row`.

### Flock Size Calculation — Edge Cases

1. **No FlockBatch records:** Fall back to `FlockProfile.flock_size`, then to `1`
2. **No DeathRecords:** Timeline only has acquisition events — cumulative only increases
3. **Deaths exceed acquisitions:** `max(0, cumulative)` prevents negative flock size in timeline; `max(1, size)` prevents division by zero in calculations
4. **Single-day feed period:** `totalMonths = max(1, ...)` ensures no division by zero

### Monthly Cost Per Bird Formula

The React reference calculates: `grandTotal / totalMonths / avgFlockSize * 30`

This normalizes to a 30-day cost. The `* 30` factor accounts for months of varying length — it gives "cost per bird for a standard 30-day month" regardless of whether actual months had 28, 30, or 31 days.

### Alpine.js Registration

The `feedCostCalculator()` function should be registered as an Alpine component in `resources/js/app.js` or defined inline in the partial. Given the project convention (other Alpine components are inline), define it inline with `x-data="{ ... }"` directly in the partial.

### Future Integration Points

- **Story 6 (Trends Chart):** Will consume `FeedStatsService@monthlyTrends()` (not yet implemented) — the `getMetrics()` and `buildFlockTimeline()` methods will be reused
- **Story 7 (Period Breakdown):** Will consume `FeedStatsService@feedPeriodBreakdown()` (not yet implemented) — the `getAverageFlockSize()` and `buildFlockTimeline()` methods will be reused
- The `breakdown` array in `getMetrics()` is a lightweight version; Story 7 adds flock-change-aware sub-period allocation
