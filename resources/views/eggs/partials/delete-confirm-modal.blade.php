<x-modals.modal :title="__('eggs.delete_modal.title')" size="sm" :id="'delete-confirm-' . $entry->id">
    <p class="confirm-delete__message">
        {{ __('eggs.delete_modal.message', [
            'date' => $entry->date->format('M d, Y'),
            'count' => $entry->count,
            'eggs' => $entry->count !== 1 ? __('eggs.status.eggs') : __('eggs.status.egg'),
        ]) }}
    </p>
    <div class="confirm-delete__actions">
        <button @click="close()" class="btn btn--secondary">{{ __('eggs.delete_modal.cancel') }}</button>
        <button hx-delete="{{ route('app.eggs.destroy', $entry) }}"
                hx-target="#egg-entry-{{ $entry->id }}"
                hx-swap="outerHTML swap:500ms"
                class="btn btn--danger">
            {{ __('eggs.delete_modal.confirm') }}
        </button>
    </div>
</x-modals.modal>
