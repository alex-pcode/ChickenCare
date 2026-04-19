@php
    $causeLabels = [
        'predator' => 'Predator',
        'disease' => 'Disease',
        'age' => 'Natural/Age',
        'injury' => 'Injury',
        'unknown' => 'Unknown',
        'culled' => 'Culled',
        'other' => 'Other',
    ];
@endphp

<div class="batches__death-row" id="death-{{ $death->id }}">
    <div class="batches__death-row-content">
        <div class="batches__death-row-header">
            <span class="batches__death-count">{{ $death->count }} {{ Str::plural('bird', $death->count) }}</span>
            <span class="batches__death-cause batches__death-cause--{{ $death->cause }}">{{ $causeLabels[$death->cause] ?? ucfirst($death->cause) }}</span>
            <span class="batches__death-date">{{ $death->date->format('M d, Y') }}</span>
        </div>
        <p class="batches__death-description">{{ $death->description }}</p>
        @if($death->notes)
            <p class="batches__death-notes">{{ Str::limit($death->notes, 100) }}</p>
        @endif
    </div>
    <div class="batches__death-row-actions">
        <button class="btn btn--sm btn--outline"
                hx-get="{{ route('app.batches.deaths.edit', [$batch, $death]) }}"
                hx-target="#death-form-area"
                hx-swap="innerHTML"
                aria-label="Edit death record: {{ $death->description }}">
            Edit
        </button>
        <button class="btn btn--sm btn--outline"
                hx-delete="{{ route('app.batches.deaths.destroy', [$batch, $death]) }}"
                hx-target="#death-{{ $death->id }}"
                hx-swap="outerHTML swap:500ms"
                hx-confirm="Delete this death record? The bird count will be restored."
                aria-label="Delete death record: {{ $death->description }}">
            Delete
        </button>
    </div>
</div>
