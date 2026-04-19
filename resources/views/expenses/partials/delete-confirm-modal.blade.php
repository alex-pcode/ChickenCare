<x-modals.modal title="Delete Expense" size="sm" :id="'delete-confirm-' . $expense->id">
    <p class="confirm-delete__message">
        Are you sure you want to delete the expense for <strong>{{ $expense->date->format('M d, Y') }}</strong> — {{ $expense->description }} (@usd($expense->amount))? This action cannot be undone.
    </p>
    <div class="confirm-delete__actions">
        <button @click="close()" class="btn btn--secondary">Cancel</button>
        <button hx-delete="{{ route('app.expenses.destroy', $expense) }}"
                hx-target="#expense-{{ $expense->id }}"
                hx-swap="outerHTML swap:500ms"
                class="btn btn--danger">
            Delete
        </button>
    </div>
</x-modals.modal>
