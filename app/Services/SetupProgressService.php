<?php

namespace App\Services;

use App\Models\User;

class SetupProgressService
{
    /**
     * @return array{
     *     percentage: int,
     *     bracket: string,
     *     phase: array{key: string, label: string, message: string},
     *     items: list<array{key: string, label: string, points: int, icon: string, completed: bool, action: string, action_href: string}>
     * }
     */
    public function compute(User $user): array
    {
        $items = $this->buildItems($user);

        $collection = collect($items);
        $total = $collection->sum('points');
        $earned = $collection->where('completed', true)->sum('points');
        $percentage = $total > 0 ? (int) round($earned / $total * 100) : 0;
        $phase = $this->resolvePhase($percentage);

        return [
            'percentage' => $percentage,
            'bracket' => $phase['key'],
            'phase' => $phase,
            'items' => $items,
        ];
    }

    /**
     * @return list<array{key: string, label: string, points: int, icon: string, completed: bool, action: string, action_href: string}>
     */
    private function buildItems(User $user): array
    {
        $hasSetUpFlock = $user->flockBatches()->exists() || $this->hasMeaningfulFlockProfile($user);
        $hasRecordedProduction = $user->eggEntries()->exists();
        $hasRecordedExpense = $user->expenses()->exists();
        $hasFeedTracking = $user->feedInventory()->exists();

        return [
            [
                'key' => 'setup-flock',
                'label' => __('dashboard.setup.items.setup-flock.label'),
                'points' => 50,
                'icon' => '🐔',
                'completed' => $hasSetUpFlock,
                'action' => __('dashboard.setup.items.setup-flock.action'),
                'action_href' => route('app.flock.index'),
            ],
            [
                'key' => 'add-eggs',
                'label' => __('dashboard.setup.items.add-eggs.label'),
                'points' => 30,
                'icon' => '🥚',
                'completed' => $hasRecordedProduction,
                'action' => __('dashboard.setup.items.add-eggs.action'),
                'action_href' => route('app.eggs.index'),
            ],
            [
                'key' => 'add-expense',
                'label' => __('dashboard.setup.items.add-expense.label'),
                'points' => 20,
                'icon' => '💸',
                'completed' => $hasRecordedExpense,
                'action' => __('dashboard.setup.items.add-expense.action'),
                'action_href' => route('app.expenses.index'),
            ],
            [
                'key' => 'add-feed',
                'label' => __('dashboard.setup.items.add-feed.label'),
                'points' => 20,
                'icon' => '🌾',
                'completed' => $hasFeedTracking,
                'action' => __('dashboard.setup.items.add-feed.action'),
                'action_href' => route('app.feed.index'),
            ],
        ];
    }

    /**
     * A flock profile row is auto-created (empty) the first time a user opens the
     * flock page, so its mere existence is not proof of setup. Only count it once
     * it holds real flock data.
     */
    private function hasMeaningfulFlockProfile(User $user): bool
    {
        return $user->flockProfile()
            ->where(function ($query): void {
                $query->where('flock_size', '>', 0)
                    ->orWhere('hens', '>', 0)
                    ->orWhere('roosters', '>', 0)
                    ->orWhere('chicks', '>', 0)
                    ->orWhere('brooding', '>', 0);
            })
            ->exists();
    }

    /**
     * @return array{key: string, label: string, message: string}
     */
    private function resolvePhase(int $percentage): array
    {
        return match (true) {
            $percentage <= 40 => ['key' => 'new', 'label' => __('dashboard.setup.phases.new.label'), 'message' => __('dashboard.setup.phases.new.message')],
            $percentage <= 70 => ['key' => 'getting-started', 'label' => __('dashboard.setup.phases.getting-started.label'), 'message' => __('dashboard.setup.phases.getting-started.message')],
            $percentage <= 90 => ['key' => 'active', 'label' => __('dashboard.setup.phases.active.label'), 'message' => __('dashboard.setup.phases.active.message')],
            default => ['key' => 'power', 'label' => __('dashboard.setup.phases.power.label'), 'message' => __('dashboard.setup.phases.power.message')],
        };
    }
}
