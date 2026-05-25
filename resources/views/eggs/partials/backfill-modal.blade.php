<div class="egg-counter__backfill-modal" role="dialog" aria-modal="true" aria-labelledby="backfill-title"
    x-data="{
        rows: [
            { date: '', count: '' },
            { date: '', count: '' },
            { date: '', count: '' },
            { date: '', count: '' },
            { date: '', count: '' }
        ],
        addRow() { this.rows.push({ date: '', count: '' }); },
        close() { document.getElementById('backfill-modal').innerHTML = ''; }
    }"
    x-init="$nextTick(() => $el.querySelector('input')?.focus())"
    @keydown.escape.window="close()">

    <div class="egg-counter__backfill-modal-overlay" @click="close()"></div>

    <div class="egg-counter__backfill-modal-content">
        <div class="egg-counter__backfill-modal-header">
            <h2 id="backfill-title" class="egg-counter__backfill-modal-title">{{ __('eggs.backfill.modal_title') }}</h2>
            <button type="button" class="btn btn--sm btn--secondary" @click="close()" aria-label="Close modal">&times;</button>
        </div>

        <p class="egg-counter__backfill-modal-desc">{{ __('eggs.backfill.description') }}</p>

        <form hx-post="{{ route('app.eggs.backfill') }}"
              hx-target="#backfill-modal"
              hx-swap="innerHTML">
            @csrf

            <div class="egg-counter__backfill-rows">
                <template x-for="(row, index) in rows" :key="index">
                    <div class="egg-counter__backfill-row">
                        <div class="form-group">
                            <label class="form-label" x-bind:for="'entries_' + index + '_date'">{{ __('eggs.backfill.date_label') }}</label>
                            <input type="date"
                                   class="egg-counter__input"
                                   x-bind:id="'entries_' + index + '_date'"
                                   x-bind:name="'entries[' + index + '][date]'"
                                   x-model="row.date"
                                   max="{{ now()->format('Y-m-d') }}"
                                   min="{{ now()->subDays(90)->format('Y-m-d') }}"
                                   required>
                        </div>
                        <div class="form-group">
                            <label class="form-label" x-bind:for="'entries_' + index + '_count'">{{ __('eggs.backfill.count_label') }}</label>
                            <input type="number"
                                   class="egg-counter__input"
                                   x-bind:id="'entries_' + index + '_count'"
                                   x-bind:name="'entries[' + index + '][count]'"
                                   x-model="row.count"
                                   min="0"
                                   required>
                        </div>
                    </div>
                </template>
            </div>

            <div class="egg-counter__backfill-modal-actions">
                <button type="button" class="btn btn--sm btn--secondary" @click="addRow()">{{ __('eggs.backfill.add_row') }}</button>
                <div class="egg-counter__backfill-modal-actions-right">
                    <button type="button" class="btn btn--sm btn--secondary" @click="close()">{{ __('eggs.backfill.cancel') }}</button>
                    <button type="submit" class="btn btn--sm btn--primary">{{ __('eggs.backfill.save') }}</button>
                </div>
            </div>
        </form>
    </div>
</div>
