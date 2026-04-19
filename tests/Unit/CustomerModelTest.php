<?php

namespace Tests\Unit;

use App\Models\Customer;
use App\Models\User;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CustomerModelTest extends TestCase
{
    use RefreshDatabase;

    public function test_customer_belongs_to_user(): void
    {
        $customer = Customer::factory()->create();

        $this->assertInstanceOf(BelongsTo::class, $customer->user());
        $this->assertInstanceOf(User::class, $customer->user);
    }

    public function test_customer_has_many_sales(): void
    {
        $customer = Customer::factory()->create();

        $this->assertTrue(method_exists($customer, 'sales'));
    }

    public function test_customer_fillable_attributes(): void
    {
        $customer = new Customer();

        $this->assertEquals(['name', 'phone', 'notes', 'is_active'], $customer->getFillable());
        $this->assertNotContains('user_id', $customer->getFillable());
    }

    public function test_customer_casts_is_active_to_boolean(): void
    {
        $customer = Customer::factory()->create(['is_active' => 1]);

        $this->assertIsBool($customer->is_active);
        $this->assertTrue($customer->is_active);
    }

    public function test_customer_active_scope(): void
    {
        $user = User::factory()->create();
        Customer::factory()->create(['user_id' => $user->id, 'is_active' => true]);
        Customer::factory()->inactive()->create(['user_id' => $user->id]);

        $active = Customer::active()->where('user_id', $user->id)->get();

        $this->assertCount(1, $active);
        $this->assertTrue($active->first()->is_active);
    }

    public function test_customer_search_scope(): void
    {
        $user = User::factory()->create();
        Customer::factory()->create(['user_id' => $user->id, 'name' => 'John Smith']);
        Customer::factory()->create(['user_id' => $user->id, 'name' => 'Jane Doe']);

        $results = Customer::search('John')->where('user_id', $user->id)->get();

        $this->assertCount(1, $results);
        $this->assertEquals('John Smith', $results->first()->name);
    }
}
