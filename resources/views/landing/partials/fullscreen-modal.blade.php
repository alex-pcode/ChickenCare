<div
    class="landing__fullscreen"
    x-data="fullscreenModal()"
    x-show="open"
    x-cloak
    x-transition:enter="landing__fullscreen-enter"
    x-transition:enter-start="landing__fullscreen-enter-start"
    x-transition:enter-end="landing__fullscreen-enter-end"
    x-transition:leave="landing__fullscreen-leave"
    x-transition:leave-start="landing__fullscreen-leave-start"
    x-transition:leave-end="landing__fullscreen-leave-end"
    @open-fullscreen.window="openModal($event.detail)"
    @keydown.escape.window="close()"
    @keydown.tab.window="trapFocus($event)"
    @click="close()"
    role="dialog"
    aria-modal="true"
    :aria-label="alt"
>
    <button class="landing__fullscreen-close" @click.stop="close()" aria-label="Close fullscreen image" x-ref="closeButton">
        <svg width="24" height="24" viewBox="0 0 24 24" fill="none"><path d="M18 6L6 18M6 6l12 12" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
    </button>

    <img
        class="landing__fullscreen-img"
        :src="src"
        :alt="alt"
        @click.stop
    >
    <p class="landing__fullscreen-title" x-text="title" x-show="title"></p>
    <p class="landing__fullscreen-hint">Press ESC to close or click outside</p>
</div>
