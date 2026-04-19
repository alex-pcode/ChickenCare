<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreExpenseRequest;
use App\Http\Requests\UpdateExpenseRequest;
use App\Models\Expense;
use App\Services\ExpenseStatsService;
use App\Traits\HandlesHtmx;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Pagination\LengthAwarePaginator;

class ExpenseController extends Controller
{
    use HandlesHtmx;

    public function index(Request $request): \Illuminate\Contracts\View\View
    {
        $allowedSort = ['date', 'category', 'description', 'amount'];
        $sort = in_array($request->query('sort'), $allowedSort, true) ? $request->query('sort') : 'date';
        $dir = $request->query('dir') === 'asc' ? 'asc' : 'desc';
        $currentCategory = $request->query('category');

        $query = $request->user()->expenses()->orderBy($sort, $dir)->orderBy('id', 'desc');

        if ($request->filled('category')) {
            $query->where('category', $currentCategory);
        }

        $expenses = $query->paginate(5)->withQueryString();

        $stats = app(ExpenseStatsService::class)->for($request->user())->payload();

        if ($this->isHtmx($request) && !$request->hasHeader('HX-Boosted')) {
            return view('expenses.partials.records-table', [
                'expenses' => $expenses,
                'sort' => $sort,
                'dir' => $dir,
            ]);
        }

        return view('expenses.index', [
            'expenses' => $expenses,
            'stats' => $stats,
            'currentCategory' => $currentCategory,
            'sort' => $sort,
            'dir' => $dir,
        ]);
    }

    public function stats(Request $request): \Illuminate\Http\JsonResponse
    {
        return response()->json(
            app(ExpenseStatsService::class)->for($request->user())->payload()
        );
    }

    public function store(StoreExpenseRequest $request)
    {
        $expense = $request->user()->expenses()->create($request->validated());

        if ($this->isHtmx($request)) {
            return response()
                ->view('expenses.partials.entry-row', compact('expense'))
                ->header('HX-Trigger', 'expenses:changed');
        }

        return redirect()->route('app.expenses.index')
            ->with('success', 'Expense recorded.');
    }

    public function show(Request $request, Expense $expense)
    {
        Gate::authorize('view', $expense);

        return view('expenses.partials.entry-row', compact('expense'));
    }

    public function editForm(Request $request, Expense $expense)
    {
        Gate::authorize('update', $expense);

        return view('expenses.partials.edit-form', compact('expense'));
    }

    public function update(UpdateExpenseRequest $request, Expense $expense)
    {
        Gate::authorize('update', $expense);
        $expense->update($request->validated());

        if ($this->isHtmx($request)) {
            return view('expenses.partials.entry-row', compact('expense'));
        }

        return redirect()->route('app.expenses.index')
            ->with('success', 'Expense updated.');
    }

    public function destroy(Request $request, Expense $expense)
    {
        Gate::authorize('delete', $expense);
        $expense->delete();

        if ($this->isHtmx($request)) {
            return response('', 200)->header('HX-Trigger', 'expenses:changed');
        }

        return redirect()->route('app.expenses.index')
            ->with('success', 'Expense deleted.');
    }
}
