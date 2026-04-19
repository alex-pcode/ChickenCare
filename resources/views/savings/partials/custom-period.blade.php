<div class="glass-card savings__custom-period">
    <x-forms.date-input
        name="from"
        label="Start Date"
        :value="$period->from?->format('Y-m-d')"
        :max="$period->to?->format('Y-m-d') ?? now()->format('Y-m-d')"
        hx-get="{{ route('app.savings.index') }}"
        hx-target="#savings-financial-summary"
        hx-swap="innerHTML"
        hx-trigger="change"
        hx-push-url="true"
        hx-include="[name='period'],[name='to']"
    />
    <x-forms.date-input
        name="to"
        label="End Date"
        :value="$period->to?->format('Y-m-d')"
        :min="$period->from?->format('Y-m-d')"
        :max="now()->format('Y-m-d')"
        hx-get="{{ route('app.savings.index') }}"
        hx-target="#savings-financial-summary"
        hx-swap="innerHTML"
        hx-trigger="change"
        hx-push-url="true"
        hx-include="[name='period'],[name='from']"
    />
</div>
