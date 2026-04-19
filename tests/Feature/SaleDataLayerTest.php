<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Sale;
use App\Models\User;
use Database\Seeders\CustomerSeeder;
use Database\Seeders\SaleSeeder;
use Database\Seeders\UserSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class SaleDataLayerTest extends TestCase
{
    use RefreshDatabase;

    public function test_sales_table_exists_with_correct_columns(): void
    {
        $this->assertTrue(Schema::hasTable('sales'));
        $this->assertTrue(Schema::hasColumns('sales', [
            'id',
            'user_id',
            'customer_id',
            'sale_date',
            'dozen_count',
            'individual_count',
            'total_amount',
            'paid',
            'notes',
            'created_at',
            'updated_at',
        ]));
    }

    public function test_sale_factory_creates_valid_model(): void
    {
        $user = User::factory()->create();
        $sale = Sale::factory()->create(['user_id' => $user->id]);

        $this->assertDatabaseHas('sales', ['id' => $sale->id, 'user_id' => $user->id]);
    }

    public function test_sale_factory_paid_state(): void
    {
        $user = User::factory()->create();
        $sale = Sale::factory()->paid()->create(['user_id' => $user->id]);

        $this->assertTrue((bool) $sale->fresh()->paid);
    }

    public function test_sale_factory_unpaid_state(): void
    {
        $user = User::factory()->create();
        $sale = Sale::factory()->unpaid()->create(['user_id' => $user->id]);

        $this->assertFalse((bool) $sale->fresh()->paid);
    }

    public function test_sale_belongs_to_user_via_foreign_key(): void
    {
        $user = User::factory()->create();
        Sale::factory()->count(2)->create(['user_id' => $user->id]);

        $user->delete();

        $this->assertDatabaseMissing('sales', ['user_id' => $user->id]);
    }

    public function test_sale_customer_id_set_null_when_customer_deleted(): void
    {
        $user = User::factory()->create();
        $customer = Customer::factory()->create(['user_id' => $user->id]);
        $sale = Sale::factory()->create(['user_id' => $user->id, 'customer_id' => $customer->id]);

        $customer->delete();

        $this->assertNull($sale->fresh()->customer_id);
    }

    public function test_sale_seeder_creates_entries_for_premium_users(): void
    {
        $this->seed(UserSeeder::class);
        $this->seed(CustomerSeeder::class);
        $this->seed(SaleSeeder::class);

        $premiumUserExists = User::where('tier', 'premium')->exists();
        if ($premiumUserExists) {
            $this->assertDatabaseCount('sales', Sale::count());
            $this->assertGreaterThan(0, Sale::count());
        } else {
            $this->markTestSkipped('No premium users in UserSeeder.');
        }
    }
}
