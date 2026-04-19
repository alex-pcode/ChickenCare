{{-- Dashboard Layout --}}
<div class="showcase-section">
    <div class="showcase-section__header">
        <h2 class="showcase-section__title">Dashboard Layout</h2>
        <p class="showcase-section__subtitle">Common dashboard arrangement with stat cards and summaries</p>
    </div>

    <div class="showcase-grid showcase-grid--4">
        <x-ui.stat-card title="Total Hens" total="50" label="in all flocks" :change="5" changeType="increase" />
        <x-ui.stat-card title="Eggs Today" total="47" label="collected" :change="12" changeType="increase" variant="corner-gradient" />
        <x-ui.stat-card title="Feed Stock" total="87 lbs" label="remaining" :change="15" changeType="decrease" variant="gradient" />
        <x-ui.stat-card title="Revenue" total="$1,247" label="this month" :change="23" changeType="increase" variant="corner-gradient" />
    </div>

    <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 1.5rem; margin-top: 1.5rem;">
        <x-ui.progress-card title="Monthly Egg Target" :value="987" :max="1200" label="Target: 1,200 eggs" variant="detailed" />
        <x-ui.summary-card
            title="Quick Overview"
            :items="[
                ['label' => 'Active Flocks', 'value' => '3'],
                ['label' => 'Avg per Hen', 'value' => '0.94'],
                ['label' => 'Best Flock', 'value' => 'Flock A'],
            ]"
            variant="compact"
        />
    </div>
</div>

{{-- Analytics Grid --}}
<div class="showcase-section">
    <div class="showcase-section__header">
        <h2 class="showcase-section__title">Analytics Grid</h2>
        <p class="showcase-section__subtitle">Metric-focused layout for data analysis views</p>
    </div>

    <div class="showcase-grid showcase-grid--4">
        <x-ui.metric-display label="Total Revenue" :value="4891.25" format="currency" />
        <x-ui.metric-display label="Avg Daily Eggs" :value="47.2" format="decimal" :precision="1" />
        <x-ui.metric-display label="Productivity" :value="94.2" format="percentage" :precision="1" color="success" />
        <x-ui.metric-display label="Feed Efficiency" :value="2.34" format="decimal" :precision="2" unit=" eggs/lb" />
    </div>

    <div class="showcase-grid showcase-grid--3" style="margin-top: 1.5rem;">
        <x-ui.comparison-card title="Eggs: This vs Last Week" :before="['value' => 287, 'label' => 'Last Week']" :after="['value' => 324, 'label' => 'This Week']" format="number" />
        <x-ui.comparison-card title="Feed Cost" :before="['value' => 89.50, 'label' => 'Last Month']" :after="['value' => 82.25, 'label' => 'This Month']" format="currency" />
        <x-ui.comparison-card title="Mortality Rate" :before="['value' => 3.2, 'label' => 'Previous']" :after="['value' => 2.1, 'label' => 'Current']" format="percentage" />
    </div>
</div>

{{-- Financial Summary --}}
<div class="showcase-section">
    <div class="showcase-section__header">
        <h2 class="showcase-section__title">Financial Summary Layout</h2>
        <p class="showcase-section__subtitle">Revenue and expense tracking layout</p>
    </div>

    <div class="showcase-grid showcase-grid--2" style="gap: 1.5rem;">
        <x-ui.stat-card title="Net Profit" total="$824" label="this month" :change="18" changeType="increase" variant="dark" />
        <x-ui.stat-card title="Total Expenses" total="$423" label="this month" :change="5" changeType="decrease" variant="dark" />
    </div>

    <div class="showcase-grid showcase-grid--2" style="gap: 1.5rem; margin-top: 1.5rem;">
        <x-ui.summary-card
            title="Revenue Breakdown"
            :items="[
                ['label' => 'Egg Sales', 'value' => '$892.00', 'color' => 'success'],
                ['label' => 'Chick Sales', 'value' => '$245.00', 'color' => 'success'],
                ['label' => 'Manure Sales', 'value' => '$110.00'],
            ]"
            variant="detailed"
            :showDividers="true"
        />
        <x-ui.summary-card
            title="Expense Breakdown"
            :items="[
                ['label' => 'Feed', 'value' => '$245.00', 'color' => 'danger'],
                ['label' => 'Veterinary', 'value' => '$85.00', 'color' => 'warning'],
                ['label' => 'Utilities', 'value' => '$93.00'],
            ]"
            variant="detailed"
            :showDividers="true"
        />
    </div>
</div>
