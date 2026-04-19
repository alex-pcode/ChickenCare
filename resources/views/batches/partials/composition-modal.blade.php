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
                ✏️ Edit Batch Composition
            </h2>
            <button
                type="button"
                class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-200 transition-colors"
                @click="close()"
                aria-label="Close modal">
                &times;
            </button>
        </div>

        <div class="p-6"
             x-data="{
                 hens:     {{ $batch->hens_count }},
                 brooding: {{ $batch->brooding_count }},
                 roosters: {{ $batch->roosters_count }},
                 chicks:   {{ $batch->chicks_count }},
                 get total() { return this.hens + this.brooding + this.roosters + this.chicks; },
                 get batchType() {
                     const hensAndBrooding = this.hens + this.brooding;
                     if (hensAndBrooding > 0 && this.roosters === 0 && this.chicks === 0) return 'hens';
                     if (this.roosters > 0 && hensAndBrooding === 0 && this.chicks === 0) return 'roosters';
                     if (this.chicks > 0 && hensAndBrooding === 0 && this.roosters === 0) return 'chicks';
                     return 'mixed';
                 }
             }">

            <div class="flex items-start gap-2 p-3 bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-700 rounded-lg text-sm text-amber-700 dark:text-amber-400 mb-4">
                <span class="shrink-0 mt-0.5" aria-hidden="true">⚠️</span>
                <p>Adjusting counts here does not log a death. Use the Log Loss form to log losses.</p>
            </div>

            <form
                hx-patch="{{ route('app.batches.composition', $batch) }}"
                hx-target="closest [role='dialog']"
                hx-swap="outerHTML"
                id="composition-form">
                @csrf
                @method('PATCH')

                <div class="grid grid-cols-2 gap-4 mb-4">
                    <div>
                        <label for="hens_count" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">🐔 Hens</label>
                        <input
                            type="number"
                            id="hens_count"
                            name="hens_count"
                            min="0"
                            x-model.number="hens"
                            class="form-group__input w-full">
                    </div>
                    <div>
                        <label for="brooding_count" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">🪺 Brooding Hens</label>
                        <input
                            type="number"
                            id="brooding_count"
                            name="brooding_count"
                            min="0"
                            x-model.number="brooding"
                            class="form-group__input w-full">
                    </div>
                    <div>
                        <label for="roosters_count" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">🐓 Roosters</label>
                        <input
                            type="number"
                            id="roosters_count"
                            name="roosters_count"
                            min="0"
                            x-model.number="roosters"
                            class="form-group__input w-full">
                    </div>
                    <div>
                        <label for="chicks_count" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">🐥 Chicks</label>
                        <input
                            type="number"
                            id="chicks_count"
                            name="chicks_count"
                            min="0"
                            x-model.number="chicks"
                            class="form-group__input w-full">
                    </div>
                </div>

                <template x-if="total > 0">
                    <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-4 mt-4">
                        <h4 class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Updated Composition:</h4>
                        <p class="text-sm text-gray-600 dark:text-gray-400">
                            <strong x-text="`Total: ${total} birds`"></strong>
                        </p>
                        <p class="text-sm text-gray-500 dark:text-gray-400 mt-1"
                           x-text="`Type will be recalculated to: ${batchType}`"></p>
                    </div>
                </template>
            </form>
        </div>

        <div class="flex justify-end gap-3 px-6 pb-6">
            <button type="button" class="btn btn--secondary" @click="close()">Cancel</button>
            <button type="submit" form="composition-form" class="btn btn--primary">Save Composition</button>
        </div>
    </div>
</div>
