<?php

namespace Tests\Unit;

use App\Models\FlockBatch;
use App\Models\User;
use App\Services\ViabilityService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ViabilityServiceNewDefaultsTest extends TestCase
{
    use RefreshDatabase;

    private ViabilityService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new ViabilityService();
    }

    public function test_get_new_defaults_returns_correct_structure(): void
    {
        $user = User::factory()->premium()->create();

        $defaults = $this->service->getNewDefaults($user);

        $this->assertArrayHasKey('birdCount', $defaults);
        $this->assertArrayHasKey('eggPrice', $defaults);
        $this->assertArrayHasKey('startingCost', $defaults);
        $this->assertIsInt($defaults['birdCount']);
        $this->assertIsFloat($defaults['eggPrice']);
        $this->assertIsInt($defaults['startingCost']);
    }

    public function test_get_new_defaults_uses_active_flock_count(): void
    {
        $user = User::factory()->premium()->create();
        FlockBatch::factory()->active()->create(['user_id' => $user->id, 'hens_count' => 10]);
        FlockBatch::factory()->active()->create(['user_id' => $user->id, 'hens_count' => 8]);

        $defaults = $this->service->getNewDefaults($user);

        $this->assertEquals(18, $defaults['birdCount']);
    }

    public function test_get_new_defaults_falls_back_to_five_with_no_batches(): void
    {
        $user = User::factory()->premium()->create();

        $defaults = $this->service->getNewDefaults($user);

        $this->assertEquals(5, $defaults['birdCount']);
    }

    public function test_get_new_defaults_ignores_inactive_batches(): void
    {
        $user = User::factory()->premium()->create();
        FlockBatch::factory()->active()->create(['user_id' => $user->id, 'hens_count' => 12]);
        FlockBatch::factory()->archived()->create(['user_id' => $user->id, 'hens_count' => 20]);

        $defaults = $this->service->getNewDefaults($user);

        $this->assertEquals(12, $defaults['birdCount']);
    }

    public function test_get_new_defaults_returns_static_egg_price(): void
    {
        $user = User::factory()->premium()->create();

        $defaults = $this->service->getNewDefaults($user);

        $this->assertEquals(0.30, $defaults['eggPrice']);
    }

    public function test_get_new_defaults_returns_static_starting_cost(): void
    {
        $user = User::factory()->premium()->create();

        $defaults = $this->service->getNewDefaults($user);

        $this->assertEquals(50, $defaults['startingCost']);
    }
}
