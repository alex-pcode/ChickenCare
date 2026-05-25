<tr id="feed-{{ $feed->id }}" class="feed__row--editing">
    <td class="data-table__cell">
        <input type="text" name="brand" value="{{ $feed->brand }}" class="form-input" maxlength="255" required>
    </td>
    <td class="data-table__cell">
        <select name="feed_type" class="form-select" required>
            @foreach(\App\Enums\FeedType::cases() as $type)
                <option value="{{ $type->value }}" {{ $feed->feed_type === $type ? 'selected' : '' }}>{{ $type->label() }}</option>
            @endforeach
        </select>
    </td>
    <td class="data-table__cell">
        <div class="flex gap-1">
            <input type="number" name="quantity" value="{{ $feed->quantity }}" class="form-input" step="0.01" min="0.01" required style="flex:2">
            <select name="unit" class="form-select" required style="flex:1">
                <option value="kg" {{ $feed->unit === 'kg' ? 'selected' : '' }}>kg</option>
                <option value="lbs" {{ $feed->unit === 'lbs' ? 'selected' : '' }}>lbs</option>
            </select>
        </div>
    </td>
    <td class="data-table__cell">
        <input type="number" name="total_cost" value="{{ $feed->total_cost }}" class="form-input" step="0.01" min="0.01" required>
    </td>
    <td class="data-table__cell">
        @if($feed->isActive())
            <span class="feed__duration-active">{{ __('feed.status.active') }}</span>
        @else
            {{ trans_choice('feed.status.days', $feed->durationInDays(), ['count' => $feed->durationInDays()]) }}
        @endif
    </td>
    <td class="data-table__cell feed__actions">
        <button type="button" class="btn btn--sm btn--primary"
            hx-put="{{ route('app.feed.update', $feed) }}"
            hx-include="closest tr"
            hx-target="closest tr"
            hx-swap="outerHTML"
            aria-label="{{ __('feed.actions.save_aria') }}">
            {{ __('feed.actions.save') }}
        </button>
        <button type="button" class="btn btn--sm btn--secondary"
            hx-get="{{ route('app.feed.show-row', $feed) }}"
            hx-target="closest tr"
            hx-swap="outerHTML"
            aria-label="{{ __('feed.actions.cancel_aria') }}">
            {{ __('feed.actions.cancel') }}
        </button>
    </td>
</tr>
