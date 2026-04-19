@props(['title' => null, 'subtitle' => null, 'noPadding' => false])

<section class="section">
    @if($title)
        <div class="section__header">
            <h2 class="section__title">{{ $title }}</h2>
            @if($subtitle)
                <p class="section__subtitle">{{ $subtitle }}</p>
            @endif
        </div>
    @endif
    <div class="section__body {{ $noPadding ? 'section__body--no-padding' : '' }}">
        {{ $slot }}
    </div>
</section>
