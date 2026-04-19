# Components

## Blade Layout Components

**`<x-app-layout>`** — Main authenticated layout: sidebar navigation, top bar, user menu, flash messages, HTMX/Alpine includes.

**`<x-guest-layout>`** — Public/unauthenticated layout: landing page, login, register.

**`<x-sidebar>`** — Navigation menu with active state highlighting, tier-based feature visibility, responsive collapse.

## Reusable UI Blade Components

| Blade Component | Replaces React Component | Purpose |
|----------------|------------------------|---------|
| `<x-ui.stat-card>` | `StatCard` | Dashboard metric display with icon, value, label, trend |
| `<x-ui.progress-card>` | `ProgressCard` | Progress bar card for targets/goals |
| `<x-ui.empty-state>` | `EmptyState` | Friendly message when no data exists |
| `<x-ui.flash>` | N/A | Toast/flash message with auto-dismiss |
| `<x-ui.timeline>` | `EventTimeline` | Chronological event display |
| `<x-ui.chart>` | N/A | Chart.js canvas wrapper |
| `<x-forms.input>` | `TextInput` / `NumberInput` | Text/number input with error state |
| `<x-forms.select>` | `SelectInput` | Dropdown select with error state |
| `<x-forms.textarea>` | `TextareaInput` | Textarea with error state |
| `<x-forms.date-input>` | `DateInput` | Date picker input |
| `<x-forms.form-card>` | `FormCard` | Styled card wrapping a form |
| `<x-forms.form-group>` | `FormGroup` | Label + input + error message wrapper |
| `<x-forms.form-row>` | `FormRow` | Horizontal group of form fields |
| `<x-forms.submit-button>` | `SubmitButton` | Form submit button |
| `<x-tables.data-table>` | `DataTable` | Sortable table with optional HTMX pagination |
| `<x-tables.pagination>` | `Pagination` | Page navigation using Laravel's paginator |
| `<x-modals.modal>` | `FormModal` / `ConfirmDialog` | HTMX-powered modal dialog |
| `<x-modals.confirm-delete>` | `AlertDialog` | Delete confirmation with HTMX |
| `<x-layout.page-header>` | N/A | Page title and subtitle |
| `<x-layout.section>` | N/A | Content section wrapper |
| `<x-premium-gate>` | `PremiumFeatureGate` | Shows upgrade prompt for free-tier users |

## Controllers

| Controller | Feature | Tier | Key Actions |
|-----------|---------|------|-------------|
| `DashboardController` | Dashboard analytics | auth | `index`, `data` — aggregates data from all models |
| `EggEntryController` | Egg tracking | free | Full CRUD + pagination + `backfillForm`, `backfill`, `editForm`, `deleteConfirm` |
| `FlockProfileController` | Flock profile | premium | `index`, `store`, `update` |
| `FlockEventController` | Flock timeline | premium | `store`, `update`, `destroy` |
| `FlockBatchController` | Batch management | premium | Full CRUD + detail view + `compositionModal`, `layingDateModal`, `updateComposition`, `updateLayingDate` |
| `BatchEventController` | Batch events | premium | `store`, `update`, `destroy` (nested) |
| `DeathRecordController` | Mortality tracking | premium | `index`, `store`, `update`, `destroy` (nested) |
| `ExpenseController` | Expense tracking | premium | Full CRUD + pagination + stats |
| `FeedInventoryController` | Feed management | premium | Full CRUD + stats + depletion |
| `CustomerController` | CRM customers | premium | Full CRUD + search |
| `SaleController` | Sales tracking | premium | Full CRUD + pagination + payment toggle |
| `SalesReportController` | Sales analytics | premium | `index` — read-only aggregations |
| `CrmController` | CRM dashboard | premium | `index` — combined CRM overview |
| `SavingsController` | Financial analysis | premium | `index` — read-only calculations |
| `SavingsPreferencesController` | Savings settings | premium | `update` — savings display preferences |
| `ViabilityController` | Viability calc | premium | `index` — calculator page |
| `ImportController` | Data import | auth | `index`, `store` — CSV/data import |
| `AccountController` | User settings | auth | `index`, `updateProfile`, `updatePreferences`, `sendPasswordResetLink` |

## Service Classes

| Service | Responsibility | Key Interface |
|---------|---------------|---------------|
| `DashboardService` | Aggregates data across all models for dashboard | `getSummary(User): array` |
| `ReportService` | Sales report generation — totals by period, per-customer breakdowns | `getSalesReport(User, ?from, ?to): array` |
| `SavingsService` | Financial analysis — income vs expenses, profit margins, cost per egg | `getFinancialAnalysis(User): array` |
| `SavingsAnalysisService` | Advanced savings analysis with period comparison and lifetime impact | `analyze(User, SavingsPeriod): array` |
| `ViabilityService` | Viability calculations — cost/profit per bird, break-even | `calculate(User, array): array` |
| `EggStatsService` | Egg production statistics — weekly/monthly totals, averages, streaks | `getStats(User): array` |
| `ExpenseStatsService` | Expense analytics — category breakdowns, period comparisons | `getStats(User): array` |
| `FeedStatsService` | Feed tracking analytics — consumption rates, cost trends | `getStats(User): array` |
| `FlockBatchStatsService` | Flock data across batches — total birds, mortality, breed distribution | `overview(User): array` |
| `CrmReportsService` | CRM analytics — customer activity, sales summaries | `getReport(User): array` |
| `ImportDataService` | CSV/data import processing and validation | `import(User, array): array` |
| `SetupProgressService` | Tracks user onboarding/setup completion percentage | `getProgress(User): array` |

## Enums

| Enum | Values | Purpose |
|------|--------|--------|
| `BatchAgeAtAcquisition` | `Chick`, `Juvenile`, `Adult` | Age classification when birds were acquired |
| `BatchEventType` | `health_check`, `vaccination`, `relocation`, `breeding`, `laying_start`, `brooding_start`, `brooding_stop`, `production_note`, `flock_added`, `flock_loss`, `other` | Lifecycle event types for batches |
| `ChickenGoal` | Various goals | User's primary purpose for keeping chickens |
| `DeathCause` | `predator`, `disease`, `age`, `injury`, `unknown`, `culled`, `other` | Mortality cause classification |
| `ExpenseCategory` | `Birds`, `Feed`, `Equipment`, `Veterinary`, `Maintenance`, `Supplies`, `Start-up`, `Other` | Expense categorization (each has color code) |
| `FeedType` | Layer, Grower, Starter, Scratch, etc. | Feed product classification |

## Support Classes

| Class | Purpose |
|-------|---------|
| `Money` | Money formatting and arithmetic helpers |
| `SavingsPeriod` | Value object representing a date range for savings analysis |
| `WeekStart` | Week boundary calculation utility |

## Console Commands

| Command | Purpose |
|---------|---------|
| `ExpensesNormalizeCategories` | Normalizes legacy expense category values to enum format |
| `WarmupRoutes` | Pre-caches route definitions for performance |

## Middleware

| Middleware | Purpose |
|-----------|---------|
| `auth` (Breeze) | Ensures user is logged in |
| `EnsurePremiumTier` | Blocks free-tier users from premium routes |
| `DetectHtmx` | Sets `$request->isHtmx()` helper |

## Form Requests (Validation)

### Store Requests
| Form Request | Validates |
|-------------|-----------|
| `StoreEggEntryRequest` | date, count, size, color, notes |
| `StoreExpenseRequest` | date, category, description, amount |
| `StoreFlockProfileRequest` | farm_name, location, breed, hens, roosters, etc. |
| `StoreFlockBatchRequest` | batch_name, breed, acquisition_date, initial_count, type, age_at_acquisition, source, cost |
| `StoreSaleRequest` | customer_id, sale_date, dozen_count, individual_count, total_amount, paid |
| `StoreCustomerRequest` | name, phone, notes |
| `StoreDeathRecordRequest` | batch_id, date, count, cause, description |
| `StoreBatchEventRequest` | batch_id, date, type, description, affected_count |
| `StoreFlockEventRequest` | flock_profile_id, date, type, description, affected_birds |
| `StoreFeedInventoryRequest` | brand, feed_type, quantity, unit, opened_date, depleted_date, total_cost |

### Update Requests
| Form Request | Validates |
|-------------|-----------|
| `UpdateEggEntryRequest` | date, count, size, color, notes |
| `UpdateExpenseRequest` | date, category, description, amount |
| `UpdateFlockBatchRequest` | batch_name, breed, acquisition_date, initial_count, type, age_at_acquisition, source, cost |
| `UpdateSaleRequest` | customer_id, sale_date, dozen_count, individual_count, total_amount, paid |
| `UpdateCustomerRequest` | name, phone, notes |
| `UpdateFeedInventoryRequest` | brand, feed_type, quantity, unit, opened_date, depleted_date, total_cost |
| `UpdateCompositionRequest` | hens_count, roosters_count, chicks_count, brooding_count |
| `UpdateLayingDateRequest` | actual_laying_start_date |
| `UpdateProfileRequest` | name, email |
| `UpdatePreferencesRequest` | yearly_egg_goal, egg_price, chicken_goal |

### Specialized Requests
| Form Request | Validates |
|-------------|-----------|
| `BackfillEggEntriesRequest` | date range and counts for bulk egg entry |
| `ExpenseRequest` | Generic expense validation (shared store/update) |
| `FeedInventoryRequest` | Generic feed validation (shared store/update) |
| `SaleRequest` | Generic sale validation (shared store/update) |
| `SavingsFilterRequest` | Period and date range filters for savings analysis |
| `SavingsPreferencesRequest` | Savings display preference fields |
| `ImportDataRequest` | File upload and import format validation |

## Policies (Authorization)

One per model — all follow the same ownership pattern:

```php
class EggEntryPolicy
{
    public function view(User $user, EggEntry $entry): bool
    {
        return $user->id === $entry->user_id;
    }

    public function update(User $user, EggEntry $entry): bool
    {
        return $user->id === $entry->user_id;
    }

    public function delete(User $user, EggEntry $entry): bool
    {
        return $user->id === $entry->user_id;
    }
}
```

**Policies:** `EggEntryPolicy`, `FlockProfilePolicy`, `FlockEventPolicy`, `FlockBatchPolicy`, `BatchEventPolicy`, `DeathRecordPolicy`, `ExpensePolicy`, `FeedInventoryPolicy`, `CustomerPolicy`, `SalePolicy`

---
