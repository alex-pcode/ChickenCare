<?php

namespace App\Http\Controllers;

use Illuminate\View\View;

class LandingController extends Controller
{
    public function index(): View
    {
        return view('welcome');
    }

    public function costs(): View
    {
        return view('landing.costs');
    }

    public function privacy(): View
    {
        return view('landing.privacy');
    }
}
