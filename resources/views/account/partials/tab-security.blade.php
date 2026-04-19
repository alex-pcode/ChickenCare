<div class="account-security">
    {{-- Password Reset Card --}}
    <div class="account-security__reset-card">
        <div class="account-security__reset-header">
            <div>
                <h3 class="account-security__reset-title">Password Reset</h3>
                <p class="account-security__reset-subtitle">Reset your password by receiving a secure link via email</p>
            </div>
        </div>
        <p class="account-security__reset-hint">We'll email a secure link to {{ $user->email }}.</p>
        <button type="button"
                class="btn btn--secondary btn--lg"
                @click="$dispatch('open-password-reset-dialog')">
            Reset Password
        </button>
    </div>

    {{-- Confirm Dialog --}}
    <x-ui.confirm-dialog
        id="password-reset-dialog"
        title="Reset Password"
        :message="'Are you sure you want to reset your password? A reset link will be sent to ' . $user->email . '.'"
        variant="warning"
        confirmText="Continue"
        cancelText="Cancel"
        hx-post="{{ route('app.account.password-reset-link') }}"
        hx-swap="none">
        @csrf
    </x-ui.confirm-dialog>
</div>
