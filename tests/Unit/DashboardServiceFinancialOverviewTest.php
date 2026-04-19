<?php

namespace Tests\Unit;

use App\Models\EggEntry;
use App\Models\Sale;
use App\Models\User;
use App\Services\DashboardService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardServiceFinancialOverviewTest extends TestCase
{
    use RefreshDatabase;

    private DashboardService $service;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->travelTo(Carbon::create(2026, 4, 18));
        $this->service = new DashboardService;
        $this->user = User::factory()->premium()->withEggPrice(0.30)->create();
    }

    public function testReturnsExpectedKeys(): void
    {
        $result = $this->service->getFinancialOverview($this->user);

        $this->assertArrayHasKey('eggValue', $result);
        $this->assertArrayHasKey('revenue', $result);
        $this->assertArrayHasKey('freeEggs', $result);
        $this->assertArrayHasKey('eggPriceUsed', $result);
    }

    public function testEggValueCalculation(): void
    {
        $user = User::factory()->premium()->withEggPrice(0.50)->create();

        EggEntry::factory()->for($user)->create(['date' => '2026-04-10', 'count' => 60]);
        EggEntry::factory()->for($user)->create(['date' => '2026-04-15', 'count' => 40]);

        $result = $this->service->getFinancialOverview($user);

        $this->assertSame(50.0, $result['eggValue']);
        $this->assertSame(0.50, $result['eggPriceUsed']);
    }

    public function testEggValueFallbackPrice(): void
    {
        $user = User::factory()->premium()->create(['egg_price' => null]);

        EggEntry::factory()->for($user)->create(['date' => '2026-04-10', 'count' => 100]);

        $result = $this->service->getFinancialOverview($user);

        $this->assertSame(30.0, $result['eggValue']);
        $this->assertSame(0.30, $result['eggPriceUsed']);
    }

    public function testRevenueCalculation(): void
    {
        Sale::factory()->for($this->user)->create(['sale_date' => '2026-04-05', 'total_amount' => 25.50]);
        Sale::factory()->for($this->user)->create(['sale_date' => '2026-04-12', 'total_amount' => 14.50]);

        $result = $this->service->getFinancialOverview($this->user);

        $this->assertSame(40.0, $result['revenue']);
    }

    public function testFreeEggsCalculation(): void
    {
        Sale::factory()->for($this->user)->create([
            'sale_date' => '2026-04-05',
            'total_amount' => 0,
            'dozen_count' => 2,
            'individual_count' => 3,
        ]);

        $result = $this->service->getFinancialOverview($this->user);

        $this->assertSame(27, $result['freeEggs']); // 2*12 + 3
    }

    public function testMixOfFreeAndPaidSales(): void
    {
        Sale::factory()->for($this->user)->create([
            'sale_date' => '2026-04-05',
            'total_amount' => 0,
            'dozen_count' => 1,
            'individual_count' => 6,
        ]);
        Sale::factory()->for($this->user)->create([
            'sale_date' => '2026-04-10',
            'total_amount' => 20.00,
            'dozen_count' => 2,
            'individual_count' => 0,
        ]);

        $result = $this->service->getFinancialOverview($this->user);

        $this->assertSame(20.0, $result['revenue']);
        $this->assertSame(18, $result['freeEggs']); // 1*12 + 6
    }

    public function testOnlyCurrentMonthSalesCounted(): void
    {
        // Last month sale — should be excluded
        Sale::factory()->for($this->user)->create(['sale_date' => '2026-03-15', 'total_amount' => 100.00]);
        // This month sale
        Sale::factory()->for($this->user)->create(['sale_date' => '2026-04-10', 'total_amount' => 15.00]);

        $result = $this->service->getFinancialOverview($this->user);

        $this->assertSame(15.0, $result['revenue']);
    }

    public function testZeroDataForNewUser(): void
    {
        $freshUser = User::factory()->create(['egg_price' => null]);

        $result = $this->service->getFinancialOverview($freshUser);

        $this->assertSame(0.0, $result['eggValue']);
        $this->assertSame(0.0, $result['revenue']);
        $this->assertSame(0, $result['freeEggs']);
        $this->assertSame(0.30, $result['eggPriceUsed']);
    }
}
