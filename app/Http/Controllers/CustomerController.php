<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreCustomerRequest;
use App\Http\Requests\UpdateCustomerRequest;
use App\Models\Customer;
use App\Services\CrmReportsService;
use App\Traits\HandlesHtmx;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class CustomerController extends Controller
{
    use HandlesHtmx;

    public function index(Request $request)
    {
        $query = $request->user()->customers();

        $status = $request->input('status', 'active');
        if ($status === 'active') {
            $query->active();
        } elseif ($status === 'inactive') {
            $query->where('is_active', false);
        }

        if ($request->filled('search')) {
            $query->search($request->search);
        }

        $customers = $query->orderBy('name')->get();

        if ($this->isHtmx($request) && ! $request->hasHeader('HX-Boosted')) {
            return view('customers.partials.table', compact('customers'));
        }

        return view('customers.index', [
            'customers' => $customers,
            'currentStatus' => $status,
            'search' => $request->search,
        ]);
    }

    public function store(StoreCustomerRequest $request)
    {
        $customer = $request->user()->customers()->create($request->validated());

        app(CrmReportsService::class)->clearCacheForUser($request->user());

        if ($this->isHtmx($request)) {
            return response()
                ->view('customers.partials.entry-row', compact('customer'))
                ->header('HX-Trigger', 'crm:changed');
        }

        return redirect()->route('app.customers.index')
            ->with('success', 'Customer added.');
    }

    public function show(Request $request, Customer $customer)
    {
        Gate::authorize('view', $customer);

        return view('customers.partials.entry-row', compact('customer'));
    }

    public function editForm(Request $request, Customer $customer)
    {
        Gate::authorize('update', $customer);

        return view('customers.partials.edit-form', compact('customer'));
    }

    public function update(UpdateCustomerRequest $request, Customer $customer)
    {
        Gate::authorize('update', $customer);
        $customer->update($request->validated());

        app(CrmReportsService::class)->clearCacheForUser($request->user());

        if ($this->isHtmx($request)) {
            return response()
                ->view('customers.partials.entry-row', compact('customer'))
                ->header('HX-Trigger', 'crm:changed');
        }

        return redirect()->route('app.customers.index')
            ->with('success', 'Customer updated.');
    }

    public function destroy(Request $request, Customer $customer)
    {
        Gate::authorize('delete', $customer);
        $customer->update(['is_active' => false]);

        app(CrmReportsService::class)->clearCacheForUser($request->user());

        if ($this->isHtmx($request)) {
            return response('', 200)->header('HX-Trigger', 'crm:changed');
        }

        return redirect()->route('app.customers.index')
            ->with('success', 'Customer deactivated.');
    }
}
