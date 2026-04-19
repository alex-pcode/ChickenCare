<div id="deaths-form-region"
     class="batches__deaths-form"
     x-data="{ submitting: false, success: {{ isset($successMessage) ? 'true' : 'false' }}, errors: [] }">

    {{-- Success banner --}}
    <div x-show="success"
         x-cloak
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0 -translate-y-2"
         x-transition:enter-end="opacity-100 translate-y-0"
         class="batches__form-banner batches__form-banner--success"
         role="status">
        <span aria-hidden="true">✓</span>
        <span>Loss logged successfully</span>
    </div>

    {{-- Error banner --}}
    <div x-show="errors.length > 0"
         x-cloak
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0 -translate-y-2"
         x-transition:enter-end="opacity-100 translate-y-0"
         class="batches__form-banner batches__form-banner--error"
         role="alert">
        <span aria-hidden="true">!</span>
        <div>
            <div class="batches__form-banner-title">Please fix the following errors:</div>
            <div class="batches__form-banner-body" x-text="errors.join(', ')"></div>
        </div>
    </div>

    <x-forms.form-card
        title="Log New Loss"
        icon="💀"
        action="{{ route('app.batches.deaths.store', $batch) }}"
        hx-post="{{ route('app.batches.deaths.store', $batch) }}"
        hx-target="#deaths-form-region"
        hx-swap="outerHTML"
        hx-headers='{"Accept": "application/json"}'
        hx-on::before-request="submitting = true; errors = []; success = false"
        hx-on::after-request="submitting = false; if (event.detail.successful) { success = true; $el.reset(); setTimeout(() => success = false, 4000); }"
        hx-on::response-error="try { errors = Object.values(JSON.parse(event.detail.xhr.responseText).errors).flat(); } catch(e) { errors = ['An unexpected error occurred.']; }">

        <div class="batches__form-grid">
            <div class="batches__form-field">
                <label for="date" class="form-label">Date<span class="form-label__required" aria-hidden="true">*</span></label>
                <input type="date" id="date" name="date" required
                       value="{{ now()->format('Y-m-d') }}"
                       max="{{ now()->format('Y-m-d') }}"
                       class="form-input">
            </div>

            <div class="batches__form-field">
                <label for="count" class="form-label">Number Lost<span class="form-label__required" aria-hidden="true">*</span></label>
                <input type="number" id="count" name="count" required min="1" max="{{ $batch->current_count }}"
                       placeholder="Max {{ $batch->current_count }}"
                       class="form-input">
            </div>

            <div class="batches__form-field">
                <label for="cause" class="form-label">Cause<span class="form-label__required" aria-hidden="true">*</span></label>
                <select id="cause" name="cause" required class="form-select">
                    @foreach(\App\Enums\DeathCause::cases() as $case)
                        <option value="{{ $case->value }}">{{ $case->label() }}</option>
                    @endforeach
                </select>
            </div>

            <div class="batches__form-field batches__form-field--full">
                <label for="description" class="form-label">Description<span class="form-label__required" aria-hidden="true">*</span></label>
                <input type="text" id="description" name="description" required maxlength="500"
                       placeholder="Brief description of what happened…"
                       class="form-input">
            </div>

            <div class="batches__form-field batches__form-field--full">
                <label for="notes" class="form-label">Additional Notes</label>
                <textarea id="notes" name="notes" rows="3" maxlength="2000"
                          placeholder="Vet notes, observations…"
                          class="form-textarea"></textarea>
            </div>
        </div>

        <div class="batches__form-actions">
            <button type="submit"
                    class="btn btn--primary"
                    :disabled="submitting || {{ $batch->current_count === 0 ? 'true' : 'false' }}">
                <span x-show="!submitting">Log Loss</span>
                <span x-show="submitting" x-cloak>Logging...</span>
            </button>
        </div>
    </x-forms.form-card>
</div>
