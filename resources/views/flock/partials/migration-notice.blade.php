<div class="flock__migration-notice" role="status">
    <p>You're currently tracking {{ $profile->hens + $profile->roosters + $profile->chicks }} birds individually. Batch management lets you track groups with automatic count updates and production analysis.</p>
    <a href="{{ route('app.batches.index') }}" class="btn btn--sm btn--primary">Start Using Batches</a>
</div>
