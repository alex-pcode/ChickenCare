<?php

namespace App\Http\Controllers;

use App\Services\ViabilityService;
use App\Traits\HandlesHtmx;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ViabilityController extends Controller
{
    use HandlesHtmx;

    public function index(Request $request, ViabilityService $viabilityService): View
    {
        $defaults = $viabilityService->getDefaults($request->user());
        $newDefaults = $viabilityService->getNewDefaults($request->user());
        $results = null;

        if ($request->hasAny(['birds', 'laying_rate', 'price_per_dozen'])) {
            $results = $viabilityService->calculate(
                $request->user(),
                $request->only([
                    'birds', 'laying_rate', 'price_per_dozen', 'price_per_individual',
                    'monthly_feed_cost', 'other_monthly_costs', 'cost_per_bird', 'sell_as',
                ]),
                $defaults,
            );
        }

        if ($this->isHtmx($request) && $results !== null) {
            return view('viability.partials.results', compact('results'));
        }

        return view('viability.index', compact('defaults', 'results', 'newDefaults'));
    }
}
