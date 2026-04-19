@props(['label' => 'Save', 'variant' => 'primary'])

<button type="submit"
    x-data="{ submitting: false, success: false }"
    x-init="
        const form = $el.closest('form');
        if (!form) return;
        const isHtmx = ['hx-post','hx-get','hx-put','hx-patch','hx-delete'].some(a => form.hasAttribute(a));
        form.addEventListener('htmx:beforeRequest', () => { submitting = true; success = false; });
        form.addEventListener('htmx:afterRequest', (e) => {
            submitting = false;
            if (e.detail.successful) {
                success = true;
                setTimeout(() => { success = false; }, 2500);
            }
        });
        if (!isHtmx) {
            form.addEventListener('submit', () => { submitting = true; });
        }
    "
    :disabled="submitting || success"
    :class="{ 'submit-button--submitting': submitting, 'submit-button--success': success }"
    {{ $attributes->merge(['class' => 'submit-button shiny-cta']) }}>
    <template x-if="submitting">
        <span class="submit-button__content">
            <span class="submit-button__spinner" aria-hidden="true"></span>
            <span>Saving…</span>
        </span>
    </template>
    <template x-if="!submitting && success">
        <span class="submit-button__content">
            <span aria-hidden="true">✓</span>
            <span>Saved!</span>
        </span>
    </template>
    <template x-if="!submitting && !success">
        <span>{{ $label }}</span>
    </template>
</button>
