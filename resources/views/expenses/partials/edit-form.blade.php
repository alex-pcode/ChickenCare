<tr id="expense-{{ $expense->id }}" class="expenses__row--editing">
    <td class="data-table__cell">
        <input type="date" name="date" value="{{ $expense->date->format('Y-m-d') }}" max="{{ now()->format('Y-m-d') }}" class="form-input" required>
    </td>
    <td class="data-table__cell">
        <select name="category" class="form-select" required>
            @foreach(\App\Enums\ExpenseCategory::cases() as $category)
                <option value="{{ $category->value }}" {{ (string) $expense->category === $category->value ? 'selected' : '' }}>{{ $category->label() }}</option>
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
            aria-label="{{ __('expenses.actions.save_aria_label', ['date' => $expense->date->translatedFormat('d. M Y.')]) }}">
            {{ __('expenses.actions.save') }}
        </button>
        <button type="button" class="btn btn--sm btn--secondary"
            hx-get="{{ route('app.expenses.show-row', $expense) }}"
            hx-target="closest tr"
            hx-swap="outerHTML"
            aria-label="{{ __('expenses.actions.cancel_aria_label') }}">
            {{ __('expenses.actions.cancel') }}
        </button>
    </td>
</tr>
