<div class="account-security">
    {{-- Password Reset Card --}}
    <div class="account-security__reset-card">
        <div class="account-security__reset-header">
            <div>
                <h3 class="account-security__reset-title">{{ __('account.security.title') }}</h3>
                <p class="account-security__reset-subtitle">{{ __('account.security.subtitle') }}</p>
            </div>
        </div>
        <p class="account-security__reset-hint">{{ __('account.security.hint', ['email' => $user->email]) }}</p>
        <button type="button"
                class="btn btn--secondary btn--lg"
                @click="$dispatch('open-password-reset-dialog')">
            {{ __('account.security.reset') }}
        </button>
    </div>

    {{-- Confirm Dialog --}}
    <x-ui.confirm-dialog
        id="password-reset-dialog"
        :title="__('account.security.title')"
        :message="__('account.security.dialog_message', ['email' => $user->email])"
        variant="warning"
        :confirm-text="__('account.security.continue')"
        :cancel-text="__('account.security.cancel')"
        hx-post="{{ route('app.account.password-reset-link') }}"
        hx-swap="none">
        @csrf
    </x-ui.confirm-dialog>
</div>
