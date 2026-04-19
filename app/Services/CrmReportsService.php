<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\Sale;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class CrmReportsService
{
    public function revenueOverview(User $user, string $period = 'month', ?string $from = null, ?string $to = null): array
    {
        return Cache::remember(
            "crm_revenue_{$user->id}_{$period}_{$from}_{$to}",
            300,
            function () use ($user, $period, $from, $to) {
                $query = $user->sales();
                $this->applyPeriodFilter($query, $period, $from, $to);

                $stats = (clone $query)
                    ->selectRaw('COALESCE(SUM(total_amount), 0) as total_revenue')
                    ->selectRaw('COUNT(*) as total_sales')
                    ->selectRaw('COALESCE(AVG(total_amount), 0) as avg_sale_value')
                    ->selectRaw('COALESCE(SUM(dozen_count * 12 + individual_count), 0) as total_eggs_sold')
                    ->selectRaw('COALESCE(SUM(CASE WHEN total_amount = 0 THEN dozen_count * 12 + individual_count ELSE 0 END), 0) as free_eggs')
                    ->first();

                return [
                    'totalRevenue' => number_format((float) ($stats->total_revenue ?? 0), 2),
                    'totalSales' => (int) ($stats->total_sales ?? 0),
                    'avgSaleValue' => number_format((float) ($stats->avg_sale_value ?? 0), 2),
                    'totalEggsSold' => (int) ($stats->total_eggs_sold ?? 0),
                    'freeEggs' => (int) ($stats->free_eggs ?? 0),
                ];
            }
        );
    }

    public function customerAnalytics(User $user): array
    {
        $sales = $user->sales()->with('customer')->get();

        // Top customers by revenue
        $topCustomers = $sales->groupBy('customer_id')
            ->map(function (Collection $customerSales, $customerId) {
                $customer = $customerSales->first()->customer;

                return [
                    'name' => $customer->name ?? 'Unknown',
                    'revenue' => $customerSales->sum('total_amount'),
                    'transactions' => $customerSales->count(),
                ];
            })
            ->filter(fn ($c) => $c['name'] !== 'Walk-in / No Customer')
            ->sortByDesc('revenue')
            ->take(5)
            ->values()
            ->toArray();

        // Paid vs free eggs
        $paidEggs = $sales->where('total_amount', '>', 0)->sum(fn (Sale $s) => $s->dozen_count * 12 + $s->individual_count);
        $freeEggs = $sales->where('total_amount', 0)->sum(fn (Sale $s) => $s->dozen_count * 12 + $s->individual_count);

        // Purchase frequency (top 5 customers with 2+ sales)
        $purchaseFrequency = $sales->groupBy('customer_id')
            ->filter(fn (Collection $customerSales) => $customerSales->count() >= 2)
            ->map(function (Collection $customerSales) {
                $customer = $customerSales->first()->customer;
                $dates = $customerSales->pluck('sale_date')->sort()->values();
                $totalDays = 0;
                $intervals = 0;

                for ($i = 1; $i < $dates->count(); $i++) {
                    $totalDays += $dates[$i]->diffInDays($dates[$i - 1]);
                    $intervals++;
                }

                $avgDays = $intervals > 0 ? round($totalDays / $intervals) : 0;

                return [
                    'name' => $customer->name ?? 'Unknown',
                    'avgDays' => $avgDays,
                ];
            })
            ->filter(fn ($c) => $c['name'] !== 'Walk-in / No Customer')
            ->sortBy('avgDays')
            ->take(5)
            ->values()
            ->toArray();

        // Inactive customers (30+ days since last purchase)
        $activeCustomers = $user->customers()->active()->get();
        $customerLastSale = $sales->groupBy('customer_id')
            ->map(fn (Collection $customerSales) => $customerSales->max('sale_date'));

        $inactiveCustomers = $activeCustomers
            ->filter(function (Customer $customer) use ($customerLastSale) {
                $lastSale = $customerLastSale->get($customer->id);
                if (! $lastSale) {
                    return true;
                }

                return Carbon::parse($lastSale)->diffInDays(now()) >= 30;
            })
            ->map(fn (Customer $c) => ['name' => $c->name, 'id' => $c->id])
            ->values()
            ->toArray();

        return [
            'topCustomers' => $topCustomers,
            'paidEggs' => $paidEggs,
            'freeEggs' => $freeEggs,
            'purchaseFrequency' => $purchaseFrequency,
            'inactiveCustomers' => $inactiveCustomers,
        ];
    }

    public function productionPipeline(User $user): array
    {
        $now = now();
        $sixMonthsAgo = $now->copy()->subMonths(5)->startOfMonth();

        // Single query for 6 months of egg production grouped by month
        $dateExpr = DB::getDriverName() === 'sqlite'
            ? "strftime('%Y-%m', date)"
            : "DATE_FORMAT(date, '%Y-%m')";

        $eggsByMonth = $user->eggEntries()
            ->where('date', '>=', $sixMonthsAgo->toDateString())
            ->selectRaw("{$dateExpr} as month_key, SUM(count) as total")
            ->groupByRaw("{$dateExpr}")
            ->pluck('total', 'month_key');

        // Single query for 6 months of sales grouped by month
        $saleDateExpr = DB::getDriverName() === 'sqlite'
            ? "strftime('%Y-%m', sale_date)"
            : "DATE_FORMAT(sale_date, '%Y-%m')";

        $salesByMonth = $user->sales()
            ->where('sale_date', '>=', $sixMonthsAgo->toDateString())
            ->selectRaw("{$saleDateExpr} as month_key, SUM(dozen_count * 12 + individual_count) as total_eggs")
            ->groupByRaw("{$saleDateExpr}")
            ->pluck('total_eggs', 'month_key');

        $thisMonthKey = $now->format('Y-m');
        $thisMonthProduced = (int) ($eggsByMonth[$thisMonthKey] ?? 0);
        $thisMonthSold = (int) ($salesByMonth[$thisMonthKey] ?? 0);
        $sellThroughRate = $thisMonthProduced > 0 ? round(($thisMonthSold / $thisMonthProduced) * 100, 1) : 0;

        $chartData = [];
        for ($i = 5; $i >= 0; $i--) {
            $date = $now->copy()->subMonths($i);
            $key = $date->format('Y-m');
            $chartData[] = [
                'month' => $date->format('M Y'),
                'produced' => (int) ($eggsByMonth[$key] ?? 0),
                'sold' => (int) ($salesByMonth[$key] ?? 0),
            ];
        }

        return [
            'thisMonthProduced' => $thisMonthProduced,
            'thisMonthSold' => $thisMonthSold,
            'sellThroughRate' => $sellThroughRate,
            'chart' => $chartData,
        ];
    }

    public function revenueTrend(User $user): array
    {
        $now = now();
        $startDate = $now->copy()->subMonths(11)->startOfMonth();

        // Single query for 12 months of revenue
        $saleDateExpr = DB::getDriverName() === 'sqlite'
            ? "strftime('%Y-%m', sale_date)"
            : "DATE_FORMAT(sale_date, '%Y-%m')";

        $revenueByMonth = $user->sales()
            ->where('sale_date', '>=', $startDate->toDateString())
            ->selectRaw("{$saleDateExpr} as month_key, COALESCE(SUM(total_amount), 0) as revenue")
            ->groupByRaw("{$saleDateExpr}")
            ->pluck('revenue', 'month_key');

        $trend = [];
        for ($i = 11; $i >= 0; $i--) {
            $date = $now->copy()->subMonths($i);
            $key = $date->format('Y-m');
            $trend[] = [
                'month' => $date->format('M Y'),
                'revenue' => round((float) ($revenueByMonth[$key] ?? 0), 2),
            ];
        }

        return $trend;
    }

    public function recentSales(User $user): Collection
    {
        return $user->sales()
            ->with('customer')
            ->orderBy('sale_date', 'desc')
            ->limit(10)
            ->get();
    }

    public function perCustomer(User $user, int $customerId): array
    {
        $customer = $user->customers()->find($customerId);

        if (! $customer) {
            return ['found' => false];
        }

        $sales = $user->sales()
            ->where('customer_id', $customerId)
            ->orderBy('sale_date', 'desc')
            ->get();

        $totalRevenue = $sales->sum('total_amount');
        $totalEggs = $sales->sum(fn (Sale $s) => $s->dozen_count * 12 + $s->individual_count);
        $freeEggs = $sales->where('total_amount', 0)->sum(fn (Sale $s) => $s->dozen_count * 12 + $s->individual_count);
        $paidEggs = $totalEggs - $freeEggs;
        $transactionCount = $sales->count();
        $avgSale = $transactionCount > 0 ? $totalRevenue / $transactionCount : 0;
        $lastPurchase = $sales->first()?->sale_date;

        // Average days between purchases
        $avgDaysBetween = null;
        if ($transactionCount >= 2) {
            $dates = $sales->pluck('sale_date')->sort()->values();
            $totalDays = 0;
            for ($i = 1; $i < $dates->count(); $i++) {
                $totalDays += $dates[$i]->diffInDays($dates[$i - 1]);
            }
            $avgDaysBetween = round($totalDays / ($dates->count() - 1));
        }

        // Monthly trend (last 6 months)
        $monthlyTrend = [];
        $now = now();
        for ($i = 5; $i >= 0; $i--) {
            $date = $now->copy()->subMonths($i);
            $monthSales = $sales->filter(
                fn (Sale $s) => $s->sale_date->month === $date->month && $s->sale_date->year === $date->year
            );

            $monthlyTrend[] = [
                'month' => $date->format('M Y'),
                'revenue' => round((float) $monthSales->sum('total_amount'), 2),
                'eggs' => $monthSales->sum(fn (Sale $s) => $s->dozen_count * 12 + $s->individual_count),
            ];
        }

        // Recent transactions (up to 10)
        $recentTransactions = $sales->take(10)->map(function (Sale $sale) {
            $totalEggs = $sale->dozen_count * 12 + $sale->individual_count;
            $pricePerEgg = $totalEggs > 0 && $sale->total_amount > 0
                ? round($sale->total_amount / $totalEggs, 2)
                : 0;

            return [
                'date' => $sale->sale_date->format('M d, Y'),
                'eggs' => $totalEggs,
                'pricePerEgg' => $pricePerEgg,
                'total' => (float) $sale->total_amount,
                'notes' => $sale->notes,
                'isFree' => (float) $sale->total_amount === 0.0,
            ];
        })->toArray();

        return [
            'found' => true,
            'customer' => $customer,
            'totalRevenue' => number_format($totalRevenue, 2),
            'totalEggs' => $totalEggs,
            'paidEggs' => $paidEggs,
            'freeEggs' => $freeEggs,
            'transactionCount' => $transactionCount,
            'avgSale' => number_format($avgSale, 2),
            'avgDaysBetween' => $avgDaysBetween,
            'lastPurchase' => $lastPurchase,
            'monthlyTrend' => $monthlyTrend,
            'recentTransactions' => $recentTransactions,
        ];
    }

    /**
     * Clear cached revenue overview data for a specific user.
     * Called after sale/customer mutations to ensure fresh report data.
     */
    public function clearCacheForUser(User $user): void
    {
        $userId = $user->id;

        Cache::forget("crm_revenue_{$userId}_month__");
        Cache::forget("crm_revenue_{$userId}_year__");
        Cache::forget("crm_revenue_{$userId}_all__");
    }

    private function applyPeriodFilter($query, string $period, ?string $from, ?string $to): void
    {
        $now = now();

        match ($period) {
            'month' => $query->whereMonth('sale_date', $now->month)->whereYear('sale_date', $now->year),
            'year' => $query->whereYear('sale_date', $now->year),
            'custom' => $this->applyCustomDateFilter($query, $from, $to),
            default => null, // 'all' — no filter
        };
    }

    private function applyCustomDateFilter($query, ?string $from, ?string $to): void
    {
        if ($from) {
            $query->where('sale_date', '>=', Carbon::parse($from)->startOfDay());
        }
        if ($to) {
            $query->where('sale_date', '<=', Carbon::parse($to)->endOfDay());
        }
        if (! $from && ! $to) {
            $query->where('sale_date', '>=', now()->subMonths(3));
        }
    }
}
