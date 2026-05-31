<?php

namespace App\Http\Controllers;

use App\Services\ViabilityService;
use Illuminate\View\View;

class LandingController extends Controller
{
    public function index(): View
    {
        return view('welcome');
    }

    public function costs(ViabilityService $viabilityService): View
    {
        return view('landing.costs', [
            'newDefaults' => $viabilityService->getNewDefaults(),
        ]);
    }

    public function privacy(): View
    {
        return view('landing.privacy');
    }
}
