@props([
    'title',
    'description' => '',
    'icon' => null,
    'action' => null,
    'actionLabel' => 'Get Started',
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
        <a href="{{ $action }}" class="btn btn--primary">{{ $actionLabel }}</a>
    @endif
</div>
