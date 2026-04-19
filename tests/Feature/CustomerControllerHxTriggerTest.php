<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CustomerControllerHxTriggerTest extends TestCase
{
    use RefreshDatabase;

    public function test_store_returns_crm_changed_trigger(): void
    {
        $user = User::factory()->create(['tier' => 'premium']);

        $response = $this->actingAs($user)
            ->withHeaders(['HX-Request' => 'true'])
            ->post(route('app.customers.store'), ['name' => 'Test Customer']);

        $response->assertHeader('HX-Trigger', 'crm:changed');
    }

    public function test_update_returns_crm_changed_trigger(): void
    {
        $user = User::factory()->create(['tier' => 'premium']);
        $customer = Customer::factory()->create(['user_id' => $user->id]);

        $response = $this->actingAs($user)
            ->withHeaders(['HX-Request' => 'true'])
            ->put(route('app.customers.update', $customer), ['name' => 'Updated Name']);

        $response->assertHeader('HX-Trigger', 'crm:changed');
    }

    public function test_destroy_returns_crm_changed_trigger(): void
    {
        $user = User::factory()->create(['tier' => 'premium']);
        $customer = Customer::factory()->create(['user_id' => $user->id]);

        $response = $this->actingAs($user)
            ->withHeaders(['HX-Request' => 'true'])
            ->delete(route('app.customers.destroy', $customer));

        $response->assertHeader('HX-Trigger', 'crm:changed');
    }
}
