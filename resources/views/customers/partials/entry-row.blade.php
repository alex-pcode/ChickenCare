<tr id="customer-{{ $customer->id }}" class="{{ !$customer->is_active ? 'crm__row--inactive' : '' }}">
    <td class="data-table__cell">{{ $customer->name }}</td>
    <td class="data-table__cell">{{ $customer->phone ?? '—' }}</td>
    <td class="data-table__cell crm__notes">{{ $customer->notes ? Str::limit($customer->notes, 80) : '—' }}</td>
    <td class="data-table__cell">
        <span class="crm__status {{ $customer->is_active ? 'crm__status--active' : 'crm__status--inactive' }}">
            {{ $customer->is_active ? 'Active' : 'Inactive' }}
        </span>
    </td>
    <td class="data-table__cell crm__actions">
        <button type="button" class="btn btn--sm btn--secondary"
            hx-get="{{ route('app.customers.edit-form', $customer) }}"
            hx-target="#customer-{{ $customer->id }}"
            hx-swap="outerHTML"
            aria-label="Edit customer {{ $customer->name }}">
            Edit
        </button>
        @if($customer->is_active)
            <button type="button" class="btn btn--sm btn--danger"
                hx-delete="{{ route('app.customers.destroy', $customer) }}"
                hx-confirm="Deactivate this customer?"
                hx-target="closest tr"
                hx-swap="outerHTML swap:500ms"
                aria-label="Deactivate customer {{ $customer->name }}">
                Deactivate
            </button>
        @endif
    </td>
</tr>
