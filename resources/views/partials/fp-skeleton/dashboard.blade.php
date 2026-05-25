<x-fp-skeleton.frame>
    <div class="fp-skeleton__header">
        <div class="fp-skeleton__block fp-skeleton__block--eyebrow"></div>
        <div class="fp-skeleton__block fp-skeleton__block--hero"></div>
        <div class="fp-skeleton__block fp-skeleton__block--body fp-skeleton__block--body-wide"></div>
    </div>

    <div class="fp-skeleton__grid">
        @for ($i = 0; $i < 4; $i++)
            <div class="fp-skeleton__card"></div>
        @endfor
    </div>

    <div class="fp-skeleton__panel fp-skeleton__panel--chart"></div>

    <div class="fp-skeleton__grid fp-skeleton__grid--three">
        @for ($i = 0; $i < 3; $i++)
            <div class="fp-skeleton__card"></div>
        @endfor
    </div>

    <div class="fp-skeleton__panel"></div>
</x-fp-skeleton.frame>
