<x-modals.modal :title="__('expenses.delete_modal.title')" size="sm" :id="'delete-confirm-' . $expense->id">
    <p class="confirm-delete__message">
        {{ __('expenses.delete_modal.message', ['date' => $expense->date->translatedFormat('d. M Y.'), 'description' => $expense->description, 'amount' => \App\Support\Money::usd($expense->amount)]) }}
    </p>
    <div class="confirm-delete__actions">
        <button @click="close()" class="btn btn--secondary">{{ __('ui.confirm_dialog.cancel') }}</button>
        <button hx-delete="{{ route('app.expenses.destroy', $expense) }}"
                hx-target="#expense-{{ $expense->id }}"
                hx-swap="outerHTML swap:500ms"
                class="btn btn--danger">
            {{ __('expenses.delete_modal.confirm') }}
        </button>
    </div>
</x-modals.modal>
