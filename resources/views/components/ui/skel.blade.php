@props([
    'block' => 'body',
    'width' => null,
    'height' => null,
])

@php
    $style = '';
    if ($width !== null) {
        $style .= 'width:' . $width . ';';
    }
    if ($height !== null) {
        $style .= 'height:' . $height . ';';
    }
@endphp

<span
    {{ $attributes->class(['skel', 'skel--' . $block]) }}
    @if ($style) style="{{ $style }}" @endif
    aria-hidden="true"
></span>
