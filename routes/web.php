<?php

use App\Http\Controllers\AccountController;
use App\Http\Controllers\BatchEventController;
use App\Http\Controllers\CrmController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DeathRecordController;
use App\Http\Controllers\EggEntryController;
use App\Http\Controllers\ExpenseController;
use App\Http\Controllers\FeedInventoryController;
use App\Http\Controllers\FlockBatchController;
use App\Http\Controllers\FlockEventController;
use App\Http\Controllers\FlockProfileController;
use App\Http\Controllers\ImportController;
use App\Http\Controllers\SaleController;
use App\Http\Controllers\SalesReportController;
use App\Http\Controllers\SavingsController;
use App\Http\Controllers\SavingsPreferencesController;
use App\Http\Controllers\ViabilityController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
})->name('landing');

require __DIR__.'/auth.php';

Route::middleware(['auth'])->prefix('app')->name('app.')->group(function () {
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

    // Account Settings (free tier)
    Route::get('account', [AccountController::class, 'index'])->name('account.index');
    Route::patch('account/profile', [AccountController::class, 'updateProfile'])->name('account.update-profile');
    Route::patch('account/preferences', [AccountController::class, 'updatePreferences'])->name('account.update-preferences');
    Route::post('account/password-reset-link', [AccountController::class, 'sendPasswordResetLink'])->name('account.password-reset-link');
    Route::get('/dashboard/data', [DashboardController::class, 'data'])->name('dashboard.data');

    // Import data from original app
    Route::get('import', [ImportController::class, 'index'])->name('import.index');
    Route::post('import', [ImportController::class, 'store'])->name('import.store');

    Route::get('/components', function () {
        return view('app.components-showcase');
    })->name('components');

    Route::get('eggs/backfill-form', [EggEntryController::class, 'backfillForm'])->name('eggs.backfill-form');
    Route::post('eggs/backfill', [EggEntryController::class, 'backfill'])->name('eggs.backfill');
    Route::resource('eggs', EggEntryController::class)->except(['create', 'edit', 'show']);
    Route::get('eggs/{egg}/edit-form', [EggEntryController::class, 'editForm'])->name('eggs.edit-form');
    Route::get('eggs/{egg}/row', [EggEntryController::class, 'show'])->name('eggs.show-row');
    Route::get('eggs/{egg}/delete-confirm', [EggEntryController::class, 'deleteConfirm'])->name('eggs.delete-confirm');

    Route::middleware(['premium'])->group(function () {
        Route::get('crm', [CrmController::class, 'index'])->name('crm.index');

        Route::resource('expenses', ExpenseController::class)->except(['create', 'edit', 'show']);
        Route::get('expenses/{expense}/edit-form', [ExpenseController::class, 'editForm'])->name('expenses.edit-form');
        Route::get('expenses/{expense}/row', [ExpenseController::class, 'show'])->name('expenses.show-row');
        Route::get('expenses/{expense}/delete-confirm', [ExpenseController::class, 'deleteConfirm'])->name('expenses.delete-confirm');
        Route::get('expenses/stats', [ExpenseController::class, 'stats'])->name('expenses.stats');
        Route::get('expenses/cost-per-egg', [ExpenseController::class, 'costPerEgg'])->name('expenses.cost-per-egg');
        Route::get('expenses/category-items', [ExpenseController::class, 'categoryItems'])->name('expenses.category-items');

        Route::get('feed/stats', [FeedInventoryController::class, 'stats'])->name('feed.stats');
        Route::resource('feed', FeedInventoryController::class)->except(['create', 'edit', 'show']);
        Route::get('feed/{feed}/edit-form', [FeedInventoryController::class, 'editForm'])->name('feed.edit-form');
        Route::get('feed/{feed}/row', [FeedInventoryController::class, 'show'])->name('feed.show-row');
        Route::get('feed/{feed}/delete-confirm', [FeedInventoryController::class, 'deleteConfirm'])->name('feed.delete-confirm');
        Route::patch('feed/{feed}/deplete', [FeedInventoryController::class, 'markDepleted'])->name('feed.deplete');

        // Flock Batches detail-page actions (must come BEFORE resource to avoid show-route swallowing)
        Route::get('batches/{batch}/composition-modal', [FlockBatchController::class, 'compositionModal'])
            ->name('batches.composition-modal');
        Route::get('batches/{batch}/laying-date-modal', [FlockBatchController::class, 'layingDateModal'])
            ->name('batches.laying-date-modal');
        Route::patch('batches/{batch}/composition', [FlockBatchController::class, 'updateComposition'])
            ->name('batches.composition');
        Route::patch('batches/{batch}/laying-date', [FlockBatchController::class, 'updateLayingDate'])
            ->name('batches.laying-date');
        Route::get('batches/{batch}/deaths', [DeathRecordController::class, 'index'])
            ->name('batches.deaths.index');

        Route::resource('batches', FlockBatchController::class);
        Route::resource('batches.events', BatchEventController::class)
            ->only(['create', 'store', 'edit', 'update', 'destroy']);
        Route::resource('batches.deaths', DeathRecordController::class)
            ->only(['create', 'store', 'edit', 'update', 'destroy']);

        Route::resource('customers', CustomerController::class)->except(['create', 'edit', 'show']);
        Route::get('customers/{customer}/edit-form', [CustomerController::class, 'editForm'])->name('customers.edit-form');
        Route::get('customers/{customer}/row', [CustomerController::class, 'show'])->name('customers.show-row');

        Route::get('sales/reports', [SalesReportController::class, 'index'])->name('sales.reports');
        Route::resource('sales', SaleController::class)->except(['create', 'edit', 'show']);
        Route::get('sales/{sale}/edit-form', [SaleController::class, 'editForm'])->name('sales.edit-form');
        Route::get('sales/{sale}/row', [SaleController::class, 'show'])->name('sales.show-row');
        Route::patch('sales/{sale}/toggle-payment', [SaleController::class, 'togglePayment'])->name('sales.toggle-payment');

        Route::get('savings', [SavingsController::class, 'index'])->name('savings.index');
        Route::patch('savings/preferences', [SavingsPreferencesController::class, 'update'])->name('savings.preferences.update');
        Route::get('viability', [ViabilityController::class, 'index'])->name('viability.index');

        // Flock Profile
        Route::get('/flock', [FlockProfileController::class, 'index'])->name('flock.index');
        Route::put('/flock/{flockProfile}', [FlockProfileController::class, 'update'])->name('flock.update');

        // Flock Events (nested under profile)
        Route::get('/flock/{flockProfile}/events/create', [FlockEventController::class, 'create'])->name('flock.events.create');
        Route::post('/flock/{flockProfile}/events', [FlockEventController::class, 'store'])->name('flock.events.store');
        Route::get('/flock/{flockProfile}/events/{flockEvent}/edit', [FlockEventController::class, 'edit'])->name('flock.events.edit');
        Route::put('/flock/{flockProfile}/events/{flockEvent}', [FlockEventController::class, 'update'])->name('flock.events.update');
        Route::delete('/flock/{flockProfile}/events/{flockEvent}', [FlockEventController::class, 'destroy'])->name('flock.events.destroy');
    });
});
