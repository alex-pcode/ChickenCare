@props(['label' => null, 'name' => null, 'required' => false])

<div class="form-group @if($name) @error($name) form-group--error @enderror @endif">
    @if($label)
        <label @if($name) for="{{ $name }}" @endif class="form-label">
            {{ $label }}
            @if($required)<span class="form-label__required" aria-hidden="true">*</span>@endif
        </label>
    @endif
    {{ $slot }}
    @if($name)
        @error($name)
            <p class="form-error" id="{{ $name }}-error" role="alert">{{ $message }}</p>
        @enderror
    @endif
</div>
