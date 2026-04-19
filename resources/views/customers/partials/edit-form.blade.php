<tr id="customer-{{ $customer->id }}" class="crm__row--editing">
    <td class="data-table__cell">
        <input type="text" name="name" value="{{ $customer->name }}" class="form-input" maxlength="255" required>
    </td>
    <td class="data-table__cell">
        <input type="text" name="phone" value="{{ $customer->phone }}" class="form-input" maxlength="50" placeholder="Phone">
    </td>
    <td class="data-table__cell">
        <textarea name="notes" class="form-input" rows="2" maxlength="5000" placeholder="Notes">{{ $customer->notes }}</textarea>
    </td>
    <td class="data-table__cell">
        <span class="crm__status {{ $customer->is_active ? 'crm__status--active' : 'crm__status--inactive' }}">
            {{ $customer->is_active ? 'Active' : 'Inactive' }}
        </span>
    </td>
    <td class="data-table__cell crm__actions">
        <button type="button" class="btn btn--sm btn--primary"
            hx-put="{{ route('app.customers.update', $customer) }}"
            hx-include="closest tr"
            hx-target="closest tr"
            hx-swap="outerHTML"
            aria-label="Save customer {{ $customer->name }}">
            Save
        </button>
        <button type="button" class="btn btn--sm btn--secondary"
            hx-get="{{ route('app.customers.show-row', $customer) }}"
            hx-target="closest tr"
            hx-swap="outerHTML"
            aria-label="Cancel editing">
            Cancel
        </button>
    </td>
</tr>
