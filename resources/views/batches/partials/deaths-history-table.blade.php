<section id="deaths-history-region"
         class="batches__deaths-history mt-6"
         hx-get="{{ route('app.batches.deaths.index', ['batch' => $batch, 'sort' => $sort ?? 'date', 'dir' => $dir ?? 'desc']) }}"
         hx-trigger="flock:changed from:body"
         hx-target="#deaths-history-region"
         hx-swap="outerHTML">

    <h2 class="text-2xl font-bold text-gray-900 dark:text-white mb-4">Loss History</h2>

    @if($records->count() === 0)
        <x-ui.empty-state
            title="No Losses Recorded"
            description="No bird losses have been logged for this batch yet"
            icon="📝"
        />
    @else
        <div class="data-table-wrapper overflow-x-auto">
            <table class="data-table data-table--striped w-full">
                <thead class="data-table__head">
                    <tr>
                        @php
                            $currentSort = $sort ?? 'date';
                            $currentDir  = $dir ?? 'desc';
                        @endphp

                        <th scope="col" class="data-table__header"
                            aria-sort="{{ $currentSort === 'date' ? ($currentDir === 'asc' ? 'ascending' : 'descending') : 'none' }}">
                            <a href="{{ route('app.batches.deaths.index', array_merge(request()->query(), ['batch' => $batch->id, 'sort' => 'date', 'dir' => $currentSort === 'date' && $currentDir === 'asc' ? 'desc' : 'asc', 'page' => 1])) }}"
                               hx-get="{{ route('app.batches.deaths.index', array_merge(request()->query(), ['batch' => $batch->id, 'sort' => 'date', 'dir' => $currentSort === 'date' && $currentDir === 'asc' ? 'desc' : 'asc', 'page' => 1])) }}"
                               hx-target="#deaths-history-region"
                               hx-swap="outerHTML"
                               hx-push-url="true"
                               class="inline-flex items-center gap-1 hover:text-indigo-600 dark:hover:text-indigo-400">
                                Date
                                @if($currentSort === 'date')
                                    <span aria-hidden="true">{{ $currentDir === 'asc' ? '↑' : '↓' }}</span>
                                @endif
                            </a>
                        </th>

                        <th scope="col" class="data-table__header"
                            aria-sort="{{ $currentSort === 'count' ? ($currentDir === 'asc' ? 'ascending' : 'descending') : 'none' }}">
                            <a href="{{ route('app.batches.deaths.index', array_merge(request()->query(), ['batch' => $batch->id, 'sort' => 'count', 'dir' => $currentSort === 'count' && $currentDir === 'asc' ? 'desc' : 'asc', 'page' => 1])) }}"
                               hx-get="{{ route('app.batches.deaths.index', array_merge(request()->query(), ['batch' => $batch->id, 'sort' => 'count', 'dir' => $currentSort === 'count' && $currentDir === 'asc' ? 'desc' : 'asc', 'page' => 1])) }}"
                               hx-target="#deaths-history-region"
                               hx-swap="outerHTML"
                               hx-push-url="true"
                               class="inline-flex items-center gap-1 hover:text-indigo-600 dark:hover:text-indigo-400">
                                Birds Lost
                                @if($currentSort === 'count')
                                    <span aria-hidden="true">{{ $currentDir === 'asc' ? '↑' : '↓' }}</span>
                                @endif
                            </a>
                        </th>

                        <th scope="col" class="data-table__header">Cause</th>
                        <th scope="col" class="data-table__header">Description</th>
                    </tr>
                </thead>
                <tbody class="data-table__body">
                    @foreach($records as $record)
                        @php
                            $causeValue = $record->cause instanceof \App\Enums\DeathCause
                                ? $record->cause
                                : \App\Enums\DeathCause::tryFrom($record->cause) ?? \App\Enums\DeathCause::Unknown;
                        @endphp
                        <tr>
                            <td class="data-table__cell">{{ $record->date->format('M j, Y') }}</td>
                            <td class="data-table__cell font-bold text-red-600 dark:text-red-400">{{ $record->count }}</td>
                            <td class="data-table__cell">
                                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium {{ $causeValue->badgeColor() }}">
                                    {{ $causeValue->label() }}
                                </span>
                            </td>
                            <td class="data-table__cell"
                                title="{{ $record->description }}">
                                {{ \Illuminate\Support\Str::limit($record->description, 50) }}
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>

            @if($records->hasPages())
                <div class="mt-4">
                    <x-tables.pagination :paginator="$records" />
                </div>
            @endif
        </div>
    @endif
</section>
