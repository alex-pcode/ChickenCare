@foreach (['success', 'error', 'warning'] as $type)
    @if (session($type))
        <div class="flash flash--{{ $type }}"
             role="alert"
             aria-live="{{ $type === 'error' ? 'assertive' : 'polite' }}"
             x-data="{ show: true }"
             x-show="show"
             x-init="setTimeout(() => show = false, 5000)">
            {{ session($type) }}
        </div>
    @endif
@endforeach
