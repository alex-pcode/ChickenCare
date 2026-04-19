<?php

namespace App\Http\Controllers;

use App\Http\Requests\SavingsFilterRequest;
use App\Services\SavingsAnalysisService;
use App\Support\SavingsPeriod;
use App\Traits\HandlesHtmx;
use Illuminate\View\View;

class SavingsController extends Controller
{
    use HandlesHtmx;

    public function index(SavingsFilterRequest $request, SavingsAnalysisService $service): View
    {
        $user = $request->user();
        $period = SavingsPeriod::fromRequest(
            $request->validated('period', 'month'),
            $request->validated('from'),
            $request->validated('to'),
        );

        $summary = $service->financialSummary($user, $period);
        $analysis = $service->costAnalysis($user, $period, $summary['eggPrice']);

        if ($this->isHtmx($request) && !$request->hasHeader('HX-Boosted')) {
            return view('savings.partials.financial-summary', compact('summary', 'period', 'analysis'));
        }

        $lifetime = $service->lifetimeImpact($user);

        return view('savings.index', compact('summary', 'period', 'lifetime', 'analysis', 'user'));
    }
}
