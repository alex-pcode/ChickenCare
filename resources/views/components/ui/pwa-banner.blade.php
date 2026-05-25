<div class="pwa-banner"
    data-install-title="{{ __('ui.pwa.install_title') }}"
    data-install-message="{{ __('ui.pwa.install_message') }}"
    data-install-action="{{ __('ui.pwa.install_action') }}"
    data-update-title="{{ __('ui.pwa.update_title') }}"
    data-update-message="{{ __('ui.pwa.update_message') }}"
    data-update-action="{{ __('ui.pwa.update_action') }}"
     x-data="window.ChickenCare.pwa.banner()"
     x-init="init()"
     x-show="visible"
     x-cloak
     x-transition:enter="toast--enter"
     x-transition:enter-start="toast--enter-start"
     x-transition:enter-end="toast--enter-end"
     x-transition:leave="toast--leave"
     x-transition:leave-start="toast--leave-start"
     x-transition:leave-end="toast--leave-end">
    <div class="pwa-banner__panel" role="status" aria-live="polite">
        <div class="pwa-banner__copy">
            <p class="pwa-banner__title" x-text="title"></p>
            <p class="pwa-banner__message" x-text="message"></p>
        </div>

        <div class="pwa-banner__actions">
            <button type="button"
                    class="btn btn--primary btn--sm"
                    @click="act()"
                    x-text="actionLabel"></button>
            <button type="button"
                    class="btn btn--secondary btn--sm"
                    @click="dismiss()">
                {{ __('ui.pwa.dismiss') }}
            </button>
        </div>
    </div>
</div>