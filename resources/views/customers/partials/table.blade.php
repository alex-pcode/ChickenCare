@if($customers->isEmpty())
    <x-ui.empty-state
        title="No customers found"
        description="No customers match your current filters."
        icon="👥"
    />
@else
    <div class="data-table-wrapper">
        <table class="data-table data-table--striped">
            <thead class="data-table__head">
                <tr>
                    <th scope="col" class="data-table__header">Name</th>
                    <th scope="col" class="data-table__header">Phone</th>
                    <th scope="col" class="data-table__header">Notes</th>
                    <th scope="col" class="data-table__header">Status</th>
                    <th scope="col" class="data-table__header">Actions</th>
                </tr>
            </thead>
            <tbody id="customers-body" class="data-table__body">
                @foreach($customers as $customer)
                    @include('customers.partials.entry-row', ['customer' => $customer])
                @endforeach
            </tbody>
        </table>
    </div>
@endif
