<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreSaleRequest;
use App\Http\Requests\UpdateSaleRequest;
use App\Models\Sale;
use App\Traits\HandlesHtmx;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class SaleController extends Controller
{
    use HandlesHtmx;

    public function index(Request $request): mixed
    {
        $sales = $request->user()->sales()
            ->with('customer')
            ->orderBy('sale_date', 'desc')
            ->paginate(15);

        if ($this->isHtmx($request) && $request->has('page')) {
            return view('sales.partials.table', compact('sales'));
        }

        $customers = $request->user()->customers()
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        return view('sales.index', compact('sales', 'customers'));
    }

    public function store(StoreSaleRequest $request): mixed
    {
        $sale = $request->user()->sales()->create($request->validated());
        $sale->load('customer');

        if ($this->isHtmx($request)) {
            return view('sales.partials.entry-row', compact('sale'));
        }

        return redirect()->route('app.sales.index')->with('success', 'Sale recorded.');
    }

    public function show(Request $request, Sale $sale): mixed
    {
        Gate::authorize('view', $sale);

        return view('sales.partials.entry-row', compact('sale'));
    }

    public function editForm(Request $request, Sale $sale): mixed
    {
        Gate::authorize('update', $sale);

        $customers = $request->user()->customers()
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        return view('sales.partials.edit-form', compact('sale', 'customers'));
    }

    public function update(UpdateSaleRequest $request, Sale $sale): mixed
    {
        Gate::authorize('update', $sale);

        $sale->update($request->validated());
        $sale->load('customer');

        if ($this->isHtmx($request)) {
            return view('sales.partials.entry-row', compact('sale'));
        }

        return redirect()->route('app.sales.index')->with('success', 'Sale updated.');
    }

    public function destroy(Request $request, Sale $sale): mixed
    {
        Gate::authorize('delete', $sale);

        $sale->delete();

        if ($this->isHtmx($request)) {
            return response('', 200);
        }

        return redirect()->route('app.sales.index')->with('success', 'Sale deleted.');
    }

    public function togglePayment(Request $request, Sale $sale): mixed
    {
        Gate::authorize('update', $sale);

        $sale->update(['paid' => ! $sale->paid]);
        $sale->load('customer');

        return view('sales.partials.entry-row', compact('sale'));
    }
}
