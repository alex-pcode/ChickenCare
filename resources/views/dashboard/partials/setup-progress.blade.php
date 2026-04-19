@if($progress['percentage'] < 100)
<section class="dashboard__section dashboard__section--animate" style="animation-delay: 0.1s">
    <h2 class="dashboard__section-title">
        @if($progress['percentage'] <= 40)
            🚀 Getting Started
        @elseif($progress['percentage'] <= 70)
            📈 Building Your Farm
        @elseif($progress['percentage'] <= 90)
            ⚡ Advanced Features
        @else
            🎯 Final Steps
        @endif
    </h2>

    <div class="glass-card dashboard__setup">
        <div class="dashboard__setup-header">
            <div>
                <span class="dashboard__setup-phase">{{ $progress['phase']['label'] }}</span>
                <p class="dashboard__setup-message">{{ $progress['phase']['message'] }}</p>
            </div>
        </div>

        <x-ui.progress-card
            title="Setup Progress"
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
                        <div class="dashboard__setup-item-points">{{ $item['points'] }} pts</div>
                    </div>
                    <div class="dashboard__setup-item-status">
                        @if($item['completed'])
                            ✓
                        @else
                            <a href="{{ $item['action_href'] }}" class="btn btn--primary btn--sm">
                                {{ $item['action'] }}
                            </a>
                        @endif
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</section>
@endif
