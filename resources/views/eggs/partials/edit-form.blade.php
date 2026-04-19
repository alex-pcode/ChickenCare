<tr id="egg-entry-{{ $entry->id }}" class="egg-counter__row--editing">
    <td class="data-table__cell">
        <input type="date" name="date" value="{{ $entry->date->format('Y-m-d') }}" max="{{ now()->format('Y-m-d') }}" class="form-input" required>
    </td>
    <td class="data-table__cell">
        <input type="number" name="count" value="{{ $entry->count }}" min="0" class="form-input" required>
    </td>
    <td class="data-table__cell">
        <select name="size" class="form-select">
            <option value="">—</option>
            @foreach(['small' => 'Small', 'medium' => 'Medium', 'large' => 'Large', 'extra-large' => 'Extra Large', 'jumbo' => 'Jumbo'] as $val => $label)
                <option value="{{ $val }}" {{ $entry->size === $val ? 'selected' : '' }}>{{ $label }}</option>
            @endforeach
        </select>
    </td>
    <td class="data-table__cell">
        <select name="color" class="form-select">
            <option value="">—</option>
            @foreach(['white' => 'White', 'brown' => 'Brown', 'blue' => 'Blue', 'green' => 'Green', 'speckled' => 'Speckled', 'cream' => 'Cream'] as $val => $label)
                <option value="{{ $val }}" {{ $entry->color === $val ? 'selected' : '' }}>{{ $label }}</option>
            @endforeach
        </select>
    </td>
    <td class="data-table__cell">
        <input type="text" name="notes" value="{{ $entry->notes }}" class="form-input" maxlength="1000" placeholder="Notes...">
    </td>
    <td class="data-table__cell egg-counter__actions">
        <button type="button" class="btn btn--sm btn--primary"
            hx-put="{{ route('app.eggs.update', $entry) }}"
            hx-include="closest tr"
            hx-target="closest tr"
            hx-swap="outerHTML"
            aria-label="Save entry for {{ $entry->date->format('M d, Y') }}">
            Save
        </button>
        <button type="button" class="btn btn--sm btn--secondary"
            hx-get="{{ route('app.eggs.show-row', $entry) }}"
            hx-target="closest tr"
            hx-swap="outerHTML"
            aria-label="Cancel editing">
            Cancel
        </button>
    </td>
</tr>
