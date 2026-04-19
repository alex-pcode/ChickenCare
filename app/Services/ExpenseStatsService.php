<?php

namespace App\Services;

use App\Enums\ExpenseCategory;
use App\Models\Expense;
use App\Models\User;

final class ExpenseStatsService
{
    private ?int $userId = null;

    public function for(int|User $user): self
    {
        $this->userId = is_int($user) ? $user : $user->id;

        return $this;
    }

    public function totalsByCategory(): array
    {
        $data = $this->aggregatedData();

        return $data['totals'];
    }

    public function grandTotal(): float
    {
        $data = $this->aggregatedData();

        return $data['grandTotal'];
    }

    public function transactionCountByCategory(): array
    {
        $data = $this->aggregatedData();

        return $data['counts'];
    }

    /**
     * Single query to compute all category aggregates at once, memoized per request.
     */
    private function aggregatedData(): array
    {
        return once(function () {
            $rows = Expense::where('user_id', $this->userId)
                ->selectRaw('category, SUM(amount) as total, COUNT(*) as c')
                ->groupBy('category')
                ->get();

            $totals = [];
            $counts = [];
            $grandTotal = 0.0;

            foreach (ExpenseCategory::cases() as $case) {
                $totals[$case->value] = 0.0;
                $counts[$case->value] = 0;
            }

            foreach ($rows as $row) {
                $totals[$row->category] = (float) $row->total;
                $counts[$row->category] = (int) $row->c;
                $grandTotal += (float) $row->total;
            }

            return [
                'totals' => $totals,
                'counts' => $counts,
                'grandTotal' => $grandTotal,
            ];
        });
    }

    public function categoryBreakdown(): array
    {
        $grandTotal = $this->grandTotal();
        $totals = $this->totalsByCategory();
        $counts = $this->transactionCountByCategory();

        $breakdown = [];

        foreach (ExpenseCategory::cases() as $case) {
            $category = $case->value;
            $total = $totals[$category];
            $count = $counts[$category];

            $breakdown[] = [
                'value' => $case->value,
                'name' => $case->label(),
                'total' => $total,
                'transactionCount' => $count,
                'percentage' => $grandTotal > 0.0
                    ? round(($total / $grandTotal) * 100, 1)
                    : 0.0,
                'color' => $case->color(),
            ];
        }

        usort($breakdown, fn ($a, $b) => $b['total'] <=> $a['total']);

        return $breakdown;
    }

    public function monthOverMonth(): array
    {
        return once(function () {
            $now = now();
            $thisStart = $now->copy()->startOfMonth()->toDateString();
            $thisEnd = $now->copy()->endOfMonth()->toDateString();
            $prev = $now->copy()->subMonth();
            $prevStart = $prev->copy()->startOfMonth()->toDateString();
            $prevEnd = $prev->copy()->endOfMonth()->toDateString();

            $row = Expense::where('user_id', $this->userId)
                ->selectRaw('COALESCE(SUM(CASE WHEN date BETWEEN ? AND ? THEN amount ELSE 0 END), 0) as this_month', [$thisStart, $thisEnd])
                ->selectRaw('COALESCE(SUM(CASE WHEN date BETWEEN ? AND ? THEN amount ELSE 0 END), 0) as prev_month', [$prevStart, $prevEnd])
                ->first();

            $thisTotal = (float) $row->this_month;
            $prevTotal = (float) $row->prev_month;
            $delta = $thisTotal - $prevTotal;
            $pct = $prevTotal > 0.0 ? round(($delta / $prevTotal) * 100, 1) : null;

            return [
                'thisMonthTotal' => $thisTotal,
                'previousMonthTotal' => $prevTotal,
                'delta' => $delta,
                'deltaPct' => $pct,
            ];
        });
    }

    public function monthlyTrend(int $months = 12): array
    {
        return once(function () use ($months) {
            $start = now()->subMonths($months - 1)->startOfMonth();
            $end = now()->endOfMonth();

            $buckets = Expense::where('user_id', $this->userId)
                ->whereBetween('date', [$start->toDateString(), $end->toDateString()])
                ->get(['date', 'amount'])
                ->groupBy(fn ($row) => $row->date->format('Y-m'))
                ->map(fn ($group) => (float) $group->sum('amount'));

            $labels = [];
            $data = [];

            $cursor = $start->copy();
            while ($cursor->lessThanOrEqualTo($end)) {
                $key = $cursor->format('Y-m');
                $labels[] = $cursor->format('M Y');
                $data[] = round((float) ($buckets[$key] ?? 0), 2);
                $cursor->addMonth();
            }

            return [
                'labels' => $labels,
                'datasets' => [[
                    'label' => 'Expenses',
                    'data' => $data,
                    'backgroundColor' => '#4F46E5',
                    'borderRadius' => 4,
                ]],
            ];
        });
    }

    public function payload(): array
    {
        return [
            'totalsByCategory' => $this->totalsByCategory(),
            'grandTotal' => $this->grandTotal(),
            'transactionCountByCategory' => $this->transactionCountByCategory(),
            'breakdown' => $this->categoryBreakdown(),
            'monthOverMonth' => $this->monthOverMonth(),
            'monthlyTrend' => $this->monthlyTrend(),
        ];
    }
}
