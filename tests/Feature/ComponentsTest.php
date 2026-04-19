<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\MessageBag;
use Illuminate\Support\ViewErrorBag;
use Tests\TestCase;

class ComponentsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Share an empty error bag so @error directive works in Blade::render()
        $this->app['view']->share('errors', new ViewErrorBag());
    }

    private function shareErrors(array $errors): void
    {
        $bag = new ViewErrorBag();
        $bag->put('default', new MessageBag($errors));
        $this->app['view']->share('errors', $bag);
    }

    public function test_input_component_renders_with_error_state(): void
    {
        $this->shareErrors(['username' => 'The username field is required.']);

        $html = Blade::render('<x-forms.input name="username" label="Username" />');

        $this->assertStringContainsString('aria-invalid="true"', $html);
        $this->assertStringContainsString('aria-describedby="username-error"', $html);
        $this->assertStringContainsString('role="alert"', $html);
        $this->assertStringContainsString('The username field is required.', $html);
        $this->assertStringContainsString('form-group--error', $html);
    }

    public function test_input_component_renders_label_and_placeholder(): void
    {
        $html = Blade::render('<x-forms.input name="email" label="Email Address" placeholder="you@example.com" />');

        $this->assertStringContainsString('Email Address', $html);
        $this->assertStringContainsString('placeholder="you@example.com"', $html);
        $this->assertStringContainsString('id="email"', $html);
        $this->assertStringContainsString('name="email"', $html);
    }

    public function test_input_component_shows_required_indicator(): void
    {
        $html = Blade::render('<x-forms.input name="name" label="Name" :required="true" />');

        $this->assertStringContainsString('aria-hidden="true"', $html);
        $this->assertStringContainsString('*', $html);
        $this->assertStringContainsString('required', $html);
    }

    public function test_input_component_restores_old_value(): void
    {
        $this->app['request']->setLaravelSession(
            tap($this->app['session.store'], function ($session) {
                $session->put('_old_input', ['username' => 'old_username_value']);
            })
        );

        $html = Blade::render('<x-forms.input name="username" label="Username" />');

        $this->assertStringContainsString('old_username_value', $html);
    }

    public function test_select_component_renders_options(): void
    {
        $html = Blade::render(
            '<x-forms.select name="flock" label="Flock" :options="$options" />',
            ['options' => ['1' => 'Flock A', '2' => 'Flock B']]
        );

        $this->assertStringContainsString('<option value="1"', $html);
        $this->assertStringContainsString('Flock A', $html);
        $this->assertStringContainsString('<option value="2"', $html);
        $this->assertStringContainsString('Flock B', $html);
        $this->assertStringContainsString('-- Select --', $html);
    }

    public function test_modal_component_has_dialog_role(): void
    {
        $html = Blade::render('<x-modals.modal id="test-modal" title="Test Modal">Modal content</x-modals.modal>');

        $this->assertStringContainsString('role="dialog"', $html);
        $this->assertStringContainsString('aria-modal="true"', $html);
        $this->assertStringContainsString('aria-labelledby="test-modal-title"', $html);
        $this->assertStringContainsString('Test Modal', $html);
    }

    public function test_empty_state_has_status_role(): void
    {
        $html = Blade::render('<x-ui.empty-state title="No Data" description="Nothing here yet" />');

        $this->assertStringContainsString('role="status"', $html);
        $this->assertStringContainsString('No Data', $html);
        $this->assertStringContainsString('Nothing here yet', $html);
    }

    public function test_flash_component_shows_session_messages(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->withSession(['success' => 'Operation successful'])
            ->get(route('app.dashboard'));

        $response->assertSee('role="alert"', false);
        $response->assertSee('Operation successful');
    }

    public function test_page_header_renders_title_and_actions(): void
    {
        $html = Blade::render('
            <x-layout.page-header title="Egg Tracking">
                <x-slot:actions><button>Add Entry</button></x-slot:actions>
            </x-layout.page-header>
        ');

        $this->assertStringContainsString('Egg Tracking', $html);
        $this->assertStringContainsString('Add Entry', $html);
        $this->assertStringContainsString('page-header__actions', $html);
    }

    public function test_premium_gate_renders_upgrade_prompt(): void
    {
        $html = Blade::render('<x-premium-gate feature="Advanced Analytics" />');

        $this->assertStringContainsString('Premium Feature', $html);
        $this->assertStringContainsString('Advanced Analytics', $html);
        $this->assertStringContainsString('Premium subscription', $html);
    }

    public function test_stat_card_renders_with_trend(): void
    {
        $html = Blade::render('<x-ui.stat-card title="Total Eggs" total="47" change="12" changeType="increase" />');

        $this->assertStringContainsString('Total Eggs', $html);
        $this->assertStringContainsString('47', $html);
        $this->assertStringContainsString('stat-card__change--increase', $html);
        $this->assertStringContainsString('12%', $html);
    }

    public function test_progress_card_has_progressbar_role(): void
    {
        $html = Blade::render('<x-ui.progress-card title="Target" :value="30" :max="100" label="eggs" />');

        $this->assertStringContainsString('role="progressbar"', $html);
        $this->assertStringContainsString('aria-valuenow="30"', $html);
        $this->assertStringContainsString('aria-valuemax="100"', $html);
        $this->assertStringContainsString('30%', $html);
    }

    public function test_data_table_renders_headers_with_scope(): void
    {
        $html = Blade::render(
            '<x-tables.data-table :headers="$headers"><tr><td>Row</td></tr></x-tables.data-table>',
            ['headers' => ['Date', 'Count']]
        );

        $this->assertStringContainsString('<th scope="col"', $html);
        $this->assertStringContainsString('Date', $html);
        $this->assertStringContainsString('Count', $html);
    }

    public function test_textarea_component_renders(): void
    {
        $html = Blade::render('<x-forms.textarea name="notes" label="Notes" placeholder="Enter notes" :rows="6" />');

        $this->assertStringContainsString('rows="6"', $html);
        $this->assertStringContainsString('placeholder="Enter notes"', $html);
        $this->assertStringContainsString('form-textarea', $html);
    }

    public function test_date_input_component_renders(): void
    {
        $html = Blade::render('<x-forms.date-input name="hatch_date" label="Hatch Date" min="2026-01-01" max="2026-12-31" />');

        $this->assertStringContainsString('type="date"', $html);
        $this->assertStringContainsString('min="2026-01-01"', $html);
        $this->assertStringContainsString('max="2026-12-31"', $html);
    }

    public function test_form_card_renders_with_csrf(): void
    {
        $html = Blade::render('<x-forms.form-card title="Test Form" action="/test"><p>Fields</p></x-forms.form-card>');

        $this->assertStringContainsString('form-card__title', $html);
        $this->assertStringContainsString('Test Form', $html);
        $this->assertStringContainsString('action="/test"', $html);
        $this->assertStringContainsString('_token', $html);
    }

    public function test_form_card_renders_method_spoofing_for_put(): void
    {
        $html = Blade::render('<x-forms.form-card title="Edit" action="/test" method="PUT"><p>Fields</p></x-forms.form-card>');

        $this->assertStringContainsString('method="POST"', $html);
        $this->assertStringContainsString('_method', $html);
    }

    public function test_section_component_renders(): void
    {
        $html = Blade::render('<x-layout.section title="My Section" subtitle="Details"><p>Content</p></x-layout.section>');

        $this->assertStringContainsString('My Section', $html);
        $this->assertStringContainsString('Details', $html);
        $this->assertStringContainsString('Content', $html);
    }
}
