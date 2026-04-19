<div class="space-y-6">
    @include('batches.partials.deaths-form', ['batch' => $batch])

    <div id="deaths-history-region-wrapper"
         hx-get="{{ route('app.batches.deaths.index', $batch) }}"
         hx-trigger="load"
         hx-swap="outerHTML">
        <div class="flex items-center justify-center py-8">
            <span class="text-gray-400 text-sm">Loading history...</span>
        </div>
    </div>
</div>
