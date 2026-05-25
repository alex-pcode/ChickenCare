<x-fp-skeleton.frame>
    <div class="fp-skeleton__header">
        <div class="fp-skeleton__block fp-skeleton__block--eyebrow"></div>
        <div class="fp-skeleton__block fp-skeleton__block--hero"></div>
        <div class="fp-skeleton__block fp-skeleton__block--body"></div>
    </div>

    <div class="fp-skeleton__grid">
        @for ($i = 0; $i < 4; $i++)
            <div class="fp-skeleton__card"></div>
        @endfor
    </div>

    <div class="fp-skeleton__panels">
        @for ($i = 0; $i < 2; $i++)
            <div class="fp-skeleton__panel"></div>
        @endfor
    </div>
</x-fp-skeleton.frame>
