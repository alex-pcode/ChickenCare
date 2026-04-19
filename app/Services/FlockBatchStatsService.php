<?php

namespace App\Services;

use App\Models\DeathRecord;
use App\Models\FlockBatch;
use App\Models\User;

class FlockBatchStatsService
{
    public function overview(User $user): array
    {
        $batches = $user->flockBatches()->active()->with('deathRecords')->get();

        // Laying: batches where actual_laying_start_date IS NOT NULL
        $layingBatches = $batches->filter(fn ($b) => $b->actual_laying_start_date !== null);
        $layingTotal = $layingBatches->sum(fn ($b) => max(0, ($b->hens_count ?? 0) - ($b->brooding_count ?? 0)));
        $layingCount = $layingBatches->filter(fn ($b) => ($b->hens_count ?? 0) > 0)->count();

        // Not Laying: actual_laying_start_date IS NULL AND (hens_count > 0 OR type = 'hens')
        $notLayingBatches = $batches->filter(
            fn ($b) => $b->actual_laying_start_date === null
                    && (($b->hens_count ?? 0) > 0 || $b->type === 'hens')
        );
        $notLayingTotal = $notLayingBatches->sum(fn ($b) => max(0, ($b->hens_count ?? 0) - ($b->brooding_count ?? 0)));
        $notLayingCount = $notLayingBatches->count();

        // Brooding
        $showBrooding = $batches->contains(fn ($b) => ($b->brooding_count ?? 0) > 0);
        $broodingTotal = $batches->sum(fn ($b) => $b->brooding_count ?? 0);
        $broodingCount = $batches->filter(fn ($b) => ($b->brooding_count ?? 0) > 0)->count();

        // Roosters
        $roostersTotal = $batches->sum(fn ($b) => $b->roosters_count ?? 0);
        $roostersCount = $batches->filter(fn ($b) => ($b->roosters_count ?? 0) > 0)->count();

        // Chicks
        $chicksTotal = $batches->sum(fn ($b) => $b->chicks_count ?? 0);
        $chicksCount = $batches->filter(fn ($b) => ($b->chicks_count ?? 0) > 0)->count();

        return [
            'laying'      => ['total' => $layingTotal,   'label' => "{$layingCount} batches laying"],
            'notLaying'   => ['total' => $notLayingTotal, 'label' => "{$notLayingCount} batches"],
            'brooding'    => ['total' => $broodingTotal,  'label' => "{$broodingCount} hen brooding"],
            'roosters'    => ['total' => $roostersTotal,  'label' => "{$roostersCount} batches"],
            'chicks'      => ['total' => $chicksTotal,    'label' => "{$chicksCount} batches"],
            'showBrooding' => $showBrooding,
        ];
    }

    public function metricDisplayStats(User $user): array
    {
        $batches = $user->flockBatches()->active()->with('deathRecords')->get();

        return [
            'totalBatches'  => $batches->count(),
            'totalBirds'    => $batches->sum('current_count'),
            'layingBatches' => $batches->filter(fn ($b) => $b->actual_laying_start_date !== null)->count(),
            'totalLosses'   => $batches->sum(fn ($b) => $b->deathRecords->sum('count')),
        ];
    }

    public function tabCounts(User $user): array
    {
        return [
            'batches'  => $user->flockBatches()->active()->count(),
            'deaths'   => DeathRecord::whereIn('batch_id', $user->flockBatches()->select('id'))->count(),
            'addBatch' => null,
        ];
    }

    public function batchComposition(FlockBatch $batch): array
    {
        return [
            'hens'       => $batch->hens_count ?? 0,
            'activeHens' => max(0, ($batch->hens_count ?? 0) - ($batch->brooding_count ?? 0)),
            'brooding'   => $batch->brooding_count ?? 0,
            'roosters'   => $batch->roosters_count ?? 0,
            'chicks'     => $batch->chicks_count ?? 0,
            'total'      => $batch->current_count ?? 0,
        ];
    }
}
