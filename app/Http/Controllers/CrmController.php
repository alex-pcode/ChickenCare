<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\Sale;
use App\Services\CrmReportsService;
use App\Traits\HandlesHtmx;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CrmController extends Controller
{
    use HandlesHtmx;

    public function index(Request $request, CrmReportsService $reportsService): View
    {
        $tab = $request->query('tab', 'quick-sale');

        $allowedTabs = ['quick-sale', 'customers', 'reports'];
        if (! in_array($tab, $allowedTabs, true)) {
            $tab = 'quick-sale';
        }

        $data = $this->loadTabData($request, $tab, $reportsService);
        $data['activeTab'] = $tab;

        if ($this->isHtmx($request) && $request->header('HX-Target') === 'crm-tab-content') {
            return view("crm.partials.tab-{$tab}", $data);
        }

        return view('crm.index', $data);
    }

    private function loadTabData(Request $request, string $tab, CrmReportsService $reportsService): array
    {
        $user = $request->user();

        return match ($tab) {
            'quick-sale' => [
                'customers' => $user->customers()->active()->orderBy('name')->get(),
            ],
            'customers' => $this->loadCustomersTabData($request),
            'reports' => $this->loadReportsTabData($request, $reportsService),
            default => [],
        };
    }

    private function loadCustomersTabData(Request $request): array
    {
        $user = $request->user();
        $sort = $request->query('sort', 'created_at');
        $dir = $request->query('dir', 'desc');

        $allowedSorts = ['name', 'created_at'];
        if (! in_array($sort, $allowedSorts, true)) {
            $sort = 'created_at';
        }

        $dir = strtolower($dir) === 'asc' ? 'asc' : 'desc';

        $customers = $user->customers()
            ->active()
            ->orderBy($sort, $dir)
            ->get();

        return [
            'customers' => $customers,
            'sort' => $sort,
            'dir' => $dir,
        ];
    }

    private function loadReportsTabData(Request $request, CrmReportsService $reportsService): array
    {
        $user = $request->user();
        $view = $request->query('view', 'overview');
        $period = $request->query('period', 'month');
        $from = $request->query('from');
        $to = $request->query('to');
        $customerId = $request->query('customer_id');

        $customers = $user->customers()->active()->orderBy('name')->get();

        $data = [
            'reportView' => $view,
            'period' => $period,
            'from' => $from,
            'to' => $to,
            'customers' => $customers,
            'customerId' => $customerId,
        ];

        if ($view === 'customer' && $customerId) {
            $data['customerReport'] = $reportsService->perCustomer($user, (int) $customerId);
        } elseif ($view === 'overview' || $view !== 'customer') {
            $data['revenueOverview'] = $reportsService->revenueOverview($user, $period, $from, $to);
            $data['customerAnalytics'] = $reportsService->customerAnalytics($user);
            $data['productionPipeline'] = $reportsService->productionPipeline($user);
            $data['revenueTrend'] = $reportsService->revenueTrend($user);
            $data['recentSales'] = $reportsService->recentSales($user);
        }

        return $data;
    }
}
