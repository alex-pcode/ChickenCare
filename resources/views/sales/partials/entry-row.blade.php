<tr id="sale-{{ $sale->id }}">
    <td class="data-table__cell">{{ $sale->sale_date->format('M d, Y') }}</td>
    <td class="data-table__cell">{{ $sale->dozen_count }}</td>
    <td class="data-table__cell">{{ $sale->individual_count }}</td>
    <td class="data-table__cell sales__amount">${{ number_format($sale->total_amount, 2) }}</td>
    <td class="data-table__cell sales__customer">{{ $sale->customer->name }}</td>
    <td class="data-table__cell">
        <button type="button"
            class="sales__badge {{ $sale->paid ? 'sales__badge--paid' : 'sales__badge--unpaid' }}"
            hx-patch="{{ route('app.sales.toggle-payment', $sale) }}"
            hx-target="#sale-{{ $sale->id }}"
            hx-swap="outerHTML"
            aria-label="{{ $sale->paid ? 'Mark as unpaid' : 'Mark as paid' }}">
            {{ $sale->paid ? 'Paid' : 'Unpaid' }}
        </button>
    </td>
    <td class="data-table__cell sales__actions">
        <button type="button" class="btn btn--sm btn--secondary"
            hx-get="{{ route('app.sales.edit-form', $sale) }}"
            hx-target="#sale-{{ $sale->id }}"
            hx-swap="outerHTML"
            aria-label="Edit sale from {{ $sale->sale_date->format('M d, Y') }}">
            Edit
        </button>
        <button type="button" class="btn btn--sm btn--danger"
            hx-delete="{{ route('app.sales.destroy', $sale) }}"
            hx-confirm="Delete this sale?"
            hx-target="#sale-{{ $sale->id }}"
            hx-swap="outerHTML swap:500ms"
            aria-label="Delete sale from {{ $sale->sale_date->format('M d, Y') }}">
            Delete
        </button>
    </td>
</tr>
