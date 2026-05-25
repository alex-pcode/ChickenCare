<div class="auth-form__social" aria-label="{{ __('auth.social.aria_label') }}">
    <div class="auth-form__social-buttons">
        <a href="{{ route('social.redirect', ['provider' => 'google']) }}" class="auth-form__social-button auth-form__social-button--google">
            <span class="auth-form__social-icon" aria-hidden="true">G</span>
            <span>{{ $mode === 'register' ? __('auth.social.sign_up_with', ['provider' => __('auth.social.providers.google')]) : __('auth.social.continue_with', ['provider' => __('auth.social.providers.google')]) }}</span>
        </a>

        <a href="{{ route('social.redirect', ['provider' => 'facebook']) }}" class="auth-form__social-button auth-form__social-button--facebook">
            <span class="auth-form__social-icon" aria-hidden="true">f</span>
            <span>{{ $mode === 'register' ? __('auth.social.sign_up_with', ['provider' => __('auth.social.providers.facebook')]) : __('auth.social.continue_with', ['provider' => __('auth.social.providers.facebook')]) }}</span>
        </a>
    </div>

    <div class="auth-form__separator" aria-hidden="true">
        <span>{{ $mode === 'register' ? __('auth.social.or_sign_up_with_email') : __('auth.social.or_continue_with_email') }}</span>
    </div>
</div>