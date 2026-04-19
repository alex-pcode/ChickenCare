<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class CustomerDataLayerTest extends TestCase
{
    use RefreshDatabase;

    public function test_customers_table_exists_with_correct_columns(): void
    {
        $this->assertTrue(Schema::hasTable('customers'));
        $this->assertTrue(Schema::hasColumns('customers', [
            'id', 'user_id', 'name', 'phone', 'notes', 'is_active', 'created_at', 'updated_at',
        ]));
    }

    public function test_customer_factory_creates_valid_model(): void
    {
        $customer = Customer::factory()->create();

        $this->assertDatabaseHas('customers', ['id' => $customer->id]);
        $this->assertNotEmpty($customer->name);
        $this->assertTrue($customer->is_active);
    }

    public function test_customer_factory_inactive_state(): void
    {
        $customer = Customer::factory()->inactive()->create();

        $this->assertFalse($customer->is_active);
    }

    public function test_customer_belongs_to_user_via_foreign_key(): void
    {
        $user = User::factory()->create();
        Customer::factory()->create(['user_id' => $user->id]);

        $user->delete();

        $this->assertDatabaseMissing('customers', ['user_id' => $user->id]);
    }

    public function test_customer_seeder_creates_entries_for_users(): void
    {
        User::factory()->premium()->create();

        $this->seed(\Database\Seeders\CustomerSeeder::class);

        $this->assertGreaterThanOrEqual(6, Customer::count());
    }
}
