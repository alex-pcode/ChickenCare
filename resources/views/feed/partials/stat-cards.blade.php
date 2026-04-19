<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
    <div class="feed__stat-card">
        <div class="feed__stat-label">Monthly Cost (per bird)</div>
        <div class="feed__stat-value" x-text="'$' + stats.monthlyCostPerBird.toFixed(2) + '/bird'"></div>
    </div>
    <div class="feed__stat-card">
        <div class="feed__stat-label">Total Feed Purchased</div>
        <div class="feed__stat-value" x-text="'$' + Number(stats.totalPurchased).toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2})"></div>
    </div>
    <div class="feed__stat-card">
        <div class="feed__stat-label">Depleted Feed Cost</div>
        <div class="feed__stat-value" x-text="'$' + Number(stats.depletedCost).toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2})"></div>
    </div>
    <div class="feed__stat-card">
        <div class="feed__stat-label">Feed Cycles</div>
        <div class="feed__stat-value" x-text="stats.feedCycles"></div>
    </div>
</div>
