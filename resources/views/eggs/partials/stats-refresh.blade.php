@include('eggs.partials.stats', ['skel' => false, 'stats' => $stats, 'yearlyGoal' => $yearlyGoal])

<x-ui.comparison-card
    id="egg-hero-week"
    hx-swap-oob="true"
    :title="__('eggs.comparison.seven_day_title')"
    :before="['value' => $stats['previousWeekTotal'], 'label' => __('eggs.comparison.previous_7_days')]"
    :after="['value' => $stats['thisWeekTotal'], 'label' => __('eggs.comparison.last_7_days')]"
/>
