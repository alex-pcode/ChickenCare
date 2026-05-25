<x-modals.modal :title="__('feed.delete_modal.title')" size="sm" :id="'delete-confirm-' . $feed->id">
    <p class="confirm-delete__message">
        {{ __('feed.delete_modal.message', ['brand' => $feed->brand, 'type' => $feed->feed_type->label(), 'quantity' => $feed->quantity, 'unit' => $feed->unit, 'amount' => \App\Support\Money::usd($feed->total_cost)]) }}
    </p>
    <div class="confirm-delete__actions">
        <button @click="close()" class="btn btn--secondary">{{ __('ui.confirm_dialog.cancel') }}</button>
        <button hx-delete="{{ route('app.feed.destroy', $feed) }}"
                hx-target="#feed-{{ $feed->id }}"
                hx-swap="outerHTML swap:500ms"
                class="btn btn--danger">
            {{ __('feed.delete_modal.confirm') }}
        </button>
    </div>
</x-modals.modal>
