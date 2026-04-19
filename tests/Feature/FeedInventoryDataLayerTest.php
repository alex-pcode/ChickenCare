<?php

namespace Tests\Feature;

use App\Models\FeedInventory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class FeedInventoryDataLayerTest extends TestCase
{
    use RefreshDatabase;

    public function test_feed_inventory_table_exists_with_correct_columns(): void
    {
        $this->assertTrue(Schema::hasTable('feed_inventory'));
        $this->assertTrue(Schema::hasColumns('feed_inventory', [
            'id', 'user_id', 'brand', 'feed_type', 'quantity', 'unit',
            'opened_date', 'depleted_date', 'batch_number', 'total_cost',
            'expense_id', 'created_at', 'updated_at',
        ]));
    }

    public function test_feed_inventory_factory_creates_valid_model(): void
    {
        $feed = FeedInventory::factory()->create();

        $this->assertDatabaseHas('feed_inventory', ['id' => $feed->id]);
        $this->assertNotNull($feed->brand);
        $this->assertNotNull($feed->quantity);
        $this->assertNotNull($feed->unit);
    }

    public function test_feed_inventory_factory_respects_unit_values(): void
    {
        $feed = FeedInventory::factory()->create();

        $this->assertContains($feed->unit, ['kg', 'lbs']);
    }

    public function test_feed_inventory_belongs_to_user_via_foreign_key(): void
    {
        $user = User::factory()->create();
        FeedInventory::factory()->count(3)->create(['user_id' => $user->id]);

        $user->delete();

        $this->assertDatabaseMissing('feed_inventory', ['user_id' => $user->id]);
    }

    public function test_feed_inventory_seeder_creates_entries_for_users(): void
    {
        User::factory()->premium()->count(2)->create();

        $this->seed(\Database\Seeders\FeedInventorySeeder::class);

        $this->assertTrue(FeedInventory::count() >= 10);
    }
}
