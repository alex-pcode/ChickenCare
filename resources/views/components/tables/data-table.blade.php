@props([
    'headers' => [],
    'striped' => true,
])

<div class="data-table-wrapper">
    <table class="data-table {{ $striped ? 'data-table--striped' : '' }}">
        <thead class="data-table__head">
            <tr>
                @foreach($headers as $header)
                    <th scope="col" class="data-table__header">{{ $header }}</th>
                @endforeach
            </tr>
        </thead>
        <tbody class="data-table__body">
            {{ $slot }}
        </tbody>
    </table>
</div>
