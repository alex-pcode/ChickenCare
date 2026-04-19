@extends('layouts.app')

@section('title', 'Expenses')

@section('content')
<div class="expenses">
    <x-layout.page-header title="Expense Tracking" />

    @include('expenses.partials.hero')

    <div x-data="{
        success: false,
        errors: [],
        submitting: false
    }" class="lg:mx-[20%]">
        @include('expenses.partials.banner-success')
        @include('expenses.partials.banner-errors')

        <x-forms.form-card
            title="Add New Expense"
            subtitle="Track your farm expenses to maintain accurate financial records"
            icon="💰"
            method="POST"
            action="{{ route('app.expenses.store') }}"
            hx-post="{{ route('app.expenses.store') }}"
            hx-target="#expense-entries-body"
            hx-swap="afterbegin"
            hx-headers='{"Accept": "application/json"}'
            hx-on::before-request="submitting = true; errors = []; success = false"
            hx-on::after-request="submitting = false; if (event.detail.successful) { success = true; $el.reset(); setTimeout(() => success = false, 3000); }"
            hx-on::response-error="try { errors = Object.values(JSON.parse(event.detail.xhr.responseText).errors).flat(); } catch(e) { errors = ['An unexpected error occurred.']; }"
        >
            <x-forms.form-row :cols="2">
                <x-forms.date-input name="date" label="Date" :value="now()->format('Y-m-d')" :max="now()->format('Y-m-d')" required />
                <x-forms.select
                    name="category"
                    label="Category"
                    :options="collect(\App\Enums\ExpenseCategory::cases())->mapWithKeys(fn($c) => [$c->value => $c->label()])->all()"
                    value="Birds"
                    placeholder=""
                    required
                />
            </x-forms.form-row>

            <x-forms.input name="description" label="Description" required placeholder="e.g., Feed purchase from farm store" />

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <x-forms.form-row>
                    <x-forms.input name="amount" label="Amount (USD)" type="number" required placeholder="0.00" step="0.01" min="0" class="w-full md:w-48" />
                </x-forms.form-row>
            </div>

            <div class="flex justify-center pt-4 border-t border-gray-200 dark:border-gray-700">
                <button type="submit" class="shiny-cta" :disabled="submitting">
                    <span x-text="submitting ? 'Adding Expense...' : 'Add Expense'">Add Expense</span>
                </button>
            </div>
        </x-forms.form-card>
    </div>

    <div class="expenses__breakdown"
         x-data="expenseBreakdown()"
         x-init="init()"
         @expenses:changed.window="refetchStats()">

        @include('expenses.partials.breakdown-chart', ['stats' => $stats])
        @include('expenses.partials.category-summary', ['stats' => $stats])
    </div>

    @if($expenses->isEmpty() && !$currentCategory)
        <x-ui.empty-state
            title="No expenses yet"
            description="Start tracking your farm expenses above."
            icon="💰"
        />
    @else
        <section class="expenses__records" hx-indicator="#records-spinner">
            <h2 class="expenses__records-heading">Expense Records</h2>
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
