@extends('layouts.app')

@section('title', 'Sales Reports')

@section('content')
<div class="sales-reports">
    <x-layout.page-header title="Sales Reports" />

    <div class="sales-reports__filter">
        <form hx-get="{{ route('app.sales.reports') }}"
              hx-target="#report-content"
              hx-swap="innerHTML"
              hx-push-url="true"
              role="search"
              aria-label="Filter sales by date range"
              class="sales-reports__filter-form">

            <x-forms.form-row :cols="3">
                <x-forms.date-input name="from" :value="$report['from']->format('Y-m-d')" label="From" />
                <x-forms.date-input name="to" :value="$report['to']->format('Y-m-d')" label="To" />
                <div class="sales-reports__filter-action">
                    <x-forms.submit-button label="Apply Filter" />
                </div>
            </x-forms.form-row>
        </form>

        <p class="sales-reports__period-label">
            Showing results for {{ $report['from']->format('M j') }} &ndash; {{ $report['to']->format('M j, Y') }}
        </p>
    </div>

    <div id="report-content" role="region" aria-label="Sales report results">
        @include('sales.partials.report-results', compact('report'))
    </div>
</div>
@endsection
