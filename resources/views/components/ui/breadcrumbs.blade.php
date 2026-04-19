@props(['items' => []])

<nav class="breadcrumbs" aria-label="Breadcrumb">
    <ol class="breadcrumbs__list">
        @foreach($items as $item)
            <li class="breadcrumbs__item">
                @if(!($item['current'] ?? false) && isset($item['href']))
                    <a href="{{ $item['href'] }}" class="breadcrumbs__link">{{ $item['label'] }}</a>
                @else
                    <span class="breadcrumbs__current" aria-current="page">{{ $item['label'] }}</span>
                @endif
                @if(!$loop->last)
                    <span class="breadcrumbs__separator" aria-hidden="true">›</span>
                @endif
            </li>
        @endforeach
    </ol>
</nav>
