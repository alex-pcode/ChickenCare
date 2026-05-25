<x-fp-skeleton.frame>
    <div class="fp-skeleton__hero-row">
        <div class="fp-skeleton__panel fp-skeleton__panel--hero-media"></div>
        <div class="fp-skeleton__panel fp-skeleton__panel--hero-status"></div>
    </div>

    <div class="fp-skeleton__panel fp-skeleton__panel--form"></div>

    <div class="fp-skeleton__grid">
        @for ($i = 0; $i < 4; $i++)
            <div class="fp-skeleton__card"></div>
        @endfor
    </div>

    <div class="fp-skeleton__panel fp-skeleton__panel--table"></div>
</x-fp-skeleton.frame>
