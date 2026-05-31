<?php

namespace App\Services;

use App\Models\User;

class ViabilityService
{
    /**
     * @return array{monthly_eggs: int, monthly_revenue: float, monthly_costs: float, monthly_profit: float, cost_per_egg: float|null, profit_per_bird: float|null, break_even_months: int|null, annual_roi_pct: float|null, inputs: array}
     */
    public function calculate(User $user, array $inputs, ?array $defaults = null): array
    {
        $defaults = $defaults ?? $this->getDefaults($user);

        $birds = max(1, (int) ($inputs['birds'] ?? $defaults['birds']));
        $layingRate = min(1.0, max(0.0, (float) ($inputs['laying_rate'] ?? $defaults['laying_rate'])));
        $pricePerDozen = max(0.0, (float) ($inputs['price_per_dozen'] ?? $defaults['price_per_dozen']));
        $pricePerIndividual = max(0.0, (float) ($inputs['price_per_individual'] ?? $defaults['price_per_individual']));
        $monthlyFeedCost = max(0.0, (float) ($inputs['monthly_feed_cost'] ?? $defaults['monthly_feed_cost']));
        $otherMonthlyCosts = max(0.0, (float) ($inputs['other_monthly_costs'] ?? $defaults['other_monthly_costs']));
        $costPerBird = max(0.0, (float) ($inputs['cost_per_bird'] ?? $defaults['cost_per_bird']));
        $sellAs = in_array($inputs['sell_as'] ?? 'dozen', ['dozen', 'individual', 'mixed'])
            ? ($inputs['sell_as'] ?? 'dozen')
            : 'dozen';

        $monthlyEggs = (int) round($birds * $layingRate * 30);

        $monthlyRevenue = match ($sellAs) {
            'dozen' => floor($monthlyEggs / 12) * $pricePerDozen,
            'individual' => $monthlyEggs * $pricePerIndividual,
            'mixed' => (floor(($monthlyEggs / 2) / 12) * $pricePerDozen) + (($monthlyEggs - floor($monthlyEggs / 2)) * $pricePerIndividual),
        };

        $monthlyCosts = $monthlyFeedCost + $otherMonthlyCosts;
        $monthlyProfit = $monthlyRevenue - $monthlyCosts;
        $costPerEgg = $monthlyEggs > 0 ? round($monthlyCosts / $monthlyEggs, 4) : null;
        $profitPerBird = $birds > 0 ? round($monthlyProfit / $birds, 2) : null;

        $acquisitionCost = $costPerBird * $birds;
        $breakEvenMonths = ($monthlyProfit > 0 && $acquisitionCost > 0) ? (int) ceil($acquisitionCost / $monthlyProfit) : null;
        $annualRoiPct = $acquisitionCost > 0 ? round(($monthlyProfit * 12 / $acquisitionCost) * 100, 1) : null;

        return [
            'monthly_eggs' => $monthlyEggs,
            'monthly_revenue' => round($monthlyRevenue, 2),
            'monthly_costs' => round($monthlyCosts, 2),
            'monthly_profit' => round($monthlyProfit, 2),
            'cost_per_egg' => $costPerEgg,
            'profit_per_bird' => $profitPerBird,
            'break_even_months' => $breakEvenMonths,
            'annual_roi_pct' => $annualRoiPct,
            'inputs' => [
                'birds' => $birds,
                'laying_rate' => $layingRate,
                'price_per_dozen' => $pricePerDozen,
                'price_per_individual' => $pricePerIndividual,
                'monthly_feed_cost' => $monthlyFeedCost,
                'other_monthly_costs' => $otherMonthlyCosts,
                'cost_per_bird' => $costPerBird,
                'sell_as' => $sellAs,
            ],
        ];
    }

    /**
     * @return array{birds: int, laying_rate: float, price_per_dozen: float, price_per_individual: float, monthly_feed_cost: float, other_monthly_costs: float, cost_per_bird: float, sell_as: string}
     */
    public function getDefaults(User $user): array
    {
        $activeBirds = (int) $user->flockBatches()->where('is_active', true)->sum('hens_count');

        $feedTotal = (float) $user->expenses()
            ->where('category', 'feed')
            ->where('date', '>=', now()->subMonths(3)->startOfMonth()->toDateString())
            ->sum('amount');
        $avgFeedCost = $feedTotal > 0 ? $feedTotal / 3 : 0.0;

        return [
            'birds' => $activeBirds > 0 ? $activeBirds : 10,
            'laying_rate' => 0.7,
            'price_per_dozen' => 3.00,
            'price_per_individual' => 0.30,
            'monthly_feed_cost' => round($avgFeedCost, 2),
            'other_monthly_costs' => 0.0,
            'cost_per_bird' => 0.0,
            'sell_as' => 'dozen',
        ];
    }

    /**
     * @return array{birdCount: int, eggPrice: float, startingCost: int}
     */
    public function getNewDefaults(?User $user = null): array
    {
        $activeBirds = $user
            ? (int) $user->flockBatches()->where('is_active', true)->sum('hens_count')
            : 0;

        return [
            'birdCount' => $activeBirds > 0 ? $activeBirds : 5,
            'eggPrice' => 0.30,
            'startingCost' => 50,
        ];
    }
}
