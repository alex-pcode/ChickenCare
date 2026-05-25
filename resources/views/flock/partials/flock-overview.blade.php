<div class="flock__overview">
    @if($batches->isNotEmpty())
        @include('flock.partials.overview-stats', ['overviewStats' => $overviewStats])
    @elseif(isset($profile) && ($profile->hens + $profile->roosters + $profile->chicks) > 0)
        @include('flock.partials.migration-notice', ['profile' => $profile])
    @else
        @include('flock.partials.batch-promo')
    @endif
</div>
