<x-fp-skeleton.frame>
    <div class="fp-skeleton__hero-row">
        <div class="fp-skeleton__panel fp-skeleton__panel--hero-media"></div>
        <div class="fp-skeleton__panel fp-skeleton__panel--hero-status"></div>
    </div>

    <div class="fp-skeleton__strip">
        @for ($i = 0; $i < 3; $i++)
            <div class="fp-skeleton__chip"></div>
        @endfor
    </div>

    <div class="fp-skeleton__panel fp-skeleton__panel--form-tall"></div>
</x-fp-skeleton.frame>
