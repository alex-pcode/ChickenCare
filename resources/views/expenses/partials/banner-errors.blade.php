<div x-show="errors.length > 0"
     x-cloak
     class="flash flash--error"
     role="alert"
     aria-live="assertive">
    <div>Please fix the following errors:</div>
    <div x-text="errors.join(', ')"></div>
</div>
