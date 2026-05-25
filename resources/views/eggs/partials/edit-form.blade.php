<tr id="egg-entry-{{ $entry->id }}" class="egg-counter__row--editing">
    <td class="data-table__cell" data-label="{{ __('eggs.table.columns.date') }}">
        <input type="date" name="date" value="{{ $entry->date->format('Y-m-d') }}" max="{{ now()->format('Y-m-d') }}" class="form-input" required>
    </td>
    <td class="data-table__cell" data-label="{{ __('eggs.table.columns.eggs') }}">
        <input type="number" name="count" value="{{ $entry->count }}" min="0" class="form-input" required>
    </td>
    <td class="data-table__cell" data-label="{{ __('eggs.table.columns.size') }}">
        <select name="size" class="form-select">
            <option value="">—</option>
            @foreach(__('eggs.form.sizes') as $val => $label)
                <option value="{{ $val }}" {{ $entry->size === $val ? 'selected' : '' }}>{{ $label }}</option>
            @endforeach
        </select>
    </td>
    <td class="data-table__cell" data-label="{{ __('eggs.table.columns.color') }}">
        <select name="color" class="form-select">
            <option value="">—</option>
            @foreach(__('eggs.form.colors') as $val => $label)
                <option value="{{ $val }}" {{ $entry->color === $val ? 'selected' : '' }}>{{ $label }}</option>
            @endforeach
        </select>
    </td>
    <td class="data-table__cell" data-label="{{ __('eggs.table.columns.notes') }}">
        <input type="text" name="notes" value="{{ $entry->notes }}" class="form-input" maxlength="1000" placeholder="{{ __('eggs.form.notes_placeholder') }}">
    </td>
    <td class="data-table__cell egg-counter__actions" data-label="{{ __('eggs.table.columns.actions') }}">
        <button type="button" class="btn btn--sm btn--primary"
            hx-put="{{ route('app.eggs.update', $entry) }}"
            hx-include="closest tr"
            hx-target="closest tr"
            hx-swap="outerHTML"
            aria-label="{{ __('eggs.actions.save_aria_label', ['date' => $entry->date->format('M d, Y')]) }}">
            {{ __('eggs.actions.save') }}
        </button>
        <button type="button" class="btn btn--sm btn--secondary"
            hx-get="{{ route('app.eggs.show-row', $entry) }}"
            hx-target="closest tr"
            hx-swap="outerHTML"
            aria-label="{{ __('eggs.actions.cancel_aria_label') }}">
            {{ __('eggs.actions.cancel') }}
        </button>
    </td>
</tr>
