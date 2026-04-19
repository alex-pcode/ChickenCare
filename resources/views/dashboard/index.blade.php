@extends('layouts.app')

@section('title', 'Dashboard')

@section('content')
<div class="dashboard">
    @include('dashboard.partials.welcome-header', ['displayName' => $displayName])

    @include('dashboard.partials.setup-progress', ['progress' => $progress])

    @include('dashboard.partials.production-metrics', ['productionMetrics' => $productionMetrics])

    @include('dashboard.partials.production-chart', ['productionChartData' => $productionChartData])

    @include('dashboard.partials.financial-overview', ['financialOverview' => $financialOverview])

    @include('dashboard.partials.revenue-trend', ['revenueTrendData' => $revenueTrendData])

    {{-- Recent Activity — all users --}}
    <section class="dashboard__section">
        <div class="dashboard__activity-header">
            <h2 class="dashboard__section-title">Recent Activity</h2>
            <button class="btn btn--secondary btn--sm"
                    hx-get="{{ route('app.dashboard') }}"
                    hx-target="#dashboard-activity"
                    hx-swap="innerHTML"
                    aria-label="Refresh recent activity">
                Refresh
            </button>
        </div>
        <div id="dashboard-activity">
            @include('dashboard.partials.recent-activity', ['recentActivity' => $summary['recent_activity']])
        </div>
    </section>
</div>
@endsection
