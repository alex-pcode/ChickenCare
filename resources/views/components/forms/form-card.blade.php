@props([
    'title' => null,
    'subtitle' => null,
    'icon' => null,
    'action',
    'method' => 'POST',
])

<div class="form-card" {{ $attributes }}>
    @if($title)
        <div class="form-card__header">
            <div class="flex items-center gap-3">
                @if($icon)
                    <span class="text-2xl">{{ $icon }}</span>
                @endif
                <h2 class="form-card__title">{{ $title }}</h2>
            </div>
            @if($subtitle)
                <p class="form-card__subtitle">{{ $subtitle }}</p>
            @endif
        </div>
    @endif
    <form action="{{ $action }}" method="{{ in_array(strtoupper($method), ['GET', 'POST']) ? $method : 'POST' }}" {{ $attributes->merge(['class' => 'form-card__form']) }}>
        @csrf
        @if(!in_array(strtoupper($method), ['GET', 'POST']))
            @method($method)
        @endif
        {{ $slot }}
    </form>
</div>
