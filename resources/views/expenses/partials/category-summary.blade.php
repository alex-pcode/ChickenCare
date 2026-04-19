<div class="glass-card">
    <div class="expenses__summary-header">
        <h3 class="expenses__summary-title">Category Summary</h3>
        <div class="expenses__summary-total">
            <div class="expenses__summary-total-amount">@usd($stats['grandTotal'])</div>
            <div class="expenses__summary-total-label">Total</div>
        </div>
    </div>
    <p class="expenses__summary-subtitle">Detailed breakdown of expenses by category</p>

    @empty(collect($stats['breakdown'])->filter(fn($cat) => $cat['total'] > 0))
        <div class="expenses__summary-empty">
            <p>No expenses recorded yet</p>
            <p class="expenses__summary-percentage" style="margin-top: 0.25rem;">Add your first expense above to see the breakdown</p>
        </div>
    @else
        <div class="expenses__summary-rows">
            @foreach(collect($stats['breakdown'])->filter(fn($cat) => $cat['total'] > 0) as $cat)
                <div class="expenses__summary-row">
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
                </div>
            @endforeach
        </div>
    @endempty
</div>
