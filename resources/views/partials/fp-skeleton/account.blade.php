<x-fp-skeleton.frame>
    <div class="fp-skeleton__header">
        <div class="fp-skeleton__block fp-skeleton__block--eyebrow"></div>
        <div class="fp-skeleton__block fp-skeleton__block--hero"></div>
    </div>

    <div class="fp-skeleton__strip">
        @for ($i = 0; $i < 4; $i++)
            <div class="fp-skeleton__chip"></div>
        @endfor
    </div>

    <div class="fp-skeleton__panel fp-skeleton__panel--form-tall"></div>
    <div class="fp-skeleton__panel fp-skeleton__panel--form"></div>
</x-fp-skeleton.frame>
