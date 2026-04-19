<?php

namespace App\Services;

use App\Models\DeathRecord;
use App\Models\FeedInventory;
use App\Models\FlockBatch;
use Illuminate\Support\Carbon;

final class FeedStatsService
{
    private ?int $userId = null;

    public function for(int|\App\Models\User $user): self
    {
        $this->userId = is_int($user) ? $user : $user->id;

        return $this;
    }

    /**
     * Get the date range based on the range parameter.
     *
     * @return array{Carbon, Carbon}
     */
    private function dateRange(string $range): array
    {
        $end = now();
        $start = match ($range) {
            '3months' => now()->subMonths(3),
            '6months' => now()->subMonths(6),
            '12months' => now()->subMonths(12),
            default => Carbon::create(2000, 1, 1), // 'all'
        };

        return [$start, $end];
    }

    /**
     * Get average flock size for the user over a period.
     * Uses FlockBatch current_count sum as proxy.
     */
    public function currentFlockSize(): int
    {
        return (int) FlockBatch::where('user_id', $this->userId)
            ->where('is_active', true)
            ->sum('current_count');
    }

    /**
     * Get flock size at a specific date by looking at batches acquired by that date
     * minus deaths up to that date.
     */
    public function flockSizeAtDate(Carbon $date): int
    {
        $acquired = (int) FlockBatch::where('user_id', $this->userId)
            ->where('acquisition_date', '<=', $date)
            ->sum('initial_count');

        $deaths = (int) DeathRecord::where('user_id', $this->userId)
            ->where('date', '<=', $date)
            ->sum('count');

        return max(0, $acquired - $deaths);
    }

    /**
     * Key metrics for the cost calculator.
     */
    public function metrics(string $range = '6months'): array
    {
        [$start, $end] = $this->dateRange($range);

        $feedQuery = FeedInventory::where('user_id', $this->userId)
            ->where(function ($q) use ($start, $end) {
                $q->whereBetween('opened_date', [$start, $end])
                    ->orWhere(function ($q2) use ($start, $end) {
                        $q2->whereNull('opened_date')
                            ->whereBetween('created_at', [$start, $end]);
                    });
            });

        $totalPurchased = (float) (clone $feedQuery)->sum('total_cost');

        $depletedCost = (float) (clone $feedQuery)
            ->whereNotNull('depleted_date')
            ->sum('total_cost');

        $feedCycles = (int) (clone $feedQuery)
            ->whereNotNull('depleted_date')
            ->count();

        // Monthly cost per bird calculation
        $monthlyCostPerBird = 0.0;
        if ($feedCycles > 0) {
            $avgFlockSize = $this->averageFlockSizeInRange($start, $end);
            if ($avgFlockSize > 0) {
                $months = max(1, $start->diffInMonths($end));
                $monthlyCostPerBird = $depletedCost / $months / $avgFlockSize * 30;
            }
        }

        return [
            'monthlyCostPerBird' => round($monthlyCostPerBird, 2),
            'totalPurchased' => round($totalPurchased, 2),
            'depletedCost' => round($depletedCost, 2),
            'feedCycles' => $feedCycles,
        ];
    }

    /**
     * Average flock size in a date range.
     */
    public function averageFlockSizeInRange(Carbon $start, Carbon $end): float
    {
        // Simple approach: average of flock size at start and end
        $sizeAtStart = $this->flockSizeAtDate($start);
        $sizeAtEnd = $this->flockSizeAtDate($end);

        if ($sizeAtStart === 0 && $sizeAtEnd === 0) {
            // Try current flock size as fallback
            return (float) $this->currentFlockSize();
        }

        return ($sizeAtStart + $sizeAtEnd) / 2;
    }

    /**
     * Monthly trends data for the chart (Story 6 will use this).
     */
    public function monthlyTrends(string $range = '6months'): array
    {
        [$start, $end] = $this->dateRange($range);

        $depleted = FeedInventory::where('user_id', $this->userId)
            ->whereNotNull('depleted_date')
            ->where('depleted_date', '>=', $start)
            ->where('depleted_date', '<=', $end)
            ->orderBy('depleted_date')
            ->get();

        $monthlyData = [];

        foreach ($depleted as $feed) {
            $monthKey = $feed->depleted_date->format('Y-m');
            if (! isset($monthlyData[$monthKey])) {
                $monthlyData[$monthKey] = [
                    'month' => $feed->depleted_date->format('M Y'),
                    'totalCost' => 0,
                    'feedCount' => 0,
                ];
            }
            $monthlyData[$monthKey]['totalCost'] += (float) $feed->total_cost;
            $monthlyData[$monthKey]['feedCount']++;
        }

        // Batch-calculate flock sizes for all months at once
        $monthDates = [];
        foreach (array_keys($monthlyData) as $key) {
            $monthDates[$key] = Carbon::createFromFormat('Y-m', $key)->startOfMonth();
        }

        $flockSizes = $this->batchFlockSizes(array_values($monthDates));

        // Calculate per-bird cost for each month
        foreach ($monthlyData as $key => &$data) {
            $dateIndex = array_search($monthDates[$key], array_values($monthDates));
            $flockSize = $flockSizes[$dateIndex] ?? 0;
            $data['avgFlockSize'] = $flockSize > 0 ? $flockSize : $this->currentFlockSize();
            $data['costPerBirdPerMonth'] = $data['avgFlockSize'] > 0
                ? round($data['totalCost'] / $data['avgFlockSize'], 2)
                : 0;
            $data['totalCost'] = round($data['totalCost'], 2);
        }
        unset($data);

        return array_values($monthlyData);
    }

    /**
     * Batch-calculate flock sizes at multiple dates in two queries instead of 2*N.
     *
     * @param  Carbon[]  $dates
     * @return int[]
     */
    private function batchFlockSizes(array $dates): array
    {
        if (empty($dates)) {
            return [];
        }

        // Pre-load all acquisition data and death data once
        $allAcquired = FlockBatch::where('user_id', $this->userId)
            ->select('acquisition_date', 'initial_count')
            ->orderBy('acquisition_date')
            ->get();

        $allDeaths = DeathRecord::where('user_id', $this->userId)
            ->select('date', 'count')
            ->orderBy('date')
            ->get();

        $sizes = [];
        foreach ($dates as $date) {
            $acquired = $allAcquired->where('acquisition_date', '<=', $date)->sum('initial_count');
            $deaths = $allDeaths->where('date', '<=', $date)->sum('count');
            $sizes[] = max(0, (int) $acquired - (int) $deaths);
        }

        return $sizes;
    }

    /**
     * Feed period breakdown for depleted feed entries.
     * Includes flock changes and sub-period cost allocation.
     *
     * @return array<int, array{
     *     id: int,
     *     brand: string,
     *     feedType: string,
     *     quantity: string,
     *     unit: string,
     *     batchNumber: ?string,
     *     totalCost: float,
     *     openedDate: string,
     *     depletedDate: string,
     *     durationDays: int,
     *     flockSizeAtStart: int,
     *     costPerBirdPerDay: float,
     *     costPerBirdPerMonth: float,
     *     hasFlockChanges: bool,
     *     flockChanges: array,
     *     subPeriods: array,
     * }>
     */
    public function feedPeriodBreakdown(): array
    {
        $feeds = FeedInventory::where('user_id', $this->userId)
            ->whereNotNull('depleted_date')
            ->whereNotNull('opened_date')
            ->orderBy('opened_date', 'desc')
            ->get();

        $periods = [];

        foreach ($feeds as $feed) {
            $openedDate = $feed->opened_date;
            $depletedDate = $feed->depleted_date;
            $durationDays = $openedDate->diffInDays($depletedDate);

            if ($durationDays === 0) {
                $durationDays = 1; // Minimum 1 day
            }

            $flockSizeAtStart = $this->flockSizeAtDate($openedDate);
            $totalCost = (float) $feed->total_cost;

            // Find flock changes during this period
            $flockChanges = $this->getFlockChangesInRange($openedDate, $depletedDate);
            $hasFlockChanges = count($flockChanges) > 0;

            // Calculate costs
            if ($hasFlockChanges) {
                $subPeriods = $this->calculateSubPeriods($openedDate, $depletedDate, $totalCost, $flockChanges);
                // Weighted average cost per bird
                $totalBirdDays = array_sum(array_column($subPeriods, 'birdDays'));
                $costPerBirdPerDay = $totalBirdDays > 0 ? $totalCost / $totalBirdDays : 0;
            } else {
                $subPeriods = [];
                $costPerBirdPerDay = ($flockSizeAtStart > 0 && $durationDays > 0)
                    ? $totalCost / $durationDays / $flockSizeAtStart
                    : 0;
            }

            $costPerBirdPerMonth = $costPerBirdPerDay * 30;

            $periods[] = [
                'id' => $feed->id,
                'brand' => $feed->brand,
                'feedType' => $feed->feed_type->label(),
                'quantity' => $feed->quantity,
                'unit' => $feed->unit,
                'batchNumber' => $feed->batch_number,
                'totalCost' => round($totalCost, 2),
                'openedDate' => $openedDate->format('M d, Y'),
                'depletedDate' => $depletedDate->format('M d, Y'),
                'durationDays' => $durationDays,
                'flockSizeAtStart' => $flockSizeAtStart,
                'costPerBirdPerDay' => round($costPerBirdPerDay, 4),
                'costPerBirdPerMonth' => round($costPerBirdPerMonth, 2),
                'hasFlockChanges' => $hasFlockChanges,
                'flockChanges' => $flockChanges,
                'subPeriods' => $subPeriods,
            ];
        }

        return $periods;
    }

    /**
     * Get flock changes (acquisitions and deaths) in a date range.
     */
    private function getFlockChangesInRange(Carbon $start, Carbon $end): array
    {
        $changes = [];

        // Acquisitions
        $batches = FlockBatch::where('user_id', $this->userId)
            ->where('acquisition_date', '>', $start)
            ->where('acquisition_date', '<=', $end)
            ->orderBy('acquisition_date')
            ->get();

        foreach ($batches as $batch) {
            $changes[] = [
                'date' => $batch->acquisition_date->format('Y-m-d'),
                'type' => 'acquisition',
                'change' => $batch->initial_count,
                'description' => "Acquired {$batch->initial_count} birds ({$batch->batch_name})",
                'batchName' => $batch->batch_name,
            ];
        }

        // Deaths
        $deaths = DeathRecord::where('user_id', $this->userId)
            ->where('date', '>', $start)
            ->where('date', '<=', $end)
            ->with('flockBatch:id,batch_name')
            ->orderBy('date')
            ->get();

        foreach ($deaths as $death) {
            $batchName = $death->flockBatch?->batch_name ?? 'Unknown';
            $changes[] = [
                'date' => $death->date->format('Y-m-d'),
                'type' => 'death',
                'change' => -$death->count,
                'description' => $death->description ?? "Lost {$death->count} birds",
                'batchName' => $batchName,
            ];
        }

        // Sort by date
        usort($changes, fn ($a, $b) => $a['date'] <=> $b['date']);

        // Add running counts
        $currentCount = $this->flockSizeAtDate(Carbon::parse($start));
        foreach ($changes as &$change) {
            $previousCount = $currentCount;
            $currentCount = max(0, $currentCount + $change['change']);
            $change['previousCount'] = $previousCount;
            $change['newCount'] = $currentCount;
        }
        unset($change);

        return $changes;
    }

    /**
     * Calculate sub-periods when flock changes occur during a feed period.
     */
    private function calculateSubPeriods(Carbon $start, Carbon $end, float $totalCost, array $flockChanges): array
    {
        // Build timeline boundaries
        $boundaries = [$start->format('Y-m-d')];
        foreach ($flockChanges as $change) {
            if ($change['date'] > $start->format('Y-m-d') && $change['date'] < $end->format('Y-m-d')) {
                $boundaries[] = $change['date'];
            }
        }
        $boundaries[] = $end->format('Y-m-d');
        $boundaries = array_unique($boundaries);
        sort($boundaries);

        // Calculate bird-days for each sub-period
        $subPeriods = [];
        $flockSize = $this->flockSizeAtDate($start);

        for ($i = 0; $i < count($boundaries) - 1; $i++) {
            $periodStart = Carbon::parse($boundaries[$i]);
            $periodEnd = Carbon::parse($boundaries[$i + 1]);
            $days = $periodStart->diffInDays($periodEnd);

            if ($days === 0) {
                $days = 1;
            }

            // Apply any flock changes that happen at this boundary
            foreach ($flockChanges as $change) {
                if ($change['date'] === $boundaries[$i]) {
                    $flockSize = max(0, $flockSize + $change['change']);
                }
            }

            $birdDays = $flockSize * $days;

            $subPeriods[] = [
                'startDate' => $periodStart->format('M d, Y'),
                'endDate' => $periodEnd->format('M d, Y'),
                'days' => $days,
                'flockSize' => $flockSize,
                'birdDays' => $birdDays,
            ];
        }

        // Calculate proportional costs
        $totalBirdDays = array_sum(array_column($subPeriods, 'birdDays'));
        foreach ($subPeriods as &$sp) {
            $sp['proportionalCost'] = $totalBirdDays > 0
                ? round($totalCost * ($sp['birdDays'] / $totalBirdDays), 2)
                : 0;
            $sp['costPerBirdPerDay'] = ($sp['flockSize'] > 0 && $sp['days'] > 0)
                ? round($sp['proportionalCost'] / $sp['days'] / $sp['flockSize'], 4)
                : 0;
            $sp['costPerBirdPerMonth'] = round($sp['costPerBirdPerDay'] * 30, 2);
        }
        unset($sp);

        return $subPeriods;
    }
}
