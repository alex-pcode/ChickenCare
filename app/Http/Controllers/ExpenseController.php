<?php

namespace App\Http\Controllers;

use App\Enums\ExpenseCategory;
use App\Http\Requests\StoreExpenseRequest;
use App\Http\Requests\UpdateExpenseRequest;
use App\Models\Expense;
use App\Models\User;
use App\Services\ExpenseStatsService;
use App\Traits\HandlesHtmx;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Gate;

class ExpenseController extends Controller
{
    use HandlesHtmx;

    public function index(Request $request): View
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

        $costWindow = $this->resolveCostWindow($request->query('cost_window'));
        $stats['costPerEgg'] = $this->computeCostPerEgg($request->user(), $costWindow);
        $stats['costWindowMonths'] = $costWindow;

        $topCategory = collect($stats['breakdown'])
            ->firstWhere(fn ($c) => $c['total'] > 0)['value'] ?? null;
        $categoryItems = $this->topCategoryItems($request->user(), $topCategory);

        if ($this->isHtmx($request) && ! $request->hasHeader('HX-Boosted')) {
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
            'selectedCategory' => $topCategory,
            'categoryLabel' => $topCategory ? ExpenseCategory::from($topCategory)->label() : null,
            'categoryItems' => $categoryItems,
        ]);
    }

    public function stats(Request $request): JsonResponse
    {
        return response()->json(
            app(ExpenseStatsService::class)->for($request->user())->payload()
        );
    }

    public function costPerEgg(Request $request): View
    {
        $window = $this->resolveCostWindow($request->query('window'));

        return view('expenses.partials.cost-per-egg-card', [
            'costPerEgg' => $this->computeCostPerEgg($request->user(), $window),
            'costWindowMonths' => $window,
        ]);
    }

    public function categoryItems(Request $request): View
    {
        $category = $this->resolveCategory($request->query('category'));

        return view('expenses.partials.category-items', [
            'selectedCategory' => $category,
            'categoryLabel' => $category ? ExpenseCategory::from($category)->label() : null,
            'items' => $this->topCategoryItems($request->user(), $category),
        ]);
    }

    private function resolveCategory(mixed $value): ?string
    {
        if (! is_string($value) || $value === '') {
            return null;
        }

        $valid = array_map(fn ($c) => $c->value, ExpenseCategory::cases());

        return in_array($value, $valid, true) ? $value : null;
    }

    private function topCategoryItems(User $user, ?string $category, int $perPage = 5): \Illuminate\Contracts\Pagination\LengthAwarePaginator
    {
        if ($category === null) {
            return new LengthAwarePaginator([], 0, $perPage);
        }

        return $user->expenses()
            ->where('category', $category)
            ->orderByDesc('amount')
            ->orderByDesc('date')
            ->paginate($perPage, ['*'], 'page')
            ->withPath(route('app.expenses.category-items'))
            ->appends(['category' => $category]);
    }

    private function resolveCostWindow(mixed $value): int
    {
        $int = (int) $value;

        return in_array($int, [3, 6, 12], true) ? $int : 12;
    }

    /**
     * @return array{
     *     current: array{perEgg: float|null, totalEggs: int, totalExpenses: float},
     *     previous: array{perEgg: float|null, totalEggs: int, totalExpenses: float}
     * }
     */
    private function computeCostPerEgg(User $user, int $windowMonths): array
    {
        $now = now();
        $currentFrom = $now->copy()->subMonths($windowMonths)->startOfDay();
        $previousFrom = $now->copy()->subMonths($windowMonths * 2)->startOfDay();

        return [
            'current' => $this->costPerEggFor($user, $currentFrom->toDateString(), $now->toDateString()),
            'previous' => $this->costPerEggFor($user, $previousFrom->toDateString(), $currentFrom->copy()->subDay()->toDateString()),
        ];
    }

    /**
     * @return array{perEgg: float|null, totalEggs: int, totalExpenses: float}
     */
    private function costPerEggFor(User $user, string $from, string $to): array
    {
        $totalEggs = (int) $user->eggEntries()
            ->whereBetween('date', [$from, $to])
            ->sum('count');

        $totalExpenses = (float) $user->expenses()
            ->whereBetween('date', [$from, $to])
            ->sum('amount');

        return [
            'perEgg' => $totalEggs > 0 ? round($totalExpenses / $totalEggs, 3) : null,
            'totalEggs' => $totalEggs,
            'totalExpenses' => $totalExpenses,
        ];
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
            ->with('success', __('expenses.messages.recorded'));
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
            ->with('success', __('expenses.messages.updated'));
    }

    public function destroy(Request $request, Expense $expense)
    {
        Gate::authorize('delete', $expense);
        $expense->delete();

        if ($this->isHtmx($request)) {
            return response('', 200)->header('HX-Trigger', json_encode([
                'closeModal' => true,
                'expenses:changed' => true,
                'toast:success' => __('expenses.messages.deleted'),
            ]));
        }

        return redirect()->route('app.expenses.index')
            ->with('success', __('expenses.messages.deleted'));
    }

    public function deleteConfirm(Request $request, Expense $expense)
    {
        Gate::authorize('delete', $expense);

        return view('expenses.partials.delete-confirm-modal', compact('expense'));
    }
}
