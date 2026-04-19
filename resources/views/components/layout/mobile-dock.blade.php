@php
    $primaryLinks = [
        ['route' => 'app.dashboard', 'pattern' => 'app.dashboard', 'label' => 'Dashboard', 'emoji' => '🏠'],
        ['route' => 'app.eggs.index', 'pattern' => 'app.eggs.*', 'label' => 'Eggs', 'emoji' => '🥚'],
    ];

    $premiumPrimaryLinks = [
        ['route' => 'app.sales.index', 'pattern' => 'app.sales.*', 'label' => 'Sales', 'emoji' => '💼'],
        ['route' => 'app.expenses.index', 'pattern' => 'app.expenses.*', 'label' => 'Expenses', 'emoji' => '💰'],
    ];

    $secondaryLinks = [
        ['route' => 'app.flock.index', 'pattern' => 'app.flock.*', 'label' => 'My Flock', 'emoji' => '🐔'],
        ['route' => 'app.batches.index', 'pattern' => 'app.batches.*', 'label' => 'Batches', 'emoji' => '📦'],
        ['route' => 'app.customers.index', 'pattern' => 'app.customers.*', 'label' => 'Customers', 'emoji' => '👥'],
        ['route' => 'app.feed.index', 'pattern' => 'app.feed.*', 'label' => 'Feed', 'emoji' => '🌾'],
        ['route' => 'app.savings.index', 'pattern' => 'app.savings.*', 'label' => 'Savings', 'emoji' => '📈'],
        ['route' => 'app.viability.index', 'pattern' => 'app.viability.*', 'label' => 'Viability', 'emoji' => '🧮'],
        ['route' => 'app.account.index', 'pattern' => 'app.account.*', 'label' => 'Account', 'emoji' => '⚙️'],
    ];

    $isPremium = auth()->user()->isPremium();
    $dockLinks = $isPremium ? array_merge($primaryLinks, $premiumPrimaryLinks) : $primaryLinks;
    $hasSecondary = $isPremium && count($secondaryLinks) > 0;
@endphp

<nav class="mobile-dock" x-data="{ moreOpen: false }" role="navigation" aria-label="Mobile navigation">
    <div class="mobile-dock__bar">
        @foreach($dockLinks as $link)
            @if(Route::has($link['route']))
            <a href="{{ route($link['route']) }}"
               class="mobile-dock__item {{ request()->routeIs($link['pattern']) ? 'mobile-dock__item--active' : '' }}"
               @if(request()->routeIs($link['pattern'])) aria-current="page" @endif>
                <span class="mobile-dock__emoji" role="img" aria-hidden="true">{{ $link['emoji'] }}</span>
                <span class="mobile-dock__label">{{ $link['label'] }}</span>
            </a>
            @endif
        @endforeach

        @if($hasSecondary)
        <button @click="moreOpen = true" class="mobile-dock__item" aria-label="More options">
            <span class="mobile-dock__emoji" role="img" aria-hidden="true">⋯</span>
            <span class="mobile-dock__label">More</span>
        </button>
        @endif
    </div>

    {{-- Overflow sheet --}}
    @if($hasSecondary)
    <template x-teleport="body">
        <div class="mobile-dock-sheet" x-show="moreOpen" x-cloak
             @keydown.escape.window="moreOpen = false">
            <div class="mobile-dock-sheet__overlay" @click="moreOpen = false"
                 x-show="moreOpen" x-transition:enter="transition ease-out duration-200"
                 x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
                 x-transition:leave="transition ease-in duration-150"
                 x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"></div>

            <div class="mobile-dock-sheet__panel"
                 x-show="moreOpen"
                 x-transition:enter="transition ease-out duration-200"
                 x-transition:enter-start="translate-y-full" x-transition:enter-end="translate-y-0"
                 x-transition:leave="transition ease-in duration-150"
                 x-transition:leave-start="translate-y-0" x-transition:leave-end="translate-y-full">
                <div class="mobile-dock-sheet__header">
                    <h3 class="mobile-dock-sheet__title">More Options</h3>
                    <button @click="moreOpen = false" class="mobile-dock-sheet__close" aria-label="Close menu">
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                            <line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/>
                        </svg>
                    </button>
                </div>
                <div class="mobile-dock-sheet__grid">
                    @foreach($secondaryLinks as $link)
                        @if(Route::has($link['route']))
                        <a href="{{ route($link['route']) }}"
                           class="mobile-dock-sheet__item {{ request()->routeIs($link['pattern']) ? 'mobile-dock-sheet__item--active' : '' }}"
                           @if(request()->routeIs($link['pattern'])) aria-current="page" @endif>
                            <span class="mobile-dock-sheet__emoji" role="img" aria-hidden="true">{{ $link['emoji'] }}</span>
                            <span>{{ $link['label'] }}</span>
                        </a>
                        @endif
                    @endforeach
                </div>
            </div>
        </div>
    </template>
    @endif
</nav>
