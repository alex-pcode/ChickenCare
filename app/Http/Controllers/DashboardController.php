<?php

namespace App\Http\Controllers;

use App\Services\DashboardService;
use App\Services\SetupProgressService;
use App\Traits\HandlesHtmx;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    use HandlesHtmx;

    public function index(Request $request, DashboardService $dashboardService, SetupProgressService $setupProgressService): View
    {
        $user = $request->user();
        $summary = $dashboardService->getSummary($user);

        if ($this->isHtmx($request) && $request->header('HX-Target') === 'dashboard-activity') {
            return view('dashboard.partials.recent-activity', [
                'recentActivity' => $summary['recent_activity'],
            ]);
        }

        $eggChartData = $dashboardService->getEggChartData($user);
        $productionMetrics = $dashboardService->getProductionMetrics($user);
        $productionChartData = $dashboardService->getThirtyDayProductionChart($user);
        $financialOverview = $user->isPremium() ? $dashboardService->getFinancialOverview($user) : [];
        $revenueTrendData = $user->isPremium() ? $dashboardService->getWeeklyRevenueTrend($user) : [];

        $displayName = $user->name ?: explode('@', $user->email)[0] ?: 'User';
        $progress = $setupProgressService->compute($user);

        return view('dashboard.index', compact('summary', 'eggChartData', 'displayName', 'progress', 'productionMetrics', 'productionChartData', 'financialOverview', 'revenueTrendData'));
    }

    public function data(Request $request, DashboardService $dashboardService, SetupProgressService $setupProgressService): JsonResponse
    {
        $user = $request->user();
        $section = $request->query('section', 'all');
        $data = [];

        if (in_array($section, ['production', 'all'])) {
            $data['production'] = $dashboardService->getProductionMetrics($user);
        }

        if (in_array($section, ['financial', 'all']) && $user->isPremium()) {
            $data['financial'] = $dashboardService->getFinancialOverview($user);
        }

        if (in_array($section, ['analytics', 'all']) && $user->isPremium()) {
            $data['analytics'] = $dashboardService->getWeeklyRevenueTrend($user);
        }

        if (in_array($section, ['onboarding', 'all'])) {
            $data['onboarding'] = $setupProgressService->compute($user);
        }

        return response()->json($data);
    }
}
