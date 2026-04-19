@props([
    'id' => 'main-modal',
    'title',
    'size' => 'md',
    'closeable' => true,
])

<div id="{{ $id }}" class="modal modal--{{ $size }}" role="dialog" aria-modal="true" aria-labelledby="{{ $id }}-title"
     x-data="{ close() { document.getElementById('modal-container').innerHTML = ''; } }"
     @keydown.escape.window="close()">
    <div class="modal__overlay" @click="close()"></div>
    <div class="modal__content">
        <div class="modal__header">
            <h2 id="{{ $id }}-title" class="modal__title">{{ $title }}</h2>
            @if($closeable)
                <button @click="close()" class="modal__close" aria-label="Close">&times;</button>
            @endif
        </div>
        <div class="modal__body">
            {{ $slot }}
        </div>
    </div>
</div>
