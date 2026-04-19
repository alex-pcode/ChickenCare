<tr id="sale-{{ $sale->id }}" class="sales__row--editing">
    <td class="data-table__cell">
        <input type="date" name="sale_date" value="{{ $sale->sale_date->format('Y-m-d') }}" class="form-input" required aria-label="Sale date">
    </td>
    <td class="data-table__cell">
        <input type="number" name="dozen_count" value="{{ $sale->dozen_count }}" class="form-input" min="0" aria-label="Dozen count">
    </td>
    <td class="data-table__cell">
        <input type="number" name="individual_count" value="{{ $sale->individual_count }}" class="form-input" min="0" aria-label="Individual count">
    </td>
    <td class="data-table__cell">
        <input type="number" name="total_amount" value="{{ $sale->total_amount }}" class="form-input" min="0" step="0.01" required aria-label="Total amount">
    </td>
    <td class="data-table__cell">
        <select name="customer_id" class="form-input" aria-label="Customer">
            <option value="">Walk-in / No Customer</option>
            @foreach($customers as $customer)
                <option value="{{ $customer->id }}" {{ $sale->customer_id == $customer->id ? 'selected' : '' }}>
                    {{ $customer->name }}
                </option>
            @endforeach
        </select>
    </td>
    <td class="data-table__cell">
        <label class="sales__paid-label">
            <input type="checkbox" name="paid" value="1" {{ $sale->paid ? 'checked' : '' }} aria-label="Mark as paid">
            Paid
        </label>
    </td>
    <td class="data-table__cell sales__actions">
        <button type="button" class="btn btn--sm btn--primary"
            hx-put="{{ route('app.sales.update', $sale) }}"
            hx-include="closest tr"
            hx-target="closest tr"
            hx-swap="outerHTML"
            aria-label="Save sale">
            Save
        </button>
        <button type="button" class="btn btn--sm btn--secondary"
            hx-get="{{ route('app.sales.show-row', $sale) }}"
            hx-target="closest tr"
            hx-swap="outerHTML"
            aria-label="Cancel editing">
            Cancel
        </button>
    </td>
</tr>
