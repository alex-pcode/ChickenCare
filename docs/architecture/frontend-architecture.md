# Frontend Architecture

## Component Organization

```
resources/views/
├── layouts/
│   ├── app.blade.php                # Authenticated layout
│   └── guest.blade.php              # Public layout
├── components/
│   ├── ui/                          # stat-card, progress-card, empty-state, flash, timeline, chart,
│   │                                #   breadcrumbs, comparison-card, confirm-dialog, metric-display,
│   │                                #   summary-card, theme-toggle
│   ├── forms/                       # input, select, textarea, date-input, form-card, form-group, form-row, submit-button
│   ├── tables/                      # data-table, pagination
│   ├── modals/                      # modal, confirm-delete
│   ├── layout/                      # sidebar, navbar, page-header, section, mobile-dock
│   └── premium-gate.blade.php
├── dashboard/                       # index + partials/ (financial-overview, production-chart,
│                                    #   production-metrics, recent-activity, revenue-trend,
│                                    #   setup-progress, welcome-header)
├── eggs/                            # index + partials/ (backfill-modal, delete-confirm-modal,
│                                    #   duplicate-confirm, edit-form, entry-row, set-goal-cta, table)
├── flock/                           # index + partials/ (batch-promo, event-form, event-row,
│                                    #   flock-overview, migration-notice, overview-stats, timeline)
├── batches/                         # create, edit, index, show + partials/ (batches-table,
│                                    #   composition-modal, death-form, death-row, deaths-form,
│                                    #   deaths-history-table, deaths-section, event-form,
│                                    #   event-row, laying-date-modal, timeline-event-row)
├── expenses/                        # index + partials/ (banner-errors, banner-success,
│                                    #   breakdown-chart, category-summary, edit-form, entry-row,
│                                    #   hero, records-table, table)
├── feed/                            # index + partials/ (banner-errors, banner-success,
│                                    #   cost-calculator, cost-trends-chart, edit-form, entry-row,
│                                    #   hero, period-breakdown, records-table, stat-cards, table)
├── customers/                       # index + partials/ (edit-form, entry-row, table)
├── sales/                           # index, reports + partials/ (edit-form, entry-row,
│                                    #   report-results, table)
├── crm/                             # index + partials/
├── savings/                         # index + partials/ (analysis, custom-period,
│                                    #   financial-summary, hero, lifetime-impact, preferences)
├── viability/                       # index + partials/ (results)
├── import/                          # index
├── account/                         # index + partials/ (tab-billing, tab-goals,
│                                    #   tab-profile, tab-security)
├── app/                             # components-showcase, placeholder + showcase-tabs/
├── partials/                        # premium-gate
├── auth/                            # login, register, forgot-password, reset-password,
│                                    #   confirm-password, verify-email
└── welcome.blade.php                # Landing / root page
```

## SCSS Organization

```
resources/scss/
├── app.scss                          # Main entry — imports all partials
├── _variables.scss                   # Colors, spacing, typography, shadows
├── _mixins.scss                      # Neumorphic, card, form mixins
├── _base.scss                        # Reset, global typography
├── _layout.scss                      # App shell, sidebar, navbar, grid
├── _animations.scss                  # HTMX swap transitions, fade, slide
├── components/
│   ├── _badges.scss
│   ├── _buttons.scss
│   ├── _cards.scss
│   ├── _flash.scss
│   ├── _forms.scss
│   ├── _modals.scss
│   ├── _neumorphic.scss
│   ├── _pagination.scss
│   ├── _shiny-cta.scss
│   ├── _showcase.scss
│   ├── _tables.scss
│   └── _timeline.scss
└── features/
    ├── _account.scss
    ├── _auth.scss
    ├── _batches.scss
    ├── _crm.scss
    ├── _dashboard.scss
    ├── _egg-counter.scss
    ├── _expenses.scss
    ├── _feed.scss
    ├── _flock.scss
    ├── _import.scss
    ├── _sales.scss
    ├── _savings.scss
    └── _viability.scss
```

## Blade Component Pattern

```php
{{-- resources/views/components/forms/input.blade.php --}}
@props([
    'name',
    'label' => null,
    'type' => 'text',
    'value' => '',
    'required' => false,
    'placeholder' => '',
])

<div class="form-group @error($name) form-group--error @enderror">
    @if($label)
        <label for="{{ $name }}" class="form-label">
            {{ $label }}
            @if($required)<span class="form-label__required" aria-hidden="true">*</span>@endif
        </label>
    @endif

    <input
        type="{{ $type }}"
        id="{{ $name }}"
        name="{{ $name }}"
        value="{{ old($name, $value) }}"
        placeholder="{{ $placeholder }}"
        {{ $required ? 'required' : '' }}
        @error($name) aria-invalid="true" aria-describedby="{{ $name }}-error" @enderror
        {{ $attributes->merge(['class' => 'form-input']) }}
    >

    @error($name)
        <p class="form-error" id="{{ $name }}-error" role="alert">{{ $message }}</p>
    @enderror
</div>
```

## State Management

No client-side state management. The server is the single source of truth:

- **Page state** — Blade receives data from controller via `compact()`
- **Form state** — `old()` helper restores input on validation failure
- **Flash messages** — `session()->flash()` with auto-dismiss
- **Tab state** — URL query parameter `?tab=events`
- **Modal state** — Alpine.js `x-data` or HTMX loads content on demand
- **Filter/search state** — URL query parameters

## Routing

```php
// routes/web.php

Route::get('/', fn () => view('welcome'))->name('landing');

require __DIR__.'/auth.php';

Route::middleware(['auth'])->prefix('app')->name('app.')->group(function () {
    // Dashboard
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/dashboard/data', [DashboardController::class, 'data'])->name('dashboard.data');

    // Import
    Route::get('/import', [ImportController::class, 'index'])->name('import.index');
    Route::post('/import', [ImportController::class, 'store'])->name('import.store');

    // Eggs (free tier)
    Route::resource('eggs', EggEntryController::class)->except(['create', 'edit', 'show']);
    Route::get('eggs/backfill-form', [EggEntryController::class, 'backfillForm']);
    Route::post('eggs/backfill', [EggEntryController::class, 'backfill']);
    Route::get('eggs/{egg}/edit-form', [EggEntryController::class, 'editForm']);
    Route::get('eggs/{egg}/row', [EggEntryController::class, 'show']);
    Route::get('eggs/{egg}/delete-confirm', [EggEntryController::class, 'deleteConfirm']);

    // Account (free tier)
    Route::get('account', [AccountController::class, 'index'])->name('account.index');
    Route::patch('account/profile', [AccountController::class, 'updateProfile']);
    Route::patch('account/preferences', [AccountController::class, 'updatePreferences']);
    Route::post('account/password-reset-link', [AccountController::class, 'sendPasswordResetLink']);

    // Premium routes
    Route::middleware('premium')->group(function () {
        Route::get('crm', [CrmController::class, 'index'])->name('crm.index');
        Route::resource('expenses', ExpenseController::class)->except(['create', 'edit', 'show']);
        Route::resource('feed', FeedInventoryController::class)->except(['create', 'edit', 'show']);

        Route::resource('batches', FlockBatchController::class);
        Route::resource('batches.events', BatchEventController::class)->only(['store', 'update', 'destroy']);
        Route::resource('batches.deaths', DeathRecordController::class)->only(['store', 'update', 'destroy']);
        // Batch modals and inline updates
        Route::get('batches/{batch}/composition-modal', [FlockBatchController::class, 'compositionModal']);
        Route::patch('batches/{batch}/composition', [FlockBatchController::class, 'updateComposition']);
        Route::get('batches/{batch}/laying-date-modal', [FlockBatchController::class, 'layingDateModal']);
        Route::patch('batches/{batch}/laying-date', [FlockBatchController::class, 'updateLayingDate']);
        Route::get('batches/{batch}/deaths', [DeathRecordController::class, 'index']);

        Route::resource('customers', CustomerController::class)->except(['show']);
        Route::resource('sales', SaleController::class)->except(['create', 'edit', 'show']);
        Route::get('sales/reports', [SalesReportController::class, 'index'])->name('sales.reports');

        Route::get('savings', [SavingsController::class, 'index'])->name('savings.index');
        Route::patch('savings/preferences', [SavingsPreferencesController::class, 'update']);
        Route::get('viability', [ViabilityController::class, 'index'])->name('viability.index');

        // Flock profile and events
        Route::get('flock', [FlockProfileController::class, 'index'])->name('flock.index');
        Route::post('flock', [FlockProfileController::class, 'store'])->name('flock.store');
        Route::put('flock', [FlockProfileController::class, 'update'])->name('flock.update');
        Route::resource('flock/events', FlockEventController::class)
            ->only(['store', 'update', 'destroy'])->names('flock.events');
    });
});
```

## App Layout

```html
{{-- resources/views/layouts/app.blade.php --}}
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ config('app.name') }} — @yield('title', 'Dashboard')</title>
    @vite(['resources/scss/app.scss', 'resources/js/app.js'])
</head>
<body hx-boost="true" hx-headers='{"X-CSRF-TOKEN": "{{ csrf_token() }}"}'>
    <div class="app-layout">
        <x-layout.sidebar />
        <main class="app-layout__main">
            <x-layout.navbar />
            <x-ui.flash />
            <div class="app-layout__content" id="main-content">
                @yield('content')
            </div>
        </main>
    </div>
    <div id="modal-container" aria-live="polite"></div>
</body>
</html>
```

**`hx-boost="true"` on `<body>`** — all `<a href>` and `<form>` submissions within the layout are intercepted by HTMX and issued as AJAX `GET`/`POST` requests. HTMX then swaps the `<body>` content in place, so CSS, JS, fonts, and Alpine state are not re-parsed on navigation. Server-side routing and controllers are unchanged — the server still returns full HTML pages; `hx-boost` is transparent to PHP. Exempt individual links/forms with `hx-boost="false"` if they must full-reload (e.g. third-party redirects, file downloads).

## JavaScript Entry Point

```javascript
// resources/js/app.js
import htmx from 'htmx.org';
import Alpine from 'alpinejs';
import Chart from 'chart.js/auto';

window.htmx = htmx;
window.Alpine = Alpine;
window.Chart = Chart;

// Handle 422 validation errors in HTMX
document.body.addEventListener('htmx:beforeSwap', function(evt) {
    if (evt.detail.xhr.status === 422) {
        evt.detail.shouldSwap = true;
        evt.detail.isError = false;
    }
});

// Close modal after successful form submission
document.body.addEventListener('htmx:afterSwap', function(evt) {
    if (evt.detail.xhr.getResponseHeader('HX-Trigger') === 'closeModal') {
        document.getElementById('modal-container').innerHTML = '';
    }
});

// Handle session expiry
document.body.addEventListener('htmx:responseError', function(evt) {
    if (evt.detail.xhr.status === 419) {
        window.location.href = '/login';
    }
});

Alpine.start();
```

---
