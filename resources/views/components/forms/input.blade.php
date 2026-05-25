@props([
    'name',
    'label' => null,
    'type' => 'text',
    'value' => '',
    'required' => false,
    'placeholder' => '',
    'loading' => false,
])

<div class="form-group @error($name) form-group--error @enderror">
    @if($label)
        <label for="{{ $name }}" class="form-label">
            @if ($loading)
                <x-ui.skel block="label" />
            @else
                {{ $label }}
                @if($required)<span class="form-label__required" aria-hidden="true">*</span>@endif
            @endif
        </label>
    @endif

    @if ($loading)
        <x-ui.skel block="input" />
    @else
        <input
            type="{{ $type }}"
            id="{{ $name }}"
            name="{{ $name }}"
            value="{{ old($name, $value) }}"
            placeholder="{{ $placeholder }}"
            {{ $required ? 'required' : '' }}
            @error($name) aria-invalid="true" aria-describedby="{{ $name }}-error" @enderror
            {{ $attributes->merge(['class' => 'form-input']) }}
        >

        @error($name)
            <p class="form-error" id="{{ $name }}-error" role="alert">{{ $message }}</p>
        @enderror
    @endif
</div>
