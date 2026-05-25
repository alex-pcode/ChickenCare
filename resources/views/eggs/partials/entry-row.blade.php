<tr id="egg-entry-{{ $entry->id }}">
    <td class="data-table__cell" data-label="{{ __('eggs.table.columns.date') }}">{{ $entry->date->format('M d, Y') }}</td>
    <td class="data-table__cell egg-counter__table-count" data-label="{{ __('eggs.table.columns.eggs') }}">{{ $entry->count }}</td>
    <td class="data-table__cell" data-label="{{ __('eggs.table.columns.size') }}">
        @if($entry->size)
            <span class="egg-counter__table-cell-secondary">
                {{ \Illuminate\Support\Str::title(str_replace('-', ' ', $entry->size)) }}
            </span>
        @else
            <span class="egg-counter__table-empty">—</span>
        @endif
    </td>
    <td class="data-table__cell" data-label="{{ __('eggs.table.columns.color') }}">
        @if($entry->color)
            <span class="inline-flex items-center gap-1 egg-counter__table-cell-secondary">
                <span class="egg-counter__color-dot{{ ' egg-counter__color-dot--' . $entry->color }}"></span>
                <span>{{ \Illuminate\Support\Str::title($entry->color) }}</span>
            </span>
        @else
            <span class="egg-counter__table-empty">—</span>
        @endif
    </td>
    <td class="data-table__cell" data-label="{{ __('eggs.table.columns.notes') }}">
        @if($entry->notes)
            <span class="egg-counter__table-cell-secondary max-w-32 truncate"
                  title="{{ $entry->notes }}">
                {{ $entry->notes }}
            </span>
        @else
            <span class="egg-counter__table-empty">—</span>
        @endif
    </td>
    <td class="data-table__cell data-table__actions" data-label="{{ __('eggs.table.columns.actions') }}">
        <button type="button" class="btn btn--sm btn--secondary"
            hx-get="{{ route('app.eggs.edit-form', $entry) }}"
            hx-target="#egg-entry-{{ $entry->id }}"
            hx-swap="outerHTML"
            aria-label="{{ __('eggs.actions.edit_aria_label', ['date' => $entry->date->format('M d, Y')]) }}">
            {{ __('eggs.actions.edit') }}
        </button>
        <button type="button"
            class="data-table__delete-btn"
            hx-get="{{ route('app.eggs.delete-confirm', $entry) }}"
            hx-target="#modal-container"
            aria-label="{{ __('eggs.actions.delete_aria_label', ['date' => $entry->date->format('M d, Y')]) }}">
            <svg fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round"
                      d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0" />
            </svg>
        </button>
    </td>
</tr>
