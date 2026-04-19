<?php

namespace Tests\Unit;

use App\Models\EggEntry;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EggEntryModelTest extends TestCase
{
    use RefreshDatabase;

    public function test_egg_entry_belongs_to_user(): void
    {
        $user = User::factory()->create();
        $entry = EggEntry::factory()->create(['user_id' => $user->id]);

        $this->assertInstanceOf(BelongsTo::class, $entry->user());
        $this->assertTrue($entry->user->is($user));
    }

    public function test_egg_entry_fillable_attributes(): void
    {
        $entry = new EggEntry();

        $this->assertEquals(['date', 'count', 'size', 'color', 'notes'], $entry->getFillable());
    }

    public function test_egg_entry_casts_date_to_carbon(): void
    {
        $entry = EggEntry::factory()->create();

        $this->assertInstanceOf(Carbon::class, $entry->date);
    }

    public function test_egg_entry_casts_count_to_integer(): void
    {
        $entry = EggEntry::factory()->create();

        $this->assertIsInt($entry->count);
    }

    public function test_egg_entry_for_week_scope(): void
    {
        $user = User::factory()->create();
        $today = Carbon::parse('2026-04-08'); // Wednesday

        // Entry in current week
        $thisWeek = EggEntry::factory()->create([
            'user_id' => $user->id,
            'date' => $today->format('Y-m-d'),
        ]);

        // Entry in previous week
        $lastWeek = EggEntry::factory()->create([
            'user_id' => $user->id,
            'date' => $today->copy()->subWeek()->format('Y-m-d'),
        ]);

        $results = EggEntry::forWeek($today)->where('user_id', $user->id)->get();

        $this->assertTrue($results->contains($thisWeek));
        $this->assertFalse($results->contains($lastWeek));
    }

    public function test_egg_entry_for_month_scope(): void
    {
        $user = User::factory()->create();
        $today = Carbon::parse('2026-04-15');

        // Entry in current month
        $thisMonth = EggEntry::factory()->create([
            'user_id' => $user->id,
            'date' => '2026-04-10',
        ]);

        // Entry in previous month
        $lastMonth = EggEntry::factory()->create([
            'user_id' => $user->id,
            'date' => '2026-03-15',
        ]);

        $results = EggEntry::forMonth($today)->where('user_id', $user->id)->get();

        $this->assertTrue($results->contains($thisMonth));
        $this->assertFalse($results->contains($lastMonth));
    }

    // === Story 2.3: Factory State Tests (Task 8) ===

    public function test_factory_generates_valid_size_enum(): void
    {
        $entries = EggEntry::factory()->count(20)->create();
        $validSizes = ['small', 'medium', 'large', 'extra-large', 'jumbo', null];

        foreach ($entries as $entry) {
            $this->assertContains($entry->size, $validSizes);
        }
    }

    public function test_factory_generates_valid_color_enum(): void
    {
        $entries = EggEntry::factory()->count(20)->create();
        $validColors = ['white', 'brown', 'blue', 'green', 'speckled', 'cream', null];

        foreach ($entries as $entry) {
            $this->assertContains($entry->color, $validColors);
        }
    }

    public function test_factory_generates_dates_within_last_90_days(): void
    {
        $entries = EggEntry::factory()->count(20)->create();
        $ninetyDaysAgo = Carbon::now()->subDays(90)->startOfDay();
        $today = Carbon::now()->endOfDay();

        foreach ($entries as $entry) {
            $this->assertTrue(
                $entry->date->greaterThanOrEqualTo($ninetyDaysAgo) && $entry->date->lessThanOrEqualTo($today),
                "Date {$entry->date->toDateString()} is not within last 90 days"
            );
        }
    }
}
