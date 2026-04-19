<div class="account-goals" x-data="{ goal: '{{ $user->chicken_goal?->value ?? 'hobby' }}' }">
    <form hx-patch="{{ route('app.account.update-preferences') }}"
          hx-target="#account-tab-content"
          hx-swap="innerHTML"
          class="account-goals__form">
        @csrf

        {{-- Farm Preferences (merged card) --}}
        <div class="form-card">
            <div class="form-card__header">
                <h2 class="form-card__title">Farm Preferences</h2>
                <p class="form-card__subtitle">Customize your goals and pricing</p>
            </div>

            <div class="form-card__form">
                <div class="account-goals__prefs-grid">
                    {{-- Chicken Goal --}}
                    <div class="form-group">
                        <label for="chicken_goal" class="form-label">Primary goal with raising chickens</label>
                        <select id="chicken_goal" name="chicken_goal" class="form-select" required
                                x-model="goal">
                            <option value="hobby" {{ ($user->chicken_goal?->value ?? 'hobby') === 'hobby' ? 'selected' : '' }}>Hobby / Family Use</option>
                            <option value="business" {{ ($user->chicken_goal?->value ?? '') === 'business' ? 'selected' : '' }}>Business / Profit</option>
                        </select>
                        @error('chicken_goal')
                            <p class="form-error" role="alert">{{ $message }}</p>
                        @enderror
                        <p class="form-help-text"
                           x-show="goal === 'hobby'" x-cloak>We'll show savings vs. buying store eggs.</p>
                        <p class="form-help-text"
                           x-show="goal === 'business'" x-cloak>We'll show revenue vs. expenses.</p>
                    </div>

                    {{-- Yearly Egg Goal --}}
                    <x-forms.input
                        name="yearly_egg_goal"
                        label="Yearly Egg Production Goal"
                        type="number"
                        :value="$user->yearly_egg_goal ?? 0"
                        placeholder="e.g. 1200"
                        min="0"
                        max="1000000"
                        :required="true"
                    />

                    {{-- Price per Egg --}}
                    <x-forms.input
                        name="egg_price"
                        label="Price per Egg ($)"
                        type="number"
                        :value="$user->egg_price ?? '0.30'"
                        placeholder="0.30"
                        step="0.01"
                        min="0"
                        max="999.99"
                        :required="true"
                    />
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
                <h3 class="account-goals__progress-heading">Your progress</h3>

                <div class="account-production__progress-panel">
                    @if($overGoal)
                        <p class="account-production__progress-label account-production__progress-label--over">
                            {{ number_format($yearProgress) }} eggs collected &mdash; {{ $percentage }}% over your annual goal of {{ number_format($user->yearly_egg_goal) }}! Consider raising your target.
                        </p>
                    @else
                        <p class="account-production__progress-label">
                            {{ number_format($yearProgress) }} of {{ number_format($user->yearly_egg_goal) }} eggs ({{ $percentage }}% of goal)
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
                        title="This Month"
                        :total="$thisMonthEggs"
                        label="eggs collected"
                        icon="📅"
                    />
                    <x-ui.stat-card
                        title="This Week"
                        :total="$thisWeekEggs"
                        label="eggs collected"
                        icon="📊"
                    />
                </div>

                @if($yearProgress > 0 && !$overGoal)
                    <div class="account-production__keep-going">
                        <div>
                            <h3>Keep Going!</h3>
                            <p>You need {{ number_format($remaining) }} more eggs to reach your annual goal.</p>
                        </div>
                    </div>
                @endif
            </div>
        @endif

        {{-- Historical Data (conditional, full width below form) --}}
        @if($hasEggEntries)
            <div class="form-card" style="margin-top: 2rem;">
                <div class="form-card__header">
                    <h2 class="form-card__title">Historical Data</h2>
                    <p class="form-card__subtitle">Import historical egg tracking data</p>
                </div>

                <div class="account-historical__info">
                    <div>
                        <strong>Backfill Historical Data</strong>
                        <p>Add egg production data for dates before you started using ChickenCare. This helps create more accurate analytics and trends.</p>
                    </div>
                </div>

                <button type="button"
                        class="btn btn--secondary btn--lg"
                        hx-get="{{ route('app.eggs.backfill-form') }}"
                        hx-target="body"
                        hx-swap="beforeend">
                    Import Historical Data
                </button>
            </div>
        @endif

        {{-- Save Preferences --}}
        <div style="margin-top: 2rem;">
            <x-forms.submit-button label="Save Preferences" />
        </div>
    </form>
</div>
