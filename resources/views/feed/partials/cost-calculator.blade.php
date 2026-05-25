<section class="feed__calculator"
    x-data="feedCostCalculator()"
    x-init="fetchStats()">
    
    <div class="feed__calculator-header">
        <div>
            <h2 class="text-2xl font-bold text-gray-900 dark:text-white">{{ __('feed.calculator.title') }}</h2>
            <p class="text-sm text-gray-500 dark:text-gray-400">{{ __('feed.calculator.subtitle') }}</p>
        </div>
        <div class="feed__range-selector">
            <template x-for="r in ranges" :key="r.value">
                <button type="button"
                    class="feed__range-btn"
                    :class="{ 'feed__range-btn--active': range === r.value }"
                    @click="range = r.value; fetchStats()"
                    x-text="r.label">
                </button>
            </template>
        </div>
    </div>

    <div x-show="loading" class="text-center py-8">
        <div class="inline-block animate-spin rounded-full h-8 w-8 border-b-2 border-green-500"></div>
    </div>

    <template x-if="!loading && stats.feedCycles === 0">
        <x-ui.empty-state
            :title="__('feed.calculator.empty_title')"
            :description="__('feed.calculator.empty_description')"
            icon="📊"
        />
    </template>

    <div x-show="!loading && stats.feedCycles > 0" x-cloak>
        @include('feed.partials.stat-cards')
    </div>

    @include('feed.partials.cost-trends-chart')
</section>
