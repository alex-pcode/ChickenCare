<?php

namespace App\Services;

use App\Models\User;

class EggStatsService
{
    public function getStats(User $user): array
    {
        $now = now();
        $thisWeekStart = $now->copy()->startOfWeek()->toDateString();
        $thisWeekEnd = $now->copy()->endOfWeek()->toDateString();
        $prevWeekStart = $now->copy()->subWeek()->startOfWeek()->toDateString();
        $prevWeekEnd = $now->copy()->subWeek()->endOfWeek()->toDateString();
        $thisMonthStart = $now->copy()->startOfMonth()->toDateString();
        $thisMonthEnd = $now->copy()->endOfMonth()->toDateString();
        $prevMonthDate = $now->copy()->subMonth();
        $prevMonthStart = $prevMonthDate->copy()->startOfMonth()->toDateString();
        $prevMonthEnd = $prevMonthDate->copy()->endOfMonth()->toDateString();

        $stats = $user->eggEntries()
            ->selectRaw('COALESCE(SUM(count), 0) as total_eggs')
            ->selectRaw('COUNT(DISTINCT date) as distinct_days')
            ->selectRaw('COALESCE(SUM(CASE WHEN date BETWEEN ? AND ? THEN count ELSE 0 END), 0) as this_week', [$thisWeekStart, $thisWeekEnd])
            ->selectRaw('COALESCE(SUM(CASE WHEN date BETWEEN ? AND ? THEN count ELSE 0 END), 0) as prev_week', [$prevWeekStart, $prevWeekEnd])
            ->selectRaw('COALESCE(SUM(CASE WHEN date BETWEEN ? AND ? THEN count ELSE 0 END), 0) as this_month', [$thisMonthStart, $thisMonthEnd])
            ->selectRaw('COALESCE(SUM(CASE WHEN date BETWEEN ? AND ? THEN count ELSE 0 END), 0) as prev_month', [$prevMonthStart, $prevMonthEnd])
            ->first();

        $totalEggs = (int) $stats->total_eggs;
        $distinctDays = (int) $stats->distinct_days;
        $averageDaily = $distinctDays > 0
            ? round($totalEggs / $distinctDays, 1)
            : 0;

        $last7DaysStart = $now->copy()->subDays(6)->startOfDay()->toDateString();
        $last7DaysEnd = $now->copy()->startOfDay()->toDateString();

        $dailyTotals = $user->eggEntries()
            ->whereBetween('date', [$last7DaysStart, $last7DaysEnd])
            ->selectRaw('date, SUM(count) as daily_total')
            ->groupBy('date')
            ->pluck('daily_total', 'date')
            ->all();

        $last7Days = [];
        for ($i = 6; $i >= 0; $i--) {
            $day = $now->copy()->subDays($i)->startOfDay();
            $key = $day->toDateString();
            $last7Days[] = [
                'label' => $day->format('D'),
                'date' => $key,
                'count' => (int) ($dailyTotals[$key] ?? 0),
            ];
        }

        return [
            'totalEggs' => $totalEggs,
            'averageDaily' => $averageDaily,
            'thisWeekTotal' => (int) $stats->this_week,
            'previousWeekTotal' => (int) $stats->prev_week,
            'thisMonthTotal' => (int) $stats->this_month,
            'previousMonthTotal' => (int) $stats->prev_month,
            'proteinLbs' => round($totalEggs * 0.125),
            'layRate' => null,
            'layingHens' => null,
            'last7Days' => $last7Days,
        ];
    }
}
