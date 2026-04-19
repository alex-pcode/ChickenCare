@php
    $sort = $sort ?? 'date';
    $dir = $dir ?? 'desc';

    $columns = [
        'date' => 'Date',
        'category' => 'Category',
        'description' => 'Description',
        'amount' => 'Amount',
    ];
@endphp

<div id="records-table">
    @if($expenses->isEmpty())
        <x-ui.empty-state
            title="No expenses found"
            description="No expenses match the selected filter."
            icon="💰"
        />
    @else
        <div class="data-table-wrapper">
            <table class="data-table data-table--striped">
                <thead class="data-table__head">
                    <tr>
                        @foreach($columns as $col => $label)
                            @php
                                $isActive = $sort === $col;
                                $nextDir = ($isActive && $dir === 'asc') ? 'desc' : 'asc';
                                $arrow = $isActive ? ($dir === 'asc' ? ' ↑' : ' ↓') : '';
                            @endphp
                            <th scope="col" class="data-table__header">
                                <a href="#"
                                   class="expenses__sort-link {{ $isActive ? 'expenses__sort-link--active' : '' }}"
                                   hx-get="{{ route('app.expenses.index', array_merge(request()->only('category', 'page'), ['sort' => $col, 'dir' => $nextDir])) }}"
                                   hx-target="#records-table"
                                   hx-swap="outerHTML"
                                   hx-push-url="true"
                                   aria-label="Sort by {{ $label }}"
                                   aria-sort="{{ $isActive ? ($dir === 'asc' ? 'ascending' : 'descending') : 'none' }}"
                                >{{ $label }}{{ $arrow }}</a>
                            </th>
                        @endforeach
                        <th scope="col" class="data-table__header">Actions</th>
                    </tr>
                </thead>
                <tbody id="expense-entries-body" class="data-table__body">
                    @foreach($expenses as $expense)
                        @include('expenses.partials.entry-row', ['expense' => $expense])
                    @endforeach
                </tbody>
            </table>
        </div>

        @if($expenses->hasPages())
            <nav class="pagination" aria-label="Expense pagination">
                {{-- Previous --}}
                @if($expenses->onFirstPage())
                    <span class="pagination__link pagination__link--disabled">Previous</span>
                @else
                    <a href="#" class="pagination__link"
                       hx-get="{{ $expenses->appends(request()->only('sort', 'dir', 'category'))->previousPageUrl() }}"
                       hx-target="#records-table"
                       hx-swap="outerHTML"
                       hx-push-url="true"
                    >Previous</a>
                @endif

                {{-- Page Numbers --}}
                @foreach($expenses->getUrlRange(1, $expenses->lastPage()) as $page => $url)
                    @if($page == $expenses->currentPage())
                        <span class="pagination__link pagination__link--active">{{ $page }}</span>
                    @else
                        <a href="#" class="pagination__link"
                           hx-get="{{ $url }}&sort={{ $sort }}&dir={{ $dir }}{{ request('category') ? '&category=' . request('category') : '' }}"
                           hx-target="#records-table"
                           hx-swap="outerHTML"
                           hx-push-url="true"
                        >{{ $page }}</a>
                    @endif
                @endforeach

                {{-- Next --}}
                @if(!$expenses->hasMorePages())
                    <span class="pagination__link pagination__link--disabled">Next</span>
                @else
                    <a href="#" class="pagination__link"
                       hx-get="{{ $expenses->appends(request()->only('sort', 'dir', 'category'))->nextPageUrl() }}"
                       hx-target="#records-table"
                       hx-swap="outerHTML"
                       hx-push-url="true"
                    >Next</a>
                @endif
            </nav>
        @endif
    @endif
</div>
