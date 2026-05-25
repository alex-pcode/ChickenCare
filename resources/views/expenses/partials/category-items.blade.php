@php
    $selectedCategory = $selectedCategory ?? null;
    $total = $items->total();
@endphp
<div class="expenses__category-items">
    @if($selectedCategory === null)
        <div class="expenses__category-items-empty">
            {{ __('expenses.category_items.empty_selection') }}
        </div>
    @else
        <div class="expenses__category-items-header">
            <h3 class="expenses__summary-title">{{ __('expenses.category_items.title', ['category' => $categoryLabel]) }}</h3>
            <p class="expenses__summary-subtitle">{{ trans_choice('expenses.category_items.subtitle', $total, ['count' => $total]) }}</p>
        </div>

        @if($total === 0)
            <div class="expenses__category-items-empty">
                {{ __('expenses.category_items.empty') }}
            </div>
        @else
            <ul class="expenses__category-items-list">
                @foreach($items as $item)
                    <li class="expenses__category-items-row">
                        <div class="expenses__category-items-body">
                            <div class="expenses__category-items-description">{{ $item->description }}</div>
                            <div class="expenses__category-items-date">{{ $item->date->translatedFormat('d. M Y.') }}</div>
                        </div>
                        <div class="expenses__category-items-amount">@usd($item->amount)</div>
                    </li>
                @endforeach
            </ul>

            @if($items->hasPages())
                <nav class="pagination" aria-label="{{ __('expenses.category_items.pagination_aria_label') }}">
                    @if($items->onFirstPage())
                        <span class="pagination__link pagination__link--disabled">{{ __('pagination.previous') }}</span>
                    @else
                        <a href="#" class="pagination__link"
                           hx-get="{{ $items->previousPageUrl() }}"
                           hx-target="#category-items-panel"
                           hx-swap="innerHTML">{{ __('pagination.previous') }}</a>
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
                        <span class="pagination__link pagination__link--disabled">{{ __('pagination.next') }}</span>
                    @else
                        <a href="#" class="pagination__link"
                           hx-get="{{ $items->nextPageUrl() }}"
                           hx-target="#category-items-panel"
                           hx-swap="innerHTML">{{ __('pagination.next') }}</a>
                    @endif
                </nav>
            @endif
        @endif
    @endif
</div>
