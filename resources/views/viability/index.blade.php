@extends('layouts.app')

@section('title', 'Viability Calculator')

@section('content')
<div class="viability" x-data="viabilityCalculator({{ Js::from($newDefaults) }})" x-cloak>
    <x-layout.page-header title="Viability Calculator" />

    {{-- Hero Section --}}
    <div class="viability__hero">
        <img src="/images/cute-chickens-discussing.webp" alt="Cute chickens discussing" class="viability__hero-image">
        <div class="viability__hero-badge">🐔 Viability Calculator</div>
        <div class="viability__hero-welcome">Calculate your chicken venture!</div>
    </div>

    {{-- Starting Investment Section --}}
    <div class="viability__investment glass-card">
        <h2 class="viability__section-title">Starting Investment</h2>
        <p class="viability__section-desc">Starting costs for chicken keeping can vary dramatically based on your situation. Some people can start with minimal investment using existing structures and gifted birds, while others need to build everything from scratch. Consider your available space, DIY skills, and whether you have existing materials or structures to work with.</p>

        <div class="viability__info-box">
            <p><strong>💰 Don't forget:</strong> If you're purchasing birds, include their costs in your starting investment above. Baby chicks typically cost $3-5 each, while laying hens cost $15-25 each. Many people receive birds for free from friends or neighbors!</p>
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
                            :aria-label="'Go to ' + option.title"></button>
                </template>
            </div>
        </div>

        {{-- Custom Amount (desktop) --}}
        <div class="viability__custom-amount viability__custom-amount--desktop glass-card">
            <h3>Custom Amount</h3>
            <p class="viability__section-desc">If your setup doesn't match these scenarios, you can enter a custom amount below:</p>
            <div class="viability__custom-amount-input">
                <input type="number" min="0" class="viability__param-input"
                       x-model.number="startingCost"
                       @input="selectedStartingCostId = startingCostOptions.find(o => o.cost === startingCost)?.id || 'custom'">
                <span class="viability__input-label">USD</span>
            </div>
        </div>

        {{-- Custom Amount (mobile) --}}
        <div class="viability__custom-amount viability__custom-amount--mobile glass-card">
            <h3>Custom Amount</h3>
            <p class="viability__section-desc">If your setup doesn't match these scenarios, you can enter a custom amount below:</p>
            <div class="viability__custom-amount-input">
                <input type="number" min="0" class="viability__param-input"
                       x-model.number="startingCost"
                       @input="selectedStartingCostId = startingCostOptions.find(o => o.cost === startingCost)?.id || 'custom'">
                <span class="viability__input-label">USD</span>
            </div>
        </div>
    </div>

    {{-- Acquisition Method Section --}}
    <div class="viability__acquisition glass-card">
        <h2 class="viability__section-title">Acquisition Method</h2>
        <p class="viability__section-desc">Your acquisition method significantly impacts both costs and timeline. Baby chicks cost less upfront but require 5 months of feeding before they start laying eggs. Mature laying hens cost more initially but begin producing immediately. Consider your patience, budget, and desire to raise chickens from the beginning.</p>

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
                        <span class="viability__delay-badge" x-text="option.layingDelayMonths + ' months until laying'"></span>
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
        <h2 class="viability__section-title">Setup Parameters</h2>

        <div class="viability__params-grid">
            <div class="viability__param-group">
                <label class="viability__param-label" for="bird-count">Number of Chickens</label>
                <input type="number" id="bird-count" min="1" max="100" step="1"
                       class="viability__param-input"
                       x-model.number="birdCount">
            </div>
            <div class="viability__param-group">
                <label class="viability__param-label" for="egg-price">Price per Egg ($)</label>
                <input type="number" id="egg-price" min="0" step="0.01"
                       class="viability__param-input"
                       x-model.number="eggPrice">
            </div>
        </div>
    </div>

    {{-- Feeding Approach Section --}}
    <div class="viability__feeding glass-card">
        <h2 class="viability__section-title">Feeding Approach</h2>
        <p class="viability__section-desc">Your feeding approach significantly impacts both costs and chicken health. Consider your available time, space for free-ranging, access to kitchen scraps, and whether you prefer organic or conventional feeds. The right approach balances cost-effectiveness with your chickens' nutritional needs and your lifestyle.</p>

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
        <h2 class="viability__section-title">Egg Production Scenario</h2>
        <p class="viability__section-desc">Egg production varies significantly based on breed, age, season, and care quality. Younger hens in their prime (1-2 years) with good nutrition and long daylight hours will lay more eggs. Winter months, older hens, and stress can dramatically reduce production. Choose a scenario that matches your expected conditions and chicken care level.</p>

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
        <h2 class="viability__section-title">Financial Analysis</h2>

        {{-- StatCards Grid --}}
        <div class="viability__analysis-grid">
            <div class="stat-card stat-card--corner-gradient">
                <div class="stat-card__gradient-blob" aria-hidden="true"></div>
                <div class="stat-card__inner">
                    <div class="stat-card__body">
                        <div class="stat-card__title">Monthly Egg Production</div>
                        <div class="stat-card__value" x-text="results.monthlyEggProduction"></div>
                        <div class="stat-card__meta">
                            <span x-text="results.layingDelayMonths > 0 ? 'after ' + results.layingDelayMonths + ' months' : 'eggs per month'"></span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="stat-card stat-card--corner-gradient">
                <div class="stat-card__gradient-blob" aria-hidden="true"></div>
                <div class="stat-card__inner">
                    <div class="stat-card__body">
                        <div class="stat-card__title">Monthly Egg Value</div>
                        <div class="stat-card__value" x-text="formatUsd(results.monthlyEggValue)"></div>
                        <div class="stat-card__meta"><span>potential revenue</span></div>
                    </div>
                </div>
            </div>

            <div class="stat-card stat-card--corner-gradient">
                <div class="stat-card__gradient-blob" aria-hidden="true"></div>
                <div class="stat-card__inner">
                    <div class="stat-card__body">
                        <div class="stat-card__title">Monthly Feed Cost</div>
                        <div class="stat-card__value" x-text="formatUsd(results.monthlyFeedCost)"></div>
                        <div class="stat-card__meta"><span>total feed expense</span></div>
                    </div>
                </div>
            </div>

            <div class="stat-card stat-card--corner-gradient">
                <div class="stat-card__gradient-blob" aria-hidden="true"></div>
                <div class="stat-card__inner">
                    <div class="stat-card__body">
                        <div class="stat-card__title">Monthly Profit</div>
                        <div class="stat-card__value" x-text="formatUsd(results.monthlyProfit)"></div>
                        <div class="stat-card__meta">
                            <span class="viability__profit-badge"
                                  :class="isProfitable ? 'viability__profit-badge--positive' : 'viability__profit-badge--negative'"
                                  x-text="isProfitable ? 'Profitable (when laying)' : 'Loss (when laying)'"></span>
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
            <h3>Baby Chick Timeline Impact</h3>
            <ul class="viability__timeline-list">
                <li>Non-laying period: <strong x-text="results.layingDelayMonths"></strong> months with <strong x-text="formatUsd(results.nonLayingFeedCost)"></strong> feed costs and no egg revenue</li>
                <li>First year production: Only <strong x-text="12 - results.layingDelayMonths"></strong> months of egg laying</li>
                <li>Starting investment: Remember to include chick costs in your starting investment above if purchasing</li>
            </ul>
        </div>

        {{-- Panels: Annual Summary + Payback Analysis --}}
        <div class="viability__panels">
            {{-- Annual Summary Panel --}}
            <div class="viability__summary-panel">
                <h3>📈 Annual Summary</h3>

                <div class="viability__panel-row">
                    <span class="viability__panel-label">First Year Feed Cost</span>
                    <div>
                        <span class="viability__panel-value" x-text="formatUsd(results.annualFeedCost)"></span>
                        <span class="viability__panel-sub" x-text="'(' + formatUsd(results.monthlyFeedCost) + ' × 12)'"></span>
                    </div>
                </div>

                <div class="viability__panel-row">
                    <span class="viability__panel-label">First Year Egg Value</span>
                    <div>
                        <span class="viability__panel-value" x-text="formatUsd(results.annualEggValue)"></span>
                        <span class="viability__panel-sub" x-text="'(' + formatUsd(results.monthlyEggValue) + ' × ' + (12 - results.layingDelayMonths) + ')'"></span>
                    </div>
                </div>

                <template x-if="results.layingDelayMonths > 0">
                    <div class="viability__panel-row viability__panel-row--warning">
                        <span x-text="'• Non-laying months: ' + results.layingDelayMonths + ' (feed only)'"></span>
                    </div>
                </template>

                <div class="viability__panel-separator"></div>

                <div class="viability__panel-row">
                    <span class="viability__panel-label">First Year Profit</span>
                    <span class="viability__panel-value"
                          :class="results.annualProfit >= 0 ? 'viability__panel-value--positive' : 'viability__panel-value--negative'"
                          x-text="formatUsd(results.annualProfit)"></span>
                </div>
            </div>

            {{-- Payback Analysis Panel --}}
            <div class="viability__summary-panel">
                <h3>⏱️ Payback Analysis</h3>

                <div class="viability__panel-row">
                    <span class="viability__panel-label">Starting Investment</span>
                    <span class="viability__panel-value" x-text="formatUsd(startingCost)"></span>
                </div>

                <div class="viability__panel-row">
                    <span class="viability__panel-label">Monthly Profit (when laying)</span>
                    <span class="viability__panel-value"
                          :class="results.monthlyProfit >= 0 ? 'viability__panel-value--positive' : 'viability__panel-value--negative'"
                          x-text="formatUsd(results.monthlyProfit)"></span>
                </div>

                <div class="viability__panel-separator"></div>

                <div class="viability__panel-row">
                    <span class="viability__panel-label">Payback Period</span>
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
        <h2 class="viability__section-title">💡 Viability Assessment</h2>

        <div class="viability__assessment-item">
            <h4>Break-Even Analysis</h4>
            <p>A dozen store-bought eggs costs $4-6+ in 2025. Each chicken lays about 20 eggs per month, so your feed cost per bird should be less than $6-10 to break even on eggs alone.</p>
        </div>

        <div class="viability__assessment-item">
            <h4>Your Assessment</h4>
            <p x-text="assessmentText"></p>
        </div>

        <div class="viability__assessment-item">
            <h4>Recommendations</h4>
            <p x-text="recommendationText"></p>
        </div>
    </div>
</div>
@endsection
