@extends('layouts.app')

@section('title', __('viability.page.title'))

@section('content')
<div class="viability" x-data="viabilityCalculator({{ Js::from($newDefaults) }}, {{ Js::from(__('viability')) }})" x-cloak>
    <x-layout.page-header :title="__('viability.page.header')" />

    {{-- Hero Section --}}
    <div class="viability__hero">
        <div class="viability__hero-badge" aria-hidden="true">
            <span class="viability__hero-badge-icon">🐔</span>
        </div>
        <div class="viability__hero-media">
            <img src="/images/cute-chickens-discussing.webp" alt="{{ __('viability.hero.image_alt') }}" class="viability__hero-image">
        </div>

        <div class="viability__hero-side">
            <div class="viability__hero-notice" role="note">
                <div class="viability__hero-notice-text">
                    <h2 class="viability__hero-notice-title">
                        <span class="d-none-mobile">{{ __('viability.hero.notice') }}</span>
                        <span class="d-only-mobile">{{ __('viability.hero.notice_short') }}</span>
                    </h2>
                </div>
            </div>
        </div>
    </div>

    {{-- Starting Investment Section --}}
    <div class="viability__investment glass-card">
        <h2 class="viability__section-title">{{ __('viability.sections.starting_investment.title') }}</h2>
        <p class="viability__section-desc">{{ __('viability.sections.starting_investment.description') }}</p>

        <div class="viability__info-box">
            <p><strong>{{ __('viability.sections.starting_investment.reminder_title') }}</strong> {{ __('viability.sections.starting_investment.reminder_body') }}</p>
        </div>

        {{-- Desktop Grid --}}
        <div class="viability__option-grid viability__option-grid--desktop">
            <template x-for="option in startingCostOptions" :key="option.id">
                <div class="viability__option-card"
                     :class="{ 'viability__option-card--selected': selectedStartingCostId === option.id }"
                     @click="selectStartingCost(option)"
                     role="button"
                     tabindex="0"
                     @keydown.enter="selectStartingCost(option)"
                     @keydown.space.prevent="selectStartingCost(option)">
                    <div class="viability__option-card-cost" x-text="'$' + option.cost"></div>
                    <div class="viability__option-card-title" x-text="option.title"></div>
                    <div class="viability__option-card-desc" x-text="option.description"></div>
                    <ul class="viability__option-card-details">
                        <template x-for="detail in option.details" :key="detail">
                            <li>
                                <svg class="viability__checkmark" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>
                                <span x-text="detail"></span>
                            </li>
                        </template>
                    </ul>
                </div>
            </template>
        </div>

        {{-- Mobile Carousel --}}
        <div class="viability__carousel">
            <div class="viability__carousel-track" x-ref="carouselTrack">
                <template x-for="option in startingCostOptions" :key="'m-' + option.id">
                    <div class="viability__option-card viability__carousel-card"
                         :class="{ 'viability__option-card--selected': selectedStartingCostId === option.id }"
                         @click="selectStartingCost(option)"
                         role="button"
                         tabindex="0"
                         @keydown.enter="selectStartingCost(option)"
                         @keydown.space.prevent="selectStartingCost(option)">
                        <div class="viability__option-card-cost" x-text="'$' + option.cost"></div>
                        <div class="viability__option-card-title" x-text="option.title"></div>
                        <div class="viability__option-card-desc" x-text="option.description"></div>
                        <ul class="viability__option-card-details">
                            <template x-for="detail in option.details" :key="detail">
                                <li>
                                    <svg class="viability__checkmark" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>
                                    <span x-text="detail"></span>
                                </li>
                            </template>
                        </ul>
                    </div>
                </template>
            </div>
            <div class="viability__carousel-dots">
                <template x-for="(option, index) in startingCostOptions" :key="'dot-' + option.id">
                    <button class="viability__carousel-dot"
                            :class="{ 'viability__carousel-dot--active': activeCarouselIndex === index }"
                            @click="$refs.carouselTrack.children[index]?.scrollIntoView({ behavior: 'smooth', inline: 'center', block: 'nearest' })"
                            :aria-label="interpolate(i18n.labels.go_to_option, { option: option.title })"></button>
                </template>
            </div>
        </div>

        {{-- Custom Amount (desktop) --}}
        <div class="viability__custom-amount viability__custom-amount--desktop glass-card">
            <h3>{{ __('viability.sections.starting_investment.custom_amount_title') }}</h3>
            <p class="viability__section-desc">{{ __('viability.sections.starting_investment.custom_amount_description') }}</p>
            <div class="viability__custom-amount-input">
                <input type="number" min="0" class="viability__param-input"
                       x-model.number="startingCost"
                       @input="selectedStartingCostId = startingCostOptions.find(o => o.cost === startingCost)?.id || 'custom'">
                <span class="viability__input-label">{{ __('viability.sections.starting_investment.currency_label') }}</span>
            </div>
        </div>

        {{-- Custom Amount (mobile) --}}
        <div class="viability__custom-amount viability__custom-amount--mobile glass-card">
            <h3>{{ __('viability.sections.starting_investment.custom_amount_title') }}</h3>
            <p class="viability__section-desc">{{ __('viability.sections.starting_investment.custom_amount_description') }}</p>
            <div class="viability__custom-amount-input">
                <input type="number" min="0" class="viability__param-input"
                       x-model.number="startingCost"
                       @input="selectedStartingCostId = startingCostOptions.find(o => o.cost === startingCost)?.id || 'custom'">
                <span class="viability__input-label">{{ __('viability.sections.starting_investment.currency_label') }}</span>
            </div>
        </div>
    </div>

    {{-- Acquisition Method Section --}}
    <div class="viability__acquisition glass-card">
        <h2 class="viability__section-title">{{ __('viability.sections.acquisition.title') }}</h2>
        <p class="viability__section-desc">{{ __('viability.sections.acquisition.description') }}</p>

        <div class="viability__option-grid viability__option-grid--acquisition">
            <template x-for="option in acquisitionOptions" :key="option.id">
                <div class="viability__option-card"
                     :class="{ 'viability__option-card--selected': selectedAcquisitionId === option.id }"
                     @click="selectedAcquisitionId = option.id"
                     role="button"
                     tabindex="0"
                     @keydown.enter="selectedAcquisitionId = option.id"
                     @keydown.space.prevent="selectedAcquisitionId = option.id">
                    <div class="viability__option-card-cost" x-text="option.emoji"></div>
                    <div class="viability__option-card-title" x-text="option.title"></div>
                    <div class="viability__option-card-desc" x-text="option.description"></div>
                    <template x-if="option.layingDelayMonths > 0">
                        <span class="viability__delay-badge" x-text="interpolate(i18n.labels.months_until_laying, { months: option.layingDelayMonths })"></span>
                    </template>
                    <ul class="viability__option-card-details">
                        <template x-for="detail in option.details" :key="detail">
                            <li>
                                <svg class="viability__checkmark" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>
                                <span x-text="detail"></span>
                            </li>
                        </template>
                    </ul>
                </div>
            </template>
        </div>
    </div>

    {{-- Setup Parameters Section --}}
    <div class="viability__params glass-card">
        <h2 class="viability__section-title">{{ __('viability.sections.parameters.title') }}</h2>

        <div class="viability__params-grid">
            <div class="viability__param-group">
                <label class="viability__param-label" for="bird-count">{{ __('viability.sections.parameters.bird_count') }}</label>
                <input type="number" id="bird-count" min="1" max="100" step="1"
                       class="viability__param-input"
                       x-model.number="birdCount">
            </div>
            <div class="viability__param-group">
                <label class="viability__param-label" for="egg-price">{{ __('viability.sections.parameters.egg_price') }}</label>
                <input type="number" id="egg-price" min="0" step="0.01"
                       class="viability__param-input"
                       x-model.number="eggPrice">
            </div>
        </div>
    </div>

    {{-- Feeding Approach Section --}}
    <div class="viability__feeding glass-card">
        <h2 class="viability__section-title">{{ __('viability.sections.feeding.title') }}</h2>
        <p class="viability__section-desc">{{ __('viability.sections.feeding.description') }}</p>

        <div class="viability__option-grid viability__option-grid--feed">
            <template x-for="option in feedOptions" :key="option.id">
                <div class="viability__option-card"
                     :class="{ 'viability__option-card--selected': selectedFeedId === option.id }"
                     @click="selectedFeedId = option.id"
                     role="button"
                     tabindex="0"
                     @keydown.enter="selectedFeedId = option.id"
                     @keydown.space.prevent="selectedFeedId = option.id">
                    <div class="viability__option-card-cost" x-text="'$' + option.costPerBird.toFixed(2)"></div>
                    <div class="viability__option-card-title" x-text="option.title"></div>
                    <div class="viability__option-card-desc" x-text="option.description"></div>
                    <ul class="viability__option-card-details">
                        <template x-for="detail in option.details" :key="detail">
                            <li>
                                <svg class="viability__checkmark" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>
                                <span x-text="detail"></span>
                            </li>
                        </template>
                    </ul>
                </div>
            </template>
        </div>
    </div>

    {{-- Egg Production Scenario Section --}}
    <div class="viability__production glass-card">
        <h2 class="viability__section-title">{{ __('viability.sections.production.title') }}</h2>
        <p class="viability__section-desc">{{ __('viability.sections.production.description') }}</p>

        <div class="viability__option-grid viability__option-grid--production">
            <template x-for="option in productionOptions" :key="option.id">
                <div class="viability__option-card"
                     :class="{ 'viability__option-card--selected': selectedProductionId === option.id }"
                     @click="selectedProductionId = option.id"
                     role="button"
                     tabindex="0"
                     @keydown.enter="selectedProductionId = option.id"
                     @keydown.space.prevent="selectedProductionId = option.id">
                    <div class="viability__option-card-cost" x-text="option.eggsPerBirdPerWeek"></div>
                    <div class="viability__option-card-title" x-text="option.title"></div>
                    <div class="viability__option-card-desc" x-text="option.description"></div>
                    <ul class="viability__option-card-details">
                        <template x-for="detail in option.details" :key="detail">
                            <li>
                                <svg class="viability__checkmark" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>
                                <span x-text="detail"></span>
                            </li>
                        </template>
                    </ul>
                </div>
            </template>
        </div>
    </div>

    {{-- Financial Analysis Section --}}
    <div class="viability__analysis" x-show="showResults" x-cloak
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0 transform translate-y-4"
         x-transition:enter-end="opacity-100 transform translate-y-0">
        <h2 class="viability__section-title">{{ __('viability.sections.analysis.title') }}</h2>

        {{-- StatCards Grid --}}
        <div class="viability__analysis-grid">
            <div class="stat-card stat-card--corner-gradient">
                <div class="stat-card__gradient-blob" aria-hidden="true"></div>
                <div class="stat-card__inner">
                    <div class="stat-card__body">
                        <div class="stat-card__title">{{ __('viability.metrics.monthly_egg_production') }}</div>
                        <div class="stat-card__value" x-text="results.monthlyEggProduction"></div>
                        <div class="stat-card__meta">
                            <span x-text="results.layingDelayMonths > 0 ? interpolate(i18n.labels.after_months, { months: results.layingDelayMonths }) : i18n.labels.eggs_per_month"></span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="stat-card stat-card--corner-gradient">
                <div class="stat-card__gradient-blob" aria-hidden="true"></div>
                <div class="stat-card__inner">
                    <div class="stat-card__body">
                        <div class="stat-card__title">{{ __('viability.metrics.monthly_egg_value') }}</div>
                        <div class="stat-card__value" x-text="formatUsd(results.monthlyEggValue)"></div>
                        <div class="stat-card__meta"><span x-text="i18n.labels.potential_revenue"></span></div>
                    </div>
                </div>
            </div>

            <div class="stat-card stat-card--corner-gradient">
                <div class="stat-card__gradient-blob" aria-hidden="true"></div>
                <div class="stat-card__inner">
                    <div class="stat-card__body">
                        <div class="stat-card__title">{{ __('viability.metrics.monthly_feed_cost') }}</div>
                        <div class="stat-card__value" x-text="formatUsd(results.monthlyFeedCost)"></div>
                        <div class="stat-card__meta"><span x-text="i18n.labels.total_feed_expense"></span></div>
                    </div>
                </div>
            </div>

            <div class="stat-card stat-card--corner-gradient">
                <div class="stat-card__gradient-blob" aria-hidden="true"></div>
                <div class="stat-card__inner">
                    <div class="stat-card__body">
                        <div class="stat-card__title">{{ __('viability.metrics.monthly_profit') }}</div>
                        <div class="stat-card__value" x-text="formatUsd(results.monthlyProfit)"></div>
                        <div class="stat-card__meta">
                            <span class="viability__profit-badge"
                                  :class="isProfitable ? 'viability__profit-badge--positive' : 'viability__profit-badge--negative'"
                                  x-text="isProfitable ? i18n.labels.profitable : i18n.labels.loss"></span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Baby Chick Timeline Impact --}}
        <div class="viability__timeline" x-show="results.layingDelayMonths > 0"
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0"
             x-transition:enter-end="opacity-100">
            <h3>{{ __('viability.sections.timeline.title') }}</h3>
            <ul class="viability__timeline-list">
                <li x-text="interpolate(@js(__('viability.sections.timeline.non_laying_period')), { months: results.layingDelayMonths, cost: formatUsd(results.nonLayingFeedCost) })"></li>
                <li x-text="interpolate(@js(__('viability.sections.timeline.first_year_production')), { months: 12 - results.layingDelayMonths })"></li>
                <li>{{ __('viability.sections.timeline.starting_investment') }}</li>
            </ul>
        </div>

        {{-- Panels: Annual Summary + Payback Analysis --}}
        <div class="viability__panels">
            {{-- Annual Summary Panel --}}
            <div class="viability__summary-panel">
                <h3>{{ __('viability.sections.annual_summary.title') }}</h3>

                <div class="viability__panel-row">
                    <span class="viability__panel-label">{{ __('viability.sections.annual_summary.first_year_feed_cost') }}</span>
                    <div>
                        <span class="viability__panel-value" x-text="formatUsd(results.annualFeedCost)"></span>
                        <span class="viability__panel-sub" x-text="interpolate(i18n.labels.times_twelve, { amount: formatUsd(results.monthlyFeedCost) })"></span>
                    </div>
                </div>

                <div class="viability__panel-row">
                    <span class="viability__panel-label">{{ __('viability.sections.annual_summary.first_year_egg_value') }}</span>
                    <div>
                        <span class="viability__panel-value" x-text="formatUsd(results.annualEggValue)"></span>
                        <span class="viability__panel-sub" x-text="interpolate(i18n.labels.times_laying_months, { amount: formatUsd(results.monthlyEggValue), months: 12 - results.layingDelayMonths })"></span>
                    </div>
                </div>

                <template x-if="results.layingDelayMonths > 0">
                    <div class="viability__panel-row viability__panel-row--warning">
                        <span x-text="interpolate(@js(__('viability.sections.annual_summary.non_laying_months')), { months: results.layingDelayMonths })"></span>
                    </div>
                </template>

                <div class="viability__panel-separator"></div>

                <div class="viability__panel-row">
                    <span class="viability__panel-label">{{ __('viability.sections.annual_summary.first_year_profit') }}</span>
                    <span class="viability__panel-value"
                          :class="results.annualProfit >= 0 ? 'viability__panel-value--positive' : 'viability__panel-value--negative'"
                          x-text="formatUsd(results.annualProfit)"></span>
                </div>
            </div>

            {{-- Payback Analysis Panel --}}
            <div class="viability__summary-panel">
                <h3>{{ __('viability.sections.payback.title') }}</h3>

                <div class="viability__panel-row">
                    <span class="viability__panel-label">{{ __('viability.sections.payback.starting_investment') }}</span>
                    <span class="viability__panel-value" x-text="formatUsd(startingCost)"></span>
                </div>

                <div class="viability__panel-row">
                    <span class="viability__panel-label">{{ __('viability.sections.payback.monthly_profit') }}</span>
                    <span class="viability__panel-value"
                          :class="results.monthlyProfit >= 0 ? 'viability__panel-value--positive' : 'viability__panel-value--negative'"
                          x-text="formatUsd(results.monthlyProfit)"></span>
                </div>

                <div class="viability__panel-separator"></div>

                <div class="viability__panel-row">
                    <span class="viability__panel-label">{{ __('viability.sections.payback.payback_period') }}</span>
                    <span class="viability__payback-badge"
                          :class="'viability__payback-badge--' + paybackColor"
                          x-text="paybackText"></span>
                </div>
            </div>
        </div>
    </div>

    {{-- Viability Assessment Section --}}
    <div class="viability__assessment" x-show="showResults" x-cloak
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0 transform translate-y-4"
         x-transition:enter-end="opacity-100 transform translate-y-0">
        <h2 class="viability__section-title">{{ __('viability.sections.assessment.title') }}</h2>

        <div class="viability__assessment-item">
            <h4>{{ __('viability.sections.assessment.break_even_title') }}</h4>
            <p>{{ __('viability.sections.assessment.break_even_body') }}</p>
        </div>

        <div class="viability__assessment-item">
            <h4>{{ __('viability.sections.assessment.your_assessment') }}</h4>
            <p x-text="assessmentText"></p>
        </div>

        <div class="viability__assessment-item">
            <h4>{{ __('viability.sections.assessment.recommendations') }}</h4>
            <p x-text="recommendationText"></p>
        </div>
    </div>
</div>
@endsection
