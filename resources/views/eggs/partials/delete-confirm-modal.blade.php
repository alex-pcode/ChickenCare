<x-modals.modal title="Delete Egg Entry" size="sm" :id="'delete-confirm-' . $entry->id">
    <p class="confirm-delete__message">
        Are you sure you want to delete the egg entry for <strong>{{ $entry->date->format('M d, Y') }}</strong> ({{ $entry->count }} egg{{ $entry->count !== 1 ? 's' : '' }})? This action cannot be undone.
    </p>
    <div class="confirm-delete__actions">
        <button @click="close()" class="btn btn--secondary">Cancel</button>
        <button hx-delete="{{ route('app.eggs.destroy', $entry) }}"
                hx-target="closest tr"
                hx-swap="outerHTML swap:500ms"
                hx-on:after-request="close()"
                class="btn btn--danger">
            Delete
        </button>
    </div>
</x-modals.modal>
