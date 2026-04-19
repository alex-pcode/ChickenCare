@extends('layouts.app')

@section('title', $batch->batch_name)

@section('content')
<div class="batches batches__detail"
     x-data="{
         toastMessage: '',
         toastType: 'success',
         showToast: false,
         displayToast(message, type) {
             this.toastMessage = message;
             this.toastType = type ?? 'success';
             this.showToast = true;
             setTimeout(() => this.showToast = false, 4000);
         }
     }"
     @flock:changed.window="displayToast(event.detail?.message ?? 'Flock updated.', event.detail?.type ?? 'success')"
     @flock:success.window="displayToast($event.detail?.value ?? $event.detail?.message ?? 'Updated.', 'success')"
     @flock:error.window="displayToast($event.detail?.value ?? $event.detail?.message ?? 'Something went wrong.', 'error')"
     @modal:close.window="document.getElementById('modal-container').innerHTML = ''">

    {{-- Modal container --}}
    <div id="modal-container"></div>

    {{-- Toast --}}
    <div class="fixed bottom-6 right-6 z-50 w-80"
         x-show="showToast"
         x-cloak
         :role="toastType === 'success' ? 'status' : 'alert'"
         aria-live="polite"
         x-transition:enter="transition ease-out duration-300 transform"
         x-transition:enter-start="opacity-0 translate-y-4"
         x-transition:enter-end="opacity-100 translate-y-0"
         x-transition:leave="transition ease-in duration-200 transform"
         x-transition:leave-start="opacity-100 translate-y-0"
         x-transition:leave-end="opacity-0 translate-y-4">
        <div :class="toastType === 'success'
                ? 'bg-green-50 dark:bg-green-900/30 border border-green-200 dark:border-green-700 text-green-800 dark:text-green-300'
                : 'bg-red-50 dark:bg-red-900/30 border border-red-200 dark:border-red-700 text-red-800 dark:text-red-300'"
             class="rounded-xl px-4 py-3 shadow-lg flex items-center gap-3">
            <span x-text="toastMessage"></span>
        </div>
    </div>

    {{-- Back + header --}}
    <div class="mb-6">
        <a href="{{ route('app.batches.index') }}"
           class="inline-flex items-center gap-2 px-4 py-3 text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg transition-colors min-h-[44px] mb-4">
            ← Back to Batches
        </a>

        <h1 class="text-xl sm:text-2xl font-bold text-gray-900 dark:text-white flex items-center gap-2 sm:gap-3 flex-wrap">
            <span class="text-xl sm:text-2xl flex-shrink-0" aria-hidden="true">
                {{ $batch->type === 'roosters' ? '🐓' : ($batch->type === 'chicks' ? '🐥' : '🐔') }}
            </span>
            <span class="break-words min-w-0">{{ $batch->batch_name }}</span>
        </h1>
        @if($batch->breed || $batch->type)
            <p class="text-sm sm:text-base text-gray-600 dark:text-gray-400 mt-1 break-words">
                {{ $batch->breed }}{{ $batch->breed && $batch->type ? ' • ' : '' }}{{ $batch->type ? \Illuminate\Support\Str::ucfirst($batch->type) : '' }}
            </p>
        @endif
    </div>

    {{-- Section: Batch Composition --}}
    <div class="mb-8">
        <div class="flex flex-col sm:flex-row sm:justify-between sm:items-center gap-4 mb-6">
            <h2 class="text-2xl font-bold text-gray-900 dark:text-white">Batch Composition</h2>
            <button
                hx-get="{{ route('app.batches.composition-modal', $batch) }}"
                hx-target="#modal-container"
                hx-swap="innerHTML"
                class="flex items-center gap-2 px-4 py-3 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg transition-colors text-sm font-medium min-h-[44px] w-full sm:w-auto justify-center sm:justify-start"
                aria-label="Edit batch composition">
                ✏️ Edit Composition
            </button>
        </div>

        <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
            <x-ui.stat-card
                title="Hens"
                :total="$batch->hens_count"
                :label="$batch->actual_laying_start_date ? 'Laying active' : 'Not laying yet'"
                icon="🐔"
                variant="default" />
            <x-ui.stat-card
                title="Roosters"
                :total="$batch->roosters_count"
                label="Male birds"
                icon="🐓"
                variant="default" />
            <x-ui.stat-card
                title="Brooding"
                :total="$batch->brooding_count"
                :label="$batch->brooding_count > 0 ? 'Currently brooding' : 'Available for breeding'"
                icon="🪺"
                variant="default" />
            <x-ui.stat-card
                title="Chicks"
                :total="$batch->chicks_count"
                :label="$batch->chicks_count > 0 ? 'Growing birds' : 'No chicks currently'"
                icon="🐥"
                variant="default" />
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-6">
            <x-ui.stat-card
                title="Batch Age"
                :total="(int) now()->diffInWeeks($batch->acquisition_date) . ' weeks'"
                :label="'Since ' . $batch->acquisition_date->format('M j, Y')"
                icon="📅"
                variant="default"
                data-testid="batch-age-metric" />
            <x-ui.stat-card
                title="Batch Cost"
                :total="$batch->cost > 0 ? '$' . number_format($batch->cost, 2) : 'Free'"
                :label="$batch->cost > 0 && $batch->initial_count > 0
                    ? '$' . number_format($batch->cost / $batch->initial_count, 2) . ' per bird'
                    : ($batch->cost > 0 ? 'No per-bird rate (initial count 0)' : 'No cost recorded')"
                icon="💰"
                variant="default"
                data-testid="batch-cost-metric" />
        </div>
    </div>

    {{-- Section: Batch Details --}}
    <div class="neu-form !px-[10px] mb-8">
        <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Batch Details</h3>
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Acquired</label>
                <p class="text-gray-900 dark:text-white">{{ $batch->acquisition_date->format('M j, Y') }}</p>
                <p class="text-xs text-gray-500 dark:text-gray-400">{{ (int) now()->diffInWeeks($batch->acquisition_date) }} weeks ago</p>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Age at Acquisition</label>
                <p class="text-gray-900 dark:text-white capitalize">{{ $batch->age_at_acquisition }}</p>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Source</label>
                <p class="text-gray-900 dark:text-white">{{ $batch->source ?? '—' }}</p>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Cost</label>
                <p class="text-gray-900 dark:text-white">
                    @if($batch->cost > 0)
                        ${{ number_format($batch->cost, 2) }}
                    @else
                        Free
                    @endif
                </p>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Started With</label>
                <p class="text-gray-900 dark:text-white">{{ $batch->initial_count }} birds</p>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Laying Status</label>
                <div class="flex items-center gap-2">
                    @if($batch->actual_laying_start_date)
                        <span class="text-green-600 dark:text-green-400">🥚 Laying</span>
                        <span class="text-xs text-gray-500 dark:text-gray-400">
                            since {{ $batch->actual_laying_start_date->format('M j, Y') }}
                        </span>
                    @else
                        <span class="text-amber-600 dark:text-amber-400">⏳ Not laying yet</span>
                    @endif
                    <button
                        hx-get="{{ route('app.batches.laying-date-modal', $batch) }}"
                        hx-target="#modal-container"
                        hx-swap="innerHTML"
                        class="text-gray-400 hover:text-indigo-600 dark:hover:text-indigo-400 transition-colors"
                        aria-label="Set laying date">
                        📅
                    </button>
                </div>
            </div>

            @if($batch->notes)
                <div class="sm:col-span-2 lg:col-span-3">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Notes</label>
                    <p class="text-gray-900 dark:text-white text-sm break-words">{{ $batch->notes }}</p>
                </div>
            @endif
        </div>
    </div>

    {{-- Section: Add Timeline Event --}}
    <div class="mb-8" x-data="{ submitting: false }">
        <div class="form-card">
            <div class="form-card__header">
                <div class="flex items-center gap-3">
                    <h2 class="form-card__title">Add Timeline Event</h2>
                </div>
                <p class="form-card__subtitle">Record important events for this batch</p>
            </div>
            <form
                class="form-card__form"
                hx-post="{{ route('app.batches.events.store', $batch) }}"
                hx-target="#batch-timeline-events"
                hx-swap="afterbegin"
                hx-on::before-request="submitting = true"
                hx-on::after-request="submitting = false; if(event.detail.successful) { $el.reset(); }">
                @csrf

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-4">
                    <div>
                        <label for="event_date" class="form-group__label">Date <span class="text-red-500">*</span></label>
                        <input
                            type="date"
                            id="event_date"
                            name="date"
                            required
                            value="{{ now()->format('Y-m-d') }}"
                            max="{{ now()->format('Y-m-d') }}"
                            class="form-group__input w-full">
                    </div>
                    <div>
                        <label for="event_type" class="form-group__label">Event Type <span class="text-red-500">*</span></label>
                        <select id="event_type" name="type" required class="form-group__input w-full">
                            <option value="health_check">🩺 Health Check</option>
                            <option value="vaccination">💉 Vaccination</option>
                            <option value="relocation">🏠 Relocation</option>
                            <option value="breeding">💕 Breeding</option>
                            <option value="laying_start">🥚 Laying Start</option>
                            <option value="brooding_start">🪺 Brooding Start</option>
                            <option value="brooding_stop">🐔 Brooding Stop</option>
                            <option value="production_note">📝 Production Note</option>
                            <option value="flock_added">🎉 Flock Added</option>
                            <option value="flock_loss">💔 Flock Loss</option>
                            <option value="other" selected>📋 Other</option>
                        </select>
                    </div>
                </div>

                <div class="mb-4">
                    <label for="event_description" class="form-group__label">Description <span class="text-red-500">*</span></label>
                    <input
                        type="text"
                        id="event_description"
                        name="description"
                        required
                        maxlength="500"
                        placeholder="Brief description of the event…"
                        class="form-group__input w-full">
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-4">
                    <div>
                        <label for="affected_count" class="form-group__label">Affected Count</label>
                        <input
                            type="number"
                            id="affected_count"
                            name="affected_count"
                            min="0"
                            placeholder="Number of birds affected"
                            class="form-group__input w-full">
                    </div>
                </div>

                <div class="mb-4">
                    <label for="event_notes" class="form-group__label">Notes</label>
                    <textarea
                        id="event_notes"
                        name="notes"
                        rows="3"
                        maxlength="2000"
                        placeholder="Additional details…"
                        class="form-group__input w-full"></textarea>
                </div>

                <div class="flex justify-center pt-4 border-t border-gray-200 dark:border-gray-700">
                    <button type="submit" class="shiny-cta" :disabled="submitting">
                        <span x-show="!submitting">Add Event</span>
                        <span x-show="submitting" x-cloak>Adding...</span>
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- Section: Timeline --}}
    <div class="neu-form !px-[10px] mb-8" id="batch-timeline">
        <h2 class="text-2xl font-bold text-gray-900 dark:text-white mb-6">Timeline</h2>

        <div class="relative">
            <div class="hidden lg:block absolute left-1/2 top-0 bottom-0 w-0.5 bg-gradient-to-b from-transparent via-gray-300 dark:via-gray-600 to-transparent" aria-hidden="true"></div>

            <div class="space-y-8" id="batch-timeline-events">
                @if($batch->batchEvents->isEmpty())
                    <div class="text-center py-8 text-gray-500 dark:text-gray-400">
                        <span class="text-3xl mb-3 block" aria-hidden="true">📅</span>
                        <p class="text-sm">No events recorded yet. Add a timeline event above to track health checks, vaccinations, and more.</p>
                    </div>
                @else
                    @foreach($batch->batchEvents as $index => $event)
                        @include('batches.partials.timeline-event-row', [
                            'event' => $event,
                            'index' => $index,
                        ])
                    @endforeach
                @endif
            </div>
        </div>
    </div>

    {{-- Section: Deaths --}}
    <div class="mb-8">
        <h2 class="text-2xl font-bold text-gray-900 dark:text-white mb-4">Deaths</h2>
        @include('batches.partials.deaths-section', ['batch' => $batch])
    </div>
</div>
@endsection
