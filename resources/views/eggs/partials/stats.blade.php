@php($skel = $skel ?? false)
@php($oob = $oob ?? false)

<div class="egg-counter__stats" id="egg-stats" @if($oob) hx-swap-oob="true" @endif>
    @if ($skel)
        <x-ui.progress-card title="" :value="0" :max="100" :loading="true" variant="detailed" />
    @elseif($yearlyGoal)
        <x-ui.progress-card
            :title="__('eggs.goal.progress_title')"
            :value="$stats['thisMonthTotal']"
            :max="round($yearlyGoal / 12)"
            :label="__('eggs.goal.monthly_target', ['yearly' => number_format($yearlyGoal)])"
            variant="detailed"
        />
    @else
        @include('eggs.partials.set-goal-cta')
    @endif

    <div class="egg-counter__stats-grid egg-counter__stats-grid--comparison">
        @if ($skel)
            <x-ui.comparison-card title="" :before="['value' => 0, 'label' => '']" :after="['value' => 0, 'label' => '']" :loading="true" />
            <x-ui.comparison-card title="" :before="['value' => 0, 'label' => '']" :after="['value' => 0, 'label' => '']" :loading="true" />
        @else
            @include('eggs.partials.last-7-days-sparkline', ['days' => $stats['last7Days']])
            <x-ui.comparison-card
                :title="__('eggs.comparison.monthly_title')"
                :before="['value' => $stats['previousMonthTotal'], 'label' => __('eggs.comparison.previous_month')]"
                :after="['value' => $stats['thisMonthTotal'], 'label' => __('eggs.comparison.this_month')]"
            />
        @endif
    </div>

    <div class="egg-counter__stats-grid egg-counter__stats-grid--stat-cards">
        <x-ui.stat-card :title="__('eggs.stat_cards.total_eggs')" :total="$skel ? 0 : number_format($stats['totalEggs'])" :label="__('eggs.stat_cards.total_eggs_label')" icon="🥚" variant="corner-gradient" :loading="$skel" />
        <x-ui.stat-card :title="__('eggs.stat_cards.average_daily')" :total="$skel ? 0 : $stats['averageDaily']" :label="__('eggs.stat_cards.average_daily_label')" icon="📈" variant="corner-gradient" :loading="$skel" />
        <x-ui.stat-card :title="__('eggs.stat_cards.lay_rate')" total="--" :label="__('eggs.stat_cards.lay_rate_label')" icon="🐔" variant="corner-gradient" :loading="$skel" />
        <x-ui.stat-card :title="__('eggs.stat_cards.protein_generated')" :total="$skel ? 0 : ($stats['proteinLbs'] . ' lbs')" :label="__('eggs.stat_cards.protein_generated_label')" icon="🧙‍♂️" variant="corner-gradient" :loading="$skel" />
    </div>
</div>
