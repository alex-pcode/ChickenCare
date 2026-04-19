@extends('layouts.app')

@section('title', 'Account Settings')

@section('content')
<div class="account-page">
    <x-ui.breadcrumbs :items="[
        ['label' => 'Dashboard', 'href' => route('app.dashboard')],
        ['label' => 'Account Settings', 'current' => true],
    ]" />

    <div class="account-page__header">
        <h1 class="account-page__title">Account Settings</h1>
        <p class="account-page__subtitle">Manage your personal information, security, and preferences</p>
    </div>

    <div class="account-page__tabs" role="tablist" aria-label="Account settings tabs"
         x-data="{ activeTab: '{{ $tab }}' }">
        @php
            $tabs = [
                'profile' => '👤 Profile',
                'security' => '🔒 Security',
                'billing' => '💳 Billing',
                'goals' => '🎯 Goals & Preferences',
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
         @account-profile-updated.window="show('success', 'Profile updated successfully!')"
         @account-preferences-updated.window="show('success', 'Preferences updated successfully!')"
         @account-password-reset-sent.window="show('success', 'Password reset link sent to your email!')"
         @account-password-reset-failed.window="show('error', $event.detail?.message || 'Failed to send password reset link.')">
        <template x-if="visible">
            <div :class="'account-page__banner account-page__banner--' + type"
                 x-transition:enter="account-page__banner-enter"
                 x-transition:enter-start="account-page__banner-enter-start"
                 x-transition:enter-end="account-page__banner-enter-end"
                 role="alert"
                 :aria-live="type === 'error' ? 'assertive' : 'polite'">
                <span x-text="type === 'success' ? '✅' : '❌'" aria-hidden="true"></span>
                <span x-text="message"></span>
                <button @click="dismiss()" class="account-page__banner-close" aria-label="Dismiss">&times;</button>
            </div>
        </template>
    </div>

    <div id="account-tab-content" class="account-page__tab-content {{ $tab === 'goals' ? 'account-page__tab-content--wide' : 'account-page__tab-content--narrow' }}">
        @include("account.partials.tab-{$tab}")
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
