<?php

namespace Tests\Unit;

use App\Enums\FeedType;
use App\Models\FeedInventory;
use App\Models\User;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FeedInventoryModelTest extends TestCase
{
    use RefreshDatabase;

    public function test_feed_inventory_belongs_to_user(): void
    {
        $user = User::factory()->create();
        $feed = FeedInventory::factory()->create(['user_id' => $user->id]);

        $this->assertInstanceOf(BelongsTo::class, $feed->user());
        $this->assertTrue($feed->user->is($user));
    }

    public function test_feed_inventory_fillable_attributes(): void
    {
        $feed = new FeedInventory();

        $this->assertEquals(
            ['brand', 'feed_type', 'quantity', 'unit', 'opened_date', 'depleted_date', 'batch_number', 'total_cost', 'expense_id'],
            $feed->getFillable()
        );
    }

    public function test_feed_inventory_casts_dates_to_carbon(): void
    {
        $feed = FeedInventory::factory()->create([
            'opened_date' => '2026-04-01',
            'depleted_date' => '2026-04-15',
        ]);

        $this->assertInstanceOf(\Illuminate\Support\Carbon::class, $feed->opened_date);
        $this->assertInstanceOf(\Illuminate\Support\Carbon::class, $feed->depleted_date);
    }

    public function test_feed_inventory_casts_feed_type_to_enum(): void
    {
        $feed = FeedInventory::factory()->create(['feed_type' => 'Both']);

        $feed->refresh();
        $this->assertInstanceOf(FeedType::class, $feed->feed_type);
        $this->assertSame(FeedType::Both, $feed->feed_type);
    }

    public function test_feed_inventory_casts_quantity_to_decimal(): void
    {
        $feed = FeedInventory::factory()->create(['quantity' => 25.50]);

        $feed->refresh();
        $this->assertEquals('25.50', $feed->quantity);
    }

    public function test_feed_inventory_casts_total_cost_to_decimal(): void
    {
        $feed = FeedInventory::factory()->create(['total_cost' => 49.99]);

        $feed->refresh();
        $this->assertEquals('49.99', $feed->total_cost);
    }

    public function test_feed_inventory_is_active_returns_true_when_no_depleted_date(): void
    {
        $feed = FeedInventory::factory()->active()->create();

        $this->assertTrue($feed->isActive());
    }

    public function test_feed_inventory_is_active_returns_false_when_depleted(): void
    {
        $feed = FeedInventory::factory()->depleted()->create();

        $this->assertFalse($feed->isActive());
    }

    public function test_feed_inventory_duration_in_days_returns_correct_count(): void
    {
        $feed = FeedInventory::factory()->create([
            'opened_date' => '2026-04-01',
            'depleted_date' => '2026-04-11',
        ]);

        $this->assertSame(10, $feed->durationInDays());
    }

    public function test_feed_inventory_duration_in_days_returns_null_when_active(): void
    {
        $feed = FeedInventory::factory()->active()->create([
            'opened_date' => '2026-04-01',
        ]);

        $this->assertNull($feed->durationInDays());
    }

    public function test_feed_inventory_mark_depleted_sets_date_to_today(): void
    {
        $feed = FeedInventory::factory()->active()->create();

        $feed->markDepleted();
        $feed->refresh();

        $this->assertNotNull($feed->depleted_date);
        $this->assertTrue($feed->depleted_date->isToday());
        $this->assertFalse($feed->isActive());
    }
}
