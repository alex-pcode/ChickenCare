<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ComponentShowcaseTest extends TestCase
{
    use RefreshDatabase;

    public function test_showcase_page_loads_for_authenticated_user(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/app/components');

        $response->assertStatus(200);
        $response->assertSee('Components Showcase');
    }

    public function test_showcase_page_requires_authentication(): void
    {
        $response = $this->get('/app/components');

        $response->assertRedirect('/login');
    }
}
