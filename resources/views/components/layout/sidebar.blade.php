@php
    $menuLinks = [
        ['route' => 'app.dashboard', 'pattern' => 'app.dashboard', 'label' => 'Dashboard', 'emoji' => '🏠'],
        ['route' => 'app.eggs.index', 'pattern' => 'app.eggs.*', 'label' => 'Eggs', 'emoji' => '🥚'],
        ['route' => 'app.account.index', 'pattern' => 'app.account.*', 'label' => 'Account', 'emoji' => '⚙️'],
    ];

    $premiumLinks = [
        ['route' => 'app.flock.index', 'pattern' => 'app.flock.*', 'label' => 'My Flock', 'emoji' => '🐔'],
        ['route' => 'app.batches.index', 'pattern' => 'app.batches.*', 'label' => 'Batches', 'emoji' => '📦'],
        ['route' => 'app.sales.index', 'pattern' => 'app.sales.*', 'label' => 'Sales', 'emoji' => '💼'],
        ['route' => 'app.customers.index', 'pattern' => 'app.customers.*', 'label' => 'Customers', 'emoji' => '👥'],
        ['route' => 'app.expenses.index', 'pattern' => 'app.expenses.*', 'label' => 'Expenses', 'emoji' => '💰'],
        ['route' => 'app.feed.index', 'pattern' => 'app.feed.*', 'label' => 'Feed', 'emoji' => '🌾'],
        ['route' => 'app.savings.index', 'pattern' => 'app.savings.*', 'label' => 'Savings', 'emoji' => '📈'],
        ['route' => 'app.viability.index', 'pattern' => 'app.viability.*', 'label' => 'Viability', 'emoji' => '🧮'],
    ];
@endphp

<aside class="sidebar" x-data="{ open: false }" :class="{ 'sidebar--open': open }"
       @toggle-sidebar.window="open = !open" @keydown.escape.window="open = false">

    <div class="sidebar-overlay" :class="{ 'sidebar-overlay--visible': open }" @click="open = false"></div>

    <div class="sidebar__brand">
        <span class="sidebar__brand-emoji" role="img" aria-label="ChickenCare">🐔</span>
        <div>
            <h1 class="sidebar__brand-title">ChickenCare</h1>
            <p class="sidebar__brand-subtitle">Egg-ceptional flock management</p>
        </div>
    </div>

    <nav class="sidebar__nav" role="navigation" aria-label="Main navigation">
        {{-- Free tier — always visible --}}
        <span class="sidebar__section-label">Menu</span>

        @foreach($menuLinks as $link)
            @if(Route::has($link['route']))
            <a href="{{ route($link['route']) }}"
               class="sidebar__link {{ request()->routeIs($link['pattern']) ? 'sidebar__link--active' : '' }}"
               @if(request()->routeIs($link['pattern'])) aria-current="page" @endif>
                <span class="sidebar__emoji" role="img" aria-hidden="true">{{ $link['emoji'] }}</span>
                {{ $link['label'] }}
            </a>
            @else
            <span class="sidebar__link" aria-disabled="true">
                <span class="sidebar__emoji" role="img" aria-hidden="true">{{ $link['emoji'] }}</span>
                {{ $link['label'] }}
            </span>
            @endif
        @endforeach

        {{-- Premium tier — conditionally rendered --}}
        @if(auth()->user()->isPremium())
        <span class="sidebar__section-label">Premium</span>

        @foreach($premiumLinks as $link)
            @if(Route::has($link['route']))
            <a href="{{ route($link['route']) }}"
               class="sidebar__link {{ request()->routeIs($link['pattern']) ? 'sidebar__link--active' : '' }}"
               @if(request()->routeIs($link['pattern'])) aria-current="page" @endif>
                <span class="sidebar__emoji" role="img" aria-hidden="true">{{ $link['emoji'] }}</span>
                {{ $link['label'] }}
            </a>
            @else
            <span class="sidebar__link" aria-disabled="true">
                <span class="sidebar__emoji" role="img" aria-hidden="true">{{ $link['emoji'] }}</span>
                {{ $link['label'] }}
            </span>
            @endif
        @endforeach
        @endif
    </nav>

    <div class="sidebar__footer">
        <div class="sidebar__user-card">
            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true">
                <path d="M20 21v-2a4 4 0 00-4-4H8a4 4 0 00-4 4v2"/>
                <circle cx="12" cy="7" r="4"/>
            </svg>
            <div>
                <span class="sidebar__user-email">{{ Str::limit(auth()->user()->email, 20) }}</span>
                <span class="sidebar__user-greeting">Welcome back!</span>
            </div>
        </div>

        <div class="sidebar__theme">
            <span class="sidebar__section-label" style="padding-left: 0;">Theme</span>
            <x-ui.theme-toggle />
        </div>

        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit" class="sidebar__logout-btn">
                Logout ({{ Str::before(auth()->user()->email, '@') }})
            </button>
        </form>
    </div>
</aside>
