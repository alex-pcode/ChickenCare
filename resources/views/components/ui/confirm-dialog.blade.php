@props([
    'id',
    'title' => __('ui.confirm_dialog.title'),
    'message' => __('ui.confirm_dialog.message'),
    'variant' => 'warning',
    'confirmText' => __('ui.confirm_dialog.confirm'),
    'cancelText' => __('ui.confirm_dialog.cancel'),
])

<div x-data="{ open: false }"
     x-on:open-{{ $id }}.window="open = true"
     x-on:keydown.escape.window="open = false"
     x-cloak>
    <template x-teleport="body">
        <div x-show="open" class="confirm-dialog" role="dialog" aria-modal="true" aria-labelledby="{{ $id }}-title">
            <div class="confirm-dialog__overlay" x-show="open" x-transition.opacity @click="open = false"></div>
            <div class="confirm-dialog__panel confirm-dialog__panel--{{ $variant }}"
                 x-show="open"
                 x-transition
                 x-trap.noscroll="open"
                 @click.outside="open = false">
                <h3 class="confirm-dialog__title" id="{{ $id }}-title">{{ $title }}</h3>
                <p class="confirm-dialog__message">{{ $message }}</p>
                <div class="confirm-dialog__actions">
                    <button type="button" class="btn btn--secondary" @click="open = false">{{ $cancelText }}</button>
                    <div {{ $attributes }}>
                        {{ $slot }}
                        <button type="submit"
                                class="btn btn--primary"
                                @click="open = false">
                            {{ $confirmText }}
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </template>
</div>
