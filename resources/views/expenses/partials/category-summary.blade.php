@php
    $selectedCategory = $selectedCategory ?? null;
@endphp
<div class="glass-card">
    <div class="expenses__summary-header">
        <h3 class="expenses__summary-title">Category Summary</h3>
        <div class="expenses__summary-total">
            <div class="expenses__summary-total-amount">@usd($stats['grandTotal'])</div>
            <div class="expenses__summary-total-label">Total</div>
        </div>
    </div>
    <p class="expenses__summary-subtitle">Click a category to see its biggest line items</p>

    @empty(collect($stats['breakdown'])->filter(fn($cat) => $cat['total'] > 0))
        <div class="expenses__summary-empty">
            <p>No expenses recorded yet</p>
            <p class="expenses__summary-percentage" style="margin-top: 0.25rem;">Add your first expense above to see the breakdown</p>
        </div>
    @else
        <div class="expenses__summary-rows"
             role="tablist"
             aria-label="Expense categories"
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
                                {{ number_format($cat['percentage'], 1) }}% of total
                            </div>
                        </div>
                    </div>
                    <div class="expenses__summary-row-right">
                        <div class="expenses__summary-amount">@usd($cat['total'])</div>
                        <div class="expenses__summary-count">
                            {{ $cat['transactionCount'] }} transaction{{ $cat['transactionCount'] !== 1 ? 's' : '' }}
                        </div>
                    </div>
                </a>
            @endforeach
        </div>
    @endempty
</div>
