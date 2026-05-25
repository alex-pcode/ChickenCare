@php
    $pageTitle = __('account.page.title');
    $skel = $skel ?? false;
@endphp

@extends('layouts.app')

@section('title', $pageTitle)

@section('content')
<div class="account-page">
    <x-ui.breadcrumbs :items="[
        ['label' => __('navigation.menu.dashboard'), 'href' => route('app.dashboard')],
        ['label' => $pageTitle, 'current' => true],
    ]" />

    <div class="account-page__header">
        <h1 class="account-page__title">{{ $pageTitle }}</h1>
    </div>

    <div class="account-page__tabs" role="tablist" aria-label="{{ __('account.page.tabs_aria_label') }}"
         x-data="{ activeTab: '{{ $tab }}' }">
        @php
            $tabs = [
                'profile' => __('account.tabs.profile'),
                'security' => __('account.tabs.security'),
                'billing' => __('account.tabs.billing'),
                'goals' => __('account.tabs.goals'),
            ];
        @endphp
        @foreach($tabs as $id => $label)
            <button
                role="tab"
                :aria-selected="activeTab === '{{ $id }}' ? 'true' : 'false'"
                :class="'account-page__tab' + (activeTab === '{{ $id }}' ? ' account-page__tab--active' : '')"
                hx-get="{{ route('app.account.index', ['tab' => $id]) }}"
                hx-target="#account-tab-content"
                hx-push-url="true"
                hx-swap="innerHTML"
                @click="activeTab = '{{ $id }}'"
            >
                {{ $label }}
            </button>
        @endforeach
    </div>

    <div id="account-banner"
         x-data="accountBanners()"
            @account-profile-updated.window="show('success', @js(__('account.messages.profile_updated')))"
            @account-preferences-updated.window="show('success', @js(__('account.messages.preferences_updated')))"
            @account-password-reset-sent.window="show('success', @js(__('account.messages.password_reset_sent')))"
            @account-password-reset-failed.window="show('error', $event.detail?.message || @js(__('account.messages.password_reset_failed')))">
        <template x-if="visible">
            <div :class="'account-page__banner account-page__banner--' + type"
                 x-transition:enter="account-page__banner-enter"
                 x-transition:enter-start="account-page__banner-enter-start"
                 x-transition:enter-end="account-page__banner-enter-end"
                 role="alert"
                 :aria-live="type === 'error' ? 'assertive' : 'polite'">
                <span x-text="type === 'success' ? '✅' : '❌'" aria-hidden="true"></span>
                <span x-text="message"></span>
                <button @click="dismiss()" class="account-page__banner-close" aria-label="{{ __('account.page.dismiss_banner') }}">&times;</button>
            </div>
        </template>
    </div>

    <div id="account-tab-content" class="account-page__tab-content has-loading-skeleton" data-loading-skeleton="account-tab">
        @if ($skel)
            <div class="account-profile">
                <div class="form-card">
                    <div class="form-card__header">
                        <h2 class="form-card__title"><x-ui.skel block="title" /></h2>
                        <p class="form-card__subtitle"><x-ui.skel block="body-wide" /></p>
                    </div>
                    <div class="form-card__form">
                        <x-forms.input name="skel-1" label=" " :loading="true" />
                        <x-forms.input name="skel-2" label=" " :loading="true" />
                        <x-forms.input name="skel-3" label=" " :loading="true" />
                        <x-forms.submit-button label="" :loading="true" />
                    </div>
                </div>
                <div class="form-card">
                    <div class="form-card__header">
                        <h2 class="form-card__title"><x-ui.skel block="title" /></h2>
                        <p class="form-card__subtitle"><x-ui.skel block="body-wide" /></p>
                    </div>
                    <div class="form-card__form">
                        <p><x-ui.skel block="body-wide" /></p>
                    </div>
                </div>
            </div>
        @else
            @include("account.partials.tab-{$tab}")
        @endif
    </div>
</div>
@endsection

@push('scripts')
<script>
    window.accountBanners = function() {
        return {
            visible: false,
            type: 'success',
            message: '',
            timeout: null,
            show(type, message) {
                this.type = type;
                this.message = message;
                this.visible = true;
                clearTimeout(this.timeout);
                if (type === 'success') {
                    this.timeout = setTimeout(() => this.dismiss(), 3000);
                }
            },
            dismiss() {
                this.visible = false;
                clearTimeout(this.timeout);
            }
        };
    };
</script>
@endpush
