{{-- Daily Egg Collection Form --}}
<div class="showcase-section">
    <div class="showcase-section__header">
        <h2 class="showcase-section__title">Form Components</h2>
        <p class="showcase-section__subtitle">Input components for data collection and user interaction</p>
    </div>

    <x-forms.form-card title="Daily Egg Collection" subtitle="Record today's egg production data" action="#" method="POST">
        <x-forms.form-row>
            <x-forms.input name="collector" label="Collector Name" placeholder="Enter your name" required />
            <x-forms.date-input name="collection_date" label="Collection Date" required />
        </x-forms.form-row>

        <x-forms.form-row>
            <x-forms.select name="flock" label="Flock" :options="['1' => 'Flock A', '2' => 'Flock B', '3' => 'Flock C']" required />
            <x-forms.input name="egg_count" label="Eggs Collected" type="number" placeholder="0" required />
        </x-forms.form-row>

        <x-forms.textarea name="notes" label="Notes" placeholder="Any observations about the flock..." />

        <x-forms.submit-button label="Save Collection" />
    </x-forms.form-card>
</div>

{{-- Button Variations --}}
<div class="showcase-section">
    <div class="showcase-section__header">
        <h2 class="showcase-section__title">Button Variations</h2>
        <p class="showcase-section__subtitle">Different button styles and sizes</p>
    </div>

    <div style="display: flex; gap: 1rem; flex-wrap: wrap; align-items: center;">
        <button type="button" class="shiny-cta"><span>Primary</span></button>
        <button type="button" class="shiny-cta shiny-cta--sm"><span>Primary Small</span></button>
        <button class="btn btn--secondary">Secondary</button>
        <button class="btn btn--secondary btn--sm">Secondary Small</button>
        <button class="btn btn--danger">Danger</button>
        <button class="btn btn--danger btn--sm">Danger Small</button>
    </div>
</div>

{{-- Form Field Types --}}
<div class="showcase-section">
    <div class="showcase-section__header">
        <h2 class="showcase-section__title">Form Field Types</h2>
        <p class="showcase-section__subtitle">All available input types</p>
    </div>

    <x-forms.form-card title="Field Types Demo" subtitle="Showcasing all available form inputs" action="#" method="POST">
        <x-forms.input name="text_field" label="Text Input" placeholder="Standard text input" />
        <x-forms.input name="email_field" label="Email Input" type="email" placeholder="you@example.com" />
        <x-forms.input name="number_field" label="Number Input" type="number" placeholder="0" />
        <x-forms.select name="select_field" label="Select Input" :options="['opt1' => 'Option 1', 'opt2' => 'Option 2', 'opt3' => 'Option 3']" />
        <x-forms.textarea name="textarea_field" label="Textarea" placeholder="Multi-line text input..." />
        <x-forms.date-input name="date_field" label="Date Input" />
    </x-forms.form-card>
</div>

{{-- Premium Gate --}}
<div class="showcase-section">
    <div class="showcase-section__header">
        <h2 class="showcase-section__title">Premium Gate</h2>
        <p class="showcase-section__subtitle">Feature restriction component</p>
    </div>

    <x-premium-gate feature="Advanced Analytics" />
</div>
