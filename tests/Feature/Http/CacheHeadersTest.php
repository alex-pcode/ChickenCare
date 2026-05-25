<?php

namespace Tests\Feature\Http;

use App\Models\Expense;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

class CacheHeadersTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_auth_page_returns_private_no_store(): void
    {
        $response = $this->get(route('login'));

        $response->assertOk();

        $this->assertPrivateNoStore($response);
    }

    public function test_guest_marketing_page_returns_private_no_store(): void
    {
        $response = $this->get(route('landing'));

        $response->assertOk();

        $this->assertPrivateNoStore($response);
    }

    public function test_authenticated_dashboard_returns_private_no_store(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('app.dashboard'));

        $response->assertOk();

        $this->assertPrivateNoStore($response);
    }

    public function test_boosted_authenticated_full_page_returns_private_no_store(): void
    {
        $user = User::factory()->premium()->create();

        $response = $this->actingAs($user)
            ->withHeaders([
                'HX-Request' => 'true',
                'HX-Boosted' => 'true',
            ])
            ->get(route('app.expenses.index'));

        $response->assertOk();
        $response->assertViewIs('expenses.index');

        $this->assertPrivateNoStore($response);
    }

    public function test_authenticated_htmx_partial_returns_private_no_store(): void
    {
        $user = User::factory()->premium()->create();
        Expense::factory()->create(['user_id' => $user->id]);

        $response = $this->actingAs($user)
            ->withHeaders(['HX-Request' => 'true'])
            ->get(route('app.expenses.index'));

        $response->assertOk();
        $response->assertViewIs('expenses.partials.records-table');

        $this->assertPrivateNoStore($response);
    }

    public function test_authenticated_json_endpoint_returns_private_no_store(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->getJson(route('app.dashboard.data'));

        $response->assertOk();

        $this->assertPrivateNoStore($response);
    }

    public function test_premium_gate_fragment_returns_private_no_store(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->withHeaders(['HX-Request' => 'true'])
            ->get(route('app.expenses.index'));

        $response->assertOk();
        $response->assertViewIs('partials.premium-gate');

        $this->assertPrivateNoStore($response);
    }

    public function test_validation_error_response_returns_private_no_store(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->withHeaders(['HX-Request' => 'true'])
            ->post(route('app.eggs.store'), []);

        $response->assertStatus(422);

        $this->assertPrivateNoStore($response);
    }

    public function test_guest_redirect_to_login_returns_private_no_store(): void
    {
        $response = $this->get('/app/');

        $response->assertRedirect(route('login', absolute: false));

        $this->assertPrivateNoStore($response);
    }

    private function assertPrivateNoStore(TestResponse $response): void
    {
        $cacheControl = $response->headers->get('Cache-Control', '');

        $this->assertStringContainsString('private', $cacheControl);
        $this->assertStringContainsString('no-store', $cacheControl);
        $this->assertStringNotContainsString('public', $cacheControl);
        $response->assertHeaderMissing('ETag');
        $response->assertHeaderMissing('Last-Modified');
    }
}
