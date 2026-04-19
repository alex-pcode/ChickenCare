{{-- Modal Examples --}}
<div class="showcase-section">
    <div class="showcase-section__header">
        <h2 class="showcase-section__title">Modal Examples</h2>
        <p class="showcase-section__subtitle">Dialog components for user interactions and confirmations</p>
    </div>

    <div style="display: flex; gap: 1rem; flex-wrap: wrap;">
        {{-- Basic Modal --}}
        <button class="btn btn--primary"
                onclick="document.getElementById('modal-container').innerHTML = document.getElementById('basic-modal-tmpl').innerHTML; return false;">
            Basic Modal
        </button>
        <template id="basic-modal-tmpl">
            <x-modals.modal title="Basic Modal" size="md">
                <p>This is a basic modal dialog with standard content.</p>
                <p>Press <kbd>Escape</kbd> to close, or click the overlay.</p>
            </x-modals.modal>
        </template>

        {{-- Small Modal --}}
        <button class="btn btn--secondary"
                onclick="document.getElementById('modal-container').innerHTML = document.getElementById('small-modal-tmpl').innerHTML; return false;">
            Small Modal
        </button>
        <template id="small-modal-tmpl">
            <x-modals.modal title="Small Modal" size="sm">
                <p>A compact modal for simple confirmations or messages.</p>
            </x-modals.modal>
        </template>

        {{-- Large Modal --}}
        <button class="btn btn--secondary"
                onclick="document.getElementById('modal-container').innerHTML = document.getElementById('large-modal-tmpl').innerHTML; return false;">
            Large Modal
        </button>
        <template id="large-modal-tmpl">
            <x-modals.modal title="Large Modal" size="lg">
                <p>A large modal suitable for forms, detailed content, or complex interactions.</p>
                <div style="margin-top: 1rem;">
                    <x-ui.summary-card
                        title="Example Content"
                        :items="[
                            ['label' => 'Item 1', 'value' => 'Value 1'],
                            ['label' => 'Item 2', 'value' => 'Value 2'],
                            ['label' => 'Item 3', 'value' => 'Value 3'],
                        ]"
                    />
                </div>
            </x-modals.modal>
        </template>

        {{-- Confirm Delete --}}
        <button class="btn btn--danger"
                onclick="document.getElementById('modal-container').innerHTML = document.getElementById('delete-modal-tmpl').innerHTML; return false;">
            Confirm Delete
        </button>
        <template id="delete-modal-tmpl">
            <x-modals.modal title="Confirm Deletion" size="sm">
                <p style="color: #525252; margin-bottom: 1.5rem;">Are you sure you want to delete this item? This action cannot be undone.</p>
                <div style="display: flex; justify-content: flex-end; gap: 0.75rem;">
                    <button class="btn btn--secondary btn--sm" onclick="document.getElementById('modal-container').innerHTML = '';">Cancel</button>
                    <button class="btn btn--danger btn--sm" onclick="document.getElementById('modal-container').innerHTML = '';">Delete</button>
                </div>
            </x-modals.modal>
        </template>
    </div>
</div>
