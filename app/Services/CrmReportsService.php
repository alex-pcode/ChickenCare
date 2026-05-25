<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\Sale;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class CrmReportsService
{
    public function revenueOverview(User $user, string $period = 'month', ?string $from = null, ?string $to = null): array
    {
        return Cache::remember(
            $this->cacheKey($user, sprintf('revenue_%s_%s_%s', $period, $from ?? 'null', $to ?? 'null')),
            now()->addMinutes(5),
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

    public function customerAnalytics(User $user, string $period = 'month', ?string $from = null, ?string $to = null): array
    {
        return Cache::remember(
            $this->cacheKey($user, sprintf('customer_analytics_%s_%s_%s', $period, $from ?? 'null', $to ?? 'null')),
            now()->addMinutes(5),
            function () use ($user, $period, $from, $to) {
                $topQuery = $user->sales()->whereNotNull('customer_id');
                $this->applyPeriodFilter($topQuery, $period, $from, $to);
                $topCustomerRows = $topQuery
                    ->selectRaw('customer_id, COALESCE(SUM(total_amount), 0) as revenue, COUNT(*) as transactions')
                    ->groupBy('customer_id')
                    ->orderByDesc('revenue')
                    ->limit(5)
                    ->get();

                $repeatQuery = $user->sales()->whereNotNull('customer_id');
                $this->applyPeriodFilter($repeatQuery, $period, $from, $to);
                $repeatCustomerIds = $repeatQuery
                    ->selectRaw('customer_id, COUNT(*) as transaction_count')
                    ->groupBy('customer_id')
                    ->having('transaction_count', '>=', 2)
                    ->pluck('customer_id')
                    ->map(fn (mixed $customerId) => (int) $customerId)
                    ->values();

                $customerIds = $topCustomerRows->pluck('customer_id')
                    ->merge($repeatCustomerIds)
                    ->filter()
                    ->map(fn (mixed $customerId) => (int) $customerId)
                    ->unique()
                    ->values();

                $customerNames = $customerIds->isEmpty()
                    ? collect()
                    : $user->customers()->whereIn('id', $customerIds)->pluck('name', 'id');

                $topCustomers = $topCustomerRows
                    ->map(fn ($row) => [
                        'name' => $customerNames->get((int) $row->customer_id, __('crm.unknown_customer')),
                        'revenue' => (float) $row->revenue,
                        'transactions' => (int) $row->transactions,
                    ])
                    ->values()
                    ->toArray();

                $eggQuery = $user->sales();
                $this->applyPeriodFilter($eggQuery, $period, $from, $to);
                $eggStats = $eggQuery
                    ->selectRaw('COALESCE(SUM(CASE WHEN total_amount > 0 THEN dozen_count * 12 + individual_count ELSE 0 END), 0) as paid_eggs')
                    ->selectRaw('COALESCE(SUM(CASE WHEN total_amount = 0 THEN dozen_count * 12 + individual_count ELSE 0 END), 0) as free_eggs')
                    ->first();

                $purchaseFrequency = collect();

                if ($repeatCustomerIds->isNotEmpty()) {
                    $freqQuery = $user->sales()->whereIn('customer_id', $repeatCustomerIds);
                    $this->applyPeriodFilter($freqQuery, $period, $from, $to);
                    $saleDatesByCustomer = $freqQuery
                        ->orderBy('customer_id')
                        ->orderBy('sale_date')
                        ->get(['customer_id', 'sale_date'])
                        ->groupBy('customer_id');

                    $purchaseFrequency = $saleDatesByCustomer
                        ->map(function (Collection $customerSales, int|string $customerId) use ($customerNames) {
                            $dates = $customerSales
                                ->pluck('sale_date')
                                ->map(fn ($date) => $date instanceof Carbon ? $date : Carbon::parse($date))
                                ->values();

                            $totalDays = 0;
                            for ($i = 1; $i < $dates->count(); $i++) {
                                $totalDays += abs($dates[$i]->diffInDays($dates[$i - 1]));
                            }

                            return [
                                'name' => $customerNames->get((int) $customerId, __('crm.unknown_customer')),
                                'avgDays' => (int) round($totalDays / max(1, $dates->count() - 1)),
                            ];
                        })
                        ->sortBy('avgDays')
                        ->take(5)
                        ->values();
                }

                $lastSalesQuery = $user->sales()->whereNotNull('customer_id');
                $this->applyPeriodFilter($lastSalesQuery, $period, $from, $to);
                $lastSales = $lastSalesQuery
                    ->selectRaw('customer_id, MAX(sale_date) as last_sale_date')
                    ->groupBy('customer_id');

                $inactiveCustomers = $user->customers()
                    ->active()
                    ->leftJoinSub($lastSales, 'last_sales', function ($join) {
                        $join->on('customers.id', '=', 'last_sales.customer_id');
                    })
                    ->where(function ($query) {
                        $query->whereNull('last_sales.last_sale_date')
                            ->orWhere('last_sales.last_sale_date', '<', now()->subDays(30)->toDateString());
                    })
                    ->orderBy('customers.name')
                    ->get(['customers.id', 'customers.name'])
                    ->map(fn (Customer $customer) => ['name' => $customer->name, 'id' => $customer->id])
                    ->values()
                    ->toArray();

                return [
                    'topCustomers' => $topCustomers,
                    'paidEggs' => (int) ($eggStats->paid_eggs ?? 0),
                    'freeEggs' => (int) ($eggStats->free_eggs ?? 0),
                    'purchaseFrequency' => $purchaseFrequency->toArray(),
                    'inactiveCustomers' => $inactiveCustomers,
                ];
            }
        );
    }

    public function productionPipeline(User $user, string $period = 'month', ?string $from = null, ?string $to = null): array
    {
        return Cache::remember(
            $this->cacheKey($user, sprintf('production_pipeline_%s_%s_%s', $period, $from ?? 'null', $to ?? 'null')),
            now()->addMinutes(5),
            function () use ($user, $period, $from, $to) {
                [$rangeStart, $rangeEnd, $monthCount] = $this->resolveChartRange($period, $from, $to, 6);
                $dateExpr = $this->monthKeyExpression('date');
                $saleDateExpr = $this->monthKeyExpression('sale_date');

                $eggsByMonth = $user->eggEntries()
                    ->where('date', '>=', $rangeStart->toDateString())
                    ->where('date', '<=', $rangeEnd->toDateString())
                    ->selectRaw("{$dateExpr} as month_key, SUM(count) as total")
                    ->groupByRaw($dateExpr)
                    ->pluck('total', 'month_key');

                $salesByMonth = $user->sales()
                    ->where('sale_date', '>=', $rangeStart->toDateString())
                    ->where('sale_date', '<=', $rangeEnd->toDateString())
                    ->selectRaw("{$saleDateExpr} as month_key, SUM(dozen_count * 12 + individual_count) as total_eggs")
                    ->groupByRaw($saleDateExpr)
                    ->pluck('total_eggs', 'month_key');

                $chartData = [];
                $totalProduced = 0;
                $totalSold = 0;
                for ($i = 0; $i < $monthCount; $i++) {
                    $date = $rangeStart->copy()->addMonths($i);
                    $key = $date->format('Y-m');
                    $produced = (int) ($eggsByMonth[$key] ?? 0);
                    $sold = (int) ($salesByMonth[$key] ?? 0);
                    $totalProduced += $produced;
                    $totalSold += $sold;
                    $chartData[] = [
                        'month' => $date->translatedFormat('M Y'),
                        'produced' => $produced,
                        'sold' => $sold,
                    ];
                }

                $sellThroughRate = $totalProduced > 0 ? round(($totalSold / $totalProduced) * 100, 1) : 0;

                return [
                    'thisMonthProduced' => $totalProduced,
                    'thisMonthSold' => $totalSold,
                    'sellThroughRate' => $sellThroughRate,
                    'chart' => $chartData,
                ];
            }
        );
    }

    public function revenueTrend(User $user, string $period = 'month', ?string $from = null, ?string $to = null): array
    {
        return Cache::remember(
            $this->cacheKey($user, sprintf('revenue_trend_%s_%s_%s', $period, $from ?? 'null', $to ?? 'null')),
            now()->addMinutes(5),
            function () use ($user, $period, $from, $to) {
                [$rangeStart, $rangeEnd, $monthCount] = $this->resolveChartRange($period, $from, $to, 12);
                $saleDateExpr = $this->monthKeyExpression('sale_date');

                $revenueByMonth = $user->sales()
                    ->where('sale_date', '>=', $rangeStart->toDateString())
                    ->where('sale_date', '<=', $rangeEnd->toDateString())
                    ->selectRaw("{$saleDateExpr} as month_key, COALESCE(SUM(total_amount), 0) as revenue")
                    ->groupByRaw($saleDateExpr)
                    ->pluck('revenue', 'month_key');

                $trend = [];
                for ($i = 0; $i < $monthCount; $i++) {
                    $date = $rangeStart->copy()->addMonths($i);
                    $key = $date->format('Y-m');
                    $trend[] = [
                        'month' => $date->translatedFormat('M Y'),
                        'revenue' => round((float) ($revenueByMonth[$key] ?? 0), 2),
                    ];
                }

                return $trend;
            }
        );
    }

    public function recentSales(User $user, string $period = 'month', ?string $from = null, ?string $to = null): Collection
    {
        $query = $user->sales()->with('customer');
        $this->applyPeriodFilter($query, $period, $from, $to);

        return $query
            ->orderBy('sale_date', 'desc')
            ->limit(10)
            ->get()
            ->map(fn (Sale $sale) => [
                'customer_name' => $sale->customer?->name ?? __('crm.unknown_customer'),
                'sale_date' => $sale->sale_date->translatedFormat('d. M Y.'),
                'dozen_count' => $sale->dozen_count,
                'individual_count' => $sale->individual_count,
                'total_amount' => (float) $sale->total_amount,
                'notes' => $sale->notes,
            ]);
    }

    public function perCustomer(User $user, int $customerId): array
    {
        return Cache::remember(
            $this->cacheKey($user, "customer_{$customerId}"),
            now()->addMinutes(5),
            function () use ($user, $customerId) {
                $customer = $user->customers()->find($customerId);

                if (! $customer) {
                    return ['found' => false];
                }

                $summary = $user->sales()
                    ->where('customer_id', $customerId)
                    ->selectRaw('COUNT(*) as transaction_count')
                    ->selectRaw('COALESCE(SUM(total_amount), 0) as total_revenue')
                    ->selectRaw('COALESCE(SUM(dozen_count * 12 + individual_count), 0) as total_eggs')
                    ->selectRaw('COALESCE(SUM(CASE WHEN total_amount = 0 THEN dozen_count * 12 + individual_count ELSE 0 END), 0) as free_eggs')
                    ->selectRaw('MAX(sale_date) as last_purchase')
                    ->first();

                $transactionCount = (int) ($summary->transaction_count ?? 0);
                $totalRevenue = (float) ($summary->total_revenue ?? 0);
                $totalEggs = (int) ($summary->total_eggs ?? 0);
                $freeEggs = (int) ($summary->free_eggs ?? 0);
                $paidEggs = $totalEggs - $freeEggs;
                $avgSale = $transactionCount > 0 ? $totalRevenue / $transactionCount : 0;
                $lastPurchase = $summary->last_purchase ? Carbon::parse($summary->last_purchase) : null;

                $avgDaysBetween = null;
                if ($transactionCount >= 2) {
                    $dates = $user->sales()
                        ->where('customer_id', $customerId)
                        ->orderBy('sale_date')
                        ->pluck('sale_date')
                        ->map(fn ($date) => $date instanceof Carbon ? $date : Carbon::parse($date))
                        ->values();

                    $totalDays = 0;
                    for ($i = 1; $i < $dates->count(); $i++) {
                        $totalDays += $dates[$i]->diffInDays($dates[$i - 1]);
                    }

                    $avgDaysBetween = (int) round($totalDays / max(1, $dates->count() - 1));
                }

                $now = now();
                $monthExpr = $this->monthKeyExpression('sale_date');
                $trendRows = $user->sales()
                    ->where('customer_id', $customerId)
                    ->where('sale_date', '>=', $now->copy()->subMonths(5)->startOfMonth()->toDateString())
                    ->selectRaw("{$monthExpr} as month_key, COALESCE(SUM(total_amount), 0) as revenue, COALESCE(SUM(dozen_count * 12 + individual_count), 0) as eggs")
                    ->groupByRaw($monthExpr)
                    ->get()
                    ->keyBy('month_key');

                $monthlyTrend = [];
                for ($i = 5; $i >= 0; $i--) {
                    $date = $now->copy()->subMonths($i);
                    $monthKey = $date->format('Y-m');
                    $trendRow = $trendRows->get($monthKey);

                    $monthlyTrend[] = [
                        'month' => $date->format('M Y'),
                        'revenue' => round((float) ($trendRow->revenue ?? 0), 2),
                        'eggs' => (int) ($trendRow->eggs ?? 0),
                    ];
                }

                $recentTransactions = $user->sales()
                    ->where('customer_id', $customerId)
                    ->orderBy('sale_date', 'desc')
                    ->limit(10)
                    ->get()
                    ->map(function (Sale $sale) {
                        $totalEggs = $sale->dozen_count * 12 + $sale->individual_count;
                        $pricePerEgg = $totalEggs > 0 && $sale->total_amount > 0
                            ? round($sale->total_amount / $totalEggs, 2)
                            : 0;

                        return [
                            'date' => $sale->sale_date->translatedFormat('d. M Y.'),
                            'eggs' => $totalEggs,
                            'pricePerEgg' => $pricePerEgg,
                            'total' => (float) $sale->total_amount,
                            'notes' => $sale->notes,
                            'isFree' => (float) $sale->total_amount === 0.0,
                        ];
                    })
                    ->toArray();

                return [
                    'found' => true,
                    'customer' => ['id' => $customer->id, 'name' => $customer->name, 'phone' => $customer->phone],
                    'totalRevenue' => number_format($totalRevenue, 2),
                    'totalEggs' => $totalEggs,
                    'paidEggs' => $paidEggs,
                    'freeEggs' => $freeEggs,
                    'transactionCount' => $transactionCount,
                    'avgSale' => number_format($avgSale, 2),
                    'avgDaysBetween' => $avgDaysBetween,
                    'lastPurchase' => $lastPurchase?->toIso8601String(),
                    'monthlyTrend' => $monthlyTrend,
                    'recentTransactions' => $recentTransactions,
                ];
            }
        );
    }

    public function monthlySalesSummary(User $user): array
    {
        $now = now();

        $thisMonthSales = (int) $user->sales()
            ->whereBetween('sale_date', [
                $now->copy()->startOfMonth()->toDateString(),
                $now->copy()->endOfMonth()->toDateString(),
            ])
            ->count();

        $lastMonthSales = (int) $user->sales()
            ->whereBetween('sale_date', [
                $now->copy()->subMonth()->startOfMonth()->toDateString(),
                $now->copy()->subMonth()->endOfMonth()->toDateString(),
            ])
            ->count();

        return [
            'thisMonthSales' => $thisMonthSales,
            'lastMonthSales' => $lastMonthSales,
            'thisMonthName' => $now->translatedFormat('F'),
        ];
    }

    /**
     * Clear cached revenue overview data for a specific user.
     * Called after sale/customer mutations to ensure fresh report data.
     */
    public function clearCacheForUser(User $user): void
    {
        Cache::put($this->cacheVersionKey($user), $this->getCacheVersion($user) + 1, now()->addDays(30));
    }

    /**
     * @return array{0: Carbon, 1: Carbon, 2: int}
     */
    private function resolveChartRange(string $period, ?string $from, ?string $to, int $defaultMonths): array
    {
        $now = now();

        return match ($period) {
            'month' => [
                $now->copy()->startOfMonth(),
                $now->copy()->endOfMonth(),
                1,
            ],
            'year' => [
                $now->copy()->startOfYear(),
                $now->copy()->endOfYear(),
                (int) $now->format('n'),
            ],
            'custom' => $this->resolveCustomChartRange($from, $to, $now, $defaultMonths),
            default => [
                $now->copy()->subMonths($defaultMonths - 1)->startOfMonth(),
                $now->copy()->endOfMonth(),
                $defaultMonths,
            ],
        };
    }

    /**
     * @return array{0: Carbon, 1: Carbon, 2: int}
     */
    private function resolveCustomChartRange(?string $from, ?string $to, Carbon $now, int $defaultMonths): array
    {
        $start = $from ? Carbon::parse($from)->startOfMonth() : $now->copy()->subMonths($defaultMonths - 1)->startOfMonth();
        $end = $to ? Carbon::parse($to)->endOfMonth() : $now->copy()->endOfMonth();
        $months = max(1, (int) $start->diffInMonths($end) + 1);

        return [$start, $end, $months];
    }

    private function applyPeriodFilter(Builder|HasMany $query, string $period, ?string $from, ?string $to): void
    {
        $now = now();

        match ($period) {
            'month' => $query->whereBetween('sale_date', $this->monthDateRange($now)),
            'year' => $query->whereBetween('sale_date', $this->yearDateRange($now)),
            'custom' => $this->applyCustomDateFilter($query, $from, $to),
            default => null, // 'all' — no filter
        };
    }

    private function applyCustomDateFilter(Builder|HasMany $query, ?string $from, ?string $to): void
    {
        if ($from) {
            $query->where('sale_date', '>=', Carbon::parse($from)->toDateString());
        }

        if ($to) {
            $query->where('sale_date', '<=', Carbon::parse($to)->toDateString());
        }

        if (! $from && ! $to) {
            $query->where('sale_date', '>=', now()->subMonths(3)->startOfDay()->toDateString());
        }
    }

    /**
     * @return array{0: string, 1: string}
     */
    private function monthDateRange(Carbon $date): array
    {
        return [
            $date->copy()->startOfMonth()->toDateString(),
            $date->copy()->endOfMonth()->toDateString(),
        ];
    }

    /**
     * @return array{0: string, 1: string}
     */
    private function yearDateRange(Carbon $date): array
    {
        return [
            $date->copy()->startOfYear()->toDateString(),
            $date->copy()->endOfYear()->toDateString(),
        ];
    }

    private function monthKeyExpression(string $column): string
    {
        return DB::getDriverName() === 'sqlite'
            ? "strftime('%Y-%m', {$column})"
            : "DATE_FORMAT({$column}, '%Y-%m')";
    }

    private function cacheKey(User $user, string $suffix): string
    {
        return sprintf('crm_reports2_%d_v%d_%s', $user->id, $this->getCacheVersion($user), $suffix);
    }

    private function cacheVersionKey(User $user): string
    {
        return "crm_cache_version_{$user->id}";
    }

    private function getCacheVersion(User $user): int
    {
        return max(1, (int) Cache::get($this->cacheVersionKey($user), 1));
    }
}
