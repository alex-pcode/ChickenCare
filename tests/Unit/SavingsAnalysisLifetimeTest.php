<?php

namespace Tests\Unit;

use App\Enums\BatchAgeAtAcquisition;
use App\Models\EggEntry;
use App\Models\FlockBatch;
use App\Models\Sale;
use App\Models\User;
use App\Services\SavingsAnalysisService;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class SavingsAnalysisLifetimeTest extends TestCase
{
    use LazilyRefreshDatabase;

    private SavingsAnalysisService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new SavingsAnalysisService();
    }

    public function test_days_gone_counts_inclusive_days_between_first_and_last_egg(): void
    {
        $user = User::factory()->premium()->create();
        EggEntry::factory()->create(['user_id' => $user->id, 'date' => '2026-01-01', 'count' => 3]);
        EggEntry::factory()->create(['user_id' => $user->id, 'date' => '2026-01-10', 'count' => 5]);

        $result = $this->service->lifetimeImpact($user);

        $this->assertEquals(10, $result['daysGone']);
    }

    public function test_days_gone_is_zero_when_no_eggs(): void
    {
        $user = User::factory()->premium()->create();

        $result = $this->service->lifetimeImpact($user);

        $this->assertEquals(0, $result['daysGone']);
    }

    public function test_days_gone_is_one_when_single_egg_entry(): void
    {
        $user = User::factory()->premium()->create();
        EggEntry::factory()->create(['user_id' => $user->id, 'date' => '2026-03-15', 'count' => 2]);

        $result = $this->service->lifetimeImpact($user);

        $this->assertEquals(1, $result['daysGone']);
    }

    public function test_free_eggs_sums_sales_with_zero_total(): void
    {
        $user = User::factory()->premium()->create();
        Sale::factory()->create([
            'user_id' => $user->id, 'dozen_count' => 2, 'individual_count' => 3, 'total_amount' => 0.00,
        ]);
        Sale::factory()->create([
            'user_id' => $user->id, 'dozen_count' => 1, 'individual_count' => 0, 'total_amount' => 5.00,
        ]);

        $result = $this->service->lifetimeImpact($user);

        $this->assertEquals(27, $result['freeEggs']);
    }

    public function test_free_eggs_is_zero_when_no_sales(): void
    {
        $user = User::factory()->premium()->create();

        $result = $this->service->lifetimeImpact($user);

        $this->assertEquals(0, $result['freeEggs']);
    }

    public function test_omelettes_calculation(): void
    {
        $user = User::factory()->premium()->create();
        EggEntry::factory()->create(['user_id' => $user->id, 'date' => '2026-01-01', 'count' => 100]);
        Sale::factory()->create([
            'user_id' => $user->id, 'dozen_count' => 1, 'individual_count' => 0, 'total_amount' => 5.00,
        ]);
        Sale::factory()->create([
            'user_id' => $user->id, 'dozen_count' => 0, 'individual_count' => 6, 'total_amount' => 0.00,
        ]);

        $result = $this->service->lifetimeImpact($user);

        $this->assertEquals(16, $result['omelettes']);
    }

    public function test_omelettes_clamped_at_zero_when_negative(): void
    {
        $user = User::factory()->premium()->create();
        EggEntry::factory()->create(['user_id' => $user->id, 'date' => '2026-01-01', 'count' => 5]);
        Sale::factory()->create([
            'user_id' => $user->id, 'dozen_count' => 2, 'individual_count' => 0, 'total_amount' => 10.00,
        ]);

        $result = $this->service->lifetimeImpact($user);

        $this->assertEquals(0, $result['omelettes']);
    }

    public function test_comedy_hours_is_half_of_days_gone(): void
    {
        $user = User::factory()->premium()->create();
        EggEntry::factory()->create(['user_id' => $user->id, 'date' => '2026-01-01', 'count' => 1]);
        EggEntry::factory()->create(['user_id' => $user->id, 'date' => '2026-01-21', 'count' => 1]);

        $result = $this->service->lifetimeImpact($user);

        $this->assertEquals(10, $result['comedyHours']);
    }

    public function test_chickens_saved_uses_initial_count_sum(): void
    {
        $user = User::factory()->premium()->create();
        FlockBatch::factory()->create(['user_id' => $user->id, 'initial_count' => 10, 'current_count' => 8]);
        FlockBatch::factory()->create(['user_id' => $user->id, 'initial_count' => 5, 'current_count' => 5]);

        $result = $this->service->lifetimeImpact($user);

        $this->assertEquals(15, $result['chickensSaved']);
    }

    public function test_chickens_saved_is_zero_when_no_batches(): void
    {
        $user = User::factory()->premium()->create();

        $result = $this->service->lifetimeImpact($user);

        $this->assertEquals(0, $result['chickensSaved']);
    }

    public function test_flocks_raised_counts_chick_batches_only(): void
    {
        $user = User::factory()->premium()->create();
        FlockBatch::factory()->create([
            'user_id' => $user->id, 'age_at_acquisition' => BatchAgeAtAcquisition::Chick,
        ]);
        FlockBatch::factory()->create([
            'user_id' => $user->id, 'age_at_acquisition' => BatchAgeAtAcquisition::Chick,
        ]);
        FlockBatch::factory()->create([
            'user_id' => $user->id, 'age_at_acquisition' => BatchAgeAtAcquisition::Adult,
        ]);
        FlockBatch::factory()->create([
            'user_id' => $user->id, 'age_at_acquisition' => BatchAgeAtAcquisition::Juvenile,
        ]);

        $result = $this->service->lifetimeImpact($user);

        $this->assertEquals(2, $result['flocksRaised']);
    }

    public function test_flocks_raised_is_zero_when_no_chick_batches(): void
    {
        $user = User::factory()->premium()->create();
        FlockBatch::factory()->create([
            'user_id' => $user->id, 'age_at_acquisition' => BatchAgeAtAcquisition::Adult,
        ]);

        $result = $this->service->lifetimeImpact($user);

        $this->assertEquals(0, $result['flocksRaised']);
    }

    public function test_all_metrics_zero_for_empty_user(): void
    {
        $user = User::factory()->premium()->create();

        $result = $this->service->lifetimeImpact($user);

        $this->assertEquals(0, $result['daysGone']);
        $this->assertEquals(0, $result['freeEggs']);
        $this->assertEquals(0, $result['omelettes']);
        $this->assertEquals(0, $result['comedyHours']);
        $this->assertEquals(0, $result['chickensSaved']);
        $this->assertEquals(0, $result['flocksRaised']);
    }
}
