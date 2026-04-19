@if($sales->isEmpty())
    <x-ui.empty-state
        title="No sales found"
        description="No sales match your current filters."
        icon="🥚"
    />
@else
    <div class="data-table-wrapper">
        <table class="data-table data-table--striped">
            <thead class="data-table__head">
                <tr>
                    <th scope="col" class="data-table__header">Date</th>
                    <th scope="col" class="data-table__header">Dozens</th>
                    <th scope="col" class="data-table__header">Individual</th>
                    <th scope="col" class="data-table__header">Amount</th>
                    <th scope="col" class="data-table__header">Customer</th>
                    <th scope="col" class="data-table__header">Status</th>
                    <th scope="col" class="data-table__header">Actions</th>
                </tr>
            </thead>
            <tbody id="sales-body" class="data-table__body">
                @foreach($sales as $sale)
                    @include('sales.partials.entry-row', ['sale' => $sale])
                @endforeach
            </tbody>
        </table>
    </div>

    <x-tables.pagination :paginator="$sales" />
@endif
