@extends('layouts.app')

@section('title', __('expenses.page.title'))

@section('content')
<div class="expenses">
    @include('expenses.partials.hero')

    <div x-data="{
        success: false,
        errors: [],
        submitting: false
    }" class="expenses__form-container">
        @include('expenses.partials.banner-success')
        @include('expenses.partials.banner-errors')

        <x-forms.form-card
            :title="__('expenses.form.title')"
            :subtitle="__('expenses.form.subtitle')"
            icon="💰"
            method="POST"
            action="{{ route('app.expenses.store') }}"
            hx-post="{{ route('app.expenses.store') }}"
            hx-target="#expense-entries-body"
            hx-swap="afterbegin"
            data-offline-queue="expenses"
            :hx-headers='json_encode(["Accept" => "application/json"])'
            hx-on::before-request="submitting = true; errors = []; success = false"
            hx-on::after-request="submitting = false; if (event.detail.successful) { success = true; window.ChickenCare.htmx.resetForm(event); setTimeout(() => success = false, 3000); }"
            hx-on::response-error="errors = window.ChickenCare.htmx.extractErrors(event.detail.xhr)"
        >
            <x-forms.form-row :cols="3">
                <x-forms.date-input name="date" :label="__('expenses.form.fields.date')" :value="now()->format('Y-m-d')" :max="now()->format('Y-m-d')" required />
                <x-forms.select
                    name="category"
                    :label="__('expenses.form.fields.category')"
                    :options="collect(\App\Enums\ExpenseCategory::cases())->mapWithKeys(fn($c) => [$c->value => $c->label()])->all()"
                    value="Birds"
                    placeholder=""
                    required
                />
                <x-forms.input name="amount" :label="__('expenses.form.fields.amount')" type="number" required :placeholder="__('expenses.form.placeholders.amount')" step="0.01" min="0" />
            </x-forms.form-row>

            <x-forms.input name="description" :label="__('expenses.form.fields.description')" required :placeholder="__('expenses.form.placeholders.description')" />

            <div class="expenses__form-actions">
                <x-forms.submit-button
                    :label="__('expenses.form.submit')"
                    :saving-label="__('ui.submit_button.saving')"
                    :saved-label="__('ui.submit_button.saved')" />
            </div>
        </x-forms.form-card>
    </div>

    <div class="expenses__headline-stats">
        <x-ui.comparison-card
            :title="__('expenses.stats.monthly_expenses')"
            format="currency"
            semantic="inverse"
            :before="['value' => $stats['monthOverMonth']['previousMonthTotal'], 'label' => __('expenses.stats.previous_month')]"
            :after="['value' => $stats['monthOverMonth']['thisMonthTotal'], 'label' => __('expenses.stats.this_month')]" />
        @include('expenses.partials.cost-per-egg-card', [
            'costPerEgg' => $stats['costPerEgg'],
            'costWindowMonths' => $stats['costWindowMonths'],
        ])
    </div>

    @include('expenses.partials.monthly-trend-chart', ['expenseTrendData' => $stats['monthlyTrend']])

    <div class="expenses__breakdown-grid">
        @include('expenses.partials.category-summary', [
            'stats' => $stats,
            'selectedCategory' => $selectedCategory,
        ])
        <div id="category-items-panel" class="glass-card">
            @include('expenses.partials.category-items', [
                'selectedCategory' => $selectedCategory,
                'categoryLabel' => $categoryLabel,
                'items' => $categoryItems,
            ])
        </div>
    </div>

    @if($expenses->isEmpty() && !$currentCategory)
        <x-ui.empty-state
            :title="__('expenses.records.empty_title')"
            :description="__('expenses.records.empty_description')"
            icon="💰"
        />
    @else
        <section class="expenses__records" hx-indicator="#records-spinner">
            <h2 class="expenses__records-heading">{{ __('expenses.records.heading') }}</h2>
            <div id="records-spinner" class="htmx-indicator expenses__records-spinner">
                <svg style="width: 1.5rem; height: 1.5rem; color: #6366f1;" class="animate-spin" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                    <circle style="opacity: 0.25;" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path style="opacity: 0.75;" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
            </div>
            @include('expenses.partials.records-table', ['expenses' => $expenses, 'sort' => $sort, 'dir' => $dir])
        </section>
    @endif
</div>
@endsection
