<div class="account-goals" x-data="{ goal: '{{ $user->chicken_goal?->value ?? 'hobby' }}' }">
    @php
        $selectedLocale = old('locale', $user->locale ?? app()->getLocale());
    @endphp
    <form hx-patch="{{ route('app.account.update-preferences') }}"
          hx-target="#account-tab-content"
          hx-swap="innerHTML"
          class="account-goals__form">
        @csrf

        {{-- Farm Preferences (merged card) --}}
        <div class="form-card">
            <div class="form-card__header">
                <h2 class="form-card__title">{{ __('account.preferences.title') }}</h2>
                <p class="form-card__subtitle">{{ __('account.preferences.subtitle') }}</p>
            </div>

            <div class="form-card__form">
                <div class="account-goals__prefs-grid">
                    {{-- Chicken Goal --}}
                    <div class="form-group">
                        <label for="chicken_goal" class="form-label">{{ __('account.preferences.chicken_goal') }}</label>
                        <select id="chicken_goal" name="chicken_goal" class="form-select" required
                                x-model="goal">
                            <option value="hobby" {{ ($user->chicken_goal?->value ?? 'hobby') === 'hobby' ? 'selected' : '' }}>{{ __('account.goal_options.hobby') }}</option>
                            <option value="business" {{ ($user->chicken_goal?->value ?? '') === 'business' ? 'selected' : '' }}>{{ __('account.goal_options.business') }}</option>
                        </select>
                        @error('chicken_goal')
                            <p class="form-error" role="alert">{{ $message }}</p>
                        @enderror
                        <p class="form-help-text"
                           x-show="goal === 'hobby'" x-cloak>{{ __('account.preferences.chicken_goal_help_hobby') }}</p>
                        <p class="form-help-text"
                           x-show="goal === 'business'" x-cloak>{{ __('account.preferences.chicken_goal_help_business') }}</p>
                    </div>

                    {{-- Yearly Egg Goal --}}
                    <x-forms.input
                        name="yearly_egg_goal"
                        :label="__('account.preferences.yearly_egg_goal')"
                        type="number"
                        :value="old('yearly_egg_goal', $user->yearly_egg_goal ?? 0)"
                        :placeholder="__('account.preferences.yearly_egg_goal_placeholder')"
                        min="0"
                        max="1000000"
                        :required="true"
                    />

                    {{-- Price per Egg --}}
                    <x-forms.input
                        name="egg_price"
                        :label="__('account.preferences.egg_price')"
                        type="number"
                        :value="old('egg_price', $user->egg_price ?? '0.30')"
                        placeholder="0.30"
                        step="0.01"
                        min="0"
                        max="999.99"
                        :required="true"
                    />

                    <div class="form-group">
                        <label for="locale" class="form-label">{{ __('account.preferences.language') }}</label>
                        <select id="locale" name="locale" class="form-select" required>
                            @foreach(config('app.supported_locales', ['en']) as $locale)
                                <option value="{{ $locale }}" @selected($selectedLocale === $locale)>{{ __('account.locales.'.$locale) }}</option>
                            @endforeach
                        </select>
                        @error('locale')
                            <p class="form-error" role="alert">{{ $message }}</p>
                        @enderror
                        <p class="form-help-text">{{ __('account.preferences.language_help') }}</p>
                    </div>
                </div>
            </div>
        </div>

        {{-- Annual Progress (read-only summary, shown only when goal is set) --}}
        @if(($user->yearly_egg_goal ?? 0) > 0)
            @php
                $percentage = round(($yearProgress / $user->yearly_egg_goal) * 100, 1);
                $remaining = max(0, $user->yearly_egg_goal - $yearProgress);
                $overGoal = $yearProgress > $user->yearly_egg_goal;
            @endphp
            <div class="account-goals__progress-section">
                <h3 class="account-goals__progress-heading">{{ __('account.progress.heading') }}</h3>

                <div class="account-production__progress-panel">
                    @if($overGoal)
                        <p class="account-production__progress-label account-production__progress-label--over">
                            {{ __('account.progress.over_goal', ['count' => number_format($yearProgress), 'percentage' => $percentage, 'goal' => number_format($user->yearly_egg_goal)]) }}
                        </p>
                    @else
                        <p class="account-production__progress-label">
                            {{ __('account.progress.summary', ['count' => number_format($yearProgress), 'goal' => number_format($user->yearly_egg_goal), 'percentage' => $percentage]) }}
                        </p>
                    @endif
                    <div class="account-production__progress-bar{{ $overGoal ? ' account-production__progress-bar--over' : '' }}"
                         role="progressbar"
                         aria-valuenow="{{ $yearProgress }}" aria-valuemin="0" aria-valuemax="{{ $user->yearly_egg_goal }}">
                        <div class="account-production__progress-fill" style="width: {{ min(100, $percentage) }}%"></div>
                    </div>
                </div>

                <div class="account-production__mini-stats">
                    <x-ui.stat-card
                        :title="__('account.progress.this_month')"
                        :total="$thisMonthEggs"
                        :label="__('account.progress.eggs_collected')"
                        icon="📅"
                    />
                    <x-ui.stat-card
                        :title="__('account.progress.this_week')"
                        :total="$thisWeekEggs"
                        :label="__('account.progress.eggs_collected')"
                        icon="📊"
                    />
                </div>

                @if($yearProgress > 0 && !$overGoal)
                    <div class="account-production__keep-going">
                        <div>
                            <h3>{{ __('account.progress.keep_going_title') }}</h3>
                            <p>{{ __('account.progress.keep_going_body', ['remaining' => number_format($remaining)]) }}</p>
                        </div>
                    </div>
                @endif
            </div>
        @endif

        {{-- Historical Data (conditional, full width below form) --}}
        @if($hasEggEntries)
            <div class="form-card" style="margin-top: 2rem;">
                <div class="form-card__header">
                    <h2 class="form-card__title">{{ __('account.historical.title') }}</h2>
                    <p class="form-card__subtitle">{{ __('account.historical.subtitle') }}</p>
                </div>

                <div class="account-historical__info">
                    <div>
                        <strong>{{ __('account.historical.info_title') }}</strong>
                        <p>{{ __('account.historical.info_body') }}</p>
                    </div>
                </div>

                <button type="button"
                        class="btn btn--secondary btn--lg"
                        hx-get="{{ route('app.eggs.backfill-form') }}"
                        hx-target="body"
                        hx-swap="beforeend">
                    {{ __('account.historical.action') }}
                </button>
            </div>
        @endif

        {{-- Save Preferences --}}
        <div style="margin-top: 2rem;">
            <x-forms.submit-button
                :label="__('account.preferences.save')"
                :saving-label="__('ui.submit_button.saving')"
                :saved-label="__('ui.submit_button.saved')"
            />
        </div>
    </form>
</div>
