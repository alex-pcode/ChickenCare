<div class="account-profile">
    {{-- Personal Information --}}
    <div class="form-card">
        <div class="form-card__header">
            <div class="account-profile__header-row">
                <span class="account-profile__icon">👤</span>
                <h2 class="form-card__title">Personal Information</h2>
            </div>
            <p class="form-card__subtitle">Update your profile details</p>
        </div>

        {{-- Account Verification --}}
        <div class="account-verification">
            <h4 class="account-verification__title">Account Verification</h4>
            <div class="account-verification__row">
                <div class="account-verification__info">
                    <span class="account-verification__icon">✅</span>
                    <div>
                        <div class="account-verification__label">Email Address</div>
                        <div class="account-verification__sublabel">Your email is verified and secure</div>
                    </div>
                </div>
                <span class="account-verification__pill account-verification__pill--verified">Verified</span>
            </div>
        </div>

        <form hx-patch="{{ route('app.account.update-profile') }}"
              hx-target="#account-tab-content"
              hx-swap="innerHTML"
              class="form-card__form"
              x-data="{ name: '{{ addslashes($user->name) }}' }">
            @csrf

            <x-forms.input
                name="name"
                label="Display Name"
                :value="$user->name"
                placeholder="Enter your display name"
                :required="true"
            />

            <x-forms.input
                name="email"
                label="Email Address"
                type="email"
                :value="$user->email"
                disabled
                readonly
            />
            <p class="form-help-text">Email changes require account verification and are currently disabled</p>

            <x-forms.submit-button label="💾 Save Profile" />
        </form>
    </div>

    {{-- Appearance --}}
    <div class="form-card" style="margin-top: 2rem;">
        <div class="form-card__header">
            <div class="account-profile__header-row">
                <span class="account-profile__icon">🎨</span>
                <h2 class="form-card__title">Appearance</h2>
            </div>
            <p class="form-card__subtitle">Customize your visual experience</p>
        </div>

        <div class="account-theme">
            <h4 class="account-theme__title">Theme</h4>
            <p class="account-theme__description">Choose how ChickenCare looks to you. By default, it follows your device settings.</p>

            <x-ui.theme-toggle />
        </div>
    </div>
</div>
