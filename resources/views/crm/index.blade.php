@extends('layouts.app')

@section('title', __('crm.page.title'))

@section('content')
<div class="crm-page">
    {{-- Hero --}}
    @php
        if ($heroStats['thisMonthSales'] === 0) {
            $heroStatus = 'neutral';
        } elseif ($heroStats['thisMonthSales'] >= $heroStats['lastMonthSales']) {
            $heroStatus = 'success';
        } else {
            $heroStatus = 'warning';
        }
    @endphp

    <div class="crm-page__hero crm-page__hero--{{ $heroStatus }}">
        <div class="crm-page__hero-corner-badge" aria-hidden="true">
            <span class="crm-page__hero-corner-badge-icon crm-page__hero-corner-badge-icon--{{ $heroStatus }}">
                @if($heroStatus === 'success') 📈 @elseif($heroStatus === 'warning') 📉 @else 📭 @endif
            </span>
        </div>
        <div class="crm-page__hero-media">
            <img src="{{ asset('images/cute-chicken-business.webp') }}"
                 alt=""
                 class="crm-page__hero-image"
                 loading="eager">
        </div>

        <div class="crm-page__hero-side">
            <div class="crm-page__hero-status crm-page__hero-status--{{ $heroStatus }}" role="status">
                <div class="crm-page__hero-status-text">
                    @if($heroStats['thisMonthSales'] === 0)
                        <h2 class="crm-page__hero-status-title">
                            <span class="d-none-mobile">{{ __('crm.hero.no_sales_title') }}</span>
                            <span class="d-only-mobile">{{ __('crm.hero.no_sales_short') }}</span>
                        </h2>
                        <p class="crm-page__hero-status-detail d-none-mobile">
                            {{ __('crm.hero.no_sales_detail', ['month' => $heroStats['thisMonthName']]) }}
                        </p>
                    @elseif($heroStatus === 'success')
                        <h2 class="crm-page__hero-status-title">
                            <span class="d-none-mobile">{{ __('crm.hero.strong_month_title') }}</span>
                            <span class="d-only-mobile">{{ __('crm.hero.strong_month_short', [
                                'count' => $heroStats['thisMonthSales'],
                                'sales' => trans_choice('crm.hero.sale_word', $heroStats['thisMonthSales']),
                            ]) }}</span>
                        </h2>
                        <p class="crm-page__hero-status-detail d-none-mobile">
                            {{ __('crm.hero.strong_month_detail', [
                                'count' => $heroStats['thisMonthSales'],
                                'sales' => trans_choice('crm.hero.sale_word', $heroStats['thisMonthSales']),
                                'month' => $heroStats['thisMonthName'],
                            ]) }}
                        </p>
                    @else
                        <h2 class="crm-page__hero-status-title">
                            <span class="d-none-mobile">{{ __('crm.hero.down_month_title') }}</span>
                            <span class="d-only-mobile">{{ __('crm.hero.down_month_short', [
                                'count' => $heroStats['thisMonthSales'],
                                'sales' => trans_choice('crm.hero.sale_word', $heroStats['thisMonthSales']),
                            ]) }}</span>
                        </h2>
                        <p class="crm-page__hero-status-detail d-none-mobile">
                            {{ __('crm.hero.down_month_detail', [
                                'count' => $heroStats['thisMonthSales'],
                                'sales' => trans_choice('crm.hero.sale_word', $heroStats['thisMonthSales']),
                            ]) }}
                        </p>
                    @endif
                </div>
            </div>

            <x-ui.comparison-card
                :title="__('crm.hero.comparison_title')"
                :before="['value' => $heroStats['lastMonthSales'], 'label' => __('crm.hero.last_month')]"
                :after="['value' => $heroStats['thisMonthSales'], 'label' => __('crm.hero.this_month')]"
            />
        </div>
    </div>

    {{-- Tab Navigation --}}
    <div class="crm-page__tabs-wrapper">
        <div class="crm-page__tabs glass-card" role="tablist" aria-label="{{ __('crm.page.tabs_label') }}">
            @php
                $tabs = [
                    ['id' => 'quick-sale', 'emoji' => '⚡', 'label' => __('crm.tabs.quick_sale')],
                    ['id' => 'customers', 'emoji' => '👥', 'label' => __('crm.tabs.customers')],
                ];
            @endphp
            @foreach($tabs as $tab)
                <button
                    class="crm-page__tab {{ $activeTab === $tab['id'] ? 'crm-page__tab--active' : '' }}"
                    role="tab"
                    aria-selected="{{ $activeTab === $tab['id'] ? 'true' : 'false' }}"
                    hx-get="{{ route('app.crm.index', ['tab' => $tab['id']]) }}"
                    hx-target="#crm-tab-content"
                    hx-swap="innerHTML"
                    hx-push-url="true"
                    hx-on::before-request="document.querySelectorAll('.crm-page__tab').forEach(t => { t.classList.remove('crm-page__tab--active'); t.setAttribute('aria-selected', 'false'); }); this.classList.add('crm-page__tab--active'); this.setAttribute('aria-selected', 'true');"
                >
                    {{ $tab['label'] }}
                </button>
            @endforeach
            <a href="{{ route('app.crm.reports') }}" class="crm-page__tab">
                {{ __('crm.tabs.reports') }}
            </a>
        </div>
    </div>

    {{-- Tab Content --}}
    <div class="crm-page__content has-loading-skeleton" id="crm-tab-content" data-loading-skeleton="crm-tab">
        <div class="crm-page__content-enter">
            @include("crm.partials.tab-{$activeTab}")
        </div>
    </div>
</div>
@endsection
