<?php

namespace Tests\Feature;

use App\Models\FeedInventory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FeedInventoryEdgeCaseTest extends TestCase
{
    use RefreshDatabase;

    private function premiumUser(): User
    {
        return User::factory()->create(['tier' => 'premium']);
    }

    private function validFeed(array $overrides = []): array
    {
        return array_merge([
            'brand' => 'Layer pellets',
            'feed_type' => 'Both',
            'quantity' => '10',
            'unit' => 'kg',
            'total_cost' => '25.00',
            'opened_date' => now()->format('Y-m-d'),
            'depleted_date' => null,
            'batch_number' => null,
        ], $overrides);
    }

    public function test_feed_store_validates_quantity_minimum(): void
    {
        $user = $this->premiumUser();

        $this->actingAs($user)
            ->post('/app/feed', $this->validFeed(['quantity' => '-1']))
            ->assertSessionHasErrors('quantity');
    }

    public function test_feed_store_validates_unit_from_enum(): void
    {
        $user = $this->premiumUser();

        $this->actingAs($user)
            ->post('/app/feed', $this->validFeed(['unit' => 'gallons']))
            ->assertSessionHasErrors('unit');
    }

    public function test_feed_store_validates_total_cost_minimum(): void
    {
        $user = $this->premiumUser();

        $this->actingAs($user)
            ->post('/app/feed', $this->validFeed(['total_cost' => '-5']))
            ->assertSessionHasErrors('total_cost');
    }

    public function test_feed_index_shows_depleted_entries(): void
    {
        $user = $this->premiumUser();
        FeedInventory::factory()->depleted()->create([
            'user_id' => $user->id,
        ]);

        $response = $this->actingAs($user)->get('/app/feed');

        $response->assertStatus(200);
        $response->assertSee('feed__row--depleted');
    }

    public function test_feed_index_shows_active_entries_without_depleted_class(): void
    {
        $user = $this->premiumUser();
        FeedInventory::factory()->active()->create([
            'user_id' => $user->id,
        ]);

        $response = $this->actingAs($user)->get('/app/feed');

        $response->assertStatus(200);
        $response->assertDontSee('feed__row--depleted');
    }

    public function test_feed_index_empty_state_for_new_user(): void
    {
        $user = $this->premiumUser();

        $response = $this->actingAs($user)->get('/app/feed');

        $response->assertStatus(200);
        $response->assertViewIs('feed.index');
        $feeds = $response->viewData('feeds');
        $this->assertTrue($feeds->isEmpty());
    }
}
