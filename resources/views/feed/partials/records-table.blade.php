@php
    $sort = $sort ?? 'opened_date';
    $dir = $dir ?? 'desc';

    $columns = [
        'brand' => 'Brand',
        'feed_type' => 'Type',
        'quantity' => 'Quantity',
        'total_cost' => 'Price',
        'opened_date' => 'Duration',
    ];
@endphp

<div id="feed-records-table">
    @if($feeds->isEmpty())
        <x-ui.empty-state
            title="No feed entries found"
            description="No feed entries match the current page."
            icon="🌾"
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
                                   class="feed__sort-link {{ $isActive ? 'feed__sort-link--active' : '' }}"
                                   hx-get="{{ route('app.feed.index', array_merge(request()->only('page'), ['sort' => $col, 'dir' => $nextDir])) }}"
                                   hx-target="#feed-records-table"
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
                <tbody id="feed-entries-body" class="data-table__body">
                    @foreach($feeds as $feed)
                        @include('feed.partials.entry-row', ['feed' => $feed])
                    @endforeach
                </tbody>
            </table>
        </div>

        @if($feeds->hasPages())
            <nav class="pagination" aria-label="Feed pagination">
                <div class="flex items-center justify-center gap-1 mt-4">
                    {{-- Previous --}}
                    @if($feeds->onFirstPage())
                        <span class="pagination__link pagination__link--disabled">Previous</span>
                    @else
                        <a href="#" class="pagination__link"
                           hx-get="{{ $feeds->appends(request()->only('sort', 'dir'))->previousPageUrl() }}"
                           hx-target="#feed-records-table"
                           hx-swap="outerHTML"
                           hx-push-url="true"
                        >Previous</a>
                    @endif

                    {{-- Page Numbers --}}
                    @foreach($feeds->getUrlRange(1, $feeds->lastPage()) as $page => $url)
                        @if($page == $feeds->currentPage())
                            <span class="pagination__link pagination__link--active">{{ $page }}</span>
                        @else
                            <a href="#" class="pagination__link"
                               hx-get="{{ $url }}&sort={{ $sort }}&dir={{ $dir }}"
                               hx-target="#feed-records-table"
                               hx-swap="outerHTML"
                               hx-push-url="true"
                            >{{ $page }}</a>
                        @endif
                    @endforeach

                    {{-- Next --}}
                    @if(!$feeds->hasMorePages())
                        <span class="pagination__link pagination__link--disabled">Next</span>
                    @else
                        <a href="#" class="pagination__link"
                           hx-get="{{ $feeds->appends(request()->only('sort', 'dir'))->nextPageUrl() }}"
                           hx-target="#feed-records-table"
                           hx-swap="outerHTML"
                           hx-push-url="true"
                        >Next</a>
                    @endif
                </div>
            </nav>
        @endif
    @endif
</div>
