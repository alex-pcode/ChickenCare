<div class="account-security">
    {{-- Security Status Card --}}
    <div class="account-security__status">
        <div class="account-security__status-header">
            <span class="account-security__status-icon">🛡️</span>
            <div>
                <h3 class="account-security__status-title">Security Status: Secure</h3>
                <p class="account-security__status-subtitle">Your account is protected with email verification and secure authentication</p>
            </div>
        </div>
        <div class="account-security__progress" role="progressbar" aria-valuenow="100" aria-valuemin="0" aria-valuemax="100">
            <div class="account-security__progress-fill" style="width: 100%"></div>
        </div>
        <p class="account-security__progress-caption">Your account security is fully configured</p>
    </div>

    {{-- Password Reset Card --}}
    <div class="account-security__reset-card">
        <div class="account-security__reset-header">
            <span class="account-security__reset-icon">🔐</span>
            <div>
                <h3 class="account-security__reset-title">Password Reset</h3>
                <p class="account-security__reset-subtitle">Reset your password by receiving a secure link via email</p>
            </div>
        </div>
        <button type="button"
                class="btn btn--secondary btn--full btn--lg"
                @click="$dispatch('open-password-reset-dialog')">
            🔄 Reset Password
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
