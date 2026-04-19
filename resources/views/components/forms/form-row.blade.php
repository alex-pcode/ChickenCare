@props(['cols' => 2])

<div class="form-row form-row--{{ $cols }}-col">
    {{ $slot }}
</div>
