<div class="flock__overview">
    @if($batches->isNotEmpty())
        @include('flock.partials.overview-stats', ['overviewStats' => $overviewStats])

        <div class="flock__overview-actions">
            <a href="{{ route('app.batches.index') }}" class="btn btn--sm btn--secondary">Manage Batches</a>
        </div>
    @elseif(isset($profile) && ($profile->hens + $profile->roosters + $profile->chicks) > 0)
        @include('flock.partials.migration-notice', ['profile' => $profile])
    @else
        @include('flock.partials.batch-promo')
    @endif
</div>
