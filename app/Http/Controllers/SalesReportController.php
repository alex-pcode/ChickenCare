<?php

namespace App\Http\Controllers;

use App\Services\ReportService;
use App\Traits\HandlesHtmx;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SalesReportController extends Controller
{
    use HandlesHtmx;

    public function index(Request $request, ReportService $reportService): View
    {
        $from = $this->parseDate($request->query('from'));
        $to = $this->parseDate($request->query('to'));

        $report = $reportService->getSalesReport($request->user(), $from, $to);

        if ($this->isHtmx($request) && !$request->hasHeader('HX-Boosted')) {
            return view('sales.partials.report-results', compact('report'));
        }

        return view('sales.reports', compact('report'));
    }

    private function parseDate(?string $value): ?Carbon
    {
        if ($value === null || $value === '') {
            return null;
        }

        try {
            return Carbon::parse($value);
        } catch (\Exception) {
            return null;
        }
    }
}
