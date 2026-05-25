<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreBatchEventRequest;
use App\Models\BatchEvent;
use App\Models\FlockBatch;
use App\Traits\HandlesHtmx;
use Illuminate\Http\Request;

class BatchEventController extends Controller
{
    use HandlesHtmx;

    public function create(FlockBatch $batch)
    {
        $this->authorize('create', [BatchEvent::class, $batch]);

        return view('batches.partials.event-form', [
            'batch' => $batch,
            'event' => null,
        ]);
    }

    public function store(StoreBatchEventRequest $request, FlockBatch $batch)
    {
        $event = $batch->batchEvents()->create([
            ...$request->validated(),
            'user_id' => $request->user()->id,
        ]);

        if ($this->isHtmx($request)) {
            return response()->view('batches.partials.timeline-event-row', [
                'event' => $event,
                'index' => 0,
            ])->header('HX-Trigger', json_encode([
                'flock:changed' => true,
                'flock:success' => __('batches.messages.event_added_timeline'),
            ]));
        }

        return redirect()->back()->with('success', __('batches.messages.event_added'));
    }

    public function edit(FlockBatch $batch, BatchEvent $event)
    {
        $this->authorize('update', $event);
        abort_unless($event->batch_id === $batch->id, 404);

        return view('batches.partials.event-form', compact('batch', 'event'));
    }

    public function update(StoreBatchEventRequest $request, FlockBatch $batch, BatchEvent $event)
    {
        $this->authorize('update', $event);
        abort_unless($event->batch_id === $batch->id, 404);

        $event->update($request->validated());

        if ($this->isHtmx($request)) {
            $batch->load(['batchEvents' => fn ($q) => $q->orderBy('date', 'desc')]);

            return response()->view('batches.partials.timeline-event-row', [
                'event' => $event,
                'index' => 0,
            ])->header('HX-Trigger', json_encode([
                'flock:changed' => true,
                'flock:success' => __('flock.messages.event_updated'),
            ]));
        }

        return redirect()->back()->with('success', __('batches.messages.event_updated'));
    }

    public function destroy(Request $request, FlockBatch $batch, BatchEvent $event)
    {
        $this->authorize('delete', $event);
        abort_unless($event->batch_id === $batch->id, 404);

        $event->delete();

        if ($this->isHtmx($request)) {
            return response('', 200)
                ->header('HX-Trigger', json_encode(['flock:changed' => true]));
        }

        return redirect()->back()->with('success', __('batches.messages.event_deleted'));
    }
}
