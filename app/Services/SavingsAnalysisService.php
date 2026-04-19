<?php

namespace App\Services;

use App\Enums\BatchAgeAtAcquisition;
use App\Enums\ChickenGoal;
use App\Models\User;
use App\Support\SavingsPeriod;
use Carbon\Carbon;

final class SavingsAnalysisService
{
    /**
     * @return array{totalEggs: int, eggValue: float, actualRevenue: float, totalExpenses: float, netResult: float, isBusinessGoal: bool, eggPrice: float}
     */
    public function financialSummary(User $user, SavingsPeriod $period): array
    {
        $eggPrice = (float) ($user->egg_price ?? 0.30);
        $isBusinessGoal = ($user->chicken_goal ?? ChickenGoal::Hobby) === ChickenGoal::Business;

        // Query eggs for period
        $eggsQuery = $user->eggEntries();
        if ($period->from) {
            $eggsQuery->where('date', '>=', $period->from);
        }
        if ($period->to) {
            $eggsQuery->where('date', '<=', $period->to);
        }
        $totalEggs = (int) $eggsQuery->sum('count');
        $eggValue = $totalEggs * $eggPrice;

        // Query sales revenue for period
        $salesQuery = $user->sales();
        if ($period->from) {
            $salesQuery->where('sale_date', '>=', $period->from);
        }
        if ($period->to) {
            $salesQuery->where('sale_date', '<=', $period->to);
        }
        $actualRevenue = (float) $salesQuery->sum('total_amount');

        // Query expenses for period
        $expensesQuery = $user->expenses();
        if ($period->from) {
            $expensesQuery->where('date', '>=', $period->from);
        }
        if ($period->to) {
            $expensesQuery->where('date', '<=', $period->to);
        }
        $totalExpenses = (float) $expensesQuery->sum('amount');

        // Net result depends on goal
        $netResult = $isBusinessGoal
            ? $actualRevenue - $totalExpenses
            : $eggValue - $totalExpenses;

        return [
            'totalEggs' => $totalEggs,
            'eggValue' => $eggValue,
            'actualRevenue' => $actualRevenue,
            'totalExpenses' => $totalExpenses,
            'netResult' => $netResult,
            'isBusinessGoal' => $isBusinessGoal,
            'eggPrice' => $eggPrice,
        ];
    }

    /**
     * @return array{daysGone: int, freeEggs: int, omelettes: int, comedyHours: int, chickensSaved: int, flocksRaised: int}
     */
    public function lifetimeImpact(User $user): array
    {
        $firstEgg = $user->eggEntries()->orderBy('date')->value('date');
        $lastEgg = $user->eggEntries()->orderByDesc('date')->value('date');
        $daysGone = ($firstEgg && $lastEgg)
            ? Carbon::parse($firstEgg)->diffInDays(Carbon::parse($lastEgg)) + 1
            : 0;

        $lifetimeEggs = (int) $user->eggEntries()->sum('count');

        $eggsSold = (int) $user->sales()
            ->where('total_amount', '>', 0)
            ->selectRaw('COALESCE(SUM(dozen_count * 12 + individual_count), 0) as total')
            ->value('total');

        $freeEggs = (int) $user->sales()
            ->where('total_amount', 0)
            ->selectRaw('COALESCE(SUM(dozen_count * 12 + individual_count), 0) as total')
            ->value('total');

        $consumed = $lifetimeEggs - $eggsSold - $freeEggs;
        $omelettes = max(0, (int) floor($consumed / 5));

        $comedyHours = (int) floor($daysGone * 0.5);

        $chickensSaved = (int) $user->flockBatches()->sum('initial_count');

        $flocksRaised = (int) $user->flockBatches()
            ->where('age_at_acquisition', BatchAgeAtAcquisition::Chick->value)
            ->count();

        return [
            'daysGone' => $daysGone,
            'freeEggs' => $freeEggs,
            'omelettes' => $omelettes,
            'comedyHours' => $comedyHours,
            'chickensSaved' => $chickensSaved,
            'flocksRaised' => $flocksRaised,
        ];
    }

    /**
     * @return array{costPerEgg: float|null, profitPerEgg: float|null, eggsToBreakEven: int|null, hasCostData: bool, hasBreakEvenData: bool, profitPositive: bool}
     */
    public function costAnalysis(User $user, SavingsPeriod $period, float $eggPrice): array
    {
        // Reuse the same period-scoped queries
        $eggsQuery = $user->eggEntries();
        if ($period->from) {
            $eggsQuery->where('date', '>=', $period->from);
        }
        if ($period->to) {
            $eggsQuery->where('date', '<=', $period->to);
        }
        $totalEggs = (int) $eggsQuery->sum('count');

        $expensesQuery = $user->expenses();
        if ($period->from) {
            $expensesQuery->where('date', '>=', $period->from);
        }
        if ($period->to) {
            $expensesQuery->where('date', '<=', $period->to);
        }
        $totalExpenses = (float) $expensesQuery->sum('amount');

        // Cost per egg
        $costPerEgg = $totalEggs > 0 ? $totalExpenses / $totalEggs : null;

        // Profit per egg
        $profitPerEgg = $totalEggs > 0 ? $eggPrice - ($totalExpenses / $totalEggs) : null;

        // Eggs to break even
        $eggsToBreakEven = ($totalExpenses > 0 && $eggPrice > 0)
            ? (int) ceil($totalExpenses / $eggPrice)
            : null;

        return [
            'costPerEgg' => $costPerEgg,
            'profitPerEgg' => $profitPerEgg,
            'eggsToBreakEven' => $eggsToBreakEven,
            'hasCostData' => $totalEggs > 0,
            'hasBreakEvenData' => $totalExpenses > 0 && $eggPrice > 0,
            'profitPositive' => $profitPerEgg !== null && $profitPerEgg >= 0,
        ];
    }
}
