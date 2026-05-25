<?php

namespace App\Services;

use App\Models\DeathRecord;
use App\Models\FlockBatch;
use App\Models\User;

class FlockBatchStatsService
{
    public function overview(User $user): array
    {
        $stats = $this->activeBatchAggregates($user);
        $layingCount = (int) $stats->laying_count;
        $notLayingCount = (int) $stats->not_laying_count;
        $broodingCount = (int) $stats->brooding_count;
        $roostersCount = (int) $stats->roosters_count;
        $chicksCount = (int) $stats->chicks_count;

        return [
            'laying' => ['total' => (int) $stats->laying_total, 'label' => trans_choice('flock.overview.labels.laying_batches', $layingCount, ['count' => $layingCount])],
            'notLaying' => ['total' => (int) $stats->not_laying_total, 'label' => trans_choice('flock.overview.labels.not_laying_batches', $notLayingCount, ['count' => $notLayingCount])],
            'brooding' => ['total' => (int) $stats->brooding_total, 'label' => trans_choice('flock.overview.labels.brooding_hens', $broodingCount, ['count' => $broodingCount])],
            'roosters' => ['total' => (int) $stats->roosters_total, 'label' => trans_choice('flock.overview.labels.rooster_batches', $roostersCount, ['count' => $roostersCount])],
            'chicks' => ['total' => (int) $stats->chicks_total, 'label' => trans_choice('flock.overview.labels.chick_batches', $chicksCount, ['count' => $chicksCount])],
            'showBrooding' => (int) $stats->brooding_total > 0,
            'totalBirds' => (int) $stats->total_birds,
        ];
    }

    public function metricDisplayStats(User $user): array
    {
        $stats = $this->activeBatchAggregates($user);
        $activeBatchIds = $user->flockBatches()->active()->select('id');

        return [
            'totalBatches' => (int) $stats->total_batches,
            'totalBirds' => (int) $stats->total_birds,
            'layingBatches' => (int) $stats->laying_batch_count,
            'totalLosses' => (int) DeathRecord::whereIn('batch_id', $activeBatchIds)->sum('count'),
        ];
    }

    public function tabCounts(User $user): array
    {
        return [
            'batches' => $user->flockBatches()->active()->count(),
            'deaths' => DeathRecord::whereIn('batch_id', $user->flockBatches()->select('id'))->count(),
            'addBatch' => null,
        ];
    }

    public function batchComposition(FlockBatch $batch): array
    {
        return [
            'hens' => $batch->hens_count ?? 0,
            'activeHens' => max(0, ($batch->hens_count ?? 0) - ($batch->brooding_count ?? 0)),
            'brooding' => $batch->brooding_count ?? 0,
            'roosters' => $batch->roosters_count ?? 0,
            'chicks' => $batch->chicks_count ?? 0,
            'total' => $batch->current_count ?? 0,
        ];
    }

    private function activeBatchAggregates(User $user): object
    {
        return once(fn () => $user->flockBatches()
            ->active()
            ->selectRaw('COUNT(*) as total_batches')
            ->selectRaw('COALESCE(SUM(current_count), 0) as total_birds')
            ->selectRaw('COALESCE(SUM(CASE WHEN actual_laying_start_date IS NOT NULL THEN 1 ELSE 0 END), 0) as laying_batch_count')
            ->selectRaw('COALESCE(SUM(CASE WHEN actual_laying_start_date IS NOT NULL THEN CASE WHEN COALESCE(hens_count, 0) - COALESCE(brooding_count, 0) > 0 THEN COALESCE(hens_count, 0) - COALESCE(brooding_count, 0) ELSE 0 END ELSE 0 END), 0) as laying_total')
            ->selectRaw("COALESCE(SUM(CASE WHEN actual_laying_start_date IS NULL AND (COALESCE(hens_count, 0) > 0 OR type = 'hens') THEN 1 ELSE 0 END), 0) as not_laying_count")
            ->selectRaw("COALESCE(SUM(CASE WHEN actual_laying_start_date IS NULL AND (COALESCE(hens_count, 0) > 0 OR type = 'hens') THEN CASE WHEN COALESCE(hens_count, 0) - COALESCE(brooding_count, 0) > 0 THEN COALESCE(hens_count, 0) - COALESCE(brooding_count, 0) ELSE 0 END ELSE 0 END), 0) as not_laying_total")
            ->selectRaw('COALESCE(SUM(COALESCE(brooding_count, 0)), 0) as brooding_total')
            ->selectRaw('COALESCE(SUM(CASE WHEN COALESCE(brooding_count, 0) > 0 THEN 1 ELSE 0 END), 0) as brooding_count')
            ->selectRaw('COALESCE(SUM(COALESCE(roosters_count, 0)), 0) as roosters_total')
            ->selectRaw('COALESCE(SUM(CASE WHEN COALESCE(roosters_count, 0) > 0 THEN 1 ELSE 0 END), 0) as roosters_count')
            ->selectRaw('COALESCE(SUM(COALESCE(chicks_count, 0)), 0) as chicks_total')
            ->selectRaw('COALESCE(SUM(CASE WHEN COALESCE(chicks_count, 0) > 0 THEN 1 ELSE 0 END), 0) as chicks_count')
            ->first());
    }
}
