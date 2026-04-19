<?php

namespace Tests\Feature;

use App\Models\EggEntry;
use App\Models\User;
use Database\Seeders\EggEntrySeeder;
use Database\Seeders\UserSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class EggEntryDataLayerTest extends TestCase
{
    use RefreshDatabase;

    public function test_egg_entries_table_exists_with_correct_columns(): void
    {
        $this->assertTrue(Schema::hasTable('egg_entries'));
        $this->assertTrue(Schema::hasColumns('egg_entries', [
            'id',
            'user_id',
            'date',
            'count',
            'size',
            'color',
            'notes',
            'created_at',
            'updated_at',
        ]));
    }

    public function test_egg_entry_factory_creates_valid_model(): void
    {
        $entry = EggEntry::factory()->create();

        $this->assertInstanceOf(EggEntry::class, $entry);
        $this->assertTrue($entry->exists);
        $this->assertDatabaseHas('egg_entries', ['id' => $entry->id]);
    }

    public function test_egg_entry_factory_respects_enum_values(): void
    {
        $entries = EggEntry::factory()->count(20)->create();

        $validSizes = ['small', 'medium', 'large', 'extra-large', 'jumbo', null];
        $validColors = ['white', 'brown', 'blue', 'green', 'speckled', 'cream', null];

        foreach ($entries as $entry) {
            $this->assertContains($entry->size, $validSizes);
            $this->assertContains($entry->color, $validColors);
        }
    }

    public function test_egg_entry_belongs_to_user_via_foreign_key(): void
    {
        $user = User::factory()->create();
        $entry = EggEntry::factory()->create(['user_id' => $user->id]);

        $user->delete();

        $this->assertDatabaseMissing('egg_entries', ['id' => $entry->id]);
    }

    public function test_egg_entry_seeder_creates_entries_for_users(): void
    {
        $this->seed(UserSeeder::class);
        $this->seed(EggEntrySeeder::class);

        $users = User::all();
        $this->assertGreaterThanOrEqual(2, $users->count());

        foreach ($users as $user) {
            $this->assertGreaterThan(0, EggEntry::where('user_id', $user->id)->count());
        }
    }

    public function test_policy_blocks_cross_user_access(): void
    {
        $userA = User::factory()->create();
        $userB = User::factory()->create();

        $entryB = EggEntry::factory()->create(['user_id' => $userB->id]);

        $this->assertFalse(Gate::forUser($userA)->allows('view', $entryB));
        $this->assertFalse(Gate::forUser($userA)->allows('update', $entryB));
        $this->assertFalse(Gate::forUser($userA)->allows('delete', $entryB));
    }
}
