<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreDeathRecordRequest;
use App\Models\DeathRecord;
use App\Models\FlockBatch;
use App\Traits\HandlesHtmx;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class DeathRecordController extends Controller
{
    use HandlesHtmx;

    public function index(Request $request, FlockBatch $batch): View
    {
        $this->authorize('view', $batch);

        $sortAllow = ['date', 'count'];
        $sort = in_array($request->query('sort'), $sortAllow, true) ? $request->query('sort') : 'date';
        $dir = $request->query('dir') === 'asc' ? 'asc' : 'desc';

        $records = $batch->deathRecords()
            ->orderBy($sort, $dir)
            ->paginate(10)
            ->appends(['sort' => $sort, 'dir' => $dir]);

        return view('batches.partials.deaths-history-table', compact('batch', 'records', 'sort', 'dir'));
    }

    public function create(FlockBatch $batch)
    {
        $this->authorize('create', [DeathRecord::class, $batch]);

        return view('batches.partials.death-form', [
            'batch' => $batch,
            'death' => null,
        ]);
    }

    public function store(StoreDeathRecordRequest $request, FlockBatch $batch)
    {
        DB::transaction(function () use ($batch, $request) {
            $batch->deathRecords()->create([
                ...$request->validated(),
                'user_id' => $request->user()->id,
            ]);

            $batch->decrement('current_count', $request->validated()['count']);
        });

        if ($this->isHtmx($request)) {
            $batch->refresh();

            return response()
                ->view('batches.partials.deaths-form', [
                    'batch' => $batch,
                    'successMessage' => __('batches.messages.loss_logged'),
                ])
                ->header('HX-Trigger', json_encode([
                    'flock:changed' => true,
                    'flock:success' => __('batches.messages.loss_logged'),
                ]));
        }

        return redirect()->back()->with('success', __('batches.messages.death_added'));
    }

    public function edit(FlockBatch $batch, DeathRecord $death)
    {
        $this->authorize('update', $death);
        abort_unless($death->batch_id === $batch->id, 404);

        return view('batches.partials.death-form', compact('batch', 'death'));
    }

    public function update(StoreDeathRecordRequest $request, FlockBatch $batch, DeathRecord $death)
    {
        $this->authorize('update', $death);
        abort_unless($death->batch_id === $batch->id, 404);

        DB::transaction(function () use ($batch, $death, $request) {
            $batch->increment('current_count', $death->count);
            $batch->decrement('current_count', $request->validated()['count']);
            $death->update($request->validated());
        });

        if ($this->isHtmx($request)) {
            $batch->refresh();

            return response()
                ->view('batches.partials.deaths-form', [
                    'batch' => $batch,
                    'successMessage' => __('batches.messages.loss_updated'),
                ])
                ->header('HX-Trigger', json_encode([
                    'flock:changed' => true,
                    'flock:success' => __('batches.messages.loss_updated'),
                ]));
        }

        return redirect()->back()->with('success', __('batches.messages.death_updated'));
    }

    public function destroy(Request $request, FlockBatch $batch, DeathRecord $death)
    {
        $this->authorize('delete', $death);
        abort_unless($death->batch_id === $batch->id, 404);

        DB::transaction(function () use ($batch, $death) {
            $batch->increment('current_count', $death->count);
            $death->delete();
        });

        if ($this->isHtmx($request)) {
            return response('', 200)
                ->header('HX-Trigger', json_encode(['flock:changed' => true]));
        }

        return redirect()->back()->with('success', __('batches.messages.death_deleted'));
    }
}
