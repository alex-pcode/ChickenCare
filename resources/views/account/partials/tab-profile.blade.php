<div class="account-profile">
    <div class="form-card">
        <div class="form-card__header">
            <h2 class="form-card__title">Personal Information</h2>
            <p class="form-card__subtitle">Update your profile details</p>
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

            <div class="form-group">
                <div class="account-profile__email-label-row">
                    <label for="email" class="form-label">Email Address</label>
                    <span class="account-profile__verified-badge">Verified</span>
                </div>
                <input
                    type="email"
                    id="email"
                    name="email"
                    value="{{ $user->email }}"
                    class="form-input"
                    disabled
                    readonly
                >
                <p class="form-help-text">Email address cannot be changed</p>
            </div>

            <x-forms.submit-button label="Save Profile" />
        </form>
    </div>
</div>
