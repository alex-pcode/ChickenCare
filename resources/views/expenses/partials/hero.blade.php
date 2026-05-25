@php
    $thisMonth = $stats['monthOverMonth']['thisMonthTotal'] ?? 0;
    $lastMonth = $stats['monthOverMonth']['previousMonthTotal'] ?? 0;

    if ($thisMonth == 0) {
        $heroStatus = 'neutral';
    } elseif ($thisMonth <= $lastMonth || $lastMonth == 0) {
        $heroStatus = 'success';
    } else {
        $heroStatus = 'warning';
    }
@endphp

<div class="expenses-hero expenses-hero--{{ $heroStatus }}">
    <div class="expenses-hero__corner-badge" aria-hidden="true">
        <span class="expenses-hero__corner-badge-icon expenses-hero__corner-badge-icon--{{ $heroStatus }}">
            @if($heroStatus === 'success') ✅ @elseif($heroStatus === 'warning') ⚠️ @else 💸 @endif
        </span>
    </div>
    <div class="expenses-hero__media">
        <img src="/images/chicken-coin.webp"
             alt="{{ __('expenses.hero.image_alt') }}"
             class="expenses-hero__image expenses-hero__image--animated">
    </div>

    <div class="expenses-hero__side">
        <div class="expenses-hero__status expenses-hero__status--{{ $heroStatus }}" role="status">
            <div class="expenses-hero__status-text">
                @if($thisMonth == 0)
                    <h2 class="expenses-hero__status-title">
                        <span class="d-none-mobile">{{ __('expenses.hero.status.no_expenses_title') }}</span>
                        <span class="d-only-mobile">{{ __('expenses.hero.status.no_expenses_short') }}</span>
                    </h2>
                    <p class="expenses-hero__status-detail d-none-mobile">{{ __('expenses.hero.status.no_expenses_detail') }}</p>
                @elseif($heroStatus === 'success')
                    <h2 class="expenses-hero__status-title">
                        <span class="d-none-mobile">{{ __('expenses.hero.status.down_title') }}</span>
                        <span class="d-only-mobile">{{ __('expenses.hero.status.down_short', ['amount' => number_format($thisMonth, 2)]) }}</span>
                    </h2>
                    <p class="expenses-hero__status-detail d-none-mobile">
                        {{ __('expenses.hero.status.down_detail', ['amount' => number_format($thisMonth, 2)]) }}
                    </p>
                @else
                    <h2 class="expenses-hero__status-title">
                        <span class="d-none-mobile">{{ __('expenses.hero.status.up_title') }}</span>
                        <span class="d-only-mobile">{{ __('expenses.hero.status.up_short', ['amount' => number_format($thisMonth, 2)]) }}</span>
                    </h2>
                    <p class="expenses-hero__status-detail d-none-mobile">
                        {{ __('expenses.hero.status.up_detail', ['amount' => number_format($thisMonth, 2)]) }}
                    </p>
                @endif
            </div>
        </div>

        <x-ui.comparison-card
            :title="__('expenses.hero.comparison.title')"
            format="currency"
            semantic="inverse"
            :before="['value' => $lastMonth, 'label' => __('expenses.hero.comparison.last_month')]"
            :after="['value' => $thisMonth, 'label' => __('expenses.hero.comparison.this_month')]"
        />
    </div>
</div>
