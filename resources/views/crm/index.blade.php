@extends('layouts.app')

@section('title', 'CRM')

@section('content')
<div class="crm-page">
    {{-- Animated Hero --}}
    <div class="crm-page__hero" aria-hidden="true">
        <img src="{{ asset('images/cute-chicken-business.webp') }}"
             alt=""
             class="crm-page__hero-image"
             loading="eager">
        <div class="crm-page__badge">💼 CRM System</div>
    </div>

    <div class="crm-page__welcome glass-card">
        <p class="crm-page__welcome-text">Manage your customers!</p>
    </div>

    {{-- Tab Navigation --}}
    <div class="crm-page__tabs-wrapper">
        <div class="crm-page__tabs glass-card" role="tablist" aria-label="CRM sections">
            @php
                $tabs = [
                    ['id' => 'quick-sale', 'emoji' => '⚡', 'label' => 'Quick Sale'],
                    ['id' => 'customers', 'emoji' => '👥', 'label' => 'Customers'],
                    ['id' => 'reports', 'emoji' => '📊', 'label' => 'Reports'],
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
                    <span role="img" aria-label="{{ $tab['label'] }}">{{ $tab['emoji'] }}</span>
                    <span>{{ $tab['label'] }}</span>
                </button>
            @endforeach
        </div>
    </div>

    {{-- Tab Content --}}
    <div class="crm-page__content glass-card" id="crm-tab-content">
        <div class="crm-page__content-enter">
            @include("crm.partials.tab-{$activeTab}")
        </div>
    </div>
</div>
@endsection
