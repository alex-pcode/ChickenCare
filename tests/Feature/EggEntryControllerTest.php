<?php

namespace Tests\Feature;

use App\Models\EggEntry;
use App\Models\User;
use Illuminate\Contracts\Http\Kernel;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EggEntryControllerTest extends TestCase
{
    use RefreshDatabase;

    // === CRUD Operations (Task 12) ===

    public function test_user_can_view_egg_entries_index(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/app/eggs');

        $response->assertStatus(200);
        $response->assertViewIs('eggs.index');
    }

    public function test_user_sees_only_own_entries(): void
    {
        $userA = User::factory()->create();
        $userB = User::factory()->create();

        $entryA = EggEntry::factory()->create(['user_id' => $userA->id, 'date' => '2026-04-01']);
        $entryB = EggEntry::factory()->create(['user_id' => $userB->id, 'date' => '2026-04-02']);

        $response = $this->actingAs($userA)->get('/app/eggs');

        $response->assertStatus(200);
        $response->assertSee('Apr 01, 2026');
        $response->assertDontSee('Apr 02, 2026');
    }

    public function test_user_can_store_egg_entry(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/app/eggs', [
            'date' => '2026-04-10',
            'count' => 5,
            'size' => 'large',
            'color' => 'brown',
            'notes' => 'Good day',
        ]);

        $response->assertRedirect(route('app.eggs.index'));
        $response->assertSessionHas('success');
        $this->assertDatabaseHas('egg_entries', [
            'user_id' => $user->id,
            'count' => 5,
        ]);
    }

    public function test_user_can_store_egg_entry_via_htmx(): void
    {
        $user = User::factory()->create();
        // Existing entry → list region is already on the page, so the new entry
        // is swapped in as a row partial (not a full redirect).
        EggEntry::factory()->create(['user_id' => $user->id, 'date' => '2026-04-09']);

        $response = $this->actingAs($user)
            ->withHeaders(['HX-Request' => 'true'])
            ->post('/app/eggs', [
                'date' => '2026-04-10',
                'count' => 3,
                'size' => 'medium',
                'color' => 'white',
            ]);

        $response->assertStatus(200);
        $response->assertSee('egg-entry-');
        $this->assertDatabaseHas('egg_entries', [
            'user_id' => $user->id,
            'count' => 3,
        ]);
    }

    public function test_first_egg_entry_via_htmx_returns_redirect(): void
    {
        // The very first entry has no list region/#egg-entries-body to swap into,
        // so the controller returns an HX-Redirect to re-render the full page.
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->withHeaders(['HX-Request' => 'true'])
            ->post('/app/eggs', [
                'date' => '2026-04-10',
                'count' => 3,
            ]);

        $response->assertStatus(200);
        $response->assertHeader('HX-Redirect', route('app.eggs.index'));
        $this->assertDatabaseHas('egg_entries', [
            'user_id' => $user->id,
            'count' => 3,
        ]);
    }

    public function test_empty_index_still_renders_egg_entries_body_target(): void
    {
        // Regression: the logging form's hx-target (#egg-entries-body) must exist
        // even with no entries, or htmx aborts the first submit with targetError.
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/app/eggs');

        $response->assertStatus(200);
        $response->assertSee('id="egg-entries-body"', false);
    }

    public function test_user_can_update_egg_entry(): void
    {
        $user = User::factory()->create();
        $entry = EggEntry::factory()->create(['user_id' => $user->id, 'count' => 3]);

        $response = $this->actingAs($user)->put("/app/eggs/{$entry->id}", [
            'date' => $entry->date->format('Y-m-d'),
            'count' => 7,
            'size' => 'jumbo',
        ]);

        $response->assertRedirect(route('app.eggs.index'));
        $response->assertSessionHas('success');
        $this->assertDatabaseHas('egg_entries', ['id' => $entry->id, 'count' => 7]);
    }

    public function test_user_can_update_egg_entry_via_htmx(): void
    {
        $user = User::factory()->create();
        $entry = EggEntry::factory()->create(['user_id' => $user->id, 'count' => 3]);

        $response = $this->actingAs($user)
            ->withHeaders(['HX-Request' => 'true'])
            ->put("/app/eggs/{$entry->id}", [
                'date' => $entry->date->format('Y-m-d'),
                'count' => 10,
            ]);

        $response->assertStatus(200);
        $response->assertSee('egg-entry-');
        $this->assertDatabaseHas('egg_entries', ['id' => $entry->id, 'count' => 10]);
    }

    public function test_user_can_delete_egg_entry(): void
    {
        $user = User::factory()->create();
        $entry = EggEntry::factory()->create(['user_id' => $user->id]);

        $response = $this->actingAs($user)->delete("/app/eggs/{$entry->id}");

        $response->assertRedirect(route('app.eggs.index'));
        $response->assertSessionHas('success');
        $this->assertDatabaseMissing('egg_entries', ['id' => $entry->id]);
    }

    public function test_user_can_delete_egg_entry_via_htmx(): void
    {
        $user = User::factory()->create();
        $entry = EggEntry::factory()->create(['user_id' => $user->id]);

        $response = $this->actingAs($user)
            ->withHeaders(['HX-Request' => 'true'])
            ->delete("/app/eggs/{$entry->id}");

        $response->assertStatus(200);
        $this->assertEquals('', $response->getContent());
        $this->assertDatabaseMissing('egg_entries', ['id' => $entry->id]);
    }

    public function test_user_cannot_update_other_users_entry(): void
    {
        $userA = User::factory()->create();
        $userB = User::factory()->create();
        $entry = EggEntry::factory()->create(['user_id' => $userB->id]);

        $response = $this->actingAs($userA)->put("/app/eggs/{$entry->id}", [
            'date' => $entry->date->format('Y-m-d'),
            'count' => 99,
        ]);

        $response->assertStatus(403);
    }

    public function test_user_cannot_delete_other_users_entry(): void
    {
        $userA = User::factory()->create();
        $userB = User::factory()->create();
        $entry = EggEntry::factory()->create(['user_id' => $userB->id]);

        $response = $this->actingAs($userA)->delete("/app/eggs/{$entry->id}");

        $response->assertStatus(403);
    }

    // === Validation (Task 13) ===

    public function test_store_validates_required_fields(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/app/eggs', []);

        $response->assertSessionHasErrors(['date', 'count']);
    }

    public function test_store_validates_date_not_in_future(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/app/eggs', [
            'date' => '2030-01-01',
            'count' => 1,
        ]);

        $response->assertSessionHasErrors('date');
    }

    public function test_store_validates_count_is_non_negative_integer(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/app/eggs', [
            'date' => '2026-04-10',
            'count' => -1,
        ]);

        $response->assertSessionHasErrors('count');
    }

    public function test_store_validates_size_enum_values(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/app/eggs', [
            'date' => '2026-04-10',
            'count' => 1,
            'size' => 'giant',
        ]);

        $response->assertSessionHasErrors('size');
    }

    public function test_store_validates_color_enum_values(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/app/eggs', [
            'date' => '2026-04-10',
            'count' => 1,
            'color' => 'purple',
        ]);

        $response->assertSessionHasErrors('color');
    }

    public function test_store_accepts_valid_data_with_nullable_fields(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/app/eggs', [
            'date' => '2026-04-10',
            'count' => 4,
        ]);

        $response->assertRedirect(route('app.eggs.index'));
        $this->assertDatabaseHas('egg_entries', [
            'user_id' => $user->id,
            'count' => 4,
            'size' => null,
            'color' => null,
            'notes' => null,
        ]);
    }

    // === Pagination & Empty State (Task 14) ===

    public function test_index_paginates_at_5_items(): void
    {
        $user = User::factory()->create();
        EggEntry::factory()->count(20)->create(['user_id' => $user->id]);

        $response = $this->actingAs($user)->get('/app/eggs');

        $response->assertStatus(200);
        $response->assertViewHas('entries', function ($entries) {
            return $entries->count() === 5 && $entries->total() === 20;
        });
    }

    public function test_index_shows_empty_state_when_no_entries(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/app/eggs');

        $response->assertStatus(200);
        $response->assertSee('No egg entries yet');
    }

    public function test_htmx_pagination_returns_partial(): void
    {
        $user = User::factory()->create();
        EggEntry::factory()->count(20)->create(['user_id' => $user->id]);

        $response = $this->actingAs($user)
            ->withHeaders(['HX-Request' => 'true'])
            ->get('/app/eggs?page=2');

        $response->assertStatus(200);
        $response->assertDontSee('<!DOCTYPE html>');
        $response->assertSee('egg-entry-');
    }

    // === Stats Section (Story 2.4) ===

    public function test_stats_appear_when_entries_exist(): void
    {
        $user = User::factory()->create();
        EggEntry::factory()->create(['user_id' => $user->id, 'date' => now()->format('Y-m-d'), 'count' => 5]);

        $response = $this->actingAs($user)->get('/app/eggs');

        $response->assertStatus(200);
        $response->assertSee('Total Eggs');
        $response->assertSee('Average Daily');
        $response->assertSee('Lay Rate');
        $response->assertSee('Protein Generated');
    }

    public function test_stats_section_absent_when_no_entries(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/app/eggs');

        $response->assertStatus(200);
        $response->assertDontSee('Total Eggs');
        $response->assertDontSee('Average Daily');
    }

    public function test_goal_progress_card_shown_when_yearly_goal_set(): void
    {
        $user = User::factory()->withYearlyGoal(3600)->create();
        EggEntry::factory()->create(['user_id' => $user->id, 'date' => now()->format('Y-m-d'), 'count' => 5]);

        $response = $this->actingAs($user)->get('/app/eggs');

        $response->assertStatus(200);
        $response->assertSee('Monthly Egg Production Goal');
        $response->assertSee('3,600');
        $response->assertDontSee('Set Your Annual Goal');
    }

    public function test_set_goal_cta_shown_when_yearly_goal_null(): void
    {
        $user = User::factory()->create(['yearly_egg_goal' => null]);
        EggEntry::factory()->create(['user_id' => $user->id, 'date' => now()->format('Y-m-d'), 'count' => 5]);

        $response = $this->actingAs($user)->get('/app/eggs');

        $response->assertStatus(200);
        $response->assertSee('Set Your Annual Goal');
        $response->assertDontSee('Monthly Egg Production Goal');
    }

    public function test_stat_card_values_match_expected(): void
    {
        $user = User::factory()->create();
        EggEntry::factory()->create(['user_id' => $user->id, 'date' => now()->format('Y-m-d'), 'count' => 8]);

        $response = $this->actingAs($user)->get('/app/eggs');

        $response->assertStatus(200);
        // 8 eggs total
        $response->assertSee('8');
        // protein: 8 * 0.125 = 1 lb
        $response->assertSee('1 lbs');
        // Lay rate placeholder
        $response->assertSee('--');
        $response->assertSee('available after flock setup');
    }

    public function test_comparison_cards_display(): void
    {
        $user = User::factory()->create();
        EggEntry::factory()->create(['user_id' => $user->id, 'date' => now()->format('Y-m-d'), 'count' => 3]);

        $response = $this->actingAs($user)->get('/app/eggs');

        $response->assertStatus(200);
        $response->assertSee('7 Day Comparison');
        $response->assertSee('Monthly Comparison');
    }

    // === Edit Form Partial (Task 15) ===

    public function test_user_can_get_edit_form_via_htmx(): void
    {
        $user = User::factory()->create();
        $entry = EggEntry::factory()->create([
            'user_id' => $user->id,
            'date' => '2026-04-05',
            'count' => 6,
        ]);

        $response = $this->actingAs($user)->get("/app/eggs/{$entry->id}/edit-form");

        $response->assertStatus(200);
        $response->assertSee('2026-04-05');
        $response->assertSee('value="6"', false);
    }

    public function test_user_cannot_get_edit_form_for_other_users_entry(): void
    {
        $userA = User::factory()->create();
        $userB = User::factory()->create();
        $entry = EggEntry::factory()->create(['user_id' => $userB->id]);

        $response = $this->actingAs($userA)->get("/app/eggs/{$entry->id}/edit-form");

        $response->assertStatus(403);
    }

    // === Duplicate Detection (Story 2.5 Task 8) ===

    public function test_duplicate_date_size_color_returns_confirmation_via_htmx(): void
    {
        $user = User::factory()->create();
        EggEntry::factory()->create([
            'user_id' => $user->id,
            'date' => '2026-04-10',
            'count' => 3,
            'size' => 'large',
            'color' => 'brown',
        ]);

        $response = $this->actingAs($user)
            ->withHeaders(['HX-Request' => 'true'])
            ->post('/app/eggs', [
                'date' => '2026-04-10',
                'count' => 5,
                'size' => 'large',
                'color' => 'brown',
            ]);

        $response->assertStatus(200);
        $response->assertSee('Duplicate Entry Detected');
        $response->assertSee('confirm_update');
    }

    public function test_duplicate_with_confirm_update_updates_existing_entry(): void
    {
        $user = User::factory()->create();
        $existing = EggEntry::factory()->create([
            'user_id' => $user->id,
            'date' => '2026-04-10',
            'count' => 3,
            'size' => 'large',
            'color' => 'brown',
        ]);

        $response = $this->actingAs($user)
            ->withHeaders(['HX-Request' => 'true'])
            ->post('/app/eggs', [
                'date' => '2026-04-10',
                'count' => 8,
                'size' => 'large',
                'color' => 'brown',
                'confirm_update' => 1,
            ]);

        $response->assertStatus(200);
        $response->assertSee('egg-entry-');
        $this->assertDatabaseHas('egg_entries', ['id' => $existing->id, 'count' => 8]);
        $this->assertDatabaseCount('egg_entries', 1);
    }

    public function test_non_duplicate_creates_normally(): void
    {
        $user = User::factory()->create();
        EggEntry::factory()->create([
            'user_id' => $user->id,
            'date' => '2026-04-10',
            'count' => 3,
            'size' => 'large',
            'color' => 'brown',
        ]);

        $response = $this->actingAs($user)->post('/app/eggs', [
            'date' => '2026-04-11',
            'count' => 5,
            'size' => 'large',
            'color' => 'brown',
        ]);

        $response->assertRedirect(route('app.eggs.index'));
        $this->assertDatabaseCount('egg_entries', 2);
    }

    public function test_duplicate_date_with_different_size_creates_new_entry(): void
    {
        $user = User::factory()->create();
        EggEntry::factory()->create([
            'user_id' => $user->id,
            'date' => '2026-04-10',
            'count' => 3,
            'size' => 'large',
            'color' => 'brown',
        ]);

        $response = $this->actingAs($user)->post('/app/eggs', [
            'date' => '2026-04-10',
            'count' => 2,
            'size' => 'small',
            'color' => 'brown',
        ]);

        $response->assertRedirect(route('app.eggs.index'));
        $this->assertDatabaseCount('egg_entries', 2);
    }

    public function test_standard_duplicate_redirects_back_with_warning(): void
    {
        $user = User::factory()->create();
        EggEntry::factory()->create([
            'user_id' => $user->id,
            'date' => '2026-04-10',
            'count' => 3,
            'size' => null,
            'color' => null,
        ]);

        $response = $this->actingAs($user)->post('/app/eggs', [
            'date' => '2026-04-10',
            'count' => 5,
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('warning');
        $this->assertDatabaseCount('egg_entries', 1);
    }

    // === Backfill (Story 2.5 Task 9) ===

    public function test_backfill_form_route_returns_modal_partial(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/app/eggs/backfill-form');

        $response->assertStatus(200);
        $response->assertSee('Backfill History');
    }

    public function test_backfill_post_creates_one_entry_per_day_in_range(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/app/eggs/backfill', [
            'start_date' => now()->subDays(6)->format('Y-m-d'),
            'end_date' => now()->format('Y-m-d'),
            'average' => 10,
        ]);

        $response->assertRedirect(route('app.eggs.index'));
        // 6 days ago .. today inclusive = 7 days
        $this->assertDatabaseCount('egg_entries', 7);
    }

    public function test_backfill_counts_vary_around_the_average(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->post('/app/eggs/backfill', [
            'start_date' => now()->subDays(6)->format('Y-m-d'),
            'end_date' => now()->format('Y-m-d'),
            'average' => 10,
        ]);

        // variance = max(1, round(10 * 0.3)) = 3, so counts stay within [7, 13].
        $counts = $user->eggEntries()->pluck('count');
        $this->assertCount(7, $counts);
        foreach ($counts as $count) {
            $this->assertGreaterThanOrEqual(7, $count);
            $this->assertLessThanOrEqual(13, $count);
        }
    }

    public function test_backfill_skips_days_that_already_have_an_entry(): void
    {
        $user = User::factory()->create();
        $existingDate = now()->subDays(3)->format('Y-m-d');
        EggEntry::factory()->create(['user_id' => $user->id, 'date' => $existingDate, 'count' => 99]);

        $this->actingAs($user)->post('/app/eggs/backfill', [
            'start_date' => now()->subDays(6)->format('Y-m-d'),
            'end_date' => now()->format('Y-m-d'),
            'average' => 10,
        ]);

        // 7 days in range, 1 already had an entry → 6 created + the original = 7 total.
        $this->assertDatabaseCount('egg_entries', 7);
        // The pre-existing entry is untouched (still a single row at count 99).
        $this->assertSame(1, $user->eggEntries()->whereDate('date', $existingDate)->count());
        $this->assertSame(99, $user->eggEntries()->whereDate('date', $existingDate)->value('count'));
    }

    public function test_backfill_rejects_start_date_over_90_days_ago(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/app/eggs/backfill', [
            'start_date' => now()->subDays(91)->format('Y-m-d'),
            'end_date' => now()->format('Y-m-d'),
            'average' => 10,
        ]);

        $response->assertSessionHasErrors('start_date');
        $this->assertDatabaseCount('egg_entries', 0);
    }

    public function test_backfill_rejects_end_date_before_start_date(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/app/eggs/backfill', [
            'start_date' => now()->subDays(2)->format('Y-m-d'),
            'end_date' => now()->subDays(5)->format('Y-m-d'),
            'average' => 10,
        ]);

        $response->assertSessionHasErrors('end_date');
        $this->assertDatabaseCount('egg_entries', 0);
    }

    public function test_backfill_validates_required_fields(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/app/eggs/backfill', []);

        $response->assertSessionHasErrors(['start_date', 'end_date', 'average']);
    }

    public function test_backfill_entries_scoped_to_authenticated_user(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->post('/app/eggs/backfill', [
            'start_date' => now()->subDays(1)->format('Y-m-d'),
            'end_date' => now()->format('Y-m-d'),
            'average' => 4,
        ]);

        $this->assertDatabaseCount('egg_entries', 2);
        $this->assertEquals($user->id, $user->eggEntries()->first()->user_id);
    }

    public function test_backfill_button_shows_when_entries_empty(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/app/eggs');

        $response->assertStatus(200);
        $response->assertSee('Backfill History');
    }

    public function test_backfill_button_hidden_when_entries_exist(): void
    {
        $user = User::factory()->create();
        EggEntry::factory()->create(['user_id' => $user->id]);

        $response = $this->actingAs($user)->get('/app/eggs');

        $response->assertStatus(200);
        $response->assertDontSee('Backfill History');
    }

    // === Advanced Toggle (Story 2.5 Task 10) ===

    public function test_store_works_without_size_and_color(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/app/eggs', [
            'date' => '2026-04-10',
            'count' => 3,
        ]);

        $response->assertRedirect(route('app.eggs.index'));
        $this->assertDatabaseHas('egg_entries', [
            'user_id' => $user->id,
            'count' => 3,
            'size' => null,
            'color' => null,
        ]);
    }

    public function test_page_renders_with_size_and_color_fields_in_dom(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/app/eggs');

        $response->assertStatus(200);
        $response->assertSee('name="size"', false);
        $response->assertSee('name="color"', false);
        $response->assertSee('Enable detailed tracking');
    }

    // === Story 2.3: Validation Boundary & Edge-Case Tests (Task 3) ===

    public function test_store_validates_count_maximum(): void
    {
        $user = User::factory()->create();

        // No explicit max constraint in rules — count=99999 should succeed
        $response = $this->actingAs($user)->post('/app/eggs', [
            'date' => '2026-04-10',
            'count' => 99999,
        ]);

        $response->assertRedirect(route('app.eggs.index'));
        $this->assertDatabaseHas('egg_entries', [
            'user_id' => $user->id,
            'count' => 99999,
        ]);
    }

    public function test_store_validates_date_format(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/app/eggs', [
            'date' => 'not-a-date',
            'count' => 1,
        ]);

        $response->assertSessionHasErrors('date');
    }

    public function test_store_allows_duplicate_date(): void
    {
        $user = User::factory()->create();
        EggEntry::factory()->create([
            'user_id' => $user->id,
            'date' => '2026-04-10',
            'size' => 'large',
            'color' => 'brown',
        ]);

        // Same date but different size — no unique constraint, should create new entry
        $response = $this->actingAs($user)->post('/app/eggs', [
            'date' => '2026-04-10',
            'count' => 2,
            'size' => 'small',
            'color' => 'white',
        ]);

        $response->assertRedirect(route('app.eggs.index'));
        $this->assertEquals(2, EggEntry::where('user_id', $user->id)->count());
    }

    public function test_update_validates_same_rules_as_store(): void
    {
        $user = User::factory()->create();
        $entry = EggEntry::factory()->create(['user_id' => $user->id]);

        $response = $this->actingAs($user)->put("/app/eggs/{$entry->id}", [
            'date' => '2030-01-01',
            'count' => -1,
        ]);

        $response->assertSessionHasErrors(['date', 'count']);
    }

    public function test_store_trims_notes_whitespace(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/app/eggs', [
            'date' => '2026-04-10',
            'count' => 3,
            'notes' => '  leading spaces  ',
        ]);

        $response->assertRedirect(route('app.eggs.index'));
        // Laravel's TrimStrings middleware trims whitespace
        $this->assertDatabaseHas('egg_entries', [
            'user_id' => $user->id,
            'notes' => 'leading spaces',
        ]);
    }

    public function test_store_accepts_zero_count(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/app/eggs', [
            'date' => '2026-04-10',
            'count' => 0,
        ]);

        $response->assertRedirect(route('app.eggs.index'));
        $this->assertDatabaseHas('egg_entries', [
            'user_id' => $user->id,
            'count' => 0,
        ]);
    }

    public function test_store_rejects_negative_count(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/app/eggs', [
            'date' => '2026-04-10',
            'count' => -1,
        ]);

        $response->assertSessionHasErrors('count');
        $this->assertDatabaseMissing('egg_entries', ['user_id' => $user->id]);
    }

    // === Story 2.3: HTMX Error Handling Tests (Task 4) ===

    public function test_htmx_store_validation_failure_returns_422(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->withHeaders(['HX-Request' => 'true'])
            ->post('/app/eggs', [
                'date' => '',
                'count' => '',
            ]);

        $response->assertStatus(422);
    }

    public function test_htmx_update_validation_failure_returns_422(): void
    {
        $user = User::factory()->create();
        $entry = EggEntry::factory()->create(['user_id' => $user->id]);

        $response = $this->actingAs($user)
            ->withHeaders(['HX-Request' => 'true'])
            ->put("/app/eggs/{$entry->id}", [
                'date' => '',
                'count' => -1,
            ]);

        $response->assertStatus(422);
    }

    public function test_htmx_update_unauthorized_returns_403(): void
    {
        $userA = User::factory()->create();
        $userB = User::factory()->create();
        $entry = EggEntry::factory()->create(['user_id' => $userB->id]);

        $response = $this->actingAs($userA)
            ->withHeaders(['HX-Request' => 'true'])
            ->put("/app/eggs/{$entry->id}", [
                'date' => $entry->date->format('Y-m-d'),
                'count' => 99,
            ]);

        $response->assertStatus(403);
    }

    public function test_htmx_delete_unauthorized_returns_403(): void
    {
        $userA = User::factory()->create();
        $userB = User::factory()->create();
        $entry = EggEntry::factory()->create(['user_id' => $userB->id]);

        $response = $this->actingAs($userA)
            ->withHeaders(['HX-Request' => 'true'])
            ->delete("/app/eggs/{$entry->id}");

        $response->assertStatus(403);
    }

    // === Story 2.3: Tier Access Tests (Task 5) ===

    public function test_free_user_can_access_eggs_index(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/app/eggs');

        $response->assertStatus(200);
    }

    public function test_premium_user_can_access_eggs_index(): void
    {
        $user = User::factory()->premium()->create();

        $response = $this->actingAs($user)->get('/app/eggs');

        $response->assertStatus(200);
    }

    public function test_unauthenticated_user_redirected_from_eggs(): void
    {
        $response = $this->get('/app/eggs');

        $response->assertRedirect('/login');
    }

    // === Story 2.3: Policy Edge-Case Tests (Task 6) ===

    public function test_index_only_shows_authenticated_users_entries(): void
    {
        $userA = User::factory()->create();
        $userB = User::factory()->create();
        $userC = User::factory()->create();

        EggEntry::factory()->create(['user_id' => $userA->id, 'count' => 7, 'date' => '2026-04-01']);
        EggEntry::factory()->create(['user_id' => $userB->id, 'count' => 13, 'date' => '2026-04-02']);
        EggEntry::factory()->create(['user_id' => $userC->id, 'count' => 19, 'date' => '2026-04-03']);

        $response = $this->actingAs($userA)->get('/app/eggs');

        $response->assertStatus(200);
        $response->assertViewHas('entries', function ($entries) {
            return $entries->total() === 1;
        });
    }

    // === Story 2.3: CSRF Protection Test (Task 7) ===

    public function test_csrf_middleware_is_active_on_egg_routes(): void
    {
        // Laravel's PreventRequestForgery bypasses in tests (runningUnitTests()),
        // so we verify the middleware is registered in the web group instead.
        $kernel = $this->app->make(Kernel::class);
        $middlewareGroups = $kernel->getMiddlewareGroups();

        $this->assertContains(
            PreventRequestForgery::class,
            $middlewareGroups['web']
        );
    }

    // === Story 2.6: Hero Animation Tests (QA Fix TEST-001) ===

    public function test_hero_section_displays_on_egg_counter_page(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/app/eggs');

        $response->assertStatus(200);
        $response->assertSee('egg-hero', false);
        $response->assertSee('egg-hero__image', false);
        $response->assertSee('egg-hero__badge', false);
        $response->assertSee('egg-hero__status', false);
    }

    public function test_hero_image_has_correct_attributes(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/app/eggs');

        $response->assertStatus(200);
        $response->assertSee('/images/hen-on-eggs.webp', false);
        $response->assertSee('alt="Hen sitting on eggs"', false);
        $response->assertSee('egg-hero__image--animated', false);
    }

    public function test_hero_badge_has_aria_hidden_true(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/app/eggs');

        $response->assertStatus(200);
        $response->assertSee('egg-hero__badge', false);
        $response->assertSee('aria-hidden="true"', false);
    }

    public function test_hero_status_has_aria_status_role(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/app/eggs');

        $response->assertStatus(200);
        $response->assertSee('role="status"', false);
        $response->assertSee('No eggs logged yet');
    }

    public function test_hero_status_reflects_logged_today(): void
    {
        $user = User::factory()->create();
        $user->eggEntries()->create([
            'date' => now()->toDateString(),
            'count' => 3,
        ]);

        $response = $this->actingAs($user)->get('/app/eggs');

        $response->assertStatus(200);
        $response->assertSee('You collected eggs today!');
        $response->assertSee('egg-hero__status--success', false);
    }

    public function test_hero_status_reflects_days_since_last_entry(): void
    {
        $user = User::factory()->create();
        $user->eggEntries()->create([
            'date' => now()->subDays(4)->toDateString(),
            'count' => 2,
        ]);

        $response = $this->actingAs($user)->get('/app/eggs');

        $response->assertStatus(200);
        $response->assertViewHas('daysSinceLastEntry', 4);
        $response->assertViewHas('loggedToday', false);
        $response->assertViewHas('todayTotal', 0);
    }

    public function test_response_contains_reduced_motion_media_query(): void
    {
        // Verify the SCSS file contains reduced motion support
        $scssContent = file_get_contents(base_path('resources/scss/features/_egg-counter.scss'));
        $this->assertStringContainsString('prefers-reduced-motion', $scssContent ?? '');
    }

    public function test_combined_animation_class_present_in_stylesheet(): void
    {
        // Verify the SCSS file contains the rotation animation
        $scssContent = file_get_contents(base_path('resources/scss/features/_egg-counter.scss'));
        $this->assertStringContainsString('rotate-gentle', $scssContent ?? '');
        $this->assertStringContainsString('egg-hero__image--animated', $scssContent ?? '');
    }

    // === Story 2: Neumorphic Form QA Fixes (TEST-001) ===

    public function test_page_contains_form_submit_state_classes(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/app/eggs');

        $response->assertStatus(200);
        $response->assertSee('submit-button', false);
        $response->assertSee('shiny-cta', false);
        $response->assertSee('submit-button__spinner', false);
        $response->assertSee('submit-button--submitting', false);
        $response->assertSee('submit-button--success', false);
        $response->assertSee('Saving…', false);
        $response->assertSee('Saved!', false);
        $response->assertSee('Log Eggs', false);
    }

    public function test_form_has_alpine_state_management(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/app/eggs');

        $response->assertStatus(200);
        $response->assertSee('x-data="{ detailed: false, submitting: false, success: false }"', false);
        $response->assertSee('x-data="{ submitting: false, success: false }"', false);
        $response->assertSee(':disabled="submitting || success"', false);
        $response->assertSee('submitting = true; success = false', false);
        $response->assertSee('form.addEventListener(\'htmx:beforeRequest\'', false);
        $response->assertSee('form.addEventListener(\'htmx:afterRequest\'', false);
        $response->assertSee('setTimeout(() => { success = false; }, 2500);', false);
        $response->assertSee('$el.reset(); detailed = false', false);
    }

    public function test_page_contains_recent_entries_header(): void
    {
        $user = User::factory()->create();
        EggEntry::factory()->create(['user_id' => $user->id, 'count' => 5]);

        $response = $this->actingAs($user)->get('/app/eggs');

        $response->assertStatus(200);
        $response->assertSee('egg-counter__table-header-title', false);
        $response->assertSee('Recent Entries');
    }

    public function test_entry_row_has_count_styling(): void
    {
        $user = User::factory()->create();
        $entry = EggEntry::factory()->create(['user_id' => $user->id, 'count' => 7]);

        $response = $this->actingAs($user)->get("/app/eggs/{$entry->id}/row");

        $response->assertStatus(200);
        $response->assertSee('egg-counter__table-count', false);
    }

    public function test_entry_row_displays_size_as_title_case(): void
    {
        $user = User::factory()->create();
        $entry = EggEntry::factory()->create([
            'user_id' => $user->id,
            'size' => 'extra-large',
        ]);

        $response = $this->actingAs($user)->get("/app/eggs/{$entry->id}/row");

        $response->assertStatus(200);
        $response->assertSee('Extra Large');
        $response->assertDontSee('extra-large');
    }

    public function test_entry_row_displays_dash_when_size_null(): void
    {
        $user = User::factory()->create();
        $entry = EggEntry::factory()->create([
            'user_id' => $user->id,
            'size' => null,
        ]);

        $response = $this->actingAs($user)->get("/app/eggs/{$entry->id}/row");

        $response->assertStatus(200);
        $response->assertSee('egg-counter__table-empty', false);
        $response->assertSee('—');
    }

    public function test_entry_row_color_dot_uses_css_classes(): void
    {
        $user = User::factory()->create();
        $entry = EggEntry::factory()->create([
            'user_id' => $user->id,
            'color' => 'brown',
        ]);

        $response = $this->actingAs($user)->get("/app/eggs/{$entry->id}/row");

        $response->assertStatus(200);
        $response->assertSee('egg-counter__color-dot', false);
        $response->assertSee('egg-counter__color-dot--brown', false);
        $response->assertDontSee('style="background-color:', false);
    }

    public function test_entry_row_allows_all_color_enum_values(): void
    {
        $user = User::factory()->create();

        $colors = ['white', 'brown', 'blue', 'green', 'speckled', 'cream'];

        foreach ($colors as $color) {
            $entry = EggEntry::factory()->create([
                'user_id' => $user->id,
                'color' => $color,
            ]);

            $response = $this->actingAs($user)->get("/app/eggs/{$entry->id}/row");

            $response->assertStatus(200);
            $response->assertSee('egg-counter__color-dot--'.$color, false);
        }
    }

    public function test_entry_row_displays_dash_when_color_null(): void
    {
        $user = User::factory()->create();
        $entry = EggEntry::factory()->create([
            'user_id' => $user->id,
            'color' => null,
        ]);

        $response = $this->actingAs($user)->get("/app/eggs/{$entry->id}/row");

        $response->assertStatus(200);
        $response->assertSee('egg-counter__table-empty', false);
        $response->assertSee('—');
    }

    public function test_entry_row_notes_has_truncation_styling(): void
    {
        $user = User::factory()->create();
        $entry = EggEntry::factory()->create([
            'user_id' => $user->id,
            'notes' => 'This is a very long note that should be truncated in the table view',
        ]);

        $response = $this->actingAs($user)->get("/app/eggs/{$entry->id}/row");

        $response->assertStatus(200);
        $response->assertSee('egg-counter__table-notes', false);
        $response->assertSee('title="'.$entry->notes.'"', false);
    }

    public function test_entry_row_displays_dash_when_notes_null(): void
    {
        $user = User::factory()->create();
        $entry = EggEntry::factory()->create([
            'user_id' => $user->id,
            'notes' => null,
        ]);

        $response = $this->actingAs($user)->get("/app/eggs/{$entry->id}/row");

        $response->assertStatus(200);
        $response->assertSee('egg-counter__table-empty', false);
        $response->assertSee('—');
    }

    public function test_delete_button_has_icon_no_text(): void
    {
        $user = User::factory()->create();
        $entry = EggEntry::factory()->create(['user_id' => $user->id]);

        $response = $this->actingAs($user)->get("/app/eggs/{$entry->id}/row");

        $response->assertStatus(200);
        $response->assertSee('data-table__delete-btn', false);
        $response->assertSee('hx-get="'.route('app.eggs.delete-confirm', $entry).'"', false);
        $response->assertSee('hx-target="#modal-container"', false);
        $response->assertSee('fill="none"', false);
        $response->assertSee('stroke="currentColor"', false);
        // Should NOT see the word "Delete" as text (only in aria-label)
        $content = $response->getContent();
        $this->assertStringNotContainsString('>Delete</button>', $content ?? '');
    }

    public function test_delete_button_opens_modal_not_native_confirm(): void
    {
        $user = User::factory()->create();
        $entry = EggEntry::factory()->create(['user_id' => $user->id]);

        $response = $this->actingAs($user)->get("/app/eggs/{$entry->id}/row");

        $response->assertStatus(200);
        // Should use hx-get to load modal, not hx-delete with confirm
        $response->assertSee('hx-get="'.route('app.eggs.delete-confirm', $entry).'"', false);
        $response->assertSee('hx-target="#modal-container"', false);
        // Should NOT have native hx-confirm
        $response->assertDontSee('hx-confirm', false);
    }

    public function test_delete_confirm_modal_route_returns_modal(): void
    {
        $user = User::factory()->create();
        $entry = EggEntry::factory()->create(['user_id' => $user->id, 'date' => '2026-04-10', 'count' => 5]);

        $response = $this->actingAs($user)->get("/app/eggs/{$entry->id}/delete-confirm");

        $response->assertStatus(200);
        $response->assertSee('Delete Egg Entry');
        $response->assertSee('Apr 10, 2026');
        $response->assertSee('5 eggs');
        $response->assertSee('Cancel');
        $response->assertSee('Delete');
        $response->assertSee('This action cannot be undone');
    }

    public function test_delete_confirm_modal_has_proper_htmx_attributes(): void
    {
        $user = User::factory()->create();
        $entry = EggEntry::factory()->create(['user_id' => $user->id]);

        $response = $this->actingAs($user)->get("/app/eggs/{$entry->id}/delete-confirm");

        $response->assertStatus(200);
        $response->assertSee('hx-delete="'.route('app.eggs.destroy', $entry).'"', false);
        $response->assertSee('hx-target="#egg-entry-'.$entry->id.'"', false);
        $response->assertSee('hx-swap="outerHTML swap:500ms"', false);
    }

    public function test_delete_confirm_modal_authorized_for_owner(): void
    {
        $user = User::factory()->create();
        $entry = EggEntry::factory()->create(['user_id' => $user->id]);

        $response = $this->actingAs($user)->get("/app/eggs/{$entry->id}/delete-confirm");

        $response->assertStatus(200);
    }

    public function test_delete_confirm_modal_unauthorized_for_non_owner(): void
    {
        $userA = User::factory()->create();
        $userB = User::factory()->create();
        $entry = EggEntry::factory()->create(['user_id' => $userB->id]);

        $response = $this->actingAs($userA)->get("/app/eggs/{$entry->id}/delete-confirm");

        $response->assertStatus(403);
    }

    public function test_scss_contains_egg_color_custom_properties(): void
    {
        $scssContent = file_get_contents(base_path('resources/scss/features/_egg-counter.scss'));

        $this->assertStringContainsString('--egg-color-white', $scssContent ?? '');
        $this->assertStringContainsString('--egg-color-brown', $scssContent ?? '');
        $this->assertStringContainsString('--egg-color-blue', $scssContent ?? '');
        $this->assertStringContainsString('--egg-color-green', $scssContent ?? '');
        $this->assertStringContainsString('--egg-color-speckled', $scssContent ?? '');
        $this->assertStringContainsString('--egg-color-cream', $scssContent ?? '');
        $this->assertStringContainsString('--egg-color-default', $scssContent ?? '');
    }

    public function test_scss_has_color_dot_modifier_classes(): void
    {
        $scssContent = file_get_contents(base_path('resources/scss/features/_egg-counter.scss'));

        $this->assertStringContainsString('&--white', $scssContent ?? '');
        $this->assertStringContainsString('&--brown', $scssContent ?? '');
        $this->assertStringContainsString('&--blue', $scssContent ?? '');
        $this->assertStringContainsString('&--green', $scssContent ?? '');
        $this->assertStringContainsString('&--speckled', $scssContent ?? '');
        $this->assertStringContainsString('&--cream', $scssContent ?? '');
        $this->assertStringContainsString('&--default', $scssContent ?? '');
    }

    public function test_neumorphic_form_classes_present(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/app/eggs');

        $response->assertStatus(200);
        $response->assertSee('egg-counter__form', false);
        $response->assertSee('egg-counter__input', false);
        $response->assertSee('egg-counter__checkbox', false);
        $response->assertSee('egg-counter__advanced-section', false);
    }

    public function test_form_has_proper_aria_attributes(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/app/eggs');

        $response->assertStatus(200);
        $response->assertSee('aria-controls="advanced-fields"', false);
        $response->assertSee(':aria-expanded="detailed.toString()"', false);
        $response->assertSee('role="status"', false);
    }
}
