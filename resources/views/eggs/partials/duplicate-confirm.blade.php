<div class="egg-counter__duplicate-confirm" role="alertdialog" aria-labelledby="duplicate-title" aria-describedby="duplicate-desc">
    <p id="duplicate-title" class="egg-counter__duplicate-confirm-title">{{ __('eggs.duplicate.title') }}</p>
    <p id="duplicate-desc" class="egg-counter__duplicate-confirm-desc">
        {{ __('eggs.duplicate.message', ['date' => $existing->date->format('M d, Y'), 'count' => $existing->count]) }}
    </p>
    <div class="egg-counter__duplicate-confirm-actions">
        <button type="button" class="btn btn--sm btn--primary"
            hx-post="{{ route('app.eggs.store') }}"
            hx-target="#egg-entries-body"
            hx-swap="afterbegin"
            hx-vals='@json(array_merge($formData, ["confirm_update" => 1]))'
            hx-on::after-request="document.getElementById('duplicate-confirm-area').innerHTML = ''">
            {{ __('eggs.duplicate.confirm') }}
        </button>
        <button type="button" class="btn btn--sm btn--secondary"
            onclick="document.getElementById('duplicate-confirm-area').innerHTML = ''">
            {{ __('eggs.duplicate.cancel') }}
        </button>
    </div>
</div>
