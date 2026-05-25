@extends('layouts.app')

@section('title', $batch->batch_name)

@section('content')
<div class="batches batches__detail"
     @modal:close.window="document.getElementById('modal-container').innerHTML = ''">

    {{-- Header --}}
    <header class="batches__detail-header">
        <a href="{{ route('app.batches.index') }}" class="batches__back-link">← {{ __('batches.actions.back_to_batches') }}</a>

        <div class="batches__detail-header-row">
            <div class="batches__detail-identity">
                <span class="batches__detail-icon" aria-hidden="true">{{ $batch->type === 'roosters' ? '🐓' : ($batch->type === 'chicks' ? '🐥' : '🐔') }}</span>
                <div class="batches__detail-titles">
                    <h1 class="batches__detail-title">{{ $batch->batch_name }}</h1>
                    @if($batch->breed || $batch->type)
                        <p class="batches__detail-subtitle">
                            {{ $batch->breed }}{{ $batch->breed && $batch->type ? ' • ' : '' }}{{ $batch->type ? \Illuminate\Support\Str::ucfirst($batch->type) : '' }}
                        </p>
                    @endif
                </div>
            </div>

            <button type="button"
                    class="btn btn--primary btn--sm"
                    hx-get="{{ route('app.batches.composition-modal', $batch) }}"
                    hx-target="#modal-container"
                    hx-swap="innerHTML"
                    aria-label="Edit batch composition">
                ✏️ Edit Composition
            </button>
        </div>
    </header>

    {{-- Section: Batch Composition --}}
    <section class="batches__section">
        <h2 class="batches__section-title">Batch Composition</h2>

        <div class="batches__stats-grid">
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

        <div class="batches__stats-grid batches__stats-grid--2">
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
    </section>

    {{-- Section: Batch Details --}}
    <section class="batches__section">
        <h2 class="batches__section-title">Batch Details</h2>
        <div class="batches__details-card">
            <dl class="batches__details-grid">
                <div class="batches__details-field">
                    <dt class="batches__details-label">Acquired</dt>
                    <dd class="batches__details-value">{{ $batch->acquisition_date->format('M j, Y') }}</dd>
                    <p class="batches__details-hint">{{ (int) now()->diffInWeeks($batch->acquisition_date) }} weeks ago</p>
                </div>

                <div class="batches__details-field">
                    <dt class="batches__details-label">Age at Acquisition</dt>
                    <dd class="batches__details-value">{{ $batch->age_at_acquisition?->label() ?? '—' }}</dd>
                </div>

                <div class="batches__details-field">
                    <dt class="batches__details-label">Source</dt>
                    <dd class="batches__details-value">{{ $batch->source ?? '—' }}</dd>
                </div>

                <div class="batches__details-field">
                    <dt class="batches__details-label">Cost</dt>
                    <dd class="batches__details-value">
                        @if($batch->cost > 0)
                            ${{ number_format($batch->cost, 2) }}
                        @else
                            Free
                        @endif
                    </dd>
                </div>

                <div class="batches__details-field">
                    <dt class="batches__details-label">Started With</dt>
                    <dd class="batches__details-value">{{ $batch->initial_count }} birds</dd>
                </div>

                <div class="batches__details-field">
                    <dt class="batches__details-label">Laying Status</dt>
                    <dd class="batches__details-value">
                        @if($batch->actual_laying_start_date)
                            <span class="batches__laying-status batches__laying-status--laying">🥚 Laying</span>
                            <span class="batches__details-hint">since {{ $batch->actual_laying_start_date->format('M j, Y') }}</span>
                        @else
                            <span class="batches__laying-status batches__laying-status--pending">⏳ Not laying yet</span>
                        @endif
                        <button type="button"
                                class="batches__laying-date-btn"
                                hx-get="{{ route('app.batches.laying-date-modal', $batch) }}"
                                hx-target="#modal-container"
                                hx-swap="innerHTML"
                                aria-label="Set laying date">📅</button>
                    </dd>
                </div>

                @if($batch->notes)
                    <div class="batches__details-field batches__details-field--wide">
                        <dt class="batches__details-label">Notes</dt>
                        <dd class="batches__details-value">{{ $batch->notes }}</dd>
                    </div>
                @endif
            </dl>
        </div>
    </section>

    {{-- Section: Add Timeline Event --}}
    <section class="batches__section" x-data="{ submitting: false }">
        <div class="form-card">
            <div class="form-card__header">
                <h2 class="form-card__title">Add Timeline Event</h2>
                <p class="form-card__subtitle">Record important events for this batch</p>
            </div>
            <form
                class="form-card__form"
                hx-post="{{ route('app.batches.events.store', $batch) }}"
                hx-target="#batch-timeline-events"
                hx-swap="afterbegin"
                hx-on::before-request="submitting = true"
                hx-on::after-request="submitting = false; if (event.detail.successful) { window.ChickenCare.htmx.resetForm(event); }">
                @csrf

                <div class="batches__form-grid">
                    <div class="batches__form-field">
                        <label for="event_date" class="form-label">Date<span class="form-label__required" aria-hidden="true">*</span></label>
                        <input type="date" id="event_date" name="date" required
                               value="{{ now()->format('Y-m-d') }}"
                               max="{{ now()->format('Y-m-d') }}"
                               class="form-input">
                    </div>
                    <div class="batches__form-field">
                        <label for="event_type" class="form-label">Event Type<span class="form-label__required" aria-hidden="true">*</span></label>
                        <select id="event_type" name="type" required class="form-select">
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
                    <div class="batches__form-field">
                        <label for="affected_count" class="form-label">Affected Count</label>
                        <input type="number" id="affected_count" name="affected_count" min="0"
                               placeholder="Birds affected"
                               class="form-input">
                    </div>

                    <div class="batches__form-field batches__form-field--full">
                        <label for="event_description" class="form-label">Description<span class="form-label__required" aria-hidden="true">*</span></label>
                        <input type="text" id="event_description" name="description" required
                               maxlength="500"
                               placeholder="Brief description of the event…"
                               class="form-input">
                    </div>

                    <div class="batches__form-field batches__form-field--full">
                        <label for="event_notes" class="form-label">Notes</label>
                        <textarea id="event_notes" name="notes" rows="3" maxlength="2000"
                                  placeholder="Additional details…"
                                  class="form-textarea"></textarea>
                    </div>
                </div>

                <div class="batches__form-actions">
                    <button type="submit" class="btn btn--primary" :disabled="submitting">
                        <span x-show="!submitting">Add Event</span>
                        <span x-show="submitting" x-cloak>Adding...</span>
                    </button>
                </div>
            </form>
        </div>
    </section>

    {{-- Section: Timeline --}}
    <section class="batches__section" id="batch-timeline">
        <h2 class="batches__section-title">Timeline</h2>
        <div class="batches__details-card">
            <div id="batch-timeline-events">
                @if($batch->batchEvents->isEmpty())
                    <div class="batches__timeline-empty">
                        <span aria-hidden="true">📅</span>
                        No events recorded yet. Add a timeline event above to track health checks, vaccinations, and more.
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
    </section>

    {{-- Section: Deaths --}}
    <section class="batches__section">
        <h2 class="batches__section-title">Deaths</h2>
        @include('batches.partials.deaths-section', ['batch' => $batch])
    </section>
</div>
@endsection
