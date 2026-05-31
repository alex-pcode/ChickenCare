<div class="egg-counter__backfill-modal" role="dialog" aria-modal="true" aria-labelledby="backfill-title"
    x-data="{
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

            <div class="egg-counter__backfill-row">
                <div class="form-group">
                    <label class="form-label" for="backfill_start_date">{{ __('eggs.backfill.start_date_label') }}</label>
                    <input type="date"
                           class="egg-counter__input"
                           id="backfill_start_date"
                           name="start_date"
                           value="{{ now()->subDays(7)->format('Y-m-d') }}"
                           min="{{ now()->subDays(90)->format('Y-m-d') }}"
                           max="{{ now()->format('Y-m-d') }}"
                           required>
                </div>
                <div class="form-group">
                    <label class="form-label" for="backfill_end_date">{{ __('eggs.backfill.end_date_label') }}</label>
                    <input type="date"
                           class="egg-counter__input"
                           id="backfill_end_date"
                           name="end_date"
                           value="{{ now()->format('Y-m-d') }}"
                           min="{{ now()->subDays(90)->format('Y-m-d') }}"
                           max="{{ now()->format('Y-m-d') }}"
                           required>
                </div>
            </div>

            <div class="form-group">
                <label class="form-label" for="backfill_average">{{ __('eggs.backfill.average_label') }}</label>
                <input type="number"
                       class="egg-counter__input"
                       id="backfill_average"
                       name="average"
                       min="0"
                       max="1000"
                       step="1"
                       placeholder="10"
                       required>
                <p class="egg-counter__backfill-hint">{{ __('eggs.backfill.average_hint') }}</p>
            </div>

            <div class="egg-counter__backfill-modal-actions">
                <div class="egg-counter__backfill-modal-actions-right">
                    <button type="button" class="btn btn--sm btn--secondary" @click="close()">{{ __('eggs.backfill.cancel') }}</button>
                    <button type="submit" class="btn btn--sm btn--primary">{{ __('eggs.backfill.save') }}</button>
                </div>
            </div>
        </form>
    </div>
</div>
