<x-modals.modal title="Delete Feed Entry" size="sm" :id="'delete-confirm-' . $feed->id">
    <p class="confirm-delete__message">
        Are you sure you want to delete the feed entry for <strong>{{ $feed->brand }}</strong> — {{ $feed->feed_type->label() }}, {{ $feed->quantity }} {{ $feed->unit }} (@usd($feed->total_cost))? This action cannot be undone.
    </p>
    <div class="confirm-delete__actions">
        <button @click="close()" class="btn btn--secondary">Cancel</button>
        <button hx-delete="{{ route('app.feed.destroy', $feed) }}"
                hx-target="#feed-{{ $feed->id }}"
                hx-swap="outerHTML swap:500ms"
                class="btn btn--danger">
            Delete
        </button>
    </div>
</x-modals.modal>
