<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\DB;

class SavingsService
{
    /**
     * @return array{income: array, expenses: array, profitability: array, per_egg: array}
     */
    public function getFinancialAnalysis(User $user): array
    {
        $income = $this->getIncomeSummary($user);
        $expenses = $this->getExpenseSummary($user);
        $profitability = $this->getProfitability($income, $expenses);
        $perEgg = $this->getPerEggMetrics($user, $income, $expenses);

        return [
            'income' => $income,
            'expenses' => $expenses,
            'profitability' => $profitability,
            'per_egg' => $perEgg,
        ];
    }

    /**
     * @return array{total_revenue: float, paid_revenue: float, unpaid_revenue: float, this_month_revenue: float, sale_count: int}
     */
    private function getIncomeSummary(User $user): array
    {
        $salesQuery = $user->sales();

        $total = (float) (clone $salesQuery)->sum('total_amount');
        $paid = (float) (clone $salesQuery)->where('paid', true)->sum('total_amount');
        $unpaid = (float) (clone $salesQuery)->where('paid', false)->sum('total_amount');
        $thisMonth = (float) (clone $salesQuery)
            ->whereMonth('sale_date', now()->month)
            ->whereYear('sale_date', now()->year)
            ->sum('total_amount');
        $count = (clone $salesQuery)->count();

        return [
            'total_revenue' => $total,
            'paid_revenue' => $paid,
            'unpaid_revenue' => $unpaid,
            'this_month_revenue' => $thisMonth,
            'sale_count' => $count,
        ];
    }

    /**
     * @return array{total_expenses: float, this_month_expenses: float, by_category: array}
     */
    private function getExpenseSummary(User $user): array
    {
        $expensesQuery = $user->expenses();

        $total = (float) (clone $expensesQuery)->sum('amount');
        $thisMonth = (float) (clone $expensesQuery)
            ->whereMonth('date', now()->month)
            ->whereYear('date', now()->year)
            ->sum('amount');
        $byCategory = (clone $expensesQuery)
            ->select('category', DB::raw('SUM(amount) as total'))
            ->groupBy('category')
            ->pluck('total', 'category')
            ->map(fn ($val) => (float) $val)
            ->toArray();

        return [
            'total_expenses' => $total,
            'this_month_expenses' => $thisMonth,
            'by_category' => $byCategory,
        ];
    }

    /**
     * @return array{net_profit: float, monthly_net: float, profit_margin_pct: float|null, is_profitable: bool}
     */
    private function getProfitability(array $income, array $expenses): array
    {
        $netProfit = $income['total_revenue'] - $expenses['total_expenses'];
        $monthlyNet = $income['this_month_revenue'] - $expenses['this_month_expenses'];
        $marginPct = $income['total_revenue'] > 0
            ? round(($netProfit / $income['total_revenue']) * 100, 1)
            : null;

        return [
            'net_profit' => $netProfit,
            'monthly_net' => $monthlyNet,
            'profit_margin_pct' => $marginPct,
            'is_profitable' => $netProfit >= 0,
        ];
    }

    /**
     * @return array{total_eggs: int, cost_per_egg: float|null, revenue_per_egg: float|null, profit_per_egg: float|null}
     */
    private function getPerEggMetrics(User $user, array $income, array $expenses): array
    {
        $totalEggs = (int) $user->eggEntries()->sum('count');

        return [
            'total_eggs' => $totalEggs,
            'cost_per_egg' => $totalEggs > 0 ? round($expenses['total_expenses'] / $totalEggs, 4) : null,
            'revenue_per_egg' => $totalEggs > 0 ? round($income['total_revenue'] / $totalEggs, 4) : null,
            'profit_per_egg' => $totalEggs > 0 ? round(($income['total_revenue'] - $expenses['total_expenses']) / $totalEggs, 4) : null,
        ];
    }
}
