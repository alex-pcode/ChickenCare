<div
    id="flock-modal"
    role="dialog"
    aria-modal="true"
    aria-labelledby="modal-title"
    class="modal modal--sm"
    x-data="flockModal()"
    x-init="open()"
    @keydown.escape.window="close()"
    @modal:close.window="close()"
    @keydown="trapFocus($event)">

    <div class="modal__overlay" @click="close()" aria-hidden="true"></div>

    <div class="modal__content" x-ref="panel" @click.stop>
        <div class="modal__header">
            <h2 id="modal-title" class="modal__title">🥚 Set Laying Date</h2>
            <button
                type="button"
                class="modal__close"
                @click="close()"
                aria-label="Close modal">
                &times;
            </button>
        </div>

        <div class="modal__body">
            @if(in_array($batch->type, ['hens', 'mixed']))
                <p class="modal__text">
                    When did this batch start laying? This helps calculate laying statistics.
                </p>
            @else
                <div class="batches__form-banner batches__form-banner--warning">
                    <span aria-hidden="true">⚠️</span>
                    <p>
                        This batch is a <strong>{{ $batch->type }}</strong> batch.
                        Setting a laying date is unusual for this type.
                    </p>
                </div>
            @endif

            <form
                hx-patch="{{ route('app.batches.laying-date', $batch) }}"
                hx-target="closest [role='dialog']"
                hx-swap="outerHTML"
                id="laying-date-form">
                @csrf
                @method('PATCH')

                <div class="form-group">
                    <label class="form-group__label" for="actual_laying_start_date">Laying Start Date</label>
                    <input
                        type="date"
                        id="actual_laying_start_date"
                        name="actual_laying_start_date"
                        class="form-group__input"
                        max="{{ now()->format('Y-m-d') }}"
                        value="{{ $batch->actual_laying_start_date?->format('Y-m-d') }}">
                    <p class="batches__form-hint-inline">Leave empty to clear the laying date.</p>
                </div>
            </form>
        </div>

        <div class="modal__actions">
            <button type="button" class="btn btn--secondary" @click="close()">Cancel</button>
            <button type="submit" form="laying-date-form" class="btn btn--primary">Set Date</button>
        </div>
    </div>
</div>
