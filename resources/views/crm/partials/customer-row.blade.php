<tr id="crm-customer-{{ $customer->id }}">
    <td class="data-table__cell">
        <span class="crm-customers__name">{{ $customer->name }}</span>
    </td>
    <td class="data-table__cell">
        @if($customer->phone)
            <span class="crm-customers__phone">📞 {{ $customer->phone }}</span>
        @else
            <span class="crm-customers__muted">-</span>
        @endif
    </td>
    <td class="data-table__cell">
        @if($customer->notes)
            <span class="crm-customers__notes-cell" title="{{ $customer->notes }}">📝 {{ Str::limit($customer->notes, 60) }}</span>
        @else
            <span class="crm-customers__muted">-</span>
        @endif
    </td>
    <td class="data-table__cell">
        <span class="crm-customers__date">{{ $customer->created_at->format('M d, Y') }}</span>
    </td>
    <td class="data-table__cell crm-customers__actions-cell">
        <button type="button"
                class="crm-customers__action-btn crm-customers__action-btn--edit"
                @click="openEditForm({{ json_encode(['id' => $customer->id, 'name' => $customer->name, 'phone' => $customer->phone, 'notes' => $customer->notes]) }})"
                aria-label="Edit {{ $customer->name }}"
                title="Edit">
            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 20 20" fill="currentColor">
                <path d="M13.586 3.586a2 2 0 112.828 2.828l-.793.793-2.828-2.828.793-.793zM11.379 5.793L3 14.172V17h2.828l8.38-8.379-2.83-2.828z" />
            </svg>
        </button>
        <button type="button"
                class="crm-customers__action-btn crm-customers__action-btn--delete"
                :class="{ 'crm-customers__action-btn--armed': deleteArmed === {{ $customer->id }} }"
                @click="armDelete({{ $customer->id }})"
                :aria-label="deleteArmed === {{ $customer->id }} ? 'Click again to confirm deletion' : 'Delete {{ $customer->name }}'"
                :title="deleteArmed === {{ $customer->id }} ? 'Click again to confirm deletion' : 'Delete'">
            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 20 20" fill="currentColor">
                <path fill-rule="evenodd" d="M13.477 14.89A6 6 0 015.11 6.524l8.367 8.368zm1.414-1.414L6.524 5.11a6 6 0 018.367 8.367zM18 10a8 8 0 11-16 0 8 8 0 0116 0z" clip-rule="evenodd" />
            </svg>
        </button>
    </td>
</tr>
