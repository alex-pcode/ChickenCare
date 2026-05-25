<?php

namespace App\Http\Controllers;

use App\Http\Requests\SavingsPreferencesRequest;
use App\Services\SavingsAnalysisService;
use App\Support\SavingsPeriod;
use App\Traits\HandlesHtmx;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class SavingsPreferencesController extends Controller
{
    use HandlesHtmx;

    public function update(SavingsPreferencesRequest $request, SavingsAnalysisService $service): RedirectResponse|View
    {
        $user = $request->user();
        $user->update($request->validated());

        if ($this->isHtmx($request)) {
            $period = SavingsPeriod::fromRequest('month');
            $summary = $service->financialSummary($user, $period);
            $analysis = $service->costAnalysis($user, $period, $summary['eggPrice']);

            return view('savings.partials.financial-summary', compact('summary', 'period', 'analysis'));
        }

        return redirect()->back()->with('success', __('savings.messages.preferences_updated'));
    }
}
