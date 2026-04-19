<div class="egg-counter__duplicate-confirm" role="alertdialog" aria-labelledby="duplicate-title" aria-describedby="duplicate-desc">
    <p id="duplicate-title" class="egg-counter__duplicate-confirm-title">Duplicate Entry Detected</p>
    <p id="duplicate-desc" class="egg-counter__duplicate-confirm-desc">
        An entry for {{ $existing->date->format('M d, Y') }} already exists with {{ $existing->count }} eggs. Update it?
    </p>
    <div class="egg-counter__duplicate-confirm-actions">
        <button type="button" class="btn btn--sm btn--primary"
            hx-post="{{ route('app.eggs.store') }}"
            hx-target="#egg-entries-body"
            hx-swap="afterbegin"
            hx-vals='@json(array_merge($formData, ["confirm_update" => 1]))'
            hx-on::after-request="document.getElementById('duplicate-confirm-area').innerHTML = ''">
            Update Existing
        </button>
        <button type="button" class="btn btn--sm btn--secondary"
            onclick="document.getElementById('duplicate-confirm-area').innerHTML = ''">
            Cancel
        </button>
    </div>
</div>
