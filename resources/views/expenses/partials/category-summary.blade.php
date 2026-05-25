@php
    $selectedCategory = $selectedCategory ?? null;
@endphp
<div class="glass-card">
    <div class="expenses__summary-header">
        <h3 class="expenses__summary-title">{{ __('expenses.summary.title') }}</h3>
        <div class="expenses__summary-total">
            <div class="expenses__summary-total-amount">@usd($stats['grandTotal'])</div>
            <div class="expenses__summary-total-label">{{ __('expenses.summary.total') }}</div>
        </div>
    </div>
    <p class="expenses__summary-subtitle">{{ __('expenses.summary.subtitle') }}</p>

    @empty(collect($stats['breakdown'])->filter(fn($cat) => $cat['total'] > 0))
        <div class="expenses__summary-empty">
            <p>{{ __('expenses.summary.empty_title') }}</p>
            <p class="expenses__summary-percentage" style="margin-top: 0.25rem;">{{ __('expenses.summary.empty_description') }}</p>
        </div>
    @else
        <div class="expenses__summary-rows"
             role="tablist"
             aria-label="{{ __('expenses.summary.categories_aria_label') }}"
             x-data="{ selected: @js($selectedCategory) }">
            @foreach(collect($stats['breakdown'])->filter(fn($cat) => $cat['total'] > 0) as $cat)
                <a href="#"
                   role="tab"
                   :aria-selected="selected === @js($cat['value']) ? 'true' : 'false'"
                   class="expenses__summary-row"
                   :class="{ 'expenses__summary-row--active': selected === @js($cat['value']) }"
                   @click="selected = @js($cat['value'])"
                   hx-get="{{ route('app.expenses.category-items', ['category' => $cat['value']]) }}"
                   hx-target="#category-items-panel"
                   hx-swap="innerHTML">
                    <div class="expenses__summary-row-left">
                        <div class="expenses__summary-dot"
                             style="background-color: {{ $cat['color'] }};"></div>
                        <div>
                            <div class="expenses__summary-category">{{ $cat['name'] }}</div>
                            <div class="expenses__summary-percentage">
                                {{ __('expenses.summary.percentage_of_total', ['percentage' => number_format($cat['percentage'], 1)]) }}
                            </div>
                        </div>
                    </div>
                    <div class="expenses__summary-row-right">
                        <div class="expenses__summary-amount">@usd($cat['total'])</div>
                        <div class="expenses__summary-count">
                            {{ trans_choice('expenses.summary.transactions', $cat['transactionCount'], ['count' => $cat['transactionCount']]) }}
                        </div>
                    </div>
                </a>
            @endforeach
        </div>
    @endempty
</div>
