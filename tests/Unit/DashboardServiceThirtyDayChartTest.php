<?php

namespace Tests\Unit;

use App\Models\EggEntry;
use App\Models\User;
use App\Services\DashboardService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardServiceThirtyDayChartTest extends TestCase
{
    use RefreshDatabase;

    private DashboardService $service;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->travelTo(Carbon::create(2026, 4, 18));
        $this->service = new DashboardService;
        $this->user = User::factory()->create();
    }

    public function test_returns_thirty_elements(): void
    {
        $result = $this->service->getThirtyDayProductionChart($this->user);

        $this->assertCount(30, $result['labels']);
        $this->assertCount(30, $result['datasets'][0]['data']);
    }

    public function test_zero_fill_for_days_with_no_entries(): void
    {
        EggEntry::factory()->for($this->user)->create(['date' => '2026-04-10', 'count' => 5]);
        EggEntry::factory()->for($this->user)->create(['date' => '2026-04-15', 'count' => 8]);

        $result = $this->service->getThirtyDayProductionChart($this->user);
        $data = $result['datasets'][0]['data'];

        // Days without entries should be 0
        $nonZero = array_filter($data, fn (int $v) => $v > 0);
        $this->assertCount(2, $nonZero);

        // Verify specific values exist
        $this->assertContains(5, $data);
        $this->assertContains(8, $data);
    }

    public function test_sums_multiple_entries_on_the_same_day(): void
    {
        EggEntry::factory()->for($this->user)->create(['date' => '2026-04-10', 'count' => 5, 'size' => 'large']);
        EggEntry::factory()->for($this->user)->create(['date' => '2026-04-10', 'count' => 3, 'size' => 'medium']);

        $result = $this->service->getThirtyDayProductionChart($this->user);
        $data = $result['datasets'][0]['data'];

        $nonZero = array_values(array_filter($data, fn (int $v) => $v > 0));
        $this->assertSame([8], $nonZero);
    }

    public function test_dates_ascending(): void
    {
        $result = $this->service->getThirtyDayProductionChart($this->user);
        $labels = $result['labels'];

        // First label: 29 days ago (March 20) => 3/20
        $this->assertSame('3/20', $labels[0]);
        // Last label: today (April 18) => 4/18
        $this->assertSame('4/18', $labels[29]);
    }

    public function test_correct_dataset_structure(): void
    {
        $result = $this->service->getThirtyDayProductionChart($this->user);
        $dataset = $result['datasets'][0];

        $this->assertSame('Production', $dataset['label']);
        $this->assertSame('#4F46E5', $dataset['backgroundColor']);
        $this->assertSame(4, $dataset['borderRadius']);
    }
}
