<?php

namespace App\Services;

use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class ReportService
{
    /**
     * @return array{summary: array, by_customer: Collection, by_week: Collection, by_month: Collection, from: Carbon, to: Carbon}
     */
    public function getSalesReport(User $user, ?Carbon $from = null, ?Carbon $to = null): array
    {
        $from = $from ?? now()->startOfMonth();
        $to = $to ?? now()->endOfMonth();

        // Load all sales in range once — reuse for all breakdowns
        $sales = $user->sales()
            ->with('customer')
            ->whereBetween('sale_date', [$from->toDateString(), $to->toDateString()])
            ->orderBy('sale_date')
            ->get();

        return [
            'summary' => $this->getSummary($sales),
            'by_customer' => $this->getCustomerBreakdown($sales),
            'by_week' => $this->getWeeklyTotals($sales),
            'by_month' => $this->getMonthlyTotals($sales),
            'from' => $from,
            'to' => $to,
        ];
    }

    /**
     * @return array{total_revenue: string, sale_count: int, average_sale: string, unpaid_amount: string, paid_amount: string}
     */
    private function getSummary(Collection $sales): array
    {
        $totalRevenue = (float) $sales->sum('total_amount');
        $saleCount = $sales->count();

        return [
            'total_revenue' => number_format($totalRevenue, 2),
            'sale_count' => $saleCount,
            'average_sale' => number_format($saleCount > 0 ? round($totalRevenue / $saleCount, 2) : 0, 2),
            'unpaid_amount' => number_format((float) $sales->where('paid', false)->sum('total_amount'), 2),
            'paid_amount' => number_format((float) $sales->where('paid', true)->sum('total_amount'), 2),
        ];
    }

    private function getCustomerBreakdown(Collection $sales): Collection
    {
        return $sales
            ->groupBy(fn ($sale) => $sale->customer_id ?? 'walk_in')
            ->map(function (Collection $group, $key) {
                $totalRevenue = (float) $group->sum('total_amount');

                return [
                    'customer_name' => $key === 'walk_in' ? 'Walk-in / No Customer' : $group->first()->customer->name,
                    'total_revenue' => number_format($totalRevenue, 2),
                    'total_revenue_raw' => $totalRevenue,
                    'sale_count' => $group->count(),
                    'paid_amount' => number_format((float) $group->where('paid', true)->sum('total_amount'), 2),
                    'unpaid_amount' => number_format((float) $group->where('paid', false)->sum('total_amount'), 2),
                ];
            })
            ->sortByDesc('total_revenue_raw')
            ->values();
    }

    private function getWeeklyTotals(Collection $sales): Collection
    {
        return $sales
            ->groupBy(fn ($sale) => $sale->sale_date->format('o-W'))
            ->map(fn (Collection $group, $key) => [
                'week_label' => 'Week of '.$group->first()->sale_date->copy()->startOfWeek()->format('M j'),
                'total_revenue' => number_format((float) $group->sum('total_amount'), 2),
                'sale_count' => $group->count(),
            ])
            ->values();
    }

    private function getMonthlyTotals(Collection $sales): Collection
    {
        return $sales
            ->groupBy(fn ($sale) => $sale->sale_date->format('Y-m'))
            ->map(fn (Collection $group, $key) => [
                'month_label' => $group->first()->sale_date->format('F Y'),
                'total_revenue' => number_format((float) $group->sum('total_amount'), 2),
                'sale_count' => $group->count(),
            ])
            ->values();
    }
}
