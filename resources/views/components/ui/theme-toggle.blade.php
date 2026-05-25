<div class="account-theme-toggle" x-data="themeToggle()">
    <button type="button"
            class="account-theme-toggle__btn"
            :class="{ 'account-theme-toggle__btn--active': theme === 'light' }"
            @click="setTheme('light')"
            aria-label="{{ __('ui.theme.light_aria_label') }}">
        ☀️ {{ __('ui.theme.light') }}
    </button>
    <button type="button"
            class="account-theme-toggle__btn"
            :class="{ 'account-theme-toggle__btn--active': theme === 'dark' }"
            @click="setTheme('dark')"
            aria-label="{{ __('ui.theme.dark_aria_label') }}">
        🌙 {{ __('ui.theme.dark') }}
    </button>
</div>

@once
@push('scripts')
<script>
    window.themeToggle = function() {
        return {
            theme: document.cookie.match(/(?:^|; )theme=([^;]+)/)?.[1] || 'system',
            setTheme(value) {
                this.theme = value;
                document.cookie = `theme=${value};path=/;max-age=${60*60*24*365};SameSite=Lax`;
                this.applyTheme(value);
            },
            applyTheme(value) {
                const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
                const isDark = value === 'dark' || (value === 'system' && prefersDark);
                document.documentElement.classList.toggle('dark', isDark);
            }
        };
    };
</script>
@endpush
@endonce
