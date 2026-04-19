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
        $hasFlockProfile = $user->flockProfile()->exists() || $user->flockBatches()->exists();
        $hasRecordedProduction = $user->eggEntries()->exists();
        $hasRecordedExpense = $user->expenses()->exists();
        $hasFeedTracking = $user->feedInventory()->exists();

        return [
            [
                'key' => 'setup-flock',
                'label' => 'Set up your flock',
                'points' => 50,
                'icon' => '🐔',
                'completed' => $hasFlockProfile,
                'action' => 'Setup Flock',
                'action_href' => route('app.flock.index'),
            ],
            [
                'key' => 'add-eggs',
                'label' => 'Record egg production',
                'points' => 30,
                'icon' => '🥚',
                'completed' => $hasRecordedProduction,
                'action' => 'Add Eggs',
                'action_href' => route('app.eggs.index'),
            ],
            [
                'key' => 'add-expense',
                'label' => 'Track an expense',
                'points' => 20,
                'icon' => '💸',
                'completed' => $hasRecordedExpense,
                'action' => 'Add Expense',
                'action_href' => route('app.expenses.index'),
            ],
            [
                'key' => 'add-feed',
                'label' => 'Track feed inventory',
                'points' => 20,
                'icon' => '🌾',
                'completed' => $hasFeedTracking,
                'action' => 'Add Feed',
                'action_href' => route('app.feed.index'),
            ],
        ];
    }

    /**
     * @return array{key: string, label: string, message: string}
     */
    private function resolvePhase(int $percentage): array
    {
        return match (true) {
            $percentage <= 40 => ['key' => 'new', 'label' => 'New User', 'message' => 'Get started with basic setup'],
            $percentage <= 70 => ['key' => 'getting-started', 'label' => 'Getting Started', 'message' => 'Expand to core features'],
            $percentage <= 90 => ['key' => 'active', 'label' => 'Active User', 'message' => 'Unlock advanced features'],
            default => ['key' => 'power', 'label' => 'Power User', 'message' => "You're using all features!"],
        };
    }
}
