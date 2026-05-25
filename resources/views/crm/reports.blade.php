@extends('layouts.app')

@section('title', __('crm.reports.page_title'))

@section('content')
<div class="crm-page">
    <a href="{{ route('app.crm.index') }}" class="crm-page__back-link">
        ← {{ __('crm.reports.back_to_crm') }}
    </a>

    <div class="crm-reports">
        {{-- View Toggle (outside swap target so it persists) --}}
        <div class="crm-reports__view-toggle">
            <button class="crm-reports__pill {{ ($reportView ?? 'overview') === 'overview' ? 'crm-reports__pill--active' : '' }}"
                    hx-get="{{ route('app.crm.reports', ['view' => 'overview']) }}"
                    hx-target="#crm-reports-content"
                    hx-swap="innerHTML"
                    hx-push-url="true"
                    hx-on::before-request="document.querySelectorAll('.crm-reports__pill').forEach(p => p.classList.remove('crm-reports__pill--active')); this.classList.add('crm-reports__pill--active');">
                {{ __('crm.reports.overview') }}
            </button>
            <button class="crm-reports__pill {{ ($reportView ?? 'overview') === 'customer' ? 'crm-reports__pill--active' : '' }}"
                    hx-get="{{ route('app.crm.reports', ['view' => 'customer']) }}"
                    hx-target="#crm-reports-content"
                    hx-swap="innerHTML"
                    hx-push-url="true"
                    hx-on::before-request="document.querySelectorAll('.crm-reports__pill').forEach(p => p.classList.remove('crm-reports__pill--active')); this.classList.add('crm-reports__pill--active');">
                {{ __('crm.reports.per_customer') }}
            </button>
        </div>

        <div id="crm-reports-content">
            @if(($reportView ?? 'overview') === 'overview')
                @include('crm.partials.tab-reports-overview')
            @else
                @include('crm.partials.tab-reports-customer')
            @endif
        </div>
    </div>
</div>
@endsection
