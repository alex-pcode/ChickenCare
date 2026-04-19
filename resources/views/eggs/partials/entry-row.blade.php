<tr id="egg-entry-{{ $entry->id }}">
    <td class="data-table__cell">{{ $entry->date->format('M d, Y') }}</td>
    <td class="data-table__cell egg-counter__table-count">{{ $entry->count }}</td>
    <td class="data-table__cell">
        @if($entry->size)
            <span class="egg-counter__table-cell-secondary">
                {{ \Illuminate\Support\Str::title(str_replace('-', ' ', $entry->size)) }}
            </span>
        @else
            <span class="egg-counter__table-empty">—</span>
        @endif
    </td>
    <td class="data-table__cell">
        @if($entry->color)
            <span class="inline-flex items-center gap-1 egg-counter__table-cell-secondary">
                <span class="egg-counter__color-dot{{ ' egg-counter__color-dot--' . $entry->color }}"></span>
                <span>{{ \Illuminate\Support\Str::title($entry->color) }}</span>
            </span>
        @else
            <span class="egg-counter__table-empty">—</span>
        @endif
    </td>
    <td class="data-table__cell">
        @if($entry->notes)
            <span class="egg-counter__table-cell-secondary max-w-32 truncate"
                  title="{{ $entry->notes }}">
                {{ $entry->notes }}
            </span>
        @else
            <span class="egg-counter__table-empty">—</span>
        @endif
    </td>
    <td class="data-table__cell egg-counter__actions">
        <button type="button" class="btn btn--sm btn--secondary"
            hx-get="{{ route('app.eggs.edit-form', $entry) }}"
            hx-target="#egg-entry-{{ $entry->id }}"
            hx-swap="outerHTML"
            aria-label="Edit entry for {{ $entry->date->format('M d, Y') }}">
            Edit
        </button>
        <button type="button"
            class="text-red-600 hover:text-red-800 dark:text-red-500 dark:hover:text-red-400 transition-colors duration-200 p-1 rounded hover:bg-red-50 dark:hover:bg-red-900/30"
            hx-get="{{ route('app.eggs.delete-confirm', $entry) }}"
            hx-target="#modal-container"
            aria-label="Delete entry for {{ $entry->date->format('M d, Y') }}">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                      d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
            </svg>
        </button>
    </td>
</tr>
