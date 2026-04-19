# Story: Feed Inventory — Schema Migration, FeedType Enum & Model Updates

## User Story

As a developer,
I want the feed_inventory table schema and model to match the React data structure,
So that all subsequent stories can build on the correct foundation.

---

## Story Context

**Existing System Integration:**
- Integrates with: `app/Models/FeedInventory.php`, `database/migrations/2026_04_07_000005_create_feed_inventory_table.php`, `database/factories/FeedInventoryFactory.php`, `database/seeders/FeedInventorySeeder.php`, `app/Http/Requests/FeedInventoryRequest.php`, `app/Http/Requests/StoreFeedInventoryRequest.php`, `app/Http/Requests/UpdateFeedInventoryRequest.php`, `app/Http/Controllers/FeedInventoryController.php`
- Technology: Laravel 13, PHP 8.3, MariaDB 10.6.22, PHPUnit 12
- Follows pattern: PHP 8.3 string-backed enums with `label()` method (established by `App\Enums\ExpenseCategory` and `App\Enums\DeathCause`)
- Touch points: Schema rename of 3 columns and addition of 2 new columns, new `FeedType` enum, model method replacements, factory state replacements, form request rule changes, and all 51 existing feed tests updated to new field names

**Change Scope:**
- New migration to alter `feed_inventory` table: rename `name` → `brand`, `purchase_date` → `opened_date`, `expiry_date` → `depleted_date`; add `feed_type` and `batch_number` columns; update index
- New `App\Enums\FeedType` backed string enum with 3 cases
- `FeedInventory` model: updated `$fillable`, `$casts`; remove `isExpired()` and `isNearExpiry()`; add `isActive()`, `durationInDays()`, `markDepleted()`
- `FeedInventoryRequest` base rules rewritten for new fields
- `FeedInventoryFactory` updated with new field names, `FeedType` enum, `depleted()` and `active()` states replacing `expired()` and `nearExpiry()`
- `FeedInventorySeeder` updated to use new factory states
- `FeedInventoryController` column references updated (`name` → `brand`, `purchase_date` → `opened_date`)
- ALL existing test files updated to reference new field names and assertions
- Does NOT introduce the hero section, neumorphic form card, paginated table, cost calculator, or any view changes — those are Stories 2–5

---

## Acceptance Criteria

### Functional Requirements

#### Database Migration

1. **New migration** created via `php artisan make:migration alter_feed_inventory_rename_and_add_columns --table=feed_inventory --no-interaction`:
   - Rename `name` → `brand` (string 255, not null)
   - Add `feed_type` column: string, default `'Both'`, placed after `brand`
   - Rename `purchase_date` → `opened_date` (nullable date)
   - Rename `expiry_date` → `depleted_date` (nullable date)
   - Add `batch_number` column: nullable string 255, placed after `depleted_date`

2. **Existing data preserved** during migration — column renames carry data over automatically; `feed_type` defaults to `'Both'` for all existing rows; `batch_number` defaults to null.

3. **Index updated:**
   - Drop the existing `idx_feed_inventory_user` index (was `user_id, purchase_date DESC`)
   - Create new index: `(user_id, opened_date DESC)` named `idx_feed_inventory_user_opened`

4. **Migration is fully reversible** — the `down()` method:
   - Drop `batch_number` column
   - Rename `depleted_date` → `expiry_date`
   - Rename `opened_date` → `purchase_date`
   - Drop `feed_type` column
   - Rename `brand` → `name`
   - Restore original index `(user_id, purchase_date DESC)` named `idx_feed_inventory_user`

5. **MariaDB / SQLite compatibility:**
   - Use `$table->renameColumn()` for renames (Laravel 13 handles both drivers)
   - Use `$table->string('feed_type')->default('Both')` instead of `$table->enum()` for SQLite test compatibility; validation enforces allowed values at the application layer via `Rule::enum(FeedType::class)`

6. **CHECK constraint:**
   - Existing `chk_feed_quantity CHECK (quantity >= 0)` on MariaDB is preserved (not dropped or recreated)

#### FeedType Enum

7. **New file** `app/Enums/FeedType.php` — PHP 8.3 string-backed enum:
   - `BabyChicks = 'Baby chicks'`
   - `BigChicks = 'Big chicks'`
   - `Both = 'Both'`

8. **`label()` method** returns human-readable display text:
   - `BabyChicks` → `'Baby chicks'`
   - `BigChicks` → `'Big chicks'`
   - `Both` → `'Both'`

9. **Follows existing enum pattern** of `App\Enums\ExpenseCategory` and `App\Enums\DeathCause` — same namespace, same `label()` method signature.

#### Model Updates

10. **`$fillable` updated:**
    ```php
    protected $fillable = ['brand', 'feed_type', 'quantity', 'unit', 'opened_date', 'depleted_date', 'batch_number', 'total_cost'];
    ```

11. **`$casts` updated:**
    ```php
    protected $casts = [
        'opened_date' => 'date',
        'depleted_date' => 'date',
        'feed_type' => FeedType::class,
        'quantity' => 'decimal:2',
        'total_cost' => 'decimal:2',
    ];
    ```

12. **Remove methods:**
    - `isExpired(): bool` — removed entirely
    - `isNearExpiry(): bool` — removed entirely

13. **Add `isActive(): bool`:**
    - Returns `true` when `depleted_date` is `null` (feed bag is still in use)
    - Returns `false` when `depleted_date` is set

14. **Add `durationInDays(): ?int`:**
    - Returns `null` if `opened_date` is `null` or `depleted_date` is `null`
    - Returns integer number of days between `opened_date` and `depleted_date` (inclusive, using `diffInDays`)

15. **Add `markDepleted(): void`:**
    - Sets `depleted_date` to `today()` (Carbon)
    - Calls `$this->save()`
    - No-op guard: if `depleted_date` is already set, does nothing (idempotent)

#### Form Requests

16. **`FeedInventoryRequest` base rules** rewritten:
    ```php
    public function rules(): array
    {
        return [
            'brand' => ['required', 'string', 'max:255'],
            'feed_type' => ['required', Rule::enum(FeedType::class)],
            'quantity' => ['required', 'numeric', 'min:0.01'],
            'unit' => ['required', 'in:kg,lbs'],
            'total_cost' => ['required', 'numeric', 'min:0.01'],
            'opened_date' => ['nullable', 'date', 'before_or_equal:today'],
            'depleted_date' => ['nullable', 'date', 'after_or_equal:opened_date'],
            'batch_number' => ['nullable', 'string', 'max:255'],
        ];
    }
    ```

17. **Validation changes from old rules:**
    - `name` → `brand` (same constraints)
    - `feed_type`: NEW — required, must be a valid `FeedType` enum value
    - `quantity`: minimum changed from `0` → `0.01` (must be positive)
    - `total_cost`: changed from nullable to required, minimum changed from `0` → `0.01`
    - `purchase_date` → `opened_date` (same nullable date constraints)
    - `expiry_date` → `depleted_date`, `after_or_equal` reference updated to `opened_date`
    - `batch_number`: NEW — nullable string max 255

18. **`StoreFeedInventoryRequest` and `UpdateFeedInventoryRequest`** continue to extend `FeedInventoryRequest` with no overrides (unchanged structure).

#### Factory & Seeder

19. **`FeedInventoryFactory::definition()`** updated:
    - `name` → `brand`: random from updated brand list (`'Layer Pellets'`, `'Scratch Grains'`, `'Oyster Shell'`, `'Starter Crumble'`, `'Grower Mash'`, `'Mealworm Treats'`, `'Grit Mix'`, `'Sunflower Seeds'`)
    - `feed_type`: `fake()->randomElement(FeedType::cases())` (produces the enum instance)
    - `purchase_date` → `opened_date`
    - `expiry_date` → `depleted_date`: generated 7–60 days after `opened_date` (was 30–180 days after `purchase_date`), probability 0.5
    - `batch_number`: `fake()->optional(0.4)->regexify('[A-Z]-[0-9]{4}-[0-9]{2}')` (e.g., `B-2026-04`)
    - `total_cost`: changed from nullable (0.8 probability) to always present, range 10.00–100.00

20. **Factory states replaced:**
    - Remove `expired()` state
    - Remove `nearExpiry()` state
    - Add `depleted()` state: sets `depleted_date` to 7–30 days after `opened_date` (ensures `opened_date` is set)
    - Add `active()` state: sets `depleted_date` to `null`

21. **`FeedInventorySeeder`** updated:
    - Replace `FeedInventory::factory()->expired()` call with `FeedInventory::factory()->depleted()`
    - Replace `FeedInventory::factory()->nearExpiry()` call with `FeedInventory::factory()->active()`
    - Same distribution: `$count - 2` general entries, 1 depleted, 1 active

#### Controller Updates

22. **`FeedInventoryController@index`** — update `orderByRaw`:
    - Change `COALESCE(purchase_date, created_at)` → `COALESCE(opened_date, created_at)`

23. **No other controller changes required** for this story — store/update/destroy use `$request->validated()` which flows through automatically. View partial field references are updated in Story 2.

#### Tests

24. **ALL existing feed test files updated** to reference new field names:
    - `tests/Unit/FeedInventoryModelTest.php` (11 tests)
    - `tests/Unit/FeedInventoryPolicyTest.php` (6 tests — no field name changes needed, policy only checks `user_id`)
    - `tests/Feature/FeedInventoryControllerTest.php` (28 tests)
    - `tests/Feature/FeedInventoryDataLayerTest.php` (5 tests)
    - `tests/Feature/FeedInventoryEdgeCaseTest.php` (8 tests)

25. **Field name replacements in tests** (across all test files):
    - `'name'` → `'brand'` in factory creates, assertions, request payloads
    - `'purchase_date'` → `'opened_date'` in factory creates, assertions, request payloads
    - `'expiry_date'` → `'depleted_date'` in factory creates, assertions, request payloads
    - All `->expired()` factory calls → `->depleted()`
    - All `->nearExpiry()` factory calls → `->active()` or explicit `depleted_date` set
    - Add `'feed_type'` and `'total_cost'` to required field payloads in controller tests

26. **`FeedInventoryModelTest` — replace expiry tests with new model method tests:**
    - Remove: `test_feed_inventory_is_expired_returns_true_for_past_date`
    - Remove: `test_feed_inventory_is_expired_returns_false_for_future_date`
    - Remove: `test_feed_inventory_is_expired_returns_false_when_no_expiry_date`
    - Remove: `test_feed_inventory_is_near_expiry_returns_true_within_7_days`
    - Remove: `test_feed_inventory_is_near_expiry_returns_false_beyond_7_days`
    - Remove: `test_feed_inventory_is_near_expiry_returns_true_at_exactly_7_days`
    - Add: `test_feed_inventory_is_active_returns_true_when_depleted_date_null`
    - Add: `test_feed_inventory_is_active_returns_false_when_depleted_date_set`
    - Add: `test_feed_inventory_duration_in_days_returns_null_when_active`
    - Add: `test_feed_inventory_duration_in_days_returns_correct_count`
    - Add: `test_feed_inventory_duration_in_days_returns_null_when_no_opened_date`
    - Add: `test_feed_inventory_mark_depleted_sets_today`
    - Add: `test_feed_inventory_mark_depleted_is_idempotent`
    - Add: `test_feed_inventory_casts_feed_type_to_enum`

27. **`FeedInventoryModelTest` — update existing tests:**
    - `test_feed_inventory_fillable_attributes`: assert new `$fillable` array
    - `test_feed_inventory_casts_dates_to_carbon`: use `opened_date` / `depleted_date`

28. **`FeedInventoryDataLayerTest` — schema test updated:**
    - `test_feed_inventory_table_exists_with_correct_columns`: assert `brand`, `feed_type`, `opened_date`, `depleted_date`, `batch_number` instead of `name`, `purchase_date`, `expiry_date`
    - `test_feed_inventory_factory_creates_valid_model`: assert `$feed->brand` not null instead of `$feed->name`

29. **`FeedInventoryControllerTest` — payload updates:**
    - All `post('/app/feed', [...])` payloads: `'name'` → `'brand'`, add `'feed_type' => 'Both'`, add `'total_cost' => 45.99` where needed
    - All `put('/app/feed/{id}', [...])` payloads: `'name'` → `'brand'`, add `'feed_type' => 'Both'`, add `'total_cost' => 20.00` where needed
    - All `assertDatabaseHas` and `assertSee`: `'name'` → `'brand'`
    - Validation tests: `'name'` error key → `'brand'` error key; add `feed_type` and `total_cost` to required field validation
    - Remove: `test_index_shows_expired_feed_with_expired_class` and `test_index_shows_near_expiry_feed_with_warning_class` (view-layer tests will be rewritten in Story 2)
    - Remove: `test_store_validates_expiry_after_purchase` — replace with `test_store_validates_depleted_date_after_opened_date`

30. **`FeedInventoryEdgeCaseTest` — updates:**
    - `validFeed()` helper: `'name'` → `'brand'`, `'purchase_date'` → `'opened_date'`, `'expiry_date'` → `'depleted_date'`, add `'feed_type' => 'Both'`, add `'total_cost' => 25.00`
    - Remove: `test_feed_index_highlights_expired_entries` and `test_feed_index_highlights_near_expiry_entries` (view-layer tests rewritten in Story 2)
    - Remove: `test_feed_with_null_expiry_date_does_not_show_expiry_badge` (view-layer, Story 2)
    - Add: `test_feed_store_validates_feed_type_enum`
    - Add: `test_feed_store_validates_total_cost_required`
    - Add: `test_feed_store_accepts_valid_batch_number`
    - Add: `test_feed_store_rejects_batch_number_over_255_chars`

31. **New `FeedTypeEnumTest`** (`tests/Unit/FeedTypeEnumTest.php`):
    - `test_feed_type_enum_has_three_cases`
    - `test_feed_type_enum_values_match_expected_strings`
    - `test_feed_type_enum_label_returns_display_text`
    - `test_feed_type_enum_can_be_created_from_value`
    - `test_feed_type_enum_throws_on_invalid_value`

### Integration Requirements

1. **No view changes** are made in this story. Existing Blade templates (`feed/index.blade.php`, `feed/partials/entry-row.blade.php`, `feed/partials/edit-form.blade.php`, `feed/partials/table.blade.php`) will reference stale field names until Story 2 updates them. This is acceptable because Story 1 is a foundation story — the views will show errors if browsed directly, but no user-facing deploy occurs until Story 2 is complete.

2. **Existing routes remain unchanged** — `GET/POST/PUT/DELETE /app/feed` and `/app/feed/{feed}/edit-form` continue to work with the same URL structure.

3. **Policy unchanged** — `FeedInventoryPolicy` checks `user_id` only, no field name dependencies.

4. **`User::feedInventory()` relationship** unchanged — `HasMany` relationship uses `user_id` FK which is not affected.

5. **`FeedInventorySeeder`** must remain runnable for `php artisan db:seed` — updated states ensure compatibility.

6. **Run `vendor/bin/pint --dirty --format agent`** after all PHP file changes to ensure code style compliance.

### Quality Requirements

1. All existing feed inventory tests pass after updates (0 failures, 0 errors).
2. New enum tests pass.
3. New model method tests pass.
4. Migration can be rolled back cleanly: `php artisan migrate:rollback --step=1`.
5. No regressions in unrelated test suites — run `php artisan test --compact` to verify.
6. Factory generates valid models with new schema: `FeedInventory::factory()->create()` succeeds.
7. Seeder runs without errors: `php artisan db:seed --class=FeedInventorySeeder`.

---

## Technical Notes

### File Changes Summary

| File | Action | Description |
|------|--------|-------------|
| `database/migrations/xxxx_xx_xx_xxxxxx_alter_feed_inventory_rename_and_add_columns.php` | NEW | Alter migration: rename 3 columns, add 2 columns, update index |
| `app/Enums/FeedType.php` | NEW | PHP 8.3 string-backed enum with 3 cases + `label()` |
| `app/Models/FeedInventory.php` | MODIFY | Updated `$fillable`, `$casts`; remove `isExpired()`/`isNearExpiry()`; add `isActive()`/`durationInDays()`/`markDepleted()` |
| `app/Http/Requests/FeedInventoryRequest.php` | MODIFY | Base rules rewritten for new field names and constraints |
| `app/Http/Controllers/FeedInventoryController.php` | MODIFY | Update `orderByRaw` column reference |
| `database/factories/FeedInventoryFactory.php` | MODIFY | New field names, `FeedType` enum, `depleted()`/`active()` states |
| `database/seeders/FeedInventorySeeder.php` | MODIFY | Use `depleted()` and `active()` states |
| `tests/Unit/FeedTypeEnumTest.php` | NEW | 5 tests for FeedType enum |
| `tests/Unit/FeedInventoryModelTest.php` | MODIFY | Replace 6 expiry tests with 8 new model method tests; update 2 existing tests |
| `tests/Feature/FeedInventoryControllerTest.php` | MODIFY | Update all payloads and assertions to new field names; remove 3 view-layer tests; add 1 new validation test |
| `tests/Feature/FeedInventoryDataLayerTest.php` | MODIFY | Update schema assertions and factory assertions |
| `tests/Feature/FeedInventoryEdgeCaseTest.php` | MODIFY | Update `validFeed()` helper; remove 3 view-layer tests; add 4 new validation tests |

### Implementation Sketches

#### Migration

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('feed_inventory', function (Blueprint $table) {
            // Rename columns
            $table->renameColumn('name', 'brand');
            $table->renameColumn('purchase_date', 'opened_date');
            $table->renameColumn('expiry_date', 'depleted_date');
        });

        Schema::table('feed_inventory', function (Blueprint $table) {
            // Add new columns
            $table->string('feed_type')->default('Both')->after('brand');
            $table->string('batch_number', 255)->nullable()->after('depleted_date');

            // Update index
            $table->dropIndex('idx_feed_inventory_user');
            $table->index([DB::raw('user_id, opened_date DESC')], 'idx_feed_inventory_user_opened');
        });
    }

    public function down(): void
    {
        Schema::table('feed_inventory', function (Blueprint $table) {
            $table->dropColumn('batch_number');

            $table->dropIndex('idx_feed_inventory_user_opened');
        });

        Schema::table('feed_inventory', function (Blueprint $table) {
            $table->renameColumn('depleted_date', 'expiry_date');
            $table->renameColumn('opened_date', 'purchase_date');
        });

        Schema::table('feed_inventory', function (Blueprint $table) {
            $table->dropColumn('feed_type');
            $table->renameColumn('brand', 'name');

            $table->index([DB::raw('user_id, purchase_date DESC')], 'idx_feed_inventory_user');
        });
    }
};
```

> **Note:** Renames and adds are split into separate `Schema::table()` calls because SQLite (used in tests) does not support mixing renames with other column operations in a single call.

#### FeedType Enum

```php
<?php

namespace App\Enums;

enum FeedType: string
{
    case BabyChicks = 'Baby chicks';
    case BigChicks = 'Big chicks';
    case Both = 'Both';

    public function label(): string
    {
        return match ($this) {
            self::BabyChicks => 'Baby chicks',
            self::BigChicks => 'Big chicks',
            self::Both => 'Both',
        };
    }
}
```

#### Model (`FeedInventory.php`)

```php
<?php

namespace App\Models;

use App\Enums\FeedType;
use Database\Factories\FeedInventoryFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FeedInventory extends Model
{
    /** @use HasFactory<FeedInventoryFactory> */
    use HasFactory;

    protected $table = 'feed_inventory';

    protected $fillable = ['brand', 'feed_type', 'quantity', 'unit', 'opened_date', 'depleted_date', 'batch_number', 'total_cost'];

    protected $casts = [
        'opened_date' => 'date',
        'depleted_date' => 'date',
        'feed_type' => FeedType::class,
        'quantity' => 'decimal:2',
        'total_cost' => 'decimal:2',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function isActive(): bool
    {
        return $this->depleted_date === null;
    }

    public function durationInDays(): ?int
    {
        if ($this->opened_date === null || $this->depleted_date === null) {
            return null;
        }

        return (int) $this->opened_date->diffInDays($this->depleted_date);
    }

    public function markDepleted(): void
    {
        if ($this->depleted_date !== null) {
            return;
        }

        $this->depleted_date = now()->toDateString();
        $this->save();
    }
}
```

#### Form Request (`FeedInventoryRequest.php`)

```php
<?php

namespace App\Http\Requests;

use App\Enums\FeedType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

abstract class FeedInventoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, array<mixed>> */
    public function rules(): array
    {
        return [
            'brand' => ['required', 'string', 'max:255'],
            'feed_type' => ['required', Rule::enum(FeedType::class)],
            'quantity' => ['required', 'numeric', 'min:0.01'],
            'unit' => ['required', 'in:kg,lbs'],
            'total_cost' => ['required', 'numeric', 'min:0.01'],
            'opened_date' => ['nullable', 'date', 'before_or_equal:today'],
            'depleted_date' => ['nullable', 'date', 'after_or_equal:opened_date'],
            'batch_number' => ['nullable', 'string', 'max:255'],
        ];
    }
}
```

#### Factory (`FeedInventoryFactory.php`)

```php
<?php

namespace Database\Factories;

use App\Enums\FeedType;
use App\Models\FeedInventory;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<FeedInventory>
 */
class FeedInventoryFactory extends Factory
{
    protected $model = FeedInventory::class;

    /** @var list<string> */
    private static array $feedBrands = [
        'Layer Pellets',
        'Scratch Grains',
        'Oyster Shell',
        'Starter Crumble',
        'Grower Mash',
        'Mealworm Treats',
        'Grit Mix',
        'Sunflower Seeds',
    ];

    public function definition(): array
    {
        $openedDate = fake()->optional(0.8)->dateTimeBetween('-60 days', 'now');

        return [
            'user_id' => User::factory(),
            'brand' => fake()->randomElement(self::$feedBrands),
            'feed_type' => fake()->randomElement(FeedType::cases()),
            'quantity' => fake()->randomFloat(2, 5.00, 50.00),
            'unit' => fake()->randomElement(['kg', 'lbs']),
            'opened_date' => $openedDate?->format('Y-m-d'),
            'depleted_date' => $openedDate
                ? fake()->optional(0.5)->dateTimeBetween($openedDate->format('Y-m-d') . ' +7 days', $openedDate->format('Y-m-d') . ' +60 days')?->format('Y-m-d')
                : null,
            'batch_number' => fake()->optional(0.4)->regexify('[A-Z]-[0-9]{4}-[0-9]{2}'),
            'total_cost' => fake()->randomFloat(2, 10.00, 100.00),
        ];
    }

    public function depleted(): static
    {
        return $this->state(function (array $attributes) {
            $openedDate = $attributes['opened_date']
                ? \Carbon\Carbon::parse($attributes['opened_date'])
                : now()->subDays(fake()->numberBetween(14, 45));

            return [
                'opened_date' => $openedDate->format('Y-m-d'),
                'depleted_date' => $openedDate->copy()->addDays(fake()->numberBetween(7, 30))->format('Y-m-d'),
            ];
        });
    }

    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'depleted_date' => null,
        ]);
    }
}
```

#### Seeder (`FeedInventorySeeder.php`)

```php
<?php

namespace Database\Seeders;

use App\Models\FeedInventory;
use App\Models\User;
use Illuminate\Database\Seeder;

class FeedInventorySeeder extends Seeder
{
    public function run(): void
    {
        $premiumUsers = User::where('tier', 'premium')->get();

        foreach ($premiumUsers as $user) {
            $count = fake()->numberBetween(5, 8);

            FeedInventory::factory()->count($count - 2)->create([
                'user_id' => $user->id,
            ]);

            FeedInventory::factory()->depleted()->create([
                'user_id' => $user->id,
            ]);

            FeedInventory::factory()->active()->create([
                'user_id' => $user->id,
            ]);
        }
    }
}
```

#### Controller Adjustment (`FeedInventoryController.php`)

Only the `index` method `orderByRaw` changes:

```php
public function index(Request $request)
{
    $feeds = $request->user()->feedInventory()
        ->orderByRaw('COALESCE(opened_date, created_at) DESC')
        ->paginate(15);

    if ($this->isHtmx($request)) {
        return view('feed.partials.table', compact('feeds'));
    }

    return view('feed.index', compact('feeds'));
}
```

#### FeedType Enum Test (`tests/Unit/FeedTypeEnumTest.php`)

```php
<?php

namespace Tests\Unit;

use App\Enums\FeedType;
use PHPUnit\Framework\TestCase;

class FeedTypeEnumTest extends TestCase
{
    public function test_feed_type_enum_has_three_cases(): void
    {
        $this->assertCount(3, FeedType::cases());
    }

    public function test_feed_type_enum_values_match_expected_strings(): void
    {
        $this->assertSame('Baby chicks', FeedType::BabyChicks->value);
        $this->assertSame('Big chicks', FeedType::BigChicks->value);
        $this->assertSame('Both', FeedType::Both->value);
    }

    public function test_feed_type_enum_label_returns_display_text(): void
    {
        $this->assertSame('Baby chicks', FeedType::BabyChicks->label());
        $this->assertSame('Big chicks', FeedType::BigChicks->label());
        $this->assertSame('Both', FeedType::Both->label());
    }

    public function test_feed_type_enum_can_be_created_from_value(): void
    {
        $this->assertSame(FeedType::BabyChicks, FeedType::from('Baby chicks'));
        $this->assertSame(FeedType::BigChicks, FeedType::from('Big chicks'));
        $this->assertSame(FeedType::Both, FeedType::from('Both'));
    }

    public function test_feed_type_enum_throws_on_invalid_value(): void
    {
        $this->expectException(\ValueError::class);
        FeedType::from('invalid');
    }
}
```

### Test Execution Plan

Run tests in this order during implementation:

1. `php artisan test --compact tests/Unit/FeedTypeEnumTest.php` — verify enum
2. `php artisan test --compact tests/Unit/FeedInventoryModelTest.php` — verify model methods
3. `php artisan test --compact tests/Feature/FeedInventoryDataLayerTest.php` — verify schema
4. `php artisan test --compact tests/Feature/FeedInventoryControllerTest.php` — verify CRUD
5. `php artisan test --compact tests/Feature/FeedInventoryEdgeCaseTest.php` — verify edge cases
6. `php artisan test --compact` — full regression

### Deployment Notes

1. Run migration: `php artisan migrate`
2. Verify rollback: `php artisan migrate:rollback --step=1` then re-run `php artisan migrate`
3. Run seeder on fresh environments: `php artisan db:seed --class=FeedInventorySeeder`
4. No user-facing changes — views are updated in Story 2

---

## Change Log

| Date | Version | Description | Author |
|------|---------|-------------|--------|
| 2026-04-18 | 1.0 | Initial draft | AI (Dev) |
