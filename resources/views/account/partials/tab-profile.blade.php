<div class="account-profile">
    <div class="form-card">
        <div class="form-card__header">
            <h2 class="form-card__title">{{ __('account.profile.title') }}</h2>
            <p class="form-card__subtitle">{{ __('account.profile.subtitle') }}</p>
        </div>

        <form hx-patch="{{ route('app.account.update-profile') }}"
              hx-target="#account-tab-content"
              hx-swap="innerHTML"
              class="form-card__form"
              x-data="{ name: '{{ addslashes($user->name) }}' }">
            @csrf

            <x-forms.input
                name="name"
                :label="__('account.profile.display_name')"
                :value="$user->name"
                :placeholder="__('account.profile.display_name_placeholder')"
                :required="true"
            />

            <div class="form-group">
                <div class="account-profile__email-label-row">
                    <label for="email" class="form-label">{{ __('account.profile.email_address') }}</label>
                    <span class="account-profile__verified-badge">{{ __('account.profile.verified') }}</span>
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
                <p class="form-help-text">{{ __('account.profile.email_readonly') }}</p>
            </div>

            <x-forms.submit-button
                :label="__('account.profile.save')"
                :saving-label="__('ui.submit_button.saving')"
                :saved-label="__('ui.submit_button.saved')"
            />
        </form>
    </div>

    <section class="form-card" x-data="window.ChickenCare.offlineQueue.failedItemsPanel()">
        <div class="form-card__header">
            <h2 class="form-card__title">{{ __('account.offline_queue.title') }}</h2>
            <p class="form-card__subtitle">{{ __('account.offline_queue.subtitle') }}</p>
        </div>

        <div class="form-card__form account-profile__offline-queue" x-init="init()">
            <template x-if="loading">
                <p class="account-profile__offline-empty">{{ __('account.offline_queue.loading') }}</p>
            </template>

            <template x-if="!loading && items.length === 0">
                <p class="account-profile__offline-empty">{{ __('account.offline_queue.empty') }}</p>
            </template>

            <template x-if="!loading && items.length > 0">
                <div class="account-profile__offline-list">
                    <template x-for="item in items" :key="item.id">
                        <article class="account-profile__offline-item">
                            <div class="account-profile__offline-copy">
                                <h3 class="account-profile__offline-title" x-text="item.sourceLabel"></h3>
                                <p class="account-profile__offline-meta" x-text="formatTimestamp(item.failedAt || item.createdAt)"></p>
                                <p class="account-profile__offline-error" x-text="item.lastError"></p>
                            </div>
                            <button type="button"
                                    class="btn btn--secondary btn--sm"
                                    @click="discard(item.id)">
                                {{ __('account.offline_queue.discard') }}
                            </button>
                        </article>
                    </template>
                </div>
            </template>
        </div>
    </section>
</div>
