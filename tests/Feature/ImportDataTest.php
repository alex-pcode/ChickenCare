<?php

namespace Tests\Feature;

use App\Models\FlockProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class ImportDataTest extends TestCase
{
    use RefreshDatabase;

    private function makeJsonFile(array $data, string $name = 'export.json'): UploadedFile
    {
        $content = json_encode($data, JSON_THROW_ON_ERROR);
        $tmp = tempnam(sys_get_temp_dir(), 'import_');
        file_put_contents($tmp, $content);

        return new UploadedFile($tmp, $name, 'application/json', null, true);
    }

    // === Page Access ===

    public function test_guest_cannot_access_import_page(): void
    {
        $this->get('/app/import')->assertRedirect('/login');
    }

    public function test_authenticated_user_can_view_import_page(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get('/app/import')
            ->assertStatus(200)
            ->assertViewIs('import.index');
    }

    // === Validation ===

    public function test_import_requires_file(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post('/app/import', [])
            ->assertSessionHasErrors('import_file');
    }

    public function test_import_rejects_non_json_file(): void
    {
        $user = User::factory()->create();
        $file = UploadedFile::fake()->create('data.csv', 100, 'text/csv');

        $this->actingAs($user)
            ->post('/app/import', ['import_file' => $file])
            ->assertSessionHasErrors('import_file');
    }

    public function test_import_rejects_invalid_json_structure(): void
    {
        $user = User::factory()->create();
        $file = $this->makeJsonFile(['randomKey' => 'value']);

        $this->actingAs($user)
            ->post('/app/import', ['import_file' => $file])
            ->assertSessionHasErrors('import_file');
    }

    public function test_import_rejects_files_exceeding_row_cap(): void
    {
        $user = User::factory()->create();
        $file = $this->makeJsonFile([
            'exportedAt' => '2025-01-15T10:00:00.000Z',
            'eggEntries' => array_fill(0, 10001, ['date' => '2025-01-10', 'count' => 1]),
        ]);

        $this->actingAs($user)
            ->post('/app/import', ['import_file' => $file])
            ->assertSessionHasErrors('import_file');

        $this->assertDatabaseCount('egg_entries', 0);
    }

    public function test_import_is_rate_limited(): void
    {
        $user = User::factory()->create();

        for ($i = 0; $i < 6; $i++) {
            $this->actingAs($user)->post('/app/import', [])->assertStatus(302);
        }

        $this->actingAs($user)->post('/app/import', [])->assertStatus(429);
    }

    // === Egg Entries Import ===

    public function test_import_egg_entries(): void
    {
        $user = User::factory()->create();

        $file = $this->makeJsonFile([
            'exportedAt' => '2025-01-15T10:00:00.000Z',
            'eggEntries' => [
                ['id' => 'e1', 'date' => '2025-01-10', 'count' => 6, 'size' => 'large', 'color' => 'brown', 'notes' => 'Good morning'],
                ['id' => 'e2', 'date' => '2025-01-11', 'count' => 4, 'size' => 'medium', 'color' => null],
                ['id' => 'e3', 'date' => '2025-01-12', 'count' => 0],
            ],
        ]);

        $this->actingAs($user)
            ->post('/app/import', ['import_file' => $file])
            ->assertSessionHas('success');

        $this->assertDatabaseCount('egg_entries', 3);
        $this->assertDatabaseHas('egg_entries', [
            'user_id' => $user->id,
            'count' => 6,
            'size' => 'large',
            'color' => 'brown',
        ]);
    }

    // === Expenses Import ===

    public function test_import_expenses(): void
    {
        $user = User::factory()->create();

        $file = $this->makeJsonFile([
            'exportedAt' => '2025-01-15T10:00:00.000Z',
            'expenses' => [
                ['id' => 'x1', 'date' => '2025-01-05', 'category' => 'Feed', 'description' => 'Layer feed', 'amount' => 25.50],
                ['id' => 'x2', 'date' => '2025-01-08', 'category' => 'Veterinary', 'description' => 'Checkup', 'amount' => 50.00],
            ],
        ]);

        $this->actingAs($user)
            ->post('/app/import', ['import_file' => $file])
            ->assertSessionHas('success');

        $this->assertDatabaseCount('expenses', 2);
        $this->assertDatabaseHas('expenses', [
            'user_id' => $user->id,
            'category' => 'Feed',
            'amount' => 25.50,
        ]);
    }

    public function test_import_expenses_with_invalid_category_defaults_to_other(): void
    {
        $user = User::factory()->create();

        $file = $this->makeJsonFile([
            'exportedAt' => '2025-01-15T10:00:00.000Z',
            'expenses' => [
                ['date' => '2025-01-05', 'category' => 'InvalidCategory', 'description' => 'Test', 'amount' => 10],
            ],
        ]);

        $this->actingAs($user)
            ->post('/app/import', ['import_file' => $file]);

        $this->assertDatabaseHas('expenses', [
            'user_id' => $user->id,
            'category' => 'Other',
        ]);
    }

    // === Feed Inventory Import ===

    public function test_import_feed_inventory_with_camel_case_keys(): void
    {
        $user = User::factory()->create();

        $file = $this->makeJsonFile([
            'exportedAt' => '2025-01-15T10:00:00.000Z',
            'feedInventory' => [
                [
                    'id' => 'f1',
                    'brand' => 'Purina Layena',
                    'type' => 'Big chicks',
                    'quantity' => 25.5,
                    'unit' => 'kg',
                    'openedDate' => '2025-01-01',
                    'depletedDate' => '2025-01-20',
                    'batchNumber' => 'BN123',
                    'total_cost' => 45.00,
                ],
            ],
        ]);

        $this->actingAs($user)
            ->post('/app/import', ['import_file' => $file])
            ->assertSessionHas('success');

        $this->assertDatabaseHas('feed_inventory', [
            'user_id' => $user->id,
            'brand' => 'Purina Layena',
            'feed_type' => 'Big chicks',
            'quantity' => 25.50,
            'batch_number' => 'BN123',
        ]);

        $feed = $user->feedInventory()->first();
        $this->assertEquals('2025-01-01', $feed->opened_date->toDateString());
        $this->assertEquals('2025-01-20', $feed->depleted_date->toDateString());
    }

    // === Flock Profile Import ===

    public function test_import_flock_profile(): void
    {
        $user = User::factory()->create();

        $file = $this->makeJsonFile([
            'exportedAt' => '2025-01-15T10:00:00.000Z',
            'flockProfile' => [
                'hens' => 10,
                'roosters' => 2,
                'chicks' => 5,
                'brooding' => 1,
                'breedTypes' => ['Rhode Island Red', 'Leghorn'],
                'flockStartDate' => '2024-06-01',
                'notes' => 'My farm',
            ],
        ]);

        $this->actingAs($user)
            ->post('/app/import', ['import_file' => $file])
            ->assertSessionHas('success');

        $this->assertDatabaseHas('flock_profiles', [
            'user_id' => $user->id,
            'hens' => 10,
            'roosters' => 2,
            'chicks' => 5,
            'brooding' => 1,
        ]);

        $profile = $user->flockProfile;
        $this->assertEquals('2024-06-01', $profile->start_date->toDateString());
    }

    public function test_import_skips_flock_profile_if_already_exists(): void
    {
        $user = User::factory()->create();
        FlockProfile::factory()->create(['user_id' => $user->id, 'farm_name' => 'Existing Farm']);

        $file = $this->makeJsonFile([
            'exportedAt' => '2025-01-15T10:00:00.000Z',
            'flockProfile' => [
                'hens' => 5,
                'roosters' => 1,
            ],
        ]);

        $this->actingAs($user)
            ->post('/app/import', ['import_file' => $file])
            ->assertSessionHas('success');

        // Should still have the original profile
        $this->assertDatabaseHas('flock_profiles', [
            'user_id' => $user->id,
            'farm_name' => 'Existing Farm',
        ]);
        $this->assertDatabaseCount('flock_profiles', 1);
    }

    // === Flock Batches Import ===

    public function test_import_flock_batches_with_camel_case_keys(): void
    {
        $user = User::factory()->create();

        $file = $this->makeJsonFile([
            'exportedAt' => '2025-01-15T10:00:00.000Z',
            'flockBatches' => [
                [
                    'id' => 'b1',
                    'batchName' => 'Spring Hatch',
                    'breed' => 'Rhode Island Red',
                    'acquisitionDate' => '2024-03-15',
                    'initialCount' => 12,
                    'currentCount' => 10,
                    'hensCount' => 8,
                    'roostersCount' => 2,
                    'chicksCount' => 0,
                    'broodingCount' => 0,
                    'type' => 'mixed',
                    'ageAtAcquisition' => 'chick',
                    'source' => 'Local Breeder',
                    'cost' => 120.00,
                    'isActive' => true,
                ],
            ],
        ]);

        $this->actingAs($user)
            ->post('/app/import', ['import_file' => $file])
            ->assertSessionHas('success');

        $this->assertDatabaseHas('flock_batches', [
            'user_id' => $user->id,
            'batch_name' => 'Spring Hatch',
            'breed' => 'Rhode Island Red',
            'initial_count' => 12,
            'current_count' => 10,
            'hens_count' => 8,
            'age_at_acquisition' => 'chick',
        ]);
    }

    // === Customers Import ===

    public function test_import_customers(): void
    {
        $user = User::factory()->create();

        $file = $this->makeJsonFile([
            'exportedAt' => '2025-01-15T10:00:00.000Z',
            'customers' => [
                ['id' => 'c1', 'name' => 'John Doe', 'phone' => '555-0100', 'is_active' => true],
                ['id' => 'c2', 'name' => 'Jane Smith', 'phone' => '555-0200', 'is_active' => false],
            ],
        ]);

        $this->actingAs($user)
            ->post('/app/import', ['import_file' => $file])
            ->assertSessionHas('success');

        $this->assertDatabaseCount('customers', 2);
        $this->assertDatabaseHas('customers', [
            'user_id' => $user->id,
            'name' => 'John Doe',
            'phone' => '555-0100',
        ]);
    }

    // === Sales Import with Customer Linking ===

    public function test_import_sales_linked_to_imported_customers(): void
    {
        $user = User::factory()->create();

        $file = $this->makeJsonFile([
            'exportedAt' => '2025-01-15T10:00:00.000Z',
            'customers' => [
                ['id' => 'cust-abc', 'name' => 'Buyer One', 'is_active' => true],
            ],
            'sales' => [
                [
                    'id' => 's1',
                    'customer_id' => 'cust-abc',
                    'sale_date' => '2025-01-10',
                    'dozen_count' => 3,
                    'individual_count' => 6,
                    'total_amount' => 15.00,
                    'paid' => true,
                ],
            ],
        ]);

        $this->actingAs($user)
            ->post('/app/import', ['import_file' => $file])
            ->assertSessionHas('success');

        $customer = $user->customers()->where('name', 'Buyer One')->first();
        $this->assertNotNull($customer);

        $this->assertDatabaseHas('sales', [
            'user_id' => $user->id,
            'customer_id' => $customer->id,
            'dozen_count' => 3,
            'individual_count' => 6,
            'total_amount' => 15.00,
            'paid' => true,
        ]);
    }

    // === Death Records with Batch Linking ===

    public function test_import_death_records_linked_to_batches(): void
    {
        $user = User::factory()->create();

        $file = $this->makeJsonFile([
            'exportedAt' => '2025-01-15T10:00:00.000Z',
            'flockBatches' => [
                [
                    'id' => 'batch-1',
                    'batchName' => 'Test Batch',
                    'breed' => 'Leghorn',
                    'acquisitionDate' => '2024-01-01',
                    'initialCount' => 20,
                    'currentCount' => 18,
                    'type' => 'hens',
                    'ageAtAcquisition' => 'adult',
                    'source' => 'Farm',
                    'isActive' => true,
                ],
            ],
            'deathRecords' => [
                [
                    'id' => 'd1',
                    'batchId' => 'batch-1',
                    'date' => '2024-06-15',
                    'count' => 2,
                    'cause' => 'predator',
                    'description' => 'Fox attack',
                ],
            ],
        ]);

        $this->actingAs($user)
            ->post('/app/import', ['import_file' => $file])
            ->assertSessionHas('success');

        $batch = $user->flockBatches()->where('batch_name', 'Test Batch')->first();
        $this->assertNotNull($batch);

        $this->assertDatabaseHas('death_records', [
            'user_id' => $user->id,
            'batch_id' => $batch->id,
            'count' => 2,
            'cause' => 'predator',
        ]);
    }

    // === Full Data Import ===

    public function test_import_full_export_with_all_sections(): void
    {
        $user = User::factory()->create();

        $file = $this->makeJsonFile([
            'exportedAt' => '2025-01-15T10:00:00.000Z',
            'eggEntries' => [
                ['id' => 'e1', 'date' => '2025-01-10', 'count' => 5],
            ],
            'expenses' => [
                ['id' => 'x1', 'date' => '2025-01-05', 'category' => 'Feed', 'description' => 'Feed', 'amount' => 25],
            ],
            'feedInventory' => [
                ['id' => 'f1', 'brand' => 'Test Feed', 'type' => 'Both', 'quantity' => 10, 'unit' => 'kg', 'total_cost' => 20],
            ],
            'flockProfile' => [
                'hens' => 5, 'roosters' => 1, 'chicks' => 0, 'brooding' => 0,
            ],
            'flockBatches' => [
                [
                    'id' => 'b1', 'batchName' => 'Batch 1', 'breed' => 'Mixed',
                    'acquisitionDate' => '2024-01-01', 'initialCount' => 6,
                    'currentCount' => 5, 'type' => 'mixed', 'ageAtAcquisition' => 'adult',
                    'source' => 'Farm', 'isActive' => true,
                ],
            ],
            'customers' => [
                ['id' => 'c1', 'name' => 'Test Customer', 'is_active' => true],
            ],
            'sales' => [
                ['id' => 's1', 'customer_id' => 'c1', 'sale_date' => '2025-01-12', 'dozen_count' => 1, 'individual_count' => 0, 'total_amount' => 5],
            ],
        ]);

        $this->actingAs($user)
            ->post('/app/import', ['import_file' => $file])
            ->assertSessionHas('success');

        $this->assertDatabaseCount('egg_entries', 1);
        $this->assertDatabaseCount('expenses', 1);
        $this->assertDatabaseCount('feed_inventory', 1);
        $this->assertDatabaseCount('flock_profiles', 1);
        $this->assertDatabaseCount('flock_batches', 1);
        $this->assertDatabaseCount('customers', 1);
        $this->assertDatabaseCount('sales', 1);
    }

    // === Edge Cases ===

    public function test_import_handles_empty_arrays_gracefully(): void
    {
        $user = User::factory()->create();

        $file = $this->makeJsonFile([
            'exportedAt' => '2025-01-15T10:00:00.000Z',
            'eggEntries' => [],
            'expenses' => [],
        ]);

        $this->actingAs($user)
            ->post('/app/import', ['import_file' => $file])
            ->assertSessionHas('success');

        $this->assertDatabaseCount('egg_entries', 0);
        $this->assertDatabaseCount('expenses', 0);
    }

    public function test_import_skips_entries_with_invalid_dates(): void
    {
        $user = User::factory()->create();

        $file = $this->makeJsonFile([
            'exportedAt' => '2025-01-15T10:00:00.000Z',
            'eggEntries' => [
                ['id' => 'e1', 'date' => 'not-a-date', 'count' => 5],
                ['id' => 'e2', 'date' => '2025-01-10', 'count' => 3],
            ],
        ]);

        $this->actingAs($user)
            ->post('/app/import', ['import_file' => $file]);

        $this->assertDatabaseCount('egg_entries', 1);
    }

    public function test_import_handles_iso_datetime_format(): void
    {
        $user = User::factory()->create();

        $file = $this->makeJsonFile([
            'exportedAt' => '2025-01-15T10:00:00.000Z',
            'eggEntries' => [
                ['id' => 'e1', 'date' => '2025-01-10T08:30:00.000Z', 'count' => 7],
            ],
        ]);

        $this->actingAs($user)
            ->post('/app/import', ['import_file' => $file]);

        $entry = $user->eggEntries()->first();
        $this->assertNotNull($entry);
        $this->assertEquals('2025-01-10', $entry->date->toDateString());
        $this->assertEquals(7, $entry->count);
    }

    public function test_import_invalid_egg_size_becomes_null(): void
    {
        $user = User::factory()->create();

        $file = $this->makeJsonFile([
            'exportedAt' => '2025-01-15T10:00:00.000Z',
            'eggEntries' => [
                ['id' => 'e1', 'date' => '2025-01-10', 'count' => 3, 'size' => 'gigantic'],
            ],
        ]);

        $this->actingAs($user)
            ->post('/app/import', ['import_file' => $file]);

        $this->assertDatabaseHas('egg_entries', [
            'user_id' => $user->id,
            'size' => null,
        ]);
    }

    public function test_import_data_belongs_to_authenticated_user_only(): void
    {
        $userA = User::factory()->create();
        $userB = User::factory()->create();

        $file = $this->makeJsonFile([
            'exportedAt' => '2025-01-15T10:00:00.000Z',
            'eggEntries' => [
                ['id' => 'e1', 'date' => '2025-01-10', 'count' => 5],
            ],
        ]);

        $this->actingAs($userA)
            ->post('/app/import', ['import_file' => $file]);

        $this->assertDatabaseHas('egg_entries', ['user_id' => $userA->id]);
        $this->assertDatabaseMissing('egg_entries', ['user_id' => $userB->id]);
    }

    // === Flock Events Import ===

    public function test_import_flock_events(): void
    {
        $user = User::factory()->create();

        $file = $this->makeJsonFile([
            'exportedAt' => '2025-01-15T10:00:00.000Z',
            'flockProfile' => [
                'hens' => 5, 'roosters' => 1, 'chicks' => 0, 'brooding' => 0,
            ],
            'flockEvents' => [
                ['id' => 'fe1', 'date' => '2024-06-01', 'type' => 'acquisition', 'description' => 'Got new chickens', 'affectedBirds' => 6],
                ['id' => 'fe2', 'date' => '2024-08-01', 'type' => 'laying_start', 'description' => 'Started laying'],
            ],
        ]);

        $this->actingAs($user)
            ->post('/app/import', ['import_file' => $file])
            ->assertSessionHas('success');

        $this->assertDatabaseCount('flock_events', 2);
        $this->assertDatabaseHas('flock_events', [
            'type' => 'acquisition',
            'affected_birds' => 6,
        ]);
    }
}
