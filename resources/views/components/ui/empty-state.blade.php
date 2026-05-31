@props([
    'title',
    'description' => '',
    'icon' => null,
    'action' => null,
    'actionLabel' => __('ui.empty_state.action_label'),
])

<div class="empty-state" role="status">
    @if($icon)
        <div class="empty-state__icon" aria-hidden="true">{{ $icon }}</div>
    @endif
    <h3 class="empty-state__title">{{ $title }}</h3>
    @if($description)
        <p class="empty-state__description">{{ $description }}</p>
    @endif
    @if($action)
        {{-- Reset hx-target/hx-swap so a boosted nav isn't hijacked by an
             ancestor region's htmx targeting (e.g. a list region the empty
             state is rendered inside). Mirrors the default hx-boost behavior. --}}
        <a href="{{ $action }}" class="shiny-cta" hx-target="body" hx-swap="innerHTML">
            <span>{{ $actionLabel }}</span>
        </a>
    @endif
    @if(trim($slot) !== '')
        {{-- Optional extra actions (e.g. an htmx modal trigger) --}}
        {{ $slot }}
    @endif
</div>
