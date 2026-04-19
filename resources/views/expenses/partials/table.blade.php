@if($expenses->isEmpty())
    <x-ui.empty-state
        title="No expenses found"
        description="No expenses match the selected filter."
        icon="💰"
    />
@else
    <div class="data-table-wrapper">
        <table class="data-table data-table--striped">
            <thead class="data-table__head">
                <tr>
                    <th scope="col" class="data-table__header">Date</th>
                    <th scope="col" class="data-table__header">Category</th>
                    <th scope="col" class="data-table__header">Description</th>
                    <th scope="col" class="data-table__header">Amount</th>
                    <th scope="col" class="data-table__header">Actions</th>
                </tr>
            </thead>
            <tbody id="expense-entries-body" class="data-table__body">
                @foreach($expenses as $expense)
                    @include('expenses.partials.entry-row', ['expense' => $expense])
                @endforeach
            </tbody>
        </table>
    </div>

    <x-tables.pagination :paginator="$expenses" />
@endif
