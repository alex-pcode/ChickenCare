<?php

namespace App\Http\Controllers;

use App\Http\Requests\BackfillEggEntriesRequest;
use App\Http\Requests\StoreEggEntryRequest;
use App\Http\Requests\UpdateEggEntryRequest;
use App\Models\EggEntry;
use App\Services\EggStatsService;
use App\Traits\HandlesHtmx;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Gate;

class EggEntryController extends Controller
{
    use HandlesHtmx;

    public function index(Request $request, EggStatsService $eggStatsService)
    {
        $entries = $request->user()
            ->eggEntries()
            ->orderBy('date', 'desc')
            ->paginate(5);

        if ($this->isHtmx($request) && $request->has('page')) {
            return view('eggs.partials.table', compact('entries'));
        }

        $stats = null;
        $yearlyGoal = null;

        if ($entries->total() > 0) {
            $stats = $eggStatsService->getStats($request->user());
            $yearlyGoal = $request->user()->yearly_egg_goal;
        }

        $today = now()->startOfDay();
        $lastEntry = $request->user()
            ->eggEntries()
            ->orderBy('date', 'desc')
            ->first();
        $lastEntryDate = $lastEntry?->date;
        $loggedToday = $lastEntryDate !== null && $lastEntryDate->isSameDay($today);
        $todayTotal = $loggedToday
            ? (int) $request->user()->eggEntries()->whereDate('date', $today->toDateString())->sum('count')
            : 0;
        $daysSinceLastEntry = $lastEntryDate
            ? (int) abs($today->diffInDays($lastEntryDate->copy()->startOfDay()))
            : null;

        return view('eggs.index', compact(
            'entries',
            'stats',
            'yearlyGoal',
            'lastEntryDate',
            'loggedToday',
            'todayTotal',
            'daysSinceLastEntry'
        ));
    }

    public function skeleton(): Response
    {
        return response()->view('eggs.index', [
            'skel' => true,
            'entries' => new LengthAwarePaginator(new Collection, 0, 5),
            'stats' => null,
            'yearlyGoal' => null,
            'lastEntryDate' => null,
            'loggedToday' => false,
            'todayTotal' => 0,
            'daysSinceLastEntry' => null,
        ])->header('Cache-Control', 'private, max-age=300');
    }

    public function stats(Request $request, EggStatsService $eggStatsService)
    {
        $stats = $eggStatsService->getStats($request->user());
        $yearlyGoal = $request->user()->yearly_egg_goal;

        return view('eggs.partials.stats-refresh', compact('stats', 'yearlyGoal'));
    }

    public function store(StoreEggEntryRequest $request)
    {
        $validated = $request->validated();

        // First-ever entry: the table and stats sections aren't rendered yet, so a
        // partial swap has nothing to target. Fall back to a full page refresh.
        $wasEmpty = ! $request->user()->eggEntries()->exists();

        // Check for duplicate entry with same date + size + color
        $duplicateQuery = $request->user()
            ->eggEntries()
            ->whereDate('date', $validated['date']);

        if (isset($validated['size']) && $validated['size'] !== null) {
            $duplicateQuery->where('size', $validated['size']);
        } else {
            $duplicateQuery->whereNull('size');
        }

        if (isset($validated['color']) && $validated['color'] !== null) {
            $duplicateQuery->where('color', $validated['color']);
        } else {
            $duplicateQuery->whereNull('color');
        }

        $existing = $duplicateQuery->first();

        if ($existing && ! $request->boolean('confirm_update')) {
            if ($this->isHtmx($request)) {
                return response()
                    ->view('eggs.partials.duplicate-confirm', [
                        'existing' => $existing,
                        'formData' => $validated,
                    ])
                    ->header('HX-Retarget', '#duplicate-confirm-area')
                    ->header('HX-Reswap', 'innerHTML');
            }

            return redirect()->back()
                ->with('warning', __('eggs.messages.duplicate_warning', ['date' => $existing->date->format('M d, Y'), 'count' => $existing->count]))
                ->withInput();
        }

        if ($existing && $request->boolean('confirm_update')) {
            $existing->update([
                'count' => $validated['count'],
                'notes' => $validated['notes'] ?? $existing->notes,
            ]);

            if ($this->isHtmx($request)) {
                return response()
                    ->view('eggs.partials.entry-row', ['entry' => $existing])
                    ->header('HX-Trigger', 'eggLogged');
            }

            return redirect()->route('app.eggs.index')
                ->with('success', __('eggs.messages.existing_updated'));
        }

        $entry = $request->user()
            ->eggEntries()
            ->create($validated);

        if ($this->isHtmx($request)) {
            if ($wasEmpty) {
                return $this->htmxRedirect(route('app.eggs.index'));
            }

            return response()
                ->view('eggs.partials.entry-row', compact('entry'))
                ->header('HX-Trigger', 'eggLogged');
        }

        return redirect()->route('app.eggs.index')
            ->with('success', __('eggs.messages.recorded'));
    }

    public function show(Request $request, EggEntry $egg)
    {
        Gate::authorize('view', $egg);

        return view('eggs.partials.entry-row', ['entry' => $egg]);
    }

    public function editForm(Request $request, EggEntry $egg)
    {
        Gate::authorize('update', $egg);

        return view('eggs.partials.edit-form', ['entry' => $egg]);
    }

    public function update(UpdateEggEntryRequest $request, EggEntry $egg)
    {
        Gate::authorize('update', $egg);
        $egg->update($request->validated());

        if ($this->isHtmx($request)) {
            return view('eggs.partials.entry-row', ['entry' => $egg]);
        }

        return redirect()->route('app.eggs.index')
            ->with('success', __('eggs.messages.updated'));
    }

    public function backfillForm()
    {
        return view('eggs.partials.backfill-modal');
    }

    public function backfill(BackfillEggEntriesRequest $request)
    {
        $entries = $request->validated()['entries'];

        foreach ($entries as $entryData) {
            $request->user()->eggEntries()->create([
                'date' => $entryData['date'],
                'count' => $entryData['count'],
            ]);
        }

        if ($this->isHtmx($request)) {
            return $this->htmxRedirect(route('app.eggs.index'));
        }

        return redirect()->route('app.eggs.index')
            ->with('success', __('eggs.messages.backfilled', ['count' => count($entries)]));
    }

    public function destroy(Request $request, EggEntry $egg)
    {
        Gate::authorize('delete', $egg);
        $egg->delete();

        if ($this->isHtmx($request)) {
            return response('', 200)
                ->header('HX-Trigger', json_encode([
                    'closeModal' => true,
                    'toast:success' => __('eggs.messages.deleted'),
                ]));
        }

        return redirect()->route('app.eggs.index')
            ->with('success', __('eggs.messages.deleted'));
    }

    public function deleteConfirm(Request $request, EggEntry $egg)
    {
        Gate::authorize('delete', $egg);

        return view('eggs.partials.delete-confirm-modal', ['entry' => $egg]);
    }
}
