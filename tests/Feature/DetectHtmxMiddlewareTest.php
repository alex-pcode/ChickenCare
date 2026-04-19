<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DetectHtmxMiddlewareTest extends TestCase
{
    use RefreshDatabase;

    public function test_request_is_htmx_returns_true_with_header(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->withHeaders(['HX-Request' => 'true'])
            ->get('/app')
            ->assertOk();

        $this->assertTrue(request()->isHtmx());
    }

    public function test_request_is_htmx_returns_false_without_header(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get('/app')
            ->assertOk();

        $this->assertFalse(request()->isHtmx());
    }
}
