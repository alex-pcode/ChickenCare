{{-- StatCard Examples --}}
<div class="showcase-section">
    <div class="showcase-section__header">
        <h2 class="showcase-section__title">StatCard Examples</h2>
        <p class="showcase-section__subtitle">Versatile cards for displaying key metrics and statistics</p>
    </div>

    <div class="showcase-grid showcase-grid--4">
        <x-ui.stat-card
            title="Daily Eggs"
            total="47"
            label="eggs collected"
            :change="12"
            changeType="increase"
        />

        <x-ui.stat-card
            title="Active Hens"
            total="45"
            label="currently laying"
            icon="🐔"
            :change="2"
            changeType="increase"
            variant="corner-gradient"
        />

        <x-ui.stat-card
            title="Feed Remaining"
            total="87"
            label="lbs left"
            :change="15"
            changeType="decrease"
            variant="compact"
        />

        <x-ui.stat-card
            title="Monthly Revenue"
            total="$1,247"
            label="from egg sales"
            :change="23"
            changeType="increase"
            variant="corner-gradient"
        />

        <x-ui.stat-card
            title="Expenses"
            total="$423"
            label="this month"
            icon="💰"
            :change="8"
            changeType="decrease"
            variant="gradient"
        />

        <x-ui.stat-card
            title="Premium Card"
            total="$2,847"
            label="premium features"
            icon="⭐"
            :change="15"
            changeType="increase"
            variant="corner-gradient"
        />
    </div>
</div>

{{-- StatCard Dark Examples --}}
<div class="showcase-section">
    <div class="showcase-section__header">
        <h2 class="showcase-section__title">StatCard Dark Examples</h2>
        <p class="showcase-section__subtitle">Dark themed cards with white text styling</p>
    </div>

    <div class="showcase-grid showcase-grid--4">
        <x-ui.stat-card
            title="Net Profit/Loss"
            total="-$91.09"
            label="period profit only"
            variant="dark"
        />

        <x-ui.stat-card
            title="Premium Card"
            total="$2,847"
            label="premium features"
            icon="⭐"
            :change="15"
            changeType="increase"
            variant="dark"
        />
    </div>
</div>

{{-- MetricDisplay Examples --}}
<div class="showcase-section">
    <div class="showcase-section__header">
        <h2 class="showcase-section__title">MetricDisplay Examples</h2>
        <p class="showcase-section__subtitle">Flexible displays for various data types and formats</p>
    </div>

    <div class="showcase-grid showcase-grid--4">
        <x-ui.metric-display
            label="Total Revenue"
            :value="1247.50"
            format="currency"
        />

        <x-ui.metric-display
            label="Productivity Rate"
            :value="94.2"
            format="percentage"
            :precision="1"
        />

        <x-ui.metric-display
            label="Average Weight"
            :value="2.34"
            format="decimal"
            :precision="2"
            unit=" oz"
        />

        <x-ui.metric-display
            label="Total Eggs"
            :value="1423"
            format="number"
        />
    </div>

    <div class="showcase-grid showcase-grid--3" style="margin-top: 1.5rem;">
        <x-ui.metric-display
            label="Compact Display"
            :value="567"
            format="number"
            variant="compact"
        />

        <x-ui.metric-display
            label="Default Display"
            :value="1234.56"
            format="currency"
        />

        <x-ui.metric-display
            label="Large Display"
            :value="89.7"
            format="percentage"
            variant="large"
        />
    </div>
</div>

{{-- ProgressCard Examples --}}
<div class="showcase-section">
    <div class="showcase-section__header">
        <h2 class="showcase-section__title">ProgressCard Examples</h2>
        <p class="showcase-section__subtitle">Visual progress indicators with customizable styling</p>
    </div>

    <div class="showcase-grid showcase-grid--4 showcase-grid--gap-6">
        <x-ui.progress-card
            title="Egg Production"
            :value="78"
            :max="100"
            label="Daily Goal"
        />

        <x-ui.progress-card
            title="Feed Consumption"
            :value="245"
            :max="300"
            label="Weekly Target"
            variant="detailed"
        />

        <x-ui.progress-card
            title="Nest Occupancy"
            :value="42"
            :max="50"
            label="Available Nests"
            variant="compact"
        />

        <x-ui.progress-card
            title="Loading Progress"
            :value="0"
            :max="100"
            label="Processing..."
            :loading="true"
        />
    </div>
</div>

{{-- ComparisonCard Examples --}}
<div class="showcase-section">
    <div class="showcase-section__header">
        <h2 class="showcase-section__title">ComparisonCard Examples</h2>
        <p class="showcase-section__subtitle">Before and after comparisons with change indicators</p>
    </div>

    <div class="showcase-grid showcase-grid--4 showcase-grid--gap-6">
        <x-ui.comparison-card
            title="Daily Egg Count"
            :before="['value' => 35, 'label' => 'Yesterday']"
            :after="['value' => 47, 'label' => 'Today']"
            format="number"
        />

        <x-ui.comparison-card
            title="Feed Cost"
            :before="['value' => 45.50, 'label' => 'Last Month']"
            :after="['value' => 42.75, 'label' => 'This Month']"
            format="currency"
        />

        <x-ui.comparison-card
            title="Productivity"
            :before="['value' => 87.5, 'label' => 'Previous Week']"
            :after="['value' => 94.2, 'label' => 'Current Week']"
            format="percentage"
        />

        <x-ui.comparison-card
            title="Loading Comparison"
            :before="['value' => 0, 'label' => 'Before']"
            :after="['value' => 0, 'label' => 'After']"
            format="number"
            :loading="true"
        />
    </div>
</div>

{{-- SummaryCard Examples --}}
<div class="showcase-section">
    <div class="showcase-section__header">
        <h2 class="showcase-section__title">SummaryCard Examples</h2>
        <p class="showcase-section__subtitle">Detailed summaries with lists and actions</p>
    </div>

    <div class="showcase-grid showcase-grid--3 showcase-grid--gap-6">
        <x-ui.summary-card
            title="Today's Activities"
            :items="[
                ['label' => 'Eggs Collected', 'value' => '47 eggs'],
                ['label' => 'Feed Given', 'value' => '25 lbs'],
                ['label' => 'Nest Checks', 'value' => '3 times'],
                ['label' => 'Water Refilled', 'value' => '2 containers'],
            ]"
        />

        <x-ui.summary-card
            title="Weekly Summary"
            :items="[
                ['label' => 'Total Eggs', 'value' => '324'],
                ['label' => 'Revenue', 'value' => '$162.00'],
                ['label' => 'Feed Used', 'value' => '175 lbs'],
                ['label' => 'Efficiency', 'value' => '94%'],
            ]"
            variant="detailed"
        />

        <x-ui.summary-card
            title="Quick Stats"
            :items="[
                ['label' => 'Active Hens', 'value' => '45'],
                ['label' => 'Eggs Today', 'value' => '47'],
            ]"
            variant="compact"
        />

        <x-ui.summary-card
            title="Monthly Overview"
            :items="[
                ['label' => 'Days Active', 'value' => '21/21'],
                ['label' => 'Total Production', 'value' => '987 eggs'],
                ['label' => 'Average Daily', 'value' => '47 eggs'],
                ['label' => 'Best Day', 'value' => '52 eggs'],
            ]"
            :showDividers="true"
        />

        <x-ui.summary-card
            title="Loading Summary"
            :loading="true"
        />
    </div>
</div>

{{-- Combined Dashboard Example --}}
<div class="showcase-section">
    <div class="showcase-section__header">
        <h2 class="showcase-section__title">Combined Dashboard Example</h2>
        <p class="showcase-section__subtitle">Real-world usage combining multiple card types</p>
    </div>

    <div style="display: grid; grid-template-columns: 1fr; gap: 1.5rem;">
        <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 1.5rem;">
            <x-ui.stat-card
                title="Today's Collection"
                total="47"
                label="eggs"
                :change="12"
                changeType="increase"
                icon="🥚"
                variant="corner-gradient"
            />

            <x-ui.metric-display
                label="Productivity Rate"
                :value="104.4"
                format="percentage"
            />

            <x-ui.progress-card
                title="Daily Goal"
                :value="47"
                :max="45"
                label="Target: 45 eggs"
                variant="detailed"
            />

            <x-ui.comparison-card
                title="Week over Week"
                :before="['value' => 287, 'label' => 'Last Week']"
                :after="['value' => 324, 'label' => 'This Week']"
                format="number"
            />
        </div>

        <x-ui.summary-card
            title="Farm Status"
            :items="[
                ['label' => 'Active Hens', 'value' => '45/50'],
                ['label' => 'Feed Level', 'value' => '87 lbs'],
                ['label' => 'Water', 'value' => 'Full'],
                ['label' => 'Nest Boxes', 'value' => 'Clean'],
            ]"
            variant="detailed"
        />
    </div>
</div>
