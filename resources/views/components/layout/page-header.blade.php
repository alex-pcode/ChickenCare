@props(['title', 'subtitle' => null])

<div class="page-header">
    <div class="page-header__content">
        <h1 class="page-header__title">{{ $title }}</h1>
        @if($subtitle)
            <p class="page-header__subtitle">{{ $subtitle }}</p>
        @endif
    </div>
    @if(isset($actions))
        <div class="page-header__actions">{{ $actions }}</div>
    @endif
</div>
