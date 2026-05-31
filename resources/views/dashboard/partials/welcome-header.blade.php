@php($skel = $skel ?? false)

<div class="dashboard-hero">
    @if (! $skel)
        <div class="dashboard-hero__corner-badge" aria-hidden="true">
            <span class="dashboard-hero__corner-badge-icon">👋</span>
        </div>
    @endif
    <div class="dashboard-hero__media">
        @if ($skel)
            <x-ui.skel block="block" style="width:100%;height:14rem;border-radius:1rem;" />
        @else
            <img
                src="/images/cute-chicken-waving-.webp"
                alt="{{ __('dashboard.welcome.heading', ['name' => $displayName]) }}"
                class="dashboard-hero__image dashboard-hero__image--animated"
            >
        @endif
    </div>

    <div class="dashboard-hero__side">
        <div class="dashboard-hero__status" role="status">
            <div class="dashboard-hero__status-text">
                @if ($skel)
                    <h1 class="dashboard-hero__status-title"><x-ui.skel block="hero" /></h1>
                @else
                    @php($percentage = $progress['percentage'] ?? 0)
                    @php($welcomeKey = $percentage >= 100 ? 'complete' : ($percentage > 0 ? 'progress' : 'start'))
                    <h1 class="dashboard-hero__status-title gradient-text">{{ __('dashboard.welcome.heading', ['name' => $displayName]) }}</h1>
                    <p class="dashboard-hero__status-message">{{ __('dashboard.welcome.messages.' . $welcomeKey, ['percentage' => $percentage]) }}</p>
                @endif
            </div>
        </div>

        @if (! $skel && $recentActivity->isNotEmpty())
            <div class="dashboard-hero__activity"
                 x-data="{
                    page: 0,
                    perPage: 3,
                    items: {{ Js::from($recentActivity->map(fn($item) => [
                        'type' => $item['type'],
                        'description' => $item['description'],
                        'date' => $item['date']->translatedFormat('d. M Y.'),
                    ])) }},
                    get totalPages() { return Math.ceil(this.items.length / this.perPage); },
                    get visible() { return this.items.slice(this.page * this.perPage, (this.page + 1) * this.perPage); },
                    prev() { if (this.page > 0) this.page--; },
                    next() { if (this.page < this.totalPages - 1) this.page++; },
                 }">
                <div class="dashboard__activity-header">
                    <h2 class="dashboard__section-title">{{ __('dashboard.recent_activity.heading') }}</h2>
                    <div class="dashboard-hero__activity-nav">
                        <button class="dashboard-hero__activity-arrow" :disabled="page === 0" @click="prev()" aria-label="Previous">&#8249;</button>
                        <span class="dashboard-hero__activity-page" x-text="(page + 1) + ' / ' + totalPages"></span>
                        <button class="dashboard-hero__activity-arrow" :disabled="page >= totalPages - 1" @click="next()" aria-label="Next">&#8250;</button>
                    </div>
                </div>
                <ul class="dashboard__activity-list" role="list">
                    <template x-for="(item, i) in visible" :key="page + '-' + i">
                        <li class="dashboard__activity-item">
                            <span class="dashboard__activity-badge" x-text="item.type"></span>
                            <span class="dashboard__activity-description" x-text="item.description"></span>
                            <time class="dashboard__activity-date" x-text="item.date"></time>
                        </li>
                    </template>
                </ul>
            </div>
        @endif
    </div>
</div>
