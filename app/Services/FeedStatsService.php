<?php

namespace App\Services;

use App\Models\DeathRecord;
use App\Models\FeedInventory;
use App\Models\FlockBatch;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

final class FeedStatsService
{
    private ?int $userId = null;

    public function for(int|User $user): self
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
                $monthlyCostPerBird = $depletedCost / $months / $avgFlockSize;
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

        $monthExpr = $this->monthKeyExpression('depleted_date');
        $monthlyRows = FeedInventory::where('user_id', $this->userId)
            ->whereNotNull('depleted_date')
            ->whereBetween('depleted_date', [$start, $end])
            ->selectRaw("{$monthExpr} as month_key, COALESCE(SUM(total_cost), 0) as total_cost, COUNT(*) as feed_count")
            ->groupByRaw($monthExpr)
            ->orderByRaw($monthExpr)
            ->get();

        $monthDates = $monthlyRows
            ->map(fn ($row) => Carbon::createFromFormat('Y-m', $row->month_key)->startOfMonth())
            ->all();

        $flockSizes = $this->batchFlockSizes($monthDates);
        $currentFlockSize = $this->currentFlockSize();

        return $monthlyRows
            ->values()
            ->map(function ($row, int $index) use ($flockSizes, $currentFlockSize) {
                $avgFlockSize = ($flockSizes[$index] ?? 0) > 0
                    ? (int) $flockSizes[$index]
                    : $currentFlockSize;
                $totalCost = round((float) $row->total_cost, 2);

                return [
                    'month' => Carbon::createFromFormat('Y-m', $row->month_key)->translatedFormat('M Y'),
                    'totalCost' => $totalCost,
                    'feedCount' => (int) $row->feed_count,
                    'avgFlockSize' => $avgFlockSize,
                    'costPerBirdPerMonth' => $avgFlockSize > 0
                        ? round($totalCost / $avgFlockSize, 2)
                        : 0,
                ];
            })
            ->all();
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

    private function monthKeyExpression(string $column): string
    {
        return DB::getDriverName() === 'sqlite'
            ? "strftime('%Y-%m', {$column})"
            : "DATE_FORMAT({$column}, '%Y-%m')";
    }
}
