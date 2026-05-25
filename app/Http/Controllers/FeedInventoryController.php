<?php

namespace App\Http\Controllers;

use App\Enums\ExpenseCategory;
use App\Http\Requests\StoreFeedInventoryRequest;
use App\Http\Requests\UpdateFeedInventoryRequest;
use App\Models\FeedInventory;
use App\Services\FeedStatsService;
use App\Traits\HandlesHtmx;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;

class FeedInventoryController extends Controller
{
    use HandlesHtmx;

    public function index(Request $request)
    {
        $sort = $request->query('sort', 'opened_date');
        $dir = $request->query('dir', 'desc');

        $allowedSorts = ['brand', 'feed_type', 'quantity', 'total_cost', 'opened_date'];
        if (! in_array($sort, $allowedSorts)) {
            $sort = 'opened_date';
        }
        $dir = $dir === 'asc' ? 'asc' : 'desc';

        $feeds = $request->user()->feedInventory()
            ->orderBy($sort === 'opened_date' ? DB::raw('COALESCE(opened_date, created_at)') : $sort, $dir)
            ->paginate(5);

        if ($this->isHtmx($request) && ! $request->hasHeader('HX-Boosted')) {
            return view('feed.partials.records-table', compact('feeds', 'sort', 'dir'));
        }

        $lastFeed = $request->user()->feedInventory()
            ->orderByRaw('COALESCE(opened_date, created_at) DESC')
            ->first();

        $lastPurchaseDate = null;
        if ($lastFeed) {
            $lastPurchaseDate = $lastFeed->opened_date ?? $lastFeed->created_at->startOfDay();
        }

        $daysSinceLastPurchase = $lastPurchaseDate
            ? (int) abs(now()->startOfDay()->diffInDays($lastPurchaseDate->copy()->startOfDay()))
            : null;

        return view('feed.index', compact('feeds', 'sort', 'dir', 'lastPurchaseDate', 'daysSinceLastPurchase'));
    }

    public function store(StoreFeedInventoryRequest $request)
    {
        $feed = $request->user()->feedInventory()->create($request->validated());

        try {
            $expense = $request->user()->expenses()->create([
                'category' => ExpenseCategory::Feed->value,
                'description' => "{$feed->brand} {$feed->feed_type->label()} ({$feed->quantity} {$feed->unit})",
                'amount' => $feed->total_cost,
                'date' => $feed->opened_date ?? now()->toDateString(),
            ]);
            $feed->update(['expense_id' => $expense->id]);
        } catch (\Throwable $e) {
            Log::warning('Failed to create auto-expense for feed entry', [
                'feed_id' => $feed->id,
                'error' => $e->getMessage(),
            ]);
        }

        if ($this->isHtmx($request)) {
            return view('feed.partials.entry-row', compact('feed'));
        }

        return redirect()->route('app.feed.index')
            ->with('success', __('feed.messages.recorded'));
    }

    public function show(Request $request, FeedInventory $feed)
    {
        Gate::authorize('view', $feed);

        return view('feed.partials.entry-row', compact('feed'));
    }

    public function editForm(Request $request, FeedInventory $feed)
    {
        Gate::authorize('update', $feed);

        return view('feed.partials.edit-form', compact('feed'));
    }

    public function update(UpdateFeedInventoryRequest $request, FeedInventory $feed)
    {
        Gate::authorize('update', $feed);
        $feed->update($request->validated());

        if ($feed->expense_id) {
            $feed->expense?->update([
                'description' => "{$feed->brand} {$feed->feed_type->label()} ({$feed->quantity} {$feed->unit})",
                'amount' => $feed->total_cost,
                'date' => $feed->opened_date ?? $feed->expense->date,
            ]);
        }

        if ($this->isHtmx($request)) {
            return view('feed.partials.entry-row', compact('feed'));
        }

        return redirect()->route('app.feed.index')
            ->with('success', __('feed.messages.updated'));
    }

    public function destroy(Request $request, FeedInventory $feed)
    {
        Gate::authorize('delete', $feed);

        $feed->expense?->delete();
        $feed->delete();

        if ($this->isHtmx($request)) {
            return response('', 200)
                ->header('HX-Trigger', json_encode([
                    'closeModal' => true,
                    'toast:success' => __('feed.messages.deleted'),
                ]));
        }

        return redirect()->route('app.feed.index')
            ->with('success', __('feed.messages.deleted'));
    }

    public function deleteConfirm(Request $request, FeedInventory $feed)
    {
        Gate::authorize('delete', $feed);

        return view('feed.partials.delete-confirm-modal', compact('feed'));
    }

    public function markDepleted(Request $request, FeedInventory $feed)
    {
        Gate::authorize('update', $feed);
        $feed->markDepleted();

        if ($this->isHtmx($request)) {
            return view('feed.partials.entry-row', compact('feed'));
        }

        return redirect()->route('app.feed.index')
            ->with('success', __('feed.messages.depleted'));
    }

    public function stats(Request $request): JsonResponse
    {
        $range = $request->query('range', '6months');
        $allowedRanges = ['3months', '6months', '12months', 'all'];
        if (! in_array($range, $allowedRanges)) {
            $range = '6months';
        }

        $service = (new FeedStatsService)->for($request->user());

        return response()->json([
            ...$service->metrics($range),
            'trends' => $service->monthlyTrends($range),
        ]);
    }
}
