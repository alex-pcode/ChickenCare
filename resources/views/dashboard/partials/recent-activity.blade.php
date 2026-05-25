@php($loading = $loading ?? false)
@if($loading)
    <ul class="dashboard__activity-list" role="list" aria-busy="true">
        @for ($i = 0; $i < 6; $i++)
            <li class="dashboard__activity-item">
                <span class="dashboard__activity-badge"><x-ui.skel block="pill" /></span>
                <span class="dashboard__activity-description"><x-ui.skel block="body-wide" /></span>
                <time class="dashboard__activity-date"><x-ui.skel block="label" /></time>
            </li>
        @endfor
    </ul>
@elseif($recentActivity->isEmpty())
    <x-ui.empty-state
        :title="__('dashboard.recent_activity.empty_title')"
        :description="__('dashboard.recent_activity.empty_description')"
        icon="📋"
    />
@else
    <ul class="dashboard__activity-list" role="list">
        @foreach($recentActivity as $item)
            <li class="dashboard__activity-item dashboard__activity-item--{{ $item['type'] }}"
                aria-label="{{ $item['date']->translatedFormat('d. M Y.') }}: {{ $item['description'] }}">
                <span class="dashboard__activity-badge dashboard__activity-badge--{{ $item['type'] }}">
                    @if($item['type'] === 'egg')
                        {{ __('dashboard.recent_activity.types.egg') }}
                    @elseif($item['type'] === 'sale')
                        {{ __('dashboard.recent_activity.types.sale') }}
                    @else
                        {{ __('dashboard.recent_activity.types.event') }}
                    @endif
                </span>
                <span class="dashboard__activity-description">{{ $item['description'] }}</span>
                <time class="dashboard__activity-date" datetime="{{ $item['date']->toDateString() }}">
                    {{ $item['date']->translatedFormat('d. M Y.') }}
                </time>
            </li>
        @endforeach
    </ul>
@endif
