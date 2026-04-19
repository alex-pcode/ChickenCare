<div id="deaths-form-region"
     class="batches__deaths-form"
     x-data="{ submitting: false, success: {{ isset($successMessage) ? 'true' : 'false' }}, errors: [] }">

    {{-- Success banner --}}
    <div x-show="success"
         x-cloak
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0 -translate-y-2"
         x-transition:enter-end="opacity-100 translate-y-0"
         class="bg-green-50 dark:bg-green-900/30 border border-green-200 dark:border-green-700 text-green-800 dark:text-green-300 px-4 py-3 rounded-lg mb-4 flex items-center gap-2"
         role="status">
        <svg class="h-5 w-5 text-green-400 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
        </svg>
        <div class="font-medium">Loss logged successfully</div>
    </div>

    {{-- Error banner --}}
    <div x-show="errors.length > 0"
         x-cloak
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0 -translate-y-2"
         x-transition:enter-end="opacity-100 translate-y-0"
         class="bg-red-50 dark:bg-red-900/30 border border-red-200 dark:border-red-700 text-red-800 dark:text-red-300 px-4 py-3 rounded-lg mb-4 flex items-start gap-2"
         role="alert">
        <svg class="h-5 w-5 text-red-400 flex-shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z" />
        </svg>
        <div>
            <div class="font-medium">Please fix the following errors:</div>
            <div class="mt-1 text-sm" x-text="errors.join(', ')"></div>
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

        {{-- Row: Date | Number Lost --}}
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
            <div>
                <label for="date" class="form-group__label">Date <span class="text-red-500">*</span></label>
                <input type="date" id="date" name="date" required
                       value="{{ now()->format('Y-m-d') }}"
                       max="{{ now()->format('Y-m-d') }}"
                       class="form-group__input w-full">
            </div>
            <div>
                <label for="count" class="form-group__label">Number Lost <span class="text-red-500">*</span></label>
                <input type="number" id="count" name="count" required min="1" max="{{ $batch->current_count }}"
                       placeholder="Number of birds (max {{ $batch->current_count }})"
                       class="form-group__input w-full">
            </div>
        </div>

        {{-- Row: Cause | Description --}}
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
            <div>
                <label for="cause" class="form-group__label">Cause <span class="text-red-500">*</span></label>
                <select id="cause" name="cause" required class="form-group__input w-full">
                    @foreach(\App\Enums\DeathCause::cases() as $case)
                        <option value="{{ $case->value }}">{{ $case->label() }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label for="description" class="form-group__label">Description <span class="text-red-500">*</span></label>
                <input type="text" id="description" name="description" required maxlength="500"
                       placeholder="Brief description of what happened..."
                       class="form-group__input w-full">
            </div>
        </div>

        {{-- Notes --}}
        <div class="mb-4">
            <label for="notes" class="form-group__label">Additional Notes</label>
            <textarea id="notes" name="notes" rows="3" maxlength="2000"
                      placeholder="Additional details, vet notes, observations..."
                      class="form-group__input w-full"></textarea>
        </div>

        {{-- Submit --}}
        <div class="flex justify-center pt-4 border-t border-gray-200 dark:border-gray-700">
            <button type="submit"
                    class="shiny-cta"
                    :disabled="submitting || {{ $batch->current_count === 0 ? 'true' : 'false' }}">
                <span x-show="!submitting">Log Loss</span>
                <span x-show="submitting" x-cloak>Logging...</span>
            </button>
        </div>
    </x-forms.form-card>
</div>
