<tr id="feed-{{ $feed->id }}" class="{{ $feed->isActive() ? '' : 'feed__row--depleted' }}"
    x-data="{ armed: false, timer: null }"
>
    <td class="data-table__cell">{{ $feed->brand }}</td>
    <td class="data-table__cell">{{ $feed->feed_type->label() }}</td>
    <td class="data-table__cell">{{ $feed->quantity }} {{ $feed->unit }}</td>
    <td class="data-table__cell feed__cost">@usd($feed->total_cost)</td>
    <td class="data-table__cell">
        @if($feed->isActive())
            <span class="feed__duration-active">Active</span>
        @else
            {{ $feed->durationInDays() }} days
        @endif
    </td>
    <td class="data-table__cell feed__actions">
        @if($feed->isActive())
            <button type="button" class="btn btn--sm btn--secondary"
                hx-patch="{{ route('app.feed.deplete', $feed) }}"
                hx-target="#feed-{{ $feed->id }}"
                hx-swap="outerHTML"
                aria-label="Mark feed as depleted">
                Mark depleted
            </button>
        @endif
        <button type="button" class="btn btn--sm btn--secondary"
            hx-get="{{ route('app.feed.edit-form', $feed) }}"
            hx-target="#feed-{{ $feed->id }}"
            hx-swap="outerHTML"
            aria-label="Edit feed entry">
            Edit
        </button>
        <button type="button"
            class="feed__delete-btn transition-colors"
            :class="armed
                ? 'feed__delete-btn--armed'
                : 'feed__delete-btn--default'"
            :title="armed ? 'Click again to confirm deletion' : 'Delete feed entry'"
            :aria-label="armed ? 'Confirm deletion of feed entry' : 'Delete feed entry'"
            x-on:click.prevent="
                if (armed) {
                    clearTimeout(timer);
                    armed = false;
                    $el.closest('button').setAttribute('hx-delete', '{{ route('app.feed.destroy', $feed) }}');
                    htmx.trigger($el.closest('button'), 'confirmed-delete');
                } else {
                    armed = true;
                    timer = setTimeout(() => { armed = false; }, 3000);
                }
            "
            hx-delete="{{ route('app.feed.destroy', $feed) }}"
            hx-trigger="confirmed-delete"
            hx-target="closest tr"
            hx-swap="outerHTML swap:300ms"
        >
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5">
                <path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0" />
            </svg>
        </button>
    </td>
</tr>
