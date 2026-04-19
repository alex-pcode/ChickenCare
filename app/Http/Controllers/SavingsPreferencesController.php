<?php

namespace App\Http\Controllers;

use App\Http\Requests\SavingsPreferencesRequest;
use App\Traits\HandlesHtmx;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Response;

class SavingsPreferencesController extends Controller
{
    use HandlesHtmx;

    public function update(SavingsPreferencesRequest $request): RedirectResponse|Response
    {
        $request->user()->update($request->validated());

        if ($this->isHtmx($request)) {
            return $this->htmxTrigger('preferences-updated');
        }

        return redirect()->back()->with('success', 'Savings preferences updated.');
    }
}
