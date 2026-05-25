<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreFlockProfileRequest;
use App\Models\FlockProfile;
use App\Services\FlockBatchStatsService;
use App\Traits\HandlesHtmx;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class FlockProfileController extends Controller
{
    use HandlesHtmx;

    public function __construct(private readonly FlockBatchStatsService $statsService) {}

    public function index(Request $request)
    {
        $profile = $request->user()->flockProfile
            ?? $request->user()->flockProfile()->create();

        $events = $profile->flockEvents()->orderBy('date', 'asc')->get();

        $batches = $request->user()->flockBatches()->where('is_active', true)->get();
        $overviewStats = $this->statsService->overview($request->user());

        $lastRecount = $profile->flockEvents()
            ->where('type', 'recount')
            ->orderByDesc('date')
            ->first();

        $daysSinceLastRecount = $lastRecount
            ? abs((int) now()->startOfDay()->diffInDays($lastRecount->date->startOfDay()))
            : null;

        return view('flock.index', compact('profile', 'events', 'batches', 'overviewStats', 'lastRecount', 'daysSinceLastRecount'));
    }

    public function update(StoreFlockProfileRequest $request, FlockProfile $flockProfile)
    {
        Gate::authorize('update', $flockProfile);

        $flockProfile->update($request->validated());

        if ($this->isHtmx($request)) {
            $overviewStats = $this->statsService->overview($request->user());
            $batches = $request->user()->flockBatches()->where('is_active', true)->get();
            $profile = $flockProfile;

            return view('flock.partials.flock-overview', compact('profile', 'batches', 'overviewStats'));
        }

        return redirect()->route('app.flock.index')
            ->with('success', __('flock.messages.profile_updated'));
    }
}
