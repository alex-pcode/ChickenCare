@props(['label' => 'Save', 'variant' => 'primary'])

<button type="submit" {{ $attributes->merge(['class' => "btn btn--{$variant} btn--full"]) }}>
    {{ $label }}
</button>
