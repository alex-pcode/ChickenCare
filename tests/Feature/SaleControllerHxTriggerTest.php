<?php

namespace Tests\Feature;

use App\Models\Sale;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SaleControllerHxTriggerTest extends TestCase
{
    use RefreshDatabase;

    public function test_store_returns_crm_changed_trigger(): void
    {
        $user = User::factory()->create(['tier' => 'premium']);

        $response = $this->actingAs($user)
            ->withHeaders(['HX-Request' => 'true'])
            ->post(route('app.sales.store'), [
                'sale_date' => now()->format('Y-m-d'),
                'dozen_count' => 2,
                'individual_count' => 3,
                'total_amount' => 10.00,
            ]);

        $response->assertHeader('HX-Trigger', 'crm:changed');
    }

    public function test_update_returns_crm_changed_trigger(): void
    {
        $user = User::factory()->create(['tier' => 'premium']);
        $sale = Sale::factory()->create(['user_id' => $user->id]);

        $response = $this->actingAs($user)
            ->withHeaders(['HX-Request' => 'true'])
            ->put(route('app.sales.update', $sale), [
                'sale_date' => now()->format('Y-m-d'),
                'dozen_count' => 2,
                'individual_count' => 3,
                'total_amount' => 10.00,
            ]);

        $response->assertHeader('HX-Trigger', 'crm:changed');
    }

    public function test_destroy_returns_crm_changed_trigger(): void
    {
        $user = User::factory()->create(['tier' => 'premium']);
        $sale = Sale::factory()->create(['user_id' => $user->id]);

        $response = $this->actingAs($user)
            ->withHeaders(['HX-Request' => 'true'])
            ->delete(route('app.sales.destroy', $sale));

        $response->assertHeader('HX-Trigger', 'crm:changed');
    }

    public function test_toggle_payment_returns_crm_changed_trigger(): void
    {
        $user = User::factory()->create(['tier' => 'premium']);
        $sale = Sale::factory()->create(['user_id' => $user->id, 'paid' => false]);

        $response = $this->actingAs($user)
            ->withHeaders(['HX-Request' => 'true'])
            ->patch(route('app.sales.toggle-payment', $sale));

        $response->assertHeader('HX-Trigger', 'crm:changed');
    }
}
