<div x-show="!loading && stats.feedCycles > 0 && stats.breakdown && stats.breakdown.length > 0"
     x-cloak
     class="feed__breakdown">
    <h2 class="text-2xl font-bold text-gray-900 dark:text-white mb-1">Feed Period Breakdown</h2>
    <p class="text-sm text-gray-500 dark:text-gray-400 mb-4">Detailed analysis of each completed feed cycle</p>

    <div class="space-y-3">
        <template x-for="(period, idx) in stats.breakdown" :key="period.id">
            <div class="feed__period-card"
                 :class="{ 'feed__period-card--selected': expandedPeriod === period.id }"
                 @click="expandedPeriod = expandedPeriod === period.id ? null : period.id">
                {{-- Collapsed view --}}
                <div class="feed__period-summary">
                    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 flex-1">
                        <div>
                            <div class="text-sm text-gray-500 dark:text-gray-400">Brand</div>
                            <div class="font-medium text-gray-900 dark:text-white" x-text="period.brand"></div>
                        </div>
                        <div>
                            <div class="text-sm text-gray-500 dark:text-gray-400">Period</div>
                            <div class="font-medium text-gray-900 dark:text-white" x-text="period.openedDate + ' → ' + period.depletedDate"></div>
                        </div>
                        <div>
                            <div class="text-sm text-gray-500 dark:text-gray-400">Flock Size</div>
                            <div class="font-medium text-gray-900 dark:text-white" x-text="period.flockSizeAtStart + ' birds'"></div>
                        </div>
                        <div>
                            <div class="text-sm text-gray-500 dark:text-gray-400">Cost/Bird/Month</div>
                            <div class="font-medium text-green-600 dark:text-green-400" x-text="'$' + period.costPerBirdPerMonth.toFixed(2)"></div>
                        </div>
                    </div>
                    <div x-show="period.hasFlockChanges" class="text-orange-500 text-sm mt-1">
                        ⚠️ Flock changes during this period
                    </div>
                </div>

                {{-- Expanded view --}}
                <div x-show="expandedPeriod === period.id"
                     x-transition:enter="transition ease-out duration-200"
                     x-transition:enter-start="opacity-0"
                     x-transition:enter-end="opacity-100"
                     @click.stop
                     class="feed__period-detail mt-4 pt-4 border-t border-gray-200 dark:border-gray-700">
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                        <div>
                            <h4 class="font-semibold text-gray-900 dark:text-white mb-2">Feed Details</h4>
                            <dl class="space-y-1 text-sm">
                                <div class="flex justify-between"><dt class="text-gray-500">Brand</dt><dd class="text-gray-900 dark:text-white" x-text="period.brand"></dd></div>
                                <div class="flex justify-between"><dt class="text-gray-500">Type</dt><dd class="text-gray-900 dark:text-white" x-text="period.feedType"></dd></div>
                                <div class="flex justify-between"><dt class="text-gray-500">Quantity</dt><dd class="text-gray-900 dark:text-white" x-text="period.quantity + ' ' + period.unit"></dd></div>
                                <div class="flex justify-between" x-show="period.batchNumber"><dt class="text-gray-500">Batch #</dt><dd class="text-gray-900 dark:text-white" x-text="period.batchNumber"></dd></div>
                            </dl>
                        </div>
                        <div>
                            <h4 class="font-semibold text-gray-900 dark:text-white mb-2">Consumption</h4>
                            <dl class="space-y-1 text-sm">
                                <div class="flex justify-between"><dt class="text-gray-500">Duration</dt><dd class="text-gray-900 dark:text-white" x-text="period.durationDays + ' days'"></dd></div>
                                <div class="flex justify-between"><dt class="text-gray-500">Opened</dt><dd class="text-gray-900 dark:text-white" x-text="period.openedDate"></dd></div>
                                <div class="flex justify-between"><dt class="text-gray-500">Depleted</dt><dd class="text-gray-900 dark:text-white" x-text="period.depletedDate"></dd></div>
                            </dl>
                        </div>
                        <div>
                            <h4 class="font-semibold text-gray-900 dark:text-white mb-2">Cost Analysis</h4>
                            <dl class="space-y-1 text-sm">
                                <div class="flex justify-between"><dt class="text-gray-500">Total Cost</dt><dd class="font-semibold text-gray-900 dark:text-white" x-text="'$' + period.totalCost.toFixed(2)"></dd></div>
                                <div class="flex justify-between"><dt class="text-gray-500">Cost/Bird/Day</dt><dd class="text-gray-900 dark:text-white" x-text="'$' + period.costPerBirdPerDay.toFixed(4)"></dd></div>
                                <div class="flex justify-between"><dt class="text-gray-500">Cost/Bird/Month</dt><dd class="font-semibold text-green-600 dark:text-green-400" x-text="'$' + period.costPerBirdPerMonth.toFixed(2)"></dd></div>
                            </dl>
                        </div>
                    </div>

                    {{-- Flock Changes --}}
                    <template x-if="period.hasFlockChanges && period.flockChanges.length > 0">
                        <div class="mt-4">
                            <h4 class="font-semibold text-gray-900 dark:text-white mb-2">Flock Changes</h4>
                            <div class="space-y-2">
                                <template x-for="change in period.flockChanges" :key="change.date + change.type">
                                    <div class="rounded-lg px-3 py-2 text-sm"
                                         :class="change.type === 'acquisition' ? 'bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800' : 'bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800'">
                                        <div class="flex items-center justify-between">
                                            <span x-text="change.date"></span>
                                            <span class="font-medium"
                                                  :class="change.type === 'acquisition' ? 'text-green-700 dark:text-green-400' : 'text-red-700 dark:text-red-400'"
                                                  x-text="(change.change > 0 ? '+' : '') + change.change + ' birds'"></span>
                                        </div>
                                        <div class="text-gray-600 dark:text-gray-400 mt-1">
                                            <span x-text="change.previousCount"></span> → <span x-text="change.newCount"></span> birds
                                            <span class="ml-2" x-text="'(' + change.batchName + ')'"></span>
                                        </div>
                                    </div>
                                </template>
                            </div>
                        </div>
                    </template>

                    {{-- Sub-periods --}}
                    <template x-if="period.subPeriods && period.subPeriods.length > 0">
                        <div class="mt-4">
                            <h4 class="font-semibold text-gray-900 dark:text-white mb-2">Cost Allocation by Sub-Period</h4>
                            <div class="overflow-x-auto">
                                <table class="w-full text-sm">
                                    <thead>
                                        <tr class="text-gray-500">
                                            <th class="text-left py-1">Period</th>
                                            <th class="text-right py-1">Days</th>
                                            <th class="text-right py-1">Flock</th>
                                            <th class="text-right py-1">Bird-Days</th>
                                            <th class="text-right py-1">Cost</th>
                                            <th class="text-right py-1">$/Bird/Mo</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <template x-for="sp in period.subPeriods" :key="sp.startDate">
                                            <tr class="border-t border-gray-100 dark:border-gray-800">
                                                <td class="py-1" x-text="sp.startDate + ' – ' + sp.endDate"></td>
                                                <td class="text-right py-1" x-text="sp.days"></td>
                                                <td class="text-right py-1" x-text="sp.flockSize"></td>
                                                <td class="text-right py-1" x-text="sp.birdDays"></td>
                                                <td class="text-right py-1" x-text="'$' + sp.proportionalCost.toFixed(2)"></td>
                                                <td class="text-right py-1 font-medium text-green-600 dark:text-green-400" x-text="'$' + sp.costPerBirdPerMonth.toFixed(2)"></td>
                                            </tr>
                                        </template>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </template>
                </div>
            </div>
        </template>
    </div>

    {{-- How Calculations Work --}}
    <div class="mt-6" x-data="{ showInfo: false }">
        <button @click="showInfo = !showInfo" class="text-sm text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-300 flex items-center gap-1">
            <span x-text="showInfo ? '▼' : '▶'"></span>
            How Calculations Work
        </button>
        <div x-show="showInfo"
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0"
             x-transition:enter-end="opacity-100"
             class="mt-3 space-y-3 text-sm text-gray-600 dark:text-gray-400">
            <div class="bg-gray-50 dark:bg-gray-800 rounded-lg p-4">
                <h5 class="font-semibold text-gray-900 dark:text-white mb-1">Basic Cost Allocation</h5>
                <p>Cost per bird per day = Total Cost ÷ Duration (days) ÷ Flock Size. Monthly cost = daily cost × 30.</p>
            </div>
            <div class="bg-gray-50 dark:bg-gray-800 rounded-lg p-4">
                <h5 class="font-semibold text-gray-900 dark:text-white mb-1">Flock-Change-Aware Allocation</h5>
                <p>When your flock size changes during a feed period, we split it into sub-periods. Each sub-period gets a proportional share of the cost based on "bird-days" (flock size × days in that sub-period).</p>
            </div>
            <div class="bg-gray-50 dark:bg-gray-800 rounded-lg p-4">
                <h5 class="font-semibold text-gray-900 dark:text-white mb-1">Bird-Days Concept</h5>
                <p>Bird-days measure total bird-time. If you have 10 birds for 5 days, that's 50 bird-days. If your flock changes from 10 to 15 birds midway through, we calculate bird-days for each sub-period separately to allocate costs fairly.</p>
            </div>
        </div>
    </div>
</div>
