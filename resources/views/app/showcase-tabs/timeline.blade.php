{{-- Timeline Examples --}}
<div class="showcase-section">
    <div class="showcase-section__header">
        <h2 class="showcase-section__title">Timeline Examples</h2>
        <p class="showcase-section__subtitle">Chronological event displays with status indicators</p>
    </div>

    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem;">
        {{-- Default Timeline --}}
        <x-layout.section title="Farm Activity Log">
            <x-ui.timeline :events="[
                ['date' => '2026-04-09', 'type' => 'success', 'description' => 'Flock A reached 90% production rate'],
                ['date' => '2026-04-07', 'type' => 'warning', 'description' => 'Feed supply running low — reorder placed'],
                ['date' => '2026-04-05', 'type' => 'info', 'description' => 'New batch of 25 chicks added'],
                ['date' => '2026-04-03', 'type' => 'success', 'description' => 'Veterinary checkup completed — all clear'],
                ['date' => '2026-04-01', 'type' => 'error', 'description' => '2 birds lost to predator'],
            ]" />
        </x-layout.section>

        {{-- Compact Timeline --}}
        <x-layout.section title="Compact Timeline">
            <x-ui.timeline :events="[
                ['date' => '2026-04-10', 'type' => 'info', 'description' => 'Morning inspection complete'],
                ['date' => '2026-04-10', 'type' => 'success', 'description' => '47 eggs collected'],
                ['date' => '2026-04-10', 'type' => 'warning', 'description' => 'Water system pressure low'],
                ['date' => '2026-04-09', 'type' => 'success', 'description' => 'Feed delivery received'],
                ['date' => '2026-04-09', 'type' => 'info', 'description' => 'Coop temperature adjusted'],
                ['date' => '2026-04-08', 'type' => 'error', 'description' => 'Heating system malfunction reported'],
            ]" :compact="true" />
        </x-layout.section>
    </div>
</div>

{{-- Timeline Event Types --}}
<div class="showcase-section">
    <div class="showcase-section__header">
        <h2 class="showcase-section__title">Event Type Reference</h2>
        <p class="showcase-section__subtitle">Available status types for timeline markers</p>
    </div>

    <x-ui.timeline :events="[
        ['date' => '2026-04-09', 'type' => 'success', 'description' => 'Success event — positive outcomes, completions, achievements'],
        ['date' => '2026-04-08', 'type' => 'warning', 'description' => 'Warning event — cautions, low stock alerts, pending actions'],
        ['date' => '2026-04-07', 'type' => 'info', 'description' => 'Info event — neutral updates, additions, informational notes'],
        ['date' => '2026-04-06', 'type' => 'error', 'description' => 'Error event — losses, failures, critical issues'],
    ]" />
</div>
