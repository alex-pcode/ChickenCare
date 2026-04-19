<div class="toast-container"
     x-data="{
         message: '',
         type: 'success',
         visible: false,
         show(msg, t) {
             this.message = msg;
             this.type = t ?? 'success';
             this.visible = true;
             setTimeout(() => this.visible = false, 4000);
         }
     }"
     @toast:success.window="show($event.detail?.value ?? $event.detail?.message ?? 'Done.', 'success')"
     @toast:error.window="show($event.detail?.value ?? $event.detail?.message ?? 'Something went wrong.', 'error')"
     @flock:success.window="show($event.detail?.value ?? $event.detail?.message ?? 'Updated.', 'success')"
     @flock:error.window="show($event.detail?.value ?? $event.detail?.message ?? 'Something went wrong.', 'error')"
     @flock:changed.window="show($event.detail?.message ?? 'Flock updated.', $event.detail?.type ?? 'success')">
    <div class="toast"
         x-show="visible"
         x-cloak
         :class="'toast--' + type"
         :role="type === 'success' ? 'status' : 'alert'"
         aria-live="polite"
         x-transition:enter="toast--enter"
         x-transition:enter-start="toast--enter-start"
         x-transition:enter-end="toast--enter-end"
         x-transition:leave="toast--leave"
         x-transition:leave-start="toast--leave-start"
         x-transition:leave-end="toast--leave-end">
        <span x-text="message"></span>
    </div>
</div>
