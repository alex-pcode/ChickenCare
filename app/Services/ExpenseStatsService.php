<?php

namespace App\Services;

use App\Enums\ExpenseCategory;
use App\Models\Expense;

final class ExpenseStatsService
{
    private ?int $userId = null;

    public function for(int|\App\Models\User $user): self
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
                'name' => $case->label(),
                'total' => $total,
                'transactionCount' => $count,
                'percentage' => $grandTotal > 0.0
                    ? round(($total / $grandTotal) * 100, 1)
                    : 0.0,
                'color' => $case->color(),
            ];
        }

        usort($breakdown, fn($a, $b) => $b['total'] <=> $a['total']);

        return $breakdown;
    }

    public function payload(): array
    {
        return [
            'totalsByCategory' => $this->totalsByCategory(),
            'grandTotal' => $this->grandTotal(),
            'transactionCountByCategory' => $this->transactionCountByCategory(),
            'breakdown' => $this->categoryBreakdown(),
        ];
    }
}
