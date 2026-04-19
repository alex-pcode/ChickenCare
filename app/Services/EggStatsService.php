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
        $thisMonth = $now->month;
        $thisYear = $now->year;
        $prevMonthDate = $now->copy()->subMonth();
        $prevMonth = $prevMonthDate->month;
        $prevMonthYear = $prevMonthDate->year;

        $stats = $user->eggEntries()
            ->selectRaw('COALESCE(SUM(count), 0) as total_eggs')
            ->selectRaw('COUNT(DISTINCT date) as distinct_days')
            ->selectRaw('COALESCE(SUM(CASE WHEN date BETWEEN ? AND ? THEN count ELSE 0 END), 0) as this_week', [$thisWeekStart, $thisWeekEnd])
            ->selectRaw('COALESCE(SUM(CASE WHEN date BETWEEN ? AND ? THEN count ELSE 0 END), 0) as prev_week', [$prevWeekStart, $prevWeekEnd])
            ->selectRaw("COALESCE(SUM(CASE WHEN CAST(strftime('%m', date) AS INTEGER) = ? AND CAST(strftime('%Y', date) AS INTEGER) = ? THEN count ELSE 0 END), 0) as this_month", [$thisMonth, $thisYear])
            ->selectRaw("COALESCE(SUM(CASE WHEN CAST(strftime('%m', date) AS INTEGER) = ? AND CAST(strftime('%Y', date) AS INTEGER) = ? THEN count ELSE 0 END), 0) as prev_month", [$prevMonth, $prevMonthYear])
            ->first();

        $totalEggs = (int) $stats->total_eggs;
        $distinctDays = (int) $stats->distinct_days;
        $averageDaily = $distinctDays > 0
            ? round($totalEggs / $distinctDays, 1)
            : 0;

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
        ];
    }
}
