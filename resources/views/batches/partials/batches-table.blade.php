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
            :title="__('batches.table.empty_title')"
            :description="__('batches.table.empty_description')"
            icon="📦"
            :action="route('app.batches.create')"
            :action-label="__('batches.table.empty_action')"
        />
    @else
        <p class="batches__hint">{{ __('batches.table.hint') }}</p>

        <div class="data-table-wrapper">
            <table class="data-table data-table--striped">
                <thead class="data-table__head">
                    <tr>
                        @foreach ([
                            'batch_name' => __('batches.table.columns.batch_name'),
                            'current_count' => __('batches.table.columns.current_count'),
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

                        <th scope="col" class="data-table__header">{{ __('batches.table.columns.status') }}</th>

                        @foreach ([
                            'initial_count' => __('batches.table.columns.initial_count'),
                            'acquisition_date' => __('batches.table.columns.acquisition_date'),
                            'source' => __('batches.table.columns.source'),
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

                        <th scope="col" class="data-table__header">{{ __('batches.table.columns.laying_since') }}</th>
                    </tr>
                </thead>

                <tbody class="data-table__body">
                    @foreach($batches as $batch)
                        <tr id="batch-row-{{ $batch->id }}"
                            class="data-table__row batches__row"
                            onclick="window.location='{{ route('app.batches.show', $batch) }}'"
                            role="row"
                            tabindex="0"
                            aria-label="{{ __('batches.table.aria.view_details', ['batch' => $batch->batch_name]) }}"
                            @keydown.enter="window.location='{{ route('app.batches.show', $batch) }}'">

                            <td class="data-table__cell" data-label="{{ __('batches.table.columns.batch_name') }}">
                                <div class="batches__name-cell">
                                    <span class="batches__name-icon" aria-hidden="true">{{ $typeIcons[$batch->type] ?? '🐔' }}</span>
                                    <div class="batches__name-info">
                                        <span class="batches__name-primary">{{ $batch->batch_name }}</span>
                                        <span class="batches__name-secondary">{{ $batch->breed }}</span>
                                    </div>
                                </div>
                            </td>

                            <td class="data-table__cell" data-label="{{ __('batches.table.columns.current_count') }}">
                                <span class="batches__count-value">{{ $batch->current_count }}</span>
                            </td>

                            <td class="data-table__cell" data-label="{{ __('batches.table.columns.status') }}">
                                @if($batch->actual_laying_start_date !== null)
                                    <span class="batches__status batches__status--laying">🥚 {{ __('batches.table.status.laying') }}</span>
                                @elseif(in_array($batch->type, ['hens', 'mixed']))
                                    <span class="batches__status batches__status--not-laying">⏳ {{ __('batches.table.status.not_laying') }}</span>
                                @else
                                    <span class="batches__muted">—</span>
                                @endif
                            </td>

                            <td class="data-table__cell" data-label="{{ __('batches.table.columns.initial_count') }}">{{ $batch->initial_count }}</td>

                            <td class="data-table__cell" data-label="{{ __('batches.table.columns.acquisition_date') }}">{{ $batch->acquisition_date->translatedFormat('d. M Y.') }}</td>

                            <td class="data-table__cell" data-label="{{ __('batches.table.columns.source') }}">
                                {{ $batch->source ?: '—' }}
                            </td>

                            <td class="data-table__cell" data-label="{{ __('batches.table.columns.laying_since') }}">
                                <span class="batches__laying-date-cell">
                                    @if($batch->actual_laying_start_date)
                                        {{ $batch->actual_laying_start_date->translatedFormat('d. M Y.') }}
                                    @elseif(in_array($batch->type, ['hens', 'mixed']))
                                        <span class="batches__muted"><em>{{ __('batches.table.not_set') }}</em></span>
                                    @else
                                        <span class="batches__muted">—</span>
                                    @endif
                                    <button type="button"
                                            class="batches__laying-date-btn"
                                            hx-get="{{ route('app.batches.laying-date-modal', $batch) }}"
                                            hx-target="#modal-container"
                                            hx-swap="innerHTML"
                                            title="{{ __('batches.table.edit_laying_date') }}"
                                            aria-label="{{ __('batches.table.aria.edit_laying_date', ['batch' => $batch->batch_name]) }}"
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
