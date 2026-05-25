@php
    if ($daysSinceLastPurchase === null) {
        $heroStatus = 'neutral';
    } elseif ($daysSinceLastPurchase <= 7) {
        $heroStatus = 'success';
    } else {
        $heroStatus = 'warning';
    }
@endphp

<div class="feed-hero feed-hero--{{ $heroStatus }}">
    <div class="feed-hero__corner-badge" aria-hidden="true">
        <span class="feed-hero__corner-badge-icon feed-hero__corner-badge-icon--{{ $heroStatus }}">
            @if($heroStatus === 'success') ✅ @elseif($heroStatus === 'warning') ⚠️ @else 🌾 @endif
        </span>
    </div>
    <div class="feed-hero__media">
        <img
            src="/images/cute-chicken-having-dinner.webp"
            alt="{{ __('feed.hero.image_alt') }}"
            class="feed-hero__image feed-hero__image--animated"
        >
    </div>

    <div class="feed-hero__side">
        <div class="feed-hero__status feed-hero__status--{{ $heroStatus }}" role="status">
            <div class="feed-hero__status-text">
                @if($daysSinceLastPurchase === null)
                    <h2 class="feed-hero__status-title">
                        <span class="d-none-mobile">{{ __('feed.hero.status.none_yet_title') }}</span>
                        <span class="d-only-mobile">{{ __('feed.hero.status.none_yet_short') }}</span>
                    </h2>
                    <p class="feed-hero__status-detail d-none-mobile">{{ __('feed.hero.status.none_yet_detail') }}</p>
                @else
                    <h2 class="feed-hero__status-title">
                        <span class="d-none-mobile">{{ trans_choice('feed.hero.status.days_ago_title', $daysSinceLastPurchase, ['days' => $daysSinceLastPurchase]) }}</span>
                        <span class="d-only-mobile">{{ trans_choice('feed.hero.status.days_ago_short', $daysSinceLastPurchase, ['days' => $daysSinceLastPurchase]) }}</span>
                    </h2>
                    <p class="feed-hero__status-detail d-none-mobile">{{ __('feed.hero.status.days_ago_detail', ['date' => $lastPurchaseDate->format('M j, Y')]) }}</p>
                @endif
            </div>
        </div>
    </div>
</div>
