<div class="crm-page__content-enter">
<div class="crm-reports">
    {{-- View Toggle --}}
    <div class="crm-reports__view-toggle">
        <button class="crm-reports__pill {{ ($reportView ?? 'overview') === 'overview' ? 'crm-reports__pill--active' : '' }}"
                hx-get="{{ route('app.crm.index', ['tab' => 'reports', 'view' => 'overview']) }}"
                hx-target="#crm-tab-content"
                hx-swap="innerHTML"
                hx-push-url="true">
            Overview
        </button>
        <button class="crm-reports__pill {{ ($reportView ?? 'overview') === 'customer' ? 'crm-reports__pill--active' : '' }}"
                hx-get="{{ route('app.crm.index', ['tab' => 'reports', 'view' => 'customer']) }}"
                hx-target="#crm-tab-content"
                hx-swap="innerHTML"
                hx-push-url="true">
            Per Customer
        </button>
    </div>

    @if(($reportView ?? 'overview') === 'overview')
        @include('crm.partials.tab-reports-overview')
    @else
        @include('crm.partials.tab-reports-customer')
    @endif
</div>
</div>
