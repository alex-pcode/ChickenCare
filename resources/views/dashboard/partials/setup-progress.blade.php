@if($progress['percentage'] < 100)
<section class="dashboard__section dashboard__section--animate" style="animation-delay: 0.1s">
    <h2 class="dashboard__section-title">
        {{ __('dashboard.setup.phase_headings.' . $progress['bracket']) }}
    </h2>

    <div class="glass-card dashboard__setup">
        <x-ui.progress-card
            :title="__('dashboard.setup.progress_title')"
            :value="$progress['percentage']"
            :max="100"
            variant="detailed"
        />

        <div class="dashboard__setup-checklist">
            @foreach($progress['items'] as $item)
                <div class="dashboard__setup-item {{ $item['completed'] ? 'dashboard__setup-item--completed' : '' }}">
                    <span class="dashboard__setup-item-icon">{{ $item['icon'] }}</span>
                    <div class="dashboard__setup-item-body">
                        <div class="dashboard__setup-item-label">{{ $item['label'] }}</div>
                        <div class="dashboard__setup-item-points">{{ $item['points'] }} {{ __('dashboard.setup.points') }}</div>
                    </div>
                    <div class="dashboard__setup-item-status">
                        @if($item['completed'])
                            ✓
                        @else
                            <a href="{{ $item['action_href'] }}" class="shiny-cta shiny-cta--sm"><span>{{ $item['action'] }}</span></a>
                        @endif
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</section>
@endif
