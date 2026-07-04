<?php

namespace App\Http\Controllers;

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

        $allowedTabs = ['quick-sale', 'customers'];
        if (! in_array($tab, $allowedTabs, true)) {
            $tab = 'quick-sale';
        }

        $data = $this->loadTabData($request, $tab);
        $data['activeTab'] = $tab;
        $data['heroStats'] = $reportsService->monthlySalesSummary($request->user());

        if ($this->isHtmx($request)) {
            $target = $request->header('HX-Target');

            if ($target === 'crm-customers-table') {
                return view('crm.partials.customers-table', $data);
            }

            if ($target === 'crm-tab-content') {
                return view("crm.partials.tab-{$tab}", $data);
            }
        }

        return view('crm.index', $data);
    }

    public function reports(Request $request, CrmReportsService $reportsService): View
    {
        $data = $this->loadReportsData($request, $reportsService);

        if ($this->isHtmx($request) && $request->header('HX-Target') === 'crm-reports-content') {
            if (($data['reportView'] ?? 'overview') === 'customer') {
                return view('crm.partials.tab-reports-customer', $data);
            }

            return view('crm.partials.tab-reports-overview', $data);
        }

        return view('crm.reports', $data);
    }

    private function loadTabData(Request $request, string $tab): array
    {
        $user = $request->user();

        return match ($tab) {
            'quick-sale' => [
                'customers' => $user->customers()->active()->orderBy('name')->get(),
            ],
            'customers' => $this->loadCustomersTabData($request),
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

    private function loadReportsData(Request $request, CrmReportsService $reportsService): array
    {
        $user = $request->user();
        $view = $request->query('view', 'overview');
        $period = $request->query('period', 'all');
        $from = $this->sanitizeDateParam($request->query('from'));
        $to = $this->sanitizeDateParam($request->query('to'));
        $customerId = $request->query('customer_id');

        $data = [
            'reportView' => $view,
            'period' => $period,
            'from' => $from,
            'to' => $to,
            'customers' => $view === 'customer'
                ? $user->customers()->active()->orderBy('name')->get()
                : collect(),
            'customerId' => $customerId,
        ];

        if ($view === 'customer' && $customerId) {
            $data['customerReport'] = $reportsService->perCustomer($user, (int) $customerId);
        } elseif ($view === 'overview' || $view !== 'customer') {
            $data['revenueOverview'] = $reportsService->revenueOverview($user, $period, $from, $to);
            $data['customerAnalytics'] = $reportsService->customerAnalytics($user, $period, $from, $to);
            $data['productionPipeline'] = $reportsService->productionPipeline($user, $period, $from, $to);
            $data['revenueTrend'] = $reportsService->revenueTrend($user, $period, $from, $to);
            $data['recentSales'] = $reportsService->recentSales($user, $period, $from, $to);
        }

        return $data;
    }

    /**
     * Only accept valid Y-m-d date strings from the query string; anything else becomes null.
     */
    private function sanitizeDateParam(mixed $value): ?string
    {
        if (! is_string($value) || preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', $value, $matches) !== 1) {
            return null;
        }

        return checkdate((int) $matches[2], (int) $matches[3], (int) $matches[1]) ? $value : null;
    }
}
