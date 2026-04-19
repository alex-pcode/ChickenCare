<div id="expense-pie-chart" class="expenses__chart-wrapper" data-expense-stats='{{ json_encode($stats) }}'>
    <div style="margin-bottom: 0.5rem;">
        <h3 class="expenses__summary-title">Expense Breakdown</h3>
        <p class="expenses__summary-subtitle" style="margin-bottom: 0;">Monthly expenses by category</p>
    </div>
    @empty($stats['breakdown'])
        <div class="expenses__summary-empty">
            <p>No expenses recorded yet</p>
            <p class="expenses__summary-percentage" style="margin-top: 0.25rem;">Add your first expense above to see the breakdown</p>
        </div>
    @else
        <div class="expenses__chart-loading" x-show="typeof $data !== 'undefined' && $data.loading" x-cloak>
            <div class="expenses__chart-loading">
                <svg style="width: 2rem; height: 2rem; color: #6366f1;" class="animate-spin" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                    <circle style="opacity: 0.25;" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path style="opacity: 0.75;" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
            </div>
        </div>
        <canvas id="expense-breakdown-canvas" height="320"></canvas>
    @endempty
</div>
