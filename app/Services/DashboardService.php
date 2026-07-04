<?php

namespace App\Services;

use App\Models\BatchEvent;
use App\Models\DeathRecord;
use App\Models\User;
use App\Support\WeekStart;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Support\Collection;

class DashboardService
{
    /**
     * @return array{eggs: array, financial: array, flock: array, recent_activity: Collection}
     */
    public function getSummary(User $user): array
    {
        return [
            'eggs' => $this->getEggStats($user),
            'financial' => $user->isPremium() ? $this->getFinancialStats($user) : [],
            'flock' => $user->isPremium() ? $this->getFlockStats($user) : [],
            'recent_activity' => $this->getRecentActivity($user),
        ];
    }

    /**
     * @return array{today: int, this_week: int, this_month: int, daily_average: float}
     */
    private function getEggStats(User $user): array
    {
        $baseQuery = $user->eggEntries();

        $today = (int) (clone $baseQuery)->whereDate('date', now()->toDateString())->sum('count');
        $thisWeek = (int) (clone $baseQuery)->forWeek(now())->sum('count');

        // Combine month sum + avg into one query
        $monthStats = (clone $baseQuery)->forMonth(now())
            ->selectRaw('COALESCE(SUM(count), 0) as total, COALESCE(ROUND(AVG(count), 1), 0) as average')
            ->first();

        return [
            'today' => $today,
            'this_week' => $thisWeek,
            'this_month' => (int) ($monthStats->total ?? 0),
            'daily_average' => round((float) ($monthStats->average ?? 0), 1),
        ];
    }

    /**
     * @return array{total_revenue: string, month_revenue: string, total_expenses: string, month_expenses: string, unpaid_sales: string}
     */
    private function getFinancialStats(User $user): array
    {
        $monthStart = now()->startOfMonth()->toDateString();
        $monthEnd = now()->endOfMonth()->toDateString();

        // Single query for all sales aggregates
        $salesStats = $user->sales()
            ->selectRaw('COALESCE(SUM(total_amount), 0) as total_revenue')
            ->selectRaw('COALESCE(SUM(CASE WHEN sale_date BETWEEN ? AND ? THEN total_amount ELSE 0 END), 0) as month_revenue', [$monthStart, $monthEnd])
            ->selectRaw('COALESCE(SUM(CASE WHEN paid = 0 THEN total_amount ELSE 0 END), 0) as unpaid_sales')
            ->first();

        // Single query for all expense aggregates
        $expenseStats = $user->expenses()
            ->selectRaw('COALESCE(SUM(amount), 0) as total_expenses')
            ->selectRaw('COALESCE(SUM(CASE WHEN date BETWEEN ? AND ? THEN amount ELSE 0 END), 0) as month_expenses', [$monthStart, $monthEnd])
            ->first();

        return [
            'total_revenue' => number_format((float) ($salesStats->total_revenue ?? 0), 2),
            'month_revenue' => number_format((float) ($salesStats->month_revenue ?? 0), 2),
            'total_expenses' => number_format((float) ($expenseStats->total_expenses ?? 0), 2),
            'month_expenses' => number_format((float) ($expenseStats->month_expenses ?? 0), 2),
            'unpaid_sales' => number_format((float) ($salesStats->unpaid_sales ?? 0), 2),
        ];
    }

    /**
     * @return array{total_birds: int, active_batches: int, total_hens: int, total_mortality: int}
     */
    private function getFlockStats(User $user): array
    {
        // Single query for flock aggregate stats using active scope
        $activeBatches = $user->flockBatches()->active();

        $batchStats = (clone $activeBatches)
            ->selectRaw('COUNT(*) as active_count')
            ->selectRaw('COALESCE(SUM(current_count), 0) as total_birds')
            ->selectRaw('COALESCE(SUM(hens_count), 0) as total_hens')
            ->first();

        // Single query for mortality across active batches using a subquery
        $totalMortality = (int) DeathRecord::whereIn(
            'batch_id',
            (clone $activeBatches)->select('id')
        )->sum('count');

        return [
            'total_birds' => (int) ($batchStats->total_birds ?? 0),
            'active_batches' => (int) ($batchStats->active_count ?? 0),
            'total_hens' => (int) ($batchStats->total_hens ?? 0),
            'total_mortality' => $totalMortality,
        ];
    }

    public function getRecentActivity(User $user): Collection
    {
        $items = collect();

        // Egg entries (1 query)
        $eggs = $user->eggEntries()->latest('date')->limit(3)->get();
        foreach ($eggs as $e) {
            $items->push([
                'date' => $e->date,
                'type' => 'egg',
                'description' => __('dashboard.recent_activity.items.egg', ['count' => $e->count]),
            ]);
        }

        // Sales — premium only (1 query or skipped)
        if ($user->isPremium()) {
            $sales = $user->sales()->latest('sale_date')->limit(3)->get();
            foreach ($sales as $s) {
                $items->push([
                    'date' => $s->sale_date,
                    'type' => 'sale',
                    'description' => __('dashboard.recent_activity.items.sale', ['amount' => number_format((float) $s->total_amount, 2)]),
                ]);
            }
        }

        // Batch events (1 query)
        $events = BatchEvent::where('user_id', $user->id)->latest('date')->limit(3)->get();
        foreach ($events as $e) {
            $items->push([
                'date' => $e->date,
                'type' => 'batch_event',
                'description' => $e->description,
            ]);
        }

        return $items->sortByDesc('date')->values()->take(10);
    }

    /**
     * @return array{totalEggs: int, dailyAverage: float, last7DaysTotal: int, previous7DaysTotal: int, thisMonthProduction: int, lastMonthProduction: int, weekDelta: ?int, monthDelta: ?int}
     */
    public function getProductionMetrics(User $user): array
    {
        $today = Carbon::today();
        $currentDay = $today->day;

        $last7Start = $today->copy()->subDays(6)->toDateString();
        $prev7Start = $today->copy()->subDays(13)->toDateString();
        $prev7End = $today->copy()->subDays(7)->toDateString();
        $todayStr = $today->toDateString();

        $thisMonthStart = $today->copy()->startOfMonth()->toDateString();
        $thisMonthEnd = $today->copy()->endOfMonth()->toDateString();

        $lastMonth = $today->copy()->startOfMonth()->subMonth();
        $lastMonthStart = $lastMonth->toDateString();
        $lastMonthCutoff = $lastMonth->copy()->addDays(min($currentDay, $lastMonth->daysInMonth) - 1)->toDateString();

        $stats = $user->eggEntries()
            ->selectRaw('COALESCE(SUM(count), 0) as total_eggs')
            ->selectRaw('COALESCE(SUM(CASE WHEN DATE(date) BETWEEN ? AND ? THEN count ELSE 0 END), 0) as last_7_days', [$last7Start, $todayStr])
            ->selectRaw('COALESCE(SUM(CASE WHEN DATE(date) BETWEEN ? AND ? THEN count ELSE 0 END), 0) as prev_7_days', [$prev7Start, $prev7End])
            ->selectRaw('COALESCE(SUM(CASE WHEN DATE(date) BETWEEN ? AND ? THEN count ELSE 0 END), 0) as this_month', [$thisMonthStart, $thisMonthEnd])
            ->selectRaw('COALESCE(SUM(CASE WHEN DATE(date) BETWEEN ? AND ? THEN count ELSE 0 END), 0) as last_month', [$lastMonthStart, $lastMonthCutoff])
            ->selectRaw('MAX(CASE WHEN DATE(date) BETWEEN ? AND ? THEN CAST(substr(date, 9, 2) AS INTEGER) ELSE NULL END) as max_day_this_month', [$thisMonthStart, $thisMonthEnd])
            ->first();

        $totalEggs = (int) $stats->total_eggs;
        $last7DaysTotal = (int) $stats->last_7_days;
        $previous7DaysTotal = (int) $stats->prev_7_days;
        $thisMonthProduction = (int) $stats->this_month;
        $lastMonthProduction = (int) $stats->last_month;
        $maxDayThisMonth = $stats->max_day_this_month;

        $dailyAverage = $maxDayThisMonth
            ? round($thisMonthProduction / (int) $maxDayThisMonth, 1)
            : 0.0;

        $weekDelta = $previous7DaysTotal > 0
            ? (int) round((($last7DaysTotal - $previous7DaysTotal) / $previous7DaysTotal) * 100)
            : null;

        $monthDelta = $lastMonthProduction > 0
            ? (int) round((($thisMonthProduction - $lastMonthProduction) / $lastMonthProduction) * 100)
            : null;

        return [
            'totalEggs' => $totalEggs,
            'dailyAverage' => $dailyAverage,
            'last7DaysTotal' => $last7DaysTotal,
            'previous7DaysTotal' => $previous7DaysTotal,
            'thisMonthProduction' => $thisMonthProduction,
            'lastMonthProduction' => $lastMonthProduction,
            'weekDelta' => $weekDelta,
            'monthDelta' => $monthDelta,
        ];
    }

    /**
     * Daily egg totals for the last 30 days, keyed by Y-m-d date string.
     *
     * @return Collection<string, mixed>
     */
    private function getThirtyDayDailyTotals(User $user): Collection
    {
        return once(function () use ($user) {
            $startDate = now()->subDays(29)->startOfDay();

            return $user->eggEntries()
                ->where('date', '>=', $startDate->toDateString())
                ->selectRaw('DATE(date) as day, SUM(count) as daily_total')
                ->groupByRaw('DATE(date)')
                ->pluck('daily_total', 'day');
        });
    }

    /**
     * @return array{labels: array, datasets: array}
     */
    public function getEggChartData(User $user): array
    {
        $startDate = now()->subDays(29)->startOfDay();
        $endDate = now()->endOfDay();
        $dailyTotals = $this->getThirtyDayDailyTotals($user);

        $labels = [];
        $data = [];

        foreach (CarbonPeriod::create($startDate, $endDate) as $date) {
            $key = $date->format('Y-m-d');
            $labels[] = $date->format('M d');
            $data[] = (int) ($dailyTotals[$key] ?? 0);
        }

        return [
            'labels' => $labels,
            'datasets' => [[
                'label' => 'Eggs Collected',
                'data' => $data,
            ]],
        ];
    }

    /**
     * @return array{labels: string[], datasets: array<int, array{label: string, data: int[], backgroundColor: string, borderRadius: int}>}
     */
    public function getThirtyDayProductionChart(User $user): array
    {
        $startDate = now()->subDays(29)->startOfDay();
        $endDate = now()->endOfDay();
        $dailyTotals = $this->getThirtyDayDailyTotals($user);

        $labels = [];
        $data = [];

        foreach (CarbonPeriod::create($startDate, $endDate) as $date) {
            $key = $date->format('Y-m-d');
            $labels[] = $date->format('n/j');
            $data[] = (int) ($dailyTotals[$key] ?? 0);
        }

        return [
            'labels' => $labels,
            'datasets' => [[
                'label' => 'Production',
                'data' => $data,
                'backgroundColor' => '#4F46E5',
                'borderRadius' => 4,
            ]],
        ];
    }

    /**
     * @return array{eggValue: float, revenue: float, freeEggs: int, eggPriceUsed: float}
     */
    public function getFinancialOverview(User $user): array
    {
        $eggPrice = (float) ($user->egg_price ?? 0.30);

        $monthStart = now()->startOfMonth()->toDateString();
        $monthEnd = now()->endOfMonth()->toDateString();

        $thisMonthEggs = (int) $user->eggEntries()
            ->whereBetween('date', [$monthStart, $monthEnd])
            ->sum('count');

        $salesStats = $user->sales()
            ->whereBetween('sale_date', [$monthStart, $monthEnd])
            ->selectRaw('COALESCE(SUM(total_amount), 0) as revenue')
            ->selectRaw('COALESCE(SUM(CASE WHEN total_amount = 0 THEN dozen_count * 12 + individual_count ELSE 0 END), 0) as free_eggs')
            ->first();

        return [
            'eggValue' => round($thisMonthEggs * $eggPrice, 2),
            'revenue' => round((float) ($salesStats->revenue ?? 0), 2),
            'freeEggs' => (int) ($salesStats->free_eggs ?? 0),
            'eggPriceUsed' => $eggPrice,
        ];
    }

    /**
     * @return array{labels: array, datasets: array}
     */
    public function getExpenseChartData(User $user): array
    {
        $months = collect();
        for ($i = 5; $i >= 0; $i--) {
            $months->push(now()->subMonths($i));
        }

        $expenses = $user->expenses()
            ->where('date', '>=', $months->first()->startOfMonth()->toDateString())
            ->get();

        if ($expenses->isEmpty()) {
            return [];
        }

        $labels = [];
        $data = [];

        foreach ($months as $month) {
            $labels[] = $month->format('M Y');
            $data[] = (float) $expenses
                ->filter(fn ($e) => $e->date->month === $month->month && $e->date->year === $month->year)
                ->sum('amount');
        }

        return [
            'labels' => $labels,
            'datasets' => [[
                'label' => 'Expenses',
                'data' => $data,
            ]],
        ];
    }

    /**
     * @return array{labels: string[], datasets: array<int, array{label: string, data: float[], backgroundColor: string, borderColor: string, borderWidth: int, tension: float, fill: string, pointBackgroundColor: string, pointRadius: int}>}
     */
    public function getWeeklyRevenueTrend(User $user, int $weeks = 12): array
    {
        $now = now();
        $currentWeekStart = WeekStart::from($now);

        // Go back ($weeks - 1) weeks from the current week start
        $startDate = $currentWeekStart->copy()->subWeeks($weeks - 1);

        // Query all sales from startDate to now
        $sales = $user->sales()
            ->where('sale_date', '>=', $startDate->toDateString())
            ->select('sale_date', 'total_amount')
            ->get();

        // Build weekly buckets
        $weeklyMap = [];
        foreach ($sales as $sale) {
            $weekKey = WeekStart::from($sale->sale_date)->toDateString();
            $weeklyMap[$weekKey] = ($weeklyMap[$weekKey] ?? 0) + (float) $sale->total_amount;
        }

        // Build the chart data for each week
        $labels = [];
        $data = [];

        for ($i = 0; $i < $weeks; $i++) {
            $weekStart = $startDate->copy()->addWeeks($i);
            $weekKey = $weekStart->toDateString();
            $labels[] = $weekStart->format('n/j');
            $data[] = round($weeklyMap[$weekKey] ?? 0, 2);
        }

        return [
            'labels' => $labels,
            'datasets' => [[
                'label' => 'Weekly Revenue',
                'data' => $data,
                'backgroundColor' => 'rgba(84, 76, 230, 0.3)',
                'borderColor' => '#544CE6',
                'borderWidth' => 2,
                'tension' => 0.35,
                'fill' => 'origin',
                'pointBackgroundColor' => '#544CE6',
                'pointRadius' => 3,
            ]],
        ];
    }
}
