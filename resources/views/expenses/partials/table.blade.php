@if($expenses->isEmpty())
    <x-ui.empty-state
        :title="__('expenses.records.filtered_empty_title')"
        :description="__('expenses.records.filtered_empty_description')"
        icon="💰"
    />
@else
    <div class="data-table-wrapper">
        <table class="data-table data-table--striped">
            <thead class="data-table__head">
                <tr>
                    <th scope="col" class="data-table__header">{{ __('expenses.records.columns.date') }}</th>
                    <th scope="col" class="data-table__header">{{ __('expenses.records.columns.category') }}</th>
                    <th scope="col" class="data-table__header">{{ __('expenses.records.columns.description') }}</th>
                    <th scope="col" class="data-table__header">{{ __('expenses.records.columns.amount') }}</th>
                    <th scope="col" class="data-table__header">{{ __('expenses.records.columns.actions') }}</th>
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
