<?php

namespace Tests\Feature;

use App\Models\EggEntry;
use App\Models\Expense;
use App\Models\FeedInventory;
use App\Models\FlockProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardWelcomeTest extends TestCase
{
    use RefreshDatabase;

    public function test_welcome_header_shows_user_name(): void
    {
        $user = User::factory()->create(['name' => 'Alice']);

        $response = $this->actingAs($user)->get('/app/');

        $response->assertStatus(200);
        $response->assertSee('Welcome Alice');
    }

    public function test_welcome_header_falls_back_to_email_localpart(): void
    {
        $user = User::factory()->create(['name' => '', 'email' => 'bob@example.com']);

        $response = $this->actingAs($user)->get('/app/');

        $response->assertStatus(200);
        $response->assertSee('Welcome bob');
    }

    public function test_welcome_header_shows_user_as_final_fallback(): void
    {
        $user = User::factory()->create(['name' => '', 'email' => '@example.com']);

        $response = $this->actingAs($user)->get('/app/');

        $response->assertStatus(200);
        $response->assertSee('Welcome User');
    }

    public function test_setup_progress_hidden_when_all_items_complete(): void
    {
        $user = User::factory()->create();
        FlockProfile::factory()->create(['user_id' => $user->id]);
        EggEntry::factory()->create(['user_id' => $user->id]);
        Expense::factory()->create(['user_id' => $user->id]);
        FeedInventory::factory()->create(['user_id' => $user->id]);

        $response = $this->actingAs($user)->get('/app/');

        $response->assertStatus(200);
        $response->assertDontSee('Setup Progress');
    }

    public function test_setup_progress_shown_when_incomplete(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/app/');

        $response->assertStatus(200);
        $response->assertSee('Setup Progress');
        $response->assertSee('Getting Started');
    }

    public function test_correct_bracket_heading_for_new_user(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/app/');

        $response->assertStatus(200);
        $response->assertSee('Getting Started');
    }

    public function test_correct_bracket_heading_for_getting_started(): void
    {
        $user = User::factory()->create();
        FlockProfile::factory()->create(['user_id' => $user->id]);

        $response = $this->actingAs($user)->get('/app/');

        $response->assertStatus(200);
        // 42% -> "Building Your Farm" heading
        $response->assertSee('Building Your Farm');
    }

    public function test_correct_bracket_heading_for_active_user(): void
    {
        $user = User::factory()->create();
        FlockProfile::factory()->create(['user_id' => $user->id]);
        EggEntry::factory()->create(['user_id' => $user->id]);
        Expense::factory()->create(['user_id' => $user->id]);

        $response = $this->actingAs($user)->get('/app/');

        $response->assertStatus(200);
        // 83% -> "Advanced Features" heading
        $response->assertSee('Advanced Features');
    }

    public function test_welcome_message_prompts_setup_for_new_user(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/app/');

        $response->assertStatus(200);
        $response->assertSee("Let's get your flock set up.");
    }

    public function test_welcome_message_reflects_partial_progress(): void
    {
        $user = User::factory()->create();
        FlockProfile::factory()->create(['user_id' => $user->id]);

        $response = $this->actingAs($user)->get('/app/');

        $response->assertStatus(200);
        // 42% complete
        $response->assertSee("You're 42% set up");
    }

    public function test_welcome_message_shows_snapshot_when_complete(): void
    {
        $user = User::factory()->create();
        FlockProfile::factory()->create(['user_id' => $user->id]);
        EggEntry::factory()->create(['user_id' => $user->id]);
        Expense::factory()->create(['user_id' => $user->id]);
        FeedInventory::factory()->create(['user_id' => $user->id]);

        $response = $this->actingAs($user)->get('/app/');

        $response->assertStatus(200);
        $response->assertSee("Your flock is all set up. Here's today's snapshot.");
    }

    public function test_view_receives_display_name_and_progress(): void
    {
        $user = User::factory()->create(['name' => 'TestUser']);

        $response = $this->actingAs($user)->get('/app/');

        $response->assertStatus(200);
        $response->assertViewHas('displayName', 'TestUser');
        $response->assertViewHas('progress');

        $progress = $response->viewData('progress');
        $this->assertArrayHasKey('percentage', $progress);
        $this->assertArrayHasKey('bracket', $progress);
        $this->assertArrayHasKey('phase', $progress);
        $this->assertArrayHasKey('items', $progress);
    }
}
