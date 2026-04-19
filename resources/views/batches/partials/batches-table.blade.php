@php
$typeIcons = [
    'hens'     => '🐔',
    'roosters' => '🐓',
    'chicks'   => '🐥',
    'mixed'    => '🥚',
];

$sortLink = function (string $col) use ($sort, $dir) {
    $nextDir = ($sort === $col && $dir === 'asc') ? 'desc' : 'asc';
    return route('app.batches.index', array_merge(request()->query(), [
        'sort' => $col,
        'dir' => $nextDir,
        'page' => 1,
    ]));
};
@endphp

<section class="batches__list-section"
         id="batches-list-region"
         x-data="{}"
         x-init="$nextTick(() => $el.classList.add('batches__list-section--enter'))"
         hx-get="{{ route('app.batches.index', request()->query()) }}"
         hx-trigger="flock:changed from:body"
         hx-target="#batches-list-region"
         hx-swap="outerHTML"
         hx-sync="this:replace">

    @if($batches->count() === 0)
        <x-ui.empty-state
            title="No Batches Yet"
            description="Start organizing your flock by adding your first batch"
            icon="📦"
            :action="route('app.batches.create')"
            actionLabel="Add First Batch"
        />
    @else
        <p class="batches__hint">💡 Click any row to view details, composition, and timeline.</p>

        <div class="data-table-wrapper">
            <table class="data-table data-table--striped">
                <thead class="data-table__head">
                    <tr>
                        @foreach ([
                            'batch_name' => 'Batch Name',
                            'current_count' => 'Current Count',
                        ] as $col => $label)
                            @php($isActive = $sort === $col)
                            <th scope="col" class="data-table__header"
                                aria-sort="{{ $isActive ? ($dir === 'asc' ? 'ascending' : 'descending') : 'none' }}">
                                <a href="{{ $sortLink($col) }}"
                                   hx-get="{{ $sortLink($col) }}"
                                   hx-target="#batches-list-region"
                                   hx-swap="outerHTML"
                                   hx-push-url="true"
                                   class="batches__sort-link {{ $isActive ? 'batches__sort-link--active' : '' }}">
                                    {{ $label }}
                                    @if($isActive)<span aria-hidden="true">{{ $dir === 'asc' ? '↑' : '↓' }}</span>@endif
                                </a>
                            </th>
                        @endforeach

                        <th scope="col" class="data-table__header">Status</th>

                        @foreach ([
                            'initial_count' => 'Started With',
                            'acquisition_date' => 'Acquired',
                            'source' => 'Source',
                        ] as $col => $label)
                            @php($isActive = $sort === $col)
                            <th scope="col" class="data-table__header"
                                aria-sort="{{ $isActive ? ($dir === 'asc' ? 'ascending' : 'descending') : 'none' }}">
                                <a href="{{ $sortLink($col) }}"
                                   hx-get="{{ $sortLink($col) }}"
                                   hx-target="#batches-list-region"
                                   hx-swap="outerHTML"
                                   hx-push-url="true"
                                   class="batches__sort-link {{ $isActive ? 'batches__sort-link--active' : '' }}">
                                    {{ $label }}
                                    @if($isActive)<span aria-hidden="true">{{ $dir === 'asc' ? '↑' : '↓' }}</span>@endif
                                </a>
                            </th>
                        @endforeach

                        <th scope="col" class="data-table__header">Laying Since</th>
                    </tr>
                </thead>

                <tbody class="data-table__body">
                    @foreach($batches as $batch)
                        <tr id="batch-row-{{ $batch->id }}"
                            class="data-table__row batches__row"
                            onclick="window.location='{{ route('app.batches.show', $batch) }}'"
                            role="row"
                            tabindex="0"
                            aria-label="View details for {{ $batch->batch_name }}"
                            @keydown.enter="window.location='{{ route('app.batches.show', $batch) }}'">

                            <td class="data-table__cell">
                                <div class="batches__name-cell">
                                    <span class="batches__name-icon" aria-hidden="true">{{ $typeIcons[$batch->type] ?? '🐔' }}</span>
                                    <div class="batches__name-info">
                                        <span class="batches__name-primary">{{ $batch->batch_name }}</span>
                                        <span class="batches__name-secondary">{{ $batch->breed }}</span>
                                    </div>
                                </div>
                            </td>

                            <td class="data-table__cell">
                                <span class="batches__count-value">{{ $batch->current_count }}</span>
                            </td>

                            <td class="data-table__cell">
                                @if($batch->actual_laying_start_date !== null)
                                    <span class="batches__status batches__status--laying">🥚 Laying</span>
                                @elseif(in_array($batch->type, ['hens', 'mixed']))
                                    <span class="batches__status batches__status--not-laying">⏳ Not Laying</span>
                                @else
                                    <span class="batches__muted">—</span>
                                @endif
                            </td>

                            <td class="data-table__cell">{{ $batch->initial_count }}</td>

                            <td class="data-table__cell">{{ $batch->acquisition_date->format('M j, Y') }}</td>

                            <td class="data-table__cell">
                                {{ $batch->source ?: '—' }}
                            </td>

                            <td class="data-table__cell">
                                <span class="batches__laying-date-cell">
                                    @if($batch->actual_laying_start_date)
                                        {{ $batch->actual_laying_start_date->format('M j, Y') }}
                                    @elseif(in_array($batch->type, ['hens', 'mixed']))
                                        <span class="batches__muted"><em>Not set</em></span>
                                    @else
                                        <span class="batches__muted">—</span>
                                    @endif
                                    <button type="button"
                                            class="batches__laying-date-btn"
                                            hx-get="{{ route('app.batches.laying-date-modal', $batch) }}"
                                            hx-target="#modal-container"
                                            hx-swap="innerHTML"
                                            title="Edit laying date"
                                            aria-label="Edit laying date for {{ $batch->batch_name }}"
                                            @click.stop>
                                        📅
                                    </button>
                                </span>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        @if($batches->hasPages())
            <x-tables.pagination :paginator="$batches" />
        @endif
    @endif

</section>
