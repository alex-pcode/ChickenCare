<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreFlockBatchRequest;
use App\Http\Requests\UpdateCompositionRequest;
use App\Http\Requests\UpdateFlockBatchRequest;
use App\Http\Requests\UpdateLayingDateRequest;
use App\Models\FlockBatch;
use App\Traits\HandlesHtmx;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\View\View;

class FlockBatchController extends Controller
{
    use HandlesHtmx;

    public function index(Request $request)
    {
        $filter = $request->query('filter', 'active');

        $query = match ($filter) {
            'archived' => $request->user()->flockBatches()->archived(),
            'all' => $request->user()->flockBatches(),
            default => $request->user()->flockBatches()->active(),
        };

        $allowedSorts = ['batch_name', 'current_count', 'initial_count', 'acquisition_date', 'source'];
        $sort = in_array($request->query('sort'), $allowedSorts, true) ? $request->query('sort') : 'acquisition_date';
        $dir = in_array($request->query('dir'), ['asc', 'desc'], true) ? $request->query('dir') : 'desc';

        $batches = $query->orderBy($sort, $dir)->paginate(10)->appends($request->query());

        if ($this->isHtmx($request) && ! $request->hasHeader('HX-Boosted')) {
            return view('batches.partials.batches-table', compact('batches', 'sort', 'dir'));
        }

        return view('batches.index', compact('batches', 'filter', 'sort', 'dir'));
    }

    public function create()
    {
        return view('batches.create');
    }

    public function store(StoreFlockBatchRequest $request)
    {
        $validated = $request->validated();

        $hens = (int) ($validated['hens_count'] ?? 0);
        $brooding = (int) ($validated['brooding_count'] ?? 0);
        $roosters = (int) ($validated['roosters_count'] ?? 0);
        $chicks = (int) ($validated['chicks_count'] ?? 0);
        $total = $hens + $brooding + $roosters + $chicks;

        $batch = $request->user()->flockBatches()->create([
            ...$validated,
            'type' => FlockBatch::resolveType($hens, $roosters, $chicks, $brooding),
            'initial_count' => $total,
            'current_count' => $total,
        ]);

        if ($this->isHtmx($request)) {
            return $this->htmxRedirect(route('app.batches.show', $batch));
        }

        return redirect()->route('app.batches.show', $batch)
            ->with('success', __('batches.messages.created'));
    }

    public function show(Request $request, FlockBatch $batch)
    {
        $this->authorize('view', $batch);

        $batch->load([
            'batchEvents' => fn ($q) => $q->orderBy('date', 'desc')->orderBy('id', 'desc'),
        ]);

        return view('batches.show', compact('batch'));
    }

    public function edit(FlockBatch $batch)
    {
        $this->authorize('update', $batch);

        return view('batches.edit', compact('batch'));
    }

    public function update(UpdateFlockBatchRequest $request, FlockBatch $batch)
    {
        $this->authorize('update', $batch);

        $batch->update($request->validated());

        if ($this->isHtmx($request)) {
            return $this->htmxRedirect(route('app.batches.show', $batch));
        }

        return redirect()->route('app.batches.show', $batch)
            ->with('success', __('batches.messages.updated'));
    }

    public function destroy(Request $request, FlockBatch $batch)
    {
        $this->authorize('delete', $batch);

        $batch->update(['is_active' => false]);

        if ($this->isHtmx($request)) {
            return response('', 200);
        }

        return redirect()->route('app.batches.index')
            ->with('success', __('batches.messages.archived'));
    }

    public function compositionModal(Request $request, FlockBatch $batch): View
    {
        $this->authorize('update', $batch);

        return view('batches.partials.composition-modal', compact('batch'));
    }

    public function layingDateModal(Request $request, FlockBatch $batch): View
    {
        $this->authorize('update', $batch);

        return view('batches.partials.laying-date-modal', compact('batch'));
    }

    public function updateComposition(UpdateCompositionRequest $request, FlockBatch $batch): Response
    {
        $this->authorize('update', $batch);

        $hens = (int) $request->hens_count;
        $roosters = (int) $request->roosters_count;
        $chicks = (int) $request->chicks_count;
        $brooding = (int) $request->brooding_count;

        $batch->update([
            'hens_count' => $hens,
            'roosters_count' => $roosters,
            'chicks_count' => $chicks,
            'brooding_count' => $brooding,
            'current_count' => $hens + $roosters + $chicks + $brooding,
            'type' => FlockBatch::resolveType($hens, $roosters, $chicks, $brooding),
        ]);

        return response('', 200)
            ->header('HX-Trigger', json_encode([
                'flock:changed' => true,
                'flock:success' => __('batches.messages.composition_updated'),
                'modal:close' => true,
            ]));
    }

    public function updateLayingDate(UpdateLayingDateRequest $request, FlockBatch $batch): Response
    {
        $this->authorize('update', $batch);

        $batch->update([
            'actual_laying_start_date' => $request->actual_laying_start_date ?: null,
        ]);

        return response('', 200)
            ->header('HX-Trigger', json_encode([
                'flock:changed' => true,
                'flock:success' => $request->actual_laying_start_date
                    ? __('batches.messages.laying_date_set')
                    : __('batches.messages.laying_date_cleared'),
                'modal:close' => true,
            ]));
    }
}
