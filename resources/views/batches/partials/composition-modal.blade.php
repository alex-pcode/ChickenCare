<div
    id="flock-modal"
    role="dialog"
    aria-modal="true"
    aria-labelledby="modal-title"
    class="modal modal--md"
    x-data="flockModal()"
    x-init="open()"
    @keydown.escape.window="close()"
    @modal:close.window="close()"
    @keydown="trapFocus($event)">

    <div class="modal__overlay" @click="close()" aria-hidden="true"></div>

    <div class="modal__content" x-ref="panel" @click.stop>
        <div class="modal__header">
            <h2 id="modal-title" class="modal__title">✏️ Edit Batch Composition</h2>
            <button
                type="button"
                class="modal__close"
                @click="close()"
                aria-label="Close modal">
                &times;
            </button>
        </div>

        <div class="modal__body"
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

            <div class="batches__form-banner batches__form-banner--warning">
                <span aria-hidden="true">⚠️</span>
                <p>Adjusting counts here does not log a death. Use the Log Loss form to log losses.</p>
            </div>

            <form
                hx-patch="{{ route('app.batches.composition', $batch) }}"
                hx-target="closest [role='dialog']"
                hx-swap="outerHTML"
                id="composition-form">
                @csrf
                @method('PATCH')

                <div class="form-row form-row--2-col">
                    <div class="form-group">
                        <label for="hens_count" class="form-group__label">🐔 Hens</label>
                        <input
                            type="number"
                            id="hens_count"
                            name="hens_count"
                            min="0"
                            x-model.number="hens"
                            class="form-group__input">
                    </div>
                    <div class="form-group">
                        <label for="brooding_count" class="form-group__label">🪺 Brooding Hens</label>
                        <input
                            type="number"
                            id="brooding_count"
                            name="brooding_count"
                            min="0"
                            x-model.number="brooding"
                            class="form-group__input">
                    </div>
                </div>
                <div class="form-row form-row--2-col">
                    <div class="form-group">
                        <label for="roosters_count" class="form-group__label">🐓 Roosters</label>
                        <input
                            type="number"
                            id="roosters_count"
                            name="roosters_count"
                            min="0"
                            x-model.number="roosters"
                            class="form-group__input">
                    </div>
                    <div class="form-group">
                        <label for="chicks_count" class="form-group__label">🐥 Chicks</label>
                        <input
                            type="number"
                            id="chicks_count"
                            name="chicks_count"
                            min="0"
                            x-model.number="chicks"
                            class="form-group__input">
                    </div>
                </div>

                <template x-if="total > 0">
                    <div class="batches__composition-preview">
                        <h4>Updated Composition:</h4>
                        <p><strong x-text="`Total: ${total} birds`"></strong></p>
                        <p x-text="`Type will be recalculated to: ${batchType}`"></p>
                    </div>
                </template>
            </form>
        </div>

        <div class="modal__actions">
            <button type="button" class="btn btn--secondary" @click="close()">Cancel</button>
            <button type="submit" form="composition-form" class="btn btn--primary">Save Composition</button>
        </div>
    </div>
</div>
