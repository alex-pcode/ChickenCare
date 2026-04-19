<div
    id="modal-container"
    role="dialog"
    aria-modal="true"
    aria-labelledby="modal-title"
    class="fixed inset-0 z-50 flex items-center justify-center"
    x-data="flockModal()"
    x-init="open()"
    @keydown.escape.window="close()"
    @modal:close.window="close()"
    @keydown="trapFocus($event)">

    <div
        class="absolute inset-0 bg-black/50 backdrop-blur-sm"
        @click="close()"
        aria-hidden="true"></div>

    <div
        class="relative bg-white dark:bg-gray-800 rounded-lg shadow-xl w-full max-w-lg max-h-[90vh] overflow-y-auto mx-4"
        x-ref="panel"
        @click.stop>

        <div class="flex items-center justify-between p-6 border-b border-gray-200 dark:border-gray-700">
            <h2 id="modal-title" class="text-lg font-semibold text-gray-900 dark:text-white">
                🥚 Set Laying Date
            </h2>
            <button
                type="button"
                class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-200 transition-colors"
                @click="close()"
                aria-label="Close modal">
                &times;
            </button>
        </div>

        <div class="p-6">
            @if(in_array($batch->type, ['hens', 'mixed']))
                <p class="text-sm text-gray-600 dark:text-gray-400 mb-4">
                    When did this batch start laying? This helps calculate laying statistics.
                </p>
            @else
                <p class="text-sm text-amber-600 dark:text-amber-400 mb-4">
                    This batch is a <strong>{{ $batch->type }}</strong> batch.
                    Setting a laying date is unusual for this type.
                </p>
            @endif

            <form
                hx-patch="{{ route('app.batches.laying-date', $batch) }}"
                hx-target="closest [role='dialog']"
                hx-swap="outerHTML"
                id="laying-date-form">
                @csrf
                @method('PATCH')

                <div class="form-group mb-4">
                    <label class="form-group__label" for="actual_laying_start_date">Laying Start Date</label>
                    <input
                        type="date"
                        id="actual_laying_start_date"
                        name="actual_laying_start_date"
                        class="form-group__input w-full"
                        max="{{ now()->format('Y-m-d') }}"
                        value="{{ $batch->actual_laying_start_date?->format('Y-m-d') }}">
                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Leave empty to clear the laying date.</p>
                </div>
            </form>
        </div>

        <div class="flex justify-end gap-3 px-6 pb-6">
            <button type="button" class="btn btn--secondary" @click="close()">Cancel</button>
            <button type="submit" form="laying-date-form" class="btn btn--primary">Set Date</button>
        </div>
    </div>
</div>
