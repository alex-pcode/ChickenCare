# Story: Auto-Expense Creation & Integration (Feed Epic — Story 4)

## User Story

**As a** user,
**I want** feed purchases to automatically create matching expense records,
**so that** my expense tracking stays in sync without double-entry.

## Story Context

The React reference application automatically creates, updates, and deletes matching Expense records whenever a feed entry is created, modified, or removed. This story replicates that behavior in the Laravel backend. The integration must be resilient — expense-side failures must never block feed operations.

### Existing System State (after Story 1)

- **FeedInventory model:** `brand`, `feed_type` (FeedType enum), `quantity`, `unit`, `opened_date`, `depleted_date`, `batch_number`, `total_cost`
- **Expense model:** `user_id`, `date`, `category` (string matching ExpenseCategory values), `description`, `amount`
- **ExpenseCategory enum:** has `Feed` case with value `'Feed'` and color `#2A2580`
- **FeedInventoryController:** `store()`, `update()`, `destroy()` using `StoreFeedInventoryRequest` / `UpdateFeedInventoryRequest`
- **ExpenseController:** `store()`, `update()`, `destroy()` — independent CRUD, not linked to feed
- **FeedInventory migration:** `feed_inventory` table — no `expense_id` column yet
- **Expenses migration:** `expenses` table with `id`, `user_id`, `date`, `category`, `description`, `amount`

### React Reference Behavior

```typescript
// On feed create — auto-create expense:
const newExpense: Expense = {
  category: 'Feed',
  description: `${brand} ${type} (${quantity} ${unit})`,
  amount: total_cost,
  date: openedDate
};
await addExpense(newExpense);

// On feed delete — linked expense also deleted
// On feed update (cost/details change) — linked expense updated
```

---

## Acceptance Criteria

### Functional Requirements

#### Auto-Expense on Feed Creation

1. When a feed entry is stored via `FeedInventoryController@store`, an Expense record is automatically created in the same database transaction.
2. The auto-created Expense has `category` set to `'Feed'` (matching `ExpenseCategory::Feed->value`).
3. The Expense `description` follows the format `"{brand} {feed_type} ({quantity} {unit})"` — e.g., `"Layer Pellets Big chicks (25.00 kg)"`.
4. The Expense `amount` equals the feed entry's `total_cost`.
5. The Expense `date` equals the feed entry's `opened_date`, falling back to `today` if `opened_date` is null.
6. The Expense `user_id` matches the feed entry's `user_id`.
7. The feed entry's `expense_id` column stores the ID of the created Expense record.
8. If `total_cost` is null or zero, no Expense record is created (skip silently).

#### Auto-Expense on Feed Update

9. When `total_cost`, `brand`, `feed_type`, `quantity`, or `unit` is updated on a feed entry, the linked Expense record (via `expense_id`) is updated with the recalculated `description`, `amount`, and `date`.
10. If the feed entry has no linked expense (`expense_id` is null — legacy data), the update skips expense sync silently without error.
11. If `total_cost` is changed to null or zero and a linked expense exists, the linked expense is deleted and `expense_id` is set to null.
12. If `total_cost` is changed from null/zero to a positive value and no linked expense exists, a new expense is created and linked.

#### Auto-Expense on Feed Delete

13. When a feed entry is deleted via `FeedInventoryController@destroy`, the linked Expense record is also deleted.
14. If the feed entry has no linked expense, deletion proceeds without error.

#### Migration

15. A new migration adds an `expense_id` nullable unsigned big integer column to the `feed_inventory` table.
16. The column has a foreign key constraint referencing `expenses(id)` with `SET NULL` on delete.
17. The migration is reversible (down method drops the column).

#### Model Updates

18. `FeedInventory` model gains an `expense()` BelongsTo relationship returning `$this->belongsTo(Expense::class)`.
19. `expense_id` is added to `FeedInventory::$fillable`.

#### ExpenseCategory Enum

20. Verify `ExpenseCategory::Feed` case exists (it does — value `'Feed'`, color `#2A2580`). No changes needed.

### Integration Requirements

21. The expenses index page (`/app/expenses`) continues to display feed-linked expenses identically to manually-created expenses — no visual distinction.
22. Manually deleting a feed-linked expense from the expenses page sets the feed's `expense_id` to null (enforced by the `SET NULL` FK constraint) without deleting the feed entry.
23. The `ExpenseStatsService` category breakdown naturally includes auto-created feed expenses without any code changes.
24. After a feed entry is created/updated/deleted, any HTMX response that triggers `expenses:changed` events is **not** emitted (the feed page does not need to refresh expense partials in real-time; the user sees updated expenses when they navigate to the expenses page).

### Quality Requirements

25. If the auto-expense creation fails (e.g., database constraint violation on expenses table), the feed entry still saves successfully. A warning is logged via `Log::warning()`.
26. If the auto-expense update fails, the feed update still succeeds. A warning is logged.
27. If the auto-expense delete fails, the feed delete still succeeds. A warning is logged.
28. All auto-expense logic is wrapped in a try-catch around the expense portion only — the feed operation itself is never rolled back due to expense failure.

---

## Technical Notes

### Approach: Controller-Level Logic (not Observer)

The auto-expense sync is implemented directly in `FeedInventoryController` methods rather than via an Eloquent Observer. Rationale:

- The logic needs access to the authenticated user (`$request->user()`), which Observers don't receive.
- Controller-level keeps the sync explicit and testable without hidden side-effects.
- Matches the existing pattern in the codebase where controllers contain business logic (no service layer for simple CRUD sync).

A private helper method `syncExpense(FeedInventory $feed, User $user)` on the controller centralizes the create/update logic.

### File Changes Summary

| File | Action | Description |
|---|---|---|
| `database/migrations/xxxx_add_expense_id_to_feed_inventory_table.php` | **Create** | Add `expense_id` nullable FK column |
| `app/Models/FeedInventory.php` | **Edit** | Add `expense_id` to `$fillable`, add `expense()` BelongsTo |
| `app/Http/Controllers/FeedInventoryController.php` | **Edit** | Add auto-expense logic to `store()`, `update()`, `destroy()` |
| `tests/Feature/FeedAutoExpenseTest.php` | **Create** | Tests for auto-expense create/update/delete/failure scenarios |

### Implementation Sketches

#### Migration: `add_expense_id_to_feed_inventory_table`

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('feed_inventory', function (Blueprint $table) {
            $table->foreignId('expense_id')
                ->nullable()
                ->after('total_cost')
                ->constrained('expenses')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('feed_inventory', function (Blueprint $table) {
            $table->dropConstrainedForeignId('expense_id');
        });
    }
};
```

#### Model: `FeedInventory` — Changes

```php
// Add to $fillable array:
'expense_id'

// Add relationship:
public function expense(): BelongsTo
{
    return $this->belongsTo(Expense::class);
}
```

#### Controller: `FeedInventoryController` — Updated Methods

```php
use App\Enums\ExpenseCategory;
use App\Models\Expense;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

// --- Private helper ---

private function buildExpenseDescription(FeedInventory $feed): string
{
    return sprintf(
        '%s %s (%s %s)',
        $feed->brand,
        $feed->feed_type->value,
        number_format((float) $feed->quantity, 2),
        $feed->unit
    );
}

private function syncExpense(FeedInventory $feed, \App\Models\User $user): void
{
    $hasCost = $feed->total_cost !== null && (float) $feed->total_cost > 0;

    // If no cost, clean up any existing linked expense
    if (! $hasCost) {
        if ($feed->expense_id && $feed->expense) {
            $feed->expense->delete();
            $feed->update(['expense_id' => null]);
        }
        return;
    }

    $expenseData = [
        'category' => ExpenseCategory::Feed->value,
        'description' => $this->buildExpenseDescription($feed),
        'amount' => $feed->total_cost,
        'date' => $feed->opened_date ?? now()->toDateString(),
    ];

    if ($feed->expense_id && $feed->expense) {
        // Update existing linked expense
        $feed->expense->update($expenseData);
    } else {
        // Create new expense and link it
        $expense = $user->expenses()->create($expenseData);
        $feed->update(['expense_id' => $expense->id]);
    }
}

// --- store() ---

public function store(StoreFeedInventoryRequest $request)
{
    $feed = $request->user()->feedInventory()->create($request->validated());

    try {
        $this->syncExpense($feed, $request->user());
    } catch (\Throwable $e) {
        Log::warning('Auto-expense creation failed for feed entry', [
            'feed_id' => $feed->id,
            'error' => $e->getMessage(),
        ]);
    }

    if ($this->isHtmx($request)) {
        return view('feed.partials.entry-row', compact('feed'));
    }

    return redirect()->route('app.feed.index')
        ->with('success', 'Feed entry recorded.');
}

// --- update() ---

public function update(UpdateFeedInventoryRequest $request, FeedInventory $feed)
{
    Gate::authorize('update', $feed);
    $feed->update($request->validated());

    try {
        $this->syncExpense($feed->fresh(), $request->user());
    } catch (\Throwable $e) {
        Log::warning('Auto-expense update failed for feed entry', [
            'feed_id' => $feed->id,
            'error' => $e->getMessage(),
        ]);
    }

    if ($this->isHtmx($request)) {
        return view('feed.partials.entry-row', compact('feed'));
    }

    return redirect()->route('app.feed.index')
        ->with('success', 'Feed entry updated.');
}

// --- destroy() ---

public function destroy(Request $request, FeedInventory $feed)
{
    Gate::authorize('delete', $feed);

    try {
        if ($feed->expense_id && $feed->expense) {
            $feed->expense->delete();
        }
    } catch (\Throwable $e) {
        Log::warning('Auto-expense delete failed for feed entry', [
            'feed_id' => $feed->id,
            'error' => $e->getMessage(),
        ]);
    }

    $feed->delete();

    if ($this->isHtmx($request)) {
        return response('', 200);
    }

    return redirect()->route('app.feed.index')
        ->with('success', 'Feed entry deleted.');
}
```

#### Tests: `tests/Feature/FeedAutoExpenseTest.php`

```php
<?php

namespace Tests\Feature;

use App\Enums\ExpenseCategory;
use App\Enums\FeedType;
use App\Models\Expense;
use App\Models\FeedInventory;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class FeedAutoExpenseTest extends TestCase
{
    use LazilyRefreshDatabase;

    // --- Creation Tests ---

    public function test_creating_feed_creates_linked_expense(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post(route('app.feed.store'), [
            'brand' => 'Layer Pellets',
            'feed_type' => FeedType::BigChicks->value,
            'quantity' => 25.00,
            'unit' => 'kg',
            'total_cost' => 45.50,
            'opened_date' => '2026-04-10',
        ]);

        $feed = FeedInventory::where('user_id', $user->id)->first();
        $this->assertNotNull($feed);
        $this->assertNotNull($feed->expense_id);

        $expense = Expense::find($feed->expense_id);
        $this->assertNotNull($expense);
        $this->assertEquals(ExpenseCategory::Feed->value, $expense->category);
        $this->assertEquals('Layer Pellets Big chicks (25.00 kg)', $expense->description);
        $this->assertEquals(45.50, (float) $expense->amount);
        $this->assertEquals('2026-04-10', $expense->date->toDateString());
        $this->assertEquals($user->id, $expense->user_id);
    }

    public function test_creating_feed_without_cost_skips_expense(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->post(route('app.feed.store'), [
            'brand' => 'Scratch Grains',
            'feed_type' => FeedType::Both->value,
            'quantity' => 10.00,
            'unit' => 'lbs',
            'total_cost' => 0,
            'opened_date' => '2026-04-10',
        ]);

        $feed = FeedInventory::where('user_id', $user->id)->first();
        $this->assertNotNull($feed);
        $this->assertNull($feed->expense_id);
        $this->assertDatabaseCount('expenses', 0);
    }

    public function test_creating_feed_with_null_opened_date_uses_today(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->post(route('app.feed.store'), [
            'brand' => 'Layer Pellets',
            'feed_type' => FeedType::BigChicks->value,
            'quantity' => 25.00,
            'unit' => 'kg',
            'total_cost' => 45.50,
            'opened_date' => null,
        ]);

        $feed = FeedInventory::where('user_id', $user->id)->first();
        $expense = Expense::find($feed->expense_id);
        $this->assertEquals(now()->toDateString(), $expense->date->toDateString());
    }

    // --- Update Tests ---

    public function test_updating_feed_updates_linked_expense(): void
    {
        $user = User::factory()->create();
        $expense = Expense::factory()->create([
            'user_id' => $user->id,
            'category' => ExpenseCategory::Feed->value,
            'amount' => 45.50,
        ]);
        $feed = FeedInventory::factory()->create([
            'user_id' => $user->id,
            'expense_id' => $expense->id,
            'brand' => 'Layer Pellets',
            'feed_type' => FeedType::BigChicks,
            'total_cost' => 45.50,
        ]);

        $this->actingAs($user)->put(route('app.feed.update', $feed), [
            'brand' => 'Starter Crumble',
            'feed_type' => FeedType::BabyChicks->value,
            'quantity' => 30.00,
            'unit' => 'lbs',
            'total_cost' => 60.00,
            'opened_date' => '2026-04-12',
        ]);

        $expense->refresh();
        $this->assertEquals('Starter Crumble Baby chicks (30.00 lbs)', $expense->description);
        $this->assertEquals(60.00, (float) $expense->amount);
        $this->assertEquals('2026-04-12', $expense->date->toDateString());
    }

    public function test_updating_feed_without_linked_expense_does_not_error(): void
    {
        $user = User::factory()->create();
        $feed = FeedInventory::factory()->create([
            'user_id' => $user->id,
            'expense_id' => null,
            'total_cost' => 45.50,
        ]);

        $response = $this->actingAs($user)->put(route('app.feed.update', $feed), [
            'brand' => 'Starter Crumble',
            'feed_type' => FeedType::BabyChicks->value,
            'quantity' => 30.00,
            'unit' => 'lbs',
            'total_cost' => 60.00,
            'opened_date' => '2026-04-12',
        ]);

        // Feed updates, new expense created and linked
        $feed->refresh();
        $this->assertNotNull($feed->expense_id);
        $this->assertDatabaseCount('expenses', 1);
    }

    public function test_updating_feed_cost_to_zero_deletes_linked_expense(): void
    {
        $user = User::factory()->create();
        $expense = Expense::factory()->create([
            'user_id' => $user->id,
            'category' => ExpenseCategory::Feed->value,
        ]);
        $feed = FeedInventory::factory()->create([
            'user_id' => $user->id,
            'expense_id' => $expense->id,
            'total_cost' => 45.50,
        ]);

        $this->actingAs($user)->put(route('app.feed.update', $feed), [
            'brand' => 'Layer Pellets',
            'feed_type' => FeedType::BigChicks->value,
            'quantity' => 25.00,
            'unit' => 'kg',
            'total_cost' => 0,
            'opened_date' => '2026-04-10',
        ]);

        $feed->refresh();
        $this->assertNull($feed->expense_id);
        $this->assertDatabaseMissing('expenses', ['id' => $expense->id]);
    }

    // --- Delete Tests ---

    public function test_deleting_feed_deletes_linked_expense(): void
    {
        $user = User::factory()->create();
        $expense = Expense::factory()->create([
            'user_id' => $user->id,
            'category' => ExpenseCategory::Feed->value,
        ]);
        $feed = FeedInventory::factory()->create([
            'user_id' => $user->id,
            'expense_id' => $expense->id,
        ]);

        $this->actingAs($user)->delete(route('app.feed.destroy', $feed));

        $this->assertDatabaseMissing('feed_inventory', ['id' => $feed->id]);
        $this->assertDatabaseMissing('expenses', ['id' => $expense->id]);
    }

    public function test_deleting_feed_without_linked_expense_succeeds(): void
    {
        $user = User::factory()->create();
        $feed = FeedInventory::factory()->create([
            'user_id' => $user->id,
            'expense_id' => null,
        ]);

        $this->actingAs($user)->delete(route('app.feed.destroy', $feed));

        $this->assertDatabaseMissing('feed_inventory', ['id' => $feed->id]);
    }

    // --- Resilience Tests ---

    public function test_expense_failure_does_not_block_feed_creation(): void
    {
        $user = User::factory()->create();

        // Simulate expense creation failure by temporarily breaking the expenses table
        // Use Log::fake() to verify warning is logged
        \Illuminate\Support\Facades\Log::shouldReceive('warning')
            ->once()
            ->withArgs(fn ($msg) => str_contains($msg, 'Auto-expense creation failed'));

        // Mock Expense::create to throw — or use a DB constraint violation.
        // One approach: override the expenses table temporarily.
        // Simpler: test that feed is saved even if we mock the expense path.
        // Implementation detail left to developer; key assertion below.

        // The feed entry must exist regardless of expense failure.
        $feed = FeedInventory::where('user_id', $user->id)->first();
        // ... assertion that feed exists
    }

    // --- FK Constraint Tests ---

    public function test_deleting_expense_directly_nullifies_feed_expense_id(): void
    {
        $user = User::factory()->create();
        $expense = Expense::factory()->create([
            'user_id' => $user->id,
            'category' => ExpenseCategory::Feed->value,
        ]);
        $feed = FeedInventory::factory()->create([
            'user_id' => $user->id,
            'expense_id' => $expense->id,
        ]);

        // Delete expense directly (user removes it from expenses page)
        $expense->delete();

        $feed->refresh();
        $this->assertNull($feed->expense_id);
        $this->assertDatabaseHas('feed_inventory', ['id' => $feed->id]);
    }
}
```

### Edge Cases & Notes

- **Legacy feed entries** (created before this story) will have `expense_id = null`. The update path handles this by creating a new expense if `total_cost > 0` and no linked expense exists.
- **Manually deleted expenses**: If a user deletes a feed-linked expense from the expenses page, the `SET NULL` FK constraint automatically nullifies `feed_inventory.expense_id`. The feed entry is unaffected. On the next feed update, a new expense will be created if `total_cost > 0`.
- **Decimal formatting**: The description uses `number_format((float) $quantity, 2)` to ensure consistent formatting (e.g., `25.00` not `25`).
- **No observer**: An Observer was considered but rejected because (a) it adds hidden side-effects, (b) it doesn't have access to `$request->user()` without workarounds, and (c) the controller is the natural place given existing patterns.
- **No transaction wrapping the full operation**: The feed save and expense sync are deliberately not in a single DB transaction. This ensures feed operations always succeed. The try-catch around expense sync provides the resilience guarantee.

### Dependency

- **Requires Story 1 (Schema Migration)** to be completed first — the `brand`, `feed_type`, `opened_date` columns must exist on `feed_inventory`.
- **No dependency on Stories 2-3** (UI stories) — this is a backend-only change.
