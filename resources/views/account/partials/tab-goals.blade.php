<div class="account-goals" x-data="{ goal: '{{ $user->chicken_goal?->value ?? 'hobby' }}' }">
    <form hx-patch="{{ route('app.account.update-preferences') }}"
          hx-target="#account-tab-content"
          hx-swap="innerHTML"
          class="account-goals__form">
        @csrf

        <div class="account-goals__grid">
            {{-- Your Chicken Goals --}}
            <div class="form-card">
                <div class="form-card__header">
                    <div class="account-profile__header-row">
                        <span class="account-profile__icon">🐔</span>
                        <h2 class="form-card__title">Your Chicken Goals</h2>
                    </div>
                    <p class="form-card__subtitle">Help us customize your experience based on your primary goal</p>
                </div>

                <div class="form-card__form">
                    <div class="form-group">
                        <label for="chicken_goal" class="form-label">What's your primary goal with raising chickens?</label>
                        <select id="chicken_goal" name="chicken_goal" class="form-select" required
                                x-model="goal">
                            <option value="hobby" {{ ($user->chicken_goal?->value ?? 'hobby') === 'hobby' ? 'selected' : '' }}>Hobby/Family Use</option>
                            <option value="business" {{ ($user->chicken_goal?->value ?? '') === 'business' ? 'selected' : '' }}>Business/Profit</option>
                        </select>
                        @error('chicken_goal')
                            <p class="form-error" role="alert">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Hobby context panel --}}
                    <div x-show="goal === 'hobby'" x-cloak class="account-goals__context account-goals__context--hobby">
                        <div class="account-goals__context-header">
                            <span>🏠</span>
                            <h4>Hobby/Family Focus</h4>
                        </div>
                        <ul class="account-goals__context-list">
                            <li>Track egg production for family consumption</li>
                            <li>Monitor your flock's health and wellness</li>
                            <li>Compare costs vs buying store eggs</li>
                            <li>Enjoy the hobby while keeping records</li>
                            <li>Share production updates with family</li>
                        </ul>
                        <div class="account-goals__subcard">
                            📊 Your Savings tab will show: Money saved vs buying organic store eggs - perfect for tracking household cost benefits!
                        </div>
                    </div>

                    {{-- Business context panel --}}
                    <div x-show="goal === 'business'" x-cloak class="account-goals__context account-goals__context--business">
                        <div class="account-goals__context-header">
                            <span>💼</span>
                            <h4>Business/Profit Focus</h4>
                        </div>
                        <ul class="account-goals__context-list">
                            <li>Track revenue and profit margins</li>
                            <li>Manage customer relationships</li>
                            <li>Monitor feed costs and ROI</li>
                            <li>Scale your egg business efficiently</li>
                        </ul>
                        <div class="account-goals__subcard">
                            📈 Your Savings tab will show: Actual revenue vs expenses - ideal for monitoring business profitability and growth!
                        </div>
                    </div>
                </div>
            </div>

            {{-- Production Goals --}}
            <div class="form-card">
                <div class="form-card__header">
                    <div class="account-profile__header-row">
                        <span class="account-profile__icon">🎯</span>
                        <h2 class="form-card__title">Production Goals</h2>
                    </div>
                    <p class="form-card__subtitle">Track your annual egg production target</p>
                </div>

                <div class="form-card__form">
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
                    <p class="form-help-text">Set your target number of eggs for the year</p>

                    @if(($user->yearly_egg_goal ?? 0) > 0)
                        @php
                            $percentage = round(($yearProgress / $user->yearly_egg_goal) * 100, 1);
                            $remaining = max(0, $user->yearly_egg_goal - $yearProgress);
                        @endphp
                        <div class="account-production__progress-panel">
                            <div class="account-production__progress-header">
                                <span>📊</span>
                                <h4>Annual Progress</h4>
                            </div>
                            <p class="account-production__progress-label">{{ number_format($yearProgress) }} eggs collected ({{ $percentage }}% of goal)</p>
                            <div class="account-production__progress-bar" role="progressbar"
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

                        @if($yearProgress > 0 && $user->yearly_egg_goal > $yearProgress)
                            <div class="account-production__keep-going">
                                <span>🎯</span>
                                <div>
                                    <h3>Keep Going!</h3>
                                    <p>You need {{ number_format($remaining) }} more eggs to reach your annual goal.</p>
                                </div>
                            </div>
                        @endif
                    @endif
                </div>
            </div>

            {{-- Pricing Configuration --}}
            <div class="form-card">
                <div class="form-card__header">
                    <div class="account-profile__header-row">
                        <span class="account-profile__icon">💰</span>
                        <h2 class="form-card__title">Pricing Configuration</h2>
                    </div>
                    <p class="form-card__subtitle">Set your egg pricing preferences</p>
                </div>

                <div class="form-card__form">
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

            {{-- Historical Data (conditional) --}}
            @if($hasEggEntries)
                <div class="form-card account-goals__full-width">
                    <div class="form-card__header">
                        <div class="account-profile__header-row">
                            <span class="account-profile__icon">📊</span>
                            <h2 class="form-card__title">Historical Data</h2>
                        </div>
                        <p class="form-card__subtitle">Import historical egg tracking data</p>
                    </div>

                    <div class="account-historical__info">
                        <span>💡</span>
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
                        📊 Import Historical Data
                    </button>
                </div>
            @endif
        </div>

        {{-- Save Preferences --}}
        <div style="margin-top: 2rem;">
            <x-forms.submit-button label="💾 Save Preferences" />
        </div>
    </form>
</div>
