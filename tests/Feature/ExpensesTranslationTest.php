<?php

namespace Tests\Feature;

use App\Models\Expense;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExpensesTranslationTest extends TestCase
{
    use RefreshDatabase;

    public function test_expenses_page_renders_in_serbian_for_authenticated_premium_users(): void
    {
        $user = User::factory()->premium()->create(['locale' => 'sr']);
        Expense::factory()->create([
            'user_id' => $user->id,
            'category' => 'Feed',
            'description' => 'Kupovina hrane',
        ]);

        $response = $this->actingAs($user)->get(route('app.expenses.index'));

        $response->assertOk();
        $response->assertSee('Pracenje troskova');
        $response->assertSee('Dodaj novi trosak');
        $response->assertSee('Pregled kategorija');
        $response->assertSee('Evidencija troskova');
        $response->assertSee('Trend troskova za poslednjih 12 meseci');
        $response->assertSee('stubicasti grafikon troskova za poslednjih 12 meseci');
        $response->assertSee('Hrana');
        $response->assertDontSee('Expense Tracking');
        $response->assertDontSee('Add New Expense');
    }

    public function test_expenses_records_htmx_partial_renders_in_serbian(): void
    {
        $user = User::factory()->premium()->create(['locale' => 'sr']);
        $expense = Expense::factory()->create([
            'user_id' => $user->id,
            'category' => 'Feed',
            'date' => '2026-04-20',
        ]);

        $response = $this->actingAs($user)
            ->withHeaders(['HX-Request' => 'true'])
            ->get(route('app.expenses.index'));

        $response->assertOk();
        $response->assertViewIs('expenses.partials.records-table');
        $response->assertSee('Datum');
        $response->assertSee('Kategorija');
        $response->assertSee('Akcije');
        $response->assertSee('Hrana');
        $response->assertSee('Izmeni');
        $response->assertSee(__('expenses.actions.delete_aria_label', ['date' => $expense->date->translatedFormat('d. M Y.')]));
        $response->assertDontSee('Date');
        $response->assertDontSee('Category');
        $response->assertDontSee('Actions');
    }

    public function test_expenses_page_renders_english_copy_for_english_locale(): void
    {
        $user = User::factory()->premium()->create(['locale' => 'en']);
        Expense::factory()->create([
            'user_id' => $user->id,
            'category' => 'Feed',
        ]);

        $response = $this->actingAs($user)->get(route('app.expenses.index'));

        $response->assertOk();
        $response->assertSee('Add New Expense');
        $response->assertSee('Expense Records');
        $response->assertSee('12-Month Expense Trend');
        $response->assertSee('12-month expense bar chart');
        $response->assertSee('Feed');
    }
}
