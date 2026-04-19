@props([
    'action',
    'target',
    'message' => 'Are you sure you want to delete this? This action cannot be undone.',
])

<x-modals.modal title="Confirm Delete" size="sm">
    <p class="confirm-delete__message">{{ $message }}</p>
    <div class="confirm-delete__actions">
        <button @click="close()" class="btn btn--secondary">Cancel</button>
        <button hx-delete="{{ $action }}"
                hx-target="{{ $target }}"
                hx-swap="outerHTML swap:500ms"
                class="btn btn--danger">
            Delete
        </button>
    </div>
</x-modals.modal>
