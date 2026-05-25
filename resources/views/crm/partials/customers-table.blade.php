@php
    $sortIcon = fn(string $col) => $sort === $col ? ($dir === 'asc' ? '↑' : '↓') : '';
    $nextDir = fn(string $col) => $sort === $col && $dir === 'asc' ? 'desc' : 'asc';
@endphp
<div class="data-table-wrapper">
    <table class="data-table data-table--striped">
        <thead class="data-table__head">
            <tr>
                <th scope="col" class="data-table__header data-table__header--sortable"
                    hx-get="{{ route('app.crm.index', ['tab' => 'customers', 'sort' => 'name', 'dir' => $nextDir('name')]) }}"
                    hx-target="#crm-customers-table"
                    hx-swap="innerHTML"
                    style="cursor: pointer;">
                    {{ __('crm.customers.table.name') }} {!! $sortIcon('name') !!}
                </th>
                <th scope="col" class="data-table__header">{{ __('crm.customers.table.phone') }}</th>
                <th scope="col" class="data-table__header">{{ __('crm.customers.table.notes') }}</th>
                <th scope="col" class="data-table__header data-table__header--sortable"
                    hx-get="{{ route('app.crm.index', ['tab' => 'customers', 'sort' => 'created_at', 'dir' => $nextDir('created_at')]) }}"
                    hx-target="#crm-customers-table"
                    hx-swap="innerHTML"
                    style="cursor: pointer;">
                    {{ __('crm.customers.table.added') }} {!! $sortIcon('created_at') !!}
                </th>
                <th scope="col" class="data-table__header">{{ __('crm.customers.table.actions') }}</th>
            </tr>
        </thead>
        <tbody class="data-table__body">
            @foreach($customers as $customer)
                @include('crm.partials.customer-row', ['customer' => $customer])
            @endforeach
        </tbody>
    </table>
</div>
