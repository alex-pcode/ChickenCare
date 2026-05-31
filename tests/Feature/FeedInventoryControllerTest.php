<?php

namespace Tests\Feature;

use App\Models\Expense;
use App\Models\FeedInventory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FeedInventoryControllerTest extends TestCase
{
    use RefreshDatabase;

    // === CRUD Operations ===

    public function test_premium_user_can_view_feed_index(): void
    {
        $user = User::factory()->premium()->create();

        $response = $this->actingAs($user)->get('/app/feed');

        $response->assertStatus(200);
        $response->assertViewIs('feed.index');
    }

    public function test_premium_user_can_store_feed_entry(): void
    {
        $user = User::factory()->premium()->create();

        $response = $this->actingAs($user)->post('/app/feed', [
            'brand' => 'Layer Pellets',
            'feed_type' => 'Both',
            'quantity' => 25.00,
            'unit' => 'kg',
            'total_cost' => 45.99,
            'opened_date' => '2026-04-10',
            'depleted_date' => null,
            'batch_number' => null,
        ]);

        $response->assertRedirect(route('app.feed.index'));
        $response->assertSessionHas('success');
        $this->assertDatabaseHas('feed_inventory', [
            'user_id' => $user->id,
            'brand' => 'Layer Pellets',
            'quantity' => 25.00,
        ]);
    }

    public function test_premium_user_can_store_feed_entry_via_htmx(): void
    {
        $user = User::factory()->premium()->create();

        $response = $this->actingAs($user)
            ->withHeaders(['HX-Request' => 'true'])
            ->post('/app/feed', [
                'brand' => 'Scratch Grains',
                'feed_type' => 'Baby chicks',
                'quantity' => 10.00,
                'unit' => 'lbs',
                'total_cost' => 20.00,
            ]);

        $response->assertStatus(200);
        $response->assertSee('feed-');
        $response->assertHeader('HX-Trigger', 'feed:changed');
        $this->assertDatabaseHas('feed_inventory', [
            'user_id' => $user->id,
            'brand' => 'Scratch Grains',
        ]);
    }

    public function test_premium_user_can_update_feed_entry(): void
    {
        $user = User::factory()->premium()->create();
        $feed = FeedInventory::factory()->create(['user_id' => $user->id]);

        $response = $this->actingAs($user)->put("/app/feed/{$feed->id}", [
            'brand' => 'Updated Feed',
            'feed_type' => 'Big chicks',
            'quantity' => 30.00,
            'unit' => 'kg',
            'total_cost' => 55.00,
        ]);

        $response->assertRedirect(route('app.feed.index'));
        $this->assertDatabaseHas('feed_inventory', [
            'id' => $feed->id,
            'brand' => 'Updated Feed',
        ]);
    }

    public function test_premium_user_can_update_feed_entry_via_htmx(): void
    {
        $user = User::factory()->premium()->create();
        $feed = FeedInventory::factory()->create(['user_id' => $user->id]);

        $response = $this->actingAs($user)
            ->withHeaders(['HX-Request' => 'true'])
            ->put("/app/feed/{$feed->id}", [
                'brand' => 'Updated HTMX Feed',
                'feed_type' => 'Both',
                'quantity' => 15.00,
                'unit' => 'lbs',
                'total_cost' => 30.00,
            ]);

        $response->assertStatus(200);
        $response->assertSee('feed-');
        $response->assertHeader('HX-Trigger', 'feed:changed');
    }

    public function test_premium_user_can_delete_feed_entry(): void
    {
        $user = User::factory()->premium()->create();
        $feed = FeedInventory::factory()->create(['user_id' => $user->id]);

        $response = $this->actingAs($user)->delete("/app/feed/{$feed->id}");

        $response->assertRedirect(route('app.feed.index'));
        $this->assertDatabaseMissing('feed_inventory', ['id' => $feed->id]);
    }

    public function test_premium_user_can_delete_feed_entry_via_htmx(): void
    {
        $user = User::factory()->premium()->create();
        $feed = FeedInventory::factory()->create(['user_id' => $user->id]);

        $response = $this->actingAs($user)
            ->withHeaders(['HX-Request' => 'true'])
            ->delete("/app/feed/{$feed->id}");

        $response->assertStatus(200);
        $this->assertEmpty($response->getContent());
        $this->assertArrayHasKey('feed:changed', json_decode($response->headers->get('HX-Trigger'), true));
        $this->assertDatabaseMissing('feed_inventory', ['id' => $feed->id]);
    }

    public function test_premium_user_sees_only_own_feed_entries(): void
    {
        $userA = User::factory()->premium()->create();
        $userB = User::factory()->premium()->create();

        FeedInventory::factory()->create(['user_id' => $userA->id, 'brand' => 'User A Feed']);
        FeedInventory::factory()->create(['user_id' => $userB->id, 'brand' => 'User B Feed']);

        $response = $this->actingAs($userA)->get('/app/feed');

        $response->assertStatus(200);
        $response->assertSee('User A Feed');
        $response->assertDontSee('User B Feed');
    }

    public function test_premium_user_cannot_update_other_users_feed_entry(): void
    {
        $userA = User::factory()->premium()->create();
        $userB = User::factory()->premium()->create();
        $feed = FeedInventory::factory()->create(['user_id' => $userB->id]);

        $response = $this->actingAs($userA)->put("/app/feed/{$feed->id}", [
            'brand' => 'Hijack attempt',
            'feed_type' => 'Both',
            'quantity' => 1.00,
            'unit' => 'kg',
            'total_cost' => 5.00,
        ]);

        $response->assertStatus(403);
    }

    public function test_premium_user_cannot_delete_other_users_feed_entry(): void
    {
        $userA = User::factory()->premium()->create();
        $userB = User::factory()->premium()->create();
        $feed = FeedInventory::factory()->create(['user_id' => $userB->id]);

        $response = $this->actingAs($userA)->delete("/app/feed/{$feed->id}");

        $response->assertStatus(403);
        $this->assertDatabaseHas('feed_inventory', ['id' => $feed->id]);
    }

    // === Validation ===

    public function test_store_validates_required_fields(): void
    {
        $user = User::factory()->premium()->create();

        $response = $this->actingAs($user)->post('/app/feed', []);

        $response->assertSessionHasErrors(['brand', 'feed_type', 'quantity', 'unit', 'total_cost']);
    }

    public function test_store_validates_quantity_minimum(): void
    {
        $user = User::factory()->premium()->create();

        $response = $this->actingAs($user)->post('/app/feed', [
            'brand' => 'Test Feed',
            'feed_type' => 'Both',
            'quantity' => 0,
            'unit' => 'kg',
            'total_cost' => 10.00,
        ]);

        $response->assertSessionHasErrors(['quantity']);
    }

    public function test_store_validates_quantity_negative(): void
    {
        $user = User::factory()->premium()->create();

        $response = $this->actingAs($user)->post('/app/feed', [
            'brand' => 'Test Feed',
            'feed_type' => 'Both',
            'quantity' => -5.00,
            'unit' => 'kg',
            'total_cost' => 10.00,
        ]);

        $response->assertSessionHasErrors(['quantity']);
    }

    public function test_store_validates_unit_enum(): void
    {
        $user = User::factory()->premium()->create();

        $response = $this->actingAs($user)->post('/app/feed', [
            'brand' => 'Test Feed',
            'feed_type' => 'Both',
            'quantity' => 10.00,
            'unit' => 'tons',
            'total_cost' => 10.00,
        ]);

        $response->assertSessionHasErrors(['unit']);
    }

    public function test_store_validates_depleted_after_opened(): void
    {
        $user = User::factory()->premium()->create();

        $response = $this->actingAs($user)->post('/app/feed', [
            'brand' => 'Test Feed',
            'feed_type' => 'Both',
            'quantity' => 10.00,
            'unit' => 'kg',
            'total_cost' => 10.00,
            'opened_date' => '2026-04-10',
            'depleted_date' => '2026-04-05',
        ]);

        $response->assertSessionHasErrors(['depleted_date']);
    }

    public function test_store_validates_total_cost_minimum(): void
    {
        $user = User::factory()->premium()->create();

        $response = $this->actingAs($user)->post('/app/feed', [
            'brand' => 'Test Feed',
            'feed_type' => 'Both',
            'quantity' => 10.00,
            'unit' => 'kg',
            'total_cost' => -10.00,
        ]);

        $response->assertSessionHasErrors(['total_cost']);
    }

    public function test_store_validates_total_cost_required(): void
    {
        $user = User::factory()->premium()->create();

        $response = $this->actingAs($user)->post('/app/feed', [
            'brand' => 'Test Feed',
            'feed_type' => 'Both',
            'quantity' => 10.00,
            'unit' => 'kg',
            'total_cost' => null,
        ]);

        $response->assertSessionHasErrors(['total_cost']);
    }

    public function test_store_validates_feed_type_valid(): void
    {
        $user = User::factory()->premium()->create();

        $response = $this->actingAs($user)->post('/app/feed', [
            'brand' => 'Test Feed',
            'feed_type' => 'Both',
            'quantity' => 10.00,
            'unit' => 'kg',
            'total_cost' => 10.00,
        ]);

        $response->assertSessionDoesntHaveErrors(['feed_type']);
    }

    public function test_store_validates_feed_type_invalid(): void
    {
        $user = User::factory()->premium()->create();

        $response = $this->actingAs($user)->post('/app/feed', [
            'brand' => 'Test Feed',
            'feed_type' => 'Invalid Type',
            'quantity' => 10.00,
            'unit' => 'kg',
            'total_cost' => 10.00,
        ]);

        $response->assertSessionHasErrors(['feed_type']);
    }

    public function test_store_accepts_valid_data_with_nullable_fields(): void
    {
        $user = User::factory()->premium()->create();

        $response = $this->actingAs($user)->post('/app/feed', [
            'brand' => 'Oyster Shell',
            'feed_type' => 'Both',
            'quantity' => 5.00,
            'unit' => 'lbs',
            'total_cost' => 15.00,
            'opened_date' => null,
            'depleted_date' => null,
            'batch_number' => null,
        ]);

        $response->assertSessionDoesntHaveErrors();
        $this->assertDatabaseHas('feed_inventory', [
            'user_id' => $user->id,
            'brand' => 'Oyster Shell',
        ]);
    }

    // === Model Methods ===

    public function test_is_active_returns_true_when_depleted_date_is_null(): void
    {
        $feed = FeedInventory::factory()->active()->make();

        $this->assertTrue($feed->isActive());
    }

    public function test_is_active_returns_false_when_depleted_date_is_set(): void
    {
        $feed = FeedInventory::factory()->depleted()->make();

        $this->assertFalse($feed->isActive());
    }

    public function test_duration_in_days_returns_correct_count(): void
    {
        $feed = FeedInventory::factory()->make([
            'opened_date' => '2026-04-01',
            'depleted_date' => '2026-04-11',
        ]);

        $this->assertSame(10, $feed->durationInDays());
    }

    public function test_duration_in_days_returns_null_when_active(): void
    {
        $feed = FeedInventory::factory()->active()->make([
            'opened_date' => '2026-04-01',
        ]);

        $this->assertNull($feed->durationInDays());
    }

    public function test_duration_in_days_returns_null_when_no_opened_date(): void
    {
        $feed = FeedInventory::factory()->make([
            'opened_date' => null,
            'depleted_date' => null,
        ]);

        $this->assertNull($feed->durationInDays());
    }

    public function test_mark_depleted_sets_depleted_date_to_today(): void
    {
        $user = User::factory()->premium()->create();
        $feed = FeedInventory::factory()->active()->create(['user_id' => $user->id]);

        $this->assertNull($feed->depleted_date);

        $feed->markDepleted();

        $feed->refresh();
        $this->assertNotNull($feed->depleted_date);
        $this->assertTrue($feed->depleted_date->isToday());
    }

    // === Pagination ===

    public function test_index_paginates_at_5_items(): void
    {
        $user = User::factory()->premium()->create();
        FeedInventory::factory()->count(8)->create(['user_id' => $user->id]);

        $response = $this->actingAs($user)->get('/app/feed');

        $response->assertStatus(200);
        $response->assertViewHas('feeds', function ($feeds) {
            return $feeds->perPage() === 5 && $feeds->total() === 8;
        });
    }

    public function test_htmx_pagination_returns_partial(): void
    {
        $user = User::factory()->premium()->create();
        FeedInventory::factory()->count(8)->create(['user_id' => $user->id]);

        $response = $this->actingAs($user)
            ->withHeaders(['HX-Request' => 'true'])
            ->get('/app/feed?page=2');

        $response->assertStatus(200);
        $response->assertViewIs('feed.partials.records-table');
    }

    public function test_index_shows_empty_state_when_no_feed_entries(): void
    {
        $user = User::factory()->premium()->create();

        $response = $this->actingAs($user)->get('/app/feed');

        $response->assertStatus(200);
        $response->assertSee('No feed entries yet');
    }

    // === Premium Tier Enforcement ===

    public function test_free_user_cannot_access_feed_inventory(): void
    {
        $user = User::factory()->create(['tier' => 'free']);

        $response = $this->actingAs($user)->get('/app/feed');

        $response->assertRedirect(route('app.dashboard'));
    }

    public function test_unauthenticated_user_redirected_to_login(): void
    {
        $response = $this->get('/app/feed');

        $response->assertRedirect(route('login'));
    }

    // === Edit Form Partial ===

    public function test_premium_user_can_get_edit_form_via_htmx(): void
    {
        $user = User::factory()->premium()->create();
        $feed = FeedInventory::factory()->create(['user_id' => $user->id]);

        $response = $this->actingAs($user)
            ->withHeaders(['HX-Request' => 'true'])
            ->get("/app/feed/{$feed->id}/edit-form");

        $response->assertStatus(200);
        $response->assertViewIs('feed.partials.edit-form');
    }

    public function test_premium_user_cannot_get_edit_form_for_other_users_feed_entry(): void
    {
        $userA = User::factory()->premium()->create();
        $userB = User::factory()->premium()->create();
        $feed = FeedInventory::factory()->create(['user_id' => $userB->id]);

        $response = $this->actingAs($userA)->get("/app/feed/{$feed->id}/edit-form");

        $response->assertStatus(403);
    }

    // === Sorting ===

    public function test_index_sorts_by_brand_ascending(): void
    {
        $user = User::factory()->premium()->create();
        FeedInventory::factory()->create(['user_id' => $user->id, 'brand' => 'Zebra Feed']);
        FeedInventory::factory()->create(['user_id' => $user->id, 'brand' => 'Alpha Feed']);

        $response = $this->actingAs($user)->get('/app/feed?sort=brand&dir=asc');

        $response->assertStatus(200);
        $response->assertViewHas('sort', 'brand');
        $response->assertViewHas('dir', 'asc');
        $feeds = $response->viewData('feeds');
        $this->assertSame('Alpha Feed', $feeds->first()->brand);
    }

    public function test_index_defaults_invalid_sort_column_to_opened_date(): void
    {
        $user = User::factory()->premium()->create();
        FeedInventory::factory()->count(2)->create(['user_id' => $user->id]);

        $response = $this->actingAs($user)->get('/app/feed?sort=hacked_column&dir=asc');

        $response->assertStatus(200);
        $response->assertViewHas('sort', 'opened_date');
    }

    public function test_index_defaults_invalid_dir_to_desc(): void
    {
        $user = User::factory()->premium()->create();
        FeedInventory::factory()->count(2)->create(['user_id' => $user->id]);

        $response = $this->actingAs($user)->get('/app/feed?sort=brand&dir=invalid');

        $response->assertStatus(200);
        $response->assertViewHas('dir', 'desc');
    }

    // === Mark Depleted ===

    public function test_premium_user_can_mark_feed_as_depleted(): void
    {
        $user = User::factory()->premium()->create();
        $feed = FeedInventory::factory()->active()->create(['user_id' => $user->id]);

        $response = $this->actingAs($user)->patch("/app/feed/{$feed->id}/deplete");

        $response->assertRedirect(route('app.feed.index'));
        $feed->refresh();
        $this->assertNotNull($feed->depleted_date);
        $this->assertTrue($feed->depleted_date->isToday());
    }

    public function test_premium_user_can_mark_feed_as_depleted_via_htmx(): void
    {
        $user = User::factory()->premium()->create();
        $feed = FeedInventory::factory()->active()->create(['user_id' => $user->id]);

        $response = $this->actingAs($user)
            ->withHeaders(['HX-Request' => 'true'])
            ->patch("/app/feed/{$feed->id}/deplete");

        $response->assertStatus(200);
        $response->assertViewIs('feed.partials.entry-row');
        $response->assertHeader('HX-Trigger', 'feed:changed');
        $feed->refresh();
        $this->assertNotNull($feed->depleted_date);
    }

    public function test_premium_user_cannot_mark_other_users_feed_as_depleted(): void
    {
        $userA = User::factory()->premium()->create();
        $userB = User::factory()->premium()->create();
        $feed = FeedInventory::factory()->active()->create(['user_id' => $userB->id]);

        $response = $this->actingAs($userA)->patch("/app/feed/{$feed->id}/deplete");

        $response->assertStatus(403);
        $feed->refresh();
        $this->assertNull($feed->depleted_date);
    }

    // === Expense Auto-Sync ===

    public function test_store_creates_matching_expense(): void
    {
        $user = User::factory()->premium()->create();
        $this->actingAs($user)->post('/app/feed', [
            'brand' => 'Layer Pellets',
            'feed_type' => 'Big chicks',
            'quantity' => 25.00,
            'unit' => 'kg',
            'total_cost' => 45.99,
            'opened_date' => '2026-04-10',
        ]);

        $feed = FeedInventory::where('user_id', $user->id)->first();
        $this->assertNotNull($feed->expense_id);
        $this->assertDatabaseHas('expenses', [
            'user_id' => $user->id,
            'category' => 'Feed',
            'amount' => 45.99,
            'description' => 'Layer Pellets Big chicks (25.00 kg)',
        ]);
    }

    public function test_update_feed_updates_linked_expense(): void
    {
        $user = User::factory()->premium()->create();
        $expense = Expense::factory()->create(['user_id' => $user->id, 'category' => 'Feed']);
        $feed = FeedInventory::factory()->create(['user_id' => $user->id, 'expense_id' => $expense->id]);

        $this->actingAs($user)->put("/app/feed/{$feed->id}", [
            'brand' => 'Updated Brand',
            'feed_type' => 'Both',
            'quantity' => 30.00,
            'unit' => 'lbs',
            'total_cost' => 55.00,
            'opened_date' => '2026-04-15',
        ]);

        $expense->refresh();
        $this->assertEquals(55.00, (float) $expense->amount);
        $this->assertStringContainsString('Updated Brand', $expense->description);
    }

    public function test_delete_feed_deletes_linked_expense(): void
    {
        $user = User::factory()->premium()->create();
        $expense = Expense::factory()->create(['user_id' => $user->id, 'category' => 'Feed']);
        $feed = FeedInventory::factory()->create(['user_id' => $user->id, 'expense_id' => $expense->id]);

        $this->actingAs($user)->delete("/app/feed/{$feed->id}");

        $this->assertDatabaseMissing('expenses', ['id' => $expense->id]);
    }

    public function test_delete_feed_without_expense_does_not_fail(): void
    {
        $user = User::factory()->premium()->create();
        $feed = FeedInventory::factory()->create(['user_id' => $user->id, 'expense_id' => null]);

        $response = $this->actingAs($user)->delete("/app/feed/{$feed->id}");
        $response->assertRedirect();
        $this->assertDatabaseMissing('feed_inventory', ['id' => $feed->id]);
    }

    public function test_update_feed_without_expense_does_not_fail(): void
    {
        $user = User::factory()->premium()->create();
        $feed = FeedInventory::factory()->create(['user_id' => $user->id, 'expense_id' => null]);

        $response = $this->actingAs($user)->put("/app/feed/{$feed->id}", [
            'brand' => 'Updated Brand',
            'feed_type' => 'Both',
            'quantity' => 30.00,
            'unit' => 'lbs',
            'total_cost' => 55.00,
        ]);
        $response->assertRedirect();
    }

    // === Stats Endpoint ===

    public function test_stats_endpoint_returns_json(): void
    {
        $user = User::factory()->premium()->create();
        FeedInventory::factory()->depleted()->create(['user_id' => $user->id]);

        $response = $this->actingAs($user)->getJson('/app/feed/stats');

        $response->assertOk();
        $response->assertJsonStructure([
            'monthlyCostPerBird',
            'totalPurchased',
            'depletedCost',
            'feedCycles',
            'trends',
        ]);
    }

    public function test_stats_endpoint_accepts_range_parameter(): void
    {
        $user = User::factory()->premium()->create();

        $response = $this->actingAs($user)->getJson('/app/feed/stats?range=3months');

        $response->assertOk();
        $response->assertJsonStructure(['monthlyCostPerBird', 'totalPurchased']);
    }

    public function test_stats_endpoint_defaults_invalid_range(): void
    {
        $user = User::factory()->premium()->create();

        $response = $this->actingAs($user)->getJson('/app/feed/stats?range=invalid');

        $response->assertOk();
    }

    public function test_stats_returns_zero_metrics_with_no_feed(): void
    {
        $user = User::factory()->premium()->create();

        $response = $this->actingAs($user)->getJson('/app/feed/stats');

        $response->assertOk();
        $response->assertJson([
            'monthlyCostPerBird' => 0,
            'totalPurchased' => 0,
            'depletedCost' => 0,
            'feedCycles' => 0,
        ]);
    }
}
