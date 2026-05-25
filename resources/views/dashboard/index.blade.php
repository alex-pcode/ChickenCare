@extends('layouts.app')

@section('title', __('dashboard.page.title'))

@php($skel = $skel ?? false)

@section('content')
<div class="dashboard">
    @include('dashboard.partials.welcome-header', [
        'displayName' => $displayName,
        'recentActivity' => $summary['recent_activity'] ?? collect(),
        'skel' => $skel,
    ])

    @if (! $skel)
        @include('dashboard.partials.setup-progress', ['progress' => $progress])
    @endif

    @include('dashboard.partials.production-metrics', ['productionMetrics' => $productionMetrics, 'skel' => $skel])

    @include('dashboard.partials.production-chart', ['productionChartData' => $productionChartData, 'skel' => $skel])

    @include('dashboard.partials.financial-overview', ['financialOverview' => $financialOverview, 'skel' => $skel])

    @include('dashboard.partials.revenue-trend', ['revenueTrendData' => $revenueTrendData, 'skel' => $skel])
</div>
@endsection
