<tr id="expense-{{ $expense->id }}" class="expenses__row--editing">
    <td class="data-table__cell">
        <input type="date" name="date" value="{{ $expense->date->format('Y-m-d') }}" max="{{ now()->format('Y-m-d') }}" class="form-input" required>
    </td>
    <td class="data-table__cell">
        <select name="category" class="form-select" required>
            @foreach(['feed' => 'Feed', 'medical' => 'Medical', 'equipment' => 'Equipment', 'housing' => 'Housing', 'utilities' => 'Utilities', 'other' => 'Other'] as $val => $label)
                <option value="{{ $val }}" {{ $expense->category === $val ? 'selected' : '' }}>{{ $label }}</option>
            @endforeach
        </select>
    </td>
    <td class="data-table__cell">
        <input type="text" name="description" value="{{ $expense->description }}" class="form-input" maxlength="500" required>
    </td>
    <td class="data-table__cell">
        <input type="number" name="amount" value="{{ $expense->amount }}" class="form-input" step="0.01" min="0" required>
    </td>
    <td class="data-table__cell expenses__actions">
        <button type="button" class="btn btn--sm btn--primary"
            hx-put="{{ route('app.expenses.update', $expense) }}"
            hx-include="closest tr"
            hx-target="closest tr"
            hx-swap="outerHTML"
            aria-label="Save expense for {{ $expense->date->format('M d, Y') }}">
            Save
        </button>
        <button type="button" class="btn btn--sm btn--secondary"
            hx-get="{{ route('app.expenses.show-row', $expense) }}"
            hx-target="closest tr"
            hx-swap="outerHTML"
            aria-label="Cancel editing">
            Cancel
        </button>
    </td>
</tr>
