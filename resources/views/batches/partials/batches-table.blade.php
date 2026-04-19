@php
$typeIcons = [
    'hens'     => '🐔',
    'roosters' => '🐓',
    'chicks'   => '🐥',
    'mixed'    => '🥚',
];
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
        <div class="flex flex-col items-center justify-center py-16 text-center">
            <span class="text-5xl mb-4">📦</span>
            <h3 class="text-xl font-semibold text-gray-700 dark:text-gray-300 mb-2">No Batches Yet</h3>
            <p class="text-sm text-gray-500 dark:text-gray-400 mb-4">
                Start organizing your flock by adding your first batch
            </p>
            <a href="{{ route('app.batches.create') }}" class="btn btn--primary">
                Add First Batch
            </a>
        </div>
    @else
        <p class="text-sm text-gray-600 dark:text-gray-400 mb-4">
            💡 Click on any batch name to view details, composition, and timeline
        </p>

        <div class="data-table-wrapper overflow-x-auto">
            <table class="data-table data-table--striped w-full">
                <thead class="data-table__head">
                    <tr>
                        {{-- Batch Name: sortable --}}
                        <th scope="col" class="data-table__header"
                            aria-sort="{{ $sort === 'batch_name' ? ($dir === 'asc' ? 'ascending' : 'descending') : 'none' }}">
                            <a href="{{ route('app.batches.index', array_merge(request()->query(), ['sort' => 'batch_name', 'dir' => $sort === 'batch_name' && $dir === 'asc' ? 'desc' : 'asc', 'page' => 1])) }}"
                               hx-get="{{ route('app.batches.index', array_merge(request()->query(), ['sort' => 'batch_name', 'dir' => $sort === 'batch_name' && $dir === 'asc' ? 'desc' : 'asc', 'page' => 1])) }}"
                               hx-target="#batches-list-region"
                               hx-swap="outerHTML"
                               hx-push-url="true"
                               class="inline-flex items-center gap-1 hover:text-indigo-600 dark:hover:text-indigo-400">
                                Batch Name
                                @if($sort === 'batch_name')
                                    <span aria-hidden="true">{{ $dir === 'asc' ? '↑' : '↓' }}</span>
                                @endif
                            </a>
                        </th>

                        {{-- Current Count: sortable --}}
                        <th scope="col" class="data-table__header"
                            aria-sort="{{ $sort === 'current_count' ? ($dir === 'asc' ? 'ascending' : 'descending') : 'none' }}">
                            <a href="{{ route('app.batches.index', array_merge(request()->query(), ['sort' => 'current_count', 'dir' => $sort === 'current_count' && $dir === 'asc' ? 'desc' : 'asc', 'page' => 1])) }}"
                               hx-get="{{ route('app.batches.index', array_merge(request()->query(), ['sort' => 'current_count', 'dir' => $sort === 'current_count' && $dir === 'asc' ? 'desc' : 'asc', 'page' => 1])) }}"
                               hx-target="#batches-list-region"
                               hx-swap="outerHTML"
                               hx-push-url="true"
                               class="inline-flex items-center gap-1 hover:text-indigo-600 dark:hover:text-indigo-400">
                                Current Count
                                @if($sort === 'current_count')
                                    <span aria-hidden="true">{{ $dir === 'asc' ? '↑' : '↓' }}</span>
                                @endif
                            </a>
                        </th>

                        <th scope="col" class="data-table__header">Status</th>

                        {{-- Started With: sortable --}}
                        <th scope="col" class="data-table__header"
                            aria-sort="{{ $sort === 'initial_count' ? ($dir === 'asc' ? 'ascending' : 'descending') : 'none' }}">
                            <a href="{{ route('app.batches.index', array_merge(request()->query(), ['sort' => 'initial_count', 'dir' => $sort === 'initial_count' && $dir === 'asc' ? 'desc' : 'asc', 'page' => 1])) }}"
                               hx-get="{{ route('app.batches.index', array_merge(request()->query(), ['sort' => 'initial_count', 'dir' => $sort === 'initial_count' && $dir === 'asc' ? 'desc' : 'asc', 'page' => 1])) }}"
                               hx-target="#batches-list-region"
                               hx-swap="outerHTML"
                               hx-push-url="true"
                               class="inline-flex items-center gap-1 hover:text-indigo-600 dark:hover:text-indigo-400">
                                Started With
                                @if($sort === 'initial_count')
                                    <span aria-hidden="true">{{ $dir === 'asc' ? '↑' : '↓' }}</span>
                                @endif
                            </a>
                        </th>

                        {{-- Acquired: sortable --}}
                        <th scope="col" class="data-table__header"
                            aria-sort="{{ $sort === 'acquisition_date' ? ($dir === 'asc' ? 'ascending' : 'descending') : 'none' }}">
                            <a href="{{ route('app.batches.index', array_merge(request()->query(), ['sort' => 'acquisition_date', 'dir' => $sort === 'acquisition_date' && $dir === 'asc' ? 'desc' : 'asc', 'page' => 1])) }}"
                               hx-get="{{ route('app.batches.index', array_merge(request()->query(), ['sort' => 'acquisition_date', 'dir' => $sort === 'acquisition_date' && $dir === 'asc' ? 'desc' : 'asc', 'page' => 1])) }}"
                               hx-target="#batches-list-region"
                               hx-swap="outerHTML"
                               hx-push-url="true"
                               class="inline-flex items-center gap-1 hover:text-indigo-600 dark:hover:text-indigo-400">
                                Acquired
                                @if($sort === 'acquisition_date')
                                    <span aria-hidden="true">{{ $dir === 'asc' ? '↑' : '↓' }}</span>
                                @endif
                            </a>
                        </th>

                        {{-- Source: sortable --}}
                        <th scope="col" class="data-table__header"
                            aria-sort="{{ $sort === 'source' ? ($dir === 'asc' ? 'ascending' : 'descending') : 'none' }}">
                            <a href="{{ route('app.batches.index', array_merge(request()->query(), ['sort' => 'source', 'dir' => $sort === 'source' && $dir === 'asc' ? 'desc' : 'asc', 'page' => 1])) }}"
                               hx-get="{{ route('app.batches.index', array_merge(request()->query(), ['sort' => 'source', 'dir' => $sort === 'source' && $dir === 'asc' ? 'desc' : 'asc', 'page' => 1])) }}"
                               hx-target="#batches-list-region"
                               hx-swap="outerHTML"
                               hx-push-url="true"
                               class="inline-flex items-center gap-1 hover:text-indigo-600 dark:hover:text-indigo-400">
                                Source
                                @if($sort === 'source')
                                    <span aria-hidden="true">{{ $dir === 'asc' ? '↑' : '↓' }}</span>
                                @endif
                            </a>
                        </th>

                        <th scope="col" class="data-table__header">Laying Since</th>
                    </tr>
                </thead>

                <tbody class="data-table__body">
                    @foreach($batches as $batch)
                        <tr id="batch-row-{{ $batch->id }}"
                            class="batches__row cursor-pointer hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors"
                            onclick="window.location='{{ route('app.batches.show', $batch) }}'"
                            role="row"
                            tabindex="0"
                            aria-label="View details for {{ $batch->batch_name }}"
                            @keydown.enter="window.location='{{ route('app.batches.show', $batch) }}'">

                            <td class="data-table__cell">
                                <div class="flex items-center gap-3">
                                    <span class="text-xl flex-shrink-0" aria-hidden="true">
                                        {{ $typeIcons[$batch->type] ?? '🐔' }}
                                    </span>
                                    <div class="min-w-0 flex-1">
                                        <div class="font-semibold text-gray-900 dark:text-white break-words hover:text-indigo-600 dark:hover:text-indigo-400">
                                            {{ $batch->batch_name }}
                                        </div>
                                        <div class="text-sm text-gray-600 dark:text-gray-400 break-words">
                                            {{ $batch->breed }}
                                        </div>
                                    </div>
                                </div>
                            </td>

                            <td class="data-table__cell">
                                <span class="text-lg font-bold text-indigo-600 dark:text-indigo-400">
                                    {{ $batch->current_count }}
                                </span>
                            </td>

                            <td class="data-table__cell">
                                @if($batch->actual_laying_start_date !== null)
                                    <span class="text-xs px-2 py-1 rounded-full font-medium inline-flex items-center gap-1 bg-green-100 text-green-700 border border-green-200 dark:bg-green-900/30 dark:text-green-400 dark:border-green-800">
                                        🥚 Laying
                                    </span>
                                @elseif(in_array($batch->type, ['hens', 'mixed']))
                                    <span class="text-xs px-2 py-1 rounded-full font-medium inline-flex items-center gap-1 bg-amber-100 text-amber-700 border border-amber-200 dark:bg-amber-900/30 dark:text-amber-400 dark:border-amber-800">
                                        ⏳ Not Laying
                                    </span>
                                @else
                                    <span class="text-gray-400">—</span>
                                @endif
                            </td>

                            <td class="data-table__cell">
                                <span class="font-semibold text-gray-700 dark:text-gray-300">
                                    {{ $batch->initial_count }}
                                </span>
                            </td>

                            <td class="data-table__cell">
                                <span class="text-sm text-gray-600 dark:text-gray-400">
                                    {{ $batch->acquisition_date->format('M j, Y') }}
                                </span>
                            </td>

                            <td class="data-table__cell">
                                <span class="text-sm text-gray-600 dark:text-gray-400">
                                    {{ $batch->source ?: '—' }}
                                </span>
                            </td>

                            <td class="data-table__cell">
                                <div class="flex items-center gap-2">
                                    @if($batch->actual_laying_start_date)
                                        <span class="text-sm text-gray-700 dark:text-gray-300">
                                            {{ $batch->actual_laying_start_date->format('M j, Y') }}
                                        </span>
                                    @elseif(in_array($batch->type, ['hens', 'mixed']))
                                        <span class="text-gray-500 italic text-sm">Not set</span>
                                    @else
                                        <span class="text-gray-400">—</span>
                                    @endif
                                    <button type="button"
                                            class="batches__laying-date-btn text-sm hover:text-indigo-600 dark:hover:text-indigo-400 transition-colors"
                                            hx-get="{{ route('app.batches.laying-date-modal', $batch) }}"
                                            hx-target="#modal-container"
                                            hx-swap="innerHTML"
                                            title="Edit laying date"
                                            aria-label="Edit laying date for {{ $batch->batch_name }}"
                                            @click.stop>
                                        📅
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        @if($batches->hasPages())
            <div class="mt-4">
                <x-tables.pagination :paginator="$batches" />
            </div>
        @endif
    @endif

</section>
