<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreFlockEventRequest;
use App\Models\FlockEvent;
use App\Models\FlockProfile;
use App\Traits\HandlesHtmx;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class FlockEventController extends Controller
{
    use HandlesHtmx;

    public function create(FlockProfile $flockProfile)
    {
        Gate::authorize('view', $flockProfile);

        return view('flock.partials.event-form', [
            'profile' => $flockProfile,
            'mode' => 'create',
            'flockEvent' => null,
        ]);
    }

    public function store(StoreFlockEventRequest $request, FlockProfile $flockProfile)
    {
        $flockProfile->flockEvents()->create($request->validated());

        if ($this->isHtmx($request)) {
            $events = $flockProfile->flockEvents()->orderBy('date', 'asc')->get();

            return view('flock.partials.timeline', [
                'events' => $events,
                'profile' => $flockProfile,
            ]);
        }

        return redirect()->route('app.flock.index')
            ->with('success', __('flock.messages.event_added'));
    }

    public function edit(FlockProfile $flockProfile, FlockEvent $flockEvent)
    {
        Gate::authorize('update', $flockEvent);

        return view('flock.partials.event-form', [
            'profile' => $flockProfile,
            'mode' => 'edit',
            'flockEvent' => $flockEvent,
        ]);
    }

    public function update(StoreFlockEventRequest $request, FlockProfile $flockProfile, FlockEvent $flockEvent)
    {
        Gate::authorize('update', $flockEvent);

        $flockEvent->update($request->validated());

        if ($this->isHtmx($request)) {
            $events = $flockProfile->flockEvents()->orderBy('date', 'asc')->get();

            return view('flock.partials.timeline', [
                'events' => $events,
                'profile' => $flockProfile,
            ]);
        }

        return redirect()->route('app.flock.index')
            ->with('success', __('flock.messages.event_updated'));
    }

    public function destroy(Request $request, FlockProfile $flockProfile, FlockEvent $flockEvent)
    {
        Gate::authorize('delete', $flockEvent);

        $flockEvent->delete();

        if ($this->isHtmx($request)) {
            return response('', 200);
        }

        return redirect()->route('app.flock.index')
            ->with('success', __('flock.messages.event_removed'));
    }
}
