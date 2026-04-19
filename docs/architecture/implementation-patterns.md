# Implementation Patterns

Established patterns extracted from Epics 1-4. Follow these exactly when implementing new CRUD features.

---

## 1. HTMX CRUD Controller Pattern

Every inline-editable resource controller follows this structure. Reference: `EggEntryController`, `ExpenseController`.

### Trait

```php
use App\Traits\HandlesHtmx;

class ExampleController extends Controller
{
    use HandlesHtmx;
}
```

`HandlesHtmx` provides:
- `$this->isHtmx($request)` — checks for `HX-Request` header
- `$this->htmxRedirect($url)` — returns `200` with `HX-Redirect` header
- `$this->htmxTrigger($event, $body)` — returns `200` with `HX-Trigger` header

### Action Signatures

| Action | Method | Purpose | Auth |
|--------|--------|---------|------|
| `index(Request)` | GET | List + paginate + optional filter | Via middleware |
| `store(StoreRequest)` | POST | Create via relationship | Via middleware |
| `show(Request, Model)` | GET | Return entry-row partial (cancel edit) | `Gate::authorize('view', $model)` |
| `editForm(Request, Model)` | GET | Return edit-form partial | `Gate::authorize('update', $model)` |
| `update(UpdateRequest, Model)` | PUT | Update model | `Gate::authorize('update', $model)` |
| `destroy(Request, Model)` | DELETE | Delete model | `Gate::authorize('delete', $model)` |

### Dual Response Pattern

Every mutating action must handle both HTMX and standard requests:

```php
public function store(StoreExampleRequest $request)
{
    $example = $request->user()->examples()->create($request->validated());

    if ($this->isHtmx($request)) {
        return view('examples.partials.entry-row', compact('example'));
    }

    return redirect()->route('app.examples.index')
        ->with('success', 'Example recorded.');
}
```

**Delete** returns empty body for HTMX (row removed client-side):

```php
if ($this->isHtmx($request)) {
    return response('', 200);
}
```

### Authorization

Use `Gate::authorize()`, **not** `$this->authorize()` — Laravel 12's base Controller does not include `AuthorizesRequests`.

```php
use Illuminate\Support\Facades\Gate;

Gate::authorize('update', $expense);
```

### Ownership Scoping (Critical Rule #1)

All queries MUST start from the authenticated user's relationship:

```php
$request->user()->expenses()->orderBy('date', 'desc');
```

Models are created through the relationship (never set `user_id` manually):

```php
$request->user()->expenses()->create($request->validated());
```

---

## 2. Route Registration Pattern

### Free-tier routes

Registered directly inside the `auth` middleware group:

```php
Route::middleware(['auth'])->prefix('app')->name('app.')->group(function () {
    Route::resource('eggs', EggEntryController::class)->except(['create', 'edit', 'show']);
    Route::get('eggs/{egg}/edit-form', [EggEntryController::class, 'editForm'])->name('eggs.edit-form');
    Route::get('eggs/{egg}/row', [EggEntryController::class, 'show'])->name('eggs.show-row');
});
```

### Premium-tier routes

Wrapped in the `premium` middleware group (alias for `EnsurePremiumTier`):

```php
Route::middleware(['premium'])->group(function () {
    Route::resource('expenses', ExpenseController::class)->except(['create', 'edit', 'show']);
    Route::get('expenses/{expense}/edit-form', [ExpenseController::class, 'editForm'])->name('expenses.edit-form');
    Route::get('expenses/{expense}/row', [ExpenseController::class, 'show'])->name('expenses.show-row');
});
```

### Standard route set for HTMX CRUD

For each resource with inline editing, register:

1. `Route::resource(...)` excluding `create`, `edit`, `show` (no separate pages for these)
2. `GET {resource}/{model}/edit-form` — returns inline edit partial
3. `GET {resource}/{model}/row` — returns entry-row partial (cancel edit restore)

### Route naming convention

All app routes use `app.` prefix: `app.expenses.index`, `app.expenses.store`, `app.expenses.edit-form`, `app.expenses.show-row`.

### Placeholder routes

Unimplemented features use closure placeholders so sidebar links render as `<a>` instead of `<span>`:

```php
Route::get('/customers', function () {
    return 'Customers placeholder';
})->name('customers.index');
```

---

## 3. Sidebar Navigation Pattern

The sidebar (`resources/views/components/layout/sidebar.blade.php`) uses a data-driven approach for premium links:

- Premium links are defined in a `$premiumLinks` array with `route`, `pattern`, `label`, and `icon` keys
- Each link checks `Route::has($link['route'])` — renders `<a>` if route exists, `<span aria-disabled="true">` if not
- Active state via `request()->routeIs($link['pattern'])` + `aria-current="page"`
- Premium section wrapped in `@if(auth()->user()->isPremium())`

**When adding a new feature:** No sidebar change needed if the route name is already in the `$premiumLinks` array. The link auto-activates when the placeholder route is replaced with a real controller.

Current premium links already registered: `flock`, `batches`, `expenses`, `feed`, `customers`, `sales`, `sales.reports`, `savings`, `viability`.

---

## 4. Model Pattern (Critical Rule #7 — Thin Models)

Models contain ONLY: `$fillable`, `$casts`, relationships, scopes.

```php
class Expense extends Model
{
    use HasFactory;

    protected $fillable = ['date', 'category', 'description', 'amount'];

    protected $casts = [
        'date' => 'date',
        'amount' => 'decimal:2',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
```

**Key rule:** `user_id` is NEVER in `$fillable`. Models are created via `$user->relationship()->create()`.

---

## 5. Policy Pattern (Critical Rule #8)

Simple ownership check. Relies on Laravel auto-discovery (no manual registration needed).

```php
class ExpensePolicy
{
    public function view(User $user, Expense $expense): bool
    {
        return $user->id === $expense->user_id;
    }

    public function update(User $user, Expense $expense): bool
    {
        return $user->id === $expense->user_id;
    }

    public function delete(User $user, Expense $expense): bool
    {
        return $user->id === $expense->user_id;
    }
}
```

---

## 6. Form Request Pattern (Critical Rule #3)

Validation lives ONLY in Form Requests, never in controllers.

```php
class StoreExpenseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Auth via middleware, ownership via relationship
    }

    public function rules(): array
    {
        return [
            'date' => ['required', 'date', 'before_or_equal:today'],
            'category' => ['required', 'in:feed,medical,equipment,housing,utilities,other'],
            'description' => ['required', 'string', 'max:500'],
            'amount' => ['required', 'numeric', 'min:0'],
        ];
    }
}
```

`authorize()` always returns `true` — authentication is handled by route middleware, ownership by the relationship pattern.

---

## 7. Migration Pattern

### SQLite compatibility

Tests run against SQLite in-memory (`phpunit.xml` sets `DB_CONNECTION=sqlite`). Named `ALTER TABLE ... ADD CONSTRAINT` statements fail on SQLite. Use a driver guard:

```php
$driver = Schema::getConnection()->getDriverName();

if ($driver === 'sqlite') {
    // Skip — validation layer enforces in test environments
} else {
    DB::statement('ALTER TABLE expenses ADD CONSTRAINT chk_expense_amount CHECK (amount >= 0)');
}
```

**Note:** Existing migrations for `flock_batches` and `death_records` do NOT have this guard and will fail if their tests touch the migration. The guard was introduced in Story 4.1.

### Index with DESC

```php
$table->index(['user_id', DB::raw('date DESC')], 'idx_expenses_user_date');
```

### Filename convention

Migrations follow `2026_04_07_NNNNNN_create_{table}_table.php`. Sequence numbers from `database-schema.md`:

| Seq | Table |
|-----|-------|
| 000001 | users |
| 000004 | egg_entries |
| 000006 | expenses |
| 000007 | flock_profiles |
| 000008 | flock_events |
| 000009 | flock_batches |
| 000010 | death_records |

---

## 8. Factory Pattern

```php
class ExpenseFactory extends Factory
{
    protected $model = Expense::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'date' => fake()->dateTimeBetween('-90 days', 'now')->format('Y-m-d'),
            // ... domain-specific fake data
        ];
    }
}
```

Key conventions:
- `user_id` defaults to `User::factory()` so factory works standalone
- Dates formatted as `Y-m-d` string (not Carbon object)
- Use `fake()->` helper (not `$this->faker`)

### UserFactory states

| State | Usage |
|-------|-------|
| `User::factory()->premium()` | Sets `tier` to `'premium'` |
| `User::factory()->admin()` | Sets `is_admin` to `true` |
| `User::factory()->withYearlyGoal(3000)` | Sets `yearly_egg_goal` |
| `User::factory()->create(['tier' => 'free'])` | Explicit free-tier |

---

## 9. Seeder Pattern

Premium-only seeders iterate over premium users:

```php
$premiumUsers = User::where('tier', 'premium')->get();

foreach ($premiumUsers as $user) {
    Expense::factory()->count(fake()->numberBetween(20, 30))->create([
        'user_id' => $user->id,
    ]);
}
```

Register in `DatabaseSeeder.php` after `UserSeeder` (users must exist first).

---

## 10. Blade View Pattern — HTMX Inline CRUD

### Directory structure

```
resources/views/{feature}/
├── index.blade.php              # Main page
└── partials/
    ├── entry-row.blade.php      # Single <tr> for display
    ├── edit-form.blade.php      # Single <tr> for inline edit
    └── table.blade.php          # <tbody> + pagination (HTMX page swap)
```

### index.blade.php structure

```blade
@extends('layouts.app')
@section('title', 'Feature Name')
@section('content')
<div class="feature-name">
    <x-layout.page-header title="Feature Title" />

    {{-- Optional: filter bar --}}

    <x-forms.form-card title="Add Item" :action="route('app.feature.store')"
        hx-post="{{ route('app.feature.store') }}"
        hx-target="#feature-entries-body"
        hx-swap="afterbegin"
        hx-on::after-request="if(event.detail.successful) this.reset()">
        {{-- Form fields using <x-forms.*> components --}}
    </x-forms.form-card>

    @if($items->isEmpty())
        <x-ui.empty-state title="No items yet" description="..." icon="..." />
    @else
        <div id="feature-table-container">
            @include('feature.partials.table', ['items' => $items])
        </div>
    @endif
</div>
@endsection
```

### entry-row.blade.php

```blade
<tr id="feature-{{ $item->id }}">
    <td class="data-table__cell">{{ $item->date->format('M d, Y') }}</td>
    {{-- ... data columns ... --}}
    <td class="data-table__cell feature__actions">
        <button class="btn btn--sm btn--secondary"
            hx-get="{{ route('app.feature.edit-form', $item) }}"
            hx-target="#feature-{{ $item->id }}"
            hx-swap="outerHTML"
            aria-label="Edit item">Edit</button>
        <button class="btn btn--sm btn--danger"
            hx-delete="{{ route('app.feature.destroy', $item) }}"
            hx-confirm="Delete this item?"
            hx-target="closest tr"
            hx-swap="outerHTML swap:500ms"
            aria-label="Delete item">Delete</button>
    </td>
</tr>
```

### edit-form.blade.php

```blade
<tr id="feature-{{ $item->id }}" class="feature__row--editing">
    <td class="data-table__cell">
        <input type="date" name="date" value="{{ $item->date->format('Y-m-d') }}" class="form-input" required>
    </td>
    {{-- ... editable columns with native inputs (not Blade components) ... --}}
    <td class="data-table__cell feature__actions">
        <button class="btn btn--sm btn--primary"
            hx-put="{{ route('app.feature.update', $item) }}"
            hx-include="closest tr"
            hx-target="closest tr"
            hx-swap="outerHTML">Save</button>
        <button class="btn btn--sm btn--secondary"
            hx-get="{{ route('app.feature.show-row', $item) }}"
            hx-target="closest tr"
            hx-swap="outerHTML">Cancel</button>
    </td>
</tr>
```

### table.blade.php

Wraps the `<table>`, `<thead>`, `<tbody>`, and pagination. Used for HTMX page swaps and filter refreshes.

### Key HTMX conventions

- CSRF is handled globally via `hx-headers` on `<body>` in the app layout — no per-form CSRF needed for HTMX
- Create form uses `hx-swap="afterbegin"` (new row at top of tbody)
- Delete uses `hx-swap="outerHTML swap:500ms"` (fade-out animation)
- Edit form uses `hx-include="closest tr"` to collect all inputs in the row
- Pagination uses HTMX when `page` param is present: `if ($this->isHtmx($request) && $request->has('page'))`

---

## 11. SCSS Pattern

### File location and naming

Feature SCSS: `resources/scss/features/_feature-name.scss`
Imported in `resources/scss/app.scss` as `@use 'features/feature-name';`

### BEM structure

```scss
.feature-name {
    display: flex;
    flex-direction: column;
    gap: 1.5rem;

    &__filter-bar { }
    &__filter { }
    &__filter--active { }
    &__form { }
    &__table { }
    &__row { }
    &__row--editing { }
    &__actions { }
    &__amount { }
}
```

Use CSS custom properties from `_variables.scss`: `var(--color-primary)`, `var(--color-surface)`, `var(--color-border)`, `var(--color-text-muted)`.

---

## 12. Testing Pattern

### Database

Tests use SQLite in-memory (`phpunit.xml`). All test classes that touch the DB use `RefreshDatabase` trait.

### Test file organization

| Type | Location | Base class | DB trait |
|------|----------|------------|----------|
| Unit (model) | `tests/Unit/ModelTest.php` | `Tests\TestCase` | `RefreshDatabase` |
| Unit (policy) | `tests/Unit/PolicyTest.php` | `PHPUnit\Framework\TestCase` | None |
| Feature | `tests/Feature/ControllerTest.php` | `Tests\TestCase` | `RefreshDatabase` |

### Policy unit tests (no DB)

Policies are pure logic — test without database by manually constructing models:

```php
private function makeUser(int $id): User
{
    $user = new User();
    $user->id = $id;
    return $user;
}

private function makeExpense(int $userId): Expense
{
    $expense = new Expense();
    $expense->user_id = $userId;
    return $expense;
}
```

### HTMX request simulation

```php
$this->actingAs($user)
    ->withHeaders(['HX-Request' => 'true'])
    ->post('/app/expenses', $data);
```

### Premium user in tests

```php
$user = User::factory()->premium()->create();
```

### Free-tier enforcement test

```php
public function test_free_user_cannot_access_feature(): void
{
    $user = User::factory()->create(['tier' => 'free']);
    $response = $this->actingAs($user)->get('/app/expenses');
    $response->assertRedirect(route('app.dashboard'));
}
```

### Test naming

`snake_case` with `test_` prefix: `test_premium_user_can_store_expense_via_htmx()`

### Run commands

```bash
php artisan test --compact --filter=Expense     # Feature-specific
php artisan test --compact                       # Full suite
```

---

## 13. Available Blade Components

### Form components (`<x-forms.*>`)

| Component | Key props |
|-----------|-----------|
| `<x-forms.form-card>` | `title`, `subtitle`, `action`, `method` + accepts HTMX attrs |
| `<x-forms.form-row>` | `:cols="2"` for grid layout |
| `<x-forms.input>` | `name`, `label`, `type`, `value`, `required`, `placeholder` + extra attrs |
| `<x-forms.date-input>` | `name`, `label`, `value`, `required`, `min`, `max` |
| `<x-forms.select>` | `name`, `label`, `:options="[...]"`, `required`, `placeholder` |
| `<x-forms.textarea>` | `name`, `label`, `:rows`, `placeholder` |
| `<x-forms.submit-button>` | — |

All form components auto-handle `@error` display with `aria-invalid` and `aria-describedby`.

### UI components (`<x-ui.*>`)

| Component | Key props |
|-----------|-----------|
| `<x-ui.empty-state>` | `title`, `description`, `icon`, `action`, `actionLabel` — has `role="status"` |
| `<x-ui.stat-card>` | `title`, `:total`, `label`, `icon`, `variant` |
| `<x-ui.comparison-card>` | `title`, `:before`, `:after` |
| `<x-ui.progress-card>` | `title`, `:value`, `:max`, `label`, `variant` |

### Layout components (`<x-layout.*>`)

| Component | Key props |
|-----------|-----------|
| `<x-layout.page-header>` | `title` + optional `actions` slot |

### Table components (`<x-tables.*>`)

| Component | Key props |
|-----------|-----------|
| `<x-tables.pagination>` | `:paginator` — renders pagination links when `hasPages()` |

---

## 14. Premium Tier Middleware

`EnsurePremiumTier` (aliased as `premium` in `bootstrap/app.php`):

- Passes through if `$request->user()->isPremium()` (tier is `'premium'` OR `is_admin` is true)
- HTMX requests: returns `partials.premium-gate` view
- Standard requests: redirects to `app.dashboard` with warning flash

`User::isPremium()` — returns `true` for premium tier OR admin users.
`User::isFree()` — returns `true` for free tier AND non-admin users.
