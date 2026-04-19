@php
    $selectedCategory = $selectedCategory ?? null;
    $total = $items->total();
@endphp
<div class="expenses__category-items">
    @if($selectedCategory === null)
        <div class="expenses__category-items-empty">
            Select a category to see its biggest line items.
        </div>
    @else
        <div class="expenses__category-items-header">
            <h3 class="expenses__summary-title">Top {{ $categoryLabel }} Expenses</h3>
            <p class="expenses__summary-subtitle">{{ $total }} {{ Str::plural('item', $total) }}, sorted by amount</p>
        </div>

        @if($total === 0)
            <div class="expenses__category-items-empty">
                No expenses in this category yet.
            </div>
        @else
            <ul class="expenses__category-items-list">
                @foreach($items as $item)
                    <li class="expenses__category-items-row">
                        <div class="expenses__category-items-body">
                            <div class="expenses__category-items-description">{{ $item->description }}</div>
                            <div class="expenses__category-items-date">{{ $item->date->format('M j, Y') }}</div>
                        </div>
                        <div class="expenses__category-items-amount">@usd($item->amount)</div>
                    </li>
                @endforeach
            </ul>

            @if($items->hasPages())
                <nav class="pagination" aria-label="Category items pagination">
                    @if($items->onFirstPage())
                        <span class="pagination__link pagination__link--disabled">Previous</span>
                    @else
                        <a href="#" class="pagination__link"
                           hx-get="{{ $items->previousPageUrl() }}"
                           hx-target="#category-items-panel"
                           hx-swap="innerHTML">Previous</a>
                    @endif

                    @foreach($items->getUrlRange(1, $items->lastPage()) as $page => $url)
                        @if($page == $items->currentPage())
                            <span class="pagination__link pagination__link--active">{{ $page }}</span>
                        @else
                            <a href="#" class="pagination__link"
                               hx-get="{{ $url }}"
                               hx-target="#category-items-panel"
                               hx-swap="innerHTML">{{ $page }}</a>
                        @endif
                    @endforeach

                    @if(!$items->hasMorePages())
                        <span class="pagination__link pagination__link--disabled">Next</span>
                    @else
                        <a href="#" class="pagination__link"
                           hx-get="{{ $items->nextPageUrl() }}"
                           hx-target="#category-items-panel"
                           hx-swap="innerHTML">Next</a>
                    @endif
                </nav>
            @endif
        @endif
    @endif
</div>
