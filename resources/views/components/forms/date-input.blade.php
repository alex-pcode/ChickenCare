@props([
    'name',
    'label' => null,
    'value' => '',
    'required' => false,
    'min' => null,
    'max' => null,
])

<div class="form-group @error($name) form-group--error @enderror">
    @if($label)
        <label for="{{ $name }}" class="form-label">
            {{ $label }}
            @if($required)<span class="form-label__required" aria-hidden="true">*</span>@endif
        </label>
    @endif

    <input
        type="date"
        id="{{ $name }}"
        name="{{ $name }}"
        value="{{ old($name, $value) }}"
        {{ $required ? 'required' : '' }}
        @if($min) min="{{ $min }}" @endif
        @if($max) max="{{ $max }}" @endif
        @error($name) aria-invalid="true" aria-describedby="{{ $name }}-error" @enderror
        {{ $attributes->merge(['class' => 'form-input']) }}
    >

    @error($name)
        <p class="form-error" id="{{ $name }}-error" role="alert">{{ $message }}</p>
    @enderror
</div>
