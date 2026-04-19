<tbody id="egg-entries-body" class="data-table__body">
    @foreach($entries as $entry)
        @include('eggs.partials.entry-row', ['entry' => $entry])
    @endforeach
</tbody>

<x-tables.pagination :paginator="$entries" />
